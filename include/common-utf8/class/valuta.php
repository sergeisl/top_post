<?
define("tsKurs", "15:00:00"); # Время смены курса центральным банком
define('USD', 840);
define('EURO', 978);

class Valuta
{

    static function dollar()
    {
        global $dollar;
        if (empty($dollar)) self::load();
        return $dollar;
    }

    static function euro()
    {
        global $euro;
        if (empty($euro)) self::load();
        return $euro;
    }

    static function load()
    {
        global $dollar, $euro;
        if (!empty($dollar) && !empty($euro)) return;
        $kurs_file = $_SERVER['DOCUMENT_ROOT'] . "/log/kurs.txt";
        if (file_exists($kurs_file)) {
            $lastModified = filemtime($kurs_file);
            // каждые 24 часа, но с учетом времени смены курса центральным банком
            if ($lastModified > strtotime("-24 hour") && !(date("H:i:s", $lastModified) < tsKurs && date("H:i:s") > tsKurs)) {
                list($dollar, $euro) = explode('|', file_get_contents($kurs_file));
                //echo "<!--Курс ЦБ на ".date("Y-m-d H:i:s",$lastModified)."<br>Доллар - <b>".$dollar."</b><br>Евро - <b>".$euro."</b><br>".$df1."-->";
                return;
            }
        }

        if(file_exists($kurs_file)) { // считаю по старому курсу если он есть
            list($dollar, $euro) = explode('|', file_get_contents($kurs_file));
        }else{
            $dollar = "35";
            $euro = "48";
        }
        list($headers,$content,$info)=ReadUrl::ReadWithHeader("http://www.cbr.ru/scripts/XML_daily.asp?date_req=" . date("d/m/Y", time() + 60 * 60 * (24 - 15)) , false, array('timeout'=>20) );
        if (!$content) {
            Out::error('Курс $ не доступен!');
            return;
        }

        // Разбираем содержимое, при помощи регулярных выражений
        if (preg_match_all("#<Valute ID=\"([^\"]+)[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>([^<]+)#i", $content, $out, PREG_SET_ORDER)) {
            foreach ($out as $cur) {
                if ($cur[2] == USD) $dollar = str_replace(",", ".", $cur[4]);
                if ($cur[2] == EURO) $euro = str_replace(",", ".", $cur[4]);
            }
            if (file_put_contents($kurs_file, $dollar . '|' . $euro) < 7) AddToLog('Ошибка записи в ' . $kurs_file, 'LoadKurs Error');

            unset($out, $cur, $content, $kurs_file, $lastModified);
            //echo "Курс ЦБ на ".date("Y-m-d H:i:s")."<br>Доллар - <b>".$dollar."</b><br>";
            //echo "Евро - <b>".$euro."</b><br>";

            ignore_user_abort(true);
            set_time_limit(10000);
            self::RecalcAllPrice(); // т.к. поменялся курс, то пересчитываю все цены
            //system('GET http://'.$_SERVER['HTTP_HOST'].'/shop/yml.php?x=1 &'); todo повестить на cron
        }

    }
/*
    static function cPrice(&$tov, $supplier = 0, $Show = false)
    { // рассчитать розничную рублевую цену
        global $dollar, $euro;
        Valuta::load();
        if (!isset($supplier['valuta'])) {
            if (empty($tov['supplier']) || !$supplier = DB::Select('supplier_cfg', 'id=' . $tov['supplier'])){
                Tovar::SetFirstSupplier($tov);
                //$klient."', sklad='".$row['sklad']."', price0='".$row['price0']."', valuta0='".$row['valuta0']."', price='".$price."', ost='".$row['ost']."
                if (!$supplier = DB::Select('supplier_cfg', 'id=' . $tov['supplier'])){
                    echo 'ОШИБКА! Нет поставщика id=' . $tov['supplier'];
                    return $tov['price'];
                }
            }
        }
        $tov['nac1'] = $nac1 = intval($supplier['nac']); // наценка конвертации, указана в справочнике поставщиков
        if ($supplier['valuta'] == '$') $nac1 = ($nac1 + Nacenka); // если валюта в справочнике поставщиков - доллары, то добавляю базовую наценку
        elseif ($supplier['valuta'] == '€') $nac1 = ($nac1 + Nacenka); // если валюта в справочнике поставщиков - доллары, то добавляю базовую наценку
        elseif ($tov['supplier'] == 1 && !$nac1) $nac1 = Nacenka;
        elseif ($supplier['valuta'] > 0) $nac1 = ($nac1 + Nacenka); // валюта указана в самом прайсе
        if (isset($tov['tovar']) && ($tov['tovar'] > 0) && !isset($tov['gr'])) {
            if (!($tov1 = DB::Select('tovar','id=' . $tov['tovar']))) die('Нет такой записи #' . $tov['tovar'] . '!');
            $tov['gr'] = $tov1['gr'];
        }
        $tov['nac2'] = $nac2 = (isset($tov['gr']) ? Tovar::nac_v($tov['gr']) : 0); // наценка для группы товаров
        $price = intval(floatval($tov['price0']) * (trim($tov['valuta0']) == '$' ? $dollar : trim($tov['valuta0']) == '€' ? $euro : 1) * (100 + $nac1 + $nac2) / 100);
        // если цена товара до 100рублей, то делаю наценку не меньше 100%
        if ($tov['supplier'] > 1 && $price <= 100 && ($nac1 + $nac2) < 91) {
            if ($Show) $price = '</b>' . $price . ', 200%=<b>' . max($price, intval(floatval($tov['price0']) * (trim($tov['valuta0']) == '$' ? $dollar : (trim($tov['valuta0']) == '€' ? $euro : 1)) * 2)) . '';
            else $price = max($price, intval(floatval($tov['price0']) * (trim($tov['valuta0']) == '$' ? $dollar : (trim($tov['valuta0']) == '€' ? $euro : 1)) * 2));
        }
        if ($Show) $price = trim(($tov['price0'] == intval($tov['price0']) ? intval($tov['price0']) : $tov['price0'])) . (trim($tov['valuta0']) == '$' ? ('$*' . $dollar) : (trim($tov['valuta0']) == '€' ? ('€*' . $euro) : '')) . '+' . $nac1 . '%+' . $nac2 . '%=<b>' . $price . '</b>';
        if ($tov['supplier'] == 1 && $tov['valuta'] != '$' && $tov['valuta'] != '€' && @$tov['priceu'] > 0) {
            if ($Show) $price .= '~<b>' . intval($tov['priceu']) . '</b>';
            else $price = intval($tov['priceu']);
        }
        return $price;
    }

}*/

