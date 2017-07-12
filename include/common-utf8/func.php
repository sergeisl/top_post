<? // общий файл utf-8
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

//@ini_set(auto_prepend_file,'none'); // Это выключает обработку ошибок
// А это включает на E-Mail
//php_value auto_prepend_file error_handler.inc
ini_set('session.gc_probability', 5); //
//ini_set('session.cookie_httponly', 1 );
header("Content-type: text/html; charset=".charset);

spl_autoload_register(function ($class_name) {
    //echo "Autoload ".$class_name;
    $file1 = $_SERVER['DOCUMENT_ROOT'] . "/include/class/" . strtolower($class_name) . '.php';
    $file2 = $_SERVER['DOCUMENT_ROOT'] . "/include/common-utf8/class/" . strtolower($class_name) . '.php';
    $file=(file_exists($file1) !== false ? $file1 : (file_exists($file2) !== false ? $file2 :'' ) );
    if(!$file || filemtime($file)>=time()-1){
        sleep(2); // может сейчас подливаю...
        $file=(file_exists($file1) !== false ? $file1 : (file_exists($file2) !== false ? $file2 :'' ) );
        if (!$file){
            AddToLog("Нет класса  " . $class_name);
        return false;
    }
    }
    include_once($file);
    return true;
});



/** проверяет доступен ли указанный класс
 * @param $class_name
 * @return bool
 */
function is_class($class_name){
    if(class_exists($class_name,false))return true;
    $file1 = $_SERVER['DOCUMENT_ROOT'] . "/include/class/" . strtolower($class_name) . '.php';
    $file2 = $_SERVER['DOCUMENT_ROOT'] . "/include/common-utf8/class/" . strtolower($class_name) . '.php';
    return file_exists($file1) || file_exists($file2) ;

}

