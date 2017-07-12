<?php
if(!defined('fb_tmpdir'))define("fb_tmpdir",$_SERVER['DOCUMENT_ROOT'].'/images/tmp/');
if(!defined('fb_tmpdir0'))define("fb_tmpdir0",'/images/tmp/'); // WEB путь
if(!is_dir(substr(fb_tmpdir,0,-1)))mkdir(substr(fb_tmpdir,0,-1),0777,!0);
class File
{
    static public $ext_load= ['xlsx','xls','ods','csv']; // разрешенные расширения имени файла изображений
    static public $mime_load= ['application/vnd.ms-csv','text/plain','text/csv','application/vnd.ms-excel', 'application/x-zip-compressed', 'application/gzip', 'application/zip', 'application/x-rar-compressed', 'application/octet-stream', 'application/x-msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    static public $ext_img= ['jpeg','jpg','gif','png']; // расширения имени файла изображений
    static $ext_info="diz"; // расширения файла информации

    static function Get(){
        if (isset($_GET['auto'])) {
            $input = fopen("php://input", "r");
            $f = fb_tmpdir . 'tmp_in.csv';
            $fo = fopen($f, "w+");
            $realSize = stream_copy_to_stream($input, $fo);
            fclose($input);
            fclose($fo);
            if (isset($_SERVER["CONTENT_LENGTH"]) && $realSize != intval($_SERVER["CONTENT_LENGTH"])) Out::error("Ошибка загрузки!");

        } elseif (!empty($_REQUEST['f_url'])) { // передан ранее загруженный файл
            $f = $_REQUEST['f_url']; // $f = urldecode($_GET['f']);
            if(preg_match('/^https?:\/\/.*$/', $f)){
                $nname = fb_tmpdir . 'tmp_' . url2file(strtolower(basename($f)));
                @unlink($nname);
                if ( @copy($f, $nname)===true ) {
                    $f=$nname;
                }else Out::error("Не смог сохранить ".$f." в ".ShortUrl($nname));

            }elseif (!preg_match('/^[a-zA-Z0-9\-\_\.]*$/', $f)) Out::error("Ошибка в имени файла!");
            else {$f = fb_tmpdir . $f; if (!is_file($f)) Out::error("Ошибка в имени файла!");}

        } elseif (isset($_FILES['f'])) {
            $f = '';
            $mURL = $_FILES['f']['tmp_name'];
            //echo "<br>f_tmp=" . $mURL;
            $mURL_type = $_FILES['f']['type'];
            $mURL_name = $_FILES['f']['name'];
            if (!empty($mURL_name) && $_FILES['f']['error']){
                Out::error("Ошибка(<b>" . $_FILES['f']['error'] . "</b>) загрузки файла <b>" . $mURL_name . "</b> на сервер!");
            }elseif (isset($mURL_type) && in_array($mURL_type,self::$mime_load)){
                    $nname = fb_tmpdir . 'tmp_' . url2file(strtolower(basename($mURL_name)));
                    @unlink($nname);
                    if (move_uploaded_file($mURL, $nname)) {
                        $f = $nname;  //echo " - сохранил в " . $nname;
                    } else Out::error("Не смог сохранить " . $mURL . " в " . $nname);
                } elseif ($mURL != '') {
                Out::error("Неверный тип <b>" . $mURL_type . "</b>");
                }
        } else {
            //echo "<h4>Не передан файл для подлива</h4>"; //FILES
            $f = '';
        }
        return (empty($_SESSION['error'])?$f:'');
    }

    static function Extract($f){
        if (substr($f, -4, 4) == '.zip'){
            $f2='';
            $zip = new ZipArchive;
            if ($zip->open($f) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if(in_array(substr($entry, -3),self::$ext_load)) {
                        $f2=substr(basename($f),0,-3).substr($entry, -3);
                        echo "<p>Обнаружен файл " . $entry . ", переименовываю в ". $f2 . "</p>";
                        if(!$zip->renameIndex($i,'/'.$f2)) Out::error("Не смог переименовать файл $i в tmp_".basename($f)." внутри архива");
                        elseif($zip->extractTo(fb_tmpdir, '/'.$f2 )){$f2=fb_tmpdir.$f2; break;} // это папка куда распаковать
                        else Out::error("Не смог распаковать");
                    }else{
                        echo "<p class='blue'>В архиве обнаружен файл <b>" . $entry . "</b>, проигнорирован, т.к. не определен тип</p>";
                    }
                }
            }else Out::error("Архив поврежден!");
            if($f2 && empty($_SESSION['error']))@unlink($f); // удаляю исходный архив
            else $f2='';
            flush();
            return $f2;

/*                if ($zip->extractTo($path1)){ // это папка куда распаковать
                    $zip->close();
                    $f2='';
                    echo "<p>Архив " . $f . " распакован</p>";
                    $dh = opendir($path1) or die ("Не удалось открыть каталог " . $path1);
                    while ($f1 = readdir($dh)) {
                        if(in_array(substr($f1, -4),array('.csv','.xls'))) {
                            echo "<p>Обнаружен файл " . $f1 . "</p>";
                            $f2=fb_tmpdir.$f1; @unlink($f2);
                            rename($path1.'/'.$f1, $f2);
                        }else @unlink($f1);
                    }
                    closedir($dh);
                    rmdir($path1); // удаляю временный каталог
                    @unlink($f); // удаляю исходный архив
                    return $f2;
                }else {
                    echo "Не смог распаковать " . $f . " в " . $path1;
                }
            }else {
                echo "<p>Ошибка при извлечении файлов из архива</p>";
            }*/
        }elseif (substr($f, -4, 4) == '.rar'){
            $f2='';
            $path1= fb_tmpdir . 'tmp_'.basename($f); if(!is_dir($path1))mkdir($path1); // создаю временный каталог

            /*if (($rar_arch=RarArchive::open($f)) === FALSE) die("Could not open RAR archive.");
            if (($rar_entries= $rar_arch->getEntries()) === FALSE) die("Could retrieve entries.");
            foreach ($rar_entries as $f1) {
                if(in_array(substr($f1, -4),array('.csv','.xls'))) {
                    if (($rar_entry= $rar_arch->getEntry($f1)) === false) die("Failed to find such entry");
                    //$fil = $rar_entry->getStream($rar_entry);
                    $f2=fb_tmpdir.$f1; @unlink($f2);
                    $rar_entry->extract(false,$f2); // // $rar_entry->extract('/dir/extract/to/');
                }
            }*/

            $rar_file = rar_open($f);
            $list = rar_list($rar_file);
            foreach($list as $f1){
                if(in_array(substr($f1, -4),array('.csv','.xls'))) {
                    $entry = rar_entry_get($rar_file, $f1); // $entry = $rar_file1->getEntry('file2.txt');
                    //$entry->extract($path1); // extract to the current dir
                    $f2=fb_tmpdir.$f1; @unlink($f2);
                    $entry->extract(false,$f2);
                }
            }
            rar_close($rar_file);

            rmdir($path1); // удаляю временный каталог
            if(is_file($f2)){
                unlink($f); // удаляю исходный архив
                return $f2;
            }
        }
        return $f;
    }

static public $arxiv;
    /** открывает файл из архива
     * @param string $f - архив
     * @param string $filename - имя файла или конец имени файла или расширение
     *
     * можно использовать упроженные варианты:
     * file_get_contents(«zip://$file#$extract_file»);
     * $fil = fopen("zip://".$info['cache_filename']."#".$country['id'].'.txt', 'r');
     */
    static function Open($f,$filename='.csv')
    {
        if (substr($f, -4, 4) == '.zip'){ // буду подливать прямо из архива
            $fil = '';
            self::$arxiv = new ZipArchive;
            if (self::$arxiv->open($f) === true) {
                for ($i = 0; $i < self::$arxiv->numFiles; $i++) {
                    $entry = self::$arxiv->getNameIndex($i);
                    if (substr($entry, -strlen($filename)) == $filename) {
                        echo "<p>Использую <b>" . $entry . "</b> из архива <b>" . $f . "</b>.</p>";
                        $fil = self::$arxiv->getStream($entry);
                        break;
                    }
                }
            }
        } elseif (substr($f, -4, 4) == '.rar') { // буду подливать прямо из архива
            $fil = '';
            echo "<h3>RAR</h3>";
/*            $rar_arch = RarArchive::open($f);
            if ($rar_arch === FALSE) die("Could not open RAR archive.");
            $rar_entries = $rar_arch->getEntries();
            if ($rar_entries === FALSE) die("Could retrieve entries.");

            //echo get_class($rar_entry) . "\n";
            //echo $rar_entry;

            foreach ($rar_entries as $entry) {

                if (substr($entry, -4) == '.csv') {
                    echo "<p>Использую <b>" . $entry . "</b> из архива <b>" . $f . "</b>.</p>";
                    $rar_entry = $rar_arch->getEntry($entry);
                    if ($rar_entry === false) die("Failed to find such entry");
                    $fil = $rar_entry->getStream($rar_entry);
// $rar_entry->extract('/dir/extract/to/');
                    if ($fil === false) die("Failed to obtain stream.");
                    break;
                }
            }
            //rar_close($rar_file); //stream is independent from file*/

        } else {
            $fil = fopen($f, "r") or die("Ошибка " . $f . "!");
        }
        return $fil;
    }

static function Read($f, $from=0, $to=0){
    if( empty($_REQUEST['file_encoding']) || !in_array($_REQUEST['file_encoding'], ['utf-8', 'windows-1251']))  $_REQUEST['file_encoding']='utf-8';
    define('IMPORT_INPUT_ENCODING', $_REQUEST['file_encoding']); // Кодировка файла
    //--------------------------------------------------------------------------------
    // Получение данных с файла
    //--------------------------------------------------------------------------------

    $ext = pathinfo($f, PATHINFO_EXTENSION);
    switch ($ext) { // // Получение данных из файла
        case 'xlsx':
            spl_autoload_call('PHPExcel');
            $reader = PHPExcel_IOFactory::createReader('Excel2007');
            break;
        case 'xls':
            spl_autoload_call('PHPExcel');
            $reader = PHPExcel_IOFactory::createReader('Excel5');
            break;
        case 'ods':
            spl_autoload_call('PHPExcel');
            $reader = PHPExcel_IOFactory::createReader('OOCalc');
            break;
        case 'csv':
            $fil = fopen($f, "r") or die("Ошибка " . $f . "!");
            $result=[];
            $i=0;
            while (($data = File::fget_csv($fil, 0, $_REQUEST['csv_delim'], $_REQUEST['csv_enclosure'], '\\')) !== FALSE) {
                if($_REQUEST['file_encoding']!=charset)foreach($data as &$d)$d=iconv($_REQUEST['file_encoding'],charset.'//IGNORE',$d);
                if ($i++ >= $from) $result[] =$data;
                if($to>0 && $i>$to)break;
            }
            fclose($fil);
            return $result;

/*            $reader = new PHPExcel_Reader_CSV();
            $reader->setInputEncoding(IMPORT_INPUT_ENCODING);

            $reader->setDelimiter(html_entity_decode($_REQUEST['csv_delim'], ENT_QUOTES));
            $reader->setEnclosure(html_entity_decode($_REQUEST['csv_enclosure'], ENT_QUOTES));

            $excel = @$reader->load($f);
            $data = $excel->getSheet(0)->toArray();

            return array_filter(array_map('array_filter', $data));*/
        default:
            Out::error("Неизвестный формат <b>".$ext."</b> файла ".$f);
            return [];
    }

        $result = false;

        if ($reader->canRead($f)) {
            $excel = @$reader->load($f); /** @var $excel PHPExcel */
            $data = $excel->getSheet(0)->toArray();
            $result = array_filter(array_map('array_filter', $data));
        }
        return $result;
    }

