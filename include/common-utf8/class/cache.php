<?php
// работа с мемкешем
defined('MEMCACHE_PREFIX') or define("MEMCACHE_PREFIX", 'pb_');
defined('MEMCACHE_ENGINE') or define('MEMCACHE_ENGINE', true); // установите false для выключения кеширования

/**
 * Class Cache
 * @version 1.1
 */
class Cache
{
    private $memcache_obj = null; /** @var Memcached $memcache_obj */
    private $type=null;
    private $prefix=null;

    /**
     * @param $key - ключ
     * @param $data - значение
     * @param int $expire - или время жизни в секундах в пределах 30 дней или время до которого живет значение
     * @return bool - true, если успешно
     */
    static public function _Set($key, $data, $expire = 86400){
        global $_cache;
        if(!$_cache) $_cache = new Cache();
        return $_cache->set($key, $data, $expire);
    }

    /** возвращает значение переменной из кеша
     * @param $key
     * @return bool|mixed|string
     */
    static public function _Get($key){
        global $_cache;
        if(!$_cache) $_cache = new Cache();
        return $_cache->get($key);
    }

    /** Очищает кеш
     * @return bool|mixed|string
     */
    static public function _Clear(){
        global $_cache;
        if(!$_cache) $_cache = new Cache();
        return $_cache->clear();
    }


    /**
     * @param string $type = '' | 'memcached' | 'file' | 'fsock'
     * @param null|string $prefix
     */
    public function __construct($type='', $prefix=null)
    {
        $this->prefix=(is_null($prefix) ?  MEMCACHE_PREFIX : $prefix );
        if(!MEMCACHE_ENGINE){
            SendAdminMail("Ошибка Cache", " Выклюючен!",'',true);
            return false;

        }elseif($type=='memcached' || !$type && extension_loaded('memcached') /*!class_exists("Memcached")*/){
            $this->memcache_obj = new Memcached;
            $this->memcache_obj->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
            $this->memcache_obj->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            if($this->memcache_obj->addServer('localhost', 11211)===false || $this->memcache_obj->getResultCode()){
                SendAdminMail("Ошибка Cache", "Ошибка addServer код=".$this->memcache_obj->getResultCode(),'',true);
                return false;
            }
            //echo "<br>Memcached Server's version: ".var_export($this->memcache_obj->getVersion(),1)."<br/>\n";
            $this->memcache_obj->set('test',123,10);
            if($this->memcache_obj->get('test')!=123){
                SendAdminMail("Ошибка Cache", "Ошибка чтения код=".$this->memcache_obj->getResultCode(),'',true);
                return false;
            }
            $this->type='memcache';

        }elseif($type=='fsock'){
            $this->memcache_obj = fsockopen('localhost', 11211, $errno, $errst, 2);
            $this->type='fsock';

        }else{ //get_loaded_extensions()
            $this->type='file';
            defined('fb_cachedir') || define('fb_cachedir', sys_get_temp_dir().'/');
        }
        //echo "type=".$this->type;
    }

    /** возвращает значение переменной из кеша
     * @param $key
     * @return bool|mixed|string
     */
    public function get($key)
    {
        if ($this->type=='memcache'){
            //echo "<br>Читаю";
            // если не найдено, то $this->memcache_obj->getResultCode() == 16
            return $this->memcache_obj->get($key);

        }elseif($this->type=='fsock'){
            fwrite($this->memcache_obj, sprintf("get %s\r\n",$key));
            $line = rtrim(fgets($this->memcache_obj));
            if ($line != 'END'){
                return rtrim(fgets($this->memcache_obj));
            }

        }elseif($this->type=='file'){
            $fil=fb_cachedir.url2file($this->prefix.$key).".tmp";
            if(!is_file($fil))return false;
            $data=file_get_contents($fil);
            if($data){
                list($data,$expire)=js_decode($data);
                if($expire>=time()) return $data;
            }

        }
        return false;
    }

    /**
     * @param $key - ключ
     * @param $data - значение
     * @param int $expire - или время жизни в секундах в пределах 30 дней или время до которого живет значение
     * @return bool - true, если успешно
     */
    public function set($key, $data, $expire = 86400)
    {
        if ($this->type=='memcache'){
            //echo "<br>Пишу " . $data;
            return $this->memcache_obj->set($key, $data, $expire);

        }elseif($this->type=='fsock'){
            fwrite($this->memcache_obj, sprintf("set %s 0 %s %s\r\n%s\r\n",  $key,$expire,strlen($data),$data));
            $result = trim(fgets($this->memcache_obj));
            return ($result != 'ERROR');

        }elseif($this->type=='file'){
            return file_put_contents(fb_cachedir.url2file($this->prefix.$key).".tmp", js_encode([$data, ($expire>60*60*24*30 ? $expire : time()+$expire)]), LOCK_EX ) > 0;

        }else  return false;
    }

    public function get_error()
    {
        if ($this->type=='memcache'){
            return $this->memcache_obj->getResultCode();

        }else  return false;
    }

    /** удаляет переменную
     * @param string $key
     * @return boolean
     */
    public function un_set($key)
    {
        if (empty($key) or !is_string($key)) {
            return false;
        }
        if ($this->type=='memcache'){
            return $this->memcache_obj->delete($key, 0);

        }elseif($this->type=='file'){
            unlink(fb_cachedir.url2file($this->prefix.$key).".tmp");

        }else  return false;
    }

    /** очищает весь кеш
     * @return bool
     */
    public function clear()
    {
        if ($this->type=='memcache'){
            return $this->memcache_obj->flush();

        }elseif($this->type=='file'){
            $dh = opendir( fb_cachedir ) or add_error( "Не удалось открыть каталог ".fb_cachedir );
            while ( ($f = readdir( $dh )) ) if (substr($f,0,strlen($this->prefix))==$this->prefix && substr($f,-4,4)=='.tmp' ) {
                //echo "<br>удаляю ".fb_cachedir.$f;
                unlink(fb_cachedir.$f);
            }
            closedir($dh);
            return true;

        }else  return false;
    }
}
