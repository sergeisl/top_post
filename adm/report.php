<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
if(!defined('coef_'))define('coef_',1.0);
$layer=(isset($_GET['layer'])?intval($_GET['layer']):'0');

   if(isset($_REQUEST['reset'])){
	  unset($_REQUEST['d_to'],$_REQUEST['d_from']);
   }elseif(isset($_REQUEST['old_month'])){
	  $_REQUEST['d_from']=date("d.m.Y",strtotime("first day of previous month".(isset($_REQUEST['d_from'])?" ".$_REQUEST['d_from']:"")));
	  $_REQUEST['d_to']=date("d.m.Y",strtotime("last day of previous month".(isset($_REQUEST['d_to'])?" ".$_REQUEST['d_to']:"")));
   }elseif(isset($_REQUEST['next_month'])){
	  $_REQUEST['d_from']=date("d.m.Y",strtotime("first day of next month".(isset($_REQUEST['d_from'])?" ".$_REQUEST['d_from']:"")));
	  $_REQUEST['d_to']=date("d.m.Y",strtotime("last day of next month".(isset($_REQUEST['d_to'])?" ".$_REQUEST['d_to']:"")));
   }
   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
       //die(date("Y-m",$d_from)." last day");
    if(!isset($_REQUEST['d_to']))$_REQUEST['d_to']=date("d.m.Y",strtotime("last day of next month",$d_from-10));
	$d_to=strtotime($_REQUEST['d_to']);
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }elseif($layer==7){
       $d_from=(isset($_REQUEST['d_from'])? strtotime($_REQUEST['d_from']) : strtotime(date("01.m.Y",strtotime("-1 year"))));
       $d_to  =(isset($_REQUEST['d_to'])  ? strtotime($_REQUEST['d_to'])   : strtotime("last day of previous month"));
   }else{
	$d_to=time();
	$d_from=strtotime(date("01.m.Y"));
   }

if($layer==7 && $d_from>strtotime(date("01.01.Y")) ) $d_from=strtotime(date("01.01.Y"));


if(isset($_GET['ajax'])){//ajax запрос вкладки 0-услуги, 1-косметика,2-абонементы
	report_layer($layer);
	DB::close();
	exit;
}
$title="Отчеты";
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
echo "\n<h1>".$title."</h1>\n";
?>
<form method="get" onsubmit="this.layer.value=getlayer();">
За период
<input class="button" type="submit" value="&lt;" name="old_month" title="Предыдущий месяц">
с <input type="date" name="d_from" size=10 value="<?=date("d.m.Y",$d_from)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
по <input type="date" name="d_to" size=10 value="<?=date("d.m.Y",$d_to)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
<input class="button" type="submit" value="&gt;" name="next_month" title="Следующий месяц">
<input type="hidden" name="layer" value="">
<input class="button" type="submit" value="Отфильтровать">
<input class="button" type="submit" name="reset" value="Сброс" onclick="location.href='report.php';return false;">
</form>
<br class="clear">

<span class="layer<?=($layer==0?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(0)'>Сводный</span>
<span class="layer<?=($layer==1?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(1)'>Расчет зарплаты</span>
<span class="layer<?=($layer==2?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(2)'>Скидки</span>
<span class="layer<?=($layer==3?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(3)'>Сотрудники</span>
<span class="layer<?=($layer==4?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(4)'>Операции</span>
<span class="layer<?=($layer==5?' act':'')?>" style='float:left;margin-right:2px' onClick='layer(5)'>Счетчики</span>
<span class="layer<?=($layer==6?' act':'')?>" style='float:left;' onClick='layer(6)'>Протокол</span>

<?
  if(User::is_admin())echo "\n<span class='layer".($layer==7?' act':'')."' style='float:left;' onClick='layer(7)'>Свод за год</span>";
?>
<br class='clear'>
<?
for($i=0;$i<(User::is_admin()?8:7);$i++){
	echo "\n<div id=\"layer".$i."\" class=\"layer".($i==$layer?' act':'')."\">";
	if($i==$layer)report_layer($layer);
	echo "</div>";
}

include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";

