<?
/*
defined('fb_cachedir') || define('fb_cachedir', sys_get_temp_dir().'/');
if(!is_dir(fb_cachedir))mkdir(fb_cachedir,'777');
*/
/**
 * Class ReadUrl
 * @ver 1.1
 * list($headers,$buf,$info)=ReadUrl::ReadWithHeader($_url);
 */
class ReadUrl {
    protected
        $url = null,
        $post = false,
        $options=false,
        $redirect_info='';
    public $result = ['','',[]];

    public function __construct($url, $post=false, $options=false)
    {
        $this->url = trim($url);
        $this->post = $post;
        $this->options = $options;
        if(!isset($this->options['debug']) && isset($_REQUEST['debug'])  )$this->options['debug']=!0;
        $this->exec();
    }

    public function exec(){
        if(strlen($this->url)>2048){
            $this->result = ['', '', ['curl_error'=>'Слишком длинный url: '.strlen($this->url)]];
            return;
        }
        if(is_integer($this->options)){
            $cache = $this->options;
            $this->options=['cache'=>$cache];
        }else{
            $cache = (isset($this->options['cache'])? $this->options['cache'] : 720); // 12 часов в минутах
        }
        $this->options['cache_filename']= (empty($this->options['cache_filename'])?fb_cachedir . url2file($this->url.($this->post?(is_array($this->post)?multi_implode('&',$this->post):$this->post):'')).'.tmp' : $this->options['cache_filename'] );

        if(!empty($this->options['debug'])) echo "<br><br>Читаю <a href='".$this->url."'>".$this->url."</a><br>\n".var_export($this->options,!0);
        $cache_enable=$cache && is_file($this->options['cache_filename']) && !isset($_GET['reload']);

        if(!$cache_enable && is_file($this->options['cache_filename']))@unlink($this->options['cache_filename']);

        if( $cache_enable && (time()-$cache*60) < filemtime($this->options['cache_filename']) ){
            $this->result = self::ReadCache($this->options['cache_filename'], $this->options);
            if(!empty($this->options['debug']))echo " из кеша";
        }else{
            //echo "<br>fil=".$this->cache_filename."<br>".var_dump($cache_enable).'~<br>'.(time()-$cache*60).'<br>'.var_dump(@filemtime($this->cache_filename)); exit;
            if($cache_enable && empty($this->options['time']))$this->options['time']=filemtime($this->options['cache_filename']);

            $info['cache_filename']=$this->options['cache_filename'];
            $redirect_info=''; $redirect_count=0;
            $redirect_max=(isset($options['FollowLocation'])&&!$options['FollowLocation'] ? 0 : (empty($options['FollowLocation'])?3:intval($options['FollowLocation'])) );
            do{
                if(isset($info['http_code']) && isset($headers)  && isset($curl) && ($info['http_code']==301 || $info['http_code']==302 || $info['http_code']==307)){
                    $redirect_info.="\nGET ".$this->url." HTTP/1.1\n".$headers; $redirect_count++;
                    if(preg_match('/Location:(.*?)\n/', $headers, $matches)){
                        $url = trim(array_pop($matches));
                        // todo проверяю может есть в кеше
                        //echo "<br>".$url."==".$this->url;
                        if($url==$this->url){
                            curl_close($curl);
                            $info['curl_error'] = "указана циклическая переадресация!";
                            $this->result = [$redirect_info, '', $info];
                            return;
                        }
                    }else{
                        curl_close($curl);
                        $info['curl_error']="указана переадресация, но не указано куда!";
                        $this->result = [$redirect_info, '', $info];
                        return;
                    }
                    $this->url=$url;
                }else $url=$this->url;

                $curl=self::Curl($url, $this->post, $this->options);
                if(!$curl){
                    $this->result = ['', '', ['curl_error'=>( empty($this->options['curl_error']) ? 'Ошибка curl' : $this->options['curl_error'] )]];
                    return;
                }
                //$html = self::curl_exec_follow($curl);
                $html = curl_exec($curl);
                $info = curl_getinfo($curl);
                $info['cache_filename']=$this->options['cache_filename']; // восстанавливаю
                $headers = substr($html, 0, $info['header_size'] - 4)."\n";
                //echo "<br><br>".toHtml($info)."<br>".nl2br(toHtml($headers));
                $html=substr($html, $info['header_size'],(empty($this->options['max-length'])?1000000:$this->options['max-length']));  // здесь может сыпаться по памяти
                if(curl_error($curl)){
                    if (!empty($this->options['debug'])) echo "<br>" . curl_error($curl);
                    $info['curl_error'] = curl_error($curl);
                    curl_close($curl);
                    //$info['header_size']=strlen($redirect_info.$headers);
                    $info['redirect_info']=$redirect_info;
                    $this->result = [$headers, '', $info];
                    return;
                }
            }while(($info['http_code']==301 || $info['http_code']==302 || $info['http_code']==307) && $redirect_count<$redirect_max);
            curl_close($curl);
            //$headers=$headers."\n".$redirect_info;
            $info['redirect_count']+=$redirect_count;
            $info['url']=$this->url;
            $info['cache']=$cache;
            $info['redirect_info']=$redirect_info;
            $this->result=self::AfterRead($headers,$html,$info,$this->options);
        return;
        }
    }

