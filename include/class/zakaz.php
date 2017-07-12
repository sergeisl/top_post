<?

/**
 * Class Zakaz
 * @method static integer _Out
 * @method static integer _Zp
 */
class Zakaz{
    const db_prefix=db_prefix;
    static public $new_alg_start_time="2014-06-01";
    public $message='';
    public $zakaz=[];
    public $zakaz2=[];

    public function __construct($row = null){
        if(is_array($row)){
            $this->zakaz=$row;
        }elseif($row>0){
            $this->zakaz=DB::Select("zakaz",intval($row));
            if(!$this->zakaz)echo "Ошибка в коде продажи ".$row;
        }
        $this->zakaz2=DB::Select2Array("zakaz2","zakaz=".$this->zakaz['id']);
    }

    public function __destruct() {

    }

    public static function __callStatic($name,array $params){
        $zakaz=new Zakaz($params[0]);
        /*if (method_exists($zakaz, $f.sizeof($p))) return call_user_func_array(array($this, $f.sizeof($p)), $p);
        throw new Exception('Tried to call unknown method '.get_class($this).'::'.$f);*/

        if( ! is_callable( array($zakaz,substr($name,1)) ))die('Tried to call unknown method '.__CLASS__."::". substr($name,2) );
        return call_user_func_array(array($zakaz,substr($name,1)),$params);
    }

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

    static function DelAll($id){ /* удаление всей операции продажи zakaz->id */
        $query=DB::sql("SELECT * FROM `".self::db_prefix."zakaz2` WHERE id='".$id."' LIMIT 1");
        while($zakaz = DB::fetch_assoc($query)){
            self::Del($zakaz['id']);
        }
    }

    /** удаление строчки заказа
     * @param $id
     * @param bool $r
     * @return string
     */
    static function Del($id,$r=false){ /* $r=true - вызов из удаления абонемента не зациклиться */
        if(!($data = DB::Select('zakaz2',$id))) return "Нет такой записи!";
        isPrivDel('zakaz',$data['zakaz']);

        $zakaz=DB::Select("zakaz",$data['zakaz']);

        DB::log('zakaz2', $id, 'удаление',array_merge($data,$zakaz)); // добавить информацию из zakaz
        DB::Delete("zakaz2",$id);
        if(DB::affected_rows()>0){
            // если это была последняя запись - удалить заголовок
            if(!DB::Select('zakaz2',"zakaz='".$data['zakaz']."'")){
                DB::Delete("zakaz",$data['zakaz']);
            }else{ // пересчитать заголовок
                DB::sql("UPDATE `".db_prefix."zakaz`	SET `zp`=0, zpu=0 WHERE id='".$data['zakaz']."' LIMIT 1");
            }
            // восстанавливаю остатки
            $tovar=Tovar::GetTovar($data['tovar'],1); if(!$tovar)return "Нет товара № ".$data['tovar'];
            if($data['kart']&&$tovar['type']==tTYPE_USLUGA){ //услуги по абонементу - увеличиваю остаток
                DB::sql("UPDATE `".self::db_prefix."kart` SET `ost`=ost+".$data['kol']." WHERE id='".$data['kart']."'");
                if(!DB::affected_rows()){
                    DB::log('kart', $data['kart'], "Продажу удалил, абонемента № ".$data['kart']." нет!");
                    return "Продажу удалил, абонемента № ".$data['kart']." нет!";
                }
            }elseif($data['kart']&&$tovar['type']==tTYPE_ABON){/* удаление продажи абонемента*/
                if(!$r)Kart::Del($data['kart']); // удалю сам абонемент
            }elseif($tovar['type']==tTYPE_TOVAR||($tovar['type']==tTYPE_RASX && $tovar['price']>0)){// увеличиваю остаток косметики или расходки
                DB::sql("UPDATE `".self::db_prefix."tovar` SET `ost`=`ost`+".$data['kol']." WHERE id='".$data['tovar']."'");
                if(!DB::affected_rows()){
                    DB::log('tovar', $data['tovar'], "Продажу удалил, товара id:".$data['tovar']." нет!");
                    return "Продажу удалил, товара id:".$data['tovar']." нет!";
                }
            }

            if($data['sertif']&&$data['summ_sertif']!=0){ // увеличиваю остаток на сертификате
                DB::sql("UPDATE `".db_prefix."kart`	SET `ost`=ost+".$data['summ_sertif']." WHERE id='".$data['sertif']."'");
            }

            return '';
        }else return "Не удалил продажу id2:".$id;
    }

