<?
$title = "Импорт товаров.";
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/config.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/head.php";
echo "\n<h1>" . $title . "</h1>\n";

if (isset($_FILES['f'])) {
    $path1 = $_SERVER['DOCUMENT_ROOT'] . '/log/tmp'; // путь куда класть файл
    $f = '';
    print "<br>f_tmp=" . $_FILES['f']['tmp_name'] . "<br>\n";
    $mURL = $_FILES['f']['tmp_name'];
    $mURL_type = $_FILES['f']['type'];
    $mURL_name = $_FILES['f']['name'];
    if (!empty($mURL_name) && $_FILES['f']['error']) die("Ошибка(<b>" . $_FILES['f']['error'] . "</b>) загрузки файла <b>" . $mURL_name . "</b> на сервер!");
    elseif (isset($mURL_type) && ($mURL_type != '')) {
        if (in_array($mURL_type, ['application/vnd.ms-csv','text/plain','text/csv','application/vnd.ms-excel','application/gzip','application/zip','application/octet-stream'])) {
                $nname = $path1 . '/tmp_' . url2file(strtolower(basename($mURL_name)));
                if (move_uploaded_file($mURL, $nname)) {
                    $f = basename($nname);
                    if (in_array($mURL_type, ['application/gzip','application/zip']) || substr($f, -4, 4) == '.zip') {
                        $zip = new ZipArchive;
                        if ($zip->open($fil) === true) {
                            if ($zip->extractTo($path1)) { // это папка куда распаковать
                                $zip->close();
                                echo "<p>Архив " . $f . " распакован</p>";
                                $dh = opendir($path1) or die ("Не удалось открыть каталог " . $path1);
                                while(($f = readdir($dh))){
                                    if (substr($f, -4) == '.csv') {
                                        $fil = $path1 . '/' . $f;
                                    } else @unlink($fil);
                                }
                                closedir($dh);
                                //$f=basename($nname);
                            } else {
                                echo "Не смог распаковать " . $fil . " в " . $nname;
                            }
                        } else {
                            echo "<p>Ошибка при извлечении файлов из архива</p>";
                        }
                    } else $fil = $path1 . '/' . $f;
                } else die("Не смог сохранить " . $mURL . " в " . $nname);
        } elseif ($mURL != '') {
            print "Неверный тип " . $mURL_type;
        }
    } else die("Неверный тип файла!");
}
//FILES

