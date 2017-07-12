<?

/**
 * @property-read String name �������� ������
 * @property-read String normal_name �������� ������ ��� NEW
 * @property-read String id ��� ������
 * @property-read String kod_prodact �������� ���(������� ������) == kod_prodact ��� �������������
 * @property-read String ean EAN-13
 * @method string ed(integer $kol) �������� ������� ��������
 * @property-read String show_name ������ ��� � ����� � ����������
 * @property-read string best_before date
 * @property-read integer cab ������ ������� ��������� ��� �������� ������
 * @property-read string dat ���� ����������
 * @property-read string date_upd ���� ����������
 * @property-read float volume ����, ��.�
 * @property-read float weight ���, ��
 * @property-read integer in_package �� � ��������
 * @property-read integer garant ��������, ���
 * @property-read integer min_zakaz ����������� ���-�� ��� ������
 * @property-read integer ost ������� �� ������
 * @property-read integer price ��������� ���� ������
 * @property-read integer price1 ������������ ���� ������
 * @property-read integer price2 ������� ���� ������
 * @property-read integer priceu ������������� ��������� ���� ������
 * @property-read integer price_old ���� ��� ������
 * @property-read integer discount ������ � %
 * @property-read integer discount_expires ���� ��������� ������
 * @property-read integer show_ost ������� �� ���� ��������� ��� ������ � ����� "�������"
 * @property-read integer gr ���������� �������� category = gr
// * @property-read integer gr "0-�����, 1-������, 2-���������, 3-��������", tTYPE_*
 * @property-read array category {id1=>1,id2=>1,...} ��� �������������� ����������� array_keys
 * @property-read String collection
 * @property-read String collection_name
 * @property-read String brand
 * @property-read String brand_name
 * @property-read String brand_url
 * @property-read String sklad
 * @property-read String sklad_name
 * @property-read String supplier
 * @property-read String supplier_name
 * @property-read String kol �����, ���-�� �� � ��������
 * @property-read String kol_name �����+��.��������� ������
 * @property-read String ed ������� ��������� ��, ��, �����
 * @property-read String url ������������� url ������
 * @property-read String Aurl url ������ ��������� � ����� ��� �����
 * @property-read String Murl url ������ ��� ������� � ������
 * @property-read Tovar child ������ �� ������� �����
 * @property-read String img �������� ������
 * @property-read String imgBig �������� ������ �������� imgBigSize
 * @property-read String imgMedium �������� ������ �������� imgMediumSize
 * @property-read String imgSmall �������� ������ �������� imgSmallSize
 * @property-read Boolean is_img �������� ������ ��������?
 * @property-read integer vitrina todo 0-�������,1-�������,2-������� �����  vitrinaN
 * @property-read array row ������ � ����������� � ������ ��� �� ����.
 */
class Tovar implements ArrayAccess{
    const db_prefix=db_prefix;
    const img_name='kod_prodact'; // 'kod_prodact' ��� �������� ����������� �� ���� ��� �� id ������
    const tbl_alias='category_alias'; // ��������� ������� ��������� ���������, ���� ����� - �� ������������
    const tbl_prixod=''; // 'prixod' - ��������� ������� ��������, ���� ����� - �� ������������
    public $Seo_url=SEO;
    public $message;
    static public $ar_info= ['best_before','cab','volume','weight','garant','in_package','min_zakaz','seo_keywords','seo_description']; // ����������, ������� ����������� � ���� info ��
    static public $ar_float= ['price','price0','price1','price2','priceu','volume','weight','garant','in_package','min_zakaz']; // �������� ����, ������� � ������� ����� ������������� � ����� ����� ������� � ��
    static public $_sklad_name= [1=>'���� � ��������',2=>'���� �� ������ � �������',3=>'���� �������� 5-10 ����',4=>'�������� ������� � ���������',9=>'������� ����������'];
    static public $ext_load= ['jpeg','jpg','gif','png',"csv","txt","ppt", "pptx", "pptm", "pps", "ppsx", "pdf", "doc", "odt", "ods", "xls", "xlt", "docx", "docm", "dot", "dotx", "xlsx", "rtf", "pot", "potx"];
    //static public $ar_gr=array(0=>"���������",1=>"������",2=>"����������",3=>"��������"); // ���� �� ����������, �� ������������ ������� gr

    /**
     * @var array|null
     */
    private $tovar=[];

    public function offsetSet($key, $value) {
        //$this->tovar[$key] = $value;
        $this->__set($key,$value);
    }
    public function offsetUnset($key) {
        unset($this->tovar[$key]);
    }
    public function offsetGet($key) {
        return $this->tovar[$key];
    }
    public function offsetExists($key) {
        return isset($this->tovar[$key]);
    }

    function __get($property){// - ���������� ��� ��������� � ��������������� ��������
        if(isset($this->tovar[$property]))return $this->tovar[$property];
        elseif(empty($this->tovar['id'])){
            return null;
        }elseif(in_array($property, self::$ar_info )){
            if(isset($this->tovar['info'][$property]))return $this->tovar['info'][$property];
            else return '';
        }elseif($property=='category'){
            if(!isset($this->tovar['category'])){
                $this->tovar['category']=[];
                /*$query=DB::sql("SELECT link.*,category.name FROM `".self::db_prefix."category_link` as link,`".self::db_prefix."category` as category
                    WHERE link.category=category.id and tovar='".addslashes($tovar['id'])."'");
                while($row=DB::fetch_assoc($query))$info[$row['category']]=$row['name'];*/
                $query=DB::sql("SELECT * FROM `".self::db_prefix."category_link` WHERE tovar='".addslashes($this->tovar['id'])."' ORDER BY category");
                while($row=DB::fetch_assoc($query))$this->tovar['category'][$row['category']]=1; // � ���� �� ������� ���������� $_POST
            }
            return $this->tovar['category'];
        }elseif($property=='unic_category'){
            foreach([3,13,4,5,10,22,21,2,20] as $category)
                if(array_key_exists($category,$this->category))return $category;
            /*22 	��������� 	���������
    21 	��������� 	bronz,���������,����������
    13 	������ 	�����,�������
    4 	��� ���� 	���, ����, �����, Scrub, Body Wash
    1 	��� ������ 	��� ������
    3 	������ �� ������ 	spf
    10 	���� 	����,����
    12 	���� 	��� ���,Legs
    2 	����� ������ 	����� ������, After Sun
    20 	������������� 	�������������,����������,hot,���������������,�����������,�����,Blush Factor
    5 	������������� ������ 	�������,�������,�������,�������
    11 	���� 	����*/
        }elseif($property=='show_name'){
            return str_replace("'","`",$this->tovar['name'].
                (!empty($this->tovar['kol'])&&$this->tovar['kol']>1?" ".($this->tovar['kol']==intval($this->tovar['kol'])?intval($this->tovar['kol']):$this->tovar['kol']).$this->tovar['ed']:'').' - '.$this->tovar['price']."���.");
        }elseif($property=='normal_name'){
            return trim(str_replace(['NEW '.date("Y"),'NEW'],'',$this->tovar['name']), '- !.');
        }elseif($property=='show_ost'){
            $query=DB::sql("SELECT * FROM `".self::db_prefix."tovar_shop` WHERE tovar='".addslashes($this->tovar['id'])."' ORDER BY shop");
            $out='';
            while(($row=DB::fetch_assoc($query))){
                // todo if(User::OPT!!!)return ucfirst(self::$sklad_name[($this->tovar['sklad']>0?$this->tovar['sklad']:SKLAD_OLD)]);
                $shop=DB::Select('shop','id='.$row['shop']);
                $out.=",<br> <a".(User::is_admin()?'':" style='white-space:nowrap'")." href='".$shop['url']."' onclick='return !window.open(this.href)'>".$shop['name'].
                    (User::is_admin()?" - ".$row['ost']."��.".($row['price']==$this->price?'':", ".$row['price']."���."):"")."</a>";
            }
            if($out)return "<label>�������:</label> ".substr($out,6);
            elseif($this->ost==-99)return "<label>�������:</label> �� �������� ��� ������";
            else return "<label>���� ��������:</label> 7-14 ����";

        }elseif($property=='collection_name'){
            return $this->tovar['collection_name']=DB::GetName('collection',$this->tovar['collection']);
        }elseif($property=='brand_name'){
            return $this->tovar['brand_name']=DB::GetName('brand',$this->tovar['brand']);
        }elseif($property=='brand_url'){
            $brand=DB::Select('brand',$this->brand);
            return '<a href="/price/?brand='.$this->brand.'" title="'.$brand['title'].'">'.$brand['name'].'</a>';
        }elseif($property=='sklad_name'){
            return (empty(Tovar::$_sklad_name[$this->tovar['sklad']])?Tovar::$_sklad_name[SKLAD_OLD]:Tovar::$_sklad_name[$this->tovar['sklad']]);
        }elseif($property=='supplier_name'){
            return $this->tovar['supplier_name']=DB::GetName('supplier',$this->tovar['supplier']);
        }elseif($property=='kol_name'){
            return ($this->tovar['kol']==intval($this->tovar['kol'])?intval($this->tovar['kol']):$this->tovar['kol']).($this->tovar['ed']?$this->tovar['ed']:($this->tovar['gr']?'':'��'));
        }elseif($property=='child'){
            return $this->tovar['child']=($this->tovar['tovar'] ? new Tovar($this->tovar['tovar']) : null );

        }elseif($property=='price_old'){
            return round($this->tovar['priceu'],0,PHP_ROUND_HALF_UP); // �������� �� ������ �����
        }elseif($property=='discount_expires'){
            return strtotime("+7 day");
        }elseif($property=='discount'){
            return ( $this->priceu==0 || $this->price >= $this->priceu) ? 0 : round(($this->priceu-$this->price)/$this->priceu,0);
        }elseif($property=='url') {
            if($this->Seo_url){
                if(empty($this->tovar['seo_url'])|| strlen($this->tovar['seo_url']) < 3){
                    $this->tovar['seo_url'] = str2url($this->tovar['name'].
                        ($this->tovar['kol']>1?" ".($this->tovar['kol']==intval($this->tovar['kol'])?intval($this->tovar['kol']):$this->tovar['kol']):''));
                    DB::sql("UPDATE `" . db_prefix . "tovar` SET `seo_url`='" . addslashes($this->tovar['seo_url']) . "' WHERE id='" . intval($this->tovar['id']) . "'");
                }
                return "/price/" . $this->tovar['seo_url'];    //$this->tovar['url']='/tovar/'.$this->tovar['kod_prodact'];
            }
            //return "/shop.php?id=".$this->tovar['id'];    //$this->tovar['url']='/tovar/'.$this->tovar['kod_prodact'];
            return "/price/tovar".$this->tovar['id'];    //$this->tovar['url']='/tovar/'.$this->tovar['kod_prodact'];
        }elseif($property=='Aurl'){
            return "<a href='".$this->url."' class='modal'>".toHtml($this->show_name)."</a>";
        }elseif($property=='Iurl'){
            return "<a href='".$this->url."'  title='".$this->show_name."' class='modal'><img src='".$this->imgMedium[0]."' alt='".$this->name."'></a>";
        }elseif($property=='Murl'){
            return "<a href='".$this->url."'>".$this->show_name."</a>";
        }elseif($property=='is_img'){    // ������ ��������
            return !!Image::is_file(path_tovar_image.'p'.$this->tovar[self::img_name],true);
            //$fil=path_tovar_image.'p'.$this->tovar[self::img_name].'.jpg';
            //return is_file($_SERVER['DOCUMENT_ROOT'].$fil);
        }elseif($property=='imgBig'){ //images\tovar\p1082-01.jpg
            $img=[];
            for($i=0;$i<99;$i++){
                $fil=path_tovar_image.'p'.$this->tovar[self::img_name].($i?('_'.$i):'').'.jpg';
                if(!is_file($_SERVER['DOCUMENT_ROOT'].$fil))break;
                $img[]=ImgSrc($fil);
            }
            if(empty($img))$img[]='/images/noimg.gif';
            return $img;

        }elseif($property=='imgSmall'){ //images\tovar\s1082-01.jpg
            return self::ImgArray('s',$this->tovar[self::img_name], 'imgSmallSize', imgSmallSize);

        }elseif($property=='imgMedium'){ //images\tovar\m1082-01.jpg
            return self::ImgArray('m',$this->tovar[self::img_name], 'imgMediumSize', imgMediumSize);

            /*        }elseif($property=='img'){
                        $fil=path_tovar_image.'s'.$this->tovar[self::img_name].'.jpg';
                        if($fil && is_file($_SERVER['DOCUMENT_ROOT'].$fil)){
                            return "\n<a href='".path_tovar_image.'p'.$this->tovar[self::img_name].'.jpg'."' onclick='return openwind(this)'><img src='".$fil."' class='left' height='40'></a>";
                        }else{
                            return "<div class='box left small c' style='height:40px;width:50px' onclick=\"return ajaxLoad('','api.php?form_img=".$this->tovar[self::img_name]."');\">���<br>��������</div>";
                        }*/
        }elseif($property=='row'){
            $row=$this->tovar;
            if(isset($row['info'])&&is_array($row['info']))$row['info']=js_encode($row['info']);
            return $row;

        }elseif(!isset($this->tovar[$property]) || is_null($this->tovar[$property])){
            return null;

            /*}elseif($property=='ost'){
                echo "<br>"; var_dump($this);
    */
        }else die("��� �������� Tovar::".$property);
    }

    function __set($property,$value){// - ����������, ����� ��������������� �������� ������������� ��������
        //die($property.'='.$value);
        if(in_array($property, ['name','price1','price','price0'])){
            DB::sql('UPDATE '.self::db_prefix.'tovar SET '.$property.'="'.$value.'" WHERE id='.$this->id);
            $this->tovar[$property]=$value;
        }elseif(in_array($property,self::$ar_info)){
            $this->tovar[$property]=$value;
            self::WriteInfo($this->tovar);
        }else die("�� ��������� ���������� Tovar::".$property);

        if(isset($GLOBALS['tovar_cash['.$this->id.']'])){  // ��������� ���
            unset($GLOBALS['tovar_cash['.$this->id.']']);
        }
    }


    function __call($name,$arr){// - ���������� ��� ��������� � ��������������� ������
        if($name=='ed'){
            if( $this->ed=='�����' )
                return num2word($arr[0], ["������", "������", "�����"]);
            else
                return $this->ed;
        }else die("��� ������ Tovar::".$name);

    }
    public function __construct($tovar = null){
        //echo '<br>Tovar::';var_dump($tovar);
        if(is_array($tovar)){
            $this->tovar=$tovar;
            unset($this->tovar['category']); // ���� � ������� ������ ���� - ������� ��
        }elseif($tovar>0){
            $this->tovar=DB::Select("tovar",intval($tovar)); //$this->tovar=$this->GetTovar($tovar,true);
        }

        if($this->tovar==null){error("������ � ���� ������ ".var_export($tovar,!0)); return null;}

        //$this->tovar=self::RecalcPrice($this->tovar);
        //echo '<br>Tovar->';var_dump($this->tovar);
        self::cPrice($this->tovar);
        //if(empty($this->tovar['price'])&&!empty($this->tovar['price0']))$this->tovar['price']=self::CalcPrice($this->tovar,1);

        //echo "<br><br>";var_dump($this->tovar);
        if(!isset($this->tovar['info'])){
            $t=$this->GetTovar($tovar['id']);
            $this->tovar['info']=$t['info'];
        }
        if(!is_array($this->tovar['info'])){
            if(isset($this->tovar['info'])&&$this->tovar['info'])
                $this->tovar['info']=js_decode($this->tovar['info']);
        }
    }
    public function __destruct() {

    }

    public function __isset($name) {
        $a=$this->__get($name);
        return !is_null($a);
    }

