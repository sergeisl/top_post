<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/config.php";
if(!User::is_login()){
    Out::error(User::NeedLogin());
    if(isset($_GET['ajax'])){
        echo nl2br($_SESSION['error']); $_SESSION['error']="";
    }else Out::Location('/user/login.php');
    exit;
}elseif(!User::is_admin() && isset($_REQUEST['id']) && $_REQUEST['id']!=$_SESSION['user']['id']){
    Out::error("Недостаточно прав доступа!");
    if(isset($_GET['ajax'])){
        echo nl2br($_SESSION['error']); $_SESSION['error']="";
    }else Out::Location('/');
    exit;
}

$id=intval((isset($_REQUEST['id'])?$_REQUEST['id']:$_SESSION['user']['id']));
$h1=($id==$_SESSION['user']['id'] ? 'Мой профиль' : 'Профиль пользователя' );
$title=$h1.SHOP_NAME;
include_once $_SERVER['DOCUMENT_ROOT']."/include/head.php";
?>
<script src="http://htmlweb.ru/calendar_kdg_utf8.min.js" async defer></script>
<script src="/user/js.js" async defer></script>
<div class="container clearfix">
<?
/** @var User $_user */
if(!$user=new User($id)){echo "Нет пользователя ".$id; return; }
    $add_url=(User::is_admin() && $id!=User::id() ? ('?id='.$id) : '');
?>
<form name="anketa" method="post" OnSubmit="return isSend(this)" action="/user/api.php?save" accept-charset="utf-8" enctype="multipart/form-data"
          ondragenter="return _frm.drop(event);"
          ondragover="return _frm.drop(event);"
          ondragleave="return _frm.drop(event);"
          ondrop="return _frm.drop(event);" data-tbl="user" data-id="<?=$user->id?>">
    <input type="hidden" name="id" value="<?=$user->id?>">

    <h1><?=$h1?></h1><?=($user->adm>=uADM_OLD_WORKER ? '('.User::$_adm[$user->adm].')' : '' )?>

    <div class="user">
    <label for="name">Логин</label>
    <input name="name" id="name" type="text" value="<?=$user->name?>" onchange="_u.isBusy(this)">
    <span id='isbusy' class="is-info"></span>

    <label for="fullname">Имя, отчество</label>
    <input name="fullname" id="fullname" type="text" value="<?=$user->fullname?>">
    <span class="is-info"></span>

<!--    <label for="lastname">Фамилия</label>-->
<!--    <input name="lastname" id="lastname" type="text" value="--><?//=$user->lastname?><!--">-->
<!--    <span class="is-info"></span>-->

    <label for="mail">E-mail</label>
    <input name="mail" id="mail" type="email" onchange="_u.isEmail(this,true)" value="<?=$user->mail?>">
    <span id='ismail' class="is-info"></span>

    <label class="check">
        <input name="rss" type="checkbox" value="1" <?=($user->rss?'checked':'')?>> Я хочу получать информацию по электронной почте.
    </label>

    <label for="tel">Телефон</label>
    <input name="tel" id="tel" type="tel" value="<?=$user->tel?>" pattern="[78][0-9]{10}" onchange="_u.isTel(this,true)" placeholder="Номер телефона с 7">
    <span id='istel' class="is-info"></span>

    <label class="check">
        <input name="sms" type="checkbox" value="1" <?=($user->sms?'checked':'')?>> Бесплатные СМС не присылать
    </label>

    <label for="comment">О себе:</label>
    <textarea name="comment" id="comment"><?=$user->comment?></textarea>

    <?
    if($id==User::id()){
    ?>
    <fieldset>
<legend>Если Вы не хотите менять пароль, оставьте поле пустым</legend>
        <label for="pass1">Пароль</label>
        <div class="password">
            <input name="pass1" id="pass1" type="password" value="" onchange="_u.isPassword(this,true)">
            <span id="eye" class="eye" title="Показать/Скрыть" onclick='o=this.previousElementSibling;o.type=(o.type=="password"?"text":"password");ShowHide("eye","hidden")'></span>
            <span id='ispass' class="is-info"></span>
        </div>
</fieldset>

<?
    }
if(isset($_SESSION['pass_change']) && $_SESSION['pass_change']){
    $add_script='if(v.pass1.value.length<4){alert("Не указан пароль!");return false;}';
}elseif(User::is_admin()){
    ?>
    <label for="discount0">Скидка на косметику:</label>
    <input name="discount0" id="discount0" type="number" value="<?=$user->discount0?>" >

    <label for="discount1">Скидка на услуги:</label>
    <input name="discount1" id="discount1" type="number" value="<?=$user->discount1?>">

	<label for="adm">ADMIN:</label>
    <select name="adm" id="adm">
    <?
    foreach(User::$_adm as $key=>$val)
        echo "\n\t<option value='$key'".($user->adm==$key?" selected":"").">$val</option>";
	echo "</select>  id=".$user->id;
    $add_script='';
?>
    <a href='/user/api.php?ushow=<?=$user->id()?>' class="icon abonement right ajax" title="Посещения и покупки"></a>
    <a href='/adm/report.php?layer=6&user=<?=$user->id()?>' class="icon comment right ajax" title="Протокол"></a>
<?
/*}elseif($_user->is_login()){
	print "<label for='pass_old'>Для сохранения изменений укажите старый пароль</label> <div class=\"password\"><input type='password' name='pass_old' id='pass_old' value=''><span id=\"eye\" class=\"eye\" title=\"Показать/Скрыть\" onclick='o=this.previousElementSibling;o.type=(o.type==\"password\"?\"text\":\"password\");ShowHide(\"eye\",\"hidden\")'></span></div>";
    $add_script='if(v.pass_old.value.length<4){alert("Не указан пароль!");return false;}';*/
}else $add_script='';
    if($user->vk_uid||$user->fb_uid){?>
        <div class="soc-net">
<?
if($user->vk_uid){?>
            <a class="icon-vk" href="https://vk.com/id<?=$user->vk_uid?>" target="_blank" title="Профиль Vkontakte"></a>
<?}
if($user->fb_uid){?>
        <a class="icon-facebook" href="https://www.facebook.com/<?=$user->fb_uid?>" target="_blank" title="Профиль Facebook"></a>
<?}?>
        </div>
<?}?>
<br><input type="submit" class="btn green" value="Сохранить">
    </div>
</form>
    </div>

<script type="text/javascript">
function isSend(v){
    if(v.mail.value==''&&v.mail.required){alert("Не указан электронный адрес!");return false;}
    if(v.tel.value==''&&v.tel.required){alert("Не указан телефон!");return false;}
    if(v.mail.value==''&&v.tel.value==''){alert("Обязательно указать хотябы телефон или e-mail!");return false;}

        <?=$add_script?>
        if(/[^a-zA-Z@\.\-0-9]/.test(v.name.value)){alert("Имя содержит недопустимые символы!"); v.name.focus();return false;}
//        if(v.pass1.value!=v.pass2.value){alert("Пароли не совпадают!");return false;}
        if (!IsMail(v.mail, v.mail.required) ) {alert("Укажите корректный e-mail, на него придет запрос подтверждения!");return false;}
        return SendForm('',v);//v.submit();
}
    <?
    if(isset($_SESSION['pass_change'])&&$_SESSION['pass_change'])
        echo "onDomReady(function(){v=document.anketa; v.pass1.focus(); v.pass1.required=true;});";
    ?>
</script>
<?
include_once $_SERVER['DOCUMENT_ROOT']."/include/tail.php";
?>
