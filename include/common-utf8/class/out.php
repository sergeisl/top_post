<?
// todo проверять Get::isApi() вместо параметра ajax - X-Requested-With: XMLHttpRequest
class Out{

    static $res='';
    public static $message_fade=false; // =true-сообщения выводить с затуханием

    static function mes($mes,$add=''){
        $mes= $mes . (empty($_SESSION['message']) ? '' : "\n" . $_SESSION['message']);
        if(isset($_SESSION['message']))unset($_SESSION['message']);
        if(self::$message_fade)$add.=($add?';':'').'_fade.init()';
        self::_out($mes,$add,'fb_mes');
    }

    static function err($mes,$add=''){
        $mes= $mes . (empty($_SESSION['error']) ? '' : "\n" . $_SESSION['error']);
        if(isset($_SESSION['error']))unset($_SESSION['error']);
        self::_out($mes,$add,'fb_err');
    }

    static function win($mes,$add=''){
        self::_out($mes,'','fb_win',$add);
    }

    /** вывести javascript-ом
     * @param $str
     */
    static function toScript($str)
    {
        self::_out('','','',$str);
    }

    private static function _out($mes, $add, $act, $param2=''){
        $_SESSION['Last-Modified'] = time();
        $charset=(empty($_REQUEST['charset']) ? charset : $_REQUEST['charset'] );
        if($charset!=charset)$mes=@iconv(charset, $charset . '//IGNORE', $mes);
        if($act=='fb_win' && $add==2){
            $add0=',2'; // fade
            $add='';
        }else{
            $add0='';
        }
        $mes=($mes ? ($act."('" . str_replace("'", "\\'", nl2br($mes)) .($param2 ? "','" . $param2 : ''). "'".$add0.");") : '') . $add;
        if(headers_sent()){
            echo "<script>".$mes."</script>";
        }else{
            header("Pragma: no-cache");
            header("Content-Type: application/x-javascript; charset=".$charset);
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Expires: " . date("r"));
            header("Expires: -1", false);
            header('Last-Modified:');
            echo $mes;
        }
        exit;
    }

    /**
     * @param array $row
     * @param string $root
     *  todo привести в соответствие с https://habrahabr.ru/company/aligntechnology/blog/281206/
     */
    static function Api($row = [], $root = '')
    {
        $charset=(empty($_REQUEST['charset']) ? charset : $_REQUEST['charset'] );
        if(headers_sent($file,$line)){
            AddToLog('Cannot modify header information - headers already sent by (output started at '.$file.':'.$line.')','Error report', !0);
        }elseif(isset($_REQUEST['xml'])){
            header("Content-Type: text/xml; charset=".$charset);
        }elseif(isset($_REQUEST['json'])) {
            header("Content-Type: application/json; charset=" . $charset);
        }elseif ( isset($_REQUEST['sql']) ){
            header("Content-Type: text/plain; charset=" . $charset);
        }else{
            header("Content-Type: text/html; charset=" . $charset);
        }
        if (Get::isApi() && !isset($_REQUEST['json']) && !isset($_REQUEST['xml']) && !isset($_REQUEST['html']) && !isset($_REQUEST['sql'])){
            // только ajax-запрос
            if (!empty($row['error']) || !empty($_SESSION['error'])){
                self::err(empty($row['error']) ? '' : $row['error']); // &exit
            } elseif (!empty($row['message']) || !empty($_SESSION['message'])) {
                self::mes(empty($row['message']) ? '' : $row['message']); // &exit
            }else{
                self::$res='';
            }
        }else {
            if (!empty($_SESSION['message'])){
                $row['message'] = (empty($row['message']) ? '' : "\n") . $_SESSION['message'];
                unset($_SESSION['message']);
            }
            if (!empty($_SESSION['error'])){
                $row['error'] = (empty($row['error']) ? '' : "\n") . $_SESSION['error'];
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['limit']['counter'])){
                $row['limit'] = $_SESSION['limit']['counter'];
                if(!isset($row['balans']) && $row['limit']==0 && User::is_login()) $row['balans']=GetPay();
            }
        }
            if (!headers_sent()){
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Max-Age: 3600');
                if (isset($_REQUEST['nocache'])){
                    header("Pragma: no-cache");
                    header("Cache-Control: no-store, no-cache, must-revalidate");
                    header("Expires: " . date("r"));
                    header("Expires: -1", false);
                } else {
                    header("Pragma: cache");
                    header("Cache-Control: public");
                    header("Expires: " . date("r", strtotime(empty($row['error'])&&empty($row['message']) ? "+1 minutes" : "+5 minutes" )) ); // +1 day
                }
            }

