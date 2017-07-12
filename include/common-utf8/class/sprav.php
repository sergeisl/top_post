<?
/* редактирование страниц
if(User::is_admin())echo '<a title="Изменить" class="icon edit ajax" href="/adm/sprav.php?layer=pages&amp;form='.$data['id'].'&ret_path='.urlencode($_SERVER['REQUEST_URI']).'"></a>';
добавление новой страницы
if(User::is_admin())echo '<a title="Добавить" class="icon add ajax" href="/adm/sprav.php?layer=pages&amp;form&ret_path='.urlencode($_SERVER['REQUEST_URI']).'"></a>';

поля с именем:
url, если они пустые и режим добавления, автоматически заполняются транслитом от name
hour вводятся по шаблону чч:мм
parent - родитель в текущей таблице
совпадающие с названием существующих в текущей БД таблиц выводятся как select
возможно указание массива:
'services'=>['name'=>'Атрибуты', 'type'=>'bit', 'value'=>['1'=>' не выводить в меню', '2'=>'не выводить в карту сайта']],
'day'=>['name'=>'День недели', 'value'=>$_DayOfWeek],
'tovar'=>['name'=>'Услуга','sprav'=>'ИМЯ_БАЗЫ_СПРАВОЧНИКА','where'=>'type=0']
чтобы не выводить name, укажите 'name'=>'-'
если в строке адреса OnEdit указать &ajax - будет загружаться в модальном окне
Можно переопределить Sprav::$notShowInList=['onEdit','Order','keywords','description','content','parent','menu_name','title','url']; // список полей, которые не выводятся в таблице(списке)
в параметрах редактирования формы можно передать куда вернуться после сохранения в ret_path

Если у класса соответствующего имени таблицы есть метод Add, он будет вызван для добавления записи, Edit - для редактирования, а Save - для сохранения

Если поле content если пустое и есть файл /include/templates/" . $url . '.html' content будет загружен из него

Если есть /include/templates/КЛАСС_add.php он используется для добавления записей.

*/
class Sprav
{
    static public $sprav, $sprav_tbls, $sprav_field, $tbl, $list_sprav;
    // список полей, которые не выводятся в таблице(списке)
    static public $notShowInList = ['AddAction','onEdit', 'Order', 'keywords', 'description', 'content', 'parent', 'menu_name', 'title', 'url', 'price1', 'price2', 'price0', 'srok', 'ed', 'info', 'maxdiscount', 'ref'];
    /**
     * @param $sprav
     * @param $sprav_field
     */
    static function Init($sprav, $sprav_field)
    {
        self::$sprav_tbls = array_keys($sprav);
        self::$sprav = $sprav;
        if (isset($_REQUEST['soft'])) {
            Sprav::BuildSoft();
            exit;
        }

        if (isset($_REQUEST['tbl'])) {
            self::$tbl = $_REQUEST['tbl'];
            //if (!in_array(self::$tbl, self::$sprav_tbls)) Out::err('Неверная таблица:' . self::$tbl);
        } else {
            if (empty($_REQUEST['layer'])) {
                self::$tbl = self::$sprav_tbls[0];
            } elseif (Get::isKod($_REQUEST['layer']) && $_REQUEST['layer'] < count(self::$sprav_tbls)) {
                self::$tbl = self::$sprav_tbls[$_REQUEST['layer']];
            } elseif (isset(self::$sprav[$_REQUEST['layer']])) {
                self::$tbl = $_REQUEST['layer'];
            } else {
                self::$tbl = self::$sprav_tbls[0];
            }
        }
        if (isset($_REQUEST['tbl'])) {
            if (isset($_REQUEST['download'])) { // отдаю
                if (empty($_REQUEST['key']) || $_REQUEST['key'] != VK_secret) Out::err('Неверный ключ безопасности!');
                if (!DB::is_table($_REQUEST['tbl'])) Out::err('Нет таблицы '.$_REQUEST['tbl']);
                Out::ErrorAndExit(2);
                $rows = DB::Select2Array($_REQUEST['tbl']);
                if($rows&&is_dir($_SERVER['DOCUMENT_ROOT'] . '/images/' . self::$tbl . '/')) {
                    foreach ($rows as &$row) {
                        $files = glob($_SERVER['DOCUMENT_ROOT'] . '/images/' . self::$tbl . '/p' . $row['id'] . '[._]*');
                        if ($files) foreach ($files as $fil) {
                            $row['_img'][] = [basename($fil), filemtime($fil)];
                        }
                    }
                }
                Out::Api($rows, $_REQUEST['tbl']);
                exit;
            } else {
                if(self::Download(self::$tbl))Out::mes('', 'reload();');
                Out::err('');
            }
            exit;
        } elseif (isset($_GET['str2url'])) {
            Out::Api(['url' => str2url(urldecode($_GET['str2url']))]);
            exit;
        }

        if (!User::is_admin(!0)) {
            Out::error("Доступ ограничен!");
            Out::Location('/user/login.php');
        } elseif (isset($_GET['UpdateSoft'])) {
            self::Update();
            exit;
        }
        //$layer = (isset($_GET['layer']) ? (Get::isKod($_GET['layer']) ? intval($_GET['layer']) : array_search(urldecode($_GET['layer']), self::$sprav_tbls)) : '0');

        if (!empty($_GET['ord']) && $_GET['ord'] == 'image') unset($_GET['ord']);

        if (!(self::$list_sprav = Cache::_Get('list_sprav'))) {
            self::$list_sprav = DB::ListTables('name');
            self::$list_sprav[] = 'user';
            self::$list_sprav[] = 'manager';
            Cache::_Set('list_sprav', self::$list_sprav);
        }
        if (empty($sprav_field[self::$tbl]['name']) || $sprav_field[self::$tbl]['name'] != '-') {
            if (empty($sprav_field[self::$tbl]['name'])) $sprav_field[self::$tbl] = array_merge(['name' => 'Наименование'], $sprav_field[self::$tbl]);
        } else {
            unset($sprav_field[self::$tbl]['name']);
        }

        self::$sprav_field = $sprav_field;

//var_dump($list_sprav);

        /* для удаления используется общая функция из /api.php */
        if (isset($_POST['save'])) {
            self::Save();

        } elseif (isset($_GET['form'])) {//ajax- запрос формы добавления. Переданные GET-параметры при добавлении заполняются по умолчанию
            self::Form();
            exit;

        } elseif (Get::isApi()) {//ajax запрос вкладки
            self::layer(self::$tbl);
            exit;
        }

        if (isset($_GET['reload'])) Cache::_Clear();

        $h1 = $title = "Справочники";
        if (!empty($sprav[self::$tbl])) $title = $sprav[self::$tbl] . ', ' . $title;
        include_once $_SERVER['DOCUMENT_ROOT'] . "/include/head.php";
        echo "\n<h1>" . $h1 . "</h1>\n";
        $i = 0;
        foreach ($sprav as $item => $name)
            echo "\n<span class=\"layer" . (self::$tbl == $item ? ' act' : '') . "\" style='float:left;margin-right:2px' onclick='layer(" . $i++ . ")'>" . $sprav[$item] . "</span>";
        if (defined('SERVER_NAME')) {
            ?>
            <a href='?UpdateSoft' class="download icon confirm" title="Загрузить обновленное ПО с сервера"></a>
        <?
        } ?>
        <a href="/" class="layer">Главная</a>
        <br class='clear'>
        <?
        foreach ($sprav as $item => $name) {
            echo "\n<div id=\"layer" . $item . "\" class=\"layer" . (self::$tbl == $item ? ' act' : '') . "\">";
            if (self::$tbl == $item) self::layer($item);
            echo "</div>";
        }

        include_once $_SERVER['DOCUMENT_ROOT'] . "/include/tail.php";
    }

