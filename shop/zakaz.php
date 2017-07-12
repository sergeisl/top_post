<?
class Zakaz {
    const db_prefix=db_prefix;
    static public $ar_info=array('comment', 'answer', 'forma', 'time_ok', 'time_end', 'time_pay', 'ticket', 'ok_code', 'failure_code', 'manager', 'delivery', 'delivery_address','delivery_outpost'); // переменные, которые сохраняются в поле info БД
    static public $status_name=array(
        0=>'предварительный набор товара для заказа',
        1=>'отправлен на согласование менеджеру',
        2=>'отклонен менеджером',
        3=>'подтвержден менеджером, ожидает оплаты',
        4=>'оплачен. ожидает отгрузки',
        5=>'отгружен.',
        6=>'отказ клиента');
    static public $_forma_name=array(
        0=>'',
        1=>'Выписать счет на юридическое лицо для оплаты за безналичный расчет',
        2=>'Оплатить с помощью банковской карты',
        3=>'Оплатить с помощью ЭПС(WebMoney,Yandex.Деньги,Qiwi)',
        4=>'Оплатить наличными в пункте выдачи');

    public static function Add() { // добавляет корзину в заказ
        $ar=Basket::read();
        if(count($ar)<1)return false;
        if(($zakaz=DB::Select('zakaz','user="'.User::id().'" and status=0 ORDER BY id DESC'))){
            $id=$zakaz['id'];
        }else{
            DB::sql("INSERT INTO `".self::db_prefix."zakaz` (`user`,`time`) VALUES ('".User::id()."', '".date('Y-m-d H:i:s')."')");
            $id=DB::id();
        }
        foreach($ar as $tovar => $zakaz){
            $tovar=new Tovar($tovar); if(empty($tovar)||empty($tovar->name))continue;
            DB::sql("INSERT INTO `".self::db_prefix."zakaz2` (`zakaz`,`tovar`,`kol`,`price`) VALUES ('".$id."', '".$tovar->id."', '".$zakaz."', '".$tovar->price."') ".
                " ON DUPLICATE KEY UPDATE `kol`='".$zakaz."', `price`='".$tovar->price."'");
        }
        //Basket::Del();
        return $id;
    }

    public static function WriteHeader($zakaz){
        $add='';
        $zakaz['info']=array(); // очищаю
        if(!empty($zakaz['delivery_outpost'])&&!empty($zakaz['delivery'])) unset($zakaz['delivery_outpost']); // удаляю откуда самовывоз если указана доставка
        foreach ($zakaz as $key => $value){
            if(in_array($key,array('id','user','time','info'))){

            }elseif(in_array($key,self::$ar_info)){
                $zakaz['info'][$key]=$value;
            }elseif(in_array($key,array('status'))){
                $add.=','.$key.'="'.addslashes($value).'"';
            }
        }
        if(count($zakaz['info']))$add.=',info="'.addslashes(js_encode($zakaz['info'])).'"';
        if($add)DB::sql('UPDATE `'.self::db_prefix.'zakaz` SET '.substr($add,1).' WHERE id="'.intval($zakaz['id']).'"');
        //if($add2)DB::sql('UPDATE `".self::db_prefix."zakaz2` SET '.substr($add,1).' WHERE zakaz="'.intval($zakaz['id']).'" and tovar="'.$.'"');
        if($zakaz['status']==1)self::SendAdminMail($zakaz['id']);
    }

