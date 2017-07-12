<?php

class Schedule
{

    /** формирование расписания
     * @param string $date
     * @param string $format
     */
    static function Table($date = '', $format = 'html')
    {
        global $_DayOfWeek;
        if (empty($date)) $date = time();
        $rows = DB::Select2Array('schedule', '"' . date('Y-m-d', $date) . '" BETWEEN date0 and date_end ORDER BY hour, day');
        $Schedule = [];
        foreach ($rows as &$row) $Schedule[$row['hour']][$row['day']] = $row;
        for ($i = 8; $i <= 21; $i++) if (!isset($Schedule[str_pad($i, 2, '0', STR_PAD_LEFT)])) $Schedule[str_pad($i, 2, '0', STR_PAD_LEFT) . ':00'][9] = [];
        if ($format == 'html') {
            echo "<table data-api='/shop/api.php?act=schedule&ret_path=".urlencode($_SERVER['REQUEST_URI'])."' class='modal'><thead><tr><th>&nbsp;</th>";
            for ($i = 1; $i <= 7; $i++) echo "<th>" . $_DayOfWeek[($i < 7 ? $i : 0)] . "</th>";
            echo "</tr></thead><tbody>";
            foreach ($Schedule as $hour => $rows) {
                echo "<tr><td>" . $hour . "</td>";
                for ($i = 1; $i <= 7; $i++){
                    $DayOfWeek=($i < 7 ? $i : 0);
                    $class=( date("w")==$i && date("H")==intval($hour) ? "class='today' ":'');
                    if (!empty($rows[$DayOfWeek])){
                        $row=$rows[$DayOfWeek];
                        $teacher=DB::Get('user', $row['manager'],'fullname');
                        echo "<td ".$class."data-id='".$row['id']."'>" . (isset($_REQUEST['print'])?'<small>'.$teacher.'</small>': $teacher ) . "<br>". BuildUrl('pages', $row['pages'],1) . "</td>";
                    } else {
                        echo "<td ".$class."data-hour='".$hour."' data-day='".$DayOfWeek."'>&nbsp;</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        }
    }

    /** нажали на ячейку в расписании
     * @param array $options hour, day, id
     */
    static function ScheduleOne($options=[]){
        global $_DayOfWeek;
        if (empty($date)) $date = time();
        $schedule=DB::Select('schedule',(!empty($options['id'])?intval($options['id']):'hour="'.$options['hour'].'" and day="'.$options['day'].'" and "' . date('Y-m-d', $date) . '">=date0 and "' . date('Y-m-d', $date) . '"<=date_end ORDER BY hour, day'));
        //echo "<br>".DB::$query;
        if($schedule){
            if(User::is_admin())echo '<a title="Изменить" class="icon edit modal" style="margin:30px 0 0 0" href="/adm/sprav.php?layer=schedule&amp;form='.$schedule['id'].'"></a><br>'.
                '<a title="Удалить тренировку и отменить все записи" class="icon del modal confirm" style="margin:35px -20px 0 0" href="/api.php?tbl=schedule&amp;del='.$schedule['id'].'"></a>';
            // получаю ближайшую дату занятия
            $d=( (date("w")<$schedule['day']||(date("w")==$schedule['day']&&date("H:i")<=$schedule['hour'])) ? $schedule['day'] : $schedule['day']+7);
            $w=strtotime("Last Sunday + $d Days".' '.$schedule['hour']);
            $ListRecorded=self::ListRecorded($schedule['id'],$w);
            $c=count($ListRecorded);
            $c1="Записано: " . $c; if($schedule['size']>0 && $c>=$schedule['size'])$c1="<span class='red'>".$c1."</span>";
            echo "Занятие: ". BuildUrl('pages', $schedule['pages'],1) . "<br>".
                "Тренер: <a href='/user/api.php?getComment=".$schedule['manager']."' class='ajax'>" . User::_GetVar($schedule['manager'],'fullname') . "</a><br>\n".
                "Дата: " . date('d.m.Y в H:i',$w) .'('.$_DayOfWeek[$schedule['day']].')'. "<br>\n".
                $c1." человек".num2word($c,['','а','']).
                ($schedule['size']?", всего мест: " . $schedule['size']:''). "<br>\n";

            // проверяю кол-во свободных мест

            if(User::is_admin(!0)){ // админ может записывать и выписывать любого и по-любому
                if($ListRecorded){
                    echo "<ol>";
                    foreach($ListRecorded as $row)echo "\n<li>".User::_GetVar($row['user'],'url').
                        " <a href='/shop/api.php?act=check_out&klient_cs=".$row['user']."&schedule=".$row['device']."&time=".$w."' class=\"icon del right confirm\" title=\"Выписать\"></a></li>";
                    echo "</ol>";
                }
                //var_dump($options);
                ?>
                <form action="/shop/api.php" name="work" onsubmit="if(getValue(this.klient_cs)>0)SendForm('',this);return !1;" method="post">
                    <input type='hidden' name='tovar_cs' value="<?=$schedule['tovar']?>">
                    <input type='hidden' name='schedule' value="<?=$schedule['id']?>">
                    <input type='hidden' name='time' value="<?=$w?>">
                    <input type='hidden' name='act'>

                    <label>Кого: <input name="klient" id="klient" size="50" href="/user/api.php?get=" value="" value_cs="" after="afterKlient()" autofocus></label><br><br>
                    <!--<input type='submit' name='check_out' class='btn red' value="Выписать" onclick="this.form.act.value=this.name">-->
                    <input type='submit' name='check_in' class='btn green ma' value="Записать" onclick="this.form.act.value=this.name">
                </form>
                <script>getObj("klient").focus();</script>
                <?
            }elseif(self::recordedInSchedule($schedule['id'],$w)){// если я записан, и время позволяет, предложить отписаться
                echo "<div class='message'>Вы записаны на это занятие.</div><br>";
                if($w>strtotime('-'.check_out_before)) echo "<a href='/shop/api.php?act=check_out&schedule=".$schedule['id']."'>Выписаться</a>?";
            }elseif(User::is_confirm()){
                if($schedule['size']>0 && $c>=$schedule['size']) {
                    echo "<div class='message'>Все места на это занятие заняты</div>";
                    // todo если мест нет, предложить уведомить, если кто-то выпишется
                }else{
                    echo "<a href='/shop/api.php?act=check_in' class='btn green'>Записаться</a>";
                }
            }else{
                echo "<div class='error hide'></div><a href='#' class='btn green' onclick='updateObj(this.previousSibling,\"Чтобы записаться на занятие, необходимо приобрести абонемент!\");return false'>Записаться</a>";
            }
        }else{
            if(User::is_admin()){
                //echo '<a title="Добавить" class="icon add" onclick="return ajaxLoad(\'\',this.href)" href="/adm/sprav.php?layer=schedule&amp;form=0&'.http_build_query($options).'"></a>';
                // НЕ заменять, иначе не перейдет в модальном окне
                header('location: /adm/sprav.php?layer=schedule&form=0&'.http_build_query($options));
                exit;
            }
            else echo 'В этот день в это время тренировки нет!';
        }
    }

    /** записан ли пользователь на данное занятие
     * @param integer $schedule
     * @param integer $dat - если не указана, то на очередное
     * @param int $klient - если не указан, то текущий
     */
    static function recordedInSchedule($schedule, $dat, $klient=0){
        if(!$klient)$klient=User::id();
        //$schedule=DB::Select('schedule',intval($schedule)); if(!$schedule)Out::err('Неверный код записи!');
        $rows=DB::SelectSql('SELECT zakaz2.* FROM '.db_prefix.'zakaz as zakaz,'.db_prefix.'zakaz2 as zakaz2 '.
            'WHERE zakaz2.zakaz=zakaz.id and zakaz.user='.intval($klient).' and zakaz2.device='.intval($schedule).' and zakaz2.time LIKE "'.date('Y-m-d',$dat).'%"');
        return ($rows?$rows[0]:false);

    }

    /** возвращает кол-во записавшихся на занятие
     * @param integer $schedule
     * @param integer $dat
     * @return int
     */
    static function Count($schedule, $dat){
        return DB::Count('zakaz2', 'zakaz2.device='.intval($schedule).' and zakaz2.time LIKE "'.date('Y-m-d',$dat).'%"');
    }

    /** возвращает список записавшихся на занятие
     * @param integer $schedule
     * @param integer $dat
     * @return array
     */
    static function ListRecorded($schedule, $dat){
        $rows=DB::SelectSql('SELECT zakaz2.*,zakaz.* FROM '.db_prefix.'zakaz as zakaz,'.db_prefix.'zakaz2 as zakaz2 '.
            'WHERE zakaz2.zakaz=zakaz.id and zakaz2.device='.intval($schedule).' and zakaz2.time LIKE "'.date('Y-m-d',$dat).'%"');
        //die(DB::$query);
        return $rows;
    }

    /** записать пользователя на данное занятие
     * @param array $ar  - klient_cs, schedule, time-integer!
     */
    static function check_in($ar)
    {
        if(empty($ar['schedule']))Out::err('Неверный код записи!');
        $schedule=DB::Select('schedule',intval($ar['schedule'])); if(!$schedule)Out::err('Неверный код записи!');
        if(!$schedule['tovar'])if(!$schedule)Out::err('В справочнике занятий не указан продаваемый товар!');
        $klient=DB::Select('user',intval($ar['klient_cs'])); if(!$klient)Out::err('Неверный код клиента!');
        if(empty($ar['time']))Out::err('Не передана дата на которую записываете!');
        if(self::recordedInSchedule($ar['schedule'], $ar['time'], $klient['id'])) {
            Out::err($klient['name'] . ' на это занятие уже был записан!');
        }
        // если у данного клиента есть абонемент на этот вид занятий
        $kart=Tovar::getAbonement($klient,$schedule['tovar']);
        if(empty($kart)&&!User::is_admin(!0))Out::err('Нет абонемента или он просрочен!');
        // тут или админ или есть абонемент
        $ar['type']=(empty($kart)?tTYPE_USLUGA:tTYPE_ABON_USLUGA);
        $ar['comment']='Запись';
        $ar['klient_cs']=$klient['id'];
        $ar['device']=$ar['schedule'];
        if(empty($ar['tovar']))$ar['tovar']=SCHEDULE_TOVAR;
        if(!empty($kart['id']))$ar['kart']=$kart['id'];
        return Tovar::Sale($ar);
    }

    /** выписать пользователя с данного занятия
     * @param array $ar  - klient_cs, schedule, time-integer!
     */
    static function check_out($ar)
    {
        //var_dump($ar); exit;
        $schedule=DB::Select('schedule',intval($ar['schedule'])); if(!$schedule)Out::err('Неверный код записи!');
        $klient=DB::Select('user',intval($ar['klient_cs'])); if(!$klient)Out::err('Неверный код клиента!');
        $row=self::recordedInSchedule($ar['schedule'], $ar['time'], $klient['id']);
        if(!$row){
            $mes=$klient['name'].' на это занятие не записан!';
            AddToLog($mes.var_export($ar,!0).'<br>'.DB::$query, 'Error report', !0);
            Out::err($mes);
        }
        //var_dump( $row, strtotime($row['time']) ); exit;
        $check_out_before=strtotime('-'.check_out_before, strtotime($row['time']));
        if($check_out_before<time())Out::err('Время чтобы выписаться истекло '.date('d.m.y в H:i:s',$check_out_before).'!');
        $mes=Zakaz::Del($row['id']);
        if($mes)Out::message($mes);
        return empty($mes);
    }

}
