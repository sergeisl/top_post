<?
function isPrivDel($tbl,$id){
    if(!$id)Out::err("Неверный номер абонемента!");
    if(User::is_admin())return;
    //значения счетчиков старше одного дня изменить или удалить может только руководитель
    $row = DB::Select($tbl,intval($id));
    if($row && $row['time']<date('Y-m-d H:i:s',strtotime("-1 day"))) Out::err("Вам это недоступно, обратитесь к руководителю!");
}

/**
 * @param null $parent
 * @param array $options
 * @return array
 */
function getMenu($parent=null, $options=[])
{
    $rows=[];
    $query = DB::sql('SELECT * FROM ' . db_prefix . 'pages WHERE parent='.intval($parent).' and `services`&2=0 ORDER BY sort');
    while (($menu = DB::fetch_assoc($query))) {
        $rows[]=['id'=>$menu['id'], 'link' => BuildUrl('pages', $menu,0), 'url'=>BuildUrl('pages', $menu,1), 'name'=>$menu['name'], 'menu_name' => $menu['menu_name']];
    }
    return $rows;
}

/** вернуть все возможные фильтры для страниц
 * @param $parent
 * @return array
 */
function getPagesFilters( $parent ) {
    $pages = DB::Select2Array( 'pages', 'parent = '.$parent);
    $res = ['date' => [],'tag' => []];
    foreach($pages as $p) {
        $res['date'][] = date('Y-m', strtotime($p['date0']));
        $res['tag'] = array_merge( $res['tag'], explode(';', $p['keywords']) );
    }

    $res['date'] = array_unique($res['date']);
    $res['tag'] = array_unique($res['tag']);
    sort($res['date']);
    sort($res['tag']);
    return $res;
}

/** возвращает список детенышей
 * @param null|integer $parent - для какой страницы вернуть детенышей, если не задано, то для текущей
 * @param $options - link_as_html, perpage
 * @param null $bar
 * @return array
 */
function getPages($parent=null, $options=[]){

    if (is_array($parent) && empty($options) ) {
        $options = $parent;
        $parent = null;
    }

    if(is_null($parent)){
        $parent = $GLOBALS['page']['id'];
    }


    global $getPagesBar,$getPagesObj;
    $getPagesObj = new kdg_bar(['perpage' => get_key($options, 'perpage', 500), 'tbl' => db_prefix . 'pages',
        'sql'=>' WHERE parent=' . intval($parent) . (isset($options['sql']) ? ' and ' . $options['sql'] : '') ]);

    if(!empty($_REQUEST['tag'])) $getPagesObj->sql.=' and keywords LIKE "%'.addslashes($_REQUEST['tag']).'%"';
    if(!empty($_REQUEST['date']))$getPagesObj->sql.=' and date0 LIKE "'.addslashes($_REQUEST['date']).'%"';

    $getPagesBar=$getPagesObj->out(!0);
    $query = $getPagesObj->query(); // $getPagesBar->Out(true);
    $rows=[];
    while (($data = DB::fetch_assoc($query))){
        $rows[]=$data;
    }

    foreach($rows as &$row) {
        $row['link'] = BuildUrl( 'pages', $row, 0 );
        $row['url']  = BuildUrl( 'pages', $row, 1 );
        $row['imgs'] = glob( $_SERVER['DOCUMENT_ROOT'] . '/images/pages/p'.$row['id'].'[._]*' );
        if (!empty($row['imgs'])) {
            foreach ($row['imgs'] as &$img) {
                $img = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $img);
            }
        }
    }
    return $rows;
}

//todo getPrice как getPages
function getPrice($parent=null){
    $bar = new kdg_bar(['tbl' => 'tovar', 'perpage'=>1000,'sql'=>'WHERE gr='.intval($parent)]);
    //$bar_out=$bar->out(); <div>всего: <b><?=$bar->count? ></b>. &nbsp; < ?=$bar_out? ></div>
?>
<table>
<?
    echo $bar->header(['name'=>'Наименование','price'=>'Цена, руб']);
        $result = $bar->query();
        while ($row = DB::fetch_assoc($result)) {
            $tov=new Tovar($row);
?>
 <tr><td><?=(User::is_admin()?'<a title="Изменить" class="icon edit ajax" href="/adm/sprav.php?layer=tovar&amp;form='.$row['id'].'&ret_path='.urlencode($_SERVER['REQUEST_URI']).'"></a>':'')?><h3><?=$tov->name?></h3><?=$tov->description?></td><td class="price"><?=$tov->price?></td></tr>
<?
        }
?>
</table>
<?
}

/**
 * Оболочка на getBlock чтобы не передавать $no_div
 * @param $name
 * @param array $vars
 * @return string
 */
function echoBlock($name, $vars=[]) {
    return getBlock($name, $vars, true);
}

