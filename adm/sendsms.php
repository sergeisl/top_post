<?php
//define('SMSPILOT_APIKEY', '3YL8L508ZTC7LVBCTL4OP70SBS7T16Z57830MN3575S6V9B56988WWQP683FW076'); // для тестирования закоментировать строчку
define('SMSPILOT_CHARSET', 'windows-1251');
define('SMSPILOT_FROM', 'SunLife');

include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

if(!isset($_POST['sms_text'])){
	foreach($z_tbl as $z)
		echo "<br>\n".$z.":"; NormalTel($z);

	CheckStatusSms();
	exit;
}

$mes=trim(@iconv('UTF-8', 'windows-1251//IGNORE',$_POST['sms_text']));

if(isset($_POST['sms_translit'])&&$_POST['sms_translit'])$mes=rus2translit($mes);

// Преобразовать сообщение в одну строку.
$mes = preg_replace('/[\r\n\t]+/', ' ', trim($mes));
$mes = preg_replace('/\s{2,}/', ' ', $mes);
$meslength = strlen($mes);

if($meslength < 3)	Out::err('Сообщение слишком короткое.');
elseif($meslength > 5*70)	Out::err('Сообщение слишком длинное.');


//include_once $_SERVER['DOCUMENT_ROOT']."/include/smspilot.php";

/*
Прекрасной половине скидка на загар и косметику 20% только 8-9 марта!

SunLife
Только до 30 апреля скидка 40% на абонементы LPG массажа, кавитации, RF-лифтинга предъявителю этой СМС!
Тел. 89282793358

*/

if(isset($_GET['all'])){
   if(isset($_POST['sms_all'])&&$_POST['sms_all']){

	$r="select tel from (";
	for($i=0;$i<count($z_tbl);$i++){
		$r.=($i?"\n\tUNION DISTINCT\n\t":"")."\t\t(SELECT tel FROM {$z_tbl[$i]}user WHERE `tel` LIKE '9%' and sms<>'1' GROUP BY tel)";
	}
	$r.=")q GROUP by tel ORDER by tel";
echo $r;
   $query=DB::sql($r);
   }else{
	$query=DB::sql("SELECT * FROM `".db_prefix."user` WHERE `tel` LIKE '9%' GROUP BY tel");
   }

}elseif(isset($_GET['id'])){

	$query=DB::sql("SELECT * FROM `".db_prefix."user` WHERE id='".$id."' and `tel` LIKE '9%' LIMIT 1");

}else{
	CheckStatusSms();
	fb_err('Неверные параметры.');
}

echo "<br>Отправляю ".DB::num_rows($query)." SMS.";
set_time_limit(0);
ignore_user_abort(!0);

//SendSms('79281291999',$mes);

$tel='';
while($data = DB::fetch_assoc($query)){
     if(strlen($data['tel'])==10){
	$tel.=',7'.$data['tel'];
	if(strlen($tel>200)){SendSms(substr($tel,1),$mes); $tel='';}
     }else echo "<br>\n7".$data['tel']." - неверный номер телефона!";
}

if($tel){SendSms(substr($tel,1),$mes);}
//echo "<br>\nБаланс=".sms_balance();

function NormalTel($prefix){
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=SUBSTRING(tel,2) WHERE tel LIKE '89%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('903401',SUBSTRING(tel,4)) WHERE tel LIKE '221%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('903406',SUBSTRING(tel,4)) WHERE tel LIKE '256%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('918555',SUBSTRING(tel,4)) WHERE tel LIKE '275%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('918558',SUBSTRING(tel,4)) WHERE tel LIKE '298%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('918554',SUBSTRING(tel,4)) WHERE tel LIKE '294%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('928',tel) WHERE tel LIKE '226%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('928',tel) WHERE tel LIKE '296%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('928',tel) WHERE tel LIKE '270%'"); print "<br />SQL:".DB::info().DB::affected_rows();
			DB::sql("UPDATE IGNORE `".$prefix."user` SET `tel`=CONCAT('928',tel) WHERE tel LIKE '279%'"); print "<br />SQL:".DB::info().DB::affected_rows();
}

