<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

   if(isset($_REQUEST['reset'])){
	  unset($_REQUEST['d_to'],$_REQUEST['d_from']);
   }
   if(isset($_REQUEST['d_from'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=(isset($_REQUEST['d_to'])?strtotime($_REQUEST['d_to']):time());
	//if($d_to<$d_from){$t=$d_to;$d_from=$d_to; $d_to=$t; unset($t);}
   }else{
	$d_to=time();
	$d_from=strtotime(date("01.m.Y"));
   }

if(isset($_POST['kol'])&&isset($_POST['tovar_cs'])){ // добавление прихода
   $kol=intval($_POST['kol']);
   $tovar=Tovar::GetTovar(intval($_POST['tovar_cs'])); if(!$tovar)fb_err("Нет товара № ".intval($_POST['tovar_cs']));
   $query=DB::sql("SELECT * FROM `".db_prefix."prixod` WHERE dat='".date('Y-m-d')."' and tovar='".$tovar['id']."' LIMIT 1");

   _log('tovar', $tovar['id'], 'приход', '', $kol);
   if($data = DB::fetch_assoc($query)){// изменяю
	DB::sql("UPDATE `".db_prefix."prixod` SET `kol`=kol+'".$kol."' WHERE id='".$data['id']."'");
	DB::sql("UPDATE `".db_prefix."tovar` SET `ost`=ost+".$kol."	WHERE id='".$tovar['id']."' LIMIT 1");
   }else{ // добавляю
	DB::sql("INSERT INTO `".db_prefix."prixod`
		( `dat`, `tovar`, `kol`, `price`, `user`)
		VALUES ( '".date('Y-m-d')."', '".$tovar['id']."', '".$kol."', '".$tovar['price']."', '".$_SESSION['user']['id']."')");
	DB::sql("UPDATE `".db_prefix."tovar`
		SET `ost`='".addslashes(intval($tovar['ost']+$kol))."'
		WHERE id='".$tovar['id']."' LIMIT 1");
   }
   fb_mes("","window.location='?d_from=".date("d.m.Y")."'");
}


if(isset($_GET['ajax'])&&isset($_POST['kol'])){ // изменение количества прихода товара
   $kol=intval(str_replace(' ','',$_POST['kol']));
   $id=intval($_GET['ajax']);
   $query=DB::sql("SELECT * FROM `".db_prefix."prixod` WHERE id='".$id."' LIMIT 1");
   if(!($data = DB::fetch_assoc($query)))fb_err("Неверный запрос id=".$id);
   if(User::is_admin() || $data['dat']==date('Y-m-d')){
	_log('tovar', $data['tovar'], 'приход', '', $kol);
	DB::sql("UPDATE `".db_prefix."prixod` SET `kol`='".$kol."' WHERE id='".$id."'");
	echo $kol;
	$kol-=$data['kol'];
	DB::sql("UPDATE `".db_prefix."tovar` SET `ost`=ost+".$kol."	WHERE id='".$data['tovar']."' LIMIT 1");
   }else fb_err("Недоступно!");
   DB::close();
   exit;

}elseif(isset($_GET['export'])){
   $query=DB::sql("SELECT * FROM `".db_prefix."prixod` WHERE dat='".date('Y-m-d')."' and kol<0");
   if(DB::num_rows($query)>0){
      $fil=$_SERVER['DOCUMENT_ROOT'].'/'.db_prefix.'export.csv'; // путь куда класть файл
      $fp = fopen($fil, 'w');
      while($data = DB::fetch_assoc($query)){
	$tovar=Tovar::GetTovar($data['tovar'],1);
	fputcsv($fp, array($tovar['kod_prodact']?$tovar['kod_prodact']:$tovar['ean'], -$data['kol']),';');
      }
      fclose($fp);
      fb_mes("Выгрузил в ".$fil);
   }else fb_err("Нечего выгружать!");

}elseif(isset($_GET['form'])){//ajax- запрос формы добавления
echo <<< HTML
<h2>Приход косметики и расходки</h2>
<form name="prixod" id="prixod" class="client" action="/tovar_prixod.php" method="POST" onsubmit="return SendForm('prixod',this);">
<table>
	<tr>
		<td>Код или<br>наименование косметики:</td>
		<td></td>
		<td><input name="tovar" size="25" required href="/shop/api.php?type=0&get=" style='width:300px'></td>
	</tr>
	<tr>
		<td>Количество:</td>
		<td></td>
		<td><input name="kol" type="number" size="3" value="1"></td>
	</tr>

	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" value="Добавить"></td>
	</tr>

</table>
</form>
HTML;
	exit;

}elseif(isset($_GET['printcen'])){
    include_once $_SERVER['DOCUMENT_ROOT']."/include/head_print.php";
    $query=DB::sql("SELECT * FROM `".db_prefix."prixod` WHERE dat between '".date("Y-m-d",$d_from)."' and '".date("Y-m-d",$d_to)."' and kol>0");
    while($data = DB::fetch_assoc($query)){
         $tovar=Tovar::GetTovar($data['tovar'],1);
         echo Tovar::Cennik($tovar);
    }
    include_once "include/tail_print.php";
    DB::close();
    exit;
}

    $title="Приход косметики";
    include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";

    $q=trim(isset($_GET['q'])&&$_GET['q']!==''?urldecode($_GET['q']):'');
echo "\n<h1>".$title."</h1>\n";
?>
<form method="get">
За период
с <input type="date" name="d_from" size=10 value="<?=date("d.m.Y",$d_from)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
по <input type="date" name="d_to" size=10 value="<?=date("d.m.Y",$d_to)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
<br>
Искать код или название: <input type="search" name="q" size="25" value="<?=$q?>">
<input class="button" type="submit" value="Выбрать">
<input class="button" type="reset" value="Сброс" onclick="location.href='tovar_prixod.php'">
</form>
<input id='add' type="submit" class="button" style="width:auto;" value="Добавить" onclick="return ajaxLoad('','?form');">
<br class="clear">
<?
   $ord=strtolower(urlencode(@$_GET['ord'])); if(!preg_match('/(dat|name|id|kod_prodact|price|ost)/', $ord))$ord='kod';
   $desc=(isset($_GET['desc'])?' DESC':'');  // &uarr; &darr;

   $bar=new kdg_bar(array('perpage'=>20, 'tbl'=>db_prefix.'prixod as prixod,'.db_prefix.'tovar as tovar'));
   //$bar->tbl='(SELECT prixod.dat as dat, prixod.kol as kol1, prixod.price as price2, prixod.tovar as tovar FROM '.db_prefix.'prixod as prixod )a LEFT JOIN '.db_prefix.'tovar as tovar ON a.tovar=tovar.id';
   $bar->href=($ord?'&ord='.$ord:'').($desc?'&desc':'').($q?'&q='.urlencode($q):'');
   $df1=date("Y-m-d H:i:s",time()-60*60*24*2);
   $bar->sql=' WHERE prixod.tovar=tovar.id';
   if($q)$bar->sql.=' and (lower(name) LIKE "%'.addslashes(strtolower($q)).'%" or kod_prodact LIKE "'.addslashes(strtolower($q)).'%" or ean LIKE "'.addslashes(strtolower($q)).'%") ';
   $bar->sql.=' and dat between "'.date("Y-m-d",$d_from).'" and "'.date("Y-m-d",$d_to).'"';
   if(isset($_REQUEST['d_from']))$bar->href.='&d_from='.date("d.m.Y",$d_from);
   if(isset($_REQUEST['d_to']))$bar->href.='&d_to='.date("d.m.Y",$d_to);
   $bar->href='?'.substr($bar->href,1);
   $bar_out=$bar->out();
   echo "<div>\nвсего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."
</div>";

?>
 <table class="client-table tbl" data-tbl="prixod">
 <tr>
  <th onclick="location.href='tovar_prixod.php?ord=dat<?=($ord=='dat'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='dat'?($desc?'&uarr; ':'&darr; '):'')?>Дата</th>
  <th onclick="location.href='tovar_prixod.php?ord=kod_prodact<?=($ord=='kod_prodact'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='kod_prodact'?($desc?'&uarr; ':'&darr; '):'')?>Код</th>
  <th onclick="location.href='tovar_prixod.php?ord=name<?=($ord=='name'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='name'?($desc?'&uarr; ':'&darr; '):'')?>Наименование</th>
  <th onclick="location.href='tovar_prixod.php?ord=kol<?=($ord=='kol'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='kol'?($desc?'&uarr; ':'&darr; '):'')?>кол-во</th>
  <th onclick="location.href='tovar_prixod.php?ord=price<?=($ord=='price'?($desc?'':'&desc'):'').($q?'&q='.urlencode($q):'')?>'"><?=($ord=='price'?($desc?'&uarr; ':'&darr; '):'')?>Цена</th>
  <th>&nbsp;</th>
 </tr>
<?
$query=DB::sql('SELECT prixod.*, tovar.name, tovar.kod_prodact, tovar.ean, tovar.kol as kol1,tovar.ed as ed, tovar.price0 as price0 FROM '.$bar->tbl.$bar->sql.' ORDER BY '.$ord.' '.$desc.' LIMIT '.$bar->start_pos.', '.$bar->perpage);
$summ=$summ0=0;
while ($data = DB::fetch_assoc($query)){
//print_r($data);
   if(User::is_admin())echo "\n<tr id=\"id".$data['id']."\" onclick=\"ShowHide(this,'red')\" style=\"border-top:#9fbddd 1px solid;\" onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">";
	else echo "\n<tr id=\"id".$data['id']."\" onclick=\"ShowHide(this,'red')\">";
   echo "\n\t<td>".date('d.m.y',strtotime($data['dat']))."</td>
	<td>".$data['kod_prodact'].($data['kod']&&$data['ean']?"/":"").$data['ean']."</td>
	<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$data['tovar']."')\">".$data['name'].
	($data['kol1']>1?" ".($data['kol1']==intval($data['kol1'])?intval($data['kol1']):$data['kol1']).$data['ed']:'')."</td>
	<td><input name='kol' value='".$data['kol']."' onChange='SendInput(this)' onfocus='this.select()' /></td>
	<td>".$data['price']."</td>";
   if(User::is_admin())echo "\n<td class=\"edit-del\">
	<a href='/api.php?tbl=prixod&del=".$data['id']."' class=\"icon del right confirm\" title=\"Удалить(остаток не меняется)\"></td>";
   else echo "\n<td>&nbsp;</td>";
   echo "\n</tr>";
$summ+=$data['kol']*$data['price'];
$summ0+=$data['kol']*$data['price0'];
}
?>
 </table>
<?
   echo "\n<br class='clear'><div> всего: <b>".$bar->count."</b> на сумму ".$summ." (".$summ0."). &nbsp; ".$bar_out."\n</div>";
?>
<p>При изменении кол-ва прихода автоматически пересчитывается остаток</p>
    <input type="submit" class="button" value="Загрузить приход" onclick="location.href='tovar_prixod_load.php';return false;">
    <input type="submit" class="button" value="Выгрузить расход как приход другого салона" onclick="ajaxLoad('','?export');return false;">
    <input type="submit" class="button" value="Ценники" onclick="u=document.location.href;location.href=(u.indexOf('?')>=0 ? (u+'&'): '?')+'printcen'+(confirm('С описанием?')?'&description':'');return false;">
<?
include_once "include/tail.php";

?>
