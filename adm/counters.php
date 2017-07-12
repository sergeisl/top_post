<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
// todo при вводе значения счетчика больше положенного требовать ввода причины
$z_counters=[];
$z_counters[0]='Минуты/Посещения';
$z_counters[1]='Замена ламп/жидкости';
$z_counters[2]='Очистка';

if(isset($_POST['tovar'])){
   if(isset($_POST['id']) && intval($_POST['id'])>0){
	isPrivDel('counters',$_POST['id']);
	DB::sql("UPDATE `".db_prefix."counters`
	SET `time`='".date('Y-m-d H:i:s')."',
	`tovar`='".intval($_POST['tovar'])."', `device`='".intval($_POST['device'])."',
	`counter1`='".intval($_POST['counter1'])."', `counter2`='".intval($_POST['counter2'])."'".
	(isset($_POST['rej'])?", `rej`='".intval($_POST['rej'])."'":"").
	" WHERE id='".intval($_POST['id'])."'");
   }else{
	// todo если есть такой, то обновить ключевую информацию
	DB::sql("INSERT IGNORE INTO `".db_prefix."counters`
	( `time`, `tovar`, `device`, `counter1`, `counter2`".(isset($_POST['rej'])?", `rej`":"").")
	VALUES ( '".date('Y-m-d H:i:s')."', '".intval($_POST['tovar'])."', '".intval($_POST['device'])."',
		'".intval($_POST['counter1'])."', '".intval($_POST['counter2'])."'".(isset($_POST['rej'])?", '".intval($_POST['rej'])."'":"").")");
   }
   if(DB::affected_rows()>0){
	message("Сохранил!");
	fb_mes("","reload()");
   }else {DB::close();die("Не сохранил!");}
}
if(isset($_GET['tovar'])&&isset($_GET['device'])){//ajax-запрос вычисленного значения счетчиков
	$tovar=intval($_GET['tovar']);
	$device=intval($_GET['device']);
	// ищу прошлое значение счетчика
	if($row=DB::Select("counters","tovar='".$tovar."' and device='".$device."' ORDER BY time DESC")){
	   $data=[];
	   $data['counter1']=$row['counter1'];
	   $data['counter2']=$row['counter2'];
	   // считаю все операции по этому оборудования с найденного счетчика
	   $res=DB::sql("SELECT sum(`kol`) as s1, count(*) as s2
		FROM ".db_prefix."sale2 as sale2, ".db_prefix."sale as sale
		WHERE sale2.sale=sale.id and sale.time>'".date('Y-m-d H:i:s',strtotime($row['time']))."' and tovar='".$tovar."' and device='".$device."' GROUP BY tovar,device");
	   if($row = DB::fetch_assoc($res)){
            if(defined('div_counter'.$device)){
               $row['s1']*=constant("div_counter".$device);
            }
            $data['counter1']+=$row['s1'];
            $data['counter2']+=$row['s2'];
            echo php2json($data);
	   }else{
	    	echo php2json($data);
	   }
	}else echo php2json(array('info'=>'Нет прошлого значения счетчика!'));
	DB::close();
	exit;
}

if(isset($_GET['form'])){//ajax- запрос формы добавления
    $id=intval($_GET['form']);
    if($id>0){
        $query=DB::sql("SELECT * FROM `".db_prefix."counters` WHERE id='".$id."' LIMIT 1");
        if($data = DB::fetch_assoc($query)){
           $data['time']=dateForShow($data['time']);
           $data['save']="Сохранить";
        }else fb_err("Нет такого!");
    }else{
	   $data['save']="Добавить";
	   $data['id']='';$data['device']='';$data['counter1']='';$data['counter2']='';$data['tovar']='';$data['time']='';$data['rej']=0;
    }
   if(!isset($_SESSION['message']))$_SESSION['message']='';
	foreach($data as $key => $value)$data[$key]=str_replace('"',"'",$value);
echo <<< END
<h2>Счетчик</h2>
<form name="counters" id="counters" class="client" action="/counters.php" method="POST" onsubmit="return SendForm('counters',this);">
<input type="hidden" name="id" value="{$data['id']}">
<table>
	<tr>
		<td>Услуга:</td>
		<td></td>
		<td><select name="tovar">
END;
echo "			<option value=\"0\" disabled".($data['tovar']?'':' selected').">--выберите услугу--</option>";
$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=1 ORDER BY name");
while ($tovar = DB::fetch_assoc($query)){
    $tovar=new Tovar($tovar);
	echo "\n\t\t\t<option value=\"".$tovar->id."\"".($tovar->id==$data['tovar']?' selected':'').">".$tovar->show_name."</option>";
}
echo <<< END
		</select></td>
	</tr>
	<tr>
		<td>Кабинка:</td>
		<td></td>
		<td><input name="device" size="3" type="number" value="{$data['device']}" onblur="t=this.form.tovar;t=t.options[t.selectedIndex].value;ajaxLoad(this.form,'counters.php?tovar='+t+'&device='+this.value);"></td>

	</tr>
	<tr>
		<td>Счетчик минут:</td>
		<td></td>
		<td><input name="counter1" type="number" value="{$data['counter1']}"></td>
	</tr>
	<tr>
		<td>Счетчик раз:</td>
		<td></td>
		<td><input name="counter2" type="number" value="{$data['counter2']}"></td>
	</tr>
	<tr>
		<td>Дата, время:</td>
		<td></td>
		<td><span id="time">{$data['time']}</span></td>
	</tr>
END;
if(User::is_admin()){echo "
	<tr>
		<td>Вид счетчика:</td>
		<td></td>
		<td><select name=\"rej\">\n";
   foreach($z_counters as $k => $v)
	echo "\n\t\t\t<option value=\"".$k."\"".($data['rej']==$k?' selected':'').">".$v."</option>";
   echo "\n	</select></td>
	</tr>";
}
echo <<< END
	<tr>
		<td colspan="3"><input type="reset" value="Сброс" class="button right" style="width:auto;" onclick="add(this.form);return true;">
				<input type="submit" id="save" class="button right" style="width:auto;" value="{$data['save']}"></td>
	</tr>

</table>
<div id="info">{$_SESSION['message']}</div>
</form>
END;
	$_SESSION['message']='';
	DB::close();
	exit;
}

$title="Счетчики";
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
   if(isset($_REQUEST['d_from'])&&isset($_REQUEST['d_to'])){
	$d_from=strtotime($_REQUEST['d_from']);
	$d_to=strtotime($_REQUEST['d_to']);
   }else{
	$d_from=strtotime('2010-01-01');
	$d_to=time();	//date("d.m.Y");
   }

?>
<div class="w800">
    <div class="right">
        <input id='add' type="submit" class="button" style="width:auto;" value="Добавить" onclick="return ajaxLoad('','counters.php?form');">
    </div>
    <h1><?=$title?></h1>
</div>
<form method="get">
<?
    $tov=(isset($_REQUEST['tovar'])?intval($_REQUEST['tovar']):0);
    echo "\n\t<select name=\"tovar\">".
         "\n\t\t<option value=\"0\"".($tov?'':' selected').">--все--</option>";
    $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=1 ORDER BY name");
    while ($tovar=DB::fetch_assoc($query)){
        $tovar=new Tovar($tovar);
        echo "\n\t\t<option value=\"".$tovar->id."\"".($tovar->id==$tov?' selected':'').">".$tovar->show_name."</option>";
    }
?>
    </select>
<br>За период
с <input type="date" name="d_from" size=10 value="<?=date("d.m.Y",$d_from)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
по <input type="date" name="d_to" size=10 value="<?=date("d.m.Y",$d_to)?>" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" />
<input class="button" type="submit" value="Отфильтровать">
<input class="button" type="submit" name="reset" value="Сброс" onclick="location.href='counters.php';return false;">
</form>
<br class="clear">
<?
   $bar=new kdg_bar();
   $bar->tbl=db_prefix.'counters';
   if(isset($_REQUEST['d_from'])&&isset($_REQUEST['d_to'])){
	$bar->sql=' WHERE time between "'.date("Y-m-d 00:00:00",$d_from).'" and "'.date("Y-m-d 23:59:59",$d_to).'"';
        $bar->href='&d_from='.date("d.m.Y",$d_from).'&d_to='.date("d.m.Y",$d_to);
   }
   if($tov>0){
	$bar->sql.=($bar->sql?' and':' WHERE').' tovar="'.$tov.'" ';
        $bar->href='&tovar='.$tov;
   }

   if($bar->href)$bar->href='?'.substr($bar->href,1);
   $bar->perpage=20;
   $bar_out=$bar->out();
   echo "<div>\nвсего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."</div>";

?>
 <table class="client-table">
 <tr>
  <th>Время</th>
  <th>Услуга</th>
  <th>Кабинка</th>
  <th>Счетчик минут</th>
  <th>Счетчик раз</th>
  <th>&nbsp;</th>
 </tr>
<?
$query=DB::sql('SELECT * FROM '.$bar->tbl.$bar->sql.' ORDER BY time,tovar,device DESC LIMIT '.$bar->start_pos.', '.$bar->perpage);

while ($data = DB::fetch_assoc($query)) {
    echo "\n<tr id=\"id" . $data['id'] . "\" style=\"border-top:#9fbddd 1px solid;\" onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">";
    $tovar = new Tovar($data['tovar']);
    if ($tovar) {
        echo "<td>" . date('d.m.y H:i', strtotime($data['time'])) . "</td>
	<td class='left'>" . $tovar->show_name . ($data['rej'] > 0 && $data['rej'] <= 2 ? " <span class='green'>(" . $z_counters[$data['rej']] . ")</span>" : "") . "</td>
	<td>" . $data['device'] . "</td>
	<td>" . ($data['counter1'] ? number_format($data['counter1'], 0, '.', ' ') : '') . "</td>
	<td>" . ($data['counter2'] ? number_format($data['counter2'], 0, '.', ' ') : '') . "</td>";
    } else {
        echo "<td colspan='8'>Ошибка в коде товара " . $data['tovar'] . "</td>";
    }
    echo "<td class=\"edit-del\">
	<a href='/api.php?tbl=counters&del=" . $data['id'] . "' class=\"icon del right confirm\" title=\"Удалить\">
	<a href='?form=" . $data['id'] . "' class=\"icon edit right ajax\" title=\"Изменить\"></td>
  </tr>";
}
?>
 </table>
<?
   echo "\n<br class='clear'><div> всего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."\n</div>";

if(isset($_GET['add']))echo "<script type=\"text/javascript\">onDomReady(function(){ajaxLoad('','counters.php?form');});</script>";
include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";
?>
