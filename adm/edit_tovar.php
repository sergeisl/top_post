<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
    if(!empty($_GET['kod'])){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Max-Age: 3600');
        if(($data = DB::Select('tovar','kod_prodact="'.addslashes(urldecode($_GET['kod'])).'"'))){
            $data['save']="Сохранить";
        }else Out::err("Нет такого!");
    }elseif(intval($_GET['form'])>0) {
        $id = intval($_GET['form']);
        if (($data = Tovar::GetTovar($id, 1))){
            $data['save'] = "Сохранить";
        } else Out::err("Нет такого!");
    }else $data='';

    if($data){
        //Tovar::UpdateFromShop($data);
    }else{
	   $data['save']="Добавить";
	   $data['id']='';$data['name']='';$data['type']=0;$data['price']='';$data['maxdiscount']='';$data['kod_prodact']='';$data['ean']='';
	   $data['description']='';$data['tovar']='';$data['kol']='';$data['srok']='';$data['ost']='';$data['price0']='';$data['brand']=0;$data['collection']=0;$data['ed']='';
        $data['supplier']=0;
    }
    if($data['kod_prodact']>0){
        $fils=path_tovar_image.'s'.$data['kod_prodact'].'.jpg'; // todo переделать на $tovar->img
        $filp=path_tovar_image.'p'.$data['kod_prodact'].'.jpg';
        if(is_file($_SERVER['DOCUMENT_ROOT'].$filp)){
            echo "\n<img data-src='".$filp."' src='".$fils."' class='left hand' height='40'>";
        }
    }/*else
        echo "<div class='box left small c' style='height:40px;width:50px' onclick=\"return ajaxLoad('','api.php?form_img=".$data['id']."');\">Нет<br>картинки</div>";*/
?>
<h2>Товар</h2><br class='clear'>
<form name="tovar" id="tovar" class="client" action="/shop/api.php" method="POST" onsubmit="return SendForm('tovar',this);"
    ondragenter="addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();"
    ondragover="addClass(getEventTarget(event,'FORM'),'box');event.stopPropagation(); event.preventDefault();"
    ondragleave="removeClass(getEventTarget(event,'FORM'),'box');"
    ondrop="return _frm.drop(event);">
<input type="hidden" name="id" value="<?=$data['id']?>">
<input type="hidden" name="tovar" value="<?=$data['tovar']?>">
<table>
	<tr>
		<td>Вид:</td>
		<td></td>
		<td><select name="type" onchange="w_type()">
<?
foreach(Tovar::$ar_type as $key=>$val)echo "\n<option value=\"".$key."\"".($data['type']==$key?' selected':'').">".$val."</option>";
?>
		</select></td>
	</tr>
<?
if($data['type']==tTYPE_ABON && User::is_admin()){
?>
	<tr>
		<td>Вид услуги:</td>
		<td></td>
		<td><select name="tovar">
<?
	echo "\n\t\t\t<option value=\"0\"".($data['tovar']==0?' selected':'').">Сертификат на любую услугу/товар</option>\n";
        $query=DB::sql("SELECT * FROM `".db_prefix."tovar` WHERE type=".tTYPE_USLUGA." ORDER BY name");
        while ($tovar = DB::fetch_assoc($query)){
            $tovar=new Tovar($tovar);
            echo "\n\t\t\t<option value=\"".$tovar->id."\"".($data['tovar']==$tovar->id?' selected':'').">".$tovar->show_name."</option>";
        }
?>
		</select>
		</td>
	</tr>
<?
}
?>
	<tr>
		<td>Код:
		    <a class="icon download right" title="Загрузить из интернета по коду" href="/shop/api.php?json&GetTovarFromCloud=" onclick="return ajaxLoad(document.tovar,this.href+encodeURIComponent(getValue(document.tovar.kod_prodact)))"></a>
		</td>
		<td></td>
		<td><input type="text" name="kod_prodact" value="<?=$data['kod_prodact']?>" class="w80">
		<label class="fr">Код-EAN: <input type="text" name="ean" value="<?$data['ean']?>" class="w80 fr"></label>
	    </td>
	</tr>
