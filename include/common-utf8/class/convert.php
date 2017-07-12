<?
class Convert{
    static function array2html($array)
    {
        $xml = '';
        if ($array) foreach ($array as $key => $val) /*if ($key !== 'user')*/{
            if (is_array($val)) $xml .= '<div>' . Convert::array2html($val) . "</div>\n";
            else $xml .= '<label>' . htmlspecialchars($key, null, !empty($_REQUEST['charset']) ? $_REQUEST['charset'] : charset) . ': <b>' .
                htmlspecialchars((empty($_REQUEST['charset']) ? $val : iconv(charset, $_REQUEST['charset'].'//IGNORE', $val)), null, (!empty($_REQUEST['charset']) ? $_REQUEST['charset'] : charset)) .
                "</b></label><br>\n";
        }
        return $xml;
    }
    static function array2sql($array, $tbl = '_sql_')
    {
        if (!empty($_REQUEST['sql']) || empty($tbl)) $tbl = $_REQUEST['sql'];
        if (!headers_sent()){
            //header("Pragma: hack");
            header("Content-Type: application/octet-stream");
            header('Content-Disposition: attachment; filename="' . $tbl . '.sql"');
            header("Content-Transfer-Encoding: binary");
        }
        $sql = '';
        if (empty($array[0]) && is_array($array)) $array = array($array); // если передали массив полей и значений, а не массив массивов
        if ($array){
            foreach ($array as $key => $val) {
                if (is_numeric($key) && is_array($val)){
                    $sql .= "INSERT IGNORE INTO `" . $tbl . "` ( `" . (implode("`, `", array_keys($val))) . "`)\r\n\tVALUES (" . implode(", ", array_map(function ($v) {
                            $charset = (empty($_REQUEST['charset']) ? charset : $_REQUEST['charset']);
                            if(is_array($v))$v=(isset($v['name'])?$v['name']:js_encode($v));
                            return (is_null($v) ? 'null' : "'" . (is_double($v) ? str_replace(',', '.', floatval($v)) : addslashes(($charset != charset ? @iconv(charset, $charset, $v) : $v))) . "'");
                        }, $val)) . ");\r\n";

                }
            }
        } else {
            $sql .= "-- Таблица " . $tbl . " - нет данных";
        }

        return $sql;
    }

