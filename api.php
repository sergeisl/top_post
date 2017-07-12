<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php"; /** @var array $area - регион */
global $_user;/** @var USER $_user - текущий пользователь */
User::id();// чтобы подключить класс User



if(isset($_GET['img']) ){ // передали файл POST-ом через Ajax
    //if(!$user)Out::err('Писать могут только зарегистрированные пользователи!'.fb_login);
    Image::SaveIMG($_GET['img']);
    Image::OutInfoFile();

}elseif(isset($_GET['link']) ){ // передали ссылку на файл или через Ajax
    //if(!$user)Out::err('Писать могут только зарегистрированные пользователи!'.fb_login);
    Image::SaveLINK($_GET['link']);
    Image::OutInfoFile();

}elseif(isset($_FILES['img']['name']) ){ // запрос через form, ответ приходит во фрейм
    //if(!$user)Out::err('Писать могут только зарегистрированные пользователи!'.fb_login);
    if(Image::SaveFILES('img')){
        echo 'Загружено!';
    }else{
        echo "Ошибка загрузки файла на сервер!";
    }
}elseif(isset($_GET['del_img']) ){ // Удалить файлы загруженных картинок с сервера, если файл не передан, то только возвращает html-список файлов
    //todo if(!User::is_admin())die("Нет прав доступа!");
    Image::DelFile($_GET['del_img']);
    // Tovar::DelImg(intval($_GET['del_img'])); // удалить картинку товара
    Image::OutInfoFile();

}elseif(isset($_GET['desc_img'])&& isset($_POST['desc'])){ // Описание файла
    $fil=urldecode($_GET['desc_img']);
    if($fil!='' && is_file($_SERVER['DOCUMENT_ROOT'] . $fil)){
        if(!preg_match('#'.fb_dirfile.'[^/]+/p([^\._]*)[\._]#',$fil,$ar))Out::err('Неверное имя файла!');
        //if(!OBJ::canEdit($ar[1]))Out::err("Вы не можете изменять это объявление!".var_export($ar,!0));
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . substr($_GET['desc_img'],0,-3) . Image::$ext_info , Convert::utf2win($_POST['desc']));
        Out::mes("Сохранил описание","_fade.init()");
    }else Out::err('Неверное имя файла!');



}elseif(!User::is_admin()){ //////// все остальное доступно только админу //////////
    echo "Неверный API запрос или недостаточно прав!"; exit;

}elseif(isset($_GET['del'])&&isset($_GET['tbl'])){
    $tbl=$_GET['tbl'];
    $id=intval($_GET['del']); if($id<1)Out::err('Неверный id !');
    if($tbl=='user') {
        User::delete($id);
    }elseif($tbl=='schedule'){
        // todo если нет записавшихся - удалить
        DB::Delete($tbl, intval($id));
        // todo если есть старые, - поменять дату на текущую
        // todo если есть записавшиеся на будущее, - отправить им уведомление
    }else{
        DB::Delete($tbl, intval($id));
        if($tbl=='rekl'){ $fil=Image::is_file('/pic/recl_'.$id,!0); if($fil)unlink($_SERVER['DOCUMENT_ROOT'].$fil); }
    }
    if(mysqli_affected_rows(DB::$link)>0){
        DB::sql("alter table `".db_prefix.$tbl."` auto_increment=1;");
        Out::mes("Удалил","removeID('id".$id."');_fade.init()");
    }else Out::err("Не удалил!");


}elseif(isset($_GET['ajax']) && isset($_GET['tbl']) ){ // передается не более одного поля за один раз для изменения
        $id=intval($_GET['ajax']); if(!$id)Out::err('Ошибка в id!');
        $tbl=urldecode($_GET['tbl']);
        $f_reload=0;
        if(!in_array($tbl, ['smsgate_devices'])) Out::err('Не доступно!');
        if(!($row=DB::Select($tbl,'id='.$id.(User::is_admin()?'':' and user="'.User::id().'"'))))Out::err("Нет такого id=".$id."!");
        $add='';
        $err='';
        foreach($_REQUEST as $key => $value)if(array_key_exists($key,$row)){
            if(substr($key,0,4)=='time')$value=date("Y-m-d H:i:s", (preg_match('/[^0-9]/',$value) ? strtotime($value) : $value));
            $add.=','.$key.'="'.addslashes($value).'"';
        }else{
            $err.=', '.$key;
        }
        if($add){
            DB::sql('UPDATE '.db_prefix.$tbl.' SET '.substr($add,1).' WHERE id='.$id.' LIMIT 1');
            //Out::win(DB::$query.'~'.mysql_error());
            if(DB::affected_rows()){
                if($f_reload) Out::mes('','reload();'); else Out::mes('','Ok(obj)'); }
            else{
                if(DB::Select($tbl, 'id="'.$id.'"'))
                    Out::win('Изменений не обнаружено!',2);
                else
                    Out::err("Нет такого id={$id}!");}
        }else Out::err("Ошибка в данных! Нет в базе: ".substr($err,2).', есть '.var_export($row,!0));

}elseif(isset($_GET['del_file'])){ // удаляю
        if(!headers_sent())header("Content-type: text/html; charset=".charset);
        $f=basename($_GET['del_file']);
        if(substr($f,-4)=='.log' && User::is_admin()){
            $f=$_SERVER['DOCUMENT_ROOT'].'/log/error/'.$f;
            if(file_exists($f)){ echo '<br>удалил: '.$f; @unlink($f);} else { echo '<br>нет файла: '.$f; }
        }else{
            echo '<br>такой файл удалять нельзя: '.$f;
        }

//}elseif(empty($_COOKIE['name']) || $_COOKIE['name']!='kdg'){ //////// все остальное доступно только админу //////////
//    die("Неверный API запрос или недостаточно прав!");

}elseif(isset($_GET['del_log']) ){ // удаляю протокол ошибок
    $f=basename($_GET['del_log']);
    if(substr($f,-4)=='.log'){
        $f=$_SERVER['DOCUMENT_ROOT'].'/log/error/'.$f;
        if(file_exists($f)){ echo '<br>удалил: '.$f; @unlink($f); }
    }

}elseif(!empty($_REQUEST['shop'])){
    $shop=preg_replace('/^[^a-z\_]+$/','',$_REQUEST['shop']); // префикс БД
    if(empty($shop)){
        Out::BadRequest();
    }elseif(isset($_REQUEST['get_last_id'])){ // возвращаю дату(id) последней загруженной операции
        if(!DB::is_table('sale',$shop)){die(js_encode(['id'=>'','time'=>'']));}
        $row=DB::Select('sale','1 ORDER BY id DESC',$shop);
        die(js_encode($row));

    }elseif(isset($_FILES['fil'])) { // архив с операциями магазина
        $nname = 'shop_load' . $shop . '_' . $_FILES['fil']['name'];
        @mkdir(fb_tmpdir, 0777, true);
        move_uploaded_file($_FILES['fil']['tmp_name'], fb_tmpdir . $nname);
        $dump = new dump(['path' => fb_tmpdir, 'filename' => $nname]);
        if(($aff_rows = $dump->restore())) echo "<br>Загруженно в " . DBName . " <b>" . $aff_rows . '</b> записей!'; else echo "<br>Не загружено!";
    }else {
        Out::BadRequest();
    }


}else {echo "Неверный запрос: POST="; var_dump($_POST);echo "<br>GET=";var_dump($_GET);echo "<br>FILES=";var_dump($_FILES);}


function OutInfoFile(){
    if(isset($_SESSION['error'])&&$_SESSION['error']){Out::err($_SESSION['error'],'updateObj("img_block","'.str_replace('"','\\"',Image::AddFile()).'");'); $_SESSION['error']="";}
    elseif(isset($_SESSION['message'])&&$_SESSION['message']) {Out::err($_SESSION['message'],'updateObj("img_block","'.str_replace('"','\\"',Image::AddFile()).'");'); $_SESSION['message']="";}
    else Out::err('','updateObj("img_block","'.str_replace('"','\\"',Image::AddFile()).'");');
}