    /**
     * @param string|file $f
     * @param int    $length
     * @param string $d
     * @param string $q
     * @return array|string
     */
    static function fget_csv($f, $length = 0, $d = ",", $q = '"')
    {
        $length = ($length ? $length : 99999);
        $list = [];
        $st = fgets($f, $length);
        if ($st === false || $st === null) return $st;
        $st=rtrim(trim($st),' '.$d); // удаляю все разделители в конце
        if (trim($st) === "") return [];
        while ($st !== "" && $st !== false) {
            if ($st[0] !== $q) {
                // Non-quoted.
                list ($field) = explode($d, $st, 2);
                $st = substr($st, strlen($field) + strlen($d));
            } else {
                // Quoted field.
                $st = substr($st, 1);
                $field = "";
                while (1) {
                    // Find until finishing quote (EXCLUDING) or eol (including)
                    preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                    $part = $p[1];
                    $st = substr($st, strlen($p[0]));
                    $field .= str_replace($q . $q, $q, $part);
                    if (strlen($st) && $st[0] === $q) {
                        // Found finishing quote.
                        list ($dummy) = explode($d, $st, 2);
                        $st = substr($st, strlen($dummy) + strlen($d));
                        break;
                    } else {
                        // No finishing quote - newline.
                        $st = fgets($f, $length);
                    }
                }

            }
            $list[] = $field;
        }
        return $list;
    }

