<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

// если это первое наполнение то спрашиваю дату продажи и остаток
$first_work='';//($_SESSION['user']['adm']==9||date("Y-m-d")<"2013-01-15"?'':' readonly');

if(isset($_POST['id'])){
	$id=Kart::Add(intval($_POST['tovar']), intval($_POST['klient_cs']), intval($_POST['id']));
	if($id>0){
		if(!$first_work&&$_POST['ost']&&$_POST['time']){
		   if(!($tovar = DB::Select("tovar","id='".intval($_POST['tovar'])."' and type=".tTYPE_ABON)))Out::err("Неверный код услуги!");
		   $time=strtotime(str_replace(',','.',str_replace('/','.',str_replace('-','.',$_POST['time']))));
		   if($time>time())Out::err("Дата выдачи не может быть больше текущей!");
		   $dat_end=date("Y-m-d",strtotime("+".$tovar['srok']." month",$time));
            if(User::is_admin()&& $_POST['dat_end'] && date("Y-m-d",strtotime($_POST['dat_end']))>$dat_end)$dat_end=date("Y-m-d",strtotime($_POST['dat_end']));
		   DB::log('kart',$id, '', '', ['dat_end'=>$dat_end, 'ost'=>$_POST['ost']]);
		   DB::sql("UPDATE `".db_prefix."kart`
			SET `time`='".date('Y-m-d H:i:s',$time)."', `dat_end`='".$dat_end."', `ost`='".$_POST['ost']."'
			WHERE id='".$id."'");
		}
		Out::mes("Сохранил, N=".$id,"reload()");
	}else Out::err("Не сохранил!");

}elseif(isset($_GET['form'])){//ajax- запрос формы добавления
    $id=intval($_GET['form']);
    if($id>0){
	if(($data = DB::Select('kart',$id))){
	    //$data['time']=date('d.m.y H:i:s',strtotime($data['time']));
	    $data['time']=date('d.m.Y H:i:s',strtotime($data['time']));
	    $data['dat_end']=date('d.m.Y',strtotime($data['dat_end']));
        $klient=new User($data['user']); if(!$klient)Out::err("Нет такого!");
	    $data['user']=$klient->fullname." ".Out::format_phone($klient->tel);
		$data['user_cs']=$klient->id;
	}else {
        $data['user']=$data['user_cs']='';
        $data['id']='';
    }
	   $data['save']="Сохранить";

    }else{
	   $data['save']="Добавить";
	   $data['id']='';$data['user']='';$data['user_cs']='';$data['tovar']='';$data['time']=date('d.m.Y H:i:s');$data['dat_end']='';$data['ost']='';
    }
   if(!isset($_SESSION['message'])||empty($_SESSION['message']))$_SESSION['message']='Поля "номер абонемента", "выдан", "действителен до" и "остаток" будут заполнены автоматически.';
	foreach($data  as $key => $value)$data[$key]=str_replace('"',"'",$value);

echo <<< HTML
<h2>Абонемент</h2>
<form name="kart" id="kart" class="client" action="/adm/kart.php" method="POST" onsubmit="return SendForm('kart',this);">
<table>
	<tr>
		<td>Номер:</td>
		<td></td>
		<td><input name="id" value="{$data['id']}"></td>
	</tr>
	<tr>
		<td class='left hand' onclick="return ajaxLoad('','/user/?id={$data['user_cs']}')">Ф.И.О.:</td>
		<td></td>
		<td><input name="klient" size="25" href="/shop/api.php?get=" value="{$data['user']}" value_cs="{$data['user_cs']}"></td>

	</tr>
	<tr>
		<td>Вид абонемента<a href="#" onclick="alert('Для добавления обратитесь к администратору!');return false">[+]</a>:</td>
		<td></td>
		<td>
		<select name="tovar">
HTML;
echo "			<option value=\"0\" disabled".($data['tovar']?'':' selected').">--выберите вид абонемента--</option>";
$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=".tTYPE_ABON." ORDER BY name");
while ($tovar = DB::fetch_assoc($query)){
        $tovar=new Tovar($tovar);
	echo "\n\t\t\t<option value=\"".$tovar->id."\"".($tovar->id==$data['tovar']?' selected':'').">".$tovar->show_name."</option>";
}
    $readonly=(User::is_admin()?'':'readonly');
echo <<< END
		</select>
	</tr>
	<tr>
		<td>Выдан:</td>
		<td></td>
		<td><input name="time" {$first_work} value="{$data['time']}" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" /></td>
	</tr>
	<tr>
		<td>Действителен до:</td>
		<td></td>
		<td><input name="dat_end" {$readonly} value="{$data['dat_end']}"></td>
	</tr>
	<tr>
		<td class="hand" title="Посещения" onclick="return ajaxLoad('','api.php?kart&show={$data['id']}');">Остаток:</td>
		<td></td>
		<td><input name="ost" {$first_work} value="{$data['ost']}"></td>
	</tr>
	<tr><td colspan="3">
		<input type="reset" value="Копировать" class="btn red right" style="width:auto;" onclick="return !add(this.form);">
		<input type="submit" class="btn green right" style="width:auto;" value="{$data['save']}"></td>
	</tr>

</table>
<div id="info">{$_SESSION['message']}</div>
</form>
END;
	$_SESSION['message']='';
	exit;
}


$title="Абонементы";
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
echo "\n<h1>".$title."</h1>\n";
?>
<input id='add' type="submit" class="button" style="width:auto;" value="Добавить" onclick="return ajaxLoad('','/adm/kart.php?form');">
<br class="clear">
<?
   $ost=(isset($_GET['ost'])&&$_GET['ost']!=''?1:'');
   $ord=@$_GET['ord']; if(!preg_match('/(id|time|dat_end|user|tovar|ost)/', $ord))$ord='id';
   $desc=(isset($_GET['desc'])?' DESC':'');  // &uarr; &darr;
   $q=(isset($_GET['q'])&&$_GET['q']!==''?urldecode($_GET['q']):'');
   $tovar=(isset($_GET['tovar'])&&$_GET['tovar']>0?intval($_GET['tovar']):'');
   $bar=new kdg_bar();
   $bar->tbl=db_prefix.'kart';
   $bar->href=($ord?'&ord='.$ord:'').($desc?'&desc':'').($q?'&q='.urlencode($q):'').($ost?'&ost=1':'').($tovar>0?'&tovar='.$tovar:''); if($bar->href)$bar->href='?'.substr($bar->href,1);

   if($q)$bar->sql=' WHERE id="'.addslashes(strtolower($q)).'" ';
   elseif(!$ost)$bar->sql=' WHERE dat_end>"'.date('Y-m-d',strtotime('-7 days')).'"';
   if($tovar>0)$bar->sql.=($bar->sql?' and':' WHERE').' tovar="'.$tovar.'" ';

   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=(isset($_REQUEST['d_to'])?strtotime($_REQUEST['d_to']):time());
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }else{
	$d_to=time();
	$d_from=strtotime(date("d.m.Y",strtotime("-1 year")));
   }
   $caption="Продажи с ".date("d.m.Y",$d_from)." по ".date("d.m.Y",$d_to);
   $bar->sql.=($bar->sql?' and':' WHERE').' time between "'.date("Y-m-d 00:00:00",$d_from).'" and "'.date("Y-m-d 23:59:59",$d_to).'"';
   if(isset($_REQUEST['d_from']))$bar->href.='&d_from='.date("d.m.Y",$d_from);
   if(isset($_REQUEST['d_to']))$bar->href.='&d_to='.date("d.m.Y",$d_to);


   //if($q)$bar->sql=' WHERE lower(name) LIKE "'.addslashes(strtolower($q)).'%" or tel LIKE "%'.addslashes(strtolower($q)).'%" ';
   $bar->perpage=20;
   $bar_out=$bar->out();
   echo "<div>\nвсего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."<wbr>
