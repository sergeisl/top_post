<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
if(!User::is_admin(User::is_admin(!0))) {
    Out::error("Необходимо войти или зарегистрироваться!");
    Out::Location('/user/login.php');
}
// после ввода абонемента проверяю остаток и когда был последний раз, прогнозирую очередное кол-во минут загара, заполняю поле клиент
// вывожу остаток и когда был последний раз
// после клиента вывожу когда был последний раз и если есть непросроченные абонементы на эту услугу - заполняю № абонемента

$h1="Работа";
$title=$h1." ".SHOP_NAME;
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
if( date("d",strtotime("last day of month"))==date("d") || date("d")==1){
    echo "<div class='info'>Подготовьте БСО!<br>
    2 дня пишем, 2-нет, в праздники НЕ пишем: 1-9 января, 23 февраля, 8 марта, 1 мая, 9 мая, 12 июня, 4 ноября</div>";
}
// Проверка изменения времени оператором
if(empty($_SESSION['last_time']) || $_SESSION['last_time'] > time() || $_SESSION['last_time']<strtotime('-2 day') ){
    $_q=DB::Select("zakaz","1=1 ORDER by time DESC");
    $_SESSION['last_time']= ($_q ? strtotime($_q['time']) : time() );
    unset($_q);
}
if( $_SESSION['last_time'] > time() || $_SESSION['last_time']<strtotime('-2 day')){
    echo "<div class='info'>Ошибочное системное время! Последняя продажа ".date("d.m.y в H:i",$_SESSION['last_time'])." Срочно сообщите руководству!</div>";
    DB::log('time', 0, 'Время',date("d.m.y H:i:s",$_SESSION['last_time']) , date("d.m.y H:i:s",time()) );
}
//$_SESSION['last_time'] = time();

?>
    <div class="fr">
    <a href="/adm/tovar.php">Прайс</a>
    <a href="/adm/kart.php">Абонементы</a>
    <a href="/adm/counters.php">Счетчики</a>
    <a href="/adm/report.php">Отчет</a>
</div>
    <h1><?=$h1?></h1>


    <!--<a href="work_form.php?type=<?/*=tTYPE_ABON_USLUGA*/?>" class="button ajax">Оказание услуг по абонементу</a>-->
    <a href="work_form.php?type=<?=tTYPE_ABON?>" class="button ajax">Продажа абонемента</a>
    <a href="work_form.php?type=<?=tTYPE_USLUGA?>" class="button ajax">Оказание услуг</a>
    <a href="work_form.php?type=<?=tTYPE_TOVAR?>" class="button ajax">Продажа косметики</a>
    <a href="work_form.php?type=<?=tTYPE_RASX?>" class="button ajax">Расходка</a>
<? // вывожу кнопки расходки
$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type='".tTYPE_RASX."' and price between 1 and 50 ORDER BY name");
while ($tovar = DB::fetch_assoc($query)){
    $tovar=new Tovar($tovar);
	echo "\n\t<a href='/shop/api.php?sale&type=".tTYPE_RASX."&tovar=".$tovar->id."' class='button ajax'>".$tovar->show_name."</a>";
}

/*'tbl'=>db_prefix.'zakaz as zakaz,'.db_prefix.'zakaz2 as zakaz2', 'sql'=>'WHERE zakaz.id=zakaz2.zakaz and time >"'.date("Y-m-d 00:00:00",strtotime("-1 month")).'"',*/
   $bar=new kdg_bar([
       'tbl'=>db_prefix.'zakaz',
       'sql'=>' WHERE time >"'.date("Y-m-d 00:00:00",strtotime("-1 month")).'" ORDER BY time DESC',
       'perpage'=>5]);
   $bar_out=$bar->out();
//   echo "<div>\nвсего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."</div>";
?>
 <table class="client-table" data-tbl="zakaz2" data-api="/shop/api.php">
 <tr>
  <th>Время</th>
  <th>ФИО</th>
  <th>Товар / Услуга</th>
  <th>Кабинка</th>
  <th>Абонемент №</th>
  <th>Кол-во</th>
  <th>Скидка</th>
  <th>Сумма</th>
  <th>&nbsp;</th>
 </tr>