    /**
     * @param string|file $f
     * @param int    $length
     * @param string $d
     * @param string $q
     * @return array|string
     */
    static function fget_csv_buf(&$buf, $length = 0, $d = ",", $q = '"')
    {
        if(empty($buf)) return null;
        $list = [];
        list($st,$buf) = explode("\n",$buf,2);
        if(trim($st) == "") return null;
        while ($st !== "" && $st !== false) {
            if ($st[0] !== $q) {
                // Non-quoted.
                list ($field) = explode($d, $st, 2);
                $st = substr($st, strlen($field) + strlen($d));
            } else {
                // Quoted field.
                $st = substr($st, 1);
                $field = "";
                while (1) {
                    // Find until finishing quote (EXCLUDING) or eol (including)
                    preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p);
                    $part = $p[1];
                    $st = substr($st, strlen($p[0]));
                    $field .= str_replace($q . $q, $q, $part);
                    if (strlen($st) && $st[0] === $q) {
                        // Found finishing quote.
                        list ($dummy) = explode($d, $st, 2);
                        $st = substr($st, strlen($dummy) + strlen($d));
                        break;
                    } else {
                        // No finishing quote - newline.
                        list($st,$buf) = explode("\n",$buf,2);
                    }
                }

            }
            $list[] = $field;
        }
        return $list;
    }

    static function IsTranzit($ctranzit, $tranzit){
        $tranzit=mb_strtolower(trim($tranzit));
        if ($ctranzit=='_' && substr($tranzit,0,1)!='0' && $tranzit!='заказ' && $tranzit!='call' && $tranzit!='нет' && $tranzit!='-') return true;
        elseif(trim($ctranzit)=='')return intval($tranzit)>0;
        elseif(strpos( $tranzit, mb_strtolower($ctranzit) )!==false)return true;
        return false;
    }

    static function GetExt($file){
        $qp=strpos( $file, "?" );
        if($qp!==false)$file=substr($file, 0, $qp);
        $ext=strtolower(substr(strrchr(basename($file), '.'),1));
        return $ext;
    }

    static function GetFilename($file){
        $qp=strpos( $file, "?" );
        if($qp!==false)$file=mb_substr($file, 0, $qp);
        //$file=basename($file);
        $qp=strrpos( $file, "." );
        $ext=substr($file,0,$qp);
        return $ext;
    }

    /** сохранение фото или произвольного файла
     * @param $purl
     * @param $filename
     * @param null|array $option $option['ext']=array('jpeg','jpg','gif','png', "csv","txt","ppt", "pptx", "pptm", "pps", "ppsx", "pdf", "doc", "odt", "ods", "xls", "xlt", "docx", "docm", "dot", "dotx", "xlsx", "rtf", "pot", "potx")
     * @return string
     */

    static function SaveFile($purl, $filename, $option = null)
    {
        // полный путь файла куда сохранять без расширения
        if (isset($_POST[$purl . '_url']) && ($pURL_name = $_POST[$purl . '_url']) != '') {
            //print "<br>".is_file($pURL_name)."~".$pURL_name;
            $ext=mb_strtolower(pathinfo($pURL_name, PATHINFO_EXTENSION));

            if (!empty($option['ext'])) self::$ext_load= $option['ext'];

            if (!($ext = self::GetExt($pURL_name))) {
                print "Неверный тип файла " . $ext;
                return '';
            }
            $nname = $filename . '.' . $ext;
            if (is_file($nname)) @unlink($nname);
            if (@copy($pURL_name, $nname) === true) {
                message("Сохранил в " . ShortUrl($nname));
                return $nname;
            } else print "Не смог сохранить " . $pURL_name . " в " . ShortUrl($nname);
        } elseif (isset($_FILES[$purl]['name'])) {
            self::Save1File($_FILES[$purl], $filename, $option);
        }
        return '';
    }

    /**
     * @param $f
     * @param string $filename - с полным путем, но без расширения
     * @param null $option
     * @return string
     */
    static function Save1File($f,$filename, $option = null){
        $pURL = $f['tmp_name'];
        $pURL_type = $f['type'];
        $pURL_name = $f['name'];
        //print"<br>".$filename;print_r($f);
        //if(filesize($pURL)/1024>$_SESSION['ImageMaxSize'])echo "<br />Размер <b>".$pURL_name." ".filesize($pURL)."</b> допустимо не более <b>".$_SESSION['ImageMaxSize']."</b>!\n";
        if ( !empty($f['error'])){
            if(!empty($pURL_name) && $f['error']==2) {
                global $ImageMaxSize;
                $ImageMaxSize=(!empty($_REQUEST['MAX_FILE_SIZE']) ? $_REQUEST['MAX_FILE_SIZE']/1000:
                    (!empty($_REQUEST['max_file_size']) ? $_REQUEST['max_file_size']/1000:
                        (!empty($_SESSION['ImageMaxSize']) ? $_SESSION['ImageMaxSize']/1000:
                            (!empty($ImageMaxSize) ? $ImageMaxSize/1000:'???'))));
                print "Ошибка загрузки файла <b>".$pURL_name."</b>. Размер больше <b>".$ImageMaxSize."</b>Kb (".ini_get('upload_max_filesize').")!";
            }else print "Ошибка(<b>".$f['error']."</b>) загрузки файла <b>".$pURL_name."</b>!";
        }elseif(!empty($pURL_type)) {
            if (!empty($option['ext'])) self::$ext_load= $option['ext'];

            $ext=self::GetExtForType($pURL_type); // определяю по типу
            //echo ",ext1=".$ext;
            if(empty($ext)) $ext=self::GetExt($pURL_name); // определяю по рассширению изображений
            //echo ",ext2=".$ext;
            if(!empty($ext)){
                $nname = $filename . '.' . $ext;
                if (is_file($nname)) @unlink($nname);
                if (move_uploaded_file($pURL, $nname)) {
                    message("Сохранил в " . ShortUrl($nname));
                    return $nname;
                } else print "Не смог сохранить " . $pURL . " в " . ShortUrl($nname);
            }else print "Недопустимый тип файла " . $pURL_type;
        }
        return '';
    }

    /** запрос через form, ответ приходит во фрейм
     * @param string $name_var
     * @return string
     */
static function SaveFILES($name_var='img'){
    global $ext_load;
    $f_ok=false;
    if(is_array($_FILES[$name_var]['name'])){// мультизагрузка
        $count = count($_FILES[$name_var]['name']);
        //print "FIELS==";var_dump($_FILES);
        for($j=0; $j < $count; $j++){
            list($fil,$i)=self::addTmpFile();
            $fil=self::Save1File(array(
                'name'     => $_FILES[$name_var]['name'][$j],
                'type'     => $_FILES[$name_var]['type'][$j],
                'tmp_name' => $_FILES[$name_var]['tmp_name'][$j],
                'error'    => $_FILES[$name_var]['error'][$j],
                'size'     => $_FILES[$name_var]['size'][$j]),
                $fil, $ext_load);
            //print "fil=";var_dump($fil);
            if($fil){
                if(self::is_img($fil) &&
                   !self::Resize($fil, $fil , imgBigSize)){echo "Изображение меньше 150x150 или это не изображение!"; continue;};
                $_SESSION['fb_fil'][$i]=$fil;
                $f_ok|=true;
            }
        }
    }else{
        list($fil,$i)=self::addTmpFile();
        $fil=Image::Save1File($_FILES[$name_var], $fil, $ext_load);
        if($fil){
            if(self::is_img($fil) &&
                !self::Resize($fil, $fil , imgBigSize)){echo "Изображение меньше 150x150 или это не изображение!"; return '';};
            $_SESSION['fb_fil'][$i]=$fil;
            $f_ok|=true;
        }
    }
    return $f_ok;
}

    /** возвращает TRUE, если переданный файл - файл изображения
     * @param string $fil
     * @return bool
     */
    static function is_img($fil){
        return in_array(self::GetExt($fil), self::$ext_img);
    }

    /**
     * @param $link - файл для которого создается временный, если не передан, то формируется имя временного файла без расширения и не добавляется в массив
     * @return array($fil, $i)
     */
    static function addTmpFile($link=''){
        if($link) {
            $ext=self::GetExt($link); if(!$ext)Out::err("Неверный тип файла: ".$link."(".$ext.")!");
        }else{
            $ext='';
        }
        $i=(isset($_SESSION['fb_fil'])?count($_SESSION['fb_fil']):0);
        $fil=$_SERVER['DOCUMENT_ROOT'].fb_dirfile.'tmp'.(User::is_login()?$_SESSION['user']['id']:session_id()).($i?'_'.$i:'').($ext?'.'.$ext:'');
        if($ext)$_SESSION['fb_fil'][$i]=$fil;
        return array($fil,$i);
    }

