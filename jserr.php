<?php
define("AdminMail","kdg@htmlweb.ru");	// период кеширования сутки 24*60*60=86400

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    (
        empty($_SERVER['HTTP_REFERER']) ||
        strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false
    )
)    exit;


if (
    !isset($_POST['msg']) &&
    !isset($_POST['url']) &&
    !isset($_POST['line'])
)	exit;

$key = md5($_POST['url'] . ':' .  $_POST['line']);
$file = __DIR__ . '/log/error/js' . $key . '.html';

if(!is_file($file)){
$msg = <<<MSG
Message: {$_POST['msg']}

URL: {$_POST['url']}
Ref: {$_POST['ref']}
Line: {$_POST['line']}

Browser: {$_SERVER['HTTP_USER_AGENT']}

MSG;

file_put_contents($file, $msg, LOCK_EX);

mail(AdminMail, "Javascript error",
	"".$msg."",
	"From: <noreply@".preg_replace("/www\./i","",$_SERVER['HTTP_HOST']).">\nContent-Type: text/plain; charset=windows-1251");

}