    public static function SendAdminMail($zakaz){
        $zakaz=self::Get($zakaz);
        $user=new User($zakaz['user']); //        $ret.=", покупатель: ".$user->url;
        # Вытаскиваем из БД все записи, у которых adm >= uADM_MANAGER
        $query = DB::sql('SELECT * FROM '.db_prefix.'users WHERE adm>='.uADM_MANAGER);
        if(DB::num_rows($query)<1)@mail(AdminMail, "Нет менеджеров, но есть заказ в ".Get::SERVER_NAME(), "Нет менеджеров, но есть заказ в ".Get::SERVER_NAME(), "From: mailer@".preg_replace("/www\./i","",Get::SERVER_NAME())."\nContent-Type: text/html; charset=windows-1251");
        while (($data = DB::fetch_assoc($query))){
            $reciver=new User($data);
            $body="Здравствуйте, ".$reciver->user_name."!<br><br>
                    В магазине ".Get::SERVER_NAME()." клиент ".$user->url." сделал следующие заказы:
                     <table cellpadding=4><tr class='cl".$zakaz['id']." expand'><th>№</th><th>Товар</th><th>Кол-во</th><th>Сумма</th><th>&nbsp;</th></tr>\n".
                str_replace("<a href='","<a href='http://".Get::SERVER_NAME(),Zakaz::show($zakaz,array('num'=>1,'user'=>1,'mail'=>1)));
            $body="<html><body>\n".$body."\n</body></html>";
            mail($reciver->mail, "Заказ в ".Get::SERVER_NAME(), $body, "From: mailer@".preg_replace("/www\./i","",Get::SERVER_NAME())."\nContent-Type: text/html; charset=windows-1251");
        }
        message("Ваш заказ отправлен менеджеру.<br>Информация о Вашем заказе доступна в разделе <a href=\"/user/zakaz.php\">мои заказы</a>.");
        //header("location: ".$GLOBALS['http'].'://'.Get::SERVER_NAME()'/shop.php'); mysql_close(); exit;
    }

    /** возвращает массив с информацией о заказе
     * @param integer|array $zakaz
     * @return array|null
     */
public static function Get($zakaz){
    if(!$zakaz)return null;
    elseif(is_array($zakaz)){

    }else{
        $zakaz=DB::Select("zakaz",intval($zakaz));
        if(!$zakaz)return null;
    }
    if(isset($zakaz['info'])&&$zakaz['info']&&!is_array($zakaz['info'])){
        //print_r(json_decode($zakaz['info']));
        $zakaz=array_merge($zakaz, js_decode($zakaz['info']));
        unset($zakaz['info']);
    }
    if(!isset($zakaz['forma']))$zakaz['forma']=0; // форма оплаты не выбрана

    $user=new User($zakaz['user']); //        $ret.=", покупатель: ".$user->url;
    if($user->adm==uADM_OPT){// оптовик
        $zakaz['discount']=0;
        $zakaz['count']=0;
        $result=DB::sql('SELECT SUM(kol*price) as s from '.db_prefix.'zakaz2 WHERE zakaz='.$zakaz['id']);
        if(($row2=DB::fetch_assoc($result)))
            $zakaz['summ']=$row2['s'];
    }else{
        //$zakaz['user']=new User($zakaz['user']);
        $result=DB::sql('SELECT SUM(kol*price) as s, SUM(IF(price>50,kol,0)) as c from '.db_prefix.'zakaz2 WHERE zakaz='.$zakaz['id']);
        //            if(!isset($tovar->category[5]))$countForDiscount+=$zakaz;
        if(($row2=DB::fetch_assoc($result))){
            $zakaz['summ']=$row2['s'];
            $zakaz['count']=$row2['c'];
            if($user->discount0>0){ // у клиента персональная скидка
                $zakaz['discount']=$user->discount0;
                $zakaz['summ']=round($zakaz['summ']*(100-$zakaz['discount'])/100,0);
            }elseif( $zakaz['summ']>=discount_from || $zakaz['count']>=discount_count ){
                $zakaz['discount']=discount_proc;
                $zakaz['summ']=round($zakaz['summ']*(100-$zakaz['discount'])/100,0);
            }else{
                $zakaz['discount']=0;
            }

        }
    }
    if(!empty($zakaz['summ']) && $zakaz['summ']<delivery_from && isset($zakaz['delivery']) && $zakaz['delivery']){ // 0-самовывоз, 1-доставка бесплатно, 2-доставка платно
        $zakaz['summ']+=delivery_cost;
        $zakaz['delivery_cost']=delivery_cost;
    }else {
        $zakaz['delivery']=0;
        $zakaz['delivery_cost']=0;
    }

    foreach($zakaz as $key => $value)if(is_string($zakaz[$key])) $zakaz[$key]=str_replace('"',"'",$value);
        return $zakaz;
}

