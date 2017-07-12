<?php
class Sms
{
    /** Отправка СМС пользователю. Если получатель не текущий, то отправлю только если у пользователя не запрещено получение СМС
     * @param int $user_id - получатель
     * @param $sms_text - текст СМС в UTF-8
     * @return bool
     */
    static function Send($user_id=0, $sms_text){ // Отправляет sms пользователю: кому, текст, за чей счет, от имени кого
        if(!defined('API_KEY_HtmlWeb'))return false;
        if(!$user_id){$user_id=User::id(); if(!$user_id)return false;}
        $user=new User($user_id);
        if($user_id!=User::id() && $user->sms)return false;
/*        $param=array(
            'api_key'=>'2VJicLclSPktVzeXjfIovDxmhCQPbTnsL',
            'from' => SMSPILOT_FROM,
            'to' => $to,
            'text' => @iconv('windows-1251','utf-8//IGNORE',$text)
        );
        //var_dump($param);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_URL, 'http://htmlweb.ru/sendsms/api.php?send_sms&json&service=1');
        $res = curl_exec($ch);
        $result = json_decode($res,!0);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if(empty($result['sms'])){
            echo "<br>res:"; var_dump($res);
            echo "<br>result:";var_dump($result);
            echo "<br>info:";var_dump($info);
            return false;
        }else return (array)$result['sms']; // "from":"From1","time":"2014-02-06 19:13:25","message":"Test SMS1","id":123,"phone":"79112224433","cost":0.25},*/

        if(Get::DEBUG()){Out::message("SMS:".$sms_text); return true;}

        list($headers,$body,$info)=ReadUrl::ReadWithHeader('http://htmlweb.ru/sendsms/api.php',
            ['api_key'=>API_KEY_HtmlWeb,
            'send_sms'=>1,
            'json'=>1,
            'phone' => $user->tel,
            'text' => $sms_text,
            'charset'=>'utf8'
            ]
    );
        return true;
    }

    static function Lists(){

    }
}
