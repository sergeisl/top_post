<? // URL по которому пользователь будет перенаправлен после неуспешного платежа.
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

PaymentLog("Comepay: failturl ", 0, 1);
Out::error('Оплата счета'.(empty($_REQUEST['order'])?'':' N '.Convert::utf2win($_REQUEST['order'])).' НЕ прошла.'.(empty($_REQUEST['gatelineerror'])?'':' '.Convert::utf2win($_REQUEST['gatelineerror'])));

if(User::is_login()){
    Out::Location('/user/balans.php');
}else{
    if(isset($_SESSION['ret_path']) ){ // оплата НЕ прошла, возвращаю откуда пришли
        header("location: ".$_SESSION['ret_path'].'#nopay');
        unset($_SESSION['ret_path']);
    }
}
//PaymentLog("Отказ от оплаты",1,1);
exit;
