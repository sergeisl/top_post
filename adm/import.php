<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/config.php";
if( !User::is_admin(uADM_MANAGER) ) {	Out::error('Необходима авторизация!');Out::Location('/');}
if (isset($_GET['stat'])) {
/////// СТАТИСТИКА ///////
    $count1 = intval(DB::Count('tovar'));
    $count2 = intval(DB::Count('tovar','sklad<>9'));
    $count3 = intval(DB::Count('tovar','sklad=1'));
    $count4 = intval(DB::Count('category'));
    $count5 = count(glob($_SERVER['DOCUMENT_ROOT'] . path_tovar_image . 's*.jpg'));
    $count6 = count(glob($_SERVER['DOCUMENT_ROOT'] . path_tovar_image . 'p*.jpg'));

    echo "Всего категорий <b>" . $count4 . "</b><br>
Всего товаров <b>" . $count1 . "</b><br>
в т.ч. склад <b>" . $count2 . "</b><br>
в т.ч. магазин <b>" . $count3 . "</b><br>
Всего изображений <b>" . $count5 . "</b>" . ($count5 != $count6 ? '/' . $count6 : '');
    exit;
}
//@mkdir($_SERVER['DOCUMENT_ROOT'] . '/log/session', 0777);
$title="Подлив прайс-листов.";
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/head.php";
echo "\n<h1>" . $title . "</h1>\n";
// список полей для подлива
$MaxCols=30; // максимальное кол-во обрабатываемых столбцов в файле
$fields= [
    'name'=>['Название товара','Product Name/Description'],
    'name2'=>['Название товара часть 2'],
    'description'=>['Описание','Коментарий'],
    'gr'=>['Группа товара','Вид оборудования'],
    'prodact'=>['Код производителя','Part Number','Артикул'],
// todo   'kod'=>['Внутренний код поставщика','id'],
    'brand'=>['Бренд','Производитель'],
    'collection'=>['Коллекция'],
    'price0'=>['Закупочная цена','Price'],
    'price'=>['Розничная цена'],
    'price1'=>['Цена мелк.опт'],
    'price2'=>['Цена опт.'],
    'priceu'=>['Рекомендуемая розничная цена'],
    'tranzit1' =>['транзит1','Ростов-на-Дону'],
    'tranzit2'=>['транзит2','Воронеж','Волгоград','Краснодар','Пятигорск','Резервы','В пути','Транзит из ЦО','Свободный склад ЦО','Ближний транзит'],
    'tranzit3'=>['транзит3','Дальний транзит'],
    'tranzit4'=>['транзит4'],
    'tranzit5'=>['транзит5'],
    'ost'=>['остаток'],
    'garant'=>['гарантия'],
    'valuta'=>['Валюта'],
    'volume'=>['Объем'],
    'weight'=>['Вес'],
    'in_package'=>['Кол. в упак.'],
    'min_zakaz'=>['Мин.заказ.'],
    'img'=>['URL картинки']
];

//$GLOBALS['ar_brand']=DB::Select2Array('brand','','id&name'); // список брендов в формате id=>название