    /** отображение заказа
     * @param $zakaz
     * @param bool|array $options 'num'|'zakaz_button'
     * @return string
     */
    static function show($zakaz, $options=false){
        $zakaz=self::Get($zakaz);
        $user=new User($zakaz['user']);
        $domain=(isset($options['mail'])?$GLOBALS['http'].'://'.Get::SERVER_NAME():"");
        //echo nl2br(str_replace(' ',' ',print_r($zakaz,!0)));
        $class="cl".$zakaz['id'].(self::short($zakaz['id'])?'':' expand');
        $ret="\n<tr class='tr_zakaz ".$class."'><td colspan='5'>".
            (isset($options['mail'])?"":"<a class='r' href='/api.php?PrintZakaz=".$zakaz['id']."' onclick='return !window.open(this.href)'>распечатать</a>").
            (empty($options['num'])?"":"№<span class='blue b'>".$zakaz['id']."</span> от ").time2html($zakaz['time'])."<br>";
        if(!empty($options['user']) && User::is_admin()){
            $ret.="<select onchange=\"if(confirm('Сменить статус?'))ajaxLoad(this,'/user/adm/zakaz.php?setstatus='+this.options[this.selectedIndex].value+'&id=".$zakaz['id']."');return false;\">";
            foreach(self::$status_name  as $key => $value)
                $ret.="<option value=\"".$key."\"".($zakaz['status']==$key?' selected':'').">".$value."</option>";
            $ret.="</select>";
            $ret.="\n<a class='icon cart_remove confirm' href='".$domain."/api.php?tbl=zakaz&del=".$zakaz['id']."' title='Удалить заказ' onclick=\"if(confirm('Уведомление не высылается. Удалить весь заказ?'))ajaxLoad('',this.href);return false;\"></a>";
        }else{
            $ret.=" <span class='st".$zakaz['status']."'>".
                @Zakaz::$status_name[$zakaz['status']];
        }
        $ret.=($zakaz['status']==3?" до ".time2html($zakaz['time_end']):"").
            "</span>";
        if(!empty($options['user']) && $user )
            $ret.="<br>\nПокупатель: ".$user->url.($user->url=='нет'?',код:'.$zakaz['user']:'');
        if($zakaz['status']==0){

        }elseif($zakaz['status']==3&&$zakaz['forma']==2 && date('Y-m-d H:i:s')<$zakaz['time_end']){
            $ret.=",\n<a".($zakaz['user']==User::id()?" class='button'":"")." href='".$domain."/api.php?pay=".$zakaz['id']."' title='Оплата банковской картой'>Оплатить</a>";
        }elseif($zakaz['status']==3&&$zakaz['forma']==3 && date('Y-m-d H:i:s')<$zakaz['time_end']){
            $ret.=",\n<br>Оплатите сумму заказа на один из кошельков ЭПС(WebMoney,Yandex.Деньги,Qiwi), указанных в <a class='button' href='".$domain."/contact.php'>Контакты</a>";
        }elseif($zakaz['forma']==2){
            if($zakaz['status']==4)$ret.=",\nоплачен банковской картой";
            else $ret.=",\nОплата банковской картой";
        }elseif($zakaz['forma']==1){
            $ret.=",\nОплата по счету";
        }elseif($zakaz['status']==3 && date('Y-m-d H:i:s')>$zakaz['time_end']){
            $zakaz['status']=6; DB::sql("UPDATE ".db_prefix."zakaz SET status='6' WHERE id='".$zakaz['id']."' LIMIT 1");
            $ret.=", <span class='st2'>Истекло время оплаты!</span>";
        }

        $ret.=($zakaz['status']==4?"<span class='green'> ".time2html($zakaz['time_pay'])."</span>":"");

        if(!empty($options['user']) && User::is_admin()){
            if(!empty($zakaz['delivery'])) $ret.=", Доставка по адресу: ".(empty($zakaz['delivery_address'])?'не указан':$zakaz['delivery_address']);
            elseif(!empty($zakaz['delivery_outpost'])) $ret.=", Самовывоз из ".DB::GetName('shop',$zakaz['delivery_outpost']);

            if($zakaz['status']==1)
                $ret.=",\n<a class='red' href='".$domain.ADM_ZAKAZ."?no=".$zakaz['id']."' title=\"Отклонить\" onclick=\"if(confirm('Отклонить?'))ajaxLoad(this,this.href);return false;\">отклонить?</a>,".
                "<a class='green' href='".$domain.ADM_ZAKAZ."?yes=".$zakaz['id']."' title=\"Подтвердить\" onclick=\"if(confirm('Подтвердить?'))ajaxLoad(this,this.href);return false;\">подтвердить?</a>";
            elseif($zakaz['status']==3)
                $ret.=",\n<a href='".$domain.ADM_ZAKAZ."?long=".$zakaz['id']."' title=\"Продлить\" onclick=\"if(confirm('Продлить?'))ajaxLoad(this,this.href);return false;\">продлить на сутки?</a>";
            elseif($zakaz['status']==4) // оплачен
                $ret.=",\n<a href='".$domain.ADM_ZAKAZ."?id=".$zakaz['id']."&setstatus=5' title=\"Отгружен\" onclick=\"if(confirm('Отгружен?'))ajaxLoad('',this.href);return false;\">отгружен?</a>";
        }

        //if($zakaz['delivery']) $ret.=", Доставка";
        $ret.="<span class='collapse_expand' onclick='mclick(\"cl".$zakaz['id']."\",\"expand\");'></span>".
              "</td></tr>";
        if(isset($zakaz['comment'])&&$zakaz['comment'])
            $ret.="\n<tr class='".$class."'><td>&nbsp;</td><td colspan=4><i>".$zakaz['comment']."</i></td></tr>";
        $result2 = DB::sql('SELECT * from '.db_prefix.'zakaz2 WHERE zakaz="'.$zakaz['id'].'"');
        $i=1; $summ=0;
        while (($data = DB::fetch_assoc($result2))) {
            $tovar=new Tovar($data['tovar']); if(empty($tovar)||empty($tovar->name))continue;
            $price=($user && $user->adm==uADM_OPT ? $tovar->price2 : $tovar->price );
            $ret.="\n<tr id='id".$data['id']."' class='".$class."'>".
                "<td>".($i++).".</td>".
                "<td>".(User::is_admin()?"<span class='blue'>".$tovar->kod_prodact."</span> ":"").(isset($options['mail'])?$tovar->Murl:$tovar->Aurl)."</td>".
                "<td>".(!isset($options['mail']) && (User::is_admin()||!$zakaz['status']) ? "<input value='".$data['kol']."' class='edit' size='3' name='kol' data-tbl='zakaz2' onChange='SendInput(this)' >" : $data['kol'])."</td>".
                "<td>".outSumm0($data['kol']*$price)."</td>";
            $summ+=$data['kol']*$price;
            if($zakaz['status']==0){
                $ret.="\n<td><a class='icon cart_remove confirm' href='".$domain."/api.php?tbl=zakaz2&del=".$data['id']."' title='Удалить товар из заказа' onclick=\"if(confirm('Удалить?'))ajaxLoad('',this.href);return false;\"></a></td>";
            }else{
                $ret.="\n<td>&nbsp;</td>";
            }
            $ret.="\n</tr>";
        }
        $zakaz['summ']=$summ;
        // todo указать скидку !!!
        if(!empty($zakaz['delivery']))$ret.="\n<tr class='delivery ".$class."'><td>&nbsp;</td><td>Доставка</td><td colspan=2>".
            ($zakaz['delivery_cost']>0? $zakaz['delivery_cost'] : "бесплатно")."</td><td>&nbsp;</td></tr>";

        if(!empty($zakaz['discount'])){
            $ret.="\n<tr class='delivery ".$class."'><td>&nbsp;</td><td colspan='4'>Сумма заказа ".outSumm0($zakaz['summ'])." руб.<br>Скидка <b>". $zakaz['discount'] ."%</b></td></tr>";
            $zakaz['summ']=round($zakaz['summ'] * (100-$zakaz['discount'])/100,0);
        }

        $ret.="\n<tr class='itog ".$class."'><td colspan=5>Итого на сумму ".
            outSumm0($zakaz['summ'])." руб.".
            ($zakaz['status']==0 && !empty($options['zakaz_button']) ? "<br><a href='".$domain."/user/zakaz.php?add' class='button'>Оформить заказ</a>" : "" ).
            "</td></tr>";
    return $ret;
    }

