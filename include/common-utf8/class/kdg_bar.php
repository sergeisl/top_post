<?
/**
 * Class kdg_bar
 * постраничная навигация
     $bar = new kdg_bar(array('perpage' => 20, 'tbl' => db_prefix . 'tovar', 'sql'=>$add_sql));
    $bar->href=($ord?'&ord='.$ord:'').($desc?'&desc':'').($q?'&q='.urlencode($q):''); if($bar->href)$bar->href='?'.substr($bar->href,1);
    $bar_out = $bar->out();
        <div>всего: <b><?= $bar->count ?></b>. &nbsp; <?= $bar_out ?></div>
        $query = $bar->query();
            while ($data = DB::fetch_assoc($query)){
                    Tovar::PrintTovar($data);
            }

Для вызова пагинации, возвращаемой другим файлом используйте:
'ajax'=>'/sendsms/api.php', 'div'=>'history'

Если в момент вызова определена $head, то в неё будет добавлены ссылки на следующую страницу для ускорения загрузки в Firefox и Chrome
 * @ver 1.1
*/
class kdg_bar
{
    var $perpage=5;	// Количество отображаемых данных из БД
    var $page=1;		// Номер страницы
    var $pages_count;	// Количество страниц
    var $start_pos;
    var $count=-1;		// Общее количество записей информации
    var $tbl;
    var $sql='';    // WHERE name=$bar->q
    var $q='';
    var $ord='';
    var $desc=''; // ''|' DESC'
    var $href=[];
    var $url='';
    var $show_link=6;	// это количество отображаемых ссылок, нагляднее будет, когда это число будет четное
    var $get='p';	// $_GET['p']
    var $ajax=false; // или адрес куда передавать
    var $div='main'; // если в $ajax адрес функции, то в div - объект в который загружать
    //private $href1='';
    var $ar_perpage= [10,30,50,200];
    var $autoLoadScroll=false; // div на который навесить обработку скролинга
    var $autoLoadContent=false; // div в который добавлять сонтент

    /**
     * @param bool|array $options
     *
     */
    function __construct($options=false){
        if(!empty($_REQUEST['perpage']))$this->perpage=intval($_REQUEST['perpage']);
        elseif(!empty($options['perpage']))$this->perpage=intval($options['perpage']);

        if(isset($options['tbl']))$this->tbl=$options['tbl'];
        if(isset($options['sql']))$this->sql=$options['sql'];
        if(isset($options['href']))$this->href=$options['href'];
        if(isset($options['url']))$this->url=$options['url'];
        if(isset($options['ajax']))$this->ajax=$options['ajax'];
        if(isset($options['div']))$this->div=$options['div'];
        if(isset($options['q']))$this->q=$options['q'];
        elseif(!empty($_GET['q']))$this->q=urldecode($_GET['q']);


        if(!empty($_GET['ord'])){$this->ord=urldecode($_GET['ord']); if(!preg_match('/^[a-z\_0-9]+$/', $this->ord)){AddToLog('Неверная сортировка :'.$this->ord);$this->ord='';}}
        if(empty($this->ord) && isset($options['ord']))$this->ord=$options['ord'];

        if(isset($_GET['desc']))$this->desc=' DESC';  // &uarr; &darr;
        elseif(isset($options['desc'])&&!isset($_GET['ord']))$this->desc=$options['desc'];

        if(isset($options['get']))$this->get=$options['get'];   // 1.08.15

        if(isset($_REQUEST[$this->get]))$this->page=$_REQUEST[$this->get];
        elseif(isset($options['page'])){$this->get='page';$this->page=$options['page'];} // нахрена?
        $this->page=max(1,intval($this->page));

        $this->start_pos = max(0,$this->page - 1) * $this->perpage; // Начальная позиция, для запроса к БД

        if(isset($options['autoLoadScroll']))$this->autoLoadScroll=$options['autoLoadScroll'];
        if(isset($options['autoLoadContent']))$this->autoLoadContent=$options['autoLoadContent'];

    }
    private function ajax($i){
        if(!$this->ajax){
            return '';
        }elseif( $this->ajax===true && $this->div!=='main'){
            return " onclick=\"return ajaxLoad('".$this->div."',this.href);\"";
        }elseif($this->ajax===true){
            return ' onclick="return LoadMain(event);"';
        }else{
            return " onclick=\"return ajaxLoad('".$this->div."','".$this->ajax.(strpos($this->ajax,'?')===false?'?':'&').$this->getHref($i)."');\"";
        }
    }

