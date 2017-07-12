<?
if(!defined('max_size_image'))define('max_size_image',1000);
if(!defined('fb_dirobjfile'))define('fb_dirobjfile','/images/obj/');
if(!defined('fb_tmpdir'))define("fb_tmpdir",$_SERVER['DOCUMENT_ROOT'].'/images/tmp/');
if(!defined('fb_tmpdir0'))define("fb_tmpdir0",'/images/tmp/'); // WEB путь
if(!is_dir(substr(fb_tmpdir,0,-1)))mkdir(substr(fb_tmpdir,0,-1),0777,!0);
class Image
{
    static public $ext_load= ['jpeg','jpg','gif','png']; // разрешенные расширения имени файла изображений
    static public $ext_img= ['jpeg','jpg','gif','png']; // расширения имени файла изображений
    static public $dirobjfile=fb_dirobjfile;
    static $ext_info="diz"; // расширения файла информации
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
            //$ext=mb_strtolower(pathinfo($pURL_name, PATHINFO_EXTENSION));

            if (!empty($option['ext'])) self::$ext_load= $option['ext'];

            if (!($ext = self::GetExt($pURL_name))) {
                Out::error("Неверный тип файла " . $ext);
                return '';
            }
            $nname = $filename . '.' . $ext;
            if (is_file($nname)) @unlink($nname);
            if (@copy($pURL_name, $nname) === true) {
                message("Сохранил в " . ShortUrl($nname));
                return $nname;
            } else Out::error("Не смог сохранить " . $pURL_name . " в " . ShortUrl($nname));
        } elseif (isset($_FILES[$purl]['name'])) {
            self::Save1File($_FILES[$purl], $filename, $option);
        }
        return '';
    }

    /** сохраняет файл, переданный через обзор
     * @param $f = $_FILES['file']
     * @param string $filename - с полным путем, но без расширения
     * @param array|null $option ['ext'] - разрешенные расширения имени файла
     * @return string
     */
    static function Save1File($f,$filename, $option = null){
        $pURL = $f['tmp_name'];
        $pURL_type = $f['type'];
        $pURL_name = $f['name'];
        //print"<br>".$filename;print_r($f);
        //if(filesize($pURL)/1024>$_SESSION['ImageMaxSize'])echo "<br />Размер <b>".$pURL_name." ".filesize($pURL)."</b> допустимо не более <b>".$_SESSION['ImageMaxSize']."</b>!\n";
        /*UPLOAD_ERR_OK = 0; Ошибок не возникло, файл был успешно загружен на сервер.
UPLOAD_ERR_INI_SIZE = 1; Размер принятого файла превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini.
UPLOAD_ERR_FORM_SIZE = 2; Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.
UPLOAD_ERR_PARTIAL = 3; Загружаемый файл был получен только частично.
UPLOAD_ERR_NO_FILE = 4; Файл не был загружен.
UPLOAD_ERR_NO_TMP_DIR = 6; Отсутствует временная папка. Добавлено в PHP 4.3.10 и PHP 5.0.3.
UPLOAD_ERR_CANT_WRITE = 7; Не удалось записать файл на диск. Добавлено в PHP 5.1.0.
UPLOAD_ERR_EXTENSION = 8; PHP-расширение остановило загрузку файла. PHP не предоставляет способа определить какое расширение остановило загрузку файла; в этом может помочь просмотр списка загруженных расширений из phpinfo(). Добавлено в PHP 5.2.0.
*/
        if ( !empty($f['error'])){
            if(!empty($pURL_name) && $f['error']==2) {
                global $ImageMaxSize;
                $ImageMaxSize=(!empty($_REQUEST['MAX_FILE_SIZE']) ? $_REQUEST['MAX_FILE_SIZE']/1000:
                    (!empty($_REQUEST['max_file_size']) ? $_REQUEST['max_file_size']/1000:
                        (!empty($_SESSION['ImageMaxSize']) ? $_SESSION['ImageMaxSize']/1000:
                            (!empty($ImageMaxSize) ? $ImageMaxSize/1000:'???'))));
                Out::error("Ошибка загрузки файла <b>".$pURL_name."</b>. Размер больше <b>".$ImageMaxSize."</b>Kb (".ini_get('upload_max_filesize').")!");
            }else Out::error("Ошибка(<b>".$f['error']."</b>) загрузки файла <b>".$pURL_name."</b>!");
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
                } else Out::error("Не смог сохранить " . $pURL . " в " . ShortUrl($nname));
            }else Out::error("Недопустимый тип файла " . $pURL_type);
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
            $fil=self::Save1File([
                'name'     => $_FILES[$name_var]['name'][$j],
                'type'     => $_FILES[$name_var]['type'][$j],
                'tmp_name' => $_FILES[$name_var]['tmp_name'][$j],
                'error'    => $_FILES[$name_var]['error'][$j],
                'size'     => $_FILES[$name_var]['size'][$j]],
                $fil, $ext_load);
            //print "fil=";var_dump($fil);
            if($fil){
                if(self::is_img($fil) &&
                   !self::Resize($fil, $fil , imgBigSize)){error("Изображение меньше 150x150 или это не изображение!"); continue;};
                $_SESSION['fb_fil'][$i]=$fil;
                $f_ok|=true;
            }
        }
    }else{
        list($fil,$i)=self::addTmpFile();
        $fil=Image::Save1File($_FILES[$name_var], $fil, $ext_load);
        if($fil){
            if(self::is_img($fil) &&
                !self::Resize($fil, $fil , imgBigSize)){error("Изображение меньше 150x150 или это не изображение!"); return '';};
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
        $fil=fb_tmpdir.'tmp'.(User::is_login()?$_SESSION['user']['id']:session_id()).($i?'_'.$i:'').($ext?'.'.$ext:'');
        if($ext)$_SESSION['fb_fil'][$i]=$fil;
        return [$fil,$i];
    }

static function SaveLINK($link){ // передали ссылку на файл или через Ajax
    set_time_limit(600);
    list($fil,$i)=self::addTmpFile($link);
    if(@copy($link, $fil) !== true){unset($_SESSION['fb_fil'][$i]); Out::err("Ошибка загрузки!",'removeID(fb_modal);');}
    if(in_array(self::GetExt($fil), self::$ext_img) &&
        !self::Resize($fil, $fil , imgBigSize)){unset($_SESSION['fb_fil'][$i]); Out::mes("Изображение меньше 150x150 или это не изображение!");};
}

static function SaveIMG($name){
    set_time_limit(600);
    list($fil,$i)=self::addTmpFile($name);
    $input=fopen("php://input", "r");
    $f=fopen($fil, "w+"); if($f===false){AddToLog('Ошибка записи в файл:'.$fil,'Error report',!0); Out::err('Ошибка записи в файл!');}
    $realSize=stream_copy_to_stream($input, $f);
    fclose($input); fclose($f);
    if(isset($_SERVER["CONTENT_LENGTH"]) && $realSize != intval($_SERVER["CONTENT_LENGTH"]) ){unset($_SESSION['fb_fil'][$i]); Out::mes("Ошибка загрузки!");}
    if(stripos($buf=file_get_contents($fil,null,null,null,30),'base64')!==false){
        $buf=base64_decode(str_replace(' ', '+',file_get_contents($fil,null,null,strpos($buf,',')))); //data:image/jpeg;base64,
        file_put_contents($fil, $buf);
    }
    if(in_array(self::GetExt($fil), self::$ext_img) &&
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

    static function GetExt($file){
       $qp = strpos($file, "?");
        if ($qp !== false) $file=mb_substr($file, 0, $qp);
        //$ext = strtolower(substr(strrchr(basename($file), '.'), 1));
        $ext=mb_strtolower(substr(strrchr(basename($file), '.'),1));
        //$ext=mb_strtolower(pathinfo($file, PATHINFO_EXTENSION)); // basename и pathinfo не работает с русскими именами файлов

        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                return 'jpg';
            case 'gif':
                return $ext;
            case 'png':
                return $ext;
            default: // предполагаю, что это jpg
                global $ext_load;
                if(!empty($ext) && !empty($ext_load) && in_array($ext, $ext_load) ) return $ext; // определяю по расширенному набору расширений
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
     * @param int $max - максимальный размер, м.б.array(width,height)
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
        return [$width, $height];
    }

    /**
     * @param int $id
     * @param array $options
     *                      'path' путь с префиксом имени файла, но без id, у товара 'p'
     *                      'logo'=0 - без наложения изображений
     */
    static function blockLoadImage($id=0,$options= [])
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
        if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $i=>$f){
            //echo "<br>".$f.','.fb_dirfile.'tmp';
            if(strpos($f,fb_dirfile.'tmp')===false)unset($_SESSION['fb_fil'][$i]);
        }
        if($id){// переношу сохраненные картинки в сесионные переменные
            //if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $f)if(strpos($f, fb_dirfile.'tmp')!==false&&is_file($f))@unlink($f); // удаляю чужие картинки :-(
            unset($_SESSION['fb_fil']);
            for($i=0;$i<99;$i++){
                if($fil=Image::is_file($options['path'].$id.($i?'_'.$i:''))){
                    $_SESSION['fb_fil'][$i]=$_SERVER['DOCUMENT_ROOT'].$fil;
                }//else break;
            }
        }/*else{
            if(isset($_SESSION['fb_fil']))foreach($_SESSION['fb_fil'] as $i=>$f){
                //echo "<br>".$f.','.fb_dirfile.'tmp';
                if(strpos($f,fb_dirfile.'tmp')===false)unset($_SESSION['fb_fil'][$i]);
            }
        }*/
        //var_dump($_SESSION['fb_fil']);
?>

            <label for="file">Выбрать фото</label>
            <input name="MAX_FILE_SIZE" type="hidden" value="<?=max_size_image * 1000?>">
            <input type="file" name="img[]" id="file" multiple placeholder="Загрузите одну или несколько фотографий, удерживая Ctrl" accept="image/*" onchange="_frm.change(event)">
            <input type="button" formnovalidate value="Загрузить" id="submit_file" onclick="_frm.load_file()" style="visibility:hidden">
            <div class="gray small">Для выбора нескольких изображений удерживайте клавишу Ctrl</div>
<?
        echo "\n<div id=\"img_block\">" . self::AddFile() . "<br class='clear'>\n</div>"; // вывожу блок с ранее загруженными картинками
        if(isset($options['logo']) && $options['logo']=='0'){?>
            <input type="hidden" name="LogoSize" value="0">
        <?}else{
        if (User::is_admin(true)) {
            $LogoSmall=(defined('fb_logofile')&&is_file($_SERVER['DOCUMENT_ROOT'] . fb_logofile)?'selected':'disabled');
            $LogoBig=(defined('fb_logofileBig')&&is_file($_SERVER['DOCUMENT_ROOT'] . fb_logofileBig)?($LogoSmall=='selected'?'':'selected'):'disabled');
?>
<br class="clear">
        <?if($LogoSmall!='disabled' || $LogoBig!='disabled'){?>
        <p class="mb10"><b>Наложение логотипа на картинки</b></p>
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

<?
        }
        }
?>
<iframe id="upload_frame" name="upload_frame" onload="" onerror=""></iframe>
<?
if (isset($_GET['ajax'])) echo '<script type="text/javascript" defer="defer">setTimeout(_frm.set_event,1000);</script>';
}

    static function OutInfoFile(){
        //if(Get::Referer('/user/')){ // меняю аватарку текущему пользователю
        if(!empty($_REQUEST['tbl'])&&$_REQUEST['tbl']=='user'){ // меняю аватарку текущему пользователю
            if(isset($_SESSION['error'])&&$_SESSION['error']){Out::err($_SESSION['error']); $_SESSION['error']="";}
            elseif(isset($_SESSION['message'])&&$_SESSION['message']) {Out::mes($_SESSION['message']); $_SESSION['message']="";}
            else{
                $user=new User(intval($_REQUEST['ajax']));
                if(!empty($_SESSION['fb_fil'][0]) && is_file($_SESSION['fb_fil'][0])){
                    if(is_file($_SERVER['DOCUMENT_ROOT'].$user->avatar1))unlink($_SERVER['DOCUMENT_ROOT'].$user->avatar1);
                    copy($_SESSION['fb_fil'][0], $_SERVER['DOCUMENT_ROOT'].$user->avatar1);
                }else var_dump($_SESSION['fb_fil']);
                unset($_SESSION['fb_fil']);
                if($user->id()==User::id()){ // все на странице
                    Out::mes('','var el = document.querySelectorAll(".avatar");for(var i=0;i<el.length;i++)el[i].style.backgroundImage="url('.ImgSrc($user->avatar1).')";');
                }else{ // только в окне редактирования
                    Out::mes('','getObj("avatar").style.backgroundImage="url('.ImgSrc($user->avatar1).')";');
                }
            }
        }
        if(isset($_SESSION['error'])&&$_SESSION['error']){Out::err($_SESSION['error'],'updateObj("img_block","'.str_replace('"','\\"',self::AddFile()).'");'); $_SESSION['error']="";}
        elseif(isset($_SESSION['message'])&&$_SESSION['message']) {Out::mes($_SESSION['message'],'updateObj("img_block","'.str_replace('"','\\"',self::AddFile()).'");'); $_SESSION['message']="";}
        else Out::mes('','updateObj("img_block","'.str_replace('"','\\"',self::AddFile()).'");');
    }

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
                            if(isset($_REQUEST['LogoSize']) && $_REQUEST['LogoSize']=='big' && defined('fb_logofileBig') && is_file($_SERVER['DOCUMENT_ROOT'].fb_logofileBig))
                                Image::SetLogo($fil, $_SERVER['DOCUMENT_ROOT'].fb_logofileBig, $position);
                            elseif(defined('fb_logofile') && is_file($_SERVER['DOCUMENT_ROOT'].fb_logofile))
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
                        $addf.="<div class='img'>".self::imgPreview($fil0,array('whithA'=>true,'size'=>imgMediumSize))."<div class='icon-tape'>".
                                 $desc."<a onclick='if(confirm(\"Удалить?\"))ajaxLoad(\"img_block\",this.href,\"удаляю...\");return false;' href='/api.php?del_img=".$filU."' title='Удалить' class='icon del'></a>
                                        <a onclick='return _frm.edit_desc(\"".$fil0."\")' title='Описание файла' href='#' class='icon edit'></a><br class='clear'></div></div>";

                    }else
                        $addf.="<div class='left'>Ошибка в изображении</div>";
                }else{ // это документ
                    $addf.="<div class='img'>".self::imgPreview($fil0,array('whithA'=>true,'size'=>imgMediumSize))."<div class='icon-tape'>".
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
 *                  $options['class'] - класс для внешнего контейнера
 * @return string
 */
    static function imgPreview($fil,$options=null){
        //var_dump($fil,$options);
        if(is_bool($options) && $options ) $options= ['whithA'=>true];
        $fil1=ImgSrc(!empty($options['whithA'])&&is_string($options['whithA']) ? $options['whithA'] : $fil); // добавляю версию файла картинки для решения проблемы кеширования
        //$alt=(empty($options['alt']) ? self::Alt($fil) : $options['alt'] );
        $alt=self::Alt($fil,(empty($options['alt'])?'':$options['alt']));
        $size=(empty($options['size'])? imgMediumSize : $options['size']);
        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$fil)){
            $fil="/images/noimg.gif";
            list($width, $height)=Image::getSmallImage($_SERVER['DOCUMENT_ROOT'].$fil, $size);
            /*if($size==imgSmallSize) $img="<img src='".$fil."' alt='Нет изображения'>";
            else*/ $img="<img src='".$fil."' width='".$width."' height='".$height."' alt='Нет изображения'".(empty($options['class'])?'':" class='".$options['class']."'").">";
            return $img;
        }
        if(self::is_img($fil)){
            list($width, $height)=Image::getSmallImage($_SERVER['DOCUMENT_ROOT'].$fil, $size);
            if($width && $height){
                $filPreview=ImgSrc($fil); // добавляю версию файла картинки для решения проблемы кеширования
                $a="<a href='".$fil1."' onclick='return openwind(this)' title='".$alt."'".(empty($options['class'])?'':" class='".$options['class']."'").">";
            }else{
                return "<div class='left'>Ошибка в изображении</div>";
            }
        }else{
            $ext=self::GetExt($fil);
            $filPreview='/images/ext/'.$ext.'.png';
            if(!is_file($_SERVER['DOCUMENT_ROOT'].$filPreview))$filPreview='/images/ext/doc.png';
            $width=$height=$size;
            $a="<a href='".$fil1."'".(empty($options['class'])?'':" class='".$options['class']."'").">";
        }
        /*if($size==imgSmallSize)$img="<img src='".$filPreview."' data-src='".$fil."' alt='".$alt."'>";
        else*/ $img="<img src='".$filPreview."' width='".$width."' height='".$height."' data-src='".$fil."' alt='".$alt."'".((empty($options['class'])||!empty($options['whithA']))?'':" class='".$options['class']."'").">";
        //else $img="<img src='".$filPreview."' data-src='".$fil."' alt='".$alt."'>";
        return (empty($options['whithA']) ? $img : $a.$img."</a>" );

    }

    static function Alt($fil,$def=''){
        $alt=$_SERVER['DOCUMENT_ROOT'].substr($fil,0,-strlen(File::GetExt($fil))) . self::$ext_info;
        return (is_file($alt) ? addslashes(file_get_contents($alt)) : $def );
    }

    /** Открывает файл по полному пути и возвращает resource
     * @param $filename - имя файла с полным путем
     * @param string $ext
     * @return bool|resource
     */
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
                return Out::error("Неверный формат файла изображения " . $filename);
            }
        }
        return $srcImage;
    }

    /** сохраняет $img в файл
     * @param resource $img
     * @param string $filename - имя файла с полным путем
     * @param int $quality
     */
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
        if(preg_match('#^(.*/)([a-z]+)([^\.\_]+)[\._]#is', $name, $res)) {
            //die(var_export($res));
            $pref=$res[1];  // 1 => '/images/tovar/'
            $p=$res[2]; // 2 => 'p'
            $id=$res[3]; // 3 => '2'
            if(!cmp($pref,fb_dirfile))die('Путь '.$pref.' не ведет к каталогу картинок!');
            if($p=='p'){// сначала все картинки сохраню
                //var_dump($_SESSION['fb_fil']);
                self::AddFile($pref.$p.$id);
                self::ClearCash($id); // удалить маленькие картинки
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

    static function getImages($id,$p='p'){
        $rez=[];
        $c=strlen($_SERVER['DOCUMENT_ROOT']);
        for($i=0;$i<99;$i++){
            $files=glob($_SERVER['DOCUMENT_ROOT'].self::$dirobjfile.$p.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files){
                foreach($files as $file)$rez[]=substr($file,$c);
            }else{
                break;
            }
        }
        return $rez;
    }


    static function ClearCash($id){ // удалить маленькие картинки
        for($i=0;$i<99;$i++){
            $files=glob($_SERVER['DOCUMENT_ROOT'].self::$dirobjfile.'m'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files)foreach($files as $fil)@unlink($fil);
            $files=glob($_SERVER['DOCUMENT_ROOT'].self::$dirobjfile.'s'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files)foreach($files as $fil)@unlink($fil);
        }
    }

    static function DeleteAll($id){ // удалить все картинки
        for($i=0;$i<99;$i++){
            $files=glob($_SERVER['DOCUMENT_ROOT'].self::$dirobjfile.'{m,s,p}'.$id.($i?('_'.$i):'').".".'{'.implode(',',self::$ext_img).'}', GLOB_BRACE);
            if($files)foreach($files as $fil)@unlink($fil);
        }
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

}
