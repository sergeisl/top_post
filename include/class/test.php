<?php


class Test
{

    const TABLE_NAME                = 'tests';
    const LEGEND_TABLE_NAME         = 'test_legend';
    const STEP_TABLE_NAME           = 'test_steps';
    const STEP_VALUES_TABLE_NAME    = 'test_step_values';

    const FIELD_URL = 'url';

    const STATUS_OFF        = 0;
    const STATUS_ON         = 1;

    static public $status_name_arr = [
        self::STATUS_OFF => 'Выключен',
        self::STATUS_ON  => 'Включен',
    ];

    private $id = 0;
    public $data = null;

    public function __construct($id)
    {

        $this->data = DB::Select( self::TABLE_NAME, (is_int($id) ? 'id' : self::FIELD_URL) . " = '". addslashes($id) . "'" );
        $this->id = get_key($this->data, 'id', 0);
    }


    public function get_data()
    {
        return empty($this->data) ? [] : $this->data;
    }

    public function id()
    {
        return intval($this->id);
    }

    public function get_steps()
    {
        $steps = DB::Select2Array( Test::STEP_TABLE_NAME, 'test_id = ' . $this->id() );
        foreach ($steps as &$it) {
            $it['items'] = DB::Select2Array( Test::STEP_VALUES_TABLE_NAME, "test_id = " . $this->id() . " and step_id = " . $it['step_id'] );
        }
        return $steps;
    }

    public function get_link()
    {
        return '/test/'.$this->url.'/';
    }

    public function del()
    {
        global $mysql;
        $mysql->delete( self::TABLE_NAME, [ 'id' => $this->id() ] );
        $mysql->delete( self::STEP_TABLE_NAME, [ 'test_id' => $this->id() ] );
        $mysql->delete( self::STEP_VALUES_TABLE_NAME, [ 'test_id' => $this->id() ] );
        $this->cache_clear();
        return true;
    }
}