static function SaveLINK($link){ // передали ссылку на файл или через Ajax
    set_time_limit(600);
    list($fil,$i)=self::addTmpFile($link);
    if(@copy($link, $fil) !== true){unset($_SESSION['fb_fil'][$i]); Out::err("Ошибка загрузки!",'removeID(fb_modal);');}
    if(in_array(self::GetExt($fil), self::$ext_img) && defined('imgBigSize') &&
        !self::Resize($fil, $fil , imgBigSize)){unset($_SESSION['fb_fil'][$i]); Out::err("Изображение меньше 150x150 или это не изображение!");};
}

static function SaveIMG($name){
    set_time_limit(600);
    list($fil,$i)=self::addTmpFile($name);
    $input=fopen("php://input", "r");
    $f=fopen($fil, "w+");
    $realSize=stream_copy_to_stream($input, $f);
    fclose($input); fclose($f);
    if(isset($_SERVER["CONTENT_LENGTH"]) && $realSize != intval($_SERVER["CONTENT_LENGTH"]) ){unset($_SESSION['fb_fil'][$i]); Out::err("Ошибка загрузки!");}
    if(stripos($buf=file_get_contents($fil,null,null,null,30),'base64')!==false){
        $buf=base64_decode(str_replace(' ', '+',file_get_contents($fil,null,null,strpos($buf,',')))); //data:image/jpeg;base64,
        file_put_contents($fil, $buf);
    }
    if(in_array(self::GetExt($fil), self::$ext_img) && defined('imgBigSize') &&
        !self::Resize($fil, $fil , imgBigSize)){unset($_SESSION['fb_fil'][$i]); Out::err("Изображение меньше 150x150 или это не изображение!");};
}