    static function array2xml($array, $pref = "")
    {
        if (!headers_sent()){
            header("Content-Type: application/xml; charset=" . (empty($_REQUEST['charset']) ? charset : $_REQUEST['charset'] ));//header('Content-type: application/json; charset=utf-8');
        }
        //$xml = '<?xml version="1.0" encoding="'.(isset($_REQUEST['charset'])?$_REQUEST['charset']:charset).'"? >';
        //var_export($array);
        $xml = '';  //'<bank>';
        if ($array) foreach ($array as $key => $val) {
            $key = (is_numeric($key) && is_array($val) ? 'msg' : str_replace(array("\t", "\r", "\n"), ' ', trim($key)));
            //if ($key === 'user') continue;
            $params = array_reverse($key = explode(' ', $key));
            array_pop($params);
            $params = trim(implode(' ', $params));
            $cp = (isset($_REQUEST['charset']) ? $_REQUEST['charset'] : charset);
            $cp = str_replace(array('windows-', 'utf-8'), array('cp', 'UTF-8'), $cp);//ISO-8859-1, ISO-8859-15, UTF-8, cp866, cp1251, cp1252, and KOI8-R
            $xml .= $pref . '<' . $key[0] . ($params ? ' ' . $params : '') . ">" . (is_array($val) ? "\n" . call_user_func('self::array2xml', $val, $pref . "\t") : htmlspecialchars((isset($_REQUEST['charset']) && $_REQUEST['charset'] != charset ? @iconv(charset, $_REQUEST['charset'], $val) : $val), ENT_QUOTES, $cp)) . '</' . $key[0] . ">\n";
        }
        //$xml.= '</bank>';
        return $xml;
    }

static function _charset_utf8_win($s)
{
    $r = '';
    $sl = strlen($s);
    //echo '<br>_charset_utf8_win: длина='.$sl;
    for ($i = 0; $i < $sl; $i++) {
        $c0 = $c = ord($s[$i]);

        if ($c <= 127 || ($i + 1 >= $sl)){
            $r .= $s[$i];
            //echo '<br>~'.$c;
            continue;
        } elseif (($c >> 5) == 6) { // 110xxxxx 10xxxxxx
            $c1 = ord($s[++$i]);
            //$c=( ($c1>>2)&5 )*256 + ( ($c1&3)*64+($c&63) );
            //echo '<br>2:'.(($c&31 )<<6).' '.( $c1&63);
            $c = ((($c & 31) << 6) + ($c1 & 63));
            //echo '<br>2:'.$c0.' '.$c1.' ='.$c; // 1055=10000 011111=208+159 =110 10000+10 011111 =207=П
        } elseif (($c >> 4) == 14 && ($i + 2) < $sl) { // 1110xxxx 10xxxxxx 10xxxxxx // 226 + 132 + 150 = 11100010 + 10000100 + 10010110
            $c1 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c2 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c = ((($c & 15) << 12) + (($c1 & 63) << 6) + ($c2 & 63));
            //echo '<br>3:'.$c0.' '.$c1.' '.$c2.' ='.var_export($c,!0);
        } elseif (($c >> 3) == 30 && ($i + 3) < $sl) { // 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
            $c1 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c2 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c3 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c = ((($c & 7) << 18) + (($c1 & 63) << 12) + (($c2 & 63) << 6) + (($c3 & 63)));
            //echo '<br>4:'.$c0.' '.$c1.' '.$c2.' '.$c3.' ='.var_export($c,!0);
        } elseif (($c >> 3) == 62 && ($i + 4) < $sl) { // 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            $c1 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c2 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c3 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c4 = ord($s[++$i]);
            if ($c <= 127){
                $r .= chr(128);
                $i--;
                continue;
            }
            $c = ((($c & 3) << 24) + (($c1 & 63) << 18) + (($c2 & 63) << 12) + (($c3 & 63) << 6) + ($c4 & 63));
            //echo '<br>5:'.$c0.' '.$c1.' '.$c2.' '.$c3.' '.$c4.' ='.var_export($c,!0);
        } else {
            $r .= chr(128);
            //echo '<br>No:'.$c;
            continue;
        }
        //echo $c.'+';
        switch ($c) {
            case   1025:
                $r .= 'Ё';
                continue;
            case   1105:
                $r .= 'ё';
                continue;
            case   8470:
                $r .= '№';
                continue;
            case   8211:
                $r .= '-';
                continue;
            case 0x00ab:
                $r .= '«';
                continue;
            case 0x00bb:
                $r .= '»';
                continue;
            case 0x00bc:
                $r .= '1/4';
                continue;
            case 0x00bd:
                $r .= '1/2';
                continue;
            case 0x00be:
                $r .= '3/4';
                continue;
            case 0x00a9:
                $r .= '(c)';
                continue;
            case 0x00ae:
                $r .= '(r)';
                continue;
            case 0x00ac:
                $r .= '^';
                continue;
            case 0x00b1:
                $r .= '+/-';
                continue;
            case 0x00b2:
                $r .= '^2';
                continue;
            case 0x00b3:
                $r .= '^3';
                continue;
            case 0x20ac:
                $r .= 'E';
                continue; // euro
            case 8704:
                $r .= 'V';
                continue; // перевернутое А
            // РјВІ -> ?
            default:
                if (($c - 848) > 255){
                    //if($c<9999)SendAdminMail('Недопустимый UTF-символ','Код:'.$c);
                    //echo '<br>Недопустимый UTF-символ','Код:'.$c;
                    $r .= chr(128);
                } else {
                    $r .= chr($c - 848);
                    //echo "~".chr($c-848);
                }
                continue;
        }
    }
    return $r;
}

/*function utf2win($p){
    if(is_array($p))
        foreach($p as $key => $value)$p[(is_numeric($key)?intval($key):$key)]=utf2win($value);
    else
        $p= @iconv('UTF-8','windows-1251//IGNORE',$p);
    return $p;
}*/