    /**
     * рассчет зарплаты по данной продаже
     */
    public function zp() {
        $this->message="";
        if(strtotime($this->zakaz['time']) < strtotime(Zakaz::$new_alg_start_time)){
            $this->zakaz['zp']=$this->zakaz['zpu']=0;
            //$this->message="до ".zakaz::$new_alg_start_time;
            return 0;
        }
        if(!isset($_GET['recalc']) && (!empty($this->zakaz['zp']) || !empty($this->zakaz['zpu'])) ){
            //$this->message="cash";
        }else{
            $this->zakaz['zp']=$this->zakaz['zpu']=0;
            $user=($this->zakaz['user']==ANONIMOUS_KLIENT ? 0 : User::GetUser($this->zakaz['user']) );
            if(!empty($user) && ($user['adm']>=uADM_WORKER || $user['adm']==uADM_OPT ) ){
                $this->zakaz['zpu']=0.01; // 1 коп за опт и администраторов
                //if(!empty($_GET['recalc'])&&$_GET['recalc']=='2')echo "<br>\n".zakaz::Out();
                $this->message.="\nadm:".$user['adm'];
            }else{
                $ar_tovar=[]; // массив саше
                $fl_autozagar=false; // признак что был автозагар в кабинке
                $fl_autozagar_summ=0; // сумма балончика автозагара
                foreach($this->zakaz2 as $row){
                    $row['summ']=$row['kol']*$row['price'];
                    if($row['summ']==0){
                        //$this->message.="\n".$row['id']." 5%*".$row[';
                    }elseif($row['discount']>20){
                        $this->zakaz['zpu']=0.01; // 1 коп за опт и администраторов
                        $this->message.="\nскидка>20%";
                    }else{
                        $tovar=new Tovar($row['tovar']);
                        if($tovar->type==tTYPE_USLUGA && $tovar->kod_prodact==55){ // 20% при оказании разовой услуги виброплатформа
                            $this->zakaz['zpu']+=round($row['summ']*0.2,2);
                            $this->message.="\n20%*".$row['summ']."=".$row['summ']*0.2;
                        }elseif($tovar->type==tTYPE_TOVAR && $tovar->unic_category==21 && $row['summ']>1000){ // продажа балончика автозагара
                            if($fl_autozagar){ // вместе с автозагаром
                                $this->zakaz['zpu']+=$row['summ']*0.2;
                                $this->message.="\n20%*" . $row['summ']."=".$row['summ']*0.2;
                                $fl_autozagar=false;
                            }else{ // без автозагара или автозагар дальше в массиве
                                $fl_autozagar_summ=$row['summ'];
                            }
                        }elseif($tovar->type==tTYPE_ABON || $tovar->type==tTYPE_USLUGA || ($tovar->type==tTYPE_TOVAR &&$tovar->kol>30) ){
                            $this->zakaz['zp']+=round($row['summ']*0.05,2); // 5% от продажи услуг, бутылок и абонементов (делится поровну на двоих)
                            $this->message.="\n5%*".$row['summ']."=".$row['summ']*0.05;
                        }elseif($tovar->type==tTYPE_RASX){
                            $this->zakaz['zpu']+=round($row['summ']*0.1,2);  // 10% от продажи расходки тому кто продал
                            $this->message.="\n10%*".$row['summ']."=".$row['summ']*0.1;
                        }elseif($tovar->type==tTYPE_TOVAR &&$tovar->kol<=30){
                            $this->zakaz['zpu']+=round($row['summ']*0.1,2);  // 10% от продажи сашетки (объем до 30мл) тому кто продал
                            $ar_tovar[$tovar->unic_category]=$row['summ'];
                            $this->message.="\n10%*".$row['summ']."=".$row['summ']*0.1;
                        }
                        //$this->message.="\n unic_category=".$tovar->unic_category;
                        if($tovar->type==tTYPE_USLUGA && in_array($tovar->id,array(4,16)))$fl_autozagar=true; // костыль 4 -  автозагар, 16-автозагар повторно)
                    }
                }
                if(count($ar_tovar)>2){ // 30% от продажи третьей сашетки другой категории в чеке (лицо+тело+ноги, для загар лицо + для загара тело + после загара) тому кто продал.
                    sort($ar_tovar); // Повышенный процент начисляется на саше с наименьшей ценой.
                    $this->zakaz['zpu']+=$ar_tovar[0]*0.2+$ar_tovar[1]*0.1; // от второй 20% учитываю, что 10% я уже начислил
                    $this->message.="\n+20%*" . $ar_tovar[0]."=".$ar_tovar[0]*0.2 . " +10%*" . $ar_tovar[1]."=".$ar_tovar[1]*0.1;
                }elseif(count($ar_tovar)>1){ // 20% от продажи второй сашетки другой категории в чеке (лицо+тело, ноги+лицо, для загара+после загара) тому кто продал.
                    $this->zakaz['zpu']+=min($ar_tovar)*0.1;
                    $this->message.="\n+10%*" . min($ar_tovar)."=".min($ar_tovar)*0.1;
                }
                if($fl_autozagar_summ>0){ // 20% при продаже балончика для автозагара клиенту с автозагаром
                    if($fl_autozagar){ // на случай если сначала флакон, а потом услуга
                        $this->zakaz['zpu']+=$fl_autozagar_summ*0.2;
                        $this->message.="\n20%*" . $fl_autozagar_summ."=".$fl_autozagar_summ*0.2;
                    }else{ // только продажа балончика автозагара
                        $this->zakaz['zp']+=round($fl_autozagar_summ*0.1,2); // 5% от продажи услуг, бутылок и абонементов (делится поровну на двоих)
                        $this->message.="\n10%*".$fl_autozagar_summ."=".$fl_autozagar_summ*0.1;
                    }
                }
            }
            DB::sql("UPDATE `".db_prefix."zakaz`	SET `zp`='".$this->zakaz['zp']."', zpu='".$this->zakaz['zpu']."' WHERE id='".$this->zakaz['id']."' LIMIT 1");
        }
        return round($this->zakaz['zp']/2+$this->zakaz['zpu'],2);
    }

