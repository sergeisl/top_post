<?
/*
define("db_prefix","pb_");	// префикс всех БД
if(getenv('REMOTE_ADDR')=='127.0.0.1') {
    define("HostName","localhost");		//  Имя сервера (хост)
    define("DBName","novosel");		// Имя базы данных
    define("UserName","root");	//  Логин
    define("Password","");		//  Пароль
} else {
    define("HostName","127.0.0.1");		// Имя сервера (хост)
    define("DBName","p38263_novosel");	// Имя базы данных
    define("UserName","p38263_novosel");	// Логин
    define("Password","");		// Пароль
}
//include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
*/
//$db=new DB(HostName, DBName, UserName, Password);
//function __construct($host, $dbname, $user,$password){

DB::Connect();
/**
 * Class DB
 * обеспечивает изолированную работу таблиц с разными префиксами
 * @author Дмитрий Колесников <kdg@htmlweb.ru>
 * @version 1.3_mysqli
 */
class DB{
    const db_prefix = db_prefix;
    const charset = 'utf8'; // Кодировка или utf8 cp1251
    static $query='';
    static $link=null;
    static public $debug =0;
    //private $link=null;
    /**
     * @param $host
     * @param $dbname
     * @param $user
     * @param $password
     * @internal param null $options
     */

    static function Connect(){
        if (!self::$link = @mysqli_connect(HostName, UserName, Password, DBName)) {
            for ($i = 1; $i <= 5; $i++) {
                if (!self::$link = @mysqli_connect(HostName, UserName, Password)){
                    usleep(500000*$i); // 0.5 сек
            }else break;
        }
            if ($i > 5) DB::add_sqlerror("Невозможно подключение к MySQL: " . HostName);
            if (!@mysqli_select_db(self::$link, DBName)){
                self::sql('CREATE DATABASE IF NOT EXISTS `'.DBName.'` DEFAULT CHARACTER SET '.DB::charset.' COLLATE '.DB::charset.'_general_ci');
                print "<br>".DB::$query."<br>".DB::info();
                @mysqli_select_db(self::$link, DBName) or self::add_sqlerror("Невозможно открыть: ".DBName); // DB::sql('USE `'.DBName.'`;');
                include_once $_SERVER['DOCUMENT_ROOT'].'/adm/install.php';
            }
        }

        mysqli_set_charset(self::$link, DB::charset);
        mysqli_query(self::$link, "SET NAMES ".self::charset);
        /*if (!@mysql_select_db(DBName)) {
            usleep(500000); // 0.5 сек
            @mysql_select_db(DBName) or DB::add_sqlerror("Невозможно открыть: " . DBName);
        }*/
        //mysql_set_charset(DB::charset);

        //self::sql("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        //self::sql("SET SET time_zone = '+03:00';");
        //select FROM_UNIXTIME(UNIX_TIMESTAMP())

    }
    static function close(){
        mysqli_close(self::$link);
    }

    static function id(){
        return mysqli_insert_id(self::$link);
    }

    static function affected_rows(){
        return mysqli_affected_rows(self::$link);
    }

    static function num_rows($result){
        return ($result?mysqli_num_rows($result):0);
    }

    static function fetch_assoc($result){
        return mysqli_fetch_assoc($result);
    }

    static function fetch_row($result){
        return mysqli_fetch_row($result);
    }

    static function fetch_array($result){
        return mysqli_fetch_array($result);
    }

