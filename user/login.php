<?
$title='Вход. Luxe Fitness';
$keywords='';
$description='';
$h1 = 'Вход';
include_once $_SERVER['DOCUMENT_ROOT'].'/include/head.php';

if(!isset($_SESSION['ret_path']) && isset($_SERVER['HTTP_REFERER']) &&
    (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) ){
    $_SESSION['ret_path']=$_SERVER['HTTP_REFERER'];
}
?>
    <div class="container clearfix">
        <div class="b-login user clearfix">
            <h1>Вход</h1>
            <div class="soc-net">
                <div class="one-click"></div>
                <a class="icon-vk" href="/user/api.php?vk" title="Войти через Vkontakte"></a>
                <a class="icon-facebook" href="/user/api.php?fb" title="Войти через Facebook"></a><br>
            </div>
            <div class="auth-form">
                <form name='login' method='post' action="/user/api.php" onsubmit="if(this.name.value.length<3||this.pass.value.length<3){alert('Заполните поля!');return false;} if(verifyGrecaptcha==undefined){alert('Подтвердите, что Вы - не робот!');return false;} SendForm('',this);return false;">
                    <label class="login"><input id="auth_form_login" name="name" placeholder="Логин / E-mail / телефон" value="" maxlength="30" type="text"></label>
                    <label class="password">
                        <input id="auth_form_pass" name="pass" placeholder="Пароль" value="" type="password">
                        <span id="eye" class="eye" title="Показать/Скрыть" onclick='o=this.previousElementSibling;o.type=(o.type=="password"?"text":"password");ShowHide("eye","hidden")'></span>
                    </label>
                    <?
                    if(User::is_captcha()){ ?>
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
                    <label class="check"><input type="checkbox" id="auth_form_remember" name="remember" value="1"> Запомнить меня</label>
                    <input type="submit" class="btn green" value="Войти">
                    <a class="auth-link" href="/user/remember.php" onclick="this.search='n='+getValue('auth_form_login');">Восстановить пароль</a>
                    <hr>
                    <p class="tac">Вы здесь впервые? – <a href="/user/signup.php">Регистрация</a></p>
                </form>
            </div>
        </div>
    </div>
<?
include_once $_SERVER['DOCUMENT_ROOT'].'/include/tail.php';