    /**
     * @param string $valuta = 'dollar' | 'euro' | '$' | 'request' | 'rur'
     * @param int    $summ - сумма в рублях, если хотите сразу получить сумму в указанной валюте. Если не передано, то возвращает курс
     * @return float возвращает курс округленный до 2х знаков
     */
    static function ConvertKurs($valuta='dollar', $summ=false, $round=2){
        if($valuta=='request') return ($summ===false ? round(User::$ar_pay[0]['cost'],$round) : round($summ*User::$ar_pay[0]['cost'],$round) );
        if($valuta=='rur') return round(self::dollar()*$summ,$round);
        if($valuta=='$')$valuta='dollar';
        return ($summ===false ? round(Valuta::$valuta(),$round) : round($summ/Valuta::$valuta(),$round) );
    }

static function RecalcAllPrice($Show = 0) // пересчет всех цен
{
    global $dollar, $euro;
    Valuta::load();
        $query = DB::sql('SELECT * FROM '.db_prefix.'supplier');
        while ($klient = DB::fetch_assoc($query)){
            if($Show)echo "<br><br>\n".trim($klient['name'])." valuta=<b>".$klient['valuta']."</b>, nac=<b>".$klient['nac']."</b>";

            /* вариант с проходом по каждой записи
            $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE supplier='.$klient['id']);
            while($tov=DB::fetch_assoc($result))if($tov['price0']>0 && trim($tov['valuta0'])=='$'){
                 $price=cPrice($tov, $klient=0);
             if($price<>$tov['price'])
                DB::sql('UPDATE '.db_prefix.'tovar SET price='.str_replace(",",".",$price).' WHERE id='.$tov['id']);
         */
            if($klient['valuta']=='$'||$klient['id']==1){
                $nac1=intval($klient['nac']); // наценка конвертации, указана в справочнике поставщиков
                if($klient['valuta']=='$') $nac1=($nac1+Nacenka); // если валюта в справочнике поставщиков - доллары, то добавляю базовую наценку
                elseif(!$nac1 && $Show)echo " <b>nac1=0 !!!</b>";
                $nac1+=100;
                DB::sql($q='UPDATE '.db_prefix.'tovar, '.db_prefix.'gr SET tovar.price=tovar.price0*'.str_replace(",",".",$dollar).'*('.$nac1.'+gr.nac)/100 WHERE tovar.gr=gr.id and tovar.supplier='.$klient['id'].' and tovar.valuta0="$"'.($klient['id']==1?" and (tovar.valuta='$' or tovar.priceu<1)":""));
                if($Show)echo "<br>".$q."<br>\n обновлено <b>".DB::affected_rows()."</b> записей.";
            }
            /*
                  if($data['id']==6){ // ChipCard
                 $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE supplier='.$data['id']);
                 while($tov=DB::fetch_assoc($result))if($tov['price0']>0){
                    $price=CalcPrice($tov['price0'], $tov['supplier'], $tov['id']); // рассчитать розничную цену
                    DB::sql('UPDATE '.db_prefix.'tovar '.db_prefix.'tovar SET price='.str_replace(",",".",$price).' WHERE id='.$tov['id']);
                  }
                  }elseif($data['id']==1){ // TODO пока не пересчитываю

                  }elseif($data['valuta']=='$'){//если цена долларовая пересчитываю
                    $kurs=kurs($data['nac'], $data['valuta']);
                DB::sql('UPDATE '.db_prefix.'tovar, '.db_prefix.'gr SET price.price=price.price0*'.str_replace(",",".",$kurs).'*(100+gr.nac)/100 WHERE price.gr=gr.id and price.supplier='.$data['id']);
                  }elseif($data['id']>1) // все кроме магазина
                DB::sql('UPDATE '.db_prefix.'tovar, '.db_prefix.'gr SET price.price=price.price0*(100+'.str_replace(",",".",$data['nac']).'+gr.nac)/100 WHERE price.gr=gr.id and price.supplier='.$data['id']);
                  else // магазин
                DB::sql('UPDATE '.db_prefix.'tovar SET price=price0 WHERE supplier='.$data['id']);
            */
//$price=intval(floatval(str_replace(',','.',$row['price']))*$klient[$row['supplier']]);
        }
        DB::sql('UPDATE '.db_prefix.'tovar SET price=price0 WHERE supplier=1 and valuta0<>"$" and tovar.valuta<>"$" and tovar.priceu>0');
        if($Show)echo "<br><br>\n".DB::$query."<br>\n обновлено <b>".DB::affected_rows()."</b> записей.";
    }

