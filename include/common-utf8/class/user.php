<?
/*define("pay_out_min","300");	// минимум к выводу
define("pay_ref_percent","20");	// реферальский процент
define("charset","windows-1251");	// или utf-8 используется в отправляемых письмах*/

if(!defined("uADM_KLIENT"))define("uADM_KLIENT",0);    // 0 Клиент
if(!defined("uADM_READER"))define("uADM_READER",2);    // 2 только просмотр
if(!defined("uADM_OPT"))define("uADM_OPT",3);       // 3 оптовый клиент
if(!defined("uADM_OLD_WORKER"))define("uADM_OLD_WORKER",4);// 4 Бывший сотрудник
if(!defined("uADM_WORKER"))define("uADM_WORKER",5);    // 5 Сотрудник
if(!defined("uADM_BAN"))define("uADM_BAN",9);       // 9 забанен
if(!defined("uADM_HELPER"))define("uADM_HELPER",10);   // 10 Помошник админа
if(!defined("uADM_MANAGER"))define("uADM_MANAGER",50);  // 50 Получает уведомление о заказе
if(!defined("uADM_ADMIN"))define("uADM_ADMIN",99);    // 99 Руководитель
if(!defined("USER_REQUIRED"))define("USER_REQUIRED",'mail');    // какое поле обязательно при регистрации

if(!defined('path_avatar'))define('path_avatar','/images/user/');
if(!is_dir($_SERVER['DOCUMENT_ROOT'].path_avatar))mkdir($_SERVER['DOCUMENT_ROOT'].path_avatar,0777,!0); // создаю каталог

require_once($_SERVER['DOCUMENT_ROOT'].'/include/config.php');

/*if(empty($_user)){
   _session_start();
   global $_user;
//    echo "<br>user".var_export($_SESSION['user'],!0); exit;
   $_user=new User(!empty($_SESSION['user']['id'])?$_SESSION['user']:null);
   $_user->get_param();
   if(empty($_SESSION['Last-Modified']))$_SESSION['Last-Modified']=time();
}*/

$GLOBALS['from']="noreply@".Get::SERVER_NAME();

/**
 * Class User
 * @property-read String id код пользователя
 * @property-read boolean sms =1 - Бесплатные СМС не присылать
 * @property boolean rss получать новости по электронной почте
 * @property-read String fullname Полное имя
 * @property-read String user_name Полное имя или логин
 * @property-read String url возвращает Ancor на профиль пользователя
 * @property-read String Murl возвращает ссылку на профиль пользователя для вставки в письмо
 * @property-read String Turl возвращает ссылку на список курсов преподавателя
 * @property-read String adm uADM_OPT
 * @property-read String avatar - аватар текущего пользователя
 * @property string vk_uid
 * @property string fb_uid
 * @property String tel
 * @property String api_key
 * @method static String name - имя(login) текущего пользователя
 * @method static String api_key
 * @method static Array tarif
 */
class User implements ArrayAccess{
	const db_prefix=db_prefix;
    const limit_request=50; // кол-во бесплатных запросов
    const min_cost=1; // минимальная стоимость запроса, коп
    static public $_adm=[0=>'Клиент',1=>'Почта подтверждена',2=>'Оптовый-клиент',3=>'VIP-Клиент',4=>'Бывший сотрудник',5=>'Сотрудник',9=>'Забанен',uADM_HELPER=>'Помощник Админа', uADM_ADMIN=>'Админ'];

    static public $__adm=[0=>'Клиент',1=>'Почта подтверждена',2=>'Оптовый-клиент',4=>'VIP-Клиент',8=>'Бывший сотрудник',16=>'Тренер',32=>'Сотрудник',34=>'Забанен',128=>'Помошник админа',256=>'Админ'];

    public static $ar_info=['balans_mail_send','sign','api_mail_report','api_mail_report2','vk_uid','fb_uid']; // переменные, которые сохраняются в поле info БД
    public static $ar_fields=['fullname','city','tel','sms','country','rss','call_back_url','address','comment','sex','birthday','discount0','discount1','kart']; // список полей, изменяемых пользователем
    public static $ar_fields_nochange=['id','time','info','adm','mail','name','pass']; // то что менять автоматически нельзя
    public static $ar_fields_register=['name', 'pass', 'ip', 'mail', 'uid', 'time', 'date0', 'tel','address','comment','sex','birthday','fullname','sms','rss','kart','ref']; // список полей сохраняемых при регистрации

    private $user=[];

    public function offsetSet($key, $value) {
        //$this->tovar[$key] = $value;
        $this->__set($key,$value);
    }
    public function offsetUnset($key) {
        unset($this->user[$key]);
    }
    public function offsetGet($key) {
        if(isset($this->user[$key]))return $this->user[$key];
        return User::_GetVar($this->user,$key);
    }
    public function offsetExists($key) {
        return isset($this->user[$key])||in_array($key,self::$ar_info );
    }


    /** вызывается при обращении к неопределенному свойству
* @param $property
* @return bool|int|null|string
*/
    function __get($property){
        if(is_null($this->user))return null;
        elseif(isset($this->user[$property]))return $this->user[$property];
        elseif(in_array($property,self::$ar_info )){
            if(isset($this->user['info'][$property]))return $this->user['info'][$property];
            else return '';

        }elseif($property=='ban'){
            return ($this->user['adm']==uADM_BAN);

        }elseif($property=='user_name'){
            return ($this->user['fullname']?$this->user['fullname']:$this->user['name']);

        }elseif($property=='Turl'){  // возвращает ссылку на список курсов преподавателя
            if(empty($this->user['id']))return "нет";
            return "<a onclick='return !window.open(this.href)' href='/teacher/".$this->user['id']."'>".$this->user_name."</a>";

        }elseif($property=='url'){  // возвращает Ancor на профиль пользователя
            if(empty($this->user['id']))return "нет";
            return (User::is_admin() ?
                "<a class='author' onclick='return !window.open(this.href)' href='".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/?id=".$this->user['id']."'>".$this->user_name."</a>" :
                //"<a class='author' onclick='return ajaxLoad(\"\",this.href+\"&ajax=1\");' href='http://".$_SERVER['HTTP_HOST']."/user/?id=".$this->user['id']."'>".$this->user_name."</a>" :
                "<span class='author'>".$this->user_name."</span>" );

        }elseif($property=='Murl'){  // возвращает ссылку на профиль пользователя для вставки в письмо
            if(empty($this->user['id']))return "нет";
            return "<a class='author' href='".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/?id=".$this->user['id']."'>".$this->user_name."</a>";

        }elseif($property=='avatar'){ // в IMG обворачивать в ImgSrc()
            $fil=path_avatar."p".$this->user['id'].".jpg";
            return (is_file($_SERVER['DOCUMENT_ROOT'].$fil) ? $fil : '/images/noavatar.jpg' );

        }elseif($property=='avatar1'){ // в IMG обворачивать в ImgSrc()
            $fil=path_avatar."p".$this->user['id'].".jpg";
            return $fil;

        }elseif($property=='data'){
            return $this->user;

        }elseif( isset($this->user[$property]) && is_null($this->user[$property]) ){
            return null;

        }elseif(in_array($property,['adm','discount0','rss','sms'])){
            return 0; // незалогинен
        }elseif(in_array($property,['tel','mail','company'])){
            return ''; // незалогинен
        }elseif(in_array($property,self::$ar_info)){
            return ''; // незалогинен
        }else die("Нет свойства user::".$property);
    }

    /** вызывается, когда неопределенному свойству присваивается значение
     * @param $property
     * @param $value
     */
    function __set($property, $value){
        if(in_array($property,['tel','api_key','rss'])){
            DB::sql('UPDATE '.self::db_prefix.'user SET '.$property.'="'.$value.'" WHERE id='.$this->id); // сбрасываю хеш
            $this->user[$property]=$value;
            if($this->id==User::id()&&isset($_SESSION['user']['id'])){
                $_SESSION['user'][$property]=$value;
            }
            if( isset($_SESSION['LastUser']['id']) && $_SESSION['LastUser']['id']==$this->id){
                $_SESSION['LastUser'][$property]=$value;
            }
        }elseif(in_array($property,self::$ar_info)){
            $this->user[$property]=$value;
            self::WriteInfo($this->user);
        }else die("Нет обработки сохранения user::".$property);
        DB::CacheClear('user',$this->id);  // сбрасываю хеш
    }


    function __call($name,$arr){// - вызывается при обращении к неопределенному методу
        if($name=='ed'){
            if( $this->ed=='минут' )
                return num2word($arr[0], ["минута", "минуты", "минут"]);
            else
                return $this->ed;
        }elseif(isset($this->user[$name])){
            return $this->user[$name];
        }else die("Нет метода user::".$name);

    }

    /**
     * @param       $name
     * @param array $params
     * @return mixed
     */
    public static function __callStatic($name,array $params)
    {
        if(isset($_SESSION['user'][$name])){
            return $_SESSION['user'][$name];
        }elseif(in_array($name,['id','adm'])){
            return 0;
        }elseif(in_array($name,['api_key'])){
            return 'НЕДОСТУПНО_БЕЗ_РЕГИСТРАЦИИ';
        }elseif(in_array($name,['name'])||in_array($name,self::$ar_info)){
            return '';
/*        }elseif($name=='tarif'){
            foreach(User::$ar_pay as $tarif)if($_SESSION['user']['autopay']<=$tarif['request'])return $tarif;
            return User::$ar_pay[0];*/
        }else die('Вы хотели вызвать '.__CLASS__.'::'.$name.', но его не существует, и сейчас выполняется '.__METHOD__.'()');
    }

    public static function id(){ // внутри класса вызывает __call
        return (isset($_SESSION['user']['id'])?$_SESSION['user']['id']:0);
    }

    /**
     * @param integer $user
     */
    public function __construct($user = null){
        if(is_object($user)){
            return $user;
        }elseif(is_array($user) && (!empty($user['id']) || !empty($user['mail'])) ){
            $this->user=$this->GetUser($user);
        }elseif($user>0){
            if( !empty($_SESSION['user']['id']) && $_SESSION['user']['id']==$user){
                $this->user=$_SESSION['user'];
            }elseif( isset($_SESSION['LastUser']['id']) && $_SESSION['LastUser']['id']==$user){
                $this->user=$_SESSION['LastUser'];
            }else
                $this->user=$this->GetUser($user);
        }else
            $this->user=null;

        //echo "<br><br>";var_dump($this->user);
        if($user==0){}
        elseif($this->user==null && isset($user['ban'])){}
        elseif($this->user==null && isset($_SESSION['user'])){
            AddToLog("Ошибка в коде пользователя: ".var_export($user,!0).dump("POST",$_POST).dump("GET",$_GET).dump("SESSION",$_SESSION).dump("COOKIE",$_COOKIE));
        }elseif(!isset($_SESSION['user']['id']) || $this->id!=$_SESSION['user']['id'] ) $_SESSION['LastUser']=$this->user;// кеширую последнего пользователя
        return $this;
    }

