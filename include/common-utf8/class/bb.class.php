<?php

/**
 * Brand class
 */
class BB
{
    static public $bb_arr = array(
        ":)" => "smile",
        ":-)" => "smile",
        ":(" => "sad",
        ";)" => "wink",
        ";-)" => "wink",
        "O_O" => "shok",
        ":P" => "tongue",
        "*friends*" => "friends",
        "*KISSED*" => "air_kiss",
        "%)" => "crazy",
        "*boast*" => "boast",
        ":D" => "biggrin",

        "*JOKINGLY*" => "tease",
        ":*" => "kiss",
        ":-*" => "kiss",
        ":[" => "blush2",
        ":'-(" => "cray",
        ":'(" => "cray",
        "O:)" => "angel",
        ":')" => "nea",
        ":\\" => "nea",
        "=O" => "swoon2",
        "8)" => "dirol",
        "[:}" => "dance",
        "*TIRED*" => "boredom",

        ":!" => "bad",
        "*STOP*" => "stop",
        "}:-&gt;" => "diablo",
        "*THUMBS UP*" => "good",
        "*DRINK*" => "drinks",
        "*IN LOVE*" => "heart",
        ":-#" => "its_a_secret",
        "@=" => "bomb",
        "@}-:--" => "give_rose",
        ":-$" => "wacko",

        ":))" => "lol",
        "lol" => "lol",
        "rofl" => "rofl",
        "YAHOO!" => "yahoo",
        "=]" => "pardon",
        "*pardon*" => "pardon",
        "pardon*" => "pardon",
        ":db:" => "pleasantry",
        ":happy:" => "i-m_so_happy",
        "*hi*" => "hi",
        "*bye*" => "bye",
        "*thanks*" => "thanks",
        "*curtsy*" => "curtsy",

        ">:O" => "aggressive",
        "*yes*" => "yes",
        "*how_lovely*" => "how_lovely",
        "*mmm_yeah*" => "mmm_yeah",
        "*I'm_thinking*" => "im_thinking",
        "*SECRET*" => "secret",
        "*understand*" => "understand",
        "*warning_you!*" => "warning_you",
        "*I'm_pointing_at*" => "im_pointing_at",
        "*come_on!*" => "come_on",

        "*gibe*" => "gibe",
        "*beast_go_to_Babruisk!*" => "babruysk",
        "*farewell*" => "farewell",
        "*focus*" => "focus",
        "*shout*" => "shout",
        "*fig*" => "fig",
        "*pass_the_buck*" => "pass_the_buck",
        "*standing_in_beauty*" => "standing_in_beauty",
        "*I'm_whistling*" => "im_whistling",
        "*I'm_seeking*" => "im_seeking",

        "*punishment*" => "punishment",
        "*threat*" => "threat",
        "*suicide*" => "suicide",
        "*lazy*" => "lazy",
        "*panic*" => "panic",
        "*fever*" => "fever",
        "*coldly*" => "coldly",
        "*сoldly*" => "coldly",
        "*swoon*" => "swoon",
        "*sore*" => "sore",
        "*hysterics*" => "hysterics",

        "*coquette*" => "coquette",
        "*I'm_giving_you_my_heart*" => "im_giving_you_my_heart",
        "*touch*" => "touch",
        "*victory*" => "victory",
        "*yess*" => "yess",
        "*yess!*" => "yess",
        "*yow*" => "yow",
        "*beach*" => "beach",
        "*dance*" => "dance1",
        "*king*" => "king",
        "*big_boss*" => "big_boss",

        "*clever*" => "clever",
        "*hard_work*" => "hard_work",
        "*mimino*" => "mimino",
        "*pilot*" => "pilot",
        "*vampire*" => "vampire",
        "*she-devil*" => "she-devil",
        "*slowpoke*" => "slowpoke",
        "*american*" => "american",
        "*russian*" => "russian",
        "*gloat*" => "gloat",


        "*disgusting*" => "disgusting",
        "*superstition*	" => "superstition",
        "*wallbash*" => "wallbash",
    );

