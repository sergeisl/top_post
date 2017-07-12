<?
$title='Загрузка прихода';
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
if(isset($_GET['zakaz'])){
  $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=0 and srok<>0");
  while ($row = DB::fetch_assoc($query)){
	   DB::sql("INSERT INTO `".db_prefix."prixod`
		( `dat`, `tovar`, `kol`, `price`, `user`)
		VALUES ( '".date('Y-m-d')."', '".$row['id']."', '".$row['srok']."', '".$row['price']."', '".$_SESSION['user']['id']."')");
	   DB::sql("UPDATE `".db_prefix."tovar`
		SET `ost`='".addslashes(intval($row['ost']+$row['srok']))."'
		WHERE id='".$row['id']."' LIMIT 1");
	   }
   header("location: http://".$_SERVER['HTTP_HOST']."/tovar_prixod.php");
   exit;
}
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
echo "\n<h1>".$title."</h1>\n";

if(isset($_FILES['f'])){
     $path1=$_SERVER['DOCUMENT_ROOT'].'/log/tmp'; // путь куда класть файл
   $f='';
   print "<br>f_tmp=".$_FILES['f']['tmp_name']."<br>\n";
   $mURL	=$_FILES['f']['tmp_name'];
   $mURL_type	=$_FILES['f']['type'];
   $mURL_name	=$_FILES['f']['name'];
   if (!empty($mURL_name)&&$_FILES['f']['error'])die("Ошибка(<b>".$_FILES['f']['error']."</b>) загрузки файла <b>".$mURL_name."</b> на сервер!");
   elseif(isset($mURL_type) && ($mURL_type!=''))
       {if ($mURL_type=='application/vnd.ms-csv'||$mURL_type=='text/plain'||$mURL_type=='text/csv'||$mURL_type=='application/vnd.ms-excel' ||
		$mURL_type=='application/gzip'||$mURL_type=='application/zip'){
	   {$nname=$path1.'/tmp_'.url2file(strtolower(basename($mURL_name)));
            if ( move_uploaded_file($mURL, $nname) ){
		$f=basename($nname);
		if($mURL_type=='application/gzip'||$mURL_type=='application/zip'||substr($f,-4,4)=='.zip'){
			$zip = new ZipArchive;
			if ( $zip -> open($fil) === true ) {
				if($zip -> extractTo($path1)){	// это папка куда распаковать
				   $zip -> close();
				   echo "<p>Архив ".$f." распакован</p>";
				   $dh = opendir( $path1 ) or die ( "Не удалось открыть каталог ".$path1 );
				   while ( $f = readdir( $dh ) ){
					if(substr($f,-4)=='.csv'){
					   $fil=$path1.'/'.$f;
					}else @unlink($fil);
				   }
				   closedir($dh);
				   //$f=basename($nname);
				}else{echo "Не смог распаковать ".$fil." в ".$nname;}
			}else { echo "<p>Ошибка при извлечении файлов из архива</p>"; }
		}else $fil=$path1.'/'.$f;
            }else die("Не смог сохранить ".$mURL." в ".$nname);}
        }elseif ($mURL!='')  { print "Неверный тип ".$mURL_type;}
    }else die("Неверный тип файла!");
}//FILES

if(isset($fil)&&file_exists($fil)){
  echo "<h4>Загружаю ".$fil."</h4>";
  $test=isset($_GET['test']);
//Код или код2;Кол-во
?>
 <table class="client-table">
 <tr>
  <th>№</th>
  <th>Код</th>
  <th>Наименование</th>
  <th>кол-во</th>
 </tr>

<?
   $tovar=[];
   $f = fopen($fil, "r") or die("Ошибка!");
   $cnt1=0;
   while (($data = fgetcsv($f, 1000, ";")) !== FALSE) {
        if(count($data)<2)continue;
        $tovar['kod_prodact']=$data[0]; if(!$tovar['kod'])continue;
	$tovar['kol']=floatval(str_replace(' ','',str_replace(',','.',$data[1])));if(!$tovar['kol'])continue;
	if($row=Tovar::LocateKod($tovar['kod_prodact'])){
	  echo "<br>\nПриходую <b>".$row['show_name']."</b>";
	  $query=DB::sql("SELECT * FROM `".db_prefix."prixod` WHERE dat='".date('Y-m-d')."' and tovar='".$row['id']."' LIMIT 1");
	  if(DB::num_rows($query)){echo " - уже сегодня оприходован!"; continue;}
	  $cnt1++;
	  if($test){
		echo "<tr id='id".$row['id']."'><td>".$cnt1."</td>".
		"<td>".$tovar['kod_prodact']."</td>".
		"<td class='left hand' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=".$row['id']."')\">".$row['show_name']."</td>".
		"<td>".$tovar['kol']."</td></tr>";
	  }else{
	   DB::sql("INSERT INTO `".db_prefix."prixod`
		( `dat`, `tovar`, `kol`, `price`, `user`)
		VALUES ( '".date('Y-m-d')."', '".$row['id']."', '".$tovar['kol']."', '".$row['price']."', '".$_SESSION['user']['id']."')");
	   DB::sql("UPDATE `".db_prefix."tovar`
		SET `ost`='".addslashes(intval($row['ost']+$tovar['kol']))."'
		WHERE id='".$row['id']."' LIMIT 1");
	   }
	}else{
	   echo "<br>\nНЕТ ".$tovar['kod_prodact'];
	}
   }
   flush();
   fclose($f);
   unlink($fil);
   echo "<h4>Принято ".$cnt1." товаров</h4>";

}
?>
<form enctype="multipart/form-data" method="POST" action="tovar_prixod_load">
<input name='MAX_FILE_SIZE' type='hidden' value='2000000'>
Файл для загрузки(&lt;2Мб):
<input type='file' name='f' size=45>
<input type="submit" class="button right" style="width:auto;" value="Проверить" onclick="this.form.action='tovar_prixod_load.php?test';">
<input type="submit" class="button right" style="width:auto;" value="Добавить" onclick="this.form.action='tovar_prixod_load.php';">
<input type="submit" class="button right" style="width:auto;" value="Заказ в приход" onclick="
if(confirm('Перенести заказ в приход?')){this.form.action='tovar_prixod_load.php?zakaz';}else return false;">
</form>
<p>0Код или код2;1Кол-во</p>
<p>Два раза в день один и тот же товар не добавляется!</p>
<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";
?>