    static function add_sqlerror($err)
    {
        if (!headers_sent())header("HTTP/1.0 503 Service Unavailable");
        // todo сделать автовосстановление таблиц при повреждении !!!
        //Table 'page' is marked as crashed and should be repaired
        $err = date("d.m.Y H:i") . "\n" . $err . "\ninfo:" . @mysqli_info(self::$link) . "\nerror:" . @mysqli_error(self::$link) . "\nСтек: ".var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),!0)."\n\n"; //."\n\nПроцессы:";
        //$file_error='/log/sql_error.txt';
        //file_put_contents($_SERVER['DOCUMENT_ROOT'].$file_error, $err, FILE_APPEND);
        AddToLog( $err, 'SQL error report');
        die( "<b>Произошла ошибка SQL.</b> Администратор оповещен!\n");
    }

    static function sql($query)
    {
        self::$query=$query;
        $res = mysqli_query(self::$link, $query);
        $err=mysqli_error(self::$link);
        if($err){
            if (stripos($err,'gone away')!==false ||
                (stripos($err, 'Lost connection to MySQL server during query')!==false) ||
                (stripos($err, 'Error while sending QUERY packet')!==false)
		){// попытаюсь восстановить соединение!
                if (!mysqli_ping(self::$link)) {
                self::close();
                    usleep(2000000); // 2 сек
                self::Connect();
            }
                mysqli_query(self::$link, "SET NAMES ".self::charset);
                $res = mysqli_query(self::$link, $query);
                $err=mysqli_error(self::$link);
            }elseif(preg_match("/Table .* doesn\'t exist/i",$err)){
                include_once $_SERVER['DOCUMENT_ROOT'].'/adm/install.php';
                $res = mysqli_query(self::$link, $query);
                $err=mysqli_error(self::$link);
            }
        }
        // todo if($warning=mysqli_get_warnings())
        if(self::$debug>0)file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sql.log', "\n\n".date("d-m-Y H:i:s").' '."\n".$query./*"\nОтвет:\n".var_export($res,!0).*/
            (mysqli_info(self::$link)?"\nInfo:\n".mysqli_info(self::$link):'').
            ($err?"\nError:".$err:'').
            (mysqli_insert_id(self::$link)?"\ninsert_id:".mysqli_insert_id(self::$link):'').
            "\ncount:".(strtoupper(substr(DB::$query,0,4))=='SELE'?($res?mysqli_num_rows($res):0):mysqli_affected_rows(self::$link)), FILE_APPEND);
        /*if(in_array(strtoupper(substr(DB::$query,0,4), ['DELE','INSE','UPDA'])){
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/geo.sql', "\r\n".DB::$query.";", FILE_APPEND|LOCK_EX);
        }*/
        if(!$res)self::add_sqlerror("Запрос:\n".$query."\n");
        return $res;
    }

    static function GetName($tbl, $id){ // todo при перезаписи обновлять кеш
        if(!$id)return '';
        $row=self::Select($tbl, $id);
        return ( $row ? $row["name"] : (User::is_admin()?'нет:'.$id:'') );
    }

    /**
     * @param $tbl
     * @param $where
     * @param $field
     * @return null
     */
    static function Get($tbl, $where, $field){
        $row=self::Select($tbl, $where);
        return ( $row ? $row[$field] : null );
    }

    /** Выбрать одну запись из БД
     * @param string $tbl
     * @param int|string $where - id или условие
     * @param null|string $prefix - префикс таблицы
     * @return array
     */
    static function Select($tbl, $where, $prefix=null){
        // ctype_digit - для корректной проверки всегда преобразую where в строку
        if(ctype_digit((string)$where) || strlen($where)<9 && (strpos($where,'=')===false && strpos($where,'<')===false && strpos($where,'>')===false)){
            if(!isset($GLOBALS[$tbl.'_cash['.$where.']'])){ // кеширую в массив
                $GLOBALS[$tbl.'_cash['.$where.']']=self::Select($tbl, 'id="'.$where.'"');
            }
            return $GLOBALS[$tbl.'_cash['.$where.']'];
        }
        return mysqli_fetch_assoc(self::sql('SELECT * FROM '.(is_null($prefix) ? self::db_prefix : $prefix) .$tbl.' WHERE '.$where.' LIMIT 1'));
    }

    static function Count($tbl, $where='', $field='*'){
        $c=self::sql('SELECT count('.$field.') as c FROM '.self::db_prefix.$tbl.($where?' WHERE '.$where:''));
        if($c)$c=mysqli_fetch_assoc($c);
        return ($c?$c['c']:0);
    }

    static function Sum($tbl, $where='', $field){
        $c=mysqli_fetch_assoc(self::sql('SELECT SUM('.$field.') as c FROM '.self::db_prefix.$tbl.($where?' WHERE '.$where:'')));
        return ($c?$c['c']:0);
    }

    /** Удалить в БД
     * @param string $tbl
     * @param int|string $where - id или условие
     * @param null|string $prefix - префикс таблицы
     * @return array
     */
    static function Delete($tbl, $where='', $prefix=null){
        if(ctype_digit((string)$where) || strlen($where)<9 && (strpos($where,'=')===false && strpos($where,'<')===false && strpos($where,'>')===false)){
            self::CacheClear($tbl,$where);
            $where="id='".$where."' LIMIT 1";
        }
        self::sql("DELETE FROM ".(is_null($prefix) ? self::db_prefix : $prefix).$tbl.($where?' WHERE '.$where:''));
        return mysqli_affected_rows(self::$link);
    }

    /** Очистка кеша
     * @param string $tbl
     * @param integer $where
     */
    static function CacheClear($tbl, $where){
        if(isset($GLOBALS[$tbl.'_cash['.$where.']'])){ // сбрасываю кеш
            unset($GLOBALS[$tbl.'_cash['.$where.']']);
        }
    }

    /**
     * @param string $tbl
     * @param string $where
     * @param string $format - '' - все поля, 'id&name' - id=>name, 'id' - id=>row
     * @param null|string $prefix - префикс таблицы
     * @return array(array)|array(id=>name)
     */
    static function Select2Array($tbl, $where='', $format='', $prefix=null){
        $query = self::sql('SELECT * FROM ' .(is_null($prefix) ? self::db_prefix : $prefix). $tbl . ($where ? ' WHERE ' . $where : ''));
        $res=[];
        while (($row = mysqli_fetch_assoc($query))) if ($format == 'id&name') {
            $res[$row['id']] = $row['name'];
        } elseif ($format == 'id') {
            $res[$row['id']] = $row;
        } else {
            $res[] = $row;
        }
        return $res;
    }

    /** возвращает список id записей, соответствующих условию через запятую
     * @param string $tbl
     * @param string $where
     * @param string $field - поле таблицы
     * @param null|string $prefix - префикс таблицы
     * @return string
     */
    static function SelectId($tbl, $where='', $field='id', $prefix=null){
        $query=self::sql('SELECT GROUP_CONCAT(DISTINCT '.$field.' SEPARATOR ",") AS plan_id FROM '.(is_null($prefix) ? self::db_prefix : $prefix) . $tbl.($where?' WHERE '.$where : '' ));
        return (($row=mysqli_fetch_assoc($query) )? $row['plan_id'] : '' );
    }

    /** Выполняет переданный Select запрос и возвращает массив результатов
     * @param $sql
     * @return array
     */
    static function SelectSql($sql)
    {
        $result=self::sql($sql);
        $res = [];
        while ($row = mysqli_fetch_assoc($result)) $res[] = $row;
        return $res;
    }


    /** получить id только что добавленной записи и по возможности переместить её в свободный id
     * @param $tbl
     * @return int|string
     */
    static function GetInsertId($tbl)
    {
        global $insert_id;
        $id0 = mysqli_insert_id(self::$link);
        if ($id0 < 1) return $id0;
        $id_from=(isset($insert_id[$tbl])?$insert_id[$tbl]:1);

        for($id=$id_from; $id<$id0; $id++){
            $result = self::sql('SELECT id from ' . self::db_prefix . $tbl . ' WHERE id=' . $id . ' LIMIT 1');
            if (mysqli_num_rows($result) == 0) {
                self::sql('UPDATE IGNORE ' . self::db_prefix . $tbl . ' SET id=' . $id . ' WHERE id=' . $id0 . ' LIMIT 1');
                if (mysqli_affected_rows(self::$link)>0) { // никто не успел занять
                    self::CacheClear($tbl, $id0);
                    $insert_id[$tbl] = $id + 1;
                    return $id;    // id - свободен
                }
            }
        }
        $insert_id[$tbl]=$id;
        return $id0;
    }

