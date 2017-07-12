<?
/**
 */
class Favorites
{

    static function Add($id)
    {
        if (!User::is_confirm()) {
            Out::error("Добавлять в избранное могут только зарегистрированные пользователи!");
        } else {
            DB::sql("INSERT IGNORE INTO `" . db_prefix . "favorites` (`object`,`user`,`date0`) VALUES ( '" . intval($id) . "','" . User::id() . "','" . date('Y-m-d') . "')");
            Out::message((mysqli_affected_rows(DB::$link) > 0 ? 'Добавлено в избранное!' : 'Уже было добавлено в избранное!'));
        }
    }

    static function Del($id)
    {
        if(!User::is_confirm())Out::err("Ошибка доступа!");
        $id=intval($id);
        DB::Delete("favorites","object='".intval($id)."' and user='".User::id()."' LIMIT 1");
        if(mysqli_affected_rows(DB::$link)>0){
            Out::mes("","removeID(obj)");
        }else Out::err("Не удалил!");
    }
}