if (isset($fil) && file_exists($fil)) {
    $ar_coments = file($_SERVER['DOCUMENT_ROOT'].'/description.csv');
    set_time_limit(0);
    echo "<h4>Загружаю " . $fil . "</h4>";
    $test = isset($_GET['test']);
//    0Код;1коллекция;2Наименование;3Описание;4Кол-во/Объем;5Цена;6Цена прихода<br>
//500;Sunmaxx  Power S;  Deep Tan Level 1;Для тела - базовый загар; 30 ml;210;140;52,5;0;0;0;0;;0;;;;;0;
//501;;;; 150 ml;510;400;150;0;0;0;0;;0;;;;;0; - объединенные ячейки
    if($_REQUEST['format']==3){ // 0Линия;1Наименование товара;2Артикул;3Объем;4Цена опт;5Цена с учетом скидки(будет добавляться 15%)<br>
        define('iCollection',0);
        define('iName',1);
        define('iKod',2);
        define('iVolume',3);
        define('iPrice0',5);
        define('iPrice',-1);
        define('iDescription',-1);
        define('iCategory',-1);
        define('iType',-1);
        define('iBrand',-1);
        define('NacPrice',1.15);
        define('SetBrand','Tannymaxx'); // Hawaiiana
    }elseif($_REQUEST['format']==2){ // 0Линия;1Артикул;2Наименование товара;3Объем;4Цена-;5Цена с учетом скидки(будет добавляться 15%)<br>
        define('iKod',1);
        define('iCollection',0);
        define('iName',2);
        define('iVolume',3);
        define('iPrice0',5);
        define('iPrice',-1);
        define('iDescription',-1);
        define('iCategory',-1);
        define('iType',-1);
        define('iBrand',-1);
        define('NacPrice',1.15);
    }else{ // 0Код;1коллекция;2Наименование;3Описание;4Кол-во/Объем;5Цена розничная;6Цена прихода;7Категории;8Вид;9Бренд<br>
        define('iKod',0);
        define('iCollection',1);
        define('iName',2);
        define('iDescription',3);
        define('iVolume',4);
        define('iPrice',5);
        define('iPrice0',6);
        define('iCategory',7);
        define('iType',8);
        define('iBrand',9);
        define('NacPrice',1);
    }

    $f_replace=true;
    $tovar = [];
    $brand = '';
    $f = fopen($fil, "r") or die("Ошибка!");
    $cnt1 = $cnt2 = 0;
    while (($data = fgetcsv($f, 1000, ";")) !== FALSE) {
        echo "<br>\n";
        if (count($data) < 5){echo "<br>\nмало данных в строке"; continue;}
        echo "<br>\n".$data[iKod]." : ".$data[iName]." : ".(iPrice0>=0?$data[iPrice0]:"")."/".(iPrice>0?$data[iPrice]:"");
        if(defined('SetBrand')){
            $brand = ($data[iKod]>5000?'Hawaiiana':SetBrand);
        }elseif (!$data[iKod] && empty($data[iCollection]) && empty($data[iBrand]) && empty($data[iPrice0]) && strlen($data[iName]) > 4) {
            switch ($data[iName]){
            case 'SUNMAXX': $brand = 'Sunmaxx'; break;
            case 'LUXURY':  $brand = 'Luxury';  break;
            case "THAT'SO": $brand = 'That\'so'; break;
            case "SUPRE":   $brand = 'Supre'; break;
            case "HEMPZ":   $brand = 'Hempz'; break;
            default:
                echo "<br>\n<b>" . $data[iName] . " - Что это?</b>";
                $tovar = [];
                $brand = '';
            }
        }
        unset($tovar['ost'],$tovar['price'],$tovar['maxdiscount']/*,$tovar['kod']*/,$tovar['ean'],$tovar['kol'],$tovar['srok'],$tovar['proce0'],$tovar['info']);
        $data[iName] = str_replace('Cr?me', 'Creme', str_replace('!!!', '!', $data[iName])); // иначе объединенная ячейка
        $data[iKod]=str_replace('--','-',trim($data[iKod]));
        if(iCategory>=0 && !empty($data[iCategory])){
            if(($tovar['category']=explode(',',$data[iCategory]))){
                $tovar['category']=array_fill_keys($tovar['category'], 1);
            }

        }
        if(iType>=0 && !empty($data[iType]) && $data[iType]>=0 && $data[iType]<4)$tovar['type']=intval($data[iType]);
        else $tovar['type'] = 0; // косметика

        if (empty($data[iDescription]) && !empty($tovar['description']) && ($tovar['name'] == $data[iName] || (empty($data[iName])&&substr($tovar['kod_prodact'],0,4)==substr($data[iKod],0,4)))) { /*сохраняю старое описание т.к.объединенная ячейка с описанием*/
            echo " /сохраняю описание от " . $tovar['name'] . " для " . $data[iName] . "/";
        }elseif(!empty($data[iDescription])){
            $tovar['description'] = $data[iDescription];
        }else $tovar['description']='';

        if($data[iName]) $tovar['name'] = $data[iName]; // иначе объединенная ячейка

        $tovar['kod_prodact'] = trim($data[iKod]);
        if (strpos($tovar['kod_prodact'], '->') !== false) {
            list($tovar['old'], $tovar['kod_prodact']) = explode('->', $tovar['kod_prodact']);
        } else {
            $tovar['old'] = '';
        }

        if(!empty($data[iCollection])) $tovar['collection_name'] = trim($data[iCollection]);
        if(defined('SetBrand')){
            $brand = SetBrand;
        }elseif(!empty($data[iBrand])) $brand = trim($data[iBrand]);
        elseif(substr($tovar['kod_prodact'], 0, 4) == '5600') $brand = 'Sunmaxx';


        $tovar['kol'] = str_replace(' ', '', str_replace(',', '.', $data[iVolume]));
        if (preg_match('/([0-9\.]+)([^[0-9\.]]+)$/', $tovar['kol'], $ar)) {
            $tovar['kol'] = $ar[1];
            $tovar['ed'] = $ar[2];
        } else $tovar['ed'] = '';
        $tovar['kol'] = floatval($tovar['kol']);
        $tovar['replace'] = substr($data[iPrice0], 0, 1) == '+' || $f_replace;
        $tovar['delete'] = substr($data[iPrice0], 0, 1) == '-';
        $tovar['price'] =(empty($data[iPrice]) ? 0 : intval(str_replace(' ', '', str_replace(',', '.', $data[iPrice]))));
        $tovar['price0'] = round(floatval(str_replace(' ', '', str_replace(',', '.', $data[iPrice0]))) * NacPrice, 1); if($tovar['price0']>50)$tovar['price0']=ceil($tovar['price0']);
        if (!$tovar['delete'] && $tovar['kod_prodact'] != 'SQL' && empty($tovar['price']) && empty($tovar['price0'])) continue;
        //if(isset($data[19])&&intval($data[19])>0)$tovar['price0']=floatval(str_replace(' ','',str_replace(',','.',$data[19])));
        //else $tovar['price0']=round(floatval(str_replace(' ','',str_replace(',','.',$data[7])))*1.1,1);

        /*$add='';
    if($tovar['kod'])$add.=" kod='".addslashes($tovar['kod'])."'";
    if($tovar['ean'])$add.=($add?' or':'')." kod='".addslashes($tovar['ean'])."' or ean='".addslashes($tovar['ean'])."'";
    if(!$add)continue;
    $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE (".$add.") and type=0 LIMIT 1");
    if($data = DB::fetch_assoc($query)){*/

        if (!$tovar['kod_prodact']) {
            $tovar = [];
            $brand = '';
            continue;
        }
        if ($tovar['kod_prodact'] == 'SQL') {
            $q = "UPDATE `" . db_prefix . "tovar` SET " . $tovar['description'] . " WHERE " . $tovar['name'];
            echo "<br><b>SQL</b>: " . $q;
            if ($test) {
                DB::sql("SELECT count(*) as cnt FROM `" . db_prefix . "tovar` WHERE " . $tovar['name']);
                $cnt1++;
                if ($tovar = DB::fetch_assoc($query)) {
                    $cnt1 += $tovar['cnt'];
                    $q .= ' - заменено ' . $tovar['cnt'];
                } else {
                    $q .= ' -  не найдено';
                }
            } else {
                DB::sql($q);
                $cnt1 += DB::affected_rows();
            }
        }

        if ($tovar['kod_prodact'] && $tovar['old'] && $data = Tovar::LocateKod($tovar['old'], $tovar['name'] )) {
            $query = DB::sql("SELECT * FROM `" . db_prefix . "tovar` WHERE kod='" . $tovar['kod_prodact'] . "'");
            if ($tov = DB::fetch_assoc($query)) {
                echo ' объединяю коды ' . $tovar['old'] . ' + ' . $tovar['kod_prodact'];
                if(!$test)Tovar::Union($data['id'], $tov['id']);
            } else {
                echo ' заменяю код ' . $tovar['old'] . ' на ' . $tovar['kod_prodact'];
                if(!$test)DB::sql("UPDATE `" . db_prefix . "tovar` SET kod_prodact='" . $tovar['kod_prodact'] . "' WHERE kod_prodact='" . $tovar['old'] . "'");
            }
        }
        if($tovar['kod'] && $data = Tovar::LocateKod($tovar['kod_prodact'], $tovar['name'])){

            if($tovar['delete']) {
                echo "<br><b>Удаляю</b>: " . $data['kod_prodact'] . " " . $data['show_name'];
                if ($test) {
                    $cnt1++;
                    continue;
                }
                if (!Tovar::Del($data['id'])) {
                    echo "<b>- невозможно, очистил код!</b>";
                    if(!$test)DB::sql("UPDATE `" . db_prefix . "tovar` SET kod_prodact='-', ean='' WHERE id='" . $tovar['id'] . "'");
                }
                continue;
            }
            echo "<br>\nМеняю";

            if ($tovar['kod_prodact'] != $data['kod_prodact']) echo "<br><b>разный код</b>: в базе " . $data['kod_prodact'] . ", загружается " . $tovar['kod_prodact'];

            // !!! если не хочу менять цены, то для товара у которого есть цена не обновляю!!!
            if (!empty($tovar['price']) && $tovar['price']>0){
                if($tovar['price'] != $data['price']) {
                    echo "<br><span " . ($data['price'] < $tovar['price0'] * 2 && $data['price'] < $tovar['price0'] + 1000 ? ' class="red" ' : '') . (abs(($data['price'] - $tovar['price']) / $tovar['price']) < 0.2 ? '' : "style='font-weight:bold;'") . ">Цена была " . $data['price'] . ($tovar['replace'] ? '' : ' НЕ') . ' стала ' . $tovar['price'] . "</span>";
                    if (!$tovar['replace']) $tovar['price'] = $data['price'];
                }
            }else{
                $tovar['price']=$data['price'];
            }
            if($tovar['price']<1){
                $tovar['price']=ceil($tovar['price0']*2);
            }elseif($tovar['price']<$tovar['price0']*($tovar['price0']>2000?1.6:2)){
                if($f_replace){
                    echo "<span class='red'>Надо бы увеличить цену продажи ".$tovar['price'].'-&gt;'.ceil($tovar['price0']*2).'!</span>';
                }else{
                    echo "<span class='red'>Увеличиваю цену продажи ".$tovar['price'].'-&gt;'.ceil($tovar['price0']*2).'!</span>';
                    $tovar['price']=ceil($tovar['price0']*2);
                }
            }

            if ($data['kol'] > 0 && $tovar['kol'] > 0 && $tovar['kol'] != $data['kol'])
                echo "<br>Кол-во было " . $data['kol'] . ' стало <b>' . $tovar['kol'] . ' ' . $tovar['ed'] . "</b>";
            if (!cmp($tovar['name'], $data['name']) && !cmp($data['name'], $tovar['name'])) echo "<br><span style='color:#97370c'>Название " . $data['name'] . "-&gt;" . $tovar['name'] . "</span>";
            echo "<br>\nОбновляю id" . $data['id'];
            //if(strlen($tovar['ed'])<2)$tovar['ed']=$data['ed'];
            $tovar['id'] = $data['id'];
            if(strlen($brand)>3){// передан бренд - использую его!
                $tovar['brand'] = Tovar::GetBrand($brand);
                $brand_name = DB::GetName('brand', $data['brand']);
                if($data['brand'] && $brand_name != $brand) echo "<br>\n<span style='color:red'>Возможно неверный бренд: " . var_export($brand_name,!0) . " ~ " . var_export($brand,!0) . "</span>";
                $brand_name=$brand;
            }else{
                $brand_name = DB::GetName('brand', $data['brand']);
            }
            $tovar['brand'] = Tovar::GetBrand($brand); if($tovar['brand']){if($tovar['brand']['id']==0)echo "<br>\nBrand:".$tovar['brand']['name']; }

            $data['collection_name']=DB::GetName('collection', $data['collection']);
            if (intval($data['collection']) > 0 && strlen($data['collection_name']) > 3) {
                echo "/collection из товара БД ".$data['collection']."/";
                $tovar['collection'] = $data['collection'];
                $tovar['collection_name']=$data['collection_name'];
                if(($row1=DB::Select('collection',$tovar['collection']))){
                    //echo "row1=";var_dump($row1);
                    $tovar['brand']=DB::Select('brand',$row1['brand']);
                }
            } elseif (!empty($tovar['collection_name'])) {
                $tovar['collection'] = Tovar::GetCollection($tovar['collection_name'], $tovar['brand']);
                if(!empty($tovar['brand']['id']))$data['brand']=$tovar['brand']['id'];
                //elseif(!empty($tovar['brand'])&&$tovar['brand']>0)$data['brand']=$tovar['brand'];
                echo "<br>!!1 collection: ".$tovar['collection'].', brand: '.var_export($tovar['brand'],!0);
                //echo "<br>description :".$tovar['description'];
            } else {
                $tovar['collection'] = 0;
            }
            if (empty($tovar['description'])) $tovar['description'] = $data['description'];
            if (!empty($tovar['collection_name']) && (trim($tovar['collection_name']) == trim($tovar['description']))) $tovar['description'] = '';

            // получаю описание из файла description.csv
            if (empty($tovar['description'])||strlen($tovar['description'])<30){ foreach ($ar_coments as $str) if (substr($str, 0, (substr($tovar['kod_prodact'],0,4)=='5600'?6:5))==substr($tovar['kod_prodact'], 0, (substr($tovar['kod_prodact'],0,4)=='5600'?6:5)) ) {
                $ar = explode(';', $str);
                if (strlen($ar[1]) > 4) {
                    $tovar['description']=$ar[1];
                    if(stripos($tovar['description'],'NEW')!==false)$tovar['description']=trim(substr($tovar['description'],stripos($tovar['description'],'NEW')+4),'- !.');
                    if(stripos($tovar['description'],$tovar['name'])!==false)$tovar['description']=trim(substr($tovar['description'],stripos($tovar['description'],$tovar['name'])+strlen($tovar['name'])),'- !.');
                    break;
                }
            }}
            //$i=strpos($tovar['description'],' - ');
            //if($i!==false && $i<strlen($tovar['name'])+4)  $tovar['description']=trim(substr($tovar['description'],$i+3),'- !.');

            if (!$tovar['ed']) $tovar['ed'] = $data['ed'];

            /*//$tovar['info']=(empty($data['info']) ? [] : $data['info']);
            $tovar['info']=js_decode($data['info']);
            //if(empty($tovar['info']['best_before']) || $tovar['info']['best_before']<strtotime('-6 month')) $tovar['info']['best_before']=date('Y-m-d');
            unset($tovar['info']['best_before']);
            $tovar['info']=js_encode($tovar['info']);*/
            unset($tovar['delete'],$tovar['replace'],$tovar['old']);
//var_dump($tovar);
            foreach($tovar as $key=>$val){
                if($key=='category')echo "<br>".$key.":".implode(',',array_keys(Tovar::_GetVar($data,'category')))."-&gt;<span class='green'>".implode(',',array_keys($val))."</span>";
                elseif($key=='info')echo "<br>".$key.":".var_export(@$data[$key],!0)."-&gt;<span class='green'>".var_export($val,!0)."</span>";
                elseif(!isset($data[$key]))echo "<br>".$key.":неопределен-&gt;<span class='green'>".$val."</span>";
                elseif(is_array($val) && (!isset($val['name'])|| @$data[$key]!=$val['name'])) echo "<br>".$key.":".@$data[$key]."-&gt;<span class='green'>".(empty($val['name'])?'':$val['name']).(empty($val['id'])?'':'('.$val['id'].')')."</span>";
                elseif(@$data[$key]!=$val)echo "<br>".$key.":".@$data[$key]."-&gt;<span class='green'>".$val."</span>";
            }

            if ($test) $cnt1++;
            else {
                Tovar::SaveTovar($tovar);
                //DB::write_array('tovar',$tovar);
                /*DB::sql("UPDATE `" . db_prefix . "tovar`
		SET `name`='" . addslashes($tovar['name']) . "', `type`='" . $tovar['type'] . "',
		 `price`='" . str_replace(',','.',$tovar['price']) . "', `price0`='" . str_replace(',','.',$tovar['price0']) . "',
		 `kod`='" . addslashes($tovar['kod']) . "', `collection`='" . addslashes($tovar['collection']) . "',
		 `description`='" . addslashes($tovar['description']) . "',
		 `kol`='" . str_replace(',','.',$tovar['kol']) . "',`ed`='" . addslashes($tovar['ed']) . "', `brand`='" . addslashes($tovar['brand']) . "',
		 `info`='" . addslashes($tovar['info']) . "'
		WHERE id='" . $data['id'] . "' LIMIT 1");*/
                //echo "<br>\n".$q;
                $cnt1 += DB::affected_rows();

            }
        } else { // НОВЫЙ ТОВАР
            if ($tovar['delete']) continue;
            echo "<br>\nДобавляю"; $tovar['id']=0;
            if(!$brand)$brand = GetBrandName($tovar);
            $tovar['brand'] = Tovar::GetBrand($brand); if($tovar['brand']){if($tovar['brand']['id']==0)echo "<br>\nBrand:".$tovar['brand']['name']; }
            //$tovar['collection']=Tovar::GetCollection($tovar['collection'],$tovar['brand']);
            if (!empty($tovar['collection_name'])) {
                $tovar['collection'] = Tovar::GetCollection($tovar['collection_name'], $tovar['brand']);
                if(!empty($tovar['brand']['id']))$data['brand']=$tovar['brand']['id'];
                //elseif(!empty($tovar['brand'])&&$tovar['brand']>0)$data['brand']=$tovar['brand'];
                echo "<br>!!2 collection: ".$tovar['collection'].', brand: '.var_export($tovar['brand'],!0);
                //echo "<br>description :".$tovar['description'];
                if(!empty($tovar['collection']) && !empty($tovar['description']) && (trim($tovar['collection']) == trim($tovar['description']))) $tovar['description'] = '';
            } else {
                $tovar['collection'] = 0;
            }
            if(empty($tovar['description']))$tovar['description']='';

            if (empty($tovar['description'])||strlen($tovar['description'])<30){
                foreach ($ar_coments as $str)
                    if (substr($str, 0, (substr($tovar['kod_prodact'],0,4)=='5600'?6:5))==substr($tovar['kod'], 0, (substr($tovar['kod'],0,4)=='5600'?6:5)) ) {
                $ar = explode(';', $str);
                if (strlen($ar[1]) > 4) {
                    $tovar['description'] = $ar[1];
                    if(stripos($tovar['description'],'NEW')!==false)$tovar['description']=trim(substr($tovar['description'],stripos($tovar['description'],'NEW')+4),'- !.');
                    if(stripos($tovar['description'],$tovar['name'])!==false)$tovar['description']=trim(substr($tovar['description'],stripos($tovar['description'],$tovar['name'])+strlen($tovar['name'])),'- !.');
                    echo ", загрузил описание: ".var_export($ar,!0);
                    break;
                }
            }}
            $i=strpos($tovar['description'],' - ');
            if($i!==false && $i<strlen($tovar['name'])+4)  $tovar['description']=trim(substr($tovar['description'],$i+3),'- !.');

            /*if(empty($tovar['info']) || !is_array($tovar['info']))$tovar['info']= (empty($tovar['info']) ? [] : js_decode($tovar['info']));
            $tovar['info']['best_before']=date('Y-m-d');
            $tovar['info']=js_encode($tovar['info']);*/

            unset($tovar['delete'],$tovar['replace'],$tovar['old']);

            if($tovar['price']<1 && $tovar['price0']>0){
                if($tovar['price0']<33){
                    $tovar['price'] = 99;
                }elseif($tovar['price0']<50){
                    $tovar['price'] = 120;
                }elseif($tovar['price0']<1000){
                    $tovar['price']=3*$tovar['price0'];
                }elseif($tovar['price0']<3000){
                    $tovar['price']=(3.55-0.00055*$tovar['price0'])*$tovar['price0'];
                }elseif($tovar['price0']<10000){
                    $tovar['price']=1.9*$tovar['price0'];
                }else{
                    $tovar['price']=1.4*$tovar['price0'];
                }
                $tovar['price0']=round($tovar['price0'], 0, PHP_ROUND_HALF_UP);
                $tovar['price']=max(99,round($tovar['price'], -1, PHP_ROUND_HALF_UP));
            }

            foreach($tovar as $key=>$val)if($val){
                if($key=='category')echo "<br>".$key.":-&gt;<span class='green'>".implode(',',array_keys($val))."</span>";
                else echo "<br>".$key.":-&gt;<span class='green'>".$val."</span>";
            }

            if ($test) {
                $cnt2++;
            }else {
                Tovar::SaveTovar($tovar);
                //DB::write_array('tovar',$tovar);
                /*DB::sql("INSERT INTO `" . db_prefix . "tovar`
		    ( `name`, `type`, `price`, `price0`, `kod`, `collection`, `description`, `kol`, `ed`, `brand`, `info`)
		    VALUES ( '" . addslashes($tovar['name']) . "', '" . $tovar['type'] . "',
		    '" . $tovar['price'] . "', '" . $tovar['price0'] . "', '" . addslashes($tovar['kod']) . "', '" . addslashes($tovar['collection']) . "',
		    '" . addslashes($tovar['description']) . "', '" . str_replace(',','.',$tovar['kol']) . "', '" . addslashes($tovar['ed']) . "', '" . addslashes($tovar['brand']) . "', '" . addslashes($tovar['info']) . "')");*/
                //$tovar['id'] = mysql_insert_id();
                $cnt2 += DB::affected_rows();
            }
        }
        echo "<br>\n<a href='#' onclick=\"return ajaxLoad('','/adm/edit_tovar.php?form=" . $tovar['id'] . "')\">" . toHtml($tovar['name']) . "</a>, `type`='" . $tovar['type'] . "',
		 `price`='" . $tovar['price'] . "', `price0`='" . $tovar['price0'] . "',
		 `kod_prodact`='" . toHtml($tovar['kod_prodact']) . "', `collection`='" . toHtml(DB::GetName('collection', $tovar['collection'])) . "(" . toHtml($tovar['collection']) . ")',
		 `description`='<small>" . toHtml($tovar['description']) . "</small>',
		 `kol`='" . str_replace(',','.',$tovar['kol']) . "', `ed`='" . toHtml($tovar['ed']) . "', `brand`='" . $brand . "'";
    }
    flush();
    fclose($f);
    unlink($fil);
    echo "<h4>Обновлено " . $cnt1 . ", добавлено " . $cnt2 . " записей</h4>";
}
?>
<form enctype="multipart/form-data" method="POST" action="tovar_import.php">
    <input name='MAX_FILE_SIZE' type='hidden' value='2000000'>
    Файл для загрузки(&lt;2Мб):
    <input type='file' name='f' size=45 class="button"><br>
    <label>Формат файла
    <select name="format">
        <option value="1"<?=(@$_REQUEST['format']==1?' selected':'')?>>С описанием</option>
        <option value="2"<?=(@$_REQUEST['format']==2?' selected':'')?>>Бланк заказа1</option>
        <option value="3"<?=(@$_REQUEST['format']==3?' selected':'')?>>Бланк заказа2</option>
    </select>
    </label>
    <input type="submit" class="button right" style="width:auto;" value="Проверить"
           onclick="this.form.action='tovar_import.php?test';">
    <input type="submit" class="button right" style="width:auto;" value="Добавить"
           onclick="this.form.action='tovar_import.php';">