    /** преобразует строку или массив
     * @param string|array $str
     * @param string $type w|u|e w - utf->win, u - win->utf, e - utf->url_encode
     * @return string|array
     */
    static function utf2win($str, $type = "w"){
    static $conv = '';

    if (!is_array($conv) && $type != 'w'){
        $conv = [];

        for ($x = 128; $x <= 143; $x++) {
            $conv['u'][] = chr(209) . chr($x);
            $conv['w'][] = chr($x + 112);
        }

        for ($x = 144; $x <= 191; $x++) {
            $conv['u'][] = chr(208) . chr($x);
            $conv['w'][] = chr($x + 48);
        }
        $conv['u'][] = chr(208) . chr(129);
        $conv['w'][] = chr(168);
        $conv['u'][] = chr(209) . chr(145);
        $conv['w'][] = chr(184);
        $conv['u'][] = chr(208) . chr(135);
        $conv['w'][] = chr(175);
        $conv['u'][] = chr(209) . chr(151);
        $conv['w'][] = chr(191);
        $conv['u'][] = chr(208) . chr(134);
        $conv['w'][] = chr(178);
        $conv['u'][] = chr(209) . chr(150);
        $conv['w'][] = chr(179);
        $conv['u'][] = chr(210) . chr(144);
        $conv['w'][] = chr(165);
        $conv['u'][] = chr(210) . chr(145);
        $conv['w'][] = chr(180);
        $conv['u'][] = chr(208) . chr(132);
        $conv['w'][] = chr(170);
        $conv['u'][] = chr(209) . chr(148);
        $conv['w'][] = chr(186);
        $conv['u'][] = chr(226) . chr(132) . chr(150); // №
        $conv['w'][] = chr(185);
        $conv['u'][] = chr(196) . chr(171); // i
        $conv['w'][] = chr(105);
        $conv['u'][] = chr(196) . chr(129); // a
        $conv['w'][] = chr(97);
        $conv['u'][] = chr(197) . chr(189); // Z
        $conv['w'][] = chr(90);
        $conv['u'][] = chr(196) . chr(188); // l
        $conv['w'][] = chr(108);
        $conv['u'][] = chr(197) . chr(161); // s
        $conv['w'][] = chr(115);
        $conv['u'][] = chr(197) . chr(134); // n
        $conv['w'][] = chr(110);
        /*        static $table = array("\xA8" => "\xD0\x81", // Ё
                    "\xB8" => "\xD1\x91", // ё
                    // украинские символы
                    "\xA1" => "\xD0\x8E", // Ў (У)
                    "\xA2" => "\xD1\x9E", // ў (у)
                    "\xA5" => "\xD2\x90", // Ґ (Г')
                    "\xB4" => "\xD2\x91", // ґ (г')
                    "\xAA" => "\xD0\x84", // Є (Э)
                    "\xBA" => "\xD1\x94", // є (э)
                    "\xAF" => "\xD0\x87", // Ї (I..)
                    "\xBF" => "\xD1\x97", // ї (i..)
                    "\xB2" => "\xD0\x86", // I (I)
                    "\xB3" => "\xD1\x96", // i (i)
                    // чувашские символы
                    "\x8C" => "\xD3\x90", // &#1232; (A)
                    "\x8D" => "\xD3\x96", // &#1238; (E)
                    "\x8E" => "\xD2\xAA", // &#1194; (С)
                    "\x8F" => "\xD3\xB2", // &#1266; (У)
                    "\x9C" => "\xD3\x91", // &#1233; (а)
                    "\x9D" => "\xD3\x97", // &#1239; (е)
                    "\x9E" => "\xD2\xAB", // &#1195; (с)
                    "\x9F" => "\xD3\xB3", // &#1267; (у)
        C5 9E 	-> S
        C5 9F -> s

        C4 80 -> A
        C4 81 -> a
        C4 82 -> A
        C4 83 -> a
        C4 84 -> A
        C4 85 -> a

        C4 86 -> C
        C4 87 -> c
        C4 88 -> C
        C4 89 -> c

        C3 82 ->A
        C3 A2 ->a
        C3 85 ->A
        C3 A5 ->a
        C3 84 ->A
        C3 A4 ->a

        C3 87 ->C
        C3 A7 ->C

         */

    }
    if (is_object($str)) $str = (array)$str;
    if (is_array($str)){
        foreach ($str as $key => $value) $str[(is_numeric($key) ? intval($key) : $key)] = Convert::utf2win($value, $type);
    }elseif ($type == 'w') {
        if(is_numeric($str)) return $str;
        return Convert::_charset_utf8_win($str);
        //return str_replace($conv['u'],$conv['w'],$str);
    } elseif ($type == 'e') {
        if (!isset($conv['e'])) foreach ($conv['u'] as $val) $conv['e'][] = '%' . strtoupper(dechex(ord(substr($val, 0, 1)))) . '%' . strtoupper(dechex(ord(substr($val, 1, 1))));
        return str_replace($conv['u'], $conv['e'], $str);
    } elseif ($type == 'u') {
        return str_replace($conv['w'], $conv['u'], $str);
    }
    return $str;
}