    /*static public function  _zp($id){
        $zakaz=new zakaz($id);
        return $zakaz->zp();
    }*/
    /** вывод строчки заказа со всеми входящими товарами при работе администратора
     *
     */
    public function Out(){
        $old=false;
        // сортирую по убыванию
        array_sort($this->zakaz2,'id',1);
        foreach($this->zakaz2 as $zakaz2){
            echo "\n<tr id=\"id".$zakaz2['id']."\" ".($old?'':'style="border-top:#9fbddd 1px solid;" ')."onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">";
            $tovar=new Tovar($zakaz2['tovar']);
            if($tovar){
                if(!$old){$zp=$this->zp(); $zp=($zp ? "<br><span title='на з/пл. администратора'>".$zp.($this->message?"(".$this->message.")":"")."</span>" : '' );}
                echo "<td>".($old?'&nbsp':date('d.m.y H:i',strtotime($this->zakaz['time'])).$zp.($this->zakaz['visa']?"<div class='visa' title='Оплачено пластиком'>".$this->zakaz['visa']."</div>":""))."</td>
        <td class='left'><a class='ajax' href='/user/?id=".$this->zakaz['user']."'>".($old?'&nbsp':User::_GetVar($this->zakaz['user'],'user_name'))."</a></td>
        <td class='left'><a class='ajax' href='/adm/edit_tovar.php?form=".$zakaz2['tovar']."'>".$tovar->show_name."</a></td>\n";
                if($zakaz2['device']>5){ // это запись на конкретное занятие
                    $schedule=DB::Select('schedule',$zakaz2['device']);
                    echo "<td>" . BuildUrl('pages', $schedule['pages'],1).'<br>'.date('d.m.y H:i',strtotime($zakaz2['time']))/*.' '. $schedule['hour']*/ . "</td>";
                }elseif((User::is_admin()||date('Y-m-d')==date('Y-m-d',strtotime($this->zakaz['time']))) && $tovar->type==tTYPE_USLUGA) {
                    echo "<td><input value='" . ($zakaz2['device'] ? $zakaz2['device'] : '') . "' class='edit' name='device' onChange='SendInput(this)' ></td>";
                }else {
                    echo "<td>" . ($zakaz2['device'] ? $zakaz2['device'] : '&nbsp;') . "</td>";
                }
                echo "\t<td".($zakaz2['kart']?" class='hand' onclick=\"return ajaxLoad('','/adm/kart.php?form=".$zakaz2['kart']."')\">".$zakaz2['kart']:'>').
                    ($zakaz2['sertif']?"<span class='hand' onclick=\"return ajaxLoad('','/adm/kart.php?form=".$zakaz2['sertif']."')\">(".$zakaz2['sertif'].")</span>":'')."</td>\n";
                if((User::is_admin()||date('Y-m-d')==date('Y-m-d',strtotime($this->zakaz['time']))) && $zakaz2['kart']==0 && $zakaz2['sertif']==0)
                    echo     "<td><input value='".$zakaz2['kol']."' class='edit' name='kol' onChange='SendInput(this)' ></td>";
                else echo "<td>".$zakaz2['kol']."</td>";
                echo "\n\t<td title='".htmlspecialchars($zakaz2['comment'])."'>".($zakaz2['discount']?$zakaz2['discount']."%":'')."</td>
        <td>".outSumm($zakaz2['kol']*$zakaz2['price']).
                    ($zakaz2['sertif']?" <span>(".outSumm($zakaz2['summ_sertif']).")</span>":"")."</td>";
            }else{
                echo "<td colspan='8'>Ошибка в коде товара ".$zakaz2['tovar']."</td>";}
    //	<a href='work_form.php?form=".$zakaz2['id']."' class=\"icon edit right\" title=\"Изменить\" onclick=\"return ajaxLoad('',this.href)\">
            echo "\n<td class=\"edit-del\">
        <a href='/api.php?tbl=zakaz&del=".$zakaz2['id']."' class=\"icon del right confirm\" title=\"Удалить\"></td>
        </tr>";
            $old=true;
        }

    }