</form>
<p>
        Формат1: 0Код;1коллекция;2Наименование;3Описание;4Кол-во/Объем;5Цена;6Цена прихода;7Категории;8Вид<br>
    Формат2: Линия;Артикул;Наименование товара;Объем;Цена-;Цена с учетом скидки(будет добавляться 15%)<br>
    Формат2: Линия;1Наименование товара;2Артикул;3Объем;4Цена опт;5Цена с учетом скидки(будет добавляться 15%)<br>

    Если поле 5цена начинается с "+" - цена будет перезаписана.<br>
    Если поле 5цена начинается с "-" - товар будет удален.<br>
    Замена кода "Старый-&gt;Новый".<br>
    Строки содержащие меньше 6 полей пропускаются.<br>
    Если 0код=SQL Выполняется команда update над базой товаров условие WHERE в 2name, список SET в 3Краткая
    характеристика
</p>
<?
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/tail.php";

function GetBrandName($tovar)
{
    if (substr($tovar['kod_prodact'], 0, 4) == '5600') $brand = 'Sunmaxx';
    elseif (stripos($tovar['name'], 'tannymax') !== false) $brand = 'Tannymax'; elseif (stripos($tovar['name'], 'that\'so') !== false) $brand = 'That\'so'; elseif (in_array(substr($tovar['kod_prodact'], 0, 2), array('11', '12', '13', '21', '22', '44'))) $brand = 'Hempz'; else $brand = 'Supre';
    return $brand;
}
