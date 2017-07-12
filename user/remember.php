<?
$title='Восстановление доступа в систему. Сок чистотела';
$keywords='';
$description='Сок чистотела';
include_once $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
if(!isset($_SESSION['ret_path']) && isset($_SERVER['HTTP_REFERER']) &&
    (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) ){
    $_SESSION['ret_path']=$_SERVER['HTTP_REFERER'];
}
// todo добавить восстановление доступа на телефон
?>
<script type="text/javascript" src="/user/js.js" async></script>
<div class="container clearfix">

    <h1>Восстановление доступа в систему</h1>
    <p>В поле «Логин» необходимо ввести тот логин, который Вы указали при регистрации.</p>
    <p>Если Вы не помните логин, введите e-mail, указанный Вами при регистрации.</p>
    <p>На e-mail, указанный при регистрации, придет инструкция по восстановлению пароля.</p>
    <p>Письмо придет от <b><?=$GLOBALS['from']?></b>.</p>
    <p>Если письмо не пришло, проверьте папку СПАМ.</p>
    <p>Перед повторным запросом добавьте домен отправителя в белый список.</p><br>
    <div class="b-login user clearfix">
        <form onsubmit="return SendForm('',this);" method="post" action="/user/api.php?remember" >
            <label class="login">
                <input id="auth_form_login" name="name" maxlength="32" type="text" placeholder="Логин / E-mail" value="<?=(isset($_GET['n'])?$_GET['n']:'')?>" required>
            </label>
            <div class="g-recaptcha" data-sitekey="<?=reCAPTCHA_sitekey?>"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <input id="sbt" value="Восстановить пароль" type="submit" class="btn green">
            <p class="tac">Вы здесь впервые? – <a href="/user/signup.php">Регистрация</a></p>
        </form>
    </div>
</div>
<?
include_once $_SERVER['DOCUMENT_ROOT'].'/include/tail.php';

