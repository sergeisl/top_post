<?
class Basket {

    public static function is() {
        return isset($_COOKIE['Basket']);
    }

    /** Возвращает true, усли переданный товар уже есть в корзине
     * @param $tov
     * @return bool
     */
    public static function in($tov) {
        if(isset($_COOKIE['Basket'])) {
            $ar=js_decode($_COOKIE['Basket']);
            return isset($ar[$tov]);
        }
        return false;
    }


    public static function short($f=null) {
        if(is_null($f))
            return isset($_COOKIE['cart_expand'])&& $_COOKIE['cart_expand']=='1';
        elseif($f){
            @setcookie("cart_expand", 1, time()+60*60*24*30, CookiePath, CookieDomain);
            return true;
        }else{
            //@setcookie("cart_expand", '', time()-60*60*24*30, CookiePath, CookieDomain);
            @setcookie("cart_expand", 2, time()+60*60*24*30, CookiePath, CookieDomain);
            return false;
        }
    }

    public static function read() {
        if(isset($_COOKIE['Basket'])) return js_decode($_COOKIE['Basket']);
        elseif(($zakaz=DB::Select('zakaz','user="'.User::id().'" and status=0 ORDER BY id DESC'))){
            $result = DB::sql('SELECT * from '.db_prefix.'zakaz2 WHERE  zakaz="'.$zakaz['id'].'"');
            $ar=[];
            while(($zakaz = DB::fetch_assoc($result))) $ar[$zakaz['tovar']]=$zakaz['kol'];
            self::write($ar);
            return $ar;
        }
        return [];
    }

