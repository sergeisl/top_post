<? // Админка: отображение информации о ВСЕХ заказах
// возможность разрешить оплатить заказ или внести в него изменения

include_once $_SERVER['DOCUMENT_ROOT'] . "/include/config.php";
//include_once $_SERVER['DOCUMENT_ROOT']."/price/zakaz.inc.php";

if (!User::is_admin(uADM_MANAGER)) {
    Out::error('Необходима авторизация!');
    Out::Location('/');
}

if (isset($_GET['no'])) {
    $id = intval($_GET['no']);
    Zakaz::WriteHeader(['id' => $id, 'status' => 2, 'answer' => urldecode(@$_GET['comment']), 'time_end' => date('Y-m-d H:i:s'), 'manager' => User::id()]);
    Zakaz::SendUserMail($id, "который к сожалению, мы не можем выполнить.");
    die("Отклонен");

} elseif (isset($_GET['yes'])) {
    $id = intval($_GET['yes']);
    $time_end = strtotime('+3 days');
    Zakaz::WriteHeader(['id' => $id, 'status' => 3, 'time_ok' => date('Y-m-d H:i:s'), 'time_end' => date('Y-m-d H:i:s', $time_end), 'manager' => User::id()]);
    $zakaz = Zakaz::Get($id);
    $body = "Ваш товар зарезервирован.<br>\n\tВам необходимо его оплатить до <b>" . date('H:i d.m.Y', $time_end) . "</b><br>\n";
    if ($zakaz['forma'] == 2) $body .= "\tДля оплаты перейдите по ссылке: <a href='http://" . Get::SERVER_NAME() . "/user/zakaz.php?pay=" . $id . "'>Оплатить</a>.";
    else $body .= "\tОб оплате сообщите: <a href='http://" . Get::SERVER_NAME() . "/we.php'>Контакты</a>.";
    Zakaz::SendUserMail($zakaz, $body);
    // todo дублирую на СМС
    die("Подтвержден");

} elseif (isset($_GET['long'])) {
    $id = intval($_GET['long']);
    $time_end = strtotime('+1 days');
    Zakaz::WriteHeader(['id' => $id, 'time_end' => date('Y-m-d H:i:s', $time_end), 'manager' => User::id()]);
    $zakaz = Zakaz::Get($id);
    $body = "Ваш товар зарезервирован ещё на сутки.<br>\n\tВам необходимо его оплатить до <b>" . date('H:i d.m.Y', $time_end) . "</b><br>\n";
    if ($zakaz['forma'] == 2) $body .= "\tДля оплаты перейдите по ссылке: <a href='http://" . Get::SERVER_NAME() . "/user/zakaz.php?pay=" . $id . "'>Оплатить</a>.";
    else $body .= "\tОб оплате сообщите: <a href='http://" . Get::SERVER_NAME() . "/we.php'>Контакты</a>.";
    Zakaz::SendUserMail($zakaz, $body);
    // todo дублирую на СМС
    die("Подтвержден");

    /*}elseif(isset($_GET['del'])){
        $id=intval($_GET['del']);
        $result = DB::sql('SELECT * from '.db_prefix.'zakaz2 WHERE id='.$id);
        if($row=DB::fetch_assoc($result)) {
          // если это последний товар в заказе - удаляю заказ
          DB::sql('DELETE FROM '.db_prefix.'zakaz2 WHERE id='.$id);
          $result = DB::sql('SELECT * from '.db_prefix.'zakaz2 WHERE zakaz='.$row['zakaz'].' LIMIT 1');
          if(DB::num_rows($result)==0){
             DB::sql('DELETE FROM '.db_prefix.'zakaz WHERE id='.$row['zakaz']);
             die("<td colspan='5'>Удален весь заказ!</td>");
          }
        }
        die("<td colspan='5'>Удален</td>");*/

} elseif (isset($_GET['setstatus']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    DB::sql("UPDATE " . db_prefix . "zakaz SET status='" . intval($_GET['setstatus']) . "' WHERE id='" . $id . "' LIMIT 1");
    die("Статус изменен!");
}

$title = 'Все заказы. Компания "eIT". г.Ростов-на-Дону';
$mouse = 'left:397px;top:2px;';
include_once($_SERVER['DOCUMENT_ROOT'] . '/include/head.php');

$where = [];

if (isset($_GET['act']) && $_GET['act']) {
    $where[] = 'status between 1 AND 4';
}

if (isset($_GET['id'])) {
    $where[] = "id = " . intval($_GET['id']);
}
$result = DB::rows(Zakaz::TABLE_NAME, $where, ' * ', 'time DESC');
foreach($result as &$row) {
    $row = Zakaz::Get($row);
    $row['user_name'] = DB::GetName('user', $row['user']);
    if ($row['status'] == 3 && date('Y-m-d H:i:s') > $row['time_end']) {
        $row['status'] = 6;
        DB::sql("UPDATE " . db_prefix . "zakaz SET status=6 WHERE id='" . $row['id'] . "' LIMIT 1");
        Zakaz::SendUserMail($row1, "Ваш товар был зарезервирован, но НЕ оплачен Вами.<br>
		Время, резерва истекло и товар может быть продан другому покупателю.<br>
		Если Вам ещё нужен этот или другой товар нашего магазина Вы можете сделать новый заказ.<br>\n");
    }
    $row['items'] = DB::rows( Zakaz::ITEMS_TABLE_NAME, ['zakaz' => $row['id']]);
    foreach ($row['items'] as &$it) {
        $tovar = new Tovar($it['tovar']);
        $it['tovar_name'] = $tovar->name;
        $it['tovar_url'] = $tovar->url;
    }
}

echo echoBlock('adm/zakaz', [ 'zakaz' => $result] );
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/tail.php";
