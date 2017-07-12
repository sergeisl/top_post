<?php
include( $_SERVER['DOCUMENT_ROOT'] . '/connect_config.php');

define("fb_dirfile","/images/");	// путь куда класть вложенные файлы

define("DOWNLOAD_DIR","/download");	// путь куда класть файлы для скачивания после оплаты

define('path_tovar_image', '/images/tovar/');
define("fb_logofile","/images/watermark.png");
define("fb_logofileBig","/images/watermark2.png");

defined('db_prefix') || define("db_prefix","");	// префикс всех БД
defined('MEMCACHE_PREFIX') || define("MEMCACHE_PREFIX", 'ad_');

//define("max_size_image","2000"); // Kb, максимальный размер загрузаемого изображения
define("max_size_image",'200000');	// максимальный размер загрузаемого изображения
define("max_size_file",'10000000');	// максимальный размер загружаемого файла импорта
define("fb_logdir",$_SERVER['DOCUMENT_ROOT'].'/log/');
define("fb_tmpdir",$_SERVER['DOCUMENT_ROOT'].'/images/tmp/');
define("fb_tmpdir0",'/images/tmp/'); // WEB путь
define("fb_cachedir",$_SERVER['DOCUMENT_ROOT'].'/log/cache/');

defined('TOVAR_BASE_URL') || define('TOVAR_BASE_URL', '/shop/');

define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ); // ну не могу я без нее привык
define('MODULES_DIR', $_SERVER['DOCUMENT_ROOT'] . '/include/modules');
define('TEMPLATES_DIR', $_SERVER['DOCUMENT_ROOT'] . '/include/templates');

define("DBlogAll",true);

define( "SHOP_NAME", ' | Сок чистотела' );
define("DESCRIPTION",SHOP_NAME);

define("SKLAD_NOW",1);
define("SKLAD_CITY",2);
define("SKLAD_WAIT",3);
define("SKLAD_OLD",4);
define("SKLAD_NOT",9);

define('SEO', true);
define("charset","utf-8");

define('reCAPTCHA_sitekey','6Le6MAkUAAAAAAmNBUbBwkwZsmfmdyevjo2w09z1'); // если неопределена, то запрашиваться не будет. Эта универсальная не зависит от домена!
define('reCAPTCHA_secretkey','6Le6MAkUAAAAABUEaifRnyTubXjulGWErRNnDGNp'); // получить здесь https://www.google.com/recaptcha/admin

// подготовить лого 150*150, 1024 x 1024 с прозрачным фоном
define('VK_id','5944483'); // ID приложения добавить домен здесь https://vk.com/editapp?id=5931909&section=options выбрать ВЕБ-приложение
define('VK_secret','GRWwHOdG18rqNZgpNZES');// Защищённый ключ

define('FB_id','258896621230397'); // ID приложения добавить домен здесь https://developers.facebook.com/apps, укажите Действительные URL-адреса для перенаправления OAuth: http://luxefitness.dev/user/api.php?fb
define('FB_secret','07bb4afaeaf68740dcce3d7a6399cc10');// Защищённый ключ

define("Nacenka",10);
define("price_round",0);

define("tOPT_PROC",15);    // Процент наценки от оптовой цены при продаже мелким оптом

define("delivery_cost",200);// стоимость доставки
define("delivery_from",15000); // от какой суммы доставка бесплатная
define("discount_proc",3);    // ~tMOPT_PROC Процент скидки от розницы при продаже мелким оптом

define("discount_from",100000); // от этой суммы скидка(мелкооптовая цена)
define("discount_count",10000); // от этого кол-ва позиций скидка // todo нужно из кол-ва для скидки исключит расходку
define("Min_zakaz",200);    // минимальный заказ
define("Min_zakaz_opt",100000);    // todo минимальный заказ оптом

ini_set('session.gc_probability',5); //
ini_set('session.cookie_httponly', 1 );
ini_set('session.cache_limiter',16*60);
session_save_path($_SERVER['DOCUMENT_ROOT'].'/log/session');
session_cache_expire(12*60); // время жизни сесии в минутах
session_start();
$_SESSION['session_id']=1; // признак, чтобы сесеия поднята

define("imgSmallSize","140,105");
define("imgMediumSize","220,165");
define("imgBigSize",640); // поменять в js:resizeAndUpload

define("TimeCash",86400);	// период кеширования сутки 24*60*60=86400

$GLOBALS['http']=(!empty($_SERVER['SERVER_PORT'])&&($_SERVER['SERVER_PORT']==443)?'https':'http');
if(!isset($_SERVER['HTTP_HOST']))$_SERVER['HTTP_HOST']='luxefitness.ru';

defined("AdminMail") || define("AdminMail","kdg@htmlweb.ru"); // период кеширования сутки 24*60*60=86400

//@ini_set(auto_prepend_file,'none'); // Это выключает обработку ошибок
// А это включает на E-Mail
//php_value auto_prepend_file error_handler.inc

define("ZakazNoLoginUser",2); // Заказ может делать: 1-только зарегистрированный пользователь, 2-упрощенная авторегистрация

include_once $_SERVER['DOCUMENT_ROOT']."/include/common-utf8/func.php";
include_once $_SERVER['DOCUMENT_ROOT']."/include/func1.php";

if(getenv('REMOTE_ADDR')=='127.0.0.1') {
    DB::$debug=1;
}

date_default_timezone_set('Europe/Moscow'); //Etc/GMT-4

define( "CUR_TIME", time() );
define( "CUR_DATE", date('Y-m-d H:i:s', CUR_TIME) );

$z_incasso=[];
$z_incasso[1]='инкассация';
$z_incasso[2]='закупка хоз.нужд';
$z_incasso[3]='выплата аванса';
$z_incasso[4]='выплата з.платы';
//$z_incasso[7]='остаток в кассе на начало дня';
$z_incasso[8]='штраф/недостача';
$z_incasso[9]='по р/счету';
$z_incasso[10]='выплата з/п на пластик';
//$z_incasso[20]='сальдо по сотруднику user на начало месяца';
// 27 - сальдо в кассе на начало дня
$_DayOfWeek=['0'=>'вс','1'=>'пн','2'=>'вт','3'=>'ср','4'=>'чт','5'=>'пт','6'=>'сб'];

define("tTYPE_TOVAR",0);   // 0-товар
define("tTYPE_USLUGA",1);  // 1-услуга
define("tTYPE_ABON",2);    // 2-абонемент
define("tTYPE_RASX",3);    // 3-расходка
define("tTYPE_ABON_USLUGA",11);//Оказание услуг по абонементу

// Коды товаров, которые нельзя объединять в один чек, например горизонталка и вертикалка
// TODO перенести в свойства товара ибо не понятно как использовать
// define("tTOVAR_NOT_UNION", '1,2');
define("tTOVAR_NOT_UNION", '');

if(db_prefix=="zr_"){
    define("z_oklad",8500);
    define("z_oklad1",8500);
}else{
    define("z_oklad",6000);
    define("z_oklad1",7000);
}

define("ANONIMOUS_KLIENT","2");		// анонимный клиент
define("SCHEDULE_TOVAR",2); // Товар, который списывается при записи в расписании

define("check_out_before","4 Hour");		// за сколько до начала занятия можно выписаться

// список таблиц в которых может редактировать поля обычный пользователь
defined('USER_CAN_EDIT_ROWS') || define('USER_CAN_EDIT_ROWS', 'zakaz2');