    private function onclick($i){
        if(!$this->ajax){
            return '';
        }elseif( $this->ajax===true && $this->div!=='main'){
            return "return ajaxLoad('".$this->div."',this.href);";
        }elseif($this->ajax===true){
            return 'return LoadMain(event);';
        }else{
            return "return ajaxLoad('".$this->div."','".$this->ajax.(strpos($this->ajax,'?')===false?'?':'&').$this->getHref($i)."');";
        }
    }

    function out($as_array=false){
// Общее количество информации
        if($this->count<0)$this->count=DB::num_rows(DB::sql('SELECT * FROM '.$this->tbl.' '.$this->sql));
//$this->count=DB::fetch_assoc(DB::sql($qq='SELECT count(*) as counter FROM '.$this->tbl.$this->sql));
//$this->count=intval($this->count['counter']); // общее кол-во товаров
//error("<br>".$qq." Всего записей ".$this->count,3);
        $this->perpage=max(2,$this->perpage);
        $this->pages_count = ceil($this->count / $this->perpage); // Количество страниц
// Если номер страницы оказался больше количества страниц
        if ($this->page > $this->pages_count) $this->page = $this->pages_count;
        $this->start_pos = max(0,$this->page - 1) * $this->perpage; // Начальная позиция, для запроса к БД

        //echo "<br>0:"; var_export($this->href);
        if(empty($this->href)){
            $this->href=$_SERVER['REQUEST_URI']; // /sprav.php?perpage=10&p=2&layer=2
            if(strpos($this->href,'?')===false){
                $this->url=$this->href;
                $this->href='';
            }else list($this->url,$this->href)=explode('?',$this->href);

        }
    if(is_string($this->href)) $this->href=($this->href?self::parse_query($this->href):[]);

    //echo "<br>1:"; var_export($this->href);
    //parse_str($this->href,$this->href);
    //echo "<br>2:"; var_export($this->href);
    //var_dump($this->href); exit;
        unset($this->href['perpage'],$this->href['ajax'],$this->href['q'],$this->href[$this->get]);
        if(isset($_REQUEST['perpage']))$this->href['perpage']=$this->perpage;
        if($this->page>0)$this->href[$this->get]=$this->page;
        if($this->q)$this->href['q']=$this->q;

        $out=[];
// Если страница всего одна, то вообще ничего не выводим
        if ($this->pages_count > 1) {
            $begin = max(1, $this->page - intval($this->show_link / 2));

            if ($begin == 2) $begin = 1;

//pages_count=7
//тек=6
// Сам постраничный вывод
// Если количество отображ. ссылок больше кол. страниц
            if ($this->pages_count <= $this->show_link + 1) $show_dots = 'no';
// Вывод ссылки на первую страницу
            if (($begin > 2) && ($this->pages_count - $this->show_link > 2))
                $out[] = $as_array ?
            ['title' => 'в начало',
            'href' => '?' . $this->getHref(1),
            'rel' => 'first',
            'onclick'=>$this->onclick(1),
            'page' => 1,
            'caption' => ' 1 ',
            'current' => false]
                : '<a href="' . '?' . $this->getHref(1) . '" rel="first" title="в начало"' . self::ajax(1) . '> 1 </a> ';;


            for ($j = 0; $j <= $this->show_link; $j++) { // Основный цикл вывода ссылок
                $i = $begin + $j; // Номер ссылки
                // Если страница рядом с началом, то увеличить цикл для того,
                // чтобы количество ссылок было постоянным
                if ($i < 1) continue;
                // Подобное находится в верхнем цикле
                if (!isset($show_dots) && $begin > 1) {
                    $out [] = $as_array ?
                        ['title' => '',
                            'href' => '?' . $this->getHref($i-1),
                            'rel' => '',
                            'onclick'=>$this->onclick($i-1),
                            'page' => $i,
                            'caption' => '...',
                            'current' => false]
                    : ' <a href="' . '?' . $this->getHref($i - 1) . '"' . self::ajax($i - 1) . '>...</a> ';

                    $show_dots = "no";
                }

                // Номер ссылки перевалил за возможное количество страниц
                if ($i > $this->pages_count) break;
                elseif ($i == $this->page) $out[] = $as_array ?
                    ['title' => '',
                        'href' => '?' . $this->getHref($i-1),
                        'rel' => '',
                        'onclick'=>'',
                        'page' => $i,
                        'caption' => '',
                        'current' => true]
                : ' <b class="b-pager__current">' . $i . '</b> ';

                elseif ($i + 1 == $this->page) {
                    $out[] = $as_array ?
                        ['title' => '',
                            'href' => '?' . $this->getHref($i),
                            'rel' => 'prev',
                            'onclick'=>$this->onclick($i),
                            'page' => $i,
                            'caption' => $i,
                            'current' => false]
                        : ' <a rel="prev" href="?' . $this->getHref($i) . '"' . self::ajax($i) . '>' . $i . '</a> ';

                } elseif ($i - 1 == $this->page) {
                    $out[] = $as_array ?
                        ['title' => '',
                            'href' => '?' . $this->getHref($i),
                            'rel' => 'next',
                            'onclick'=>$this->onclick($i),
                            'page' => $i,
                            'caption' => $i,
                            'current' => false]
                        :' <a rel="next" href="?' . $this->getHref($i) . '"' . self::ajax($i) . '>' . $i . '</a> ';
                    global $head;
                    if (isset($head)) $head .= '<link rel="prefetch" href="?' . $this->getHref($i) . '" /><link rel="prerender" href="?' . $this->getHref($i) . '" />';
                } else $out[] = $as_array ?
                    ['title' => '',
                        'href' => '?' . $this->getHref($i),
                        'rel' => '',
                        'onclick'=>$this->onclick($i),
                        'page' => $i,
                        'caption' => $i,
                        'current' => false]
                    :' <a href="?' . $this->getHref($i) . '"' . self::ajax($i) . '>' . $i . '</a> ';

                // Если номер ссылки не равен кол. страниц и это не последняя ссылка
                ///if (($i != $this->pages_count) && ($j != $this->show_link)) $out[] = ' ';// Разделитель ссылок
                // Вывод "..." в конце
                if (($j == $this->show_link) && ($i < $this->pages_count - 1))
                    $out[] = $as_array ?
                        ['title' => '',
                            'href' => '?' . $this->getHref($i+1),
                            'rel' => '',
                            'onclick'=>$this->onclick($i+1),
                            'page' => $i,
                            'caption' => '...',
                            'current' => false]
                        :' <a href="?' . $this->getHref($i + 1) . '">...</a> ';

            }

// Вывод ссылки на последнюю страницу
            if ($begin + $this->show_link < $this->pages_count)
                $out[] = $as_array ?
                    ['title' => 'в конец',
                        'href' => '?' . $this->getHref($this->pages_count),
                        'rel' => 'last',
                        'onclick'=>$this->onclick($this->pages_count),
                        'page' => $this->pages_count,
                        'caption' => $this->pages_count,
                        'current' => false]
                    :' <a href="?' . $this->getHref($this->pages_count) . '" rel="last" title="в конец"' . self::ajax($this->pages_count) . '> ' . $this->pages_count . ' </a>';

            if ($this->autoLoadScroll) {
                $w = (is_string($this->autoLoadScroll) ? 'getObj("' . $this->autoLoadScroll . '")' : 'window');
                global $DomLoad;
                $DomLoad = (empty($DomLoad) ? "" : $DomLoad . "\n") . 'console.log("autoload");' .
                    'addEvent(' . $w . ', "scroll", AutoLoadContent);' .
                    /*'addEvent('.$w.', "touchmove", AutoLoadContent);'. load scroll touchmove MSPointerMove*/
                    'autoLoadContent="' . $this->autoLoadContent . '";';
            }

        }
        if($as_array)return $out;

        $out=implode(' ',$out);

        if(!$this->ajax){
            $onchange='location.href=\''.'?'.$this->getHref($this->page).'&perpage=\'+this.options[this.selectedIndex].value';
        }elseif( $this->ajax===true && $this->div!=='main'){
            $onchange="return perpage(this,'".$this->div."','".'?'.$this->getHref($this->page)."')";
        }elseif($this->ajax===true){
            $onchange='return perpage(this)';
        }else{
            $onchange="return perpage(this,'".$this->div."','".$this->ajax.(strpos($this->ajax,'?')===false?'?':'&').$this->getHref($this->page).'&'.$this->ajax."')";
        }

        if($this->count>$this->ar_perpage[0]){
            $out.="\n\t\t".'<select onchange="'.$onchange.'">';
            foreach($this->ar_perpage as $pp) $out.='<option value="'.$pp.'"'.($this->perpage==$pp?' selected':'').'>по '.$pp.'</option>';
            $out.='</select>';
        }

        return "\n\t<div class=\"link_bar\">\n\t\t".$out."\n\t</div>\n";
} // Конец функции out