<form method=\"get\">
<select name=\"tovar\">";
echo "<option value=\"0\" disabled".($tovar?'':' selected').">--выберите вид абонемента--</option>";
$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=".tTYPE_ABON." ORDER BY name");
while ($tov = DB::fetch_assoc($query)){
        $tov=new Tovar($tov);
	echo "\n\t\t\t<option value=\"".$tov->id."\"".($tov->id==$tovar?' selected':'').">".$tov->show_name."</option>";
}
echo "</select>
№ абонемента:<input type=\"search\" name=\"q\" size=\"10\" value=\"".$q."\">
<br>
Выданные за период
с <input type=\"date\" name=\"d_from\" size=10 value=\"".date("d.m.Y",$d_from)."\" onfocus=\"_Calendar.lcs(this)\" onclick=\"_Calendar.lcs(event)\" ontouch=\"_Calendar.lcs(event)\" />
по <input type=\"date\" name=\"d_to\" size=10 value=\"".date("d.m.Y",$d_to)."\" onfocus=\"_Calendar.lcs(this)\" onclick=\"_Calendar.lcs(event)\" ontouch=\"_Calendar.lcs(event)\" />
<label><input type=\"checkbox\" name=\"ost\" value=\"1\"".($ost?' checked':'')."> показать просроченные</label>
<br>

