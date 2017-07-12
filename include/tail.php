<?
if(!Get::isApi()){//ajax запрос вкладки
if(isset($_REQUEST['print'])){
    include_once $_SERVER['DOCUMENT_ROOT']."/include/tail_print.php";
    return;
}

echo echoBlock( 'footer' );

global $DomLoad;
if(!empty($_SESSION['error'])){
    $DomLoad=(empty($DomLoad) ? "" : $DomLoad."\n")."fb_win('".str_replace("'","\\'",str_replace("\n","",str_replace("\r","",nl2br($_SESSION['error']))))."');";
    $_SESSION['error']="";
}
if(!isset($_GET['add'])&&!empty($_SESSION['message'])){
    $DomLoad=(empty($DomLoad) ? "" : $DomLoad."\n")."fb_win('".str_replace("'","\\'",str_replace("\n","",str_replace("\r","",nl2br($_SESSION['message']))))."');";
    $_SESSION['message']="";
}
if(!empty($DomLoad))echo "
<script type=\"text/javascript\">
var oldonload = document.funcDomReady;
document.funcDomReady=function() { 	if(typeof oldonload == 'function')oldonload();
".str_replace("\r","",$DomLoad)."};
</script>
";
}else{
    if(!empty($DomLoad))echo "<script type=\"text/javascript\">".$DomLoad."</script>";
    exit;
}
?>
</body>
</html>