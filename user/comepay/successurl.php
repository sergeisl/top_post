<? // URL по которому пользователь будет перенаправлен после успешного платежа
include_once $_SERVER['DOCUMENT_ROOT']."/include/func.php";

PaymentLog("Comepay: successurl ", 0, 1);
if(empty($_REQUEST['order'])){
    error('Не передан номер оплаченного счета!');
}else{
    $order_id=intval($_REQUEST['order']);
    $Obj = ComePay::Call($order_id);
    if($Obj)ComePay::TestBill($Obj,$order_id);

    /*order=182980
	orderid=141284
	success=True
	QUERY_STRING=order=182980*/

}
if(User::is_login()){
    Out::Location('/user/balans.php?ok=1');
}else{
    if(isset($_SESSION['ret_path']) ){ // оплата прошла, возвращаю откуда пришли
        header("location: ".$_SESSION['ret_path'].'#pay');
        unset($_SESSION['ret_path']);
    }
}
//    error('Оплата не подтверждена. Если списание средств с карты произведено, обратитесь в службу поддержки');
exit;
