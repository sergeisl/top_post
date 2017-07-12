<? // отправка данных об остатках в интернет-магазин
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 3600');

//$fil=(is_dir('E:')?'E:\\':is_dir('D:')?'D:\\':'').db_prefix.'.sql';
//$fil=$_SERVER['DOCUMENT_ROOT'].'/log/'.db_prefix.'.sql';
if(!empty($_GET['to']) && $_GET['to']=='site'){
/*    foreach ($z_tbl as $shop => $val) if (db_prefix == $val) break; // определяю номер магазина
    if (empty($shop)) die("Ошибка в настройке магазина".var_export($z_tbl,!0).'~'.db_prefix);*/

    echo "<br><b>Готовлю и выгружаю остатки по косметики на " . url_shop;
    $post = [];
    $res = DB::sql("SELECT * from `" . db_prefix . "tovar` WHERE (type=" . tTYPE_RASX . " or type=" . tTYPE_TOVAR . " ) and ost!=0 and kod_prodact<>''");
    while ($row = DB::fetch_assoc($res)) $post[$row['kod_prodact']] = array('kol' => $row['ost'], 'price' => $row['price']);

    $url = url_shop . '/shop_import.php';
    echo "<br>Загружаю на " . $url;
    list($headers, $body, $info) = ReadUrl::ReadWithHeader($url, array('shop' => db_prefix, 'ost' => json_encode($post), 'from'=>strtotime('-10 days')), array('cache' => 0, 'timeout' => 120,'convert'=>charset));
    if ($body){ // todo В ответ получаю список новых товаров, доступных для заказа
        $filename = fb_tmpdir.'price.zip';
        file_put_contents($filename,$body);
        if(($fil=File::Open($filename))){ // открываю csv внутри архива
            echo "<div style='background:lightgreen'>".nl2br(File::$arxiv->getArchiveComment())."</div>";;
            while(($tovar = File::fget_csv($fil, 0, ";")) !== FALSE){
                //echo "<br>";
                //print_r($tovar); // // 0Код;1коллекция;2Наименование;3Описание;4Кол-во/Объем;5Цена;6Цена прихода;7Категории;8Вид
                if (count($tovar) < 6){
                    //echo " - пропускаю";
                    continue;
                }
                $tov = ['kod_prodact' => $tovar[0], 'collection' => $tovar[1], 'name' => $tovar[2], 'description' => $tovar[3], 'kol' => $tovar[4], 'price' => $tovar[5], 'price0' => $tovar[6], 'category' => $tovar[7], 'type' => $tovar[8]];
                if (($tovar = DB::Select('tovar', 'kod_prodact="' . addslashes($tovar[0]) . '"'))){
                    Tovar::UpdateCategory($tovar, $tov['category']);
                    Tovar::UpdateComment($tovar, $tov['description']);
                } else {
                    Tovar::SaveTovar($tovar);
                }
            }

            if($fil)fclose($fil);
            if(File::$arxiv)File::$arxiv->close();
            @unlink($filename);
        }else echo "<div class='error'>Не открыл архив: ".$filename."</div>".$body;
        //echo $body;
    } else {
        echo $info['curl_error'];
    }

    echo "<br>Получаю с сервера дату и id последней загруженной операции";
    list($headers, $body, $info) = ReadUrl::ReadWithHeader(url_domain . '/api.php', array('shop' => db_prefix, 'get_last_id' => ''), array('cache' => 0, 'timeout' => 60,'convert'=>charset));
    if (empty($body)) die('Нет ответа от сервера ' . url_domain . ' !' . var_dump($info));
    if (!empty($info['http_code']) && $info['http_code'] != 200){
        echo "<div style='background:lightgreen'>";
        var_dump($info, $body);
        echo "</div>";
        exit;
    }
    $row = js_decode($body);

    echo "<br>Выгружаю операции на " . url_domain;
    $dump = new dump(array('path' => fb_tmpdir, 'comp_level' => 1, 'prefix' => db_prefix));
    $dump->tables = array('sale', 'sale2', 'prixod', 'user', 'tovar', 'category', 'category_link', 'collection', 'brand', 'counters', 'incasso', 'kart', 'log'); // zakaz, sms
    echo "<br><b>Идет формирование файла...</b> ";
    if (!empty($row['id'])){
        $dump->no_drop = ['sale', 'sale2', 'counters', 'incasso', 'log']; // не удалять таблицы
        //$dump->where['tovar']='date_upd>"'.substr($row['time'],0,10).'"';
        $dump->where['sale'] = 'id>"' . $row['id'] . '"';
        $dump->where['sale2'] = 'sale>"' . $row['id'] . '"';
        $dump->where['counters'] = 'time>"' . $row['time'] . '"';
        $dump->where['incasso'] = 'time>"' . $row['time'] . '"';
        $dump->where['log'] = 'time>"' . $row['time'] . '"';
        echo $dump->where['sale'] . ' & ' . $dump->where['log'];
    }
    flush();
    // todo если что-то удалили:
    // добавить дату изменения в товар и клиент
    // если изменяется что-то в категориях, обновлять товары на которые это повлияло
    // при выгрузке товаров выгружать все связанные категории
    $dump->backup();
    $fileArxive = $dump->path . $dump->filename;
    $dump->close();
    echo " " . basename($fileArxive) . ', размер ' . round(filesize($fileArxive) / 1024, 1) . 'кб';
    flush();

    echo "<br>Отправляю архив на сервер... ";
    flush();
    list($headers, $body, $info) = ReadUrl::ReadWithHeader(url_domain . '/api.php?shop=' . db_prefix, array('fil' => '@' . $fileArxive), array('cache' => 0, 'timeout' => 500,'convert'=>charset));
    if (empty($body)) die('Нет ответа от сервера ' . url_domain . ' !' . var_dump($info));
    echo '<br>' . $info['url'] . '<br>Скорость загрузки: ' . round($info['speed_upload'] / 1024, 1) . ' Кбайт/сек<br>Загружено: ' . round($info['upload_content_length'] / 1024, 1) . ' Кбайт' . "<div style='background:lightgreen'>" . $body . "</div>Завершено!"; // var_export($info);
    unlink($fileArxive);

}elseif(!empty($_GET['to']) && $_GET['to']=='soft'){
    // читаю с сервера даты и размеры всех обновленных файлов
    list($headers, $body, $info) = ReadUrl::ReadWithHeader(url_domain . '/api.php?soft&json', '', ['cache' => 0, 'timeout' => 50,'convert'=>charset]);
    if (empty($body)) die('Нет ответа от сервера ' . url_domain . ' !' . var_dump($info));
    if (!empty($info['http_code']) && $info['http_code'] != 200 || empty($body)){
        echo "<div style='background:lightgreen'>";
        var_dump($info, $body);
        echo "</div>";
        exit;
    }
    $files = js_decode($body);
    $list=[];
    foreach($files as $fil){
        if(!in_array(substr($fil[0],-3),['php','css','.js'])){SendAdminMail('hack soft',var_export($files,!0)); die('Ошибка файла в запросе!');}
        //echo "<br>".$fil[0];
        if(!is_file($_SERVER['DOCUMENT_ROOT'].$fil[0]) ||
            ( /*filesize($_SERVER['DOCUMENT_ROOT'].$fil[0])!=$fil[1] || */filemtime($_SERVER['DOCUMENT_ROOT'].$fil[0])<$fil[2] ) ){
            $list[]=$fil[0];
        }
    }
    if(!$list)die('ПО актуально!');
    // отправляю список файлов, которые нужно обновить
    list($headers, $body, $info) = ReadUrl::ReadWithHeader(url_domain . '/api.php?json', ['soft' => js_encode($list)], ['cache' => 0, 'timeout' => 200,'convert'=>charset]);
    if(!empty($body)&&!empty($info['http_code'])&&$info['http_code']==200 && preg_match('/Content-Disposition: attachment; filename=([^.]*)/i',$headers)){
        $f_install=false;
        $f = fb_tmpdir.'soft_'.rand(1000,9999).'.zip'; if(is_file($f))unlink($f);
        file_put_contents($f,$body);
        $zip = new ZipArchive;
        if ($zip->open($f) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
               // echo "<br>:" . $entry;
                if(in_array(substr($entry, -3),['php','css','.js'])) {
                    $fil=$_SERVER['DOCUMENT_ROOT'].'/'.$entry;
                    $bak=$fil.'~';
                    if(is_file($bak)&&filemtime($bak)>strtotime('-1 day')){
                        unlink($bak);
                        rename($fil,$bak);
                    }elseif(!is_file($bak)){
                        rename($fil,$bak);
                    }elseif(is_file($fil)){
                        unlink($fil);
                    }
                    echo "<br>Обновляю файл " . $entry;
                    if($zip->extractTo($_SERVER['DOCUMENT_ROOT'], $entry )){
                        $ar=$zip->statIndex($i);
                        touch($fil,$ar['mtime']); // установить дату/время файла по исходному
                        if(substr($fil,-12)=='/install.php')$f_install=$fil;
                    }else{
                        error("Не смог распаковать ".$entry);
                    }
                }
            }
            if($f_install)include_once $f_install;
        }else error("Архив поврежден!");
        if(empty($_SESSION['error']))@unlink($f); // удаляю исходный архив
        Out::ErrorAndExit(0,1);
    }else die("НЕ обновил! \n<br><b>".$body."</b><br>".var_export($headers,!0)."<br><br>".var_export($info,!0));

}else{
    $fil=(isset($_GET['to'])?substr(urldecode($_GET['to']),0,1).':\\':'').db_prefix.date("Y_m_d").'.sql';
    $c='Z:\usr\local\mysql-5.5\bin\mysqldump.exe '.DBName.' -u '.UserName.' --skip-add-locks --add-drop-table -r'.$fil.' -f';
    $ret='';
    echo "<br>\n".$c."<br>\n".var_export(system(escapeshellcmd($c),$ret),!0).'~'.$ret."<br>\n".intval(@filesize($fil)/1000)."Kb";
}