static function nextId($tbl){
        if ($row = mysqli_fetch_assoc(self::sql("SHOW TABLE STATUS LIKE '" . self::db_prefix . $tbl . "'"))) return intval($row['Auto_increment']);
    return 0;
}

    static function is_table($tbl='', $prefix=null){
        return mysqli_num_rows(mysqli_query(self::$link, "SHOW TABLES LIKE '".(is_null($prefix) ? self::db_prefix : $prefix).$tbl . "'")) > 0;
    }

    /** Возвращает массив, если таблица содержит поле или null
     * @param $tbl
     * @param $field
     * @return array|null
     */
    static function is_field($tbl, $field){
        $result = mysqli_query(self::$link, "SHOW COLUMNS FROM `" . self::db_prefix . $tbl . "`");
        if($result) while ($col = mysqli_fetch_assoc($result))if($col['Field']==$field)return $col;
        return null;
    }

    /*
     * возвращает список всех таблиц без префикса в текущей БД, содержащих поле field
     * возвращаются только таблицы, начинающиеся на db_prefix
     * @param string|array $field, если fields='' возвращается массив всех таблиц
     * @return array
     */
    static function ListTables($field=''){
        $r = mysqli_query(self::$link, "SHOW TABLES");
        $ar=[];
        if($field && !is_array($field))$field=[$field];
            while ($row = mysqli_fetch_row($r))
                if (substr($row[0], 0, strlen(self::db_prefix)) == self::db_prefix) {
                    $tbl = substr($row[0], strlen(self::db_prefix));
                    if (!$field) {
                        $ar[] = $tbl;
                        continue;
                    }
                    $result = mysqli_query(self::$link, "SHOW COLUMNS FROM `{$row[0]}`");
                    while ($col = mysqli_fetch_assoc($result))
                        if (in_array($col['Field'], $field)) {
                            $ar[] = $tbl;
                            break;
                     }
              }
        return $ar;
    }

    /** Возвращает список полей таблицы
     * @param $tbl
     * @return array
    [Field] => name
    [Type] => varchar(32)
    [Null] => NO
    [Key] => UNI
    [Default] =>
    [Extra] =>
 */
    static function ListFields($tbl, $prefix=null){
        $ar=[];
        $result = mysqli_query(self::$link, "SHOW COLUMNS FROM `" .(is_null($prefix) ? self::db_prefix : $prefix) . $tbl . "`");
        while ($col = mysqli_fetch_assoc($result))
            $ar[]=$col;
        return $ar;
    }

    /**
     * @param $tbl
     * @return array|null
        [Name] => pb_abuse
        [Engine] => InnoDB
        [Version] => 10
        [Row_format] => Compact
        [Rows] => 6
        [Avg_row_length] => 2730
        [Data_length] => 16384
        [Max_data_length] => 0
        [Index_length] => 0
        [Data_free] => 213909504
        [Auto_increment] => 7
        [Create_time] => 2013-07-19 21:17:42
        [Update_time] =>
        [Check_time] =>
        [Collation] => cp1251_bin
        [Checksum] =>
        [Create_options] =>
        [Comment] =>
*/
    static function ShowTableStatus($tbl){
        $ar = mysqli_fetch_assoc(self::sql("SHOW TABLE STATUS LIKE '" . self::db_prefix . $tbl . "'"));
        //echo "<br>".nl2br(print_r($ar,!0));
    return $ar;

}

    static function info(){
        return mysqli_info(self::$link);
    }

