<?
/**
 * Class SiteMap - создает карту сайта
 */
class SiteMap{
    public $buf;
    //public $filename;
    public $root;
    public $time;
    public $pr='0.9'; // приоритет
    public $counter=0;
    public $filename='/sitemap.xml.gz'; // путь к карте от корня

    function __construct($filename='',$root=null){
        if(!empty($filename))$this->filename=$filename;
        $this->root=($root?$root:$_SERVER['HTTP_HOST']);
        $this->time=time();
        $this->buf='<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
   }

    function __destruct()
    {
        $this->buf .= "</urlset>";
        if($this->counter>49990)SendAdminMail('Sitemap is big', 'Карта сайта содержит '.$this->counter.' страниц!');
        file_put_contents($_SERVER['DOCUMENT_ROOT'].$this->filename, gzencode($this->buf."\n", 9));
        if(!Get::DEBUG())$this->Ping();
    }

    /**
     * пингую поисковики
     */
    function Ping()
    {
        $sitemap = "http://" . $_SERVER['HTTP_HOST'] . $this->filename;
        $bodys = ReadUrl::ReadMultiUrl([
            'http://www.google.com/ping?sitemap=' . $sitemap,
            'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=' . $sitemap,
            'http://submissions.ask.com/ping?sitemap=' . $sitemap,
            'http://webmaster.live.com/ping.aspx?siteMap=' . $sitemap],
            ['cache' => 0, 'convert' => charset, 'timeout' => 20]);
        foreach ($bodys as $row){ // $row['info'], $row['url'], $row['body'], $row['headers'];
            echo "\n<br>" . $row['url'] . ' ' . $row['info']['http_code'];
        }
    }

    function loc($url, $time=null, $priority=null, $changefreq=null){
       $this->buf.="<url>
<loc>".htmlspecialchars("http://".$this->root.(substr($url,0,1)=='/'?'':"/").$url,ENT_QUOTES)."</loc>
<lastmod>".date('Y-m-d',($time?$time:$this->time))."</lastmod>
<changefreq>".($changefreq?$changefreq:$this->getFreq($time))."</changefreq>
<priority>".str_replace(',','.',($priority?floatval($priority):$this->pr))."</priority>
</url>\n";
       $this->counter++;
}

   function getFreq($time){
      $t=time()-$time;
      if($t<5400)return 'hourly'; // 60*60*1.5
      elseif($t<129600)return 'daily';   // 60*60*24*1.5
      elseif($t<604800)return 'weekly';   // 60*60*24*7 - периодичность обновления страницы "еженедельно"
      elseif($t<2592000)return 'monthly';   // 60*60*24*7
      else return 'yearly';
   }

}
/*
<sitemapindex xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<sitemap>
<loc>http://www.regionkomplekt.com/sitemap_company.xml.gz</loc>
<lastmod>2016-02-29T12:15:36+00:00</lastmod>
</sitemap>
</sitemapindex>
 * */