<?
    if($data['type']==tTYPE_USLUGA||$data['type']==tTYPE_ABON){
        $data['cab']=Tovar::_GetVar($data,'cab');
?>
	<tr>
		<td>Кабинки(1,2)<br><small>если не указаны или одна, то не спрашивается</small>:</td>
		<td></td>
		<td><input type="text" name="cab" value="<?=$data['cab']?>" class="w80"></td>
	</tr>
<?
    }else{
        $brand_name=DB::GetName('brand',$data['brand']);
        $collection_name=DB::GetName('collection',$data['collection']);
?>
	<tr>
		<td>Бренд/Коллекция:</td>
		<td></td>
		<td><input type="text" name="brand" value="<?=$brand_name?>" list="lbrand" style="width:80px"> /
		    <input type="text" name="collection" value="<?=$collection_name?>" list="lcollection" style="width:200px;float:right">
<datalist id="lbrand">
<?
$query=DB::sql("SELECT * FROM `".db_prefix."brand` ORDER BY name");
while ($br=DB::fetch_assoc($query))
    echo "\n<option value=\"".$br['name']."\">";
echo <<< END
\n</datalist>
<datalist id="lcollection">
END;
        $query=DB::sql("SELECT * FROM `".db_prefix."collection` ORDER BY name");
        while ($br=DB::fetch_assoc($query))
            echo "\n<option value=\"".$br['name']."\">";
        echo <<< END
\n</datalist>
		</td>
	</tr>
END;
    }
    $data['price2']=round($data['price0']*(100+tOPT_PROC)/100,($data['type']==tTYPE_RASX&&$data['price0']<50?1:0),PHP_ROUND_HALF_UP); // округляю до целого рубля
echo <<< HTML
	<tr>
		<td>Название:</td>
		<td></td>
		<td><input type="text" name="name" value="{$data['name']}"></td>
	</tr>
	<tr>
		<td>Цена:</td>
		<td></td>
		<td><input name="price" type="float" value="{$data['price']}" class="w80">
		    Опт: {$data['price2']}
		    <label class="fr">Приход: <input name="price0" type="float" value="{$data['price0']}" class="w80 fr"></label>
		    </td>
	</tr>
	<tr>
		<td>Кол-во(объем):</td>
		<td></td>
		<td><input name="kol" type="float" value="{$data['kol']}" class="w80">
		    Ед.измерения: <input name="ed" type="text" maxlength=12 value="{$data['ed']}" class="w80 fr"></td>
	</tr>
	<tr>
		<td>Максимальная скидка:</td>
		<td></td>
		<td><input name="maxdiscount" type="number" value="{$data['maxdiscount']}" class="w80" min="0" max="100"></td>
	</tr>
	<tr>
		<td><a href="/shop/api.php?tovar&show={$data['id']}"  class="ajax">Остаток</a>:</td>
		<td></td>
		<td><input name="ost" type="number" value="{$data['ost']}" class="w80">
		<span id='isrok'>Срок действия, мес(заказ):</span>
		<input name="srok" type="number" value="{$data['srok']}" class="w80 fr">
	</tr>
HTML;

    if($data['type']==tTYPE_TOVAR||$data['type']==tTYPE_RASX){
echo <<< END
	<tr>
		<td>Категории:</td>
		<td></td>
		<td>
END;
        $t=new Tovar($data);
        $category=$t->category;
        if(count($category)){
            echo "<span title='Кликните для изменения' onclick=\"return ajaxLoad(this.parentNode,'/shop/api.php?category_show=".$data['id']."');\">";
            foreach($category as $key=>$value)
                echo "\n<span class=\"category\">".DB::GetName('category',$key)."</span>";
            echo "</span>";
        }else echo "\n<span class='small i' onclick=\"return ajaxLoad(this.parentNode,'/shop/api.php?category_show=".$data['id']."');\">не выбрано. Кликните для добавления.</span>";
echo <<< END
		</td>
	</tr>
END;
}
echo <<< HTML
	<tr>
		<td colspan="3"><textarea name="description" style="width:100%" rows="4">{$data['description']}</textarea></td>
	</tr>
	<tr>
		<td>#{$data['id']}</td>
		<td colspan="2"><input type="reset" value="Копировать" class="button right" style="width:auto;" onclick="add(this.form);return true;">
				<input type="submit" id="save" class="button right" style="width:auto;" value="{$data['save']}"></td>
	</tr>

</table>
</form>
<script type="text/javascript">w_type()</script>
HTML;
Out::ErrorAndExit(3,!0);