global $url, $ip;
$url = Get::url();
$ip = Get::ip();
global $_cache_pub;
if (empty($_cache_pub)) $_cache_pub = new Cache('','pub');
$cache_keyHacker = 'ipsHacker';
if(isset($_GET['clear_Cache'])){
    $_cache_pub->set($cache_keyHacker, serialize([]));
    $ipsHacker=[];
}else {
    $ipsHacker = unserialize($_cache_pub->get($cache_keyHacker));
    if (is_array($ipsHacker) && in_array(strtok($ip, ','), $ipsHacker)){
        if (!headers_sent()) header("HTTP/1.0 403 Forbidden");
        die("Вам доступ на проект запрещен!");
    }
}
if(!empty($_SERVER['REQUEST_URI'])){
    if(in_array($url,['/xmlrpc.php','/wp-login.php','/plus/recommend.php'])) {
        BanIt('Hacker');
    }
    if ( strpos($_SERVER['REQUEST_URI'], ".php/component/users/") !== false){    // /index.php/component/users/?view=registration
        BanIt('component'); // подсовывают трояна
    }
    if ( strpos($_SERVER['REQUEST_URI'], "DOCUMENT_ROOT") !== false){    // ?_SERVER[DOCUMENT_ROOT]=http://www.aerothaiunion.com/sik.txt?.
        BanIt('DOCUMENT_ROOT'); // подсовывают трояна
    }
    if ( strpos($_SERVER['REQUEST_URI'], "'A=0") !== false){    // /price/?brand=641'A=0,
        BanIt('A=0');
    }
}
if ($_COOKIE){
    if (!empty($_COOKIE['PHPSESSID'])){
        if (strpos($_COOKIE['PHPSESSID'], 'disclaimer_accepted') !== false || $_COOKIE['PHPSESSID'] == '-1\'' || strpos($_COOKIE['PHPSESSID'], 'username=') !== false ){
            BanIt('cookie');
        }
    }
    foreach ($_COOKIE as $key => $val) {
        if (strpos($key, 'wordpress') === 0 || strpos($key, 'phpbb') === 0 || $key == 'PHPSESSID' && $val == 'deleted' || $key == 'is_first_access'){
            BanIt('cookie');
        }
    }
}
if (isset($_REQUEST['clientaction']) || isset($_REQUEST['"']) || isset($_REQUEST['1\'']) || isset($_REQUEST['\''])){
    BanIt('clientaction');
}
// подсовывают трояна
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], "DOCUMENT_ROOT") !== false){    // //?_SERVER[DOCUMENT_ROOT]=http://www.aerothaiunion.com/sik.txt?.
    BanIt('DOCUMENT_ROOT');
}
unset($cache_keyHacker,$ipsHacker);
function BanIt($msg)
{
    global $ip, $_cache_pub, $ipsHacker;
    if (empty($_cache_pub)) $_cache_pub = new Cache('','pub');
    $cache_keyHacker = 'ipsHacker';
    if(empty($ipsHacker))$ipsHacker = unserialize($_cache_pub->get($cache_keyHacker));
    if (!is_array($ipsHacker)) $ipsHacker=[];
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/ban.log', getenv('REMOTE_ADDR') . ' [' . date('c') . "] " . $msg . ' ' . @$_SERVER['REQUEST_URI'] . ' ' . @$_SERVER['HTTP_USER_AGENT'] . "\n", FILE_APPEND | LOCK_EX);
    if (!headers_sent()) header("HTTP/1.0 403 Forbidden");
    $ipsHacker[] = strtok($ip, ',');
    $_cache_pub->set($cache_keyHacker, serialize($ipsHacker));
    die("Вам доступ на проект запрещен!");
}

/*if(!ini_get('zlib.output_compression') && function_exists('ob_gzhandler')){
	$encoding=getAcceptedEncoding();
	if($encoding[0] === 'gzip') ob_start('ob_gzhandler');}
*/

foreach ($_REQUEST as $key => $val) {
    if (strtolower($key) != $key){
    $_REQUEST[strtolower($key)] = $_REQUEST[$key];
    unset($_REQUEST[$key]);
}
    if(($key=='charset') && substr($val,0,2)=='cp')$_REQUEST['charset']='windows-'.substr($val,2);
}
define("CookiePath", "/");    //".".$SERVER_NAME    Домен
define("CookieDomain", Get::SERVER_NAME());    // ".".$SERVER_NAME    домен

$GLOBALS['DEBUG'] = Get::DEBUG();

function param($param)
{
    $param=(isset($_GET[$param])?$_GET[$param]:(isset($_POST[$param])?$_POST[$param]:false));
    //if (get_magic_quotes_gpc()) $param=stripslashes($param);
    return $param;
}

/**
 * @param string $url
 * @param int $t - время кеширования в секундах
 * @param bool $always =true - если кэш файл есть, возвращаю его вне зависимости от времени кэширования
 */
function is_load_cash($url = '', $t = 300, $always = false)
{
    if(empty($url)) $url=$_SERVER['REQUEST_URI'];
    if (strpos($url, '?') !== false || !empty($_SESSION['message']) || !empty($_SESSION['error'])) return;
    global $cash_file;
    if(isset($cash_file)) return; // сдуру вызвал дважды
///////////// start of 304 //////////////////////
    if (substr($url, -1, 1) == "/") $url .= "index"; else {
        $url = url2file(urldecode($url));
        if (substr($url, -4, 4) == ".php") $url = substr($url, 0, -4);
    }
    $cash_file = fb_cachedir . url2file($url) . '.cch';
//$cash_file=$_SERVER['DOCUMENT_ROOT'].dirname($url).'/'.basename($url, ".php").'.cch';
//print $cash_file;
//Pragma: no-cache
    if (!file_exists($cash_file)) {
        if(!headers_sent()) {
            header("Cache-control: public, max-age = " . $t); // Кеширование в течение t секунд
            header("Pragma: public");
            header("Expires: " . gmdate('D, d M Y H:i:s', time() + $t) . ' GMT');
        }
        ob_start();
        return;
    }
    $c=count($_GET)+count($_POST);
    if ($c==0) {	 // если пришли какие-то данные, то их надо обработать!
        $lastModified = filemtime($cash_file);
        $slastModified = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
        $sExpires = gmdate('D, d M Y H:i:s', $lastModified + $t) . ' GMT';
        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])){
            // Сравниваем время последней модификации контента с кэшем клиента
            if (!headers_sent() && ($lastModified <= strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) || $always)){
                header('HTTP/1.1 304 Not Modified'); /*addtolog($url.' '.$lastModified.'<='.$modifiedSince);*/
                header("Expires: " . $sExpires);
                exit;
            } // Разгружаем канал передачи данных!
        }
        $df1=date("Y-m-d H:i:s",time()-$t);
        if (date("Y-m-d H:i:s",$lastModified) > $df1 || $always ) {
            if (!headers_sent()){
                header('Last-Modified: ' . $slastModified ); // Выдаём заголовок HTTP Last-Modified
                header("Cache-control: public, max-age = ".$t); // Кеширование в течение t секунд
                header("Pragma: public");
                header("Expires: " . $sExpires);
            }
            $buf=file_get_contents($cash_file);	//readfile($cash_file);
            $buf.="<!--\n".gmdate('D, d M Y H:i:s',time()-$t)."\n".$slastModified." -->"; // признак для отладки, что взято из кэша
            _deflate($buf);
            exit;
        }
        if (!headers_sent()) {
            header("Cache-control: public, max-age = " . $t); // Кеширование в течение t секунд
            header("Pragma: public");
            header("Expires: " . gmdate('D, d M Y H:i:s', time() + $t) . ' GMT');
        }
        //if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
        //ob_start('ob_gzhandler');
        //else
        ob_start();
        return;
    }
    unset($lastModified, $headers, $GLOBALS['cash_file']);
}

function end_cash()
{
    global $cash_file, $sape_context;
    if(isset($cash_file)){
        if (isset($sape_context)) ob_end_flush(); // если sape вызывается после is_load_cash
        $buf=ob_get_contents();
        if(!empty($buf)){
            if(@file_put_contents($cash_file, $buf)){
                if(!headers_sent())header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cash_file)) . ' GMT' ); // Выдаём заголовок HTTP Last-Modified
            } else add_error('Ошибка записи в ' . $cash_file);
            }
        @ob_end_clean();
        _deflate($buf);
        //ob_end_flush();
    }
}

