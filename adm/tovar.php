<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
// todo при уменьшении остатка товара требовать ввода причины
if(isset($_REQUEST['d_from'])&&!isset($_REQUEST['d_to'])){
	$_REQUEST['d_to']=date("d.m.Y",min(strtotime("now"),strtotime("last day of ".$_REQUEST['d_from'])));
}


if(isset($_GET['category_show'])){
    die('перенес в shop/api.php');

}elseif(isset($_GET['get'])){//ajax-select запрос
    die('перенес в shop/api.php');
}

if(isset($_GET['ajax'])&&isset($_POST['srok'])){ // изменение заказа прихода товара
   $kol=intval(str_replace(' ','',$_POST['srok']));
   DB::sql("UPDATE `".db_prefix."tovar` SET `srok`='".$kol."' WHERE id='".intval($_GET['ajax'])."'");
   echo $kol;
   DB::close();
   exit;
}

if(isset($_GET['form'])){//ajax- запрос формы добавления/редактирования товара
    die('перенес в /adm/edit_tovar.php');
}


$layer=(isset($_GET['layer'])?intval($_GET['layer']): 0 );

$title="Прайс".(empty($_GET['svod'])?'':' сводный').(empty($_GET['ost'])?'':' остатки').(empty($_GET['prixod'])?'':' приходы').(empty($_GET['zakaz'])?'':' заказы').(empty($_GET['sale'])?'':' продажи');

if(Get::isApi()){
	tovar_layer($layer);
    echo "<title>".$title."</title>";
	exit;
}

if(isset($_GET['excel'])){
    header("Content-Type: application/x-msexcel; name=\"price.xls\"");
    header("Content-Disposition: inline; filename=\"price.xls\"");
    include_once $_SERVER['DOCUMENT_ROOT']."/include/head_print.php";

    echo "\n<h1>".$title."</h1>\n";

    for($i=0;$i<count(Tovar::$ar_type);$i++){
        echo "\n<h2>".Tovar::$ar_type[$i]."</h2>";
        tovar_layer($i);
    }
}elseif(isset($_GET['print'])){
    include_once $_SERVER['DOCUMENT_ROOT']."/include/head_print.php";

    echo "\n<h1>".$title."</h1>\n";

    for($i=0;$i<count(Tovar::$ar_type);$i++){
        echo "\n<h2>".Tovar::$ar_type[$i]."</h2>";
        tovar_layer($i);
    }

}elseif(isset($_GET['printcen'])){
        include_once $_SERVER['DOCUMENT_ROOT']."/include/head_print.php";
        tovar_layer($layer); // ценники только на товар и расходку!!!

}elseif(isset($_GET['printpdf'])){
    tovar_layer($layer); // ценники только на товар и расходку!!!
    exit;

}else{// !print
   include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
   echo "\n<h1>".$title."</h1>\n";

?>
<br class="clear">
<span class="layer<?=($layer==0?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(0)'>Косметика</span>
<span class="layer<?=($layer==1?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(1)'>Услуги</span>
<span class="layer<?=($layer==2?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(2)'>Абонементы</span>
<span class="layer<?=($layer==3?' act':'')?>" style='float:left;' onClick='layer(3)'>Расходка</span>
<br class="clear">

<?

for($i=0;$i<4;$i++){
	echo "\n<div id=\"layer".$i."\" class=\"layer".($i==$layer?' act':'')."\">";
	if($i==$layer)tovar_layer($layer);
	echo "</div>";
}
?>
<p>
    <input type="submit" class="button" value="Приход" onclick="location.href='tovar_prixod.php';return false;">
    <input type="submit" class="button" value="Импорт" onclick="location.href='tovar_import.php';return false;">
    <input type="submit" class="button" value="Экспорт" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'export';return false;">
    <input type="submit" class="button" value="Инвентаризация" onclick="location.href='tovar_invent.php';return false;">
    <input type="submit" class="button" value="Прайс в Excel" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'excel';return false;">
    <input type="submit" class="button" value="Печать" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'print&layer=<?=$layer?>&ajax';return false;">
    <input type="submit" class="button" value="Ценники" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'printcen'+(confirm('С описанием?')?'&description':'');return false;">
    <input type="submit" class="button" value="Этикетки" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'printpdf';return false;">
</p>
<?
}
include_once $_SERVER['DOCUMENT_ROOT']."/include/tail".(isset($_GET['excel'])||isset($_GET['print'])||isset($_GET['printcen'])?'_print':'').".php";

