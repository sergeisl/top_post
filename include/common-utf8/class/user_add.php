
    /**
     * Возвращает пользователя для которого авторизован
     * @return int
     */
    /*private static function ActUser($api=false){
        include_once($_SERVER['DOCUMENT_ROOT'].'/include/login.inc.php');
        if( empty($_SESSION['_user']['id']) ){
            //if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && cmp($_SERVER['HTTP_REFERER'],(isset($_SERVER['HTTP_HOST'])?'http://'.$_SERVER['HTTP_HOST']:'http://htmlweb.ru')))
            //if($GLOBALS['ip']=='127.0.0.1') return 0;
            $_SESSION['_user'] = 0;
            if( self::is_confirm() || self::is_Payment() ) { // лимиты для подтвержденного пользователя или по пользователю по которому были оплаты
                $_SESSION['_user'] = $_SESSION['user'];
            }elseif(isset($_REQUEST['api_key']) && $_REQUEST['api_key'] ) {
                if(strlen($_REQUEST['api_key'])>45){error('Неверный api_key!');
                }else{
                    if ($data=DB::Select('user','api_key="' . addslashes($_REQUEST['api_key']) . '"')){
                        if($data['adm']>0){
                            $_SESSION['_user'] = $data;
                        }else {error('Не подтвержден е-mail!');}
                    }else {error('Неверный api_key!');}
                }
            }elseif(!self::is_Domain()){
                if($api)error('Не передан API_KEY или не зарегистрирован домен!');
            }

        }
        return $_SESSION['_user'];
    }*/

    /**
     * @param bool|string $api             =1 - если лимит исчерпан выводит ответ и завершает скрипт, формат вывода зависит от параметр из $_REQUEST{json|xml|html}
     *                                     =2 - Вызывает CallBackUser и завершает скрипт
     * @param bool        $OnlyShow        =true - без списания запросов
     * @param bool|string $name            ajax-запросы города и телефона считаются один на все приближение
     * @param int         $user_id
     * @param int         $counter_request кол-во запросов для списания
     * @return bool - true, если исчерпаны лимиты
     *                                     определяет $_SESSION['limit']={'msg','time','login','counter','last_name'}
     */
    static function is_limit($api=0, $OnlyShow = false, $name = '', $userID=0, $counter_request=1)
    {
        //if(empty($_SESSION['user'])){$_SESSION['_user']=self::ActUser($api); $_SESSION['error']="";}
        $user_id=($userID? $userID : (User::is_confirm()? User::id() : 0) );
        if((empty($userID) || $userID==User::id()) && isset($_SESSION['limit']['time']) &&
            $_SESSION['limit']['time'] > date("Y-m-d H:i:s", strtotime("-1 day")) &&
            $_SESSION['limit']['login'] == $user_id &&
            $_SESSION['limit']['counter'] < $counter_request
        ) { // лимит исчерпан
            $ret = true;
        } else {
            if (isset($_SESSION['limit']['last_name']) && !empty($_SESSION['limit']['last_name']) && !empty($name) && cmp($name, $_SESSION['limit']['last_name'])) {
                $_SESSION['limit']['last_name'] = $name;
                $ret = false;
            } else {
                if (isset($_SESSION['limit'])) unset($_SESSION['limit']);
                $counter = self::limit_request;
                $from = strtotime("now");
                if ($user_id) {
                    if (($data=DB::Select("limit_request","user='" . $user_id . "'"))) {
                        if ($data['time'] > date("Y-m-d H:i:s", strtotime("-1 day"))) {
                            $from = strtotime($data['time']);
                            $counter = $data['counter'];
                            if ($counter < $counter_request) {
                                $ret = true;
                            } else {
                                if (!$OnlyShow) {
                                    DB::sql('UPDATE ' . self::db_prefix . 'limit_request SET counter=counter-'.$counter_request.' WHERE user="' . $user_id . '"');
                                    $counter-=$counter_request;
                                }
                                $ret = false;
                            }
                        } else { // прошло больше суток
                            if (!$OnlyShow) {
                                $counter-=$counter_request;
                                DB::sql('UPDATE ' . self::db_prefix . 'limit_request SET counter="' . $counter . '", time="' . date("Y-m-d H:i:s") . '" WHERE user="' . $user_id . '"');
                            }
                            $ret = false;
                        }
                    } else { // первый запрос
                        if (!$OnlyShow) {
                            $counter-=$counter_request;
                            DB::sql('INSERT INTO ' . self::db_prefix . 'limit_request (user,ip,time,counter) ' .
                            'VALUES ("' . $user_id . '", "' . ip2long(Get::ip(1)) . '", "' . date("Y-m-d H:i:s") . '", "' . $counter . '") '.
                            'ON DUPLICATE KEY UPDATE counter="' . $counter . '", time="' . date("Y-m-d H:i:s") . '"');
                        }
                        $ret = false;
                    }
                } else { // лимиты для ip адреса
                    $ip = ip2long(Get::ip(1));
                    if (($data=DB::Select("limit_request","ip='" . $ip . "' and user='0'"))) {
                        //echo $data['time']." ".$data['counter'];
                        if ($data['time'] > date("Y-m-d H:i:s", strtotime("-1 day"))) {
                            $counter = $data['counter'];
                            $from = strtotime($data['time']);
                            if ($counter < $counter_request) {
                                $ret = true;
                            } else {
                                if (!$OnlyShow) {
                                    DB::sql('UPDATE ' . self::db_prefix . 'limit_request SET counter=counter-'.$counter_request.' WHERE ip="' . $ip . '" and user="0"');
                                    $counter-=$counter_request;
                                }
                                $ret = false;
                            }
                        } else { // прошло больше суток
                            if (!$OnlyShow) {
                                $counter-=$counter_request;
                                DB::sql('UPDATE ' . self::db_prefix . 'limit_request SET counter="' . $counter . '", time="' . date("Y-m-d H:i:s") . '" WHERE ip="' . $ip . '" and user="0"');
                            }
                            $ret = false;
                        }
                    } else {
                        if (!$OnlyShow) {
                            $counter-=$counter_request;
                            DB::sql('INSERT INTO ' . self::db_prefix . 'limit_request (user,ip,time,counter) ' .
                                'VALUES ("0", "' . ip2long(Get::ip(1)) . '", "' . date("Y-m-d H:i:s") . '", "' . $counter . '")'.
                                'ON DUPLICATE KEY UPDATE counter="' . $counter . '", time="' . date("Y-m-d H:i:s") . '"');
                        }
                        $ret = false;
                    }
                }
                $_SESSION['limit']['counter'] = $counter;
                $_SESSION['limit']['time'] = $from;
                $_SESSION['limit']['login'] = $user_id;
                $_SESSION['limit']['last_name'] = $name;
            }
        }

        if($user_id && !$OnlyShow)self::ApiLog($user_id, "\r\n".date("d.m.Y H:i:s").' '.Get::ip().' '.
            ($ret?'*** limit request ***':(
                (empty($_SERVER['REQUEST_URI'])?'':$_SERVER['REQUEST_URI']).(empty($_POST)?'':"\n".http_build_query($_POST))))."\r\n"/*."\n Стек: ".var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),!0)*/);

        //var_dump($ret , $user_id , $_SESSION['_user']);
        if ($ret && $user_id){ // лимит исчерпан и можно автосписать
            $ret=!self::SetLimit(0, $counter_request, $user_id); // если денег нет то отправлю уведомнение и запишу в API-лог
            if(!$ret && !$OnlyShow)self::ApiLog($user_id, "\r\nLimit Updated\r\n".(empty($_SERVER['REQUEST_URI'])?'':$_SERVER['REQUEST_URI']).(empty($_POST)?'':"\n".http_build_query($_POST))."\r\n");
        }elseif(!$ret && $user_id && $_SESSION['limit']['counter']==10){
            // если лимиты подходят к концу, уведомить
            $balans=Money::GetPay($user_id);
            $tarif=self::GetUserTarif($user_id);
            if($balans<max(1,$tarif['cost'])){
                self::SendNoticeBalans($user_id, "Лимит запросов заканчивается. На вашем балансе осталось ".$balans." руб.\n".
                   ($tarif['cost']>1?"Для приобретения ".$tarif['request']." запросов по Вашему тарифу необходимо ".$tarif['cost']." рублей.\n":"").
                    "Во избежание прекращения выполнения ваших API запросов необходимо <a href=\"".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/balans.php?cost=".max(10,$tarif['cost'])."#din\">пополнить баланс</a>");
            }
        }

        if ($ret){ // лимит исчерпан
            if(!$_SESSION['limit']['login'] && $counter_request>self::limit_request)
                $_SESSION['limit']['msg'] = "<div class='error'>".User::NeedLogin()."</div>";
            else
            $_SESSION['limit']['msg'] =
                "<div class='info'><span class='red'>Исчерпан лимит количества запросов в сутки для " .
                ($_SESSION['limit']['login'] ? "одного пользователя.</span><br>".
                    "<a href='/user/api.php?GetPayMethod=1&url=" . urlencode('user/api.php?SetLimit='.self::$ar_pay[1]['request']) .
                    "' onclick='return ajaxLoad(this.parentNode,this.href);'>Приобрести пакет</a> из ".self::$ar_pay[1]['request'].
                    " запросов на сутки за ".self::$ar_pay[1]['cost']." рубл".num2word(self::$ar_pay[1]['cost'],['ь','я','ей'])."?" :
                    "одного ip адреса.</span> <a href='/user/signup.php'>Зарегистрируйтесь</a>.") .
                "</div>";
            if ($api){
                self::SendNoticeBalans($user_id, 'Исчерпан лимит количества запросов');
                if(isset($_REQUEST['json']) || isset($_REQUEST['html']) || isset($_REQUEST['xml']) ){
                    /*if(!headers_sent()){
                        header("HTTP/1.0 429 You exceeded the rate limit");
                    }*/
                    $ar=[ 'limit'=>$_SESSION['limit']['counter'], 'error'=>"Исчерпан лимит количества запросов в сутки для " . ($_SESSION['limit']['login'] ? "одного пользователя.".self::NeedLogin() : "одного ip адреса.") ];
                    outApi($ar);
                }else{
                    Out::err($_SESSION['limit']['msg']);
                }
                exit;
            }
            return true;
        } else {
            $_SESSION['limit']['msg'] = "<div class='limit' onclick='ajaxLoad(\"\",\"/user/tariffs.php?ajax\")'>Остал" . num2word($_SESSION['limit']['counter'], ["ся", "ось", "ось"]) . " " . ($_SESSION['limit']['counter']) . " запрос" . num2word($_SESSION['limit']['counter'], ["", "а", "ов"]) . " до " . date("H:i:s d.m.Y", $_SESSION['limit']['time'] + 60 * 60 * 24) . "</div>";
            //var_dump($_SESSION['limit'],$ret);
            return false;
        }
        //var_dump($_SESSION['limit'],$ret);
    }

    /** возвращает тариф для указанного пользователя
     * @param integer $user_id
     * @return array
     */
    static function GetUserTarif($user_id){
/*        $user=User::GetUser($user_id);
        $limit=$user['autopay'];
        $tarif=self::$ar_pay[0];
        foreach(User::$ar_pay as $tarif)if($limit<=$tarif['request'])break;
        return $tarif;*/
    }

    /** Приобретение лимитов
     * @param int  $limit - если =0, то списать столько, сколько у него указано в настроке autopay
     * @param bool|int $auto - авто списание указанного кол-ва запросов
     * @param int $user_id
     * @return bool
     */
    static function SetLimit($limit=0,$auto=false, $user_id=0){
        if(!$user_id)$user_id=User::id();
        if(!User::is_login($user_id)){if(!$auto)Out::error("Не задан пользователь!");return false;}
        if($limit<1){ // если не передано сколько купить лимитов, то покупаю согласно тарифу из профиля
            $tarif=self::GetUserTarif($user_id);
            // $limit=$tarif['request'];
        }else{
            $tarif=self::$ar_pay[0];
            foreach(User::$ar_pay as $tarif)if($limit<=$tarif['request'])break;
       }
        $ret=false;
        if(Money::Payment($tarif['cost'], $c=($tarif['request'].' запрос'.num2word($tarif['request'],['','а','ов'])), false, $user_id ) ){
            $_SESSION['limit']['counter'] = $tarif['request']-($auto?($auto>0?$auto:1):0);
            $_SESSION['limit']['time'] = strtotime("+".($tarif['day']-1).' day');
            $_SESSION['limit']['login'] = $user_id;//self::ActUser(); if($_SESSION['limit']['login'])$_SESSION['limit']['login']=$_SESSION['limit']['login']['id'];
            $_SESSION['limit']['last_name'] = '';
            DB::sql('UPDATE ' . self::db_prefix . 'limit_request SET counter="' . $_SESSION['limit']['counter'] . '", time="' . date("Y-m-d H:i:s",$_SESSION['limit']['time']) . '" WHERE user="' . $_SESSION['limit']['login'] . '"');
            if (DB::affected_rows() < 1) DB::sql('INSERT INTO ' . self::db_prefix . 'limit_request (user,ip,time,counter) ' .
                'VALUES ("' . $_SESSION['limit']['login'] . '", "' . ip2long(Get::ip(!0)) . '", "' . date("Y-m-d H:i:s") . '", "' . $_SESSION['limit']['counter'] . '")'.
                'ON DUPLICATE KEY UPDATE counter="' . $_SESSION['limit']['counter'] . '", time="' . date("Y-m-d H:i:s") . '"');
            if(!$auto)message("Начислено " . $c . " до " . date("H:i:s d.m.Y", $_SESSION['limit']['time'] + 60 * 60 * 24));
            $ret=true;
        }else{
            if(!$auto)Out::error("Недостаточно средств для приобретения ".$tarif['request'].' запросов');
        }
        $balans=Money::GetPay($user_id);
        if( $balans>-1 && $balans<max(1,$tarif['cost']) ){ // уведомить если осталось меньше чем тариф
            self::SendNoticeBalans($user_id, "На вашем балансе осталось ".$balans." руб.\n".
                ($ret ? "" : "Все сервисы отключены\n").
                ($tarif['cost']>1?"Для приобретения ".$tarif['request']." запросов по Вашему тарифу необходимо ".$tarif['cost']." рублей.\n":"").
                "<a href=\"".$GLOBALS['http'].'://'.Get::SERVER_NAME()."/user/balans.php?cost=".max(10,$tarif['cost'])."#din\">Пополнить баланс</a>");
        }
        return $ret;
    }

    /** заношу оплату в базу
     * @param integer $user
     * @param float $summ
     * @param string $deliver
     * @param string $mes
     * @param bool|integer $status = 9, если это счет
     * @param bool $time
     * @return int
     * session и $_user недоступны !!!
     */
    static function Depositing($user, $summ, $deliver='', $mes='', $status=false, $time=false){
        if(!headers_sent())header('Last-Modified:');
        $_SESSION['Last-Modified']=time();
        DB::sql("INSERT INTO ".db_prefix."payment ( user, summ, `time`, deliver, mes".($status===false?'':", status").")
		VALUES (".intval($user).", '".str_replace(',','.',$summ)."', '".date("Y-m-d H:i:s",($time===false?time():strtotime($time)))."', '".DB::escape($deliver)."', '".DB::escape($mes)."'".($status===false?'':", ".intval($status)).")");
        $inv_n=DB::id();
        if(DB::affected_rows()<1)PaymentLog("Платеж не сохранен, свяжитесь с поддержкой!",1,1);
        else PaymentLog("Платеж ".($status===9?' подготовлен':'выполнен'),0,1);
        file_put_contents( $_SERVER['DOCUMENT_ROOT'] . '/log/payment_log.txt', "\r\n".DB::$query."\r\n" , FILE_APPEND);
        self::_SetVar($user,'balans_mail_send',0); // после пополнения сбрасываю признак уведомления при низком балансе
        if($user==1 && $status===false)SendAdminMail('Поступила оплата', 'Поступила оплата '.$summ.'руб. '.$deliver.", ".$mes );
        return $inv_n;
    }


    /**
     * Возвращает false, если это не владелец указанного домена
     * @param string $domain если не передан, то $_SERVER['HTTP_REFERER']
     * @return bool
     */
    /*static function is_Domain($domain = '')
    {
        if (!$domain) {
            if (!empty($_SERVER['HTTP_REFERER'])) $domain = $_SERVER['HTTP_REFERER'];
            else {$domain=(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$GLOBALS['http'].'://htmlweb.ru');} //die('Не определен HTTP_REFERER !');
        }
        $domain = Domain::BaseDomain($domain);
        if ($data=DB::Select('domain','domain="' . $domain . '" ORDER BY confirm DESC')){
            if( $data['confirm']<date("Y-m-d", strtotime("-3 year")) ) die('Домен '.$_SERVER['HTTP_REFERER'].' не подтвержден!');
            if( $data['ip'] != ip2long2(strtok($GLOBALS['ip'],',')) ) die('Неверный IP адрес запроса: '.long2ip2($data['ip']) . ' != ' . strtok($GLOBALS['ip'],',') .'!');
            return $data['user'];
        }
        return false;
    }*/