    /** выводит строку заголовка
     * @param array $Header
     * @return string
     */
    function outHeader($Header){
        $str='';
        foreach($Header as $key=>$val)$str.="\n<th onclick=\"Order('".$key."')\">".$val.($key&&$this->ord==$key?($this->desc?'&uarr;':'&darr;'):'')."</th>";
        return '<tr class="hand">'.$str.'</tr>';

    }

    function query(){
        return DB::sql('SELECT * FROM '.$this->tbl.' '.$this->sql.($this->ord?' ORDER BY '.$this->ord.$this->desc:'').' LIMIT '.$this->start_pos.', '.$this->perpage);
    }

    function getHref($p){
        return str_replace('=&','&',http_build_query(array_merge($this->href, [$this->get=>$p])));
    }

    static function parse_query($var)
    { // эквивалент parse_str()
        //$var  = parse_url($var, PHP_URL_QUERY);
        if(substr($var,0,1)=='?')$var=substr($var,1);
        $var=trim($var,' &?');
        $var  = html_entity_decode($var);
        $var  = explode('&', $var);
        $arr  = [];
        foreach($var as $val)
        {
            $x = explode('=', $val.'=');
            $arr[str_replace(['%5B','%5D'], ['[',']'], $x[0])] = $x[1];
        }
        unset($val, $x, $var);
        return $arr;
    }

