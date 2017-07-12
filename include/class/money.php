<?
class Money{
    static function GetOst($d=false){ // остаток в кассе на начало дня
        if(!$d)$d=strtotime("now 00:00");
//echo date('Y-m-d H:i:s',$d);
        $query=DB::sql('SELECT * FROM '.db_prefix.'incasso WHERE `time`<="'.date('Y-m-d H:i:s',$d).'" and rej=27 ORDER BY time DESC'); // сальдо в кассе на начало дня
        if($data = DB::fetch_assoc($query)){
            //echo $data['time'].'=='.date('Y-m-d H:i:s',$d);
            if($data['time']==date('Y-m-d H:i:s',$d)) return floatval($data['summ']);
            $t=$data['time'];
            $s=floatval($data['summ']);
        }else{
            $t='1970-01-01';
            $s=0;
        }

        //echo "<br>\nОстаток на утро ".$t." : ".$s;

        $between='BETWEEN "'.date('Y-m-d H:i:s',strtotime($t)).'" and "'.date('Y-m-d',$d).'"';
        $query=DB::sql('SELECT SUM(zakaz2.price*zakaz2.kol) as s
		FROM '.db_prefix.'zakaz2 as zakaz2, '.db_prefix.'zakaz as zakaz, '.db_prefix.'user as user
		WHERE zakaz2.zakaz=zakaz.id and zakaz.user=user.id and (user.adm<5 or user.adm IS NULL) and zakaz.time '.$between);
        if($data=DB::fetch_assoc($query))$s+=$data['s'];

        //echo "<br>\n".$q."<br>"; print_r($data);

        $query=DB::sql('SELECT SUM(zakaz.visa) as visa FROM '.db_prefix.'zakaz as zakaz	WHERE time '.$between);
        if($data=DB::fetch_assoc($query))$s-=$data['visa'];

        //echo "<br>\n".$q."<br>"; print_r($data);

        $query=DB::sql('SELECT SUM(`summ`) as s FROM '.db_prefix.'incasso WHERE rej<7 and `time` '.$between);
        if($data = DB::fetch_assoc($query))$s-=floatval($data['s']);
        //echo "<br>\n".$q."<br>"; print_r($data); echo "<br>\nИтого на утро ".$s;
        if($d<=strtotime("now 00:00")) DB::sql("INSERT INTO `".db_prefix."incasso` ( `time`, `rej`, `summ`, `user`) VALUES ('".date('Y-m-d',$d)."', '27', '".$s."', '".$_SESSION['user']['id']."')");
        return $s;
    }


    static function GetBalans($d_from, $d_to=false){ // расчет зарплаты
        if($d_from>strtotime('now'))return [];
        $old_alg = $d_from < strtotime(zakaz::$new_alg_start_time);
        $days_in_month=date('t',$d_from);
        if(empty($d_to))$d_to=strtotime("last day of this month",$d_from);
        // только с начала месяца!
        $add = ' and time between "' . date("Y-m-01 00:00:00", $d_from) . '" and "' . date("Y-m-d 23:59:59", $d_to) . '"';
        $actual_mointh=($d_from >= strtotime(date('01.m.Y'))); // за текущий месяц
        $user_0 = []; // считаю сколько назагорал и взял косметики по закупочной цене(s1) и по розничной(s0)
        //Получаю список администраторов
        $spis=DB::fetch_assoc(DB::sql("SELECT GROUP_CONCAT(DISTINCT user SEPARATOR ',') AS spis FROM ".db_prefix."zakaz WHERE ". substr($add,5))); if($spis)$spis=$spis['spis']; //echo "<br>Админы:".$spis;
        // считаю все услуги и косметику для расчета з/пл
        $s = DB::fetch_assoc( DB::sql("SELECT SUM(IF(tovar.type=0,zakaz2.KOL*zakaz2.PRICE,0)) as s0, SUM(IF(tovar.type=1,zakaz2.KOL*zakaz2.PRICE,0)) as s1, SUM(IF(tovar.type=2,zakaz2.KOL*zakaz2.PRICE,0)) as s2, SUM(IF(tovar.type=3,zakaz2.KOL*zakaz2.PRICE,0)) as s3
	FROM " . db_prefix . "zakaz as zakaz," . db_prefix . "zakaz2 as zakaz2," . db_prefix . "tovar as tovar," . db_prefix . "user as user
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id and zakaz.user=user.id and (".($spis?"user.id NOT IN(".$spis.") and ":"").
            "user.adm<>" . uADM_OPT . " or user.adm IS NULL) " . str_replace('time', 'zakaz.time', $add) . "") );
        $pubZp = 0; // общая часть з.пл
        $allZp = 0; // весь фонд з.пл
        if ($old_alg) {
            $ss0 = ($s['s0'] + $s['s3']) * z_proc0 / 100;
            $ss1 = ($s['s1'] + $s['s2']) * z_proc1 / 100;
            echo "<p>Всего услуг на сумму: <b>" . ($s['s1'] + $s['s2']) . "</b>*" . z_proc1 . "%=" . $ss1 . ", косметики: <b>" . ($s['s0'] + $s['s3']) . "</b>*" . z_proc0 . "%=" . $ss0 . ".</p>";
        } else {
            // считаю зарплату по новым формулам
            $mes='';
            $res = DB::sql("SELECT * FROM " . db_prefix . "zakaz as zakaz WHERE " . substr($add, 5)." ORDER BY user");
            while ($data = DB::fetch_assoc($res)) {
                if (!isset($_GET['recalc']) && ($data['zp'] || $data['zpu']) ) {
                    $allZp += $data['zp'] + $data['zpu'];
                    $user_0[$data['user']]['p2'] = (empty($user_0[$data['user']]['p2']) ? 0 : $user_0[$data['user']]['p2']) + $data['zpu'];
                    $pubZp += $data['zp'];
                } else {
                    $zakaz = new zakaz($data);
                    $zakaz->zp(); $mes.=$zakaz->message;
                    $allZp += $zakaz->zakaz['zp'] + $zakaz->zakaz['zpu'];
                    $user_0[$data['user']]['p2'] = (empty($user_0[$data['user']]['p2']) ? 0 : $user_0[$data['user']]['p2']) + $zakaz->zakaz['zpu'];
                    $pubZp += $zakaz->zakaz['zp'];
                }
            }
            echo "<p>Всего услуг на сумму: <b>" . ($s['s1'] + $s['s2']) . "</b>. Фонд з/пл " . $allZp .nl2br($mes);
        }
        /* s0 - 1услуги,2абонементы по розничной цене(из справочника) / по зафиксированной приходной на момент списания
         * s1 - взял 0косметику и 3расходку по розничной цене(из справочника) для рассчета бонуса
         * s2 - взял 0косметики, 1услуги и 3расходку по цене прихода для вычета из з/пл; если бывший сотрудник пришел загорать, то у него будет суммма к списанию 2400
         * */
        $res = DB::sql("SELECT zakaz.user as user, tovar.type as type,
	    SUM( IF(zakaz2.price<=0,tovar.price0 * zakaz2.kol, zakaz2.KOL*zakaz2.PRICE) ) AS s0,
	    SUM( IF(tovar.type=1 && zakaz2.price>0, 0, IF(tovar.price<tovar.price0,tovar.price0,tovar.price) * zakaz2.kol )) AS s1,
	    SUM( IF(tovar.type=1 && zakaz2.price>0, 0, IF(tovar.price<tovar.price0,tovar.price,tovar.price0) * zakaz2.kol )) AS s2
	FROM " . db_prefix . "zakaz as zakaz," . db_prefix . "zakaz2 as zakaz2," . db_prefix . "tovar as tovar
	WHERE zakaz.id=zakaz2.zakaz and zakaz2.tovar=tovar.id  and ".($spis?"zakaz.user IN(".$spis.") ":"false") . str_replace('time', 'zakaz.time', $add) . " and (type<>1 or zakaz2.price=0)
	GROUP BY zakaz.user,tovar.type");
        while ($data = DB::fetch_assoc($res)) {
            $user_0[$data['user']][$data['type']] = $data;
            //echo "<br>\n"; print_r($data);
        }

        // считаю сколько отработал дней
        $res = DB::sql("SELECT count(*) as s,user from (SELECT DATE_FORMAT(time, '%Y-%m-%d') as dat, user
	FROM " . db_prefix . "zakaz
	WHERE " . substr($add,5) . " GROUP BY dat,user)q GROUP BY user");
        $adm_days = $adm_count = 0;
        while ($data = DB::fetch_assoc($res)) {
            //echo "<br>"; print_r($data);
            $user_0[$data['user']]['day'] = $data['s'];
            $user_0[$data['user']]['oklad'] = ($data['s'] > 0 ? ($d_from < strtotime('2015-10-01 00:00:00')?z_oklad:z_oklad1) : 0);
            if ($data['s'] > 0) $adm_count++;
            $adm_days += $data['s'];
            //echo "<br>\n"; print_r($data);
        }
        // если в этом месяце больше 2х администраторов, то оклад и % считаю по сложной формуле

        // считаю сумму авансов
        // rej = 1 - инкассация, 2 - закупка хоз.нужд, 3,10 - выплата аванса, 4 - выплата з.платы, 8 - штраф/недостача, 7 - остаток в кассе на начало дня, 9 - по р/счету
        $res = DB::sql("SELECT user, rej, SUM(summ) as s
        FROM `" . db_prefix . "incasso`
        WHERE rej in (3,10) " . $add . "
        GROUP BY user");
        while ($data = DB::fetch_assoc($res))
            $user_0[$data['user']]['avans'] = (isset($user_0[$data['user']]['avans']) ? $user_0[$data['user']]['avans'] : 0) + $data['s']; // сумма авансов

        $res = DB::sql("SELECT user, rej, SUM(summ) as s
        FROM `" . db_prefix . "incasso`
        WHERE rej = 4 " . $add . "
        GROUP BY user");
        while ($data = DB::fetch_assoc($res))
            $user_0[$data['user']]['zp'] = (isset($user_0[$data['user']]['zp']) ? $user_0[$data['user']]['zp'] : 0) + $data['s']; // сумма з/п

        // считаю сумму штрафов
        $res = DB::sql("SELECT user, rej, SUM(summ) as s
        FROM `" . db_prefix . "incasso`
        WHERE rej=8 " . $add . "
        GROUP BY user");
        while ($data = DB::fetch_assoc($res))
            $user_0[$data['user']]['fine'] = (isset($user_0[$data['user']]['fine']) ? $user_0[$data['user']]['fine'] : 0) + $data['s']; // сумма штрафов

        // считаю остаток на 1.01 по сотрудникам
        $res = DB::sql("SELECT user, rej, SUM(summ) as s
        FROM `" . db_prefix . "incasso`
        WHERE rej=20 and `time`='" . date('Y-m-d 00:00:00', $d_from) . "'
        GROUP BY user");
        while ($data = DB::fetch_assoc($res))
            $user_0[$data['user']]['ost0'] = (isset($user_0[$data['user']]['ost']) ? $user_0[$data['user']]['ost'] : 0) + $data['s']; // сальдо на нач. месяца

        if($spis){
            $res = DB::sql("SELECT * from `" . db_prefix . "user` WHERE id in (".$spis.") ");//OR adm=" . ($actual_mointh?uADM_WORKER:uADM_ADMIN));
            while ($row = DB::fetch_assoc($res)) {
                $u = $row['id'];
                if ($row['adm'] == uADM_OLD_WORKER && !isset($user_0[$u]['day'])) continue;
                if (!isset($user_0[$u]['oklad'])) $user_0[$u]['oklad'] = 0; //z_oklad;
                $user_0[$u]['adm'] = $row['adm'];
                $user_0[$u]['name'] = $row['fullname'];
            }
        }
        //var_dump($user_0[14]);
        foreach ($user_0 as $u => $v) {

            //echo "<br>id=".$u; print_r($v);
            if (!isset($v['zp'])) $v['zp'] = 0;
            if (!isset($v['avans'])) $v['avans'] = 0;
            if (!isset($v['fine'])) $v['fine'] = 0;
            //$s0=(isset($v['0'])?$v['0']['s0']:0); // косметика по цене продажи zakaz2.summ
            if (!isset($v['day'])) $v['day'] = 0;
            $s1 = (isset($v['0']['s1']) ? $v['0']['s1'] : 0) +
                (isset($v['3']['s1']) ? $v['3']['s1'] : 0); // взял 0косметику и 3расходку по розничной цене(из справочника) для рассчета бонуса
            //$v['s2'] = $s2 = (isset($v['0']['s2']) ? $v['0']['s2'] : 0) + // по приходной из справочника
            $v['s2'] = $s2 = (isset($v['0']['s0']) ? $v['0']['s0'] : 0) + // по зафиксированной приходной на момент списания
                (isset($v['3']['s2']) ? $v['3']['s2'] : 0) + // взял 0косметики, 1услуги и 3расходку по цене прихода для вычета из з/пл
                (isset($v['1']['s0']) ? $v['1']['s0'] : 0);
            /* услуги по цене прихода разу попадают в zakaz2.summ $v['1']['s0']
                    (isset($v['1']['s2'])?$v['1']['s2']:0);*/ // взял услуги по цене прихода
            $s3 = (isset($v['1']['s1']) ? $v['1']['s1'] : 0) +
                (isset($v['2']['s1']) ? $v['2']['s1'] : 0) -
                (isset($v['1']['s0']) ? $v['1']['s0'] : 0); // 1услуги,2абонементы по розничной цене(из справочника)
            $v['bonus'] = ($s1 - $s2) + $s3;
            // считаю остаток на 1.01
            if (!isset($v['ost0']) && (!isset($v['adm']) || $v['adm'] <= uADM_WORKER)) { // будем считать
                if ($d_from < strtotime('2013-01-02 00:00:00')) { //костыль от бесконечной рекурсии
                    $v['ost0'] = 0;
                } else {
                    if (!isset($_user)) list($_s, $_user) = self::GetBalans(strtotime("first day of previous month" . date("Y-m-d 00:00:00", $d_from)), strtotime("last day of previous month" . date("Y-m-d 00:00:00", $d_from)));
                    $v['ost0'] = (isset($_user[$u]['ost']) ? $_user[$u]['ost'] : 0);
                    if ($v['ost0'] == 0 && $v['zp'] > 0) { // это первая выплата после начала работы, считаю что эта выплата з/пл есть долг за месяц в котором не было учета
                        $v['ost0'] = $v['zp'];
                    }
                    DB::sql("INSERT INTO `" . db_prefix . "incasso`
                ( `time`, `rej`, `summ`, `user`)
                VALUES ('" . date('Y-m-d', $d_from) . "', '20', '" . $v['ost0'] . "', '" . $u . "')");
                }
            }
            //$adm_count=$days_in_month;
            if (isset($v['adm']) && $v['adm'] <= uADM_WORKER && $adm_count) {
                //$adm_oklad=$adm_proc = ($adm_count != 2 && $adm_days > 0 ? ($v['day'] / $adm_days) : ($v['day'] > 0 ? 0.5 : 0));
                /*if ($adm_count > 2 || $v['adm']==uADM_OLD_WORKER) {
                    $v['oklad'] = round($v['oklad'] * 2 * $adm_proc, 2);
                    echo "<br>adm_proc=" . $adm_proc . ", adm_count=" . $adm_count . ", adm_days=" . $adm_days;
                }*/
                $adm_oklad=( ($adm_count==2 && $v['day']>=($days_in_month/2)-5) ? 0.5 : ($v['day'] / ($days_in_month/2)/2) );
                $adm_proc=( ($adm_count==2 && $v['day']>=($days_in_month/2)-5) ? 0.5 : ($v['day'] / $adm_days) );
                echo "<br>days_in_month=" . $days_in_month . ", adm_count=" . $adm_count . ", adm_days=" . $adm_days . ", adm_oklad=" . $adm_oklad . ", adm_proc=" . $adm_proc;
                if($v['day'] / ($days_in_month/2))
                    $v['oklad'] = round($v['oklad'] * 2 * $adm_oklad, 2);
                if ($old_alg) {
                    $v['p1'] = ($s['s0'] + $s['s3']) * z_proc0 * $adm_proc / 100; // % от общей суммы продаж косметики
                    $v['p2'] = ($s['s1'] + $s['s2']) * z_proc1 * $adm_proc / 100; // % от общей суммы продаж услуг
                } else {
                    $v['p1'] = $pubZp * $adm_proc; // общий заработок
                    if(empty($v['p2']))$v['p2'] = 0; // индивидуальный заработок
                }
                $v['ost'] = $v['oklad'] + $v['p1'] + $v['p2'] - $s2 - $v['avans'] - $v['fine'] - $v['zp'] + $v['ost0'];
            } else {
                $v['p1'] = $v['p2'] = $v['ost'] = 0;
            }
            $user_0[$u] = $v;
        }
        $s['old_alg'] = $old_alg;
        //echo "<br>id=".$u; print_r($user_0[266]);

        return array($s, $user_0);
    }


}