    static function _GetVar($tovar,$var){
        $t=new Tovar($tovar);
        //echo "<br><br>".var_dump($t);
        return $t->$var;
    }
    static function GetTovar($tovar,$notChild=false){
        if(!$tovar)return null;
        elseif(is_array($tovar)){
            $tovar0=$tovar;
        }else{
            if(!($tovar0 = DB::Select('tovar',intval($tovar))))return null;
        }
        if($tovar0['tovar']&&!$notChild){
            $tovar = DB::Select('tovar',intval($tovar0['tovar']));
            if($tovar){$tovar['ost']=$tovar0['ost'];
                $tovar['parent']=$tovar0;
                $tovar['srok']=$tovar0['srok'];
                $tovar['name0']=$tovar0['name'];
                $tovar['gr0']=$tovar0['gr'];
                if(isset($tovar['maxdiscount']))$tovar['maxdiscount']=min(($tovar0['maxdiscount']?$tovar0['maxdiscount']:100),($tovar['maxdiscount']?$tovar['maxdiscount']:100));
            }else{  Out::error("������ � ���� �������: ��� ������ �� ������ ".$tovar0['tovar']." !");
                $tovar=$tovar0;
            }
        }else $tovar=$tovar0;
        if(!empty($tovar['brand']['name'])){
            $brand_name = $tovar['brand']['name'];
        }elseif(Get::isKod($tovar['brand'])){
            $brand_name = DB::GetName('brand',$tovar['brand']);
        }else{
            $brand_name = $tovar['brand'];
        }
        $tovar['show_name']=str_replace("'","`",$tovar['name'].($tovar['kol']>1?" ".($tovar['kol']==intval($tovar['kol'])?intval($tovar['kol']):$tovar['kol']).$tovar['ed']:'').' - '.$tovar['price']."���.");
        $tovar['name']=str_replace("'","`",$tovar['name']);
        //$row['brand']." ".$row['name']." - ".($row['kol']==intval($row['kol'])?intval($row['kol']):$row['kol']).$row['ed'].' - '.$row['price']."���.
        //if(isset($tovar['info'])&&$tovar['info'])$tovar=array_merge($tovar,(array)json_decode($tovar['info']));
        if(isset($tovar['info'])&&$tovar['info']&&!is_array($tovar['info'])){
            $tovar['info']=js_decode($tovar['info']);
        }
        foreach($tovar as $key => $value)$tovar[$key]=str_replace('"',"'",$value);
        return $tovar;
    }

