<?

class Kart{
    const db_prefix=db_prefix;
    public $message;
    private $kart;

    public function __construct($kart = null){
        if(is_array($kart)){
            $this->$kart=$kart;
        }elseif($kart>0)
            $this->kart=$this->GetKart($kart);
        if($this->kart==null)echo "Ошибка в номере карты ".$kart;
        else{
            $this->kart['tovar']=new Tovar($this->kart['tovar']);
        }

        //echo "<br><br>";var_dump($this->kart);
    }
    public function __destruct() {

    }
    function __get($property){// - вызывается при обращении к неопределенному свойству
        if(isset($this->kart[$property])){return $this->kart[$property];
        }elseif($property=='discount'){
            $discount=[];
            if(preg_match_all('/discount(\d)=(\d+)%/is',$this->tovar->description, $res, PREG_SET_ORDER)){
               foreach($res as $val)$discount[intval($val[1])]=$val[2];
            }
            return $discount;
        }


        if(is_null($this->kart[$property])){
            return null;
        }else die("Нет свойства Kart::".$property);
    }
    function __set($property,$value){// - вызывается, когда неопределенному свойству присваивается значение

    }

    static function GetKart($kart){
        if(!$kart)return null;
        return DB::Select('kart',$kart);
    }

    static function VIP($klient){
        if(is_array($klient))$klient=$klient['id'];
        // ищу все абонементы, предоставляющие скидки
        $abon=[];
        $add='';
        $query=DB::sql("SELECT * FROM `".self::db_prefix."tovar` WHERE type=".tTYPE_ABON." and description LIKE '%discount%'");
        while($tovar = DB::fetch_assoc($query)){
            $discount=[];
            if(preg_match_all('/discount(\d)=(\d+)%/is',$tovar['description'], $res, PREG_SET_ORDER)){
                foreach($res as $val)$discount[intval($val[1])]=$val[2];
            }
            $tovar['discount']=$discount;
            $abon[$tovar['id']]=$tovar;
            $add.=', '.$tovar['id'];
        }
        if(!$add)return null; // нет товаров со скидкой
        $query=DB::sql("SELECT * FROM `".db_prefix."kart` WHERE user='".$klient."' and ost>0 and dat_end>='".date("Y-m-d")."' and tovar in (".substr($add,2).") ORDER BY DAT_END DESC LIMIT 1");
        if(!($data = DB::fetch_assoc($query)))return null;
        $data['tovar']=$abon[$data['tovar']];
        return $data;
    }