////////////////////////////////////////////
function tovar_layer($layer){
   global $z_tbl;
   $ord=strtolower(urlencode(@$_GET['ord'])); if(!preg_match('/(brand|name|id|kod_prodact|price|ost)/', $ord))$ord='kod_prodact';
   $desc=(isset($_GET['desc'])?' DESC':'');  // &uarr; &darr;
   $q=(isset($_GET['q'])&&$_GET['q']!==''?urldecode($_GET['q']):'');

   $ost=(isset($_GET['ost'])&&$_GET['ost']!=''?1:'');
   $svod=(isset($_GET['svod'])&&$_GET['svod']!=''?1:'');
   $prixod=(isset($_GET['prixod'])&&$_GET['prixod']!=''?1:'');
   $zakaz=(isset($_GET['zakaz'])&&$_GET['zakaz']!=''?1:'');
   $sale=(isset($_GET['sale'])&&$_GET['sale']!=''?1:'');
   $comment=(isset($_GET['description'])&&$_GET['description']!=''?1:'');
   $category=(isset($_GET['category'])?(array)$_GET['category']:'');

    $bar=new kdg_bar(['perpage'=>20, 'tbl'=>db_prefix.'tovar', 'sql'=>" WHERE type='".$layer."'"]);

    if($ost)$bar->sql.=" and (kod_prodact<>'-' or ost<>0)";
    if($q){
        $collection = DB::Select('collection', 'LOWER(name)="' . addslashes(mb_strtolower($q)) . '"');
        $brand = DB::Select('brand', 'LOWER(name)="' . addslashes(mb_strtolower($q)) . '"');
        if (!empty($collection)){
            $bar->sql .= ' and collection=' . $collection['id'];
        } elseif (!empty($brand)) {
            $bar->sql .= ' and brand=' . $brand['id'];
        } else{
            $bar->sql .= " and (lower(name) LIKE '%" . addslashes(str_replace(' ', '%', strtolower($q))) . "%' or kod_prodact LIKE '" . addslashes(strtolower($q)) . "%' or ean LIKE '" . addslashes(strtolower($q)) . "%') ";
        }
    }
    //if($svod&&$zakaz)$bar->sql.=' and (ost<>0 or srok<>0) ';
    if($svod)set_time_limit(600); // Максимальное время выполнения скрипта в секундах, 0 - без ограничений
    if($svod&&$zakaz&&$ost)$bar->sql.=' and (ost<>0 or srok<>0) ';
    elseif($svod&&$zakaz)$bar->sql.=' and (srok<>0) ';
    else{
        if($ost)$bar->sql.=' and ost<>0 ';
        if($zakaz)$bar->sql.=' and srok<>0 ';
    }

/*    $tovar['info']=(empty($data['info']) ? [] : js_decode($data['info']));
    if(empty($tovar['info']['best_before']) || $tovar['info']['best_before']<strtotime('-6 month')) $tovar['info']['best_before']=date('Y-m-d');*/

    if(isset($_GET['excel'])||isset($_GET['print'])){$bar->perpage=100000; /*$bar->sql.=' and (type=3 or price<>0) and (type<>0 or ost>0 or info LIKE "%best_before%")';*/ $ord='brand,collection,kod_prodact,ean'; $desc=''; }
    if(isset($_GET['printcen'])){$bar->perpage=100000; $bar->sql.=' and price<>0 '; $ord='brand,kod_prodact'; $desc=''; }
    if(!$layer && $category){
        $category_list='';
        foreach($category as $key=>$value){
            $category_list.=','.$key;
            //$bar->href.='&category['.$key.']=1';
        }
        $bar->tbl.=' as tovar, '.db_prefix.'category_link as category_link';
        $bar->sql=" WHERE tovar.id=category_link.tovar and category_link.category IN (".substr($category_list,1).") and ".substr($bar->sql,7);

    }

   $bar_out=$bar->out(); //  onsubmit=\"return s_q(this);\">
   $caption='';

if($svod){//} && ($prixod||$sale||$zakaz)){
   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=(isset($_REQUEST['d_to'])?strtotime($_REQUEST['d_to']):time());
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }else{
	$d_to=time();
	$d_from=strtotime(date("01.m.Y"));
   }
   if(isset($_REQUEST['d_from']))$bar->href['d_from']=date("d.m.Y",$d_from);
   if(isset($_REQUEST['d_to']))$bar->href['d_to']=date("d.m.Y",$d_to);
   $caption=" Свод ".($prixod?"приходов":"").($sale?($prixod?", ":"")."продаж":"").($zakaz?($prixod||$sale?", ":"")."заказов":"")." с ".date("d.m.Y",$d_from)." по ".date("d.m.Y",$d_to);

   $r_sum=$r_ost=$r_prixod=$r_srok=$r_sale=$r='';
   for($i=1;$i<=count($z_tbl);$i++){
       $r_sum.="SUM(ost".$i.") as ost".$i.", ";
       $r_ost.=($r_ost?"+":"")."ost".$i;
       if($prixod){
           $r_sum.="SUM(prixod".$i.") as prixod".$i.", ";
           $r_prixod.=($r_prixod?"+":"")."prixod".$i;
       }
       if($sale){
           $r_sum.="SUM(sale".$i.") as sale".$i.", ";
           $r_sale.=($r_sale?"+":"")."sale".$i;
       }
       if($zakaz){
           $r_sum.="SUM(srok".$i.") as srok".$i.", ";
           $r_srok.=($r_srok?"+":"")."srok".$i;
       }

        $z=$z_tbl[$i-1];
        $add=$add2=$add_prixod=$add_sale='';
        for($j=1;$j<=count($z_tbl);$j++){
            $add.=($i==$j?"ost":"0")." as ost".$j.", ";
            $add2.="0 as ost".$j.", ";
            if($prixod){
                $add_prixod.=($i==$j?"SUM(".$z."prixod.kol)":"0")." as prixod".$j.", ";
                if($sale)$add_prixod.="0 as sale".$j.", ";
            }
            if($sale) {
                if($prixod)$add_sale.="0 as prixod".$j.", ";
                $add_sale.=($i==$j?"SUM(".$z."sale2.kol)":"0")." as sale".$j.", ";
            }
            if($zakaz){
                $add.=($i==$j?"srok":"0")." as srok".$j.", ";
                $add2.="0 as srok".$j.", ";
            }
        }
        if($prixod){$r.=($r?"\n\tUNION\n":"\n")."\t\t(SELECT ".$add.$add_prixod." name, kod_prodact, ean, {$z}tovar.kol, ed, brand, collection, info".($comment?", {$z}tovar.description":'').", {$z}tovar.price, {$z}tovar.price0, {$z}tovar.id, {$z}tovar.type, srok ".
            "\n\t\t\tFROM {$z}tovar
            LEFT JOIN {$z}prixod ON {$z}tovar.id={$z}prixod.tovar
            WHERE ".
            "(ost<>0 or {$z}prixod.kol<>0".($zakaz?" or srok<>0":"").") and ".substr($bar->sql,6)." and dat between '".date("Y-m-d",$d_from)."' and '".date("Y-m-d",$d_to)."' GROUP BY {$z}tovar.id)";
            $add=$add2;
            $bar_out='';
        }
        if($sale){$r.=($r?"\n\tUNION\n":"\n")."\t\t(SELECT ".$add.$add_sale." name, kod_prodact, ean, {$z}tovar.kol, ed, brand, collection, info".($comment?", {$z}tovar.description":'').", {$z}tovar.price, {$z}tovar.price0, {$z}tovar.id, {$z}tovar.type, srok ".
           "\n\t\t\tFROM {$z}tovar
            LEFT JOIN {$z}sale2 ON ({$z}tovar.id={$z}sale2.tovar)
            LEFT JOIN {$z}sale ON {$z}sale.id={$z}sale2.sale
            WHERE ".
           "(ost<>0 or {$z}sale2.kol<>0) and ".substr($bar->sql,6)." and ".
            "(time between '".date("Y-m-d 00:00:00",$d_from)."' and '".date("Y-m-d 23:59:59",$d_to)."' or time is NULL) GROUP BY {$z}tovar.id)";
            $add=$add2;
            $bar_out='';
        }
       if(!$prixod&&!$sale)$r.=($r?"\n\tUNION\n":"\n")."\t\t(SELECT ".$add." name, kod_prodact, ean, {$z}tovar.kol, ed, brand, collection, info".($comment?", {$z}tovar.description":'').", {$z}tovar.price, {$z}tovar.price0, {$z}tovar.id, {$z}tovar.type".($zakaz?"":", srok").
           "\n\t\t\tFROM {$z}tovar WHERE ".substr($bar->sql,6)." GROUP BY {$z}tovar.id)";
   }
   $r="select ".$r_sum."name, kod_prodact, ean, kol, ed, brand, collection, info".($comment?", description":'').", price, price0, id, type, SUM(".$r_ost.") as ost, ".($prixod?"SUM(".$r_prixod.") as prixod, ":"").($sale?"SUM(".$r_sale.") as sale, ":"").($zakaz?"SUM(".$r_srok.") as srok":"SUM(srok) as srok")."
	   from (".$r.")q GROUP BY kod_prodact,ean,price,kol ORDER BY ".$ord." ".$desc .($bar_out?" LIMIT ".$bar->start_pos.", ".$bar->perpage:"");
    var_export($r);
   $query=DB::sql($r);

}elseif($prixod){
   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=(isset($_REQUEST['d_to'])?strtotime($_REQUEST['d_to']):time());
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }else{
	$d_to=time();
	$d_from=strtotime(date("01.m.Y"));
   }
   $caption="Приходы с ".date("d.m.Y",$d_from)." по ".date("d.m.Y",$d_to);
   $bar->sql.=' and dat between "'.date("Y-m-d",$d_from).'" and "'.date("Y-m-d",$d_to).'"';
   if(isset($_REQUEST['d_from']))$bar->href['d_from']=date("d.m.Y",$d_from);
   if(isset($_REQUEST['d_to']))$bar->href['d_to']=date("d.m.Y",$d_to);
   $query=DB::sql("SELECT SUM(prixod.kol) as prixod, ost, name, kod_prodact, ean, tovar.kol, ed, brand, collection, info".($comment?", description":'').", tovar.price, tovar.price0, tovar.id, tovar.type, SUM(srok) as srok
	FROM ".db_prefix."tovar as tovar,".db_prefix."prixod as prixod WHERE tovar.id=prixod.tovar and ".substr($bar->sql,6)."
	GROUP BY kod_prodact,ean,price,kol ORDER BY ".$ord." ".$desc);
   $bar_out='';

}elseif($sale){
   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=(isset($_REQUEST['d_to'])?strtotime($_REQUEST['d_to']):time());
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }else{
	$d_to=time();
	$d_from=strtotime(date("01.m.Y"));
   }
   $caption="Продажи с ".date("d.m.Y",$d_from)." по ".date("d.m.Y",$d_to);
   $bar->sql.=' and sale.time between "'.date("Y-m-d 00:00:00",$d_from).'" and "'.date("Y-m-d 23:59:59",$d_to).'"';
   if(isset($_REQUEST['d_from']))$bar->href['d_from']=date("d.m.Y",$d_from);
   if(isset($_REQUEST['d_to']))$bar->href['d_to']=date("d.m.Y",$d_to);
   $query=DB::sql("SELECT SUM(sale2.kol) as sale, ost, name, kod_prodact, ean, tovar.kol, ed, brand, collection, info, sale2.comment, tovar.price, tovar.price0, tovar.id, tovar.type, SUM(srok) as srok
	FROM ".db_prefix."tovar as tovar,".db_prefix."sale as sale,".db_prefix."sale2 as sale2
	WHERE tovar.id=sale2.tovar and sale.id=sale2.sale and (sale<>0 or ost<>0) and ".substr($bar->sql,6)." GROUP BY tovar.id");
   $bar_out='';
}else{
   $query=DB::sql('SELECT * FROM '.$bar->tbl.$bar->sql.' ORDER BY '.$ord.' '.$desc.' LIMIT '.$bar->start_pos.', '.$bar->perpage);
}

    //echo str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",nl2br($r));