    static function DelAll($where){
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` ".($where? "WHERE ".$where: "" ) );
        $count=0;
        while($data=DB::fetch_assoc($query)){
            if(Tovar::Del($data['id']))$count++;
        }
        return $count;
    }

    /** �������� ������
     * @param $id
     * @return bool
     */
    static function Del($id){
        // todo ��������� ��� �� ����� ������ ��� ��������
        $id=intval($id);
        /*        $query=DB::sql("SELECT * FROM `".self::db_prefix."tovar` WHERE tovar='".$id."' LIMIT 1"); // �������� ��� ����������
                if(DB::num_rows($query))return false;
                $query=DB::sql("SELECT * FROM `".self::db_prefix."kart` WHERE tovar='".$id."' LIMIT 1");
                if(DB::num_rows($query))return false;
                $query=DB::sql("SELECT * FROM `".self::db_prefix."sale2` WHERE tovar='".$id."' LIMIT 1");
                if(DB::num_rows($query))return false;
                $query=DB::sql("SELECT * FROM `".self::db_prefix."prixod` WHERE tovar='".$id."' LIMIT 1");
                if(DB::num_rows($query))return false;
                $query=DB::sql("SELECT * FROM `".self::db_prefix."counters` WHERE tovar='".$id."' LIMIT 1");
                if(DB::num_rows($query))return false;*/
        $tov=self::GetTovar($id,true);
        self::DelImg($tov);

        // ��������� �������� ���������
        $gr=DB::Select2Array('category_link','tovar="'.$id.'"');
        $gr=array_column($gr,'category');
        if(!empty($tov['gr']))$gr[]=$tov['gr'];
        DB::Delete('category_link','tovar="'.$id.'"');
        self::ResetCounter($gr);

        // ������ ������ � �����������
        DB::Delete('supplier_link','tovar="'.$id.'"');

        // ������ ���������������
        DB::Delete('incompatibility','tovar="'.$id.'"');

        // �������, �� �������� ������ � �������
        DB::sql("UPDATE ".self::db_prefix."zakaz2 SET `tovar`=0 WHERE tovar=".$id);


        DB::log('tovar', $id, '��������');
        DB::Delete("tovar",$id);
        return (DB::affected_rows()>0);
    }

    /** ������� ��������
     * @param $tov
     */
    static function DelImg($tov){
        if(!is_array($tov))$tov=self::GetTovar($tov);
        for($i=0;$i<99;$i++){
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil))unlink($fil);else break;
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil))unlink($fil);
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'m'.$tov[self::img_name].($i?('_'.$i):'').'.jpg';  if(is_file($fil))unlink($fil);
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.diz';  if(is_file($fil))unlink($fil);
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov[self::img_name].($i?('_'.$i):'').'.txt';  if(is_file($fil))unlink($fil);
        }

    }

    /** ����� ��������� ��� ������ ��� ��� ������ �����
     * @param int|array $gr
     */
    static function ResetCounter($gr){
        if(is_array($gr)){
            foreach($gr as $item)self::ResetCounter($item);
        }else{
            // ��� ������� ������ ��� ����������� � �� ��������
            $row=DB::Select("category",intval($gr));
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=NULL WHERE id=".$gr);
            if( $row && !is_null($row['cnt']) && $row['parent'] ) {
                // �.�. ������� � ��� ��� �������, �� � �������� ��������� �� ����
                self::ResetCounter($row['parent']); // ��������� ������� � ������������ ������
            }
        }
    }

    /** ���������� ��� ������ � $tov
     * @param integer|array $old ������ �����, ���������
     * @param integer|array $tov ����� �����, ��������
     * @return bool
     */
    static function Union($old,$tov){
        $id_old=is_array($old)?$old['id']: $old ;
        $id_tov=is_array($tov)?$tov['id']: $tov ;

        if(is_array($old) && is_array($tov)) {
            if (strlen($old['description']) > strlen($tov['description'])) $tov['description'] = $old['description'];
            if ($tov['priceu'] == 0 && $old['priceu'] > 0) {
                $tov['priceu'] = $old['priceu'];
                $tov['valuta'] = $old['valuta'];
            }
            if ($tov['sklad'] > $old['sklad']) {
                $tov['sklad'] = $old['sklad'];
                $tov['supplier'] = $old['supplier'];
                $tov['price0'] = $old['price0'];
                $tov['price'] = $old['price'];
                $tov['valuta0'] = $old['valuta0'];
            }

            DB::sql("UPDATE IGNORE " . db_prefix . "tovar SET gr='" . (empty($tov['gr'])?$old['gr']:$tov['gr']) . "',".
                " brand='" . (empty($tov['brand'])?$old['brand']:$tov['brand']) . "',".
                " sklad='" . $tov['sklad'] . "', price0='" . $tov['price0'] . "', priceu='" . $tov['priceu'] . "', price1='" . $tov['price1'] . "', price2='" . $tov['price2'] . "', valuta='" . $tov['valuta'] . "', valuta0='" . $tov['valuta0'] . "', price='" . $tov['price'] . "',".
                " name='" . addslashes($tov['name']) . "', description='" . addslashes($tov['description']) . "',".
                " dat='" . max($tov['dat'], $old['dat']) . "', kod_prodact='" . addslashes($tov['kod_prodact']) . "' WHERE id=" . $tov['id']);
        }
        DB::sql("UPDATE `".db_prefix."tovar` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        //DB::sql("UPDATE `".db_prefix."kart` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        DB::sql("UPDATE `".db_prefix."zakaz2` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        if(self::tbl_prixod)DB::sql("UPDATE `".db_prefix."prixod` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        //DB::sql("UPDATE `".db_prefix."counters` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        DB::sql("UPDATE IGNORE ".db_prefix."supplier_link SET tovar=".$id_tov." WHERE tovar=".$id_old);
        DB::Delete("supplier_link","tovar='".$id_old."'"); // ������ �����������������
        DB::sql("UPDATE IGNORE `".db_prefix."category_link` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");
        DB::Delete("category_link","tovar='".$id_old."'"); // ������ �����������������
        //DB::sql("UPDATE `".db_prefix."zakaz2` SET tovar='".$id_tov."' WHERE tovar='".$id_old."'");

        $tov_old=self::GetTovar($id_old,true);
        $tov_tov=self::GetTovar($id_tov,true);
        $fil_old=path_tovar_image.'p'.$tov_old[self::img_name].'.jpg';
        $fil_tov=path_tovar_image.'p'.$tov_tov[self::img_name].'.jpg';
        if(!is_file($_SERVER['DOCUMENT_ROOT'].$fil_tov) && is_file($_SERVER['DOCUMENT_ROOT'].$fil_old))rename($fil_old,$fil_tov);
        self::DelImg($tov_old);
        DB::log('tovar', $id_tov, '����������� � '.$id_old);
        DB::Delete("tovar",$id_old);
        Tovar::SetFirstSupplier($id_tov);
        return (DB::affected_rows()>0);
    }

    static function Cennik($data){
        /*return "<div style='float:left;width:56mm;padding:4px;border:1px solid #ddd;page-break-inside:avoid;'>".
        "<div style='height:23mm;width:100%;overflow:hidden;font-size:13px'>".$data['name'].
        (isset($_GET['comment'])?" <span style='font-size:10px;color:#666'>".$data['comment']."</span>":"")."</div>".
        "<div style='text-align:center;margin:0 auto'>����: <b style='font-size:22px'>".$data['price']."</b>".(strlen($data['price'])<3?" ":"")."���.</div>".
        "<div style='font-size:11px;width:100%;text-align:right;vertical-align:sub;'>____________ �� ������ �.�.</div>".
        "<div style='font-size:12px;float:right;width:14mm;text-align:right'>".($data['kol']==0?'&nbsp;':($data['kol']==intval($data['kol'])?intval($data['kol']):$data['kol']).($data['ed']?$data['ed']:"��"))."</div>".
        "<div style='font-size:12px;float:left;width:14mm;text-align:left'>".$data['kod_prodact'].($data['kod_prodact']&&$data['ean']?"/":"").$data['ean']."</div>".
        "<div style='margin:0 auto;font-size:12px;width:12mm;text-align:center'>".date("d.m.y",strtotime("+1 day"))."</div>".
        "</div>";*/
        return "<div style='float:left;width:40mm;padding:4px;border:1px solid #ddd;page-break-inside:avoid;'>".
        "<div style='height:15mm;width:100%;overflow:hidden;font-size:13px'>".$data['name'].
        (isset($_GET['description'])?" <span style='font-size:10px;color:#666'>".$data['description']."</span>":"")."</div>".
        "<div style='text-align:center;margin:0 auto'>����: <b style='font-size:22px'>".$data['price']."</b>".(strlen($data['price'])<3?" ":"")."���.</div>".
        "<div style='font-size:11px;width:100%;text-align:right;vertical-align:sub;'>____________ �� ���������� �.�.</div>".
        "<div style='font-size:12px;float:right;width:14mm;text-align:right'>".($data['kol']==0?'&nbsp;':($data['kol']==intval($data['kol'])?intval($data['kol']):$data['kol']).($data['ed']?$data['ed']:"��"))."</div>".
        "<div style='font-size:12px;float:left;width:14mm;text-align:left'>".$data['kod_prodact'].($data['kod_prodact']&&$data['ean']?"/":"").$data['ean']."</div>".
        "<div style='margin:0 auto;font-size:12px;width:12mm;text-align:center'>".date("d.m.y",strtotime("+1 day"))."</div>".
        "</div>";
    }

    /** �� ������������ ���������� id ������, ��������� ������ ���������, ���� �� ������� - ���������
     * @param string $name - ������������ ������ ��� �����
     * @param bool $add =true-������� �����, ���� �� ������� - ��������, = false- ������ �� ���������
     * @return array|null
     */
    static function GetBrand($name, $add=false){
        $name=trim($name);
        if(empty($name))return null;
        if(in_array(mb_strtolower($name),['noname','������']))return 0;
        if($brand=DB::Select('brand', "name='".addslashes($name)."'"))return $brand;

        // todo ��������� ������ ���������

        // ���������� ����� �� ������ ������������
        if(!$add){// ������ ����� �� ���������
            $q=DB::sql('SELECT *,length(name) as lenname from '.db_prefix.'brand WHERE " '.addslashes(quotemeta(strtolower($name))).' " REGEXP CONCAT("[^a-z�-�0-9]",lower(name),"[^a-z�-�0-9]") or (length(title)>3 and " '.addslashes(quotemeta(strtolower($name))).' " REGEXP CONCAT("[^a-z�-�0-9]",lower(title),"[^a-z�-�0-9/]")) ORDER BY lenname DESC');
            if ($row=DB::fetch_assoc($q)) return $row;
            $br=self::brandList();
            foreach($br as $brand)if(stripos($name, $brand['name'])) return $brand;
            return 0;
        }elseif(isset($_REQUEST['test'])){
            return ['id'=>0,'name'=>'NEW:'.$name];
        }else{
            DB::sql("INSERT INTO `".self::db_prefix."brand`	(`name`) VALUES ('".addslashes($name)."')");
            return ['id'=>DB::id(),'name'=>$name];
        }
    }

    /**
     * ���������� ������ �������
     * @param string $format = '' - ���������� ������, 'select' - ���������� ���� <select>, 'id' - id=>row
     * @param int $act - ��������� ����� select �� ���������
     * @return array
     */
    static function brandList($format='', $act=0){
        static $ar=[], $sort=[], $ar_id=[];
        if(empty($ar)||isset($_GET['reload'])){ // �������
            global $_cache;
            if (empty($_cache)) $_cache = new Cache();
            $cache_keyBrandList='BrandList';
            $ar = unserialize($_cache->get($cache_keyBrandList));
            if(empty($ar)||isset($_GET['reload'])){
                $ar = [];
                $res = [];
                $query = DB::sql("SELECT brand,count(*) as `count` FROM `" . self::db_prefix . "tovar` GROUP BY brand");
                while (($row = DB::fetch_assoc($query))) $res[$row['brand']] = $row['count'];
                $query = DB::sql("SELECT * FROM `" . self::db_prefix . "brand` ORDER BY LENGTH(name) DESC"); // �������� �� ����������� ����� �������� ������
                while (($row = DB::fetch_assoc($query))) {
                    $row['url'] = '/brand/' . str2url($row['name']);
                    $row['count'] = (empty($res[$row['id']]) ? 0 : $res[$row['id']]); //DB::Count('tovar','brand='.$row['id']);
                    unset($res[$row['id']]);
                    $ar[] = $row;
                }
                foreach($res as $brand=>$count){ // �������� ���������� � noname
                    $ar[]=['id'=>$brand,'name'=>($brand?'??:'.$brand:'NoName'), 'count'=>$count];
                }
                $_cache->set($cache_keyBrandList, serialize($ar));
            }
        }
        if(!empty($format) && empty($sort)) {
            $sort = $ar;
            usort($sort, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }
        if($format=='select'){
           /* $sort = [];
            foreach($ar as $key => $row) $sort[$key] = $row['name'];
            array_multisort($sort, SORT_ASC, $ar);*/
            $str='';
            foreach($sort as &$row) $str.='<option value="'.$row['id'].'"'.($act==$row['id']?' selected':'').'>'.$row['name'].'</option>';
            return '<select name="brand"><option value="0"'.($act==0?' selected':'').'>���</option>'.$str.'</select>';

        }elseif($format=='id'){
            if(empty($ar_id)){
                foreach($sort as &$row) $ar_id[$row['id']]=$row;
            }
            return $ar_id;
        }
        return $ar;
    }

    /** ���� ���������� ������ �������� ��������� ������, �� ���������� ������ � ����������� � ������ ��� null
     * @param $str
     * @return null|array
     */
    static function is_brand($str){
        $str=mb_strtolower($str);
        $br=self::brandList();
        foreach($br as $brand)if(mb_strtolower($brand['name'])==$str)return $brand;
        return null;
    }


    /** �� ������������ ���������� id ������, ��������� ������ ���������, ���� �� ������� � $add - ���������
     * @param $name
     * @param string $tov_name
     * @return array|null
     */
    static function GetGr($name, $tov_name=''){
        //if(self::$ar_gr) return array_search($name,self::$ar_gr);
        $name=trim($name);
        if(empty($name))return null;
        if(($row=DB::Select('category', "name='".addslashes($name)."'")))return $row;
        if(self::tbl_alias){
            if (($row = DB::Select(self::tbl_alias, 'name="' . addslashes($name) . '"' .
                ($tov_name ? ' or "' . addslashes($tov_name) . '" LIKE shablon AND ' . 'NOT "' . addslashes($tov_name) . '" LIKE notshablon' : '')))){
                return DB::Select('category', intval($row['gr']));
            }
        }
        if ((strlen($name) > 10 ) && ($row = DB::Select('category','(locate("' . addslashes($name) . '", name)>0)')))return $row;
        // todo ��������� ������ ���������
        return null;
    }

    /** ��������� ������� ���������
     * @param String $name
     * @param Integer $gr
     * @return bool
     */
    static function AddSinonimGr($name, $gr){
        if(DB::Select('category', "name='".addslashes($name)."'"))return true; // �� �������� ���� ��������
        if(self::tbl_alias)return true;
        if(($row=DB::Select(self::tbl_alias, "name='".addslashes($name)."'"))){
            if($row['gr']!=$gr){echo "<br>\n<span class='red'>".$name."</span> - ������� ������ ".DB::GetName('category',$row['gr'].'!'); return false;}
        }else{
            DB::sql("INSERT INTO `".self::db_prefix.self::tbl_alias."` (`gr`,`name`) VALUES ('".addslashes($gr)."', '".addslashes($name)."')");
        }
        return true;
    }

    /** �� ������������ � ���� ������ ���������� id ���������, ���� �� ������� - ���������
     * @param $name
     * @param int|array $brand
     * @return int|string
     */
    static function GetCollection($name, &$brand){
        $_brand=(isset($brand['id'])?$brand['id']:$brand);
        if(strlen($name=trim($name))<7 && preg_match('/^[0-9]+$/',$name) ){ // Brown
            if(($row=DB::Select('collection',intval($name)))){
                if(isset($row['brand'])){
                    $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
                }
                return $row['id'];
            }
            return 0;
        }
        if( ($_brand>0) && ($row=DB::Select('collection',"name='".addslashes($name)."' and brand='".addslashes($brand)."'"))){
            if(isset($row['brand'])){
                $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
            }
            return $row['id'];
        }elseif(($row=DB::Select('collection',"name='".addslashes($name)."' and brand=0"))){
            // ������ ��� ��� ����� ������
            if( $_brand>0)DB::sql("UPDATE ".self::db_prefix."collection SET `brand`=".$_brand." WHERE id=".$row['id']);  // ���������� �����
            if(isset($row['brand'])){
                $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
            }
            return $row['id'];
        }else{
            $query=DB::sql("SELECT * FROM `".self::db_prefix."collection` WHERE name='".addslashes($name)."'");
            if( (DB::num_rows($query)==1) && ($row=DB::fetch_assoc($query))){ // ���������� �����
                if(isset($row['brand'])){
                    $brand=['id'=>$row['brand'], 'name'=> DB::GetName('brand', $row['brand'])];
                }
                return $row['id'];
            }elseif(DB::num_rows($query)>1){
                echo '<b class="red">����� �������� �� ������� '.$name."</b><br>������: ";
                while($row=DB::fetch_assoc($query)){ echo DB::GetName('brand',$row['brand']).'('.$row['brand'].'), '; if(empty($id))$id=$row['id'];}
                return $id;
            }
        }
        if(isset($_REQUEST['test']))return 'NEW:'.$name;
        // todo ��������� ������ ���������
        DB::sql("INSERT INTO `".self::db_prefix."collection` (`name`,`brand`) VALUES ('".addslashes($name)."', '".addslashes($_brand)."')");
        return DB::id();
    }

    public function GetCategory(){
        return self::_GetCategory($this->category);
    }

    /** ��������� input-� ��� ���� ��������� � �������� ��������� � ���������
     * @param array $category ������ ��������� [id_���������]=1
     * @return string ��� form
     */
    static function _GetCategory($category=[]){
        $s='';
        $query=DB::sql("SELECT * FROM `".self::db_prefix."category` ORDER BY id");
        while($row=DB::fetch_assoc($query))
            $s.="<label class=\"category\"><input type=\"checkbox\" name=\"category[".$row['id']."]\" value=\"1\"".(isset($category[$row['id']])?' checked':'')."><span>".$row['name']."</span></label>";
        return $s;
    }

    /** ����������� ��������� ������������� �� �������� ������
     *
     */
    static function SetCategory(){
        // ������������� ������
        //$query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE gr=".tTYPE_RASX." ORDER BY id");
        //while ($tovar=DB::fetch_assoc($query)) DB::sql("INSERT IGNORE INTO `".db_prefix."category_link` (`tovar`,`category`) VALUES ('".$tovar['id']."', '5')");

        $query0=DB::sql("SELECT * FROM `".db_prefix."category`");
        while ($category=DB::fetch_assoc($query0)){
            $ar=explode(',',$category['keywords']);
            echo "\n<h2>".$category['name']."</h2>";
            // ������������� ���������� ��������� �� ��������� keyword
            $s='';
            foreach($ar as $val)$s.="or name LIKE '%".addslashes(trim($val))."%' or description LIKE '%".addslashes(trim($val))."%'";
            $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE ".substr($s,3)." ORDER BY id");
            echo "<br>\n".DB::$query;
            while ($tovar=DB::fetch_assoc($query)) {
                DB::sql("INSERT IGNORE INTO `".db_prefix."category_link` (`tovar`,`category`) VALUES ('".$tovar['id']."', '".$category['id']."')");
                //if(DB::affected_rows()>0)
                echo "<br>\n".self::_GetVar($tovar, 'Aurl');
            }
        }
        // todo ���� ��������� ������ ����� ������
    }

    /** ���������� ������ ���������
     * @param int $root - �� ������ ����� (����� �� $kateg_name) =-1 ��� ���������
     * @param array $options  format = '' - ���������� ������, 'in' - ���������� ������ id ���� ����� �����',', 'select' - ���������� ���� <select>, 'option' - ���������� ������ <option>
                              act - ��������� ����� select �� ���������
                              add - ���������� � ��� select
                              noempty - ������ � ������� ���� ������
     *                        //sort - name, parent&name
     * @return array|string
     */
    static function grList($root=-1, $options=[]){// $format='',$act=0, $add=''){
        static $ar=[];
        if(empty($options['format'])){
            if($root==-1){
                if(empty($ar[$root])) {
                    $ar0 = [];
                    $query = DB::sql("SELECT * FROM `" . self::db_prefix . "category` GROUP BY `parent` ORDER BY id");
                    while ($row = DB::fetch_assoc($query)) {
                        if (!isset($ar[$row['parent']])) self::grList($row['parent']);
                        $ar0 = array_merge($ar0, $ar[$row['parent']]);
                    }
                    $ar[$root] = $ar0;
                }
                return $ar[$root];
            }

            if(!isset($ar[$root])) {
                $ar[$root] = DB::Select2Array('category', 'parent=' . intval($root).' ORDER BY name');
                //usort($ar[$root], function ($a, $b) {return strcmp($a['name'], $b['name']);});
            }
            foreach($ar[$root] as &$row){
                if(is_null($row['cnt'])){
                    if(!isset($row['ids']))$row['ids']=self::grList($row['id'],['format'=>'in']);
                    $row['cnt']=DB::Count('tovar','gr in ('.$row['ids'].')');
                    DB::sql("UPDATE ".self::db_prefix."category SET `cnt`='".addslashes($row['cnt'])."' WHERE id=".$row['id']);
                }
                $row['url']='/price/?gr='.$row['id'];
            } // todo �������� ������� �� category_link
            return $ar[$root];

        }elseif($options['format']=='in') {
            if(isset($ar[$root]['ids']))return $ar[$root]['ids'];
            $ids = $root;
            $child = $root;
            while (($row1 = DB::fetch_assoc(DB::sql("SELECT GROUP_CONCAT(DISTINCT id SEPARATOR ',') AS ids FROM `" . self::db_prefix . "category` WHERE parent in(" . $child . ") GROUP BY parent")))) {
                $child = $row1['ids']; // ������ id ����� ','
                $ids.= ($ids&&$child?',':'').$child;
            }
            return $ids;

        }elseif(isset($options['format']) && ($options['format']=='select'|| $options['format']=='option')){
            if(!empty($options['act'])&&$options['act']==$root){
                $root=(($root=DB::Select('category',$root)) ? $root['parent'] : 0 );
            }
            if(!isset($ar[$root]))self::grList($root);
            $str='';
            if(isset($options['no']))$str="\n\t".'<option value="0"'.(isset($options['act'])&&$options['act']==0?' selected':'').'>���</option>';
            /*$str="\n\t".'<option value="0"'.(isset($options['act'])&&$options['act']==0?' selected':'').'>���</option>';
            if($root!==0){
                $str.="\n\t".'<option value="-1">������</option>';
                $row=DB::Select('category',$root);
                $str.="\n\t".'<option class="b" value="'.$row['id'].'"'.(isset($options['act'])&&$options['act']==$row['id']?' selected="selected"':'').'>'.$options['act'].'~'.$row['id'].$row['name'].' ['.$row['cnt'].']</option>';
                if(isset($options['act'])&&$options['act']==$row['id']){
                    $str.=self::grList($options['act'],['format'=>'option']);
                }
            }*/
            foreach($ar[$root] as &$row){
                $str.="\n\t".'<option value="'.$row['id'].'"'.(isset($options['style'])?' style="'.$options['style'].'"':'').
                    (isset($options['act'])&&$options['act']==$row['id']?' selected="selected" class="b"':'').'>'./*$row['id'].'.'.*/$row['name'].' ['.$row['cnt'].']</option>';
                if(isset($options['act'])&&$options['act']==$row['id']){
                    $str.=self::grList($options['act'],['format'=>'option','style'=>'margin-left:10px']);
                }
            }
            if($options['format']=='option') return $str;
            return "\n\t".'<select name="gr" '.(isset($options['add'])?$options['add']:'').'>'.$str.'</select>';
        }
    }

    /** ���������� ��������� ������. ���� �������� ��������� �/��� �����, �� � �������� ��������� ���������
     * @param int $kol
     * @param int $brand
     * @param int $collection
     * @param int $exclude_id
     */
    static function GetRand($kol=3,$brand=0,$collection=0,$exclude_id=0){
        //return DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE gr='0' and kod_prodact<>'-'".($exclude_id?" and id<>'".$exclude_id."'":"").($brand?" and brand='".$brand."'":"").($collection?" and collection='".$collection."'":"")." ORDER BY RAND() LIMIT ".$kol);
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE ost<>'-99' and kod_prodact<>'-'".($exclude_id?" and id<>'".$exclude_id."'":"").($brand?" and brand='".$brand."'":"").($collection?" and collection='".$collection."'":"")." LIMIT 100");
        $ar=[];
        while($row=DB::fetch_assoc($query)){
            $tovar=new Tovar($row); if(empty($tovar)||empty($tovar->id)||!$tovar->is_img)continue;
            $ar[]="<div class='image_small_block'>".$tovar->Iurl."</div>\n";
        }
        shuffle($ar); // ���������� ���������
        echo "<div class='others_block'>\n";
        for($i=0;$i<min($kol,count($ar));$i++){
            if($i%6==0 && $i>0)echo "<br class='clear'>\n</div><div class='others_block'>\n";
            echo $ar[$i];
        }
        echo "<br class='clear'>\n</div>";

        /*
            $query=DB::sql('SELECT MAX(id) AS max, MIN(id) as min FROM `'.db_prefix.'object`');
            $row=DB::fetch_assoc($query);
            $q='';
            for($i=$kol;$i;$i--)
                $q=($q?$q."\nUNION ":"")."(SELECT * FROM `".db_prefix."object` WHERE id >= ".mt_rand($row['min'],$row['max'])." LIMIT 1)";
            $query=DB::sql($q);
            return $query;
        */
        //$result = mysql_query( "SELECT * FROM `table` WHERE id IN(".implode(',',$ids).") LIMIT ".$n);
    }

    function SearchImg($from='Y'){
        $url=urlencode($this->normal_name.($this->brand&&stripos($this->name,$this->brand_name)===false?" ".$this->brand_name:"").
            ($this->collection_name?" ".$this->collection_name:""). ' '.$this->kol_name );
        $urlY='https://yandex.ru/images/search?uinfo=sw-1076-sh-498-fw-851-fh-448-pd-1.25&nomisspell=1&noreask=1&text='.$url;
        $urlG2='https://www.google.ru/search?tbm=isch&q='.urlencode($this->normal_name);
        $urlG='https://www.google.ru/search?tbm=isch&q='.$url;
        set_time_limit(100);
        $urls=ReadUrl::ReadMultiUrl([$urlG,$urlY],['cache'=>10,'timeout'=>10,'convert'=>'windows-1251']);
        //return var_export($headers,!0)."\n".var_export($body,!0)."\n".var_export($info,!0);
        $ret='';
        $p = (empty($_GET['p']) ? 0 : intval($_GET['p'])); $perpage = 10;
        $cnt1 = $cnt2 = 0;
        foreach($urls as $row) {
            if(!empty($row['info']['curl_error'])){$ret.="\n<br>".var_export($row,!0).$row['info']['curl_error']; continue;}
            if(cmp($row['url'],$urlG) && preg_match_all('#"ou":"(http.*?)"#i', $row['body'], $ar)){ // google
                $ret.="\n<br class=\"clear\">Google:<br class=\"clear\">";
                foreach ($ar[1] as $val) if (strpos($val, 'google.ru') === false){
                    if (++$cnt1 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt1 > ($perpage * ($p + 1))) break;
                }
            }elseif(cmp($row['url'],$urlG2) && preg_match_all('#"ou":"(http.*?)"#i', $row['body'], $ar)){ // google
                $ret.="\n<br class=\"clear\">Google2:<br class=\"clear\">";
                foreach ($ar[1] as $val) if (strpos($val, 'google.ru') === false){
                    if (++$cnt1 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt1 > ($perpage * ($p + 1))) break;
                }
            }elseif(cmp($row['url'],$urlY) && preg_match_all('#\{(?:&quot;|\")url(?:&quot;|\"):(?:&quot;|\")(http.*?)(?:&quot;|\"),#i', $row['body'], $ar)){ // yandex
                $ret.="\n<br class=\"clear\">Yandex:<br class=\"clear\">";
                foreach ($ar[1] as $val) if (strpos($val, 'yandex.ru') === false){
                    if (++$cnt2 < ($perpage * $p)) continue;
                    $ret .= "<img class='SearchImg' onmouseover='o=getObj(\"SearchImg\");o.src=this.src;show(o);' onmouseout='hide(\"SearchImg\")' src='" . $val . "' onclick='ajaxLoad(false,\"/api.php?id=" . $this->id . "&link=\"+encodeURIComponent(this.src))' onerror='this.style.display=\"none\"'>";
                    if ($cnt2 > ($perpage * ($p + 1))) break;
                }
            }else{
                if(cmp($row['url'],$urlG) && stripos($row['body'],'�� ������� �� ������')!==false)$urls+=ReadUrl::ReadMultiUrl([$urlG2],['cache'=>10,'timeout'=>10,'convert'=>'windows-1251']);
                else $ret.="\n<br>".var_export($row,!0);
            }
        }
        if($ret){
            $ret .= '<img id="SearchImg" class="hide">';
            if ($cnt1 > ($perpage * ($p + 1)) || $cnt2 > ($perpage * ($p + 1))){
                $ret .= '<br class="clear"><a href="/api.php?search_img=' . $this->id . '&p=' . ($p + 1) . '" onclick="return ajaxLoad(this.parentNode,this.href)">[���&gt;&gt;]</a>';
            }
        }else $ret.='<br>� ������� � ����� �� �������� �� ������� ��������!'.
            '<br><a href="/api.php?search_img='.$this->id.'&reload" onclick="return ajaxLoad(this.parentNode,this.href)">����������� ��������</a>';
        return $ret.' <a href="'.$urlY.'">�</a> <a href="'.$urlG.'">G</a>';
    }

    /**
     * @param null|Tovar $tovar
     */
    static function OtherTovar($tovar=null,$kol=6){
        if($tovar)
            Tovar::GetRand($kol,$tovar->brand,$tovar->collection,$tovar->id);
        else
            Tovar::GetRand($kol);
    }

    /**
     * �������� �������� �������
     */
    static function RecalcOst(){
        DB::sql("UPDATE `".db_prefix."tovar` SET `ost`='0' WHERE `ost`<>'-99'"); // ������� ������� � ���������� ����������
        $query=DB::sql('Select tovar,SUM(ost) as ost1 from '.db_prefix.'tovar_shop GROUP BY tovar');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE `".db_prefix."tovar` SET `ost`='".$data['ost1']."' WHERE id='".$data['tovar']."'".($data['ost1']>0?'':" and ost<>'-99'"));
        }

        $query=DB::sql('Select * from '.db_prefix.'brand');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE ".self::db_prefix."brand SET `cnt`=".($c=DB::Count('tovar',"brand='".$data['id']."' and ost<>'-99'"))." WHERE id=".$data['id']);
            echo "<br>".$data['name'].' '.$c;
        }
        echo "<br>";
        $query=DB::sql('Select * from '.db_prefix.'category');
        while ($data = DB::fetch_assoc($query)){
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=".($c=DB::Count('category_link as c, '.db_prefix.'tovar as t',"c.tovar=t.id and c.category='".$data['id']."' and t.ost<>'-99'"))." WHERE id=".$data['id']);
            echo "<br>".$data['name'].' '.$c;
        }
    }

    static function ShowVitrina($where='vitrina=1'){
        $bar = new kdg_bar(array('perpage' => 10000, 'tbl' => db_prefix . 'tovar', 'sql'=>$where));
        ?>
        <div class="listing sb-body" xmlns="http://www.w3.org/1999/html">
            <div class="fr">
                <?
                $format_show=(!empty($_COOKIE['format_show'])&&$_COOKIE['format_show']=='list' ? 'list': 'table');
                if($format_show=='list'){ ?>
                    <span class="table_p" title="� ���� �������" onclick="setCookie('format_show','table');reload();"></span>
                    <span class="list_p current" title="� ���� ������"></span>
                <? }else{ ?>
                    <span class="table_p current" title="� ���� �������"></span>
                    <span class="list_p" title="� ���� ������" onclick="setCookie('format_show','list');reload();"></span>
                <?}?>
            </div>
            <br class="clear">
            <?php
            if($format_show=='list')echo "<br><table class=\"client-table tovar-table w100\">\n<tr>\n".
                "\t<th></th>\n".
                "\t<th onclick=\"Order('name')\">".($bar->ord=='name'?($bar->desc?'&uarr; ':'&darr; '):'')."������������</th>\n".
                "\t<th onclick=\"Order('price')\">".($bar->ord=='price'?($bar->desc?'&uarr; ':'&darr; '):'')."����, ���</th>\n".
                "\t<th></th>\n".
                "</tr>\n";
            else echo "    <ul class=\"cp-list cp-gallery\">";
            $result = DB::sql('SELECT * from '.db_prefix.'tovar WHERE '.$where.' LIMIT 20');
            while ($row = DB::fetch_assoc($result)) {
                self::PrintTovar($row);
            }
            global $euro;
            if($format_show=='list')echo "</table>\n";
            else echo "        <li class=\"cpg-item\"></li>
</ul>
";
            ?>
            <a href="/price/">��� ������, ������ � ����</a>
            <span class="sb-more-totalLink">(<?=DB::Count('tovar')?>)</span>
            <div class="kurs fr">
                <a href="/G/stock.rbc.ru/demo/cb.0/daily/USD.rus.shtml?show=1M" rel="nofollow" target=_blank>1$ = <b><?=Valuta::dollar()?></b> ���.</a>
                <a href="/G/quote.rbc.ru/exchanges/demo/cb.0/EUR/daily?show=1M" rel="nofollow" target=_blank>1� = <b><?=$euro;?></b> ���.</a>
            </div>

            <br class="clear">
        </div>
        <?
    }
/*
    static function RecalcPrice($row){
        $row['priceu']=floatval($row['priceu'])*($row['valuta']=='$'?Valuta::dollar():($row['valuta']=='E'?Valuta::euro():1));

        if($row['priceu']>0&&($row['supplier']<2||$row['valuta']=='*'))   $row['price']=$row['priceu'];

        $row['price0_r']=floatval($row['price0'])*($row['valuta0']=='$'?Valuta::dollar():($row['valuta0']=='E'?Valuta::euro():1));

        $nacenka=($row['price0_r']==0? 0 : ($row['price']-$row['price0_r'])/$row['price0_r'] );

// price1 - ������ ��� � ������
// price2 - ��� � ������

        if($row['price1']<=0){  // ������� -5%
            $row['price1']=floatval($row['price']) * ($nacenka>=1 ? 0.70 : 0.95);
        }
        if($row['price2']<=0 && $row['price0_r']>0){
            $row['price2']=$row['price0_r'] * ($nacenka>=1 ? 1.3 : 1.1);

        } // ������� +10%
        // ���� ��� ������ �������, �� ���� ���
        if($row['price1']>$row['price'])    $row['price']=$row['price1'];
        // ���� ������� ��� ������ �������
        if($row['price2']>$row['price1'])   $row['price2']=$row['price1'];

// ���� ���� ������ �� 100������, �� ����� ������� �� ������ 100%
        if( $row['price2']>0 && $row['supplier']>1 && $row['price']<=100 && ($row['price']/$row['price2'])<1.8 )  $row['price']=$row['price2']*1.8;
        return $row;
    }
*/

    static function NormalName($name,$prefix=''){
        $name=trim($name);
        $name=str_ireplace('Wi-Fi','WiFi',$name);
        $name=str_ireplace('����-�','��������',$name);
        $name=str_ireplace('``','"',$name);
        $name=str_ireplace('`',"'",$name);
        $name=str_ireplace("''",'"',$name);
        $name=str_ireplace('�','�',$name);
        $name=str_replace('  ',' ',$name);
        $name=str_replace(' + ','+',$name);
        $name=str_replace("\t",' ',$name);
        $search = ["`^V/c (.*?)`si",
            "`����� �����`si",
            "`����������� Mouse`si",
            "`���.�����`si",
            "`^M/B (.*?)`si",
            "`^�/����� (.*?)`si",
            "`^��������� ����� (.*?)`si",
            "`���-��`si",
            "`DLINK`si",
            "`Micro-Star`si",
            "`���-��`si",
            "`^�� �������`si",
            "`�����`si",
            "`^HDD`si",
            "`^��������� HDD`si",
            "`�������� �������������� �������`si",
            "`sumsung`si",
            "`cannon`si",
            "`dialog`si",
            "`^�� ����������`si",
            "`^������������������� ����������`si",
            "`^�/�������`si",
            "`^�/������`si",
            "`^����� `si",
            '`�������� "���-�-�����"`si',
            '`2,5"`si',
            '`3,5"`si',
            '`1,8"`si',
            '`������`si',
            '`^����-�����`si',
            '`^���������� ������ ���� ������`si',
            '`^Card Reader`si',
            '`^���������� ������/������ ���� ����`si',
            '`������������`si',
            '`����� ���������`si',
            '`������������`si',
            '`�\-����`si',
            '`CPU Fan universal`si',
            '`��������\-�����`si',
            '`����� ��������`si',
            '`��� ��������`si',
            "/  +/"];
        $replace = ["���������� $1",
            "����������",
            "����",
            "����������� �����",
            "����������� ����� $1",
            "����������� ����� $1",
            "����������� ����� $1",
            "����������",
            "D-Link",
            "MicroStar",
            "����������",
            "�������",
            "����������",
            "������� ���� HDD",
            "������� ���� HDD",
            "���",
            "samsung",
            "canon",
            "������",
            "���",
            "���",
            "������������",
            "�����������",
            "������������� ������� ",
            "��������",
            '2.5"',
            '3.5"',
            '1.8"',
            '',
            '���������',
            '���������',
            '���������',
            '���������',
            '2� ���������',
            '��������������',
            '������������',
            '���������',
            '���������� ��� ���������� �������������',
            '�����-��������',
            '�����-��������',
            '�������� ���',
            " "];
        $name = preg_replace($search, $replace, $name);
        if($prefix && !cmp($name,$prefix)){
            $name=$prefix.$name;
        }

        return $name;
    }