static function GetExtForType($type_file){
        switch ($type_file) {
            case 'image/jpeg':
            case 'image/pjpeg':
                return 'jpg';
            case 'image/gif':
                return 'gif';
            case 'image/png':
            case 'image/x-png':
                return 'png';
            default:
                return '';
            //return false;
        }
    }

    /** изменение размера и сохранение в формате jpeg
     * @param $img
     * @param $target
     * @param $max
     * @param null $option
     * $option['body'] =true - выделить значимую часть, число, разница в цвете для выделения
     * $option['min'] = 150 - если размер меньше указанного не загружать
     * @return bool
     */
    static function Resize($img, $target, $max, $option = null)
    { // $img - с полным путем, $target с полным путем, $max - максим. размер

        if(!in_array(self::GetExt($img), self::$ext_img) ) return true; // размер не меняю, т.к. это разрешенный формат, но не изображение

        if(is_array($max)){
            list($width,$height) = $max;
        }elseif(strpos($max,',')!==false){
            list($width,$height) = explode(',',$max);
        }else{
            $width=$height=$max;
        }
        if (!is_file($img) || @filesize($img) < 10) {
            unlink($img);
            return false;
        }

        $srcImage = self::ImageOpen($img, $ext);
        if (!$srcImage) return false;

        $srcWidth = @ImageSX($srcImage);
        $srcHeight = @ImageSY($srcImage);
        if (!$srcWidth || !$srcHeight) return false;
        if (isset($option['min'])) if ($srcWidth < $option['min'] || $srcHeight < $option['min']) return false;
        if (($width < $srcWidth) || ($height < $srcHeight) || !empty($option['body'])) {

            if (!empty($option['body'])) {
                $rgb = imagecolorsforindex($srcImage, imagecolorat($srcImage, 0, 0));
                if (isset($_GET['debug'])) {
                    echo "<br>rgb:";
                    print_r($rgb);
                }
                // ищу первую строку значимой части изображения
                for ($h1 = 0; $h1 < $srcHeight / 2; $h1++)
                    for ($w = 0; $w < $srcWidth; $w++) {
                        $ar = imagecolorsforindex($srcImage, imagecolorat($srcImage, $w, $h1));
                        if (self::cmpColor($ar, $rgb, $option['body'])) {
                            if (isset($_GET['debug'])) {
                                echo "<br>h1:";
                                print_r($ar);
                            }
                            break(2);
                        }
                    }
                // ищу последнюю строку значимой части изображения
                for ($h2 = $srcHeight - 1; $h2 > ($srcHeight / 2); $h2--)
                    for ($w = 0; $w < $srcWidth; $w++) {
                        $ar = imagecolorsforindex($srcImage, imagecolorat($srcImage, $w, $h2));
                        if (self::cmpColor($ar, $rgb, $option['body'])) {
                            if (isset($_GET['debug'])) {
                                echo "<br>h2:";
                                print_r($ar);
                            }
                            break(2);
                        }
                    }
                // ищу первый столбец значимой части изображения в пределах ограниченных строк
                for ($w1 = 0; $w1 < ($srcWidth / 2); $w1++)
                    for ($h = $h1; $h < $h2; $h++) {
                        $ar = imagecolorsforindex($srcImage, imagecolorat($srcImage, $w1, $h));
                        if (self::cmpColor($ar, $rgb, $option['body'])) {
                            if (isset($_GET['debug'])) {
                                echo "<br>w1:";
                                print_r($ar);
                            }
                            break(2);
                        }
                    }
                // ищу последний столбец значимой части изображения
                for ($w2 = $srcWidth - 1; $w2 > ($srcWidth / 2); $w2--)
                    for ($h = $h1; $h < $h2; $h++) {
                        $ar = imagecolorsforindex($srcImage, imagecolorat($srcImage, $w2, $h));
                        if (self::cmpColor($ar, $rgb, $option['body'])) {
                            if (isset($_GET['debug'])) {
                                echo "<br>w2:";
                                print_r($ar);
                            }
                            break(2);
                        }
                    }
                if (isset($_GET['debug'])) {
                    echo "<br>src:" . $w1 . ", " . $h1 . ", " . ($w2 - $w1 + 1) . ", " . ($h2 - $h1 + 1) . " (" . $srcWidth . "x" . $srcHeight . ")";
                }
                if (($w2 - $w1) > 10 && ($h2 - $h1) > 10) {
                    $srcWidth = ($w2 - $w1);
                    $srcHeight = ($h2 - $h1);
                } else {
                    $w1 = $h1 = 0;
                }
            } else {
                $w1 = $h1 = 0;
            }

            $ratioWidth = ((float)$srcWidth) / $width;
            if ($ratioWidth == 0) die("Деление на 0:" . $srcWidth . '/' . $width);
            $ratioHeight = ((float)$srcHeight) / $height;
            if ($ratioHeight == 0) die("Деление на 0:" . $srcHeight . '/' . $height);
            if ($ratioWidth < $ratioHeight) {
                $destWidth = intval($srcWidth / $ratioHeight);
                $destHeight = $height;
            } else {
                $destWidth = $width;
                $destHeight = intval($srcHeight / $ratioWidth);
            }

            $resImage = ImageCreateTrueColor($destWidth, $destHeight);
            if ($ext == 'gif' || $ext == 'png') {
                ImageAlphaBlending($srcImage, false);
                ImageSaveAlpha($srcImage, true);
                ImageColorTransparent($srcImage, ImageColorAllocate($srcImage, 0, 0, 0)); // Задание прозрачности черного цвета фона
                ImageFilledRectangle($resImage, 0, 0, $destWidth, $destHeight, imagecolorallocate($resImage, 255, 255, 255)); // заливаю белым, на случай, если был прозрачный фон
            }
            //ImageCopyResampled($resImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
            ImageCopyResampled($resImage, $srcImage, 0, 0, $w1, $h1, $destWidth, $destHeight, $srcWidth, $srcHeight);
            //$target=$_SERVER['DOCUMENT_ROOT'].path_image.$target.'.jpg';
            //imagecolortransparent($resImage, imagecolorallocate($resImage, 255, 255, 255)); // Задание прозрачности белого  цвета фона

            //ImageJPEG($resImage, $target, 100); // 100 - максимальное качество
            self::ImageSave($resImage, $target, 75);

            ImageDestroy($resImage);
        } else {
            self::ImageSave($srcImage, $target, 100);
            //ImageJPEG($srcImage, $target, 100); // 100 - максимальное качество
        }
        ImageDestroy($srcImage);
        if (isset($_GET['debug'])) {
            echo "<img src='" . ShortUrl($target) . "' border=1>";
        }

        return true;
    }

    static function cmpColor($ar, $rgb, $body)
    {
        foreach ($rgb as $key => $val) if ($key != 'alpha')
            if ((($val > 225 && $ar[$key] < $val) ||
                    ($val < 30 && $ar[$key] > $val)) &&
                (abs($ar[$key] - $val) > $body)
            ) return true;
        return false;
    }

    /** возвращает размеры маленького изображения с сохранением пропорций
     * @param $fil
     * @param int $max - макксимальный размер, м.б.array(width,height)
     * @return array
     */
    static function getSmallImage($fil, $max = 100){
        if(is_array($max)){
            list($maxsizeW,$maxsizeH) = $max;
        }elseif(strpos($max,',')!==false){
            list($maxsizeW,$maxsizeH) = explode(',',$max);
        }else{
            $maxsizeW=$maxsizeH=$max;
        }

        list($width, $height) = @getimagesize($fil);
        if ($width && $height) {
            if ($width > $maxsizeW || $height > $maxsizeH) {
                $ratioWidth = $width / $maxsizeW;
                $ratioHeight = $height / $maxsizeH;
                if ($ratioWidth < $ratioHeight) {
                    $width = intval($width / $ratioHeight);
                    $height = $maxsizeH;
                } else {
                    $height = intval($height / $ratioWidth);
                    $width = $maxsizeW;
                }
            }
        }
        return array($width, $height);
    }

    /**
     * @param int $id
     * @param array $options
     *                      'path' путь с префиксом имени файла, но без id, у товара 'p'
     *                      'logo'=0 - без наложения изображений
     */
    static function blockLoadImage($id=0,$options=array())
    {
        /* При использовании обязательно:
            На форме  <form action="/api.php" method="post" enctype="multipart/form-data"
                ondragenter="return _frm.drop(event);"
                ondragover="return _frm.drop(event);"
                ondragleave="return _frm.drop(event);"
                ondrop="return _frm.drop(event);">
        Сами восстанавливаются form.target, form.action
        Используется объект javascript: _frm
        */
        if($id){// переношу сохраненные картинки в сесионные переменные
            if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $f)if(strpos($f, fb_dirfile.'tmp')!==false&&is_file($f))@unlink($f);
            unset($_SESSION['fb_fil']);
            for($i=0;$i<99;$i++){
                if($fil=Image::is_file($options['path'].$id.($i?'_'.$i:''))){
                    $_SESSION['fb_fil'][$i]=$_SERVER['DOCUMENT_ROOT'].$fil;
                }//else break;
            }
        }else{
            if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $i=>$f){
                //echo "<br>".$f.','.fb_dirfile.'tmp';
                if(strpos($f,fb_dirfile.'tmp')===false)unset($_SESSION['fb_fil'][$i]);
            }
        }
        //var_dump($_SESSION['fb_fil']);
?>
        <tr class="act-1 act-2">
            <td style="vertical-align:top;padding-top:5px;">Выбрать фото</td>
            <td>
                <input name="MAX_FILE_SIZE" type="hidden" value="<?=max_size_image * 1000?>">
                <input type="file" name="img[]" id="file" multiple placeholder="Загрузите одну или несколько фотографий, удерживая Ctrl" accept="image/*" onchange="_frm.change(event)">
                <input type="submit" formnovalidate value="Загрузить" id="submit_file" onclick="_frm.load_file()" style="visibility:hidden">
                <br><span class="gray fs12">Для выбора нескольких изображений, удерживайте клавишу Ctrl</span>
