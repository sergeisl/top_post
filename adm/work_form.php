<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";

$add_msg=(User::is_admin() ?"<blink><b>Режим руководителя!</b></blink> ":"");
if((isset($_REQUEST['klient'])||isset($_REQUEST['kart'])||isset($_REQUEST['tovar']))&&isset($_REQUEST['type'])) {
    die('перенес в /shop/api.php?sale&');
    //Tovar::Sale();
}
if(isset($_GET['type']) && $_GET['type']==99 && isset($_POST['rej'])){
    if(intval($_POST['rej'])<1 || intval($_POST['summ'])==0) Out::err("Не заполнены обязательные поля!");
    $comment=trim($_REQUEST['comment']);
    DB::sql("INSERT INTO `".db_prefix."incasso`	( `time`, `rej`, `summ`, `user`, `comment`)	".
            " VALUES (".timeForWrite().", '".intval($_POST['rej'])."', '".intval($_POST['summ'])."', ".userForWrite().", '".addslashes($comment)."')");
    Out::message("Сохранил!");
    Out::mes("","reload()");}


if(isset($_GET['type'])){//ajax- запрос формы добавления
    $type_id=intval($_GET['type']);
    //$d_from=date("d.m.Y",strtotime("first day of previous month"));

    if($type_id==tTYPE_TOVAR){// продажа косметики

        echo <<< HTML
<h2>Продажа косметики</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale&type={$type_id}" method="POST" onsubmit="return sendSale(this,1);">
<table>
	<tr>
		<td>Код<br><small>или наименование<br>косметики:</small></td>
		<td></td>
		<td><input name="tovar" type="text" size="35" maxlength="200" required href="/shop/api.php?type=0&get=" style='width:370px'
                after="afterTovar()"></td>
	</tr>
	<tr>
		<td>Ф.И.О.: <a class="icon client right" href="#" title="Последний Клиент" onclick="return LastKlient();"></a>
					<a href='#' class="icon abonement right" title="Посещения" onclick="return ShowKlient(document.work);"></a>
		<br><small>или № сертификата</small></td>
		<td></td>
		<td><input name="klient" type="text" id="klient" size="35" href="/user/api.php?get=" style='width:370px'
		    after="afterKlient()"></td>
	</tr>
	<tr>
		<td>Кол-во:</td>
		<td></td>
		<td><input name="kol" type="number" size="3" value="1" onchange="w_discount()"></td>
	</tr>
	<tr>
		<td>Цена без скидки:</td>
		<td></td>
		<td><span id="price">- </span>руб.</td>
	</tr>
	<tr>
		<td>Скидка:</td>
		<td></td>
		<td><input name="discount" type="float" size="6" max="100" onchange="w_discount()">%</td>
	</tr>
	<tr>
		<td>Цена со скидкой:</td>
		<td></td>
		<td><input name="price2" type="number" size="6" onchange="w_price()"></td>
	</tr>
	<tr class='hide'><td colspan="3">
	    Основание для предоставления скидки:
		<textarea id="comment" name="comment" style="width:100%" rows="2"></textarea></td>
	</tr>
	<tr>
		<td>Сумма:</td>
		<td></td>
		<td><span id="summ">- </span>руб.</td>
	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" value="Добавить в новый чек" onclick="this.form.action=this.form.action+'&new'">
		<input type="submit" class="button right" style="width:auto;" value="Добавить">
		</td>
	</tr>

</table>
<div id="info">{$add_msg}Вы можете указать или % скидки или цену со скидкой.</div>
</form>
HTML;

    }elseif($type_id==tTYPE_USLUGA){// услуги
$add_msg.=' Если у клиента есть абонемент или сертификат, услуга будет списана с него';
        echo <<< HTML
<h2>Оказание услуги</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale" method="POST" onsubmit="return sendSale(this);">
 <input type="hidden" name="type" value="1">
<table>
	<tr>
		<td>Вид услуги:</td>
		<td></td>
		<td><select name="tovar_cs" onchange="ajaxLoad('work', '/shop/api.php?tovar='+this.options[this.selectedIndex].value);">
			<option value="0" disabled selected>--выберите услугу--</option>
HTML;
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=".tTYPE_USLUGA." ORDER BY gr,name");
        $gr=-1;
        while ($tovar = DB::fetch_assoc($query)){
            $tovar=new Tovar($tovar);
            if($gr!=$tovar->gr){$gr=$tovar->gr; echo "\n\t\t\t<optgroup label='".($gr?DB::GetName('category',$gr):'Без группы')."'></optgroup>";}
            echo "\n\t\t\t<option value=\"".$tovar->id."\">".$tovar->show_name."</option>";
        }
        echo <<< HTML
		</select>
		</td>
	</tr>
	<tr>
		<td>Кабинка:</td>
		<td></td>
		<td><input name="device" type="number" size="3"></td>
	</tr>
	<tr>
		<td>Ф.И.О.: <a class="icon client right" href="#" title="Последний Клиент" onclick="return LastKlient();"></a>
			<a href='#' class="icon abonement right" title="Посещения" onclick="return ShowKlient(document.work);"></a>
			<a href='#' class="icon edit right" title="Изменить" onclick="return ajaxLoad('','/user/?id='+document.work.klient_cs.value)"></a>
		<br>или № сертификата
		</td>
		<td></td>
		<td><input name="klient" id="klient" type="text" size="25" href="/user/api.php?get=" after="afterKlient()"></td>

	</tr>
	<tr>
		<td>Кол-во минут(услуг):</td>
		<td></td>
		<td><input name="kol" size="3" type="number" onchange="w_discount()" required></td>
	</tr>
	<tr>
		<td>Цена без скидки:</td>
		<td></td>
		<td><span id="price">- </span>руб.</td>
	</tr>
	<tr>
		<td>Скидка:</td>
		<td></td>
		<td><input name="discount" type="float" size="6" max="100" onchange="w_discount()">%</td>
	</tr>
	<tr>
		<td>Цена со скидкой:</td>
		<td></td>
		<td><input name="price2" type="number" size="6" onchange="w_price()"></td>
	</tr>
	<tr class='hide'><td colspan="3">
	    Основание для предоставления скидки:
		<textarea id="comment" name="comment" style="width:100%" rows="2"></textarea></td>
	</tr>
	<tr>
		<td>Сумма:</td>
		<td></td>
		<td><span id="summ">- </span>руб.</td>
	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" name="new" value="Добавить в новый чек" onclick="this.form.action=this.form.action+'&new'">
		<input type="submit" class="button right" style="width:auto;" value="Добавить">
		</td>
	</tr>

</table>
<div id="info">{$add_msg}</div>
</form>
HTML;

    }elseif($type_id==tTYPE_ABON_USLUGA){// услуги по абонементу

        echo <<< HTML
<h2>Оказание услуги по абонементу</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale&type=11" method="POST" onsubmit="return sendSale(this);">
 <input name="klient_cs" id="klient_cs" type="hidden">
<table>
	<tr>
		<td>Абонемент №:
	<a href='/shop/api.php?kart&show=' class="icon abonement right" title="Посещения по этому абонементу" onclick="return ajaxLoad('',this.href+document.work.kart.value);">
</td>
		<td></td>
		<td><input name="kart" size="13" type="number" required onblur="if(this.value)return ajaxLoad(this.form,'/shop/api.php?kart='+encodeURIComponent(this.value))"></td>
	</tr>
	<tr>
		<td>Кабинка:</td>
		<td></td>
		<td><input name="device" size="3" type="number"></td>

	</tr>
	<tr>
		<td>Кол-во минут(раз):</td>
		<td></td>
		<td><input name="kol" size="3" type="number" required> из <span id="ost"></span> <span id="ed"></span> до <span id="dat_end"></span></td>
	</tr>
	<tr>
		<td>Вид услуги:</td>
		<td></td>
		<td><span id="tovar"></span></td>
	</tr>
	<tr>
		<td>Ф.И.О.: <a href='#' class="icon edit right" title="Изменить" onclick="return ajaxLoad('','/user/?id='+document.work.klient_cs.value)"></a>
		<a href='/user/api.php?ushow=' class="icon abonement right" title="Посещения и покупки" onclick="return ajaxLoad('',this.href+document.work.klient_cs.value);"></a>
		</td>
		<td></td>
		<td><span id="klient"></span></td>

	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" name="new" value="Добавить в новый чек" onclick="this.form.action=this.form.action+'&new'">
		<input type="submit" class="button right" style="width:auto;" value="Добавить"></td>
	</tr>

</table>
<div id="info">{$add_msg}</div>
</form>
HTML;

    }elseif($type_id==tTYPE_ABON){// продажа абонемента

// onclick="return ajaxLoad('','kart.php?form')">
?>
<h2>Продажа абонемента</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale&type=<?=$type_id?>" method="POST" onsubmit="return sendSale(this,1);">
 <input name="kol" value="1" type="hidden">
<table>
	<tr>
		<td>Вид абонемента:</td>
		<td></td>
		<td>
		<select name="tovar_cs" onchange="w_tovar(this)" required>
			<option value="0" disabled selected>--выберите вид абонемента--</option>
<?
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=".$type_id." ORDER BY gr,name");
        $gr=-1;
        while ($tovar = DB::fetch_assoc($query)){
            $tovar=new Tovar($tovar);
            if($gr!=$tovar->gr){$gr=$tovar->gr; echo "\n\t\t\t<optgroup label='".($gr?DB::GetName('category',$gr):'Без группы')."'></optgroup>";}
            echo "\n\t\t\t<option value=\"".$tovar->id."\">".$tovar->show_name."</option>";
        }
?>
		</select></td>
	</tr>
	<tr>
		<td>Ф.И.О.: <a class="icon client right" href="#" title="Последний Клиент" onclick="return LastKlient();"></a>
		<a href='/user/?id=' class="icon edit right" title="Изменить" onclick="return ajaxLoad('',this.href+document.work.klient_cs.value)"></a>
		<a href='/user/api.php?ushow=' class="icon abonement right" title="Посещения и покупки" onclick="return ajaxLoad('',this.href+document.work.klient_cs.value);"></a>
		</td>
		<td></td>
		<td><input name="klient" id="klient" type="text" size="25" href="/user/api.php?get=" after="afterKlient()"></td>
	</tr>
	<tr>
		<td>Цена без скидки:</td>
		<td></td>
		<td><span id="price">- </span>руб.</td>
	</tr>
	<tr>
		<td>Скидка:</td>
		<td></td>
		<td><input name="discount" type="float" size="6" max="100" onchange="w_discount()">%</td>
	</tr>
	<tr>
		<td>Цена со скидкой:</td>
		<td></td>
		<td><input name="price2" type="number" size="6" onchange="w_price()"></td>
	</tr>
	<tr class='hide'><td colspan="3">
	    Основание для предоставления скидки:
		<textarea id="comment" name="comment" style="width:100%" rows="2"></textarea></td>
	</tr>
	<tr>
		<td>Сумма:</td>
		<td></td>
		<td><span id="summ">- </span>руб.</td>
	</tr>
	<tr>
		<td>Абонемент №:</td>
		<td></td>
		<td><input name="id" size="13"></td>
	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" name="new" value="Добавить в новый чек" onclick="this.form.action=this.form.action+'&new'">
		<input type="submit" class="button right" style="width:auto;" value="Добавить"></td>
	</tr>

</table>
<div id="info"><?=$add_msg?>Не указывайте номер абонемента для присвоения очередного</div>
</form>
<?

    }elseif($type_id==tTYPE_RASX){// расходки

        echo <<< HTML
<h2>Продажа(списание) расходки</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale&type={$type_id}" method="POST" onsubmit="return sendSale(this);">
<table>
	<tr>
		<td>Наименование:</td>
		<td></td>
		<td>
		<select name="tovar_cs" onchange="w_tovar(this)">
			<option value="0" disabled selected>--выберите расходный материал--</option>
HTML;
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type='".$type_id."' ORDER BY name");
        while ($tovar = DB::fetch_assoc($query)){
            $tovar=new Tovar($tovar);
            echo "\n\t\t\t<option value=\"".$tovar->id."\">".$tovar->show_name."</option>";
        }
        echo <<< HTML
		</select></td>
	</tr>
	<tr>
		<td>Ф.И.О.: <a class="icon client right" href="#" title="Последний Клиент" onclick="return LastKlient();"></a></td>
		<td></td>
		<td><input name="klient" id="klient" type="text" size="25" href="/user/api.php?get=" after="afterKlient()"></td>
	</tr>
	<tr>
		<td>Кол-во:</td>
		<td></td>
		<td><input name="kol" type="number" size="3" value="1" onchange="w_discount()"></td>
	</tr>
	<tr>
		<td>Цена без скидки:</td>
		<td></td>
		<td><span id="price">- </span>руб.</td>
	</tr>
	<tr>
		<td>Скидка:</td>
		<td></td>
		<td><input name="discount" type="float" size="6" max="100" onchange="w_discount()">%</td>
	</tr>
	<tr>
		<td>Цена со скидкой:</td>
		<td></td>
		<td><input name="price2" type="number" size="6" onchange="w_price()"></td>
	</tr>
	<tr class='hide'><td colspan="3">
	    Основание для предоставления скидки:
		<textarea id="comment" name="comment" style="width:100%" rows="2"></textarea></td>
	</tr>
	<tr>
		<td>Сумма:</td>
		<td></td>
		<td><span id="summ">- </span>руб.</td>
	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" name="new" value="Добавить в новый чек" onclick="this.form.action=this.form.action+'&new'">
		<input type="submit" class="button right" style="width:auto;" value="Добавить"></td>
	</tr>

</table>
<div id="info">{$add_msg}</div>
</form>
HTML;

    }elseif($type_id==99){// инкассация

        echo <<< HTML
<h2>Инкассация</h2>
 <form name="work" id="work" class="work" action="/shop/api.php?sale&?type={$type_id}" method="POST" onsubmit="return sendSale(this);">
<table>
HTML;
        if(User::is_admin()){echo <<< HTML
	<tr>
		<td>Дата:</td>
		<td></td>
		<td><input name="time" value="" size="16" onfocus="_Calendar.lcs(this)" onclick="_Calendar.lcs(event)" ontouch="_Calendar.lcs(event)" /></td>
	</tr>
	<tr>
		<td>Кто:</td>
		<td></td>
		<td><select name="user">
HTML;
            $res=DB::sql("SELECT * from `".db_prefix."user` WHERE adm>=".uADM_WORKER);
            while($row = DB::fetch_assoc($res))
                echo "\n\t\t\t<option value=\"".$row['id']."\"".(isset($_SESSION['user'])&&$_SESSION['user']['id']==$row['id']?" selected":"").">".User::_GetVar($row,'fullname')."</option>";
            echo "\n\t\t</select></td>\n\t</tr>";
        }
        echo <<< HTML
	<tr>
		<td>Вид операции:</td>
		<td></td>
		<td>
		<select name="rej">
			<option value="0" disabled selected>--выберите вид операции--</option>
HTML;
        foreach($z_incasso as $k => $v)
            echo "\n\t\t\t<option value=\"".$k."\">".$v."</option>";
        echo <<< HTML
		</select></td>
	</tr>
	<tr>
		<td>Сумма:</td>
		<td></td>
		<td><input name="summ" size="25" type="number" required></td>
	</tr>
	<tr>
		<td>Примечание:</td>
		<td></td>
		<td><textarea name="comment" ></textarea></td>
	</tr>
	<tr><td colspan="3">
		<input type="submit" class="button right" style="width:auto;" value="Добавить"></td>
	</tr>

</table>
<div id="info">{$add_msg}Будет учтено на <b>{$_SESSION['user']['name']}</b></div>
</form>
HTML;
    }
    DB::close();
    exit;
}


// ajax-запрос информации по номеру абонемента
if(isset($_GET['kart'])){ // перенес в shop/api.php
    die('перенес в shop/api.php'); //   echo Convert::php2json(Kart::getAbonement(intval($_GET['kart'])));

}

// ajax-запрос информации по товару и клиенту
elseif(isset($_GET['tovar'])){ // перенес в shop/api.php
    die('перенес в shop/api.php'); //  echo Convert::php2json(Tovar::getInfo($_GET['tovar'],(isset($_GET['klient'])?$_GET['klient']:'')));

}
