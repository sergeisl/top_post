<?
// загружается один раз в lib.php
require_once $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
if(!is_dir(fb_tmpdir))mkdir(fb_tmpdir,777,true); // создаю каталог
if(!is_dir(fb_cachedir))mkdir(fb_cachedir,777,true); // создаю каталог

//DB::sql('DROP TABLE IF EXISTS '.db_prefix.'category');
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'category (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name varchar(128) NOT NULL DEFAULT "",
		`parent` INT UNSIGNED NOT NULL,
		`keywords` varchar(128) NOT NULL DEFAULT "",
		`description` text NOT NULL DEFAULT "",
		nac TINYINT UNSIGNED NOT NULL DEFAULT 0,
		`cnt` INT UNSIGNED NULL DEFAULT NULL comment \'счетчик товаров в группе\',
		PRIMARY KEY (id),
		INDEX ( `parent` )
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

if(!DB::is_field('category','parent')){
    DB::sql("ALTER TABLE `category`
            ADD `parent` INT(10) UNSIGNED NOT NULL,
            ADD	`description` text NOT NULL DEFAULT \"\",
            ADD	nac TINYINT UNSIGNED NOT NULL DEFAULT 0,
            ADD	`cnt` INT UNSIGNED NULL DEFAULT NULL comment 'счетчик товаров в группе',
            ADD	INDEX ( `parent` )");
    print "<br>".DB::$query."<br>".DB::info();
}

// в 1 попадает все, что не попало в 2-5
DB::sql("INSERT IGNORE INTO `".db_prefix."category` (`id`,`name`,`keywords`) VALUES
    ('1','Для загара','для загара'),
    ('2','После загара','после загара, After Sun'),
    ('3','Защита от солнца','spf'),
    ('4','Для душа','душ, мыло, скраб, Scrub, Body Wash'),
    ('5','Сопутствующие товары','тапочки,шапочки,стикини,напиток'),
    ('10','Лицо','лицо,лица'),
    ('11','Тело','тело'),
    ('12','Ноги','для ног,Legs'),
    ('13','Волосы','волос,шампунь'),
    ('20','Разогревающий','разогревающий,покалывани,hot,микроциркуляцию,согревающий,тингл,Blush Factor'),
    ('21','Бронзатор','bronz,бронзатор,окрашивани'),
    ('22','Активатор','активатор')");
print "<br>".DB::$query."<br>".DB::info();

//DB::sql('DROP TABLE IF EXISTS '.db_prefix.'category_link');
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'category_link (
		tovar INT UNSIGNED NOT NULL,
		category INT UNSIGNED NOT NULL,
		PRIMARY KEY (`tovar`,`category`)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

/*
 Картинки коллекции: /pic/collection/idNNN.jpg
*/
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'collection (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name varchar(128) NOT NULL DEFAULT "",
		brand INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

/*
 Картинки бренда: /pic/brand/idNNN.jpg
*/
// kod_prodact - шаблон кода производителя
// url - поисковая ссылка на сайт производителя
// title - название торговой марки
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'brand (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL UNIQUE,
		url VARCHAR(255) NOT NULL,
		title VARCHAR(64) NOT NULL,
		kod_prodact VARCHAR(40) NOT NULL,
		search VARCHAR(255) NOT NULL,
		`cnt` INT UNSIGNED NULL DEFAULT NULL comment "счетчик товаров в группе включая подгруппы",
		PRIMARY KEY (id)'."
) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci") or die("Ошибка (" . DB::error().") в запросе:<br />\n".DB::$query );
print "<br>".DB::$query."<br>".DB::info();

if(!DB::is_field('brand','cnt')){
    DB::sql("ALTER TABLE `brand`
        ADD url VARCHAR(255) NOT NULL,
        ADD	title VARCHAR(64) NOT NULL,
        ADD	kod_prodact VARCHAR(40) NOT NULL,
        ADD	search VARCHAR(255) NOT NULL,
        ADD	`cnt` INT UNSIGNED NULL DEFAULT NULL comment \"счетчик товаров в группе включая подгруппы\"");
    print "<br>".DB::$query."<br>".DB::info();
}

DB::sql ("CREATE TABLE IF NOT EXISTS ".db_prefix."user (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(32) NOT NULL comment '=login',
		`fullname` VARCHAR(64) NOT NULL,
		tel CHAR(12) UNIQUE,
		kart CHAR(13) comment 'ean-13',
		sms ENUM('0','1') NOT NULL,
		`mail` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'e-mail',
		rss ENUM('0','1') NOT NULL,
		`birthday` DATE NULL DEFAULT NULL COMMENT 'день рождения',

		date0 DATE NOT NULL COMMENT 'Дата регистрации',
		city INT UNSIGNED NOT NULL COMMENT 'Город посещения занятий',
		address VARCHAR(128) NOT NULL COMMENT 'Адрес',
		adm TINYINT UNSIGNED DEFAULT 0 NOT NULL,
		`pass` CHAR(32) NOT NULL COMMENT 'hash пароля',
		sex TINYINT(1) COMMENT 'пол',
		discount0 TINYINT UNSIGNED COMMENT 'Скидка на товар',
		discount1 TINYINT UNSIGNED COMMENT 'Скидка на услуги',
		`comment` TEXT NOT NULL comment 'О пользователе',
		info TEXT NOT NULL comment 'json',
        `time` DATETIME NOT NULL COMMENT 'время последнего входа в систему',
        `ip` INT(10) UNSIGNED NOT NULL DEFAULT 0,
        `uid` INT UNSIGNED NOT NULL,
		PRIMARY KEY (id),
		INDEX (kart)
		) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci") or die("Ошибка (" . DB::error().") в запросе:<br>\n".DB::$query );
print "<br>".DB::$query."<br>".DB::info();


// если вообще никого нет - создаю первого ANONIMOUS_KLIENT - анонимный клиент, второго и третьего - администраторов, 4-го - админа
if(DB::Count('user')==0) {
    DB::sql("INSERT INTO `" . db_prefix . "user` (`id`, `name`,  `date0`, `adm`)
     VALUES ( '" . ANONIMOUS_KLIENT . "', 'Клиент', '" . date('Y-m-d') . "', '0')");
    print "<br>".DB::$query."<br>".DB::info();
}


// Карточки
$query='CREATE TABLE IF NOT EXISTS '.db_prefix.'kart (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		time DATETIME comment "дата выдачи",
		dat_end DATE,
		user INT UNSIGNED,
		tovar INT UNSIGNED,
		ost INT,
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci';
DB::sql ( $query ) or die("Ошибка (" . DB::error().") в запросе:<br>\n".$query );
print "<br>".DB::$query."<br>".DB::info();

/* справочник товаров, услуг и абонементов
// discount = 1 // скидки не распространяются
// discount = ??? порождает скидку на другой товар ???
// type = 0 - товар, 1 - услуга, 2-абонемент, 3 - расходка
// фото товара: /pic/tovar/{kod}.jpg
// sec - секция по которой пробивать
// srok - срок действия услуги в месяцах / заказ для товара
// todo товар может быть по цене = 0 и добавляется при оказании услуги
// у сертификата kol=0 всегда, берется сумма для списания
//ed - единица измерения количества "минут", "услуг"
//DB::sql('DROP TABLE IF EXISTS '.db_prefix.'tovar');
Brand: если type==1 - "Кабинки(1,2) - если не указаны или одна, то не спрашивается" : "Бренд"
info: best_before, cab
*/
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'tovar (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		brand INT UNSIGNED NOT NULL DEFAULT 0,
		name varchar(128) NOT NULL DEFAULT "",
		type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT "0-товар, 1-услуга, 2-абонемент, 3-расходка",
		`gr` int(10) unsigned NOT NULL COMMENT \'Основная товарная группа\',
		`price` INT UNSIGNED DEFAULT 0 COMMENT "цена",
		`price1` float(10,2) NOT NULL COMMENT "Мелкий опт",
		`price2` float(10,2) NOT NULL COMMENT "Опт",
		maxdiscount TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT "максимальная скидка",
		kod_prodact VARCHAR(40) NOT NULL DEFAULT ""  COMMENT "Артикул",
		ean char(13) NOT NULL DEFAULT "" ,
		description TEXT,
		tovar INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "ссылка на tovar для абонементов",
		kol FLOAT(7,2) NOT NULL COMMENT "Количество услуг, объем мл, если=1-то только по одной",
		srok TINYINT UNSIGNED NOT NULL COMMENT "срок действия, мес / заказ",
		ost INT NOT NULL,
        `sklad` tinyint(3) unsigned NOT NULL COMMENT "Доступность товара 1 - магазин, 2-Ростов, 3-Москва, 9 - недоступен, скрыть из поиска",
		price0 FLOAT(8,2) COMMENT "цена для сотрудников",
		ed varchar(12) NOT NULL DEFAULT "" COMMENT "единица измерения количества",
	    `info` TEXT NOT NULL comment "json",
		collection INT UNSIGNED NOT NULL DEFAULT 0,
        `supplier` int(10) unsigned DEFAULT NULL COMMENT \'Поставщик\',
        `date_upd` DATE NOT NULL COMMENT \'Дата обновления\',
        vitrina TINYINT UNSIGNED NOT NULL DEFAULT 0  COMMENT \'0-обычный,1-витрина,2-скрытый\',
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

if(!DB::is_field('tovar','gr')){
    DB::sql("ALTER TABLE `" . db_prefix . "tovar`
       ADD `gr` int(10) unsigned NOT NULL COMMENT 'Основная товарная группа'
    ");
    print "<br>".DB::$query."<br>".DB::info();
}

// счетчики
// на одном аппарате может быть три счетчика, например солярий, аквабриз, виброплатформа
// на загаре может быть счетчик минут(counter1) и счетчик пульсаций(запусков)-counter2 или счетчик на пульте и счетчик на аппарате
DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."counters (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		time DATETIME,
		tovar INT UNSIGNED NOT NULL,
		device INT UNSIGNED NOT NULL,
		counter1 INT UNSIGNED NOT NULL,
		counter2 INT UNSIGNED NOT NULL,
		rej TINYINT UNSIGNED NOT NULL comment '0-минуты, 1-замена ламп/жидкости, 2-очистка',
		PRIMARY KEY (id)
) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci") or die("Ошибка (" . DB::error().") в запросе:<br>\n".DB::$query );
print "<br>".DB::$query."<br>".DB::info();


/* todo заказ у неавторизованного пользователя
status:
 0='предварительный набор товара для заказа'
 1='отправлен на согласование менеджеру';
 2='отклонен менеджером, см.answer дата в time_ok и time_end';
 3='подтвержден менеджером, может быть оплачен дата согласования time_ok, срок оплаты до time_end';
 4='оплачен. время оплаты в time_pay';
 5='отгружен в time_end';
 ticket - код для платежной системы
 forma= 1 Просьба выписать счет на оплату
 forma= 2 Просьба оплатить пластиковой картой
 forma= 3 Оплатить с помощью ЭПС(WebMoney,Yandex.Деньги,Qiwi)
 forma= 4 Оплатить наличными в пункте выдачи
info - json('comment', 'answer', 'forma', 'time_ok', 'time_end', 'time_pay', 'ticket', 'ok_code', 'failure_code', 'manager', 'delivery', 'delivery_address'):
        comment VARCHAR(1024) NOT NULL DEFAULT "",
		answer VARCHAR(1024) NOT NULL DEFAULT "",
		forma TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT "1-счет,2-карта,3-ЭПС,4-нал",
		time_ok DATETIME,
		time_end DATETIME,
		time_pay DATETIME,
		ticket	Varchar(40) NOT NULL DEFAULT "",
		ok_code	Varchar(40) NOT NULL DEFAULT "",
		failure_code Varchar(40) NOT NULL DEFAULT "",
		manager INT UNSIGNED NOT NULL,
		delivery TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT "0-самовывоз,1-доставка,2-99 типы",
		delivery_address VARCHAR(256) NOT NULL DEFAULT "",
*/
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'zakaz (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		user INT UNSIGNED NOT NULL,
		time DATETIME,
		manager INT UNSIGNED COMMENT "Ответственный менеджер",
		`zp` FLOAT NOT NULL,
		`zpu` FLOAT NOT NULL,
		`visa` INT NOT NULL COMMENT "Сумма оплаты пластиком",
		status TINYINT UNSIGNED NOT NULL DEFAULT 0,
		`info` TEXT NOT NULL comment "json",
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();


DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'zakaz2 (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		zakaz INT UNSIGNED NOT NULL,
		tovar INT UNSIGNED NOT NULL,
		kol INT UNSIGNED NOT NULL,
		price FLOAT( 10, 2 ) UNSIGNED NOT NULL,

		kart INT UNSIGNED comment "N абонемента",
	    sertif INT UNSIGNED comment "N сертификата",
	    summ_sertif FLOAT(8,2) NOT NULL comment "Сумма по сертификату",

		device INT UNSIGNED  comment "расписание или аппарат",
		dat DATE NOT NULL  comment "дата записи",

		discount TINYINT UNSIGNED,
		`comment` TEXT NOT NULL comment "Причина скидки",

		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();


// todo может сетрификат=Оплате ?
// status - 0 операция пополнения или списания, не требует действий
// status - 9 счет
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'payment (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		user INT UNSIGNED NOT NULL,
		zakaz INT UNSIGNED NOT NULL,
		summ FLOAT(7,2),
		time DATETIME,
		time_end DATETIME,
		mes TEXT NOT NULL,
		status TINYINT UNSIGNED NOT NULL,
		deliver CHAR(20) NOT NULL,
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();


// приход косметики и расходки
$query='CREATE TABLE IF NOT EXISTS '.db_prefix.'prixod (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		dat DATE NOT NULL,
		tovar INT UNSIGNED NOT NULL,
		kol INT NOT NULL,
		price INT UNSIGNED NOT NULL,
		user INT UNSIGNED NOT NULL,
		best_before DATE NOT NULL comment "Срок годности",
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci';
DB::sql ( $query ) or die("Ошибка (" . DB::error().") в запросе:<br>\n".$query );
print "<br>".DB::$query."<br>".DB::info();



// при вводе неверного пароля сюда добавляется запись и следующий ввод только с capcha
// ip
// reg - 0 ок, 1 - capcha, 9 - ban
// user - id из базы User
//DB::sql('DROP TABLE '.db_prefix.'ban_users');
DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."ban_users (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		ip bigint(20) NOT NULL DEFAULT 0,
		name VARCHAR(32) NOT NULL,
		time DATETIME,
		counter TINYINT UNSIGNED NOT NULL DEFAULT 0,
		ban TINYINT UNSIGNED NOT NULL DEFAULT 0,
		`comment` varchar(128),
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci") or die("Ошибка (" . DB::error().") в запросе:<br>\n".DB::$query );
print "<br>".DB::$query."<br>".DB::info();


// инкассация, пр.закупки и затраты, штрафы
// rej = 1 - инкассация, 2 - закупка хоз.нужд, 3 - выплата аванса, 4 - выплата з.платы,
// 8 - штраф/недостача, 9 - по р/счету,
// 10 - выплата з/п на пластик,
// 20 - сальдо по сотруднику user на начало месяца
// 27 - сальдо в кассе на начало дня
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'incasso (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		time DATETIME NOT NULL,
	        rej TINYINT UNSIGNED NOT NULL,
		summ FLOAT(10,2) NOT NULL,
		user INT UNSIGNED NOT NULL,
		comment TEXT NOT NULL,
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();


// протокол изменений
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'log (
  `tbl` varchar(32) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `user` int(10) unsigned NOT NULL,
  `time` datetime DEFAULT NULL,
  `subject` text NOT NULL,
  `before` text NOT NULL,
  `after` text NOT NULL,
  KEY `id` (`id`)
) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

// SMS
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'sms (
		`id` INT UNSIGNED DEFAULT NULL,
		`message` varchar(255),
		`phone` CHAR(14),
		`cost` FLOAT(7,2),
		`status` INT(11),
		`time` datetime DEFAULT NULL
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();


/*
// справочник аппаратов
$query='CREATE TABLE IF NOT EXISTS '.db_prefix.'device (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name char(32),
		comment TEXT,
		PRIMARY KEY (id)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci';
DB::sql ( $query ) or die("Ошибка (" . DB::error().") в запросе:<br>\n".$query );
print "<br>SQL:".DB::info();

// на каких аппаратах какие услуги возможны
// device = 0 услуга может быть без аппарата
$query='CREATE TABLE IF NOT EXISTS '.db_prefix.'rel (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		device INT UNSIGNED NOT NULL,
		tovar INT UNSIGNED NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (device, tovar)
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci';
DB::sql ( $query ) or die("Ошибка (" . DB::error().") в запросе:<br>\n".$query );
print "<br>SQL:".DB::info();

// Инвентаризация косметики и расходки
$query='CREATE TABLE IF NOT EXISTS '.db_prefix.'invent (
		dat DATE,
		tovar INT UNSIGNED NOT NULL,
		counter INT UNSIGNED NOT NULL
		) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci';
DB::sql ( $query ) or die("Ошибка (" . DB::error().") в запросе:<br>\n".$query );
print "<br>SQL:".DB::info();
*/

if(!DB::is_table('config')) {
    DB::sql('CREATE TABLE IF NOT EXISTS ' . db_prefix . 'config (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` varchar(128) NOT NULL DEFAULT "",
		`key` varchar(32) NOT NULL DEFAULT "",
		`from` DATE NOT NULL,
		`value` varchar(256) NOT NULL DEFAULT "",
		`pattern` varchar(256) NOT NULL DEFAULT "",
		PRIMARY KEY (id),
		UNIQUE (`key`,`from` DESC)
		) DEFAULT CHARACTER SET ' . DB::charset . ' COLLATE ' . DB::charset . '_general_ci');
    print "<br>".DB::$query."<br>".DB::info();
    if (DB::Count('config') < 1) {
/*        DB::sql("INSERT INTO `" . db_prefix . "tovar` ( `id`, `name`, `key`, `from`, `value`, `pattern`) VALUES
	( '1', 'Оклад SunLife','zr_oklad', '2000-10-01',   '8500', '\d*'),
	( '2', 'Время работы со скидкой', 'z2_hour_price1', '2025-10-01 00:00:00',   '00:00-15:59', '\d{2}\:\d{2}\-\d{2}\:\d{2}'),
	");
/*        '10', 'Тренер', 'uADM_TRENER', '2000-10-01',   '10', '\d*'),
	  '11', 'Массажист', 'uADM_MASSAJ', '2000-10-01',   '11', '\d*'),
	  '12', 'Косметолог', 'uADM_KOSMETOLOG', '2000-10-01',   '12', '\d*'),
	  '13', 'Фитнес директор', 'uADM_F_DIREC', '2000-10-01',   '13', '\d*'),
	  '14', 'Мастер шарко', 'uADM_SHARKO', '2000-10-01',   '14', '\d*'),*/

    }
}

// фото новости: /images/NNN.jpg
DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."pages (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`url` varchar(128) NOT NULL DEFAULT '' COMMENT 'url от parent',
	`name` varchar(128) NOT NULL DEFAULT '' COMMENT 'заголовок =h1',
    `parent` int(11) NOT NULL DEFAULT '0',
    `menu_name` varchar(50) DEFAULT NULL COMMENT 'если не задан, используется name',
	`content` TEXT NOT NULL comment 'текст статьи',
    `sort` int(11) NOT NULL DEFAULT '0',
    `title` varchar(256) NOT NULL,
    `description` text comment 'seo',
    `keywords` varchar(256) DEFAULT NULL comment 'seo',
    `services` int(10) NOT NULL DEFAULT '0' comment '1-page, 2-не выводить в меню, 4-не выводить в карту сайта,',
    `date0` DATE NOT NULL COMMENT 'дата публикации',
    `time` DATETIME NOT NULL COMMENT 'последнее изменение',
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci");
print "<br>".DB::$query."<br>".DB::info();


// расписание
DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."schedule (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`manager` int NOT NULL DEFAULT '0' COMMENT 'Преподаватель',
	`pages` int NOT NULL DEFAULT '0' COMMENT 'Описание занятия',
    `tovar` INT UNSIGNED NOT NULL,
    `place` int NOT NULL DEFAULT '0' COMMENT 'Зал',
    `size` int NOT NULL COMMENT 'Количество мест',
    `day` TINYINT( 1 ) NOT NULL COMMENT 'день недели',
    `hour` CHAR(5) NOT NULL COMMENT 'Время начала',
    `hours` INT(2) UNSIGNED NOT NULL COMMENT 'Кол-во часов',
    `date0` DATE NOT NULL COMMENT 'дата начала',
    `date_end` DATE NOT NULL COMMENT 'дата конца',
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci");
print "<br>".DB::$query."<br>".DB::info();


// если ctranzit='_' то в колонке itranzit любой символ кроме 'заказ',call и кроме 0
// valuta=$ или ' 'рубли
// nac - наценка относительно курса ЦБ
// brand - id бренда только если подливается отдельно бренд
// если ctranzit='_' то в колонке itranzit любой символ кроме 'заказ',call и кроме 0
// valuta=$ или ' 'рубли
// nac - наценка относительно курса ЦБ
// brand - id бренда только если подливается отдельно бренд
// ранее называлась supplier
DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."supplier (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL comment 'Имя файла прайса',
  `filename` char(32) DEFAULT '',
  `dat` date DEFAULT NULL,
  `iname` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `iname2` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `iprice` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `itranzit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `itranzit2` tinyint(3) unsigned NOT NULL,
  `itranzit3` tinyint(3) unsigned NOT NULL,
  `ctranzit` char(1) DEFAULT '' comment 'Как трактовать колонку транзит',
  `iost` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ikateg` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nac` tinyint(3) unsigned NOT NULL DEFAULT '0' comment 'Наценка',
  `valuta` char(1) DEFAULT '' comment 'Валюта',
  `pp` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `iprodact` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `brand` int(10) unsigned NOT NULL DEFAULT '0' comment 'Бренд',
  `ibrand` tinyint(3) unsigned NOT NULL,
  `ipriceU` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `itranzit4` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `itranzit5` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sklad` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sklad2` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sklad3` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sklad4` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sklad5` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `igarant` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `gr` int(10) unsigned NOT NULL COMMENT 'товарная группа',
  `prefix` varchar(80) NOT NULL COMMENT 'Префикс к названию товаров',
  `add_brand_to_name` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Добавлять бренд в название товара',
  `brand_in_name` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Бренд отдельной строкой в колонке названия',
  `info` text CHARACTER SET cp1251 COLLATE cp1251_bin COMMENT 'Настройки столбцов',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci");
print "<br>".DB::$query."<br>".DB::info();


DB::sql("CREATE TABLE IF NOT EXISTS ".db_prefix."supplier_link (
		tovar INT UNSIGNED COMMENT 'Товар',
		supplier INT UNSIGNED COMMENT 'Поставщик',
		PRIMARY KEY (tovar,supplier),
		name VARCHAR(255) NOT NULL comment 'как оно называется у данного поставщика',
		price0 FLOAT( 10, 2 ) UNSIGNED NOT NULL comment 'цена закупки у этого поставщика в валюте valuta0',
		valuta0 CHAR(1),
		ost VARCHAR(5) NOT NULL,
		sklad TINYINT UNSIGNED NOT NULL DEFAULT 0,
		dat DATE,
		kod_prodact VARCHAR(40) NOT NULL,
		url VARCHAR(512) NOT NULL comment 'страница с товаром поставщика',
		index (kod_prodact)
		) DEFAULT CHARACTER SET ".DB::charset." COLLATE ".DB::charset."_general_ci");
print "<br>".DB::$query."<br>".DB::info();


// список магазинов
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'shop (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name varchar(128) NOT NULL DEFAULT "",
		address varchar(128) NOT NULL DEFAULT "",
		url varchar(128) NOT NULL DEFAULT "" comment "Сайт",
		tel varchar(64) NOT NULL DEFAULT "",
		`content` text NOT NULL DEFAULT "",
		`db_prefix` VARCHAR( 10 ) NOT NULL DEFAULT "" COMMENT "Префикс БД",
        `terminal` VARCHAR( 14 ) NOT NULL DEFAULT "" COMMENT "платежный терминал",
		PRIMARY KEY (id)
    ) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