    public static function write($ar) {
        if(empty($ar)){
            @setcookie("Basket", $_COOKIE['Basket']='', time()-60*60*24*30, CookiePath, CookieDomain);
            if (($zakaz = DB::Select('zakaz', 'user="' . User::id() . '" and status=0 ORDER BY id DESC'))) {
                $zakaz_id = $zakaz['id'];
                DB::sql("DELETE FROM " . db_prefix . "zakaz2 WHERE `zakaz`='" . $zakaz_id . "'");
            }
        }else {
            @setcookie("Basket", $_COOKIE['Basket'] = json_encode($ar), time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
            // если заказ уже в базе, - исправить в базе
            if (($zakaz = DB::Select('zakaz', 'user="' . User::id() . '" and status=0 ORDER BY id DESC'))) {
                $zakaz_id = $zakaz['id'];
                $zakaz = DB::Select2Array('zakaz2', 'zakaz="' . $zakaz_id . '"');
                foreach ($ar as $tovar => $kol) {
                    $tovar = new Tovar($tovar);
                    if (empty($tovar) || empty($tovar->name)) continue;
                    DB::sql("INSERT INTO `" . db_prefix . "zakaz2` (`zakaz`,`tovar`,`kol`,`price`) VALUES ('" . $zakaz_id . "', '" . $tovar->id . "', '" . $kol . "', '" . $tovar->price . "') " .
                        " ON DUPLICATE KEY UPDATE `kol`='" . $kol . "', `price`='" . $tovar->price . "'");
                }
                foreach ($zakaz as $row) {
                    if (empty($ar[$row['tovar']])) DB::sql("DELETE FROM " . db_prefix . "zakaz2 WHERE `zakaz`='" . $zakaz_id . "' and tovar='" . $row['tovar'] . "' LIMIT 1");
                }
            }
        }
    }
    /** Добавление товара в корзину
     * @param $id - код товара
     * @param int $kol - количество
     */
    public static function Add($id,$kol=1) {
        $id=intval($id);
        $ar=self::read();
        $ar[$id]=(isset($ar[$id])?intval($ar[$id]):0)+$kol;
        self::write($ar);
    }

    /**
     * Удаление товара из корзины, если id=0 - очищает корзину
     */
    public static function Del($id=0) {
        $id=intval($id);
        if($id>0){
            $ar=self::read();
            unset($ar[$id]);
            self::write($ar);
        }else{
            @setcookie("Basket", '', time()-60*60*24*30, CookiePath, CookieDomain);
        }
    }

    /**
     * Отображение корзины на экран
     */
    static function show(){
        $ar=self::read();
        $countForDiscount=$count=$summ=0;
        $user=new User(User::id()); //        $ret.=", покупатель: ".$user->url;

        $ret='';
        foreach($ar as $id => $zakaz){
            $tovar=new Tovar($id); if(empty($tovar)||empty($tovar->name)){unset($ar[$id]);self::write($ar);continue;}
            $price=($user->adm==uADM_OPT?$tovar->price2:$tovar->price)*$zakaz;
            $priceStr=outSumm0($price);
            $ret.="\t<li class='cart_block_big'><i>".$zakaz."</i><i>x</i><a title='".$tovar->show_name."' href='".$tovar->url."'>".substr($tovar->name,0,14-strlen($priceStr))."&#8230;</a>".
                 "<a class='icon cart_remove confirm' href='/api.php?basket_del=".$tovar->id."' title='Удалить товар из корзины' onclick=\"return ajaxLoad('',this.href)\"></a>".
                 "<span class='price'".($price!=$tovar->price*$zakaz?" title='Стоимость без скидки ".($tovar->price*$zakaz)."руб.'":"").">".$priceStr." руб.</span></li>\n";
            $summ+=$price;
            $count+=$zakaz;
            if(!isset($tovar->category[5]))$countForDiscount+=$zakaz; // кроме сопутствующих товаров
        }
        if($user->adm==uADM_OPT){// оптовик
            $discount=0;
        }elseif($user->discount0>0){ // у клиента персональная скидка
            $discount=$user->discount0;
            $summ=round($summ*(100-$discount)/100,0);
        }elseif($summ>=discount_from||$countForDiscount>=discount_count){
            $discount=discount_proc;
            $summ=round($summ*(100-$discount)/100,0);
        }else{
            $discount=0;
        }

// если есть неоплаченные заказы написать о них
        $result = DB::sql('SELECT * from '.db_prefix.'zakaz WHERE  user="'.User::id().'" and status in( 1, 3)');
        $msg='';
        while (($zakaz = DB::fetch_assoc($result))){
            if($zakaz['status']==0){if(empty($msg))$msg="<a href='/user/zakaz.php'>У Вас есть подготовленный, но не отправленный заказ.</a>";}
            elseif($zakaz['status']==1){if(empty($msg))$msg="<a href='/user/zakaz.php'>У Вас есть подготовленный заказ, ожидающий подтверждения.</a>";}
            elseif($zakaz['status']==3){$msg="<a href='/user/zakaz.php'>У Вас есть неоплаченный заказ.</a>"; break;}
        }
        return "<div class='cart".(self::short()?'':' expand')."'>\n".
        "<h3><span class='collapse_expand' onclick='mclick(\"cart\",\"expand\")'>Корзина</span>  <span class='icon cart_ww'></span></h3>\n".
        "<ul class='cart_block'>\n".
        ($msg?"<li>".nl2br($msg)."</li>\n":"").
        ($count?
            "\t<li class='cart_block_small'>".$count." товар".num2word($count,array('','а','ов'))." на ".outSumm0($summ)." руб.</li>\n":
            "\t<li> нет товаров </li>\n").
        $ret.
        "\t<li><span class='cart_block_big'>".
        ($discount?" Скидка <b>-".$discount."%</b><br>":"").
        ($summ>=delivery_from?"<small> Доставка <b>бесплатно</b></small><br>":"").
        "Всего <b>".outSumm0($summ)." руб</b></span>\n".
        ($summ>0&&$summ<Min_zakaz?"\t<span class='small red'>Не набрана минимальная сумма ".Min_zakaz."руб.</span>\n":
        "\t<form action='/user/zakaz.php?add' method='POST'><input ".($summ<Min_zakaz?'disabled ':'')."type='submit' value='Оформить заказ' class='button'></form></li>\n").
        "</ul>\n".
        "</div>\n";

/*
          return "<div class='cart".(self::short()?'':' hide')."'>\n".
        "<h3><span class='collapse_expand' onclick='mclick(\"cart\");deleteCookie(\"Basket_short\");'>Корзина</span>  <span class='icon cart_ww'></span></h3>\n".
        "<ul class='cart_block'>\n".
        ($count?
            "<li>".$count." товар".num2word($count,array('','а','ов'))." на ".outSumm0($summ)." руб.</li>\n":
            $ret).
        "<li><form action='/zakaz.php?add' method='POST'><input type='submit' value='Оформить заказ' class='button'></form></li>\n".
        "</ul><br>\n".
        "</div>\n".
        "<div class='cart expand".(self::short()?' hide':'')."'>\n".
        "<h3><span class='collapse_expand' onclick='mclick(\"cart\");setCookie(\"Basket_short\", \"1\");'>Корзина</span>  <span class='icon cart_ww'></span></h3>\n".
        "<ul class='cart_block cart_block_expand'>\n".
        $ret.
        "<li>".($discount?" Скидка <b>-".$discount."%</b><br>":"").
        ($summ>=delivery_from?"<small> Доставка <b>бесплатно</b></small><br>":"").
        "Всего <b>".outSumm0($summ)." руб</b>\n".
        "<br class='clear'><form action='/zakaz.php?add' method='POST'><input type='submit' value='Оформить заказ' class='button'></form></li>\n".
        "</ul><br>\n".
        "</div>\n";
*/
    }
}