<?
$query=$bar->query();//DB::sql('SELECT * FROM '.$bar->tbl.$bar->sql.' LIMIT '.$bar->start_pos.', '.$bar->perpage);
//$query=DB::sql('SELECT zakaz2.*, zakaz.time, zakaz.klient, zakaz.user FROM '.$bar->tbl.$bar->sql.' ORDER BY zakaz2.id DESC LIMIT '.$bar->start_pos.', '.$bar->perpage);
while ($data = DB::fetch_assoc($query)){
    echo Zakaz::_Out($data);
//echo "<br>\n";print_r($data);
}
?>
 </table>
<?
echo "\n<br class='clear'><div> всего: <b>".$bar->count."</b>. &nbsp; ".$bar_out."\n</div>";

echo "\n<br class='clear'>";
$query=DB::sql('SELECT SUM(IF(tovar.type=0,zakaz2.kol*zakaz2.price,0)) as s0, SUM(IF(tovar.type=1,zakaz2.kol*zakaz2.price,0)) as s1, SUM(IF(tovar.type=2,zakaz2.kol*zakaz2.price,0)) as s2, SUM(IF(tovar.type=3,zakaz2.kol*zakaz2.price,0)) as s3
	FROM '.db_prefix.'zakaz2 as zakaz2, '.db_prefix.'zakaz as zakaz, '.db_prefix.'tovar as tovar,'.db_prefix.'user as user
	WHERE zakaz2.zakaz=zakaz.id and zakaz2.tovar=tovar.id and zakaz.user=user.id and (user.adm<5 or user.adm IS NULL) and zakaz.time>="'.date('Y-m-d').'"');
if(!($s = DB::fetch_assoc($query)))$s['s0']=$s['s1']=$s['s2']=$s['s3']=0;

$query=DB::sql('SELECT SUM(zakaz.visa) as visa
		FROM '.db_prefix.'zakaz as zakaz
		WHERE  zakaz.time>="'.date('Y-m-d').'"');
$s['visa']=(($data=DB::fetch_assoc($query))? $data['visa'] : '');

$s['last']=$s['lastvisa']=$s['count']=0;
$query=DB::sql('SELECT * FROM '.db_prefix.'zakaz WHERE time>="'.date('Y-m-d').'" ORDER BY id DESC');
if($data = DB::fetch_assoc($query)){
  $s['count']=DB::num_rows($query);
  $s['lastvisa']=floatval($data['visa']);
  //$query=DB::sql('SELECT sum(summ) as summ FROM '.db_prefix.'zakaz2 WHERE zakaz="'.$data['id'].'"');
  $s['last']=floatval(DB::Sum('zakaz2','zakaz="'.$data['id'].'"', 'kol*price'));
}

//$s['ost']=GetOst(strtotime('2014-06-24 00:00:00')); // остаток в кассе на начало дня
$s['ost']=Money::GetOst(); // остаток в кассе на начало дня

/*$query=DB::sql('SELECT SUM(`summ`) as s FROM '.db_prefix.'incasso WHERE `time`>="'.date('Y-m-d').'" and rej<5'); // считаю инкасации
if($data = DB::fetch_assoc($query))$s['inc']=floatval($data['s']);
else $s['inc']=0;*/

$s['inc']=floatval(DB::Sum('incasso','`time`>="'.date('Y-m-d').'" and rej<5', 'summ')); // считаю инкасации

//var_dump($s);
// считаю минуты по видам оборудования в разрезе услуг
$query=DB::sql('SELECT sum(zakaz2.kol) as kol, IF(zakaz2.time<"2016-",device,0) as device, zakaz2.tovar as tovar
	FROM '.db_prefix.'zakaz2 as zakaz2, '.db_prefix.'zakaz as zakaz, '.db_prefix.'tovar as tovar
	WHERE zakaz2.zakaz=zakaz.id and zakaz2.tovar=tovar.id and zakaz.time>="'.date('Y-m-d').'" and tovar.type='.tTYPE_USLUGA.' GROUP BY tovar,device');
if (DB::num_rows($query) > 0) {
    $ar = [];
    $maxd = 0;
    $mind = 99;
    while ($data = DB::fetch_assoc($query)) {
        $t = intval($data['tovar']);
        $d = intval($data['device']);
        $ar[$t][$d] = $data['kol'];
        $maxd = max($maxd, $d);
        $mind = min($mind, $d);
    }

    echo "<div class=\"box1\"><table><tr>\n\t\t<th>Наименование</th>";
    for ($i = $mind; $i <= $maxd; $i++) echo "\n\t\t<th>Каб." . $i . "</th>";
    echo "\n\t\t</tr>";

    foreach ($ar as $k => $v) {
        $tovar = new Tovar($k);
        echo "\n\t<tr><td>" . $tovar->name . "</td>";
        for ($i = $mind; $i <= $maxd; $i++) echo "\n\t\t<td style=\"text-align:center\">" . (isset($ar[$k][$i]) ? $ar[$k][$i] : '&nbsp;') . "</td>";
        echo "</tr>";
    }
    echo "</table></div>";
}
?>
<div class="box2"><table>
<tr><td>Последний клиент<span class="hlp" onclick="return Visa(<?=ceil($s['last'])?>)" title='Оплата банковской картой' style='width:auto;padding:0 2px;'>Visa<?=($s['lastvisa']?$s['lastvisa']:'')?></span>:</td><td class='right'><b><?=ceil($s['last'])?></b></td></tr>
<tr><td>Всего: Людей <b><?=$s['count']?></b>, выручка:</td><td class='right'><?=ceil($s['s0']+$s['s1']+$s['s2']+$s['s3'])?></td></tr>
<tr><td class='right'>в т.ч.косметика:</td><td class='right'><?=ceil($s['s0'])?></td></tr>
<tr><td class='right'>услуги:</td><td class='right'><?=ceil($s['s1'])?></td></tr>
<tr><td class='right'>абонементы:</td><td class='right'><?=ceil($s['s2'])?></td></tr>
<tr><td class='right'>расходка:</td><td class='right'><?=ceil($s['s3'])?></td></tr>
<?=($s['visa']?"<tr><td class='right'>по пластиковым картам:</td><td class='right'>".$s['visa']."</td></tr>":"")?>
<tr><td>Остаток в кассе:</td><td class='right'><?=ceil($s['ost']+($s['s0']+$s['s1']+$s['s2']+$s['s3'])-$s['inc']-$s['visa'])?></td></tr>
<tr><td><a href="#" onclick="return ajaxLoad('','/adm/work_form.php?type=99')">инкассация</a> <span class="hlp" onclick="return ajaxLoad('','/shop/api.php?inkasso')">?</span>:</td><td class='right'><?=$s['inc']?></td></tr>
</table></div>
<script>
    document.onkeydown = function(event) {
        runOnKeys(event, "E", "r");
    };
    let obj = {};
    function runOnKeys(event) {
        let letters = [].slice.call(arguments, 1);//вытаскиваю символы
        let char = [];//сюда положу символы полученные из кодов символов
        let codes = []; //сюда положу коды нужных для сравнения символов
        let keyCode = event.keyCode;
        for (var i = 0; i < letters.length; i++) {
            codes.push(letters[i].toUpperCase().charCodeAt(0));
            obj[keyCode] = true;
            if (!obj[codes[i]]) return;//если нет совпадения по имени свойства то выход
            char.push(String.fromCharCode(codes[i]));//если все правильно, то добавляю символ
        }
        //если указан один символ, но с ним нажат еще символ(ы), то выход
        if(codes.length != Object.keys(obj).length) return;
        console.log('Нажаты клавиши: ' + '${char}');
        obj = {};
    }
    document.onkeyup = function(event) {
        delete obj[event.keyCode];
    };
    /*addEvent(document, 'keydown', function(e) {
        // спец. сочетание - не обрабатываем
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        var char = getChar(e);

        if (!char) return; // спец. символ - не обрабатываем

        console.log(char);

        return false;
    });
*/
    function getChar(event) {
        if (event.which == null) { // IE
            if (event.keyCode < 32) return null; // спец. символ
            return String.fromCharCode(event.keyCode)
        }

        if (event.which != 0 && event.charCode != 0) { // все кроме IE
            if (event.which < 32) return null; // спец. символ
            return String.fromCharCode(event.which); // остальные
        }

        return null; // спец. символ
    }
</script>
<?

include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";