function sms1( $to, $text) {
    $param=array(
        'api_key'=>'2VJicLclSPktVzeXjfIovDxmhCQPbTnsL',
        'from' => SMSPILOT_FROM,
        'to' => $to,
        'text' => @iconv('windows-1251','utf-8//IGNORE',$text)
    );
    //var_dump($param);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_URL, 'http://htmlweb.ru/sendsms/api.php?send_sms&json&service=1');
    $res = curl_exec($ch);
    $result = json_decode($res,!0);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if(empty($result['sms'])){
        echo "<br>res:"; var_dump($res);
        echo "<br>result:";var_dump($result);
        echo "<br>info:";var_dump($info);
        return false;
    }else return (array)$result['sms']; // "from":"From1","time":"2014-02-06 19:13:25","message":"Test SMS1","id":123,"phone":"79112224433","cost":0.25},
}

function SendSms($tel,$mes){ // tel = список номеров через ","
	echo "<br>\n".$tel."<br>\n";
    $full_cost=0;
	if ( ($v = sms1($tel, $mes)) !== false ) {
	   foreach($v as $s){// Sunlife дарит подарки к 8 марта!!! -50% на все абонементы массажа, кавитации и лифтинга + подарок для любимых клиентов! 89282793358
           //$s['message']=@iconv('utf-8','windows-1251//IGNORE',$s['message']);
		echo "<br>\n"; var_export( $s ); // array ( 'sms_id' => 372703776, 'id' => 39741, 'phone' => '79281291999', 'cost' => 0.2, 'message' => '', )   // Array ( [id] => 94 [phone] => 79087964781 [zone] => 2 [status] => 2 )
		DB::sql("INSERT IGNORE INTO `".db_prefix."sms`
			( `id`, `message`, `phone`, `cost`, `status`, `time`)
			VALUES ( '".addslashes($s['id'])."', '".addslashes($mes)."', '".addslashes($s['phone'])."', '".addslashes($s['cost'])."', '".addslashes(0)."', '".date('Y-m-d H:i:s')."')");
           $full_cost+=floatval($s['cost']);
	   }
	   echo "<br\n>Цена=".$full_cost;
	} else {// todo если не работает шлюз - попробовать через другой или записать в базу для отложенной отправки
		//echo sms_error();
        echo "<br>Ошибка отправки!";
    }
}

/* Состояние SMS обновляется каждые 2 минуты.
-2	не принято, неправильный номер, ID не найден
-1	сообщение не доставлено (телефон абонента выключен, оператор не поддерживается)
0	новое сообщение, подготовка к отправке
1	в очереди у оператора
2	сообщение успешно доставлено
3	отложенная отправка (send_datetime)
*/

function CheckStatusSms(){
    // сначала удаляю все сообщения старше 3х месяцев
    DB::sql("DELETE FROM `".db_prefix."sms` WHERE time < '".date("Y-m-d H:i:s",time()-60*60*24*90)."'");
    // подключаю библиотеку
    include_once $_SERVER['DOCUMENT_ROOT']."/include/smspilot.php";
    $ids='';
    $query=DB::sql("SELECT * FROM `".db_prefix."sms` WHERE `status` IN ('0','1')");
    while($data = DB::fetch_assoc($query)){
	$ids.=','.$data['id'];
	if(strlen($ids>200)){CheckSms(substr($ids,1)); $ids='';}
    }
    if($ids)CheckSms(substr($ids,1));
}

function CheckSms($ids){
	echo "<h4>Проверяю доставку прошлых СМС</h4>";
	if( ($s = sms_check( $ids )) !== false ){
	    for($i=0;$i<count($s);$i++){
		print_r( $s[$i] ); echo "<br>\n"; // Array ( [id] => 94 [phone] => 79087964781 [zone] => 2 [status] => 2 )
		DB::sql("UPDATE IGNORE `".db_prefix."sms` SET `status`='".addslashes($s[$i]['status'])."' WHERE id='".intval($s[$i]['id'])."'");
		print "SQL:".DB::info().DB::affected_rows();
	    }
	}else
		echo sms_error();
}
