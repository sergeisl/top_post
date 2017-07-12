<?
class Domain{
	public $schema='http://';
	public $domain=''; // исходное имя без http://
	public $base=''; // чистое имя домена без http:// в puny (.xn--p1ai)
	public $url='';  // хвост(?x=...)
	public $zone=''; // зона: ru, net, xn--p1ai
	public $error='';
	public $out; // отправленный последний запрос
	public $in;  // полученный последний ответ
	public $upd; // дата актуальности информации
    public $server='';
    public $bodyRoot=''; // html содержание главной станицы!!!
    public $body=''; // html содержание страницы $this->url
    public $headers=''; // заголовки url станицы
    public $headersRoot=''; // заголовки главной станицы
    public $curl_info=[]; // info чтения url станицы
    public $curl_infoRoot=[]; // info чтения главной станицы
	private $row=[]; // запись в базе domain для текущего пользователя
    private $ip='';
	private $seo=[];
	private $whois='';
    private $favicon='';
    private $robots='';
    static $not_found_string= ["NO MATCH TIP","No entries found","does not exist","nothing found", "No Data Found","Domain Not Found","is available for purchase","is available for registration",
"No such domain","does not exist","Status:	AVAILABLE","Status: AVAIL","We do not have an entry","no existe","Nombre del Dominio",
"is not registered","No entries found","Nombre del Dominio","is not registered", "not registred","Status: Not Registered","NO OBJECT FOUND",
"MAXCHARS:500","MAXCHARS:75","No data was found","We do not have an entry","No domain registered","Nombre del Dominio","Nombre del Dominio",
"Not Listed","Not found in database",'Not found:',"No Match for","No match","no entries",'NOT FOUND',"(null)","No records matching",
"solo acepta consultas con dominios","Domain unknown","Invalid query or domain name not known","Object_Not_Found","No Objects Found",
"No information available about domain name","Domain is not managed by this register"," is free","Status: available","Status: free",
"no matching record","Domain status: available","is not a registered","The domain has not been registered","Status: AVAILABLE","Status: AVAILABLE","No Found",
        "This domain name has not been registered"];

public function __construct($domain = null){
    if(!empty($domain)){
        $this->get($domain);
    }
}

public function get($dom){
    $this->bodyRoot=''; // html содержание главной станицы
    $this->body=''; // html содержание url станицы
    $this->row=[]; // запись в базе domain для текущего пользователя
    $this->ip='';
    $this->seo=[];
    $this->whois='';
    $this->favicon='';
    $this->url='';  // хвост(?x=...)
    $this->zone=''; // зона: ru, net, xn--p1ai
    $this->error='';
    $this->out; // отправленный последний запрос
    $this->in;  // полученный последний ответ
    $this->upd; // дата актуальности информации
    $this->server='';

    //str_replace('%A0','')
    $ar=Domain::BaseDomain($dom,$tmp=true);
    $this->schema=$ar['schema'];
    $this->url=$ar['url'];
    $this->base=$ar['base'];
    $this->domain=$ar['domain'];
    $this->zone=$ar['zone'];
    $this->error=$ar['error'];
}

public function ip(){
	if($this->ip==''){
		$this->ip=gethostbyname($this->base);
        if(empty($this->ip)){$this->ip=gethostbynamel($this->base); if($this->ip)$this->ip=$this->ip[0];}
		if($this->ip==$this->base || empty($this->ip))$this->ip=' '; // IP адрес домену не назначен!
	}
	return $this->ip;
}

/*
 * Creation Date: 27-jul-1998
 *      elseif(!empty($whois)){
		if(preg_match('/^registrar:(.*?)\n/mi', $whois, $arr))$row['reg']=trim($arr[1]);
		if(preg_match('/^paid[\-\_]till:(.*?)\n/mi', $whois, $arr))$row['paid_till']=trim($arr[1]);
		if(preg_match('/^Expiration Date:(.*?)\n/mi', $whois, $arr))$row['paid_till']=trim($arr[1]);
        if(preg_match('/^Creation Date:(.*?)\n/mi', $whois, $arr))$row['Creation']=trim($arr[1]);

 */
public function whois($reload=false){	// Whois
    if($this->whois) return $this->whois;
    $this->upd=time();
    if(strpos($this->base,'.')===false){
        $this->error="Имя домена должно содержать точку!";
        return "";
    }
    if(strpos($this->base,'\\')!==false || strpos($this->base,'"')!==false || strpos($this->base,"'")!==false){
        $this->error="Имя домена содержит недопустимый символ!";
        return "";
    }
	if(!isset($_GET['reload'])&&!$reload){
	   if(($data=DB::Select('whois','domain="'.$this->base.'"'))){
           if($data['upd']>date("Y-m-d H:i:s",strtotime("-10 day"))){
                $this->whois=$data['whois'];
                $this->seo=json_decode($data['seo'],true);
                $this->upd=strtotime($data['upd']);
                return $this->whois;
	       }
       }
	}
    set_time_limit(1000);   // Устанавливаем не ограниченное время выполнения скрипта
    $this->whois='';
    if(strlen($this->zone)<2||strlen($this->zone)>12){
        $this->error="Неверное имя зоны домена: *.".$this->zone;
        return $this->whois;
    }
    $this->server=self::server($this->zone);
    // Проверяем определён ли whois-сервер который несёт ответственность за данный доменный уровень
    if(empty($this->server)){
        $this->error="К сожалению не найден соответствующий Whois-сервер для зоны *.".$this->zone.
          (strpos($this->zone,'.')!==false?"":", возможно это поддомен на сайте <a href='/analiz/whois_domain.php?ip=http%3A%2F%2F".$this->zone."'>".$this->zone."</a>");
	    return $this->whois;
    }
  $out='';
  reset($this->server);
  for($j=0;$j<count($this->server);$j++){
      list($server, $str) = each($this->server);
      $out.="\nWhois Server: ".$server;
      //$out.=var_export($str,!0);
      if(empty($server) || empty($str['not_found_string'])){$out.=" - пропускаю!".(User::is_admin()?var_export($server,!0):''); continue;}
      $not_found_string=$str['not_found_string'];
      //print_r($server);
      $str=Domain::ReadSock($server, $str['prefix'] . $this->base);
      if( empty($str) ){
          $this->error="К сожалению в настоящее время Whois-сервер <b>".$server."</b> недоступен.";
          $out.=" - недоступен!";
          continue;
      }
      //Whois Server: whois.domaincontext.com за домен отвечает другой сервер
      if(preg_match('/^Whois Server:(.*?)\n/m', $str, $p) && !empty($p[1]) && !array_key_exists(trim($p[1]),$this->server)) { // добавляю новый Whois сервер
          $server=trim($p[1]);
          $str=self::WhoisServer($server, $this->zone);
          if($str){
              if(array_key_exists("WhoisServer",$str)){
                  if(!array_key_exists($str["WhoisServer"],$this->server))$this->server[$str["WhoisServer"]]=self::WhoisServer($server, $this->zone);
                  unset($str['WhoisServer']);
              }
              $this->server[$server]=$str;
          }
          $out.="\nWhois Server: ".$server.($str ? ", NotFound: ".$str['not_found_string'] : " - недоступен!");
          //$out.="\n".js_encode($this->server);
          SendAdminMail('Add new Zone-server',"Добавляю новый сервер доменной зоны<br>\n".$out."<br>\n".var_export($p,!0).
              "<br>\nSQL:\n".'INSERT INTO '.db_prefix.'whois ( domain, upd, seo ) VALUES ("'.addslashes(strtolower($this->zone)).'", "'.date("Y-m-d H:i:s").'", "'.addslashes(js_encode($this->server)).'")
                    ON DUPLICATE KEY UPDATE upd="'.date("Y-m-d H:i:s").'", seo="'.addslashes(js_encode($this->server)).'"');
/*          DB::sql('INSERT INTO '.db_prefix.'whois ( domain, upd, seo ) VALUES ("'.addslashes(strtolower($this->zone)).'", "'.date("Y-m-d H:i:s").'", "'.addslashes(js_encode($this->server)).'")
                    ON DUPLICATE KEY UPDATE upd="'.date("Y-m-d H:i:s").'", seo="'.addslashes(js_encode($this->server)).'"');*/
          continue;
      }
      if(strpos( $str, 'To single out one record, look it up with')!==false){
          $this->server[$server]['prefix']='domain ';
          $str=Domain::ReadSock($server, $this->server[$server]['prefix'] . $this->base);
      }
      // если в ответе имеется фраза-отказ, домен не зарегистрирован,
      // если такой фразы нет - следовательно домен зарегистрирован
      $this->error="";
      if(stripos($str, $not_found_string)===false){
          $this->whois="\nWhois Server: ".$server."\n".$str;
          DB::sql('INSERT INTO '.db_prefix.'whois ( domain, whois, upd ) VALUES ("'.addslashes($this->base).'", "'.addslashes($this->whois).'", "'.date("Y-m-d H:i:s").'")
            ON DUPLICATE KEY UPDATE whois="'.addslashes($this->whois).'", upd="'.date("Y-m-d H:i:s").'"');
          $this->upd=time();
      }else{
          $out.=" - ".$not_found_string;
          $this->whois=""; // домен не зарегистрирован!
          //return $this->whois;
          continue; // возможно инфа есть на другом сервере
      }
      break;
  }
    $this->out=$out;
    if(User::is_admin())
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/whois.log', "\n\n".date("d-m-Y H:i:s").' Out: '.$out."\n".' Whois: '.$this->whois."\n", FILE_APPEND);
    return $this->whois;
}
    static function WhoisServer($server, $zone){
        $ar= ['not_found_string'>='', 'prefix'=>''];
        if($server=='whois.arin.org')$ar['prefix']='n + ';
        elseif($server=='whois.denic.de')$ar['prefix']='-C UTF-8 -T dn,ace ';
        elseif($server=='whois.dk-hostmaster.dk')$ar['prefix']='--show-handles ';
        elseif($server=='whois.nic.name')$ar['prefix']='domain = ';
        elseif($server=='whois.ripe.net')$ar['prefix']='-B '; // показывать доп. инфу

        $domain='dj214ux1ox234.'.strtolower($zone);
        $str=Domain::ReadSock($server, $ar['prefix'] . $domain );
        if($str){
            if(preg_match('/^Whois Server:(.*?)\n/m', $str, $p) && !empty($p[1])) $ar['WhoisServer']=trim($p[1]);
            if(strpos( $str, 'To single out one record, look it up with')!==false){
                $ar['prefix']='domain ';
                $str=Domain::ReadSock($server, $ar['prefix'].$domain ); if(!$str)return '';
            }
            for($i=0;$i<count(self::$not_found_string);$i++)if(stripos($str,self::$not_found_string[$i])!==false){ $ar['not_found_string']=self::$not_found_string[$i]; break;}
            if(empty($ar['not_found_string'])) SendAdminMail('NotFound for '.$zone,"Не удалось выделить строку отсутствия домена из\n".nl2br($str)."\nСервер ".$server);
            return $ar;
        }
        return '';
    }

    /** получаю Whois сервер для зоны
     * @param $zone
     * @return array|mixed [server]={not_found_string=>строка, prefix=>строка}
     * todo доделать на основе http://habrahabr.ru/post/165869/
     *                     сверять с http://whois.domaintools.com/
     */
    static function server($zone){
        $zone=trim(strtolower($zone));
        if(strlen($zone)<2 || strlen($zone)>12 || preg_match('/^[0-9]+$/',$zone,$ar) || strpos($zone,' ')!==false){
            if(strlen($zone)>12)SendAdminMail('Error zone '.$zone,"Ошибка зоны домена!\nurl=".@$_SERVER['REQUEST_URI']."\nСтек: " . var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0));
            return '';
        }
        $servers=[];
        // Получаем имя whois-сервера, который отвечает за домен $zone
        if(($data=DB::Select('whois','domain="'.$zone.'"'))){
            $servers=json_decode($data['seo'],true);
            foreach($servers as $server => $val)if(!is_array($val)){
                $servers[$server]= ['not_found_string'=>$val,'prefix'=>''];
            }
            if( !isset($_GET['reload_zone']) && count($servers) )return $servers;
        }
        if( preg_match('/^whois:(.*?)\n/m', self::ReadSock('whois.iana.org', $zone ), $p) && !empty($p[1]) )
            if(!array_key_exists(trim($p[1]),$servers))$servers[trim($p[1])]='';
        if(!array_key_exists("whois.nic.".$zone,$servers))$servers["whois.nic.".$zone]='';
        if(!array_key_exists("whois.".$zone,$servers))$servers["whois.".$zone]='';
        if(!array_key_exists($zone.".whois-servers.net",$servers))$servers[$zone.".whois-servers.net"]='';
        if($zone=='ru'){
            if(!array_key_exists("whois.naunet.ru",$servers))$servers["whois.naunet.ru"]='';
            if(!array_key_exists("whois.reg.ru",$servers))$servers["whois.reg.ru"]='';
            if(!array_key_exists("whois.r01.ru",$servers))$servers["whois.r01.ru"]='';
            if(!array_key_exists("whois.ripn.ru",$servers))$servers["whois.ripn.ru"]='';
        }
/* whois сервера для доменов второго уровня:

    whois.nic.<домен верхнего уровня>
    whois.<домен верхнего уровня>



        whois.za.net
        whois.eu.org
        whois.nic.priv.at
        whois.ae.org
        whois.edu.ru
        whois.com.ru
    whois.edu.ru
    whois.tcinet.ru
    whois.nic.edu.ru
    whois.nic.ru
    whois.ru
    whois.cctld.ru
    ru.whois-servers.net


https://raw.githubusercontent.com/whois-server-list/whois-server-list/master/whois-server-list.xml - список Whois серверов, формат
<domain name="com.ru">
    <source>XML</source>
    <whoisServer host="whois.ripn.net">
        <source>PHOIS</source>
        <availablePattern>\QNo entries found\E</availablePattern>
    </whoisServer>
    <whoisServer host="whois.nic.ru">
        <source>PHP_WHOIS</source>
    </whoisServer>
</domain>

*/
        reset($servers);
        while(list($server, $val) = each($servers)){// foreach($servers as $server=>$not_found_string){
            $str=self::WhoisServer($server, $zone);
            if(!$str){unset($servers[$server]);continue;}
            if(array_key_exists("WhoisServer",$str)){
                if(!array_key_exists($str["WhoisServer"],$servers))$servers[$str["WhoisServer"]]=self::WhoisServer($server, $zone);
                unset($str['WhoisServer']);
            }
            $servers[$server]=$str;
            if(User::is_admin())
                @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/whois.log', "\n\n".date("d-m-Y H:i:s").' Out: '.$str."\n".var_export($servers[$server],!0)."\n", FILE_APPEND);
        }
        if($servers){
            SendAdminMail('Add2 new Zone-server',"Добавляю2 новый сервер доменной зоны<br>\n".$zone."<br>\n".
                "<br>\nSQL:\n".'INSERT INTO '.db_prefix.'whois ( domain, upd, seo ) VALUES ("'.addslashes($zone).'", "'.date("Y-m-d H:i:s").'", "'.addslashes(js_encode($servers)).'")
                ON DUPLICATE KEY UPDATE upd="'.date("Y-m-d H:i:s").'", seo="'.addslashes(js_encode($servers)).'"'.nl2br(var_export($_REQUEST,!0))."\nuser=".var_export(@$_SESSION['user'],!0)."\nserver=".var_export($_SERVER,!0));
            DB::sql('INSERT INTO '.db_prefix.'whois ( domain, upd, seo ) VALUES ("'.addslashes($zone).'", "'.date("Y-m-d H:i:s").'", "'.addslashes(js_encode($servers)).'")
                ON DUPLICATE KEY UPDATE upd="'.date("Y-m-d H:i:s").'", seo="'.addslashes(js_encode($servers)).'"');
            foreach($servers as $server => $val)if(!is_array($val)){
                $servers[$server]= ['not_found_string'=>$val,'prefix'=>''];
            }
        }
        return $servers;
    }


    public function ShortWhois(){	// удалить коментарии из Whois
	$str=preg_replace("/^%(.*?)\n/m","",$this->whois());
	$str=preg_replace("/\n\n+/","\n",$str);
	return $str;
}

// расчет PR
private function StrToNum($Str, $Check, $Magic)
   {
   $Int32Unit = 4294967296;

   $length = strlen($Str);
   for ($i = 0; $i < $length; $i++)
     {
     $Check *= $Magic;

     if ($Check >= $Int32Unit)
       {
       $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));

       $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
       }
     $Check += ord($Str{$i});
     }
   return $Check;
   }

private function HashURL($String)
   {
   $Check1 = $this->StrToNum($String, 0x1505, 0x21);
   $Check2 = $this->StrToNum($String, 0, 0x1003F);

   $Check1 >>= 2;
   $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
   $Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
   $Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);

   $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
   $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );

   return ($T1 | $T2);
   }

private function CheckHash($Hashnum)
   {
   $CheckByte = 0;
   $Flag = 0;

   $HashStr = sprintf('%u', $Hashnum) ;
   $length = strlen($HashStr);

   for ($i = $length - 1;  $i >= 0;  $i --)
     {
     $Re = $HashStr{$i};
     if (1 === ($Flag % 2))
       {
       $Re += $Re;
       $Re = (int)($Re / 10) + ($Re % 10);
       }
     $CheckByte += $Re;
     $Flag ++;
     }

   $CheckByte %= 10;
   if (0 !== $CheckByte)
     {
     $CheckByte = 10 - $CheckByte;
     if (1 === ($Flag % 2) )
       {
       if (1 === ($CheckByte % 2))
         {
         $CheckByte += 9;
         }
       $CheckByte >>= 1;
       }
     }
   return '7'.$CheckByte.$HashStr;
   }

private function getPageRank(){
    $this->seo['pr']=0;
   if(isset($this->seo['pr']))return;

   /*$this->in=$this->get_url($this->out="http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=".$this->CheckHash($this->HashURL($this->base)).
                                        "&features=Rank&q=info:".$this->base."&num=100&filter=0");
    $pos=strpos($this->in,"Rank_");
    $pagerank = ($pos === false ? '' : substr($this->in, $pos + 9) );

   $pagerank = (strlen($pagerank) > 0) ? $pagerank : -1;
   $this->seo['pr']=intval($pagerank);*/
}