    /**
     * @param $tbl
     */
    static function layer($tbl)
    {
        ?>
        <div class="w100">
            <div class="fr">
                <? if (!empty(self::$sprav_field[$tbl]['name'])) { ?>
                    <form class="q" onsubmit="Search(this.q);return false;"><input type="text" autocomplete="off"
                                                                                   name="q"
                                                                                   onblur="Search(this)"
                                                                                   value="<?= (empty($_REQUEST['q']) ? '' : $_REQUEST['q']) ?>"
                                                                                   placeholder="строка для поиска">
                        <input type="submit" value=""></form>
                <? } ?>
                <a href='/api.php?tbl=<?= $tbl ?>&del_all' class="del icon confirm" title="Удалить всё"></a>
                <? if (defined('SERVER_NAME')) {
                    ?>
                    <a href='?tbl=<?= $tbl ?>' class="download icon confirm"
                       title="Загрузить и перезаписать всё с сервера"></a>
                    <?
                } ?>
                <a id='add' class="icon add ajax" href="?form&layer=<?= $tbl ?>" title="Добавить"></a>
                <?=(empty(self::$sprav_field[$tbl]['AddAction'])?'':self::$sprav_field[$tbl]['AddAction'])?>
            </div>
        </div>
        <?

        $bar = new kdg_bar(['perpage' => 20, 'tbl' => db_prefix . $tbl/*, 'ajax'=>true*/]);
        if (empty(self::$sprav_field[$tbl]['name'])) $bar->q='';

        if (empty($bar->ord) && !empty(self::$sprav_field[$tbl]['Order'])) $bar->ord = self::$sprav_field[$tbl]['Order'];
        if (!empty($bar->q)){
            if(strpos($bar->q,',')!==false) {
                foreach(['kod','kod_prodact', 'ean','id'] as $k)if(isset(self::$sprav_field[$tbl][$k])){
                    $bar->sql .= ($bar->sql ? ' or ' : ' WHERE ').' ('.$k.'="' . str_replace(',', '" or '.$k.'="', addslashes($bar->q)) . '")';
                }
            }else {
                $bar->sql = ' WHERE name LIKE "%' . addslashes($bar->q) . '%"' . (intval($bar->q) > 0 ? ' or id="' . intval($bar->q) . '"' : '') . (isset(self::$sprav_field[$tbl]['mail']) ? ' or mail="' . addslashes($bar->q) . '"' : '');
            }
        }
        if (isset(self::$sprav_field[$tbl])) foreach (self::$sprav_field[$tbl] as $key => $val) {
            if (!empty($_GET[$key])) {
                $bar->sql .= ($bar->sql ? ' and ' : ' WHERE ') . $key . '="' . addslashes($_GET[$key]) . '"';
            } elseif (!empty($bar->q) && in_array($key, ['kod_prodact', 'ean', 'fullname', 'keywords', 'description', 'content', 'menu_name', 'title', 'url', 'tel', 'phone'])) {
                $bar->sql .= ($bar->sql ? ' or ' : ' WHERE ') . $key . ' LIKE "%' . addslashes($bar->q) . '%"';
            }
        }
        $bar_out = $bar->out();
        /*$Columns=array();
        $result=mysql_query("SHOW FULL COLUMNS FROM `{$bar->tbl}`");
        while($col = mysql_fetch_row($result))$Columns[$col[0]]=$col; // индекс - имя, [1] - тип
    print_r($Columns);*/

        echo "
<div>всего: <b>{$bar->count}</b>. &nbsp; {$bar_out}</div>
 <table class=\"client-table\">
 <tr>
  <th onclick=\"Order('id')\">#" . ($bar->ord == 'id' ? ($bar->desc ? '&uarr;' : '&darr;') : '') . "</th>\n";
        if (isset(self::$sprav_field[$tbl])) foreach (self::$sprav_field[$tbl] as $key => $val) if (!in_array($key, self::$notShowInList)) echo "\n<th onclick=\"Order('" . $key . "')\">" .
            ($key == 'image' ? "Изображение" : str_replace('(обязательно)','',is_array($val) ? $val['name'] : $val)) . ($bar->ord == $key ? ($bar->desc ? '&uarr;' : '&darr;') : '') . "</th>";
        echo "\n<th>&nbsp;</th>
 </tr>
";
        $query = $bar->query();
        //echo DB::$query;
        $datas = [];
        while (($data = DB::fetch_assoc($query))) {
            $datas[] = $data;
            $edit = "href='" . (isset(self::$sprav_field[$tbl]['onEdit']) ? self::$sprav_field[$tbl]['onEdit'] : "?layer=" . $tbl . "&form=") . $data['id'] . "'" .
                (!isset(self::$sprav_field[$tbl]['onEdit']) || strpos(self::$sprav_field[$tbl]['onEdit'], 'ajax') !== false ? " onclick=\"return ajaxLoad('',this.href)\"" : "");
            echo "\n<tr id=\"id" . $data['id'] . "\" style=\"border-top:#9fbddd 1px solid;\" onmouseout=\"removeClass(this, 'row_over');\" onmouseover=\"addClass(this, 'row_over');\" ontouchstart=\"addClass(this, 'row_over');\">" .
                "\n<td>" . $data['id'] . "</td>";
            if (isset(self::$sprav_field[$tbl])) foreach (self::$sprav_field[$tbl] as $key => $val) {
                if (in_array($key, self::$notShowInList)) {

                } elseif (substr($key, 0, 3) == 'dat' || in_array($key,['pay_before','birthday'])) {
                    $data[$key] = valDate($data[$key]);
                    echo "\n<td class='left'>" . $data[$key] . "</td>";

                } elseif (in_array($key, self::$list_sprav) || !empty($val['sprav'])) {
                    $spr = (empty($val['sprav']) ? ($key == 'manager' ? 'user' : ($key == 'rajon' ? 'rajon_obl' : $key)) : $val['sprav']);
                    echo "\n<td class='left'>" . DB::GetName($spr, $data[$key]) . "</td>";

                } elseif ($key == 'name' && isset($data['url'])) {
                    echo "\n<td class='left'>" . BuildUrl($tbl, $data, 2) . "</td>";

                    /*        }elseif($key=='category'){
                                $obj=new OBJ($data);
                                echo "\n<td class='left'>".$obj->ShowCategory()."</td>";*/

                } elseif ($key == 'image') {
                    echo "<td class='left'>";
                    for ($i = 0; $i < 99; $i++) {
                        $fil = fb_dirfile . $tbl . '/p' . $data['id'] . ($i ? '_' . $i : '') . '.jpg';
                        if (is_file($_SERVER['DOCUMENT_ROOT'] . $fil)) {
                            echo Image::imgPreview($fil, ['size' => imgSmallSize, 'whithA' => $fil]);
                            /*$fil_s = fb_dirfile . $tbl . '/s' . $data['id'] . ($i ? '_' . $i : '') . '.jpg';
                            if (!is_file($_SERVER['DOCUMENT_ROOT'] . $fil_s)) Image::Resize($_SERVER['DOCUMENT_ROOT'] . $fil, $_SERVER['DOCUMENT_ROOT'] . $fil_s, imgSmallSize);
                            //echo Image::imgPreview($fil, ['size' => imgSmallSize/*, 'whithA' => $fil* /,'alt'=>(empty($data['name'])?'':addslashes($data['name']))]);
                            echo "<img src='" . ImgSrc($fil_s) . "' data-src='" . $fil . "' alt='" . (empty($data['name']) ? '' : addslashes($data['name'])) . "'>";*/
                        } else break;
                    }
                    echo "</td>";

                } elseif (is_array($val) && !empty($val['type']) && $val['type'] == 'bit') { // bit, нумерация с '0'
                    $tmp = Convert::Bit2Array($data[$key]);
                    $out = '';
                    foreach ($tmp as $k => $v) $out .= ($out ? ', ' : '') . (isset($val['value'][$k]) ? $val['value'][$k] : '-');
                    echo "\n<td class='left'>" . $out . "</td>";

                } elseif (is_array($val)) { // select
                    echo "\n<td class='left'>" . (isset($val['value'][$data[$key]]) ? $val['value'][$data[$key]] : '-') . "</td>";

                } elseif (strlen($data[$key]) > 500) {
                    echo "\n<td class='left'>" . subText($data[$key], 500) . "</td>";

                } else
                    echo "\n<td class='left'>" . $data[$key] . "</td>";
            }
            echo "\n<td class=\"edit-del\">
	<a href='/api.php?tbl=" . $tbl . "&del=" . $data['id'] . "' class=\"icon del confirm\" style=\"margin-right: 0\" title=\"Удалить\"></a>
	<a " . $edit . " class=\"icon edit\" title=\"Изменить\"></a>" .
                "<a href='/api.php?log&amp;tbl=" . $tbl . "&amp;id=" . $data['id'] . "' class=\"icon comment ajax\" title=\"Протокол\"></a>" .
                (User::is_admin() && $tbl == 'user' ?
                    "<a href='/user/zakaz.php?id=" . $data['id'] . "' class=\"icon-symbol ajax\" style=\"margin-right:0\" title=\"Заказы пользователя\">&#128105;</a>" .
                    "<a href='/?reowner=" . $data['id'] . "' class=\"icon open\" style=\"margin-right:0\" title=\"Войти под пользователем\"" .
                    " onclick=\"return confirm('Все ваши действия будут осуществляться от " . $data['name'] . ". Действует во всех ранее открытых вкладках – будьте очень осторожны! Для возврата – нажмите «Выход». Входим?');\"></a>" : '') .
                "</td>
  </tr>";
        }
        if (!empty($bar->q) && $bar->count > 1 && $bar->count <= 3 && $tbl == 'user') $bar_out = '<a class="button ajax confirm" href="/user/api.php?union=' . $datas[0]['id'] . '&from=' . $datas[1]['id'] . '" title="Объединить">Объединить</a>';
        echo <<<HTML
</table>
<div>всего: <b>{$bar->count}</b>. &nbsp; {$bar_out}</div>
HTML;
        global $DomLoad;
        $DomLoad = (empty($DomLoad) ? "" : $DomLoad . "\n") . "if(document.location.hash=='#add')setTimeout('getObj(\"add\").click()',200);";

    }