    /**
     * перезагрузить текущего пользователя из БД
     */
    public static function reload(){
        $id=self::id();
        unset($_SESSION['user'],$_SESSION['LastUser']);
        global $_user;
        $_SESSION['user']=self::GetUser($id);
        $_user=new User($_SESSION['user']);
    }

    /**
     * @param array $user
     */
    public static function WriteInfo($user){
        if(empty($user['id']))die('WriteInfo: Нет id!');
        $row=DB::Select('user', $user['id']);
        $info=json_decode($row['info'],!0);
        foreach(self::$ar_info as $key) if(isset($user[$key])) $info[$key]=str_replace(',', '.',$user[$key]);
        DB::sql('UPDATE `'.self::db_prefix.'user` SET info="'.addslashes(json_encode($info)).'" WHERE id="'.intval($user['id']).'"');
        if($user['id']==User::id())array_merge($_SESSION['user'],$user);
        if( isset($_SESSION['LastUser']['id']) && $_SESSION['LastUser']['id']==$user['id']) array_merge($_SESSION['LastUser'],$user);
    }

    static function _GetVar($user,$var){
        $t=new User($user);
        //echo "<br><br>".var_dump($t);
        return $t->$var;
    }

    static function _SetVar($user,$key,$value){
        $user=self::GetUser($user);
        if(!$user){
            AddToLog('User не определен',false,true);
            return false;
        }elseif(in_array($key,['id','time','info','adm','mail','name','pass'])){ // то что менять автоматически нельзя
            return false;
        }elseif(in_array($key,self::$ar_info)){
            $user['info'][$key]=$value;
            DB::sql('UPDATE `'.self::db_prefix.'user` SET info="'.addslashes(json_encode($user['info'])).'" WHERE id="'.intval($user['id']).'"');
        }else{
            $user[$key]=$value;
            DB::sql('UPDATE `'.self::db_prefix.'user` SET '.$key.'="'.addslashes($value).'" WHERE id="'.intval($user['id']).'"');
        }
        if( !empty($_SESSION['user']['id']) && $_SESSION['user']['id']==$user['id']){
            $_SESSION['user']=$user;
        }elseif( isset($_SESSION['LastUser']['id']) && $_SESSION['LastUser']['id']==$user['id']){
            $_SESSION['LastUser']=$user;
        }
        global $_user;
        if($_user->id==$user['id']) $_user->$key=$value;
        return true;
    }

    /**
     * @param int|array $user
     * @return array|null
     */
    static function GetUser($user=null){
        if(!$user){
            return null;
        }elseif(!is_array($user)){// если передается только id из кеша не брать!
            if($user>0){
                DB::CacheClear("user", intval($user));
                $user=DB::Select("user", intval($user) );
            }
            if(!$user)return null;
        }
        if(empty($user['id'])){
            if(!empty($user['mail'])){
                $user=DB::Select("user", "mail='".addslashes($user['mail'])."'"); if(!$user)return null;
            }else return null;
        }
        //$user['adm']=($user['id']<2? 99 : intval($user['adm']) );// первый всегда админ
        $user['adm']=intval($user['adm']); if($user['id']==1)$user['adm']=uADM_ADMIN;
        $user['name']=str_replace("'","`",$user['name']);
        $user['user_name']=($user['fullname']?$user['fullname']:($user['name']?$user['name']:$user['mail']));
        if(isset($user['info'])&&$user['info']&&!is_array($user['info'])){
            $user['info']=json_decode($user['info'],!0);
        }
        foreach($user as $key => $value)$user[$key]=str_replace('"',"'",$value);
        return $user;
    }

    /**
     * обработка параметров GET, POST: регистрация, выход, бан
     */
    public function get_param(){
        //echo"<br>POST=";print_r($_POST); echo"<br>SESSION=";print_r($_SESSION);echo"<br>"; echo nl2br(print_r($_REQUEST,true)); echo nl2br(print_r($_SESSION['user'],true));
        if(!empty($_POST['name']) && !is_string($_POST['name']))return;

        if(!empty($_GET['reowner'])&&User::is_admin()){
            global $_user;
            $_SESSION['reowner']=$_SESSION['user'];
            $_SESSION['user']=self::GetUser(intval($_GET['reowner']));
            $_user=new User($_SESSION['user']);
            self::setLastModified();
            return;

        }elseif(isset($_GET['logout'])){	// выход
            if(!empty($_SESSION['reowner'])){
                global $_user;
                $_SESSION['user']=$_SESSION['reowner'];
                unset($_SESSION['reowner']);
                $_user=new User($_SESSION['user']);
                self::setLastModified();
            }else{
                self::logout();
            }
            Out::Location("/");

        }elseif(isset($_REQUEST['name'])&&isset($_REQUEST['id'])&&(!empty($_REQUEST['uid']))||!empty($_REQUEST['mid'])){
            // uid - восстановление пароля - переход по ссылке из письма
            // mid - подтверждение мыла - переход по ссылке из письма
            $id=intval($_REQUEST['id']);
            $kid=intval(isset($_REQUEST['uid'])&&!empty($_REQUEST['uid']) ? $_REQUEST['uid'] : $_REQUEST['mid']);
            $data = DB::Select('user','id='.$id.' and LOWER(name)="'.addslashes(strtolower(urldecode($_REQUEST['name']))).'" and uid="'.$kid.'"');
            if($data){
                $data['adm']=max($data['adm'],1);
                self::login_ok($data,1); // сразу логиню!
                DB::sql('UPDATE '.self::db_prefix.'user SET uid=0, adm="'.$data['adm'].'" WHERE id='.$id); // сбрасываю хеш
                if(isset($_REQUEST['mid']))
                    Out::message("Почта ".$data['mail']." подтверждена!");
                else{
                    $_SESSION['pass_change']=true;
                    Out::message("Укажите новый пароль и нажмите 'Сохранить'!");
                }
            }elseif(self::is_confirm()){
                Out::message("Почта ".$data['mail']." уже подтверждена!");
                Out::Location('/user/');
            }else{
                Out::error("Такой пользователь не зарегистрирован или неверный код запроса!");
                Out::Location('/user/login.php');
            }


        }elseif(self::is_ban()){
            return;

        }elseif(isset($_REQUEST['adm'])&&$_REQUEST['adm']>99){
            //self::banning()
          Out::Location("/");

        // login

        }elseif(self::is_login()){
            return;

        }elseif(isset($_COOKIE['name']) && isset($_COOKIE['hash']) && strlen($_COOKIE['name'])>2){
            if(self::is_ban($_COOKIE['name'])) return;
            // Вытаскиваем из БД запись, у которой логин равняться введенному // ,INET_ATON(ip) as ip
            $data = DB::Select('user','name="' . addslashes($_COOKIE['name']) . '"');
            if(!$data){
                return; // нет такого
/*          }elseif(empty($data['api_key'])) {
                // временная мера для генерации API
                @setcookie("hash", "", time() - 3600 * 24 * 30 * 12, CookiePath, CookieDomain);*/

            }elseif ($data['pass'] !== $_COOKIE['hash']) { // or ((inet_ntop($data['ip']) !== $_SERVER['REMOTE_ADDR'])  and ($data['ip'] !== "0")))
                // удаляю куки
                setcookie("name", "", time() - 3600*24*30*12, CookiePath, CookieDomain);
                setcookie("hash", "", time() - 3600*24*30*12, CookiePath, CookieDomain);
                Out::error("Авторизация сброшена!");
            }elseif(!empty($_SESSION['user'])){
                self::login_ok($data, true );
                return;
            }else{ // авторизую по кукам, поднимаю сессию
                self::login_ok($data, Get::isApi() );
                return;
            }

        /*}elseif(isset($_GET['ref'])){ // ставлю партнерскую cookie
                @setcookie("ref", $ref = intval($_GET['ref']), time() + 60 * 60 * 24 * 365, CookiePath, CookieDomain);
                DB::sql('UPDATE '.self::db_prefix.'user SET ref_counter=ref_counter+1 WHERE id="'.$ref.'"');*/

        } elseif(!empty($_REQUEST['api_key'])) {
            if(!preg_match('/^[a-zA-Z0-9]{32,45}$/',$_REQUEST['api_key'])){
                Out::error('Неверный символ в api_key!');
            }else{
                if ($data=DB::Select('user','api_key="' . addslashes($_REQUEST['api_key']) . '"')){
                    if($data['adm']==uADM_BAN){
                        error("Вам доступ на сайт запрещен! Код 3");
                    }elseif($data['adm']>0){
                        //$_SESSION['_user'] = $data;
                        self::login_ok($data, 1);
                    }else {Out::error('Не подтвержден е-mail!');}
                }else {error('Неверный api_key!');}
}
        }/*elseif($data=self::is_Domain()){
            if ($data=DB::Select('user','id="' . $data . '"')){
                error('Авторизация по HTTP_REFERER недоступна. Используйте api_key! ');
                if($data['adm']>0){$_SESSION['_user'] = $data;}else {error('Не подтвержден е-mail!');}
            }
        }*/
    }