    static function isStopWord($name){
        return (strpos($name,'������ ��������')!==false ||
            (strpos($name,'���������������')!==false) ||
            (strpos($name,'(�����)')!==false) ||
            (strpos($name,'����������� ')!==false) ||
            (strpos($name,'�������� �������')!==false) ||
            (strpos($name,'�������������� ���������')!==false) ||
            (strpos($name,'����������� ���������')!==false) ||
            (strpos($name,'NONAME ')!==false) );
    }

    static function highlight($str){
        return str_replace('�1','',str_replace('�2','',preg_replace("/�1(.*?)�2/si", '<span class="red">\\1</span>', htmlspecialchars($str,null,'windows-1251'))));// ���������
    }

    static function Show($str){
        return str_replace('�1','',str_replace('�2','',preg_replace("/�1(.*?)�2/si", '\\1', htmlspecialchars($str,null,'windows-1251'))));
    }


    static function toUrl($str){
        return urlencode(str_replace('�1','',str_replace('�2','',$str)));
    }

    /** ������� ������� ������ ��������� ������
     * @param integer $gr
     * @return string
     */
    static function BreadCrumbs($gr)
    {
        //var_dump($tovar);
        $i=0; // ������ �� ��������
        $gr=intval($gr);
        //var_dump($gr);
        $res='';
        while( ($gr>0) && ($gr=DB::Select('category',$gr)) && $i<5)
        {
            //var_dump($gr);
            $res=" <a href='/price/?gr=".$gr['id']."'>".$gr['name']."</a>" . ($res?' &rarr; ':''). $res;
            $gr=$gr['parent'];
            $i++;
        }

        return "<div class=\"vid\">��������� ������: ".$res."</div>";
    }

    /** ������� ���� �� ������ ��������� �������, �������� 200x200
     * @param array|integer $row
     * @param array|null $options
     *                   $options['nodiv'] - �� �������� ������� ���� ��� ������ ���������� � ������ �� ajax
     */
    static function PrintTovar_new($row, $options=null){

        global $ar_q1,$q;
        if(isset($options['format_show']))$format_show=$options['format_show'];
        else $format_show=(!empty($_COOKIE['format_show'])&&$_COOKIE['format_show']=='list' ? 'list': 'table');
        $tovar=new Tovar($row);
        if($format_show=='list'){
            $name=$tovar->name;
            if (!empty($ar_q1) && !empty($q)){ $name=Tovar::highlight(preg_replace($ar_q1, '�1\\1�2', $name)); // ��������� � ��� �����
                //if ($data['kod_prodact'] && $q) $data['kod_prodact'] = preg_replace($ar_q1, '�1\\1�2', $data['kod_prodact']); // ��������� � ��� �����
            }
            //"<td>".$tovar->kod_prodact.($tovar->kod_prodact && $tovar->ean ? "/" : "" ) . $tovar->ean."</td>\n".
            if(!isset($options['nodiv']))echo "<tr class='hand' id=\"id".$tovar->id."\" onclick=\"return ajaxLoad('','".$tovar->url.(strpos($tovar->url,'?')==false?"?":"&amp;")."ajax')\">\n";
            echo "\t<td><div>".Image::imgPreview($tovar->imgSmall[0], ['size'=>imgSmallSize])."</div></td>\n".
                "\t<td class='left'>".(User::is_admin()?"<span class='blue'>".$tovar->kod_prodact."</span> ":"").
                "<a href='".$tovar->url."' onclick=\"return false;\">". $name . "</a>, ���:".$tovar->kod_prodact."</td>\n".
                "\t<td>".$tovar->price. (User::is_admin(uADM_OPT)?"<span class='small'>(".$tovar->price2.")</span>":""). "</td>\n".
                "\t<td onclick='event.cancelBubble = true;if(event.stopPropagation)event.stopPropagation()'>".
                (User::is_admin()?
                    "<a href='#' class=\"icon edit\" title=\"��������\" onclick=\"return ajaxLoad('','/api.php?edit=".$tovar->id."')\"></a>".
                    "<a href='/api.php?tbl=tovar&amp;del=".$tovar->id."' class=\"icon del\" title=\"�������\" onclick=\"if(confirm('�������?'))ajaxLoad('answer',this.href);return false;\"></a>".
                    "<a href='#' class=\"icon vitrina".$tovar->vitrina."\" onclick=\"return ajaxLoad(this,'/api.php?vitrina=".$tovar->id."');\" title=\"�������/�������/�������\"></a>"
                    :"").
                "\t<span class='icon cart' title=\"��������\" onclick=\"return ajaxLoad('','/api.php?basket_add&amp;id=".$tovar->id."');\"></span>".
                "</td>\n";
            if(!isset($options['nodiv'])) echo "</tr>\n";

        }else{
            if(!isset($options['nodiv'])) echo "<li class=\"cpg-item\" id=\"id".$tovar->id."\">";
            ?>
            ���: <?=$tovar->kod_prodact?>
            <?=(User::is_admin()?'<div class="right">#'.$tovar->id.'</div>':'')?>
            <div class="pr">
                <?
                if(User::is_admin()){?>
                    <span class="icon-box"><a href='#' class="icon edit" title="��������" onclick="return ajaxLoad('','/api.php?edit=<?=$tovar->id?>')"></a>
            <a href='/api.php?tbl=tovar&amp;del=<?=$tovar->id?>' class="icon del" title="�������" onclick="if(confirm('�������?'))ajaxLoad('answer',this.href);return false;"></a>
            <a href='#' class="icon fabric" onclick="return ajaxLoad('','/api.php?pt=<?=$tovar->id?>','..');" title="����������"></a>
            <a href='#' class="icon vitrina<?=$tovar->vitrina?>" onclick="return ajaxLoad(this,'/api.php?vitrina=<?=$tovar->id?>');" title="�������/�������/�������"></a>
            <a href='#' class="icon duplicate" onclick="return ajaxLoad('','/api.php?copy=<?=$tovar->id?>');" title="����������"></a>
            </span>
                <?}?>
                <a href="<?=$tovar->url?>" class="cpg-link" title="<?=$tovar->name?>" onclick="return ajaxLoad('','<?=$tovar->url.(strpos($tovar->url,'?')==false?"?":"&amp;")?>ajax')">
                    <?=Image::imgPreview($tovar->imgMedium[0], ['alt'=>$tovar->name, ['size'=>imgMediumSize]])?>
                </a>
                <?if($tovar->discount){
                    ?>
                    <div class="discount-marker big">- <?=$tovar->discount?>%</div>
                <?}?>
            </div>
            <div class="title-">
                <a href="<?=$tovar->url?>"><?=$tovar->name?></a>
            </div>
            <span class="orderButton fr" onclick="return ajaxLoad('','/api.php?basket_add&amp;id=<?=$tovar->id?>');">��������</span>
            <div class="price-">
                <i class="bp-price"><?=outSumm($tovar->price)?></i> ���.<?=($tovar->ed?'/'.$tovar->ed:'')?>
            </div>
            <?if($tovar->price <> $tovar->price){?>
                <span class="bp-price-cover"><i class="bp-price fwn fsn"><?=outSumm($tovar->price_old)?></i> ���.</span>
                <span class="discount-expires-at">�� <?=date("d.m.y",$tovar->discount_expires)?></span>
            <?}
            if(!isset($options['nodiv'])) echo "</li>\n";
        }
    }

