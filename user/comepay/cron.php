<?
// кроновый скрипт проверки оплат по comepay
// */15 * * * *

$_SERVER['DOCUMENT_ROOT']=dirname(dirname(__DIR__));
include_once($_SERVER['DOCUMENT_ROOT'] . '/include/config.php');

//if (defined('COMEPAY_PURSE_ID')) {
    // выбираем все не оплаченные счета у которых не стоит что он удален
    $df1 = date("Y-m-d H:i:s", time() - 60 * 60 * 24 * 30);
    $query = DB::sql('SELECT * FROM ' . db_prefix . 'payment WHERE status=9 and `time` >= "' . $df1 . '" and mes LIKE "%comepay%"');
    while ($data = DB::fetch_assoc($query)){
        $order_id = $data['id'];
        echo "\n" . $order_id . " - ";
        $Obj = ComePay::Call($data['id']);
        if(empty($Obj)){echo "Error";  break;}
        if(ComePay::TestBill($Obj,$order_id))echo "Ok";
        else echo " No";

    }
//}else echo "Не задан Comepay кошелек";