            if (!empty($_REQUEST['fields'])){ // только по первому уровню. todo сделать на всех уровнях
                if (!empty($row[0])){
                    foreach ($row as $key => $val) {
                        if (is_array($val)) $row[$key] = array_intersect_key($val, array_flip(explode(',', $_REQUEST['fields'])));
                    }
                } elseif ($row) $row = array_intersect_key($row, array_flip(explode(',', $_REQUEST['fields'])));
            }

            if (isset($_REQUEST['json'])){
                self::$res = Convert::php2json($row);
            } elseif (isset($_REQUEST['xml'])) {
                if (!$root) $root = 'message'; //self::$res='<?xml version="1.0" encoding="'.(isset($_REQUEST['charset'])?$_REQUEST['charset']:'windows-1251').'"?'.'>'."\n";}
                self::$res = '<' . $root . ">\n" . Convert::array2xml($row) . '</' . $root . '>';
            } elseif (isset($_REQUEST['sql'])) {
                self::$res = Convert::array2sql($row, $root);
            } elseif (!empty($row['error'])) {
                if (isset($_REQUEST['short'])){
                    self::$res = $row['error'];
                } else {
                    $tag = (stripos($row['error'], '<br') === false ? 'span' : 'div');
                    self::$res = "<" . $tag . " class='error'>" . $row['error'] . "</" . $tag . ">\n";
                }
            } elseif (!empty($row['message'])) {
                if (isset($_REQUEST['short'])){
                    self::$res = $row['message'];
                } else {
                    $tag = (stripos($row['message'], '<br') === false ? 'span' : 'div');
                    self::$res = "<" . $tag . " class='message'>" . $row['message'] . "</" . $tag . ">\n";
                }
            } else {
                self::$res = Convert::array2html($row); // "<div class='box1'>".array2html($row)."</div>\n";
            }