    /** добавление нового абонемента или изменение его
     * @param $tovar
     * @param $klient
     * @param int $id
     * @return int|string
     */
    static function Add($tovar,$klient,$id=0){
        if(!is_array($tovar)){// получаю информацию об услуге
            if(!($tovar = DB::Select('tovar',"id='".$tovar."' and type=".tTYPE_ABON)))Out::err("Неверный код услуги!");
        }else{
            if(isset($tovar['parent']))$tovar=$tovar['parent'];
            if($tovar['type']!=tTYPE_ABON)Out::err("Неверный код услуги!");
        }
        if($tovar['kol']==0)$tovar['kol']=$tovar['price']; // это сертификат
        $dat_end=date("Y-m-d",strtotime("+".$tovar['srok']." month"));

        if($id>0){
            // проверяю, есть ли такой абонемент
            if(($kart = DB::Select('kart',$id))){
                if($klient>0 && $klient!=$kart['user']){
                    $query=DB::Select("zakaz2","kart='".$id."' and tovar<>'".$tovar['id']."'");
                    if(DB::num_rows($query))Out::err("Если по абонементу были списания, изменить клиента у абонемента нельзя!");
                    $add=", user='".$klient."'";
                }else{
                    $add="";
                }
                DB::sql("UPDATE `".self::db_prefix."kart`
		                SET `time`='".date('Y-m-d H:i:s')."', `dat_end`='".$dat_end."', `tovar`='".$tovar['id']."'".$add."
		                WHERE id='".$id."'"); // , `ost`='".$tovar['kol']."'
                return $id;
            }
        }//else $id=nextId('kart');

        // получаю информацию о клиенте
        if(!($klient = User::GetUser($klient)))Out::err("Неверный код клиента!");

        DB::sql("INSERT INTO `".self::db_prefix."kart` (".($id>0?" `id`, ":"")."`time`, `dat_end`, `user`, `tovar`, `ost`) ".
	            "VALUES (".($id>0?"'".$id."', ":"")."'".date('Y-m-d H:i:s')."', '".$dat_end."', '".$klient['id']."', '".$tovar['id']."', '".$tovar['kol']."')");
        return DB::id();
    }

    /** удаление абонемента или сертификата
     * @param $id
     * @return string
     */
    static function Del($id){
        isPrivDel('kart',$id); // проверяю, что абонементы создан сегодня или админ */
        // если по абонементу/сертификату были продажи - сначала удалить продажи
        $query=DB::sql("SELECT * FROM `".self::db_prefix."zakaz2` WHERE kart='".$id."' or sertif='".$id."' ORDER BY id DESC");
        while($data = DB::fetch_assoc($query)){
            $tovar=Tovar::GetTovar($data['tovar'],1); if(!$tovar)return "Нет абонемента № ".$data['tovar'];
            if(($tovar['type']==tTYPE_USLUGA)/*услуги по абонементу - увеличиваю остаток*/
               ||($tovar['type']==tTYPE_ABON)){/* удаление продажи абонемента*/
                Zakaz::Del($data['id'],true);
            }
        }
        //if(DB::num_rows($query)>0)Out::err("По абонементу есть учтенные услуги.<br>Удаление невозможно!");
        DB::log('kart', $id, 'удаление');
        DB::Delete("kart",$id);
        if(DB::affected_rows()<1)return "Не удалил id:".$id;
        //DB::sql("alter table `".self::db_prefix."kart` auto_increment=1;");
        return '';
    }
    static function is_Sertif($id){

    }

    /** ajax-запрос информации по номеру абонемента при оказании услуги по абонементу
     * @param integer $id - номер абонемента
     * @return array
     */
    static function getAbonement($id)
    {
        if ($data = DB::Select('kart',$id)) {
            $data['type'] = '1';
            $data['kol.max'] = $data['ost'];
            $data['kol'] = $data['ost'];
            if ($data['dat_end'] < date('Y-m-d')) $data['info'] = '<span class="red">Абонемент просрочен</span>';
            elseif (($t = ceil((strtotime($data['dat_end']) - time()) / 60 / 60 / 24)) < 7) $data['info'] = '<span class="green">осталось ' . $t . ' ' . num2word($t, ["день", "дня", "дней"]) . '</span>';
            if ($tovar = Tovar::GetTovar($data['tovar'])) {
                if (!isset($tovar['parent']) || $tovar['parent']['type'] != tTYPE_ABON) Out::err("Это не абонемент!");
                $data['tovar'] = $tovar['name']; // исходный товар
                $data['tovar_cs'] = $tovar['id'];
                $data['ed'] = $tovar['ed'];
                if ($tovar['kol'] == 1) {
                    $data['kol.max'] = min($data['kol'], $data['kol.max']);
                    $data['kol.readOnly'] = 'true';
                }
//elseif($tovar['parent']['kol']==0)Out::err("Это сертификат!");
            } else Out::err("Нет такого!");

            if (($klient = new User($data['user']))) {
                $data['klient'] = $klient->fullname. " " . Out::format_phone($klient->tel);
                $data['klient_cs'] = $klient->id;
            }else Out::err("Нет клиента!");

            $data = Tovar::GetPrognoz($klient->id, new Tovar($tovar), $data);
            unset($data['kol']);
            return $data;

        }else Out::err("Нет такого!");

    }
}// class Kart