/** возвращает содержимое страницы по имени для вставки блока
 * @param $name
 * @return string
 */
function getBlock($name, $vars=[], $no_div=false){
    global $data, $url, $head_img, $h1, $title;
    $page = DB::Select('blocks', '`key`="' . addslashes($name) . '"');

    $f = TEMPLATES_DIR . "/.blocks/" . get_key($page, 'key', $name) . '.html';

    if ( empty($page['content'] ) && file_exists( $f )) {
        $page['content'] = file_get_contents($f);
    }

    $block = $no_div ? get_key($page, 'content', '') : '<div class="' . toHtml($name) . '">' . get_key($page, 'content', '') . '</div>';

    extract($vars);

    if (strpos($block, '<?') !== false){
        ob_start();
        eval(' ?>' . $block . "<? ");
        $block = ob_get_contents();  // Получаем содержимое буфера
        ob_end_clean();  // Останавливаем буферизацию и очищаем буфер вывода
    }

    return $block;
}

function dateForWrite($d){
    if(trim($d)==". ."||$d=='1970-01-01'||trim($d)=='')return 'null';
    return "'".date('Y-m-d', strtotime(str_replace(',','.',$d)))."'";
}

function dateForShow($d){
    if(trim($d)==". ."||$d=='1970-01-01'||!$d)return '  .  .   ';
    return date('d.m.Y H:i:s', strtotime(str_replace(',','.',$d)));
}

function timeForWrite($d=''){
    if($d){
        $d=strtotime(str_replace(',','.',$d));
    }else{
        if(User::is_admin() && isset($_POST['time'])){
            $d=strtotime(str_replace(',','.',$_POST['time']));
        }else $d=time();
    }
    if($d<='2000-01-01')$d=time();
    return "'".date('Y-m-d H:i:s',$d)."'";
}

function userForWrite(){
    if(User::is_admin() && isset($_POST['user'])){
        $d=$_POST['user'];
    }else $d=$_SESSION['user']['id'];
    return "'".addslashes($d)."'";
}


function rus_date() {

    $translate = array(
        "am" => "дп",
        "pm" => "пп",
        "AM" => "ДП",
        "PM" => "ПП",
        "Monday" => "Понедельник",
        "Mon" => "Пн",
        "Tuesday" => "Вторник",
        "Tue" => "Вт",
        "Wednesday" => "Среда",
        "Wed" => "Ср",
        "Thursday" => "Четверг",
        "Thu" => "Чт",
        "Friday" => "Пятница",
        "Fri" => "Пт",
        "Saturday" => "Суббота",
        "Sat" => "Сб",
        "Sunday" => "Воскресенье",
        "Sun" => "Вс",
        "st" => "ое",
        "nd" => "ое",
        "rd" => "е",
        "th" => "ое",
    );

    $translate1 = array(
        "January" => "Января",
        "February" => "Февраля",
        "March" => "Марта",
        "April" => "Апреля",
        "May" => "Мая",
        "June" => "Июня",
        "July" => "Июля",
        "August" => "Августа",
        "September" => "Сентября",
        "October" => "Октября",
        "November" => "Ноября",
        "December" => "Декабря",
    );

    $translate2 = array(
        "January" => "Январь",
        "February" => "Февраль",
        "March" => "Март",
        "April" => "Апрель",
        "May" => "Май",
        "June" => "Июнь",
        "July" => "Июль",
        "August" => "Август",
        "September" => "Сентябрь",
        "October" => "Октябрь",
        "November" => "Ноябрь",
        "December" => "Декабрь",
    );

    $translate = array_merge( $translate, strpos(func_get_arg(0), 'd F')!==false ? $translate1 : $translate2 );
    $translate = array_merge( $translate, [
            "Jan" => "Янв",
            "Feb" => "Фев",
            "Mar" => "Мар",
            "Apr" => "Апр",
            "Jun" => "Июн",
            "Jul" => "Июл",
            "Aug" => "Авг",
            "Sep" => "Сен",
            "Oct" => "Окт",
            "Nov" => "Ноя",
            "Dec" => "Дек",]
    );

    if (func_num_args() > 1) {
        $timestamp = func_get_arg(1);
        if (!is_int($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        return strtr(date(func_get_arg(0), $timestamp), $translate);
    } else {
        return strtr(date(func_get_arg(0)), $translate);
    }
}

function img( $link, $w, $h ) {
    $s = str_replace( '/images/', '/img/'.$w.'_'.$h.'_', $link );
    $s = preg_replace( '|/img/'.$w.'_'.$h.'_([a-z]*)/p|', '/img/'.$w.'_'.$h.'_$1/', $s );
    return $s;
}

function get_file_version($path) {
    $file = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($file)) { return filemtime($file); }
    return false;
}

