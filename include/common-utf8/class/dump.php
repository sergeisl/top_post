<?php

// Ограничение размера данных доставаемых за одно обращения к БД (в мегабайтах)
// Нужно для ограничения количества памяти пожираемой сервером при дампе очень объемных таблиц
define('LIMIT', 1);

// Кодировка соединения с MySQL
// auto - автоматический выбор (устанавливается кодировка таблицы), cp1251 - windows-1251, и т.п.
define('CHARSET', 'auto');
// Кодировка соединения с MySQL при восстановлении
// На случай переноса со старых версий MySQL (до 4.1), у которых не указана кодировка таблиц в дампе
// При добавлении 'forced->', к примеру 'forced->cp1251', кодировка таблиц при восстановлении будет принудительно заменена на cp1251
// Можно также указывать сравнение нужное к примеру 'cp1251_ukrainian_ci' или 'forced->cp1251_ukrainian_ci'
define('RESTORE_CHARSET', '');

set_time_limit(600); // Максимальное время выполнения скрипта в секундах, 0 - без ограничений

//$timer = array_sum(explode(' ', microtime()));
/**
 * Class dump
 * @ver 1.1
 */
class dump
{
    public $SET=[];
    public $dbname = DBName;
    public $tables = []; // список таблиц для сохранения, если не задан, то все
    public $only_create=[];  // список таблиц, для которых выгружается только структура
    public $no_drop=[];  // список таблиц, для которых не делать DROP
    public $where=[];  // условия выборки отдельных таблиц в формате 'tbl_name'=>'id>100'
    public $fields=[];  // список полей, которые нужно выгрузить 'tbl_name'=>'id,name';
    public $comp_method = 1;
    public $comp_level = 9;
    public $path = '';      // папка, куда сложить бэкапы $_SERVER['DOCUMENT_ROOT']."/user/adm/backup/"
    public $debug = 1;
    public $tabs = 0;
    public $size = 0;
    public $filename='';    // по умолчанию 'dump_' . date("Y-m-d_H-i") расширение зависит от сжатия - $comp_level
    public $charset='';     // cp1251
    public $prefix='';      // префикс таблиц
    private $forced_charset = false;
    private $restore_charset = '';
    private $restore_collate = '';
    private $last_charset='';
    private $fp=null;

    function __construct($options=false)
    {
        $this->path = dirname(__FILE__);

        if(is_array($options))foreach($options as $k=>$v)$this->{$k}=$v;

        // Версия MySQL вида 40101
        preg_match('/^(\d+)\.(\d+)\.(\d+)/', mysqli_get_server_info(DB::$link), $m);

        if (preg_match('/^(forced->)?(([a-z0-9]+)(\_\w+)?)$/', RESTORE_CHARSET, $matches)) {
            $this->forced_charset = $matches[1] == 'forced->';
            $this->restore_charset = $matches[3];
            $this->restore_collate = !empty($matches[4]) ? ' COLLATE ' . $matches[2] : '';
        }
    }

    function __destruct(){
        if($this->fp)$this->close($this->fp);
    }

