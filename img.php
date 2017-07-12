<? // создает превью картинки, если ее нет для news и help
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
//var_export($_REQUEST);
//die;
$fil0=$_REQUEST['f'];
$f=basename($fil0);
//echo "<br>".path_load.basename($f).'~'.$_SERVER['DOCUMENT_ROOT'].path_tovar_image.$f."<br>";
if(defined('path_load') && @copy(path_load.path_tovar_image.basename($f),$_SERVER['DOCUMENT_ROOT'].path_tovar_image.$f)){  // указано откуда брать картинки
        header('Content-Type: image/'.str_replace('jpg','jpeg',Image::GetExt($f)));
        readfile($_SERVER['DOCUMENT_ROOT'].path_tovar_image.$f);
        exit;
}else{
    switch(substr($f,0,1)){
        case 's':
            $fil1=$_SERVER['DOCUMENT_ROOT'].'/'.str_replace('s','p',$fil0);
            $size=imgSmallSize;
            $none='noimgSmallSize.gif';
            break;
        case 'm':
            $fil1 = $_SERVER['DOCUMENT_ROOT'].'/'.str_replace('m', 'p', $fil0);
            $size = imgMediumSize;
            $none = 'noimgMediumSize.gif';
            break;
        case 'p':
            $fil1 = '';
            $size = imgBigSize;
            $none = 'noimg.gif';
            break;
        default:

            $args = explode('/', trim($fil0,'/'));

            $f = explode('_', get_key($args, 1 ,''));
            $w = $f[0];
            $h = $f[1];
            $catalog = $f[2];

            $fil0 = 'images/' . $catalog . '/p'.$args[2];
            $fil1 = DOCUMENT_ROOT . '/img/' . $w . '_' . $h . '_' . $catalog . '/'.$args[2];
            $size = $w.",".$h;
            $none = 'noimg.gif';
            if (is_file($fil0)) {
                break;
            }

            header('Content-Type: plain/text');
            die('Неверный код в имени файла');
    }

    if($fil1 && strpos(path_tovar_image,'://')===false){ // картинки хранятся на даном сервере и это маленькая и есть большая
        if(is_file($fil0) && Image::Resize($fil0, $fil1 , $size)){
            header('Content-Type: image/jpeg');
            readfile($fil1);
            exit;
        }
    }
    $fil0=$_SERVER['DOCUMENT_ROOT'].'/'.$fil0;
    $none='/images/'.$none;
    if($fil1 && !is_file($_SERVER['DOCUMENT_ROOT'].$none)){ // нет маленькой
        $fil0=$_SERVER['DOCUMENT_ROOT'].'/images/none.gif';
        Image::Resize($fil0, $_SERVER['DOCUMENT_ROOT'].$none , $size);
    }
    header('location: http://'.$_SERVER['HTTP_HOST'].$none);
}




