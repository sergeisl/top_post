<?php // раз в сутки,- очистка устаревшей информации
$startTime=time();
if(empty($_SERVER['DOCUMENT_ROOT']))$_SERVER['DOCUMENT_ROOT']=dirname(dirname(__FILE__));
if(empty($_SERVER['HTTP_HOST']))$_SERVER['HTTP_HOST']='stuspeh.ru';
if(!isset($_SERVER['REMOTE_ADDR'])||!$_SERVER['REMOTE_ADDR'])$_SERVER['REMOTE_ADDR']='127.0.0.1';
if(!isset($_SERVER['REQUEST_URI'])||!$_SERVER['REQUEST_URI'])$_SERVER['REQUEST_URI']='/cron.php';
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
set_time_limit(3500); // не более часа
session_write_close();
CronLog(date("d-m-y H:i:s")." Старт.");



/*file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sql.sql', "");
DB::sql("DELETE FROM `".db_prefix."prixod` WHERE dat='2010-01-01' and user=0");
$query=DB::sql("SELECT * FROM ".db_prefix."tovar WHERE (type=".tTYPE_TOVAR." or type=".tTYPE_RASX.")");
while ($tov = DB::fetch_assoc($query)){
    $ost_s=Tovar::GetOst($tov);
    if($ost_s!=$tov['ost']){
        DB::sql("INSERT INTO `".db_prefix."prixod` ( `dat`, `tovar`, `kol`, `price`, `user`) VALUES ( '2010-01-01', '".$tov['id']."', '".($tov['ost']-$ost_s)."', '".$tov['price']."', '0')");
        echo "<br>".$tov['kod_prodact'].' - '.$tov['name'].' - '.($tov['ost']-$ost_s);
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sql.sql', DB::$query.";\n", FILE_APPEND);
    }
}*/

// пересчет остатков
$query=DB::sql("SELECT * FROM ".db_prefix."tovar WHERE (type=".tTYPE_TOVAR." or type=".tTYPE_RASX.")");
while ($tov = DB::fetch_assoc($query)){
    $ost_s=Tovar::GetOst($tov);
    if($ost_s!=$tov['ost']){
        echo "<br>".$tov['kod_prodact'].' - '.$tov['name'].' - '.$tov['ost'].'-&gt;'.$ost_s;
        DB::sql("UPDATE ".db_prefix."tovar SET `ost`='".$ost_s."' WHERE id=".$tov['id']);
    }
}

// удаляю старые протоколы ошибок
$path = $root . '/log/error';
$dh = opendir( $path ) or add_error( "Не удалось открыть каталог ".$path );
$path.='/';
$df1=date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")-10, date("Y")));	// храню ошибки 10 дней
while ( $f = readdir( $dh ) ) {
    if (substr($f,0,1)=='.') continue;
    $df=date("Y-m-d", filemtime($path.$f));
    if ( $df < $df1 )@unlink($path.$f);
   }
closedir($dh);

// удаляю старые сессии
/*$path = $root . '/log/session';
$dh = opendir( $path ) or add_error( "Не удалось открыть каталог ".$path );
$path.='/';
$df1=date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));	// храню сесии 1 день
while ( $f = readdir( $dh ) ) {
    if (substr($f,0,1)=='.') continue;
    if ( date("Y-m-d", filemtime($path.$f)) < $df1 )@unlink($path.$f);
   }
closedir($dh);
*/

DB::sql("DELETE FROM `".db_prefix."sms` WHERE time < '".date("Y-m-d H:i:s",time()-60*60*24*90)."'");
echo "\nУдаление устаревших SMS: " . DB::affected_rows();


// перестраиваю карту сайта
//include_once $_SERVER['DOCUMENT_ROOT']."/include/sitemap.php";
//CronLog("Карта сайта обновлена");