<?
/*echo <<<HTML
<fieldset class="w100">
<legend> Фотографии ( &lt; {$max_size_image}Kb каждая ): </legend>
<input name="MAX_FILE_SIZE" type="hidden" value="{$max_size_image_b}">
<label class="w100"><b>url: <input style="min-width:200px" type="text" name="img_url" onblur="if(this.value.length>6)ajaxLoad('img_block','/api.php?link='+encodeURIComponent(this.value));this.value='';"></b></label>
<label class="w100"><b>или файл: <input type="file" name="img[]" id="file" multiple placeholder="Загрузите одну или несколько фотографий" accept="image/*" onchange="getObj('submit_file').click();"></b></label>
<span class="drag">Или перетяните изображение сюда</span>
<input type="submit" formnovalidate value="Загрузить" id="submit_file" onclick="_frm.load_file()" style="visibility:hidden">
HTML;*/
        echo "\n</td></tr>\n<tr><td colspan=2><div id=\"img_block\">" . self::AddFile() . "<br class='clear'>\n</div>"; // вывожу блок с ранее загруженными картинками
        if(isset($options['logo']) && $options['logo']=='0'){?>
            <input type="hidden" name="LogoSize" value="0">
        <?}else{
        if (User::is_admin(true)) {
            $LogoSmall=(is_file($_SERVER['DOCUMENT_ROOT'] . fb_logofile)?'selected':'disabled');
            $LogoBig=(is_file($_SERVER['DOCUMENT_ROOT'] . fb_logofileBig)?($LogoSmall=='selected'?'':'selected'):'disabled');
?>
<br class="clear">
        <p class="mb10"><b>Наложение логотипа на картинки</b></p>
        <?
        if($LogoSmall=='disabled' && $LogoBig=='disabled'){?>
            <b>не загружены логотипы для наложения в <?=fb_logofile?></b>
        <?}else{?>
        <select id='LogoSize' name='LogoSize'>
            <option value='0'>нет</option>
            <option value='small' <?=$LogoSmall?>>Маленький</option>
            <option value='big' <?=$LogoBig?>>Большой</option>
        </select>
        <select id='LogoPosition' name='LogoPosition'>
            <option value='RightBottom' selected>Правый нижний</option>
            <option value='RightTop'>Правый верхний</option>
            <option value='LeftBottom'>Левый нижний</option>
            <option value='LeftTop'>Левый верхний</option>
        </select>
        <?}?>
</td></tr>
<?
        }
        }
?>
<iframe id="upload_frame" name="upload_frame" onload="" onerror=""></iframe>
<? if (isset($_GET['ajax'])) echo '<script type="text/javascript" defer="defer">setTimeout(_frm.set_event,1000);</script>'; ?>
</td>
</tr>
<?
}

    //static function OutInfoFile() перенес в Image::

    /** формирует html отображения файлов из $_SESSION['fb_fil']
     * @param string $pref путь и часть имени файла, например fb_dirfile.'p'.$tov[Tovar::img_name]
     *               если $pref='' - режим preview без переименования изображений
     * @return string
     */
    static function AddFile($pref=''){
        $addf='';
        $cnt=0;
        if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $i => $f)
            if(is_file($f)){
                if($pref){
                    $fil0=$pref.($cnt?('_'.$cnt):'').'.'.Image::GetExt($f);
                    $fil=$_SERVER['DOCUMENT_ROOT'].$fil0;
                    if($f!=$fil){
                        if(is_file($fil))unlink($fil);
                        rename($f, $fil);
                        // переношу примечание
                        $fil_old=substr($f,0,-strlen(pathinfo($f, PATHINFO_EXTENSION))) . self::$ext_info;
                        $fil_new=substr($fil,0,-strlen(self::$ext_info)) . self::$ext_info;
                        if(is_file($fil_old))rename($fil_old, $fil_new);
                        elseif(is_file($fil_new))unlink($fil_new);
                        // обязательно после выхода удаляю все маленькие картинки!
                    }
                    unset($_SESSION['fb_fil'][$i]);
                    // если это новая картинка - преобразую размер и накладываю лого
                    if(in_array(self::GetExt($fil), self::$ext_img) && cmp($f, $_SERVER['DOCUMENT_ROOT'].fb_dirfile.'tmp') ){
                        if(Image::Resize($fil, $fil , imgBigSize)){ // накладываю лого, если исходное изображение достаточно большое
                            $position=(isset($_REQUEST['LogoPosition']) && $_REQUEST['LogoPosition']?$_REQUEST['LogoPosition']:"RightBottom");
                            if(isset($_REQUEST['LogoSize']) && $_REQUEST['LogoSize']=='big' && is_file($_SERVER['DOCUMENT_ROOT'].fb_logofileBig))
                                Image::SetLogo($fil, $_SERVER['DOCUMENT_ROOT'].fb_logofileBig, $position);
                            elseif(is_file($_SERVER['DOCUMENT_ROOT'].fb_logofile))
                                Image::SetLogo($fil, $_SERVER['DOCUMENT_ROOT'].fb_logofile, $position);
                        }
                    }
                }else{
                    $fil0=substr($f,strlen($_SERVER['DOCUMENT_ROOT']));
                    $fil=$f;
                }
                $desc=substr($fil,0,-strlen(pathinfo($f, PATHINFO_EXTENSION))) . self::$ext_info;
                $desc=(is_file($desc)?file_get_contents($desc):'');
                $filU=urlencode($fil0);
                if(self::is_img($fil0)){
                    // Определить размер изображения и указывать так, чтоб не было искажений
                    list($width, $height)=Image::getSmallImage($fil,imgMediumSize);
                    if($width && $height){
                        $addf.="<div class='img'>".self::imgPreview($fil0, ['whithA'=>true,'size'=>imgMediumSize])."<div class='icon-tape'>".
                                 $desc."<a onclick='if(confirm(\"Удалить?\"))ajaxLoad(\"img_block\",this.href,\"удаляю...\");return false;' href='/api.php?del_img=".$filU."' title='Удалить' class='icon del'></a>
                                        <a onclick='return _frm.edit_desc(\"".$fil0."\")' title='Описание файла' href='#' class='icon edit'></a><br class='clear'></div></div>";

                    }else
                        $addf.="<div class='left'>Ошибка в изображении</div>";
                }else{ // это документ
                    $addf.="<div class='img'>".self::imgPreview($fil0, ['whithA'=>true,'size'=>imgMediumSize])."<div class='icon-tape'>".
                                 $desc."<a onclick='if(confirm(\"Удалить?\"))ajaxLoad(\"img_block\",this.href,\"удаляю...\");return false;' href='/api.php?del_img=".$filU."' title='Удалить' class='icon del'></a>
                                        <a onclick='return _frm.edit_desc(\"".$fil0."\")' title='Описание файла' href='#' class='icon edit'></a><br class='clear'></div></div>";
                }
                $cnt++;
            }else{
                $addf.="<div class='left'>Нет файла ".substr($f,strlen($_SERVER['DOCUMENT_ROOT']))."</div>";
                //self::FileSdvig(substr($_SESSION['fb_fil'][$i],strlen($_SERVER['DOCUMENT_ROOT'])), fb_dirfile.'tmp', session_id(), '');
                unset($_SESSION['fb_fil'][$i]);
            }
        //if($pref)self::FileSdvig($name, $pref,'p',$id);
        return ($addf?str_replace("\n","",$addf):'');
    }