function getAcceptedEncoding()
{
    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || _isBrokenInternetExplorer()) return ['', ''];
    if (preg_match('@(?:^|,)\s*((?:x-)?gzip)\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) return ['gzip', $matches[1]];
    if (preg_match('@(?:^|,)\s*deflate\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'])) return ['deflate', 'deflate'];
    if (preg_match('@(?:^|,)\s*((?:x-)?compress)\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) return ['compress', $matches[1]];
    return ['', ''];
}

function _isBrokenInternetExplorer()
{
    if(!isset($_SERVER['HTTP_USER_AGENT']))	return false;
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') || !preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) return false;
    $version = floatval($matches[1]);
    return $version < 6 || ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'SV1'));
}

function _deflate($html)
{
    if(headers_sent()){echo $html; return;}
    $encoding=getAcceptedEncoding();
    $deflate_level=6;
    if( empty($encoding[0]) || !extension_loaded('zlib') || strlen($html) <= 1024){echo $html; return;}
    if ($encoding[0] === 'gzip')		$encoded = gzencode($html, $deflate_level);
    elseif ($encoding[0] === 'deflate')	$encoded = gzdeflate($html, $deflate_level);
    else					$encoded = gzcompress($html, $deflate_level);
    $encoded=($encoded === false ? $html : $encoded);

    header('Content-Length: '.strlen($html));
    header('Content-Encoding: '.$encoding[1]);
    header('Vary: Accept-Encoding');
    echo $encoded;
}


function url2file($str)
{
    $str=preg_replace('/^https?:\/\/(www\.)?/i', '', $str);
    //if(!($qp===false))	$str=substr($str, 0, $qp).str_replace ('/', "_", substr($str, $qp+1));
    //$str=preg_replace('/[\?\&]reload(=[^&]*)?/', '', $str);
    $str = rus2translit($str);
    $str = strtolower($str);
    $str = str_replace ('\'', "", $str);
    $str = preg_replace('~[^a-z0-9\._]+~', '_', $str); // если все в юникоде, то preg_replace('~[^a-z0-9\._]+~u', '_', $str);
    if (substr($str, -4, 4) == ".php") $str = substr($str, 0, -4);
    $str = trim($str, "_"); $str=substr($str,0,128);
    return $str;
}
// todo использовать https://ru.wikipedia.org/wiki/ISO_9
function rus2translit($string)
{
    $converter = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ь' => '\'', 'ы' => 'y', 'ъ' => '\'', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'];
    return strtr($string, $converter);
}

