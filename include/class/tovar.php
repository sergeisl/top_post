<?
/**
 * @property-read String name Название товара
 * @property-read String normal_name Название товара без NEW
 * @property-read String id код товара
 * @property-read String kod_prodact Короткий код(Артикул товара) == kod код производителя
 * @property-read String ean EAN-13
 * @method string ed(integer $kol) склоняет единицу измерени
 * @property-read String show_name Полное имя с ценой и свойствами
 * @property-read string best_before date
 * @property-read integer cab номера кабинок доступных для оказания услуги
 * @property-read string dat дата добавления
 * @property-read string date_upd дата обновления
 * @property-read float volume Обем, кв.м
 * @property-read float weight Вес, кг
 * @property-read integer in_package шт в упаковке
 * @property-read integer garant Гарантия, мес
 * @property-read integer min_zakaz минимальное кол-во для заказа
 * @property-read integer ost остаток на сейчас
 * @property-read integer price розничная цена товара
 * @property-read integer price1 мелкооптовая цена товара
 * @property-read integer price2 оптовая цена товара
 * @property-read integer price_opt рассчетная мелкооптовая цена товара
 * @property-read integer price_old цена без скидки
 * @property-read integer discount скидка в %
 * @property-read integer discount_expires дата окончания скидки
 * @property-read integer show_ost остаток по всем магазинам для вывода в графе "Наличие"
 * @property-read integer gr возвращает основную category = gr "0-товар, 1-услуга, 2-абонемент, 3-расходка", tTYPE_*
 * @property-read array category {id1=>1,id2=>1,...} для преобразования используйте array_keys
 * @property-read String collection
 * @property-read String collection_name
 * @property-read String brand
 * @property-read String brand_name
 * @property-read String brand_url
 * @property-read String sklad
 * @property-read String sklad_name
 * @property-read String supplier
 * @property-read String supplier_name
 * @property-read String kol объем, кол-во шт в упаковке
 * @property-read String kol_name объем+ед.измерения объема
 * @property-read String ed единица измерения шт, мл, мешок
 * @property-read String url относительный url товара
 * @property-read String Aurl url товара собранный в анкор для сайта
 * @property-read String Murl url товара для вставки в письмо
 * @property-read Tovar child ссылка на базовый товар
 * @property-read integer tovar id базового товара или 0
 * @property-read String img картинка товара
 * @property-read String imgBig картинка товара размером imgBigSize
 * @property-read String imgMedium картинка товара размером imgMediumSize
 * @property-read String imgSmall картинка товара размером imgSmallSize
 * @property-read Boolean is_img картинка товара доступна?
 * @property-read integer vitrina todo 0-обычный,1-витрина,2-скрытый стили  vitrinaN
 * @property-read integer type "0-товар, 1-услуга, 2-абонемент, 3-расходка", tTYPE_*
 * @property-read String description  описание товара/услуги
 * @property-read array row массив с информацией о товаре как из базы.
 */
class Tovar implements ArrayAccess{
    const db_prefix = db_prefix;
    const img_name = 'kod_prodact'; // 'kod' имя картинки формировать из кода или из id товара
    const tbl_alias=''; // category_alias поддержка таблицы синонимов категорий, если пусто - не использовать
    const tbl_prixod='prixod'; // 'prixod' - поддержка таблицы приходов, если пусто - не использовать
    const valuta=false; // все в одной валюте
    public $Seo_url=SEO;
    public $message;
    static public $ar_info = ['best_before', 'cab', 'volume', 'weight', 'garant', 'in_package', 'min_zakaz', 'seo_keywords', 'seo_description']; // переменные, которые сохраняются в поле info БД
    static public $ar_float = ['price', 'price0', 'price1', 'price2', 'priceu', 'volume', 'weight', 'garant', 'in_package', 'min_zakaz']; // числовые поля, запятую в которых нужно преобразовать в точку перед записью в БД
    static public $_sklad_name = [1 => 'есть в магазине', 2 => 'есть на складе в Ростове', 3 => 'срок поставки 5-10 дней', 4 => 'уточните наличие у менеджера', 9 => 'позиция недоступна'];
    static public $ext_load = ['jpeg', 'jpg', 'gif', 'png', "csv", "txt", "ppt", "pptx", "pptm", "pps", "ppsx", "pdf", "doc", "odt", "ods", "xls", "xlt", "docx", "docm", "dot", "dotx", "xlsx", "rtf", "pot", "potx"];

    static public $ar_type = [/*0 => "Косметика",*/ 1 => "Услуги", 2 => "Абонементы"/*, 3 => "Расходка"*/];
    static public $unic_category_list=[3, 13, 4, 5, 10, 22, 21, 2, 20];            /*22 	Активатор 	активатор

        21 	Бронзатор 	bronz,бронзатор,окрашивани
        13 	Волосы 	волос,шампунь
        4 	Для душа 	душ, мыло, скраб, Scrub, Body Wash
        1 	Для загара 	для загара
        3 	Защита от солнца 	spf
        10 	Лицо 	лицо,лица
        12 	Ноги 	для ног,Legs
        2 	После загара 	после загара, After Sun
        20 	Разогревающий 	разогревающий,покалывани,hot,микроциркуляцию,согревающий,тингл,Blush Factor
        5 	Сопутствующие товары 	тапочки,шапочки,стикини,напиток
        11 	Тело 	тело*/
    static public $repeat_uslug_list=[4=>6];             // список повторных услуг со скидкой: 4 -  автозагар, 16-автозагар повторно


    /**
     * @var array|null
     */
    private $data = [];

    public function offsetSet($key, $value) {
        //$this->data[$key] = $value;
        $this->__set($key,$value);
    }
    public function offsetUnset($key) {
        unset($this->data[$key]);
    }
    public function offsetGet($key) {
        return $this->data[$key];
    }
    public function offsetExists($key) {
        return isset($this->data[$key]);
    }

    /** вызывается при обращении к неопределенному свойству
     * @param $property
     * @return array|null
     */
    function __get($property)
    {
        if (isset($this->data[$property])) return $this->data[$property];

        elseif (empty($this->data['id'])) {
            return null;

        } elseif (in_array($property, self::$ar_info)) {
            if (isset($this->data['info'][$property])) return $this->data['info'][$property];
            else return '';

        } elseif ($property == 'gr') { // сюда попаду если в таблице товаров нет основной категории в виде отдельного поля
            $gr=DB::Select("category_link","tovar='" . addslashes($this->data['id']) . "'");
            $this->data['gr']=($gr?$gr:0);

        } elseif ($property == 'category') {
            if (!isset($this->data['category'])) {
                $this->data['category'] = [];
                if(!empty($this->data['gr']))$this->data['category'][$this->data['gr']] = 1;
                $query = DB::sql("SELECT * FROM `" . self::db_prefix . "category_link` WHERE tovar='" . addslashes($this->data['id']) . "' ORDER BY category");
                while ($row = DB::fetch_assoc($query)){
                    $this->data['category'][$row['category']] = 1; // в этом же формате возвращает $_POST
                    if(!isset($this->data['gr']))$this->data['gr']=$row['category'];
                }
            }
            return $this->data['category'];

        } elseif ($property == 'unic_category') {
            foreach (self::$unic_category_list as $category)
                if (array_key_exists($category, $this->category)) return $category;

        } elseif ($property == 'show_name') {
            return str_replace("'", "`", ($this->data['type'] == tTYPE_USLUGA || !$this->data['brand'] || cmp($this->data['name'], $this->data['brand']) ? "" : DB::GetName('brand', $this->data['brand']) . " ") .
                $this->data['name'] . ($this->data['kol'] > 1 ? " " . ($this->data['kol'] == intval($this->data['kol']) ? intval($this->data['kol']) : $this->data['kol']) . $this->data['ed'] : '') .
                ' - ' . $this->data['price'] . "руб.");

        } elseif ($property == 'normal_name') {
            return trim(str_replace(['NEW ' . date("Y"), 'NEW'], '', $this->data['name']), '- !.');

        } elseif ($property == 'show_ost') {
            $query = DB::sql("SELECT * FROM `" . self::db_prefix . "tovar_shop` WHERE tovar='" . addslashes($this->data['id']) . "' ORDER BY shop");
            $out = '';
            while(($row=DB::fetch_assoc($query))){
                // todo if(User::OPT!!!)return ucfirst(self::$sklad_name[($this->data['sklad']>0?$this->data['sklad']:SKLAD_OLD)]);
                $shop = DB::Select('shop', $row['shop']);
                $out .= ",<br> <a" . (User::is_admin() ? '' : " style='white-space:nowrap'") . " href='" . $shop['url'] . "' onclick='return !window.open(this.href)'>" . $shop['name'] . (User::is_admin() ? " - " . $row['ost'] . "шт." . ($row['price'] == $this->price ? '' : ", " . $row['price'] . "руб.") : "") . "</a>";
            }
            if ($out) return "<label>Наличие:</label> " . substr($out, 6);
            elseif ($this->ost == -99) return "<label>Наличие:</label> не доступно для заказа";
            else return "<label>Срок поставки:</label> 7-14 дней";

        } elseif ($property == 'collection_name') {
            return $this->data['collection_name'] = DB::GetName('collection', $this->data['collection']);

        } elseif ($property == 'brand_name') {
            return $this->data['brand_name'] = DB::GetName('brand', $this->data['brand']);
        }elseif($property=='brand_url'){
            $brand=DB::Select('brand',$this->brand);
            return '<a href="/shop/?brand='.$this->brand.'" title="'.$brand['title'].'">'.$brand['name'].'</a>';
        }elseif($property=='sklad_name'){
            return (empty(Tovar::$_sklad_name[$this->data['sklad']])?Tovar::$_sklad_name[SKLAD_OLD]:Tovar::$_sklad_name[$this->data['sklad']]);
        }elseif($property=='supplier_name'){
            return $this->data['supplier_name']=DB::GetName('supplier',$this->data['supplier']);
        } elseif ($property == 'kol_name') {
            return ($this->data['kol'] == intval($this->data['kol']) ? intval($this->data['kol']) : $this->data['kol']) . ($this->data['ed'] ? $this->data['ed'] : ($this->data['type'] ? '' : 'мл'));

        } elseif ($property == 'child') {
            return $this->data['child'] = ($this->data['tovar'] ? new Tovar($this->data['tovar']) : null);

        } elseif ($property == 'price_opt') {
            return round($this->data['price0'] * (100 + tOPT_PROC) / 100, ($this->data['type'] == tTYPE_RASX ? 1 : 0), PHP_ROUND_HALF_UP); // округляю до целого рубля

        }elseif($property=='price_old'){
            return round($this->data['priceu'],0,PHP_ROUND_HALF_UP); // округляю до целого рубля
        }elseif($property=='discount_expires'){
            return strtotime("+7 day");
        }elseif($property=='discount'){
            return ( $this->priceu==0 || $this->price >= $this->priceu) ? 0 : round(($this->priceu-$this->price)/$this->priceu,0);

        } elseif ($property == 'url') {
            if($this->Seo_url){
                if(empty($this->data['seo_url'])|| strlen($this->data['seo_url']) < 3){
                    $this->data['seo_url'] = str2url($this->data['name'].
                        ($this->data['kol']>1?" ".($this->data['kol']==intval($this->data['kol'])?intval($this->data['kol']):$this->data['kol']):''));
                    DB::sql("UPDATE `" . db_prefix . "tovar` SET `seo_url`='" . addslashes($this->data['seo_url']) . "' WHERE id='" . intval($this->data['id']) . "'");
                }
                return "/shop/" . $this->data['seo_url'];    //$this->data['url']='/tovar/'.$this->data['kod_prodact'];
            }
            return "/shop/?id=".$this->data['id'];    //$this->data['url']='/tovar/'.$this->data['kod_prodact'];
            //return "/shop/tovar".$this->data['id'];    //$this->data['url']='/tovar/'.$this->data['kod_prodact'];

        } elseif ($property == 'Aurl') {
            return "<a href='".$this->url."' class='modal'>".toHtml($this->show_name)."</a>";
        }elseif($property=='Iurl'){
            return "<a href='".$this->url."'  title='".$this->show_name."' class='modal'><img src='".$this->imgMedium[0]."' alt='".$this->name."'></a>";
        } elseif ($property == 'Murl') {
            return "<a href='".$this->url."'>".$this->show_name."</a>";
        }elseif($property=='is_img'){    // именно картинка
            return !!Image::is_file(path_tovar_image.'p'.$this->data[self::img_name],true);
            //$fil=path_tovar_image.'p'.$this->data[self::img_name].'.jpg';
            //return is_file($_SERVER['DOCUMENT_ROOT'].$fil);
        } elseif ($property == 'imgBig') { //images\tovar\p1082-01.jpg
            $img = [];
            for ($i = 0; $i < 99; $i++) {
                $fil = path_tovar_image . 'p' . $this->data[self::img_name] . ($i ? ('_' . $i) : '') . '.jpg';
                if (!is_file($_SERVER['DOCUMENT_ROOT'] . $fil)) break;
                $img[] = ImgSrc($fil);
            }
            if (empty($img)) $img[] = '/images/noimg.gif';
            return $img;

        } elseif ($property == 'imgSmall') { //images\tovar\s1082-01.jpg
            return self::ImgArray('s', $this->data[self::img_name], 'imgSmallSize', imgSmallSize);

        } elseif ($property == 'imgMedium') { //images\tovar\m1082-01.jpg
            return self::ImgArray('m', $this->data[self::img_name], 'imgMediumSize', imgMediumSize);

            /*        }elseif($property=='img'){
                        $fil=path_tovar_image.'s'.$this->data[self::img_name].'.jpg';
                        if($fil && is_file($_SERVER['DOCUMENT_ROOT'].$fil)){
                            return "\n<a href='".path_tovar_image.'p'.$this->data[self::img_name].'.jpg'."' onclick='return openwind(this)'><img src='".$fil."' class='left' height='40'></a>";
                        }else{
                            return "<div class='box left small c' style='height:40px;width:50px' onclick=\"return ajaxLoad('','api.php?form_img=".$this->data[self::img_name]."');\">Нет<br>картинки</div>";
                        }*/
        } elseif ($property == 'row') {
            $row = $this->data;
            if (isset($row['info']) && is_array($row['info'])) $row['info'] = js_encode($row['info']);
            return $row;

        } elseif (!isset($this->data[$property]) || is_null($this->data[$property])) {
            return null;

            /*}elseif($property=='ost'){
                echo "<br>"; var_dump($this);
    */
        } else die("Нет свойства Tovar::" . $property);
    }

    /** вызывается, когда неопределенному свойству присваивается значение
     * @param $property
     * @param $value
     */
    function __set($property, $value)
    {
        if(in_array($property, ['name','price1','price','price0'])){
            DB::sql('UPDATE '.self::db_prefix.'tovar SET '.$property.'="'.$value.'" WHERE id='.$this->id);
            $this->data[$property]=$value;
        }elseif(in_array($property,self::$ar_info)){
            $this->data[$property]=$value;
            self::WriteInfo($this->data);
        }else die("Не обработки сохранения Tovar::".$property);

        DB::CacheClear('tovar',$this->id);  // сбрасываю хеш
    }

    /** вызывается при обращении к неопределенному методу
     * @param $name = 'ed'
     * @param $arr
     * @return string
     */
    function __call($name, $arr)
    {
        if ($name == 'ed') {
            if ($this->ed == 'минут')
                return num2word($arr[0], ["минута", "минуты", "минут"]);
            elseif ($this->ed == 'занятие')
                return num2word($arr[0], ["занятие", "занятия", "занятий"]);
            else
                return $this->ed;
        } else die("Нет метода Tovar::" . $name);

    }
    public function __construct($tovar = null){
        //echo '<br>Tovar::';var_dump($tovar);
        if (is_array($tovar)) {
            $this->data = $tovar;
            unset($this->data['category']); // если я передал лишние поля - удалить их
        } elseif ($tovar > 0) {
            $this->data = DB::Select("tovar", intval($tovar)); //$this->data=$this->GetTovar($tovar,true);
        }

        if($this->data==null){error("Ошибка в коде товара ".var_export($tovar,!0)); return null;}

        if($this->type==tTYPE_ABON){
            if(stripos($this->data['name'],'Абонемент')===false)$this->data['name']='Абонемент '.$this->data['name'];
            if(empty($this->data['description']))$this->data['description']=($this->data['price']/$this->data['kol']).'руб/'.$this->data['ed'];
        }
        //$this->data=self::RecalcPrice($this->data);
        //echo '<br>Tovar->';var_dump($this->data); exit;
        self::cPrice($this->data);
        //if(empty($this->data['price'])&&!empty($this->data['price0']))$this->data['price']=self::CalcPrice($this->data,1);

        //echo "<br><br>";var_dump($this->data);
        if (!isset($this->data['info'])) {
            $t = $this->GetTovar($tovar['id']);
            $this->data['info'] = $t['info'];
        }
        if (!is_array($this->data['info'])) {
            if (isset($this->data['info']) && $this->data['info'])
                $this->data['info'] = js_decode($this->data['info']);
        }
    }

    public function __destruct()
    {

    }

    public function __isset($name)
    {
        $a = $this->__get($name);
        return !is_null($a);
    }

    static function _GetVar($tovar, $var)
    {
        $t = new Tovar($tovar);
        //echo "<br><br>".var_dump($t);
        return $t->$var;
    }

    static function GetTovar($tovar, $notChild = false)
    {
        if (!$tovar) return null;
        elseif (is_array($tovar)) {
            $tovar0 = $tovar;
        } else {
            if (!($tovar0 = DB::Select('tovar', intval($tovar)))) return null;
        }
        if ($tovar0['tovar'] && !$notChild) {
            $tovar = DB::Select('tovar',intval($tovar0['tovar']));
            if ($tovar) {
                $tovar['ost'] = $tovar0['ost'];
                $tovar['parent'] = $tovar0;
                $tovar['srok'] = $tovar0['srok'];
                $tovar['name0'] = $tovar0['name'];
                $tovar['type0'] = $tovar0['type'];
                if(isset($tovar['maxdiscount']))$tovar['maxdiscount'] = min(($tovar0['maxdiscount'] ? $tovar0['maxdiscount'] : 100), ($tovar['maxdiscount'] ? $tovar['maxdiscount'] : 100));
            } else {
                Out::error("Ошибка в базе товаров: нет товара по ссылке " . $tovar0['tovar'] . " !");
                $tovar = $tovar0;
            }
        } else $tovar = $tovar0;
        if (!empty($tovar['brand']['name'])) {
            $brand_name = $tovar['brand']['name'];
        } elseif (Get::isKod($tovar['brand'])) {
            $brand_name = DB::GetName('brand', $tovar['brand']);
        } else {
            $brand_name = $tovar['brand'];
        }
        $tovar['show_name'] = str_replace("'", "`", ($tovar['type'] == 1 || cmp($tovar['name'], $brand_name) ? "" : $brand_name . " ") . $tovar['name'] . ($tovar['kol'] > 1 ? " " . ($tovar['kol'] == intval($tovar['kol']) ? intval($tovar['kol']) : $tovar['kol']) . $tovar['ed'] : '') . ' - ' . $tovar['price'] . "руб.");
        $tovar['name'] = str_replace("'", "`", $tovar['name']);
        //$row['brand']." ".$row['name']." - ".($row['kol']==intval($row['kol'])?intval($row['kol']):$row['kol']).$row['ed'].' - '.$row['price']."руб.
        //if(isset($tovar['info'])&&$tovar['info'])$tovar=array_merge($tovar,(array)json_decode($tovar['info']));
        if (isset($tovar['info']) && $tovar['info'] && !is_array($tovar['info'])) {
            $tovar['info'] = js_decode($tovar['info']);
        }
        foreach ($tovar as $key => $value) $tovar[$key] = str_replace('"', "'", $value);
        return $tovar;
    }