/** Выводить html код показа файла <img ...
 * @param $fil - путь к файлу от корня сайта
 * @param bool|string $options =true - обрамлять в <a>...</a> или ссылка на большую картинку, если они разные
 *                  $options['whithA'] - обрамлять в <a>...</a> или ссылка на большую картинку, если они разные
 *                  $options['size'] - максимальный размер изображения, по умолчанию imgMediumSize, м.б.Array(width,height)
 *                  $options['alt'] - текст для картинки
 * @return string
 */
    static function imgPreview($fil,$options=null){
        //var_dump($fil,$options);
        if(is_bool($options) && $options ) $options=array('whithA'=>true);
        $fil1=ImgSrc(!empty($options['whithA'])&&is_string($options['whithA']) ? $options['whithA'] : $fil); // добавляю версию файла картинки для решения проблемы кеширования
        //$alt=(empty($options['alt']) ? self::Alt($fil) : $options['alt'] );
        $alt=self::Alt($fil,(empty($options['alt'])?'':$options['alt']));
        $size=(empty($options['size'])? imgMediumSize : $options['size']);
        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$fil)){
            $fil="/images/none.gif";
            list($width, $height)=Image::getSmallImage($_SERVER['DOCUMENT_ROOT'].$fil, $size);
            /*if($size==imgSmallSize) $img="<img src='".$fil."' alt='Нет изображения'>";
            else*/ $img="<img src='".$fil."' width='".$width."' height='".$height."' alt='Нет изображения'>";
            return $img;
        }
        if(self::is_img($fil)){
            list($width, $height)=Image::getSmallImage($_SERVER['DOCUMENT_ROOT'].$fil, $size);
            if($width && $height){
                $filPreview=ImgSrc($fil); // добавляю версию файла картинки для решения проблемы кеширования
                $a="<a href='".$fil1."' onclick='return openwind(this)' title='".$alt."'>";
            }else{
                return "<div class='left'>Ошибка в изображении</div>";
            }
        }else{
            $ext=self::GetExt($fil);
            $filPreview='/images/ext/'.$ext.'.png';
            if(!is_file($_SERVER['DOCUMENT_ROOT'].$filPreview))$filPreview='/images/ext/doc.png';
            $width=$height=$size;
            $a="<a href='".$fil1."'>";
        }
        /*if($size==imgSmallSize)$img="<img src='".$filPreview."' data-src='".$fil."' alt='".$alt."'>";
        else*/ $img="<img src='".$filPreview."' width='".$width."' height='".$height."' data-src='".$fil."' alt='".$alt."'>";
        //else $img="<img src='".$filPreview."' data-src='".$fil."' alt='".$alt."'>";
        return (empty($options['whithA']) ? $img : $a.$img."</a>" );

    }

    static function Alt($fil,$def=''){
        $alt=$_SERVER['DOCUMENT_ROOT'].substr($fil,0,-strlen(self::$ext_info)) . self::$ext_info;
        return (is_file($alt) ? addslashes(file_get_contents($alt)) : $def );
    }

    static function ImageOpen($filename, &$ext = '')
    {
        switch ($ext = Image::GetExt($filename)) {
            case 'jpg':
                $srcImage = @ImageCreateFromJPEG($filename);
                break;
            case 'gif':
                $srcImage = @ImageCreateFromGIF($filename);
                break;
            case 'png':
                $srcImage = @ImageCreateFromPNG($filename);
                break;
            //case 'bmp':
            //$srcImage = @imagewbmp($img); 	break;
            default:
                $srcImage = '';
        }
        if (!$srcImage) { // или нет расширения или в файле с одним расшироением другой формат изображения
            if ($srcImage = @ImageCreateFromJPEG($filename)) { // предполагаю, что это jpg
                $ext = 'jpg';
            } elseif ($srcImage = @ImageCreateFromGIF($filename)) {
                $ext = 'gif';
            } elseif ($srcImage = @ImageCreateFromPNG($filename)) {
                $ext = 'png';
            } else {
                echo "Неверный формат файла изображения " . $filename;
                return false;
            }
        }
        return $srcImage;
    }

    static function ImageSave($img, $filename, $quality = 100)
    {
        //die('<br>Сохраняю в '.$filename);
        @unlink($filename);
        switch (self::GetExt($filename)) {
            case "jpg":
                ImageJPEG($img, $filename, $quality);
                break; // 0-100, 100-best
            case "gif":
                ImageGIF($img, $filename);
                break;
            case "png":
                ImagePNG($img, $filename, min(9, intval((100 - $quality) / 10)));
                break; // 0-9, 0 - best
        }
    }