    /** Выбор из справочника для sprav
     * @param $tbl
     * @param string $where
     * @param int $id
     * @param string $add
     * @param string $fld - если имя таблицы не соответствует имени поля
     * @return string
     */
    static function ChoiceSprav($tbl, $where = '', $id = 0, $add = '', $fld = '')
    {
        global $sprav;
        if (!$fld) $fld = $tbl;
        if ($tbl == 'manager') {
            $tbl = 'user';
            if (empty($where)) $where = 'adm >=' . uADM_OLD_WORKER;
        }
        $ret = '<div style="display:table;">';
        $ret .= <<<HTML
<select name="{$fld}" id="{$fld}" style="display:table-cell;"{$add}>
    <option disabled="" value="-1">Выберите</option>
    <option value='0'>Любой</option>
HTML;
        $ar_row = DB::Select2Array($tbl, ($where ? $where : '1') . ' ORDER BY name');
        foreach ($ar_row as $row) $ret .= "\n<option value=\"" . $row['id'] . "\"" . ($id > 0 && $id == $row['id'] ? " selected" : "") . ">" . $row['name'] . "</option>";
        $ret .= "</select>";
        if (($i = array_search($tbl, $sprav)) !== false) {
            $ret .= '<div style="display:table-cell;width:24px;padding:0 7px"><a class="icon add" title="Добавить" href="?layer=' . $tbl . '#add" onclick="return ajaxLoad(\'\',\'sprav.php?layer=' . $tbl . '&form\')"></a></div>';
        }
        return $ret . "</div>";
    }