    static function DelAll($where){
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` ".($where? "WHERE ".$where: "" ) );
        $count=0;
        while($data=DB::fetch_assoc($query)){
            if(Tovar::Del($data['id']))$count++;
        }
        return $count;
    }

    /** удаление товара
     * @param $id
     * @return bool
     */
    static function Del($id){
        // todo проверить что по этому товару нет движений
        $id = intval($id);
        if (($row = DB::Select('tovar', "tovar='" . $id . "'"))) {
            Out::error('Для данного товара есть абонемент tovar->' . $row['id'] . '. Сначала удалите абонемент!');
            return false;
        }
        if (($row = DB::Select('kart', "tovar='" . $id . "'"))) {
            Out::error('Для данного товара есть абонемент kart->' . $row['id'] . '. Сначала удалите абонемент!');
            return false;
        }
        if (($row = DB::Select('zakaz2', "tovar='" . $id . "'"))) {
            Out::error('Для данного товара есть продажа sale2->' . $row['id'] . '. Сначала удалите продажи!');
            return false;
        }
        if (($row = DB::Select('prixod', "tovar='" . $id . "'"))) {
            Out::error('Для данного товара есть приход prixod->' . $row['id'] . '. Сначала удалите приходы!');
            return false;
        }
        if (($row = DB::Select('counters', "tovar='" . $id . "'"))) {
            Out::error('Для данного товара есть счетчики counters->' . $row['id'] . '. Сначала удалите счетчики!');
            return false;
        }
        $tov = self::GetTovar($id, true);
        self::DelImg($tov);

// обнуляю, но неудаляю записи в заказах
        DB::sql("UPDATE ".self::db_prefix."zakaz2 SET `tovar`=0 WHERE tovar=".$id);

        DB::log('tovar', $id, 'удаление');
        DB::Delete("tovar", $id);
        return (DB::affected_rows() > 0);
    }

    /** удалить картинки
     * @param $tov
     */
    static function DelImg($tov)
    {
        $ret=false;
        if (!is_array($tov)) $tov = self::GetTovar($tov);
        for($i=0;$i<99;$i++){
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil)){unlink($fil);$ret=true;}else break;
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil)){unlink($fil);$ret=true;}
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'m'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil)){unlink($fil);$ret=true;}
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.diz';  if(is_file($fil)){unlink($fil);$ret=true;}
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.txt';  if(is_file($fil)){unlink($fil);$ret=true;}
        }
        return $ret;
    }

    /** сброс счетчиков для группы или для списка групп
     * @param int|array $gr
     */
    static function ResetCounter($gr){
        if(is_array($gr)){
            foreach($gr as $item)self::ResetCounter($item);
        }else{
            // ищу текущую группу для определения у неё родителя
            $row=DB::Select("category",intval($gr));
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=NULL WHERE id=".$gr);
            if( $row && !is_null($row['cnt']) && $row['parent'] ) {
                // т.к. счетчик и так был нулевой, то у родителя обновлять не надо
                self::ResetCounter($row['parent']); // сбрасываю счетчик в родительской группе
            }
        }
    }

    /** объединяет два товара в $id_new
     * @param integer $id_old старый товар
     * @param integer $id_new новый товар
     * @return bool
     */
    static function Union($id_old, $id_new)
    {
        DB::sql("UPDATE `" . db_prefix . "tovar` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::sql("UPDATE `" . db_prefix . "kart` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::sql("UPDATE `" . db_prefix . "zakaz2` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::sql("UPDATE `" . db_prefix . "prixod` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::sql("UPDATE `" . db_prefix . "counters` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::sql("UPDATE IGNORE `" . db_prefix . "category_link` SET tovar='" . $id_new . "' WHERE tovar='" . $id_old . "'");
        DB::Delete("category_link", "tovar='" . $id_old . "'"); // удаляю проигнорированные
        //DB::sql("UPDATE `".db_prefix."zakaz2` SET tovar='".$id_new."' WHERE tovar='".$id_old."'");

        $tov_old = self::GetTovar($id_old, true);
        $tov_new = self::GetTovar($id_new, true);
        $fil_old = path_tovar_image . 'p' . $tov_old[self::img_name] . '.jpg';
        $fil_new = path_tovar_image . 'p' . $tov_new[self::img_name] . '.jpg';
        if (!is_file($_SERVER['DOCUMENT_ROOT'] . $fil_new) && is_file($_SERVER['DOCUMENT_ROOT'] . $fil_old)) rename($fil_old, $fil_new);
        self::DelImg($tov_old);

        DB::log('tovar', $id_new, 'Объедиенение с ' . $id_old);
        DB::Delete("tovar", $id_old);
        return (DB::affected_rows() > 0);
    }

    static function Cennik($data)
    {
        /*return "<div style='float:left;width:56mm;padding:4px;border:1px solid #ddd;page-break-inside:avoid;'>".
        "<div style='height:23mm;width:100%;overflow:hidden;font-size:13px'>".$data['name'].
        (isset($_GET['description'])?" <span style='font-size:10px;color:#666'>".$data['description']."</span>":"")."</div>".
        "<div style='text-align:center;margin:0 auto'>Цена: <b style='font-size:22px'>".$data['price']."</b>".(strlen($data['price'])<3?" ":"")."руб.</div>".
        "<div style='font-size:11px;width:100%;text-align:right;vertical-align:sub;'>____________ ИП Исаева Л.В.</div>".
        "<div style='font-size:12px;float:right;width:14mm;text-align:right'>".($data['kol']==0?'&nbsp;':($data['kol']==intval($data['kol'])?intval($data['kol']):$data['kol']).($data['ed']?$data['ed']:"мл"))."</div>".
        "<div style='font-size:12px;float:left;width:14mm;text-align:left'>".$data['kod'].($data['kod']&&$data['ean']?"/":"").$data['ean']."</div>".
        "<div style='margin:0 auto;font-size:12px;width:12mm;text-align:center'>".date("d.m.y",strtotime("+1 day"))."</div>".
        "</div>";*/
        return "<div style='float:left;width:40mm;padding:4px;border:1px solid #ddd;page-break-inside:avoid;'>" .
        "<div style='height:15mm;width:100%;overflow:hidden;font-size:13px'>" . $data['name'] .
        (isset($_GET['description']) ? " <span style='font-size:10px;color:#666'>" . $data['description'] . "</span>" : "") . "</div>" .
        "<div style='text-align:center;margin:0 auto'>Цена: <b style='font-size:22px'>" . $data['price'] . "</b>" . (strlen($data['price']) < 3 ? " " : "") . "руб.</div>" .
        "<div style='font-size:11px;width:100%;text-align:right;vertical-align:sub;'>________ ИП Чернухина Я.О.</div>" .
/*        "<div style='font-size:11px;width:100%;text-align:right;vertical-align:sub;'>________ ИП Колесников Д.Г.</div>" .*/
        "<div style='font-size:12px;float:right;width:14mm;text-align:right'>" . ($data['kol'] == 0 ? '&nbsp;' : ($data['kol'] == intval($data['kol']) ? intval($data['kol']) : $data['kol']) . ($data['ed'] ? $data['ed'] : "мл")) . "</div>" .
        "<div style='font-size:12px;float:left;width:14mm;text-align:left'>" . $data['kod_prodact'] . ($data['kod_prodact'] && $data['ean'] ? "/" : "") . $data['ean'] . "</div>" .
        "<div style='margin:0 auto;font-size:12px;width:12mm;text-align:center'>" . date("d.m.y", strtotime("+1 day")) . "</div>" .
        "</div>";
    }

    /** по наименованию возвращает id бренда, учитывает ошибки написания, если не находит - добавляет
     * @param string $name - наименование товара или бренд
     * @param bool $add =true-строгий поиск, если не найдено - добавляю, = false- искать по вхождению
     * @return array|null
     */
    static function GetBrand($name, $add=false){
        $name = trim($name);
        if (empty($name)) return null;
        if(in_array(mb_strtolower($name),['noname','Другие']))return 0;
        if($brand=DB::Select('brand', "name='".addslashes($name)."'"))return $brand;

        // todo учитывать ошибки написания

        // определяет бренд по строке наименования
        if(!$add){// пробую найти по вхождению
            $q=DB::sql('SELECT *,length(name) as lenname from '.db_prefix.'brand WHERE " '.addslashes(quotemeta(strtolower($name))).' " REGEXP CONCAT("[^a-zа-я0-9]",lower(name),"[^a-zа-я0-9]") or (length(title)>3 and " '.addslashes(quotemeta(strtolower($name))).' " REGEXP CONCAT("[^a-zа-я0-9]",lower(title),"[^a-zа-я0-9/]")) ORDER BY lenname DESC');
            if ($row=DB::fetch_assoc($q)) return $row;
            $br = self::brandList();
            foreach ($br as $brand) if (stripos($name, $brand['name'])) return $brand;
            return 0;
        }elseif(isset($_REQUEST['test'])){
            return ['id'=>0,'name'=>'NEW:'.$name];
        }else{
        DB::sql("INSERT INTO `" . self::db_prefix . "brand`	(`name`) VALUES ('" . addslashes($name) . "')");
            return ['id'=>DB::id(),'name'=>$name];
        }
    }

    /**
     * Возвращает массив брендов
     * @param string $format = '' - возвращает массив, 'select' - возвращает блок <select>, 'id' - id=>row
     * @param int $act - выбранный пункт select по умолчанию
     * @return array
     */
    static function brandList($format='', $act=0){
        static $ar=[], $sort=[], $ar_id=[];
        if(empty($ar)||isset($_GET['reload'])){ // кеширую
            global $_cache;
            if (empty($_cache)) $_cache = new Cache();
            $cache_keyBrandList='BrandList';
            $ar = unserialize($_cache->get($cache_keyBrandList));
            if(empty($ar)||isset($_GET['reload'])){
                $ar = [];
                $res = [];
                $query = DB::sql("SELECT brand,count(*) as `count` FROM `" . self::db_prefix . "tovar` GROUP BY brand");
                while (($row = DB::fetch_assoc($query))) $res[$row['brand']] = $row['count'];
            $query = DB::sql("SELECT * FROM `" . self::db_prefix . "brand` ORDER BY LENGTH(name) DESC"); // сортирую по возрастанию длины названия бренда
                while (($row = DB::fetch_assoc($query))) {
                $row['url'] = '/brand/' . str2url($row['name']);
                    $row['count'] = (empty($res[$row['id']]) ? 0 : $res[$row['id']]); //DB::Count('tovar','brand='.$row['id']);
                    unset($res[$row['id']]);
                $ar[] = $row;
            }
                foreach($res as $brand=>$count){ // добавляю потерянные и noname
                    $ar[]=['id'=>$brand,'name'=>($brand?'??:'.$brand:'NoName'), 'count'=>$count];
                }
                $_cache->set($cache_keyBrandList, serialize($ar));
            }
        }
        if(!empty($format) && empty($sort)) {
            $sort = $ar;
            usort($sort, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }
        if($format=='select'){
           /* $sort = [];
            foreach($ar as $key => $row) $sort[$key] = $row['name'];
            array_multisort($sort, SORT_ASC, $ar);*/
            $str='';
            foreach($sort as &$row) $str.='<option value="'.$row['id'].'"'.($act==$row['id']?' selected':'').'>'.$row['name'].'</option>';
            return '<select name="brand"><option value="0"'.($act==0?' selected':'').'>нет</option>'.$str.'</select>';

        }elseif($format=='id'){
            if(empty($ar_id)){
                foreach($sort as &$row) $ar_id[$row['id']]=$row;
            }
            return $ar_id;
        }
        return $ar;
    }

    /** Если переданная строка является названием бренда, то возвращает массив с информацией о бренде или null
     * @param $str
     * @return null|array
     */
    static function is_brand($str)
    {
        $str = mb_strtolower($str);
        $br = self::brandList();
        foreach ($br as $brand) if (mb_strtolower($brand['name']) == $str) return $brand;
        return null;
    }


    /** по наименованию возвращает id группы, учитывает ошибки написания, если не находит и todo $add - добавляет
     * @param $name
     * @param string $tov_name
     * @return array|null
     */
    static function GetGr($name, $tov_name=''){
        //if(self::$ar_gr) return array_search($name,self::$ar_gr);
        $name = trim($name);
        if (empty($name)) return null;
        if(($row=DB::Select('category', "name='".addslashes($name)."'")))return $row;
        if(self::tbl_alias){
            if (($row = DB::Select(self::tbl_alias, 'name="' . addslashes($name) . '"' .
                ($tov_name ? ' or "' . addslashes($tov_name) . '" LIKE shablon AND ' . 'NOT "' . addslashes($tov_name) . '" LIKE notshablon' : '')))){
                return DB::Select('category', intval($row['gr']));
        }
        }
        if ((strlen($name) > 10 ) && ($row = DB::Select('category','(locate("' . addslashes($name) . '", name)>0)')))return $row;
        // todo учитывает ошибки написания
        return null;
    }

    /** добавляет синоним категории
     * @param String $name
     * @param Integer $gr
     * @return bool
     */
    static function AddSinonimGr($name, $gr)
    {
        if(DB::Select('category', "name='".addslashes($name)."'"))return true; // не добавляю само название
        if(self::tbl_alias)return true;
        if(($row=DB::Select(self::tbl_alias, "name='".addslashes($name)."'"))){
            if($row['gr']!=$gr){echo "<br>\n<span class='red'>".$name."</span> - синоним группы ".DB::GetName('category',$row['gr'].'!'); return false;}
        } else {
            DB::sql("INSERT INTO `".self::db_prefix.self::tbl_alias."` (`gr`,`name`) VALUES ('".addslashes($gr)."', '".addslashes($name)."')");
        }
        return true;
    }

    /** по наименованию и коду бренда возвращает id коллекции, если не находит - добавляет
     * @param $name
     * @param int|array $brand
     * @return int|string
     */
    static function GetCollection($name, &$brand)
    {
        $_brand = (isset($brand['id']) ? $brand['id'] : $brand);
        if (strlen($name = trim($name)) < 7 && preg_match('/^[0-9]+$/', $name)) { // Brown
            if (($row = DB::Select('collection', intval($name)))) {
                if (isset($row['brand'])) {
                    $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
                }
                return $row['id'];
            }
            return 0;
        }
        if( ($_brand>0) && ($row=DB::Select('collection',"name='".addslashes($name)."' and brand='".addslashes($brand)."'"))){
            if (isset($row['brand'])) {
                $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
            }
            return $row['id'];
        }elseif(($row=DB::Select('collection',"name='".addslashes($name)."' and brand=0"))){
            // теперь ищу без учета бренда
            if ($_brand > 0) DB::sql("UPDATE " . self::db_prefix . "collection SET `brand`=" . $_brand . " WHERE id=" . $row['id']);  // прописываю бренд
            if (isset($row['brand'])) {
                $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
            }
            return $row['id'];
        } else {
            $query=DB::sql("SELECT * FROM `".self::db_prefix."collection` WHERE name='".addslashes($name)."'");
            if ((DB::num_rows($query) == 1) && ($row = DB::fetch_assoc($query))) { // прописываю бренд
                if (isset($row['brand'])) {
                    $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
                }
                return $row['id'];
            } elseif (DB::num_rows($query) > 1) {
                echo '<b class="red">Много колекций по запросу '.$name."</b><br>Бренды: ";
                while ($row = DB::fetch_assoc($query)) {
                    echo DB::GetName('brand', $row['brand']) . '(' . $row['brand'] . '), ';
                    if (empty($id)) $id = $row['id'];
                }
                return $id;
            }
        }
        if (isset($_REQUEST['test'])) return 'NEW:' . $name;
        // todo учитывает ошибки написания
        DB::sql("INSERT INTO `" . self::db_prefix . "collection` (`name`,`brand`) VALUES ('" . addslashes($name) . "', '" . addslashes($_brand) . "')");
        return DB::id();
    }

    public function GetCategory()
    {
        return self::_GetCategory($this->category);
    }

    /** возвращет input-ы для всех категорий с пометкой выбранных в параметре
     * @param array $category массив категорий [id_категории]=1
     * @return string для form
     */
    static function _GetCategory($category = [])
    {
        $s = '';
        $query = DB::sql("SELECT * FROM `" . self::db_prefix . "category` ORDER BY id");
        while ($row = DB::fetch_assoc($query))
            $s .= "<label class=\"category\"><input type=\"checkbox\" name=\"category[" . $row['id'] . "]\" value=\"1\"" . (isset($category[$row['id']]) ? ' checked' : '') . "><span>" . $row['name'] . "</span></label>";
        return $s;
    }

    /** простановка категорий автоматически по ключевым словам
     *
     */
    static function SetCategory()
    {
        // Сопутствующие товары
        $query = DB::sql("SELECT * FROM `" . db_prefix . "tovar` WHERE type=" . tTYPE_RASX . " ORDER BY id");
        while ($tovar = DB::fetch_assoc($query)) DB::sql("INSERT IGNORE INTO `" . db_prefix . "category_link` (`tovar`,`category`) VALUES ('" . $tovar['id'] . "', '5')");

        $query0 = DB::sql("SELECT * FROM `" . db_prefix . "category`");
        while ($category = DB::fetch_assoc($query0)) {
            $ar=explode(',',$category['keywords']);
            echo "\n<h2>" . $category['name'] . "</h2>";
            // автоматически проставляю категорию на основании keyword
            $s = '';
            foreach($ar as $val)$s.="or name LIKE '%".addslashes(trim($val))."%' or description LIKE '%".addslashes(trim($val))."%'";
            $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE ".substr($s,3)." ORDER BY id");
            echo "<br>\n".DB::$query;
            while ($tovar = DB::fetch_assoc($query)) {
                DB::sql("INSERT IGNORE INTO `" . db_prefix . "category_link` (`tovar`,`category`) VALUES ('" . $tovar['id'] . "', '" . $category['id'] . "')");
                //if(DB::affected_rows()>0)
                echo "<br>\n" . self::_GetVar($tovar, 'Aurl');
            }
        }
        // todo если бронзатор убрать после загара
    }

    /** Возвращает массив категорий
     * @param int $root - от какого корня (ранее из $kateg_name) =-1 все категории
     * @param array $options  format = '' - возвращает массив, 'in' - возвращает список id всех дочек через',', 'select' - возвращает блок <select>, 'option' - возвращает список <option>
                              act - выбранный пункт select по умолчанию
                              add - дополнение в тег select
                              noempty - только в которых есть товары
     *                        //sort - name, parent&name
     * @return array|string
     */
    static function grList($root=-1, $options=[]){// $format='',$act=0, $add=''){
        static $ar = [];
        if(empty($options['format'])){
            if($root==-1){
                if(empty($ar[$root])) {
                    $ar0 = [];
                    $query = DB::sql("SELECT * FROM `" . self::db_prefix . "category` GROUP BY `parent` ORDER BY id");
            while ($row = DB::fetch_assoc($query)) {
                        if (!isset($ar[$row['parent']])) self::grList($row['parent']);
                        $ar0 = array_merge($ar0, $ar[$row['parent']]);
                    }
                    $ar[$root] = $ar0;
                }
                return $ar[$root];
            }

            if(!isset($ar[$root])) {
                $ar[$root] = DB::Select2Array('category', 'parent=' . intval($root).' ORDER BY name');
                //usort($ar[$root], function ($a, $b) {return strcmp($a['name'], $b['name']);});
            }
            foreach($ar[$root] as &$row){
                if (is_null($row['cnt'])) {
                    if(!isset($row['ids']))$row['ids']=self::grList($row['id'],['format'=>'in']);
                    if(DB::is_field('tovar','gr')){
                        $row['cnt']=DB::Count('tovar','gr in ('.$row['ids'].')');
                    }else{
                        $row['cnt']=DB::Count('category_link','category in ('.$row['ids'].') GROUP BY tovar');
                    }
                    DB::sql("UPDATE ".self::db_prefix."category SET `cnt`='".addslashes($row['cnt'])."' WHERE id=".$row['id']);
                    }
                $row['url']='/shop/?gr='.$row['id'];
            }
        return $ar[$root];

        }elseif($options['format']=='in') {
            if(isset($ar[$root]['ids']))return $ar[$root]['ids'];
            $ids = $root;
            $child = $root;
            while (($row1 = DB::fetch_assoc(DB::sql("SELECT GROUP_CONCAT(DISTINCT id SEPARATOR ',') AS ids FROM `" . self::db_prefix . "category` WHERE parent in(" . $child . ") GROUP BY parent")))) {
                $child = $row1['ids']; // список id через ','
                $ids.= ($ids&&$child?',':'').$child;
    }
            return $ids;

        }elseif(isset($options['format']) && ($options['format']=='select'|| $options['format']=='option')){
            if(!empty($options['act'])&&$options['act']==$root){
                $root=(($root=DB::Select('category',$root)) ? $root['parent'] : 0 );
            }
            if(!isset($ar[$root]))self::grList($root);
            $str='';
            if(isset($options['no']))$str="\n\t".'<option value="0"'.(isset($options['act'])&&$options['act']==0?' selected':'').'>нет</option>';
            /*$str="\n\t".'<option value="0"'.(isset($options['act'])&&$options['act']==0?' selected':'').'>нет</option>';
            if($root!==0){
                $str.="\n\t".'<option value="-1">наверх</option>';
                $row=DB::Select('category',$root);
                $str.="\n\t".'<option class="b" value="'.$row['id'].'"'.(isset($options['act'])&&$options['act']==$row['id']?' selected="selected"':'').'>'.$options['act'].'~'.$row['id'].$row['name'].' ['.$row['cnt'].']</option>';
                if(isset($options['act'])&&$options['act']==$row['id']){
                    $str.=self::grList($options['act'],['format'=>'option']);
                }
            }*/
            foreach($ar[$root] as &$row){
                $str.="\n\t".'<option value="'.$row['id'].'"'.(isset($options['style'])?' style="'.$options['style'].'"':'').
                    (isset($options['act'])&&$options['act']==$row['id']?' selected="selected" class="b"':'').'>'./*$row['id'].'.'.*/$row['name'].' ['.$row['cnt'].']</option>';
                if(isset($options['act'])&&$options['act']==$row['id']){
                    $str.=self::grList($options['act'],['format'=>'option','style'=>'margin-left:10px']);
                }
            }
            if($options['format']=='option') return $str;
            return "\n\t".'<select name="gr" '.(isset($options['add'])?$options['add']:'').'>'.$str.'</select>';
        }
    }

    /** Возвращает случайные товары. Если передана категория и/или бренд, то в пределах указанной категории
     * @param int $kol
     * @param int $brand
     * @param int $collection
     * @param int $exclude_id
     */
    static function GetRand($kol = 3, $brand = 0, $collection = 0, $exclude_id = 0)
    {
        //return DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE gr='0' and kod_prodact<>'-'".($exclude_id?" and id<>'".$exclude_id."'":"").($brand?" and brand='".$brand."'":"").($collection?" and collection='".$collection."'":"")." ORDER BY RAND() LIMIT ".$kol);
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE ost<>'-99' and kod_prodact<>'-'".($exclude_id?" and id<>'".$exclude_id."'":"").($brand?" and brand='".$brand."'":"").($collection?" and collection='".$collection."'":"")." LIMIT 100");
        $ar=[];
        while($row=DB::fetch_assoc($query)){
            $tovar=new Tovar($row); if(empty($tovar)||empty($tovar->id)||!$tovar->is_img)continue;
            $ar[]="<div class='image_small_block'>".$tovar->Iurl."</div>\n";
        }
        shuffle($ar); // перемешать результат
        echo "<div class='others_block'>\n";
        for($i=0;$i<min($kol,count($ar));$i++){
            if($i%6==0 && $i>0)echo "<br class='clear'>\n</div><div class='others_block'>\n";
            echo $ar[$i];
        }
        echo "<br class='clear'>\n</div>";

        /*
            $query=DB::sql('SELECT MAX(id) AS max, MIN(id) as min FROM `'.db_prefix.'object`');
            $row=DB::fetch_assoc($query);
            $q='';
            for($i=$kol;$i;$i--)
                $q=($q?$q."\nUNION ":"")."(SELECT * FROM `".db_prefix."object` WHERE id >= ".mt_rand($row['min'],$row['max'])." LIMIT 1)";
            $query=DB::sql($q);
            return $query;
        */
        //$result = mysql_query( "SELECT * FROM `table` WHERE id IN(".implode(',',$ids).") LIMIT ".$n);
    }

    function SearchImg($from='Y'){
        $url=urlencode($this->normal_name.($this->brand&&stripos($this->name,$this->brand_name)===false?" ".$this->brand_name:"").
            ($this->collection_name?" ".$this->collection_name:""). ' '.$this->kol_name );
        $urlY='https://yandex.ru/images/search?uinfo=sw-1076-sh-498-fw-851-fh-448-pd-1.25&nomisspell=1&noreask=1&text='.$url;
        $urlG2='https://www.google.ru/search?tbm=isch&q='.urlencode($this->normal_name);
        $urlG='https://www.google.ru/search?tbm=isch&q='.$url;
        set_time_limit(100);
        $urls=ReadUrl::ReadMultiUrl([$urlG,$urlY],['cache'=>10,'timeout'=>10,'convert'=>charset]);
        //return var_export($headers,!0)."\n".var_export($body,!0)."\n".var_export($info,!0);
        $ret='';
        $p = (empty($_GET['p']) ? 0 : intval($_GET['p'])); $perpage = 10;
        $cnt1 = $cnt2 = 0;
        foreach($urls as $row) {
            if(!empty($row['info']['curl_error'])){$ret.="\n<br>".var_export($row,!0).$row['info']['curl_error']; continue;}
            if(cmp($row['url'],$urlG) && preg_match_all('#"ou":"(http.*?)"#i', $row['body'], $ar)){ // google
                $ret.="\n<br class=\"clear\">Google:<br class=\"clear\">";
                foreach ($ar[1] as $val) if (strpos($val, 'google.ru') === false){
                    if (++$cnt1 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt1 > ($perpage * ($p + 1))) break;
                }
            }elseif(cmp($row['url'],$urlG2) && preg_match_all('#"ou":"(http.*?)"#i', $row['body'], $ar)){ // google
                $ret.="\n<br class=\"clear\">Google2:<br class=\"clear\">";
                foreach ($ar[1] as $val) if (strpos($val, 'google.ru') === false){
                    if (++$cnt1 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt1 > ($perpage * ($p + 1))) break;
                }
            }elseif(cmp($row['url'],$urlY) && preg_match_all('#\{(?:&quot;|\")url(?:&quot;|\"):(?:&quot;|\")(http.*?)(?:&quot;|\"),#i', $row['body'], $ar)){ // yandex
                $ret.="\n<br class=\"clear\">Yandex:<br class=\"clear\">";
            foreach ($ar[1] as $val) if (strpos($val, 'yandex.ru') === false) {
                    if (++$cnt2 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt2 > ($perpage * ($p + 1))) break;
                }
            }else{
                if(cmp($row['url'],$urlG) && stripos($row['body'],'Не найдено ни одного')!==false)$urls+=ReadUrl::ReadMultiUrl([$urlG2],['cache'=>10,'timeout'=>10,'convert'=>'windows-1251']);
                else $ret.="\n<br>".var_export($row,!0);
            }
        }
        if($ret){
            $ret .= '<img id="SearchImg" class="hide">';
            if ($cnt1 > ($perpage * ($p + 1)) || $cnt2 > ($perpage * ($p + 1))){
                    $ret .= '<br class="clear"><a href="/api.php?search_img=' . $this->id . '&p=' . ($p + 1) . '" onclick="return ajaxLoad(this.parentNode,this.href)">[Еще&gt;&gt;]</a>';
                }
        }else $ret.='<br>У Яндекса и Гугла на странице не найдено картинок!'.
            '<br><a href="/api.php?search_img='.$this->id.'&reload" onclick="return ajaxLoad(this.parentNode,this.href)">Пересчитать страницу</a>';
        return $ret.' <a href="'.$urlY.'">Я</a> <a href="'.$urlG.'">G</a>';
    }

    /**
     * @param null|Tovar $tovar
     */
    static function OtherTovar($tovar=null,$kol=6){
        if ($tovar)
            Tovar::GetRand($kol,$tovar->brand,$tovar->collection,$tovar->id);
        else
            Tovar::GetRand($kol);
    }

    /**
     * Пересчет остатков товаров
     */
    static function RecalcOst(){
        DB::sql("UPDATE `".db_prefix."tovar` SET `ost`='0' WHERE `ost`<>'-99'"); // обнуляю остаток и проставляю правильный
        $query=DB::sql('Select tovar,SUM(ost) as ost1 from '.db_prefix.'tovar_shop GROUP BY tovar');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE `".db_prefix."tovar` SET `ost`='".$data['ost1']."' WHERE id='".$data['tovar']."'".($data['ost1']>0?'':" and ost<>'-99'"));
        }

        $query=DB::sql('Select * from '.db_prefix.'brand');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE ".self::db_prefix."brand SET `cnt`=".($c=DB::Count('tovar',"brand='".$data['id']."' and ost<>'-99'"))." WHERE id=".$data['id']);
            echo "<br>".$data['name'].' '.$c;
        }
        echo "<br>";
        $query=DB::sql('Select * from '.db_prefix.'category');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=".($c=DB::Count('category_link as c, '.db_prefix.'tovar as t',"c.tovar=t.id and c.category='".$data['id']."' and t.ost<>'-99'"))." WHERE id=".$data['id']);
            echo "<br>".$data['name'].' '.$c;
        }
    }

    static function ShowVitrina($where='vitrina=1'){
        $bar = new kdg_bar(array('perpage' => 10000, 'tbl' => db_prefix . 'tovar', 'sql'=>$where));
        ?>
        <div class="listing sb-body" xmlns="http://www.w3.org/1999/html">
            <div class="fr">
                <?
                $format_show=(!empty($_COOKIE['format_show'])&&$_COOKIE['format_show']=='list' ? 'list': 'table');
                if($format_show=='list'){ ?>
                    <span class="table_p" title="В виде таблицы" onclick="setCookie('format_show','table');reload();"></span>
                    <span class="list_p current" title="В виде списка"></span>
                <? }else{ ?>
                    <span class="table_p current" title="В виде таблицы"></span>
                    <span class="list_p" title="В виде списка" onclick="setCookie('format_show','list');reload();"></span>
                <?}?>
            </div>
            <br class="clear">
            <?php
            if($format_show=='list')echo "<br><table class=\"client-table tovar-table w100\">\n<tr>\n".
                "\t<th></th>\n".
                "\t<th onclick=\"Order('name')\">".($bar->ord=='name'?($bar->desc?'&uarr; ':'&darr; '):'')."Наименование</th>\n".
                "\t<th onclick=\"Order('price')\">".($bar->ord=='price'?($bar->desc?'&uarr; ':'&darr; '):'')."Цена, руб</th>\n".
                "\t<th></th>\n".
                "</tr>\n";
            else echo "    <ul class=\"cp-list cp-gallery\">";
            $result = DB::sql('SELECT * from '.db_prefix.'tovar WHERE '.$where.' LIMIT 20');
            while ($row = DB::fetch_assoc($result)) {
                self::PrintTovar($row);
            }
            global $euro;
            if($format_show=='list')echo "</table>\n";
            else echo "        <li class=\"cpg-item\"></li>
</ul>
";
            ?>
            <a href="/shop/">Все товары, услуги и цены</a>
            <span class="sb-more-totalLink">(<?=DB::Count('tovar')?>)</span>
<?if(self::valuta){?>
            <div class="kurs fr">
                <a href="/G/stock.rbc.ru/demo/cb.0/daily/USD.rus.shtml?show=1M" rel="nofollow" target=_blank>1$ = <b><?=Valuta::dollar()?></b> руб.</a>
                <a href="/G/quote.rbc.ru/exchanges/demo/cb.0/EUR/daily?show=1M" rel="nofollow" target=_blank>1€ = <b><?=$euro;?></b> руб.</a>
            </div>
<?}?>
            <br class="clear">
        </div>
        <?
    }
    /*
        static function RecalcPrice($row){
            $row['priceu']=floatval($row['priceu'])*($row['valuta']=='$'?Valuta::dollar():($row['valuta']=='E'?Valuta::euro():1));

            if($row['priceu']>0&&($row['supplier']<2||$row['valuta']=='*'))   $row['price']=$row['priceu'];

            $row['price0_r']=floatval($row['price0'])*($row['valuta0']=='$'?Valuta::dollar():($row['valuta0']=='E'?Valuta::euro():1));

            $nacenka=($row['price0_r']==0? 0 : ($row['price']-$row['price0_r'])/$row['price0_r'] );

    // price1 - мелкий опт в рублях
    // price2 - опт в рублях

            if($row['price1']<=0){  // розница -5%
                $row['price1']=floatval($row['price']) * ($nacenka>=1 ? 0.70 : 0.95);
            }
            if($row['price2']<=0 && $row['price0_r']>0){
                $row['price2']=$row['price0_r'] * ($nacenka>=1 ? 1.3 : 1.1);

            } // закупка +10%
            // если опт меньше розницы, то беру опт
            if($row['price1']>$row['price'])    $row['price']=$row['price1'];
            // если крупный опт больше мелкого
            if($row['price2']>$row['price1'])   $row['price2']=$row['price1'];

    // если цена товара до 100рублей, то делаю наценку не меньше 100%
            if( $row['price2']>0 && $row['supplier']>1 && $row['price']<=100 && ($row['price']/$row['price2'])<1.8 )  $row['price']=$row['price2']*1.8;
            return $row;
        }
    */

    static function NormalName($name,$prefix=''){
        $name=trim($name);
        $name=str_ireplace('Wi-Fi','WiFi',$name);
        $name=str_ireplace('Карт-ж','Картридж',$name);
        $name=str_ireplace('``','"',$name);
        $name=str_ireplace('`',"'",$name);
        $name=str_ireplace("''",'"',$name);
        $name=str_ireplace('ё','е',$name);
        $name=str_replace('  ',' ',$name);
        $name=str_replace(' + ','+',$name);
        $name=str_replace("\t",' ',$name);
        $search = ["`^V/c (.*?)`si",
            "`Видео карта`si",
            "`Манипулятор Mouse`si",
            "`Мат.плата`si",
            "`^M/B (.*?)`si",
            "`^С/плата (.*?)`si",
            "`^Системная плата (.*?)`si",
            "`Уст-во`si",
            "`DLINK`si",
            "`Micro-Star`si",
            "`Уст-ва`si",
            "`^ЖК монитор`si",
            "`Кулер`si",
            "`^HDD`si",
            "`^Винчестер HDD`si",
            "`Источник бесперебойного питания`si",
            "`sumsung`si",
            "`cannon`si",
            "`dialog`si",
            "`^МФ устройство`si",
            "`^Многофункциональное устройство`si",
            "`^Р/телефон`si",
            "`^Р/трубка`si",
            "`^Копир `si",
            '`Картридж "все-в-одном"`si',
            '`2,5"`si',
            '`3,5"`si',
            '`1,8"`si',
            '`купить`si',
            '`^Кард-ридер`si',
            '`^Устройство чтения карт памяти`si',
            '`^Card Reader`si',
            '`^Устройство чтения/записи флеш карт`si',
            '`Двухстороняя`si',
            '`Супер Глянцевая`si',
            '`Автоинвертер`si',
            '`в\-карт`si',
            '`CPU Fan universal`si',
            '`Картридж\-тонер`si',
            '`Тонер картридж`si',
            '`Вал фетровый`si',
            "/  +/"];
        $replace = ["Видеокарта $1",
            "Видеокарта",
            "Мышь",
            "Материнская плата",
            "Материнская плата $1",
            "Материнская плата $1",
            "Материнская плата $1",
            "Устройство",
            "D-Link",
            "MicroStar",
            "Устройства",
            "Монитор",
            "Вентилятор",
            "Жесткий диск HDD",
            "Жесткий диск HDD",
            "ИБП",
            "samsung",
            "canon",
            "диалог",
            "МФУ",
            "МФУ",
            "Радиотелефон",
            "Радиотрубка",
            "Копировальный аппарат ",
            "Картридж",
            '2.5"',
            '3.5"',
            '1.8"',
            '',
            'Картридер',
            'Картридер',
            'Картридер',
            'Картридер',
            '2х сторонняя',
            'Суперглянцевая',
            'Автоинвертор',
            'видеокарт',
            'Вентилятор для процессора универсальный',
            'Тонер-картридж',
            'Тонер-картридж',
            'Фетровый вал',
            " "];
        $name = preg_replace($search, $replace, $name);
        if($prefix && !cmp($name,$prefix)){
            $name=$prefix.$name;
        }

        return $name;
    }