    static public $bb_emoji = array(
        ":'("       => "\xF0\x9F\x98\xA2",
        ":)"        => "\xF0\x9F\x98\x80",
        ":-)"       => "\xF0\x9F\x98\x80",
        ";-]"       => "\xF0\x9F\x98\x8F",
        ":("        => "\xF0\x9F\x98\x9E",
        ":-("       => "\xF0\x9F\x98\x9E",
        "O:)"       => "\xF0\x9F\x98\x87",
        ";-P"       => "\xF0\x9F\x98\x9C",
        ";P"        => "\xF0\x9F\x98\x9C",
        ":-P"       => "\xF0\x9F\x98\x9B",
        ":P"        => "\xF0\x9F\x98\x9B",
        ";)"        => "\xF0\x9F\x98\x89",
        ";-)"       => "\xF0\x9F\x98\x89",
        '*KISSED*'  => "\xF0\x9F\x98\x98",
        ':*'        => "\xF0\x9F\x98\x98",
        ':-*'       => "\xF0\x9F\x98\x98",
        "@="        => "\xF0\x9F\x92\xA3",
        "*dance*"   => "\xF0\x9F\x92\x83",
        "[:}"       => "\xF0\x9F\x92\x83",
        "B-)"       => "\xF0\x9F\x98\x8E",
        "*IN LOVE*" => "\xE2\x9D\xA4"
    );


    static public $s = array("\\",  '*',  '+',  '.',  '[',  ']',  '{',  '}',  '(',  ':',  ')');
    static public $p = array("\\\\",'\*', '\+', '\.', '\[', '\]', '\{', '\}', '\(', '\:', '\)');

    // голосовалка
    static public function vote2html($var, $vote_link = '', $domain = '')
    {

        if(!preg_match('/\[vote(.*?)\](.*?)\[\/vote\]/uis', $var, $val))return $var;

        $domain = ((!empty($domain) && strpos($domain, '://')===false) ? 'http://' : '' ) . $domain;

        $votes = str_replace('[*]','<li>', $val[2]);

        return str_replace($val[0], ($val[1]?substr($val[1],1):'Опрос') . '<a href="' . $domain . $vote_link . '"><ul>' . $votes . '</ul></a>', $var);
    }