// todo использовать https://ru.wikipedia.org/wiki/ISO_9
function fb_translit($string)
{
    // сначала убираю повторяющиеся символы
    $string = mb_strtolower(preg_replace("/(.)\\1\\1/", "\\1", $string));
    $string = preg_replace("/[^a-zа-яырстуфхцчщшэюё]/", "", $string); // удаления опасных сиволов
    $rus = ['а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ы', 'э', 'ё', 'ц', 'ж', 'ч', 'ш', 'щ', 'ю', 'я', 'ъ', 'ь'];
    $lat = ['a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'i', 'e', 'e', 'c', 'zh', 'ch', 'sh', 'sh', 'ju', 'ja', '', ''];
    return str_replace($rus, $lat, $string);
}

function fb_soundex($string)
{
    return soundex(fb_translit($string));
}


function str2url($str)
{
    // заменям все ненужное нам на "-"
//    $str = preg_replace('~[^\\pL0-9_]+~u', '-', $str);
    // тримим "-"
//    $str = trim($str, "-");
    // переводим в транслит
    $str = rus2translit($str);
    $str = strtolower($str);
    // ну и опять чистим
    $str = str_replace ('\'', "", $str);
    if(charset=='utf-8'){
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
    }else{
        $str = preg_replace('~[^-a-z0-9_]+~', '-', $str);
    }
    $str = trim($str, "-");
    return $str;
}

if(!empty($_COOKIE['PHPSESSID']))_session_start();
function _session_start()
{
    if(headers_sent())return; // поздно пить боржоми
    if(!isset($_SESSION['session_id'])){ //if(session_status()!=PHP_SESSION_ACTIVE){ // PHP 5.4
        /*if(User::is_login()){
        //session_cache_limiter('nocache');
            session_cache_limiter('private, must-revalidate');
            session_cache_expire(10); // in minutes
        }elseif(stripos($url,'api.php')===false){
            session_cache_limiter('private, must-revalidate');
            session_cache_expire(10); // in minutes
        }else{
            session_cache_limiter('nocache');
        }*/
        //session_cache_limiter('nocache');

        $sn = session_name();
        if (isset($_COOKIE[$sn]) && !preg_match('/^[a-zA-Z0-9]{15,45}$/', $_COOKIE[$sn])&&Get::ip(1)!=='176.9.58.227'){ // у MegaIndex короткая сессия
            SendAdminMail('Session cookie error', "IP: ".getenv('REMOTE_ADDR') . "\nCOOKIE: " . var_export($_COOKIE, !0).
                "\nUSER_AGENT: ".@$_SERVER['HTTP_USER_AGENT']."\nURL: ".@$_SERVER['REQUEST_URI'].
                "\nACCEPT:".@$_SERVER['HTTP_ACCEPT'].', '.@$_SERVER['HTTP_ACCEPT_ENCODING'].
                "\nRobot=".Get::isRobot());

            unset($_COOKIE[$sn]);
        }
        /*if (!empty($_REQUEST['api_key']) && preg_match('/^[a-zA-Z0-9]{24,45}$/', $_REQUEST['api_key'])){
            //$api_key=$_REQUEST['api_key']==TestSMSgAteApi_key ? '2b145e08d9fb7b7bb56b1ae45467a8d3' : $_REQUEST['api_key'];
            // todo проверять если с одного IP много раз подбирали api_key - банить на время
            session_id($_REQUEST['api_key']);
            //session_cache_limiter('nocache');
            session_cache_limiter('private_no_expire');
        } elseif (isset($_GET['logout'])) {
            //session_cache_limiter('nocache');
            session_cache_limiter('private_no_expire');
        }else{
            // todo session_cache_limiter('public');
            //session_cache_limiter('nocache');
            session_cache_limiter('private_no_expire');
        }*/
        session_cache_limiter('nocache');
        header("Expires: " . gmdate('D, d M Y H:i:s', time() - 3600 * 24 * 30 * 12) . ' GMT');
        //if(!session_id())
        //session_regenerate_id();
        //session_save_path($_SERVER['DOCUMENT_ROOT'].'/log/session');
        //session_set_cookie_params  (1800,"/","domain.com",false,true);
        session_start();
        /* todo привязка сессии к ip
        if( isset( $_SESSION['REMOTE_ADDR'] ) && $_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR'] ) {
            session_regenerate_id(); $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        }
        if( !isset( $_SESSION['REMOTE_ADDR'] ) ) { $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR']; }
        */
        // todo отправлять продление, если осталось меньше 20 часов
        setcookie(session_name(), session_id(), strtotime("+1 days"), "/",null, null, true); // при каждом открытии страницы продлеваю сессию

        $_SESSION['session_id']=1;
        //echo session_save_path()." ~ ".session_id()."<br>";
    }
}

/** вывод сообщений пользователю
 * @param string $str
 */
function message($str){Out::message($str);}

function error($str){Out::error($str);}

/** Вывести сообщения и ошибки и
 * @param bool|int $exit = 0 - не завершать работу
 *                   = 1 - завершить работу если были ошибки
 *                   = 2 - завершить работу если были ошибки или сообщения
 *                   = 3 - завершить работу
 * @param bool $format = true - вывести в отформатированном блоке
 * @return int
 */
function WriteErrorAndExit($exit = 0, $format = false){return Out::ErrorAndExit($exit,$format);}

function AddToLog($err, $subject = 'Error report', $NotShow = false)
{
// Сохранет в протокол ошибок. Первую ошибку в день отправляет на почту.
// Все ошибки за прошлый день отправляет на почту sm_delete
    $file_error = '/log/error/' . date("Y_m_d") . '.log';
    $err = date("d.m.Y H:i") . "\n" . $err . "\n\nurl=" . $_SERVER['REQUEST_URI'] . (empty($_SERVER['HTTP_REFERER']) ? '' : ", referer=" . $_SERVER['HTTP_REFERER']) . (empty($_SESSION['REFERER']) ? '' : ", session_referer=" . $_SESSION['REFERER']) . "\n";
    $err.="Стек: ".var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),!0).".<br>\n";
    $err1 = nl2br($err) . "<br>\n<a href='" . $GLOBALS['http'] . "://" . Get::SERVER_NAME() . $file_error . "'>Все ошибки за день</a><br>\n";
    if(!file_exists($_SERVER['DOCUMENT_ROOT'].$file_error)) // первое сообщение в день отправляю на почту
        SendAdminMail($subject, $err1);
    file_put_contents($_SERVER['DOCUMENT_ROOT'].$file_error, $err."\n", FILE_APPEND|LOCK_EX);
    if ($NotShow) return;
    if (getenv('REMOTE_ADDR') != '127.0.0.1' || empty($_SESSION['user']['id']) || $_SESSION['user']['id'] != 1) $err1 = "Произошла ошибка, администрация уже решает эту проблему.";
    echo "<strong>".$err1."</strong><br>\n";
}
function add_error($err) {
    if (!headers_sent())header("HTTP/1.0 503 Service Unavailable");
    AddToLog($err);
    //die( "<b>Произошла ошибка PHP.</b> Администратор оповещен!\n");
}