    static function isStopWord($name){
        return (strpos($name,'плохая упаковка')!==false ||
            (strpos($name,'восстановленный')!==false) ||
            (strpos($name,'(ДУБЛЬ)')!==false) ||
            (strpos($name,'Фотоаппарат ')!==false) ||
            (strpos($name,'Заправка тонером')!==false) ||
            (strpos($name,'восстановление картриджа')!==false) ||
            (strpos($name,'Диагностика картриджа')!==false) ||
            (strpos($name,'NONAME ')!==false) );
    }

    static function highlight($str){
        return str_replace('№1','',str_replace('№2','',preg_replace("/№1(.*?)№2/si", '<span class="red">\\1</span>', htmlspecialchars($str,null,'windows-1251'))));// подсветка
    }

    static function Show($str){
        return str_replace('№1','',str_replace('№2','',preg_replace("/№1(.*?)№2/si", '\\1', htmlspecialchars($str,null,'windows-1251'))));
    }


    static function toUrl($str){
        return urlencode(str_replace('№1','',str_replace('№2','',$str)));
    }

    /** выводит хлебные крошки категории товара
     * @param integer $gr
     * @return string
     */
    static function BreadCrumbs($gr)
    {
        //var_dump($tovar);
        $i=0; // защита от рекурсии
        $gr=intval($gr);
        //var_dump($gr);
        $res='';
        while( ($gr>0) && ($gr=DB::Select('category',$gr)) && $i<5)
        {
            //var_dump($gr);
            $res=" <a href='/shop/?gr=".$gr['id']."'>".$gr['name']."</a>" . ($res?' &rarr; ':''). $res;
            $gr=$gr['parent'];
            $i++;
        }

        return "<div class=\"vid\">Категория товара: ".$res."</div>";
    }