    /** ������� ���� �����
     * ����� ������ 790px,
     * @param $row
     * @param null $options
     */
    static function PrintTovar($row, $options=null){ // , $full = false, $div = false, $noKateg = false ['full'=>true, 'div'=>true, 'noKateg'=>true]
        $full = isset($options['full']) ? !!$options['full'] : false; // =true-���� ����� � ��������� ���������
        $div = isset($options['div'])?$options['div']:false;
        $noKateg = isset($options['noKateg']) ? !!$options['noKateg'] : false;
        // $div �������� ������� div
        $tovar=(is_object($row) ? $row : new Tovar($row) );
        $row=$tovar->GetTovar($tovar->id);
        if (!$tovar) {Out::error("�� ������ ����� ".$row."!"); return;}

        /*if(!isset($brend_search)){
           $query = DB::sql('SELECT * FROM '.db_prefix.'brand');
           while ($data = DB::fetch_assoc($query)){
                $brend_search[]='# '.$data['name'].' #im';
                $brend_replace[]=' <a href="http://'.$data['url'].'" target="_blank" rel="nofollow">\\0</a> ';}
        // ru.asus.com/Search.aspx?SearchKey=
        // www.acorp.ru/search/?text=
        // xerox.ru/ru/search/?q=
        // www.xerox.ru/ru/catalog/465/wc5020/?phrase_id=222469
        // www.d-link.ru/ru/search/POST?find_str=
        // www.epson.ru/search/index.php?q=
        // search.hp.com/gwrurus/query.html?lang=ru&la=ru&charset=utf-8&cc=ru&qt=
        // www.canon.ru/search/default.aspPOST?txtQuery= �� ������� :-(
        // apc.com/search/results.cfm?qt=	 �� ������� :-(
            $name=preg_replace($brend_search, $brend_replace, htmlspecialchars($row['name'],null,'windows-1251'));// ������ ����� ��������
        }*/
        //$row=self::RecalcPrice($row);

        if($div)print "<br class='clear'><div class='t".$tovar->sklad.(Get::isApi()?' contentwin':'')."' id='id".$tovar->id."'".(Basket::in($tovar->id) ?" style='opacity:0.4;'":"").">";

        $is_img=is_file($_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$tovar->id.'.jpg');
        if($is_img) if(isset($_SESSION['us_img'])&&$_SESSION['us_img']==0)$is_img=false; // �� ���������� ��������

        print "<div class='tprice'>\n<a href='/api.php?basket_add&id=".$tovar->id."' onClick='return ajaxLoad(\"\",this.href)' class='basket'>&nbsp;</a><br />\n";
        if($is_img){
            $img1=path_tovar_image.'s'.$tovar->id.'.jpg';
            $img2=path_tovar_image.'p'.$tovar->id.'.jpg';
        }else{
            $img1='/images/noimg40.gif';
            $img2='';
        }
        if(!$full)print "<a href='/price/tovar".$tovar->id."' target=_blank><img id='img".$tovar->id."' src='".$img1."' /></a>";	// �� �������� �������� � �����
        else print "\n<img id='img".$tovar->id."' src='".$img1."'".($img2?" data-src='".$img2."'":'')." alt='".self::Show($tovar->kod_prodact)."'>\n";	// ��������

        print "<br class='clear'>".($tovar->priceu>0&&(User::is_admin(!0)&&$tovar->priceu!=floatval($tovar->price)||$tovar->priceu>$tovar->price)
                ?'<div class="priceu">'.number_format($tovar->priceu, 0, '.', ' ').' ���.</div>':'')."
".number_format($tovar->price, 0, '.', ' ')." ���.<sup>1</sup>";

        print "<br><span class='price1'>".number_format($tovar->price1, 0, '.', ' ')." ���.<sup>2</sup></span>";
        if(User::is_admin(!0)&&$tovar->price2<$tovar->price1)print "<br>".number_format($tovar->price2, 0, '.', ' ')." ���.<sup>3</sup>";
        print "\n<br><a href='/price/?ys=".self::toUrl($tovar->name)."' class='ya'>&nbsp;</a>";
        if(!$is_img) print "\n<a href='/price/?is=".self::toUrl($tovar->name)."' class='yaimg'>&nbsp;</a>";
        print "</div>\n";	// ���� div=tprice

        print "<div class='tdes'>";

        if(!$full)print "\n<a href='/price/tovar".$tovar->id."' class='modal'>".self::highlight($tovar->name)."</a>\n";
        else	print "<br clear='all'><h3 id='n".$tovar->id."'>".self::Show($tovar->name)."</h3>\n";

        if(!$noKateg)echo self::BreadCrumbs($tovar->gr).'<br>';

        print "�����: ";
        if (($brand=DB::Select('brand',$tovar->brand))) print '<b>'.$tovar->brand_url.'</b>';
        if(User::is_admin(uADM_MANAGER))print ", <input type='button' onclick='kp(".$tovar->id.")' value='��� �������������'>: <b id='kp".$tovar->id."'>".self::highlight($tovar->kod_prodact)."</b>";
        else print ", ��� �������������: <b>".self::highlight($tovar->kod_prodact)."</b>";
        if($tovar->garant)print ", �������� <b>".$tovar->garant."</b>���.";

        if($tovar->sklad==1)print "<br>\n�������: <span class='est'>".$tovar->sklad_name."</span>";
        else print "<br>\n�������: ".$tovar->sklad_name;

        print "</div>\n";	// ��� �������������

        if($full){
            $desc=$tovar->description;
            if( $brand['search'] && $tovar->kod_prodact && (strpos($desc, 'http://')===false) )
                if(strpos($brand['search'], '%q%')!==false)$desc.="\nhttp://".str_replace('%q%',self::toUrl($tovar->kod_prodact),$brand['search']);
                else $desc.="\nhttp://".$brand['search'].self::toUrl($tovar->kod_prodact)."\n";
            if($desc){
                $desc = trim(preg_replace('#(?<!\])\bhttp://[^\s\[<]+#i',
                    " <noindex><a href=\"/price/?url=$0\" target=_blank><u>���������� �� ����� �������������</u></a></noindex> ",
                    nl2br(stripslashes($desc))));
                print "<div class='tdes'>".$desc."</div>";	// ��������
            }
        }

        if(User::is_admin(uADM_MANAGER)){
            if( $tovar->priceu>0 )print "<div class='tdes'>������������� ���� ".($tovar->valuta=='$'?$tovar->priceu.'$ = ':'').number_format($tovar->priceu, 0, '.', ' ')." ���.</div>";

            print "<div class='tdes'>#".$tovar->id."
<a href='/api.php?pt=".$tovar->id."' onclick=\"this.style.display='none';this.nextSibling.style.display='inline';return ajaxLoad('pt".$tovar->id."',this.href,'..');\">[���������]</a><a href='#' style='display:none' onclick=\"this.style.display='none';this.previousSibling.style.display='inline'; getObj('pt".$tovar->id."').innerHTML=''; return false;\">[������ �����������]</a>
<b>".$tovar->supplier_name."</b> ".$tovar->date_upd.', ������� '.self::cPrice($row,0,true)."
<a href='/adm/edit.php?edit=".$tovar->id."' onclick=\"getObj('id".$tovar->id."').style.opacity=1;return ajaxLoad('id".$tovar->id."',this.href);\">[��������]</a>
<span id='st".$tovar->id."'>";

            if(isset($_GET['edit']))echo "\n<script type='text/javascript'>onDomReady(ajaxLoad('id".$tovar->id."','/adm/edit.php?edit=".$tovar->id."'));</script>\n";

//   if($row['sost']=='���')print "<a href='#show' onclick=\"getObj('t".$tovar->id."').style.opacity=1;return ajaxLoad('st".$tovar->id."','/adm/edit.php?show=".$tovar->id."');\">[��������]</a>"; // ����� � � edit
//	else print "<a href='#hide' id='st".$tovar->id."' onclick=\"getObj('t".$tovar->id."').style.opacity=0.2;return ajaxLoad('st".$tovar->id."','/adm/edit.php?hide=".$tovar->id."');\">[������]</a>";
            print "\n<a class='dns' href='http://www.dns-shop.ru/search/?q=".urlencode($tovar->kod_prodact)."'>[Dns]</a>
            <a class='dns' href='https://www.ulmart.ru/search?spellCheck=false&string=".urlencode($tovar->kod_prodact)."'>[������]</a>
            <a class='dns' href='http://www.citilink.ru/search/?text=".urlencode($tovar->kod_prodact)."'>[CitiLink]</a>";
            print "</span></div><span id='pt".$tovar->id."'></span>\n"; // ���� �������� ������ �����������
        }
        if($div) print "</div>\n\n";	// ����� ������
    }


    static function ActualizedDate($where=''){ // ���� ������������ ���� ���� �������
        $row=DB::fetch_assoc(DB::sql('SELECT max(date_upd) as d from '.db_prefix.'tovar'.($where?' WHERE '.$where:'')));
        return strtotime($row ? intval($row['d']) : '2014-01-01' );

    }

    static function SetActualizedDate($where=''){ // ���������� ���� ������������ ���� ���� �������
        DB::sql("UPDATE ".self::db_prefix."tovar SET `date_upd`='".date("Y-m-d")."'".($where?' WHERE '.$where:''));
    }

    /** ������� ���� ������� �����
     * @param array|Tovar $row
     */
    static function Print1Tovar($row){
        $tovar=(is_object($row)?$row: new Tovar($row));
        $img=$tovar->imgBig;
        echo "\n<form class='primary_block' name='tovar' id='tovar' action='/api.php?basket_add' method='post' onsubmit='return SendForm(\"\",this)'\n".
            (User::is_admin()?
                "ondragenter=\"addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();\"".
                "ondragover=\"addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();\"".
                "ondragleave=\"removeClass(getEventTarget(event,'FORM'),'box');\"".
                "ondrop=\"return _frm.drop(event);\"":"").">".
            "<h1><a href='".$tovar->url."'>".$tovar->name."</a></h1>\n".
            "<input type='hidden' name='id' value='".$tovar->id."'>\n".
            "<div class='image_block'".(User::is_admin()&&!$tovar->is_img?' style="height:auto"':'')."><div>\n".
            (User::is_admin()&&!$tovar->is_img?
                $tovar->SearchImg() :
                "<img src='".ImgSrc($tovar->imgBig[0])."' alt='".$tovar->name."'>"
            );
        if(count($img)>1)foreach($tovar->imgSmall as $i=>$fil){
            $alt=Image::Alt($fil);
            echo "\n<div class=\"obj_photo\">".
                "\n\t".Image::imgPreview($fil, array('whithA'=>$img[$i],'size'=>imgSmallSize)).
                "\n\t\t".htmlspecialchars($alt,null,'windows-1251').
                "\n\t</a>\n\t</div>";
        }

        echo "</div>".(User::is_admin()&&$tovar->is_img?"<a class='icon cart_remove confirm' href='/api.php?del_img=".$tovar->id."' onclick='return ajaxLoad(false,this.href);'></a>":"").
            "</div>\n<div class='left' style='width:318px'>\n".
            ($tovar->collection?"<p".(strlen($tovar->collection_name)>30?" class='long'":'')."><label>���������:</label>".Tovar::Collection_anchor($tovar->brand_name, $tovar->brand, $tovar->collection_name, $tovar->collection)."</p>\n":"").
            "<p><label>�����:</label> ".Tovar::Collection_anchor($tovar->brand_name, $tovar->brand)."</p>\n".
            "<p><label>�������:</label> ".$tovar->kod_prodact.($tovar->kod_prodact && $tovar->ean ? "/" : "" ) . $tovar->ean."</p>\n".
            ($tovar->kol==0?"" : "<p><label>�����:</label> ".$tovar->kol_name."</p>\n").
            "<p style='text-align:left'>".$tovar->show_ost."</p>\n".
            "<p class='price'><label>����:</label><b>".$tovar->price." ���.</b></p>\n".
            (User::is_admin(uADM_OPT)?"<p class='price'><label>���� ���:</label><b>".$tovar->price2." ���.</b></p>\n":"").
            (User::is_admin()?"<p class='price'><label>�������:</label><strong>".$tovar->price0." ���.</strong></p>\n":"").
            "<p class='price quantity_wanted'><label>����������:</label>\n".
            "<input type='number' maxlength='3' size='2' value='1' class='text' id='quantity_wanted' name='kol'>\n<br>\n".
            "<br>".
            ($tovar->ost==-99?'<b class="red">�� �������� ��� ������</b>':"<input type='submit' class='button' value='� �������'>").
            "<br>\n</p>\n".
            "</div>\n".
            "<br class='clear'></form>\n";
        echo
            (User::is_admin()?
                "<span class='r'><a href='/api.php?log&amp;tovar=".$tovar->id."' class=\"icon comment r\" title=\"��������\" onclick=\"return ajaxLoad('',this.href+'&amp;ajax');\"></a>".
                "<a href='/api.php?tovar&amp;show=".$tovar->id."' class=\"icon abonement r\" title=\"��������\" onclick=\"return ajaxLoad('',this.href);\"></a>".
                "<a href='/api.php?tbl=tovar&amp;del=".$tovar->id."' class=\"icon del r\" title=\"�������\" onclick=\"if(confirm('�������?'))ajaxLoad('answer',this.href);return false;\"></a>".
                "<a href='/api.php?edit=".$tovar->id."' class=\"icon edit r\" title=\"��������\" onclick=\"return ajaxLoad('',this.href)\"></a>".
                "<a href='#' class='icon vitrina'".$tovar->vitrina."' onclick='return ajaxLoad(this,\"/api.php?vitrina='.$tovar->id.'\");' title='�������/�������/�������'></a>".
                "</span>":
                "").
            "<div class='info_block'>".
            "<h3>��������</h3>".
            "<ul><li> ".$tovar->description;//.'<div class="actualized-date">��������� '.date("d.m.Y",strtotime($tovar->dat)).'</div>';
        //if(User::is_admin()) echo'<div class="right">#'.$tovar->id.'</div>';
        $category=$tovar->category;
        if(count($category)){
            $category_list='';
            foreach($category as $key=>$value)
                $category_list.=($category_list?', ':'')."\n<a href=\"/shop.php?category[".$key."]=".$value."\">".DB::GetName('category',$key)."</a>";
            echo "<br><b>��������".(count($category)>1?'�':'�').":</b>".$category_list;
        }
        echo "</li></ul>\n</div>\n";
        //if(!User::is_admin())echo "</div>\n";

        if(!isset($_GET['ajax'])) Tovar::OtherTovar($tovar);
    }

    static function CopyTovar($tov){
        $tov=Tovar::GetTovar($id=intval($tov)); if(!$tov)die('��� ����� ������ ##'.$id.'!');
        unset($tov['id']);
        $tov['name'].=' (�����)';
        $tov['seo_url']=str2url($tov['name']);
        if(self::SaveTovar($tov,array('noUnion'=>!0))){
            $ext=(empty($ext)?'*':(is_bool($ext)&&$ext ? '{'.implode(',',Image::$ext_img).'}' : (is_array($ext) ? '{'.implode(',',$ext).'}' : $ext )));
            for($i=0;$i<99;$i++){
                $files=glob($_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$id.($i?'_'.$i:'').".".$ext, GLOB_BRACE);
                if($files)foreach($files as $file){
                    copy($file, $_SERVER['DOCUMENT_ROOT'].path_tovar_image.'p'.$tov['id'].($i?'_'.$i:'').'.'.pathinfo($file, PATHINFO_EXTENSION));
                }
            }
            return $tov['id'];
        }
        //var_dump($tov);
        return 0;
    }