// Определяем новую функцию-обработчик.
function myErrorHandler($errno, $msg, $file, $line, $vars)
{
    $_SESSION['Last-Modified'] = time();
    if (!headers_sent()){
        header('Last-Modified:');
        header("Expires: " . gmdate('D, d M Y H:i:s', time() - 3600 * 24 * 30 * 12) . ' GMT');
    }
    // Если используется @, ничего не делать.
    if (error_reporting() == 0){
//	if ($GLOBALS['DEBUG']) add_error($err)
        return;
    }
    if ($errno == 2 && $file == "Unknown" && $line == 0 && substr($msg, 0, 20) == "POST Content-Length ") return;

    // определение ассоциативного массива строк ошибок
    // на самом деле следует рассматривать
    // только элементы 2,8,256,512 и 1024
    $errortype = [1 => "Ошибка", 2 => "Предупреждение", 4 => "Ошибка синтаксического анализа", 8 => "Замечание", 16 => "Ошибка ядра", 32 => "Предупреждение ядра", 64 => "Ошибка компиляции", 128 => "Предупреждение компиляции", 256 => "Ошибка пользователя", 512 => "Предупреждение пользователя", 1024 => "Замечание пользователя", 2048 => "", 4096 => ""];
    // Иначе - выводим сообщение.
    $err = "\n\n" . date("d.m.Y H:i") . "\n";
    $err.="Произошла ошибка с кодом <b>".$errno."</b> ".@$errortype[$errno]."!\n";
    $err.="Файл: $file, строка $line.\n";
    if ($line) {
        if (preg_match('#(.*?)\((.*?)\)#is', $file, $out)) {
            $file1 = $out[1];
            $line1 = intval($out[2]);
            $out = file($file1);
            for ($i = $line1 - 1; $i < $line1 + 2; $i++) $err .= "<br>\n" . $i . ": " . (isset($out[$i - 1]) ? htmlspecialchars($out[$i - 1], null, charset) : '');
            if (strpos($file, "eval()") !== false && isset($out[$line1 - 1]) && preg_match('#eval\((.*?)\)\;#is', $out[$line1 - 1], $out)) {
                $out = $out[1];
                if (preg_match('#\$(.*?)\=.*?#is', $out, $out1)&&isset($GLOBALS[$out1[1]])) $out = $GLOBALS[$out1[1]];
                //else $out=eval($out);
                //$err.="<br>\nev=".htmlspecialchars($out);
                $out = explode("\n", $out);
                //$err.="<br>\nc=".count($out);
                for ($i = $line - 1; $i < $line + 2; $i++) $err .= "\n" . $i . ": " . (isset($out[$i - 1]) ? htmlspecialchars($out[$i - 1], null, charset) : '');
            }
        }
        if (is_file($file)) {
            $out = file($file);
            for ($i = $line - 1; $i < $line + 2; $i++) $err .= "<br>\n" . $i . ": " . (isset($out[$i - 1]) ? htmlspecialchars($out[$i - 1], null, charset) : '');
        }
    }
    if (isset($_SERVER['REQUEST_URI'])) $err .= "Запрос: " . $_SERVER['REQUEST_URI'] . ".<br>\n";
    if (!empty(DB::$query)) $err .= "SQL-Запрос: " . DB::$query . ".\n";
    $err .= "Текст ошибки: <i>" . $msg . "</i>\n";
    $err .= "Стек: " . var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0) . "\n";
    $err .= "\n";
    $file_error = $_SERVER['DOCUMENT_ROOT'] . '/log/last_error.txt';
    if (is_file($file_error)) { // не сохраняю подряд одинаковые ошибки
        if ($err == file_get_contents($file_error) && !$GLOBALS['DEBUG']) return;
    }
    file_put_contents($file_error, $err);
    // сохранить протокол ошибок и отправить его мылом
    if (!($errno == 8) || $GLOBALS['DEBUG']) echo nl2br($err);
    //unset($vars['trustlink'], $vars['sape'], $vars['sape_article'], $vars['sl'], $vars['linkfeed'], $vars['sape_context']);
    if ($vars){
        foreach (array('trustlink', 'sape', 'sape_article', 'sl', 'linkfeed', 'sape_context') as $var_name) if (isset($vars[$var_name])) unset($vars[$var_name]);
    } else {
        $vars = [];
        global $head, $body, $body1, $str;
        if (isset($head)) $vars['head'] = $head;
        if (isset($body)) $vars['body'] = $body;
        if (isset($body1)) $vars['body1'] = $body1;
        if (isset($str)) $vars['str'] = $str;
    }

    $v = "<html><body>" . $err . "\n<br />Переменные:<pre>" . print_r($vars, true) . "</pre>\n</body></html>";
    if (session_id() == "") $file_error = rand();
    else $file_error = session_id();
    $file_error = '/log/error/' . $file_error . '.htm';
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . $file_error, $v);
    $v = "<br>\n<a href='" . $GLOBALS['http'] . "://" . Get::SERVER_NAME() . $file_error . "'>Переменные</a>\n";
    AddToLog($err . $v, 'PHP error report');
}

// Регистрируем ее для всех типов ошибок.
set_error_handler("myErrorHandler", E_ALL);
if (getenv('REMOTE_ADDR') == '127.0.0.1') error_reporting(E_ALL);
else error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// Определяем новую функцию-обработчик fatal error.
function myShutdownHandler() {
    if(@is_array($e = @error_get_last())){
        $code = isset($e['type']) ? $e['type'] : 0;
        $msg = isset($e['message']) ? $e['message'] : '';
        $file = isset($e['file']) ? $e['file'] : '';
        $line = isset($e['line']) ? $e['line'] : '';
        if($code>0)myErrorHandler($code,$msg,$file,$line,'');
    }
}

//register_shutdown_function('myShutdownHandler');

function _USER_SetLocaleRus()
{
    mb_internal_encoding(charset);
    if(charset=='utf-8'){
        $arrLocales = ['ru_RU', 'RU', 'rus'];
    }else{
        $arrLocales = ['ru_RU.CP1251', 'ru_RU.cp1251', 'Russian_Russia.1251', 'rus', 'ru_RU', 'RU'];
    }
    foreach ($arrLocales as $var) {
        if (setlocale(LC_ALL, $var) === false) continue;
        if (strtolower("qwertyёЁАБГДЯQWERTYZЧ") == "qwertyёёабгдяqwertyzч") return true;
    }
    return false;
    //AddToLog("Не удалось установить locale!");
}

