<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
/* page-break-inside: avoid; - разрыв страницы
@page {
	page-size: portrait;	- ориентация книжная
	page-size: landscape; - ориентация альбомная
	margin: 1cm 3cm 1cm 1.5cm; - значения полей при печати
}
hr { перед элементом ставится разрыв страницы
	page-break-before: always;
}
*/
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<title><?=(empty($title)?SHOP_NAME:$title)?></title>
<meta content="text/html; charset=<?=charset?>" http-equiv="Content-Type">
<meta name=ProgId content=Excel.Sheet>
<style type="text/css">
 @page {
  margin: 0 0 0 0;
 }
h1{font-size: 1.3rem;}
table{
 border-spacing:0;
 border-collapse:collapse;
}
th, td{
 text-align:center;
 border-spacing:0;
 border:solid 1px #aaaaaa;
 vertical-align:middle;
 font-size: 15px;
}
th{
 background-color:lightcyan;
 vertical-align:middle;
}
th span{
 font-weight:normal;
}

td span,td.small{
 text-align:left;
 font-size:11px;
}

td.left{
 text-align:left;
}
td.right {
 text-align:right;
}
td.i{
 font-style:italic;
 text-align:right;
}
</style>
</head>

<body>
<div>
<div style="float: right">Ежедневно с 08:00 до 22:00, тел. 8 (863) 241-4-241, Волкова 15</div>