    /** Вывести один из многих маленьких товаров, картинка 200x200
     * @param array|integer $row
     * @param array|null $options
     *                   $options['nodiv'] - не выводить внешний блок для замены информации о товаре по ajax
     */
    static function PrintTovar_new($row, $options=null){

        global $ar_q1,$q;
        if(isset($options['format_show']))$format_show=$options['format_show'];
        else $format_show=(!empty($_COOKIE['format_show'])&&$_COOKIE['format_show']=='list' ? 'list': 'table');
        $tovar=new Tovar($row);
        if($format_show=='list'){
            $name=$tovar->name;
            if (!empty($ar_q1) && !empty($q)){ $name=Tovar::highlight(preg_replace($ar_q1, '№1\\1№2', $name)); // подсветка в два этапа
                //if ($data['kod_prodact'] && $q) $data['kod_prodact'] = preg_replace($ar_q1, '№1\\1№2', $data['kod_prodact']); // подсветка в два этапа
            }
            //"<td>".$tovar->kod_prodact.($tovar->kod_prodact && $tovar->ean ? "/" : "" ) . $tovar->ean."</td>\n".
            if(!isset($options['nodiv']))echo "<tr class='hand' id=\"id".$tovar->id."\" onclick=\"return ajaxLoad('','".$tovar->url.(strpos($tovar->url,'?')==false?"?":"&amp;")."ajax')\">\n";
            echo "\t<td><div>".Image::imgPreview($tovar->imgSmall[0], ['size'=>imgSmallSize])."</div></td>\n".
                "\t<td class='left'>".(User::is_admin()?"<span class='blue'>".$tovar->kod_prodact."</span> ":"").
                "<a href='".$tovar->url."' onclick=\"return false;\">". $name . "</a>, код:".$tovar->kod_prodact."</td>\n".
                "\t<td>".$tovar->price. (User::is_admin(uADM_OPT)?"<span class='small'>(".$tovar->price2.")</span>":""). "</td>\n".
                "\t<td onclick='event.cancelBubble = true;if(event.stopPropagation)event.stopPropagation()'>".
                (User::is_admin()?
                    "<a href='#' class=\"icon edit\" title=\"Изменить\" onclick=\"return ajaxLoad('','/api.php?edit=".$tovar->id."')\"></a>".
                    "<a href='/api.php?tbl=tovar&amp;del=".$tovar->id."' class=\"icon del\" title=\"Удалить\" onclick=\"if(confirm('Удалить?'))ajaxLoad('answer',this.href);return false;\"></a>".
                    "<a href='#' class=\"icon vitrina".$tovar->vitrina."\" onclick=\"return ajaxLoad(this,'/api.php?vitrina=".$tovar->id."');\" title=\"Обычный/Витрина/Скрытый\"></a>"
                    :"").
                "\t<span class='icon cart' title=\"Заказать\" onclick=\"return ajaxLoad('','/api.php?basket_add&amp;id=".$tovar->id."');\"></span>".
                "</td>\n";
            if(!isset($options['nodiv'])) echo "</tr>\n";

        }else{
            if(!isset($options['nodiv'])) echo "<li class=\"cpg-item\" id=\"id".$tovar->id."\">";
            ?>
            Код: <?=$tovar->kod_prodact?>
            <?=(User::is_admin()?'<div class="right">#'.$tovar->id.'</div>':'')?>
            <div class="pr">
                <?
                if(User::is_admin()){?>
                    <span class="icon-box"><a href='#' class="icon edit" title="Изменить" onclick="return ajaxLoad('','/api.php?edit=<?=$tovar->id?>')"></a>
            <a href='/api.php?tbl=tovar&amp;del=<?=$tovar->id?>' class="icon del" title="Удалить" onclick="if(confirm('Удалить?'))ajaxLoad('answer',this.href);return false;"></a>
            <a href='#' class="icon fabric" onclick="return ajaxLoad('','/api.php?pt=<?=$tovar->id?>','..');" title="Поставщики"></a>
            <a href='#' class="icon vitrina<?=$tovar->vitrina?>" onclick="return ajaxLoad(this,'/api.php?vitrina=<?=$tovar->id?>');" title="Обычный/Витрина/Скрытый"></a>
            <a href='#' class="icon duplicate" onclick="return ajaxLoad('','/api.php?copy=<?=$tovar->id?>');" title="Копировать"></a>
            </span>
                <?}?>
                <a href="<?=$tovar->url?>" class="cpg-link" title="<?=$tovar->name?>" onclick="return ajaxLoad('','<?=$tovar->url.(strpos($tovar->url,'?')==false?"?":"&amp;")?>ajax')">
                    <?=Image::imgPreview($tovar->imgMedium[0], ['alt'=>$tovar->name, ['size'=>imgMediumSize]])?>
                </a>
                <?if($tovar->discount){
                    ?>
                    <div class="discount-marker big">- <?=$tovar->discount?>%</div>
                <?}?>
            </div>
            <div class="title-">
                <a href="<?=$tovar->url?>"><?=$tovar->name?></a>
            </div>
            <span class="orderButton fr" onclick="return ajaxLoad('','/api.php?basket_add&amp;id=<?=$tovar->id?>');">заказать</span>
            <div class="price-">
                <i class="bp-price"><?=outSumm($tovar->price)?></i> руб.<?=($tovar->ed?'/'.$tovar->ed:'')?>
            </div>
            <?if($tovar->price <> $tovar->price){?>
                <span class="bp-price-cover"><i class="bp-price fwn fsn"><?=outSumm($tovar->price_old)?></i> руб.</span>
                <span class="discount-expires-at">до <?=date("d.m.y",$tovar->discount_expires)?></span>
            <?}
            if(!isset($options['nodiv'])) echo "</li>\n";
        }
    }

    /** Выводит один товар
     * общая ширина 790px,
     * @param $row
     * @param null $options
     */
    static function PrintTovar($row, $options=null){ // , $full = false, $div = false, $noKateg = false ['full'=>true, 'div'=>true, 'noKateg'=>true]
        $full = isset($options['full']) ? !!$options['full'] : false; // =true-один товар с подробным описанием
        $div = isset($options['div'])?$options['div']:false;
        $noKateg = isset($options['noKateg']) ? !!$options['noKateg'] : false;
        // $div выводить внешний div
        $tovar=(is_object($row) ? $row : new Tovar($row) );
        $row=$tovar->GetTovar($tovar->id);
        if (!$tovar) {Out::error("Не найден товар ".$row."!"); return;}

        /*if(!isset($brend_search)){
           $query = DB::sql('SELECT * FROM '.db_prefix.'brand');
           while ($data = DB::fetch_assoc($query)){
                $brend_search[]='# '.$data['name'].' #im';
                $brend_replace[]=' <a href="http://'.$data['url'].'" target="_blank" rel="nofollow">\\0</a> ';}
        // ru.asus.com/Search.aspx?SearchKey=
        // www.acorp.ru/search/?text=
        // xerox.ru/ru/search/?q=
        // www.xerox.ru/ru/catalog/465/wc5020/?phrase_id=222469
        // www.d-link.ru/ru/search/POST?find_str=
        // www.epson.ru/search/index.php?q=
        // search.hp.com/gwrurus/query.html?lang=ru&la=ru&charset=utf-8&cc=ru&qt=
        // www.canon.ru/search/default.aspPOST?txtQuery= не находит :-(
        // apc.com/search/results.cfm?qt=	 не находит :-(
            $name=preg_replace($brend_search, $brend_replace, htmlspecialchars($row['name'],null,'windows-1251'));// бренды будут ссылками
        }*/
        //$row=self::RecalcPrice($row);

        if($div)print "<br class='clear'><div class='t".$tovar->sklad.(Get::isApi()?' contentwin':'')."' id='id".$tovar->id."'".(Basket::in($tovar->id) ?" style='opacity:0.4;'":"").">";

        $is_img=is_file($_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$tovar->id.'.jpg');
        if($is_img) if(isset($_SESSION['us_img'])&&$_SESSION['us_img']==0)$is_img=false; // не показывать картинки

        print "<div class='tprice'>\n<a href='/api.php?basket_add&id=".$tovar->id."' onClick='return ajaxLoad(\"\",this.href)' class='basket'>&nbsp;</a><br />\n";
        if($is_img){
            $img1=path_tovar_image.'s'.$tovar->id.'.jpg';
            $img2=path_tovar_image.'p'.$tovar->id.'.jpg';
        }else{
            $img1='/images/noimg40.gif';
            $img2='';
        }
        if(!$full)print "<a href='/shop/tovar".$tovar->id."' target=_blank><img id='img".$tovar->id."' src='".$img1."' /></a>";	// по картинке перехожу в товар
        else print "\n<img id='img".$tovar->id."' src='".$img1."'".($img2?" data-src='".$img2."'":'')." alt='".self::Show($tovar->kod_prodact)."'>\n";	// картинка

        print "<br class='clear'>".($tovar->priceu>0&&(User::is_admin(!0)&&$tovar->priceu!=floatval($tovar->price)||$tovar->priceu>$tovar->price)
                ?'<div class="priceu">'.number_format($tovar->priceu, 0, '.', ' ').' руб.</div>':'')."
".number_format($tovar->price, 0, '.', ' ')." руб.<sup>1</sup>";

        print "<br><span class='price1'>".number_format($tovar->price1, 0, '.', ' ')." руб.<sup>2</sup></span>";
        if(User::is_admin(!0)&&$tovar->price2<$tovar->price1)print "<br>".number_format($tovar->price2, 0, '.', ' ')." руб.<sup>3</sup>";
        print "\n<br><a href='/shop/?ys=".self::toUrl($tovar->name)."' class='ya'>&nbsp;</a>";
        if(!$is_img) print "\n<a href='/shop/?is=".self::toUrl($tovar->name)."' class='yaimg'>&nbsp;</a>";
        print "</div>\n";	// цена div=tprice

        print "<div class='tdes'>";

        if(!$full)print "\n<a href='/shop/tovar".$tovar->id."' class='modal'>".self::highlight($tovar->name)."</a>\n";
        else	print "<br clear='all'><h3 id='n".$tovar->id."'>".self::Show($tovar->name)."</h3>\n";

        if(!$noKateg)echo self::BreadCrumbs($tovar->gr).'<br>';

        print "Бренд: ";
        if (($brand=DB::Select('brand',$tovar->brand))) print '<b>'.$tovar->brand_url.'</b>';
        if(User::is_admin(uADM_MANAGER))print ", <input type='button' onclick='kp(".$tovar->id.")' value='код производителя'>: <b id='kp".$tovar->id."'>".self::highlight($tovar->kod_prodact)."</b>";
        else print ", код производителя: <b>".self::highlight($tovar->kod_prodact)."</b>";
        if($tovar->garant)print ", гарантия <b>".$tovar->garant."</b>мес.";

        if($tovar->sklad==1)print "<br>\nНаличие: <span class='est'>".$tovar->sklad_name."</span>";
        else print "<br>\nНаличие: ".$tovar->sklad_name;

        print "</div>\n";	// код производителя

        if($full){
            $desc=$tovar->description;
            if( $brand['search'] && $tovar->kod_prodact && (strpos($desc, 'http://')===false) )
                if(strpos($brand['search'], '%q%')!==false)$desc.="\nhttp://".str_replace('%q%',self::toUrl($tovar->kod_prodact),$brand['search']);
                else $desc.="\nhttp://".$brand['search'].self::toUrl($tovar->kod_prodact)."\n";
            if($desc){
                $desc = trim(preg_replace('#(?<!\])\bhttp://[^\s\[<]+#i',
                    " <noindex><a href=\"/shop/?url=$0\" target=_blank><u>Посмотреть на сайте производителя</u></a></noindex> ",
                    nl2br(stripslashes($desc))));
                print "<div class='tdes'>".$desc."</div>";	// описание
            }
        }

        if(User::is_admin(uADM_MANAGER)){
            if( $tovar->priceu>0 )print "<div class='tdes'>Рекомендуемая цена ".($tovar->valuta=='$'?$tovar->priceu.'$ = ':'').number_format($tovar->priceu, 0, '.', ' ')." руб.</div>";

            print "<div class='tdes'>#".$tovar->id."
<a href='/api.php?pt=".$tovar->id."' onclick=\"this.style.display='none';this.nextSibling.style.display='inline';return ajaxLoad('pt".$tovar->id."',this.href,'..');\">[Поставщик]</a><a href='#' style='display:none' onclick=\"this.style.display='none';this.previousSibling.style.display='inline'; getObj('pt".$tovar->id."').innerHTML=''; return false;\">[Скрыть Поставщиков]</a>
<b>".$tovar->supplier_name."</b> ".$tovar->date_upd.', закупка '.self::cPrice($row,0,true)."
<a href='/adm/edit.php?edit=".$tovar->id."' onclick=\"getObj('id".$tovar->id."').style.opacity=1;return ajaxLoad('id".$tovar->id."',this.href);\">[изменить]</a>
<span id='st".$tovar->id."'>";

            if(isset($_GET['edit']))echo "\n<script type='text/javascript'>onDomReady(ajaxLoad('id".$tovar->id."','/adm/edit.php?edit=".$tovar->id."'));</script>\n";

//   if($row['sost']=='нет')print "<a href='#show' onclick=\"getObj('t".$tovar->id."').style.opacity=1;return ajaxLoad('st".$tovar->id."','/adm/edit.php?show=".$tovar->id."');\">[показать]</a>"; // меняй и в edit
//	else print "<a href='#hide' id='st".$tovar->id."' onclick=\"getObj('t".$tovar->id."').style.opacity=0.2;return ajaxLoad('st".$tovar->id."','/adm/edit.php?hide=".$tovar->id."');\">[скрыть]</a>";
            print "\n<a class='dns' href='http://www.dns-shop.ru/search/?q=".urlencode($tovar->kod_prodact)."'>[Dns]</a>
            <a class='dns' href='https://www.ulmart.ru/search?spellCheck=false&string=".urlencode($tovar->kod_prodact)."'>[Юлмарт]</a>
            <a class='dns' href='http://www.citilink.ru/search/?text=".urlencode($tovar->kod_prodact)."'>[CitiLink]</a>";
            print "</span></div><span id='pt".$tovar->id."'></span>\n"; // сюда загрузим данные поставщиков
        }
        if($div) print "</div>\n\n";	// конец товара
    }


    static function ActualizedDate($where=''){ // дата актуальности всей базы товаров
        $row=DB::fetch_assoc(DB::sql('SELECT max(date_upd) as d from '.db_prefix.'tovar'.($where?' WHERE '.$where:'')));
        return strtotime($row ? intval($row['d']) : '2014-01-01' );

    }

    static function SetActualizedDate($where=''){ // установить дату актуальности всей базы товаров
        DB::sql("UPDATE ".self::db_prefix."tovar SET `date_upd`='".date("Y-m-d")."'".($where?' WHERE '.$where:''));
    }

    /** Вывести один большой товар
     * @param array|Tovar $row
     */
    static function Print1Tovar($row){
        $tovar=(is_object($row)?$row: new Tovar($row));
        $img=$tovar->imgBig;
        echo "\n<form class='primary_block' name='tovar' id='tovar' action='/api.php?basket_add' method='post' onsubmit='return SendForm(\"\",this)'\n".
            (User::is_admin()?
                "ondragenter=\"addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();\"".
                "ondragover=\"addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();\"".
                "ondragleave=\"removeClass(getEventTarget(event,'FORM'),'box');\"".
                "ondrop=\"return _frm.drop(event);\"":"").">".
            "<h1><a href='".$tovar->url."'>".$tovar->name."</a></h1>\n".
            "<input type='hidden' name='id' value='".$tovar->id."'>\n".
            "<div class='image_block'".(User::is_admin()&&!$tovar->is_img?' style="height:auto"':'')."><div>\n".
            (User::is_admin()&&!$tovar->is_img?
                $tovar->SearchImg() :
                "<img src='".ImgSrc($tovar->imgBig[0])."' alt='".$tovar->name."'>"
            );
        if(count($img)>1)foreach($tovar->imgSmall as $i=>$fil){
            $alt=Image::Alt($fil);
            echo "\n<div class=\"obj_photo\">".
                "\n\t".Image::imgPreview($fil, array('whithA'=>$img[$i],'size'=>imgSmallSize)).
                "\n\t\t".htmlspecialchars($alt,null,'windows-1251').
                "\n\t</a>\n\t</div>";
        }

        echo "</div>".(User::is_admin()&&$tovar->is_img?"<a class='icon cart_remove confirm' href='/api.php?del_img=".$tovar->id."' onclick='return ajaxLoad(false,this.href);'></a>":"").
            "</div>\n<div class='left' style='width:318px'>\n".
            ($tovar->collection?"<p".(strlen($tovar->collection_name)>30?" class='long'":'')."><label>Коллекция:</label>".Tovar::Collection_anchor($tovar->brand_name, $tovar->brand, $tovar->collection_name, $tovar->collection)."</p>\n":"").
            "<p><label>Бренд:</label> ".Tovar::Collection_anchor($tovar->brand_name, $tovar->brand)."</p>\n".
            "<p><label>Артикул:</label> ".$tovar->kod_prodact.($tovar->kod_prodact && $tovar->ean ? "/" : "" ) . $tovar->ean."</p>\n".
            ($tovar->kol==0?"" : "<p><label>Объем:</label> ".$tovar->kol_name."</p>\n").
            "<p style='text-align:left'>".$tovar->show_ost."</p>\n".
            "<p class='price'><label>Цена:</label><b>".$tovar->price." руб.</b></p>\n".
            (User::is_admin(uADM_OPT)?"<p class='price'><label>Цена опт:</label><b>".$tovar->price2." руб.</b></p>\n":"").
            (User::is_admin()?"<p class='price'><label>Закупка:</label><strong>".$tovar->price0." руб.</strong></p>\n":"").
            "<p class='price quantity_wanted'><label>Количество:</label>\n".
            "<input type='number' maxlength='3' size='2' value='1' class='text' id='quantity_wanted' name='kol'>\n<br>\n".
            "<br>".
            ($tovar->ost==-99?'<b class="red">Не доступно для заказа</b>':"<input type='submit' class='button' value='В корзину'>").
            "<br>\n</p>\n".
            "</div>\n".
            "<br class='clear'></form>\n";
        echo
            (User::is_admin()?
                "<span class='r'><a href='/api.php?log&amp;tovar=".$tovar->id."' class=\"icon comment r\" title=\"Протокол\" onclick=\"return ajaxLoad('',this.href+'&amp;ajax');\"></a>".
                "<a href='/api.php?tovar&amp;show=".$tovar->id."' class=\"icon abonement r\" title=\"Движения\" onclick=\"return ajaxLoad('',this.href);\"></a>".
                "<a href='/api.php?tbl=tovar&amp;del=".$tovar->id."' class=\"icon del r\" title=\"Удалить\" onclick=\"if(confirm('Удалить?'))ajaxLoad('answer',this.href);return false;\"></a>".
                "<a href='/api.php?edit=".$tovar->id."' class=\"icon edit r\" title=\"Изменить\" onclick=\"return ajaxLoad('',this.href)\"></a>".
                "<a href='#' class='icon vitrina'".$tovar->vitrina."' onclick='return ajaxLoad(this,\"/api.php?vitrina='.$tovar->id.'\");' title='Обычный/Витрина/Скрытый'></a>".
                "</span>":
                "").
            "<div class='info_block'>".
            "<h3>Описание</h3>".
            "<ul><li> ".$tovar->description;//.'<div class="actualized-date">Обновлено '.date("d.m.Y",strtotime($tovar->dat)).'</div>';
        //if(User::is_admin()) echo'<div class="right">#'.$tovar->id.'</div>';
        $category=$tovar->category;
        if(count($category)){
            $category_list='';
            foreach($category as $key=>$value)
                $category_list.=($category_list?', ':'')."\n<a href=\"/shop.php?category[".$key."]=".$value."\">".DB::GetName('category',$key)."</a>";
            echo "<br><b>Категори".(count($category)>1?'и':'я').":</b>".$category_list;
        }
        echo "</li></ul>\n</div>\n";
        //if(!User::is_admin())echo "</div>\n";

        if(!isset($_GET['ajax'])) Tovar::OtherTovar($tovar);
    }

    static function CopyTovar($tov){
        $tov=Tovar::GetTovar($id=intval($tov)); if(!$tov)die('Нет такой записи ##'.$id.'!');
        unset($tov['id']);
        $tov['name'].=' (копия)';
        $tov['seo_url']=str2url($tov['name']);
        if(self::SaveTovar($tov,array('noUnion'=>!0))){
            $ext=(empty($ext)?'*':(is_bool($ext)&&$ext ? '{'.implode(',',Image::$ext_img).'}' : (is_array($ext) ? '{'.implode(',',$ext).'}' : $ext )));
            for($i=0;$i<99;$i++){
                $files=glob($_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$id.($i?'_'.$i:'').".".$ext, GLOB_BRACE);
                if($files)foreach($files as $file){
                    copy($file, $_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov['id'].($i?'_'.$i:'').'.'.pathinfo($file, PATHINFO_EXTENSION));
                }
            }
            return $tov['id'];
        }
        //var_dump($tov);
        return 0;
    }