/*$fil=$_SERVER['DOCUMENT_ROOT'] . '/log/error/1_day.txt';
if(is_file($fil) && (filemtime($fil)>=strtotime("-1 day") ) ) exit;
$d_from=(is_file($fil)?filemtime($fil):strtotime("-1 day"));
file_put_contents($fil, (is_file($fil)?date('Y-m-d H:i',fileatime($fil)).', '.date('Y-m-d H:i',filemtime($fil)).' < ':'') . date('Y-m-d H:i',time()), LOCK_EX );*/

// это выполняется раз в сутки

DeleteOldFile(fb_tmpdir);
DeleteOldFile($_SERVER['DOCUMENT_ROOT'].'/log/error/');
DeleteOldFile(fb_cachedir);

// todo удалить старые товары и старые заказы
/*$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE date_end<'".date("Y-m-d",strtotime("-180 day"))."'");
CronLog("Удалено старых объявлений: ".DB::num_rows($query));
while($u = DB::fetch_assoc($query)){
    OBJ::Delete($u['id']);
}*/

ArchiveLog($_SERVER['DOCUMENT_ROOT']."/log/*.{log,txt}");


function DeleteOldFile($path){
    if(substr($path,-1,1)=='/')$path=substr($path,0,-1);
    $count=0;
    if($dh=opendir($path)){
        while (($f = readdir($dh)) !== false) {
            if (substr($f,0,1)!='.' && filemtime($path.'/'.$f)+(60*60*24*20) < time()){ 	// 20 дней
                unlink($path.'/'.$f);
                $count++;
            }
        }
        closedir($dh);
        CronLog("Удалено старых файлов из ".$path.": ".$count);
    }
}
function CronLog($add_mes){
    $fil=$_SERVER['DOCUMENT_ROOT'] . '/log/cron.log';
    file_put_contents($fil, "\n".$add_mes, FILE_APPEND|LOCK_EX);
    echo "<br>\n".nl2br($add_mes);
}

/** архивирует переданный файл в архив, если он превышает заданный размер
 * @param string|array $file файл(ы), подлежащий архивированию или строка запрпоса для glob. Расширение обязательно должно присутствовать! $_SERVER['DOCUMENT_ROOT']."/log/*.{log,txt}"
 * @param boolean $add добавлять или перезаписывать архив
 * @param int $MaxSize максимальный размер лога, который нужно упаковывать, по умолчанию 1Мб
 */
function ArchiveLog($file, $add=false, $MaxSize=1048576){
    if(is_array($file)){
        $files=$file;
    }elseif(strpos($file,'*')===false){
        $files= [$file];
    }else{
        $files=glob($file, GLOB_BRACE);
    }
    if($files)foreach($files as $file){
        if(!is_file($file)){echo "<br>\nНет ".$file; continue;}
        if(filesize($file) < $MaxSize){echo "<br>\n".$file." ".filesize($file); continue;}
        $coma=strrpos($file, '.');
        $fileArxive=substr($file,0,$coma).'.zip';
        // переименовываю, т.к. в него может идти запись из паралельного потока
        $fileAdd=substr($file,0,$coma) .'_'. date("Y_m_d") . substr($file,$coma);
        rename($file, $fileAdd);
        $file=basename($add ? $fileAdd : $file );// в архиве должны быть уникальные имена файлов
        $zip = new ZipArchive;
        // CREATE - Создаем архив
        // OVERWRITE - Перезаписываем
        if($zip->open($fileArxive, ($add&&is_file($fileArxive)? ZipArchive::CHECKCONS : ZipArchive::OVERWRITE) ) === TRUE){
            // первый параметр - откуда взять, второй как назвать внутри архива
            $zip->addFile($fileAdd, $file);
            $zip->close();
            echo "<br>\nУпаковал ".$file." в ".$fileArxive . " " . filesize($fileArxive);
            unlink($fileAdd);
        }else echo 'Ошибка создания архива '.$fileArxive;
    }
}