    /** Удаление заказа. Удалить может или админ или владелец. Уведомление не высылается
     * @param $zakaz
     * @param int $id
     * @return bool|string
     */
    static function Del($zakaz){
        $zakaz=self::Get($zakaz); if(!$zakaz)return false;
        if(!User::is_admin() && (User::id()!=$zakaz['user'] || $zakaz['status']))return false; // удалять нельзя
        DB::log('zakaz', $zakaz['id'], 'удаление',$zakaz); // добавить информацию из sale
        DB::sql("DELETE FROM `".self::db_prefix."zakaz2` WHERE zakaz='".$zakaz['id']."'");
        DB::sql("DELETE FROM `".self::db_prefix."zakaz` WHERE id='".$zakaz['id']."' LIMIT 1");
        if(DB::affected_rows()<1)return "Не удалил id:".$zakaz['id'];
        //DB::sql("alter table `".self::db_prefix."zakaz2` auto_increment=1;");
        //DB::sql("alter table `".self::db_prefix."zakaz` auto_increment=1;");
        return true;
    }
    /** Удаление товара из заказа
     * @param int $id
     * @return bool
     */
    static function DelTovar($id=0){
        $zakaz = DB::Select('zakaz2','id="'.$id.'"');
        //print_r($zakaz);
        $zakaz=self::Get($zakaz['zakaz']); if(!$zakaz){error('Нет такого заказа!');return false;}
        if(!User::is_admin() && (User::id()!=$zakaz['user'] || $zakaz['status'])){
            if($zakaz['status'])Out::error('На этом этапе отказаться от заказа нельзя! Свяжитесь с вашим менеджером');
            elseif(User::id()!=$zakaz['user'])error('Это не ваш заказ!');
            //echo User::id().'~'.$zakaz['user']['id'];
            return false;
        } // удалять нельзя
        DB::Delete("zakaz2",$id);
        DB::log('zakaz2', $id, 'удаление товара из заказа');
        if(!DB::Select('zakaz2','zakaz='.$zakaz['id'])){
            DB::Delete('zakaz',$zakaz['id']);
            DB::log('zakaz', $zakaz['id'], 'удаление последнего товара в заказе');
        }
        return true;
    }