    static function EditTovar($tov){
        $tov=new Tovar($id=intval($tov)); if(!$tov)die('��� ����� ������ #'.$id.'!');
        ?>
        <br>
        <form id="editTovar" action="/api.php" method="post" enctype="multipart/form-data"
              ondragenter="return fb_drop(event);"
              ondragover="return fb_drop(event);"
              ondragleave="return fb_drop(event);"
              ondrop="return fb_drop(event);">
            <input type="hidden" name="id" value=<?=$id?>>
            <div class="r"><?=$tov['date_upd']?> #<?=$id?></div>
            <span class="layer act" onClick='layer(0);'>��������</span>
            <span class="layer" onClick='layer(1);'>����</span>
            <span class="layer" onClick='layer(2);'>��������</span>
            <span class="layer" onClick='layer(3);'>SEO</span>
            <span class="layer" onClick='layer(4);'>�����</span>
            <div class="layer act">
                <a href="http://yandex.ru/yandsearch?text=<?=self::toUrl($tov['name'])?>" target=_blank class="ya" title="������ �� �������">&nbsp;</a>
                <label>������������: <input type="text" name="name" size=75 value="<?=toHtml($tov['name'])?>" class="w100" onchange="this.form.seo_url.value=''" /></label>
                <br>
                <label>���:
                    <?=self::grList(0,'select',$tov['gr'],'onchange="w_gr()"');  ?>
                </label>
                <br>
                <label>��� �������������: <input type="text" name="kod_prodact" size="25" value="<?=toHtml($tov['kod_prodact'])?>" style="width:200px"></label>
                <label>���-EAN: <input type="text" name="ean" size="13" value="<?=$tov['ean']?>" style="width:200px"></label>
                <a href="http://yandex.ru/yandsearch?text=<?=self::toUrl($tov['kod_prodact'])?>" target=_blank class="ya" title="������ �� �������">&nbsp;</a>
                <br>
                <?
                    $brand_name = DB::GetName('brand', $tov['brand']);
                    $collection_name = DB::GetName('collection', $tov['collection']);
//                <label>�����: <input name="brand" value="<?=DB::GetName('brand',$tov['brand'])? >" size="25" href="/adm/brand.php?select=1&get="></label>

                    echo <<< END
                <label>�����:
                    <input type="text" name="brand" value="{$brand_name}" list="lbrand" style="width:200px"></label>
                <label>���������:
                        <input type="text" name="collection" value="{$collection_name}" list="lcollection" style="width:200px;"></label>
END;
                    echo DataList('brand');
                    echo DataList('collection');
                ?>
                <br>
                <?
                $category=$tov->category;
                if(count($category)){
                    $category_list='';
                    foreach($category as $key=>$value)
                        $category_list.=($category_list?', ':'')."\n<a href=\"/shop.php?category[".$key."]=".$value."\">".DB::GetName('category',$key)."</a>";
                    echo "<label>��������".(count($category)>1?'�':'�').":".$category_list."</label>";
                }
                ?>
                <br>
                <select name="sklad">
                    <?
                    foreach (Tovar::$_sklad_name as $key => $value)
                        echo '<option value="'.$key.'"'.($tov['sklad']==$key?' selected':'').'>'.Tovar::$_sklad_name[$key]."</option>";
                    ?>
                </select><br>
                <label>���-��(�����): <input name="kol" type="text" value="<?=$tov['kol']?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                <label>��.���������: <input name="ed" type="text" maxlength=12 value="<?=$tov['ed']?>" style="width:80px;"></label><br>
                <label><a href="#" onclick="return ajaxLoad('fb_modal','api.php?tovar&show=<?=$tov['id']?>')">�������</a> <small>(-99 ������)</small>:
                    <input name="ost" type="number" value="{$data['ost']}" style="width:80px"></label>
                <label><span id='isrok'>���� ��������, ���(�����):</span>
                    <input name="srok" type="number" value="<?=$tov['srok']?>" style="width:80px"></label>

            </div>
            <div class="layer">
                <fieldset>
                    <legend>����</legend>
                    <label>�������: <input type="text" name="price0" size=10 value="<?=str_replace(',','.',$tov['price0'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                    <select name="valuta0">
                        <option value="$"<?=($tov['valuta0']=='$'?' selected':'')?>>$</option>
                        <option value="E"<?=($tov['valuta0']=='E'?' selected':'')?>>�</option>
                        <option value=" "<?=(empty($tov['valuta0'])?' selected':'')?>>���</option>
                    </select><br>
                    <label>�������: <input type="text" name="priceu" size=10 value="<?=str_replace(',','.',$tov['priceu'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                    <select name="valuta">
                        <option value="$"<?=($tov['valuta']=='$'?' selected':'')?>>$</option>
                        <option value="E"<?=($tov['valuta']=='E'?' selected':'')?>>�</option>
                        <option value=" "<?=(empty($tov['valuta'])?' selected':'')?>>���</option>
                    </select><br>
                    <label>������ ���: <input type="text" name="price1" size=10 value="<?=str_replace(',','.',$tov['price1'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label><br>
                    <label>���: <input type="text" name="price2" size=10 value="<?=str_replace(',','.',$tov['price2'])?>" style="width:80px" pattern="\d+(\.\d{2})?"></label>
                </fieldset>
                <div class='tdes'>
                    <a href='/api.php?pt=<?=$tov['id']?>' onclick="return ajaxLoad('',this.href,'..');" title="����������">����������:</a>
                    <b><?
                        $suppliers=DB::Select2Array('supplier_link','tovar="'.$tov['id'].'"');
                        if($suppliers)foreach($suppliers as $supplier) echo "<li>".DB::GetName('supplier',$supplier['supplier']).' '.self::cPrice($tov,$supplier['supplier'])/*Tovar::CalcPrice($supplier,true)*/."</li>";
                        else echo "����������� ���"
                        ?>
                    </b>
                </div>
            </div>
            <div class="layer">
                <div class="r button hand" onclick="editon(this)">���������� ��������</div>
                <label>��������:
                    <span onclick="editSave();getObj('seo_keywords').value=getStrong(getObj('description').value);layer(3)" class="hand">[�������� SEO.KeyWords]</span>
                    <textarea class="ckeditor" name="description" id="description" rows="7" cols="75"><?=$tov['description']?></textarea></label><br>
            </div>
            <div class="layer">
                <label>Keywords: <input type="text" name="seo_keywords" id="seo_keywords" size=75 value="<?=@$tov['seo_keywords']?>" class="w100" /></label>
                <label>Description: <input type="text" name="seo_description" size=75 value="<?=@$tov['seo_description']?>" class="w100" /></label>
                <label>URL: <input type="text" name="seo_url" size=75 value="<?=@$tov['seo_url']?>" class="w100" /></label>
            </div>
            <div class="layer">
                <?
                Image::blockLoadImage($tov[self::img_name], ['path'=>path_tovar_image.'p']);
                ?>
            </div>
            <br class="clear">
            <input type='submit' value='���������' class="button" onclick='editSave();return SendForm("id<?=$id?>",this.form);' />
            <!--<input type="submit" value="��������" class="button gray"  onclick="ajaxLoad('t<?/*=$id*/?>','/api.php?out=<?/*=$id*/?>');return false;">-->
            <span id="edit<?=$id?>"></span>
        </form>
        <?
    }

    /** �������� ���
     * @param null|array|string $options
     *                  $options=null �������� ��� ���� �������
     * $options['id']=NNN - ��������� �������� ������ id
     * $options='recalc' - ����������� yml
     */
    static function ClearCash($options=null){
        if(!empty($options['id'])){
            /*            for($i=0;$i<99;$i++){
                            $fil=$_SERVER['DOCUMENT_ROOT'].$options['path'].$id.($i?'_'.$i:'').'.jpg';
                            if(is_file($fil)){
            */
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'s'.$options[self::img_name].'.jpg';  if(is_file($fil))unlink($fil);
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'m'.$options[self::img_name].'.jpg';  if(is_file($fil))unlink($fil);
        }else{
            @unlink($_SERVER['DOCUMENT_ROOT']."/log/kurs.txt");
            DB::sql("UPDATE ".self::db_prefix."category SET `cnt`=NULL");
            //message("��� ������!");
            // todo message(self::YML(!0));
            $_GET['x']=1;
            include_once $_SERVER['DOCUMENT_ROOT']."/price/yml.php";

        }
    }

    /** ���������� ��������� ����������
     * @param array|integer|Tovar $tov
     * @param int $supplier = 0 - ���������� ������������, -1 - ���� ���������� ���, ������� �����
     * @return int 0 - ��, 1 - ��� �����������, 2 - �������� �����
     */
// todo ���������� ������ ���� �� ����������� ����������/���������� ����
    static function SetFirstSupplier($tov, $supplier=0){
        $id=(is_array($tov)?$tov['id']:(is_object($tov)?$tov->id:$tov));
        $tov_o=(is_object($tov)?$tov:new Tovar($tov));
        if($supplier<1){// ����� �������� ���������� ���������� � ������� �����
            $sklad=99; $price=99999999.99;
            $result = DB::sql('SELECT * from '.db_prefix.'supplier_link WHERE tovar='.$id);
            while ($row=DB::fetch_assoc($result)){
                if(intval($row['sklad'])<$sklad){ $supplier=$row['supplier']; $price=$row['price0']*self::Kurs($row['valuta0']); }
                elseif(intval($row['sklad'])==$sklad && ($cp=($row['price0']*self::Kurs($row['valuta0']))) < $price && $cp>0){ $supplier=$row['supplier']; $price=$cp; }
            }
            if($supplier==-1){
                Tovar::Del($id);
                if(is_array($tov))$tov['supplier']=0;
                return 1;
            }elseif($supplier<1){
                echo '<br>� <a href="'.$tov_o->url.'">������ '.$id."</a> ��� �����������:
	<a id='t".$id."' href='#' onclick=\"return ajaxLoad('t".$id."','/api.php?tbl=tovar&del=".$id."');\">[X]</a>
	".DB::GetName('tovar',$id);
                if(is_array($tov))$tov['supplier']=0;
                return 1;}
        }
        if(($row=DB::Select('supplier_link','tovar='.$id.' and supplier='.$supplier))){
            $row1=(is_array($tov) ? array_merge($tov,array_intersect_key($row,array_flip(['supplier','sklad','price0','valuta0','ost']))) : $row );
            $price=Tovar::cPrice($row1,$supplier); //$row['supplier']=$row['supplier'];
            DB::sql("UPDATE ".db_prefix."tovar SET sklad='".$row1['sklad']."', price0='".$row1['price0']."', valuta0='".$row1['valuta0']."',".
                " price='".$price."', ost='".$row1['ost']."', supplier='".$supplier."', date_upd='".$row['dat']."' WHERE id=".$id.' LIMIT 1');
            DB::CacheClear('tovar',$id);

        }else{echo '<br>��������� <a href="'.$tov_o->url.'">����� '.$id.'</a>!'; return 2;}
        return 0;
    }

    static function ShowSupplier($id){  // ������ ������ [����������]
        $id=intval($id);
        $result = DB::sql('SELECT * from '.db_prefix.'supplier_link WHERE tovar='.$id);
        echo "<br class='clear'>"; $i=0;
        if(DB::num_rows($result)>0){
            while ($row=DB::fetch_assoc($result)) {
                //self::cPrice($row);
                //$tov1=array ('price0'=>$price0, 'valuta0'=>$valuta0, 'valuta'=>$valuta, $tov['supplier']=>$supplier['id'],$tov['priceu']=>$priceu,$tov['gr']=>$gr),$supplier);
                echo "<div class='supplier".($i++%2?"2":'')."'>���������: <a href='/price/?pr=".$row['supplier']."'><b>".DB::GetName('supplier',$row['supplier'])."</b></a><br>".toHtml($row['name'])."<br>
		".date("d.m.Y", strtotime($row['dat'])).', '.(empty(Tovar::$_sklad_name[$row['sklad']])?Tovar::$_sklad_name[SKLAD_OLD]:Tovar::$_sklad_name[$row['sklad']]).
                    ', ������� '.toHtml(trim($row['ost'])).', ������� '.Tovar::cPrice($row, 0, true)/*round($row['price0']*self::Kurs($row['valuta0']),price_round)*/;
                if($row['kod_prodact'] )  print "<br>��� ����������: <b>".$row['kod_prodact']."</b>";
                if($row['url']) print "<br><a href='".$row['url']."' target=_blank>".$row['url']."</a>";
                print " <a href='/api.php?tbl=supplier_link&tovar=".$id."&del=".$row['supplier']."' onclick=\"return ajaxLoad('pt".$id."',this.href);\">[�������]</a>";
                print " <a href='/api.php?tovar=".$id."&supplier=".$row['supplier']."&first=1' onclick=\"return ajaxLoad('id".$id."',this.href);\">[��������]</a></div>";
                //print " <a href='' onclick=\"return ajaxLoad('pt".$id."','/api.php?tovar=".$id."&supplier=".$row['supplier']."&new=1');\">[�������� � ��������� �����]</a>";
            }
        }else {echo "<div class='supplier'><b>��� �����������!</b><br><a href='/api.php?tbl=tovar&del=".$id."' onclick=\"return ajaxLoad('id".$id."',this.href);\">[������� �����]</a></div>";}
    }

    /** ���������� true ���� $tov['name'] � name �� ����������� � ����� ��������� ����� �������
     * @param array $tov
     * @param string $name
     * @return bool
     */
    static function IsSovmestim($tov, $name){
        $a1=$tov['name'];
        $w= [];
        /*
        // ������������� �����
        �����-�������� ����-��������, �����-��������

        ����-����� ���� ������������ �������� ���� �� ���� ���� - ��� �� ����� �������
        ������ ��������
        ���������������
        (�����)
        �����������
        �������� �������
        �������������� ���������
        ����������� ���������
        NONAME '

        ������ �� �������
        */

        $w[]= ['ps/2','usb'];
        $w[]= ['box', 'tray']; // tray=oem
        $w[]= ['box', 'oem'];
        $w[]= ['���', '�����','��������','�������'];
        $w[]= ['���', '�����','��������','���'];
        $w[]= ['�������� �������','�������������� ���������','����������� ���������'];
        $w[]= ['��������� ���','���������� ������'];
        $w[]= ['�������', '�����','������','�������','�����'];    // ������ �����
//$w[]=array('�����-��������','����-��������', '�����-��������'); �����-�������� = �����-��������
        $w[]= ['(Katun)','(�)','(Wellprint)','(Samsung)','(Gold ATM)','(RuTone)','(Hanp)','B&W','NV-Print','�������','Fullmark'];
        $a1=str_replace(['(����)','Original'],'(�)',$a1);
        $name=str_replace(['(����)','Original'],'(�)',$name);
        foreach($w as $v){$f1=0;$f2=0;
            foreach($v as $value){
                $i1=stripos($a1,$value)!==false;
                $i2=stripos($name,$value)!==false;
                if($i1 && $i2)break; // ���� ����� ���� � �����
                if($i1)$f1=$value;
                if($i2)$f2=$value;
                if($f1&&$f2&&$f1!=$f2)return false;
            }
            //if($f1||$f2)echo "<br>�������� ��������������� ".$f1."~".$f2."<br>";//return false; // ���� ���� �� ����
        }
        if(DB::Select('incompatibility','tovar='.$tov['id'].' and name="'.addslashes($name).'"'))return false;
        return true; // ��� ����
    }

// �������� �������� ���� � ��������� � ����������� ���� ���������� supplier � � ��� ������ ��������, �� �� ����������, � �������� ���������
// �� ���������� ��� ����������� ���� ����� 50%

