<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
if(isset($_POST['client'])){

$add=!isset($_GET['test']);
$mes=trim($_REQUEST['client']);
// Преобразовать сообщение в одну строку.
$mes=preg_replace('/[ \t\r]+/', ' ', trim($mes));
$mes=preg_replace('/  +/', ' ', $mes);
$s=explode("\n",$mes);
for($i=0;$i<count($s);$i++)if($s[$i]){
	$name=$s[$i];
	if(preg_match('|^(.*?)([\+\d\-\(\)]{8,15})(.*?)$|', $name, $arr)){//8-928-199-00-03
		//$tel=str_replace('(','',str_replace(')','',str_replace('-','',str_replace('+','',$arr[2]))));
		//if(substr($arr[2],0,1)=='8'||substr($arr[2],0,1)=='7')$tel=substr($tel,1);
		//$name=trim($arr[1].' '.$arr[3]);
//echo "<br>".$name;
		if(preg_match('|^(.*?)\s(\d{1,2}[\.\,\/\-]\d{2}[\.\,\/\-]\d{2,4})\s(.*?)$|', ' '.$name.' ', $arr)){
			echo "<br>\n".print_r($arr,true);
			$name=trim($arr[1].' '.$arr[3]);
			$birthday=str_replace(',','.',str_replace('/','.',str_replace('-','.',$arr[2])));
			$birthday="'".date('Y-m-d', strtotime($birthday))."'";
		}elseif(preg_match('|^(.*?)\s(\d{4}[\.\,\/\-]\d{2}[\.\,\/\-]\d{2})\s(.*?)$|', ' '.$name.' ', $arr)){ // 1986-10-23
			echo "<br>\n".print_r($arr,true);
			$name=trim($arr[1].' '.$arr[3]);
			$birthday="'".str_replace(',','-',str_replace('/','-',str_replace('.','-',$arr[2])))."'";
		}else $birthday='NULL';

		if (preg_match("/^(.*?)\s([a-z0-9_\-\.\+]{1,20}@([a-z0-9\-а-ярстуфхцчщшэюё]+\.)+(com|net|org|mil|edu|gov|arpa|info|biz|inc|name|[a-z]{2}|рф))\s(.*?)$/is", ' '.$name.' ', $arr)){
			//echo "<br>\n".print_r($arr,true);
			$name=trim($arr[1].' '.$arr[5]);
			$email=trim($arr[2]);
		}else $email='';

		if(preg_match('|^(.*?)([\+\d\-\(\)]{8,15})(.*?)$|', $name, $arr)){//8-928-199-00-03
			$tel=str_replace('(','',str_replace(')','',str_replace('-','',str_replace('+','',$arr[2]))));
			if(substr($arr[2],0,1)=='8'||substr($arr[2],0,1)=='7')$tel=substr($tel,1);
			$name=trim($arr[1].' '.$arr[3]);
		}

		echo "<br>\n<b>".$tel."</b> ~ ".$name.($birthday!='NULL'?" ~ ".$birthday:'').($email!=''?" ~ ".$email:'');
	   if($add)DB::sql("INSERT IGNORE INTO `".db_prefix."user`
		( `name`, `tel`, `birthday`, `date0`, `sex`, `mail`)
		VALUES ( '".addslashes($name)."', '".addslashes($tel)."', ".$birthday.", '".date('Y-m-d')."', '1', '".addslashes($email)."')");
	   else{// todo проверить что такой записи нет
		}
	}else echo "<br>\n".$s[$i]." не разобрал!";
}
   if($add) echo "<br>\nСохранил!";
   else "<br>\nРазбор окончен!";
   exit;
}

$title='Загрузка клиентов';
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
echo "\n<h1>".$title."</h1>\n";
?>
<form name="client" id="client" class="client" action="users_import.php" method="POST" onsubmit="SendForm('answer',this);hide(this);return false;">
<textarea name="client" rows="50" cols="200"></textarea>
<br class='clear'>
<input type="submit" class="button right" style="width:auto;" value="Проверить" onclick="this.form.action='users_import.php?test';">
<input type="submit" class="button right" style="width:auto;" value="Добавить" onclick="this.form.action='users_import.php';">
</form>
Автоматически разбираются строки содержащие:
<ul>
<li>номер телефона в форматах: +79xxxxx, 79xxxxx, 89xxxxx, 8-9xxxx и подобные. Внутри номера могут быть разделители из "-","(",")"
<li>дату рождения в форматах: дд.мм.гг, дд.мм.гггг при чем в качестве разделителей могут быть ".",",","-","/"
<li>все не разобранное заносится в ФИО
</ul>
<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";
?>