    /**
     * @param $url
     * @param string $post
     * @param array $options
     * @return resource|null
     */
    static function Curl($url, $post='', $options=[])
    {
        if(Get::isRus($url,!0)){
            $domain=Domain::BaseDomain($url, true);
            if(!empty($domain['error'])){
                $options['curl_error']=$domain['error'];
                return null;
            }
            $url=$domain['schema'].$domain['base'].$domain['url'];
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);    // включать header в вывод
        /*   if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
               curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // следовать любому "Location: " header
               curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
           } else */
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        if (isset($options['max-length']) && $options['max-length'] == 0 || isset($options['nobody'])){
            curl_setopt($curl, CURLOPT_NOBODY, true); // читать ТОЛЬКО заголовок без тела
        } else {
            curl_setopt($curl, CURLOPT_BUFFERSIZE, 1024 * 200); // more progress info
            curl_setopt($curl, CURLOPT_NOPROGRESS, false);
            $GLOBALS['curl_MaxSize'] = (empty($options['max-length']) ? 1000000 : $options['max-length']);
            curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ( // php 5.3
                $DownloadSize, $Downloaded, $UploadSize, $Uploaded) {
                // If $Downloaded exceeds 1000KB, returning non-0 breaks the connection!
                return ($Downloaded > $GLOBALS['curl_MaxSize']) ? 1 : 0;
            }); // todo использовать $options['max-length']
        }
        /*if(!empty($options['max-length'])){
            $GLOBALS['curl_maxLength']=$options['max-length'];
            $GLOBALS['curl_html']='';
            curl_setopt($curly[$id], CURLOPT_BUFFERSIZE , $options['max-length']); // страница не более 1 Мб
            //curl_setopt($curly[$id], CURLOPT_WRITEFUNCTION, 'ReadUrl::curl_write'); // array($this, 'curl_write')
        }*/
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        if (!empty($options['proxy'])){ // использование прокси
            curl_setopt($curl, CURLOPT_PROXY, $options['proxy']);//     CURLOPT_PROXYPORT
            // CURLOPT_PROXYTYPE 	Либо CURLPROXY_HTTP (по умолчанию), либо CURLPROXY_SOCKS5.
            //curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1); // http://www.dragonflybsd.org/cgi/web-man?command=CURLOPT_HTTPPROXYTUNNEL&section=3
            // curl_setopt($ch, CURLOPT_PROXYUSERPWD,'user:pass');
            /*if(!isset($options['FollowLocation'])||$options['FollowLocation'])*/
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            if(isset($options['FollowLocation']))curl_setopt($curl, CURLOPT_MAXREDIRS, ($options['FollowLocation']>1?intval($options['FollowLocation']) : 1) );
        }elseif(isset($options['FollowLocation'])){
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, ($options['FollowLocation']>1?intval($options['FollowLocation']) : 1) );

        }else{
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);

        }
        if (!empty($options['basic'])){ // BASIC авторизация
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $options['basic']);
        }

        /* HTTP аутентификация
         // указываем имя и пароль
curl_setopt($ch, CURLOPT_USERPWD, "myusername:mypassword");
curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, 1);
*/
        //curl_easy_setopt CURLOPT_HTTP_VERSION в значение CURL_HTTP_VERSION_2
        // мультиплексирование
        //      curl_multi_setopt(CURLM *handle, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);
        //      curl_easy_setopt(CURL *handle, CURLOPT_PIPEWAIT, 1);
        // посылка сервера
        //      curl_multi_setopt(CURLM *handle, CURLMOPT_PUSHFUNCTION, curl_push_callback func);
        $timeout=(empty($options['timeout']) ? 60 : $options['timeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);    // максимальное время в секундах, для работы CURL-функций.
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, ceil($timeout * 0.7));
        $Header = ['Accept-Language: ru,ru-RU;q=0.8,en-US;q=0.5,en;q=0.3'];
        curl_setopt($curl, CURLOPT_REFERER, empty($options['Referer']) ? $url : $options['Referer']);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // для автоматической установки поля Referer: в запросах, перенаправленных заголовком Location
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        if (!empty($options['time'])) $Header[] = 'If-Modified-Since: ' . gmdate('D, d M Y H:i:s', strtotime($options['time'])) . ' GMT';
        // todo CURLOPT_TIMECONDITION 	Способ трактовки параметра CURLOPT_TIMEVALUE. Используйте CURL_TIMECOND_IFMODSINCE
        //curl_setopt($curl, CURLOPT_FILE , $this->cache_filename); // Файл, куда должен быть помещён вывод
        if(empty($options['cookie_filename'])){
            if(preg_match('@^(?:https?\://)?([^/\?]+)@i', $url, $tmp)){
                $options['cookie_filename'] = $tmp[1];
                if(!empty($options['proxy']))$options['cookie_filename'].='_'.ip2long($options['proxy']); // в разрезе прокси
            }else{
                $options['curl_error']='Ошибка в url: '.strlen($url);
                return null;
                //die($this->url. " - не выделил домен!".var_export($this->cookie_filename,!0));
            }
            $options['cookie_filename']=fb_cachedir.url2file($options['cookie_filename']).'_cookie.txt';
        }

        curl_setopt($curl, CURLOPT_COOKIEJAR, $options['cookie_filename']);//сохранять полученные COOKIE в файл
        curl_setopt($curl, CURLOPT_COOKIEFILE, $options['cookie_filename']); //отсылаем серверу COOKIE полученные от него при авторизации
        if (!empty($options['UserAgent'])) curl_setopt($curl, CURLOPT_USERAGENT, $options['UserAgent']); elseif (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        if ($post){
            //foreach($this->post as &$tmp)if(is_array($tmp)) $tmp=serialize($tmp);
            if (is_array($post)) $post = self::convertToStringArray($post); // для многомерных массивов
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // для передачи файла нужно перед именем файла добавить '@': array('fil'=>'@C:/up/a.txt')
        }
        //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        if (!empty($options['Header']) && is_array($options['Header'])) $Header=array_merge($Header,$options['Header']);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $Header);
        if(!empty($options['debug'])&&$Header)echo "<br>Header=".var_export($Header,!0);
        return $curl;
    }
        /**
     * @param string $headers
     * @param string $html - ТОЛЬКО body
     * @param array $info - curl_getinfo
     * @param array|integer|boolean $options
     * @return array
     */
    static function AfterRead($headers, $html, $info, $options){
        $info['header_size']=strlen($headers)+4;
        if(!empty($info['cache']) && empty($info['cache_filename']))$info['cache_filename'] = fb_cachedir . url2file($info['url']).'.tmp';
        if(!empty($info['cache']) && $info['http_code']==304){// файл не менялся
            list($headers,$body,$info)=self::ReadCache($info['cache_filename'], $options); // перетирает $info
            if(!empty($options['debug']))echo " из кеша по 304";
        }else{
            try {
                $info['effective_url']=iconv('windows-1251',charset.'//IGNORE',$info['url']);
            } catch (Exception $e) {
                //echo 'Поймано исключение: ',  $e->getMessage(), "\n";
                $info['effective_url']=$info['url'];
            /*}finally{
                $info['effective_url']=$info['url'];*/
            }

            $info['charset'] = self::GetCharset($headers,$html);
            //$info['url']=iconv('windows-1251','utf-8',$this->url);
            if($info['url']==$info['effective_url'])unset($info['effective_url']);

            if(!empty($info['cache']) && !isset($options['max-length']) && !isset($options['nobody'])){
                file_put_contents($info['cache_filename'], (empty($options['nohead']) ? @json_encode($info)."\r\n\r\n".
                    /*(empty($info['redirect_info'])?'':$info['redirect_info']).*/$headers."\r\n\r\n".$html : $html ),LOCK_EX);
                clearstatcache();
            }

            if ( (strlen($html)<20 && (!isset($options['max-length'])||$options['max-length']>strlen($html))) && !isset($options['nobody']) || ($info['http_code'] <> '200')){
                if(!empty($options['debug']))echo "- код возврата ".$info['http_code'].", strlen(html)=".strlen($html).": \n".substr($html,0,500);
                $info['curl_error']=(empty($info['curl_error'])?'':"\n").", len:".strlen($html).', http_code='. $info['http_code'];
                //$body = '';
            }
            //$headers=$redirect_info.$headers;
            list($headers,$body,$info)=self::Convert($headers,$html,$info, $options);
        }
        if(isset($options['max-length'])&&$options['max-length']==0 || isset($options['nobody']) )$body='';
        return [$headers,$body,$info];
    }

    /**
     * @param $headers
     * @param $body
     * @param $info
     * @param $options
     * @return array
     */
    static function Convert($headers, $body, $info, $options=[]){
        $Convert = (empty($options['convert'])? '' : $options['convert'] );
        if (!empty($body) && $Convert && $info['charset']!=$Convert){
            if(!empty($options['debug']))echo "<br>Преобразование ".$info['charset']." -&gt; ".$Convert;
            if($info['content_type']=="text/html" && ($i=strpos($body,"<!DOCTYPE"))!==false)$body=substr($body,$i);
            //$body=@iconv($info['charset'],$Convert.'//IGNORE',$body);
            $body=@mb_convert_encoding($body, $Convert, $info['charset']);
            if(empty($body))$info['curl_error']=(empty($info['curl_error'])?'':"\n").'convert_error '.$info['charset']." in ".$Convert;
        }
        return [$headers,$body,$info];
    }
    /** list($headers,$body,$info)=ReadUrl::ReadCache($cache_filename, $options);
     * @param string $cache_filename
     * @param array $options
     * @return array ($headers,$body,$info)
     */
    static public function ReadCache($cache_filename, $options=[]){
        if(!is_file($cache_filename)){
            return ['','', ['cache'=>false]];
        }
        $html=file_get_contents($cache_filename);
        if(!empty($options['nohead'])){
            $body=$html;
            $info =[];
            $headers = '';
        }else{
            $size=strpos($html,"\r\n\r\n");
            $info=json_decode(substr($html, 0, $size),!0);
            $html=substr($html, $size+4);
            $headers = substr($html, 0, $info['header_size'] - 4)."\n";
            $body = substr($html, $info['header_size']);
        }
        $info['cache']=!0;
        $info['cache_filename']=$cache_filename;
        return self::Convert($headers, $body, $info, $options);
    }

    /** list($headers,$body,$info)=ReadUrl::ReadWithHeader("http://www.cbr.ru/");
     * @param string $url
     * @param bool|array $post
     * @param bool|array $options {'debug', 'cache'-в минутах, 'convert','timeout','max-length'}
     * @return array ($headers,$body,$info)
     */
    static public function ReadWithHeader($url, $post=false, $options=false) {
        $obj = new ReadUrl($url, $post, $options);
        return $obj->result;
    }

    /** Возвращает значение конкретного поля из заголовка, аналог DOMAIN->header
     * @param string $name - название поля заголовка
     * @param string $headers - строка заголовков для выделения нужной части
     * @return string
     */
    static function getHeader($name, $headers){
        if(preg_match("|".preg_quote($name).":(.*)[\n; ]|i", $headers, $results))return trim($results[1]);
        else return '';
    }

    /** определить кодовую страницу сайта
     * @param $headers
     * @param $body
     * @param null $err
     * @return bool|string
     */
    static function GetCharset($headers, $body, &$err=null){
        if (preg_match("|Content[\\-_]Type: .*?charset=(.*)[\n; ]|imsU", $headers, $results)){
            $ct0=mb_strtolower(trim($results[1]));
        }else{
            $ct0=false;
            if(!is_null($err))$err.="\nСервер не выдает Content-Type с charset!";
        }
        if (preg_match("|Content[\\-_]Type: .*application.*\n|imsU", $headers, $results)){
            if($ct0)return $ct0;
            $ct1=false;
        }elseif(preg_match_all('/(<meta\s*http\-equiv=[\'\"]Content-Type[\'\"]\s*content=[\'\"][^;]*;\s*charset=([^\"\']*?)(?:"|\;|\')[^>]*>)/i',$body,$arr,PREG_PATTERN_ORDER) ||
            preg_match_all('/(<meta\s+content=[\'\"][^;]*;\s*charset=([^\"\']*?)(?:"|\;|\')\s*http\-equiv=[\'\"]Content-Type[\'\"][^>]*>)/i',$body,$arr,PREG_PATTERN_ORDER) ||
            preg_match_all('/(<meta\s+charset=[\'\"]([^\"\']*)[\'\"][^>]*>)/i',$body,$arr,PREG_PATTERN_ORDER) ){ // <meta charset="windows-1251">
            if(count($arr[2])>1&& !is_null($err))$err.="\nНесколько заголовков META Content-Type!";
            $ct1=mb_strtolower(trim($arr[2][0]));
            if($ct0&&$ct1&&$ct1!=$ct0 && !is_null($err))$err.="\nРазные объявления кодовой страницы у сервера(".$ct0.') и в meta('.$ct1.") ";
            if ($ct1!='utf-8'||$ct1!=$ct0){
                //$new=str_replace($arr[2][0],'utf-8',$arr[1][0]);
                //$body= str_replace($arr[1][0],$new,$body); // заменяю на utf-8 !
                if($ct0!='iso-8859-1'&&$ct0) $ct1=$ct0; // приоритет у заголовка
            }else
                $ct1=false;
        }else{
            $ct1=false;
        }
        if(!$ct1)$ct1=($ct0?$ct0:'utf-8');
        return $ct1;
    }

    static public function curl_exec_follow(/*resource*/ $ch, /*int*/ &$maxredirect = null) {
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                            if(isset($_REQUEST['debug'])) echo "<br><br>Переадресация, Читаю <a href='".$newurl."'>".$newurl."</a>";
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }

    /** Чтение страниц, парсинг ссылок  и занесение их в БД
     * @param array $urls
     * @param array $options callback, convert
     * @return array []['info'=>$info, 'url'=>$url, 'body'=>$body, 'headers'=>$headers]
     $bodys=ReadUrl::ReadMultiUrl($urls_list,['cache'=>$cache,'convert'=>'windows-1251']);
     foreach($bodys as $row){ // $row['info'], $row['url'], $row['body'], $row['headers'];

     */
    static function ReadMultiUrl($urls, $options=[])
    {
        $row=[];
        $curly =[];
        $result =[];
        $cache = isset($options['cache']) ? $options['cache'] : 0 ;
        if(!isset($options['FollowLocation']))$options['FollowLocation']=3;
        $mh = curl_multi_init();
        foreach ($urls as $id => $url) if ($url) {
            $cache_filename = fb_cachedir . url2file($url).'.tmp';
            $cache_enable=$cache && is_file($cache_filename) && !isset($_GET['reload']);
            if(!$cache_enable && is_file($cache_filename))@unlink($cache_filename);
            if( $cache_enable && (time()-$cache*60) < filemtime($cache_filename) ){
                list($headers,$body,$info)=self::ReadCache($cache_filename, $options);
                if(empty($info['http_code'])||$info['http_code']==500){ // ||empty($body)
                    if(!empty($options['debug'])) echo "<br>\n".$id.": В кеше " . $url. " ".strlen($body)."байт, header_size=".strlen($headers).", http_code=".$info['http_code']." попробую перечитать...";
                }else{
                    if(!empty($options['debug'])) echo "<br>\n".$id.": Прочитал из кеша " . $url. " ".strlen($body)."байт, header_size=".strlen($headers);
                    //echo ", strlen(body)=".strlen($body);
                    //if(is_callable($AfterCurl))call_user_func_array($AfterCurl, array($info, $url, $body));
                    $row[]= ['info'=>$info, 'url'=>$url, 'body'=>$body, 'headers'=>$headers];
                    continue;
                }
            }
            $urls[$id] = $url;
            $c=self::Curl($url, '', $options);
            if($c)curl_multi_add_handle($mh, $curly[$id]=$c);
        }
        if(count($urls)>0){
        if(!empty($options['debug'])) echo "<br>\nЧитаю " . count($urls) . " страниц.".implode(", ",$urls);
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if($running > 0)curl_multi_select($mh,1);// Блокирует выполнение скрипта, пока какое-либо из curl_multi соединений не станет активным //sleep(1);
            /*if(!empty($options['callback'])){ // если указанно вызывать функцию-обработчик по мере отработки запросов - делаю это
                while (($done = curl_multi_info_read($mh))) {
                        call_user_func($options['callback'], array_search($done['handle'], $curly), curl_multi_getcontent($done ['handle']), curl_getinfo($done ['handle']));
                    curl_multi_remove_handle($mh, $done['handle']);
                }
            }*/
        } while ($running > 0);

        foreach ($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            $info=curl_getinfo($c);
            $info['url']=$urls[$id];
            $info['cache']=$cache;
            if(curl_error($c))$info['curl_error']=curl_error($c)." (".curl_errno($c).')'; //	//CURLE_OPERATION_TIMEOUT
            $headers = substr($result[$id], 0, $info['header_size'] - 4)."\n";
            $result[$id]=substr($result[$id], $info['header_size']);
            if(!empty($options['debug'])) echo "<br>\n".$id.": Прочитал " . $urls[$id]. " ".strlen($result[$id])."байт, header_size=".$info['header_size']. var_export($info,!0);
            list($headers,$body,$info)=self::AfterRead($headers,$result[$id],$info,$options);
            $row[]= ['info'=>$info, 'url'=>$urls[$id], 'body'=>$body, 'headers'=>$headers];
            //if(is_callable($AfterCurl)) call_user_func_array($AfterCurl, array($info, $urls[$id], $html));
            curl_multi_remove_handle($mh, $c);
        }
        }
        unset($GLOBALS['curl_MaxSize'], $GLOBALS['curl_html']);
        curl_multi_close($mh);
        return $row;
    }

    static function curl_write($curl, $data){
        $GLOBALS['curl_html'].=$data;
        if(strlen($GLOBALS['curl_html']) > $GLOBALS['curl_MaxSize']) {
            return 0;
        }else
            return strlen($data);
    }