//echo "<br>".date("d.m.Y",$d_to)." ".date("d.m.Y",strtotime("first day of ".date("d.m.Y")));

if($caption && strpos($caption,' по ')!==false)
	$caption="<input class=\"button\" type=\"submit\" value=\"&lt;\" title=\"Предыдущий месяц\" ".
		"onclick=\"this.parentNode.style.visibility='hidden';this.name='d_from';this.type='input';this.value='".date("d.m.Y",strtotime("first day of previous month".(isset($_REQUEST['d_from'])?" ".$_REQUEST['d_from']:"")))."';this.form.submit();\"> ".
	$caption.
	($d_to>=strtotime("first day of ".date("d.m.Y"))?"":" <input class=\"button\" type=\"submit\" value=\"&gt;\" title=\"Следующий месяц\" ".
		"onclick=\"this.parentNode.style.visibility='hidden';this.name='d_from';this.type='input';this.value='".date("d.m.Y",min(strtotime("now"),strtotime("first day of next month".(isset($_REQUEST['d_from'])?" ".$_REQUEST['d_from']:""))))."';this.form.submit();\">");

if(isset($_GET['excel'])){
    if($caption)echo "<div>".$caption."</div>";
    echo "По вопосам приобретения косметики оптом обращайтесь по телефону: +7(863) 275-80-77, kdg@ZagarRostov.ru, Skype: kdg_22, ICQ 17754093.<br>
Ознакомиться с образцами можно в студии загара \"SunLife\" по адресу г.Ростов-на-Дону, ул.Красноармейская 103/123 ежедневно с 10 до 22.<br>
Оплата: наличный/безналичный расчет.<br>
<table>
 <tr>
  <th rowspan='2'>Код</th>
  <th rowspan='2'>Наименование</th>
  <th rowspan='2'>Описание</th>
  <th rowspan='2'>кол-во /<wbr>объем</th>
  <th colspan=3>Цена, руб</th>
 </tr>
 <tr>
 <th>Рекомендуемая<wbr>розничная цена</th>
 <th>от 7 тыс.руб.</th>
 <th>от 20 любых саше</th>
 </tr>
";
}elseif(isset($_GET['printcen'])){

}elseif(isset($_GET['printpdf'])){
    include_once $_SERVER['DOCUMENT_ROOT']."/pdf_small.php";
    //include_once $_SERVER['DOCUMENT_ROOT']."/pdf.php";

}else{
if(!isset($_GET['print']))    echo "<div>
<form method=\"get\" action=\"tovar.php\" onsubmit=\"return s_q(this);\">
Искать код или название:<input type=\"search\" name=\"q\" size=\"25\" value=\"".$q."\">
<label><input type=\"checkbox\" name=\"ost\" value=\"1\"".($ost?' checked':'')."> в наличии</label>
<label><input type=\"checkbox\" name=\"zakaz\" value=\"1\"".($zakaz?' checked':'')."> заказ</label>
<label><input type=\"checkbox\" name=\"description\" value=\"1\"".($comment?' checked':'')."> описания</label>
<label><input type=\"checkbox\" name=\"opt\" value=\"1\"".(isset($_GET['opt'])?' checked':'')."> ЦенаОпта</label>"
.(User::is_admin() ? "<label><input type=\"checkbox\" name=\"svod\" value=\"1\"".($svod?' checked':'')."> сводный</label>
<label><input type=\"checkbox\" name=\"prixod\" value=\"1\"".($prixod?' checked':'')."> приходы</label>":"")."
<label><input type=\"checkbox\" name=\"sale\" value=\"1\"".($sale?' checked':'')."> продажи</label>".
($layer?"":"<br>\n".Tovar::_GetCategory(isset($_GET['category'])?$_GET['category']:''))."
<input type=\"submit\" class=\"button\" style=\"width:auto;\" value=\"Найти\">
<input type=\"reset\" class=\"button\" style=\"width:auto;\" value=\"Сброс\" onclick=\"return s_q()\">"
.($caption?"<div>".$caption."</div>":"")."
</form>
<a href='/adm/edit_tovar.php?form' class='button fr ajax'>Добавить</a>
<br class='clear'>
<div>всего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."<br class='clear'></div>

</div>
";
    $ar= ['kod_prodact'=>"#"];
    if($layer<=1)$ar['brand']=($layer==1?"Кабинки":"Бренд");
    $ar['name']="Наименование";
    $ar['kol']="кол-во / объем";
    $ar['price']="Цена";
    if(isset($_GET['opt'])){$ar[]="Ц.прихода";$ar[]='Ц.опт';}
    $ar['ost']=($layer==2?"Срок":"Остаток");
    if($prixod)$ar['prixod']="Приход";
    if($sale)$ar['sale']="Продано";
    if($svod&&$zakaz)$ar['zakaz']="Заказ";
    if(!$svod&&$zakaz)$ar[]="Заказ";
    $ar[]="заказ, действия";// хвост
    echo "<table class=\"client-table\" border=1 data-tbl='tovar'>".$bar->header($ar);

} //!print