    function get_content()
    {
        // Формируем сегодняшнюю дату
        $date = date("d/m/Y",time()+60*60*(24-15));
        // Формируем ссылку
        $link = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$date;
        // Загружаем HTML-страницу
        $fd = @fopen($link, "r");
        $text="";
        if (!$fd) {echo 'Курс $ не доступен!<br />'; return '';}
        else { while (!feof ($fd)) $text .= fgets($fd, 4096); }
        fclose ($fd);
        return $text;
    }

    /*
    function kurs($nac, $valuta){
    global $dollar;
    if($valuta=='$') return (100+$nac+Nacenka)/100*$dollar;
    elseif($nac>0) return (100+$nac)/100;
    else	return 1;
    }
    */
    static function nac_v($gr){
        global $nac_v;
        if(isset($nac_v[$gr])) return $nac_v[$gr];
        elseif($gr==0)return '[gr=0]';
        else{
            $query = DB::sql('SELECT * FROM '.db_prefix.'category WHERE id='.intval($gr).' LIMIT 1');
            if ($data = DB::fetch_assoc($query)) return $nac_v[$gr]=($data['nac']?intval($data['nac']):'0');
            else {
                DB::sql('UPDATE '.db_prefix.'tovar SET gr=0 WHERE gr='.intval($gr));
                die('ОШИБКА! Небыло группы '.$gr);}
        }
    }
    /*
    function nac_kg($klient,$valuta=''){	// 	     $price=$price*$kurs*(100+$nac1+$nac2)/100;
    global $dollar;
      if(is_array($klient)){$data=$klient; $klient=$data['id'];}
      if($klient==1&&empty($valuta)) return array(1, 0 );
      if(!isset($data['valuta'])){
        $query = DB::sql('SELECT * FROM '.db_prefix.'supplier WHERE id='.$klient.' LIMIT 1');
        if (!($data=DB::fetch_assoc($query))) die( 'ОШИБКА! Нет клиента '.$klient);
      }
      if($valuta=='')$valuta=$data['valuta'];
      $nac=intval($data['nac']);
      if($klient==6) return array($dollar, $nac );
      if($valuta=='$') return array($dollar, ($nac+Nacenka) );
      elseif($nac>0) return array(1, $nac);
      else return array(1, 0);
    }

    function CalcPrice($price0, $klient, $id, $Show=false){ // рассчитать розничную цену
        $query=DB::sql('SELECT gr,priceu,valuta0,valuta FROM '.db_prefix.'tovar WHERE id='.$id.' LIMIT 1');
        if (!($data=DB::fetch_assoc($query))) return 0;
        $gr=$data['gr'];
        //echo "nac_kg(".$klient.",".$data['valuta0'].")";
        list($kurs,$nac1)=nac_kg($klient,$data['valuta0']);
        //echo "list(".$kurs.",".$nac1.")";
        if($data['valuta']=='+' && $data['priceu']>0 ){$price=$data['priceu']; $kurs=1; $nac2='';
        }elseif($klient==6){$price=floatval($data['priceu'])*($data['valuta']=='$'?$kurs:1); $nac2='';
        }else{
           $nac2=nac_v($gr);
           $price=intval(floatval($price0)*$kurs); // курс с учетом базовой наценки
           $price=$price*(100+$nac1+$nac2)/100;
        }
        $price=str_replace(',','.',$price);
        if($Show)$price=($kurs==1?' ':'$ *'.$kurs).' +'.$nac1.'% +'.$nac2.'% = <b>'.$price.'</b>';
    //.$klient_v[$row['supplier']]." = "
        return $price;
    }
    */
}
