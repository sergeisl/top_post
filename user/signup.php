<?
include_once $_SERVER['DOCUMENT_ROOT'].'/include/config.php';
$h1=$title=(User::is_login()?'Дорегистрация':'Регистрация');
include_once $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
?>
<script type="text/javascript" src="/user/js.js" async></script>
<div class="container clearfix">
    <div class="b-login user clearfix">
        <h1><?=$h1?></h1>
        <?if(User::is_login()){
            $user=User::GetUser();
        }elseif(empty($user)){
            $user=$_REQUEST;
            ?>
            <h3>
                Регистрация через:
                <a class="icon-vk" href="/user/api.php?vk" title="Войти через Vkontakte"></a>
                <a class="icon-facebook" href="/user/api.php?fb" title="Войти через Facebook"></a><br>
            </h3>
        <?} // я сюда попаду если зарегистрируюсь через соц.сеть и телефон обязателен
        foreach(['name','fullname','birthday','mail','tel','pass1','lastname'] as $key)if(!isset($user[$key]))$user[$key]='';
        ?>
        <form name="anketa" method="post" onsubmit="if(_u.isSend(this))SendForm('is-info',this);return false;" action="/user/api.php?register">

<?if(empty($_SESSION['LoginWithoutCaptcha'])){?>
            <label for="name">Логин</label>
        <input name="name" id="name" type="text" value="<?=$user['name']; ?>" onchange="_u.isBusy(this)" required>
            <span id='isbusy' class="is-info"></span>

            <label for="pass1">Пароль</label>
            <div class="password">
        <input name="pass1" id="pass1" type="password" value="<?=$user['pass1']; ?>" onchange="_u.isPassword(this,true)" required>
                <span id="eye" class="eye" title="Показать/Скрыть" onclick='o=this.previousElementSibling;o.type=(o.type=="password"?"text":"password");ShowHide("eye","hidden")'></span>
                <span id='ispass' class="is-info"></span>
            </div>

            <label for="fullname">Имя и отчество</label>
            <input name="fullname" id="fullname" type="text" value="<?=$user['fullname']; ?>" required>

            <label for="lastname">Фамилия</label>
            <input name="lastname" id="lastname" type="text" value="<?=$user['lastname']; ?>" required>

<?}else{ ?>
    Здравствуйте, <?=$user['fullname']?>. До окончания регистрации остался один шаг.
<?}
if(empty($user['mail'])){?>
            <label for="mail">E-mail</label>
            <input name="mail" id="mail" type="email" onchange="_u.isEmail(this,true)" value="<?=$user['mail']; ?>" required>
            <span id='ismail' class="is-info"></span>
<?}?>
            <label for="tel">Телефон</label>
            <input required id="tel" name="tel" type="tel" class="mask" value="<?=(isset($_POST['tel'])?$_POST['tel']:'+7(___)___-__-__')?>" maxlength="50"
                   pattern="\+7\s?[\(]{0,1}9[0-9]{2}[\)]{0,1}\s?\d{3}[-]{0,1}\d{2}[-]{0,1}\d{2}" placeholder="+7(___)___-__-__" onchange="_u.isTel(this,true)">

<!--            <input name="tel" id="tel" type="tel" value="--><?//=@$_REQUEST['tel']; ?><!--" required pattern="7[0-9]{10}" onchange="_u.isTel(this,true)" placeholder="Номер телефона с 7">-->
            <span id='istel' class="is-is-info"></span>

        <?if(User::is_login()){?>
            <label for="avatar">Фото для абонементов</label>
            <input name="avatar" id="avatar" type="file" accept="image/*" onchange="_frm.change(event)">
        <?}?>
<?if(empty($_SESSION['LoginWithoutCaptcha'])&&defined('reCAPTCHA_sitekey')&&!User::is_login()){?>
            <script>
                var verifyGrecaptcha=undefined;
                var verifyCallback = function(response) {
                    verifyGrecaptcha=response;
                };
            </script>
            <div class="g-recaptcha" data-sitekey="<?=reCAPTCHA_sitekey?>" data-callback="verifyCallback" data-expired-callback="verifyCallback"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?}else{?>
            <script>
                var verifyGrecaptcha=1;
            </script>
        <?}?>
            <p class="tac">
                <label class="small">
                    <input id="ch" style="margin-top:10px;" required="" onchange="if(this.checked){this.setCustomValidity('');removeClass(this.parentNode,'invalid');}" type="checkbox">
                    Я согласен(а) с <a href="/agreement" class="auth-link">пользовательским соглашением</a>
                </label>
            </p>
            <input type="submit" class="btn green" value="Зарегистрироваться">
            <p class="tac small">
                <a class="auth-link" href="/privacy_policy">Политика конфиденциальности</a>
            </p>
        </form>
    </div>
</div>

<?
include_once $_SERVER['DOCUMENT_ROOT'].'/include/tail.php' ?>