private function getTcy(){ // с http://
   if(isset($this->seo['tic']))return;
   $this->in=$this->get_url($this->out="http://bar-navig.yandex.ru/u?ver=2&url=http://".$this->base."&show=1&post=1");
   if($this->in&& preg_match('/<tcy rang="(.*)" value="(.*)"/isU', $this->in, $res )){
        //echo "<br>".nl2br(htmlspecialchars($this->in))."<br>".print_r($res);
        $this->seo['tic']=intval($res[2]);
        //if(preg_match("/<yaca /isU", $this->in, $res ))$this->seo['yaca']=1;
   }else $this->seo['tic']='нет';
   //$this->SaveSeo();
}

    /** запустить чтение всех внешних сайтов с инфой
     * @param bool $p
     * @param bool $Reload
     */
    public function Preload()
    {   ReadUrl::ReadMultiUrl(
            ["http://xml.linkpad.ru/?url=" . $this->base,
                "http://bar-navig.yandex.ru/u?ver=2&url=http://" . $this->base . "&show=1&post=1",
                /*"http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=" . $this->CheckHash($this->HashURL($this->base)) . "&features=Rank&q=info:" . $this->base . "&num=100&filter=0",*/
                $this->schema.$this->base.$this->url,
                $this->schema.$this->base.'/robots.txt',
                $this->favicon=$this->schema.$this->base.'/favicon.ico'],
            ['cache' => 43200, 'timeout' => 20, 'convert' => 'windows-1251']);
    }

    /*
    <host>htmlweb.ru</host>
    <index date="22.01.2013">1920</index>
    <mr>0</mr><ip>14</ip>
    <hin l1="66" l2="1148" l3="709" l4="2554">4477</hin>
    <din l1="59" l2="380" l3="108" l4="600">1055</din>
    <hout l1="6" l2="413" l3="751" l4="466">1636</hout>
    <dout>1277</dout><anchors>540</anchors>
    <anchors_out>1369</anchors_out>
    <igood>2366834/5682492</igood>
    <referring_ips>316</referring_ips>
    <referring_subnets>236</referring_subnets>
    */
    /** получает SEO данные о сайте
     * @param bool|string $p = ip|tic|pr|mr|din|hin|hout|dout|anchors_out|mr|yaca
     * @param bool $Reload
     * @return array|mixed|string
     */