    /*The Unicode character 0xa9 = 10101001 (the copyright sign) is encoded in UTF-8 as

        110 00010   10 101001 = 0xc2 0xa9
               10      101001

    and character 0x2260 = 0010 0010 0110 0000 (the "not equal" symbol) is encoded as:

        11100010 10001001 10100000 = 0xe2 0x89 0xa0
              10   001001   100000 */


static function win2utf($str)
{
    //return utf2win($str,'u');
    static $table = array("\xA8" => "\xD0\x81", "\xB8" => "\xD1\x91", "\xA1" => "\xD0\x8E", "\xA2" => "\xD1\x9E", "\xAA" => "\xD0\x84", "\xAF" => "\xD0\x87", "\xB2" => "\xD0\x86", "\xB3" => "\xD1\x96", "\xBA" => "\xD1\x94", "\xBF" => "\xD1\x97", "\x8C" => "\xD3\x90", "\x8D" => "\xD3\x96", "\x8E" => "\xD2\xAA", "\x8F" => "\xD3\xB2", "\x9C" => "\xD3\x91", "\x9D" => "\xD3\x97", "\x9E" => "\xD2\xAB", "\x9F" => "\xD3\xB3", "\xB9" => "\xE2\x84\x96",

    );
    return preg_replace('#[\x80-\xFF]#se', ' "$0" >= "\xF0" ? "\xD1".chr(ord("$0")-0x70) :
                           ("$0" >= "\xC0" ? "\xD0".chr(ord("$0")-0x30) :
                            (isset($table["$0"]) ? $table["$0"] : "")
                           )', $str);
}

    static function array_win2utf($p)
    {
        if (!$p) return $p;
        foreach ($p as $key => $value) $p[$key] = (is_array($value) ? Convert::array_win2utf($value) : Convert::win2utf($value));
        return $p;
    }

    static function array_utf2win($p)
    {
        if (!$p) return $p;
        foreach ($p as $key => $value) $p[$key] = (is_array($value) ? Convert::array_utf2win($value) : Convert::utf2win($value));
        return $p;
    }


    static function php2json($obj, $no_convert = false)
    {
        /*

    <script type="application/javascript" src="http://server2.example.com/Users/1234?jsonp=parseResponse"></script>
    parseResponse({"Name": "Foo", "Id": 1234, "Rank": 7});

    */
        if (!headers_sent()){
            header("Content-Type: application/json; charset=" . (empty($_REQUEST['charset']) ? charset : $_REQUEST['charset'] ));
            //header("Content-Type: application/x-suggestions+json; charset=".(isset($_REQUEST['charset'])?$_REQUEST['charset']:charset));
        }//header('Content-type: application/json; charset=utf-8');
        //$json_data = array ('id'=>1,'name'=>"ivan",'country'=>'Russia',"office"=>array("yandex"," management"));
        //json_encode($json_data);

        if (count($obj) == 0){
            $str = '[]';
        } else {
            $is_obj = isset($obj[count($obj) - 1]) ? false : true;
            $str = $is_obj ? '{' : '[';
            foreach ($obj as $key => $value) {
                //if ($key === 'user') continue;
                $str .= $is_obj ? "\"" . addcslashes($key, "\n\r\t\"\\/") . "\"" . ':' : '';
                if (is_array($value)) $str .= Convert::php2json($value, !0); elseif (is_null($value)) $str .= 'null';
                elseif (is_bool($value)) $str .= $value ? 'true' : 'false';
                //elseif (is_numeric($value) && strlen($value) < 11 && substr($value, 0, 1) != "0") $str .= str_replace(',', '.', $value);
                elseif (preg_match('/^\-?[0-9\,\.]{1,11}$/',trim($value)) && substr($value, 0, 1)!="0" && substr_count(str_replace(',', '.',$value),'.')<=1) $str .= str_replace(',', '.', $value);
                elseif (preg_match('|^(\d{2})[\,\.\-\/](\d{2})[\,\.\-\/](\d{4})$|', $value, $arr)) $str .= "\"" . addcslashes($arr[1] . '.' . $arr[2] . '.' . $arr[3], "\n\r\t'\\/") . "\""; // дд.мм.гггг -> дд.мм.гггг
                elseif (preg_match('|^(\d{4})[\,\.\-\/](\d{2})[\,\.\-\/](\d{2})$|', $value, $arr)) $str .= "\"" . addcslashes($arr[3] . '.' . $arr[2] . '.' . $arr[1], "\n\r\t'\\/") . "\""; // гг.мм.дд -> дд.мм.гггг
                else                        $str .= "\"" . addcslashes($value, "\n\r\t\"\\/") . "\"";
                $str .= ',';
            }
            $str = substr_replace($str, $is_obj ? '}' : ']', -1);
            if (!$no_convert && isset($_REQUEST['charset']) && $_REQUEST['charset'] != charset) $str = @iconv(charset, $_REQUEST['charset'] . '//IGNORE', $str);
        }
        /*Для Вашего удобства поддерживается формат JSONP. Вы можете добавить параметр &jsonp=ИМЯ_ФУНКЦИИ:
    <script type="application/javascript" src="http://htmlweb.ru/service/api.php?bic=043469751&json&jsonp=parseResponse"></script>
    будет возвращен следующий код: parseResponse({json});*/
        if (!empty($_REQUEST['jsonp'])) $str = $_REQUEST['jsonp'] . '(' . $str . ');';
        return $str;
    }


    static function BB2_html($m)
    {
        return "<pre><code class=\"language-html\">" . trim(htmlspecialchars(is_array($m) ? $m[1] : $m, null, charset)) . "</code></pre>";
    }

    static function BB2_php($m)
    {
        return "<pre><code class=\"language-php\">" . htmlspecialchars(trim(is_array($m) ? $m[1] : $m), null, charset) . "</code></pre>";
    }

    static function BB2html($body)
    {
        $body = preg_replace_callback("#\[html\](.*?)\[/html\]#s", 'self::BB2_html', $body);
        $body = preg_replace_callback("#\[php\](.*?)\[/php\]#s", 'self::BB2_php', $body);

        if (stripos($body, '<xmp>') !== false){ // меняю на pre
            $body = preg_replace("!<xmp>(.*?)</xmp>!sie", "'<pre>'.htmlspecialchars('\\1').'</pre>'", $body);
        }

        if (strpos($body, '[cache]') !== false){
            $cash_file = fb_cachedir . url2file(Get::url()) . '.cch';
            if (isset($_GET['reload'])) @unlink($cash_file);
            if (file_exists($cash_file)){
                $body = preg_replace("#\[cache\](.*?)\[/cache\]#s", '<!-- cache -->' . file_get_contents($cash_file) . '<!-- /cache -->', $body);
            }
        }
        return $body;
    }

    static function long2ip2($i)
    { // INET_NTOA()
        $d[0] = (int)($i / 256 / 256 / 256);
        $d[1] = (int)(($i - $d[0] * 256 * 256 * 256) / 256 / 256);
        $d[2] = (int)(($i - $d[0] * 256 * 256 * 256 - $d[1] * 256 * 256) / 256);
        $d[3] = $i - $d[0] * 256 * 256 * 256 - $d[1] * 256 * 256 - $d[2] * 256;
        return "$d[0].$d[1].$d[2].$d[3]";
    }

    static function ip2long2($ip='')
    { // INET_ATON("111.222.333.444")
        if($ip=='')$ip=getenv('REMOTE_ADDR');
        $a = explode(".", trim($ip));
        if (count($a) < 4) return 0;
        return $a[0] * 256 * 256 * 256 + $a[1] * 256 * 256 + $a[2] * 256 + $a[3];
        ///if (($lngIP=ip2long($strIP)) < 0){ $lngIP += 4294967296 ;}
    }

    /** преобразует битовое поле из базы в массив бит, нумерация с 0
     * $services=Bit2Array()
     "<p>Услуги гостиницы: "; foreach($services as $key=>$val){ $out.=($f++?", ":"").$ar_serv_CHasGost[$key]; }
     * @param $val
     * @return array
     */
    static function Bit2Array($val){ // "7" => array(3) { [0]=> int(1) [1]=> int(1) [2]=> int(1) }
        $ar=[];
        $nb=0;
        while($val){
            if($val&1) $ar[$nb]=1;
            $val/=2;
            $nb++;
        }
        return $ar;
    }
    /**
    $param['services']=(isset($_GET['services'])?(array)$_GET['services']: []);
     foreach($ar_serv_CHasGost as $key=>$val){
        if((($key)%4)==0)echo "\n<td style=\"width: 292px;\">";
        echo "\n\t<input type=\"checkbox\" name=\"services[".$key."]\" value=\"1\" ".(empty($data['services'][$key])?'':' checked')."> ".$val."<br>";
        if((($key)%4)==3)echo "\n</td>";
    }

     */
    /**  преобразует массив бит в маску для выборки из базы
     *      $bit=Convert::Array2Bit($param['services']);
                $param['sql'][]='`services`&'.$bit.'='.$bit;
     * @param $val
     * @return int
     */
    static function Array2Bit($val){
        $resultValue=0;
        if($val)foreach($val as $nb=>$nv) if(!empty($nv)){
            $resultValue|=1<<$nb;
        }
        return $resultValue;
    }

}