    /** ���������� � �����������, id - ���������
     * @param $tov ['brand'] ���� ����� ����� ������������� ��� ��������, �������� � ���
     * @return bool
     */
    static function SaveTovar(&$tov){
        if(isset($_GET['debug']))echo "<br>Tovar::SaveTovar= ";var_export($tov);
        if(!empty($tov['id']) && count($tov)<18){// �� ��� ���� ��������
            if(($row=DB::Select('tovar',$tov['id']))) {foreach ($row as $key => $value) if(!isset($tov[$key]))$tov[$key]=$value;}
            else {die("������ ".$tov['id']." ���!"); }
        }

        // ������ ��� ����� ����� ���� ������� ������. ����� ����� ���� ������� ��������

        if(!empty($tov['brand']['id'])){
            $tov['brand']=$tov['brand']['id'];
        }elseif(empty($tov['brand'])){
            $tov['brand']=0;
        }elseif((intval($tov['brand'])<1) || (strval(intval($tov['brand']))!=$tov['brand'])){ // 5bites
            if(empty($tov['brand'])) $tov['brand']=0;
            elseif($row=self::GetBrand($tov['brand'],1))$tov['brand']=$row['id'];
            else die("�� ������� brand=".$tov['brand']);
        }

        if(empty($tov['gr'])){
            $tov['gr']=0;
        }elseif(intval($tov['gr'])<1){ // ���� ������ �������� �������
            if($row=self::GetGr($tov['gr']))$tov['gr']=$row['id'];
            else die("�� ������� gr=".$tov['gr']);
        }


        if (!empty($tov['kol']) && preg_match('/([0-9\.]+)([^[0-9\.]]+)$/', $tov['kol'], $ar)){
            $tov['kol'] = $ar[1];
            $tov['ed'] = $ar[2];
        }elseif(!isset($tov['ed'])) $tov['ed'] = '';
        if (!empty($tov['kol']))$tov['kol'] = floatval($tov['kol']);

        if(/*empty($tov['price']) &&*/ !empty($tov['price0'])/* && !empty($tov['priceu'])*/ && !($tov['valuta']=='*' && $tov['price']) ){
            $tov['price'] = Tovar::cPrice($tov); // ������������ ���� � ������ ������� �� ���������
        }

        //echo "1:".var_dump($tov);
        if(empty($tov['id'])){
            $tov['id']=0;
            //$row['category']='';
            $row=[];
            if(!isset($tov['ost']))$tov['ost']=0;
            $add=$tov['ost'];
            if(!isset($tov['category'])||!is_array($tov['category']))$tov['category']=[];
        }else{
            $t=new Tovar($tov['id']); // ������� ������ �� ����
            //echo "tov=".var_dump($tov)."<br>row=".var_dump($t->row)."<br>ost=".$t->ost;
            $row=$t->row;
            $row['category']=$t->category;// ����� ������ � ���
            if(!isset($tov['category'])||!is_array($tov['category']))$tov['category']=$row['category']; // ��������� �� ��������, �.�. �� ���������������
            // ���� ��������� ������� �� ��������� ��� � ������� ��� � ������
            if(!isset($tov['ost']))$tov['ost']=$t->ost;
            $add=intval($tov['ost']-$t->ost);
        }
        /*if(isset($tov['category'])&&is_array($tov['category'])){
            foreach($tov['category'] as $key => $value)if(empty($tov['category'][$key]))unset($tov['category'][$key]);
        }else $tov['category']=[];*/
        //message("tov:".var_export($tov['category'],!0)."<br>\n"."row:".var_export($row['category'],!0));
        if(self::tbl_prixod) {
            if ($add != 0) {
                if (($data = DB::Select("prixod", "dat='" . date('Y-m-d') . "' and tovar='" . $tov['id'] . "'"))) { // ������� ��� ��� ������ ����� ������, ��������� ���
                    DB::sql("UPDATE `" . db_prefix . "prixod` SET `kol`='" . (intval($data['kol']) + $add) . "' WHERE id='" . $data['id'] . "'");
                } else {
                    DB::sql("INSERT INTO `" . db_prefix . "prixod` ( `dat`, `tovar`, `kol`, `price`, `user`)
                    VALUES ( '" . date('Y-m-d') . "', '" . $tov['id'] . "', '" . $add . "', '" . $tov['price'] . "', '" . User::id() . "')");
                }
            }

            DB::log('tovar', $tov['id'], '', $row, $tov);
        }
        $f_ok=false;
        if($tov['id']>0){
            if($row['category'])foreach($row['category'] as $key => $value)if(empty($tov['category'][$key])){
                DB::Delete("category_link","tovar='".$tov['id']."' and category='".$key."'");
                //message("������ ���������: [".$key."]=".$value);
                $f_ok=$f_ok||(DB::affected_rows()>0);
            }

            if($tov['category'])foreach($tov['category'] as $key => $value)if(empty($row['category'][$key])&&$value!='0'){
                //message("������� ���������: [".$key."]=".$value);
                DB::sql("INSERT INTO `".db_prefix."category_link` (tovar, category) VALUES ('".$tov['id']."', '".$key."')");
                $f_ok=$f_ok||(DB::affected_rows()>0);
                //if(DB::affected_rows()<1)message("�������� ���������: ".print_r($tov['category'],true)."<br>".print_r($row['category'],true));
            }

        }
        $tov['collection']=(empty($tov['collection']) ? 0 : Tovar::GetCollection($tov['collection'],$tov['brand']));

        /*        $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE '.(empty($tov['id'])?'':'id<>'.$tov['id'].' and '). 'brand="'.$tov['brand'].'" and (lower(name)="'.addslashes(mb_strtolower($tov['name'])).'")');
                while ($row=DB::fetch_assoc($result)) {//Duplicate entry
                    if($tov['name']==$row['name']){
                        self::Union($tov,$row); // )Obedin(
                        echo "<br>".$tov['id']." ��������� � ".$row['id']." <b>".toHtml($row['name'])."</b>!";
                        $tov['id']=$row['id'];
                    }else{
                        //self::AskObedin($tov,$row);
                    }
                }*/

        // ������� ��� ������ ���� info � ���. ����
        //foreach(Tovar::$ar_float as $key) if(!empty($tov[$key]))$tov[$key]=str_replace(',', '.', $tov[$key]);
        $info=[];
        foreach(Tovar::$ar_info as $key) if(isset($tov[$key])){$info[$key]=str_replace(',', '.',$tov[$key]); unset($tov[$key]);}

        if($tov['id']){
            $row=DB::Select('tovar',intval($tov['id']));
            $info=array_merge(js_decode($row['info']),$info);
        }
        $tov['info']=js_encode($info);
        $tov['date_upd']=date("Y-m-d");


        $aid=[];
        $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE id<>'.$tov['id'].' and brand="'.$tov['brand'].'" and gr="'.$tov['gr'].'" and '.
            '(name="'.addslashes($tov['name']).'"'.(strlen($tov['kod_prodact'])>3?' or kod_prodact="'.addslashes($tov['kod_prodact']).'"':'').')');
        while ($row=DB::fetch_assoc($result)){//Duplicate entry
            if($tov['name']==$row['name']){
                Tovar::Union($row,$tov);
                echo "<br>��������� � ".$row['id']." <b>".toHtml($row['name'])."</b>!";
            }else{
                Tovar::AskObedin($tov,$row);
                $aid[]=$row['id'];
            }
        }
        if(strlen($tov['kod_prodact'])>3){ // TODO ���������� �������� � add.php !!!

            $kod_prodact=$tov['kod_prodact'];
            //$rf='(kod_prodact="'.addslashes($kod_prodact).'" or locate(" '.addslashes($kod_prodact).' ",CONCAT(" ",name," "))>0)';
            $rf=' and brand="'.$tov['brand'].'" and gr='.$tov['gr'].' and (lower(kod_prodact)="'.addslashes(mb_strtolower($kod_prodact)).'" or LOWER(name) REGEXP "[^A-Za-z�-��-�0-9\+]'.DB::escape(preg_quote(mb_strtolower($kod_prodact))).'[^A-Za-z�-��-�0-9\+/]")';
            $result=DB::sql('SELECT * from '.db_prefix.'tovar WHERE id<>'.$tov['id'].$rf);
            while ($row=DB::fetch_assoc($result)) if(!in_array($row['id'],$aid)){
                //if(abs($row['price']-$tov['price'])/$tov['price']>0.5){echo '<br>�� ��������� <a href="/price/tovar'.$id.'">'.$id.'</a> � <a href="/price/tovar'.$row['id'].'">'.$row['id'].'</a> ��-�� ������� ������� � ����'; continue;}
                Tovar::AskObedin($tov,$row);
                $aid[]=$row['id'];
            }
        }

        //$tov['seo_url']=str2url((empty($tov['seo_url']) ? $tov['name'] : $tov['seo_url']));
        //if(!empty($tov['seo_url']) and $row=DB::Select("tovar",(empty($tov['id'])?'':"id<>'".$tov['id']."' and ")."seo_url='".addslashes($tov['seo_url'])."'" ))die('<h3>������!</h3><br>��� ������ � ���������� SEO_URL:<br><a onclick="return!window.open(this.href)" href="'.self::_GetVar($row,'url').'">'.$row['seo_url'].'</a>!');
        if(!DB::write_array('tovar',$tov)){/*echo "<span class='red b'>- �� ���� �������� �����!!!</span>";*/ return false;}
        if(isset($_GET['debug']))echo "<br>".DB::$query;
        if(empty($tov['id'])){
            $tov['id'] = DB::GetInsertId('tovar');
            if(empty($tov['id'])) {
                echo "<span class='red b'>- �� ���� �������� �����!!!</span>";
                return true;
            }else{
                DB::Delete("category_link","tovar='".$tov['id']."'"); // �� ������ ������ � ����
                if($tov['category'])foreach($tov['category'] as $key => $value){
                    DB::sql("INSERT IGNORE INTO `".db_prefix."category_link` (tovar, category) VALUES ('".$tov['id']."', '".$key."')");
                    $f_ok=$f_ok||(DB::affected_rows()>0);
                }
            }
        }
        if($tov['supplier']==0)Tovar::SetFirstSupplier($tov['id']);

        return true;
    }

    /** ������� ������ ����������� �������
     * @param array $tov
     * @param array $old - ���������
     * @return bool
     */
    static function AskObedin($tov, $old){
        if(!self::IsSovmestim($tov,$old['name'])&&!self::IsSovmestim($old,$tov['name']))return false;
        echo "<div class='".((abs($old['price']-$tov['price'])/$tov['price']>0.5)?'inbox2':'inbox3')."' id='to".$tov['id']."'>".
            "<a href='/api.php?tovar1=".$tov['id']."&tovar2=".$old['id']."' onclick=\"return ajaxLoad('to".$tov['id']."',this.href);\">����������:</a>".
            "<a style='margin-left:25px;color:gray' href='/api.php?incompatibility=".$old['id']."&tovar=".$tov['id']."' onclick=\"return ajaxLoad('to".$tov['id']."',this.href);\">�������������</a><br>
#<a href='/price/tovar".$tov['id']."' target=_blank>".$tov['id']."</a><b>".toHtml($tov['name'])."</b> ".$tov['price']."<br>
#<a href='/price/tovar".$old['id']."' target=_blank>".$old['id']."</a><b>".toHtml($old['name'])."</b> ".$old['price']."</div>\n";
        return true;
    }


    static function nac_v($gr)
    {
        static $nac_v=[];
        if (isset($nac_v[$gr])) return $nac_v[$gr];
        elseif ($gr == 0) return '[gr=0]';
        else {
            if ($data = DB::Select('category',intval($gr))){
                $nac_v[$gr] = ($data['nac'] ? intval($data['nac']) : 0);
                return $nac_v[$gr];
            }else {
                DB::sql('UPDATE ' . db_prefix . 'tovar SET gr=0 WHERE gr=' . intval($gr));
                echo ('������! ������ ������ ' . $gr);
            }
        }
    }

    static function ImgArray($sp,$tov,$SizeName,$Size){
        $img=[];
        for($i=0;$i<99;$i++){
            //echo "<br>".path_tovar_image.$sp.$tov.($i?('_'.$i):'');
            $fil=Image::is_file(path_tovar_image.$sp.$tov.($i?('_'.$i):''));
            //echo "<br>".var_export($fil,!0);
            if(!$fil){
                if($sp =='p')break;
                if(!$fil0=Image::is_file(path_tovar_image.'p'.$tov.($i?('_'.$i):'')))break;
                if(Image::is_img($fil0)){
                    $fil=path_tovar_image.$sp.$tov.($i?('_'.$i):'').'.jpg';
                    //echo "<br>����� �� ".$fil0;
                    Image::Resize($_SERVER['DOCUMENT_ROOT'].$fil0, $_SERVER['DOCUMENT_ROOT'].$fil, $Size);
                }else $fil=$fil0;
            }
            $img[]=$fil;//ImgSrc($fil);
        }
        if(empty($img)){
            $img[]='/images/no'.$SizeName.'.gif';
            if(!is_file($_SERVER['DOCUMENT_ROOT'].$img[0]) && is_file($_SERVER['DOCUMENT_ROOT'].'/images/noimg.gif')){
                Image::Resize($_SERVER['DOCUMENT_ROOT'].'/images/noimg.gif', $_SERVER['DOCUMENT_ROOT'].$img[0], $Size);
            }
        }
        return $img;
    }