    public static function short($id) {
            return isset($_COOKIE['cl'.$id.'_expand'])&& $_COOKIE['cl'.$id.'_expand']=='1';
    }

    public static function cron() {
        $result = DB::sql('SELECT * from '.db_prefix.'zakaz WHERE status between 1 AND 4');
        while (($row = DB::fetch_assoc($result)))
            if($row['status']==3 && date('Y-m-d H:i:s')>$row['time_end']){
                DB::sql("UPDATE ".db_prefix."zakaz SET status=6 WHERE id='".$row['id']."' LIMIT 1");
                self::SendUserMail($row, "Ваш товар был зарезервирован, но НЕ оплачен Вами.<br>\n".
                    "Время, резерва истекло и товар может быть продан другому покупателю.<br>\n".
                    "Если Вам ещё нужен этот или другой товар нашего магазина Вы можете сделать новый заказ.<br>\n");
        }
    }

    /** отправка уведомления заказчику
     * @param integer|array $zakaz
     * @param string $body
     */
    public static function SendUserMail($zakaz, $body){
        $zakaz=self::Get($zakaz);
        $manager=($zakaz['manager']? User::_GetVar($zakaz['manager'],'mail') : '' );
        $user=new User($zakaz['user']);
         $str="Здравствуйте, ".$user->user_name."!<br><br>".
        "В магазине <a href='".$GLOBALS['http']."://".Get::SERVER_NAME()."/'>".Get::SERVER_NAME()."</a> Вы сделали <a href='".$GLOBALS['http']."://".Get::SERVER_NAME()."/user/zakaz.php'>заказ</a>, ".$body;
        User::_mail($user->mail, "Заказ в ".Get::SERVER_NAME(), $str, ($manager?"CC: <".$manager.">":''));
    }

    public static function order_step(){
            echo '
        <ul class="step">
        <li class="step_current"> Выбор </li>
        <li class="step_todo"> Авторизация </li>
        <li class="step_todo"> Оплата </li>
        <li class="step_end"> Доставка </li>
    </ul>';
    }