$br=''; $collection='';
$di=0; $d='';
$S_zakaz=$S_sale=$S_prixod=$S_ost=0;

if(isset($_GET['export'])&&is_file(db_prefix."export.csv"))unlink(db_prefix."export.csv");

while ($data = DB::fetch_assoc($query)){
	//print_r($data); echo "<br>";
    if((isset($_GET['excel'])||isset($_GET['print'])) && empty($_GET['zakaz'])){
       if($br!=$data['brand']){
           if($di)outData($d,$di);
           $di=0;$br=$data['brand'];
           echo "<tr><td colspan='7' style='background-color:cyan'>".DB::GetName('brand',$br)."</td></tr>";
       }
       if($collection!=$data['collection']){
           if($di)outData($d,$di);  $di=0;
           $collection=$data['collection'];
           if(DB::GetName('collection',$collection))echo "<tr><td colspan='7' style='background-color:lightcyan'>".DB::GetName('brand',$br).", ".DB::GetName('collection',$collection)."</td></tr>";
       }
    }
   if(isset($_GET['printcen'])){for($i=1;$i<max(@$_GET['count'],1);$i++){outData(array($data),1);$S_zakaz++; /*if($S_zakaz%32==0)echo "<br style='page-break-before:always'>";*/ continue;}}
   if($di&&$d[$di-1]['name']!=$data['name'] || isset($_GET['analiz']) ){outData($d,$di);$di=0;}
   $d[$di++]=$data;
   $S_ost+=$data['ost']*$data['price'];
   if($prixod)$S_prixod+=$data['prixod']*$data['price0'];
   if($sale)$S_sale+=$data['sale']*$data['price'];
   if($zakaz)$S_zakaz+=$data['srok']*$data['price0'];
}
if($di){outData($d,$di);}
unset($GLOBALS['br']);