    static function EditTovar($tov){
        $tov=new Tovar($id=intval($tov)); if(!$tov)die('Нет такой записи #'.$id.'!');
        ?>
        <br>
        <form id="editTovar" action="/api.php" method="post" enctype="multipart/form-data"
              ondragenter="return fb_drop(event);"
              ondragover="return fb_drop(event);"
              ondragleave="return fb_drop(event);"
              ondrop="return fb_drop(event);">
            <input type="hidden" name="id" value=<?=$id?>>
            <div class="r"><?=$tov['date_upd']?> #<?=$id?></div>
            <span class="layer act" onClick='layer(0);'>Основное</span>
            <span class="layer" onClick='layer(1);'>Цена</span>
            <span class="layer" onClick='layer(2);'>Описание</span>
            <span class="layer" onClick='layer(3);'>SEO</span>
            <span class="layer" onClick='layer(4);'>Файлы</span>
            <div class="layer act">
                <a href="http://yandex.ru/yandsearch?text=<?=self::toUrl($tov['name'])?>" target=_blank class="ya" title="Искать на Яндексе">&nbsp;</a>
                <label>Наименование: <input type="text" name="name" size=75 value="<?=toHtml($tov['name'])?>" class="w100" onchange="this.form.seo_url.value=''" /></label>
                <br>
                <label>Вид:
                    <?=self::grList(0,['format'=>'select','act'=>$tov['gr'],'add'=>'onchange="w_gr()"']); ?>
                </label>
                <br>
                <label>Код производителя: <input type="text" name="kod_prodact" size="25" value="<?=toHtml($tov['kod_prodact'])?>" style="width:200px"></label>
                <label>Код-EAN: <input type="text" name="ean" size="13" value="<?=$tov['ean']?>" style="width:200px"></label>
                <a href="http://yandex.ru/yandsearch?text=<?=self::toUrl($tov['kod_prodact'])?>" target=_blank class="ya" title="Искать на Яндексе">&nbsp;</a>
                <br>
                <?
                $brand_name = DB::GetName('brand', $tov['brand']);
                $collection_name = DB::GetName('collection', $tov['collection']);
                //                <label>Бренд: <input name="brand" value="<?=DB::GetName('brand',$tov['brand'])? >" size="25" href="/adm/brand.php?select=1&get="></label>

                echo <<< END
                <label>Бренд:
                    <input type="text" name="brand" value="{$brand_name}" list="lbrand" style="width:200px"></label>
                <label>Коллекция:
                        <input type="text" name="collection" value="{$collection_name}" list="lcollection" style="width:200px;"></label>
END;
                echo DataList('brand');
                echo DataList('collection');
                ?>
                <br>
                <?
                $category=$tov->category;
                if(count($category)){
                    $category_list='';
                    foreach($category as $key=>$value)
                        $category_list.=($category_list?', ':'')."\n<a href=\"/shop.php?category[".$key."]=".$value."\">".DB::GetName('category',$key)."</a>";
                    echo "<label>Категори".(count($category)>1?'и':'я').":".$category_list."</label>";
                }
                ?>
                <br>
                <select name="sklad">
                    <?
                    foreach (Tovar::$_sklad_name as $key => $value)
                        echo '<option value="'.$key.'"'.($tov['sklad']==$key?' selected':'').'>'.Tovar::$_sklad_name[$key]."</option>";
                    ?>
                </select><br>
                <label>Кол-во(объем): <input name="kol" type="text" value="<?=$tov['kol']?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                <label>Ед.измерения: <input name="ed" type="text" maxlength=12 value="<?=$tov['ed']?>" style="width:80px;"></label><br>
                <label><a href="#" onclick="return ajaxLoad('fb_modal','api.php?tovar&show=<?=$tov['id']?>')">Остаток</a> <small>(-99 скрыть)</small>:
                    <input name="ost" type="number" value="{$data['ost']}" style="width:80px"></label>
                <label><span id='isrok'>Срок действия, мес(заказ):</span>
                    <input name="srok" type="number" value="<?=$tov['srok']?>" style="width:80px"></label>

            </div>
            <div class="layer">
                <fieldset>
                    <legend>Цена</legend>
                    <label>Закупки: <input type="text" name="price0" size=10 value="<?=str_replace(',','.',$tov['price0'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
<?if(self::valuta){?>
                    <select name="valuta0">
                        <option value="$"<?=($tov['valuta0']=='$'?' selected':'')?>>$</option>
                        <option value="E"<?=($tov['valuta0']=='E'?' selected':'')?>>€</option>
                        <option value=" "<?=(empty($tov['valuta0'])?' selected':'')?>>руб</option>
                    </select><br>
<?}?>
                    <label>Продажи: <input type="text" name="priceu" size=10 value="<?=str_replace(',','.',$tov['priceu'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                    <?if(self::valuta){?>
                    <select name="valuta">
                        <option value="$"<?=($tov['valuta']=='$'?' selected':'')?>>$</option>
                        <option value="E"<?=($tov['valuta']=='E'?' selected':'')?>>€</option>
                        <option value=" "<?=(empty($tov['valuta'])?' selected':'')?>>руб</option>
                    </select><br>
<?}?>
                    <label>Мелкий опт: <input type="text" name="price1" size=10 value="<?=str_replace(',','.',$tov['price1'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label><br>
                    <label>Опт: <input type="text" name="price2" size=10 value="<?=str_replace(',','.',$tov['price2'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                </fieldset>
                <div class='tdes'>
                    <a href='/shop/api.php?pt=<?=$tov['id']?>' onclick="return ajaxLoad('',this.href,'..');" title="Поставщики">Поставщики:</a>
                    <b><?
                        $suppliers=DB::Select2Array('supplier_link','tovar="'.$tov['id'].'"');
                        if($suppliers)foreach($suppliers as $supplier) echo "<li>".DB::GetName('supplier',$supplier['supplier']).' '.self::cPrice($tov,$supplier['supplier'])/*Tovar::CalcPrice($supplier,true)*/."</li>";
                        else echo "Поставщиков нет"
                        ?>
                    </b>
                </div>
            </div>
            <div class="layer">
                <div class="r button hand" onclick="editon(this)">Визуальный редактор</div>
                <label>Описание:
                    <span onclick="editSave();getObj('seo_keywords').value=getStrong(getObj('description').value);layer(3)" class="hand">[обновить SEO.KeyWords]</span>
                    <textarea class="ckeditor" name="description" id="description" rows="7" cols="75"><?=$tov['description']?></textarea></label><br>
            </div>
            <div class="layer">
                <label>Keywords: <input type="text" name="seo_keywords" id="seo_keywords" size=75 value="<?=@$tov['seo_keywords']?>" class="w100" /></label>
                <label>Description: <input type="text" name="seo_description" size=75 value="<?=@$tov['seo_description']?>" class="w100" /></label>
                <label>URL: <input type="text" name="seo_url" size=75 value="<?=@$tov['seo_url']?>" class="w100" /></label>
            </div>
            <div class="layer">
                <?
                Image::blockLoadImage($tov[self::img_name], ['path'=>path_tovar_image.'p']);
                ?>
            </div>
            <br class="clear">
            <input type='submit' value='Сохранить' class="button" onclick='editSave();return SendForm("id<?=$id?>",this.form);' />
            <!--<input type="submit" value="Обновить" class="button gray"  onclick="ajaxLoad('t<?/*=$id*/?>','/api.php?out=<?/*=$id*/?>');return false;">-->
            <span id="edit<?=$id?>"></span>
        </form>
        <?
    }

    /** очистить кэш
     * @param null|array|string $options
     *                  $options=null файловый кеш всех товаров
     * $options['id']=NNN - маленькие картинки товара id
     * $options='recalc' - пересчитать yml
     */
    static function ClearCash($options=null){
        if(!empty($options['id'])){
            /*            for($i=0;$i<99;$i++){
                            $fil=$_SERVER['DOCUMENT_ROOT'].$options['path'].$id.($i?'_'.$i:'').'.jpg';
                            if(is_file($fil)){
            */
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$options[self::img_name].'.jpg';  if(is_file($fil))unlink($fil);
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'m'.$options[self::img_name].'.jpg';  if(is_file($fil))unlink($fil);
        }else{
            @unlink($_SERVER['DOCUMENT_ROOT']."/log/kurs.txt");
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=NULL");
            //message("Кеш очищен!");
            // todo message(self::YML(!0));
            if(is_file($yml=$_SERVER['DOCUMENT_ROOT']."/shop/yml.php")) include_once $yml."?x=1";
        }
    }

    /** установить основного поставщика
     * @param array|integer|Tovar $tov
     * @param int $supplier = 0 - установить оптимального, -1 - если поставщика нет, удалить товар
     * @return int 0 - ок, 1 - нет поставщиков, 2 - неверный товар
     */
// todo установить вторую цену по минимальной московской/ростовской цене
    static function SetFirstSupplier($tov, $supplier=0){
        $id=(is_array($tov)?$tov['id']:(is_object($tov)?$tov->id:$tov));
        $tov_o=(is_object($tov)?$tov:new Tovar($tov));
        if($supplier<1){// найти наиболее доступного поставщика с меньшей ценой
            $sklad=99; $price=99999999.99;
            $result = DB::sql('SELECT * from '.db_prefix.'supplier_link WHERE tovar='.$id);
            while ($row=DB::fetch_assoc($result)){
                if(!self::valuta)$row['valuta0']='';
                if(intval($row['sklad'])<$sklad){ $supplier=$row['supplier']; $price=$row['price0']*self::Kurs($row['valuta0']); }
                elseif(intval($row['sklad'])==$sklad && ($cp=($row['price0']*self::Kurs($row['valuta0']))) < $price && $cp>0){ $supplier=$row['supplier']; $price=$cp; }
            }
            if($supplier==-1){
                Tovar::Del($id);
                if(is_array($tov))$tov['supplier']=0;
                return 1;
            }elseif($supplier<1){
                echo '<br>У <a href="'.$tov_o->url.'">товара '.$id."</a> нет поставщиков:
	<a id='t".$id."' href='#' onclick=\"return ajaxLoad('t".$id."','/api.php?tbl=tovar&del=".$id."');\">[X]</a>
	".DB::GetName('tovar',$id);
                if(is_array($tov))$tov['supplier']=0;
                return 1;}
        }
        if(($row=DB::Select('supplier_link','tovar='.$id.' and supplier='.$supplier))){
            $row1=(is_array($tov) ? array_merge($tov,array_intersect_key($row,array_flip(['supplier','sklad','price0','valuta0','ost']))) : $row );
            $price=Tovar::cPrice($row1,$supplier); //$row['supplier']=$row['supplier'];
            DB::sql("UPDATE ".db_prefix."tovar SET sklad='".$row1['sklad']."', price0='".$row1['price0']."', ".(self::valuta?"valuta0='".$row1['valuta0']."',":'').
                " price='".$price."', ost='".$row1['ost']."', supplier='".$supplier."', date_upd='".$row['dat']."' WHERE id=".$id.' LIMIT 1');
            DB::CacheClear('tovar',$id);

        }else{echo '<br>Ошибочный <a href="'.$tov_o->url.'">товар '.$id.'</a>!'; return 2;}
        return 0;
    }

    static function ShowSupplier($id){  // нажали кнопку [Поставщики]
        $id=intval($id);
        $result = DB::sql('SELECT * from '.db_prefix.'supplier_link WHERE tovar='.$id);
        echo "<br class='clear'>"; $i=0;
        if(DB::num_rows($result)>0){
            while ($row=DB::fetch_assoc($result)) {
                //self::cPrice($row);
                //$tov1=array ('price0'=>$price0, 'valuta0'=>$valuta0, 'valuta'=>$valuta, $tov['supplier']=>$supplier['id'],$tov['priceu']=>$priceu,$tov['gr']=>$gr),$supplier);
                echo "<div class='supplier".($i++%2?"2":'')."'>Поставщик: <a href='/shop/?pr=".$row['supplier']."'><b>".DB::GetName('supplier',$row['supplier'])."</b></a><br>".toHtml($row['name'])."<br>
		".date("d.m.Y", strtotime($row['dat'])).', '.(empty(Tovar::$_sklad_name[$row['sklad']])?Tovar::$_sklad_name[SKLAD_OLD]:Tovar::$_sklad_name[$row['sklad']]).
                    ', остаток '.toHtml(trim($row['ost'])).', закупка '.Tovar::cPrice($row, 0, true)/*round($row['price0']*self::Kurs($row['valuta0']),price_round)*/;
                if($row['kod_prodact'] )  print "<br>Код поставщика: <b>".$row['kod_prodact']."</b>";
                if($row['url']) print "<br><a href='".$row['url']."' target=_blank>".$row['url']."</a>";
                print " <a href='/api.php?tbl=supplier_link&tovar=".$id."&del=".$row['supplier']."' onclick=\"return ajaxLoad('pt".$id."',this.href);\">[удалить]</a>";
                print " <a href='/api.php?tovar=".$id."&supplier=".$row['supplier']."&first=1' onclick=\"return ajaxLoad('id".$id."',this.href);\">[основной]</a></div>";
                //print " <a href='' onclick=\"return ajaxLoad('pt".$id."','/api.php?tovar=".$id."&supplier=".$row['supplier']."&new=1');\">[выделить в отдельный товар]</a>";
            }
        }else {echo "<div class='supplier'><b>Нет поставщиков!</b><br><a href='/api.php?tbl=tovar&del=".$id."' onclick=\"return ajaxLoad('id".$id."',this.href);\">[удалить товар]</a></div>";}
    }

    /** возвращает true если $tov['name'] и name не конфликтуют и могут оказаться одним товаром
     * @param array $tov
     * @param string $name
     * @return bool
     */
    static function IsSovmestim($tov, $name){
        return true;
        $a1=$tov['name'];
        $w= [];
        /*
        // Несовместимые слова
        тонер-картридж копи-картридж, принт-картридж

        стоп-слова если наименование содержит одно из этих слов - оно не будет подлито
        плохая упаковка
        восстановленный
        (ДУБЛЬ)
        Фотоаппарат
        Заправка тонером
        восстановление картриджа
        Диагностика картриджа
        NONAME '

        замена по шаблону
        */

        $w[]= ['ps/2','usb'];
        $w[]= ['box', 'tray']; // tray=oem
        $w[]= ['box', 'oem'];
        $w[]= ['Чип', 'Тонер','картридж','принтер'];
        $w[]= ['Чип', 'Тонер','картридж','МФУ'];
        $w[]= ['Заправка тонером','восстановление картриджа','Диагностика картриджа'];
        $w[]= ['Резиновый вал','Дозирующее лезвие'];
        $w[]= ['Красный', 'Белый','Черный','Зеленый','Синий'];    // разные цвета
//$w[]=array('тонер-картридж','копи-картридж', 'принт-картридж'); Принт-картридж = Тонер-картридж
        $w[]= ['(Katun)','(о)','(Wellprint)','(Samsung)','(Gold ATM)','(RuTone)','(Hanp)','B&W','NV-Print','Колибри','Fullmark'];
        $a1=str_replace(['(ориг)','Original'],'(о)',$a1);
        $name=str_replace(['(ориг)','Original'],'(о)',$name);
        foreach($w as $v){$f1=0;$f2=0;
            foreach($v as $value){
                $i1=stripos($a1,$value)!==false;
                $i2=stripos($name,$value)!==false;
                if($i1 && $i2)break; // если слово есть в обоих
                if($i1)$f1=$value;
                if($i2)$f2=$value;
                if($f1&&$f2&&$f1!=$f2)return false;
            }
            //if($f1||$f2)echo "<br>Возможно несовместимость ".$f1."~".$f2."<br>";//return false; // есть одно из слов
        }
        if(DB::Select('incompatibility','tovar='.$tov['id'].' and name="'.addslashes($name).'"'))return false;
        return true; // нет слов
    }