/**
 * @param string $add_mes -
 * @param int $show_err = 0 не выводить, 1 - message, 2 - error, 3 - print, 4- win->utf и print
 * @param int $add_request = true - добавить в протокол $_REQUEST, QUERY_STRING, HTTP_REFERER
 */
function PaymentLog($add_mes, $show_err = 0, $add_request = 0)
{
    if(isset($GLOBALS['LastPaymentLogMessage'])&&$GLOBALS['LastPaymentLogMessage']==$add_mes)return; else $GLOBALS['LastPaymentLogMessage']=$add_mes; // не сохранять повторяющиеся сообщения
    if ($show_err == 1) message($add_mes); elseif ($show_err == 2) Out::error($add_mes);
    elseif($show_err==3)print $add_mes;
    elseif($show_err==4)print @iconv("windows-1251","UTF-8//IGNORE",$add_mes);
    $logfile=$_SERVER['DOCUMENT_ROOT'] . '/log/payment_log.txt';
    $add_mes = "\r\n" . date('d-m-Y H:i') . ' ' . $add_mes . "\t" . User::name() . '(' . User::id() . "), ip:" . Get::ip() . "\r\n";
    if ($add_request){
        foreach ($_REQUEST as $key => $value) $add_mes .= "\t" . $key . '=' . $value . "\r\n";
        if(isset($_SERVER['QUERY_STRING']))$add_mes.="\tQUERY_STRING=".$_SERVER['QUERY_STRING']."\r\n";
        if(isset($_SERVER['HTTP_REFERER']))$add_mes.="\tHTTP_REFERER=".$_SERVER['HTTP_REFERER']."\r\n";
    }
    file_put_contents($logfile, $add_mes, FILE_APPEND|LOCK_EX);
}

/** отправка сообщения админам
 * @param string $Subj заголвок
 * @param string $Body тело в html
 * @param string $from
 * @param bool $oneDay = true, если только одн раз в день
 * @return bool
 */
function SendAdminMail($Subj, $Body, $from = '', $oneDay = false)
{
    if ($oneDay){
        $fil0 = fb_tmpdir0 . str2url($Subj) . '.tmp';
        $fil = $_SERVER['DOCUMENT_ROOT'].$fil0;
        if (is_file($fil) && filemtime($fil) > strtotime("-1 day")) return !0;
        @file_put_contents($fil, '');
    }
    @file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/mail.log', "\n\n" . date("d-m-Y H:i:s") . " " . $Subj . "\n" . $Body, FILE_APPEND);
    if(empty($from)){
        $from = "From: <noreply@" . Get::SERVER_NAME() . ">\nContent-Type: text/html; charset=".charset;
        if($oneDay)$Body.="\n<p style='color:gray'>Сообщение приходит один раз в день. <a href='". $GLOBALS['http'] . "://" . Get::SERVER_NAME()."/log/img.php?del=".urlencode($fil0)."'>Прислать следующее?</a></p>";
        $Body = "<html><body>" . nl2br($Body) . "</body></html>";
    }
    return mail(AdminMail, mime_header_encode($Subj), $Body, $from);
}

function IsMail($path) {
    $path=trim(preg_replace("/[^\x20-\xFF]/","",@strval($path)));
    if (strlen($path)==0){ return '0'; }
    if (!preg_match('/^[a-z0-9_\-\.\+]{1,30}@(([a-z0-9\-\.]+\.)+([a-z]{2,9})|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/is', $path)){
        return 'error';
    }
    return $path;
}

/**
 * @param string $str
 * @param string $charset
 * @return string
 * $from = mime_header_encode($name_from) .' <' . $email_from . '>'
 * $subject = mime_header_encode($subject)
 */
function mime_header_encode($str, $charset = charset)
{
    if ($charset == 'koi8-r') $str = convert_cyr_string($str, "w", "k");
    return '=?' . $charset . '?B?' . base64_encode($str) . '?=';
}

/** проверяет вхождение второй строки в первую
 * @param string $s1 где искать
 * @param string $s2 что искать
 * @return bool найдено?
 */
function cmp($s1, $s2)
{
    //return strpos($s1,$s2)===0;
    if(strlen($s1)<strlen($s2))return false;
    return substr($s1,0,strlen($s2))==$s2;
}

function valDate($d){
    if(empty($d)||$d=='0000-00-00 00:00:00'||$d=='0000-00-00')return '  .  .    ';
    $d=strtotime($d);
    if(date("d.m.Y",$d)=='01.01.1970') return '  .  .    ';
    if(date("d.m.Y H:i:s",$d)==date("d.m.Y 00:00:00",$d)) return date("d.m.Y",$d);
    else return date("d.m.Y H:i:s",$d);
}

/** добавляю версию файла картинки для решения проблемы кеширования
 * @param $fil
 * @return string
 */
function ImgSrc($fil){
    $f=$_SERVER['DOCUMENT_ROOT'].$fil;
    return $fil.(file_exists($f)&&(filemtime($f)>strtotime("-1 month"))?'?v='.filemtime($f):'');
}