static function Optimize(){
        $r = mysqli_query(self::$link, "SHOW TABLES");
    $q = "LOCK TABLES";
    $table=[];
        while ($row = mysqli_fetch_row($r)) {
        $table[] = $row[0];
        $q .= " " . $row[0]." WRITE,";
    }
    $q = substr($q,0,-1);
        mysqli_query(self::$link, $q);

    print "\nБаза данных заблокированна для чтения/записи.\n";

        foreach ($table as $value) {
        $q = "OPTIMIZE TABLE ".$value;
        print $q."\n"; //flush();
            if (!mysqli_query(self::$link, $q)) echo "QUERY: \"$q\" " . mysqli_error(self::$link) . "\n\n";
    }
        mysqli_query(self::$link, "UNLOCK TABLES");
    print "База данных оптимизированна и разблокированна.\n";


        print "\nУспешно завершено!" . mysqli_info(self::$link) . "\n";
}

    /** функция записывает данные в таблицу
     * если id нет или =0 будет добавлена запись
     * функция маскирует недопустимые символы, учитывает get_magic_quotes_gpc
     * если utf=true, то производится преобразование из UTF-8 в win-1251
     */
    static function write_array($tbl, $arr, $utf = false)
    {
        global $tbl_columns; // кеширую структуру таблицы
        if (!is_array($tbl_columns)) $tbl_columns = [];
        if (empty($tbl_columns[$tbl])) {
            $result = mysqli_query(self::$link, "SHOW COLUMNS FROM `" . self::db_prefix . $tbl . "`");
            while ($col = mysqli_fetch_row($result)) $tbl_columns[$tbl][$col[0]] = $col[1]; // индекс - имя, значение - тип
        }
        //if (!empty($GLOBALS['DEBUG'])) @file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sql.log', "\n\n" . "\n" . var_export($arr, !0), FILE_APPEND);
        foreach ($tbl_columns[$tbl] as $key => $value) {
            if (isset($arr[$key])) {
                if (strpos($value, 'char') || strpos($value, 'text') !== false) {
                    if ($utf) $arr[$key] = trim(@iconv("UTF-8", "windows-1251//IGNORE", $arr[$key]));
                } elseif (strpos($value, 'int') !== false){
                    $arr[$key] = intval($arr[$key]);
                    continue;
                }elseif (strpos($value, 'time') !== false) $arr[$key] = date("Y-m-d H:i:s", (is_string($arr[$key]) ? strtotime($arr[$key]) : $arr[$key]));
                elseif (strpos($value, 'date') !== false) $arr[$key] = date("Y-m-d", (is_string($arr[$key]) ? strtotime($arr[$key]) : $arr[$key]));
                elseif (strpos($value, 'floatval') !== false) $arr[$key] = str_replace(',', '.', floatval($arr[$key]));
                elseif ( (strpos($value, 'bit') !== false) && is_array($arr[$key])){
                    $arr[$key]=Convert::Arry2Bit($arr[$key]);
                    continue;
                }
                $arr[$key]='"' . addslashes($arr[$key]) . '"';
            }
        }
        //if(User::id()==1) @file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sql.log', "\n\n" . "\n" . var_export($arr, !0), FILE_APPEND);
        $add = '';
        //var_dump($arr);
        if (isset($arr['id']) && intval($arr['id']) > 0) {
            foreach ($tbl_columns[$tbl] as $key => $value) {
//echo "<br>".$key.'~'.$value;
                if ($key == 'id') {
                } elseif (isset($arr[$key])) {
                    $add .= ',' . $key . '=' . $arr[$key];
                    //echo '~'.$arr[$key];
                }
            }
            //var_dump($add);
            if ($add) $add = 'UPDATE ' . self::db_prefix . $tbl . ' SET ' . substr($add, 1) . ' WHERE id="' . intval($arr['id']) . '"';
            else return false;
        } else {
            $add1 = $add2 = '';
            foreach ($tbl_columns[$tbl] as $key => $value) {
                if ($key == 'id') {
                    continue;
                } elseif (isset($arr[$key])) {
                    // ok
                }elseif ($key == 'user' && User::id()) {
                    $arr[$key] = User::id();
                }else continue;
                $add .= ',' . $key . '=' . $arr[$key];
                $add1 .= ',' . $key;
                $add2 .= ',' . $arr[$key] ;
            }
            if ($add) $add = 'INSERT INTO ' . self::db_prefix . $tbl . ' (' . substr($add1, 1) . ')'.
                ' VALUES (' . substr($add2, 1) . ')'.
                ' ON DUPLICATE KEY UPDATE ' . substr($add, 1);
            else return false;
        }
        //if(User::id()==1)@file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sql.log', "\n\n" .$add, FILE_APPEND);
        self::sql($add);
        return mysqli_affected_rows(self::$link) > 0;
    }


    /**
     * вставка записи в таблицу
     * @param $tbl
     * @param array $_fields
     * @param array $_upd_fields, типы полей integer, double, string, null, array( будет преобразован js_encode )
     * @param bool $_debug
     * @return bool
     * Запись в базу только полей, содержащихся в базе
        $fields=array_flip([('id','time','user']);
        DB::insert('sms', array_intersect_key($ar, $fields)  );
     */
    static function insert($tbl, array $_fields, $_upd_fields = null, $_debug = false)
    {
        if ($tbl[0]=='!') {
            $ignore = ' IGNORE';
            $tbl = ltrim($tbl, '!');
        }else $ignore = '';

        // собираем строку запроса
        $sql = "INSERT{$ignore} INTO `". self::db_prefix.$tbl ."` ( `" . (implode("`, `", array_keys($_fields))) . "`)\n\t VALUES (" . implode(", ", array_map(
                    function($v) { return (is_null($v) ? 'null' : "'" . (is_double($v)?str_replace(',','.',floatval($v)):addslashes((is_array($v)?json_encode($v):$v))) . "'"); },
                    $_fields)
            ) . ")";

        if (!empty($_upd_fields)) {
            if (is_array($_upd_fields)) {
                if (isAssoc($_upd_fields)) {
                    $tmp = [];
                    foreach ($_upd_fields as $k => $v) {
                        $tmp[] = '`'.$k.'` = '.
                            (is_null($v)
                                ? 'NULL'
                                : "'" . (is_double($v)?str_replace(',','.',floatval($v)):
                                    addslashes( (is_array($v)?js_encode($v):$v) )
                                ) . "'");
                    }
                    $sql .= "\n\t ON DUPLICATE KEY UPDATE " . join(', ', $tmp);
                } else {
                    $sql .= "\n\t ON DUPLICATE KEY UPDATE " . join(', ', $_upd_fields);
                }
            } else {
                $sql .= "\n\t ON DUPLICATE KEY UPDATE " . strval($_upd_fields);
            }
        }

        self::sql($sql);
        //$this->last_error = $mysql_error = mysql_error($this->rs);
        //$this->last_error_no = mysql_errno($this->rs);

        return (mb_strlen(mysqli_error(self::$link)) ==0 );
    }

    /**
     * Изменение записей в таблице
     * @param string $tbl - название таблицы
     * @param array $_fields    =array_intersect_key($_fields, array_flip(array('id','time',...)));
     * @param mixed $_where
     * @param bool $_debug = false
     * @return bool
     **/
    static function update($tbl, $_fields, $_where=' 1 ', $_debug=false)
    {
        if(ctype_digit((string)$_where) || strlen($_where)<9 && (strpos($_where,'=')===false && strpos($_where,'<')===false && strpos($_where,'>')===false)) {
            $_where = 'id = ' . $_where. ' LIMIT 1';
        } elseif (is_array($_where)) {
            if (isAssoc($_where)) {
                $tmp = [];
                foreach ($_where as $k => $v) {
                    // (is_null($v) ? 'null' : "'" . (is_double($v)?str_replace(',','.',floatval($v)):addslashes($v)) . "'")
                    if (is_null($v)) {
                        $v = 'IS NULL';
                    } elseif (is_array($v)) {
                        $v = "in ('" . join("','", array_map('addslashes', $v)) . "')";
                    } elseif (is_double($v)){
                        $v = "= '" . str_replace(',','.',$v) . "'";
                    } else {
                        $v = "= '" . addslashes($v) . "'";
                    }
                    $tmp[] = $k . ' ' . $v;
                }
                $_where = join(' AND ', $tmp);
            } else {
                $_where = join(' AND ', $_where);
            }
        } else {
            $_where = strval($_where);
        }

        if (is_array($_fields)) {
            // собираем строку запроса
            if (isAssoc($_fields)) {
                $tmp = [];
                foreach ($_fields as $k => $v){
                    $tmp[] = '`'.$k.'` = '.
                        (is_null($v) ?
                            'NULL' :
                            "'" . (is_double($v)?str_replace(',','.',$v):
                                addslashes( (is_array($v)?js_encode($v):$v) )
                            ) . "'");
                }
                $sql = join(', ', $tmp);
            } else {
                $sql = join(', ', $_fields);
            }

        } else {
            $sql = strval($_fields);
        }
        $sql = "UPDATE `". self::db_prefix.$tbl ."` SET " . $sql . "\n\t WHERE " . $_where;

        self::sql($sql);
        return (mb_strlen(mysqli_error(self::$link)) ==0 );
    }

    static function escape($str){
        return addslashes($str); //return mysql_real_escape_string($str);
    }

    static function error(){
        return mysqli_error(self::$link);
    }

    static function float($a){ return str_replace(',', '.', floatval($a));}


    //_log('idea', $id, 'удаление', $idea['title']);