/**
 * Более продвинутый аналог strip_tags() для корректного вырезания тагов из html кода.
 * Функция strip_tags(), в зависимости от контекста, может работать не корректно.
 * Возможности:
 *   - корректно обрабатываются вхождения типа "a < b > c"
 *   - корректно обрабатывается "грязный" html, когда в значениях атрибутов тагов могут встречаться символы < >
 *   - корректно обрабатывается разбитый html
 *   - вырезаются комментарии, скрипты, стили, PHP, Perl, ASP код, MS Word таги, CDATA
 *   - автоматически форматируется текст, если он содержит html код
 *   - защита от подделок типа: "<<fake>script>alert('hi')</</fake>script>"
 *
 * @param   string  $s
 * @param   array   $allowable_tags     Массив тагов, которые не будут вырезаны
 *                                      Пример: 'b' -- таг останется с атрибутами, '<b>' -- таг останется без атрибутов
 * @param   bool    $is_format_spaces   Форматировать пробелы и переносы строк?
 *                                      Вид текста на выходе (plain) максимально приближеется виду текста в браузере на входе.
 *                                      Другими словами, грамотно преобразует text/html в text/plain.
 *                                      Текст форматируется только в том случае, если были вырезаны какие-либо таги.
 * @param   array   $pair_tags   массив имён парных тагов, которые будут удалены вместе с содержимым
 *                               см. значения по умолчанию
 * @param   array   $para_tags   массив имён парных тагов, которые будут восприниматься как параграфы (если $is_format_spaces = true)
 *                               см. значения по умолчанию
 * @return  string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat
 * @charset  ANSI
 * @version  4.0.14
 */
