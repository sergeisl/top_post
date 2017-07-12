<?
include_once($_SERVER['DOCUMENT_ROOT'] . '/include/func.php');
_session_start();
if(!empty($_GET['mail']))User::register($_GET); // быстрая регистрация - сохранение временного нового пользователя

    if(empty($_GET['user_id'])){
        if(!User::is_login()) Out::Location('/user/balans.php');
        $id=intval((!empty($_REQUEST['id'])?$_REQUEST['id'] : (empty($_SESSION['user']['id']) ? 0 : $_SESSION['user']['id'])));
        if(!$id){
            Out::error(User::NeedLogin());
            Out::ErrorAndExit(3);
        }
    }else{
        $id=intval($_GET['user_id']);
    }
if(!empty($_GET['zakaz'])){ // link
    $kurs = DB::Select('zakaz', intval($_GET['zakaz']));
    $kurs = array_merge($kurs, DB::Select('kurs', intval($kurs['kurs'])));
    $inv_desc = str_replace("'",'"',$kurs['name'] . (is_null($kurs['date_start']) ? '' : ' ' . $kurs['date_start']) . ' с ' . $kurs['from']);
    $out_summ = $kurs['price'];

}elseif(!empty($_GET['inv_n'])){ // оплата уже существующего счета
    $inv_n=intval($_GET['inv_n']);
    $row=DB::Select('payment',$inv_n);
    if(!$row){
        Out::error("Неверный номер счета.");
        Out::ErrorAndExit(3);
    }
    $kurs = DB::Select('zakaz', intval($row['zakaz']));
    $kurs = array_merge($kurs, DB::Select('kurs', intval($kurs['kurs'])));
    $inv_desc = str_replace("'",'"',$kurs['name'] . (is_null($kurs['date_start']) ? '' : ' ' . $kurs['date_start']) . ' с ' . $kurs['from']);
    $out_summ = $kurs['price'];
}else{
    if(empty($_GET['inv_desc'])){
        $inv_desc = "Пополнение ".(isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : DB::GetName('user',$id) ); // invoice desc
    }else{
        $inv_desc=str_replace("'",'"',urldecode($_GET['inv_desc']));
    }
    $out_summ = max(10,intval(@$_REQUEST['lmi_payment_amount'])); // рубли!!!
}

$param = [
//    'user' => 'tel:+79'.$phone,  //Идентификатор пользователя которому выставляется счет
//    'user' => 'bankcard',
    'amount' => number_format($out_summ,2,'.',''),//number_format($cost, 2, '.', ''),//	Number(24)	+	Сумма заказа, указывается в рублях с копейками - 16.00
    'ccy' => 'RUB', // Идентификатор валюты согласно Alpha-3 ISO 4217 (RUB или 643)
    'comment' => $inv_desc, /* в UTF-8 */
    'lifetime' => date('Y-m-d\TH:i:s', time() + 604800),  // Дата в формате ISO 8601. По достижении этой даты счет будет считаться отвергнутым
    'prv_name' => $_SERVER['SERVER_NAME'] // Имя Агента
];

if(!empty($_SESSION['user']['mail']))$param['mail']=$_SESSION['user']['mail'];

if(!empty($_GET['tel'])){
    $tel=trim(urldecode(@$_GET['tel']));
    if(substr($tel,0,1)=='+')$tel=substr($tel,1);
    if(substr($tel,0,1)=='7')$tel=substr($tel,1);
    if(strlen($tel)<9){
        Out::error("Неверный номер телефона!");
        Out::ErrorAndExit(3);
    }
    $param['user'] =  'tel:+7'.$tel;
}else{
    $tel='';
    $param['user'] = 'bankcard';
    $param['amount'] = number_format(myFloatVal($out_summ),2,'.',''); // без доп.комиссии
    //$param['amount'] = number_format(myFloatVal($out_summ)*1.0174,2,'.',''); // +1.7%
}

if(empty($inv_n)){
    // заношу счет в базу
    $inv_n = User::Depositing($id, $out_summ, $tel, 'comepay', 9);
}

$Obj = ComePay::Call($inv_n , $param);
PaymentLog("Comepay: Счет ".$inv_n."\r\n".var_export($param,!0)."\r\n Ответ ".var_export($Obj,!0));
//file_put_contents( $_SERVER['DOCUMENT_ROOT'] . '/log/payment_log.txt', "\r\n\r\n Счет ".$inv_n."\r\n".var_export($param,!0)."\r\n Ответ ".var_export($Obj,!0)."\r\n" , FILE_APPEND);

if (!empty($Obj)) {
    // перехожу к оплате
    Out::Location(ComePay::Url($inv_n));
    //PaymentLog("Comepay: ".var_export($param,!0)."\n".$url,0,0);
} else {
    Out::error("Платежная система временно недоступна. Попробуйте повторить операцию позже или воспользуйтесь другим способом оплаты.");
}
Out::ErrorAndExit(3);
