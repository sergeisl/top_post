<?

/**
 * Class Get
 * @method static boolean DEBUG режим отладки
 * @method static string SHOP_NAME Заголовок сайта
 * @method static string SignupSms СМС при создании личного кабинета админом
 * @method static string SignupMail E-mail при создании личного кабинета админом
 * @method static string PaymentSms     Списание занятия
 * @method static string CancelSchedule 	СМС при отмене тренировки 	Тренировка {date} в {time} отменена. Ваш LuxeFitness
 * @method static integer RefBonus 	    Бонус за приведенного пользователя, руб.
 * @method static string RefDiscount 	Скидка за регистрацию по приглашению
 * @method static integer RefDelay 	    Через сколько дней можно использовать бонус
 * @method static integer RefBonusUseProc 	Какой % от стоимости покупки можно оплатить бонусами
 * @method static integer RefLife 	Срок жизни бонуса, месяцев
 */
 class Get{

    static function isApi(){
        //X-Requested-With: XMLHttpRequest
        return (isset($_REQUEST['json'])||isset($_REQUEST['xml'])||isset($_REQUEST['html'])||isset($_REQUEST['api'])/*||stripos(self::url(),'/api.php')!==false*/ ||
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /** возвращает true, если запрос с нашего сайта
     * @return bool
     */
    static function isSite(){
        return (isset($_SERVER['HTTP_REFERER'])&&isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_REFERER'], self::SERVER_NAME()) !== false) ;
    }

    /** возвращает true, если запрос с url. Если url не передан, возвращает Referer
     * @param string $url
     * @return bool
     */
    static function Referer($url=''){
        if(empty($url))return (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '' );
        return (isset($_SERVER['HTTP_REFERER'])&&(
                (substr($url,0,1)=='/' && strpos($_SERVER['HTTP_REFERER'], self::SERVER_NAME().$url) !== false) ||
                (strpos($_SERVER['HTTP_REFERER'], $url) !== false) )) ;
    }

    /** возвращает относительный путь к запрошенному файлу по URL
     * @return string
     */
    static function url(){
        $url=(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:''); // /php/function/$_server.php?ajax=1
        if(strpos( $url, "?" )!==false) $url=substr($url, 0, strpos($url, "?"));
        if(strpos( $url, "://" )!==false || strpos( $url, ".php/" )!==false ){ // todo если повторяется, банить по ip
            $url='/404.php';
        }elseif($url=="" || substr($url, -1, 1)=="/" ) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'].$url."index.htm")) $url.= "index.htm";
            elseif (file_exists($_SERVER['DOCUMENT_ROOT'].$url."index.php")) $url.= "index.php";
            elseif (file_exists($_SERVER['DOCUMENT_ROOT'].$url."index.head.php")) $url.="index.head.php";
        }elseif(substr($url,0,7)=='/index.') {
            $url = 'index.htm';
        }
        if($url=="/index.htm")$_SERVER['QUERY_STRING']='';	// на всякий случай
        return $url;
    }

    /** возвращает полный путь к запрошенному файлу по URL
     * @return string
     */
    static function file(){
        return $_SERVER['DOCUMENT_ROOT'].self::url();
    }

    static function SERVER_NAME(){
        if(!isset($_SERVER['HTTP_HOST'])) return (defined('SERVER_NAME')?SERVER_NAME:'htmlweb.ru');
        $SERVER_NAME=str_replace(':443', '', $_SERVER['HTTP_HOST']);
        $SERVER_NAME=preg_replace('/^https?:\/\//', '', $SERVER_NAME);
        $SERVER_NAME=preg_replace('/^www\./', '', $SERVER_NAME);
        return $SERVER_NAME;
    }

    /** возвращает имя робота или пустую строку
     * @param string $name =(yandex|google) - если передана, то возвращает
     * @return int|null|string
     */
    static function isRobot($name=''){
        $name=strtolower($name);
        static $_robot=[]; if(isset($_robot[$name.'_']))return $_robot[$name.'_'];
        $ip = Get::ip(1); // 193.232.121.204 [2017-01-25T18:19:34+03:00] LIMIT_PAGES /geo/country/NR/city/12751 Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)
        if(in_array(substr($ip,0,strrpos($ip,'.')+1),['188.72.80.','193.232.121.'])){ // 193.232.121.204 [2017-01-24T12:11:05+03:00] LIMIT_PAGES/geo/country/NR/city/12751 Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)
            $_robot['sape_']='sape'; // LIMIT_PAGES
            self::BotLog('Bot(Sape)');
            return ($name?($name=='sape'):'Sape');
        }elseif( isset($_SERVER['HTTP_USER_AGENT']) ){ // Браузер/Версия (Платформа; Шифрование; Система, Язык[; Что-нибудь еще]) [Дополнения]
            global $_cache_pub;
            if (empty($_cache_pub)) $_cache_pub = new Cache('','pub');
            $cache_keyYandex = 'ipsYandex';
            $cache_keyGoogle = 'ipsGoogle';
            $cache_keyBot = 'ipsBot';
            if(isset($_GET['clear_Cache'])){
                $_cache_pub->set($cache_keyYandex, serialize([]));
                $_cache_pub->set($cache_keyGoogle, serialize([]));
                $_cache_pub->set($cache_keyBot, serialize([]));
                Out::Location('/');
            }
            if(preg_match('/(Yandex|StackRambler|Aport|Googlebot|Mediapartners\-Google|inktomi\.com|WebCrawler|FrontPage|ZyBorg|TurnitinBot|PlantyNet|Wget|Baiduspider|yahoo|msnbot|WebAlta|scooter|ia_archiver|'.
                'accoona|ia_archiver|antabot|ask\ jeeves|baidu|dcpbot|eltaindexer|feedfetcher|gamespy|gigabot|googlebot|gsa\-crawler|grub\-client|gulper|slurp|mihalism|msnbot|worldindexer|ooyyo|pagebull|scooter|'.
                'w3c_validator|jigsaw|webalta|yahoofeedseeker|yahoo\!\ slurp|mmcrawler|yandexbot|yandeximages|yandexvideo|yandexmedia|yandexblogs|yandexaddurl|yandexfavicons|yandexdirect|yandexmetrika|yandexcatalog|'.
                'yandexnews|yandeximageresizer|bingbot|megaindex)/i',
                /*'rambler','googlebot','aport','yahoo','msnbot','turtle','mail.ru','omsktele',
                'yetibot','picsearch','sape.bot','sape_context','gigabot','snapbot','alexa.com',
                'megadownload.net','askpeter.info','igde.ru','ask.com','qwartabot','yanga.co.uk',
                'scoutjet','similarpages','oozbot','shrinktheweb.com','aboutusbot','followsite.com',
                'dataparksearch','google-sitemaps','appEngine-google','feedfetcher-google',
                'liveinternet.ru','xml-sitemaps.com','agama','metadatalabs.com','h1.hrn.ru',
                'googlealert.com','seo-rus.com','yaDirectBot','yandeG','yandex',
                'yandexSomething','Copyscape.com','AdsBot-Google','domaintools.com',
                'Nigma.ru','bing.com','dotnetdotcom'*/
                $_SERVER['HTTP_USER_AGENT'], $robot)){

                $robot=$robot[1];
                $ipsYandex = unserialize($_cache_pub->get($cache_keyYandex));   if (is_array($ipsYandex) && in_array($ip, $ipsYandex)){ return $_robot[$name.'_']=$robot;}
                $ipsGoogle = unserialize($_cache_pub->get($cache_keyGoogle));   if (is_array($ipsGoogle) && in_array($ip, $ipsGoogle)){ return $_robot[$name.'_']=$robot;}
                $ipsBot    = unserialize($_cache_pub->get($cache_keyBot));      if (is_array($ipsBot)    && in_array($ip, $ipsBot)){ return $_robot[$name.'_']=$robot;}
                if(!empty($name)){
                    $host_name = gethostbyaddr($ip);
                    if (strtolower(substr($robot, 0, 8)) == 'yandex'){ // https://yandex.ru/support/webmaster/robot-workings/check-yandex-robots.xml
                        if (preg_match('/^.+?\.yandex\.(ru|net|com)$/', $host_name)){
                            $ip1 = gethostbyname($host_name);
                            if ($ip1 == $ip){ // иначе хорошо поддельный робот
                                // todo получать выделенную яндексу подсеть и всю ее заносить
                                $ipsYandex = unserialize($_cache_pub->get($cache_keyYandex));
                                if (is_array($ipsYandex)) $ipsYandex[] = $ip; else $ipsYandex = [$ip];
                                $_cache_pub->set($cache_keyYandex, serialize($ipsYandex));
                                self::BotLog('Yandex',$host_name.'='.$ip1);
                                $_robot[$name.'_']=$name;
                                return true;
                            }else $robot='Bot_'.$robot;
                        }else $robot='Bot_'.$robot;

                    }elseif (stripos($robot,'google')!==false){
                        // 10.12.16 15:30:16 Bot(Mediapartners-Google): 66.249.91.70(rate-limited-proxy-66-249-91-70.google.com) Mediapartners-Google /geo/telcod.php?short=link&tel=79632468491
                        if (preg_match('/^.+?\.googlebot\.com$/', $host_name)){
                            $ip1 = gethostbyname($host_name);
                            if ($ip1 == $ip){ // иначе хорошо поддельный робот
                                $ipsGoogle = unserialize($_cache_pub->get($cache_keyGoogle));
                                if (is_array($ipsGoogle)) $ipsGoogle[] = $ip; else $ipsGoogle = [$ip];
                                $_cache_pub->set($cache_keyGoogle, serialize($ipsGoogle));
                                self::BotLog('Google',$host_name.'='.$ip1);
                                $_robot[$name.'_']=$name;
                                return true;
                            }else $robot='Bot_'.$robot;
                        }else $robot='Bot_'.$robot;

                    }elseif (stripos($robot,'bingbot')!==false){
                        // Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)
                        if (preg_match('/^.+?\.bing\.com$/', $host_name)){
                            $ip1 = gethostbyname($host_name);
                            if ($ip1 == $ip){ // иначе хорошо поддельный робот
                                $ipsGoogle = unserialize($_cache_pub->get($cache_keyGoogle));
                                if (is_array($ipsGoogle)) $ipsGoogle[] = $ip; else $ipsGoogle = [$ip];
                                $_cache_pub->set($cache_keyGoogle, serialize($ipsGoogle));
                                self::BotLog('Bing',$host_name.'='.$ip1);
                                $_robot[$name.'_']=$name;
                                return true;
                            }else $robot='Bot_'.$robot;
                        }else $robot='Bot_'.$robot;
                            }

                    $ipsBot = unserialize($_cache_pub->get($cache_keyBot));
                    if (is_array($ipsBot)) $ipsBot[] = $ip; else $ipsBot = [$ip];
                    $_cache_pub->set($cache_keyBot, serialize($ipsBot));
                    self::BotLog('Bot('.$robot.')',$host_name.(isset($ip1)?'~'.$ip1:''));
                }else{ // нет задачи определить конкретного робота
                    self::BotLog('Bot('.$robot.')');
                }
            }elseif(empty($_SERVER['HTTP_ACCEPT']) && empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                $robot = 'Robot';
                self::BotLog($robot,"empty HTTP_ACCEPT");
            }else{
                $robot=0;
            }
        }else {
            $robot = 'Robot';
            self::BotLog($robot,"empty USER_AGENT");
        }
        $_robot[$name.'_']=strtolower($robot);
        return ($name ? ($name==strtolower($robot)) : $robot);
        }

    static function BotLog($Bot, $add_ip=''){
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/bot.log', date('d.m.y H:i:s').' '.$Bot.': '.Get::ip().' '.($add_ip?'('.$add_ip.') ':'').
            (empty($_SERVER['HTTP_USER_AGENT'])?'':$_SERVER['HTTP_USER_AGENT'].' ').
            (empty($_SERVER['REQUEST_URI'])?'':$_SERVER['REQUEST_URI'])."\n" , FILE_APPEND|LOCK_EX);
    }

    static function isMobile(){
        static $mobile=null; if(!is_null($mobile))return $mobile;
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            if(preg_match('/android|midp|j2me|symbian|series\ 60|symbos|windows\ mobile|windows\ ce|ppc|smartphone|blackberry|mtk|bada|windows\ phone/', $_SERVER['HTTP_USER_AGENT']))$_GET['mobile']=1;
        }
        if(isset($_GET['mobile'])){
            $mobile=intval($_GET['mobile']);
            if(!headers_sent())setcookie('mobile',$mobile, time()+60*60*24*30, CookiePath, CookieDomain);
            $_COOKIE['mobile']=$mobile;
        }elseif(isset($_COOKIE['mobile'])){
            $mobile=intval($_COOKIE['mobile']);
        }elseif(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/android|midp|j2me|series 60|symbos|ppc|smartphone|blackberry|mtk|bada|up.browser|up. link |windows ce|iemobile|mini|mmp|symbian|midp|wap|phone|pocket|mobile|pda|psp/i', $_SERVER['HTTP_USER_AGENT'],$ar)){
            $mobile=1;
        }elseif(isset($_SERVER['HTTP_ACCEPT'])&&(stristr($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')||stristr($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml xml'))||
            isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])||isset($_SERVER['X-OperaMini-Features'])||isset($_SERVER['UA-pixels'])){
            $mobile=1;
        }else $mobile=0;
        return $mobile;
    }

    /** возвращает все ip пользователя. Для получения основного используйте $f1=true
     * @param bool $f1 =true?  вернуть только один IP
     * @return string
     */
    static function ip($f1=false){
        static $ip="";
        if(!$ip){
            if (!empty($_SERVER['REMOTE_ADDR'])) $ip .= ', ' . $_SERVER['REMOTE_ADDR']; $ip2 = getenv('REMOTE_ADDR');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_X_FORWARDED_FOR'); // используется не анонимными прокси-серверами для передачи реального IP клиента X-Forwarded-For: client_ip, proxy1_ip, ..., proxyN_ip
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_FORWARDED_FOR');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_X_COMING_FROM');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_VIA');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_XROXY_CONNECTION');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2; $ip2 = getenv('HTTP_CLIENT_IP');
            if (!empty($ip2) && $ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2;
            if (!empty($_SERVER['HTTP_X_REAL_IP'])){
                $ip2 = $_SERVER['HTTP_X_REAL_IP'];
                if ($ip2 != "0.0.0.0" && stripos($ip, $ip2) === false) $ip .= ', ' . $ip2;
            }
            $ip = substr($ip, 2);
        }
        return ($f1 ? strtok($ip,',') : $ip );
    }

    /** возвращает true, если это полностью русское словосочетание или содержит хотя бы одну русскую букву
     * @param $str
     * @param bool $contains = true, содержит хотя бы одну русскую букву
     * @return bool
     */
    static function isRus($str, $contains=false){
        if($contains)return !!preg_match('/[а-ярстуфхцчщшэюёА-ЯРСТУФХЦЧЩШЭЮЁ]/',$str);
        return !!preg_match('/^[а-ярстуфхцчщшэюёА-ЯРСТУФХЦЧЩШЭЮЁ0-9_\.\`\'\" \-]+$/',$str);
    }

    /** возвращает true, если это полностью английское словосочетание
     * @param $str
     * @return bool
     */
    static function isEng($str){
        return !!preg_match('/^[a-zA-Z0-9_\.\`\'\" \-]+$/',$str);
    }

    /** переданный параметр является числовым кодом
     * @param $str
     * @return bool
     */
    static function isKod($str){
        return (!!preg_match('/^[0-9]+$/',trim($str),$ar)) && intval($str)>0;
    }

    /** возвращает значения из заданной колонку из массива
     * @param $array - массив
     * @param $column_name - индекс колоки
     * @return array
     */
    static function Column($array,$column_name)
    {
        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
    }

    /** определяем выходные дни
     * @param $date
     * @return bool
     */
    static function isWeekend($date) {
        return (date('N', strtotime($date)) >= 6);
    }

    /**
     * @param $name - oklad, hour_price1
     * @param array $params
     * @return string
     */
    public static function __callStatic($name, array $params)
    {
        if(DB::is_table('config')&&($row=DB::Select('config','`key`="'.addslashes($name).'"'.(empty($params[0])?'':' and `from`>="'.date('Y-m-d',(is_integer($params[0])?$params[0]:strtotime($params[0]))).'"').' ORDER BY `from` DESC'))) {
            return $row['value'];
        }elseif($name=='DEBUG'){
            return(getenv('REMOTE_ADDR')=='127.0.0.1'||User::name()=='kdg');
        }else{
            echo 'Вы хотели вызвать '.__CLASS__.'::'.$name.', но его не существует, и сейчас выполняется '.__METHOD__.'()';
            return '';
        }
    }


}