public function Seo($p=false, $Reload=false){ // с http://
   if(!$Reload){
       if($p && isset($this->seo[$p]))return $this->format_num($this->seo[$p]);
       elseif(!$p && isset($this->seo['ip']))return $this->seo;
   }
   $this->in=$this->get_url($this->out="http://xml.linkpad.ru/?url=".$this->base);

   if(preg_match_all('|<.*>(.*)</(.*)>|Ums', $this->in, $res, PREG_SET_ORDER)){
    	foreach($res as $var)$this->seo[$var[2]]=trim(strip_tags($var[1]));
   }
   $this->getTcy();
   $this->getPageRank();
   $this->SaveSeo();
    //if($p)echo "<br>\n".$p."=".var_export(@$this->seo[$p],!0);
   return ($p && isset($this->seo[$p]) ? $this->format_num($this->seo[$p]) : ($p?'':$this->seo) );
}

private function SaveSeo(){
	DB::sql('INSERT INTO '.db_prefix.'whois ( domain, whois, seo, upd ) VALUES ("'.addslashes($this->base).'", "'.addslashes($this->whois()).'", "'.addslashes(js_encode($this->seo)).'", "'.date("Y-m-d H:i:s").'")
		 ON DUPLICATE KEY UPDATE whois="'.addslashes($this->whois()).'", seo="'.addslashes(js_encode($this->seo)).'", upd="'.date("Y-m-d H:i:s").'"');
	$this->upd=time();
}

function format($str){
	$trans["<"]="&lt;";
	$trans[">"]="&gt;";
	$trans["&"]="&amp;";
	$trans['"']="&quot;";
        $search = [
		'/\s((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))/is',
		'/^((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))/is',
		'/\s((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))\s/i',
		'/^((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))\s/i',
		'/\s((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))$/i',
		'/^((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))$/i',
		'/\s(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))/i',
		'/^(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))/i',
		'/\s(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))\s/i',
		'/^(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))\s/i',
		'/\s(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))$/i',
		'/^(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))$/i',
		'/(\S+)@([a-z0-9.]+)/is'];

        $replace = [
		' <a href="$1">$1</a>',
		'<a href="$1">$1</a>',
		' <a href="$1">$1</a> ',
		'<a href="$1">$1</a> ',
		' <a href="$1">$1</a>',
		'<a href="$1">$1</a>',
		' <a href="http://$1">$1</a>',
		'<a href="http://$1">$1</a>',
		' <a href="http://$1">$1</a> ',
		'<a href="http://$1">$1</a> ',
		' <a href="http://$1">$1</a>',
		'<a href="http://$1">$1</a>',
		'<a href="mailto:$0">$0</a>'];
	$str=str_replace("\n\n","\n",$str);
	$str=strtr($str, $trans);
	$str=preg_replace($search, $replace, $str);
	if(substr($str,-1,1)=='\\')$str.=' '; // защита от инъекций
	return nl2br(@iconv('koi8-u',charset.'//IGNORE',$str));
}



public function format_num($str){
	if(is_numeric($str))return number_format($str, 0, '.', ' ');
    elseif(is_array($str)) return var_export($str,!0);
	else return $str;
}

//Получить все ip-адреса домена
/*$host=htmlspecialchars(@$_GET['str']);
if (!empty($host)) {
	echo "<h3>".$host."</h3>\n";
	$ips=gethostbynamel($host);
	{if ($ips) {$i=1;
		foreach($ips as $ip) echo ($i++).". <b>".$ip."</b><br />\n";
		}
	 else	echo $ip."Не удалось определить.<br />\n";
	 }
}
*/

public function favicon(){
	$this->favicon=$this->schema.$this->base.'/favicon.ico';
	$this->in=$this->get_url($this->favicon);
	$i=strlen($this->in);
	if($i<6||$i>4000){
		$this->favicon='';
		$this->in=$this->get_url("http://www.google.com/s2/favicons?domain=".$this->base);
		$i=strlen($this->in);
		if($i<6||$i>4000) return '';
	}
	return $this->favicon;
}


function favicon_url($rel='icon'){ // apple-touch-icon-precomposed
    if(empty($this->favicon)||$rel!=='icon'){
        $this->in=$this->get_url();
        if($rel=='icon')$rel='icon|shortcut icon';
	    if (preg_match('/<link(([^>]*)rel=[\'\"]('.$rel.')[\'\"]([^>]*))>/seiU', $this->in, $out)) {
            $link_tag = $out[1];
            if(preg_match('/href=[\'\"]([^\'\"\»]*)[\'\"\»]/seiU', $link_tag, $out)){
                $favicon = trim($out[1]);
                if(strpos($favicon, '://')===false) $favicon = $this->schema.$this->base. '/' . ltrim($favicon, '/');
                if($rel!='icon')return $favicon;
                $this->favicon=$favicon;
            }elseif(isset($_REQUEST['debug']))echo "<br><span class='red'>href в link не найдено!</span>";
        }elseif(isset($_REQUEST['debug']))echo "<br><span class='red'>Иконка на странице в link не найдена!</span>";
    }
    return $this->favicon;
}

function title(){
    $this->get_url();
    //echo "<br>\nLEN: ".strlen($this->body);
//    if(preg_match('|<title.*? >(.*?)</title>|seiu', $this->body, $arr)){
    if(preg_match('|<title.*?>(.*?)</title>|sei', $this->body, $arr)){
    //if(preg_match('~<title[^<>]>([^<]*)<\/title>~siU',$this->body, $arr)){
        //print_r($arr);
        return trim($arr[1]);
    }
    else return '';
}

function meta($name='keywords'){ // description
    $this->get_url();
    if(preg_match('/<meta\s*(?:name|http\-equiv)=[\'\"]?'.$name.'[\'\"]?\s*content=[\'\"]([^>]*?)[\"\']/sei',$this->body,$arr)){
        return trim($arr[1]);
    }elseif(preg_match('/<meta\s*content=[\'\"]([^>]*?)[\"\']\s*(?:name|http\-equiv)=[\'\"]?'.$name.'[\'\"]?/sei',$this->body,$arr)){
        return trim($arr[1]);
    }
    else return '';
}
    /** кол-во тегов на странице $dom->tagCount('script','src=')
     * @param string $tag
     * @param string $add - дополнение, например 'src'
     * @return int
     */
    function tagCount($tag='a',$add=''){
        $this->get_url();
        if($add){
            if(preg_match_all('|<'.$tag.'\s*[^>]*'.$add.'.*?>|sei', $this->body, $arr))
                return count($arr[0])/*.'~'.var_export($arr,!0)*/;
        }else{
            if(preg_match_all('|<'.$tag.'\s*.*?>|sei', $this->body, $arr))
                return count($arr[0])/*.'~'.var_export($arr,!0)*/;
        }
        return 0;
    }

    /** возвращает массив тегов на странице $dom->tagCount('script','src=')
     * @param string $tag
     * @param string $add - дополнение, например 'src'
     * @return array
     */
    function tag($tag='a',$add=''){
        $this->get_url();
        if($add){
            if(preg_match_all('|<'.$tag.'\s*[^>]*'.$add.'.*?>|sei', $this->body, $arr))
                return count($arr[0])/*.'~'.var_export($arr,!0)*/;
        }else{
            if(preg_match_all('|<'.$tag.'\s*.*?>|sei', $this->body, $arr))
                return count($arr[0])/*.'~'.var_export($arr,!0)*/;
        }
        return 0;
    }

    /** получение данных, кеширование, мин, по умолчанию = 30 суток
     * @param string $url
     * @param bool $post
     * @param int $cache
     * @return string
     */
function get_url($url='',$post=false, $cache=43200){
    if(empty($url)){
        $url=$this->schema.$this->base.$this->url;
    }
    if(isset($_GET['reload'])){
        $cache=0;
    }elseif($url==$this->schema.$this->base && ($this->bodyRoot || $this->headersRoot) ){// из кеша
        //echo(':1:'.strlen($this->bodyRoot));
        return $this->bodyRoot;
    }elseif($url==$this->schema.$this->base.$this->url && ($this->body || $this->headers) ){// из кеша
        //echo(':2:'.strlen($this->body));
        return $this->body;
    }
	list($headers,$body,$info)=ReadUrl::ReadWithHeader($url,$post, ['cache'=>$cache,'timeout'=>20,'convert'=>charset]);
    //echo(':3:'.strlen($body));
    //var_dump($info); exit;
    //SendAdminMail($this->domain,var_export($info,!0));
    //if($body && isset($info['charset']) && $info['charset']!='windows-1251')$body=@iconv($info['charset'],'windows-1251//IGNORE',$body);
    //echo "<br>\nпреобразую ".$info['charset']." -&gt; windows-1251"; //utf-8";
    if($url==$this->schema.$this->base){// кеширую
        $this->bodyRoot=$body;
        $this->headersRoot=$headers;
        $this->curl_infoRoot=$info;
    }
    if($url==$this->schema.$this->base.$this->url){// кеширую и сюда тоже
        $this->body=$body;
        $this->headers=$headers;
        $this->curl_info=$info;
    }
    $this->in=$body;
	return $body;
}

    /** Возвращает значение конкретного поля из заголовка, аналог ReadUrl::getHeader
     * @param string $name - название поля заголовка
     * @param string $headers - строка заголовков для выделения нужной части
     * @return string
     */
    public function header($name){
        if(preg_match("|".preg_quote($name).":(.*)[\n\\;\\ ]|i", $this->headers, $results))return trim($results[1]);
        else return '';
    }

    /** проверка наличия сайта в черном списке
     * @return array
     */
    function is_blacklist(){
        return DB::Select("bl","url='".addslashes($this->domain)."'".($this->domain==$this->base? '' : " or url='".addslashes($this->base)."'") );
    }

    /** проверка наличия сайта в черном списке
     * @param $url
     * @return array
     */
    static function _is_blacklist($url){
        $dom=new Domain($url);
        return $dom->is_blacklist();
    }

        /* todo проверка на глобальный BL
         $host = '64.53.200.156';
        $rbl  = 'sbl-xbl.spamhaus.org';
        // valid query format is: 156.200.53.64.sbl-xbl.spamhaus.org
        $rev = array_reverse(explode('.', $host));
        $lookup = implode('.', $rev) . '.' . $rbl;
        if ($lookup != gethostbyname($lookup)) {
        echo "ip: $host is listed in $rbl\n";
        } else {
            echo "ip: $host NOT listed in $rbl\n";
        }*/

/*function is_sale(){
    return (!!($this->row=DB::Select("domain","zakaz=1 and confirm>'".date("Y-m-d",strtotime("-1 year"))."' and (domain='".addslashes($this->domain)."' or domain='".addslashes($this->base)."')")));
}*/

function row(){/* возвращает информацию, если домен есть в моих доменах */
    if($this->row)return $this->row;
    return $this->row=DB::Select("domain","user='".intval($_SESSION['user']['id'])."' and (domain='".addslashes($this->domain)."' or domain='".addslashes($this->base)."')");
}

function is_confirm(){
    if(!User::is_login()){$this->error="<span class='red'>Вы не зарегистрированны!</span>";return false;}
    if(!$this->row){
        $this->row=self::row();
        if(!$this->row){$this->error="<span class='red'>Домен не добавлен!</span>";
            AddToLog(print_r($_SESSION['user'],true)."\ndomain=".$this->domain.",  base=".$this->base);
            return false;}
    }
    if($this->row['confirm']>=date("Y-m-d",strtotime("-1 year"))){
        $this->error="<span class='green'>Права подтверждены</span>";
        return true;
    }else{
        $cache=60; // минут
        $this->in=$this->get_url('','',$cache);
        //SendAdminMail($this->domain,var_export($this,!0));
        if(!empty($this->curl_info['curl_error'])){
            $this->error="Ошибка чтения сайта:".$this->curl_info['curl_error'];
            return false;
        }elseif(preg_match("~<a\s[^<>]*href=[\'\"]https?://htmlweb.ru([^\'\"]+)[\'\"][^<>]*>(.*?)<\/a>~si",$this->in, $arr)){
            //ref=$id
            if(preg_match("~ref=(\d+)~si",$arr[1], $arr)){
                if($arr[1]!=User::id()){echo "<span class='red'>Ссылка на другого владельца: ref=".$arr[1].", ваша ссылка: ref=".User::id()."</span>";return false;}
            }
            //if(strpos('ref='.intval(User::id()),$arr[1])===false){echo "<span class='red'>Ссылка на другого("..") владельца!</span>";return false;}
            DB::sql("UPDATE ".db_prefix."domain SET ip='".addslashes(Convert::ip2long2($this->ip()))."', confirm='".date("Y-m-d")."' WHERE id='".$this->row['id']."' and user='".intval($_SESSION['user']['id'])."' LIMIT 1");
            $this->error="<span class='green'>Права подтверждены</span>";
            // если был подтвержден у других - сбросить у них подтверждение
            DB::sql("UPDATE ".db_prefix."domain SET confirm='' WHERE id<>'".$this->row['id']."' and (domain='".addslashes($this->domain)."' or domain='".addslashes($this->base)."') LIMIT 1");
            return true;
        }else{
            $this->error="Ошибка подтверждения прав! На странице не найдена ссылка. Подробнее в <a href=\"/domainsale/#FAQ\">FAQ</a>. Следующая проверка возможна ".date("d.m.Y H:i:s", filemtime($this->curl_info['cache_filename'])+($cache*60));
            return false;
        }
    }
}

    /** возвращает дату создания домена из Whois
     * @return int|null
     */
function creation(){
    $this->whois();
    if(preg_match('/^created:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    elseif(preg_match('/creation\sdate:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    else return null;
    return strtotime(str_replace(".","-",$creation));
}

    /** оплачен до
     * @return int|null
     */
function paid(){
    $this->whois();
    if(preg_match('/^paid\-till:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    elseif(preg_match('/Expiration\sdate:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    elseif(preg_match('/Registry\sExpiry\sDate:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    elseif(preg_match('/^reg\-till:(.*?)\n/mi', $this->whois, $arr))$creation=trim($arr[1]);
    else return null;
    return strtotime(str_replace(".","-",$creation));
}

    /** возвращает возраст(полных лет) домена из Whois
     * @return float|string
     */
function Age(){
    $creation=$this->creation(); if(!$creation)return "-";
    return floor((strtotime('now')-$creation)/(365*24*60*60));
}


static function ReadSock($url,$out){
    $rn=($url=='whois.nic.org.mt'?"\n":"\r\n");
    $fp=@fsockopen($url, 43, $errno, $errstr, 20);
    $str = "";
    if($fp!==false){
        fputs($fp, $out.$rn);
        while(!feof($fp)) $str .= fgets($fp,128)."\n";
        fclose($fp);
    }
    $str=trim($str);
    if(User::is_admin())
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/whois.log', "\n\n".date("d-m-Y H:i:s").' '.$url.' для '.$out.': '."\n errno=".$errno.", errstr=".$errstr."\n".$str."\n", FILE_APPEND);
    return $str;
}

static function GetWhoIs($domain, $nocomment=false)
{
	$dom = new Domain($domain);
	//$dom->get($domain);
	if($nocomment)return [$dom->ShortWhois(),$dom->error];
	return [$dom->whois(),$dom->error];
}

/**
 * Возвращает чистое имя домена второго уровня или массив параметров:
 * @param $domain
 * @param boolean|string $full - true|false|'schema'|'error'|'zone'|'base'|'www'|'url'|'domain'
 * @return string|array
 */
static function BaseDomain($domain, $full=false){
    $ar= ['schema'=>'http://', 'error'=>'', 'zone'=>'', 'base'=>'', 'www'=>false];
    $ar['url']=''; // путь после имени домена
    $ar['domain']=$domain;

    if(strrpos($domain, "СЂС„")!==false || strrpos($domain, "сђс„")!==false)$domain=@iconv("UTF-8", "windows-1251//IGNORE", $domain);
    $domain=trim($domain);

    //$str=preg_replace('/^https?:\/\/(www\.)?/i', '', $str);
    /*
    preg_match('@^(?:http://)?([^/]+)@i', $domain, $matches); // выделение имени домена
    $host = $matches[1];
    */

    while(mb_strtolower(substr($domain,0,7))=='http://'){$domain=trim(substr($domain,7));$ar['schema']='http://'; }
    while(mb_strtolower(substr($domain,0,8))=='https://'){$domain=trim(substr($domain,8)); $ar['schema']='https://'; }
    while(mb_strtolower(substr($domain,0,3))=='//')$domain=trim(substr($domain,3));

    // выделяю "хвост"
    if(preg_match('~^([^\/\?\#]+)([\/\?\#].*)~', $domain,$i)){
        $domain=$i[1]; $ar['url']=$i[2];
    }
    do{ // для переходов на конец
    $domain=trim(mb_strtolower($domain));
    if(preg_match('~([\'\"\<\>\:])~', $domain,$i)){
        $ar['error']='Неверный символ в имени домена!';
        break;
    }

    $domain=str_replace(',','.',$domain); // часто вместо . ставят ,

    while(preg_match('/^www\..*\..{2,8}$/i', $domain)){ // может быть домен www.ru или www.museum
        $ar['www']=true;
        $domain=substr($domain,4);
    }

    /*preg_match("~^[^://]+://(www\.)?[a-z0-9_-]{1,32}\.~i", $val)
    (!preg_match("~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}".
       "(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|".
       "org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{2})|(?!0)(?:(?".
       "!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&".
       "?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i",$url,$ok))*/

    if(preg_match('~([\'\"\<\>\_])~', $domain,$i)){
        $ar['error']='Неверный символ в имени домена!';
        break;
    }
    if(strlen($domain)<3){
        $ar['error']='Имя домена не бывает таким коротким!';
        break;
    }
    $ar['domain']=$ar['base']=$domain;
    $t=strrpos($domain, ".");
    if($t===false){
        $ar['error']='Имя домена должно содержать точку!';
        break;
    }

    // Извлекаем домен первого уровня
    $ar['zone'] = substr($domain, $t + 1);
    if($ar['zone']=="ру"){$ar['zone']='ru'; $ar['domain']=substr($domain, 0, $t).".ru";}
    elseif($ar['zone']=="нет"){$ar['zone']='net'; $ar['domain']=substr($domain, 0, $t).".net";}
    elseif(mb_strtolower($ar['zone'])=="рф")$ar['zone']='xn--p1ai';
    elseif(preg_match('/[^a-zA-Z\-\0-9]+/',$ar['zone'])) $ar['zone']=self::punicode_enc(Convert::win2utf($ar['zone']));
    $domainbase=substr($ar['domain'], 0, $t);
    if(substr($ar['zone'],0,4)=='xn--'|| preg_match('/[^a-zA-Z\-\0-9]+/',$domainbase)){ // бокалы.su
        $domainbase=self::punicode_enc(Convert::win2utf($domainbase));
        $ar['base']=$domainbase.".".$ar['zone'];
        if(substr($ar['domain'],0,4)=='xn--'){
            if(strlen($ar['domain'])<6){
                $ar['error']='Неверное имя кирилического домена!';
                break;
            }
            $ar['domain']=Convert::utf2win(self::punicode_dec($ar['domain'])); // делаю обратное перекодирование
        }
    }
    }while(0);
    if(empty($ar['domain']))$ar['schema']='';
    return ($full?(is_string($full)?(isset($ar[$full])&&empty($ar['error'])?$ar[$full]:''):$ar):(empty($ar['error'])?$domain:''));
}

static function punicode_enc($stringconv)
{
$IDN = new idna_convert();
return $IDN->encode($stringconv);
}

static function punicode_dec($stringconv)
{
$IDN = new idna_convert();
return $IDN->decode($stringconv);
}


    function robots(){
        list($headers,$body,$info)=ReadUrl::ReadWithHeader($this->schema.$this->base.'/robots.txt',false, ['cache'=>true,'timeout'=>10,'convert'=>charset,'debug'=>false]);
        $this->robots='';
        if(empty($info['http_code']) || $info['http_code']==404) return '<b class="red">нет</b>  [<a href="/analiz/robots.php">создать robots.txt</a>]</p>';
        elseif($info['http_code']<>200) return '<b class="acronym red" onclick="ShowHide(\'RobotsTxt\')">ошибка '.$info['http_code'].'</b> [<a href="/analiz/robots.php">создать robots.txt</a>]</p><pre class="hide" id="RobotsTxt">'.$headers.'</pre>';
        if(!cmp(strtolower($info['content_type']),"text/plain")) return '<b class="acronym red" onclick="ShowHide(\'RobotsTxt\')">Ошибочный тип '.$info['content_type'].'</b>  [<a href="/analiz/robots.php">создать robots.txt</a>]</p><pre class="hide" id="RobotsTxt">'.$headers.'</pre>';
        $this->robots=$body;
        return '<span class="acronym" onclick="ShowHide(\'RobotsTxt\')"><b class="green">есть</b>, содержит '.count(explode("\n",$body)).' строк</span> [<a href="/analiz/robots.php">создать robots.txt</a>]</p> <div class="hide" id="RobotsTxt">'.nl2br(toHtml($this->robots)).'</div>';
    }

    function sitemap(){
        if( preg_match("|sitemap:(.*)\n|imsU", $this->robots."\n", $fsitemap)!==false && !empty($fsitemap[1]) ){
            if(preg_match('@^https?\://([^/\?]+)/(.*)$@i', trim($fsitemap[1]), $tmp)===false || empty($tmp[1]) || empty($tmp[2])){
                return '<b>' . $fsitemap[0] . '</b> - <b class="red">ошибка в адресе</b> (' . $fsitemap[1] . ')';
            }
            if($tmp[1]!==$this->base)return '<b>' . $fsitemap[1] . '</b> - <b class="red">ссылается на другой домен</b> (' . $tmp[1].'~'.$this->base . ')';
            $name=$tmp[2];
            $fsitemap = trim($fsitemap[1]);
        }else{
            $name='/sitemap.xml';
            $fsitemap=$this->schema.$this->base.$name;
        }
        list($headers,$body,$info)=ReadUrl::ReadWithHeader($fsitemap,!0, ['cache'=>true,'timeout'=>10,'convert'=>charset,'debug'=>false,'nobody'=>1]);
        //var_dump($headers,$body,$info);
        $fsitemap='<a href="'.$fsitemap.'" onclick="return!window.open(this.href)">'.$name.'</a>';
        if(empty($info['http_code']) || $info['http_code']==404) return $fsitemap.' - <b class="acronym red" onclick="ShowHide(\'SiteMapXml\')">нет</b><pre class="hide" id="SiteMapXml">'.$headers.'</pre>';
        elseif($info['http_code']<>200) return $fsitemap.' - <span class="acronym red" onclick="ShowHide(\'SiteMapXml\')">ошибка '.$info['http_code'].(empty($info['curl_error'])?'':', '.toHtml($info['curl_error'])).'</span><pre class="hide" id="SiteMapXml">'.$headers.'</pre>';
        if(!cmp(strtolower($info['content_type']),"application/xml")&&!cmp(strtolower($info['content_type']),"text/xml")) return $fsitemap.' - <b class="acronym red" onclick="ShowHide(\'SiteMapXml\')">ошибочный тип '.$info['content_type'].'</b><pre class="hide" id="SiteMapXml">'.$headers.'</pre>';
        return '<b class="green">есть</b>: '.$fsitemap.'';
    }


}