    static public function bb2html($var, $_params = '')
    {

        if (is_array($_params)) {
            $domain = get_key($_params, 'domain', '');
            $emoji = intval(get_key($_params, 'emoji', 0));
        } else {
            $emoji = 0;
            $domain = $_params;
        }

        $domain = ((!empty($domain) && strpos($domain, '://')===false) ? 'http://' : '' ) . $domain;

        /* todo
        [img=300x500]адрес[/img]	Картинка с размерами	<img src="адрес" style="width: 300px; height: 500px">
        [imgleft]адрес[/imgleft]	Картинка влево	<img src="адрес" style="float: left; margin: 0 10px 0 0;">
        [imgright]адрес[/imgright]	Картинка вправо	<img src="адрес" style="float: right; margin: 0 0 0 10px;">
        [imgcenter]адрес[/imgcenter]	Картинка по центру	<div style="text-align: center"><img src="адрес"></div>
        */

        $se = '/\[quote\=(.*?)\](.*?)\[\/quote\]/uis';
        $re = '<table class="n-table"><tr><td class="n-td-cite"><cite><b>$1 писал(а):</b><br>$2</cite></td></tr></table>';
        while(preg_match($se, $var)) {
            $var = preg_replace($se,$re,$var);
        }

        if ($emoji) {

            $var = preg_replace_callback('|\[img\](.*)\[\/img\]|Uuis', function ($arr) {
                return '';
            }, $var);

            /*if (intval($emoji)==2) {

                $var = preg_replace_callback('|\[img\](.*)\[\/img\]|Uuis', function ($arr) {
                    return '';
                }, $var);
            } else {
                $var = preg_replace_callback('|\[img\](/.*?)\[\/img\]|Uuis', function ($arr) {
                    $imgs = Files::proxyImg($arr[1]);
                    return '<a href="' . $imgs[0] . '"><img src="' . $imgs[100] . '" ' . ($imgs[100] === $imgs[0] ? ' onload="fb_resize(this)"' : '') . '></img></a>';
                }, $var);
            }*/
        }

        $search = array(

            '/\[sp=(.*?)\]/ui'                     => $emoji==1 ? ' СП№$1 ('.$domain.'/sp/$1/) ' : '<a target="_blank" href="'.$domain.'/sp/$1/">СП№$1</a>',
            '/\[(nik|user|login)=(.*?)\]/ui'       => $emoji==1 ? ' '.$domain.'/users/$2/ ' : '<a target="_blank" href="'.$domain.'/users/$2/">$2</a>',
            '/\[b\](.*?)\[\/b\]/uis'                => '<b>$1</b>',
            '/\[s\](.*?)\[\/s\]/uis'                => '<s>$1</s>',
            '/\[i\](.*?)\[\/i\]/uis'                => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/uis'                => '<u>$1</u>',
            '/\[color\=(.*?)\](.*?)\[\/color\]/uis' => '<span style="color:$1">$2</span>',
            '/\[size\=(.*?)%*\](.*?)\[\/size\]/uis'   => '<span style="font-size:$1%">$2</span>',

            '/\[url\=(.*?)\]\[img\](.*?)\[\/img\]\[\/url\]/ui'             => $emoji
                                                                                ? ( $emoji==2 ? '' : ' $1 ')
                                                                                : '<a href="$1" target="_blank"><img onload="fb_resize(this)" src="$2"></a>',

            '/\[url\=([^\/].*?)\](.*?)\[\/url\]/ui'                             => $emoji==1 ? ' '. '$1 ' : '<a target="_blank" href="$1">$2</a>',
            '/\[url\=(\/.*?)\](.*?)\[\/url\]/ui'                           => $emoji==1 ? ' '.$domain . '$1 ' : '<a target="_blank" href="' . $domain . '$1">$2</a>',

            '/\[img\](.*?)\[\/img\]/Uui'                                   => $domain ? '<img src="' . $domain . '$1"/>' : '<div data-emoji="'.$emoji.'" style="overflow:hidden;width:100px;height:100px;cursor:pointer" onclick="return !window.open(\'$1\')"><img src="$1" onload="fb_resize(this)" /></div>',
            '/\[img(left|right|center)=(.*?)x(.*?)\](.*?)\[\/img.*?\]/ui'  => $domain ? '<img style="float:$1" width="$2" height="$3" src="' . $domain . '$4"/>' : '<div style="float:$1;text-align:$1;overflow:hidden;width:$2px;height:$3px;cursor:pointer" onclick="return !window.open(\'$4\')"><img src="$4" onload="fb_resize(this)" /></div>',
            '/\[img(left|right|center)\](.*?)\[\/img.*?\]/ui'              => $domain ? '<img style="float:$1" src="' . $domain . '$2"/>' : '<div style="float:$1;text-align:$1;overflow:hidden;width:100px;height:100px;cursor:pointer" onclick="return !window.open(\'$2\')"><img src="$2" onload="fb_resize(this)" /></div>',
            '/\[img=(.*?)x(.*?)\](.*?)\[\/img\]/Uui'                       => $domain ? '<img width="$1" height="$2" src="' . $domain . '$3"/>' : '<div style="overflow:hidden;width:$1px;height:$2px;cursor:pointer" onclick="return !window.open(\'$3\')"><img width="$1" height="$2" src="$3" /></div>',
            '/\[quote\](.*?)\[\/quote\]/uis'                                => '<table class="n-table"><tr><td class="n-td-cite"><cite>$1</cite></td></tr></table>',
            '/\[url\](.*?)\[\/url\]/ui'                                    => $emoji==1 ? ' '.$domain.'$1 ' : '<a target="_blank" href="'.$domain.'$1">ссылка</a>',


            //'/([\n\s])((http|https):\/\/([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))(?=[\n\s])/ui' =>

            '/([\n\s])((http|https):\/\/([[:alpha:]0-9][[:alpha:]0-9_\.\-]*\.[[:alpha:]]{2,6})([[:alpha:]0-9;\.,:~|\[\]\+\/\*\-\?&%#=_]*))(?=[<\n\s])/ui' =>
                $emoji==1 ? ' $2 ' : '$1<a target="_blank" href="$2">ссылка</a>$6',

            //'/([\n\s])(www\.([a-z][a-z0-9_\.\-]*\.[a-z]{2,6})([a-zA-Z0-9;:~|\[\]\.\+\/\*\-\?&%#=_]*))(?=[\n\s])/ui' =>

            '/([\n\s])(www\.([[:alpha:]0-9][[:alpha:]0-9_\.\-]*\.[[:alpha:]]{2,6})([[:alpha:]0-9;\.,:~|\[\]\+\/\*\-\?&%#=_]*))(?=[\n\s])/ui' =>
                $emoji==1 ? ' $2 ' : '$1<a href="http://$2">ссылка</a>$5',

            '/\sСП[№N]*([0-9]+)(?=[^\d])(?!.*<\/a>)/ui' => $emoji==1 ? ' СП№$1 ('.$domain.'/sp/$1/) ' : ' <a target="_blank" href="'.$domain.'/sp/$1/">СП№$1</a>',

            '#href="(?!(https?://|/))#ui' => 'href="http://'
        );

        $replace = array_values($search);
        $search = array_keys($search);

        if ($emoji==1) {
            foreach(self::$bb_emoji as $key => $value) {
                $search[] = '/([\s\r\n\>])' . str_replace(self::$s,self::$p,$key) . '(?=[\s\r\n\<])/uis';
                $replace[] = '$1'. $value . '$2';
            }
        } else {
            foreach(self::$bb_arr as $key => $value) {
                $search[] = '/([\s\r\n\>])' . str_replace(self::$s,self::$p,$key) . '(?=[\s\r\n\<])/uis';
                $replace[] = '$1<img src="' . $domain . '/fb/images/smileys/'.$value.'.gif" alt="'.str_replace('"','\"',$key).'" />$2';
            }
        }
        $var=preg_replace_callback( // обработка button
            '/(\[\/button\])?(.*?)\[button=[\'\"]?(.*?)[\'\"]?\](.*?)\[\/button\]/s',
            function($matches){
                if(mb_strlen($matches[4])<20){ // если внутри button текст <20 символов, то текст перед button запизиваем в левый столбец таблицы, а кнопку в правый столбец
                    $str='<table class="n-table">'.
                        '<tr>'.
                            '<td class="n-td-text">'.
                                $matches[2].
                            '</td>'.
                            '<td class="n-td-btn">'.
                                '<a class="n-a-btn" href="'.$matches[3].'">'.$matches[4].'</a>'.
                            '</td>'.
                        '</tr>'.
                    '</table>';
                } else { // если внутри button >=20 символов, то выводим кнопку по центру, а текст перед ней выводим без изменений
                    $str=$matches[2].
                        '<table class="n-table-birthday">'.
                            '<tr>'.
                                '<td class="n-td-btn350">'.
                                    '<a class="n-a-btn350" href="'.$matches[3].'">'.$matches[4].'</a>'.
                                '</td>'.
                            '</tr>'.
                        '</table>';
                }
                return $str;
            },
            $var
        );
        $var=preg_replace_callback( //ссылки на видео
            '/\[video\](.*?)\[\/video\]/ui',
            function($matches){
                if(empty($matches[1])){
                    $str='(Ошибка добавления видео на страницу: для добавления на эту страницу видео вставьте ссылку на видео (без пробелов и символов &lt;&gt;) между [video] и [/video]) ';
                } elseif(strpos($matches[1],'youtube.com')!==FALSE){ //ссылка на YouTube
                    if(strpos($matches[1],'watch')){
                        preg_match('/watch\?v=([\w-]*)/',$matches[1],$arr);
                        $str='<div class="video-responsive"><iframe src="https://youtube.com/embed/'.$arr[1].'"></iframe></div>';
                    } elseif(strpos($matches[1],'/embed/')){
                        preg_match('/embed\/([\w-]*)/',$matches[1],$arr);
                        $str='<div class="video-responsive"><iframe src="https://youtube.com/embed/'.$arr[1].'"></iframe></div>';
                    } else {
                        $str='<a target="_blank" href="'.$matches[1].'">ссылка на видео</a> (Ошибка добавления видео на страницу: для добавления на эту страницу видео с YouTube вставьте ссылку на видео между [video] и [/video]) ';
                    }
                } elseif (strpos($matches[1], 'youtu.be')!==FALSE) { //ссылка на YouTube
                    preg_match('/youtu\.be\/([\w]*)/', $matches[1], $arr);
                    $str = '<div class="video-responsive"><iframe src="https://youtube.com/embed/' . $arr[1] . '"></iframe></div>';
                } elseif(strpos($matches[1],'vk.com')!==FALSE) { //ссылка на ВК
                    if (strpos($matches[1], 'video_ext.php')) {
                        preg_match('/\?([\w&=;-]*)/', $matches[1], $arr);
                        $str = '<div class="video-responsive"><iframe src="https://vk.com/video_ext.php?' . $arr[1] . '"></iframe></div>';
                    } else {
                        $str = '<a  target="_blank" href="' . $matches[1] . '">ссылка на видео</a> (Ошибка добавления видео на страницу: для добавления на эту страницу видео с ВКонтакте откройте страницу с видео, нажмите <b>Поделиться</b>, затем нажмите <b>Экспортировать</b> и скопируйте из поля <b>Код для вставки</b> ссылку <i>src="</i><b>ссылка</b><i>"</i> )';
                    }
                } elseif(strpos($matches[1],'rutube.ru')!==FALSE) { //ссылка на RuTube
                    if(strpos($matches[1],'/play/embed/')){
                        preg_match('/embed\/([\w]*)/', $matches[1], $arr);
                        $str = '<div class="video-responsive"><iframe src="https://rutube.ru/play/embed/' . $arr[1] . '"></iframe></div>';
                    } else {
                        $str = '<a  target="_blank" href="' . $matches[1] . '">ссылка на видео</a> (Ошибка добавления видео на страницу: для добавления на эту страницу видео с RuTube откройте страницу с видео, нажмите <b>Поделиться</b>, затем нажмите <b>Код для вставки</b> и скопируйте из поля <b>Код вставки плеера</b> ссылку <i>src="</i><b>ссылка</b><i>"</i> )';
                    }
                } else {
                    //$str = '<a target="_blank" href="' . $matches[1] . '">ссылка на видео</a>';
                    $str = '<div class="video_responsive"><iframe src="' . $matches[1] . '"></iframe></div>';
                }
                return $str;
            },
            $var
        );

        $var=trim(preg_replace($search, $replace, ' '.$var.' '));

        if(substr($var,-1,1)=='\\')$var.=' '; // защита от инъекций

        if ($emoji==1) {
            $var = strip_tags($var);
            //$var = preg_replace('/([\n\r]){3,}/', '$1', $var);
        }
        $var = $var;
        return $var;
    }