    /** сохранение изменений анкеты, если я admin, то я могу сохранять других пользователей
     * @param array $user
     */
    static function Save($user){
/*        if(!User::is_login()&&!empty($user['name'])&&!empty($user['pass_old'])){// слетела сессия - попробую залогинить
            if($row=DB::Select("user","LOWER(name)='".addslashes(strtolower($user['name']))."' and pass='".md5(strval($user['pass_old']))."'")){
                self::login_ok(self::GetUser(intval($row['id'])));
            }
        }*/
        if(!isset($_SESSION['user']['id'])){
            Out::err("Сессия завершена. Для продолжения необходимо ".User::NeedLogin());
        }elseif(!self::is_admin(!0) && isset($user['id']) && $user['id']!=$_SESSION['user']['id']){
            Out::err("Недостаточно прав доступа!");
        }elseif(!User::is_login()){
            Out::err(User::NeedLogin());
        }elseif(isset($user['adm'])&&($user['adm']>uADM_ADMIN||$user['adm']<0)){
            Out::err("Недостаточно прав доступа!");
        }//}elseif(isset($_POST['id'])) {// сохранить

        $id=intval((isset($user['id'])?$user['id']:$_SESSION['user']['id']));

        if(!$data=self::GetUser($id))Out::err("Нет такого пользователя!");
        $add='';

        if( !empty($_SESSION['pass_change']) || 1 ||
            /*(!empty($user['pass_old']) && ($data['pass'] === md5(strval($user['pass_old'])))) ||*/
            ($_SESSION['user']['adm']==uADM_ADMIN) ){// сохранить
            if(empty($user['mail'])){//  die("Не передан e-mail!");
                $user['mail']=$data['mail'];
            }
            $mail=$user['mail'];
            if(IsMail($mail) == "error") Out::err("Ваш email некорректен!");
            elseif (IsMail($mail) == '0')Out::error("Вы не указали email!");
            if(empty($user['tel'])){
                $user['tel']=$data['tel'];
            }

            if(isset($user['pass1'])&&strlen($user['pass1'])>3){
                $add.=", pass='".addslashes($hash=md5(strval($user['pass1'])))."'";
                if($id==User::id()) {
                    setcookie("name", $data['name'], time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
                    setcookie("hash", $hash, time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
                }
            }elseif(!empty($user['pass1']))Out::error("Новый пароль слишком короткий и не будет сохранен!");

            unset($_SESSION['pass_change']);
        }else{
            Out::err("Старый пароль введен неверно");
        }

        if(self::is_admin()){
            if(isset($user['adm'])&&intval($user['adm'])==uADM_BAN){
                self::banning(array_merge($user, ['comment'=>'Забанен админом']));
                echo "Забанен!";
            }elseif(isset($user['adm'])&&intval($user['adm'])!=uADM_BAN&&isset($data['adm'])&&intval($data['adm'])==uADM_BAN){
                self::unbanning($user);
                echo "Разбанен!";
            }else{
                $add.=(isset($user['adm'])?", adm=".intval($user['adm']):'').(isset($user['mail'])?", mail='".addslashes(trim($user['mail']))."'":'');
            }
        }elseif($user['mail']!=$data['mail']){
            $add.=", adm=0, mail='".addslashes(trim($user['mail']))."'";
            if(isset($_SESSION['user']['adm']))$_SESSION['user']['adm']=0;
            // todo запросить подтверждение нового мыла
        }
        if($user['name']!=$data['name']){
            if(!User::is_admin()&&strtolower($user['name'])=='admin')Out::error("Такое имя недоступно!");
            elseif(DB::Select("user","LOWER(name)='".addslashes(strtolower($user['name']))."' and id<>'".$id."'")){
                Out::err("Такое имя уже занято!");
            }else{
                $name=$user['name'];
                $add.=", name='".addslashes($name)."'";
                if($id==User::id()) {
                    setcookie("name", $name, time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
                }
            }
        }
        if(!empty($user['call_back_url'])){
            if(!preg_match('#^https?://.+\..+/.+#i',$user['call_back_url']) && in_array(IsMail($user['call_back_url']), ['0','error'])){
                Out::err("Call Back Url должен быть корректным url-адресом обработчика или E-mail или пустым!");
            }
        }
        $f_add=!1;
        if(!empty($user['from'])){// && $user['from']!=SMS::From($id,1)){
            DB::sql('UPDATE `'.self::db_prefix.'users_contact` SET confirm=IF(val="'.addslashes($user['from']).'",2,1) WHERE user="'.$id.'" and confirm>0 and type=0');
            $f_add|=DB::affected_rows()>0;
        }
        // записываю изменения
/*        DB::sql($q = "UPDATE " . self::db_prefix . "user SET fullname='" . addslashes($user['fullname']) . "'" . $add .
            ", city='" . addslashes(isset($user['city']) ? $user['city'] : $data['city']) .
            "', country='" . addslashes(isset($user['country']) ? $user['country'] : $data['country']) .
            "', rss='" . addslashes((isset($user['rss']) && $user['rss'] ? '1' : '0')) .
            "', wm='" . addslashes($user['wm']) .
            "', tel='" . addslashes($user['tel']) . "' WHERE id='" . $id . "' LIMIT 1");*/

        $user['info']=[]; // очищаю
        foreach ($user as $key => $value){
            if(in_array($key,self::$ar_fields_nochange)){ // то что менять автоматически нельзя
            }elseif(in_array($key,self::$ar_info)){
                $user['info'][$key]=$value;
            }elseif(in_array($key,['birthday'])){
                $add.=','.$key.'="'.date('Y-m-d',strtotime($value)).'"';
            }elseif(in_array($key,['tel','phone'])){
                $add.=','.$key.'="'.addslashes(self::NormalTel($value)).'"';
            }elseif(in_array($key,self::$ar_fields)){
                $add.=','.$key.'="'.addslashes($value).'"';
            }
        }
        if(count($user['info']))$add.=',info="'.addslashes(json_encode($user['info'])).'"';
        // записываю изменения
        if($add){
            DB::log('user', $id, '', $data, $user);
            DB::sql('UPDATE `'.self::db_prefix.'user` SET '.substr($add,1).' WHERE id="'.$id.'"');
            if(DB::affected_rows()>0){
                PaymentLog("Изменение профиля.\n".DB::$query);
                unset($_SESSION['LastUser']);
                if($user['id']==User::id()){
                    $user=self::GetUser(intval($user['id']));
                    self::login_ok($user); // обновляю данные сессии
                }
                $f_add=!0;
            }
        }
        $add='';
        if ($id) {// переношу картинки
            if(($add=File::FileSave('user', $id)))$f_add=!0;
        }

        if($f_add)Out::message("Изменения сохранены!".$add);
        else Out::message("Изменений не обнаружено!");
    }

    /** Проверить были ли оплаты от других забаненых пользователей с этого кошелька
     * @param $id
     */
    static function TestPayBan($id,$mes){
        $data=DB::Select('payment','id='.intval($id));
        $query = DB::sql('SELECT * FROM ' . db_prefix . 'payment WHERE status=0 and user<>"'.$data['user'].'" and mes LIKE "%'.$mes.'%" and deliver="'.$data['deliver'].'" GROUP BY user');
        while(($pay =DB::fetch_assoc($query))){
            $old_user=DB::Select("user", intval($pay['user'])); // ранее забаненный пользователь
            if($old_user['adm']==uADM_BAN){
                $user=DB::Select("user", intval($data['user']) ); // пользователь, который сейчас оплатил
                self::banning(array_merge($user,['comment'=>'Оплата с кошелька '.$data['mes'].', '.$data['deliver'].' забаненого пользователя '.$old_user['id']]));
                break;
            }
        }
    }

    /** забанить пользователя
     * @param $user
     */
    static function banning($user){
        if(isset($user['id'])){
            //AddToLog("Banning User=".var_export($user,true),"Banning User1");
            DB::sql("UPDATE ".self::db_prefix."user SET adm='".uADM_BAN."' WHERE id='".$user['id']."' LIMIT 1");
            if(!empty($_SESSION['user']['id']) && ($user['id']==$_SESSION['user']['id'])){
                @setcookie("ban", uADM_BAN, time()+60*60*24*30, CookiePath, CookieDomain);
                $_SESSION['user']['ban']=uADM_BAN;
            }
        }
        if(isset($user['name'])&&isset($user['comment'])){
            /*$query = DB::sql("SELECT * FROM ".self::db_prefix."ban_users WHERE name='".addslashes($user['name'])."' LIMIT 1");
             if(!DB::num_rows($query))*/
            //AddToLog("Banning User=".var_export($user,true),"Banning User1");
            DB::sql('INSERT INTO '.self::db_prefix.'ban_users (name,ban,time,comment)
                 VALUES ("'.addslashes(strtolower($user['name'])).'","'.uADM_BAN.'","'.date("Y-m-d H:i:s").'","'.addslashes($user['comment']).'")');
            if(!empty($user['id']))SendAdminMail("Забанен ".$user['name'], "Забанен <a href='http://htmlweb.ru/user/?id=".$user['id']."'>".$user['name']."</a>, <a href='http://htmlweb.ru/user/balans.php?id=".$user['id']."'>баланс</a> \n".$user['comment']);
        }

    }

    /** разбанить пользователя
     * @param $user
     */
    static function unbanning($user){
    if(isset($user['id']))
        DB::sql("UPDATE ".self::db_prefix."user SET adm='".$user['adm']."' WHERE id='".$user['id']."' LIMIT 1");
    if(isset($user['name']))
        DB::sql('DELETE FROM '.self::db_prefix.'ban_users WHERE name="'.addslashes(strtolower($user['name'])).'"');
}

    static function setLastModified($time=0){
        $_SESSION['Last-Modified']=(empty($time)?time():$time);
        if(empty($_SESSION['message'])&&empty($_SESSION['error'])){
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $_SESSION['Last-Modified']).' GMT');
        }
    }

    static function logout()
    {
        unset($_SESSION['user'], $_COOKIE['name'], $_COOKIE['hash']);
        $old=time()-3600*24*30*12;
        setcookie("name", "", $old, CookiePath, CookieDomain);
        setcookie("hash", "", $old, CookiePath, CookieDomain);
        setcookie("ban",  "", $old, CookiePath, CookieDomain);
        //session_regenerate_id(true);
        unset($_SESSION['session_id']);
        @session_destroy();
        session_id(md5(microtime(true)));
        setcookie(session_name(), session_id(), strtotime("+1 days"), "/"); // при каждом открытии страницы продлеваю сессию
        header("Pragma: no-cache");
        header("Expires: ",gmdate('D, d M Y H:i:s', $old).' GMT');
        header( 'Cache-Control: no-store, no-cache, must-revalidate' );
        header( 'Cache-Control: post-check=0, pre-check=0', false );
        self::setLastModified();
    }



    /**
     * @param $user
     * @param int $shut = true - Без вывода сообщений
     */
    static function login_ok($user,$shut=0){
        global $_user;
        $user=self::GetUser($user);
        if(!empty($user['api_key']) && session_id()!=$user['api_key']){     // если имя сессии не соответствует API_KEY, меняю имя сесии
            unset($_SESSION['session_id']);
            @session_destroy();
            session_id($user['api_key']);
            setcookie(session_name(), session_id(), strtotime("+1 days"), "/"); // при каждом открытии страницы продлеваю сессию
            _session_start();   // меняю кеширование, поднимаю сесию с новым именем
        }
        self::setLastModified();

        $_SESSION['user']=$user; if(!$_SESSION['user']){
        SendAdminMail('login_ok error',
                "Запрос: ".$_SERVER['REQUEST_URI'].".<br>\n".
                "Стек: ".var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),!0));
            die('Это невозможно!');
        }
        $_user=new User($_SESSION['user']);
         //echo nl2br(print_r($user,true));

        if(($_SESSION['user']['adm']==uADM_BAN) || (isset($_SESSION['user']['ban'])&&$_SESSION['user']['ban']==uADM_BAN)){
            $_SESSION['user']['ban']=uADM_BAN;
        }else{
            unset($_SESSION['user']['ban']);
            DB::sql('UPDATE '.self::db_prefix.'user SET ip=INET_ATON("'.Get::ip(1).'"), time="'.date("Y-m-d H:i:s",time()).'" WHERE id="'.$user['id'].'"');
            DB::sql('DELETE FROM '.self::db_prefix.'ban_users WHERE name="'.strtolower($_SESSION['user']['name']).'" or ip="'.ip2long(Get::ip(1)).'" LIMIT 1');
        }

        if($shut || !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'],'/api.php')!==false || isset($_REQUEST['ajax'])){

        }elseif($_user->adm==0){
            Out::message("Здравствуйте, ".$_user->user_name.",\nВам необходимо проверить почту и подтвердить регистрацию!");
        }elseif(!empty($_SESSION['user']['ban'])&&$_SESSION['user']['ban']==uADM_BAN){
            Out::message("Вам заблокирован доступ на проект!");
        }elseif(!isset($_REQUEST['ajax'])){
            /*SendAdminMail('login_ok ',
                "Запрос: ".$_SERVER['REQUEST_URI'].".<br>\n".
                "Стек: ".var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),!0));*/

            //Здравствуйте, Дмитрий Геннадьевич Колесников также известный как kdg22
            $ip0=long2ip($_SESSION['user']['ip']);
                Out::message("Здравствуйте, ".($_SESSION['user']['adm']==uADM_ADMIN?' администратор ':'').$_user->user_name."!\n".
        "Прошлый раз Вы заходили ".date("d.m.Y в H:i", strtotime($_SESSION['user']['time']))." с IP: <a href='http://htmlweb.ru/analiz/whois_ip.php?ip=".$ip0."'>".$ip0."</a>.\n".
        "Сейчас IP=<a href='http://htmlweb.ru/analiz/whois_ip.php?ip=".Get::ip(1)."'>".Get::ip()."</a>.");
        }
}
/** формирую ник автоматически по мылу
* @param $mail
* @return array|string
*/
static function GetNameForMail($mail){
    $name=explode('@',$mail);
    $name=strtolower(trim(substr($name[0],0,32))); // проверяю на занятость имя до @
    if(in_array($name,['admin','info'])) {
        $name = explode("@", $mail);
        $name = strtolower(trim(substr($name[1], 0, 32)));
    }
    if(self::is_busy(['name'=>$name])){
        $name=strtolower(trim(substr($mail,0,32)));
        if(self::is_busy(['name'=>$name])){
            do{
                $name='u'.mt_rand(1,100000000);
            }while (self::is_busy(['name'=>$name]));
        }
    }
    return $name;
}
/*echo "<br>User="; var_dump($userInfo);
["uid"]=> int(5607242)
["first_name"]=> string(14) "Р”РјРёС‚СЂРёР№"
["last_name"]=> string(20) "РљРѕР»РµСЃРЅРёРєРѕРІ"
["sex"]=> int(2)
["screen_name"]=> string(5) "kdg22"
["bdate"]=> string(9) "22.1.1971"
["photo_big"]=> string(44) "http://cs10004.vk.me/u5607242/a_3793e787.jpg"
["mail"]=> string(14) "kdg@htmlweb.ru"
echo "Социальный ID пользователя: " . $userInfo['uid'] . '<br />';
echo "Имя пользователя: " . $userInfo['first_name'] . '<br />';
echo "Мыло: " . $userInfo['email'] . '<br />';
echo "Ссылка на профиль пользователя: " . $userInfo['screen_name'] . '<br />';
echo "Пол пользователя: " . $userInfo['sex'] . '<br />';
echo "День Рождения: " . $userInfo['bdate'] . '<br />';
echo '<img src="' . $userInfo['photo_big'] . '" />';
echo "<br />";*/
static function login_vk($user){

    if(self::is_ban('',true)){
        die('Вам доступ на сайт запрещен!');
    }elseif(empty($user['mail'])||strpos($user['mail'],'@')===false){
        die("Не передан e-mail!");
    }
    if(USER_REQUIRED=="tel"&&empty($user['tel'])){

        //Out::mes("<form action='/user/api.php?add' method='post'><label>Для окончания регистрации укажите Ваш мобильный телефон:<input type='tel' name='tel'></label></form>");
    }

    $user['mail']=strtolower($user['mail']);
    $user['vk_uid']=$user['uid'];
    if(charset!='utf-8')$user=Convert::array_utf2win($user);

    if(($user1=DB::Select("user","mail='".addslashes($user['mail'])."'"))){
        $fields=[];
        if($user1['uid'])$fields['uid']=0;   // сбрасываю хеш
        if($user1['adm']==0)$fields['adm']=1; // подтверждаю почту, если зарегистрирован и не подтверждена
        if(empty($user1['fullname']))$fields['fullname']=$user["first_name"].' '.$user["last_name"]; // ФИО
        if(strtotime($user1['birthday'])<strtotime('01.01.1950') && !empty($user['bdate'])) $fields['birthday']=date('Y-m-d',strtotime($user['bdate']));
        if(empty($user1['sex'])) $fields['sex']=$user['sex'];
        if($fields) DB::update('user', $fields, $user1['id']);
        if($GLOBALS['DEBUG'])file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/vk.log',"\n".date('i:h:s')."\n"."\nuser=".var_export($user,!0)."\nuser1=".var_export($user1,!0)."\nfields=".var_export($fields,!0)."\n".DB::$query, FILE_APPEND);
        $user=array_merge($user,$user1,$fields);
        self::WriteInfo(['id'=>$user['id'], 'vk_uid'=>$user["vk_uid"]]);
        $nname=$_SERVER['DOCUMENT_ROOT'].path_avatar.'p'.$user['id'].'.jpg';
        if(!is_file($nname))if(copy($user['photo_big'], $nname) !== true) Out::error('Не удалось загрузить аватар из '.$user['photo_big'].'!');
        User::login_ok($user);

    }else{
        $user['name']=$user['screen_name'];
        $user['fullname']=$user["first_name"].' '.$user["last_name"]; // ФИО
        $user['birthday']=$user["bdate"];
        //if(!empty($user['mobile_phone'])){$tel=self::NormalTel($user['mobile_phone']); if (preg_match("/^[78][0-9]{10}$/", $tel))$user['tel']=$tel;}
        if(empty($user['tel']) && !empty($user['home_phone'])){$tel=self::NormalTel($user['home_phone']); if (preg_match("/^[78][0-9]{10}$/", $tel))$user['tel']=$tel;}
        unset($user["bdate"],$user['screen_name'],$user["first_name"],$user["last_name"], $user['mobile_phone'], $user['home_phone']);
        $user['img']=$user['photo_big'];
        self::login_add($user);
    }
    setcookie("name", $user['name'], time()+60*60*24*30, CookiePath, CookieDomain);
    setcookie("hash", $user['pass'], time()+60*60*24*30, CookiePath, CookieDomain);
    //@setcookie("ref", '', time() - 999, CookiePath, CookieDomain); // удаляю реферальное cookie
}

/*  [id] => 100000317390816
    [name] => Стас Протасевич
    [first_name] => Стас
    [last_name] => Протасевич
    [birthday] => 07/03/1988
    [hometown] => Array
        (
            [id] => 110228142339670
            [name] => Chisinau, Moldova
        )
    [location] => Array
        (
            [id] => 110228142339670
            [name] => Chisinau, Moldova
        )
    [gender] => male
    [email] => stanislav.protasevich@gmail.com
    [timezone] => 2
    [verified] => 1
    [updated_time] => 2012-12-06T18:06:38+0000
*/
static function login_fb($user){

    if(self::is_ban('',true)){
        die('Вам доступ на сайт запрещен!');
    }elseif(!isset($user['email'])){
        Out::error("Facebook не передал Ваш e-mail!");
        WriteErrorAndExit(3);
    }
    $user['mail']=strtolower($user['email']);
    $user['fb_uid']=$user['id'];
    unset($user['id'], $user['email']);
    if(($user1=DB::Select("user","mail='".addslashes($user['mail'])."'"))){
        $fields=[];
        if($fields['uid'])$fields['uid']=0;   // сбрасываю хеш
        if($user1['adm']==0)$fields['adm']=1; // подтверждаю почту, если зарегистрирован и не подтверждена
        if(empty($user1['fullname']))$fields['fullname']=$user["name"]; // ФИО
        if(strtotime($user1['birthday'])<strtotime('01.01.1950') && !empty($user['birthday'])) $fields['birthday']=date('Y-m-d',strtotime($user['birthday']));
        if(empty($user1['sex'])) $fields['sex']=($user['gender']=='male'?2:($user['gender']=='female'?1:0));
        if($fields) DB::update('user', $fields, $user1['id']);
        if($GLOBALS['DEBUG'])file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fb.log',"\n".date('i:h:s')."\n"."\nuser=".var_export($user,!0)."\nuser1=".var_export($user1,!0)."\nfields=".var_export($fields,!0)."\n".DB::$query, FILE_APPEND);
        $user=array_merge($user,$user1,$fields);
        self::WriteInfo(['id'=>$user['id'], 'fb_uid'=>$user["fb_uid"]]);
        User::login_ok($user);
    }else{
        //$user['name']=$user['name'];
        if(empty($user['gender']))$user['sex']=0;
        elseif($user['gender']=='male')$user['sex']=2;
        elseif($user['gender']=='female')$user['sex']=1;
        else $user['sex']=0;

        //if(isset($user["first_name"])&&isset($user["last_name"]))
            $user['fullname']=$user["first_name"].' '.$user["last_name"];

        unset($user['gender'],$user["first_name"],$user["last_name"]);

        $user['img']='http://graph.facebook.com/' . $user['fb_uid'] . '/picture?type=large';

        self::login_add($user);
    }
    @setcookie("name", $user1['name'], time()+60*60*24*30, CookiePath, CookieDomain);
    @setcookie("hash", $user1['pass'], time()+60*60*24*30, CookiePath, CookieDomain);
    //@setcookie("ref", '', time() - 999, CookiePath, CookieDomain); // удаляю реферальное cookie
}

