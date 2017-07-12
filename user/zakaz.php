<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

if(!User::is_login()){
    Out::error(User::NeedLogin());
    if(isset($_GET['ajax'])){
        echo nl2br($_SESSION['error']); $_SESSION['error']="";
    }else header("location: http://".$_SERVER['SERVER_NAME'].'/user/login.php');
    exit;
}elseif(!User::is_admin() && isset($_REQUEST['id']) && $_REQUEST['id']!=$_SESSION['user']['id']){
    Out::error("Недостаточно прав доступа!");
    if(isset($_GET['ajax'])){
        echo nl2br($_SESSION['error']); $_SESSION['error']="";
    }else header("location: http://".$_SERVER['SERVER_NAME'].'/');
    exit;
}

$id=intval((isset($_REQUEST['id'])?$_REQUEST['id']:$_SESSION['user']['id']));


$title='Мой заказ. '.SHOP_NAME;
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";

echo echoBlock('user/zakaz', [ 'zakaz' => Zakaz::uget( [ 'user_id' => User::id() ] ) ] );

include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";