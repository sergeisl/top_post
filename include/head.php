<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
if(!Get::isApi()) {
    if (isset($_REQUEST['print'])) {
        include_once $_SERVER['DOCUMENT_ROOT'] . "/include/head_print.php";
        return;
    }
    echo echoBlock('header');
}

//if (!empty($_SESSION['error'])) {echo "<div class='error'>".$_SESSION['error']."</div><br>\n"; unset($_SESSION['error']);}
//if (!empty($_SESSION['message']) && substr($_SESSION['message'],0,1)!=='<') {echo "<br clear='all'><div class='message'>".$_SESSION['message']."</div>\n"; unset($_SESSION['message']);}