//_log('idea', $id, 'status', $idea['status'], $status);
//_log('users', $id, '', '', $_POST);

    static function log($tbl, $id, $subject, $before='', $after=''){
        if(!defined('DBlogAll'))return;
        if($tbl && $id && empty($before)){
        $before=$before1=DB::Select($tbl,intval($id));
            if(is_array($after)){
                $before1=$before;
                foreach($before as $key => $value)
                    if(!isset($after[$key])||$value==$after[$key] || (empty($value)&&empty($after[$key])))unset($before1[$key]);
                $before=$before1;
            }
        }
        if(is_array($before)&&is_array($after)){
            $before1=$before; $after1=$after;
            unset($after1['screen'],$after1['referer']);
            if(!empty($after1['id'])&&$id==$after1['id'])unset($after1['id']);
            foreach($before as $key => $value)
                if(isset($after[$key]) && ($value==$after[$key] || empty($value)&&empty($after[$key])))unset($before1[$key], $after1[$key]); // null==0==''
            $before=js_encode($before1);
            $after=js_encode($after1);
            unset($before1, $after1);
        }elseif(is_array($before)){
            $before1=$before;
            foreach($before as $key => $value) // удалить нулевые значения
                if(empty($value)||$value==='0.00')unset($before1[$key]);
            $before=js_encode($before1);
        }elseif(is_array($after)){
            foreach($after as $key => $value) // удалить нулевые значения
                if(empty($value)||$value==='0.00')unset($after[$key]);
            $after=js_encode($after);
        }
        DB::sql("INSERT INTO ".db_prefix."log ( `tbl`, `id`, `user`, `time`, `subject`, `before`, `after`)
			VALUES ('".$tbl."', '".$id."', '".$_SESSION["user"]["id"]."', '".date("Y-m-d H:i:s")."', '".addslashes($subject)."', '".addslashes($before)."', '".addslashes($after)."')");
    }

}

/**
 * Является ли массив ассоциативным?
 *
 * @param array $arr Массив
 * @return boolean
 */
function isAssoc(array $arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}


/*
print_r(DB::ListTables('user'));
echo "<br>".nl2br(print_r(DB::ListFields('users'),!0));
echo "<br>".nl2br(print_r(DB::Info('users'),!0));
*/