// добавить проверку если у исходного и заменяемого есть одинаковые supplier и у них разные названия, то не объединять, а выдавать сообщение
// не объединять при расхождении цены более 50%

    static function SaveTovar(&$tov)
    { // сохранение и объединение, id - останется
        /*        if(!empty($tov['id'])&&count($tov)<18){// не все поля переданы
                    if ($row=DB::Select('tovar','id='.$tov['id'])) {foreach ($row as $key => $value)
                        if(!isset($tov[$key])||empty($tov[$key])&&in_array($key,array('kod_prodact','description','priceu')))$tov[$key]=$value;}
                    else {mysql_close(); die("Товара ".$tov['id']." нет!"); }
                }*/
        //var_export($tov);

        /*        $aid=[];
                if($tov['supplier']==0)self::SetFirstSupplier($id);
                if($tov['supplier']>0 && $tov['price0']>0){
                    $tov['price']=Valuta::cPrice($tov); // рассчитать розничную цену
                }*/

        // группа или бренд могут быть укзанны именем. бренд может быть передан массивом
        if (!empty($tov['brand']['id'])) {
            $tov['brand'] = $tov['brand']['id'];
        } elseif (empty($tov['brand'])) {
            $tov['brand'] = 0;
        } elseif ((intval($tov['brand']) < 1) || (strval(intval($tov['brand'])) != $tov['brand'])) { // 5bites
            if (empty($tov['brand'])) $tov['brand'] = 0;
            elseif($row=self::GetBrand($tov['brand'],1))$tov['brand']=$row['id'];
            else die("Не найдено brand=" . $tov['brand']);
        }
        if (empty($tov['gr'])) {
            $tov['gr'] = 0;
        }elseif(intval($tov['gr'])<1){ // Если группа указанна текстом
            if ($row = self::GetGr($tov['gr'])) $tov['gr'] = $row['id'];
            else die("Не найдено gr=" . $tov['gr']);
        }

        if (isset($tov['category']) && is_string($tov['category'])) {
            if (($tov['category'] = explode(',', $tov['category']))) {
                $tov['category'] = array_fill_keys($tov['category'], 1);
            }
        }

        if (!empty($tov['kol']) && preg_match('/([0-9\.]+)([^[0-9\.]]+)$/', $tov['kol'], $ar)){
            $tov['kol'] = $ar[1];
            $tov['ed'] = $ar[2];
        }elseif(!isset($tov['ed'])) $tov['ed'] = '';
        if (!empty($tov['kol']))$tov['kol'] = floatval($tov['kol']);

        if(empty($tov['price']) && !empty($tov['price0']) && !empty($tov['supplier'])/* && !empty($tov['priceu'])*/ && (!isset($tov['valuta']) || !($tov['valuta']=='*' && $tov['price'])) ){
            $tov['price'] = Tovar::cPrice($tov); // пересчитываю цену с учетом наценки за категорию
        }
        if (empty($tov['id'])) {
            $tov['id'] = 0;
            //$row['category']='';
            $row = [];
            if (!isset($tov['ost'])) $tov['ost'] = 0;
            $add = $tov['ost'];
            if (!isset($tov['category']) || !is_array($tov['category'])) $tov['category'] = [];
        } else {
            $t = new Tovar($tov['id']); // считать старый из базы
            //echo "tov=".var_dump($tov)."<br>row=".var_dump($t->row)."<br>ost=".$t->ost;
            $row = $t->row;
            $row['category'] = $t->category;// чтобы попало в лог
            if(!isset($tov['category'])||!is_array($tov['category']))$tov['category']=$row['category']; // категории не переданы, т.к. не редактировались
            // если изменился остаток то записываю или в продажу или в приход
            if (!isset($tov['ost'])) $tov['ost'] = $t->ost;
            $add = intval($tov['ost'] - $t->ost);
        }
        /*if(isset($tov['category'])&&is_array($tov['category'])){
            foreach($tov['category'] as $key => $value)if(empty($tov['category'][$key]))unset($tov['category'][$key]);
        }else $tov['category']=[];*/
        //message("tov:".var_export($tov['category'],!0)."<br>\n"."row:".var_export($row['category'],!0));
        if(self::tbl_prixod) {
            if ($add != 0) {
                    if (($data = DB::Select("prixod", "dat='" . date('Y-m-d') . "' and tovar='" . $tov['id'] . "'"))) { // сегодня уже был приход этого товара, исправляю его
                    DB::sql("UPDATE `" . db_prefix . "prixod` SET `kol`='" . (intval($data['kol']) + $add) . "' WHERE id='" . $data['id'] . "'");
                } else {
                        DB::sql("INSERT INTO `" . db_prefix . "prixod` ( `dat`, `tovar`, `kol`, `price`, `user`)
                        VALUES ( '" . date('Y-m-d') . "', '" . $tov['id'] . "', '" . $add . "', '" . $tov['price'] . "', '" . User::id() . "')");
                }
            }

            DB::log('tovar', $tov['id'], '', $row, $tov);
        }
        $f_ok = false;
        if ($tov['id'] > 0) {
            if ($row['category']) foreach ($row['category'] as $key => $value) if (empty($tov['category'][$key])) {
                DB::Delete("category_link","tovar='".$tov['id']."' and category='".$key."'");
                //message("Удалил категории: [".$key."]=".$value);
                $f_ok = $f_ok || (DB::affected_rows() > 0);
            }

            if ($tov['category']) foreach ($tov['category'] as $key => $value) if (empty($row['category'][$key]) && $value != '0') {
                //message("Добавил категории: [".$key."]=".$value);
                DB::sql("INSERT INTO `" . db_prefix . "category_link` (tovar, category) VALUES ('" . $tov['id'] . "', '" . $key . "')");
                $f_ok = $f_ok || (DB::affected_rows() > 0);
                //}else message("Неверная категория: ".print_r($tovar['category'],true)."<br>".print_r($row['category'],true)."<br>".print_r($category,true));
            }
        }
        $tov['collection'] = (empty($tov['collection']) ? 0 : Tovar::GetCollection($tov['collection'], $tov['brand'])); // $tov['brand'] может стать массивом !
        if(!empty($tov['brand']['id'])) $tov['brand'] = $tov['brand']['id'];

        /*        $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE '.(empty($tov['id'])?'':'id<>'.$tov['id'].' and '). 'brand="'.$tov['brand'].'" and (lower(name)="'.addslashes(mb_strtolower($tov['name'])).'")');
                while ($row=DB::fetch_assoc($result)) {//Duplicate entry
                    if($tov['name']==$row['name']){
                        self::Union($tov,$row); // )Obedin(
                        echo "<br>".$tov['id']." Объединил с ".$row['id']." <b>".toHtml($row['name'])."</b>!";
                        $tov['id']=$row['id'];
                    }else{
                        //self::AskObedin($tov,$row);
                    }
                }*/

        // готовлю для записи поле info и доп. поля
        //foreach(Tovar::$ar_float as $key) if(!empty($tov[$key]))$tov[$key]=str_replace(',', '.', $tov[$key]);
        $info = [];
        foreach (Tovar::$ar_info as $key) if (isset($tov[$key])) {
            $info[$key] = str_replace(',', '.', $tov[$key]);
            unset($tov[$key]);
        }

        if ($tov['id']) {
            $row = DB::Select('tovar', intval($tov['id']));
            $info = array_merge(js_decode($row['info']), $info);
        }
        $tov['info'] = js_encode($info);
        $tov['date_upd']=date("Y-m-d");


        $aid=[];
        $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE id<>'.$tov['id'].' and brand="'.$tov['brand'].'" and '.
            '(name="'.addslashes($tov['name']).'"'.(strlen($tov['kod_prodact'])>3?' or kod_prodact="'.addslashes($tov['kod_prodact']).'"':'').')');
        while ($row=DB::fetch_assoc($result)){//Duplicate entry
            if(isset($row['gr']) && $row['gr']!=$tov['gr'])continue; // todo добавить проверку всех групп
            if(!empty($row['kol']) && !empty($tov['kol']) && $row['kol']!=$tov['kol'])continue;
            if($tov['name']==$row['name']){
                Tovar::Union($row,$tov);
                echo "<br>Объединил с ".$row['id']." <b>".toHtml($row['name'])."</b>!";
            }else{
                Tovar::AskObedin($tov,$row);
                $aid[]=$row['id'];
        }
        }
        if(strlen($tov['kod_prodact'])>3){ // TODO перетащить алгоритм в add.php !!!

            $kod_prodact=$tov['kod_prodact'];
            //$rf='(kod_prodact="'.addslashes($kod_prodact).'" or locate(" '.addslashes($kod_prodact).' ",CONCAT(" ",name," "))>0)';
            $rf=' and brand="'.$tov['brand'].'" and (lower(kod_prodact)="'.addslashes(mb_strtolower($kod_prodact)).'" or LOWER(name) REGEXP "[^A-Za-zА-Яа-я0-9\+]'.DB::escape(preg_quote(mb_strtolower($kod_prodact))).'[^A-Za-zА-Яа-я0-9\+/]")';
            $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE id<>'.$tov['id'].$rf);
            while ($row=DB::fetch_assoc($result)) if(!in_array($row['id'],$aid)){
                if(isset($row['gr']) && $row['gr']!=$tov['gr'])continue; // todo добавить проверку всех групп
                //if(abs($row['price']-$tov['price'])/$tov['price']>0.5){echo '<br>Не объединил <a href="/shop/tovar'.$id.'">'.$id.'</a> и <a href="/shop/tovar'.$row['id'].'">'.$row['id'].'</a> из-за большой разницы в цене'; continue;}
                Tovar::AskObedin($tov,$row);
                $aid[]=$row['id'];
            }
        }

        //$tov['seo_url']=str2url((empty($tov['seo_url']) ? $tov['name'] : $tov['seo_url']));
        //if(!empty($tov['seo_url']) and $row=DB::Select("tovar",(empty($tov['id'])?'':"id<>'".$tov['id']."' and ")."seo_url='".addslashes($tov['seo_url'])."'" ))die('<h3>Ошибка!</h3><br>Два товара с одинаковым SEO_URL:<br><a onclick="return!window.open(this.href)" href="'.self::_GetVar($row,'url').'">'.$row['seo_url'].'</a>!');
        if(!DB::write_array('tovar',$tov)){/*echo "<span class='red b'>- НЕ СМОГ ДОБАВИТЬ ТОВАР!!!</span>";*/ return false;}
        if(isset($_GET['debug']))echo "<br>".DB::$query;
        if (empty($tov['id'])) {
            $tov['id'] = DB::GetInsertId('tovar');
            if (empty($tov['id'])) {
                echo "<span class='red b'>- НЕ СМОГ ДОБАВИТЬ ТОВАР!!!</span>";
                return true;
            } else {
                DB::Delete("category_link","tovar='".$tov['id']."'"); // на случай косяка в базе
                if ($tov['category']) foreach ($tov['category'] as $key => $value) {
                    DB::sql("INSERT IGNORE INTO `" . db_prefix . "category_link` (tovar, category) VALUES ('" . $tov['id'] . "', '" . $key . "')");
                    $f_ok = $f_ok || (DB::affected_rows() > 0);
                }
            }
        }
        if(isset($tov['type'])&&$tov['type']==tTYPE_TOVAR && empty($tov['supplier']))Tovar::SetFirstSupplier($tov['id']);

        return true;
    }

    /** Вывости запрос объединения товаров
     * @param array $tov
     * @param array $old - удаляемый
     * @return bool
     */
    static function AskObedin($tov, $old){
        if(!self::IsSovmestim($tov,$old['name'])&&!self::IsSovmestim($old,$tov['name']))return false;
        echo "<div class='".((abs($old['price']-$tov['price'])/$tov['price']>0.5)?'inbox2':'inbox3')."' id='to".$tov['id']."'>".
            "<a href='/api.php?tovar1=".$tov['id']."&tovar2=".$old['id']."' onclick=\"return ajaxLoad('to".$tov['id']."',this.href);\">Объединить:</a>".
            "<a style='margin-left:25px;color:gray' href='/api.php?incompatibility=".$old['id']."&tovar=".$tov['id']."' onclick=\"return ajaxLoad('to".$tov['id']."',this.href);\">Несовместимые</a><br>
#<a href='/shop/tovar".$tov['id']."' target=_blank>".$tov['id']."</a><b>".toHtml($tov['name'])."</b> ".$tov['price']."<br>
#<a href='/shop/tovar".$old['id']."' target=_blank>".$old['id']."</a><b>".toHtml($old['name'])."</b> ".$old['price']."</div>\n";
        return true;
    }


    static function nac_v($gr)
    {
        static $nac_v=[];
        if (isset($nac_v[$gr])) return $nac_v[$gr];
        elseif ($gr == 0) return '[gr=0]';
        else {
            if ($data = DB::Select('category',intval($gr))){
                $nac_v[$gr] = ($data['nac'] ? intval($data['nac']) : 0);
                return $nac_v[$gr];
            }else {
                DB::sql('UPDATE ' . db_prefix . 'tovar SET gr=0 WHERE gr=' . intval($gr));
                echo ('ОШИБКА! Небыло группы ' . $gr);
            }
        }
    }

    static function ImgArray($sp,$tov,$SizeName,$Size){
        $img = [];
        for ($i = 0; $i < 99; $i++) {
            //echo "<br>".path_tovar_image.$sp.$tov.($i?('_'.$i):'');
            $fil=Image::is_file(path_tovar_image.$sp.$tov.($i?('_'.$i):''));
            //echo "<br>".var_export($fil,!0);
            if(!$fil){
                if($sp =='p')break;
                if(!$fil0=Image::is_file(path_tovar_image.'p'.$tov.($i?('_'.$i):'')))break;
                if(Image::is_img($fil0)){
            $fil = path_tovar_image . $sp . $tov . ($i ? ('_' . $i) : '') . '.jpg';
                    //echo "<br>Делаю из ".$fil0;
                Image::Resize($_SERVER['DOCUMENT_ROOT'] . $fil0, $_SERVER['DOCUMENT_ROOT'] . $fil, $Size);
                }else $fil=$fil0;
            }
            $img[]=$fil;//ImgSrc($fil);
        }
        if (empty($img)) {
            $img[] = '/images/no' . $SizeName . '.gif';
            if (!is_file($_SERVER['DOCUMENT_ROOT'] . $img[0]) && is_file($_SERVER['DOCUMENT_ROOT'] . '/images/noimg.gif')) {
                Image::Resize($_SERVER['DOCUMENT_ROOT'] . '/images/noimg.gif', $_SERVER['DOCUMENT_ROOT'] . $img[0], $Size);
            }
        }
        return $img;
    }

    static function YML($out=false){
        define("MaxTimeLimit",600);		# максимальное время работы скрипта в секундах
        ignore_user_abort(true);
        set_time_limit(MaxTimeLimit);
        $fil=$_SERVER['DOCUMENT_ROOT'].'/shop/yml.yml';
        // нельзя вызывать чаще чем раз в 10 минут
        if( is_file($fil) && (time()-fileatime($fil)) <MaxTimeLimit ){if($out)$out="Уже идет формирование!"; return $out;}
        if($out)$out.="<br>\nФормирование YML<br>\n";
        file_put_contents($fil, "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
<yml_catalog date=\"".date("Y-m-d H:i")."\">
<shop>
<name>".$_SERVER['HTTP_HOST']."</name>
<company>".SHOP_NAME."</company>
<url>http://".$_SERVER['HTTP_HOST']."/shop/</url>
<currencies><currency id=\"RUR\"/></currencies>
<categories>\n");
        $query=DB::sql('SELECT * FROM '.db_prefix.'category');
        while ($data = DB::fetch_assoc($query))
            file_put_contents($fil, "<category id=\"".$data['id']."\">".toHtml($data['name'])."</category>\n", FILE_APPEND);//<category id="2" parentId="1">Детективы</category>
        file_put_contents($fil, "</categories>
<local_delivery_cost>0</local_delivery_cost>
<offers>\n", FILE_APPEND);
        if($out){$out.="Выгружено <b>".DB::num_rows($query)."</b> категорий<br>\n"; flush();}
        if(!is_file($_SERVER['DOCUMENT_ROOT']."/adm/country.txt")){
            $search = file_get_contents('', "http://comfortrostov.ru/adm/country.txt");
            file_put_contents($_SERVER['DOCUMENT_ROOT']."/adm/country.txt",$search );
        }
        $search = file($_SERVER['DOCUMENT_ROOT'] . "/adm/country.txt");
        $count=count($search); if($out){$out.= "Загружено <b>".$count."</b> стран<br>\n"; flush();}

        $query=DB::sql('SELECT * from '.db_prefix.'tovar WHERE sklad<4');
        while ($row = DB::fetch_assoc($query)){
            $tovar=new Tovar($row);

            $name=trim($tovar->name," \t\n\r\'\"*");
            $country='';
            for($i=0;$i<$count;$i++)if(strpos($name,$country=trim($search[$i]))!==false){
                if($out)$out.=str_replace($country, '<b>'.$country.'</b>', toHtml($name))."<br>\n";
                $name=str_replace($country, '', $name); // удаляю страну из наименования
                $name=str_replace('()', '', $name);
                $name=str_replace('  ', ' ', $name);
                break;
            }
            if(empty($name))continue;
            $pic=$tovar->imgBig[0];
            file_put_contents($fil, "<offer id=\"".$row['id']."\" available=\"".($row['sklad']<2?"true":"false")."\">
    <url>http://".$_SERVER['HTTP_HOST']."/shop/tovar".$row['id']."</url>
    <price>".$tovar->price."</price>
    <currencyId>RUR</currencyId>
    <categoryId>".$tovar->gr."</categoryId>
".(!is_file($_SERVER['DOCUMENT_ROOT'].$pic)?"":"    <picture>http://".$_SERVER['HTTP_HOST'].$pic."</picture>
")."    <name>".toHtml($name)."</name>
".(empty($row['brand'])?"":"    <vendor>".DB::GetName('brand',$row['brand'])."</vendor>
").(empty($row['kod_prodact'])?"":"    <vendorCode>".toHtml($row['kod_prodact'])."</vendorCode>
").(empty($row['description'])?"":"    <description>".toHtml(substr($row['description'],0,512))."</description>
").($row['sklad']<2?"":"    <sales_notes>предоплата</sales_notes>
").(!$country?"":"    <country_of_origin>$country</country_of_origin>
")."</offer>\n", FILE_APPEND);
        }
        file_put_contents($fil, "</offers>
</shop>
</yml_catalog>\n", FILE_APPEND);

//file_put_contents($fil.'.gz', gzencode(file_get_contents($fil), 9));

        if($out){$out.= "Выгружено <b>".DB::num_rows($query)."</b> товаров<br>\n"; flush();}

        $zipfile=substr($fil,0,-3).'zip';
        @unlink($zipfile);

        if($out){$out.= "Архивирую в ".$zipfile."<br>\n"; flush();}

        $zip=new ZipArchive;
        if($zip->open($zipfile,ZipArchive::CREATE)===TRUE){
            $zip->addFile($fil,'yml.yml');	// первый параметр - откуда взять, второй как назвать внутри архива
            $zip->close();
        }elseif($out)$out.= 'Ошибка открытия файла архива!';
        @unlink($fil);


// удаляю "хвосты" от архивов
        $zpath = dirname(__FILE__);	//yml.zip.L4sJ0A
        if($zhandle = opendir($zpath)){
            while(false !== ($zfil = readdir($zhandle)))
                if (substr($zfil,0,8)=="yml.zip."){
                    if($out)$out.= "Удаляю ".$zpath."/".$zfil."<br>\n";
                    @unlink($zpath."/".$zfil);
                }
            closedir($zhandle);
        }

        return $out;

    }

    static function LocateKod($kod, $name=''){
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE (kod_prodact='".addslashes($kod)."' or ean='".addslashes($kod)."') LIMIT 2");
        //echo "<br>LocateKod:".DB::$query." записей: ".DB::num_rows($query); exit;
        if (DB::num_rows($query) > 1) {
            if (strlen($name) > 10) {// попоробую найти по однозначности названия
                $query1=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE (kod_prodact='".addslashes($kod)."' or ean='".addslashes($kod)."') and name='".addslashes($name)."' LIMIT 2");
                if (DB::num_rows($query1) == 1) return Tovar::GetTovar(DB::fetch_assoc($query1), 1);
            }
            echo "<h2>НЕОДНОЗНАЧНОСТЬ для кода  " . $kod . ":</h2>";
            while ($data = DB::fetch_assoc($query)) {
                $tovar = new Tovar($data);
                echo "<br>#".$tovar->id." ".$tovar->kod_prodact."/".$tovar->ean." - ".$tovar->show_name;
            }
            exit;
            return null;
        }
        return Tovar::GetTovar(DB::fetch_assoc($query), 1);
    }

    static function Collection_url($brand_name, $brand_id, $collection_name = '', $collection_id = 0)
    {
        if (empty($collection_id)) return "/" . (SEO ? urlencode($brand_name) . '/' : 'shop/?brand=' . $brand_id);
        else return "/" . (SEO ? urlencode($brand_name) . "/" . urlencode($collection_name) :
            "shop.php?brand=" . $brand_id . "&amp;collection=" . $collection_id) . "";
    }

    static function Collection_anchor($brand_name, $brand_id, $collection_name = '', $collection_id = 0)
    {
        if (empty($collection_id)) return "<a href='/" . (SEO ? urlencode($brand_name) . '/' : 'shop/?brand=' . $brand_id) . "'>" . $brand_name . "</a>";
        else return "<a href='/" . (SEO ? urlencode(Convert::win2utf($brand_name)) . "/" . urlencode($collection_name) :
            "shop.php?brand=" . $brand_id . "&amp;collection=" . $collection_id) . "'>" . $collection_name . "</a>";
    }

    static function GetOst($tov)
    {
        $id = (empty($tov['id']) ? $tov : $tov['id']);
        $row = DB::fetch_assoc(DB::sql("SELECT sum(kol) as kol FROM (
            (SELECT sum(-kol) as kol FROM `" . db_prefix . "zakaz2` WHERE tovar='" . $id . "')
            UNION
            (SELECT sum(kol) as kol FROM `" . db_prefix . "prixod` WHERE tovar='" . $id . "')
            )q"));
        return ($row ? $row['kol'] : 0);

    }

    /**
     * @param array $tovar
     */
    public static function WriteInfo($tovar){
        if(empty($tovar['id']))die('Нет id!');
        $row=DB::Select('tovar',intval($tovar['id']));
        $info=js_decode($row['info']);
        foreach(self::$ar_info as $key) if(isset($tovar[$key])) $info[$key]=str_replace(',', '.',$tovar[$key]);
        DB::sql('UPDATE `'.self::db_prefix.'tovar` SET info="'.addslashes(js_encode($info)).'" WHERE id="'.intval($tovar['id']).'"');
    }


    static function RecalcPrice_old($row){
        $row['priceu']=floatval($row['priceu'])*($row['valuta']=='$'?Valuta::dollar():1);
        if($row['priceu']>0&&($row['supplier']<2||$row['valuta']=='*'))   $row['price']=$row['priceu'];

        $row['price0_r']=floatval($row['price0'])*($row['valuta0']=='$'?Valuta::dollar():1);

        $nacenka=($row['price']-$row['price0_r'])/$row['price0_r'];

        if($row['price1']<=0){  // розница -5%
            $row['price1']=floatval($row['price']) * ($nacenka>=1 ? 0.70 : 0.95);
        }
        if($row['price2']<=0){
            $row['price2']=$row['price0_r'] * ($nacenka>=1 ? 1.3 : 1.1);

        } // закупка +10%
        // если опт меньше розницы, то беру опт
        if($row['price1']>$row['price'])    $row['price']=$row['price1'];
        // если крупный опт больше мелкого
        if($row['price2']>$row['price1'])   $row['price2']=$row['price1'];

// если цена товара до 100рублей, то делаю наценку не меньше 100%
        if( $row['supplier']>1 && $row['price']<=100 && $row['price2']<>0 && ($row['price']/$row['price2'])<1.8 )  $row['price']=$row['price2']*1.8;
        return $row;
    }


    /** рассчитать розничную рублевую цену
     * @param array $tov - из Tovar или из Supplier_link
     * @param int $supplier
     * @param bool $Show = true-вернуть алгоритм рассчета цены в виде текстовой строки
     * @return int|mixed|string
     */
    static function cPrice(&$tov, $supplier=0, $Show=false){
        //if(isset($tov['type'])&&$tov['type']==tTYPE_USLUGA)return 0;
        if($supplier){
            if(!isset($supplier['valuta'])){
                if (!($supplier1=DB::Select('supplier',intval($supplier)))) die( 'ОШИБКА!! Нет поставщика '.$supplier.' для '.var_export($tov,!0).'<br>'.var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0));
                $supplier=$supplier1;
            }
            $valuta0=$supplier['valuta'];
            $tov['nac1']=$nac1=intval($supplier['nac']); // наценка конвертации, указана в справочнике поставщиков
            $supplier=$supplier['id'];
            if(!empty($tov['tovar'])&&!empty($tov['price0'])){// передали supplier_link
                $price0=$tov['price0'];
            }elseif(!empty($tov['id']) && $tov1=DB::Select('supplier_link','tovar='.$tov['id'].' and supplier='.$supplier)){
                $price0=$tov1['price0'];
                $valuta0=$tov1['valuta0'];
            }else{
                $price0=0; // у этого поставщика нет этого товара
            }
        }else{
            $valuta0=(isset($tov['valuta0'])?$tov['valuta0']:'');
            $price0=$tov['price0'];
            $supplier=(isset($tov['supplier'])?$supplier=intval($tov['supplier']) : 0);
            if (!$supplier || !($supplier1=DB::Select('supplier',intval($supplier)))){
                //if($valuta0!=''||empty($tov['price'])) die( 'ОШИБКА! Нет поставщика '.$supplier.' для '.var_export($tov,!0).'<br>'.var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0));
                $nac1=30;
            }else{
                $nac1=intval($supplier1['nac']); // наценка конвертации, указана в справочнике поставщиков
            }
            $tov['nac1']=$nac1;
        }

        if(!empty($tov['tovar'])&&!isset($tov['gr'])){ // передали supplier_link
            if (!($tov1=DB::Select('tovar',$tov['tovar'])))die('Нет такой записи #'.$tov['tovar'].'!');
            $tov['gr']=Tovar::_GetVar($tov1,'gr');
        }

        if($valuta0=='$' || $valuta0=='E') $nac1+=Nacenka; // если валюта в справочнике поставщиков - доллары или Евро, то добавляю базовую наценку
        elseif($supplier==1 && !$nac1)$nac1=Nacenka;
        elseif($valuta0>0)$nac1+=Nacenka; // валюта указана в самом прайсе

        $tov['nac2']=$nac2=(isset($tov['gr'])?self::nac_v($tov['gr']):0);	// наценка для группы товаров
        $price0_r=floatval($price0) * self::Kurs($valuta0); // закупочная цена в рублях
        $tov['price0_r']=$price0_r;
        $price=round($price0_r * (100+$nac1+$nac2)/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP);
        // если цена товара до 100рублей, то делаю наценку не меньше 100%
        if($tov['supplier']>1 && $price<=100 && ($nac1+$nac2)<91 ){
            if($Show)$price='</b>'.$price.', 200%=<b>'.max($price,intval($price0_r * 2)).'';
            else $price=max($price,intval($price0_r * 2));
        }
        if($Show)$price=/*var_export($tov,!0).*/trim(($price0==intval($price0)?intval($price0):$price0)).
            (in_array(trim($valuta0),['$','E'])?trim($valuta0).'*'.self::Kurs($valuta0) :'').
            '+'.$nac1.'%+'.$nac2.'%=<b>'.$price.'</b>';
        if($tov['supplier']==1 && $valuta0!='$' && @$tov['priceu']>0 ){
            if($Show)$price.='~<b>'.intval($tov['priceu']).'</b>';
            else $price=intval($tov['priceu']);
        }
        if( !$Show && $price0_r>0 ){ // просчитаю оптовые цены
// price1 - мелкий опт в рублях
// price2 - опт в рублях
            if(empty($tov['price1']) || $tov['price1']<$price0_r){  //  // мелкий опт, скидка от розничной цены discount_proc%, но не ниже оптовой
                // оптовая цена не может быть выше розничной.
                $tov['price1']=min($price, round($price*(100-discount_proc)/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP)); // округляю до целого рубля
            }
            if(empty($tov['price2']) || $tov['price2']<$price0_r){ // крупный опт
                // оптовая цена не может быть выше розничной.
                $tov['price2']=min($tov['price1'], round($price0_r*(100+max(10,$nac1))/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP));
            } // закупка +10%
            // если опт больше розницы, то беру розницу
            if($tov['price1']>$price && $price>$price0_r)    $tov['price1']=$price;
            // если крупный опт больше мелкого
            if($tov['price2']>$tov['price1'] && $tov['price1']>$price0_r)   $tov['price2']=$tov['price1'];
            if((empty($tov['valuta'])||$tov['valuta']!='*')&&$price>$price0_r)$tov['price']=$price;
        }

        if(!empty($tov['sklad']) && !isset(self::$_sklad_name[$tov['sklad']]))$tov['sklad']=4; // уточните наличие

        return $price;
    }

    static function Kurs($valuta){
        return trim($valuta)=='$'?Valuta::dollar():(trim($valuta)=='E'?Valuta::euro():1);
    }

    /** рассчитать розничную рублевую цену для данного товара
     * @param array $supplier - поля таблицы suplier+'suplier_cfg.nac' + 'tovar.gr'
     * @param bool $Show вернуть алгоритм рассчета цены в  виде текстовой строки
     * @return float|string
     */
    static function CalcPrice(&$supplier, $Show = false){

        if((empty($supplier['price'])||$supplier['price']<1) && $supplier['price0']>0){
            if($supplier['price0']<33){
                $supplier['price'] = 99;
            }elseif($supplier['price0']<50){
                $supplier['price'] = 120;
            }elseif($supplier['price0']<1000){
                $supplier['price']=3*$supplier['price0'];
            }elseif($supplier['price0']<3000){
                $supplier['price']=(3.55-0.00055*$supplier['price0'])*$supplier['price0'];
            }elseif($supplier['price0']<10000){
                $supplier['price']=1.9*$supplier['price0'];
            }else{
                $supplier['price']=1.4*$supplier['price0'];
            }
            $supplier['price0']=round($supplier['price0'], 0, PHP_ROUND_HALF_UP);
            $supplier['price']=max(99,round($supplier['price'], -1, PHP_ROUND_HALF_UP));
        }
        return $supplier['price'];

        /*
                if( trim($supplier['valuta']) == '$' || trim($supplier['valuta']) == 'E')Valuta::load();
                if(!isset($supplier['supplier']) && isset($supplier['supplier'])) $supplier['supplier']=$supplier['supplier'];// передали товар

                if(!empty($supplier['priceu'])){
                   $price=($Show ?
                       ($supplier['priceu'].'*'.(trim($supplier['valuta']) == '$' ? ('$*' . Valuta::dollar()) : (trim($supplier['valuta']) == 'E' ? ('€*' . Valuta::euro()) : ''))):
                    ($supplier['priceu']*(trim($supplier['valuta']) == '$' ? Valuta::dollar() : (trim($supplier['valuta']) == 'E' ? Valuta::euro() : 1) ))
                   );
                    return $price;
                }

                if(!isset($supplier['nac'])){ // наценка поставщика
                    if(empty($supplier['supplier'])){
                        $supplier['nac']=Nacenka;
                    }else{
                        $row=DB::Select('supplier', intval($supplier['supplier']) );
                        $supplier['nac']=(empty($row['nac'])? 0 : intval($row['nac'])); // наценка конвертации, указана в справочнике поставщиков
                    }
                    if ($supplier['valuta0'] == '$') $supplier['nac'] = ($supplier['nac'] + Nacenka); // если валюта в справочнике поставщиков - доллары, то добавляю базовую наценку
                    elseif ($supplier['valuta0'] == 'E') $supplier['nac'] = ($supplier['nac'] + Nacenka); // если валюта в справочнике поставщиков - доллары, то добавляю базовую наценку
                    //elseif ($supplier['supplier'] == 1 && !$supplier['nac']) $supplier['nac'] = Nacenka;
                    elseif ($supplier['valuta0'] > 0) $supplier['nac'] = ($supplier['nac'] + Nacenka); // валюта указана в самом прайсе
                }
                if(!isset($supplier['nac2'])){ // наценка группы товара
                    if(!isset($supplier['gr']) && isset($supplier['tovar']) ){
                        $row=DB::Select('tovar', 'id=' . intval($supplier['tovar']) );
                        $supplier['gr']=(empty($row['gr'])? 0 : $row['gr'] );
                    }
                    $supplier['nac2'] = (empty($supplier['gr']) ? 0: self::nac_v($supplier['gr']) ); // наценка для группы товаров
                }
                $price = intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1) * (100 + $supplier['nac'] + $supplier['nac2']) / 100);
                // если цена товара до 100рублей, то делаю наценку не меньше 100%
                if ( $price <= 100 && ($supplier['nac'] + $supplier['nac2']) < 91 ) {
                    if ($Show) $price = '</b>' . $price . ', 200%=<b>' . max($price, intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : (trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1)) * 2));
                    else $price = max($price, intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : (trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1)) * 2));
                }
                if ($Show) $price = trim(($supplier['price0'] == intval($supplier['price0']) ? intval($supplier['price0']) : $supplier['price0'])) .
                    (trim($supplier['valuta0']) == '$' ? ('$*' . Valuta::dollar()) : (trim($supplier['valuta0']) == 'E' ? ('€*' . Valuta::euro()) : '')) . '+' .
                    $supplier['nac'] . '%+' . $supplier['nac2'] . '%=<b>' . $price . '</b>';
                return $price;*/

    }

    /** Обновляю description и category из базы магазина или из интернета
     * @param array $data
     */
    static function GetTovarFromCloud($kod)
    {
        if (defined('db_prefix_shop')) { // я на сервере
            // попробую добыть данные с базы магазина
            if (($data = DB::Select("tovar", 'kod_prodact="' . addslashes($kod) . '"', db_prefix_shop))) {
                $data['category'] = DB::SelectId('category_link', 'tovar="' . $data['id'] . '"', 'category', db_prefix_shop);
                Out::message('GetTovarFromCloud: Нашел в базе интернет-магазина');
                return $data;
            }
        } elseif (defined('path_load')) {// http://shop.zagarrostov.dev/api.php?GetTovar=426&json
            list($headers, $body, $info) = ReadUrl::ReadWithHeader(path_load . "/api.php?json&GetTovar=" . urlencode($kod),false,['convert'=>charset,'cache'=>0]);
            if ($body) {
                $ar = js_decode($body);
                if (!empty($ar['name'])) {
                    //Out::message('Нашел на ' . path_load);
                    /*if (empty($ar['description']) && !empty($ar['description'])) {
                        $ar['description'] = $ar['description'];
                        unset($ar['description']);
                    }*/
                    unset($ar['ost']);
                    unset($ar['id']); // иначе перетирает существующий товар
                    if(!empty($ar['collection_name'])){$ar['collection']=$ar['collection_name']; unset($ar['collection_name']);}
                    if(!empty($ar['brand_name'])){$ar['brand']=$ar['brand_name']; unset($ar['brand_name']);}
                    if(!empty($ar['category'])){
                        $category=explode(',',str_replace('.',',',$ar['category']));
                        foreach($category as $i)$ar['category['.$i.']'] = ['value'=>'1','type'=>'checkbox','checked'=>'true'];
                    }
                    return $ar;
                } else Out::error('GetTovarFromCloud: Ошибка в ответе ' . $body);
            }
        } else Out::error('GetTovarFromCloud: неоткуда загрузить!');
        return null;
    }

    /** Обновляю description и category из базы магазина или из интернета
     * @param array $data
     */
    static function UpdateFromShop(&$data)
    {
        if (!empty($data['kod_prodact']) && ( empty($data['description']) || empty($data['category'])) ) { // я на сервере
            $ar = self::GetTovarFromCloud($data['kod_prodact']);
            if (!empty($ar['category'])) {
                self::UpdateCategory($data, $ar['category']);
            }
            if (!empty($ar['description'])) {
                self::UpdateComment($data, $ar['description']);

            }
            if (!empty($ar['category']) || !empty($ar['description'])) {
                Out::message("Обновил данные с сервера");
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $data
     * @param string $from
     */
    static function UpdateComment(&$data, $from)
    {
        if (empty($data['description'])) {
            $data['description'] = $from;
            DB::sql("UPDATE `" . db_prefix . "tovar` SET `description`='" . addslashes($data['description']) . "' WHERE id='" . $data['id'] . "'");
        }
    }

    /**
     * @param array $data
     * @param array|string $from
     */
    static function UpdateCategory(&$data, $from)
    {
        if (empty($data['category'])) {
            if (is_string($from)) {
                if (($from = explode(',', $from))) {
                    $from = array_fill_keys($from, 1);
                } else return;
            }
            if (is_array($from)) {
                $data['category'] = $from;
                if ($data['category']) foreach ($data['category'] as $key => $value) if (empty($row['category'][$key]) && $value != '0') {
                    DB::sql("INSERT IGNORE INTO `" . db_prefix . "category_link` (tovar, category) VALUES ('" . $data['id'] . "', '" . $key . "')");
                }
            }
        }
    }

    /**
     * возвращает непросроченный абонемент данного клиента на данную услугу(вид тренировок)
     * @param int|array $klient
     * @param int|array $tovar - если 0, возвращает все абонементы клиента
     *                          если > то конкретный абонемент
     *                   - может быть задан как кодом услуги, так и кодом абонемента
     * @return array
     */
    static function getAbonement($klient,$tovar=0){
        if(!empty($tovar)&&!is_array($tovar))$tovar=DB::Select('tovar',$tovar);
        if(!empty($tovar)&&is_array($tovar)){
            if($tovar['type']==tTYPE_USLUGA){ // ищу абонементы для этой услуги
                $ids=DB::SelectId('tovar','type='.tTYPE_ABON.' and tovar='.$tovar['id']);
            }else{ // это конкретный абонемент
                $ids=$tovar['id'];
            }
        }else{
            $ids=DB::SelectId('tovar','type='.tTYPE_ABON);
        }
        if (!is_array($klient)) $klient = DB::Select("user",intval($klient));
        $rows=[];
        $query=DB::sql("SELECT * FROM `".db_prefix."kart` WHERE user='".intval($klient['id'])."'".($tovar?' and tovar IN ('.$ids.')':'')." LIMIT 1");
        //$query = DB::sql("SELECT * FROM `".db_prefix."zakaz` as zakaz,`".db_prefix."zakaz2` as zakaz2 WHERE zakaz.id=zakaz2.zakaz and time>'".date('Y-m-d',strtotime('-1 year'))."' and zakaz.user=".intval(is_array($klient)?$klient['id']:$klient).($tovar?' and zakaz2.tovar IN ('.$ids.')':''));
        while($data = DB::fetch_assoc($query)){
// todo товар "Сертификат" нельзя отоваривать если продаю сертификат

            if(!($tovar0 = Tovar::GetTovar($data['tovar']))){
                Out::error(date('d.m.y в h:i:s',$data['time'])." - Продан абонемент, описание которого отсутствует в справочнике");
                continue;
            }

            $tov=new Tovar($tovar0);
            if ($data['dat_end'] < date('Y-m-d')) {
                $data['info'] = 'Абонемент '.$data['id'].' просрочен';
                if($data['dat_end'] < date('Y-m-d',strtotime('-7 day')))continue; // сильно просрочен
            }elseif (($t = ceil((strtotime($data['dat_end']) - time()) / 60 / 60 / 24)) < 7){
                $data['info']= 'Абонемент '.$data['id'].' осталось ' . $data['ost'] . ' '.$tov->ed($data['ost']) . ', '.$t . ' ' . num2word($t, ["день", "дня", "дней"]). ', до ' . date('d.m.Y',strtotime($data['dat_end']));
            }else{
                $data['info']= 'Абонемент '.$data['id'].' осталось ' . $data['ost'] . ' '. $tov->ed($data['ost']) . ', до ' . date('d.m.Y',strtotime($data['dat_end']));
            }



            $data['type'] = tTYPE_ABON_USLUGA; //tTYPE_USLUGA;
            $data['kol.max'] = $data['ost'];
            $data['kol'] = $data['ost'];

            if (!isset($tovar0['parent']) || $tovar0['parent']['type'] != tTYPE_ABON){ Out::error("Это не абонемент: ".var_export($data,!0)); continue;}
            $data['tovar'] = $tovar0['name']; // исходный товар
            $data['tovar_cs'] = $tovar0['id'];
            $data['ed'] = $tovar0['ed'];
            if ($tovar0['kol'] == 1) {
                $data['kol.max'] = min($data['kol'], $data['kol.max']);
                $data['kol.readOnly'] = 'true';
            }
//elseif($tovar0['parent']['kol']==0)Out::err("Это сертификат!");

            $data['klient'] = $klient['fullname'] . " " . Out::format_phone($klient['tel']);
            $data['klient_cs'] = $klient['id'];

            //$data = Tovar::GetPrognoz($klient, new Tovar($tovar0), $data);
            unset($data['kol']);
            if($tovar)return $data;
            $rows[]=$data;
        }
        return $rows;
    }

    /**
     * @param $klient
     * @param $type
     * @param $tovar array|int|object
     * @return array
     */
    static function GetVipDiscount($klient, $type, $tovar){
        if( ($vip_kart=Kart::VIP($klient)) && !empty($vip_kart['tovar']['discount']) ){
            $klient_id=(is_array($klient) ? $klient['id'] : $klient);
            $tovar_id=(is_array($tovar) ? $tovar['id'] : (is_object($tovar)? $tovar->id : $tovar) );
            $vip_discount=$vip_kart['tovar']['discount'];
            $info="VIP ".$vip_kart['id']." до ".date("d.m.Y",strtotime($vip_kart['dat_end']));
            if($type==tTYPE_TOVAR && !empty($vip_discount[tTYPE_TOVAR]) )   return array(floatval($vip_discount[tTYPE_TOVAR]),$info ); // Скидка на товар
            elseif($type==tTYPE_RASX && !empty($vip_discount[tTYPE_RASX]) ){
                // если это вторая расходка за эту операцию скидка =0 !
                $query=DB::sql("SELECT * FROM `".db_prefix."sale2` as sale2, `".db_prefix."sale` as sale
                    WHERE sale2.sale=sale.id and sale.time>='".date("Y-m-d",strtotime("-1 day"))."' and sale.klient='".$klient_id."' and sale2.tovar='".$tovar_id."' LIMIT 1");
                if($sale = DB::fetch_assoc($query)){
                    $info.="<br>\nРасходка бесплатная 1 раз в два дня!";
                    return array(0,$info);
                }
                $tovar_price=(is_array($tovar) ? $tovar['price'] : (is_object($tovar)? $tovar->price : 0 ) );
                if(intval($tovar_price)>60)return array(0,$info); // очки не могут быть бесплатными
                return array(floatval($vip_discount[tTYPE_RASX]),$info );
            }elseif($type==tTYPE_USLUGA && !empty($vip_discount[tTYPE_USLUGA]) ) return array(floatval($vip_discount[tTYPE_USLUGA]),$info ); // Скидка на услуги
        }
        return array(0,'');
    }

    /**
     * @param array|integer $klient
     * @param Tovar $tovar
     * @param array $data
     * @return array
     */
    static function GetPrognoz($klient, $tovar, $data=[]){
        if(isset($klient['id']))$klient=$klient['id'];
        if($klient==ANONIMOUS_KLIENT){
            // прогноза по анониму не бывает!
        }elseif($tovar->type==tTYPE_TOVAR){//косметика
            $query=DB::sql("SELECT * FROM `".db_prefix."zakaz` as zakaz,`".db_prefix."zakaz2` as zakaz2,`".db_prefix."tovar` as tovar ".
                "WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and klient='".$klient."' and type='0' ORDER by zakaz.time DESC LIMIT 3");
            while($zakaz = DB::fetch_assoc($query)){
//print_r($zakaz); Array ( [id] => 136 [time] => 2012-12-31 22:29:29 [klient] => 2 [user] => 1181 [zakaz] => 2 [tovar] => 0 [kart] => 0 [device] => 0 [discount] => 50 [kol] => 15.00 [summ] => 450.00 [brand] => [name] => Luxx Black 50x Bronzer [type] => 0 [price] => 900 [maxdiscount] => 0 [kod_prodact] => [ean] => 1844-01 [description] => Беспрецедентная 50ти кратная формула для невероятно тёмного бронзового загара. Микрочастицы самого чистого золота, пудра из черного жемчуга и алмазов придадут Вашей коже соблазнительное сияние, которое усилит глубину загара. 50ти кратный бронзатор с углу [srok] => 0 [ost] => [price0] => 297.00 [ed] => )
                $data['info']=(empty($data['info'])?"":$data['info']."<br>\n").date('d.m.y',strtotime($zakaz['time'])).' '.$zakaz['name'].' '.outSumm($zakaz['kol']).'мл.';
            }
            if(!isset($data['info']))$data['info']='Косметику не приобретал';
            $data['kol']=1;
        }else{// ищу конкретную услугу
            //если это абонемент, то ищу конкретную услугу
            if(($zakaz = DB::fetch_assoc(DB::sql("SELECT * FROM `".db_prefix."zakaz2` as zakaz2, `".db_prefix."zakaz` as zakaz ".
                "WHERE zakaz2.zakaz=zakaz.id and zakaz.user='".$klient."' and zakaz2.tovar='".
                ($tovar->type==tTYPE_ABON?$tovar->tovar: $tovar->id)."' ORDER by zakaz.time DESC LIMIT 1")))){
                //var_dump($tovar); var_dump($zakaz);
                if($tovar->type==tTYPE_USLUGA && in_array($tovar->id,array_keys(self::$repeat_uslug_list))){ // костыль 4 -  автозагар, 16-автозагар повторно todo сделать через две связанных услуги
                    if($zakaz['time']>=date('Y-m-d H:i:s',strtotime("-1 months"))){
                        $zakaz['price']=$zakaz['summ']/$zakaz['kol'];
                        $tov=new Tovar(self::$repeat_uslug_list[$tovar->id]);
                        //var_dump($tov);
                        if($tov && $zakaz['price']>$tov->price){
                            $data['price2']=$tov->price;
                            $data['discount']=round(($data['price']-$data['price2'])/$data['price']*100,4);
                            $data['info']=(empty($data['info'])?"":$data['info']."<br>\n")."Скидка при повторном посещении";
                            $data['comment']="Скидка при повторном посещении: ".date('d.m.y',strtotime($zakaz['time']));
                        }else $data['info']=(empty($data['info'])?"":$data['info']."<br>\n")."<span class='red'>Скидка уже использованна!</span>";
                    }else $data['info']=(empty($data['info'])?"":$data['info']."<br>\n")."<span class='red'>Скидка просрочена</span>";
                    $data['kol']=1;
                } // Скидка на товар
//var_dump( $tovar->child );
//print_r( $tovar->child->ed);
                $data['info']=(empty($data['info'])?"":$data['info']."<br>\n").'Прошлое посещение: '.date('d.m.y',strtotime($zakaz['time'])).' - <b>'.$zakaz['kol'].'</b> '.
                    ( $tovar->type==tTYPE_ABON ? $tovar->child->ed($zakaz['kol']) : $tovar->ed($zakaz['kol']) ).
                    (($tovar->type==tTYPE_USLUGA || $tovar->type==tTYPE_ABON) && $zakaz['device'] ?', кабинка - '.$zakaz['device']:'').'.';
                //$data['kol']=$zakaz['kol'];
            }else{
                $data['info']=(empty($data['info'])?"":$data['info']."<br>\n").'Первое посещение.';
                if($tovar->type==tTYPE_TOVAR)$data['kol']=1;//($tovar['type']==2?1:5);
            }
        }
        return $data;
    }

    /** нажали кнопку "последний клиент" для добавления ему продажи
     * @return array|null|string
     */
    static function LastKlient(){
        if(!empty($GLOBALS['LastKlient']))return $GLOBALS['LastKlient'];
        if(($zakaz=DB::Select("zakaz","user='".User::id()."' and time<'".date('Y-m-d H:i:s')."' ORDER by id DESC"))){ // на случай перевода времени
            $zakaz2=DB::Select("zakaz2","zakaz='".$zakaz['id']."' and sertif>0");
            if(($row=User::GetUser($zakaz['user']))){
                if($zakaz2){
                    if($zakaz2['sertif'])$row['klient_kart']=$zakaz2['sertif'];
                    /* todo VIP
                    $kart= new Kart($zakaz2['kart']);
                    if(!empty($kart->discount))$row['discount']=$kart->discount;
                    */
                }
                return $GLOBALS['LastKlient']=$row;
            }else echo "Нет клиента id:".$zakaz['user']."!";
        }else Out::err("Нет продаж у администратора ".User::id()."!");
        return '';
    }

    /** сохранение продажи
     * @param $ar
     * @return bool
     */
static function Sale($ar){
        $type=intval($ar['type']);
        $comment=(isset($ar['comment'])?trim($ar['comment']):"");
        $vip_discount=0;
        if(empty($ar['tovar'])&&!empty($ar['tovar_cs']))$ar['tovar']=$ar['tovar_cs'];
        if(isset($ar['tovar'])&&$type==tTYPE_RASX){ // кнопка конкретная расходка
            $tovar=Tovar::GetTovar(intval($ar['tovar'])); if(!$tovar)Out::err("Нет товара № ".intval($ar['tovar']));
            $klient=Tovar::LastKlient(); if(!$klient) return false;
//PaymentLog('LastKlient:'.print_r($klient,true));
            $kol=1;
            list($vip_discount,$info)=Tovar::GetVipDiscount($klient, $type, $tovar);
            $discount=max( ($klient['adm']>=5?100:floatval($klient['discount0'])), $vip_discount );
            if($discount)$comment.=trim(($vip_discount>0?"VIP ":"").($klient['adm']>=5?"adm:".$klient['adm']." ":"").($klient['discount0']?"Скидка клиента:".$klient['discount0']."%":"") );
            $device=0;
            $kart=0;

        }elseif($type==tTYPE_TOVAR||$type==tTYPE_RASX){// косметика и расходка
            $tovar=Tovar::GetTovar(intval($ar['tovar'])); if(!$tovar)Out::err("Нет товара № ".intval($ar['tovar']));
            $klient=intval(@$ar['klient_cs']); if(!$klient)$klient=ANONIMOUS_KLIENT;
            $kart=0;
            $kol=intval($ar['kol']);
            //$price2=intval($ar['price2']); // цена со скидкой
            $device=0;
            list($vip_discount,$info)=Tovar::GetVipDiscount($klient, $type, $tovar);
            if($type==tTYPE_RASX && $vip_discount>0){
                $kol=1; // расходка только по 1 шт на клиента в сутки
                if(intval($tovar['price'])>60)$vip_discount=0; // очки не могут быть бесплатными
                //var_export($tovar);
            }
            $discount=max( floatval($ar['discount']), $vip_discount );

        }elseif($type==tTYPE_USLUGA){//Оказание разовой услуги
            $klient=intval(@$ar['klient_cs']); if(!$klient)$klient=ANONIMOUS_KLIENT;
            $kol=(isset($ar['kol'])?intval($ar['kol']):1);
            $discount=(isset($ar['discount'])?floatval($ar['discount']):0);
            $device=intval($ar['device']);
            $tovar=Tovar::GetTovar(intval($ar['tovar'])); if(!$tovar)Out::err("Нет товара № ".intval($ar['tovar']));
            $kart=0;

        }elseif($type==tTYPE_ABON_USLUGA){//услуги по абонементу
            if (empty($ar['kart']) && !empty($ar['klient_cs'])) {
                if (!($kart = Tovar::getAbonement($ar['klient_cs'], $ar['tovar']))) Out::err("У клиента нет абонемента!");
                $kart=$kart['id'];
            }else{
                $kart=intval($ar['kart']);
            }

            $device=intval($ar['device']);
            $kol=(isset($ar['kol'])?intval($ar['kol']):1);
            $data=Kart::GetKart($kart); if(!$data)Out::err("Нет абонемента № ".$kart);
            $tovar=Tovar::GetTovar($data['tovar']); if(!$tovar)Out::err("Нет товара № ".$data['tovar']);
            $klient=$data['user'];
            if($tovar['kol']==1)$kol=1;
            if($kol>$data['ost'])Out::err("Остаток только ".$data['ost']." НЕ списано ".$kol."!");
            //$kol=min($kol,$data['ost']);
            if($data['dat_end']<date('Y-m-d')){ // если просрочен более 7 дней - то совсем нельзя!
                if($data['dat_end']<date('Y-m-d',strtotime("-7 day")))Out::err("Абонемент просрочен более чем на 7 дней и НЕ списан!");
                else				Out::message("Абонемент списан, хоть и просрочен!");
            }
            $discount=0;

        }elseif($type==tTYPE_ABON){// продажа абонемента
            $tovar=Tovar::GetTovar(intval($ar['tovar']),1); if(!$tovar)Out::err("Нет товара id:".intval($ar['tovar']));
            $id=intval($ar['id']);
            if($id>0){
                if(DB::Select('kart',$id))Out::err("Абонемент № ".$id." уже есть!");
            }
            if(empty($ar['klient_cs']))Out::err("Не передан клиент!");
            $klient=intval($ar['klient_cs']); if(!$klient)Out::err("Не указан клиент!");
            $kol=1;
            $discount=floatval($ar['discount']);
            $device=0;
            // добавляю абонемент и получаю его номер
            $kart=Kart::Add($tovar, $klient, $id); if(!$kart)Out::err("Абонемент не добавлен!");

        }else{Out::err("Ошибка ",$type); return false;}

        if($kol<1)Out::err("Количество должно быть положительным числом!");

        if(!empty($ar['klient_kart'])){
            $id=intval($ar['klient_kart']);
            //PaymentLog('1.sertif='.$id);
        }elseif(isset($klient['klient_kart']) && is_array($klient) && intval($klient['klient_kart'])>0){ //LastKlient
            $id=intval($klient['klient_kart']);
            //PaymentLog('2.sertif='.$id.", klient=".print_r($klient,true));
        }else $id=0;

        if($id>0){
            $sertif=Kart::GetKart($id); if(!$sertif)Out::err("Нет сертификата № ".$id);
            $sertif['tovar']=Tovar::GetTovar($sertif['tovar'],true);
            if($sertif['tovar']['kol']!=0)Out::err("Это абонемент № ".$id.", а не сертификат!");
            $klient=$sertif['user'];
            //PaymentLog('2.klient='.$klient);
            $discount=0;
        }else $sertif=0;

        if(!is_array($klient)){
            if(!empty($GLOBALS['LastKlient']['id']) && $GLOBALS['LastKlient']['id']==$klient)$klient=$GLOBALS['LastKlient'];
            elseif(!($klient=User::GetUser($klient)))  Out::err("Нет клиента!");
        }
        $GLOBALS['LastKlient']=$klient;
        // если последняя запись была по этому же клиенту и прошло менее 10 минут и товар не повторяется с таким же в текущем чеке, то объединяю в один чек
        $id=0;
        if(!isset($ar['new'])){
            if(($zakaz=DB::Select("zakaz","manager='".User::id()."' and `time`>'".date("Y-m-d H:i:s",strtotime("-10 minutes"))."' ORDER by time DESC")) && ($zakaz['user']==$klient['id'])){ // если это повтор существующей операции, то это новый человек
                // горизонталка и вертикалка - в разные чеки
                if(!DB::Select("zakaz2", "zakaz='".$zakaz['id']."' and (tovar='".$tovar['id']."'".
                    (in_array($tovar['id'],explode(',',tTOVAR_NOT_UNION))?' or tovar IN ('.tTOVAR_NOT_UNION.')':'').')')) $id=$zakaz['id'];
            }
        }
        if(defined('db_prefix_shop')){
            Out::err("Изменение доступно только в самой студии!");
        }elseif($id==0){
            DB::sql("INSERT INTO `".db_prefix."zakaz` ( `time`, `user`, `manager`) VALUES ('".date('Y-m-d H:i:s')."', '".$klient['id']."', '".User::id()."')");
            $id=DB::id();
            //$_SESSION['last_time']=time();
        }else{
            DB::sql("UPDATE `".db_prefix."zakaz`	SET `zp`='0', zpu='0' WHERE id='".$id."' LIMIT 1"); // очищаю з/плату
        }
//echo "<br>klient['adm']=".$klient['adm'].", discount=".$discount; var_dump($tovar);
        if($klient['adm']<5 && $discount){// если дисконт для этого товара запрещен - обнуляю его
            $discount_ok=false;
            if($tovar['type']==tTYPE_USLUGA && in_array($tovar['id'],array_keys(self::$repeat_uslug_list))){ // костыль 4 -  автозагар, 16-автозагар повторно
                $query=DB::sql("SELECT * FROM `".db_prefix."zakaz2` as zakaz2, `".db_prefix."zakaz` as zakaz WHERE zakaz2.zakaz=zakaz.id and zakaz.user='".$klient['id']."' and zakaz2.tovar='".$tovar['id']."' ORDER by time DESC LIMIT 1");
                if($zakaz = DB::fetch_assoc($query)){
                    if($zakaz['time']>=date('Y-m-d H:i:s',strtotime("-1 months"))){
                        $zakaz['price']=intval($zakaz['summ']/$zakaz['kol']);
                        $tov=Tovar::GetTovar(self::$repeat_uslug_list[$tovar['id']],1);
//echo "<br>zakaz['price']=". $zakaz['price'].", tov['price']=".$tov['price'];
                        if($tov && $zakaz['price']>$tov['price']){
                            if(intval($tov['price'])==round($kol*$tovar['price']*(100-$discount)/100,0)){// " Скидка при повторном посещении!";
                                $discount_ok=true;
                            }else $comment.=" Цена повторной услуги не соответствует!";
                        }elseif($discount>$klient['discount1'] )$comment.=" Скидка уже использованна!";
                    }else $comment.=" Скидка просрочена (".date('d-m-y H:i:s',strtotime($zakaz['time'])).")!";
                }
            }
            if($discount_ok || $discount<=$vip_discount){

            }elseif( $tovar['type']==tTYPE_TOVAR && $klient['adm']==uADM_OPT ){
                $comment.=" Мелкий опт";
                if($discount> round(($tovar['price']-($d=round($tovar['price0']*(100+tOPT_PROC)/100,0)))/$tovar['price']*100,5))
                    Out::message("Внимание: минимальная оптовая цена ".$d."!");
            }elseif( $discount>$tovar['maxdiscount'] ){
                Out::message("Внимание: скидка ".$discount.", максимальная скидка ".$tovar['maxdiscount']."!");
            }elseif($discount> $d=intval($tovar['type']==tTYPE_USLUGA ? $klient['discount1'] : $klient['discount0']))
                Out::message("Внимание: скидка ".$discount.", скидка клиента ".$d."!");
        }
        if($type==tTYPE_ABON_USLUGA)$summ=0; //услуги по абонементу
        else $summ=round($kol*$tovar['price']*(100-$discount)/100,0);

        if($sertif){
            $summ_sertif=min($sertif['ost'],$summ);
            if($summ_sertif>0){
                $summ-=$summ_sertif; if($summ)Out::message("Необходимо доплатить ".outSumm($summ)."руб.");
                $sertif=$sertif['id'];
                DB::sql("UPDATE `".db_prefix."kart`	SET `ost`=ost-".$summ_sertif." WHERE id='".$sertif."'");
            }else{
                Out::message("Оплата за наличные ".outSumm($summ)."руб.");
                $sertif=0;
            }
        }else $summ_sertif=0;

        DB::sql("INSERT INTO `".db_prefix."zakaz2` SET `zakaz`='".$id."', `tovar`='".$tovar['id']."', `kart`='".$kart."', `device`='".$device."',".
            "`discount`='".$discount."', `kol`='".$kol."', `price`='".($summ/$kol)."',`sertif`='".$sertif."', `summ_sertif`='".$summ_sertif."',".
            "`comment`='".$comment."'".(empty($ar['time'])?'':", `time`='".date('Y-m-d H:i:s',$ar['time'])."'"));

        if($kart&&$type==tTYPE_ABON_USLUGA){//услуги по абонементу - уменьшаю остаток
            DB::sql("UPDATE `".db_prefix."kart`	SET `ost`=ost-".$kol." WHERE id='".$kart."'");
        }elseif($type==tTYPE_TOVAR||$type==tTYPE_RASX){// уменьшаю остаток косметики или расходки
            DB::sql("UPDATE `".db_prefix."tovar` SET `ost`=ost-".$kol." WHERE id='".$tovar['id']."'");
        }
        //Out::message("Сохранил!");
        //header("location: http://".$_SERVER['HTTP_HOST']."/work.php");
        //Out::mes("","reload('window.location=\"work.php\"')");}
    return true;
}

    /** возвращает информацию по товару или по товару и клиенту
     * @param integer $tovar
     * @param integer $klient
     * @return array
     */
    static function getInfo($tovar,$klient=0){
        $data=[];
        if(($tovar=new Tovar($tovar,1))){
            $data['tovar']=$tovar->show_name; // исходный товар
            $data['price']=$tovar->price;
            $data['tovar_cs']=$tovar->id;
            if($tovar->maxdiscount)$data['discount.max']=$tovar->maxdiscount;
            else $data['discount.max']=100;
            if($tovar->kol==1){$data['kol']=1; $data['kol.disabled']=true;}
            else $data['kol.disabled']=false;
        }else Out::err("Нет такого товара!");

        if($klient) {
            if (($klient = new User($klient))) {
                $data['klient'] = $klient->fullname. " " . Out::format_phone($klient->tel);
                $data['klient_cs'] = $klient->id;
                $CalcPrice = 0;
                // если у клиента есть абонемент на эту услугу, учитываю его
                if(($kart=Tovar::getAbonement($klient->id, $tovar->id))) {
                    $data['kart']=$kart['id'];
                    $data['type']=tTYPE_ABON_USLUGA;
                    unset($kart['id'],$kart['time'],$kart['user']);
                    $data=array_merge($data,$kart);
                    $data['price']=0;
                    $data['price2']=0;
                    /*$data['type']=(empty($kart)?tTYPE_USLUGA:tTYPE_ABON_USLUGA);
                    $data['comment']='Услуга';
                    $data['klient_cs']=$klient->id;
                    $data['kart']=$kart['id'];*/

                }elseif ($klient['adm'] == uADM_ADMIN || ($klient['adm'] == uADM_WORKER && $tovar->type == tTYPE_RASX)) {
                    $data['price2'] = 0;
                    $data['discount'] = 100;
                } elseif ($klient['adm'] >= uADM_WORKER) {
                    $data['price2'] = $tovar->price0;
                    $data['discount'] = round(($tovar->price - $data['price2']) / $tovar->price * 100, 5);
                } elseif (($tovar->type == tTYPE_TOVAR || $tovar->type == tTYPE_RASX) && $klient['adm'] == uADM_OPT) {
                    $data['price2'] = round($tovar->price0 * (100 + tOPT_PROC) / 100, ($tovar->type == tTYPE_RASX ? 1 : 0), PHP_ROUND_HALF_UP); // округляю до целого рубля
                    $data['discount'] = round(($tovar->price - $data['price2']) / $tovar->price * 100, 5);
                    $data['kol.disabled'] = false;
                } else {
                    if ($tovar->type == tTYPE_TOVAR || $tovar->type == tTYPE_RASX) $data['discount'] = intval($klient['discount0']); // Скидка на товар
                    else $data['discount'] = intval($klient['discount1']); // Скидка на услуги
                    $CalcPrice = 1;
                }
                if ($klient['adm'] != uADM_ADMIN && $klient['adm'] != uADM_WORKER && $tovar->type != tTYPE_ABON) {
                    // проверяю, есть ли по этому клиенту непросроченный статус VIP и если есть, определяю возможность предоставления ему скидки
                    list($vip_discount, $info) = Tovar::GetVipDiscount($klient, $tovar->type, $tovar);
                    //var_export($data);
                    //echo "~".$data['discount'].", ".$vip_discount;
                    if ($vip_discount > 0) {
                        $data['info'] = (empty($data['info']) ? "" : $data['info'] . "<br>\n") . $info;
                        $data['discount'] = max($data['discount'], $vip_discount);
                        $CalcPrice = 1;
                    }
                }
                if ($CalcPrice) $data['price2'] = round($tovar->price * (100 - floatval($data['discount'])) / 100, ($tovar->price > 5 ? 0 : 2), PHP_ROUND_HALF_UP);
            } else Out::err("Нет такого клиента!");
            $data = Tovar::GetPrognoz($klient->id, $tovar, $data);

            if ($tovar->maxdiscount) $data['discount.max'] = $tovar->maxdiscount;
            else $data['discount.max'] = 100;
        }else{
            $data=[];
            if($tovar=new Tovar(intval($_GET['tovar']))){
                $data['tovar']=$tovar->show_name; // исходный товар
                $data['price']=$tovar->price;
                $data['tovar_cs']=$tovar->id;
                if($tovar->kol==1){$data['kol']=$tovar->kol; $data['kol.disabled']=true;}
                else $data['kol.disabled']=false;
            }else Out::win("Нет такого товара!",2);
            //var_dump($tovar);exit;
            if($tovar->maxdiscount)$data['discount.max']=$tovar->maxdiscount;
            else $data['discount.max']=100;

            if($tovar->type==tTYPE_USLUGA){ // услуги, указать на каких аппаратах возможны
                //var_dump($tovar->cab);exit;
                $cab=str_replace('.',',',$tovar->cab);
                if(strlen($cab)>0){// разрешенные аппараты
                    $ar=explode(",",$cab);
                    $data['device.min']=min($ar);
                    $data['device.max']=max($ar);
                    $data['device.step']=1;
                    if($data['device.min']==$data['device.max']){
                        $data['device.value']=$data['device.min'];
                        $data['device.disabled']=true;
                    }else
                        $data['device.disabled']=false;
                }else{
                    $data['device.min']="";
                    $data['device.max']="";
                    $data['device.step']="";
                    $data['device.disabled']=false;
                }
            }

        }
        return $data;
    }


} // class Tovar