    static function Save()
    {
        if (!empty(self::$sprav_field[self::$tbl]['name']) && empty($_POST['name'])) {
            Out::error("Пустое название не добавил!");
        } else {
            // убираю теги из тех полей, в которых они не должны быть никогда
            foreach (self::$sprav_field[self::$tbl] as $key => $val) {
                if (in_array($key, ['seo_description', 'inflect', 'keywords']) && isset($_POST[$key])) $_POST[$key] = strip_tags($_POST[$key]);
                elseif (in_array($key, ['value']) && isset($_POST[$key])) $_POST[$key] = str_replace('\n', "\n", $_POST[$key]);
                elseif (in_array($key, ['content']) && isset($_POST[$key])) $_POST[$key] = str_replace(['<!--?=','?-->'], ['<?=','?>'], $_POST[$key]); // визуальный редактор убирает PHP-вставки
            }
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id) {
                if(is_class(self::$tbl) && is_callable([self::$tbl, "Save"])){ call_user_func_array([self::$tbl, "Save"],[$_POST]); return;}

                //isPrivDel(self::$tbl,$_POST['id']);
                $add = "";
                //!!!!!!!!!!if(!empty(self::$sprav_field[self::$tbl]['url']) && isset($_POST['url'])&&empty($_POST['url'])){$_POST['url']=str2url($_POST['name']);}
                if (isset(self::$sprav_field[self::$tbl])) foreach (self::$sprav_field[self::$tbl] as $key => $val)
                    if (!in_array($key, ['image', 'AddAction', 'onEdit', 'Order', 'date0', 'category']) || ($key == 'date0' && User::is_admin())) {
                        if (substr($key, 0, 3) == 'dat') $_POST[$key] = date('Y-m-d', (empty($_POST[$key]) ? strtotime('now') : strtotime($_POST[$key])));
                        elseif (substr($key, 0, 4) == 'time') $_POST[$key] = date('Y-m-d i:h:s', (empty($_POST[$key]) ? strtotime('now') : strtotime($_POST[$key])));
                        elseif (isset($_POST[$key]) && is_array($_POST[$key])) $_POST[$key] = Convert::Array2Bit($_POST[$key]); // битовое поле
                        elseif (in_array($key, ['tel', 'phone'])) $_POST[$key] = addslashes(User::NormalTel($_POST[$key]));
                        if (isset($_POST[$key])) $_POST[$key] = trim($_POST[$key]);
                        $add .= ",`" . $key . "`='" . (isset($_POST[$key]) ? addslashes($_POST[$key]) : "") . "'";
                    }
                //var_dump($_POST); echo "<br>".$add;
                DB::log(self::$tbl, $id, '', '', $_POST);
                DB::sql("UPDATE `" . db_prefix . self::$tbl . "` SET " . substr($add, 1) . " WHERE id='" . $id . "'");
                unset($GLOBALS[self::$tbl . '_cash[' . $id . ']']); // сбросить кеш

            } else { // новая запись

                if(is_class(self::$tbl) && is_callable([self::$tbl, "Add"])){ call_user_func_array([self::$tbl, "Add"],[$_POST]); return;}
                $autourl = false;
                $add1 = "";
                $add2 = "";
                if (isset(self::$sprav_field[self::$tbl])) foreach (self::$sprav_field[self::$tbl] as $key => $val) if (!in_array($key, ['image', 'name', 'AddAction', 'onEdit', 'Order', 'category'])) {
                    if ($key == 'url') {
                        $autourl = true;
                        if (isset($_POST['url']) && empty($_POST['url'])) continue;
                    }
                    $add1 .= ",`" . $key . "`";
                    if ($key == 'user' && empty($_POST[$key])) $_POST[$key] = User::id();
                    if ($key == 'date0') $add2 .= ",'" . date('Y-m-d', (empty($_POST[$key]) || !User::is_admin() ? time() : strtotime($_POST[$key]))) . "'";
                    elseif (substr($key, 0, 3) == 'dat') $add2 .= ",'" . date('Y-m-d', (isset($_POST[$key]) ? strtotime($_POST[$key]) : time())) . "'";
                    elseif (substr($key, 0, 4) == 'time') $add2 .= ",'" . date('Y-m-d i:h:s', (isset($_POST[$key]) ? strtotime($_POST[$key]) : time())) . "'";
                    elseif (isset($_POST[$key]) && is_array($_POST[$key])) $add2 .= ",'" . Convert::Array2Bit($_POST[$key]) . "'"; // битовое поле
                    elseif (in_array($key, ['tel', 'phone'])) $add2 .= ",'" . addslashes(User::NormalTel($_POST[$key])) . "'";
                    else $add2 .= ",'" . (isset($_POST[$key]) ? addslashes(trim($_POST[$key])) : "") . "'";
                }
                if (empty(self::$sprav_field[self::$tbl]['name']) || strpos($_POST['name'], "\n") === false) { // одиночная вставка
                    if ($autourl && isset($_POST['url']) && empty($_POST['url'])) {
                        $add1 .= ",`url`";
                        $add2 .= ",'" . addslashes(str2url($_POST['name'])) . "'";
                    }
                    if (!empty(self::$sprav_field[self::$tbl]['name'])) {
                        $add1 .= ",`name`";
                        $add2 .= ",'" . addslashes($_POST['name']) . "'";
                    }
                    DB::sql("INSERT IGNORE INTO `" . db_prefix . self::$tbl . "` (" . substr($add1, 1) . ") VALUES ( " . substr($add2, 1) . ")");
                } else { // множественное добавление
                    $q = explode("\n", $_POST['name']);
                    if ($autourl && isset($_POST['url']) && empty($_POST['url'])) $add1 .= ",`url`";
                    $add = "";
                    foreach ($q as $v) if (($v = trim($v, "\n\r\t, "))) {
                        $add .= ",( '" . addslashes($v) . "'" . $add2 .
                            (($autourl && isset($_POST['url']) && empty($_POST['url'])) ? ",'" . addslashes(str2url($v)) . "'" : '') .
                            ")";
                    }
                    DB::sql("INSERT IGNORE INTO `" . db_prefix . self::$tbl . "` (`name`" . $add1 . ") VALUES " . substr($add, 1));
                }
                $id = DB::id();
                //if (!$id) Out::err("Не добавил:" . DB::$query);
            }

            //if(isset(self::$sprav_field[self::$tbl]['category']))OBJ::SaveCategory($_POST, $id);
            $add = '';

            if ($id) {// переношу картинки
                $add .= File::FileSave(self::$tbl, $id);
            }
            if (DB::affected_rows() > 0 || $add) {
                Out::message("Сохранил!" . (Get::DEBUG() ? "\n" . $add : ''));
            } else Out::error("Не сохранил!" . (Get::DEBUG() ? "\n" . DB::$query . ', ' . $add : ''));//{DB::close();die("Не сохранил!");}
        }
        if (!empty($_SESSION['ret_path'])) {
            Out::LocationRef();
        }
    }

    static function Form()
    {
        $id = intval($_GET['form']);
        if ($id > 0) {
            if (($data = DB::Select(self::$tbl, $id))) {
                if (isset($data['content']) && empty($data['content']) && !empty($data['url'])) {
                    $url = BuildUrl(self::$tbl, $data);
                    if (file_exists($f = $_SERVER['DOCUMENT_ROOT'] . "/include/templates/" . ($url == '/index.php' ? 'index' : trim($url, '/')) . '.html')) {
                        $data['content'] = file_get_contents($f);
                    }
                }
                $data['save'] = "Сохранить";
            } else Out::err("Нет такого!");
            if(file_exists($f = $_SERVER['DOCUMENT_ROOT'] . "/include/templates/". self::$tbl . '_edit.php')){
                include_once $f;
                return;
            }
        } else {
            $data['save'] = "Добавить";
            $data['id'] = '';
            if (isset(self::$sprav_field[self::$tbl])) foreach (self::$sprav_field[self::$tbl] as $key => $val) if (!in_array($key, ['image', 'AddAction', 'onEdit', 'Order'])) $data[$key] = (isset($_GET[$key]) ? urldecode($_GET[$key]) : '');
            if(file_exists($f = $_SERVER['DOCUMENT_ROOT'] . "/include/templates/". self::$tbl . '_add.php')){
                include_once $f;
                return;
            }
        }
        // todo поменять на поле формы !!!
        if (!empty($_REQUEST['ret_path'])) $_SESSION['ret_path'] = "http://" . $_SERVER['HTTP_HOST'] . (substr($_REQUEST['ret_path'], 0, 1) == '/' ? '' : '/') . $_REQUEST['ret_path'];

        //foreach($data as $key => $value)if(strpos($value,'"')!==false&&strpos($value,'\'')!==false)$data[$key]=str_replace('"','\'',$value);
        foreach ($data as $key => $value) if (!in_array($key, ['info', 'content', 'comment', 'description', 'seo_description', 'inflect'])) $data[$key] = str_replace('"', '\'', $value);
        ?>
        <h2><?= self::$sprav[self::$tbl] ?></h2>
        <form name="<?= self::$tbl ?>" id="<?= self::$tbl ?>" class="sprav"
              action="<?= (Get::Referer('/adm/sprav.php') ? Get::Referer() : '/adm/sprav.php?layer=' . self::$tbl) ?>"
              method="POST"
              enctype="multipart/form-data"
              ondragenter="return _frm.drop(event);"
              ondragover="return _frm.drop(event);"
              ondragleave="return _frm.drop(event);"
              ondrop="return _frm.drop(event);" onsubmit="return SendForm('',this);">
            <input type="hidden" name="save">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">
            <? if (!empty(self::$sprav_field[self::$tbl]['name'])) {
                ?>
                <label for="name"><?= self::$sprav_field[self::$tbl]['name'] ?></label>
                <input name="name" class="multi" type="text" maxlength="128" value="<?= $data['name'] ?>" required>
                <textarea rows="3" class='hide multi' placeholder="по одному в строке"></textarea>
                <a class="icon add" title="Множественное добавление" href="#"
                   onclick="n1=getElementsByClass('multi',this.parentNode,'INPUT');n1=n1[0];n2=getElementsByClass('multi',this.parentNode,'TEXTAREA');n2=n2[0];
           if(n1.name==''){
                removeClass(this,'minus');addClass(this,'plus');n2.name='';n1.name='name';n1.required=!0;n2.required=!1;removeClass(n1,'hide');addClass(n2,'hide');
           }else{
                removeClass(this,'plus');addClass(this,'minus');n1.name='';n2.name='name';n1.required=!1;n2.required=!0;removeClass(n2,'hide');addClass(n1,'hide');
           }
           return false;"></a>
                <br class="clear">
                <?
            }
            $f_load__Calendar = false;
            $f_load__Mask = false;
            if (isset(self::$sprav_field[self::$tbl])) foreach (self::$sprav_field[self::$tbl] as $key => $val) {
                if (in_array($key, self::$list_sprav) || !empty($val['sprav'])) {
                    $spr = empty($val['sprav']) ? $key : $val['sprav'];
                    $where = '';
                    if (is_array($val)) {
                        if (isset($val['where'])) $where = $val['where'];
                        if (isset($val['name'])) $val = $val['name'];
                    }
                    if ($spr == 'rajon_city' && !empty($data['city'])) $where = 'city="' . intval($data['city']) . '"';
                    if ($spr == 'city' && array_search('rajon_city', self::$sprav) !== false && isset(self::$sprav_field[self::$tbl]['rajon_city'])) $add = ' onchange="ajaxLoad(this,\'/api.php?load&city=\'+getValue(this.form.city))"';
                    else $add = '';
                    echo "\n<label for=\"" . $key . "\">" . $val . "</label>" . self::ChoiceSprav($spr, $where, $data[$key], $add, $key) . "<br>";

                } elseif (substr($key, 0, 3) == 'dat' || in_array($key,['pay_before','birthday'])) {
                    $data[$key] = valDate($data[$key]);
                    /*if($data[$key]=='1970-01-01'||!$data[$key])$data[$key]='  .  .  ';
                    else $data[$key]=date('d.m.Y', strtotime($data[$key]));*/
                    echo "\n<label for=\"" . $key . "\">" . $val . "</label><input type=\"text\" name=\"" . $key . "\" size=\"10\" value=\"" . $data[$key] . "\" class='calendar' data-yearfrom='-70'  data-yearto='10' >";
                    $f_load__Calendar = true;

                } elseif ($key == 'parent') { // родитель в текущей таблице
                    $spr = DB::Select2Array(self::$tbl, '1', 'id&name'); // parent=0 or parent=id
                    echo "\n<label for=\"" . $key . "\">" . $val . "</label>\n<select name='" . $key . "'>\n<option value='0'>нет</option>";
                    foreach ($spr as $k => $v) echo "\n<option value=\"" . $k . "\"" . ($data[$key] == $k ? " selected" : "") . ">" . $v . "</option>";
                    echo "\n<option value=\"" . $data['parent'] . "\"" . ($data[$key] == $data['id'] && $data['parent'] > 0 ? " selected" : "") . ">" . $data['name'] . "</option>";
                    echo "\n</select><br>";
                    /*        }elseif($key=='category'){
                                $obj=new OBJ($data);
                                echo "\n<br><label>".$val."</label><span>".$obj->ChoiceCategory()."</span><br>";*/

                } elseif (in_array($key, ['info', 'content', 'comment','description'])) {
                    echo "\n<label for=\"" . $key . "\">" . $val .
                        "<span class=\"visual\" onclick='editon(this,\"" . $key . "\")'>Визуальный редактор</span>" . "<span class=\"dotted fr\" onclick='fb_full(\"" . $key . "\")'>распахнуть</span>" .
                        "</label><textarea id=\"" . $key . "\" name=\"" . $key . "\" class=\"ckeditor\">" . toHtml($data[$key]) . "</textarea>";

                } elseif ($key == 'image') {
                    Image::blockLoadImage($data['id'], ['path' => fb_dirfile . self::$tbl . '/p', 'logo' => $val]);

                } elseif (in_array($key, ['AddAction', 'onEdit', 'Order'])) {

                } elseif (in_array($key, ['seo_description', 'inflect']) || strpos($data[$key], "\n") !== false) {
                    echo "\n<label for=\"" . $key . "\" style=\"margin-top:10px;width:50%;\">" . $val .
                        "</label><textarea id=\"" . $key . "\" name=\"" . $key . "\" class=''>" . toHtml($data[$key]) . "</textarea>";

                } elseif (in_array($key, ['name'])) {

                } elseif ($key == 'hour') {
                    echo "\n<label for=\"" . $key . "\">" . $val . "</label>\n<input class=\"mask\" type=\"text\" id=\"" . $key . "\" name=\"" . $key . "\" " .
                        "value=\"" . toHtml($data[$key]) . "\" pattern=\"\\d{2}:{0,1}\\d{2}\" placeholder=\"__:__\"><br>";
                    $f_load__Mask = true;

                } elseif ($key == 'url') {
                    echo "\n<label for=\"" . $key . "\">" . $val . "<span class=\"dotted fr\" onclick='ajaxLoad(\"" . $key . "\",\"?json&str2url=\"+getValue(parentNode.parentNode.name))'>подставить</span>" .
                        "</label>\n<input type=\"text\" id=\"" . $key . "\" name=\"" . $key . "\" value=\"" . toHtml($data[$key]) . "\"><br>";

                } elseif (is_array($val)) {       // 'price_for'=>['name'=>"Цена за:",'value'=>[1=>'за час', 2=>'за занятие', 3=>'за курс']]
                    if (is_array($val['value'])) {
                        if (isset($val['type']) && $val['type'] == 'bit') { // битовое
                            if (!empty($val['name'])) echo "<label>\n" . $val['name'] . "</label>";
                            $tmp = Convert::Bit2Array($data[$key]); // нумерация с 0
                            foreach ($val['value'] as $k => $v) echo "\n\t<label><input type=\"checkbox\" name=\"" . $key . "[" . $k . "]\" value=\"1\" " . (empty($tmp[$k]) ? '' : ' checked') . "> " . $v . "</label>";
                        } else {
                            echo "\n<label for=\"" . $key . "\">" . $val['name'] . "</label>";
                            echo "\n<select name='" . $key . "'>\n";
                            foreach ($val['value'] as $k => $v) echo "\n<option value=\"" . $k . "\"" . ($data[$key] == $k ? " selected" : "") . ">" . $v . "</option>";
                            echo "\n</select><br>";
                        }
                    } else var_dump($val); // косяк!
                } else
                    echo "\n<label for=\"" . $key . "\">" . $val . "</label>\n<input type=\"text\" id=\"" . $key . "\" name=\"" . $key . "\" value=\"" . toHtml($data[$key]) . "\"><br>";
            }
            ?>
            <div class="tac">
                <? if ($id > 0) {
                    ?>
                    <input type="reset" value="Копировать" onclick="add(this.form);hide(this);return true;">
                    <?
                } ?>
                <? /*<input type="submit" id="save" value="<?=$data['save']?>" onclick="editSave();" > иначе по Enter отправляется */
                ?>
                <input type="button" class="button submit" value="<?= $data['save'] ?>"
                       onclick="editSave();this.form.submit();">
            </div>
            <div
                id="info"<?= (empty($_SESSION['message']) ? ' class="hide"' : '') ?>><?= (empty($_SESSION['message']) ? '' : $_SESSION['message']) ?></div>
        </form>
        <?
        //if ($f_load__Calendar) echo "\n<script src=\"//htmlweb.dev/calendar_kdg_utf8.js\" async defer></script>";
        if ($f_load__Mask) echo "\n<script>oef();</script>";
        $_SESSION['message'] = '';
    }

    static public $ext_for_update = ['php', 'css', '.js', '.htm', '.html'];

    /**
     * обновляет ПО на текущей машине
     */
    static function Update()
    {
        // запрашиваю с сервера список файлов
        list($headers, $body, $info) = ReadUrl::ReadWithHeader(SERVER_NAME . '/adm/sprav.php?soft&json', ['key' => VK_secret], ['cache' => 0, 'timeout' => 50]);
        if (empty($body)) die('Нет ответа от сервера ' . SERVER_NAME . ' !' . var_dump($info));
        if (!empty($info['http_code']) && $info['http_code'] != 200 || empty($body)) {
            echo "<div style='background:lightgreen'>";
            var_dump($info, $body);
            echo "</div>";
            exit;
        }
        $files = js_decode($body);
        $list = []; // формирую список файлов, требующих обновления
        foreach ($files as $fil) {
            if (!in_array(File::GetExt($fil[0]), self::$ext_for_update)) {
                SendAdminMail('hack soft', var_export($files, !0));
                die('Ошибка файла в запросе!');
            }
            //echo "<br>".$fil[0];
            if (!is_file($_SERVER['DOCUMENT_ROOT'] . $fil[0]) ||
                ( /*filesize($_SERVER['DOCUMENT_ROOT'].$fil[0])!=$fil[1] || */
                    filemtime($_SERVER['DOCUMENT_ROOT'] . $fil[0]) < $fil[2])
            ) {
                $list[] = $fil[0];
            } elseif (filemtime($_SERVER['DOCUMENT_ROOT'] . $fil[0]) > $fil[2] && filesize($_SERVER['DOCUMENT_ROOT'] . $fil[0]) != $fil[1]) {
                echo "<br>" . $fil[0] . " на сервере файл старее: " . date('d.m.y H:i:s', filemtime($_SERVER['DOCUMENT_ROOT'] . $fil[0])) . '~' . date('d.m.y  H:i:s', $fil[2]);
            }
        }
        if (!$list) die('ПО актуально!');
        // отправляю список файлов, которые нужно обновить
        list($headers, $body, $info) = ReadUrl::ReadWithHeader(SERVER_NAME . '/adm/sprav.php?json', ['soft' => js_encode($list), 'key' => VK_secret], ['cache' => 0, 'timeout' => 200]);
        if (!empty($body) && !empty($info['http_code']) && $info['http_code'] == 200 && preg_match('/Content-Disposition: attachment; filename=([^.]*)/i', $headers)) {
            $f_install = false;
            $f = fb_tmpdir . 'soft_' . rand(1000, 9999) . '.zip';
            if (is_file($f)) unlink($f);
            file_put_contents($f, $body);
            $zip = new ZipArchive;
            if ($zip->open($f) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    // echo "<br>:" . $entry;
                    if (in_array(File::GetExt($entry), self::$ext_for_update)) {
                        $fil = $_SERVER['DOCUMENT_ROOT'] . '/' . $entry;
                        if (is_file($fil)) echo "<br>Обновляю файл " . $entry . ': ' . date('d.m.y H:i:s', filemtime($fil));
                        else echo "<br>Новый файл " . $entry . ': ';
                        //continue;
                        $bak = $fil . '~';
                        if (is_file($bak) && filemtime($bak) > strtotime('-1 day')) {
                            unlink($bak);
                            rename($fil, $bak);
                        } elseif (is_file($fil) && !is_file($bak)) {
                            rename($fil, $bak);
                        } elseif (is_file($fil)) {
                            unlink($fil);
                        } elseif (!is_dir(dirname($fil))) {
                            echo "<br>Создаю папку " . dirname($entry);
                            mkdir(dirname($fil), 0777, !0);
                        }
                        if ($zip->extractTo($_SERVER['DOCUMENT_ROOT'], $entry)) {
                            $ar = $zip->statIndex($i);
                            touch($fil, $ar['mtime']); // установить дату/время файла по исходному
                            echo '~' . date('d.m.y  H:i:s', $ar['mtime']);
                            if (substr($fil, -12) == '/install.php') $f_install = $fil;
                        } else {
                            Out::error("Не смог распаковать " . $entry);
                            if (is_file($bak)) rename($bak, $fil);
                        }
                    }
                }
                if ($f_install) include_once $f_install;
            } else Out::error("Архив поврежден!");
            if (empty($_SESSION['error'])) @unlink($f); // удаляю исходный архив
            Out::ErrorAndExit(0, 1);
        } else die("НЕ обновил! \n<br><b>" . $body . "</b><br>" . var_export($headers, !0) . "<br><br>" . var_export($info, !0));

    }

    /** если ?soft формирует список файлов
     *  если soft=[список файлов], - создает архив с запрошенными файлами
     */
    static function BuildSoft()
    {
        if (empty($_REQUEST['key']) || $_REQUEST['key'] != VK_secret) Out::err('Неверный ключ безопасности!');
        $_REQUEST['nocache'] = true;
        if (empty($_REQUEST['soft'])) {
            global $l, $out;
            // формирую список всех новых файлов
            function outTree($c)
            {
                global $l, $out;
                $a = scandir($c);
                //var_dump($c,$a);
                foreach ($a as $f) {
                    if (in_array($f, ['.', '..', '.idea', '.git', '.gitignore', 'ckeditor', 'PHPExcel'])) continue;
                    $f = $c . '/' . $f;
                    if (is_dir($f)) {
                        outTree($f);
                        continue;
                    }
                    //echo '<br>'.$f;
                    if (in_array(File::GetExt($f), Sprav::$ext_for_update) && filemtime($f) > strtotime('-60 days')) {
                        $out[] = [substr($f, $l), filesize($f), filemtime($f)];
                        //echo ' - '.substr($f, $l).', '.filesize($f).', '.filemtime($f);
                    }//else echo '- нет!';
                }
            }

            $c = $_SERVER['DOCUMENT_ROOT'];
            $l = strlen($c);
            $out = [];
            outTree($c);
            Out::Api($out);
        } else {
            $files = js_decode($_REQUEST['soft']);
            $filename = fb_tmpdir . 'soft_' . rand(1000, 9999) . '.zip';
            if (is_file($filename)) unlink($filename);
            $zip = new ZipArchive;
            if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) die('Ошибка создания архива ' . $filename);
            foreach ($files as $f) {
                //echo "<br>Добавляю ".$f;
                /*if(strpos($f,'/',2)!==false){
                    $d=substr(dirname($f),1);
                    echo " в ".$d ;
                    if($zip->addEmptyDir($d)===false) die('Ошибка добавления ' . $d);
                }*/
                if ($zip->addFile($_SERVER['DOCUMENT_ROOT'] . $f, substr($f, 1)) === FALSE) die('Ошибка добавления ' . $f);
            }
            $zip->close();
            //flush();
            if (@filesize($filename) < 5) die('<br>НЕ создал архив ' . $filename . '!!');
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Pragma: hack");
            header("Content-Type: application/octet-stream");
            header("Content-Length: " . (string)(filesize($filename)));
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header("Content-Transfer-Encoding: binary");
            @readfile($filename) or add_error('Ошибка чтения ' . basename($filename));
        }
    }

    static function Download($tbl)
    {
        set_time_limit(300);
        list($headers, $body1, $info) = ReadUrl::ReadWithHeader("http://" . SERVER_NAME . "/adm/sprav.php?download&json&tbl=" .$tbl, ['key' => VK_secret], ['cache' => 0, 'timeout' => 60, 'convert' => charset]);
        $body = js_decode($body1);
        if (isset($body['error'])) return Out::error($body['error']);
        if (isset($body['message'])) return Out::error($body['message']);
        if (count($body) > 0) {
            DB::sql('TRUNCATE TABLE ' . db_prefix . $tbl);
            $cnt = 0;
            foreach ($body as &$row) {
                //echo "<br>".implode(', ',$row);
                foreach ($row as $key => $val) {
                    if (substr($key, 0, 3) == 'dat') $row[$key] = date('Y-m-d', strtotime($val));
                    elseif (substr($key, 0, 4) == 'time') $_POST[$key] = date('Y-m-d i:h:s', strtotime($val));
                }

                if (!empty($row['_img'])) foreach ($row['_img'] as $fil) { // перетащить новые картинки
                    $fil['name'] = '/images/' . $tbl . '/' . $fil[0];
                    if (!is_file($fil['name']) || $fil[1] > filemtime($_SERVER['DOCUMENT_ROOT'] . $fil['name'])) {
                        list($headers, $body1, $info) = ReadUrl::ReadWithHeader("http://" . SERVER_NAME . $fil['name'], '', ['cache' => 0, 'timeout' => 20]);
                        if (strlen($body1) > 200) {
                            file_put_contents($_SERVER['DOCUMENT_ROOT'] . $fil['name'], $body1);
                            $cnt++;
                        }
                    }
                }
                unset($row['_img']);
                DB::insert($tbl, $row);
            }
            return !Out::message('Загружено ' . count($body) . ' записей и ' . $cnt . ' картинок');
        } else {
            return Out::error('Пришел пустой ответ!' . var_export($info, !0) . "\n" . var_dump($body1, !0));
        }
    }
}