function report_layer($layer){
global $d_from, $d_to;
   $bar=new kdg_bar();
   //$bar->tbl=db_prefix.'zakaz as zakaz,'.db_prefix.'zakaz2 as zakaz2,'.db_prefix.'tovar as tovar';
   //$bar->sql=' WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id';
   //if(isset($_REQUEST['d_from'])&&isset($_REQUEST['d_to'])){
	$bar->sql.=' and zakaz.time between "'.date("Y-m-d 00:00:00",$d_from).'" and "'.date("Y-m-d 23:59:59",$d_to).'"';
    if(isset($_REQUEST['d_from'])&&isset($_REQUEST['d_to']))$bar->href='&d_from='.date("d.m.Y",$d_from).'&d_to='.date("d.m.Y",$d_to);
    else $bar->href='&d_from='.date("d.m.Y",$d_from);
   //}
if($layer==0){
   if($bar->href)$bar->href='?'.substr($bar->href,1);
   //$bar->perpage=10;
   //$bar_out=$bar->out();
   //echo "<div>\nвсего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."</div>";
if(is_file($fil=$_SERVER['DOCUMENT_ROOT'].'/log/movements.TXT')){
    if(($shop=DB::Select('shop','db_prefix="'.addslashes(db_prefix).'"', 'z0_'))===false)die("Ошибка! Не верный магазин ".db_prefix."!");
    //$terminal='77116664'; '.*';
    //$shop['terminal']='77116664'; // Барабу
    //$shop['terminal']='77110358'; // sunlife
    $buf=file_get_contents($fil);
    //НазначениеПлатежа=Возмещение ср-в по операциям с ПК по терминалу 77116664. Учтено покупок 1 на сумму 734.00 руб Комиссия банка 13.95 руб. Без НДС
    preg_match_all('/ДатаПоступило=([\d\.]+?).*НазначениеПлатежа=Возмещение ср\-в.* '.$shop['terminal'].'\..*сумму ([\d\.]*?) руб Комиссия банка ([\d\.]*?) руб./imsU',$buf,$ar, PREG_SET_ORDER);

    $terminal=[];
    if($ar){
        foreach ($ar as $row) {
            $d=date('d.m.Y',strtotime($row[1])/*-60*60*24*/);
            //if(!empty($terminal[$d]))$d=date('d.m.Y',strtotime($row[1])-60*60*24*2);
            $terminal[$d] = (empty($terminal[$d]) ? 0 : $terminal[$d]) + $row[2]; /*echo "<br>".$row[1].', '.$d.', '.$row[2].'-'.$row[3]*/;
        }
        echo "Терминал: ".$shop['terminal'];
    }
    unset($buf, $ar, $row);
}


?>
 <table class="client-table">
 <tr>
  <th>Дата</th>
  <th>Администратор</th>
  <th>Клиентов</th>
  <th class='it1 hide'>Косметика</th>
  <th class='it1 hide'>Расходка</th>
  <th class='it1 hide'>Услуги</th>
  <th class='it1 hide'>Абонементы</th>
  <th onclick='ShowHide("it1")'>Итого выручка<span class="box">+</span></th>
  <th>Затраты</th>
  <th>Остаток</th>
 </tr>
<?
/*
$query=DB::sql("SELECT COUNT(zakaz.id) as cnt, DATE_FORMAT(zakaz.time, '%Y-%m-%d') as dat,
	SUM(IF(tovar.type=0,`summ`,0)) as s0, SUM(IF(tovar.type=1,`summ`,0)) as s1, SUM(IF(tovar.type=2,`summ`,0)) as s2, SUM(IF(tovar.type=3,`summ`,0)) as s3
	FROM ".$bar->tbl.$bar->sql.'
	GROUP BY dat ORDER BY dat');
*/
$query=DB::sql("select dat, SUM(cnt) as cnt, SUM(s0) as s0, SUM(s1) as s1, SUM(s2) as s2, SUM(s3) as s3, SUM(s) as s, user, SUM(sX) as sX, SUM(visa) as visa, SUM(opt) as opt
   from
	((SELECT COUNT(DISTINCT zakaz.id) as cnt, DATE_FORMAT(zakaz.time, '%Y-%m-%d') as dat,
	SUM(IF(tovar.type=0,zakaz2.kol*zakaz2.price,0)) as s0, SUM(IF(tovar.type=1,zakaz2.kol*zakaz2.price,0)) as s1, SUM(IF(tovar.type=2,zakaz2.kol*zakaz2.price,0)) as s2, SUM(IF(tovar.type=3,zakaz2.kol*zakaz2.price,0)) as s3,
	0 as s, zakaz.manager as user, 0 as sX, 0 as visa, SUM(IF(user.adm=".uADM_OPT.",zakaz2.kol*zakaz2.price,0)) as opt
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar,".db_prefix."user as user
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.manager=user.id and (user.adm<".uADM_WORKER." or user.adm IS NULL) ". str_replace('time','zakaz.time',$bar->sql). "
	GROUP BY dat)
	UNION DISTINCT
	(SELECT 0 as cnt, DATE_FORMAT(time, '%Y-%m-%d') as dat,
	0 as s0, 0 as s1, 0 as s2, 0 as s3,
	0 as s, user, 0 as sX, SUM(visa) as visa, 0 as opt
	FROM ".db_prefix."zakaz
	WHERE 1 ". $bar->sql. "
	GROUP BY dat)
	UNION DISTINCT
	(SELECT 0 as cnt, DATE_FORMAT(time, '%Y-%m-%d') as dat,
	0 as s0, 0 as s1, 0 as s2, 0 as s3,
	SUM(IF(rej<7,summ,0)) as s, user, SUM(IF(rej=27,summ,0)) as sX, 0 as visa, 0 as opt
	FROM ".db_prefix."incasso
	WHERE 1 ".$bar->sql."
	GROUP BY dat)
	)q GROUP BY dat ORDER BY dat");
// исключить администраторов и руководство

/*
$query=DB::sql("SELECT COUNT(zakaz.id) as cnt, DATE_FORMAT(zakaz.time, '%Y-%m-%d') as dat,
	SUM(IF(tovar.type=0,zakaz2.summ,0)) as s0, SUM(IF(tovar.type=1,zakaz2.summ,0)) as s1, SUM(IF(tovar.type=2,zakaz2.summ,0)) as s2, SUM(IF(tovar.type=3,zakaz2.summ,0)) as s3,
	zakaz.user as user, SUM(inkasso.summ) as s
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar,".db_prefix."klient as klient, ".db_prefix."incasso as incasso
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.klient=klient.id and DATE_FORMAT(zakaz.time, '%Y-%m-%d')=DATE_FORMAT(inkasso.time, '%Y-%m-%d') and klient.adm<5 ".$bar->sql."
	GROUP BY dat ORDER BY dat");


$query=DB::sql('SELECT SUM(`summ`) as s FROM '.db_prefix.'incasso WHERE `time`>="'.date('Y-m-d').'" and rej<5');
if($data = DB::fetch_assoc($query))$s['inc']=floatval($data['s']);
   $bar->tbl='(SELECT id FROM '.db_prefix.'idea WHERE user="'.$user_.'"
	UNION
	SELECT idea as id FROM '.db_prefix.'comment WHERE user="'.$user_.'"
	)a LEFT JOIN '.db_prefix.'idea ON a.id='.db_prefix.'idea.id';
   $result=DB::sql('SELECT '.db_prefix.'idea.* FROM '.$bar->tbl.$bar->sql.' ORDER BY '.db_prefix.'idea.id LIMIT '.$bar->start_pos.', '.$bar->perpage);
*/
//$user=[];
$cnt=$s0=$s1=$s2=$s3=$s=$sVisa=$sTerminal=0;
$sOst=Money::GetOst($d_from); // остаток в кассе на начало дня
$user=[];
while ($data = DB::fetch_assoc($query)){
    //echo "<tr><td colspan=10>".var_export($data,!0)."</td></tr>";
    if(coef_){
        $data['s0']*=coef_; // косметика
        $data['s1']*=coef_; // расходка
        $data['s2']*=coef_; // услуги
        $data['s3']*=coef_; // абонементы
    }
    $cnt+=$data['cnt']; $s0+=$data['s0']; $s1+=$data['s1']; $s2+=$data['s2']; $s3+=$data['s3']; $s+=$data['s']; $sVisa+=$data['visa'];
    if(!empty($terminal[date('d.m.Y',strtotime($data['dat']))]))$sTerminal+=$terminal[date('d.m.Y',strtotime($data['dat']))];
    $sVir=$data['s0']+$data['s1']+$data['s2']+$data['s3'];
    if($data['sX']){$sc=($sOst!=$data['sX'] ? " class='hand' style='color:red' title='Сумма ".$sOst."! Пересчитать?'":""); $sOst=floatval($data['sX']);}
    else $sc='';
    $sOst+=$sVir-$data['s']-$data['visa'];
   //print_r($data); echo "<br>";
   //$user[$data['user']]=(isset($user[$data['user']])?$user[$data['user']]:0)+1; // отработано дней
    $user[$data['user']]['cnt']=(isset($user[$data['user']]['cnt'])?$user[$data['user']]['cnt']:0)+$data['cnt']; // количество клиентов
    $user[$data['user']]['sum']=(isset($user[$data['user']]['sum'])?$user[$data['user']]['sum']:0)+$sVir; // Выручка
    $user[$data['user']]['kosm']=(isset($user[$data['user']]['kosm'])?$user[$data['user']]['kosm']:0)+$data['s0']; // косметика

    $d=date('d.m.Y',strtotime($data['dat']));
   echo "<tr>
   <td".(Get::isWeekend($data['dat'])?' class="red"':'').">".date('d.m.y',strtotime($data['dat']))."</td>
   <td>".User::_GetVar($data['user'],'user_name')."</td>
   <td>".$data['cnt']."</td>
   <td class='right it1 hide'>".number_format($data['s0'], 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($data['s3'], 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($data['s1'], 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($data['s2'], 0, '.', ' ')."</td>
   <td class='right'><a style='text-decoration:none' href='/adm/report.php?d_from=".$d."&d_to=".$d."&layer=4' target=_blank>".number_format($sVir-$data['visa'], 0, '.', ' ').
       ($data['opt']?"-<span class='gray' title='Оптом'>".$data['opt']."</span>":"").
       ($data['visa']?"+<div class='visa'>".$data['visa']."</div>":"").
       (empty($terminal[date('d.m.Y',strtotime($data['dat']))])?" - ":"+<div class='visa b'>".$terminal[date('d.m.Y',strtotime($data['dat']))]."</div>").
       "</a></td>
   <td class='right hand' onclick=\"return ajaxLoad('','api.php?inkasso&d_from=".date('d.m.Y',strtotime($data['dat']))."&d_to=".date('d.m.Y',strtotime($data['dat']))."')\">".number_format($data['s'], 0, '.', ' ')."</td>
   <td class='right'".$sc." onclick=\"if(confirm('Пересчитать?'))ajaxLoad('','api.php?RecalcOst&d_from=".date('d.m.Y',strtotime($data['dat']))."')\">"/*.$data['sX'].'~'*/.number_format($sOst, 0, '.', ' ')."</td>
  </tr>";
}
   echo "<tr>
   <td>Итого</td>
   <td>&nbsp;</td>
   <td>".$cnt."</td>
   <td class='right it1 hide'>".number_format($s0, 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($s3, 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($s1, 0, '.', ' ')."</td>
   <td class='right it1 hide'>".number_format($s2, 0, '.', ' ')."</td>
   <td class='right'><b>".number_format(($s0+$s1+$s2+$s3), 0, '.', ' ')."</b></td>
   <td class='right hand' onclick=\"return ajaxLoad('','api.php?inkasso&d_from=".date('d.m.Y',$d_from)."&d_to=".date('d.m.Y',$d_to)."')\">".number_format($s, 0, '.', ' ')."</td>
   <td>&nbsp;</td>
  </tr>
  <tr>
   <td>&nbsp;</td>
   <td>&nbsp;</td>
   <td>&nbsp;</td>
   <td class='it1 hide' colspan='2'>&mdash;&mdash;&mdash; ".number_format($s0+$s3, 0, '.', ' ')." &mdash;&mdash;&mdash;</td>
   <td class='it1 hide' colspan='2'>&mdash;&mdash;&mdash; ".number_format($s1+$s2, 0, '.', ' ')." &mdash;&mdash;&mdash;</td>
   <td><div class='it1 visa hide'>".number_format($sVisa, 0, '.', ' ')."</div> <div class='it1 visa b hide'>".number_format($sTerminal, 0, '.', ' ')."</div></td>
   <td>&nbsp;</td>
   <td>&nbsp;</td>
  </tr>
 </table>

<h3>Средняя сумма чека</h3>
 <table class=\"client-table\">

 <tr>
  <th>Администратор</th>
  <th>Клиентов</th>
  <th>Сумма</th>
  <th>Выручка на человека</th><th>Косметики на сумму</th>
 </tr>
";


foreach($user as $key=>$val)
	if($val['cnt'])echo "\n<tr><td>".User::_GetVar($key,'user_name')."</td><td>".$val['cnt']."</td><td>".number_format($val['sum'], 0, '.', ' ')."</td><td>".round($val['sum']/$val['cnt'],2)."</td><td>".number_format($val['kosm'], 0, '.', ' ')."</td></tr>";
echo "\n</table><br>\n";

if( User::is_admin() ){
// считаю сколько сгорело минут по абонементам, со сроком окончания в этом периоде
  echo "<h3>Всего и сгорело минут</h3>
 <table class=\"client-table\">
 <tr>
  <th>Товар</th>
  <th>Минуты всего</th>
  <th>Минуты сгорело</th>
  <th>Сумма сгорело</th>
  <th>Сумма скидки по абонементам</th>
 </tr>";

    $kOst=[];
    $query=DB::sql("SELECT tovar, SUM(ost) as ost, COUNT(*) as kol FROM `".db_prefix."kart` WHERE dat_end between '".date("Y-m-d",$d_from)."' and '".date("Y-m-d",$d_to)."' GROUP BY tovar");
    while($row = DB::fetch_assoc($query)){
        $tovar=Tovar::GetTovar($row['tovar']);
        $kOst[$tovar['id']][$row['tovar']]['ost']=(empty($kOst[$tovar['id']][$row['tovar']]['ost'])?0:$kOst[$tovar['id']][$row['tovar']]['ost'])+$row['ost'];
        $kOst[$tovar['id']][$row['tovar']]['kol']=(empty($kOst[$tovar['id']][$row['tovar']]['kol'])?0:$kOst[$tovar['id']][$row['tovar']]['kol'])+$row['kol'];
        $kOst[$tovar['id']][$row['tovar']]['ss'] =(empty($kOst[$tovar['id']][$row['tovar']]['ss'] )||empty($tovar['parent'])? 0 : $kOst[$tovar['id']][$row['tovar']]['ss']  +
                    ($row['kol'] * max(1,$tovar['parent']['kol']) * ($tovar['price']/max(1,$tovar['kol']) - $tovar['parent']['price']/max(1,$tovar['parent']['kol']))) ) ; // считаю сумму скидок по этому абонементу
        $kOst[$tovar['id']][$row['tovar']]['proc'] =(empty($tovar['parent'])?0:round(($tovar['price']/max(1,$tovar['kol']) - $tovar['parent']['price']/max(1,$tovar['parent']['kol'])) / $tovar['price']/max(1,$tovar['kol'])*100,0 )) ; // скидка от розничной цены
     //echo "<br>".$row['kol']." * ". max(1,$tovar['parent']['kol'])." * (". $tovar['price']/max(1,$tovar['kol'])." - ". $tovar['parent']['price']."/".$tovar['parent']['kol'].")";
    }
    $ss=$c=0;
    // всего минут загара, в т.ч. по абонементам
    $query=DB::sql("SELECT sum(zakaz2.kol) as kols, zakaz2.tovar as tovar
	FROM ".db_prefix."zakaz2 as zakaz2, ".db_prefix."zakaz as zakaz, ".db_prefix."tovar as tovar
	WHERE zakaz2.zakaz=zakaz.id and zakaz2.tovar=tovar.id and zakaz.time between '".date("Y-m-d",$d_from)."' and '".date("Y-m-d",$d_to)."' and tovar.type=1 GROUP BY tovar");
    while($row = DB::fetch_assoc($query)){
        $tovar=Tovar::GetTovar($row['tovar'],1); // основной товар
        $tovar['kol']=max($tovar['kol'],1);
        if(!$tovar)$tovar['name']="Ошибка в коде товара ".$row['tovar'];
        $row['sum']=$row['ost']=$row['kol']=$row['ss']=0;

        $add='';
        // проверяю есть ли абонементы на этот товар
        if(!empty($kOst[$tovar['id']]))foreach($kOst[$tovar['id']] as $key =>$val){
            $tov=Tovar::GetTovar($key,1); // абонемент
            $s=round($val['ost']*$tov['price']/$tov['kol']);
            $add.="<tr>\n\t<td class='right hand i' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$tov['id']."')\">".$tov['name']." <span>~ ".$val['proc']."%</span></td>
		<td class='i c'>".$val['kol']*$tov['kol']."</td>
		<td class='i c'>".$val['ost']." <span>~ ".round($val['ost']/($val['kol']*$tov['kol'])*100,0) . "%</td>
		<td class='i c'>".$s."</td>
		<td class='i c'>".number_format($val['ss'], 0, '.', ' ')."</td>
		</tr>";
            $c+=$s; $row['sum']+=$s; $row['ost']+=$val['ost']; $row['ss']+=$val['ss']; $row['kol']+=$val['kol']*$tov['kol'];
        }

        echo "<tr>\n\t<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$row['tovar']."')\">".$tovar['name']."</td>
		<td>".$row['kols']." / ".$row['kol']."<span> ~ ".round($row['kol']/$row['kols']*100,0)."%</span></td>
		<td>".$row['ost'].($row['kol']!=0?"<span> ~ ".round($row['ost']/$row['kol']*100,0)."%":"")."</span></td>
		<td>".$row['sum']."</td>
		<td>".number_format($row['ss'], 0, '.', ' ')."</td>
		</tr>".$add;
        $ss+=$row['ss'];
        //$c+=$row['ost']*$tovar['price']/$tovar['kol'];
    }
    echo "<tr>\n\t<td class='left'>Итого</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>".number_format($c, 0, '.', ' ')."</td>
		<td>".number_format($ss, 0, '.', ' ')."</td>
		</tr>";
    echo "</table>"; unset($kOst);

echo "<h3>Себестоимость косметики и расходки:</h3>
 <table class=\"client-table\">
 <tr>
  <th>Товар</th>
  <th>Кол-во</th>
  <th>Цена прихода</th>
  <th>Сумма</th>
 </tr>";
  $cc=0;
$query=DB::sql("SELECT SUM(zakaz2.kol) as s, zakaz2.tovar as tovar
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2
	WHERE zakaz.id=zakaz2.zakaz ".$bar->sql." GROUP BY tovar");
   while($row = DB::fetch_assoc($query)){
        $tovar=Tovar::GetTovar($row['tovar'],1);
        if($tovar['type']==2)continue;
        if(!$tovar){$tovar['show_name']="Ошибка в коде товара ".$row['tovar']; $tovar['price0']=0;}
        echo "<tr>\n\t<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$row['tovar']."')\">".$tovar['show_name']."</td>
            <td>".$row['s']."</td>
            <td>".$tovar['price0']."</td>
            <td>".round($row['s']*$tovar['price0'])."</td>\n</tr>";
        $cc+=$row['s']*$tovar['price0'];
  }
  $cc*=0.9;
  echo "<tr>\n\t<td class='left'>Итого</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>".$cc."</td>\n</tr>";
  echo "</table>";

// rej = 1 - инкассация, 2 - закупка хоз.нужд, 3 - выплата аванса, 4,10 - выплата з.платы, 7 - остаток в кассе на начало дня, 8 - штраф/недостача, 9 - по р/счету
   $query=DB::sql("SELECT SUM(summ) as s FROM ".db_prefix."incasso WHERE rej in (2,3,4,9,10) ".$bar->sql);
   if($row = DB::fetch_assoc($query))$cc+=$row['s'];

   $s0=$s0+$s1+$s2+$s3;
   echo "Выручка: ".number_format($s0, 0, '.', ' ').($sVisa?"<br>в т.ч.по пластиковым картам:".number_format($sVisa, 0, '.', ' ')."~".number_format($sVisa*(100-1.8)/100, 0, '.', ' ')."руб":"")."<br>\n".
	"Затраты: ".number_format($cc, 0, '.', ' ')." (косметика+хоз.+з/пл.)<br>\n".
	"Прибыль: ".number_format($s0-$cc, 0, '.', ' ')."<br>\n";

}

}elseif($layer==1){ // <h2>Расчет зарплаты</h2>
set_time_limit(0);
list($s,$user_0)=Money::GetBalans($d_from, $d_to);
?>
<table class="client-table">
 <tr>
    <th>ФИО<br>сотрудника</th>
    <th>Отработал<br>дней</th>
    <th>Начислено<br>на <?=date("d.m.y",$d_from)?></th>
    <th>Оклад</th>
     <?if($s['old_alg']){?>
    <th>%Косметика</th>
    <th>%Услуги</th>
     <?}else{?>
    <th>Общий заработок</th>
    <th>Индивидуальный заработок</th>
     <?}?>
    <th>Итого<br>за месяц</th>
    <th>Аванс</th>
    <th>Штраф или<br>недостача</th>
    <th>Взял<br>косметики</th>
    <th>К выплате</th>
    <th>Бонус<sup>*</sup></th>
 </tr>
<?
   if($user_0)foreach($user_0 as $u => $v){
	if(!isset($v['adm'])){// бывший сотрудник
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"'>#".$u." ".User::_GetVar($u,'user_name')."</td>".
		"<td>".$v['day']."</td>".
		"<td class='num hand' title=\"Расчет за год\" onclick=\"layerAdd('".User::_GetVar($u,'user_name')."',!1,'/api.php?CalcZP=".$u."')\"'>".outSumm($v['ost0']).($v['zp']?"<br><small ".(($v['zp']-$v['ost0'])>1?"style='color:red'":"")."title='выплата з/пл за прошлый мес.'>(".outSumm($v['zp']).")</small>":"")."</td>".
		"<td colspan='4'>&nbsp;</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?inkasso&user=".$u.$bar->href."')\">".outSumm($v['avans'])."</td>".
		"<td class='num'>".outSumm($v['fine'])."</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?type=0&ushow=".$u.$bar->href."')\">".outSumm($v['s2'])."</td>".
		"<td class='num'>&nbsp;</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?type=1&ushow=".$u.$bar->href."')\">".outSumm($v['bonus'])."</td>".
		"</tr>";
	}elseif($v['adm']>uADM_WORKER){ // руководитель
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"' title=\"".User::$_adm[$v['adm']]."\">".$v['name']."</td>".
		"<td colspan='8'>&nbsp;</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?type=0&ushow=".$u.$bar->href."')\">".outSumm($v['s2'])."</td>".
		"<td class='num'>&nbsp;</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?type=1&ushow=".$u.$bar->href."')\">".outSumm($v['bonus'])."</td>".
		"</tr>";
	}else{
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"' title=\"".User::$_adm[$v['adm']]."\">".$v['name']."</td>".
		"<td>".$v['day']."</td>".
		"<td class='num hand' title=\"Расчет за год\" onclick=\"layerAdd('".User::_GetVar($u,'user_name')."',!1,'/shop/api.php?CalcZP=".$u."')\"'>".outSumm($v['ost0']).($v['zp']?"<br><small ".(($v['zp']-$v['ost0'])>1?"style='color:red'":"")."title='выплата з/пл за прошлый мес.'>(".outSumm($v['zp']).")</small>":"")."</td>".
		"<td class='num'>".outSumm($v['oklad'])."</td>".
		"<td class='num'>".outSumm($v['p1'])."</td>". // %Косметика / общий заработок
		"<td class='num'>".outSumm($v['p2'])."</td>". // %Услуги / индивидуальный заработок
		"<td class='num'>".outSumm($v['oklad'] + $v['p1'] + $v['p2'])."</td>". // Итого начислено
		"<td class='num hand' onclick=\"return ajaxLoad('','api.php?inkasso&user=".$u.$bar->href."')\">".outSumm($v['avans'])."</td>".
		"<td class='num'>".outSumm($v['fine'])."</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','/user/api.php?type=0&ushow=".$u.$bar->href."')\">".$v['s2']."</td>".
		"<td class='num' onclick=\"if(confirm('Пересчитать?'))ajaxLoad('','api.php?RecalcBalans=".$u."')\">".outSumm($v['ost'])."</td>".
		"<td class='num hand' onclick=\"return ajaxLoad('','/user/api.php?ushow=".$u.$bar->href."')\">".outSumm($v['bonus'])."</td>".
		"</tr>";
	}}

/*
   $res=DB::sql("SELECT * from `".db_prefix."klient` WHERE adm>=4");
   while($row = DB::fetch_assoc($res)){
	$u=$row['id'];
	if($row['adm']==4&&!isset($user_0[$u]['day']))continue;
	if(!isset($user_0[$u]['oklad']))$user_0[$u]['oklad']=0;	//z_oklad;
	$user_0[$u]['adm']=$row['adm'];
	$user_0[$u]['name']=$row['name'];
   }
   foreach($user_0 as $u => $v){
	//echo "<br>id=".$u; print_r($v);
	$avans=(isset($v['avans'])?$v['avans']:0);
	$fine=(isset($v['fine'])?$v['fine']:0);
        //$s0=(isset($v['0'])?$v['0']['s0']:0); // косметика по цене продажи zakaz2.summ
        $s_day=(isset($v['day'])?$v['day']:0);

        $s1=(isset($v['0']['s1'])?$v['0']['s1']:0)+
	    (isset($v['3']['s1'])?$v['3']['s1']:0); // взял косметику и расходку по розничной цене(из справочника)
        $s2=(isset($v['0']['s2'])?$v['0']['s2']:0)+
	    (isset($v['3']['s2'])?$v['3']['s2']:0); // взял косметики и расходку по цене прихода
        $s3=(isset($v['1']['s1'])?$v['1']['s1']:0)+
	    (isset($v['2']['s1'])?$v['2']['s1']:0); // услуги по розничной цене(из справочника)
	$bonus=($s1-$s2)+$s3;
	if(!isset($v['adm'])){
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"'>#".$u." ".DB::GetName('klient',$u)."</td>".
		"<td>".$s_day."</td>".
		"<td colspan='7'>&nbsp;</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?type=0&ushow=".$u.$bar->href."')\">".$s2."</td>".
		"<td class='right'>&nbsp;</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?type=1&ushow=".$u.$bar->href."')\">".$bonus."</td>".
		"</tr>";
	}elseif($v['adm']>5){
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"' title=\"".User::$_adm[$v['adm']]."\">".$v['name']."</td>".
		"<td colspan='8'>&nbsp;</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?type=0&ushow=".$u.$bar->href."')\">".$s2."</td>".
		"<td class='right'>&nbsp;</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?type=1&ushow=".$u.$bar->href."')\">".$bonus."</td>".
		"</tr>";
	}else{
	   $adm_proc=($adm_count!=2&&$adm_days>0?($s_day/$adm_days):($s_day>0?0.5:0));
	   if($adm_count>2)$v['oklad']=round($v['oklad']*2*$adm_proc,2);
	   $p1=($s['s0']+$s['s3'])*z_proc0*$adm_proc/100; // % от общей суммы продаж косметики
	   $p2=($s['s1']+$s['s2'])*z_proc1*$adm_proc/100; // % от общей суммы продаж услуг
	   echo "\n <tr><td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$u."')\"' title=\"".User::$_adm[$v['adm']]."\">".$v['name']."</td>".
		"<td>".$s_day."</td>".
		"<td class='right'>".number_format(0, 2, '.', ' ')."</td>".
		"<td class='right'>".$v['oklad']."</td>".
		"<td class='right'>".number_format($p1, 2, '.', ' ')."</td>".
		"<td class='right'>".number_format($p2, 2, '.', ' ')."</td>".
		"<td class='right'>".number_format($v['oklad'] + $p1 + $p2, 2, '.', ' ')."</td>".
		"<td class='right'>".$avans."</td>".
		"<td class='right'>".$fine."</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?type=0&ushow=".$u.$bar->href."')\">".$s2."</td>".
		"<td class='right'>".number_format($v['oklad'] + $p1 + $p2 - $s2 - $avans- $fine, 2, '.', ' ')."</td>".
		"<td class='right hand' onclick=\"return ajaxLoad('','api.php?ushow=".$u.$bar->href."')\">".$bonus."</td>".
		"</tr>";
	}}
*/

echo "\n</table>\n<p>*<i>В бонус считается сумма всех услуг и разница по косметике от розничной цены.</i></p>
<p>Выплата аванса: в свою смену после 25 числа текущего месяца.<br>
Выплата зарплаты: в свою смену после 10 числа следующего месяца.<br>
Если в течение текущего месяца работало более 2х администраторов, то оклад и процент считаются пропорционально отработанным дням.</p>";

}elseif($layer==2 || $layer==3){ // Скидки или Сотрудники
?>
<table class="client-table">
 <tr>
  <th>Дата</th>
  <th>Клиент <span>(скидка К/У)</span></th>
  <th>Администратор</th>
  <th>Товар/услуга</th>
  <th>Сумма по прайсу</th>
  <th>Сумма со скидкой</th>
  <th>%скидки</th>
 </tr>

<?
   $res=DB::sql("SELECT zakaz.time as time, zakaz.user as user, zakaz.manager as manager, zakaz2.tovar as tovar, zakaz2.kol as kol, zakaz2.kart as kart, zakaz2.sertif as sertif,
	user.fullname as klient_name, user.discount0 as discount0, user.discount1 as discount1, user.adm as adm,
	tovar.maxdiscount as maxdiscount, tovar.name as tovar_name, tovar.type as type, tovar.ed as ed,
	zakaz2.kol*zakaz2.price as s0, tovar.price*zakaz2.kol as s1, tovar.price0*zakaz2.kol as s2, zakaz2.comment as comment
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar, ".db_prefix."user as user
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.user=user.id ".str_replace('time','zakaz.time',$bar->sql).
       " and zakaz2.kol*zakaz2.price <> tovar.price*zakaz2.kol and (user.adm".($layer==2?"<".uADM_WORKER." or user.adm IS NULL":">=".uADM_WORKER).")
	ORDER BY time");
   $sum=0;
   while($row = DB::fetch_assoc($res)){
       $c=($row['comment']?"<span class='blue'>".htmlspecialchars($row['comment'])."</span> ":"");
        if($row['kart']&&$row['type']==1){// услуга по абонементу
            if($kart=DB::Select("kart","id='".$row['kart']."'")){
                // проверяю был ли продан этот абонемент
                $query=DB::sql("SELECT zakaz2.*, tovar.type as type, tovar.price as price
                    FROM ".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar
                    WHERE zakaz2.tovar=tovar.id and zakaz2.kart='".$row['kart']."' and tovar.type=2
                    LIMIT 1");
                if(!($data=DB::fetch_assoc($query))){
                    $c.="Абонемент <a href='/adm/kart.php?form=".$row['kart']."' class=\"ajax\">№ ".$row['kart']."</a> от ".date('d.m.y',strtotime($kart['time']))." не был продан! <a href='/shop/api.php?kart&show=".$row['kart']."' class=\"icon abonement right ajax\" title=\"Посещения\">";
                }elseif(strtotime($row['time'])>strtotime("+1 day",strtotime($kart['dat_end']))){ // todo проверить оставались ли минуты
                    $c.="Абонемент <a href='/adm/kart.php?form=".$row['kart']."' class=\"ajax\">№ ".$row['kart']."</a> от ".date('d.m.y',strtotime($kart['time']))." до ".date('d.m.y',strtotime($kart['dat_end']))." просрочен! <a href='/shop/api.php?kart&show=".$row['kart']."' class=\"icon abonement right ajax\" title=\"Посещения\">";
                }elseif($data['summ']<($data['price']*(100-$row['discount1'])/100)){
                    $c.="Абонемент <a href='/adm/kart.php?form=".$row['kart']."' class=\"ajax\">№ ".$row['kart']."</a> от ".date('d.m.y',strtotime($kart['time']))." был продан за ".$data['summ']."руб.! <a href='/shop/api.php?kart&show=".$row['kart']."' class=\"icon abonement right ajax\" title=\"Посещения\">";
                }else  continue;
           }else $c.="Абонемент № ".$row['kart']." не выдавался!";
           $style=" style='color:blue'";
        }elseif($row['sertif']&&$row['type']==1){// услуга по сертификату
            if($kart=DB::Select("kart","id='".$row['sertif']."'")){
                // проверяю был ли продан этот сертификат
                $query=DB::sql("SELECT zakaz2.*, tovar.type as type, tovar.price as price
                    FROM ".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar
                    WHERE zakaz2.tovar=tovar.id and zakaz2.kart='".$row['sertif']."' and tovar.type=2
                    LIMIT 1");
                if(!($data=DB::fetch_assoc($query))){
                    $c.="Сертификат <a href='/adm/kart.php?form=".$row['sertif']."' class=\"ajax\">№ ".$row['kart']."</a> от ".date('d.m.y',strtotime($kart['time']))." не был продан! <a href='/shop/api.php?kart&show=".$row['sertif']."' class=\"icon abonement right ajax\" title=\"Посещения\">";
                }elseif($data['summ']<($data['price']*(100-$row['discount1'])/100)){
                    $c.="Сертификат <a href='/adm/kart.php?form=".$row['sertif']."' class=\"ajax\">№ ".$row['kart']."</a> от ".date('d.m.y',strtotime($kart['time']))." был продан за ".$data['summ']."руб.! <a href='/shop/api.php?kart&show=".$row['sertif']."' class=\"icon abonement right ajax\" title=\"Посещения\">";
                }else  continue;
            }else $c.="Сертификат № ".$row['kart']." не выдавался!";
            $style=" style='color:blue'";
	    }else{
            $sum+=$row['s1']-$row['s0'];
            // type = 0 - товар, 1 - услуга, 2-абонемент, 3 - расходка
            if($layer==3&&($row['type']==0||$row['type']==3))$row['s0']=$row['s2'];
            $discount=($row['s1']>0?round(($row['s1']-$row['s0'])/$row['s1']*100,2):100);
            if($layer==3) ;
            elseif($row['s2']>0 && $row['s0']<$row['s2'])$c.="Продажа ниже закупки: ".$row['s2']."!";
            elseif($row['maxdiscount']>0&&$discount>$row['maxdiscount'])$c.="Скидка ".$discount."%, максимальная скидка товара ".$row['maxdiscount']."!";
            elseif($row['adm']==uADM_OPT && ($row['type']==0||$row['type']==3))$c.="Оптовая стоимость: ".round($row['s2']*(100+tOPT_PROC)/100,($row['type']==tTYPE_RASX?1:0),PHP_ROUND_HALF_UP)."!";
            elseif($discount>$d=intval($row['type']==1||$row['type']==2?$row['discount1']:$row['discount0']))$c.="Скидка ".$discount."%".($row['adm']==uADM_OPT?"":", скидка клиента ".$d."!");
            $style='';
        }
	$sc=($layer==3 && $row['adm']!=uADM_ADMIN && $row['user']!=$row['manager'] ? " style='color:red' title='Не в свою смену'":""); // "Не в свою смену!";
	echo "\n <tr".$style.">".
		"<td>".date('d.m.y H:i',strtotime($row['time']))."</td>".
		"<td class='left hand'".$sc." onclick=\"return ajaxLoad('','/user/?id=".$row['user']."')\">".$row['klient_name'].($row['discount0']||$row['discount1']?" (<span".($row['type']==0||$row['type']==3?" style='color:Green;font-weight:bold;'":"").">".intval($row['discount0'])."</span>/<span".($row['type']==1||$row['type']==2?" style='color:Green;font-weight:bold;'":"").">".intval($row['discount1'])."</span>)":"")."</td>".
		"<td class='left'".$sc.">".User::_GetVar($row['manager'],'user_name')."</td>".
		"<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$row['tovar']."')\">".$row['tovar_name'].($row['kol']==1?"":" ".$row['kol'].' '.($row['type']==0?'шт':$row['ed']))."</td>".
		"<td class='right'>".number_format($row['s1'], 2, '.', ' ')."</td>".
		"<td class='right'>".number_format($row['s0'], 2, '.', ' ')."<span>(-".($row['s1']-$row['s0']).")</span></td>".
		"<td class='right'>".($row['s1']>0?number_format((($row['s1']-$row['s0'])/$row['s1']*100), 2, '.', ''):'??')."</td>".
		"</tr>";
	if(trim($c))echo "\n <tr><td colspan='7' class='i' style='padding-top:0'>".$c."</td><tr>";
	}
echo "\n</table>\n<p>Сумма скидок <b>".number_format($sum, 2, '.', ' ')."</b> руб.</p>";


}elseif($layer==4){ // <h2>Операции</h2>
    $tov=(isset($_GET['tovar'])?intval($_GET['tovar']):0);
    echo "\n\t<select name=\"tovar\" onchange=\"url=document.location.href;url=(url+'&').replace(/tovar=(.*?)&/gi,'').replace(/&&/gi,'&')+'tovar='+encodeURIComponent(this.options[this.selectedIndex].value);return LoadLayer('layer4',url);\">".
        "\n\t\t<option value=\"0\"".($tov?'':' selected').">--все--</option>";
    $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=1 ORDER BY name");
    while ($tovar=DB::fetch_assoc($query)){
        $tovar=new Tovar($tovar);
        echo "\n\t\t<option value=\"".$tovar->id."\"".($tovar->id==$tov?' selected':'').">".$tovar->show_name."</option>";
    }
    echo <<< END
    </select>
 <table class="client-table">
 <tr>
  <th>Время</th>
  <th>Администратор</th>
  <th>ФИО</th>
  <th>Товар / Услуга</th>
  <th>Кабинка</th>
  <th>Абонемент №</th>
  <th>Кол-во</th>
  <th>Скидка</th>
  <th>Сумма</th>
  <th>&nbsp;</th>
 </tr>
END;
$old_id='';// еcли дата, время и клиент то-же не выводить повторы
$summ=$visa=$summ_adm=$summ_opt=0;
   $res=DB::sql("SELECT zakaz2.*,(zakaz2.kol*zakaz2.price) as summ,zakaz.time,zakaz.manager,zakaz.user,zakaz.visa
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2
	WHERE zakaz.id=zakaz2.zakaz ".($tov?"and zakaz2.tovar='".$tov."' ":"").$bar->sql."
	ORDER BY zakaz2.id DESC ");
   while($data = DB::fetch_assoc($res)){
//echo "<br>\n";print_r($data);
       $klient=new User($data['user']);
   $old=($old_id==$data['zakaz']); $old_id=$data['zakaz'];
   echo "\n<tr id=\"id".$data['id']."\" ".($old?'':'style="border-top:#9fbddd 1px solid;"')."onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">";
   $tovar=new Tovar($data['tovar']);
   if($tovar){
	echo "<td>".($old?'&nbsp':date('d.m.y H:i',strtotime($data['time'])).($data['visa']?'<div class="visa">'.$data['visa'].'</div>':''))."</td>
	<td>".($old?'&nbsp':User::_GetVar($data['manager'],'user_name'))."</td>
	<td class='left hand' onclick=\"return ajaxLoad('','/user/?id=".$data['user']."')\">".($old?'&nbsp':$klient->user_name)."</td>
	<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$data['tovar']."')\">".$tovar->show_name."</td>
	<td>".$data['device']."</td>
	<td".($data['kart']?" class='hand' onclick=\"return ajaxLoad('','/adm/kart.php?form=".$data['kart']."')\">".$data['kart']:'>')."</td>
	<td>".$data['kol']."</td>
	<td title='".htmlspecialchars($data['comment'])."'>".($data['discount']?$data['discount']."%":'')."</td>
	<td>".($data['summ']?number_format($data['summ'], ($data['summ']==intval($data['summ'])?0:2), '.', ' '):'')."</td>";
  }else{
        echo "<td colspan='8'>Ошибка в коде товара ".$data['tovar']."</td>";
   }
       $summ+=$data['summ'];
       if(!$old)$visa+=$data['visa'];
       if($klient->adm==uADM_OPT)$summ_opt+=$data['summ'];
       if($klient->adm>=uADM_WORKER)$summ_adm+=$data['summ'];
  echo "\n<td class=\"edit-del\">
	<a href='/api.php?tbl=zakaz&del=".$data['id']."' class=\"icon del right confirm\" title=\"Удалить\">
	</tr>";
}
?>
 </table>
    <div class="right">Итого <?=$summ.($visa ?', в т.ч. пластиком:'.$visa.', кредит:'.($summ_adm).', за наличные:'.($summ-$visa-$summ_adm).($summ_opt?' (в т.ч.опт:'.$summ_opt.', розница:'.($summ-$visa-$summ_adm-$summ_opt).')':'') : '')?></div>
<?
echo "\n<br class='clear'>";

}elseif($layer==5){ // <h2>Счетчики</h2>
    $tov=(isset($_GET['tovar'])?intval($_GET['tovar']):0);
    echo "\n\t<select name=\"tovar\" onchange=\"url=document.location.href;url=(url+'&').replace(/tovar=(.*?)&/gi,'').replace(/&&/gi,'&')+'tovar='+encodeURIComponent(this.options[this.selectedIndex].value);return LoadLayer('layer5',url);\">".
         "\n\t\t<option value=\"0\"".($tov?'':' selected').">--все--</option>";
    $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=1 ORDER BY name");
    while ($tovar=DB::fetch_assoc($query)){
        $tovar=new Tovar($tovar);
        echo "\n\t\t<option value=\"".$tovar->id."\"".($tovar->id==$tov?' selected':'').">".$tovar->show_name."</option>";
    }
echo <<< END
    </select>
<table class="client-table">
 <tr>
  <th>Дата с по</th>
  <th>Услуга</th>
  <th>Кабинка</th>
  <th>Минуты по счетчику</th>
  <th>Минуты по базе</th>
  <th>&plusmn;</th>
  <th>Запуски по счетчику</th>
  <th>Запуски по базе</th>
  <th>&plusmn;</th>
 </tr>
END;
   $res=DB::sql("SELECT * FROM `".db_prefix."counters` WHERE ".($tov?"tovar='".$tov."'":"1=1 ").$bar->sql." ORDER BY tovar,device,time");
   $old='';
   while($row = DB::fetch_assoc($res)){
	$tovar=new Tovar($row['tovar']);
	// ищу прошлое значение счетчика и просчитываю реализацию этой услуги между ними
	if($row0=DB::Select("counters","time<'".$row['time']."' and device='".$row['device']."' and tovar='".$row['tovar']."' ORDER BY time DESC")){
		$row['time0']=$row0['time'];
		$row['c1']=$row0['counter1'];
		$row['c2']=$row0['counter2'];
	}else{	$row['time0']=''; $row['c1']=$row['c2']=0;}
	$res0=DB::sql("SELECT zakaz.time as time, zakaz2.device as device, SUM(zakaz2.kol) as s1, COUNT(zakaz2.id) as s2
	FROM ".db_prefix."zakaz2 as zakaz2,".db_prefix."zakaz as zakaz
	WHERE zakaz2.zakaz=zakaz.id and zakaz2.tovar='".$row['tovar']."' and device='".$row['device']."' and time between '".$row['time0']."' and '".$row['time']."'");
	if($row0=DB::fetch_assoc($res0)){
		$row['s1']=$row0['s1'];
		$row['s2']=$row0['s2'];
	}else{
		$row['s1']=$row['s2']=0;
	}
       $d1=($row['counter1']-$row['c1']);
       if(defined('div_counter'.$row['device'])){
           $d1/=constant("div_counter".$row['device']);
       }
	$s1=($d1-$row['s1']); if($s1+10000>-100&&$s1+10000<100)$s1=$s1+10000; elseif($s1+100000>-100&&$s1+100000<100)$s1=$s1+100000;
	$s2=(($row['counter2']-$row['c2'])-$row['s2']);
	echo "<tr".($old==$row['tovar'].$row['device']?"":" style='background-color:lightcyan'").">
	<td>".dateForShow($row['time0'])." - ".dateForShow($row['time'])."</td>
	<td class='left'>".$tovar->show_name."</td>
	<td>".$row['device']."</td>
	<td><span>".number_format($row['counter1'], 0, '.', ' ')."-".number_format($row['c1'], 0, '.', ' ')."=</span>".number_format($d1, 0, '.', ' ')."</td>
	<td class='hand' onclick=\"return ajaxLoad('','api.php?d_from=".$row['time0']."&d_to=".$row['time']."&tovar=".$row['tovar']."&device=".$row['device']."')\">".$row['s1']."</td>
	<td".($s1>0?" style='color:red;font-weight:bold;'":($s1<0?" style='color:green;font-weight:bold;'":"")).">".$s1."</td>
	<td><span>".number_format($row['counter2'], 0, '.', ' ')."-".number_format($row['c2'], 0, '.', ' ')."=</span>".number_format($row['counter2']-$row['c2'], 0, '.', ' ')."</td>
	<td class='hand' onclick=\"return ajaxLoad('','api.php?d_from=".$row['time0']."&d_to=".$row['time']."&tovar=".$row['tovar']."&device=".$row['device']."')\">".$row['s2']."</td>
	<td".($s2>0?" style='color:red;font-weight:bold;'":($s2<0?" style='color:green;font-weight:bold;'":"")).">".$s2."</td>
	</tr>";
	$old=$row['tovar'].$row['device'];
   }
echo "\n</table>\n";

}elseif($layer==6){ // <h2>Протокол</h2>
    $tov=(isset($_GET['tovar'])?intval($_GET['tovar']):0);
    $user=(isset($_GET['user'])?intval($_GET['user']):0);
echo "
<table class=\"client-table\">
  <thead>
  <tr><th>#<th>Дата <br> Логин<th>Инфо<th>До / После</tr>
  </thead>
  <tbody>";
   //$query=DB::sql('SELECT * FROM '.$bar->tbl.$bar->sql.' ORDER BY time DESC LIMIT '.$bar->start_pos.', '.$bar->perpage);
   $res=DB::sql("SELECT * FROM `".db_prefix."log` WHERE ".($tov?"tbl='tovar' and id='".$tov."'":($user?"tbl='user' and id='".$user."'":"1=1 ")).$bar->sql." ORDER BY time DESC");
   while($row = DB::fetch_assoc($res)){
	if($row['tbl']=='tovar')$add=" class='hand' onclick=\"return ajaxLoad('','/adm/tovar.php?form=".$row['id']."')\"";
	elseif($row['tbl']=='user')$add=" class='hand' onclick=\"return ajaxLoad('','/user/?id=".$row['id']."')\"";
	elseif($row['tbl']=='kart')$add=" class='hand' onclick=\"return ajaxLoad('','/adm/kart.php?form=".$row['id']."')\"";
	else $add='';
	echo "<tr><td".$add.">".$row['tbl']."<br>".$row['id']."</td>
	<td class='hand' onclick=\"return ajaxLoad('','/user/?id=".$row['user']."')\">".
	date("d.m.y H:i",strtotime($row['time']))." ".
        User::_GetVar($row['user'],'user_name')."</td>".
	"<td>".$row['subject']."</td>".
	"<td class='left'><div class='row1'>".$row['before']."</div><div class='row2'>".$row['after']."</div></td>".
	"</td>".
	"</tr>\n";
}
echo "\n</tbody></table>\n";

}elseif($layer==7){ // <h2>Свод за год</h2>

    ?>
    <div class="right">
    <a href="?d_from=01.01.2013&d_to=31.12.2013&layer=7">2013</a>
    <a href="?d_from=01.01.2014&d_to=31.12.2014&layer=7">2014</a>
    <a href="?d_from=01.01.2015&d_to=31.12.2015&layer=7">2015</a>
    </div>
    <table class="client-table">
        <tr>
            <th>Дата</th>
            <th>Клиентов</th>
            <th>Выручка</th>
            <th>Затраты</th>
            <th>Доход</th>
        </tr>
<?
$query0=DB::sql($q="SELECT COUNT(DISTINCT zakaz.id) as cnt, DATE_FORMAT(zakaz.time, '%Y-%m') as dat,
	SUM(zakaz2.kol*zakaz2.price)".(coef_?'*'.coef_:'')." as s0
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2,".db_prefix."tovar as tovar,".db_prefix."user as user
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.user=user.id and (user.adm<".uADM_WORKER." and user.adm<>".uADM_OPT." or user.adm IS NULL) ". str_replace('time','zakaz.time',$bar->sql). "
	GROUP BY dat ORDER BY dat");
// исключить администраторов и руководство

        //echo "<br>q=".$q."<br>";
$sVir=$sRasx=$cnt=0;
$srej=[];
while ($data = DB::fetch_assoc($query0)){
    $Vir=$data['s0'];
    if(db_prefix=='zr_')$Rasx=50000+750+2000+2000+1000+10000+1000+4000-15000; // аренда+телефон+свет+охрана+бухи+лампы,стартеры(120/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)-субаренда
    elseif(db_prefix=='z2_'&&strtotime($data['dat'])<strtotime(date("01.01.2014")))$Rasx=30000+100+5000+1000+5000+500+4000; // аренда+телефон+свет+бухи+лампы,стартеры(120/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)
    elseif(db_prefix=='z2_'&&strtotime($data['dat'])<strtotime(date("01.03.2016")))$Rasx=23000+100+5000+1000+5000+500+4000; // аренда+телефон+свет+бухи+лампы,стартеры(120/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)
    elseif(db_prefix=='z2_')$Rasx=32000+100+5000+1000+5000+500+4000; // аренда+телефон+свет+бухи+лампы,стартеры(120/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)
    elseif(db_prefix=='z3_')$Rasx=40002+100+1700+50+2000+1800+1000+5800+500+4000-12000; // аренда+телефон+свет+вода+охрана+ТСЖ+бухи+лампы,стартеры(70/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)-субаренда
    else die('Нет такого '.db_prefix);
    $tit='аренда+телефон+свет+вода+охрана+ТСЖ+бухи+лампы,стартеры(70/12)+расходка+налоги(з.пл+вмененка+упрощенка+БСО)-субаренда='.$Rasx;
    // вычесть с/с косметики и расходки
    $query=DB::sql("SELECT SUM(zakaz2.kol*tovar.price0*IF(DATE_FORMAT(zakaz.time, '%Y')<2015,0.5,1)) as s, DATE_FORMAT(zakaz.time, '%Y-%m') as dat
	FROM ".db_prefix."zakaz as zakaz,".db_prefix."zakaz2 as zakaz2, ".db_prefix."tovar as tovar
	WHERE zakaz.id=zakaz2.zakaz and tovar.id=zakaz2.tovar and DATE_FORMAT(zakaz.time, '%Y-%m')='".$data['dat']."' and tovar.type<>".tTYPE_ABON." GROUP BY dat");
    $s=0;
    if($row = DB::fetch_assoc($query)){
        //echo "<br>c/c=".$q."<br>".var_export($row,!0);
        $s+=$row['s']*0.9;
    }
    $tit.=', с/с косметики и расходки='.$s;
    $Rasx+=$s;
    // rej = 1 - инкассация, 2 - закупка хоз.нужд, 3 - выплата аванса, 4 - выплата з.платы, 7 - остаток в кассе на начало дня, 8 - штраф/недостача, 9 - по р/счету, 10-выплата з/п на пластик
    $query=DB::sql("SELECT SUM(summ) as s, rej FROM ".db_prefix."incasso WHERE rej in (2,3,4,9,10) and DATE_FORMAT(time, '%Y-%m')='".$data['dat']."' GROUP BY rej ORDER BY rej");
    $s=0;
    while($row = DB::fetch_assoc($query)){
        //echo "<br>затраты=".$q."<br>".var_export($row,!0);
        $srej[$row['rej']]=(empty($srej[$row['rej']])?0:$srej[$row['rej']])+$row['s'];
        $s+=$row['s'];
    }
    $tit.=', з/пл, хоз.нужды ='.$s;
    $Rasx+=$s;

    echo "<tr class='hand' onclick='window.open(\"/report.php?d_from=01.".date('m.Y',strtotime($data['dat']))."&layer=0\")'>
   <td>".date('m.y',strtotime($data['dat']))."</td>
   <td>".$data['cnt']."</td>
   <td class='right'>".number_format($Vir, 0, '.', ' ')."</td>
   <td class='right' title='".$tit."'>".number_format($Rasx, 0, '.', ' ')."</td>
   <td class='right'>".number_format($Vir-$Rasx, 0, '.', ' ')."</td>
  </tr>";
    $cnt+=$data['cnt'];
    $sRasx+=$Rasx;
    $sVir+=$Vir;
}
   echo "<tr>
   <td>Итого</td>
   <td>".$cnt."</td>
   <td class='right'>".number_format($sVir, 0, '.', ' ')."</td>
   <td class='right'>".number_format($sRasx, 0, '.', ' ')."</td>
   <td class='right'>".number_format($sVir-$sRasx, 0, '.', ' ')."</td>
  </tr>
 </table><br><table><caption>Инкасации/затраты</caption>";
    global $z_incasso;
foreach($srej as $key=>$val)echo "<tr>
    <td>".$z_incasso[$key]."</td>
    <td class='right hand' onclick=\"return ajaxLoad('','/adm/api.php?inkasso&d_from=".date('d.m.Y',$d_from)."&d_to=".date('d.m.Y',$d_to)."&rej=".$key."')\">".number_format($val, 0, '.', ' ')."</td>
</tr>";
echo "</table>";
/*На год по всем салонам:
Шапочки, коврики  10 000
Бум.полотенца 4 000
Стикини на год 3 000
Тапочки 15680+3260=18940 (5600пар, = 3,39 за пару)
Косметика:
09.14 39718
12.14 69134*/

}elseif($layer==8){ // <h2>Свод за год</h2>
    // api.php?CalcZP=
}

}