/*    static function SaveImage($fil, $kod)
    {
        $img_p = fb_dirfile . 'p' . $kod . '.jpg';
        self::Resize($fil, $_SERVER['DOCUMENT_ROOT'] . $img_p, imgBigSize, 7);
        return $img_p;
    }*/

    static function SetLogo($filename, $logo, $position = "RightBottom")
    {
        $srcImage = self::ImageOpen($filename);
        if ($srcImage === false) return false;
        $logoImage = self::ImageOpen($logo);
        if ($logoImage === false) return false;

        $srcWidth = ImageSX($srcImage);
        $srcHeight = ImageSY($srcImage);

        $logoWidth = ImageSX($logoImage);
        $logoHeight = ImageSY($logoImage);

        /*imageAlphaBlending($srcImage, false);
        imageSaveAlpha($srcImage, false);*/

        imageAlphaBlending($logoImage, true);
        imageSaveAlpha($logoImage, true);

        $trcolor = ImageColorAllocate($logoImage, 255, 255, 255);
        ImageColorTransparent($logoImage, $trcolor);

        if ($position == "RightTop")
            //imagecopymerge($srcImage, $logoImage, $srcWidth - $logoWidth, 0, 0, 0, $logoWidth, $logoHeight, 50);
            imagecopy($srcImage, $logoImage, $srcWidth - $logoWidth,0, 0, 0, $logoWidth, $logoHeight);
        elseif ($position == "LeftBottom")
            //imagecopymerge($srcImage, $logoImage, 0, $srcHeight - $logoHeight, 0, 0, $logoWidth, $logoHeight, 50);
            imagecopy($srcImage, $logoImage, 0, $srcHeight - $logoHeight, 0, 0, $logoWidth, $logoHeight);
        elseif ($position == "LeftTop")
            //imagecopymerge($srcImage, $logoImage, 0, 0, 0, 0, $logoWidth, $logoHeight, 50);
            imagecopy($srcImage, $logoImage, 0, 0, 0, 0, $logoWidth, $logoHeight);
        else // RightBottom
            //imagecopymerge($srcImage, $logoImage, $srcWidth - $logoWidth, $srcHeight - $logoHeight, 0, 0, $logoWidth, $logoHeight, 100);
            imagecopy($srcImage, $logoImage, $srcWidth - $logoWidth, $srcHeight - $logoHeight, 0, 0, $logoWidth, $logoHeight);

        self::ImageSave($srcImage, $filename);

        ImageDestroy($logoImage);
        ImageDestroy($srcImage);
        return true;
    }

    /**  возвращает имя файла по шаблону
     * @param string $s - имя файла с путем от корня сайта без расширения
     * @param null|boolean|string|array $ext - расширение или шаблон расширений или если true - то все картинки.
     * @return string
     */
    static function is_file($s,$ext=null){
        if(empty($ext)|| (is_bool($ext)&&$ext)) if(is_file($_SERVER['DOCUMENT_ROOT'].$s.".jpg"))return $s.".jpg";
        //$files=glob($_SERVER['DOCUMENT_ROOT'].$s.".{gif,jpg,png}", GLOB_BRACE);
        $ext=(empty($ext)?'*':(is_bool($ext)&&$ext ? '{'.implode(',',self::$ext_img).'}' : (is_array($ext) ? '{'.implode(',',$ext).'}' : $ext )));
        $files=glob($_SERVER['DOCUMENT_ROOT'].$s.".".$ext, GLOB_BRACE);
        if($files)foreach($files as $file) if(self::GetExt($file)) return substr($file,strlen($_SERVER['DOCUMENT_ROOT']));
        return '';
    }

    static function DelFile($name){
    if($name!='' && isset($_SESSION['fb_fil'])){
        if(preg_match('#^(.*/)([a-z]+)(\d+)[\._]#is', $name, $res)){
            //die(var_export($res));
            $pref=$res[1];  // 1 => '/images/tovar/'
            $p=$res[2]; // 2 => 'p'
            $id=$res[3]; // 3 => '2'
            if(!cmp($pref,fb_dirfile))die('Путь '.$pref.' не ведет к каталогу картинок!');
            if($p=='p'){// сначала все картинки сохраню
                //var_dump($_SESSION['fb_fil']);
                self::AddFile($pref.$p.$id);
                self::ClearCash($id,$pref); // удалить маленькие картинки
                    // снова считываю в сессионную переменную
                for($i=0;$i<99;$i++){
                    $fil=self::is_file($pref.$p.$id.($i?('_'.$i):''));
                    if($fil)$_SESSION['fb_fil'][]=$_SERVER['DOCUMENT_ROOT'].$fil;
                }
                //var_dump($_SESSION['fb_fil']); die;
            }
            self::FileSdvig($name, $pref,$p,$id);
            //var_export($_SESSION['fb_fil']);

        }elseif(cmp($name,fb_dirfile.'tmp'.session_id())){ // fb_dirfile.'tmp'.(User::is_login()?$_SESSION['user']['id']:session_id()).'_'.$i.'.'.$fil
            self::FileSdvig($name, fb_dirfile.'tmp', (User::is_login()?$_SESSION['user']['id']:session_id()) , '');

        }else Out::err('Ошибка выделения id');
    }
}

    static function ClearCash($id, $path=fb_dirobjfile){ // удалить маленькие картинки
        for($i=0;$i<99;$i++){
            $files=glob($_SERVER['DOCUMENT_ROOT'].$path.'m'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files)foreach($files as $fil)if(is_file($fil))unlink($fil);
            $files=glob($_SERVER['DOCUMENT_ROOT'].$path.'s'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files)foreach($files as $fil)if(is_file($fil))unlink($fil);
        }
    }

    static function DeleteAll($id){ // удалить все картинки
        for($i=0;$i<99;$i++){
            $files=glob($_SERVER['DOCUMENT_ROOT'].fb_dirobjfile.'{m,s,p}'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            foreach($files as $fil)@unlink($fil);
        }
    }

    /** переношу загруженные картинки
     * @param $tbl
     * @param $id
     * @return string
     */
static function FileSave($tbl,$id){
    /*if(!empty($_FILES['img']['name']) ){ // запрос через form, ответ приходит во фрейм
                if(Image::SaveFILES('img')){
                    message('Загружено!');
                }else{
                    error("Ошибка загрузки файла на сервер!");
                }
    }*/
    $add='';
    if (isset($_SESSION['fb_fil'])) {
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/images/' . $tbl)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/images/' . $tbl,0777,!0);
            Out::message("Создал папку картинок: /images/" . $tbl);
        }
        ksort($_SESSION['fb_fil']);
        $i=0;
        foreach ($_SESSION['fb_fil'] as $f)
            if (is_file($f)) {
                $fil = '/images/' . $tbl . '/p' . $id . ($i ? '_' . $i : '') . '.jpg';
                $fil2 = $_SERVER['DOCUMENT_ROOT'] . $fil;
                if ($f !== $fil2) {
                    rename($f, $fil2);
                    touch($fil2);

                    // сдвигаю описание файлов
                    $fil1=$_SERVER['DOCUMENT_ROOT'].substr($f,0,-3) . self::$ext_info;
                    $fil2=$_SERVER['DOCUMENT_ROOT'].substr($fil2,0,-3) . self::$ext_info;
                    if(is_file($fil1)){
                        if(is_file($fil2))unlink($fil2);
                        rename($fil1, $fil2);
                        touch($fil2);
                    }elseif(is_file($fil2))unlink($fil2);

                    $add .= "\nСохранил " . $fil;
                }
                $i++;
            }

        unset($_SESSION['fb_fil']);
        File::ClearCash($id, '/images/' . $tbl . '/'); // при каждом сохранении удаляю превьюшки
    }
    return $add;
}

    static function FileSdvig($name, $pref, $p,$id){
    foreach($_SESSION['fb_fil'] as $i => $f){
        if($_SERVER['DOCUMENT_ROOT'].$name==$f && is_file($f)){
            unlink($f); @unlink(substr($f,0,-3) . self::$ext_info);
            unset($_SESSION['fb_fil'][$i]);
            for(;$i<99;$i++){ // сдвигаю файлы картинок и описаний
                if($p=='p'){@unlink($_SERVER['DOCUMENT_ROOT'].$pref.'m'.$id.($i?('_'.$i):'').'.jpg'); @unlink($_SERVER['DOCUMENT_ROOT'].$pref.'s'.$id.($i?('_'.$i):'').'.jpg');}
                $fil1=$pref.$p.$id.($i?('_'.$i):'').'.jpg';
                for($j=$i+1;$j<99;$j++){
                    $fil2=$pref.$p.$id.'_'.$j.'.jpg';
                    if(is_file($_SERVER['DOCUMENT_ROOT'].$fil2)){
                        rename($_SERVER['DOCUMENT_ROOT'].$fil2, $_SERVER['DOCUMENT_ROOT'].$fil1);
                        touch($_SERVER['DOCUMENT_ROOT'].$fil1);
                        $_SESSION['fb_fil'][$i]=$_SERVER['DOCUMENT_ROOT'].$fil1;
                        unset($_SESSION['fb_fil'][$j]);
                        // сдвигаю описание файлов
                        $fil1=$_SERVER['DOCUMENT_ROOT'].substr($fil1,0,-3) . self::$ext_info;
                        $fil2=$_SERVER['DOCUMENT_ROOT'].substr($fil2,0,-3) . self::$ext_info;
                        if(is_file($fil2)){
                            rename($fil2, $fil1);
                            touch($fil1);
                        }elseif(is_file($fil1))unlink($fil1);
                        break;
                    }
                }
            }
            clearstatcache();
            break;
        }
    }
}

    static function DirSize($dir) {
        $totalsize=0;
        if ($dirstream = @opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                if ($filename!="." && $filename!="..")
                {
                    if (is_file($dir."/".$filename))
                        $totalsize+=filesize($dir."/".$filename);

                    if (is_dir($dir."/".$filename))
                        $totalsize+=File::DirSize($dir."/".$filename);
                }
            }
        }
        closedir($dirstream);
        return $totalsize;
    }
    //system("du -bs папка") первое число будет - размер в байтах
}