<input type=\"submit\" class=\"button\" style=\"width:auto;\" value=\"Найти\">
<input type=\"reset\" class=\"button\" style=\"width:auto;\" value=\"Сброс\" onclick=\"location.href='kart.php'\">
</form>
</div>";
?>
 <table class="client-table">
 <tr>
  <th onclick="location.href='kart.php?ord=id<?=($ord=='id'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='id'?($desc?'&uarr; ':'&darr; '):'')?>№</th>
  <th onclick="location.href='kart.php?ord=time<?=($ord=='time'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='time'?($desc?'&uarr; ':'&darr; '):'')?>Выдан</th>
  <th onclick="location.href='kart.php?ord=dat_end<?=($ord=='dat_end'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='dat_end'?($desc?'&uarr; ':'&darr; '):'')?>До</th>
  <th onclick="location.href='kart.php?ord=user<?=($ord=='user'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='user'?($desc?'&uarr; ':'&darr; '):'')?>Клиент</th>
  <th onclick="location.href='kart.php?ord=tovar<?=($ord=='tovar'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='tovar'?($desc?'&uarr; ':'&darr; '):'')?>Услуга</th>
  <th onclick="location.href='kart.php?ord=ost<?=($ord=='ost'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='ost'?($desc?'&uarr; ':'&darr; '):'')?>Остаток</th>
  <th></th>
 </tr>
<?
$query=$bar->query();//DB::sql('SELECT * FROM '.$bar->tbl.$bar->sql.' ORDER BY '.$ord.' '.$desc.' LIMIT '.$bar->start_pos.', '.$bar->perpage);

while ($data = DB::fetch_assoc($query)){
   $data['id']=trim($data['id']);
   echo "<tr id=\"id".$data['id']."\" onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">
   <td>".$data['id'].".</td>
   <td>".date('d.m.y H:i',strtotime($data['time']))."</td>
   <td>".date('d.m.y',strtotime($data['dat_end']))."</td>
   <td>".User::_GetVar($data['user'],'user_name')."</td>
   <td class='left'>".DB::GetName('tovar',$data['tovar'])."</td>
   <td>".$data['ost']."</td>
   <td class=\"edit-del\">
	<a href='/shop/api.php?kart&show=".$data['id']."' class=\"icon abonement right ajax\" title=\"Посещения\">
	<a href='/api.php?tbl=kart&del=".$data['id']."' class=\"icon del right confirm\" title=\"Удалить\">
	<a href='?form=".$data['id']."' class=\"icon edit right ajax\" title=\"Изменить\"></td>
  </tr>";
}
?>
 </table>
<?
   echo "\n<br class='clear'><div> всего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."\n</div>";

?>
<br><br>
<?
if(isset($_GET['add']))echo "<script type=\"text/javascript\">onDomReady(function(){ajaxLoad('','/adm/kart.php?form');});</script>";

include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";

