<? // Оповещение об оплате (ResultURL, Callback Url)
include_once($_SERVER['DOCUMENT_ROOT'].'/include/config.php');

header('Cache-Control: no-store, no-cache, must-revalidate');     // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0');    // HTTP/1.1
header("Pragma: no-cache");

PaymentLog("Comepay: resulturl ", 0, 1);

/* если запрос из balans.php, то передается $order_id
   ecли оповежение о платеже, то передается:
	bill_id=order1&status=paid&error=0&amount=15.00&user=tel%3A%2B79000000000&prv_name=TEST&ccy=RUB&comment=result
вернуть нужно:
	HTTP/1.1 200 OK
	Content-Type: text/xml
	<?xml version="1.0"?> <result><result_code>0</result_code></result>
*/
if(!empty($_POST['bill_id'])){
    header("Content-Type: text/xml");
    $order_id = intval($_POST['bill_id']); // номер проверяемого счета
}elseif(!empty($_GET['order'])){
    header("Content-Type: text/plain; charset=windows-1251");
    $order_id = intval($_GET['order']); // номер проверяемого счета
}else{
    die('Нет id');
}
if ($order_id == 0) die('Нет id');

$res = DB::Select('payment','id="'.$order_id.'"');
// счет не найден
if (empty($res)) {
    SendAdminMail('ComePay error','Счет '.$order_id.' не найден' . json_encode($_REQUEST) );
    exit;
}
if($res['status']==0){
    $res="Уже оплачен";
}elseif($res['status']==9){
    $Obj = ComePay::Call($order_id);
    if(empty($Obj)){
        $res="Error";
    }elseif(ComePay::TestBill($Obj,$order_id)){
        //User::Depositing($id, $summ, $_POST['LMI_PAYER_PURSE']); // заношу оплату в базу
        $res="Ok";
    }else $res="No";
}

if(!empty($_POST['bill_id'])){
    echo '<?xml version="1.0"?><result><result_code>0</result_code></result>';
}else{
    echo $res;
}
exit;