    /** Печать заказа на принтере
     * @param $zakaz
     * @return string
     */
    static function PrintZakaz($zakaz){
        $zakaz=self::Get($zakaz);
        if(!User::is_admin() && $zakaz['user']!=User::id())die('Нет доступа к заказу!');
        $ret=<<<END_TEXXT
Внимание, изменились банковские реквизиты!
ПОСТАВЩИК: ИП Исаева Лариса Владимировна
Юридический адрес: 344019, РФ, Ростовская обл., г. Ростов-на-Дону, ул. 10 линия, д. 8
Местонахождение: 344038, РФ, Ростовская обл., г. Ростов-на-Дону, ул. М. Нагибина, д. 27
Гос. регистрационный номер: 312619534000020
РЕКВИЗИТЫ ПОСТАВЩИКА: ИНН 616300086267
Банк: ОАО КБ "Центр-Инвест" г. Ростов-на-Дону
р/с 40802810700000015739, БИК 046015762, к/с 30101810100000000762

Счет N Е00147
От 11 Мая 2016 г.

END_TEXXT;

        $ret=nl2br($ret)."Заказ № ".$zakaz['id']." от ".time2html($zakaz['time'])."<br>";
        $ret.=" ".@Zakaz::$status_name[$zakaz['status']];
        $ret.=($zakaz['status']==3?" до ".time2html($zakaz['time_end']):"");

        if($zakaz['status']==0){

        }elseif($zakaz['status']==3&&$zakaz['forma']==2 && date('Y-m-d H:i:s')<$zakaz['time_end'])
            $ret.=",\nОжидает оплаты банковской картой";
        elseif($zakaz['forma']==2)
            if($zakaz['status']==4)$ret.=",\nоплачен банковской картой";
            else $ret.=",\nОплата банковской картой";
        elseif($zakaz['forma']==1)
            $ret.=",\nОплата по счету";
        elseif($zakaz['status']==6){
            $ret.=", Истекло время оплаты!";
        }
        if($zakaz['status']==4)$ret.=" ".time2html($zakaz['time_pay']);

        $user=new User($zakaz['user']);
        $ret.="<br>\nПокупатель: ".$user->user_name.
            "<br>телефон: ".$user->tel.", ".$user->teldom.
            "<br>Адрес: ". $user->adress;

        if(!empty($zakaz['delivery'])) $ret.="\n<br>Доставка по адресу: ".(empty($zakaz['delivery_address'])?'не указан':$zakaz['delivery_address']);
        elseif(!empty($zakaz['delivery_outpost'])) $ret.="\n<br>Самовывоз из ".DB::GetName('shop',$zakaz['delivery_outpost']);

        if(!empty($zakaz['comment']))
            $ret.="<br>\nКомментарий покупателя:<br>".$zakaz['comment']."<br>";
        if(User::is_admin()&&!empty($zakaz['note']))
            $ret.="\n<br>Заметка:<br>".$zakaz['note']."<br>";

        $ret.="\n<table>\n<tr><th>N</th><th>Наименование товара</th><th>Цена</th><th>Кол-во</th><th>Ед</th><th>Сумма</th></tr>";
        $result2 = DB::sql('SELECT * from '.db_prefix.'zakaz2 WHERE zakaz="'.$zakaz['id'].'"');
        $i=1; $summ=0;
        while (($data = DB::fetch_assoc($result2))) if($data['kol']>0){
            $tovar=new Tovar($data['tovar']); if(empty($tovar)||empty($tovar->name))continue;
            $price=($user && $user->adm==uADM_OPT ? $tovar->price2 : $tovar->price );
            $ret.="\n<tr>".
                "<td>".($i++).".</td>".
                "<td style='text-align:left'>".$tovar->kod_prodact." ".$tovar->show_name."</td>".
                "<td>". $price . "</td>".
                "<td>". $data['kol'] . "</td>".
                "<td> шт. </td>".
                "<td>".outSumm0($data['kol']*$price)."</td>";
            $summ+=$data['kol']*$price;
            $ret.="\n</tr>";
        }
        $zakaz['summ']=$summ;
        if(!empty($zakaz['delivery']))$ret.="\n<tr class='delivery'><td>&nbsp;</td><td>Доставка</td><td colspan='2'>".
            ($zakaz['delivery_cost']>0? $zakaz['delivery_cost'] : "бесплатно")."</td></tr>";
        if(!empty($zakaz['discount']))$ret.="\n<tr class='delivery'><td>&nbsp;</td><td colspan=3>Скидка ". $zakaz['discount'] ."%</td></tr>";
        $ret.="\n<tr class='itog'><td colspan='6' style='text-align:right'>Итого на сумму <b>".outSumm0($zakaz['summ'])."</b> руб.<br>Без НДС</td></tr></table>";
        return $ret;
    }

}