    /** вызывается после регистрации через соц.сеть для добавления пользователя
     * @param $user
     */
    static function login_add($user)
    {
        $user['name']=substr($user['name'],0,32);
        if(self::is_busy(['name'=>$user['name']])){
            $user['name']=self::GetNameForMail($user['mail']);
        }
        $user['pass1']=mt_rand(1,100000000); // pass1 - пароль в исходном виде
        $user['pass']=md5(strval($user['pass1']));
        if(empty($user['birthday']))$user['birthday']=0;

        if (USER_REQUIRED == "tel" && empty($user['tel']) || empty($user['mail']) ) {
            $_SESSION['LoginWithoutCaptcha']=$user;
            include_once($_SERVER['DOCUMENT_ROOT'] . '/user/signup.php'); // дорегистрация
            exit;
        }

        // сюда попадаю если достаточно только мыла и я его получил от соц.сети
        DB::sql("INSERT INTO " . self::db_prefix . "user ( name, pass, ip, mail, adm, rss, time, date0, fullname, birthday, sex ) ".
            "VALUES ('".addslashes($user['name'])."', '".$user['pass']."', INET_ATON('".$_SERVER['REMOTE_ADDR']."'), ".
            "'" . addslashes($user['mail']) . "', 1, 1, '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d") . "', '".addslashes($user["fullname"]) ."', ".
            "'" . date("Y-m-d", strtotime($user['birthday']) ) . "', " . $user['sex'] . ")");
        if(!($data=DB::Select('user','mail="' . addslashes($user['mail']) . '"'))){
            AddToLog('Пользователь не добавлен: ' . print_r($user, true));
            die('Пользователь не добавлен!');
        }
        $user['id']=$data['id'];
        self::WriteInfo($user);
        $nname=$_SERVER['DOCUMENT_ROOT'].path_avatar.'p'.$user['id'].'.jpg';
        if (!empty($user['img']) && !is_file($nname)) {
            if (copy($user['img'], $nname) !== true) Out::error('Не удалось загрузить аватар!');
        }
        self::login_ok($data,1);
        self::confirm_mail(array_merge($data,['pass1'=>$user['pass1']]));

    }
        /**
     * @param bool|integer $helper -
     * @return bool = true если права админа
     */
    static public function is_admin($helper=false,$user=0){
        if($user==1) {
            return true;
        }elseif($user>0 ) {
            if(empty($_SESSION['user']['id']) || $user!=$_SESSION['user']['id']){ // чтобы не делать лишний запрос в базу
                $user=DB::Select('user', intval($user) );
                return ($user && $user['adm'] == uADM_ADMIN);
            }
        }
        return (isset($_SESSION['user']['adm']) &&
            (($_SESSION['user']['adm']==uADM_ADMIN) ||
                (is_bool($helper) && $helper && $_SESSION['user']['adm']>=uADM_WORKER && $_SESSION['user']['adm']!=uADM_BAN) ||
                (is_integer($helper) && $_SESSION['user']['adm']==$helper)) );
    }

    /** разрешена ли данному пользователю отправка СМС по дорогому каналу
     * @param int $user
     * @return bool = true если разрешена
     */
    static public function is_sms($user=0){
        if(empty($user)&&!empty($_SESSION['user']['id']))$user=$_SESSION['user']['id'];
        if($user==1 || self::is_admin($user)) {
            return true;
        }elseif($user>0 ) {
            if(empty($_SESSION['user']['id']) || $user!=$_SESSION['user']['id']){ // чтобы не делать лишний запрос в базу
                $user=DB::Select('user', intval($user) );
                return !empty($user['sms']);
            }
        }
        return !empty($_SESSION['user']['sms']);
    }

    /** Отправляет e-mail пользователю
     * @param $subject - заголовок сообщения
     * @param $msg - тело сообщения в html формате, кодировка charset
     * @param $user - id|array - получатель
     */
    static function SendMail($subject, $msg, $user){
        $host=Get::SERVER_NAME();
        $user= new User($user);
        // защита от дублей
        $fil=fb_tmpdir.str2url($user->mail.$subject).'.tmp';
        if(is_file($fil) && filemtime($fil)>strtotime("-6 hours")){
            SendAdminMail("Повторное письмо",nl2br("Получатель: ".$user->mail."\nТема: ".$subject."\n".$msg."\nUser=".var_export($user,!0)) );
            return;
        }
        @file_put_contents($fil,'');

        $str="<p>Здравствуйте, ".$user->user_name.".</p>\n".
        $msg.
        "<p>С Уважением, администрация сайта ".$host.".</p>\n".
        "<p><font color=\"gray\">Это сообщение приходит только один раз.<br>\n".
        "Ваш логин на сайте <b>".$user->name."</b>.<br>\n".
        "Если Вы забыли пароль <a href=\"".$GLOBALS['http'].'://'.$host."/user/remember.php?n=".urlencode($user->name)."\">нажмите сюда</a>.</font></p>\n";
        self::_mail($user->mail, $subject, $str, "CC: <".AdminMail.'>');
    }

    /**
     * @param $to - мыло получателя
     * @param $subject - заголовок
     * @param $msg - тело
     * @param string $AddHeader
     * @param string $file
     * @return bool
     */
    static function _mail($to, $subject, $msg, $AddHeader='', $file='')
    { // Отправляет e-mail пользователю
        if(is_array($to)){
            $to=$to['mail'];
        }elseif(preg_match('/^[0-9]+$/',$to,$ar)&&intval($to)>0){
            $to = self::GetUser($to); if($to)$to=$to['mail'];
        }
        $from=
            "From: <".(isset($GLOBALS['from'])&&$GLOBALS['from'] ? $GLOBALS['from'] : "noreply@".Get::SERVER_NAME()).">\n".
            'Date: ' . date('r', $_SERVER['REQUEST_TIME']) ."\n".
            'Message-ID: <' . $_SERVER['REQUEST_TIME'] . md5($_SERVER['REQUEST_TIME']) . '@' . $_SERVER['SERVER_NAME'] . '>' ."\n".
            'Reply-To: ' . 'kdg@htmlweb.ru' ."\n".
            'Return-Path: ' . 'kdg@htmlweb.ru' ."\n".
            "List-Unsubscribe: <".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/api.php?unsubscribe=".urlencode($to)."&hash=".md5(strtolower($to.$_SERVER['HTTP_HOST']))."&all>\n".
            (empty($_SERVER['SERVER_ADDR'])?'':'X-Originating-IP: ' . $_SERVER['SERVER_ADDR']."\n").
            /*'X-Mailer: PHP v' . phpversion().*/
            ($AddHeader?$AddHeader:"");
        //$subject='=?koi8-r?B?'.base64_encode(convert_cyr_string($subject, "w","k")).'?=';
        //$subject='=?UTF-8?B?' . base64_encode($subject) . '?=';
        $subject=mime_header_encode($subject, charset);
        $msg = "<html><body>\n" . $msg . "\n</body></html>";
        //AddToLog(htmlspecialchars("TO::" . $to ."\nSUBJECT::" . $subject ."\nMSG::" . $msg ."\nFROM::" . $from));
        if($file){
            $bound="HtmlWeb-".rand(1000,9999);
            $from="Mime-Version: 1.0\n".
                "Content-Type: multipart/mixed; boundary=\"$bound\"\n".
                $from;
            $body="--$bound\n";
            $body.="Content-type: text/html; charset=\"".charset."\"\n";
            $body.="Content-Transfer-Encoding: 8bit\n\n";
            $body.=$msg;
            $body.="\n\n--$bound\n";
            $body.="Content-Type: application/octet-stream; name=\"".basename($file)."\"\n";
            $body.="Content-Transfer-Encoding:base64\n";
            $body.="Content-Disposition: attachment; filename=\"".basename($file)."\"\n\n";
            $body.=chunk_split(base64_encode(file_get_contents($file)))."\n";
            $msg=$body."--$bound--\n\n";
        }else{
            $from="Content-Type: text/html; charset=". charset ."\n".$from;
        }
        return mail($to, $subject, $msg, $from );
    }

    /** проверка бана пользователя, вызывается после успешной регистрации
     * @param string $name
     * @param bool $NoMessage = true сообщения не выводить
     * @return bool = true, если доступ запрещен
     */
    static function is_ban($name='',$NoMessage=false){
        if(isset($_COOKIE['ban'])&&$_COOKIE['ban']==uADM_BAN){
            //AddToLog("User=".var_export($_SESSION['user'],true).", Cookie=".var_export($_COOKIE,true),"Ban User1");
            if(!$NoMessage)Out::error("Вам доступ на сайт запрещен! Код 1");
            return true; //нафиг
        }elseif(isset($_SESSION['user']['ban'])&&$_SESSION['user']['ban']==uADM_BAN){
            //AddToLog("User=".var_export($_SESSION['user'],true).", Cookie=".var_export($_COOKIE,true),"Ban User1");
            if(!$NoMessage)Out::error("Вам доступ на сайт запрещен! Код 2");
            return true; //нафиг
        }elseif(isset($_SESSION['user']['adm']) && $_SESSION['user']['adm']==uADM_BAN){
            AddToLog("User=".var_export($_SESSION['user'],true),"Ban User 2");
            if(!$NoMessage)Out::error("Вам доступ на сайт запрещен! Код 3");
            return true; //нафиг
        }elseif(isset($_SESSION['user']['adm'])){
            return false; // если я залогинен, то дергать базу не нужно
        }elseif(DB::is_table('ban_users')){
            if(DB::Select('ban_users','ban='.uADM_BAN.' and ('.($name? 'name="'.addslashes($name).'" or ' : '').' (ip>0 and ip="'.ip2long(Get::ip(1)).'"))')){ // баню всех пользователей этого компьютера
                //AddToLog("Ban_users=".var_export($data,true).(ip2long(Get::ip(1))==$data['ip']?' IP! ':''),"Ban User2");
                @setcookie("ban", uADM_BAN, time()+60*60*24*30, CookiePath, CookieDomain);
                $_SESSION['user']['ban']=uADM_BAN;
                if(!$NoMessage)Out::error("Вам доступ на сайт запрещен! Код 4");
                return true;
            }
        }
        return false;
    }

    /** проверка необходимости ввести капчу
     * @param string $name - username
     * @return bool|int - количество минут через которые капчу можно не вводить или false, если капча не нужна
     */
    static function is_captcha($name=''){
        if(!defined('UserCaptcha')||!UserCaptcha)return false;
    if(strlen($name)>2) ;
    elseif(isset($_SESSION['user']['name']))$name=$_SESSION['user']['name'];
    elseif(isset($_COOKIE['name']))$name=$_COOKIE['name'];
    else $name='';
    if($name)$name='name="'.addslashes($name).'" or';
    $data=DB::Select('ban_users','('.$name.' ip="'.ip2long(Get::ip(1)).'") and time>"'.date("Y-m-d H:i:s",strtotime('-10 minutes')).'"');
	//echo $q." ~ ".DB::num_rows($query);
    //if(DB::num_rows($query)&&!isset($_SESSION['captcha_keystring']))$_SESSION['captcha_keystring']=1;
    //else unset($_SESSION['captcha_keystring']);
    if($data)
	    return intval((strtotime($data['time'])-(time()-(60*10)))/60); // запросить capcha
    else
	    return false;
}