/** обрезает текст для далее..., подробнее...
subText($data['answer'],200,"&#8230;<i><a href=\"/id/".$data['id']."\">Читать ответ полностью »</a></i>");
 * @param $string
 * @param int $len
 * @param string $add
 * @return string
*/
function subText($string, $len=200, $add="&#8230;"){

    //$string=nl2br(htmlspecialchars($string,null,'windows-1251'));
    $string=ReadUrl::strip_tags($string);
    if(strlen($string)>$len){
        // обрежем его на определённое количество символов:
        $string = substr($string, 0, $len);

        // удалим в конце текста восклицательй знак, запятую, точку или тире:
        $string = rtrim($string, "!,.-");

        // находим последний пробел, устраняем его и ставим троеточие:
        $string = substr($string, 0, strrpos($string, ' '));

        return $string.$add;
    }
    return $string;
}

/** Написание окончание числительных
 * echo "Сейчас на сайте ".$num1." новост".num2word($num1,array("ь", "и", "ей")); // сколько гостей
 * num2word($t,array("день", "дня", "дней"))
 * num2word($t,array("рубль", "рубля", "рублей"))
 * "копейка","копейки","копеек"
 * @param $num integer
 * @param $words array
 * @return string
 */
function num2word($num, $words)
{
    $num=$num%100;
    if ($num > 19){
        $num = $num % 10;
    }
    switch ($num) {
        case 1: {
            return ($words[0]);
        }
        case 2:
        case 3:
        case 4: {
            return ($words[1]);
        }
        default: {
            return ($words[2]);
        }
    }
}

function ShortUrl($t)
{ // из полного пути на диске делает короткий http-путь
    return (cmp($t, $_SERVER['DOCUMENT_ROOT']) ? substr($t, strlen($_SERVER['DOCUMENT_ROOT']) + 1) : $t);
}

/**
 * @param $s
 * @return string  - строка в формате для записи в БД
 */
function parseFloat($s)
{
    $a = localeConv();
    return (isset($a['decimal_point']) && !empty($a['decimal_point']) && $a['decimal_point'] != $a['mon_decimal_point'] ? str_replace($a['mon_decimal_point'], '', str_replace('.', $a['decimal_point'], $s)) : str_replace(',', '.', $s));
}

function myFloatVal($s)
{
    return floatval(str_replace(',', '.', $s));
}

function outSumm($d){
    return ($d!=0?str_replace(' ','&nbsp;',number_format($d, ($d==intval($d)?0:2), '.', ' ')):'');
}

function outSumm0($d){
    return number_format($d, ($d==intval($d)?0:2), '.', chr(160));
}

/** кодирует массив в строку для записи в базу
 * @param array $p
 * @return string
 */
function js_encode($p)
{
    //if(charset=='windows-1251')$p=Convert::array_win2utf($p);
    $p = json_encode($p,JSON_UNESCAPED_UNICODE);
    //$p=iconv('UTF-8', 'windows-1251//IGNORE',$p);
    //$arr_replace_utf = ['\u0410', '\u0430', '\u0411', '\u0431', '\u0412', '\u0432', '\u0413', '\u0433', '\u0414', '\u0434', '\u0415', '\u0435', '\u0401', '\u0451', '\u0416', '\u0436', '\u0417', '\u0437', '\u0418', '\u0438', '\u0419', '\u0439', '\u041a', '\u043a', '\u041b', '\u043b', '\u041c', '\u043c', '\u041d', '\u043d', '\u041e', '\u043e', '\u041f', '\u043f', '\u0420', '\u0440', '\u0421', '\u0441', '\u0422', '\u0442', '\u0423', '\u0443', '\u0424', '\u0444', '\u0425', '\u0445', '\u0426', '\u0446', '\u0427', '\u0447', '\u0428', '\u0448', '\u0429', '\u0449', '\u042a', '\u044a', '\u042b', '\u044b', '\u042c', '\u044c', '\u042d', '\u044d', '\u042e', '\u044e', '\u042f', '\u044f'];
    //$arr_replace_cyr = ['А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е', 'Ё', 'ё', 'Ж', 'ж', 'З', 'з', 'И', 'и', 'Й', 'й', 'К', 'к', 'Л', 'л', 'М', 'м', 'Н', 'н', 'О', 'о', 'П', 'п', 'Р', 'р', 'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц', 'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ', 'Ъ', 'ъ', 'Ы', 'ы', 'Ь', 'ь', 'Э', 'э', 'Ю', 'ю', 'Я', 'я'];
    //$p = str_replace($arr_replace_utf, $arr_replace_cyr, $p);
    return ($p=='[]'?'':$p);
}

function time2html($time)
{
    if (!is_integer($time) && preg_match('/[^0-9]/', $time)) $time = strtotime($time);
    //if(User::id()==1)return $time;
    if ($time == 0 || $time == -62169998400) return '-';
    return date('d.m.y', $time) . "<sup> " . date('H:i', $time) . "</sup>";
}

function toHtml($str)
{
    $str = str_replace(["<b>Fatal error</b>", "<b>Parse error</b>"],"<b>error</b>",$str);
    return empty($str) ? '' : str_replace('"','&quot;',(is_string($str) ? htmlspecialchars($str, null, charset) : var_export($str, !0)));
}

/** кодирует строку в массив
 * @param $p
 * @return array|string
 */