$file=File::Get();
if($file){// передан файл - буду подливать
    //ignore_user_abort(true);
    set_time_limit(1000);
    session_write_close(); // закрыть сесию
    if(!empty($_REQUEST['supplier_new'])){
        if(($supplier=DB::Select('supplier','name="'.addslashes($_REQUEST['supplier_new']).'"'))){
            echo "<br>\nПоставщик ".$supplier['id'].':'.$supplier['name'];
            $supplier=$supplier['id'];
        }else{
            DB::sql('INSERT INTO '.db_prefix.'supplier ( name ) VALUES ("'.addslashes($_REQUEST['supplier_new']).'")');
            $supplier=DB::GetInsertId('supplier');
            echo "<br>\nДобавил поставщика ".$supplier.':'.$_REQUEST['supplier_new'];
        }
    }elseif(!empty($_REQUEST['supplier'])){
        $supplier=intval($_REQUEST['supplier']);
    }else {$supplier=0; error("Не указан поставщик!");}

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $file=File::Extract($file);
    $time1 = filemtime($file);
    if(!empty($_REQUEST['file_offset'])){
        $skip=intval($_REQUEST['file_offset']);
        //$rows = array_slice($rows, $skip);
    }else $skip=0;
    $rows=File::Read($file,$skip,(empty($_REQUEST['col'])?$skip+7:0)); if(empty($rows) || count($rows) === 0 ) error('Файл пустой или неверной структуры!');
    //if($GLOBALS['DEBUG']) $rows = array_slice($rows, 0,100);
    if(!empty($_REQUEST['col']))echo "<br>В файле ".count($rows).' строк';
    if(empty($_SESSION['error'])){
        //var_export($rows);
        $maxCol = count($fields); // посчитаем реальные столбцы

        $supplier_cfg=DB::Select('supplier',intval($supplier));
        echo "<h2>Поставщик: ".$supplier_cfg['name']."</h2>";
        if(empty($_REQUEST['col'])){ // подбор колонок - этап 2
            $col=(empty($supplier_cfg['info']) ? [] : js_decode($supplier_cfg['info']) );
            if(Get::DEBUG()){if($col)echo "<br>Столбцы уже определены"; else echo "<br>Подбираю столбцы:";}
            $count=0;
            for ($i=$count=0; $i < min(count($rows),15)&&$count<3; $i++) { // по строкам
                $rows[$i]=array_slice($rows[$i], 0, $MaxCols);
                if(!empty($_REQUEST['file_encoding'])&&$_REQUEST['file_encoding']=='utf-8')$rows[$i] = Convert::array_utf2win($rows[$i]);
                if(Get::DEBUG()&&!$col)echo "<br>".implode($_REQUEST['csv_delim'],$rows[$i]);
                if(empty($rows[$i])){if(Get::DEBUG()&&!$col)echo " - пустая строка";continue;}
                $maxCol=max($maxCol,count($rows[$i]));
                $rows[$i] = array_map('trim', $rows[$i]);
                $empty = 2;
                foreach ($rows[$i] as $v) if($v){ if(--$empty < 1)break; }
                if($empty>0){ unset($rows[$i]); $skip++; if(Get::DEBUG()&&!$col)echo "Пустой - удаляю!"; continue; } // пропускаем строки, c заполненным одним полем

                if(!$col)foreach ($rows[$i] as $f_i => $f) {
                    if(Get::DEBUG())echo $f . ", ";
                    foreach ($fields as $key => $vals)foreach($vals as $val){
                        if(!in_array($key,$col) && (stripos($val,$f)!==false || stripos($f,$val)!==false)){
                            $col[$f_i] = $key;
                            if(Get::DEBUG())echo "!" . $key;
                            $count++;
                        }
                    }
                }
            }
            //var_export($col);
            //$rows = array_slice($rows, 0,10); // режу строки
            ?>
            <form enctype='multipart/form-data' method='post' action='/adm/import.php'>
                <input name='f_url' type='hidden' value='<?=basename($file)?>'>
                <input name='skip' type='hidden' value='<?=$skip?>'>
                <input name='file_encoding' type='hidden' value='<?=$_REQUEST['file_encoding']?>'>
                <input name="csv_delim" type="hidden" value="<?=$_REQUEST['csv_delim']?>">
                <input name="csv_enclosure" type="hidden" value='<?=$_REQUEST['csv_enclosure']?>'>
                <input name="supplier" type="hidden" value='<?=$supplier?>'>

                <label>Бренд: <?=Tovar::brandList('select',$supplier_cfg['brand'])?> <small>(укажите, если в файле все товары одного бренда)</small>
                <a title="Добавить" class="icon add ajax" href="/adm/sprav.php?layer=brand&amp;form&ret_path=<?=urlencode($_SERVER['REQUEST_URI'])?>"></a>
                </label>
                <br>
                <label><input type="checkbox" value="1" name="add_brand_to_name" <?=($supplier_cfg['add_brand_to_name']?' checked':'')?>> добавлять бренд в название товара</label><br>
                <label><input type="checkbox" value="1" name="brand_in_name" <?=($supplier_cfg['brand_in_name']?' checked':'')?>> бренд отдельной строкой в колонке названия</label><br>

                <label>Группа: <?=Tovar::grList(0,['format'=>'select','no'=>1,'act'=>$supplier_cfg['gr']])?> <small>(укажите, если в файле все товары одной группы)
                        Вы можете указать колонку с группой или единную группу для всего файла или группа будет в том же поле, что и наименование
                    </small></label><br>
                <label>Наценка: <input name="nac" type="number" value='<?=$supplier_cfg['nac']?>'>
                <small>Если валюта $, то наценка относительно курса ЦБ + стандартная (<?=Nacenka?>%) <br>
                    Если валюта рубли, то только указанная вами наценка<br>
                    Наценка указанная в группе, добавляется к указанной для поставщика</small></label><br>
                <label>Валюта закупочной цены
                    <select name="valuta">
                        <option value="$"<?=($supplier_cfg['valuta']=='$'?' selected':'')?>>$</option>
                        <option value="E"<?=($supplier_cfg['valuta']=='E'?' selected':'')?>>€</option>
                        <option value=" "<?=(trim($supplier_cfg['valuta'])==''?' selected':'')?>>руб</option>
                    </select>
                    <small>если не указано, то берется из колонки валюта - допустимо USD,руб или первый символ у цены '$'</small>
                </label><br>
                <label>Префикс к названию всех товаров: <input type="text" value="<?=$supplier_cfg['prefix']?>" name="prefix" maxlength="100" size="30"></label><br>


                <label>Колонка транзит:
                    <select name="ctranzit">
                        <option value="_"<?=($supplier_cfg['ctranzit']=='_'?' selected':'')?>>в колонке tranzit любой символ кроме 'заказ', 'call', '0', 'Нет'</option>
                        <option value="*"<?=($supplier_cfg['ctranzit']=='*'?' selected':'')?>>товар есть всегда</option>
                        <option value=" "<?=($supplier_cfg['ctranzit']==' '?' selected':'')?>>в колонке tranzit количество</option>
                    </select>
                </label><br>
                <?
                //var_dump($supplier_cfg);
                for ($i = 1; $i <= 5; $i++){
                echo "\n<label>Склад транзита ".$i.": <select name='sklad".$i."'>";
                    foreach (Tovar::$_sklad_name as $key => $val)
                        echo "\n<option value='".$key."'".(!empty($supplier_cfg['sklad'.$i])&&$supplier_cfg['sklad'.$i]==$key?' selected':'').">".$val."</option>";
                echo "\n</select></label><br>";
                }
                ?>
                <p>Если колонка с розничной ценой не задана, то розничная цена будет взята из рекомендуемой, если и рекомендуемая не задана,
                    то цена будет расчитана от закупочной.</p>
                <br><br>


                <div class="w100" style="overflow-x:scroll">
                    <table><tr>
                            <?
                            //$maxCol = max(max(array_keys($rows[0])),count($fields));
                            for ($i=0;$i<=$maxCol;$i++){
                                echo "\n<th class='nobr'>".($i+1).":<select name='col[".$i."]'><option>Не выбрано</option>";
                                foreach($fields as $key=>$val) echo "<option value='".$key."'".(!empty($col[$i])&&$col[$i]==$key ? " selected":"").'>'.$val[0]."</option>";
                                echo "</select></th>";
                            }
                            ?>
                        </tr>
                        <?
                        foreach ($rows as $index => $row) {
                            $row = array_map('trim', $row);

                            $empty = true;
                            foreach ($row as $v) if($v){ $empty = false;  break; }
                            if($empty) continue; // пропускаем пустые строки

                            // добавляем пустые элементы в строки в выбранные столбцы
                            for ($i = 0; $i <= $maxCol; $i++) if (!array_key_exists($i, $row)) $row[$i] = '';
                            ksort($row);

                            echo "\n<tr style='vertical-align: top'>";
                            foreach ($row as $v) echo "<td>".(!empty($_REQUEST['file_encoding'])&&$_REQUEST['file_encoding']=='utf-8' ?Convert::utf2win($v): $v )."</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table></div>
                <input name='maxcount' type='hidden' value='<?=$maxCol?>'><br class="clear">
                <input type='submit' value='Загрузить' class="button"><br>
            </form>
        <?
        }else{ // это уже окончательная загрузка этап 3
            // сохраняю настройки для этого поставщика ["ikateg","0","0","0","0","0","iname","0","iprice","0","0","0","0","0","0","0","0","0","0","0"]
            $add='';
            foreach($supplier_cfg as $key=>$val){
                if(isset($_POST[$key]))$add.=', '.$key."='".addslashes($_POST[$key])."'"; // 'valuta','brand','nac','ctranzit','prefix'
            }
            // сохраняю настройки колонок
            DB::sql("UPDATE ".db_prefix."supplier SET info='".addslashes(js_encode($_POST['col']))."'".$add." WHERE id=".$supplier);

            DB::sql('UPDATE ' . db_prefix . 'supplier_link SET sklad='.SKLAD_NOT.',ost=0 WHERE supplier=' . $supplier);
            echo "<br>Состарил записей: " . DB::affected_rows();

            $parent = [];
            $count=0; $gr=0; $brand=0; $brand_old=0;
            foreach ($rows as &$row) {
                $row=array_slice($row, 0, $_POST['maxcount']);
                if(!empty($_REQUEST['file_encoding'])&&$_REQUEST['file_encoding']=='utf-8')$row = Convert::array_utf2win($row);
                $row=array_map('trim', $row);

                $empty = true;
                foreach ($row as $v) if($v){ $empty = false;  break; }
                if($empty){echo "<br><br>!: Пустая строка"; continue;} // пропускаем пустые строки

                // добавляем пустые элементы в строки в выбранные столбцы
                for ($i = 0; $i <= intval($_POST['maxcount']); $i++) if (!array_key_exists($i, $row)) $row[$i] = '';
                ksort($row);

                // повторяю значения если они не заданы для объединенных ячеек (только наименование!)
                if (!empty($parent)) foreach ($row as $k => $v) if(empty($v)&&$_POST['col'][$k]=='name') $row[$k] = $parent[$k];
                $parent = $row;

                $result=['supplier'=>$supplier];
                foreach ($_POST['col'] as $col => $name)if(!ctype_digit($name))$result[$name]=$row[$col];
                if(empty($result['name']) || empty($result['price'])&&empty($result['price0'])){ echo "<br>\nНет наименования или цены: ".var_export($result,!0); continue;}
                echo "<br><br>\n".$count.":";//var_export($result);
                if(SaveRow($result)) // загружаю в базу одну строку
                    $count++;
                    //if($count>30)break; ////// todo !!!!!!!!!!!!!!!!!
                else
                    echo " - не добавил!";
            }
            print '<br>Добавлено <b>' . $count . '</b> записей';
            // ставлю дату последнего подлива
            DB::sql("UPDATE " . db_prefix . "supplier SET dat='" . date("Y-m-d", $time1) . "' WHERE id=" . $supplier . " LIMIT 1");

/*            // обновить по всем товарам актуального поставщика, если поставщика нет, ставлю "9-Уточните наличие у менеджера"
            $result = DB::sql('SELECT * from ' . db_prefix . 'supplier_link WHERE supplier=' . $supplier );
            while ($row = DB::fetch_assoc($result)) Tovar::SetFirstSupplier($row['tovar']);
*/

            /*// проставить неподлитым товарам другого основного поставщика
            $result = DB::sql('SELECT * from ' . db_prefix . 'tovar WHERE supplier=' . $supplier . ' and sklad=9');
            while ($row = DB::fetch_assoc($result)) Tovar::SetFirstSupplier($row['id']);
            DB::sql('UPDATE ' . db_prefix . 'tovar SET sklad=4 WHERE supplier=' . $supplier . ' and sklad=9'); // остальным ставлю "Уточните наличие у менеджера"*/
            Tovar::ClearCash();
        }
    }else {
        Out::ErrorAndExit(0,!0);
    }

}else{ // вывести форму загрузки - этап 1 !!!
    Out::ErrorAndExit(0,!0);
    $suppliers=DB::Select2Array('supplier');
    ?>
    <form enctype='multipart/form-data' method='post' action='/adm/import.php'>
        <label>Поставщик:
        <?if($suppliers){?>
            <select name="supplier">
                <?
                foreach($suppliers as $supplier)echo "\n<option value='".$supplier['id']."'>".$supplier['name']."</option>";
                ?>

            </select>
        </label> или новый: <label>
        <?}?>
        <input name="supplier_new"></label> <br>
        <input name='MAX_FILE_SIZE' type='hidden' value='10000000'>
        <label>Файл для загрузки(<10Мб): <input type='file' name='f' size=45></label><br>
        <label>Раннее загруженные:
        <select name="f_url">
            <option value=""> -- </option>
            <?
            $dh = opendir(fb_tmpdir) or die ("Не удалось открыть каталог " . fb_tmpdir);
            while ($f = readdir($dh)) {
                if (in_array(pathinfo($f, PATHINFO_EXTENSION), File::$ext_load)) echo "\n<option value='".$f."'>".$f."</option>";
            }
            closedir($dh);
            ?>
        </select></label><br>

        <label>Кодировка
           <select name="file_encoding">
                <option value="utf-8">UTF-8</option>
                <option value="windows-1251" selected>Windows 1251</option>
            </select>
        </label><br>
        <label>Пропустить строк: <input type="number" value="0" min="0" max="999"></label><br>
        <label>Разделитель полей: <input type="text" value=";" name="csv_delim" maxlength="1" size="1" /></label><br>
        <label>Ограничитель текста: <input type="text" value='"' name="csv_enclosure" maxlength="1" size="1" /></label><br>
        <input type='submit' value='Отправить файл' class="button"><br>
    </form>

    <div id='stat' style='BORDER:#9fbddd 1px solid;padding:10px;width:200px;float:right;' onclick="return ajaxLoad('stat','?stat=1');"> Посчитать статистику </div>
    <div style="width:500px">
        <table><caption class="b">Последние подливы:</caption>
            <thead><th>id</th><th>Поставщик</th><th>Дата подлива</th><th>Записей в базе</th></thead>
<?
        $res = DB::sql('SELECT * FROM ' . db_prefix . 'supplier');
        while ($row = DB::fetch_assoc($res)){
            echo '<tr><td>' . $row['id'] . '<td>' . $row['name'] . '<td' . (($row['dat'] < date("Y-m-d", time() - 60 * 60 * 24 * 15)) ? ' style="color:red"' : '') . '>' .
                date("d.m.Y",strtotime($row['dat'])) . '<td style="text-align:center">' . DB::Count('supplier_link', 'supplier=' . $row['id']) . "
                <td><a href='/api.php?tbl=supplier_link&tovar=all&del=" . $row['id'] . "' onclick=\"return ajaxLoad(this.parentNode,this.href);\">[удалить]</a></tr>\n";
        }
?>
        </table></div><span id="otvet"></span>
    <br>Подлив описаний и изображений: <a href='!dlink.php'>[dlink]</a> <a href='!merlion.php'>[merlion]</a> <a href='!optra.ru.php'>[optra.ru]</a> <br>
    <a href='adm.php'>[Администрирование базы данных]</a> <a href='!.php'>[Обслуживание базы данных]</a>.

    <?
}


/** разбирает и сохранеят одну строку из файла в базу
 * @param $data
 * @return bool
 */
function SaveRow($data){
    global $gr, $brand, $brand_old, $time1;
    $supplier=$data['supplier'];
    $name = $data['name'];
    if(!empty($data['name2'])) {
        if (!empty($name) && strpos($data['name2'], $name) !== false) $name = $data['name2'];
        else $name .= ' ' . $data['name2'];
    }
    if (!$name || Tovar::isStopWord($name)) return false;
    $name_supplier=$name;
    if(!empty($_POST['prefix']))$name=$_POST['prefix'].' '.$name;

    if(empty($data['valuta'])){
        $valuta0= $_POST['valuta'];
    }elseif(stripos($data['valuta'], 'usd')!==false || (strpos($data['valuta'],'$')!==false)){
        $valuta0 = '$';
    }elseif(stripos($data['valuta'], 'euro')!==false || (strpos($data['valuta'],'€')!==false)){
        $valuta0 = 'E';
    }else{
        $valuta0 = ' ';
    }

    if(empty($data['price0'])){
        $price0=0;
    }else{
        $price0 = $data['price0']; // закупочная цена
        if (substr($price0, 0, 1) == '$' || substr($price0, 0, 1) == 'E') {
            $price0 = substr($price0, 1);
            $valuta0 = substr($price0, 0, 1);
        }
        $price0 = floatval(str_replace(' ', '', str_replace(',', '.', $price0)));
    }

    if (empty($data['priceu'])){
        $priceU=0;$valuta='';
    }else{
        $priceU = $data['priceu'];
        if (substr($priceU, 0, 1) == '$' || substr($priceU, 0, 1) == 'E') {
            $priceU = substr($priceU, 1);
            $valuta = substr($priceU, 0, 1);
        }else $valuta = '*';
        $priceU = floatval(str_replace(' ', '', str_replace(',', '.', $priceU)));
    }
    $price=(empty($data['price'])?0: floatval(str_replace(' ', '', str_replace(',', '.',$data['price'])))); // розничная цена
    $price1=(empty($data['price1'])?0: floatval(str_replace(' ', '', str_replace(',', '.',$data['price1'])))); // мелкооптовая цена
    $price2=(empty($data['price2'])?0: floatval(str_replace(' ', '', str_replace(',', '.',$data['price2'])))); // оптовая цена

    $name1 = Tovar::NormalName($name, $_POST['prefix']);
    print "<br>\n" . toHtml($name1) . ($name != $name1 ? '<small>(' . toHtml($name) . ')</small>' : '') .
        ' <i>' . $price0 . $valuta0 . '/' . $priceU . $valuta . '/' . $price . '/' . $price1. '/' . $price2  . '</i>'; // Чип для картриджа Kyocera FS-1120D (TK-160-2.5K);202;TK-160-2.5K;0;184;173;$3.6;
    $name = $name1;
    $ost = (empty($data['ost'])?'':floatval(str_replace(' ', '', str_replace(',', '.',$data['ost']))));

    if( $_POST['ctranzit'] == '*' || ($ost>0 && $_REQUEST['sklad1']==1)) {
        $sklad = 1; // в наличие
    } else {
        $out='';
        $sklad = 0;
        //echo "<br>sklad=";
        for ($i = 1; $i <= 5; $i++){
            //echo " ".$i.":".$data['tranzit'.$i].":".$_REQUEST['sklad'.$i]." ";
            if (!empty($data['tranzit'.$i]) && IsTranzit($data['tranzit'.$i])) {
                if(empty($data['ost']))$ost = trim($ost) . $data['tranzit'.$i];
                if(!$sklad) $sklad = intval($_REQUEST['sklad'.$i]);
                elseif($sklad>intval($_REQUEST['sklad'.$i]))$sklad = intval($_REQUEST['sklad'.$i]); // более доступный
                //echo " ~ ost=".$ost.", sklad=".$sklad;
            }elseif(!isset($data['tranzit'.$i])) $out.='<br>Нет sklad'.$i.' '.var_export($data,!0).'<br>';
        }
        if(!$sklad) echo $out;
        elseif($ost<1 && $sklad == 1) $sklad = 2;

   }


    if($_POST['ctranzit'] && !$sklad && $price==0 && $price0==0) { // это может быть строка с названием бренда или с названием группы товара
        if ($price0 == 0 && $price==0 && (empty($data['name2']) != empty($data['name']))) {
            // если группа - это бренд - не присваиваю!
            $tmp_name = trim(empty($data['name2'])?$data['name']:$data['name2'], "\x00../");
            if (strlen($tmp_name) < 5) {
                echo "<b class='blue'>" . $tmp_name . " - <5 </b>";
                return false;
            }elseif(empty($_POST['brand']) && !empty($_POST['brand_in_name']) && ($br=Tovar::is_brand($tmp_name))) { // бренд в поле названия
                echo "<b class='blue'>" . $tmp_name . " - БРЕНД </b>";
                $brand=$brand_old=$br;
                return false;
            }
            if (empty($_POST['gr'])) {
                echo '<b class="blue">' . $tmp_name . ' - Группа1</b>';
                $gr=Tovar::GetGr($tmp_name); $gr=$gr['id'];
            }
            return false;
        }
        print " - нет(" . @$data['tranzit1'] . ",". @$data['tranzit2'] . ",". @$data['tranzit3'] . "," . @$data['tranzit4'] . "," . @$data['tranzit5'] . ")!";
        return false; // sklad=9 я поставил вначале!
    }
    if($price>0){
        // ок, розничная цена задана
        if(!$priceU) $priceU=$price;
    }elseif($priceU>0){
        $price=$priceU;
    }elseif($price0>0){
        $tov = ['price0' => $price0, 'valuta0' => $valuta0, 'valuta' => $valuta, 'supplier' => $supplier, 'priceu' => $priceU];
        $price = Tovar::CalcPrice($tov); // курс с учетом базовой наценки, без учета наценки за категорию
    } else {
        print ' <b>цена=' . @$data['price'] . '</b>';
        return false;
    }
    echo ', ост=' . $ost . ', sklad=' . $sklad;

    if(!empty($_REQUEST['brand'])){ // бренд задан явно для всего файла
        $brand = DB::Select('brand', intval($_REQUEST['brand']));
    }elseif(!empty($data['brand'])){ // бренд задан для столбца
        $brand=Tovar::GetBrand($data['brand'],1);
    }

    if(!empty($_REQUEST['gr']))$gr=$_REQUEST['gr'];

    $tovar = null; // товар, если найден
    if(isset($data['prodact'])&&empty($data['prodact'])){// если колонка кода заданна, и код пустой - не добавлять!
        // todo может это группа товара
        return false;
    }elseif(!empty($data['prodact']) && !Tovar::is_brand($data['prodact'])) {
        $kod_prodact = trim($data['prodact'],'#* ');
        if (strpos($kod_prodact, '#') > 5) $kod_prodact = substr($kod_prodact, 0, strpos($kod_prodact, '#'));
        if (substr($kod_prodact, 0, 4) == 'Lom-') {
            $kod_prodact = substr($kod_prodact, 4);
            //if(!$brand)$brand = 65;
        }
        echo '(код:' . $kod_prodact . ')';
    } else $kod_prodact = '';
    // = preg_replace('/[^\d,]+/', '', $value); // заменить все символы кроме чисел и запятой на ''

    // если в названии есть описание выделяю его в отдельное поле
    if(!empty($data['description'])){
        $description = toHtml($data['description']);
        echo '<br>description=' . $description;
    }elseif ((strpos($name, '{') !== false) && (preg_match('|\{([^\}]{18,})\}|', $name, $arr))) {
        $description = toHtml(@$arr[1]);
        $name = preg_replace('|\{[^\}]{18,}\}|', '', $name);
        echo '<br>name=' . toHtml($name) . ', description=' . $description;
    } elseif ((strpos($name, '(') > 40) && (preg_match('|\(([^\)]{20,})\)|', $name, $arr))) {
        $description = toHtml(@$arr[1]);
        $name = preg_replace('|\([^\)]{20,}\)|', '', $name);
        echo '<br>name=' . toHtml($name) . ', description=' . $description;
    } else $description = '';

    // пробую найти товар
    if($tov = DB::Select('supplier_link','name="'.addslashes($name_supplier).'" and supplier='.$supplier.($kod_prodact?' and kod_prodact="'.addslashes(kod_prodact).'"':''))) {
        if(!($tovar=FoundedTovar(intval($tov['tovar']))))  return false;
        echo " <i class='green'>gr1=" . $gr."</i>"; // если товар есть, то группу сохраняю
    }elseif ($tov = DB::Select('tovar','name="'.addslashes($name).'"'.($kod_prodact?' and kod_prodact="'.addslashes(kod_prodact).'"':''))) {
        if(!($tovar=FoundedTovar($tov)))  return false;
        echo " <i class='green'>gr2=" . $gr."</i>"; // если товар есть, то группу сохраняю
    }elseif($tov = DB::Select('supplier_link','name="'.addslashes($name_supplier).'"'.($kod_prodact?' and kod_prodact="'.addslashes(kod_prodact).'"':''))) {
        if(!($tovar=FoundedTovar(intval($tov['tovar']))))  return false;
        echo " <i class='green'>gr3=" . $gr."</i>"; // если товар есть, то группу сохраняю
    } elseif ($_REQUEST['brand']){        // бренд задан для всего файла

    } elseif (empty($data['brand'])){   // колонка бренд не задана

    } elseif (mb_strtolower($data['brand']) == 'noname') {
        $brand_old = 0;
        $brand = 0;
    } elseif ($brand = Tovar::GetBrand($data['brand'],1)) {
        $brand_old = $brand;
    } elseif ($brand = Tovar::GetBrand(implode(" ", $data))) {
        $brand_old = $brand;
    } else {
        echo '<span class="red">Не определил бренд!</span>';
    }

    //if(strtolower(substr($kod_prodact,0,6))=='core i'){$brand=21; $gr=119; $brand_name='Intel'; /*процессоры intel*/ echo " grI=".$gr;}
	//elseif(strtolower(substr($kod_prodact,0,5))=='xeon '){$brand=21; $gr=120; $brand_name='Intel'; /*процессоры intel*/ echo " grI=".$gr;}

    if($brand){
        if(empty($brand_old)){
            $brand_old = $brand['id'];
            if(empty($brand)){echo "<br>\n<span class='red'>неверный код бренда: ".$brand['id']."</span>"; return false;}
        }
        echo " <a class='green' href='/shop/?brand=".$brand['id']."'>".$brand['name']."</a> ";
    }elseif(!empty($brand_old)&&$_POST['brand_in_name']){
        $brand=$brand_old;
    }

    if (!empty($brand['name']) && !empty($_REQUEST['add_brand_to_name'])) { // если в исходной строке файла есть бренд и если его нет в названии, то добавлять в название товара
        if (mb_strtolower($brand['name']) <> 'noname' && stripos($name, $brand['name']) === false) {
            $name = $name . ' ' . $brand['name'];
            echo ', <span class="u">добавил бренд в название</span>';
        }
        //echo ", <span class='green'>" . $brand_name . '</span>';
    }

    if (!empty($data['gr'])) {
        $gr_name = Tovar::NormalName($data['gr']);
        $gr=Tovar::GetGr($gr_name,$name_supplier); if($gr)$gr=$gr['id']; // если указана колонка и такой группы нет - добавляю её
    }elseif (empty($gr)) { // ищу по шаблону товара
        $gr=Tovar::GetGr($name_supplier,$name_supplier); if($gr)$gr=$gr['id'];
    }
    if((empty($gr) || empty($tovar)) && strlen($kod_prodact)>3){
        // ищу по коду производителя с учетом бренда
        $result = DB::sql('SELECT * from ' . db_prefix . 'tovar WHERE brand="' . $brand['id'] . '" and (kod_prodact="' . addslashes($kod_prodact) . '" or LOWER(name) REGEXP "[^A-Za-zА-Яа-я0-9\+]' . mysqli_real_escape_string(DB::$link,preg_quote(strtolower($kod_prodact))) . '[^A-Za-zА-Яа-я0-9\+/]")' );
        // ищу по коду производителя с учетом бренда
        /*        $result = DB::sql('SELECT * from ' . db_prefix . 'tovar WHERE (LENGTH(TRIM(kod_prodact))>3 and brand=' . $brand .
                        ' and (locate(lower(trim(kod_prodact)), "' . addslashes(strtolower($name)) . '")>0) ' .
                        (strlen($kod_prodact) > 3 ? 'or lower(kod_prodact)="' . mb_strtolower(addslashes($kod_prodact)) . '"' : '') . ')' .
                        ($gr > 0 ? ' and gr=' . $gr : ''));*/
        while (($tov = DB::fetch_assoc($result))) {
            if($gr > 0 && !empty($tov['gr']) && $gr!=$tov['gr'])continue;
            if (Tovar::IsSovmestim($tov, $name)) {
                $gr = Tovar::_GetVar($tov,'gr');
                $tovar = $tov;
                echo " <i class='green'>gr4=" . $gr . "</i>" . '(' . ($gr_name = DB::GetName('category', $gr)) . ')';
                if (empty($kod_prodact)) {
                    $kod_prodact = stripslashes($tov['kod_prodact']);
                    echo " kod_prodact=" . toHtml($kod_prodact);
                }
                if (!empty($data['gr'])) Tovar::AddSinonimGr($data['gr'], $gr);
                break;
            }else{
                echo "<br>".$tov['name']." - похожий, но несовместимый!";
            }
        }
    }
    if (empty($gr) || empty($tovar) ) {
        // если товар есть в базе поставщиков с учетом бренда, то беру его id, gr
        $result = DB::sql("SELECT * from " . db_prefix . "supplier_link WHERE " .
            (strlen($kod_prodact) > 3 ? "kod_prodact='" . addslashes($kod_prodact) ."' or " : "") .
            "name='" . addslashes($name) . "'");
        while (($row = DB::fetch_assoc($result))) {
            if (($tov=DB::Select('tovar',intval($row['tovar'])))) {
                if(!empty($data['volume']) && !empty($tov['kol']) && $data['volume']!=$tov['kol'])continue; // разные объемы с одинаковым названием
                if ($tov['brand'] != $brand['id']) continue;
                if ($gr > 0 && $tov['gr'] != $gr) continue;
                if (Tovar::IsSovmestim($tov, $name)) {
                    $tovar = $tov;
                    $gr = Tovar::_GetVar($tov,'gr');
                    echo " <i class='green'>gr5=" . $gr . "</i>" . '(' . ($gr_name = DB::GetName('gr', $gr)) . ')';
                    if (!empty($tov['kod_prodact']) && empty($kod_prodact)) {
                        $kod_prodact = stripslashes($tov['kod_prodact']);
                        echo " kod_prodact=" . $kod_prodact;
                    }
                    break;
                }
            } else {
                echo '<b class="red">удалил Tovar id=' . $row['tovar'] . ' в supplier, т.к. нет в tovar!!!</b>';
                DB::Delete('supplier_link','tovar=' . $row['tovar']);
            }
        }
    }

    if (empty($gr) && !empty($data['gr'])){ // ucfirst($group));// добавляю новую категорию, первый символ в верхний регистр
            DB::sql('INSERT INTO '.db_prefix.'category ( name ) VALUES ("'.addslashes($gr_name).'")');
            if(DB::affected_rows()){
                $gr=DB::GetInsertId('category');
                echo "<br>\nДобавил новую категорию <b><a href='/shop/?gr=".$gr."'>".toHtml($gr_name)."</a></b> запись!";
            }else {echo "<br>\nНЕ смог добавить новую категорию <b>".toHtml($gr_name)."</b> запись!"; return false;}
    }

    if($gr){
        if(empty($gr_name)){
            $gr_name = DB::GetName('category',$gr);
            if(empty($gr_name)){echo "<br>\n<span class='red'>неверный код группы: ".$gr."</span>"; return false;}
        }
        echo " <a style='color:darkgreen' href='/shop/?gr=".$gr."'>".toHtml($gr_name)."</a> ";
    }

    if ($brand && empty($tovar) ) {
        if (($tovar=DB::Select('tovar','brand=' . $brand['id'] . ($gr>0?' and gr=' . $gr : '' ) . ' and '.
       '(' .(strlen($kod_prodact) > 3 ?
            'kod_prodact="' . addslashes($kod_prodact) . '" or locate(" ' . addslashes($kod_prodact) . ' ",CONCAT(" ",name," "))>0 or' : '' ).
       '(LENGTH(TRIM(kod_prodact))>3 and locate(CONCAT(" ",kod_prodact," "), " ' . addslashes($name) . ' ")>0 )
       )'))) {
            $gr = intval($tovar['gr']);
            echo " <i class='green'>gr6=" . $gr . "</i>" . '(' . ($gr_name = DB::GetName('gr', $gr)) . ')';
        }
    }
    echo "<br>supplier=".$supplier;
    $tov= ['name' => $name, 'description' => $description, 'price0' => $price0, 'valuta0' => $valuta0, 'valuta' => $valuta0,
        'supplier' => $supplier, 'priceu' => $priceU, 'gr' => $gr, 'sklad'=>$sklad, 'brand'=>$brand['id'], 'kod_prodact'=>$kod_prodact];
    if(!empty($data['volume']))$tov['kol']=$data['volume'];
    if($price)$tov['price']=$price;
    if($price1)$tov['price1']=$price1;
    $tov=(is_array($tovar)?array_merge($tovar,$tov):$tov);
    foreach(Tovar::$ar_info as $key) if(isset($data[$key]))$tov[$key]=$data[$key];
    Tovar::SaveTovar($tov); // >>>>>>>>>>>>>>>>>>>>>> СОХРАНЯЮ <<<<<<<<<<<<<<<<
    if(empty($tov['id'])) return false;
    if(!empty($tov['price']))echo ", розничная цена ".$tov['price'];
    if(!empty($tov['nac2'])) echo ", доп.наценка за товарную группу :" . $tov['nac2'] . "%";
    if(!empty($data['img'])){
        $link=$data['img'];
        echo "<br>Загружаю с ".$link;
        if(!($ext=Image::GetExt($link))){
            echo " - неверный тип файла: ".$ext."!";
        }else{
            Tovar::DelImg($tov);
            $img_p = $_SERVER['DOCUMENT_ROOT'] . path_tovar_image . 'p' . $tov[Tovar::img_name] . '.' . $ext;
            if(@copy($link, $img_p) !== true){
                echo " - ошибка загрузки изображения!";
            }else{
                if(in_array(Image::GetExt($ext), Image::$ext_img) &&
                    !Image::Resize($img_p, $img_p , imgBigSize)){
                    unlink($img_p); echo " - это не изображение!";
                }
            }
        }
    }

    /*if (($tov1=DB::Select('tovar', 'name="' . addslashes($name) . '" and id<>' . $tov['id'] )))
        echo "<br><b><font color='red'>Повторяющийся товар <a href='/price/?q=tovar" . $tov1['id']. ',tovar' . $tov['id'] . "' target=_blank>Найти</a></font></b>";*/

    DB::sql("INSERT INTO " . db_prefix . "supplier_link ( tovar, name, supplier, price0, ost, dat, kod_prodact, sklad, valuta0 ) ".
      "VALUES (".$tov['id'].", '" . addslashes($name_supplier) . "', " . $supplier . ", " . DB::float($price0) . ", '" . addslashes($ost) . "', '" . date("Y-m-d", $time1) . "', '" . addslashes($kod_prodact) . "', '" . $sklad . "', '" . $valuta0 . "') ".
      "ON DUPLICATE KEY UPDATE tovar=" . $tov['id'] . ",supplier=" . $supplier . ", price0=" . DB::float($price0) . ", ost='" . addslashes($ost) . "', dat='" . date("Y-m-d", $time1) . "', kod_prodact='" . addslashes($kod_prodact) . "', sklad='" . $sklad . "', valuta0='" . $valuta0 . "'");
    if($tov['supplier']==0)Tovar::SetFirstSupplier($tov['id']);
    $tov=new Tovar($tov['id']);
    print "<br>\n<b>".(empty($tovar)?"Добавил":"Обновил"). ' '.$tov->Aurl . ', цена=' . $price . '</b>/' . $price1 . '/' . $price2 . '/'. $price0 . '/';
    flush();
    return true;
}


function IsTranzit($tranzit){
    $tranzit=strtolower(trim($tranzit));
    if ($_REQUEST['ctranzit']=='_' && substr($tranzit,0,1)!='0' && $tranzit!='заказ' && $tranzit!='call' && $tranzit!='нет' && $tranzit!='-') return true;
    elseif(trim($_REQUEST['ctranzit'])=='')return intval($tranzit)>0;
    elseif(strpos( $tranzit, mb_strtolower($_REQUEST['ctranzit']) )!==false)return true;
    return false;
}

/**
 * @param int|array $tov
 * @return array|bool
 */
function FoundedTovar($tov){
    global $gr, $brand, $brand_old, $data;
    if(!is_array($tov))$tov=DB::Select('tovar',$tov);
    if(!$brand){
        $brand = intval($tov['brand']);
        if($tov['brand'])$brand=DB::Select('brand',intval($tov['brand']));
    }elseif($tov['brand'] && $brand['id'] <> intval($tov['brand'])){
        echo "<br>В базе ".$tov['name']."<br>\n<span class='red'>Бренд товара ".toHtml($brand['name']).'('.$brand['id'].') в базе '.toHtml(DB::GetName('brand',$tov['brand'])).'('.$tov['brand'].')!</span>';
        $brand=DB::Select('brand',intval($tov['brand'])); // беру из базы
        return $tov;
        //return false;
    }
    if(!empty($data['gr'])&&!empty($tov['gr']))Tovar::AddSinonimGr($data['gr'],intval($tov['gr']));
    $brand_old = $brand;
    return $tov;
}


include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";
