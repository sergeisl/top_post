<?
include_once($_SERVER['DOCUMENT_ROOT'] . '/include/config.php');
global $_user;/** @var USER $_user - текущий пользователь */
User::id();
/*$_user=new User(!empty($_SESSION['user']['id'])?$_SESSION['user']:null);
$_user->get_param();
if(empty($_SESSION['Last-Modified']))$_SESSION['Last-Modified']=time();*/

if (isset($_GET['GetPayMethod'])) {    // выбор способа пополнения счета
    if (!User::is_login()) {
        die(User::NeedLogin());
    }
    $cost = intval(urldecode($_GET['GetPayMethod']));
    $url = $GLOBALS['http'] . '://' . Get::SERVER_NAME() . '/' . urldecode($_GET['url']);
    if (GetPay() > $cost) {
        unset($_SESSION['REFERER']);
        header("Location: " . $url);
        exit;
    }
    $_SESSION['REFERER'] = $url;
    User::GetPayMethod($cost); // он сделает exit


} elseif (isset($_REQUEST['isbusy'])) {    // проверка занятости имени
    $i=User::is_busy($_REQUEST,'html');
    if($i)die($i);
    Out::mes('','Ok(obj)');

} elseif (isset($_POST['name']) && isset($_POST['pass'])) {
    User::Authorization($_POST);

} elseif (isset($_GET['vk'])) {
    $ret_path= $GLOBALS['http'] . '://' . Get::SERVER_NAME(). '/user/api.php?vk'.(empty($_GET['ret_path']) ? '' : urlencode('&ret_path='.urldecode($_GET['ret_path'])));
    $provider='http://htmlweb.ru/user/api.php';
    if (isset($_GET['error'])) {
        Out::Location('/user/signup.php');

    } elseif (isset($_REQUEST['access_token'])) {
        if (empty($_REQUEST["email"])) {
            Out::error("Не получен e-mail: " . var_export($_REQUEST['access_token'], !0));
            Out::Location('/user/signup.php'); // дорегистрация
        }
         // а теперь получим дополнительную информацию
        $params = [
            'uids' => $_REQUEST['user_id'],
            'fields'       => 'uid,first_name,last_name,screen_name,sex,bdate,photo_big,contacts',
            'access_token' => $_REQUEST['access_token']];
        list($headers, $body, $info) = ReadUrl::ReadWithHeader('https://api.vk.com/method/users.get?' . http_build_query($params), false, ['timeout' => 10]);
        // {"response":[{"uid":5607242,"first_name":"Дмитрий","last_name":"Колесников","sex":2,"screen_name":"kdg22","bdate":"22.1.1971","photo_big":"https:\/\/pp.vk.me\/c10004\/u5607242\/a_3793e787.jpg"}]}
        if ($GLOBALS['DEBUG']) file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/vk.log', "\n" . date('i:h:s') . "\n" . $info['url'] . "\nres=" . var_export($body, !0), FILE_APPEND);
        $body = json_decode($body, true);
        if (isset($body['response'][0]['uid'])) {
            $body = $body['response'][0];
            $body['mail'] = $_REQUEST['email'];
            User::login_vk($body);
            Out::LocationRef(User::is_admin(!0)?'/adm/':'/');
        }

    } else {
        if ($GLOBALS['DEBUG']) file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/vk.log', "\n" . date('i:h:s') . ' ' . $provider. '?vk&ret_path=' . $ret_path); // с очисткой файла
        Out::Location($provider. '?vk&ret_path=' . $ret_path);
    }
    exit;

} elseif (isset($_GET['fb'])) {

    $ret_path= $GLOBALS['http'] . '://' . Get::SERVER_NAME(). '/user/api.php?fb'.(empty($_GET['ret_path']) ? '' : urlencode('&ret_path='.urldecode($_GET['ret_path'])));
    $provider='https://htmlweb.ru/user/api.php';

    if (isset($_GET['error'])) {
        Out::Location('/user/signup.php');

    } elseif (isset($_REQUEST['access_token'])) { // а теперь получим информацию
            $params = [
                'fields'=>'id,email,name,gender,first_name,last_name',     /*,age_range,picture,birthday,user_birthday*/
                'access_token' => $_REQUEST['access_token']
            ];
            list($headers, $body, $info) = ReadUrl::ReadWithHeader('https://graph.facebook.com/me?' . http_build_query($params), false, ['timeout' => 10]);
            if ($GLOBALS['DEBUG']) file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/fb.log', "\n" . date('i:h:s') . "\n" . $info['url'] . "\nres=" . var_export($body, !0), FILE_APPEND);
            $body = json_decode($body, true);
            if (isset($body['id'])) {
                User::login_fb($body);
                Out::LocationRef(User::is_admin(!0)?'/adm/':'/');
            } else {
                Out::error("Не получен корректный ответ(1): " . var_export($body, !0));
                WriteErrorAndExit(3);
            }
    } else {
        // описание параметров https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow#login
        if ($GLOBALS['DEBUG']) file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/fb.log', "\n" . date('i:h:s') . $provider. '?fb&ret_path=' . $ret_path); // с очисткой файла
        Out::Location($provider. '?fb&ret_path=' . $ret_path);
    }
    exit;

} elseif (isset($_GET['register'])) {
    if ($_user->is_ban('', true)) {
        Out::error('Вам доступ на сайт запрещен!');
    }elseif(empty($_SESSION['LoginWithoutCaptcha']) && empty($_POST['g-recaptcha-response'])){
        Out::error('Вы - робот!?');

    }elseif(!defined('reCAPTCHA_sitekey')||!empty($_SESSION['LoginWithoutCaptcha'])){
        User::register($_POST);
        Out::ErrorAndExit(1);
        Out::LocationRef('/user/');
    } else {
        /*
         * https://www.google.com/recaptcha/admin#site/318686498
         * https://developers.google.com/recaptcha/docs/verify
         * https://developers.google.com/recaptcha/docs/display#render_param
         */
        list($headers, $body, $info) = ReadUrl::ReadWithHeader('https://www.google.com/recaptcha/api/siteverify',
            'secret=' . reCAPTCHA_secretkey . '&response=' . urlencode($_POST['g-recaptcha-response']) . '&remoteip=' . getenv('REMOTE_ADDR'), ['cache' => 0, 'timeout' => 10]);
        if ($body) {
            $recaptcha = json_decode($body, !0);
            if (!empty($recaptcha['success']) && $recaptcha['success']) {
                User::register($_POST);
                Out::ErrorAndExit(1);
                Out::LocationRef('/user/');
            } else Out::error("Не верная капча. Попробуйте ещё раз.");
        } else {
            SendAdminMail('Capcha error', "headers=" . var_export($headers, !0) . "\nbody= " . var_export($body, !0) . "\ninfo=" . var_export($info, !0));
            Out::error("Не удалось проверить капчу. Попробуйте ещё раз.");
        }
    }
    Out::ErrorAndExit(3, !0);

} elseif (isset($_GET['remember'])) {// переход из формы восстановления
    if (!isset($_POST['name'])) die('Не указанно имя для восстановления пароля!');
    if (!User::test_captcha()) Out::ErrorAndExit(3);
    if (User::remember($_POST['name'])) Out::LocationRef();//&exit;
    Out::ErrorAndExit(3, !0);

} elseif (isset($_GET['sendmail']) && isset($_SESSION['user']['mail'])) { // переход по ссылке "запросить повторно письмо"
    // Отправить письмо повторно с инструкцией для восстановления пароля
    User::confirm_mail($_SESSION['user']);
    Out::ErrorAndExit(3,!0);

} elseif (isset($_GET['del']) && !empty($_SESSION['user']['id']) && ($_SESSION['user']['id'] == intval($_GET['del']))) {
    $_user->delete($_SESSION['user']['id']);
    exit;

} elseif (isset($_GET['save']) && isset($_POST['name'])) { // сохранение изменений анкеты
    $_user->Save($_POST);
    if(!Get::isApi())Out::LocationRef(Get::Referer());
    exit;

} elseif (isset($_GET['getComment'])) { // Информация о тренере
    $user = new User(intval($_GET['getComment']));
    if (!$user) Out::err("Ошибочный код");
    if($user->adm>=uADM_OLD_WORKER && $user->adm<uADM_ADMIN) Out::mes('<img src="'.ImgSrc($user->avatar).'" class="avatar fr"><h2>'.$user->fullname.'</h2>'.$user->comment);
    else Out::err("Это не сотрудник");

} elseif (isset($_GET['is_confirm_phone'])) { // Проверка ранее подтвержденного телефона

} elseif (isset($_GET['confirm_phone'])) { // Запрос подтверждения телефона

} elseif (isset($_GET['unsubscribe'])) { // Отписка
    $mail = urldecode($_GET['unsubscribe']);
    if (!isset($_GET['hash'])) {
        Out::error('Не передан hash отписки!');
        Out::Location("/user/");
    }
    // http://htmlweb.ru/user/api.php?unsubscribe=kdg%40htmlweb.ru&hash=707ea04a8e397ced1f8bda75fd4110c2
    // http://htmlweb.dev/user/api.php?unsubscribe=kdg%40htmlweb.ru&hash=f36292f90a1d518000019ee0c504f03f
    if (md5(strtolower($mail . Get::SERVER_NAME())) != urldecode($_GET['hash'])) {
        Out::error('Неверный hash отписки!' . md5(strtolower($mail . Get::SERVER_NAME())));
        Out::Location("/user/");
    }
    if (!($user = new User(['mail' => $mail]))) {
        Out::error('Неверный mail отписки!');
        Out::Location("/user/");
    }
    $user->api_mail_report = 1; // Отчет по API запросам = Не присылать
    $user->rss = 0; // получать новости по электронной почте
    if (isset($_GET['all'])) {
        die("Вы успешно отписаны от всех уведомлений!");
    } else {
        Out::error('Вы успешно отписаны от всех уведомлений!');
        Out::Location("/user/");
    }

}elseif(isset($_GET['union'])) { // запрос списка клиентов или сертификатов на основании введенного в поле
    if(User::Union($_GET['from'],$_GET['union']))Out::mes('Объединил');
    else Out::err('Не объединил!');

} elseif (isset($_GET['ushow'])) { //просмотр списаний косметики и посещений по клиенту + непросроченные абонементы
    Zakaz::ushow($_REQUEST);

} elseif (isset($_GET['get'])) { // запрос списка клиентов или сертификатов на основании введенного в поле
    $name = trim(urldecode($_GET['get']));
    if (($t = strpos($name, ',')) !== false) $name = substr($name, 0, $t); // это номер сертификата и ФИО
    if (substr($name, 0, 1) == '2' and strlen($name) == 13) { // это персональная карточка пользователя
        if (($row = DB::Select('user', 'kart="' . addslashes($name) . '"'))) {
            $klient = new User($row['id']);
            echo Convert::php2json(['klient' => $klient->fullname . " " . Out::format_phone($klient->tel), 'klient_cs.value' => $row['id']]); // один конкретный
            exit;
            //echo Convert::php2json(['klient_cs' => [$row['id'] => ['text' => $klient->fullname . " " . Out::format_phone($klient->tel), 'selected' => true]], 'info' => '']);
        } else {
            echo Convert::php2json(['klient_cs' => ['0' => ['text' => " карточка не найдена ", 'selected' => true, 'disabled' => true]]]);
        }
    } elseif (Get::isKod($name) && strlen($name) < 5) { // если это сертификат
        if (($row = DB::Select('kart', intval($name)))) {
            //echo "<option value='" . $row['id'].", ",$row['klient'] . "'>Остаток: ".$row['ost']."руб.</option>\n";
            echo Convert::php2json(['klient' => $row['id'] . ", " . User::_GetVar($row['user'], 'user_name'),
                'klient_cs' => $row['user'],
                'klient_kart' => ['value' => $row['id']],
                'info' => 'Остаток: ' . $row['ost'] . 'руб.']);
        } else Out::err("Сертификат № " . $name . " не найден!");
    } else {
        $res = DB::sql("SELECT * from `" . db_prefix . "user` WHERE fullname LIKE '%" . addslashes($name) . "%' or " .
            "`name` LIKE '%" . addslashes($name) . "%' or " .
            "`tel` LIKE '%" . addslashes($name) . "%' LIMIT 15");
        if (DB::num_rows($res) == 1 && $row = DB::fetch_assoc($res)) {
            $klient = new User($row['id']);
            echo Convert::php2json(['klient' => $klient->fullname . " " . Out::format_phone($klient->tel), 'klient_cs.value' => $row['id']]); // один конкретный
            exit;
            //echo Convert::php2json(['klient_cs' => [$row['id'] => ['text' => $klient->fullname . " " . Out::format_phone($klient->tel), 'selected' => true]], 'info' => '']);
        } elseif (DB::num_rows($res) > 1) {
            $data = [];
            while ($row = DB::fetch_assoc($res)) {
                //echo "<option value='" . $row['id'] . "'>" . $row['fullname']." ".format_phone($row['tel'])."</option>\n";
                $klient = new User($row['id']);
                $data[$row['id']] = $klient->fullname . " " . Out::format_phone($klient->tel); //Klient::GetKlient($row['id']); $data[$row['id']]=$data[$row['id']]['show_name'];
            }
            echo Convert::php2json(['klient_cs' => $data]); // список
        } else
            echo Convert::php2json(['klient_cs' => ['0' => ['text' => " не найдено ", 'selected' => true, 'disabled' => true]]]);
    }
    exit;

} else {
    Out::BadRequest();
}