    function backup($options=false)
    {
        if(is_array($options))foreach($options as $k=>$v)$this->{$k}=$v;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777,!0) || error("Не удалось создать каталог для бекапа: " . $this->path);
        }
        if(substr($this->path,-1,1)!=='/')$this->path.='/';

        if ($this->comp_level == 0) $this->comp_method = 0;

        if(empty($this->tables)){
            $this->tables = [];
            $all=1;
        }else{
            $all=0;
            $this->tables=array_unique($this->tables);
        }
        if($this->prefix){
            if($this->tables)foreach($this->tables as $k=>$val)$this->tables[$k]=$this->prefix.$val;
            if($this->only_create)foreach($this->only_create as $k=>$val)$this->only_create[$k]=$this->prefix.$val;
            if($this->no_drop)foreach($this->no_drop as $k=>$val)$this->no_drop[$k]=$this->prefix.$val;
            if($this->where)foreach($this->where as $k=>$val){
                $this->where[$this->prefix.$k]=$val; unset($this->where[$k]);
            }
            if($this->fields)foreach($this->fields as $k=>$val){
                $this->fields[$this->prefix.$k]=$val; unset($this->fields[$k]);
            }
        }

        // Определение размеров таблиц
        $result = DB::sql("SHOW TABLE STATUS"); if(!$result)die('Error SHOW TABLE STATUS '.var_export(DB::info(),!0));
        $tabinfo = [];
        $tab_charset = [];
        $tabsize=[];
        $tabinfo[0] = 0;
        $info = '';
        while ($item = DB::fetch_assoc($result)) {
            if($all)$this->tables[] = $item['Name'];
            if (in_array($item['Name'], $this->tables)) {
                $item['Rows'] = empty($item['Rows']) ? 0 : $item['Rows'];
                $tabinfo[0] += $item['Rows'];
                $tabinfo[$item['Name']] = $item['Rows'];
                $this->size += $item['Data_length'];
                $tabsize[$item['Name']] = 1 + round(LIMIT * 1048576 / ($item['Avg_row_length'] + 1));
                if ($item['Rows']) $info .= "|" . $item['Rows'];
                if (!empty($item['Collation']) && preg_match("/^([a-z0-9]+)_/i", $item['Collation'], $m))
                    $tab_charset[$item['Name']] = $m[1];
            }
        }
        $info = $tabinfo[0] . $info;
        $name = (empty($this->filename) ? 'dump_' . date("Y-m-d_H-i") : $this->filename );
        if($this->fp)$this->fn_write("\n\n");
        else $this->fp = $this->fn_open($name, "w");

        $this->fn_write("#SKD101|{$this->dbname}|".count($this->tables)."|" . date("Y.m.d H:i:s") . "|{$info}\n\n");

        DB::sql("SET SQL_QUOTE_SHOW_CREATE = 1"); //  SHOW CREATE TABLE будет заключать в кавычки имена таблиц и столбцов.

        $this->last_charset= ( CHARSET != 'auto' ? CHARSET : '' );

        foreach ($this->tables AS $table) {
            if(!isset($tab_charset[$table]))die('Нет таблицы '.$table);
            if ($tab_charset[$table] != $this->last_charset) {
                if (CHARSET == 'auto') $this->last_charset = $tab_charset[$table];
            }
            // Создание таблицы
            $tab = DB::fetch_array(DB::sql("SHOW CREATE TABLE `{$table}`"));
            $tab = preg_replace('/(default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP|DEFAULT CHARSET=\w+|COLLATE=\w+|character set \w+|collate \w+)/i', '/*!40101 '.(empty($this->charset)?'\\1':$this->charset).' */', $tab);
            if(!in_array($table,$this->no_drop))$tab[1]="DROP TABLE IF EXISTS `{$table}`;\n".$tab[1];
            else $tab[1]=str_ireplace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$tab[1]);
            $this->fn_write($tab[1].";\n\n");
            // Проверяем нужно ли дампить данные
            if(!in_array($table, $this->only_create)){
                // Опредеделяем типы столбцов
                $NumericColumn = [];
                $result = DB::sql("SHOW COLUMNS FROM `{$table}`");
                while ($col = DB::fetch_row($result)) if(preg_match('/^(\w*int|year)/', $col[1])) $NumericColumn[]=$col[0];
                if ($tabinfo[$table] > 0) {
                    $from = 0;
                    $limit = $tabsize[$table];
                    while (($result = DB::sql("select ".(empty($this->fields[$table])?'*':$this->fields[$table])." FROM `{$table}`".(empty($this->where[$table])?'': ' WHERE '.$this->where[$table])." LIMIT {$from}, {$limit}")) && ($total = DB::num_rows($result))) {
                        while ($row = DB::fetch_assoc($result)) {
                            $sql = "INSERT INTO `".$table."`".(empty($this->fields[$table])?'':" (`" . (implode("`,`", array_keys($row))) . "`)").
                                   " VALUES (" . implode(",", array_map(
                                        function($v) { return (is_null($v) ? 'null' : "'" . addslashes($v) . "'"); },
                                        $row)
                                ) . ");\n";
                            $this->fn_write($sql);
                        }
                        if ($total < $limit) break;
                        $from += $limit;
                    }
                    $this->fn_write("\n");
                }
            }
        }
    }

    function restore()
    {

        $file = $this->filename;

        // Определение формата файла
        if (preg_match('/^(.+?)\.sql(\.(bz2|gz))?$/', $file, $matches)) {
            if (isset($matches[3]) && $matches[3] == 'bz2') $this->comp_method = 2;
            elseif (isset($matches[2]) && $matches[3] == 'gz') $this->comp_method = 1;
            else    $this->comp_method = 0;
            $this->comp_level = '';
            if (!file_exists($this->path . $file)) return Out::error("Нет файла ".$this->path.$file);
            $file = $matches[1];
        } else    exit;
        $this->fp = $this->fn_open($file, "r");
        $this->file_cache = $sql = $table = $insert = '';
        $is_skd = $query_len = $execute = $q = $t = $i = $aff_rows = 0;
        $tabs = 0;

        // Установка кодировки соединения
        if (CHARSET != 'auto' || $this->forced_charset) $last_charset = $this->restore_charset;
        else    $last_charset = '';
        $last_showed = '';
        while(($str = $this->fn_read_str()) !== false){
            if (empty($str) || preg_match("/^(#|--)/", $str)) {
                if (!$is_skd && preg_match('/^#SKD101\|/', $str)) {
                    $is_skd = 1;
                }
                continue;
            }
            $query_len += strlen($str);

            $str=preg_replace("/^INSERT INTO `/i","INSERT IGNORE INTO `",$str);
            if (!$insert && preg_match("/^(INSERT INTO `?([^` ]+)`? .*?VALUES)(.*)$/i", $str, $m)) {
                if ($table != $m[2]) {
                    $table = $m[2];
                    $tabs++;
                    $last_showed = $table;
                    $i = 0;
                }
                $insert = $m[1] . ' ';
                $sql .= $m[3];
            } else {
                $sql .= $str;
                if ($insert) {
                    $i++;
                    $t++;
                }
            }
            $str=preg_replace("/^CREATE TABLE `/i","CREATE TABLE IF NOT EXISTS `",$str);
            if (!$insert && preg_match("/^CREATE TABLE (IF NOT EXISTS )?`?([^` ]+)`?/i", $str, $m) && $table != $m[2]) {
                $table = $m[2];
                $insert = '';
                $tabs++;
                $i = 0;
                echo "<br>".$tabs.". Загружаю ".$table;
            }
            if ($sql) {
                if (preg_match("/;$/", $str)) {
                    $sql = rtrim($insert . $sql, ";");
                    if (empty($insert)) {
                        if (preg_match('/CREATE TABLE/i', $sql)) {
                            if (preg_match('/(CHARACTER SET|CHARSET)[=\s]+(\w+)/i', $sql, $charset)) {
                                if (!$this->forced_charset && $charset[2] != $last_charset) {
                                    if (CHARSET == 'auto') $last_charset = $charset[2];
                                }

                                if ($this->forced_charset) {
                                    $sql = preg_replace('/(\/\*!\d+\s)?((COLLATE)[=\s]+)\w+(\s+\*\/)?/i', '', $sql);
                                    $sql = preg_replace('/((CHARACTER SET|CHARSET)[=\s]+)\w+/i', "\\1" . $this->restore_charset . $this->restore_collate, $sql);
                                }
                            } elseif (CHARSET == 'auto') {
                                $sql .= ' DEFAULT CHARSET=' . $this->restore_charset . $this->restore_collate;
                                if ($this->restore_charset != $last_charset) $last_charset = $this->restore_charset;
                            }
                        }
                        if ($last_showed != $table) {
                            $last_showed = $table;
                        }
                    } elseif (empty($last_charset)) $last_charset = $this->restore_charset;
                    $insert = '';
                    $execute = 1;
                }
                if ($query_len >= 65536 && preg_match("/,$/", $str)) {
                    $sql = rtrim($insert . $sql, ",");
                    $execute = 1;
                }
                if ($execute) {
                    $q++;
                    DB::sql($sql) or trigger_error("Неправильный запрос.<BR>" . DB::error(), E_USER_ERROR);
                    if (preg_match("/^insert/i", $sql)) {
                        $aff_rows += DB::affected_rows();
                    }
                    $sql = '';
                    $query_len = 0;
                    $execute = 0;
                }
            }
        }

        $this->close($this->fp);
        return $aff_rows;
    }

    function fn_open($name, $mode)
    {

        if ($this->comp_method == 2) {
            $this->filename = "{$name}.sql.bz2";
            return bzopen($this->path . $this->filename, "{$mode}b{$this->comp_level}");
        } elseif ($this->comp_method == 1) {
            $this->filename = "{$name}.sql.gz";
            return gzopen($this->path . $this->filename, "{$mode}b{$this->comp_level}");
        } else {
            $this->filename = "{$name}.sql";
            return fopen($this->path . $this->filename, "{$mode}b");
        }
    }

    function fn_write($str)
    {
        if(!empty($this->charset)&&$this->charset!=$this->last_charset)$str=iconv($this->last_charset,($this->charset=='utf8'?'utf-8':$this->charset).'//IGNORE',$str);
        if ($this->comp_method == 2) {
            bzwrite($this->fp, $str);
        } elseif ($this->comp_method == 1) {
            gzwrite($this->fp, $str);
        } else {
            fwrite($this->fp, $str);
        }
    }

    function fn_read()
    {
        if ($this->comp_method == 2) {
            return bzread($this->fp, 4096);
        } elseif ($this->comp_method == 1) {
            return gzread($this->fp, 4096);
        } else {
            return fread($this->fp, 4096);
        }
    }

    function fn_read_str()
    {
        $string = '';
        $this->file_cache = ltrim($this->file_cache);
        $pos = strpos($this->file_cache, "\n", 0);
        if ($pos < 1) {
            while (!$string && ($str = $this->fn_read($this->fp))) {
                $pos = strpos($str, "\n", 0);
                if ($pos === false) {
                    $this->file_cache .= $str;
                } else {
                    $string = $this->file_cache . substr($str, 0, $pos);
                    $this->file_cache = substr($str, $pos + 1);
                }
            }
            if (!$str) {
                if ($this->file_cache) {
                    $string = $this->file_cache;
                    $this->file_cache = '';
                    return trim($string);
                }
                return false;
            }
        } else {
            $string = substr($this->file_cache, 0, $pos);
            $this->file_cache = substr($this->file_cache, $pos + 1);
        }
        return trim($string);
    }

    function close()
    {
        if ($this->comp_method == 2) {
            bzclose($this->fp);
        } elseif ($this->comp_method == 1) {
            gzclose($this->fp);
        } else {
            fclose($this->fp);
        }
        //@chmod($this->path . $this->filename, 0666);
        $this->fp=null;
    }
}