static function strip_tags(
    /*string*/ $s,
               array $allowable_tags = null,
    /*boolean*/ $is_format_spaces = true,
               array $pair_tags = ['script', 'style', 'map', 'iframe', 'frameset', 'object', 'applet', 'comment', 'button', 'textarea', 'select'],
               array $para_tags = ['p', 'td', 'th', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'form', 'title', 'pre']
)
{
    //return strip_tags($s);
    static $_callback_type  = false;
    static $_allowable_tags = [];
    static $_para_tags      = [];
    //regular expression for tag attributes
    //correct processes dirty and broken HTML in a singlebyte or multibyte UTF-8 charset!
    static $re_attrs_fast_safe =  '(?![a-zA-Z\d])
                                   (?>
                                       [^>"\']+
                                     | (?<=[\ =\x20\r\n\t]|\xc2\xa0) "[^"]*"
                                     | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*\'
                                   )*
                                   [^>]*+';

    if (is_array($s))
    {
        if ($_callback_type === 'strip_tags')
        {
            $tag = strtolower($s[1]);
            if ($_allowable_tags)
            {
                /*tag with attributes*/
                if (array_key_exists($tag, $_allowable_tags)) return $s[0];

                /*tag without attributes*/
                if (array_key_exists('<' . $tag . '>', $_allowable_tags))
                {
                    if (substr($s[0], 0, 2) === '</') return '</' . $tag . '>';
                    if (substr($s[0], -2) === '/>')   return '<' . $tag . ' />';
                    return '<' . $tag . '>';
                }
            }
            if ($tag === 'br') return "\r\n";
            if ($_para_tags && array_key_exists($tag, $_para_tags)) return "\r\n\r\n";
            return '';
        }
        trigger_error('Unknown callback type "' . $_callback_type . '"!', E_USER_ERROR);
    }

    if (($pos = strpos($s, '<')) === false || strpos($s, '>', $pos) === false)  //speed improve
    {
        //tags are not found
        return $s;
    }

    $length = strlen($s);

    //unpaired tags (opening, closing, !DOCTYPE, MS Word namespace)
    $re_tags = '~  <[/!]?+
                   (
                       [a-zA-Z][a-zA-Z\d]*+
                       (?>:[a-zA-Z][a-zA-Z\d]*+)?
                   ) #1
                   ' . $re_attrs_fast_safe . '
                   >
                ~sxSX';

    $patterns = [
        '/<([\?\%]) .*? \\1>/sxSX',     /*встроенный PHP, Perl, ASP код*/
        '/<\!\[CDATA\[ .*? \]\]>/sxSX', /*блоки CDATA*/
        /*'/<\!\[  [\x20\r\n\t]* [a-zA-Z] .*?  \]>/sxSX',  //:DEPRECATED: MS Word таги типа <![if! vml]>...<![endif]>*/

        '/<\!--.*?-->/sSX', /*комментарии*/

        /*MS Word таги типа "<![if! vml]>...<![endif]>",*/
        /*условное выполнение кода для IE типа "<!--[if expression]> HTML <![endif]-->"*/
        /*условное выполнение кода для IE типа "<![if expression]> HTML <![endif]>"*/
        /*см. http://www.tigir.com/comments.htm*/
        '/ <\! (?:--)?+
               \[
               (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
               \]
               (?:--)?+
           >
         /sxSX',];
    if ($pair_tags)
    {
        //парные таги вместе с содержимым:
        foreach ($pair_tags as $k => $v) $pair_tags[$k] = preg_quote($v, '/');
        $patterns[] = '/ <((?i:' . implode('|', $pair_tags) . '))' . $re_attrs_fast_safe . '(?<!\/)>
                         .*?
                         <\/(?i:\\1)' . $re_attrs_fast_safe . '>
                       /sxSX';
    }
    //d($patterns);

    $i = 0; //защита от зацикливания
    $max = 99;
    while ($i < $max)
    {
        $s2 = preg_replace($patterns, '', $s);
        if (preg_last_error() !== PREG_NO_ERROR)
        {
            $i = 999;
            break;
        }

        if ($i == 0)
        {
            $is_html = ($s2 != $s || preg_match($re_tags, $s2));
            if (preg_last_error() !== PREG_NO_ERROR)
            {
                $i = 999;
                break;
            }
            if ($is_html)
            {
                if ($is_format_spaces)
                {
                        $s2=str_replace(array('&nbsp;','&quot;'),array(' ','"'),$s2);
                    /*
                    В библиотеке PCRE для PHP \s - это любой пробельный символ, а именно класс символов [\x09\x0a\x0c\x0d\x20\xa0] или, по другому, [\t\n\f\r \xa0]
                    Если \s используется с модификатором /u, то \s трактуется как [\x09\x0a\x0c\x0d\x20]
                    Браузер не делает различия между пробельными символами, друг за другом подряд идущие символы воспринимаются как один
                    */
                    //$s2 = str_replace(array("\r", "\n", "\t"), ' ', $s2);
                    //$s2 = strtr($s2, "\x09\x0a\x0c\x0d", '    ');
                    $s2 = preg_replace('/  [\x09\x0a\x0c\x0d]++
                                         | <((?i:pre|textarea))' . $re_attrs_fast_safe . '(?<!\/)>
                                           .+?
                                           <\/(?i:\\1)' . $re_attrs_fast_safe . '>
                                           \K
                                        /sxSX', ' ', $s2);
                    if (preg_last_error() !== PREG_NO_ERROR)
                    {
                        $i = 999;
                        break;
                    }
                }

                /*массив тагов, которые не будут вырезаны*/
                if ($allowable_tags) $_allowable_tags = array_flip($allowable_tags);

                /*парные таги, которые будут восприниматься как параграфы*/
                if ($para_tags) $_para_tags = array_flip($para_tags);
            }
        }//if

        //tags processing
        if ($is_html)
        {
            $_callback_type = 'strip_tags';
                //$s2 = preg_replace_callback($re_tags, __FUNCTION__, $s2);
                $s2 = preg_replace_callback($re_tags,  array('ReadUrl','strip_tags'), $s2);
            $_callback_type = false;
            if (preg_last_error() !== PREG_NO_ERROR)
            {
                $i = 999;
                break;
            }
        }

        if ($s === $s2) break;
        $s = $s2; $i++;
    }//while
    if ($i >= $max) $s = strip_tags($s); //too many cycles for replace...

    if ($is_format_spaces && strlen($s) !== $length)
    {
            $s=str_replace(array('&nbsp;','&quot;'),array(' ','"'),$s);
        /*remove a duplicate spaces*/
        $s = preg_replace('/\x20\x20++/sSX', ' ', trim($s));
        /*remove a spaces before and after new lines*/
        $s = str_replace(["\r\n\x20", "\x20\r\n"], "\r\n", $s);
        /*replace 3 and more new lines to 2 new lines*/
        $s = preg_replace('/[\r\n]{3,}+/sSX', "\r\n\r\n", $s);
    }
    return $s;
}

    /**
     * @param $headers
     * @param $body
     * @param $info
     * @return string
     *     CURLINFO_EFFECTIVE_URL - Последний использованный URL
    CURLINFO_HTTP_CODE - Последний полученный HTTP код
    CURLINFO_FILETIME - Удаленная (серверная) дата загруженного документа, если она неизвестна, возвращается -1.
    CURLINFO_TOTAL_TIME - Полное время выполнения последней операции в секундах.
    CURLINFO_NAMELOOKUP_TIME - Время разрешения имени сервера в секундах.
    CURLINFO_CONNECT_TIME - Время, затраченное на установку соединения, в секундах

    CURLINFO_PRETRANSFER_TIME - Время, прошедшее от начала операции до готовности к фактической передаче данных, в секундах
    CURLINFO_STARTTRANSFER_TIME - Время, прошедшее от начала операции до момента передачи первого байта данных, в секундах
    CURLINFO_REDIRECT_COUNT - Число перенаправлений
    CURLINFO_REDIRECT_TIME - Общее время, затраченное на перенаправления, в секундах
    CURLINFO_SIZE_UPLOAD - Общее количество байт при закачке
    CURLINFO_SIZE_DOWNLOAD - Общее количество байт при загрузке
    CURLINFO_SPEED_DOWNLOAD - Средняя скорость загрузки
    CURLINFO_SPEED_UPLOAD - Средняя скорость закачки
    CURLINFO_HEADER_SIZE - Суммарный размер всех полученных заголовков
    CURLINFO_HEADER_OUT - Посылаемая строка запроса. Для работы этого параметра, добавьте опцию CURLINFO_HEADER_OUT к дескриптору с помощью вызова curl_setopt()
    CURLINFO_REQUEST_SIZE - Суммарный размер всех отправленных запросов, в настоящее время используется только для HTTP запросов
    CURLINFO_SSL_VERIFYRESULT - Результат проверки SSL сертификата, запрошенной с помощью установки параметра CURLOPT_SSL_VERIFYPEER
    CURLINFO_CONTENT_LENGTH_DOWNLOAD - размер скачанных данных, прочитанный из заголовка Content-Length:
    CURLINFO_CONTENT_LENGTH_UPLOAD - Размер закачиваемых данных
    CURLINFO_CONTENT_TYPE - Содержимое полученного заголовка Content-Type:, или NULL, если сервер не послал правильный заголовок Content-Type:

     */
    static function Info2Html($headers,$body,$info){
        return "<h5>Скорость загрузки</h5>".
        (empty($info['namelookup_time'])?'':"\n<div class='box'>Время разрешения имени сервера в секундах(DNS): <b>".$info['namelookup_time']."</b>").
        (empty($info['connect_time'])?'':"\nВремя, затраченное на установку соединения с сервером, в секундах: <b>".$info['connect_time']."</b>").
        (empty($info['pretransfer_time'])?'':"\nВремя, <abbr title=\"Время, прошедшее от начала операции до готовности к фактической передаче данных\">реакции сервера</abbr>, в секундах: <b>".$info['pretransfer_time']."</b>").
        (empty($info['starttransfer_time'])?'':"\nВремя, прошедшее от начала операции до момента передачи первого байта данных, в секундах: <b>".$info['starttransfer_time']."</b>").
            (!empty($info['redirect_count'])||!empty($info['redirect_time']) ?
                "\nОбщее время, затраченное на ".(empty($info['redirect_count'])?'':"<b>".$info['redirect_count']."</b>")." перенаправления, в секундах: <b>".(empty($info['redirect_time'])?'0':$info['redirect_time'])."</b>" : "").
        (empty($info['total_time'])?'':"\nПолное время загрузки страницы в секундах: <b>".$info['total_time']."</b>").
        (empty($info['download_content_length'])?'':"\nРазмер страницы в заголовке, байт: <b>".($info['download_content_length']==-1?"не задан":number_format($info['download_content_length'], 0, '.', ' '))."</b>").
        (empty($info['size_download'])?'':"\nРазмер страницы фактически получено, байт: <b>".number_format($info['size_download'], 0, '.', ' ')."</b>").
        (empty($info['speed_download'])?'':"\nСредняя скорость загрузки страницы, байт/сек: <b>".number_format($info['speed_download'], 0, '.', ' ')."</b>").
        "\nHTTP-код ответа сервера: <b>".(empty($info['http_code'])?'0':$info['http_code'])."</b></div>".
        "\n<h5>Ответ сервера(заголовок страницы)</h5>".
        "\n<div class='box'>".htmlspecialchars($headers,null,'windows-1251')."</div>";

    }
    /** преобразует многомерный массив в одномерный, используйя сложные индексы и заменяет @ в префиксе на CurlFile для испрользоания в Curl
     * @param $inputArray
     * @param string $inputKey
     * @return array
    $requestVars = array(
    'id' => array(1, 2,'id'=>1234),
    'name' => 'log',
    'logfile' => '@/tmp/test.log');
    получим:
    ["id[0]"]=> int(1)
    ["id[1]"]=> int(2)
    ["id[id]"]=> int(1234)
    ["name"]=> string(3) "log"
    ["logfile"]=> string(13) "/tmp/test.log" }
     */
    static function convertToStringArray($inputArray, $inputKey='') {
        $resultArray=[];
        foreach ($inputArray as $key => $value) {
            $tmpKey = (bool)$inputKey ? $inputKey."[$key]" : $key;
            if (is_array($value)) {
                $resultArray+=self::convertToStringArray($value, $tmpKey);
            } elseif (substr($value,0,1) == '@'){
                $resultArray[$tmpKey] = (class_exists(' CURLFile ', false)) ? new CurlFile(ltrim($value, '@')) : $value;
            } else {
                $resultArray[$tmpKey] = $value;
            }
        }
        return $resultArray;
    }

}