function js_decode($p){
    if(empty($p))return [];
    if(is_array($p))return $p;
    if(charset=='windows-1251')$p=@iconv('windows-1251', 'UTF-8//IGNORE', $p);
    $p=(array)json_decode($p,true);
    if(charset=='windows-1251')$p=Convert::utf2win($p);
    return $p;
}

/**
 * @param array $row
 * @param string $root
 */
function outApi($row = [], $root = '')
{ Out::Api($row,$root);}


/**
 * Пример:
 * $result = get_key($_POST, 'category', 1);
 *
 * @param array $from Откуда
 * @param string $key Какой ключ
 * @param mixed $default Значение по умолчанию
 * @return mixed
 */
function get_key($from, $key, $default = null)
{
    $fna = func_num_args();


    if ($fna>3) {
        $a = func_get_args();
        $default = $a[$fna-1];
        for ($i=1;$i<$fna-1;$i++) {
            $k = $a[$i];
            $from = get_key($from, $k, $default);
        }
        return $from;
    }

    if (is_array($from) && array_key_exists($key, $from)) {
        $default = $from[$key];
    }
    return $default;
}

function date2html($str='now'){ /*Вывод даты по-русски*/
    static $month_rus=array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
    //$str=strtotime($str);
    return date("j",$str).' '.$month_rus[intval(date("m",$str)-1)].' '.date("Y",$str);
}

//var_export(array_intersect_ukey(array('book_edit' => '','name'=>'ddd'),array('name'=>'fff','phone'=>'','mail'=>'','comment'=>''), 'key_compare_func'));
function key_compare_func($key1, $key2)
{
    if ($key1 == $key2) return 0; else if ($key1 > $key2) return 1; else return -1;
}

function between($a, $b, $c)
{
    if ($b > $c) return ($a >= $c && $a <= $b); else return ($a >= $b && $a <= $c);
}

function dump($varname, $varval, $maxlevel = 5)
{
    if ($varname == 'GLOBALS' && isset($varval['GLOBALS'])){
        unset($varval['GLOBALS']);
        unset($varval['linkfeed'], $varval['sl'], $varval['sape_context'], $varval['sape']);
    }

    if (!is_array($varval)){
        $r = $varname . ' = ' . var_export($varval, true) . ";\n";
    } elseif ($maxlevel > 0) {
        $r = $varname . " = [];\n";
        $maxlevel--;
        foreach ($varval as $key => $val) {
            $r .= dump($varname . "[" . var_export($key, true) . "]", $val, $maxlevel);
        }
    } else {
        $r = $varname . " = array(???);<br>\n";
    }
    return $r;
}

function multi_implode($glue, $array)
{
    $_array = [];
    foreach ($array as $val) $_array[] = is_array($val) ? multi_implode($glue, $val) : $val;
    return implode($glue, $_array);
}

//function sql($q){return DB::sql($q);}

function ReadUrl($url, $post=false, $options=false){
    list($headers,$body,$info)=ReadUrl::ReadWithHeader($url, $post, $options);
    return $body;
}

if(!function_exists("array_column"))
{
    function array_column($array,$column_name)
    {
        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
    }
}
/** сортирует многомерный массив по полю $field
 * @param array &$ar
 * @param string $field
 * @param bool $desc =true, - по убыванию
 * @return bool =true, если успешно
 */
function array_sort(&$ar, $field='id', $desc=false){
    return usort($ar, function($a, $b) use($field, $desc) {
        if($a[$field] === $b[$field])
            return 0;

        return ($a[$field] > $b[$field] ? 1 : -1)*($desc?-1:1);
    });
}

/** формирует строку url
 * @param string $tbl
 * @param integer|array $data
 * @param integer $format = 0 - вернуть только url
 *                        = 1 - ссылка с коротким названием
 *                        = 2 - ссылка с полным названием
 * @return string
 */
function BuildUrl($tbl, $data, $format=0){
    if(!is_array($data))$data=DB::Select($tbl,$data);
    $name=$data['name'];
    $url = $data['url'];
    while (!empty($data['parent'])&&substr($data['url'],0,1)!='/') {
        $data = DB::Select($tbl, $data['parent']);
        if ($data) {
            $url = $data['url'] . '/' . $url;
            if ($format == 2) $name = $data['name'] . " -&gt; " . $name;
        }
    }
    if(substr($url,0,1)!='/')$url = '/' . $url;
    if ($format) {
        return "<a href=\"".$url."\">".$name."</a>";
    }

    return $url;
}

/** execTemplate('SUM(s{$i}) as s{$i}',Tovar::$ar_type,', ')

<input type="text" name="brand" value="<?=$brand_name?>" list="lbrand" style="width:80px">
<datalist id="lbrand">'.execTemplate('<option value="{$v}">',DB::Select2Array('brand','','id&name'),', ')."</datalist>

  execTemplate('<option value="{$i}">{$v}</option>',DB::Select2Array('brand','','id&name'),', ');

 * @param $templ
 * @param $ar
 * @param string $glue
 * @return string
 */
function execTemplate($templ, $ar, $glue=''){
    $r='';
    foreach($ar as $i=>$v)$r.=($r?$glue:'').str_replace(['{$i}','{$v}'],[$i,$v],$templ);
    return $r;
}