    /**
     * @param string $var строка GET-запроса
     * @param array|string $param параметры в формате строки через & или в формате массива ключ=>значение
     * @param array|string $delete удаляемые параметры в формате строки через & или в формате массива ключ=>значение.
     * @return string;
     */
    static function replace_query($var,$param,$delete='')
    {
        $var=self::parse_query($var);
        if(is_string($delete)) $delete=self::parse_query($delete);
        if(is_string($param)) $param=self::parse_query($param);
        if($delete)foreach($delete as $key=>$val)unset($var[$key]);
        foreach($param as $key=>$val)$var[$key]=$val;
        return htmlentities(self::implode($var));
    }
    static function implode($arr) {
        $sQuery = ''; if(!$arr)return '';
        foreach ($arr as $Key=>$Val) {
            $sQuery.= '&'.$Key.($Val?'='.urlencode($Val):'');
        }
        return substr($sQuery,1);
    }
    public function header($h=[]){
        $s="";
        foreach($h as $key=>$val){
            if(preg_match("/^[0-9]+$/",$key))$s.="\n<th style='cursor:not-allowed'>".$val."</th>";
            else $s.="\n<th onclick=\"Order('".$key."')\">".$val.($this->ord===$key?($this->desc?'&uarr;':'&darr;'):'')."</th>";
        }
        return "\n<tr>".$s."\n</tr>";
    }

}
