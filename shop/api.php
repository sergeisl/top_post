<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php"; /** @var array $area - ������ */
global $_user;/** @var USER $_user - ������� ������������ */
include_once($_SERVER['DOCUMENT_ROOT'].'/include/class/user.php');

if(isset($_GET['ajax'])){ // ���������� �� ����� ������ ���� �� ����  ��� ��� ���������
        $id=intval($_GET['ajax']); if(!$id)fb_err('������ � id!');
        $f_reload=0; // =1 - �������������, �.�. ���������� ��������� ����
        if(isset($_GET['tbl'])&&User::is_admin(!0)){
            $tbl=urldecode($_GET['tbl']);
        }else{
            $tbl='zakaz2';
        }
        if(!($row=DB::Select($tbl,intval($id))))fb_err("��� ������ id=".$id."!");
        $add='';
        $err='';
        foreach($_REQUEST as $key => $value)if(array_key_exists($key,$row)){
            $value=trim(@iconv('UTF-8', 'windows-1251//IGNORE', $value));
            if($key=='parent'&&$value==$id)fb_err('������� ���� ��� �������� ������!');
            if(substr($key,0,4)=='time')$value=date("Y-m-d H:i:s", (preg_match('/[^0-9]/',$value) ? strtotime($value) : $value));
            $add.=',`'.$key.'`="'.addslashes($value).'"';
            if(isset($_REQUEST['kol'])&&$_REQUEST['kol']!=$row['kol']){
                if (isset($row['summ'])){
                    $price = intval($row['summ'] / $row['kol']);
                    $add .= ', summ="' . round(intval($_POST['kol']) * $price, 0) . '"';
                }
                $f_reload=1;
            }
        }else{
            $err.=', '.$key;
        }

        if($add){
            DB::sql('UPDATE '.db_prefix.$tbl.' SET '.substr($add,1).' WHERE id='.$id.' LIMIT 1');
            $q=DB::$query;
            if(DB::affected_rows()){
                if($tbl=='category'&&isset($_REQUEST['reload'])) category_list($id); // & exit
                // unset($GLOBALS[$tbl.'_cash['.$id.']']); // todo �������� ���
                if($f_reload) fb_mes('','reload();'); else fb_mes('','Ok(obj);console.log(\''.$q.'\')'); }
            else{
                if(DB::Select($tbl, 'id="'.$id.'"'))
                    fb_win('��������� �� ����������!'.$q,2);
                else
                    fb_err("��� ������ id={$id}!");}
        }else fb_err("������ � ������! ��� � ����: ".substr($err,2).', ���� '.var_export($row,!0));

}elseif(isset($_GET['tbl'])&&isset($_GET['del_all'])&&User::is_admin(!0)){
    $tbl=$_GET['tbl'];
        DB::log($tbl, 0, '������ ��������');
        if($tbl=='tovar') {
            if (Tovar::DelAll("") > 0) {
                Tovar::ClearCash();
                DB::sql("alter table `" . db_prefix . "tovar` auto_increment=1;");
                fb_mes("", "reload()");
            } else fb_err("�� ������!");
        }else{
            DB::sql("TRUNCATE TABLE `".db_prefix.$tbl."`");
            fb_mes("������",'reload();');
        }

}elseif(isset($_GET['tbl'])&&isset($_GET['del'])){
    $tbl=$_GET['tbl'];
    $id=intval($_GET['del']); if($id<1)fb_err('�������� id !');
    if($tbl=='incasso'){
        if(!User::is_admin(!0)){
            if(!($data=DB::Select("incasso","id='".$id."'")))fb_err("��� ������!");
            if(date('Y-m-d',strtotime($data['time']))<date('Y-m-d'))fb_err("�� ��������!");
        }
        DB::log('incasso', $id, '��������');
        DB::Delete("incasso",$id);
        if(DB::affected_rows()>0){
            DB::sql("alter table `".db_prefix."incasso` auto_increment=1;");
            fb_mes("","removeID('id".$id."');f_reload=1;");
        }else fb_err("�� ������!");

    }elseif($tbl=='supplier_link' && $_GET['tovar']=='all' ){ //������� ��� ������ ����������. ���������� �� import
            $result = DB::sql('SELECT * from ' . db_prefix . 'supplier_link WHERE supplier=' . $id);
            while ($row = DB::fetch_assoc($result)) {
                DB::Delete('supplier_link','supplier=' . $id . ' and tovar=' . $row['tovar']);
                Tovar::SetFirstSupplier($row['tovar'], -1);
            }
            echo "�������!";

    }elseif($tbl=='supplier_link' ){ // ������� ���������� �� ������. ���������� �� Tovar::ShowSupplier
            $tov=intval($_GET['tovar']);
            DB::Delete('supplier_link','tovar='.$tov.' and supplier='.$id.' LIMIT 1');
            Tovar::ShowSupplier($tov);

    }elseif($tbl=='tovar'){
        if(Tovar::Del($id))fb_mes("","removeID('id".$id."');_fade.init();");
        else fb_err("�� ������!");

    }elseif($tbl=='zakaz'){
        if(Zakaz::Del($id))fb_mes("","removeID('id".$id."');_fade.init();");
        else fb_err("�� ������!");

    }elseif($tbl=='zakaz2'){// �������� ������ �� ������
        if(Zakaz::DelTovar($id))Out::mes("","removeID('id".$id."');");
        else Out::err("�� ������!");

    }elseif($tbl=='prixod' && User::is_admin(!0)){
        DB::log('prixod', $id, '��������');
        DB::Delete("prixod",$id);
        if(DB::affected_rows()>0){
            DB::sql("alter table `".db_prefix."prixod` auto_increment=1;");
            fb_mes("","removeID('id".$id."')");
        }else fb_err("�� ������!");

    }elseif($tbl=='supplier' && User::is_admin(!0)){
        DB::log('supplier', $id, '��������');
        DB::Delete("supplier",$id);
        if(Tovar::DelAll("supplier='".$id."'")>0){
            Tovar::ClearCash();
            DB::sql("alter table `".db_prefix."tovar` auto_increment=1;");
            fb_mes("������","removeID('id".$id."')");
        }else fb_err("�� ������!");

    }elseif($tbl=='category' && User::is_admin(!0)) {
        if(DB::Select(Tovar::tbl_alias,'gr='.$id))die('�� ������! ���� �������� ��� ������!');
        DB::log('category', $id, '��������');
        DB::Delete('category',$id);
        if (Tovar::DelAll("gr='" . $id . "'") > 0) { // todo ������ category
            Tovar::ClearCash();
            DB::sql("alter table `" . db_prefix . "tovar` auto_increment=1;");
            fb_mes("������", "removeID('id" . $id . "')");
        } else fb_err("�� ������!");

        /*        }elseif($tbl=='user'){
                    // ���� �� ���������� ���� ������� - ������� ������!
                    $query=DB::sql("SELECT * FROM `".db_prefix."sale` WHERE supplier='".$id."' LIMIT 1");
                    if(DB::num_rows($query)>0)fb_err("�� ������� ���� �������� ������.<br>�������� ����������!");
                    _log('users', $id, '��������');
                    DB::sql("DELETE FROM `".db_prefix."users` WHERE id='".$id."' LIMIT 1");
                    if(DB::affected_rows()>0){
                        DB::sql("alter table `".db_prefix."users` auto_increment=1;");
                        fb_mes("","removeID('id".$id."')");
                    }else fb_err("�� ������!");*/

    }elseif($tbl=='subscribe'){
            $tbl = $_GET['tbl'];
            DB::sql("DELETE FROM " . db_prefix . $tbl . " WHERE id='" . $id . "' and user='" . $_SESSION['user']['id'] . "' LIMIT 1");
            if (DB::affected_rows() > 0){
                fb_mes("������", "removeID('id" . $id . "');_fade.init()");
            } else fb_err("�� ������!");

    }elseif($tbl=='users'){
        if(User::Delete($id)){
            fb_mes("������","removeID('id".$id."')");
        }else fb_err("�� ������� ���� �������.<br>�������� ����������!");

    }elseif(in_array($tbl, ['brand','category','collection','shop'])){
        //isPrivDel($tbl,$id);
        DB::log($tbl, $id, '��������');
        DB::Delete($tbl,$id);
        if(DB::affected_rows()>0){
            DB::sql("alter table `".db_prefix.$tbl."` auto_increment=1;");
            if($tbl=='brand'||$tbl=='collection')DB::sql("UPDATE `".db_prefix."tovar` SET `".$tbl."`='0' WHERE `".$tbl."`='".$id."'");
            if($tbl=='brand'&&DB::is_table('collection'))DB::sql("DELETE FROM `".db_prefix."collection` WHERE brand='".$id."'");
            if($tbl=='category')DB::sql("DELETE FROM `".db_prefix."category_link` WHERE category='".$id."'");
            if($tbl=='shop')DB::sql("DELETE FROM `".db_prefix."tovar_shop` WHERE shop='".$id."'");
            fb_mes("","removeID('id".$id."')");
        }else fb_err("�� ������!");
    }else {
        fb_err("�������� �� ��������������!");
        /*DB::Delete($tbl, $id);
        if ($tbl == 'rekl') { $fil = Image::is_file('/pic/recl_' . $id, !0); if ($fil) unlink($_SERVER['DOCUMENT_ROOT'] . $fil);}
        if(DB::affected_rows()>0){
            DB::sql("alter table `".db_prefix.$tbl."` auto_increment=1;");
            fb_mes("������","removeID('id".$id."');_fade.init()");
        }else fb_err("�� ������!");
        */
    }
}elseif(isset($_GET['tovar']) && isset($_GET['show'])){// �������� �������� � �������
        $tovar=new Tovar(intval($_GET['show']));
        echo "<h2>".$tovar->show_name."</h2>";
        $ost=$tovar->ost;
        $query=DB::sql("(SELECT dat, tovar, kol, '������' as c FROM `".db_prefix."prixod` WHERE tovar='".$tovar->id."')
	UNION
	(SELECT sale.time as dat, sale2.tovar as tovar, sale2.kol as kol, '�������' as c FROM `".db_prefix."sale2` as sale2, `".db_prefix."sale` as sale WHERE sale2.sale=sale.id and sale2.tovar='".$tovar->id."')
	 ORDER BY dat DESC LIMIT 20");
        if(DB::num_rows($query)){
            echo "<table class=\"client-table\">";
            while(($data=DB::fetch_assoc($query))){
                $d=strtotime($data['dat']);
                $prix=($data['c']=='������');
                echo "<tr".($prix?" style='color:green'":"").">
		<td class='left'><a class='hand' target=_blank href='/".($prix?"tovar_prixod.php?":"report.php?layer=4&")."d_from=".date("d.m.Y",$d)."&d_to=".date("d.m.Y",$d)."'>".date("d.m.y".($prix?"":" H:i"),$d)."</a></td>
		<td>".number_format($data['kol'], 0, '.', ' ')."<small class='gray'>(".$ost.")</small></td>
		<td>".$data['c']."</td>
	  </tr>";
                $ost=$ost+($data['c']=='������'?$data['kol']:-$data['kol']);
            }
            echo "</table>";
        }else echo "��� ��������!";

}elseif(isset($_GET['log'])){ // �������� todo ����������
        $tov=(isset($_GET['tovar'])?intval($_GET['tovar']):0);
        echo "
<table class=\"client-table\">
  <thead>
  <tr><th>#<th>���� <br> �����<th>����<th>�� / �����</tr>
  </thead>
  <tbody>";
        $bar=new kdg_bar(
            ['tbl'=>db_prefix.'log',
                'sql'=>($tov?" WHERE tbl='tovar' and id='".$tov."'":"")." ORDER BY time DESC",
                'perpage'=>10,]
        );
        $res=$bar->query();
        while(($row = DB::fetch_assoc($res))){
            if($row['tbl']=='tovar')$add=" class='hand' onclick=\"return ajaxLoad('','tovar.php?form=".$row['id']."')\"";
            elseif($row['tbl']=='users')$add=" class='hand' onclick=\"return ajaxLoad('','users.php?form=".$row['id']."')\"";
            elseif($row['tbl']=='kart')$add=" class='hand' onclick=\"return ajaxLoad('','kart.php?form=".$row['id']."')\"";
            else $add='';
            echo "<tr><td".$add.">".$row['tbl']."<br>".$row['id']."</td>
	<td class='hand' onclick=\"return ajaxLoad('','users.php?form=".$row['user']."')\">".
                date("d.m.y H:i",strtotime($row['time']))." ".
                User::_GetVar($row['user'],'user_name')."</td>".
                "<td>".$row['subject']."</td>".
                "<td class='left'><div class='row1'>".$row['before']."</div><div class='row2'>".$row['after']."</div></td>".
                "</td>".
                "</tr>\n";
        }
        echo "\n</tbody></table>\n";

 /*   }elseif(isset($_GET['device'])){ //�������� �������� �� ������������ todo ����������
        $device=intval($_GET['device']);
        $tovar=intval($_GET['tovar']);
        $add='';
        $d_from=(isset($_REQUEST['d_from'])? strtotime($_REQUEST['d_from']) : strtotime(date("01.m.Y")) );
        $d_to=(isset($_REQUEST['d_to'])    ? strtotime($_REQUEST['d_to'])   : time() );
        echo "<h2>".DB::GetName('tovar',$tovar).", ���.".$device."</h2><center>c ".date('d.m.y H:i:s',$d_from).' �� '.date('d.m.y H:i:s',$d_to)."</center>";
        $query=DB::sql("SELECT sale.time as time, sale.supplier as supplier, sale.user as user, sale2.kol as kol, sale2.tovar as tovar
	FROM ".db_prefix."sale2 as sale2,".db_prefix."sale as sale
	WHERE sale.id=sale2.sale and sale2.tovar='".$tovar."' and sale2.device='".$device."' and time between '".date("Y-m-d H:i:s",$d_from)."' and '".date("Y-m-d H:i:s",$d_to)."'
	ORDER BY time");
        if(DB::num_rows($query)){
            $s=$k=0;
            echo "<table class=\"client-table\" style='min-width:300px'>";
            while(($data=DB::fetch_assoc($query))){
                $user=new User($data['user']);
                $klient=new User($data['supplier']);
                echo "\t\t<tr>
		<td>".date('d.m.y H:i',strtotime($data['time']))."</td>
		<td class='left hand' onclick=\"return ajaxLoad('','users.php?form=".$data['user']."')\">".$user->user_name."</td>
		<td class='left hand' onclick=\"return ajaxLoad('','users.php?form=".$data['supplier']."')\">".$klient->user_name."</td>
		<td>".$data['kol']."</td>
		</tr>"; $s+=$data['kol']; $k++;
            }
            echo "<tr><td colspan='3'>����� (".$k.")</td><td>".$s."</td></tr></table>";
        }else echo "��������� �� ����!";
*/

}elseif (isset($_GET['edit']) &&User::is_admin(!0)){ // ����� ����� �������������� ������
        Tovar::EditTovar($_GET['edit']);

}elseif (isset($_GET['copy']) &&User::is_admin(!0)){ // ����������� ����� � ����� ����� �������������� ������
        $id=Tovar::CopyTovar($_GET['copy']);
        Tovar::EditTovar($id);

}elseif(isset($_GET['vitrina'])&&User::is_admin(!0)) { // ������ ������ �������/�������/�������
        $tov=Tovar::GetTovar($_GET['vitrina']);
        $tov['vitrina']=(($tov['vitrina']+1)%3);
        DB::sql('UPDATE IGNORE '.db_prefix.'tovar SET vitrina="'.$tov['vitrina'].'" WHERE id='.$tov['id']);
        Out::toScript('obj.className="icon vitrina'.$tov['vitrina'].'"');


}elseif(isset($_GET['img']) &&User::is_admin(!0)){ // �������� ���� POST-�� ����� Ajax
        //if(!$user)fb_err('������ ����� ������ ������������������ ������������!'.fb_login);
        Image::SaveIMG($_GET['img']);
        Image::OutInfoFile();

}elseif(isset($_GET['link']) &&User::is_admin(!0)){ // �������� ������ �� ���� ��� ����� Ajax
        //if(!$user)fb_err('������ ����� ������ ������������������ ������������!'.fb_login);
        if(!empty($_GET['id'])){
            $id=intval($_GET['id']);
            $tovar=Tovar::GetTovar($id,true); if(!$tovar)die('�������� ��� ������!');
            $kod=$tovar[Tovar::img_name];
            $fil=$_SERVER['DOCUMENT_ROOT'].path_tovar_image.'b'.$kod.'.'.Image::GetExt($_GET['link']);
            set_time_limit(600);
            list($headers,$img,$info)=ReadUrl::ReadWithHeader($_GET['link']);
            if(empty($img))die("������ �������� � ������ ".$_GET['link']/*.":<br>\n".nl2br(var_export($info,!0))."<br>headers=".var_export($headers,!0)."<br>img=".var_export($img,!0)*/);
            if(!file_put_contents($fil,$img))die("������ ������ � ".$fil."!");
            $img_p=Image::SaveImage($fil,$kod);
            @unlink($fil); // ������ �������� �����������
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header("Content-Type: application/x-javascript; charset=windows-1251");
            echo "UpdateImg(".$id.",'".ImgSrc(path_tovar_image.'s'.$kod.'.jpg')."','".ImgSrc($img_p)."');\r\n";
        }else{
            Image::SaveLINK($_GET['link']);
            Image::OutInfoFile();
        }

}elseif(isset($_FILES['img']['name']) &&User::is_admin(!0)){ // ������ ����� form, ����� �������� �� �����
        //if(!$user)fb_err('������ ����� ������ ������������������ ������������!'.fb_login);
        if(Image::SaveFILES('img')){
            echo '���������!';
        }else{
            echo "������ �������� ����� �� ������!";
        }

}elseif (isset($_GET['del_img']) && User::is_admin(!0)) { // ������� �������� ������
        //echo (Tovar::DelImg(intval($_GET['del_img'])) ?'������!' : '�� ������!' );
        Image::DelFile($_GET['del_img']);
        // Tovar::DelImg(intval($_GET['del_img'])); // ������� �������� ������
        Image::OutInfoFile();

}elseif (isset($_GET['form_img'])&&User::is_admin(!0)) {// todo - ������� !!! ����� ���������� ����������� ������
        $id=intval($_GET['form_img']); // id ������
        $max_size_image_b=2000000;
        $max_size_image_kb=2000;
        echo <<<END
<form enctype="multipart/form-data" method="POST" action="api.php" target="upload_frame" onsubmit='getObj("upload_frame").style.display="block"' id="file_form">
<fieldset class="drag"
         ondragenter="addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();"
    ondragover="addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();"
    ondragleave="removeClass(getEventTarget(event,'FORM'),'box');"
    ondrop="return _frm.drop(event);">
<legend> �����������(<{$max_size_image_kb}).'Kb): </legend>
<input type="hidden" name="id" value="{$id}" />
<input name="MAX_FILE_SIZE" type="hidden" value="{$max_size_image_b}" />
url: <input type="text" name="img_url" size=75 /> ���<br>
����: <input type="file" name="img" size=65 id="file" />
<input type="button" value="x" onclick="return ajaxLoad('','api.php?del_img={$id}')">
<input type="submit" value="���������" />
</fieldset>
</form>
<iframe id="upload_frame" name="upload_frame" width="95%" height="30" style="display:none;border:0"></iframe>
END;

}elseif(isset($_GET['desc_img'])&& isset($_POST['desc']) &&User::is_admin(!0)){ // �������� �����
        if($_GET['desc_img']!='' && is_file($_SERVER['DOCUMENT_ROOT'] . $_GET['desc_img'])){
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . substr($_GET['desc_img'],0,-3) . Image::$ext_info , Convert::utf2win($_POST['desc']));
            fb_mes("�������� ��������","_fade.init()");
        }else fb_err('�������� ��� �����!');

}elseif(isset($_POST['name'])){ // ���������� ��������� ������
        if(!User::is_login())die(User::NeedLogin());
        foreach($_POST as $key=>$val)$tov[$key]=Convert::utf2win($val);
        $add=(Tovar::SaveTovar($tov)?'':'�� �������� �����!');
        if($tov['id']){
            $add=Image::AddFile(path_tovar_image.'p'.$tov[Tovar::img_name]);
            Tovar::ClearCash($tov);// ������� ��������� ��������
            //Tovar::PrintTovar($tov['id']);
            //exit;
        }
        if($add)fb_err($add,"_fade.init();");
        // todo ���� ������� kod, �� �������� �� ���� ���� � ������ ������ � �������� ��������� � ������� � �������� �� �������� kod, ���������� � �������� kod
        // todo ���� ���� ��� ����� �������� ���, ����� �������
        //PrintTovar($id, true);

}elseif (isset($_GET['pt'])&&User::is_admin(!0)) { // �������� ���� �����������
        Tovar::ShowSupplier($_GET['pt']);

}elseif (isset($_GET['tovar']) && isset($_GET['supplier']) && isset($_GET['first']) && User::is_admin(!0)) { // �������� ��������� /api.php?tovar=63361&supplier=22&first=1
        $id=intval($_GET['tovar']);
        Tovar::SetFirstSupplier($id, intval($_GET['supplier']));
        Tovar::PrintTovar($id);
        //ShowSupplier($id);

}elseif (isset($_GET['tovar']) && isset($_GET['kp']) &&User::is_admin(!0)) { // �������� ��� �������������
        $tov['id']=intval($_GET['tovar']);
        $tov['kod_prodact']=trim(@iconv("UTF-8", "windows-1251//IGNORE", urldecode($_GET['kp'])));
        Tovar::SaveTovar($tov);//  DB::sql("UPDATE IGNORE ".db_prefix."tovar SET  kod_prodact='".addslashes($kod_prodact)."' WHERE id=".$id);
        print Tovar::Show($tov['kod_prodact']);

}elseif(isset($_GET['basket_add'])) {// ���������� ������ � �������
        //$tovar=Tovar::GetTovar(intval($_REQUEST['id']),true);
        if(empty($_REQUEST['id']))fb_err("�� ������� ��� ������!");
        Basket::Add(intval($_REQUEST['id']),(empty($_REQUEST['kol'])?1:intval($_REQUEST['kol'])));
        Out::mes("","updateObj('Basket',\"".str_replace("\r","",str_replace("\n"," ",str_replace("\"",'\\"',Basket::show())))."\")");

}elseif(isset($_GET['basket_del'])) {// �������� ������ �� �������
        Basket::Del(intval($_GET['basket_del']));
        Out::mes("","updateObj('Basket',\"".str_replace("\r","",str_replace("\n"," ",str_replace("\"",'\\"',Basket::show())))."\")");

}elseif(isset($_GET['SetActualizedDate'])) {// ���������� ���� ������������ ���� ���� �������
        Tovar::SetActualizedDate();
        Out::mes("","reload()");

}elseif(isset($_GET['ClearCash'])) {// �������� ���
        Tovar::ClearCash();
        Out::mes("","reload()");

}elseif(isset($_GET['RecalcPrice'])) {// ����������� ��� ����
        $result = DB::sql('SELECT * from ' . db_prefix . 'tovar');
        while ($row = DB::fetch_assoc($result)) Tovar::SetFirstSupplier($row['id']);
        fb_mes("","reload()");

}elseif (isset($_GET['search_img'])&&User::is_admin(!0)) {// ����� �������� ������ �� ������ ���
        $tovar=new Tovar($_GET['search_img']);
        echo $tovar->SearchImg();

}elseif(isset($_GET['pay'])){ // ������� �� ������ �������� � �� ������ �� ������
        $id=intval($_GET['pay']);
        $zakaz=Zakaz::Get(intval($_GET['pay']));
        $user=User::GetUser($zakaz['user']); //        $ret.=", ����������: ".$user->url;
        if($zakaz['status']!=3)PaymentLog("�� �� ������ �������� ���� �����!",2);
        if($zakaz['time_end']<date('Y-m-d'))PaymentLog("����� ������� ������ �������.<br>��������� � ���������� ��� ��������� ����� �������!",2);
        $param=[];
        $param['amount']=$zakaz['summ'];	//	Number(24)	+	����� ������, ����������� � �������� ��
        $param['order_number']=$id;	//	Varchar(100)	+	���������� ����������������� ����� ������ � ������� ������ ��������-��������
        $param['order_description']='SunLife - ��������� ��� ������';	//	Varchar(500)	+	�������� ������
        $param['language']='RU';	//	Varchar(2)	+	���� ������
        $param['back_url']="http://".$GLOBALS['SERVER_NAME']."/api.php";	//	Varchar(500)	+	URL ������ �������� ������� � ������� �� �������� ������
        $param['client_name']=(empty($user['fullname'])?$user['name']:$user['fullname']);	//	Varchar(254)	-	������ ��� �������*
        $param['client_address']=$user['adress'];	//	Varchar(254)	-	������ ����� �������*
        $param['client_phone']=$user['tel'];	//	Varchar(30)	-	������� �������*
        $param['client_email']=$user['mail'];	//	Varchar(60)	-	����������� �������� ����� �������*
        $param['client_ip']=$GLOBALS['ip'];	//	Varchar(100)	-	IP-����� �������*, � ������� �� ���������� �����

        $Obj=Bank::ToBank('h2h/reg','NEW_ORDER',$param);
        if($Obj){
            $zakaz['failure_code']=$Obj->failure_code; $zakaz['ok_code']=$Obj->ok_code; $zakaz['time_ok']=date('Y-m-d H:i:s'); $zakaz['ticket']=$Obj->ticket;
            Zakaz::WriteHeader($zakaz); //DB::sql("UPDATE ".db_prefix."zakaz SET ticket='".addslashes($Obj->ticket)."', failure_code='".addslashes($Obj->failure_code)."', ok_code='".addslashes($Obj->ok_code)."' WHERE id='".$id."' LIMIT 1");
            // �������� � ������
            header("location: https://www.avangard.ru/iacq/pay?ticket=".$Obj->ticket);
        }
        die("������ �������!");

}elseif(isset($_GET['result_code'])){// ������� �� ����� ����� ������
        //if(empty($_SESSION['user']['id']))die("����� ������ �������. ���������� �������������� ������.");
        $result = DB::sql('SELECT * from '.db_prefix.'zakaz WHERE status=3'); // user="'.$_SESSION['user']['id'].'" and
        while(($row=DB::fetch_assoc($result))){
            $zakaz=Zakaz::Get($row);
            if( empty($zakaz['ticket']) || empty($zakaz['ok_code']) || empty($zakaz['failure_code']) )continue;
            if($_GET['result_code']==$zakaz['ok_code']){ // ����� ������ �� ������ ����� ��� �������� ������
                if(Bank::TestPay($zakaz['id']))PaymentLog("<span class='green b'>������ ������ �������.</span>",1,2);
                else PaymentLog("<span class='red b'>������ ������ �������, �� �� ������������ �����.</span><br>������������� ��� ����������� � ������� ������� ���������.",2,2);
            }elseif($_GET['result_code']==$zakaz['failure_code']){
                PaymentLog('<span class="red">������ �� ���������:<br>����� ����� � �������� �����.<br>������ � �������� ������, ������� �������� ������ �����.</span>',2,1);
            }
        }// ���� �������� ������� ���, �� �������� ������
        if(empty($_SESSION['message']) && empty($_SESSION['error']) ) PaymentLog('<span class="red">������ �� ��������� :<br>����� ����� � �������� �����.<br>������ � �������� ������, ������� �������� ������ �����.</span>',2,1);
        header("location: http://".$GLOBALS['SERVER_NAME']."/user/zakaz.php");

}elseif(isset($_GET['SetCategory'])) {
        Tovar::SetCategory();

}elseif(isset($_GET['category_show'])){
        $tovar=new Tovar(intval($_GET['category_show']));
        echo Tovar::_GetCategory($tovar->category);

}elseif(isset($_GET['category_list'])){
    category_list(intval($_GET['category_list']));

}elseif(isset($_GET['GetTovar'])){
        $data=Tovar::GetTovar(intval($_GET['GetTovar']));
        $data['category']=implode(',',array_keys(Tovar::_GetVar($data,'category')));
        Out::Api($data);

}elseif(isset($_GET['PrintZakaz'])) {// ����������� �����
        $title='������ ������ '.intval($_GET['PrintZakaz']);
        include_once $_SERVER['DOCUMENT_ROOT']."/include/head_print.php";
        echo Zakaz::PrintZakaz($_GET['PrintZakaz']);
        include_once $_SERVER['DOCUMENT_ROOT']."/include/tail_print.php";


}elseif(isset($_GET['UpdateTovar'])) {// ��������
        DB::sql("UPDATE `" . db_prefix . "tovar` SET `seo_url`=''");
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar`");
        while(($tov=DB::fetch_assoc($query))){
            $tov=new Tovar($tov);
            echo "<br>".$tov->url;
        }

}elseif(isset($_GET['del_file'])){ // ������
        if(!headers_sent())header("Content-type: text/html; charset=WINDOWS-1251");
        $f=basename($_GET['del_file']);
        if(substr($f,-4)=='.log' && User::is_admin(!0)){
            $f=$_SERVER['DOCUMENT_ROOT'].'/log/error/'.$f;
            if(file_exists($f)){ echo '<br>������: '.$f; @unlink($f);} else { echo '<br>��� �����: '.$f; }
        }else{
            echo '<br>����� ���� ������� ������: '.$f;
        }

}elseif(isset($_GET['recalc'])){ // �������� ��������
        Tovar::RecalcOst();

//    }else Out::BadRequest();




















}elseif(!User::is_admin(!0)){ //////// ��� ��������� �������� ������ ������ //////////
    DB::close();die("�������� API ������ ��� ������������ ����!");


}elseif(isset($_GET['ReindexBrand'])) {// ��������
    ignore_user_abort(true);
    set_time_limit(10000);
    session_write_close();
    // ���������� ������
    //DB::sql("UPDATE ".db_prefix."tovar SET brand=0");
    $query = DB::sql('SELECT * FROM '.db_prefix.'tovar WHERE brand=0');
    while ($data = DB::fetch_assoc($query)){
        if($brand=Tovar::GetBrand($data['name'])){
            echo "<br>\n".$data['name']." -> ".$brand['name'];
            DB::sql("UPDATE ".db_prefix."tovar SET brand=".$brand['id']." WHERE id=".$data['id']." LIMIT 1");
        }else echo "<br>\n�� ��������� ����� ��� ".$data['name'];
    }
    exit;


}elseif (isset($_GET['tovar1']) && isset($_GET['tovar2'])){ // ���������� ������ ->tovar::Union
    $id1=intval($_GET['tovar1']);
    $id2=intval($_GET['tovar2']); // ���������
    if (!($tov = DB::Select('tovar',$id1)))die('��� ����� ������ #'.$id1.'!');
    if (!($old = DB::Select('tovar',$id2)))die('��� ����� ������ #'.$id2.'!');
    Tovar::Union($old,$tov);
    echo "��������� � <a href='/price/tovar".$tov['id']."' target=_blank>".$tov['id']."</a>!";

}elseif (isset($_GET['tovar']) && isset($_GET['incompatibility'])){ // �������� ������ ��������������
    $id1=intval($_GET['tovar']);
    $id2=intval($_GET['incompatibility']);
    if (!($tov1 = DB::Select('tovar',$id1)))die('��� ����� ������ #'.$id1.'!');
    if (!($tov2 = DB::Select('tovar',$id2)))die('��� ����� ������ #'.$id2.'!');
    DB::sql("INSERT IGNORE INTO `".db_prefix."incompatibility` (`tovar`,`name`) VALUES ('".$tov1['id']."','".addslashes($tov2['name'])."'),('".$tov2['id']."','".addslashes($tov1['name'])."')");
    echo "��������!";


}elseif(isset($_GET['ajax']) && isset($_GET['tbl'])){ // ���������� �� ����� ������ ���� �� ����  ��� ��� ���������
    $id=intval($_GET['ajax']);// if(!$id)fb_err('������ � id!');
    $fil=$_SERVER['DOCUMENT_ROOT']."/include/rekl.dat";
    if(is_file($fil)){
        $data=file_get_contents($fil);
        if($data)$data=js_decode($data);
    }else{
        $data=array_fill(0,12,'����������');
    }
    $data[$id]=Convert::utf2win($_REQUEST['cost'.$id]);
    file_put_contents($fil,js_encode($data));
    fb_mes('','Ok(obj)');

}elseif(isset($_GET['del_file'])){ // ������
        if(!headers_sent())header("Content-type: text/html; charset=windows-1251");
        $f=basename($_GET['del_file']);
        if(substr($f,-4)=='.log' && User::is_admin(!0)){
            $f=$_SERVER['DOCUMENT_ROOT'].'/log/error/'.$f;
            if(file_exists($f)){ echo '<br>������: '.$f; @unlink($f);} else { echo '<br>��� �����: '.$f; }
        }else{
            echo '<br>����� ���� ������� ������: '.$f;
        }

}else {echo "�������� ������:\nurl=".(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'').
    ", referer=".(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'')."\nPOST=".
    var_export($_POST,!0) . "\n". var_export($_GET,!0) . "\n" .var_export($_FILES);
}

DB::close();
exit;

function category_list($id)
{
    $row = DB::Select('category', $id);
    $gr = DB::Select('category', $row['parent']);
    if ($gr) $gr = DB::Select('category', $gr['parent']);
    echo Tovar::grList(($gr ? $gr['id'] : 0), ['format' => 'option', 'act' => $row['parent'],
        'add' => " onChange=\"ajaxLoad(this, (getValue(this)==-1 ? '/api.php?category_list=" . ($gr ? $gr['id'] : 0) . "': '/api.php?tbl=category&ajax=" . $row['id'] . '&' . "parent='+getValue(this), '','','');\""]);
    exit;
}