// остатки товаров по магазинам
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'tovar_shop (
		tovar INT UNSIGNED NOT NULL,
		shop INT UNSIGNED NOT NULL,
		ost INT NOT NULL,
		`price` INT UNSIGNED NOT NULL DEFAULT "0",
		PRIMARY KEY (`tovar`,`shop`)
    ) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

// остатки товаров по магазинам
DB::sql('CREATE TABLE IF NOT EXISTS '.db_prefix.'blocks (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(128) NOT NULL DEFAULT \'\' COMMENT \'ключ для выбора записи\',
    `name` varchar(128) NOT NULL DEFAULT \'\',
    `content` text NOT NULL DEFAULT \'\' COMMENT \'текст блока\',
    PRIMARY KEY (`id`),
    KEY `key` (`key`)
    ) DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
print "<br>".DB::$query."<br>".DB::info();

DB::sql("INSERT IGNORE INTO `".db_prefix."blocks` (`id`, `key`, `name`) VALUES
(1,	'header',	'header'),
(2,	'footer',	'footer'),
(3,	'breadcrumbs',	'breadcrumbs'),
(4,	'menu',	'menu')");
print "<br>".DB::$query."<br>".DB::info();

if(getenv('REMOTE_ADDR')=='127.0.0.1'){// если я на локале, утащить все с сервера
    foreach(DB::ListTables() as $tbl){
        echo "\n<br>".$tbl;
        Sprav::Download($tbl);
        Out::ErrorAndExit();
    }
}

Cache::_Clear();
