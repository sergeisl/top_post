<?php

function bbcode($subject)
{
    $subject = preg_replace('/\[(\/?)(b|i|u|s|h1|ul|li|p)\]/', '<$1$2>', $subject);
    $subject = preg_replace('/\[img\](.*?)\[\/img\]/', '<img src="$1" style="max-width: 100%;" />', $subject);
    $subject = preg_replace('/\[video\](.*?)\[\/video\]/', '<iframe src="$1" width="560" height="315" frameborder="0" allowfullscreen></iframe>', $subject);
    $subject = preg_replace('/\[url\](.*?)\[\/url\]/', '<a class="a" href="$1" onclick="window.open(this.href); return false;">$1</a>', $subject);
    $subject = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/', '<a class="a" href="$1" onclick="window.open(this.href); return false;">$2</a>', $subject);
    $subject = preg_replace('/\[color=(.*?)\]/', '<span style="color:$1;">', $subject);
    $subject = preg_replace('/\[\/color\]/', '</span>', $subject);
    $subject = preg_replace('/\[left\]/', '<div style="text-align: left;">', $subject);
    $subject = preg_replace('/\[\/left\]/', '</div>', $subject);
    $subject = preg_replace('/\[center\]/', '<div style="text-align: center;">', $subject);
    $subject = preg_replace('/\[\/center\]/', '</div>', $subject);
    $subject = preg_replace('/\[right\]/', '<div style="text-align: right;">', $subject);
    $subject = preg_replace('/\[\/right\]/', '</div>', $subject);
    $subject = preg_replace('/\[justify\]/', '<div style="text-align: justify;">', $subject);
    $subject = preg_replace('/\[\/justify\]/', '</div>', $subject);
    $subject = preg_replace('/\[size=(.*?)\]/', '<font size="$1";>', $subject);
    $subject = preg_replace('/\[\/size\]/', '</font>', $subject);
    $subject = preg_replace('/\[(\/?)(b|i|u|s)\s*\]/', "<$1$2>", $subject);
    $subject = preg_replace('/\[code\]/', '<pre><code>', $subject);
    $subject = preg_replace('/\[\/code\]/', '</code></pre>', $subject);
    $subject = preg_replace('/\[(\/?)quote\]/', "<$1blockquote>", $subject);
    $subject = preg_replace('/\[(\/?)quote(\s*=\s*([\'"]?)([^\'"]+)\3\s*)?\]/', "<$1blockquote>Цитата $4:<br>", $subject);
    $subject = preg_replace('/\[url\](?:http:\/\/)?([a-z0-9-.]+\.\w{2,4})\[\/url\]/', "<a href=\"http://$1\">$1</a>", $subject);
    $subject = preg_replace('/\[url\s?=\s?([\'"]?)(?:http:\/\/)?([a-z0-9-.]+\.\w{2,4})\1\](.*?)\[\/url\]/', "<a href=\"http://$2\">$3</a>", $subject);
    $subject = preg_replace('/\[img\s*\]([^\]\[]+)\[\/img\]/', "<img src='$1'/>", $subject);
    $subject = preg_replace('/\[img\s*=\s*([\'"]?)([^\'"\]]+)\1\]/', "<img src='$2'/>", $subject);
    return nl2br($subject);
}