/** проверка введенной Капчи
 * @return bool
*/
static function test_captcha(){
global $_user;
if(empty($_user))$_user=new User();
if($_user->is_ban('',true)){
        Out::error('Вам доступ на сайт запрещен!');
    }elseif(empty($_POST['g-recaptcha-response'])){
        Out::error('Вы - робот!?');
    }else{
        /*
         * https://www.google.com/recaptcha/admin#site/318686498
         * https://developers.google.com/recaptcha/docs/verify
         * https://developers.google.com/recaptcha/docs/display#render_param
         */
        list($headers,$body,$info)=ReadUrl::ReadWithHeader('https://www.google.com/recaptcha/api/siteverify',
            'secret='.reCAPTCHA_secretkey.'&response='.urlencode($_POST['g-recaptcha-response']).'&remoteip='.getenv('REMOTE_ADDR'), ['cache'=>0,'timeout'=>10]);
        if($body){
            $recaptcha =json_decode($body,!0);
            if(!empty($recaptcha['success'])&&$recaptcha['success']){
                return true;
            }else Out::error("Не верная капча. Попробуйте ещё раз.");
        }else{
            SendAdminMail('Capcha error', "headers=".var_export($headers,!0)."\nbody= ".var_export($body,!0)."\ninfo=".var_export($info,!0));
            Out::error("Не удалось проверить капчу. Попробуйте ещё раз.");
        }
    }
        return false;
}

    /** вызывается после ввода неверного пароля
     * @param $name
     */
    static function bad_password($name, $message='Вы ввели неправильный логин/пароль!' ){
    @setcookie("name", $name, time()+60*60*24*30, CookiePath, CookieDomain);
	//$_SESSION['user']['name']=$name;
    $data=DB::Select('ban_users','name="'.addslashes($name).'" or ip="'.ip2long(Get::ip(1)).'"');
	if($data)DB::sql('UPDATE '.self::db_prefix.'ban_users SET counter="'.(intval($data['counter'])+1).'", time="'.date("Y-m-d H:i:s").'" WHERE id="'.$data['id'].'"');
	else DB::sql('INSERT INTO '.self::db_prefix.'ban_users (ip,name,counter,time) VALUES ("'.ip2long(Get::ip(1)).'","'.addslashes($name).'","1","'.date("Y-m-d H:i:s").'")');
    Out::error($message);
}

    /**
     * @return bool =true, если залогинин
     */
    static public function is_login($user_id=0){
        if(!isset($_SESSION['session_id'])) return false;
        if(empty($user_id) || ($user_id>0 && !empty($_SESSION['user']['id']) && $_SESSION['user']['id']==$user_id)){
            return (isset($_SESSION['user']['adm'])&&!empty($_SESSION['user']['id'])&&$_SESSION['user']['adm']!=uADM_BAN&&
                (!isset($_SESSION['user']['ban'])||$_SESSION['user']['ban']!=uADM_BAN));

        }
        if( empty($_SESSION['LastUser']['id']) || $_SESSION['LastUser']['id']!=$user_id){
            $_SESSION['LastUser']=DB::Select('user', intval($user_id) );
        }
        return (isset($_SESSION['LastUser']['adm'])&&!empty($_SESSION['LastUser']['id'])&&$_SESSION['LastUser']['adm']!=uADM_BAN&&
            (!isset($_SESSION['LastUser']['ban'])||$_SESSION['LastUser']['ban']!=uADM_BAN));
    }
    /**
    * @return bool
    */
    static public function is_MayByLogin()
    {
        return (isset($_SESSION['user']['adm']) && $_SESSION['user']['adm'] != uADM_BAN &&
            (!isset($_SESSION['user']['ban']) || $_SESSION['user']['ban'] != uADM_BAN));
    }

    /**
     * @return bool =true, если почта подтверждена
     */
    static public function is_confirm(){
        return (!empty($_SESSION['user']['adm']) && $_SESSION['user']['adm']!=uADM_BAN);
    }

    /**
    * @param bool $user
    * @param string $phone
    * @return bool =true, если телефон подтвержден
    */
    static public function is_confirm_phone($user=false, $phone=''){
        //var_dump($_SESSION['user']);
        if(empty($user)&&!empty($_SESSION['user']))$user=$_SESSION['user'];
        if(empty($user['id']))return false;
        if(empty($phone)&&!empty($user['tel']))$phone=trim($user['tel']);
        if(strlen($phone)<11)return false;
        $confirm=DB::Select('users_contact','user='.intval($user['id']).' and type=0 and val="'.addslashes($phone).'"');
        //var_dump($confirm);
        return (!empty($confirm['confirm']));
    }

    /**
     * вывод формы запроса кода подтверждения
     */
    static public function confirm_phone(){
        ?>
        <label>Код подтверждения: <input name="code" size="6">&nbsp;<br/>
            <input type="submit"  class="submitL" value="Подтвердить" onclick="return ajaxLoad('confirm_phone1','/user/api.php?confirm_phone='+getValue(this.form.tel)+'&code='+getValue(this.form.code));">
        <?
    }
    /**
     * Есть ли платежные операции у пользователя
     * @return bool есть ли платежные операции у пользователя
     */
    static function is_Payment()
    {
        if (!isset($_SESSION['user']['id']) || intval($_SESSION['user']['id']) < 0) return false;
        return (!!DB::Select('payment','user=' . intval($_SESSION['user']['id']) . ' and status<9'));
    }

    /**
     * @param bool $html
     * @return int|string - сообщение для вывода | код | 0, если нормальный доступ
     */
    static public function is_OnlyRead($html=false){// пользователь может только читать
        if(isset($_SESSION['user']['adm']) && $_SESSION['user']['adm']==2) return ($html?'<span class="red">Вам запрещено писать на сайте!</span>':3);
        /*if(DB::Select("ban_tel","name='".addslashes($_SESSION['user']['tel'])."'")){
            $_SESSION['user']['adm']=2;
            return ($html?'<span class="red">Вам запрещено писать на сайте!</span>':3);
        }*/
        return 0;
}

    /**
* @param $name
 * @return bool
*/static function login_ban($name)
    {
        if (($data=DB::Select('ban_users','name="' . addslashes($name) . '" or (ip>0 and ip="' . ip2long(Get::ip(1)) . '")'))) {
            if (strtotime($data['time']) > time() - 60 * 10) { // запросить capcha
                if (isset($_SESSION['captcha_keystring']) && $_SESSION['captcha_keystring'] === @$_POST['keystring']) return false;
                Out::error("Неверно указан код с картинки!");
                if (!isset($_SESSION['user']['ban'])) $_SESSION['user']['ban'] = 1;
                return true; //нафиг
            }
        }
        //unset($_SESSION['captcha_keystring']);
        return false;
    }

    /**
     * получить кол-во пользователей ON-line
     * @return integer кол-во пользователей ON-line
     */
    static function getUsersOnline() {
        $path=session_save_path();
        if(empty($path)) $path=$_SERVER['DOCUMENT_ROOT'].'/log/session';
        $cfile=$path.'/UsersOnline.cch';
        $df=strtotime("-30 minutes");
        if (file_exists($cfile) && filemtime($cfile) > $df ) return intval(file_get_contents($cfile));
        if(!($dh=opendir($path)))return 0;
        $c=strlen(session_id());
        $count=0;
        while (($f = readdir($dh)) !== false) {
           if ( strlen($f)<$c || strpos( $f, "." )!==false) continue;
        if (@filemtime($path . '/' . $f) < $df) {
            @unlink($path . '/' . $f); //$del++;
            //if(strlen($t)<5000)$t.="\n".$path . '/' . $f . (file_exists($path . '/' . $f)?" - Не смог удалить":" - удалил");
        }else{
            //if(strlen($t)<5000)$t.="\n".$path . '/' . $f . " ". date("d.m.Y H:i",filemtime($path . '/' . $f)) ;
           $count++;
           }
    }
        closedir($dh);
        file_put_contents($cfile, $count);   // 5 минут
        return $count;
        //return count(scandir($path))-2; // '.','..'
}

    /**
     * добавление нового клиента админом
     * @param $user array = $_POST
     */
    static function Add($user){
        if(empty($user['pass1'])) {
            $user['pass1'] = mt_rand(1, 100000000); // pass1 - пароль в исходном виде
            $user['pass'] = md5(strval($user['pass1']));
        }

        $_SESSION['LoginWithoutCaptcha']=$user;
        self::register($user); // по умолчанию подписываю на рассылку на e-mail
        if(!empty($user['tel']) && Get::SignupSms() ){
            Sms::Send( $user['id'], str_ireplace(['{login}','{pass}'],[$user['name'],$user['pass1']], Get::SignupSms() ));
        }
        if(!empty($user['mail']) &&  Get::SignupMail() ){
            // Здравствуйте, {user_name}. Для Вас создан личный кабинет: http://luxefitness.ru Логин: {login} Пароль: {pass}
            self::_mail( $user['id'], 'Создан личный кабинет', str_ireplace(['{login}','{pass}'],[$user['name'],$user['pass1']], Get::SignupMail() ) );
        }
    }

    /**
     * @param $user = $_POST для сохранения
     * $user['pass1'] - пароль в исходном виде
     */
    static function register($user)
    {
        //if($GLOBALS['DEBUG'])file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/register.log',"\n".date('i:h:s')."\n"."\nuser=".var_export($user,!0), FILE_APPEND);
        $fields = array_flip(self::$ar_fields_register); // список полей, которые буду записывать

        if(!empty($_SESSION['LoginWithoutCaptcha'])){
            $user=array_merge($_SESSION['LoginWithoutCaptcha'],$user);
            unset($_SESSION['LoginWithoutCaptcha']);
            if(!empty($user['adm'])){
                $fields['adm'] = '';
                $user['adm'] = min($user['adm'],User::adm()); // права не больше, чем у текущего админа
            }
            $user['rss'] = 1; // по умолчанию подписываю на рассылку на e-mail

        }elseif(!User::is_admin(!0)&&!empty($user['adm'])) $user['adm']=min(intval($user['adm']),User::adm());// права не больше, чем у текущего админа

        // если почта была внесена, но неподтверждена и теперь я с подтвержденной почтой, то все отлично!
        if (!empty($user['mail']) && !empty($user['adm']) && ($data=DB::Select("user", "LOWER(mail)='" . addslashes($user['mail']) . "'")) and (!$data['adm']) ){
            $user['id']=$data['id'];
        }

        if(!empty($user['ref'])&&strlen($user['ref'])==4){ // указано 4 последних цифры карты
            if(($ref=DB::Select('user','kart LIKE "%'.addslashes($user['ref']).'"')))$user['ref']=$ref['id'];
        }

        if(!empty($user['id'])){self::Save($user);return;}
        // сохранение нового пользователя при ускренной регистрации
        if(empty($user[USER_REQUIRED])) {Out::error('Заполните '.USER_REQUIRED.'!');return;}
        if (!empty($user['mail']))$user['mail'] = trim(strtolower($user['mail']));
        if (!empty($user['tel'])) $user['tel'] = self::NormalTel($user['tel']);

        if(($i = User::is_busy($user,'html'))){Out::error($i);return;}

        mt_srand((double)microtime() * 1000000);
        if (!isset($user['name'])){ // формирую ник автоматически
            if(!empty($user['mail'])) {
                $user['name'] = self::GetNameForMail($user['mail']);
            }else{
                do{
                    $name='u'.mt_rand(1,100000000);
                }while (self::is_busy(['name'=>$name]));
            }
        }
        if (empty($user['pass1'])) $user['pass1'] = mt_rand(1, 100000000);
        $user['pass'] = md5(strval($user['pass1']));
        $user['uid'] = mt_rand(1, 100000000);
        $user['ip'] = Convert::ip2long2();
        $user['time'] = date("Y-m-d H:i:s");
        $user['date0'] = date("Y-m-d");

        if(!empty($user['sex'])) {
            if ($user['sex'] == "Женский") $user['sex'] = 1;
            elseif ($user['sex'] == "Мужской") $user['sex'] = 2;
            else $user['sex'] = intval($user['sex']);
        }
        if(!empty($user['birthday']))$user['birthday']=date("Y-m-d", (is_string($user['birthday']) ? strtotime($user['birthday']) : $user['birthday']));

        // todo объединить с Save
        DB::insert('!user', array_intersect_key($user, $fields));

        //if($GLOBALS['DEBUG'])file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/register.log',"\n".date('i:h:s')."\n".DB::$query, FILE_APPEND);

        //DB::sql("INSERT IGNORE INTO " . self::db_prefix . "user ( name, pass, ip, mail, uid, time, date0 )	VALUES ('" . addslashes($user['name']) . "', '" . $user['pass'] . "', INET_ATON('" . $_SERVER['REMOTE_ADDR'] . "'),	'" . addslashes($user['mail']) . "', " . $user['uid'] . ", '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d") . "')");
        $q = DB::$query;
        if (!($data = DB::Select('user', USER_REQUIRED.'="' . addslashes($user[USER_REQUIRED]) . '"'))) {
            AddToLog('Пользователь не добавлен: ' . print_r($user, true) . "\n" . $q);
            Out::error('Пользователь не добавлен!');
            return;
        }
        $user['id']=$data['id'];

        self::WriteInfo($user);

        // если передали аватарку, сохраняю ее
        File::FileSave('user',$data['id']);
        // а если регистрировался через соц.сеть, то передать могли ссылкой
        $nname=$_SERVER['DOCUMENT_ROOT'].path_avatar.'p'.$user['id'].'.jpg';
        if (!empty($user['img']) && !is_file($nname) && is_string($user['img'])) {
            if(copy($user['img'], $nname) !== true) Out::error('Не удалось загрузить аватар!');
        }

        if($data['id']==User::id()){
            setcookie("name", $user['name'], time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
            setcookie("hash", $user['pass'], time() + 60 * 60 * 24 * 30, CookiePath, CookieDomain);
            //@setcookie("ref", '', time() - 999, CookiePath, CookieDomain); // удаляю реферальное cookie
            self::login_ok($data, 1);
        }
        if (!empty($user['mail']))self::confirm_mail(array_merge($data, ['pass1' => $user['pass1']]));
        //if (!empty($user['tel'])) self::Send()
        //Out::LocationRef();
    }

/* провека занятости мыла или логина
 false - свободно
 1 - занято
 2 - неверно DB::close();die('<font color="red">неверный символ в e-mail!</font>');
 3 - недоступно {DB::close();die('<font color="red">недоступно</font>');}
 4 - DB::close();die('<font color="red">e-mail сервер '.$parts[1].' не существует!</font>');
 5 - неверно DB::close();die('<font color="red">неверный символ в телефоне!</font>');
 9 - "Ошибка в параметрах!"
*/
    /**
     * @param $ar - array(name,mail,tel)
     * @param string $format ='html'- в html формате для вывода
     * @return int |false - 0||false - если имя|мыло|телефон свободно
     */
    static function is_busy($ar, $format=''){
        $answer=[0=>'', /*<span class="green b">&radic;</span>*/
        1=>'<span class="red">уже зарегистрирован!</span> <a href="/user/remember.php">Восстановить пароль?</a>',
           /*"Пользователь с таким именем(login) уже зарегистрирован!<br>Если Вы забыли пароль, воспользуйтесь системой <a href='/user/remember.php?n=" . urlencode($user['mail']) . "'>восстановления пароля</a>."*/
        2=>'<span class="red">неверный символ в e-mail!</span>',
        3=>'<span class="red">недоступно!</span>',
        4=>'<span class="red">e-mail сервер не существует!</span>',
        5=>'<span class="red">неверный номер телефона!</span>',
        6=>'<span class="red">В логине должны быть только латинские буквы и "@",".","-","+"</span>',
        7=>'<span class="red">Логин должен быть от 3 до 32 символов</span>',
        8=>'<span class="red">Не верный логин</span>',
        9=>'<span class="red">Не верные параметры</span>'];
        $add='';
        $i=9;
        while(1) { // для переходов на конец
            if (isset($ar['name'])) {
                if (trim(mb_strtolower($ar['name'])) == 'admin'){$i=3;break;}
                //if (preg_match('/^\d/', $ar['name'])){$i=5;break;}
                if (!preg_match('/^[a-zA-Z0-9@\.\-\+]+$/', $ar['name'])){$i=6;break;}
                if (strlen($ar['name']) < 3 || strlen($ar['name']) > 32){$i=7;break;}
                if (!preg_match('/^[a-zA-z]{1}[a-zA-Z0-9@\.\-\+]{2,31}$/', $ar['name'])){$i=8;break;}
                $add = "LOWER(name)='" . addslashes(trim(strtolower($ar['name']))) . "'";
            }
            if (!empty($ar['mail'])) {
                $_val = trim($ar['mail']);
                if (filter_var($_val, FILTER_VALIDATE_EMAIL) === false){$i=2;break;}
                $parts = explode('@', $_val, 2);
                $parts = $parts[1];
                //$parts=end(explode('@',$_val, 2));
                if (in_array($parts, ['yandex.ru', 'mail.ru', 'rambler.ru', 'gmail.com', 'bk.ru'])) {
                    // Эти сервера точно есть
                } elseif (function_exists("checkdnsrr")) {
                    if (!checkdnsrr($parts . '.', 'MX')){$i=4;break;}
                }
                $add .= ($add ? ' or ' : '') . "LOWER(mail)='" . addslashes(trim(strtolower($_val))) . "'";
            }
            if (!empty($ar['tel'])) {
                $_val = self::NormalTel($ar['tel']);
                if (!preg_match("/^[78][0-9]{10}$/", $_val)){$i=5;break;}
                $add .= ($add ? ' or ' : '') . "tel='" . addslashes($_val) . "'";
            }
            if (!$add){$i=9;break;}
            if (!empty($ar['id'])) $add = '(' . $add . ') and id<>' . intval($ar['id']);
            elseif (isset($_SESSION['user']['id'])) $add = '(' . $add . ') and id<>' . intval($_SESSION['user']['id']);
            $i=(DB::Select("user",$add) ? 1 : 0 ); // 1 || 0 || false
            break;
        }
        if($format && $i==1){
            if (!empty($ar['mail'])) return str_ireplace('remember.php','remember.php?n=' . urlencode($ar['mail']),$answer[$i]);
            if (!empty($ar['tel'])) return '<span class="red">уже зарегистрирован! Для получения доступа обратитесь в администрацию.</span>';
        }
    return ($format?$answer[$i]:$i);
}

    /** запрос письма подтверждения мыла или уведомление о регистрации через соц.сеть
     * @param array $user
     */
    static function confirm_mail($user)
    {
        if (empty($user['uid'])) {
            $user['uid'] = mt_rand(1, 100000000);
            DB::sql('UPDATE ' . db_prefix . 'user SET uid=' . $user['uid'] . ' WHERE id="' . $user['id'] . '" LIMIT 1');
        }
        $mid = $GLOBALS['http'] . "://" . Get::SERVER_NAME() . "/user/?name=" . urlencode($user['name']) . "&mid=" . $user['uid'] . "&id=" . $user['id'];
        $uid = $GLOBALS['http'] . "://" . Get::SERVER_NAME() . "/user/?name=" . urlencode($user['name']) . "&uid=" . $user['uid'] . "&id=" . $user['id'];
        $mail_body = "Здравствуйте" . (empty($user['fullname']) ? '' : ', ' . $user['fullname']) . "!<br>\nВы зарегистрировались на " . Get::SERVER_NAME() . "<br>\nИмя: <b>" . $user['name'] . "</b><br>
Пароль: <b>" . (empty($user['pass1']) ? "<a href=\"" . $uid . "\">[задать новый]</a>" : $user['pass1']) . "</b><br>\n" .
            (empty($user['adm']) ?
                "Для подтверждения регистрации перейдите по ссылке: <a href=\"" . $mid . "\">" . $mid . "</a>.<br>\nЕсли ссылка не открывается, скопируйте её, вставьте в адресную строку браузера и нажмите Enter.<br>\n"
                : '') .
            (empty($user['pass1']) ? "" : "Сохраните это письмо в надежном месте. Мы не сможем вам выслать повторно пароль, т.к. пароли у нас не хранятся.
Если Вы забудете пароль, то Вы сможете запросить создание нового пароля.<br>");

        if(Get::DEBUG()) Out::message("Mail: ".$user['mail']."\n".$mail_body);

        if (!mail($user['mail'], mime_header_encode("Подтверждение регистрации в " . Get::SERVER_NAME()), $mail_body, "From: <noreply@" . Get::SERVER_NAME() . ">\nContent-Type: text/html; charset=" . charset)) {
            AddToLog("Не смог отправить почту на " . $user['mail']);
            die("Не смог отправить почту на " . $user['mail']);
        }
        if (empty($user['adm'])) {
            Out::message("Получите почту <b>" . $user['mail'] . "</b> и подтвердите регистрацию!");
        } else {
            Out::message('На <b>' . $user['mail'] . '</b> отправлено письмо с регистрационными данными.');
        }

    }

    /** восстановление пароля
     * @param $name
     */
   static function remember($name){
   if(strlen($name)<3){
       Out::error("Ошибка в параметрах!");
   }else{ // Вытаскиваем из БД запись, у которой логин или мыло равняеться введенному
        if(($data=DB::Select('user','mail="'.addslashes($name).'" or LOWER(name)="'.addslashes(strtolower($name)).'"'))){
            mt_srand((double)microtime()*1000000);
            $uid=mt_rand(1,100000000);
            DB::sql('UPDATE '.self::db_prefix.'user SET uid='.$uid.' WHERE id="'.$data['id'].'" LIMIT 1');
            $str=$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/?name=".urlencode($data['name'])."&uid=".$uid."&id=".$data['id'];
            $body = "<p>Здравствуйте, " . $data['name'] . "!<br>\nДля получения нового пароля на " . Get::SERVER_NAME() . " перейдите по ссылке\n <a href=\"" . $str . "\">\n" . $str . "</a>.</p>";
            if (self::_mail($data['mail'], "Восстановление пароля в " . Get::SERVER_NAME(), $body, "Cc: <" . AdminMail . ">")){
                Out::message("Информация для восстановления пароля выслана на " . $data['mail'] . " !");
                return true;
            }else {
                Out::error("Ошибка отправки информации для восстановления пароля на " . $data['mail'] . " !");
            }
            //header("Location: http://".$_SERVER['HTTP_HOST']."/");
        }else Out::error("Такой пользователь не зарегистрирован!");
    }
       return false;
}

    /** удаление пользователя с проверкой прав
     * @param $id
     */
    static function Delete($id){
        $id=intval($id);
        if( self::is_admin() || (isset($_SESSION['user']['id']) && $_SESSION['user']['id']==$id)){
            $tbls=DB::ListTables('user'); // пройти по всем базам и проверить есть ли операции, и если нигде нет - удалить
            foreach($tbls as $tbl){
                //DB::sql("DELETE FROM ".self::db_prefix.$tbl." WHERE user='".$id."'");
                if(DB::Select($tbl,"user='".$id."'"))Out::err("В таблице ".$tbl." есть ссылка на пользователя. НЕ Удалил!");
            }
            DB::log('user', $id, 'удаление');
            // удаляю самого пользователя
            DB::Delete("user",$id);
            if($id==User::id())self::logout();
            return true;
        }else Out::err("НЕ Удалил!");
}


    /** Уведомление о балансе на мыло
     * @param Object|integer $user
     * @param string $msg
     */
    static function SendNoticeBalans($user, $msg){
        if($user){
            $c=intval(self::_GetVar($user,'balans_mail_send'));
            if($c<2 && intval(self::_GetVar($user,'api_mail_report'))!=1){ // 1-не присылать отчет и уведомления
                self::_SetVar($user,'balans_mail_send', (++$c) );
                self::ApiLog($user, "Отправлено уведомление на e-mail ".$c."\r\n");
                self::SendMail('Пополните баланс', nl2br($msg."\nОтключить уведомление о низком балансе можно в <a href='".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/'>личном кабинете</a>."), $user);
            }
        }
    }

    static function ApiLog($user, $msg)
    {
        file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/log/error/'.date("Y_m_d").'_'.$user.'.log', $msg, FILE_APPEND);
    }


    /**
     * @param string $pref
     * @return string
     */
    static function NeedLogin($pref=' Необходимо ')
    {
        if(isset($_SESSION['user']['ban'])&&$_SESSION['user']['ban']==uADM_BAN) return "";
        return $pref." <a href=\"/user/login.php\">войти</a> или <a href=\"/user/signup.php\">зарегистрироваться</a>!";
    }
/** проверка телефона на валидность
 * @param string $tel
 * @return string
 */
    static function NormalTel($tel){
        $tel=str_replace(' ','',str_replace('(','',str_replace(')','',str_replace('-','',str_replace('+','',$tel)))));
        //if(substr($tel,0,2)=='88'||substr($tel,0,2)=='89')$tel='7'.substr($tel,1);
        if(substr($tel,0,1)=='8')$tel='7'.substr($tel,1);
        return $tel;
    }


    /** объединяет два пользователя в $id_new
     * @param integer $id_old старый товар
     * @param integer $id_new новый товар
     * @return bool
     */
    static function Union($id_old,$id_new){
        if($id_old==$id_new)Out::err("Выбраны одинаковые пользователи!");
        if(!($user_old=DB::Select('user', $id_old)))Out::err('Не пользователя с кодом '.$id_old.'!');
        if(!($user_new=DB::Select('user', $id_new)))Out::err('Не пользователя с кодом '.$id_new.'!');
        foreach(['user','manager'] as $fld) {
            $tbls = DB::ListTables($fld);
            foreach ($tbls as $tbl) {
                DB::sql("UPDATE IGNORE`" . db_prefix . $tbl . "` SET `" . $fld . "`='" . $id_new . "' WHERE `" . $fld . "`='" . $id_old . "'");
            }
        }
        $add='';
        foreach($user_new as $key => $value){
            if($key=='info'&& !empty($value) && !empty($user_old[$key])) {
                // info="{"vk_uid":"74918305"}" и info="{"fb_uid":"74918305"}"
                $info=array_merge(json_decode($user_old[$key], !0), json_decode($value, !0) );
                $add.=','.$key.'="'.addslashes(json_encode($info)).'"';
            }elseif($key!='id' && empty($value) && !empty($user_old[$key]) ){
                $add.=','.$key.'="'.addslashes($user_old[$key]).'"';
            }
        }
        if($add){DB::sql('UPDATE '.db_prefix.'user SET '.substr($add,1).' WHERE id='.$id_new.' LIMIT 1');if(Get::DEBUG())Out::message($add);} // заполняю пустые поля

        $fil_new=$_SERVER['DOCUMENT_ROOT'].path_avatar.'p'.$id_new.'.jpg';
        $fil_old=$_SERVER['DOCUMENT_ROOT'].path_avatar.'p'.$id_old.'.jpg';
        if(!is_file($fil_new) && is_file($fil_old))rename($fil_old,$fil_new);
        elseif(is_file($fil_old))unlink($fil_old);
        DB::log('user', $id_new, 'Объединение из '.$id_old);
        DB::Delete("user",$id_old);
        return (DB::affected_rows()>0);
    }

    /** Авторизация пользователя
     * @param $ar
     */
    static function Authorization($ar){
        if(!User::is_admin() && User::is_ban($ar['name'])){
            if(Get::isApi())Out::ErrorAndExit(3);
            Out::Location('/user/login.php');
        }
        if(User::is_captcha($ar['name']) && !User::test_captcha() ){
            if(Get::isApi())Out::ErrorAndExit(3);
            Out::Location('/user/login.php');
        }
        // Вытаскиваем из БД запись, у которой логин или мыло равняется введенному
        $data = DB::Select('user','LOWER(name)="'.addslashes(strtolower($ar['name'])).'" or LOWER(mail)="'.addslashes(strtolower($ar['name'])).'" or tel="'.addslashes($ar['name']).'"');
        // Сравниваем пароли
        $hash=md5(strval($ar['pass']));
        if($data && $data['pass'] === $hash){
            // Ставим куки
            @setcookie("name", $data['name'], time()+60*60*24*30, CookiePath, CookieDomain);
            @setcookie("hash", $hash, time()+60*60*24*30, CookiePath, CookieDomain,null,true);
            User::login_ok($data); // передан верный пароль, авторизую
            Out::LocationRef(User::is_admin(!0)?'/adm/':'/');
        }else{
            User::bad_password($ar['name']);
        }
        if(Get::isApi())Out::ErrorAndExit(3);
        Out::Location('/user/login.php');

    }

} // end of class




if( isset($_GET['logout'])||
    isset($_GET['name'])&&isset($_GET['id'])&& (!empty($_GET['uid'])||!empty($_GET['mid']))||
    isset($_COOKIE['name']) && isset($_COOKIE['hash']) && strlen($_COOKIE['name'])>2 ||
    isset($_POST['name'])&&isset($_POST['pass'])||
    count($_POST)>0 || !empty($_COOKIE['PHPSESSID']) ){
    _session_start();
    global $_user;
    //echo "<br>user".var_export($_SESSION['user'],!0); exit;
    if(!isset($_user)){
        $_user=new User(!empty($_SESSION['user']['id'])?$_SESSION['user']:null);
        $_user->get_param();
        if(empty($_SESSION['Last-Modified']))$_SESSION['Last-Modified']=time();
    }
}