    static function YML($out=false){
        define("MaxTimeLimit",600);		# ������������ ����� ������ ������� � ��������
        ignore_user_abort(true);
        set_time_limit(MaxTimeLimit);
        $fil=$_SERVER['DOCUMENT_ROOT'].'/price/yml.yml';
        // ������ �������� ���� ��� ��� � 10 �����
        if( is_file($fil) && (time()-fileatime($fil)) <MaxTimeLimit ){if($out)$out="��� ���� ������������!"; return $out;}
        if($out)$out.="<br>\n������������ YML<br>\n";
        file_put_contents($fil, "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
<yml_catalog date=\"".date("Y-m-d H:i")."\">
<shop>
<name>".$_SERVER['HTTP_HOST']."</name>
<company>".SHOP_NAME."</company>
<url>http://".$_SERVER['HTTP_HOST']."/price/</url>
<currencies><currency id=\"RUR\"/></currencies>
<categories>\n");
        $query=DB::sql('SELECT * FROM '.db_prefix.'category');
        while ($data = DB::fetch_assoc($query))
            file_put_contents($fil, "<category id=\"".$data['id']."\">".toHtml($data['name'])."</category>\n", FILE_APPEND);//<category id="2" parentId="1">���������</category>
        file_put_contents($fil, "</categories>
<local_delivery_cost>0</local_delivery_cost>
<offers>\n", FILE_APPEND);
        if($out){$out.="��������� <b>".DB::num_rows($query)."</b> ���������<br>\n"; flush();}
        if(!is_file($_SERVER['DOCUMENT_ROOT']."/adm/country.txt")){
            $search = file_get_contents('', "http://comfortrostov.ru/adm/country.txt");
            file_put_contents($_SERVER['DOCUMENT_ROOT']."/adm/country.txt",$search );
        }
        $search = file($_SERVER['DOCUMENT_ROOT'] . "/adm/country.txt");
        $count=count($search); if($out){$out.= "��������� <b>".$count."</b> �����<br>\n"; flush();}

        $query=DB::sql('SELECT * from '.db_prefix.'tovar WHERE sklad<4');
        while ($row = DB::fetch_assoc($query)){
            $tovar=new Tovar($row);

            $name=trim($tovar->name," \t\n\r\'\"*");
            $country='';
            for($i=0;$i<$count;$i++)if(strpos($name,$country=trim($search[$i]))!==false){
                if($out)$out.=str_replace($country, '<b>'.$country.'</b>', toHtml($name))."<br>\n";
                $name=str_replace($country, '', $name); // ������ ������ �� ������������
                $name=str_replace('()', '', $name);
                $name=str_replace('  ', ' ', $name);
                break;
            }
            if(empty($name))continue;
            $pic=$tovar->imgBig[0];
            file_put_contents($fil, "<offer id=\"".$row['id']."\" available=\"".($row['sklad']<2?"true":"false")."\">
    <url>http://".$_SERVER['HTTP_HOST']."/price/tovar".$row['id']."</url>
    <price>".$tovar->price."</price>
    <currencyId>RUR</currencyId>
    <categoryId>".$row['gr']."</categoryId>
".(!is_file($_SERVER['DOCUMENT_ROOT'].$pic)?"":"    <picture>http://".$_SERVER['HTTP_HOST'].$pic."</picture>
")."    <name>".toHtml($name)."</name>
".(empty($row['brand'])?"":"    <vendor>".DB::GetName('brand',$row['brand'])."</vendor>
").(empty($row['kod_prodact'])?"":"    <vendorCode>".toHtml($row['kod_prodact'])."</vendorCode>
").(empty($row['description'])?"":"    <description>".toHtml(substr($row['description'],0,512))."</description>
").($row['sklad']<2?"":"    <sales_notes>����������</sales_notes>
").(!$country?"":"    <country_of_origin>$country</country_of_origin>
")."</offer>\n", FILE_APPEND);
        }
        file_put_contents($fil, "</offers>
</shop>
</yml_catalog>\n", FILE_APPEND);

//file_put_contents($fil.'.gz', gzencode(file_get_contents($fil), 9));

        if($out){$out.= "��������� <b>".DB::num_rows($query)."</b> �������<br>\n"; flush();}

        $zipfile=substr($fil,0,-3).'zip';
        @unlink($zipfile);

        if($out){$out.= "��������� � ".$zipfile."<br>\n"; flush();}

        $zip=new ZipArchive;
        if($zip->open($zipfile,ZipArchive::CREATE)===TRUE){
            $zip->addFile($fil,'yml.yml');	//���������������-������������,������������������������������
            $zip->close();
        }elseif($out)$out.= '��������������������������!';
        @unlink($fil);


// ������ "������" �� �������
        $zpath = dirname(__FILE__);	//yml.zip.L4sJ0A
        if($zhandle = opendir($zpath)){
            while(false !== ($zfil = readdir($zhandle)))
                if (substr($zfil,0,8)=="yml.zip."){
                    if($out)$out.= "������ ".$zpath."/".$zfil."<br>\n";
                    @unlink($zpath."/".$zfil);
                }
            closedir($zhandle);
        }

        return $out;

    }

    static function LocateKod($kod, $name=''){
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE (kod_prodact='".addslashes($kod)."' or ean='".addslashes($kod)."') LIMIT 2");
        //echo "<br>LocateKod:".DB::$query." �������: ".DB::num_rows($query); exit;
        if(DB::num_rows($query)>1){
            if(strlen($name)>10){// ��������� ����� �� ������������� ��������
                $query1=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE (kod_prodact='".addslashes($kod)."' or ean='".addslashes($kod)."') and name='".addslashes($name)."' LIMIT 2");
                if(DB::num_rows($query1)==1) return Tovar::GetTovar(DB::fetch_assoc($query1),1);
            }
            echo "<h2>��������������� ��� ����  ".$kod.":</h2>";
            while($data = DB::fetch_assoc($query)){
                $tovar=new Tovar($data);
                echo "<br>#".$tovar->id." ".$tovar->kod_prodact."/".$tovar->ean." - ".$tovar->show_name;
            }
            exit;
            return null;
        }
        return Tovar::GetTovar(DB::fetch_assoc($query),1);
    }

    static function Collection_url($brand_name, $brand_id, $collection_name='', $collection_id=0)
    {
        if(empty($collection_id)) return "/".(SEO ? urlencode(Convert::win2utf($brand_name)).'/' : 'shop.php?brand='.$brand_id);
        else return "/".(SEO ? urlencode(Convert::win2utf($brand_name))."/".urlencode(Convert::win2utf($collection_name)) :
            "shop.php?brand=".$brand_id."&amp;collection=".$collection_id)."";
    }

    static function Collection_anchor($brand_name, $brand_id, $collection_name='', $collection_id=0)
    {
        if(empty($collection_id)) return "<a href='/".(SEO?urlencode(Convert::win2utf($brand_name)).'/':'shop.php?brand='.$brand_id)."'>".$brand_name."</a>";
        else return "<a href='/".(SEO?urlencode(Convert::win2utf($brand_name))."/".urlencode(Convert::win2utf($collection_name)):
            "shop.php?brand=".$brand_id."&amp;collection=".$collection_id)."'>".$collection_name."</a>";
    }

    static function GetOst($tov){
        $id=(empty($tov['id'])?$tov:$tov['id']);
        $row=DB::fetch_assoc(DB::sql("SELECT sum(kol) as kol FROM (
            (SELECT sum(-kol) as kol FROM `".db_prefix."zakaz2` WHERE tovar='".$id."')
            UNION
            (SELECT sum(kol) as kol FROM `".db_prefix."prixod` WHERE tovar='".$id."')
            )q"));
        return ($row?$row['kol']:0);

    }

    /**
     * @param array $tovar
     */
    public static function WriteInfo($tovar){
        if(empty($tovar['id']))die('��� id!');
        $row=DB::Select('tovar',intval($tovar['id']));
        $info=js_decode($row['info']);
        foreach(self::$ar_info as $key) if(isset($tovar[$key])) $info[$key]=str_replace(',', '.',$tovar[$key]);
        DB::sql('UPDATE `'.self::db_prefix.'tovar` SET info="'.addslashes(js_encode($info)).'" WHERE id="'.intval($tovar['id']).'"');
    }


static function RecalcPrice_old($row){
    $row['priceu']=floatval($row['priceu'])*($row['valuta']=='$'?Valuta::dollar():1);
    if($row['priceu']>0&&($row['supplier']<2||$row['valuta']=='*'))   $row['price']=$row['priceu'];

    $row['price0_r']=floatval($row['price0'])*($row['valuta0']=='$'?Valuta::dollar():1);

    $nacenka=($row['price']-$row['price0_r'])/$row['price0_r'];

    if($row['price1']<=0){  // ������� -5%
        $row['price1']=floatval($row['price']) * ($nacenka>=1 ? 0.70 : 0.95);
    }
    if($row['price2']<=0){
        $row['price2']=$row['price0_r'] * ($nacenka>=1 ? 1.3 : 1.1);

    } // ������� +10%
    // ���� ��� ������ �������, �� ���� ���
    if($row['price1']>$row['price'])    $row['price']=$row['price1'];
    // ���� ������� ��� ������ �������
    if($row['price2']>$row['price1'])   $row['price2']=$row['price1'];

// ���� ���� ������ �� 100������, �� ����� ������� �� ������ 100%
    if( $row['supplier']>1 && $row['price']<=100 && $row['price2']<>0 && ($row['price']/$row['price2'])<1.8 )  $row['price']=$row['price2']*1.8;
    return $row;
}



    /** ���������� ��������� �������� ����
     * @param array $tov - �� Tovar ��� �� Supplier_link
     * @param int $supplier
     * @param bool $Show = true-������� �������� �������� ���� � ���� ��������� ������
     * @return int|mixed|string
     */
    static function cPrice(&$tov, $supplier=0, $Show=false){
        if($supplier){
            if(!isset($supplier['valuta'])){
                if (!($supplier1=DB::Select('supplier',intval($supplier)))) die( '������!! ��� ���������� '.$supplier.' ��� '.var_export($tov,!0).'<br>'.var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0));
                $supplier=$supplier1;
            }
            $valuta0=$supplier['valuta'];
            $tov['nac1']=$nac1=intval($supplier['nac']); // ������� �����������, ������� � ����������� �����������
            $supplier=$supplier['id'];
            if(!empty($tov['tovar'])&&!empty($tov['price0'])){// �������� supplier_link
                $price0=$tov['price0'];
            }elseif(!empty($tov['id']) && $tov1=DB::Select('supplier_link','tovar='.$tov['id'].' and supplier='.$supplier)){
                $price0=$tov1['price0'];
                $valuta0=$tov1['valuta0'];
            }else{
                $price0=0; // � ����� ���������� ��� ����� ������
            }
        }else{
            $valuta0=$tov['valuta0'];
            $price0=$tov['price0'];
            $supplier=intval($tov['supplier']);
            if (!($supplier1=DB::Select('supplier',intval($supplier)))) die( '������! ��� ���������� '.$supplier.' ��� '.var_export($tov,!0).'<br>'.var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), !0));
            $nac1=intval($supplier1['nac']); // ������� �����������, ������� � ����������� �����������
            $tov['nac1']=$nac1;
        }

        if(!empty($tov['tovar'])&&!isset($tov['gr'])){ // �������� supplier_link
            if (!($tov1=DB::Select('tovar',$tov['tovar'])))die('��� ����� ������ #'.$tov['tovar'].'!');
            $tov['gr']=$tov1['gr'];
        }

        if($valuta0=='$' || $valuta0=='E') $nac1+=Nacenka; // ���� ������ � ����������� ����������� - ������� ��� ����, �� �������� ������� �������
        elseif($supplier==1 && !$nac1)$nac1=Nacenka;
        elseif($valuta0>0)$nac1+=Nacenka; // ������ ������� � ����� ������

        $tov['nac2']=$nac2=(isset($tov['gr'])?self::nac_v($tov['gr']):0);	// ������� ��� ������ �������
        $price0_r=floatval($price0) * self::Kurs($tov['valuta0']); // ���������� ���� � ������
        $tov['price0_r']=$price0_r;
        $price=round($price0_r * (100+$nac1+$nac2)/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP);
        // ���� ���� ������ �� 100������, �� ����� ������� �� ������ 100%
        if($tov['supplier']>1 && $price<=100 && ($nac1+$nac2)<91 ){
            if($Show)$price='</b>'.$price.', 200%=<b>'.max($price,intval($price0_r * 2)).'';
            else $price=max($price,intval($price0_r * 2));
        }
        if($Show)$price=/*var_export($tov,!0).*/trim(($price0==intval($price0)?intval($price0):$price0)).
            (in_array(trim($tov['valuta0']),['$','E'])?trim($tov['valuta0']).'*'.self::Kurs($tov['valuta0']) :'').
            '+'.$nac1.'%+'.$nac2.'%=<b>'.$price.'</b>';
        if($tov['supplier']==1 && $tov['valuta0']!='$' && @$tov['priceu']>0 ){
            if($Show)$price.='~<b>'.intval($tov['priceu']).'</b>';
            else $price=intval($tov['priceu']);
        }
        if( !$Show && $price0_r>0 ){ // ��������� ������� ����
// price1 - ������ ��� � ������
// price2 - ��� � ������
            if(empty($tov['price1']) || $tov['price1']<$price0_r){  //  // ������ ���, ������ �� ��������� ���� discount_proc%, �� �� ���� �������
                // ������� ���� �� ����� ���� ���� ���������.
                $tov['price1']=min($price, round($price*(100-discount_proc)/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP)); // �������� �� ������ �����
            }
            if(empty($tov['price2']) || $tov['price2']<$price0_r){ // ������� ���
                // ������� ���� �� ����� ���� ���� ���������.
                $tov['price2']=min($tov['price1'], round($price0_r*(100+max(10,$nac1))/100,($price0_r<10?1:price_round),PHP_ROUND_HALF_UP));
            } // ������� +10%
            // ���� ��� ������ �������, �� ���� �������
            if($tov['price1']>$price && $price>$price0_r)    $tov['price1']=$price;
            // ���� ������� ��� ������ �������
            if($tov['price2']>$tov['price1'] && $tov['price1']>$price0_r)   $tov['price2']=$tov['price1'];
            if((empty($tov['valuta'])||$tov['valuta']!='*')&&$price>$price0_r)$tov['price']=$price;
        }

        if(!empty($tov['sklad']) && !isset(self::$_sklad_name[$tov['sklad']]))$tov['sklad']=4; // �������� �������

        return $price;
    }

    static function Kurs($valuta){
        return trim($valuta)=='$'?Valuta::dollar():(trim($valuta)=='E'?Valuta::euro():1);
    }

    /** ���������� ��������� �������� ���� ��� ������� ������
     * @param array $supplier - ���� ������� suplier+'suplier_cfg.nac' + 'tovar.gr'
     * @param bool $Show ������� �������� �������� ���� �  ���� ��������� ������
     * @return float|string
     */
    static function CalcPrice(&$supplier, $Show = false){

        if((empty($supplier['price'])||$supplier['price']<1) && $supplier['price0']>0){
            if($supplier['price0']<33){
                $supplier['price'] = 99;
            }elseif($supplier['price0']<50){
                $supplier['price'] = 120;
            }elseif($supplier['price0']<1000){
                $supplier['price']=3*$supplier['price0'];
            }elseif($supplier['price0']<3000){
                $supplier['price']=(3.55-0.00055*$supplier['price0'])*$supplier['price0'];
            }elseif($supplier['price0']<10000){
                $supplier['price']=1.9*$supplier['price0'];
            }else{
                $supplier['price']=1.4*$supplier['price0'];
            }
            $supplier['price0']=round($supplier['price0'], 0, PHP_ROUND_HALF_UP);
            $supplier['price']=max(99,round($supplier['price'], -1, PHP_ROUND_HALF_UP));
        }
        return $supplier['price'];

        /*
                if( trim($supplier['valuta']) == '$' || trim($supplier['valuta']) == 'E')Valuta::load();
                if(!isset($supplier['supplier']) && isset($supplier['supplier'])) $supplier['supplier']=$supplier['supplier'];// �������� �����

                if(!empty($supplier['priceu'])){
                   $price=($Show ?
                       ($supplier['priceu'].'*'.(trim($supplier['valuta']) == '$' ? ('$*' . Valuta::dollar()) : (trim($supplier['valuta']) == 'E' ? ('�*' . Valuta::euro()) : ''))):
                    ($supplier['priceu']*(trim($supplier['valuta']) == '$' ? Valuta::dollar() : (trim($supplier['valuta']) == 'E' ? Valuta::euro() : 1) ))
                   );
                    return $price;
                }

                if(!isset($supplier['nac'])){ // ������� ����������
                    if(empty($supplier['supplier'])){
                        $supplier['nac']=Nacenka;
                    }else{
                        $row=DB::Select('supplier', intval($supplier['supplier']) );
                        $supplier['nac']=(empty($row['nac'])? 0 : intval($row['nac'])); // ������� �����������, ������� � ����������� �����������
                    }
                    if ($supplier['valuta0'] == '$') $supplier['nac'] = ($supplier['nac'] + Nacenka); // ���� ������ � ����������� ����������� - �������, �� �������� ������� �������
                    elseif ($supplier['valuta0'] == 'E') $supplier['nac'] = ($supplier['nac'] + Nacenka); // ���� ������ � ����������� ����������� - �������, �� �������� ������� �������
                    //elseif ($supplier['supplier'] == 1 && !$supplier['nac']) $supplier['nac'] = Nacenka;
                    elseif ($supplier['valuta0'] > 0) $supplier['nac'] = ($supplier['nac'] + Nacenka); // ������ ������� � ����� ������
                }
                if(!isset($supplier['nac2'])){ // ������� ������ ������
                    if(!isset($supplier['gr']) && isset($supplier['tovar']) ){
                        $row=DB::Select('tovar', 'id=' . intval($supplier['tovar']) );
                        $supplier['gr']=(empty($row['gr'])? 0 : $row['gr'] );
                    }
                    $supplier['nac2'] = (empty($supplier['gr']) ? 0: self::nac_v($supplier['gr']) ); // ������� ��� ������ �������
                }
                $price = intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1) * (100 + $supplier['nac'] + $supplier['nac2']) / 100);
                // ���� ���� ������ �� 100������, �� ����� ������� �� ������ 100%
                if ( $price <= 100 && ($supplier['nac'] + $supplier['nac2']) < 91 ) {
                    if ($Show) $price = '</b>' . $price . ', 200%=<b>' . max($price, intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : (trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1)) * 2));
                    else $price = max($price, intval(floatval($supplier['price0']) * (trim($supplier['valuta0']) == '$' ? Valuta::dollar() : (trim($supplier['valuta0']) == 'E' ? Valuta::euro() : 1)) * 2));
                }
                if ($Show) $price = trim(($supplier['price0'] == intval($supplier['price0']) ? intval($supplier['price0']) : $supplier['price0'])) .
                    (trim($supplier['valuta0']) == '$' ? ('$*' . Valuta::dollar()) : (trim($supplier['valuta0']) == 'E' ? ('�*' . Valuta::euro()) : '')) . '+' .
                    $supplier['nac'] . '%+' . $supplier['nac2'] . '%=<b>' . $price . '</b>';
                return $price;*/

    }


}
