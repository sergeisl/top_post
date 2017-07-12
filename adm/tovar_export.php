<?
include_once $_SERVER['DOCUMENT_ROOT'] . "/include/config.php";
header("Content-Type: text/csv; name=\"price.csv\"");
header("Content-Disposition: inline; filename=\"price.csv\"");

$query=DB::sql('SELECT * FROM '.db_prefix.'tovar WHERE type IN('.tTYPE_TOVAR.','.tTYPE_RASX.') ORDER BY type,brand DESC,collection,kod_prodact,ean');
while ($data = DB::fetch_assoc($query)){
    // 0Код;1коллекция;2Наименование;3Описание;4Кол-во/Объем;5Цена;6Цена прихода;7Категории;8Вид;9Бренд
    echo $data['kod_prodact'].';'.DB::GetName('collection',$data['collection']).';"'.addslashes(trim($data['name'])).'";"'.
        addslashes(trim($data['description'])).'";'.$data['kol'].';'.
        (strtotime($data['upd'])>strtotime("-3 day")?'+':'').$data['price'].';'.$data['price0'].';'.
        '"'.implode(',',array_keys(Tovar::_GetVar($data,'category'))).'";'.$data['type'].';'.addslashes(DB::GetName('brand',$data['brand']))."\r\n";
}
