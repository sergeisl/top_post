<?
include_once './include/config.php';
spl_autoload_call('bb.class');
$url = Get::url();


if ($url != '/index.php') {
    $url=trim($url,'. ');
    if (!($data = DB::Select('pages', 'url="' . addslashes($url) . '" order by services'))){ // если адресация от корня (поле url начинается с '/')
        $args = explode('/', substr($url, 1));
        $parent = 0;
        for ($i = 0; $i < count($args); $i++) {
            $args[$i]=trim($args[$i],'. ');
            if (empty($args[$i])) continue; // исключаю '/' в конце и '//'
            if (($data = DB::Select('pages', '(parent=' . $parent . ' and url="' . addslashes($args[$i]) . '") or url="/' . addslashes($args[$i]) . '"'))) {
                //echo "<br>i=".$i.", parent=".$parent.": ";var_dump($data);
                $parent = $data['id'];
            } else {
                $data = null;
                break;
            }
        }
    }

    if (empty($data)) {
        header("HTTP/1.0 404 Not Found");
        if (!($data = DB::Select('pages', 'parent=0 and url="404.php"'))) {
            $data = [];
            $data['name'] = $data['title'] = $data['content'] = 'Страница не найдена!';
            $data['title'] .= ' ' . SHOP_NAME;
            $data['id'] = "404.php";
        }
    }
    /*if(!empty($param['services'])){
        $bit=Convert::Array2Bit($param['services']);
        $param['sql'][]='`services`&'.$bit.'='.$bit;
    }*/
} else { // главная
    include_once './getSuppliers.php';
    global $pages_suppliers;
    $data_get = get_data((!empty($_GET['page']))?$_GET['page']:1);
    $data = DB::Select('pages', 'parent=0 and url=""');

}
$data['imgs'] = glob( $_SERVER['DOCUMENT_ROOT'] . '/images/pages/p'.$data['id'].'[._]*' );
if (!empty($data['imgs'])) {
    foreach ($data['imgs'] as &$img) {
        $img = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $img);
    }
}

$h1 = $title = $data['title'];
if (empty($data['content']) && file_exists( $f = $_SERVER['DOCUMENT_ROOT'] . "/include/templates/" . ($url=='/index.php' ? 'index' : trim($url, '/')) . '.html')) {
    $data['content'] = file_get_contents( $f );
}

$data['title'].= SHOP_NAME;
$GLOBALS['page'] = $data;

if (strpos($data['content'], '<?') !== false){
    ob_start();
    eval((' ?>' . $data['content'] . "<? "));
    $data['content'] = ob_get_contents();  // Получаем содержимое буфера
    ob_end_clean();  // Останавливаем буферизацию и очищаем буфер вывода
}

include_once $_SERVER['DOCUMENT_ROOT'] . "/include/head.php";

if (User::is_admin()) echo '<div style="position: fixed; right: 30px; bottom: 30px;z-index: 300000"><a title="Изменить" class="icon edit ajax" href="/adm/sprav.php?layer=pages&amp;form=' . $data['id'] . '&ret_path=' . urlencode($_SERVER['REQUEST_URI']) . '"></a>' .
    "<a title='Распечатать' class='icon print' href='?print' target=_blank></a></div>";

echo $data['content'];
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/tail.php';