echo "\n</table>";
      if(!isset($_GET['excel'])&&!isset($_GET['print'])&&!isset($_GET['printcen']))echo "\n<br class='clear'><div> всего: <b>".$bar->count."</b>".
          ", остаток на сумму <b>".outSumm($S_ost)."</b>руб.".
          ($prixod?", приход на сумму <b>".outSumm($S_prixod)."</b>руб.":"").
          ($sale?", продаж на сумму <b>".outSumm($S_sale)."</b>руб.":"").
          ($zakaz?", заказ на сумму <b>".outSumm($S_zakaz)."</b>руб.":"").
          ". &nbsp; ".$bar_out."\n</div>";
}

function d($data,$ind){
    global $z_tbl;
    if(isset($data[$ind.'1'])){
        echo "\n\t<td>"/*.$ind.':'*/.($data[$ind]?$data[$ind]:'').":(";
        for($i=1;$i<=count($z_tbl);$i++)
            echo ($i>1?",":"").($data[$ind.$i]?$data[$ind.$i]:'');
        echo ")</td>";
    }elseif(isset($data[$ind]))echo "\n\t<td>".$data[$ind]."</td>";
}

function outData($d,$di){
global $layer;
$rowspan=($di<2?'':' rowspan="'.$di.'"');
    //var_dump($d,$di);exit;
for($i=0;$i<$di;$i++){
   $data=$d[$i];
    if(isset($_GET['analiz'])){
        $ost_s=Tovar::GetOst($data);
        if($ost_s==0&&$data['ost']==0)continue;
    }
    if(isset($_GET['export'])&&isset($_GET['zakaz']))
	    file_put_contents(db_prefix."export.csv",($data['kod_prodact']?$data['kod_prodact']:$data['ean']).";".$data['srok']."\r\n", FILE_APPEND);
   if(isset($_GET['excel'])){
        echo "<tr>\n<td>".$data['kod_prodact'].($data['kod_prodact']&&$data['ean']?"/":"").$data['ean'].($data['type']>0||Tovar::_GetVar($data,'best_before')?($data['type']>0||$data['ost']<1?'':'<span style="color:green"><wbr>в наличии</span>'):'<span style="color:green"><wbr>последние</span>')."</td>";
        $data['name']=str_replace('NEW','<b style="color:red">NEW '.date('Y').'</b>',$data['name']);
   }elseif(isset($_GET['printcen'])){
       //Tovar::UpdateFromShop($data);
       $d3=Tovar::Cennik($data);
       if(isset($_GET['count']))$d3=str_repeat($d3,intval($_GET['count']));
       echo $d3;
       continue;
   }else echo "<tr id=\"id".$data['id']."\"".(isset($_GET['export'])||isset($_GET['excel'])||isset($_GET['print'])||isset($_GET['printcen']) ? "" :
                        " onclick=\"ShowHide(this,'red')\" onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\"").">
	<td class='hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$data['id']."')\">".$data['kod_prodact'].($data['kod_prodact']&&$data['ean']?"/":"").$data['ean']."</td>".
    ($data['type']>1?"":"   <td class='left'>".($data['type']==1? Tovar::_GetVar($data,'cab') : DB::GetName('brand',$data['brand']) . (isset($_GET['description'])?"<span><br>".DB::GetName('collection',$data['collection'])."</span>":"") )."</td>");
   if($i==0)echo "\n   <td class='left'".$rowspan.">".$data['name'].(isset($_GET['description'])?"<span><br>".$data['description']."</span>":"")."</td>";
   if($i==0&&isset($_GET['excel']))echo "
	<td class='small'".$rowspan.">".($data['description']?$data['description']:'&nbsp;')."</td>";
   echo "\n   <td>".outSumm($data['kol']).($data['kol']==0?'':$data['ed'])."</td>
	<td>".$data['price']."</td>";
   if(isset($_GET['opt'])) echo "<td>".outSumm($data['price0'])."</td><td>".outSumm(ceil($data['price0']*1.15))."</td>";
   if(!isset($_GET['excel'])){
       if($layer==2) echo "\n\t<td>".($data['srok']?$data['srok']:'')."</td>";
       elseif(isset($_GET['analiz'])){
            echo "\n\t<td>".$data['ost'].'/'.$ost_s."</td>";
       }else{
           d($data, 'ost');
       }

       d($data, 'prixod');
       d($data, 'sale');
       if(isset($data['srok1']))d($data, 'srok');
       if(isset($_GET['print'])){
           if(!empty($_GET['zakaz'])&&empty($_GET['svod']))echo "\n\t<td>".($data['srok']?$data['srok']:'')."</td>";
       }else echo "\n<td".(!empty($_GET['zakaz']) && empty($_GET['svod']) ?"":" class=\"edit-del\"")." style='width:175px;'>".
            ($layer==2?"":"<input type='text' name='srok' value='".$data['srok']."' onfocus='this.select()' onChange='SendInput(this)' style='display:inline-block;float:left;' />").
            "<a href='/adm/report.php?layer=6&tovar=".$data['id']."' class=\"icon comment right ajax\" title=\"Протокол\"></a>
            <a href='/shop/api.php?tovar&show=".$data['id']."' class=\"icon abonement right ajax\" title=\"Движения\" ></a>
            <a href='/api.php?tbl=tovar&del=".$data['id']."' class=\"icon del right confirm\" title=\"Удалить\"></a>
            <a href='/adm/edit_tovar.php?form=".$data['id']."' class=\"icon edit right ajax\" title=\"Изменить\"></a></td>";
	}
    echo "\n</tr>";
}//for
}