    static public function html2bb($var){
        $search = array(
            '/<a href\=\"(.*?)\">(.*?)<\/a>/uis',
            '/<br.*?>/uis',
            '/<div .*?><img src=\"(.*?)\".*?><\/div>/uis',
            '/<div .*?float:(.*?);.*?><img src=\"(.*?)\".*?><\/div>/uis',
            '<img src\=\"\/fb\/images\/smileys\/.*?\" alt\=\"(.*?)\">/uis',
            '/<img src=\"(.*?)\".*?>/uis',
            '/<img .*?float:(.*?)\" src=\"(.*?)\".*?>/uis',
            '/<(b|s|u)>/uis',
            '/<em>/uis',
            '/<\/em>/uis',
            '/<div class=\"video-responsive\"><iframe src=\"(.*?)\"><\/iframe><\/div>/uis',
            '/<a target=\"_blank\" href=\"(.*?)\">ссылка на видео<\/a>/uis'
        );
        $replace = array(
            '[url=$1]$2[/url]',
            '',
            '[img]$1[/img]',
            '[img$1]$2[/img]',
            ' $1 ',
            '[img]$1[/img]',
            '[img$1]$2[/img]',
            '[$1]',
            '[i]',
            '[/i]',
            '[video]&1[/video]',
            '[video]&1[/video]'
        );

        $var=preg_replace($search, $replace, $var);
        if(substr($var,-1,1)=='\\')$var.=' '; // защита от инъекций
        return $var;
    }

    /**
     * возвращает строку для вывода кнопок BBCode
     * @param $_obj
     * @param bool $as_js
     * @return string
     */
    static function write($_obj, $as_js = false)
    {
        return sprintf(
            "<div class='right' id='%s_bb'><script type='text/javascript'>$(document).ready(function () { addBB( $('#%s_bb'), '%s'); });<" . ($as_js ? "\"+\"" : '' ) . "/script></div>",
            $_obj,$_obj,$_obj
        );
    }

    static function proxyImg($str)
    {
        return preg_replace_callback(
            '|\[img(.*)\](.*)\[/img\]|iU',
            function($arr) {
                if (empty ($arr[1]) && empty ($arr[2])){
                    return "[img][/img]";
                }

                return "[img".( mb_substr($arr[1],0,5)=='=http' ? '' : $arr[1] )."]" . Files::proxyImg($arr[2], 0) . "[/img]";
            }
            , $str
        );
    }
}