    /** просмотр списаний косметики и посещений по клиенту + непросроченные абонементы
     * @param $ar = $_REQUEST 'ushow'=id, 'type', 'd_from', 'd_to'
     */
    static function ushow($ar){
        $id=intval($ar['ushow']);
        $add='';
        if(isset($ar['type']))$add=(intval($ar['type'])?'and tovar.type>0':'and (tovar.type=0 or (tovar.type=1 and (zakaz2.price>0 or tovar.price0>0)))'); // не задан-все, 0 - взял косметики И услуг, 1-бонус:услуги, расходка
        if(!isset($ar['d_from']) && !isset($ar['d_to'])){ // если период не задан, то буду выводить последние 10 записей
            $query=DB::sql("SELECT * FROM ".db_prefix."zakaz WHERE user='".$id."' ORDER BY time DESC LIMIT 10,1");
            $d_from=(($data=DB::fetch_assoc($query)) ? strtotime($data['time']) : strtotime("first day of previous year") );
        }else{
            $d_from=(isset($ar['d_from'])? strtotime($ar['d_from']) : strtotime(date("01.m.Y")) );
        }
        $d_to=(isset($ar['d_to'])    ? strtotime($ar['d_to'])   : time() );
        echo "<h2>".User::_GetVar($id,'user_name')."</h2>";
        $query=DB::sql("SELECT zakaz.time as time, zakaz.user as user, zakaz.user as user, tovar.type as type, zakaz2.kol as kol, zakaz2.tovar as tovar, tovar.name as tovar_name, tovar.ed as ed,
	(zakaz2.kol*zakaz2.price) AS summ, (tovar.price * zakaz2.kol ) AS s1, ( tovar.price0 * zakaz2.kol ) AS s2, zakaz2.device as device
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.user='".$id."' ".$add."
	ORDER BY time, type DESC"); //  and zakaz.time between '".date("Y-m-d 00:00:00",$d_from)."' and '".date("Y-m-d 23:59:59",$d_to)."'".$add."
        if(DB::num_rows($query)){
            ?>
            <h4>Мои заказы</h4>
            <table class="client-table" style='min-width:300px'>
                <?
                while($data=DB::fetch_assoc($query)){
                    ?>
                    <tr>
                        <td><?=time2html($data['time'])?></td>
                        <? if(User::is_admin(!0)){?>
                            <td class='left hand<?=($data['type']==tTYPE_TOVAR?" blue":"")?>' onclick="return ajaxLoad('','/adm/edit_tovar.php?form=<?=$data['tovar']?>')"><?=$data['tovar_name']?> <span class='gray'><?=User::_GetVar($data['user'],'user_name')?></span></td>
                        <?}else{?>
                            <td class='left'><?=$data['tovar_name']?></td>
                        <?}?>
                        <td><?=$data['kol']." ".$data['ed']?></td>
                        <td style='white-space:nowrap'><?=outSumm($data['summ']).(User::is_admin(!0)?"/".outSumm($data['s1'])."/".outSumm($data['s2']):'')?></td>
                    </tr>
                <?}?>
            </table>
            <?
        }else echo "Посещений не было!";

    }

}