        echo self::$res;
        if(isset($_SESSION['session_id'])){
        unset($_SESSION['session_id']);
        @session_destroy(); // закрываю, чтобы следующее обращение было с обязательным предъявлением API_KEY
    }
    }

    static function message($str)
    {
        if (!empty($_SESSION['message']) && !empty($str) && strpos($_SESSION['message'], $str) !== false) return false;
        _session_start();
        $_SESSION['message'] = (!empty($_SESSION['message']) ? $_SESSION['message'] . "\n" : '') . $str;
        $_SESSION['Last-Modified'] = time();
        if (!headers_sent()) header('Last-Modified:');
        return false;
    }

    static function error($str)
    {
        if (!empty($_SESSION['error']) && !empty($str) && strpos($_SESSION['error'], $str) !== false) return false;
        _session_start();
        $_SESSION['error'] = (!empty($_SESSION['error']) ? $_SESSION['error'] . "\n" : '') . $str;
        $_SESSION['Last-Modified'] = time();
        if (!headers_sent()) header('Last-Modified:');
        return false;
    }

    static function debug($str){
        if(User::name()=='kdg')self::message($str);
    }

    /** Вывести сообщения и ошибки и
     * @param bool|int $exit = 0 - не завершать работу
     *                   = 1 - завершить работу если были ошибки
     *                   = 2 - завершить работу если были ошибки или сообщения
     *                   = 3 - завершить работу
     * @param bool $format = true - вывести в отформатированном блоке без API
     * @return int
     */
    static function ErrorAndExit($exit = 0, $format = false)
    {
        $f_exit = 3;
        if(!$format && (Get::isApi() || isset($_REQUEST['json']) || isset($_REQUEST['xml'])) && (!empty($_SESSION['message'])||!empty($_SESSION['error'])) ){
            self::Api();
        }
        if (!empty($_SESSION['message'])){
            $f_exit = 2;
            if (!isset($_REQUEST['json']) && !isset($_REQUEST['xml'])){
                echo($format ? '<div class="message">' . nl2br($_SESSION['message']) . '</div>' : $_SESSION['message']);
            }
            unset($_SESSION['message']);
        }
        if (!empty($_SESSION['error'])){
            $f_exit = 1;
            if (!isset($_REQUEST['json']) && !isset($_REQUEST['xml'])){
                echo($format ? '<div class="error">' . nl2br($_SESSION['error']) . '</div>' : $_SESSION['error']);
            }
            unset($_SESSION['error']);
        }
        if ($exit >= $f_exit) exit;
        return $f_exit;
    }

    /**
     * @param string $url - относительный или полный url для перехода
     * @param int|string $http = 302|404|script
     */
    static function Location($url, $http = 302)
    {
        $url=str_replace("\n",'+',$url);
        if(strpos($url, '://') === false) $url = (empty($GLOBALS['http'])?'http':$GLOBALS['http']) . '://' . $_SERVER['HTTP_HOST'] . $url;
        if(isset($_REQUEST['ajax'])||Get::isApi()){
            //self::mes('', 'location.href="' . $url . '";');
            self::_out('', 'location.href="' . $url . '";','fb_mes');
        }elseif(headers_sent()||$http=='script'){
            echo '<script>document.location="'.addslashes($url).'";</script></body></html>';
        } else {
            if ($http == 404) header("HTTP/1.0 404 Not Found");
            elseif ($http == 301) header("HTTP/1.0 301 Moved Permanently");
            elseif ($http != 302) header("HTTP/1.0 " . $http);
            header('location: ' . $url);
        }
        exit;
    }


    static function LocationRef($def_path='/'){
        if(!empty($_GET['ret_path'])){
            $i=urldecode($_GET['ret_path']);
            unset($_GET['ret_path']);
        }elseif(!empty($_SESSION['ret_path'])){
            $i=$_SESSION['ret_path'];
            unset($_SESSION['ret_path']);
        }else{
            $i=$def_path;
        }
        self::Location($i);
    }

    static function BadRequest(){
        if(!headers_sent())header("http/1.0 400 Bad Request");
        self::Api(['error'=>"Неверные параметры:\nGET:".htmlspecialchars(print_r($_GET,true),null,charset).
            "\n POST:".htmlspecialchars(print_r($_POST,true),null,charset).
            "\n FILES:".htmlspecialchars(print_r($_FILES,true),null,charset)]);
        exit;
    }

    static function TmpFileForDownload($ext='zip',$pref='geo_'){
        do{
            $fileArxive = fb_tmpdir0.$pref.User::id().rand(1000,9999).'.'.$ext;
        }while(is_file($_SERVER['DOCUMENT_ROOT'].$fileArxive));
        return $fileArxive;
    }

    static function format_phone($phone){

        if (strlen($phone) == 7||strlen($phone) == 6) {
            return preg_replace("/^([0-9]{2,3})([0-9]{2})([0-9]{2})$/", "$1-$2-$3", $phone);
        } elseif (strlen($phone) == 10) {
            return preg_replace("/^([0-9]{3})([0-9]{3})([0-9]{2})([0-9]{2})$/", "($1) $2-$3-$4", $phone);
        } elseif (strlen($phone) == 11) {
            return preg_replace("/^([0-9])([0-9]{3})([0-9]{3})([0-9]{2})([0-9]{2})$/", "$1($2) $3-$4-$5", $phone);
        }
        return $phone;
    }

}
