"use strict";
// todo querySelector и querySelectorAll
/*
 $(function() {
    // Do something with DOM-Ready
 });
$('.FlatPlans-itemMenu:nth-of-type('+(i+1)+') .FlatPlans-link').addClass('FlatPlans-link--active');
$('.FlatRooms-menu li a').removeClass('FlatRooms-link--active');
$('li.FlatPlans-itemMenu').show();
$('li.FlatPlans-itemMenu').hide();
$('li.FlatPlans-itemMenu[data-rooms="'+i+'"]').show();
$('.FlatPlans-itemMenu[data-rooms="'+i+'"] .FlatPlans-link')[0].classList.add('FlatPlans-link--active');
el=$('.my-selector', el); // поиск относительно другого элемента
$('.my-selector').forEach(function(element) { element.remove(); }); // удаление элементов
for(let node of $('.xyz')) { node.insertAdjacentHTML('beforeend', '<xyz />'); } // всем элементам добавить в конец
for(let el of $('.my-selector')) {
 el.addEventListener('click', function ({ target }) {
    if (this.contains(target.closest('.delegated-selector'))) {
        alert('yep!');
    }
 });
 }
 div = $('<div><span class="yeah"></span></div>');
 cells = $('<td>foo</td><td>bar</td>', 'tr');
 [0].animate({ opacity: [0.5, 1],
    transform: ['scale(0.5)', 'scale(1)'],
 }, {
     direction: 'alternate',
     duration: 500,
     iterations: Infinity,
 });

*/
var $ = function (d, e, c, f, g) {
    c = function (a, b) {
        return new f(a, b)
    };
    f = function (a, b) {
        e.push.apply(this, a ? a.nodeType || a == window ? [a] :
            "" + a === a ?
                /*a.charAt(0)=='#' ? (b && c(b)[0] || d).getElementById( a.slice(1) ) :*/
                    /</.test(a) ?
                        ((g = d.createElement(b || "q")).innerHTML = a, g.children) :
                        (b && c(b)[0] || d).querySelectorAll(a) :
                /f/.test(typeof a) ?
                    /c/.test(d.readyState) ?
                        a() :
                        d.addEventListener("DOMContentLoaded", a) :
                    a :
            e)
    };
    c.fn = f.prototype = e;
    c.fn.addClass = function( className ) {
        this.forEach( function( item ) {
            var classList = item.classList;
            classList.add.apply( classList, className.split( /\s/ ) );
        });
        return this;
    };
    c.fn.removeClass = function( className ) {
        this.forEach( function( item ) {
            var classList = item.classList;
            classList.remove.apply( classList, className.split( /\s/ ) );
        });
        return this;
    };
    c.fn.show = function( className ) {
        this.forEach( function( item ) {
            var classList = item.classList.remove('hide');
        });
        return this;
    };
    c.fn.hide = function( className ) {
        this.forEach( function( item ) {
            var classList = item.classList.add('hide');
        });
        return this;
    };
    return c
}(document, []);
// https://habrahabr.ru/post/273751/
// https://github.com/finom/balalaika

var f_reload=0; // после закрытия модального окна перегрузить основное
function getObj(o){return document.getElementById(o);}

function getElementsByClass(searchClass,node,tag){
    var classElements=[];
    if ( node == null ) node=document;
    else if(typeof(node)!="object")node=document.getElementById(node); if(!node)return [];
    if ( tag == null ) tag='*';
    var els=node.getElementsByTagName(tag);
    var elsLen=els.length;
    for (var i=0, j=0; i < elsLen; i++) {
        var pattern=new RegExp("(^|\\s)"+searchClass.replace('-','\-')+"(\\s|$)"); // при выносе наружу теряет каждый второй элемент
        if ( pattern.test(els[i].className) ) {
            classElements[j]=els[i];
            j++;
        }
    }
    return classElements;
}

function getSubmit(node) {
    var els=node.getElementsByTagName('INPUT');
    var elsLen=els.length;
    for(var i=0; i < elsLen; i++){if(els[i].type=='submit')return els[i];}
    return null;
}

function getText(o)
{
    if(typeof(o)!="object")o=document.getElementById(o);
    if(!o)return '';
    if(o.tagName=='SELECT') return (o.selectedIndex>=0?o.options[o.selectedIndex].text:'');
    if (o.nodeType == 3 || o.nodeType == 4) { return o.data; }
    var i; var returnValue = [];
    for (i = 0; i < o.childNodes.length; i++) { if(o.childNodes[i].tagName=='BR') returnValue.push("\r\n"); else returnValue.push(getText(o.childNodes[i])); }
    return returnValue.join('');
    /*
     if(to.innerText)return to.innerText;
     if(to.nodeValue)return to.nodeValue;
     if(to.outerText)return to.outerText;
     if(!document.all&&to.textContent)return to.textContent;
     if(to.text)return to.text;
     if(to.innerHTML){t=to.innerHTML.replace(/<br[^>]*>/gm,"\r\n"); t=t.replace(/<[^>]*>/gm,""); t=t.replace(/&lt;/gm,"<").replace(/&gt;/gm,">").replace(/&nbsp;/gm," "); return t;}
     */
}

function getValue(o,def){
    if(typeof(o)!="object")o=document.getElementById(o);
    if(def==undefined)def=0;
    if(!o)return def;
    if(o.length&&!o.tagName){
        var rl = o.length;
        if(rl == undefined) return (o.checked ? o.value : def);
        for(var i = 0; i < rl; i++) if(o[i].checked) return o[i].value;
        return def;
    }else if(o.tagName=='SELECT') return (o.selectedIndex>=0?o.options[o.selectedIndex].value:def);
    else if((o.tagName=='INPUT')&& ((o.type=='checkbox')||(o.type=='radio')))return (o.checked?o.value:def);
    else return o.value; // INPUT || TEXTAREA
}

function isVisible(o,glob){
    if(typeof(o)!="object")o=document.getElementById(o);
    var c;
    do{	if(o.classList.contains('hide') )return false;
        if(o.style){
        //pattern=new RegExp("(^|\\s)hide(\\s|$)");
        //if( pattern.test(o.className) )return false;
        c=window.getComputedStyle(o, null).display;
        if((c.indexOf("block")==-1)&&(c.indexOf("inline")==-1)&&(c.indexOf("table")==-1)&&(c!=""))return false;
    }
        o=o.parentNode;
    }while(glob&&o);
    return true;
}

function strip_tags(s){
    return s.replace(/[\r\n\t]/gm," ").replace(/<br[^>]*>/igm,"\n").replace(/<tr[^>]*>/igm,"\n").replace(/<h[^>]*>/igm,"\n").replace(/<[^>]*>/gm,"").replace(/&lt;/gm,"<").replace(/&gt;/igm,">").replace(/&nbsp;/igm," ").replace(/  +/gm," ").replace(/\n[ ]+\n/gm,"\n");
}
/**
 * @return {boolean}
 */
function IsMail(m, EnableEmpty){
     var mail=(m.value?m.value:m);
    if(EnableEmpty && (mail=="") ) {m.setCustomValidity(""); return true;}
    if((mail=="")||(mail.indexOf(".") == -1)||(mail.indexOf(",")>=0)||(mail.indexOf(";")>=0)) {m.setCustomValidity("Неверный формат E-mail"); return false;}
    var dog = mail.indexOf("@");
    if (dog == -1) {m.setCustomValidity("Неверный формат E-mail"); return false;}
    if ((dog < 1) || (dog > mail.length - 5)) {m.setCustomValidity("Неверный формат E-mail"); return false;}
    if ((mail.charAt(dog - 1) == '.') || (mail.charAt(dog + 1) == '.')) {m.setCustomValidity("Неверный формат E-mail"); return false;}
    // /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/
    m.setCustomValidity("");
    return true;
}

function WaitWind(){
    if(!getObj('wait')){
        var o=document.createElement('div');
        o.setAttribute('id','wait');
        addEvent(o, 'click', function(){show(getObj("wait").childNodes[0])});
        o.setAttribute('class','hide');
        document.body.appendChild(o);
        o.innerHTML=
            '<div class="hide">'+
            '<div>'+
            '<h3>Идет обработка Вашего запроса...</h3>'+
            '<img src="/images/loading.gif">'+
            '</div>'+
            '</div>';
    }
    //hide(getObj("wait").childNodes[0]);
    show('wait');
}
/**
 * @param obj
 * @param url
 * @param defMessage
 * @param post
 * @param callback
 * @param header
 * @returns {boolean}
 */
function ajaxLoad(obj,url,defMessage,post,callback,header){
    var ajaxObj;
    if(typeof(obj)!="object"&&obj)obj=document.getElementById(obj);
    if(defMessage&&obj)updateObj(obj,defMessage+' <img src="/images/loading.gif">');

    if(window.XMLHttpRequest){
        ajaxObj = new XMLHttpRequest();
    } else if(window.ActiveXObject){
        ajaxObj = new ActiveXObject("Microsoft.XMLHTTP");
    } else {
        return false;
    }
    ajaxObj.open((post?'POST':'GET'), url);
    if(post&&ajaxObj.setRequestHeader){
        if(post=='chat'){ajaxObj.chat=true;post='';}
        else ajaxObj.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8;");
    }
    ajaxObj.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    ajaxObj.onreadystatechange = ajaxCallBack(obj,ajaxObj,(callback?callback:null));
    ajaxObj.timeout = 30000; // Set timeout to 30 seconds (30000 milliseconds)
    ajaxObj.ontimeout = function () { alert("От сервера не получен ответ! Проверьте соединение с интернетом и повторите запрос!");};
    WaitWind();
    ajaxObj.send(post);
    //if(url.indexOf('ajax')>=0)UpdateUrl(MainUrl=url.replace('&ajax','').replace('ajax&','').replace('?ajax','')); todo проблема восставновления url при закрытии окна
    return false;
}

var oldUrl=document.location;
var MainUrl='';
if(typeof window.addEventListener === "function"){
    setTimeout( function() {
        try{
            window.addEventListener("popstate", function(e) {
                MainUrl=e.location || document.location;
                if(oldUrl.pathname==MainUrl.pathname && oldUrl.hash.substring(1,1)!='/'){/*alert(oldUrl.pathname+'|'+MainUrl.pathname+'|'+oldUrl.hash);*/return;}
                LoadMainUrl(MainUrl.href);
            }, false);
        }catch(e){}
    }, 900 );
}

/**
 * @return {string}
 */
function ShortUrl(url){
    if(url.substring(0,1)=='?') {
        return document.location.pathname + url;
    }
 var d=document.location,e=d.protocol+'//'+d.hostname;
 var i=url.indexOf(e);
 if(i>-1)url=url.substring(i+e.length);
 if(url.substring(0,1)!='/'){console.error("Ошибка в url=",url,i,e);return '';}
 return url;
}

function LoadMainUrl(url,AddHistory){
    MainUrl=url;
    url=ShortUrl(url);
    var i=url.indexOf('#');
    if(i>0)url=url.substring(0,i)+(url.indexOf('?')>=0?'&':'?')+'ajax=1'+url.substring(i);
    else url=url+(url.indexOf('?')>=0?'&':'?')+'ajax=1';
    ajaxLoad('main',url,'Загрузка...');
    if(AddHistory)UpdateUrl(MainUrl);
}

function UpdateUrl(url){
    //history.pushState(null, null, MainUrl);
    console.log('UpdateUrl:',url);
    url=ShortUrl(url);
    if(history.pushState)history.pushState(null, null, url);
    else window.location.hash='#'+url;
}

function _GET() {
    var $_GET = {};
    var __GET = window.location.search.substring(1).split("&");
    for(var i=0; i<__GET.length; i++) {
        var getVar = __GET[i].split("=");
        $_GET[getVar[0]] = typeof(getVar[1])=="undefined" ? "" : getVar[1];
    }
    return $_GET;
}
function perpage(t,obj,api){
    var url=document.location.href; if(url.indexOf('#')>=0)url=url.substr(0,url.indexOf('#'));
    if(url.indexOf('?')>=0)url=url.replace(/[\&\?]perpage=(\w)*/gi,"");
    console.log('url=',url);
    if(api||getElementsByClass('layer','main','DIV')){    //if(getObj('layer0')){
        // если работаю с вкладками, то очистить все вкладки и загрузить в текущую
        //var l=getElementsByClass('layer',null,'div');
        //for(var i=0;i<l.length;i++)l[i].innerHTML='';
        //определяю текущую вкладку
        //l='layer'+getlayer();
        url = url + (url.indexOf('?') >= 0 ? '&' : '?') + "perpage=" + encodeURIComponent(t.options[t.selectedIndex].value);
        UpdateUrl(url+document.location.hash);
        //ajaxLoad(l,url+'&ajax=1');
        if(!obj)obj='main';
        if(api)api=api+(api.indexOf('?')>=0?'&':'?')+url.substr(url.indexOf('?')+1);
        else api=url+'&ajax=1';
        //console.log('загружаю ',api,' в ',obj);
        ajaxLoad(obj,api);
    }else
        LoadMainUrl(url+(url.indexOf('?')>=0?'&':'?')+"perpage="+encodeURIComponent(t.options[t.selectedIndex].value),true);
    return false;
}

//noinspection JSUnusedGlobalSymbols
function Order(name){ // используется в kdg_bar
    var s=_GET();
    //console.log(typeof(s['desc']), s['ord']==name);
    if(typeof(s['desc'])=='undefined'){
        if(s['ord']==name)s['desc']='';
    }else{ delete s['desc'];}
    s['ord']=name;
    var url='';
    for(var key in s)url+=(url?'&':'?')+key+ (s[key] ? '=' + s[key] : '');
    location.href=url;
    /*if(api||getElementsByClass('layer','main','DIV')){
     UpdateUrl(url+document.location.hash);
     if(!obj)obj='main';
     else api=url+'&ajax=1';
     ajaxLoad(obj,api);
     }else
     location.href=url;
     //LoadMainUrl(url,true);
     return false;*/
}

function Search(name){
    name=getValue(name);
    var s=document.location.search;
    var url=s.replace(/(^|&|\\?)q=(.*?)(?=&|$)/gi, "").replace(/(^|&|\\?)p=(.*?)(?=&|$)/gi, "").replace(/[\\&\\?]$/, '');
    url=(url?url+'&':'?')+'q='+name;
    location.href=url;
}

function getParam(name) {
    name=name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var r=new RegExp("[\\?&]" + name + "=([^&#]*)");
    r=r.exec(window.location.search);
    return (r == null ? "" : decodeURIComponent(r[1].replace(/\+/g, " ")));
}

function updateObj(obj, data, bold, blink){
    if(bold)data=data.bold();
    if(blink)data=data.blink();

    //console.log("1:",obj,data);
    var t='',text;
    if(typeof data=='string')data=data.replace(new RegExp("<script([^>]*)>([\\s\\S]*?)<\/script>", "igm"),
        function(str, p1, p2, offset, s){
            if(p1.indexOf('src=')>0){
                var re2=new RegExp("src=[\'\"](.*?)[\'\"]","i"); var te2=re2.exec(p1);
                if(te2!=null)
                    t=t+"\r\nLoadScript('"+te2[1]+"');";
            }else{ t=t+"\r\n"+p2.replace(/<!\-\-/g,"").replace(/\/\/\-\->/g,"");}
            return "";
        });

    var re1=new RegExp("<body\\b([^>]*)>([\\s\\S]*?)<\/body>","img");
    if((text=re1.exec(data))!=null){
        console.error("В ответе <body>!");
        data=text[2].replace(/<!\-\-/g,"").replace(/\/\/\-\->/g,"");
    }
    //console.log("t:",t);
    //console.log("2:",obj,t,data);
    if(typeof(obj)!="object"&&obj)obj=document.getElementById(obj);
    if(!obj){
        //console.log("4:",obj,t,data);
        if(data&&trim(data)!=''){fb_win(data,t);if(typeof _cs != 'undefined')_cs.d();}
        else if(t)ExecScript(t);
        return;}
    var o=obj;
    do{	if(o.style){
        if(!isVisible(o)){/*log(c.display);*/show(o);/*o.style.display=(o.tagName=="DIV"?"block":"inline");*/}
    }
        o=o.parentNode;
    }while(o);
    if(obj.id=='main'){
        re1=new RegExp("<title>([^<]+)</title>","gim"); text=re1.exec(data);
        if(text!=null){t=text[1]; document.title=t;
            data=data.replace(re1, "");
        }
        obj.innerHTML=data;
        if(t)ExecScript(t);

        //MainUrl='http://'+window.location.hostname+MainUrl;
        var el=document.getElementsByTagName('base'); if(el)el=el[0];
        if(!el){el=document.createElement("base");
            document.getElementsByTagName('head')[0].appendChild(el);}
        el.setAttribute('href', MainUrl);
        // обновляю счетчики
      /*
       c=new Image(); c.src="//counter.yadro.ru/hit?t42.6;r"+
         escape(oldUrl)+((typeof(screen)=="undefined")?"":
         ";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?
         screen.colorDepth:screen.pixelDepth))+";u"+escape(MainUrl)+
         ";h"+escape(document.title.substring(0,80))+";"+Math.random();
       LoadScript('http://counter.rambler.ru/top100.jcn?1367185');
       LoadScript('http://openstat.net/cnt.js');
       if(typeof yaCounter9489841 == 'object')yaCounter9489841.hit(MainUrl, document.title, null);
       */
        oldUrl=MainUrl;
       window.setTimeout('oef()',200);
        // прокрутка на позицию начала блока main
        ScrollToObj(obj);
        // todo в правый блок загружаю новую рекламу, нижний банер и лайки вконтакте
        return;
    }
    ajaxEval(obj, data);
    if(t)ExecScript(t);
}

function ScrollToObj(o){
    var o1=o; var pos=0; while(o1.offsetParent){ pos+=parseInt(o1.offsetTop); o1=o1.offsetParent;}
    window.scrollTo(0,pos-10);
    //console.log('window.scrollTo(0,',pos,'-10);');
}

function ajaxEval(obj, data){
console.log("ajaxEval(",obj,'<',obj.tagName,'>', data,'<',typeof(data),">)");
    if(obj.tagName=='INPUT'||obj.tagName=='TEXTAREA'){
        if(obj.value!=data){
            obj.value=data;
            if(obj.onchange!=null)obj.onchange(obj);
        }
//   }else if(obj.tagName=='FORM' && typeof(data)=='array'){
//	for(i in window)ajaxEval(obj[i], data[i]);
    }else if(obj.tagName=='SELECT'){
        if(typeof(data)=='number' || data.indexOf('<')<0){ // это value
            //alert(obj.tagName+' '+data+' '+typeof(data));
            for(i=0;i<obj.options.length;i++)
                if(obj.options[i].value==data){obj.options[i].selected=true;break;}
        }else{
            obj.options=[];
            var re=new RegExp ("<option[^<]+</option>","img");
            data=data.match(re);
            if(data){
                for(var i=0;i<data.length;i++){
                    var value; var text;
                    var re0 = new RegExp ("value=[\'\"]([^\'\"]+)[\'\"]?","i"); value=re0.exec(data[i]); value= value==null? '' : value[1];
	            	if(!value){re0 = new RegExp ("value=([^<>]+)","i"); value=re0.exec(data[i]); value= value==null? '' : value[1];}
                    var re1=new RegExp ("<option[^>]+>([^<]+)</option>","i"); text=re1.exec(data[i]); text= text==null? null : text[1];
                    var re4 = new RegExp ("class=[\'\"]([^\'\"]+)[\'\"]","i"); var defclass=re4.exec(data[i]);
                    var j=obj.options.length;
                    if (text !=null){
                        var re2 = /selected/i; var defSelected=re2.test(data[i]);
                        obj.options[j] = new Option(text, value,defSelected,defSelected);
                        var re3 = /disabled/i; if(re3.test(data[i]))obj.options[j].disabled=true;
                        if(defclass!=null) obj.options[j].className=defclass[1];
                    }else obj.options[j] = new Option('ОШИБКА!', '' );
                }
            }}
    }else if(typeof(data)=='object' && obj.tagName=='A'){
        //console.log('data=',data, ', obj=',obj);
        for(var k in data){
            //console.log(obj,'[',k, '] =',data[k]);
            if(k=='innerHTML')obj.innerHTML=data[k];
            else obj.setAttribute(k, data[k]);
        }
    }else obj.innerHTML = data;
}

function ajaxJson(obj, data){
    if(!data)return;
    var ajaxObj=eval("(" + data + ")");
    //console.log('ajaxJson:',obj,ajaxObj)
    if(!obj){for(var k in ajaxObj)if(o=getObj(k))updateObj(o,ajaxObj[k]);return;} // обновляю по id
    if(obj.tagName!="FORM"&&obj.form)obj=obj.form;// это был элемент формы
    if(obj.tagName!="FORM"){
        if(obj.form){
            obj=obj.form;
        }else{
            //log("obj.tagName",obj.tagName);
            ajaxEval(obj, ajaxObj);
            return;
        }
    }
    var pos;
    for(var key in ajaxObj){
        var o=obj[key];
        //console.log("key=",key, "o=",o, "typeof(o)=",typeof(o),"ajaxObj[]=",typeof(ajaxObj[key]), ajaxObj[key]);
        if(typeof(ajaxObj[key])=='object'){
            if(typeof(o)=='object' && o.tagName=='SELECT'){
//console.log('select!');
                o.options.length = 0; var j=0;
                s=ajaxObj[key];
                for(var k in s){
                    var m=s[k];
                    if(typeof(m)=='object'){
                        if(typeof(m['selected'])=='undefined')m['selected']=false;
                        if(typeof(m['value'])=='undefined')m['value']=k;
                        o.options[j++] = new Option(m['text'], m['value'] ,m['selected'],m['selected']);
//console.log('text=',m['text'], ', value=',m['value'] ,', selected=',m['selected']);
                        for(var k1 in m)if(k1!='text'&&k1!='value'&&k1!='selected')o.options[j-1].setAttribute(k1,m[k1]);
                    }else{
//console.log('m=',m, ', k=',k);
                        o.options[j++] = new Option(m, k,false,false);
                    }
                }
                //console.log('Обработка cs1:',o.name.substring(o.name.length - 3));
                if(o.name.substring( o.name.length - 3)=='_cs') {
                    //console.log('Обработка cs2:',o.name);
                    o.style.visibility = 'visible'; // делаю _cs видимым
                    /*if (o.options.length == 1) {_cs.cha();_cs.chd();}*/
                }
            }else if(typeof(o)=='undefined' && getObj(key)){
                ajaxEval(getObj(key),ajaxObj[key]);
            }else{
                //console.log("создаю input name=",key, ', o=',o,', typeof(o)=',typeof(o));
                var s=(typeof(o)=='undefined'?document.createElement("input"):o);
                //console.log(o);
                s.setAttribute('name', key);
                s.setAttribute('type', 'hidden');/*по умолчанию скрытый*/
                if(typeof(o)=='undefined')obj.appendChild(s);
                o=ajaxObj[key];
                for(k in o)s.setAttribute(k, o[k]);
            }
        }else if(typeof(o)!='undefined'&&typeof(o)!='null'){
            ajaxEval(o, ajaxObj[key]);// здесь вылетает в "use strict"
        }else if((pos=key.indexOf('.'))>0){ // имя.атрибут=значение
            o=key.substr(0,pos);
            o=obj[o];
            if(typeof(o)!='undefined'&&typeof(o)!='null'){
                s=key.substr(pos+1);
                if(s=='disabled')o.disabled=ajaxObj[key];
                else if(s=='value'&&o.tagName=='SELECT'){
                    o.options.length = 0;
                    var t=ajaxObj[key]; s='';
                    //console.log("name=",o.name);
                    if(o.name.substr(o.name.length-3,3)=='_cs'){
                        s=eval('o.form.'+o.name.substr(0,o.name.length-3));
                        //console.log("s=",s,", n=",'o.form.'+o.name.substr(0,o.name.length-3));
                        if(s)_cs.cho=t=s.value; // text option беру из klient.value, value option беру из ajaxObj['klient_cs']
                    }                      // text, value
                    o.options[0] = new Option(t, ajaxObj[key],true,true);
                    if(s){var o1=s.getAttribute('after'); if(o1)eval(o1);}
                }else o.setAttribute(s, ajaxObj[key]);
            }//else console.error("ошибка в ",key, "для ",ajaxObj[key]);

        }else if(o=getObj(key)){
            ajaxEval(o,ajaxObj[key]);

        }else if(key=='error'){
            fb_err(ajaxObj[key]);

        }else if(key=='message'){
            fb_mes(ajaxObj[key]);

        }//else console.error("нет id=",key, "для ",ajaxObj[key]);
    }
    if(obj.style)obj.style.display='block';
}

function ajaxCallBack(obj, ajaxObj, callback){
    return function(){
        //console.log("ajaxCallBack",ajaxObj.readyState, ajaxObj);
        if(ajaxObj.readyState==4){
            if(callback) if(!callback(obj,ajaxObj))return;
            hide('wait');
            if (ajaxObj.status==200){
                var ct=ajaxObj.getResponseHeader("Content-Type");
                if(ajaxObj.responseText.length<1){
                }else if(ct.indexOf("application/x-javascript")>=0 || ct.indexOf("text/javascript")>=0){
//console.log("eval.JS: ",ajaxObj.responseText.replace(/\n/g,";").replace(/\r/g,""));
		        eval(ajaxObj.responseText.replace(/\n/g,"").replace(/\r/g,""));
            }else if(ct.indexOf('json')>=0){
                if(obj.tagName=='DIV')updateObj(obj, ajaxObj.responseText.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/,\"/g,", \""));
                else ajaxJson(obj,ajaxObj.responseText);
            }else updateObj(obj, ajaxObj.responseText);
	    }else{
            updateObj(obj, (ajaxObj.responseText ? ajaxObj.responseText : ajaxObj.status+' '+ajaxObj.statusText+ajaxObj.getAllResponseHeaders()),1,1);
        }
    }else {
            if (ajaxObj.readyState == 3) {
                var pattern = new RegExp("(^|\\s)chat(\\s|$)");
                if (ajaxObj.chat || (obj && pattern.test(obj.className))) {
                    obj.innerHTML = ajaxObj.responseText;
                }
            }
        }
    }}

function oef(){
    //log('oef',document.links.length);
    var pos;
    for(var i=0; i<document.links.length; i++) {
        var l=document.links[i];
        var lp=l.pathname;
        if(l.id=='mail'||l.className=='mail'){/*страница контакты*/
            //log(l);
            var mail="kdg"+'@'; mail=mail+/*window.location.hostname*/ 'ZagarRostov.ru';
            clear(l);
            l.appendChild( document.createTextNode( mail ) );
            if(l.href==document.location)l.href="mai"+"lto:"+mail;
        }else if(l.hostname.indexOf(window.location.hostname)<0){
            l.target='_blank';
            pos=l.href.indexOf('/',10);
            if (pos>=0 && l.onclick==null){
                l.onclick=new Function('this.href="'+l.href+'";');
                l.href=l.href.slice(0,pos);
            }
        }else if(lp.substring(0,3)=='/G/'){
            l.target='_blank';
            var h='http:/'+l.href.substr(l.href.indexOf('/G/')+2);
            pos=h.indexOf('/',10);
            if (pos>0 && l.onclick==null){ // прячу ref ссылку
                l.onclick=new Function('this.href="'+h+'";');
                l.href=h.substr(0,pos);
            }else l.href=h;
        }else if(lp.substring(0,4)=='/Gs/'){
            l.target='_blank';
            l.href='https:/'+l.href.substr(l.href.indexOf('/Gs/')+3);
        }else if(!document.getElementById('main')){
        }else if(/*document.location.pathname.indexOf('function')>=0 &&*/
            lp.indexOf('function')>=0 && l.onclick==null ){ //	l.href.indexOf('#')<0 &&
            addEvent(l, 'click',LoadMain);// l.onclick=LoadMain;
        }else if(lp.indexOf('/example/')>=0 && lp.indexOf('/index.')<0 && lp.substring(lp.length-1)!='/' && l.onclick==null ){
            addEvent(l, 'click',InWin);// l.onclick=LoadMain;
        }//else //if(l.href.hostname==document.location.hostname)
//	addEvent(l, 'click',LoadMain);// l.onclick=LoadMain;
//else alert(document.links[i].href);
    }
    addEvent(document, 'click', oef_d);// l.onclick=LoadMain;
    if(typeof _cs != 'undefined')_cs.d();

    var e=document.getElementsByTagName('INPUT');
    var el=e.length;
    for(i=0; i < el; i++) {
        if (e[i].classList.contains('mask')){
            //console.log('mask',e[i]);
            e[i].addEventListener("input", mask, false);
        }
    }
}


Function.prototype.bind = function(object) {
    var method = this;
    return function() {
        return method.apply(object, arguments)
    }
};

function BuildHttp(frm, wait){
    var str='';
    if(frm.tagName=='INPUT'){/*передан input type=submit*/
        if(frm.name)str=encodeURIComponent(frm.name)+'='+encodeURIComponent(frm.value)+'&';
        frm=frm.form;
    }
    var els=frm.getElementsByTagName("INPUT");
    var elsLen=els.length, v;
    var j,i;
    for (i=0, j=0; i < elsLen; i++)
        if( els[i].type=='submit' && wait ) {
            els[i].disabled=true;
            v=els[i].value;
            els[i].value='Ожидайте...';
            window.setTimeout(function(o,v){return function(){o.disabled=false;o.value=v;}}(els[i],v),3000);
        }
    for (i=0; i<frm.length; i++) if(frm[i].name){
        if(frm[i].disabled)continue;
        //console.log(frm[i].tagName,frm[i].name,frm[i].selectedIndex);
        if(!frm[i].name){
        }else if(frm[i].tagName=='SELECT'&&frm[i].selectedIndex>=0){
            str=str+encodeURIComponent(frm[i].name)+'='+encodeURIComponent(frm[i].options[frm[i].selectedIndex].value)+'&';
        }else if((frm[i].tagName=='INPUT')&& ((frm[i].type=='radio')|| (frm[i].type=='checkbox'))){
            if(frm[i].checked) str+=encodeURIComponent(frm[i].name)+'='+encodeURIComponent(frm[i].value)+'&';
        }else if(frm[i].tagName=='INPUT' && frm[i].type=='submit' ){
            str=str+encodeURIComponent(frm[i].name)+(frm[i].value?'='+encodeURIComponent(frm[i].value):'')+'&';
        }else{
            if(frm.method=='GET') str=str+encodeURIComponent(frm[i].name)+(frm[i].value?'='+encodeURIComponent(frm[i].value):'')+'&';
            else str=str+encodeURIComponent(frm[i].name)+'='+(frm[i].value?encodeURIComponent(frm[i].value):'')+'&';
        }
    }
    str=str.slice(0,-1);
    return str;
}

/** универсальная отправка формы на onsubmit или на onclick
 */
function SendForm(obj,frm,msg,AddHistory){
    var str=BuildHttp(frm,!0);
    if(frm.tagName=='INPUT'){/*передан input type=submit*/
        frm=frm.form;
    }
// str=str.slice(0,-1);
    str=str+'&screen='+encodeURIComponent(wW()+'x'+wH()+(('ontouchstart' in document.documentElement)?',touch':''));
    if(document.referrer)str=str+'&referer='+encodeURIComponent(document.referrer);
    var s=getSubmit(frm);
    var fcb;
    if(s){s.style.display='none';fcb=function(){this.style.display='block';return true;}.bind(s);}
    else fcb=null;
    if(frm.method=='get'){
        ajaxLoad(obj, frm.action+(frm.action.indexOf('?')>0?'&':'?')+str, (msg ? msg : 'Отправка...'),'', fcb);
    }else{
        ajaxLoad(obj, frm.action, (msg ? msg : 'Отправка...'), str, fcb);
    }
    if(obj!='info')fb_close();
    if(AddHistory)UpdateUrl(MainUrl=(frm.action.indexOf('/api.php?')!==-1?frm.action.replace('/api.php?','/?'):frm.action)+(str?(frm.action.indexOf('?')!==-1?"&":"?"):'')+str);
    return false;
}

MainUrl='';
/**
 * @return {boolean}
 */
function LoadMain(e0){
    var e=e0||window.event;
    if(e){if(e.ctrlKey||e.shiftKey)return true;} // если нажата Ctrl или Shift, то загружать в отдельном окне

    if(e0 && e0.stopPropagation){e0.stopPropagation();e0.preventDefault();}       // для DOM-совместимых браузеров
 else if(window.event)window.event.cancelBubble=true; //для IE

    var url = getEventTarget(e0);
    if(url.nodeName!='A'&&url.parentNode)url=url.parentNode;

    if(url.href)url=url.href;
    LoadMainUrl(url,1);
    return false;
}

function getCookie( name ) {
    var start = document.cookie.indexOf( name + '=' );
    var len = start + name.length + 1;
    if ( ( !start ) && ( name != document.cookie.substring( 0, name.length ) ) ) return null;
    if ( start == -1 ) return null;
    var end = document.cookie.indexOf( ';', len );
    if ( end == -1 ) end = document.cookie.length;
    return unescape( document.cookie.substring( len, end ) );
}

function setCookie( name, value, expires, path, domain, secure ) {
    var today = new Date();
    today.setTime( today.getTime() );
    if ( expires ) expires = expires * 1000 * 60 * 60 * 24;
    var expires_date = new Date( today.getTime() + (( expires ) ? expires : 1000 * 60 * 60 * 24 ) );
    document.cookie = name+'='+escape( value ) +
        ';expires='+expires_date.toGMTString() +
        ( ( path ) ? ';path=' + path : '' ) +
        ( ( domain ) ? ';domain=' + domain : '' ) +
        ( ( secure ) ? ';secure' : '' );
}

function deleteCookie( name, path, domain ) {
    if ( getCookie( name ) ) document.cookie = name + '=' +
        ( ( path ) ? ';path=' + path : '') +
        ( ( domain ) ? ';domain=' + domain : '' ) +
        ';expires=Thu, 01-Jan-1970 00:00:01 GMT';
}

if(!document.funcDomReady)document.funcDomReady='';

function onDomReady(func) {
    var oldonload = document.funcDomReady;
    document.funcDomReady=function(){if(typeof oldonload == 'function')oldonload(); func();};
    /*if(typeof document.funcDomReady != 'function'){
     if(document.funcDomReady)console.error('Ошибка в document.funcDomReady',document.funcDomReady);
     document.funcDomReady = func;
     }else{	document.funcDomReady = function() {
     oldonload();
     func();}}
     */
}
function init() {
    /*if(arguments.callee.done) return;
     arguments.callee.done = true;*/
    if(document.funcDomReady){document.funcDomReady();document.funcDomReady='';}	// вызываем всю цепочку обработчиков
}
if(document.addEventListener)document.addEventListener("DOMContentLoaded", init, false);

/*@cc_on @*/
/*@if (@_win32)
 document.write("<script id=\"__ie_onload\" defer=\"defer\" src=\"javascript:void(0)\"><\/script>");
 var script = document.getElementById("__ie_onload");
 script.onreadystatechange = function(){if (this.readyState=="complete")init();};
 /*@end @*/

if(/WebKit/i.test(navigator.userAgent)) { // для Safari
    var _timer = setInterval(function() {
        if (/loaded|complete/.test(document.readyState)) {
            clearInterval(_timer);
            init(); // вызываем обработчик для onload
        }
    }, 10);
}
var OldOnload = window.onload;
if (typeof OldOnload === "function"){
    window.onload = function() {
        OldOnload();
        init();
    };
}else
    window.onload = init; // для остальных браузеров

function LoadScript(src,f){
    //console.log('LoadScript',src);
    var el=document.createElement('script');
    el.setAttribute('src',src);
    el.setAttribute('type','text/javascript');
    el.setAttribute('async','true');
    el.setAttribute('charset','utf-8');
    if(typeof f == 'function')addEvent(el,'load',f);
    document.getElementsByTagName('head')[0].appendChild(el);
    return el;
}

function ExecScript(src){
    var el=document.createElement('script');
    el.setAttribute('type','text/javascript');
    try{el.appendChild(document.createTextNode(src));}catch(e){alert('Ошибка '+e+' выполнения\n'+src);}
    document.body.appendChild(el);
    if(document.funcDomReady){document.funcDomReady();document.funcDomReady='';}
    return el;
}

var addEvent = (function(){
    if (document.addEventListener){
        return function(obj, type, fn, useCapture){
            if(!obj)console.error(obj, type, fn, useCapture);
            if(typeof(obj)!="object")obj=document.getElementById(obj);
            //console.log("addEvent:",obj,fn);
            if(obj)obj.addEventListener(type, fn, useCapture);
        }
    } else if (document.attachEvent){ // для Internet Explorer
        return function(obj, type, fn, useCapture){
            if(typeof(obj)!="object")obj=document.getElementById(obj);
            obj.attachEvent("on"+type, fn);
        }
    } else {
        return function(obj, type, fn, useCapture){
            if(typeof(obj)!="object")obj=document.getElementById(obj);
            obj["on"+type] = fn;
        }
    }
})();

function removeEvent(obj, eventType, handler)
{    if(obj&&typeof(obj)!="object")obj=document.getElementById(obj);
    return (obj.detachEvent ? obj.detachEvent("on" + eventType, handler) : ((obj.removeEventListener) ? obj.removeEventListener(eventType, handler, false) : null));
}

function getEventTarget(e,tag) {
    e = e || window.event;
    var target=e.target || e.srcElement;
    if(typeof target == "undefined"){
        target=e; // передали this, а не event
    }else{
        if (target.nodeType==3) target=target.parentNode;// боремся с Safari
    }
    if(tag!=null)while((target) && target.nodeName!=tag)target=target.parentNode;
    return target;
}

function CheckClick(e){
    var t=e.previousSibling.previousSibling;
    if( (t.tagName=='INPUT')&&(t.type=='checkbox'||t.type=='radio')) t.click();
}

/** определить координаты элемента
 * @param obj
 * @param f_modal =true- считать относительно модального окна. если div будет внутри окна
 * @returns {{top: number, left: number}}
 */
function getOffset(obj,f_modal) {
    var top=0, left=0;
    if(obj.getBoundingClientRect){
        var box = obj.getBoundingClientRect();
        var body = document.body;
        var docElem = document.documentElement;
        var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
        var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;
        var clientTop = docElem.clientTop || body.clientTop || 0;
        var clientLeft = docElem.clientLeft || body.clientLeft || 0;
        top  = box.top +  scrollTop - clientTop;
        left = box.left + scrollLeft - clientLeft;
        while(obj) {
            c=window.getComputedStyle(obj, null);
            //console.log(obj,c);
            if(f_modal && (c.position=='fixed'||c.position=='absolute'||c.position=='relative')){
                top-=parseInt(c.top)+parseInt(c.marginTop);
                left-= parseInt(c.left)+parseInt(c.marginLeft);
                //console.log(c.top,c.left);
                break;
            }
            obj = obj.offsetParent;
        }
        //log("getOffset:getBoundingClientRect");
        return { top: Math.round(top), left: Math.round(left) }
    }else{
        while(obj) {
            var c=window.getComputedStyle(obj, null);
            if(f_modal && (c.position=='fixed'||c.position=='absolute'))break;
            top+=parseInt(obj.offsetTop);
            left+=parseInt(obj.offsetLeft);
            obj = obj.offsetParent;
        }
        //log("getOffset:calc");
        return {top: top, left: left}
    }
}

var _tt=function(){ //всплывающие подсказки
    var id='tt';
    var top=3;
    var left=3;
    var maxw=300;
    var speed=4;
    var timer=10;
    var endalpha=95;
    var alpha=0;
    var tt,t,h;
    var ie=document.all ? true : false;
    return{
        show:function(e,v,w){
            var t=getEventTarget(e);addEvent(t,'mouseout',this.hide); //t.style.cursor='help';
            if(tt==null){
                tt=document.createElement('div');
                tt.setAttribute('id',id);
                document.body.appendChild(tt);
                tt.style.opacity=0;
                if(ie)tt.style.filter='alpha(opacity=0)';
                addEvent(tt,'mouseover',this.over);
                addEvent(tt,'mouseout',this.hide);
                //addEvent(document,'mousemove',this.pos);
            }
            tt.style.display='block';
            tt.innerHTML=v;
            var dd=getOffset(t);
            if(!w){
                w=Math.min(maxw, wW()-dd.left-55);
            }
            if(w<100){
                w+=100;
                dd.left-=w+26;
                tt.style.borderRadius='10px 10px 0 10px';
            }else{
                tt.style.borderRadius='';
            }
            tt.style.width=w ? w + 'px' : 'auto';
            if(tt.offsetWidth > maxw){tt.style.width=maxw+'px'}
            h=parseInt(tt.offsetHeight) + top;
            _tt.over(e);
             tt.style.top=(dd.top-h+4) + "px";
             tt.style.left=(dd.left+13) + "px";
        },
        pos:function(e){
            var u=ie ? event.clientY + document.documentElement.scrollTop : e.pageY;
            var l=ie ? event.clientX + document.documentElement.scrollLeft : e.pageX;
            tt.style.top=(u - h) + 'px';
            tt.style.left=(l + left) + 'px';
        },
        fade:function(d){
            var a=alpha;
            if((a != endalpha && d == 1) || (a != 0 && d == -1)){
                var i=speed;
                if(endalpha - a < speed && d == 1){i=endalpha - a;
                }else if(alpha < speed && d == -1){i=a;}
                alpha=a + (i * d);
                tt.style.opacity=alpha * .01;
                if(ie)tt.style.filter='alpha(opacity=' + alpha + ')';
            }else{
                clearInterval(tt.timer);
                if(d == -1){tt.style.display='none'}
            }
        },
        hide:function(e){
            clearInterval(tt.timer);
            tt.timer=setInterval(function(){_tt.fade(-1)},timer);
        },
        over:function(e){
            clearInterval(tt.timer);
            tt.timer=setInterval(function(){_tt.fade(1)},timer);
        }
    };
}();


function tabs(t,p){
    var l=t.parentNode.getElementsByTagName('a');
    var j=0; var o=null;
    for(var i=0;i<l.length;i++){removeClass(l[i],'on'); if(l[i]==t)j=i;}
    addClass(t,'on');
    var u=t.href;
    if(p==null)p=t.parentNode.parentNode.nextSibling;while(p.tagName!="DIV")p= p.nextSibling; // ищу следующий блок DIV-ов
    for(i=0;i<l.length&&p!=null;i++){
        if(i==j){removeClass(p,'hide'); o=p; }
        else addClass(p,'hide');
        p=p.nextSibling;
        while(p!=null && p.tagName!="DIV")p=p.nextSibling;
        if(p==null)break;

    }
    if(o.innerHTML=='')ajaxLoad(o, u+(u.indexOf('?')>=0?'&':'?')+'ajax=1','Загрузка...'); // если div пустой запрашиваю данные для него
    return false;
}
/**
 * возвращает адрес без GET параметров. Если url не передан, то от текущего документа
 * @param url
 * @returns {*}
 */
function getUrl(url){
    if(!url)url=document.location.href;
    var i=url.indexOf('?');
    if(i>=0)url=url.substr(0,i);
    i=url.indexOf('#');
    if(i>=0)url=url.substr(0,url.indexOf('#'));
    if(url.indexOf(' ')>= 0)url=trim(url);
    if(url.substr(0,14)=='http://http://')url=url.substr(7);
    if(url.indexOf(' ')>0)url=url.substr(0,url.indexOf(' '));
    if(url=='http://'||url=='https://')return '';
    return url;
}

function layer(a,cl){
    if(!cl)cl='layer';
    //console.log(cl,a);
    var l=getElementsByClass(cl,null,'div');
    for(var i=0;i<l.length;i++)l[i].style.display=(i==a?'block':'none');
    if(l[a].innerHTML==''){ // если div пустой запрашиваю данные для него
        var url=getUrl();
    if(a!=0)url=url+(url.indexOf('?')>=0?'&':'?')+"layer="+encodeURIComponent(a);
    MainUrl=url;
    if(history.pushState)history.pushState(null, null, MainUrl);
        //url=url+(url.indexOf('?')>=0?'&':'?')+'ajax=1';
        ajaxLoad(l[a],url,'Загрузка...');
    }
    l=getElementsByClass(cl,null,'span');
    for(i=0;i<l.length;i++){
        //l[i].className=(i==a?removeClass('layer act':'layer');
        if(i==a) addClass(l[i], 'act');
        else removeClass(l[i], 'act');
        if(i==a)window.location.hash=getText(l[i]);
    }
    return false;
}

/*
 function layer(a){
 // если a=null перезагружаю текущую вкладку
 var url=document.location.href;
 if(url.indexOf('?')>=0)url=(url+'&').replace(/layer=(.*?)&/gi,"").replace(/&&/gi,"&");
 var i=url.substr(url.length-1,1);
 if(i=='?'||i=='&')url=url.substr(0,url.length-1);
 url=url+(url.indexOf('?')>=0?'&':'?')+"layer="+encodeURIComponent(a);
 MainUrl=url;
 if(history.pushState)history.pushState(null, null, MainUrl);
 var l = getElementsByClass('layer', null, 'div');
 for(i=0;i<l.length;i++){
 l[i].style.display=(i==a?'block':'none');
 }
 if(l[a].innerHTML=='')ajaxLoad(l[a],url+'&ajax=1','Загрузка...'); // если div пустой запрашиваю данные для него

 l=getElementsByClass('layer',null,'span');
 for(i=0;i<l.length;i++){
 l[i].className=(i==a?'layer act':'layer');
 //if(i==a)window.location.hash=getText(l[i]);
 }
 }
 */

/**
 * @return {boolean}
 */
function LoadLayer(a,url){    //загрузить в текущую вкладку
    MainUrl=url;
    if(history.pushState)history.pushState(null, null, MainUrl);
    if(!a)a='layer'+getlayer();
    ajaxLoad(a,url+(url.indexOf('?')>=0?'&':'?')+'ajax=1','Загрузка...');
    return false;
}

function getlayer(){    // определяю текущую вкладку даже если она заданна именем
    var l=getElementsByClass('layer',null,'span');
    for(var i=0;i<l.length;i++)if(l[i].classList.contains('act'))return i;
    var j=location.search.match( /layer=(\w)*/g );
    return (j?j[0].substring(6):'0');
}

function layer_(a){ // %18AB>@8O = История
if(a==null)a=/*window.location.hash;*/new String(document.location.hash).replace("#","");
    var l=getElementsByClass('layer',null,'span');
    for(var i=0;i<l.length;i++)
	if(encodeURIComponent(getText(l[i]))==a){/*layer(i);*/l[i].onclick();break;}
}

/** добавляет вкладку, возвращает div */
function layerAdd(tSpan,cl,url){
    if(!cl)cl='layer';
    var s=getElementsByClass(cl,null,'span');
    var ld=getElementsByClass(cl,null,'div');
    var a=s.length;
    var o=null; var d=null;
    for(var i=0;i<a;i++)if(encodeURIComponent(getText(s[i]))==tSpan){o=s[i];d=ld[i];a--;break;}
    if(!o){
        o=document.createElement('span');
        o.setAttribute('class',cl);
        o.innerHTML=tSpan;
        var p=s[a-1]; var pp=p.parentNode;
        pp.insertBefore(o, p.nextSibling);
        pp.insertBefore(document.createTextNode("\n"), o); // иначе вкладки сливаются

        d=document.createElement('div');
        d.setAttribute('class',cl);
        ld[0].parentNode.appendChild(d);
    }
    if(url)o.setAttribute('data-url',url);
    addEvent(o, 'click',function(a,cl){return function(){layer(a,cl)}}(a,cl));
    layer(a,cl);
    return d;
}

/** удаляет вкладку */
function layerDel(num,cl){
    if(!cl)cl='layer';
    var l=getElementsByClass(cl,null,'span');
    if(l.length>=num)l[0].parentNode.removeChild(l[num-1]);
    l=getElementsByClass(cl,null,'div');
    if(l.length>=num)l[0].parentNode.removeChild(l[num-1]);
}

function s_q(f){
    var url, u, g, s;
    if(f){
        s=_GET();
        g=BuildHttp(f,!0).split("&");
        for(var i=0; i<g.length; i++) {
            u = g[i].split("=");
            s[u[0]] = typeof(u[1])=="undefined" ? "" : u[1];
            }
        url='';
        for(var key in s)url+=(url?'&':'?')+key+ (s[key] ? '=' + s[key] : '');
        url=document.location.pathname+url; /*+document.location.hash;*/
    }else{
        url=location.search.match( /layer=(\w)*/g );
        url=document.location.pathname+(url?'?'+url[0]:'');
    }
    console.log("~1: ",url);

    if(url.indexOf('?')>=0)url=url.replace(/&&/g,"&").replace(/\?&/g,"?").replace(/&\?/g,"?");
//console.log("~2: ",url);
    url=url.replace(/[\?\&]+$/g, '');
//console.log("~3: ",url);
    MainUrl=url;
    if(history.pushState)history.pushState(null, null, MainUrl);
    i='layer'+getlayer();
//console.log(i+", url="+url);
    ajaxLoad(i,url,'Загрузка...');
    return false;
}

/*function perpage(t){
 var url=document.location.href;
 if(url.indexOf('?')>=0)url=url.replace(/[\&\?]perpage=(\w)*//*gi,"");
 if(getObj('layer0')){
 // если работаю с вкладками, то очистить все вкладки и загрузить в текущую
 var l=getElementsByClass('layer',null,'div');
 for(var i=0;i<l.length;i++)l[i].innerHTML='';
 //определяю текущую вкладку
 l='layer'+getlayer();
 url = url + (url.indexOf('?') >= 0 ? '&' : '?') + "perpage=" + encodeURIComponent(t.options[t.selectedIndex].value);
 UpdateUrl(url);
 ajaxLoad(l,url+'&ajax=1');
 }else
 LoadMainUrl(url+(url.indexOf('?')>=0?'&':'?')+"perpage="+encodeURIComponent(t.options[t.selectedIndex].value),true);
 return false;
 }*/
////////////////////////////////

function clear(o){
    if(typeof(o)=="string")o=getObj(o);
    if(o)while(o.firstChild&&o.hasChildns())o.removeChild(o.firstChild);
    return o;}

function show(o){
    if(typeof(o)=="string")o=getObj(o);
    if(!o)return false;
    removeClass(o,"hide");
    //if(o.style.display=="none")o.style.display=(o.tagName=="DIV"?"block":"inline");
    return false;}

function hide(o){
    if(o)addClass(o,"hide");
    return o;}

function mark(o,cl){ /* mclick */
    if(typeof(o)=="string")o=getObj(o);
    if(!cl)cl='red';/* класс пометки */
    if(o.classList.contains(cl) )removeClass(o, cl);
    else addClass(o, cl);
    //var re = new RegExp("(^|\\s)" + cl + "(\\s|$)", "g"); if (re.test(o.className)) removeClass(o, cl);
}

function ShowHide(c,cl){
    if(!cl)cl='hide';
    var l=(typeof c =='object' ? c : document.getElementById(c));
    if(l){
        l.classList.toggle(cl);
    }else{
        l=getElementsByClass(c,null,null);
        for(var i=0;i<l.length;i++){
            l.classList.toggle(cl);
        }
    }
    return false;
}


function move(o){
    if(typeof(o)=="string")o=getObj(o);
    var pos=0; while(o.offsetParent){ pos+=parseInt(o.offsetTop); o=o.offsetParent;}
    window.scroll(0,pos-10);
}

function removeID(o){
if(typeof(o)=="string")o=getObj(o);
if(o)o.parentNode.removeChild(o);
}

function reload(){
    window.location.reload();
//    parent.location=
    //location.href=String(window.location.href+(window.location.href.indexOf('?')>=0?'&':'?')+'v='+data()+(window.location.hash.length>1?window.location.hash:''));
    //console.log(window.location);
}
var fb_modal=null;

var _fade=function(){ // затухание/проявление блока, используется для всплывающих подсказок
    var speed = 2;
    var timer=20;
    var otimer;
    var endalpha = 95;
    var alpha = 0;
    var tt;
    var ie = document.all ? true : false;
    var d=-1;/*по умолчанию затухание*/
    return{

        init:function(){
            window.setTimeout(function(){_fade.start()},2000);
        },
        start:function(){
            tt=getElementsByClass('fb-win1',null,'DIV');
            if(tt)tt=tt[tt.length-1];
            if(!tt){/*console.log('fade:start');*/ return;}
            addEvent(tt,'mouseover',function(e){_fade.over(e)});
            addEvent(tt,'mouseout',function(e){_fade.hide(e)});
            alpha=100;
            otimer=setInterval(function(){_fade.fade()},timer);
        },
        fade:function(){
            var a = alpha;
            if((a != endalpha && d == 1) || (a != 0 && d == -1)){
                var i = speed;
                if(endalpha - a < speed && d == 1){i = endalpha - a;
                }else if(alpha < speed && d == -1){i = a;}
                alpha = a + (i * d);
                if((tt)&&tt.style){
                    tt.style.opacity = alpha * .01;
                    if(ie)tt.style.filter='alpha(opacity=' + alpha + ')';
                }
            }else{
                clearInterval(otimer);
                if(d == -1)fb_close();
            }
        },
        hide:function(e){
            clearInterval(otimer);
            otimer=setInterval(function(){_fade.fade()},timer);
        },
        over:function(e){
            clearInterval(otimer);
            alpha=100;
            tt.style.opacity=1;
            if(ie)tt.style.filter='alpha(opacity=100)';
        }
    };
}();


function wW(){
    var de = document.documentElement;
    return self.innerWidth || ( de && de.clientWidth ) || document.body.clientWidth;
}
function wH(){
    var e=document.documentElement;
    return self.innerHeight||(e&&e.clientHeight)||document.body.clientHeight;
}

/*
 if (!window.getComputedStyle) { // борьба с IE
 window.getComputedStyle = function(el, pseudo) {
 this.el = el;
 this.getPropertyValue = function (prop) {
 var re = /(\-([a-z]){1})/g;
 if (prop == "float") prop = "styleFloat";
 if (re.test(prop)) {
 prop = prop.replace(re, function () {
 return arguments[2].toUpperCase();
 });
 }
 return el.currentStyle[prop] ? el.currentStyle[prop] : null;
 };
 return this;
 }
 }
 */

"getComputedStyle" in window || function() { // борьба с IE
    function c(a, b, g, e) {
        var h = b[g];
        b = parseFloat(h);
        h = h.split(/\d/)[0];
        e = null !== e ? e : /%|em/.test(h) && a.parentElement ? c(a.parentElement, a.parentElement.currentStyle, "fontSize", null) : 16;
        a = "fontSize" == g ? e : /width/i.test(g) ? a.clientWidth : a.clientHeight;
        return "em" == h ? b * e : "in" == h ? 96 * b : "pt" == h ? 96 * b / 72 : "%" == h ? b / 100 * a : b;
    }
    function a(a, c) {
        var b = "border" == c ? "Width" : "", e = c + "Top" + b, h = c + "Right" + b, l = c + "Bottom" + b, b = c + "Left" + b;
        a[c] = (a[e] == a[h] == a[l] == a[b] ? [a[e]] : a[e] == a[l] && a[b] == a[h] ? [a[e], a[h]] : a[b] == a[h] ? [a[e], a[h], a[l]] : [a[e], a[h], a[l], a[b]]).join(" ");
    }
    function b(b) {
        var d, g = b.currentStyle, e = c(b, g, "fontSize", null);
        for (d in g) {
            /width|height|margin.|padding.|border.+W/.test(d) && "auto" !== this[d] ? this[d] = c(b, g, d, e) + "px" : "styleFloat" === d ? this["float"] = g[d] : this[d] = g[d];
        }
        a(this, "margin");
        a(this, "padding");
        a(this, "border");
        this.fontSize = e + "px";
        return this;
    }
    b.prototype = {};
    window.getComputedStyle = function(a) {
        return new b(a);
    };
}();

function fb_win(html,ev){
    fb_modal=document.createElement('div');
    fb_modal.setAttribute('class','fb-win');
    //console.log("fb_win:",html);
    fb_modal.innerHTML=((ev==2)?'':'<div class="fb-win0" onclick="fb_close()"></div>')+'<div class="fb-win1">'+
        '<a onclick="fb_close();return false;" title="Закрыть" href="#"></a>'+html+
'<div class="dragbar" onmousedown="DD.start(event)" onmouseup="DD.stop(event)" oncontextmenu="return false" ondragstart="return false" ondblclick="DD.full(event)" ondragend="return DD.stop(event)"></div></div>';
    document.body.appendChild(fb_modal);
if(ev){
    if(ev==1){var d=document.forms;d=d[d.length-1][0].focus();}
	else if(ev==2){_fade.init();}
        else {/*console.log("eval:",ev); eval(ev);*/ExecScript(ev);}
    }
    addEvent(document, "keydown", fb_close);
    addEvent(window, "resize", fb_ResizeDocument);
    fb_ResizeDocument(); // для корректного расчета ширины окна нужно на все загружаемые в модальном окне изображения добавить onload="fb_ResizeDocument(event)"
    // если в окно загружаются элементы с неограниченной шириной - окно будет максимально широким
    var o=document.body.firstElementChild;
    if(o){ // фиксирую основной экран(первый div внутри body) как подложку
        var y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        var c=window.getComputedStyle(o, null);
        var b=window.getComputedStyle(document.body, null);
        //console.log(c.width, b.width,wW(),o);
        if(c.width!=b.width && !o.getAttribute('data-width'))o.setAttribute('data-width',c.width);
        o.style.width=Math.min(parseInt(c.width),wW())+'px'; // возможно была auto
        var l=parseInt((wW()-parseInt(parseInt(c.width)))/2); if(l==10)l=0; // это вертикальная прокрутка
        //console.log('fb_win l=',l);
        o.style.left=l+'px';
        o.style.marginTop=-y+'px'; // подложка
        addClass(o,'fb-fix');
        window.scroll(0,0);
    }
    return fb_modal;
}

function fb_close(e){// вызывается при нажатии клавиши в модальном окне и при нажатии на крестик
    if(f_reload)reload();
    if(typeof(e)=='object'){
        e=e||window.event;
        if(e.type=='keydown'){
            if(e.keyCode==27)fb_close(); // Esc
            return;}
    }
    var a=getElementsByClass('fb-win',null,'DIV');
    if(a){
        removeID(a[a.length-1]);
        if(a.length<2)removeEvent(document, "keydown", fb_close);
        a=getObj('Calendar');if(a&&a.style)a.style.display='none'; // прячу календарь
        var o=document.body.firstElementChild;
        if(o){
            var c=window.getComputedStyle(o, null);
            var y=-parseInt(c.marginTop);
            removeClass(o,'fb-fix');
            o.style.margin=''; // 0 auto
            o.style.left=''; // auto
            //o.style.width='auto'; // возможно была auto
            o.style.width=(c=o.getAttribute('data-width') ? c : ''); // auto
            //console.log("body.width:",o.style.width);
            window.scroll(0,y);
        }
    }
}

function fb_err(mes){
    var o;
    if (getElementsByClass('fb-win', null, 'DIV')) {
        hide('answer');
        fb_win(mes);
    }
    else {
        o = getObj('answer');
        o.className = "error clear";
        o.innerHTML = mes;
        o.style.display = 'block';
    }
}

function fb_mes(mes){
    var o;
    if(getElementsByClass('fb-win', null, 'DIV')) {
        hide('answer');
        fb_win(mes);
    }else{
        o = getObj('answer');
        o.className = "info clear";
        o.innerHTML = mes;
        o.style.display = 'block';
    }
}

function fb_ResizeDocument(e){
    // вычисляю ширину модального окна и его положение по горизонтали
    var o=getElementsByClass('fb-win1',fb_modal,'DIV'); o=o[0]; if(!o)return;
    var c=window.getComputedStyle(o, null);
    //console.log('до',o,'width=',c.width, wW(), e);
    if(!e || e.type!='resize' || wW()< parseInt(c.width)){
        o.style.width='auto';
        o.style.maxWidth=(wW()-10)+'px';
        //console.log('~',o.style.width,o.style.maxWidth,c.width);
        if(parseInt(c.width)<wW()&&wW()<500)o.style.width=wW()+'px';
        else o.style.width=parseInt(parseInt(c.width)>500 ? Math.min(wW(),parseInt(c.width)+20) : 500 )+'px';
        o.style.maxWidth='none';
        //console.log('Меняю',o);
    }else{
        //console.log('НЕ Меняю',e.type);
    }
    getElementsByClass('dragbar',o,'DIV')[0].style.width=(parseInt(c.width)-45)+'px';
    o.style.left=Math.max(0,parseInt((wW()-parseInt(parseInt(c.width)))/2))+'px';
    //console.log('после',c.width, o.style.left);
    // вычисляю положение модального окна по вертикали
    o.style.top=(parseInt(c.height)>wH()*0.99 ? '0' : Math.floor((wH()-parseInt(c.height))/2)+'px' );
    //console.log('fb_ResizeDocument', o.style.top );

    /*o=getElementsByClass('fb-win0',fb_modal,'DIV'); o=o[0]; if(!o)return;
     o.style.height=wH()+'px';*/
}

function IsUrl(t){ //  oninput="IsUrl(this)" required
    var u=t.value;
    if(u.substr(0,14)=='http://http://')u=u.substr(7);
    if(u==''||u=='http://'){t.setCustomValidity("Укажите URL!");return;}
    u=u.replace(/\,/, ".");
    u=u.replace(/\.\./, ".");
    if(u.indexOf(' ')>7)u=u.substr(0,u.indexOf(' '));
    if(u.indexOf(' ')>=0||u.indexOf('.')<0) {t.setCustomValidity("Укажите корректный URL!");return;}
    if(u.slice(0,7)!='http://'&&u.slice(0,8)!='https://')u='http://'+u;
    t.value=u;
    t.setCustomValidity("");
    return;
}

function addClass(o, c){
    if(typeof(o)!="object")o=document.getElementById(o);if(!o)return;
    if(o.classList.contains(c) )return;
    o.classList.add(c);
    ////var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g"); if (re.test(o.className)) return;
    //o.className = (o.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
    //console.log('addClass(',o,',', c, ')' );
}

function removeClass(o, c){
    if(typeof(o)!="object")o=document.getElementById(o);if(!o||!o.classList)return;
    o.classList.remove(c);
    //var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
    //o.className = o.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
    //console.log('removeClass(',o,',', c, ')' );
}

var DD = { //перемещение окна
    DDmove: false,
    DDobj: null,
    DDx: 0,
    DDy: 0,
    start: function (e) {
        if (DD.DDmove)return false;
        DD.DDobj = getEventTarget(e).parentNode;
        e = e || window.event;
        //DD.DDobj.style.position = 'absolute';
        var c=window.getComputedStyle(DD.DDobj, null);
        DD.DDx=parseInt(c.left) - e.clientX;
        DD.DDy=parseInt(c.top) - e.clientY;
        /*
         var xy = getOffset(DD.DDobj);
         DD.DDx = parseInt(xy.left) - (document.all ? event.clientX : e.clientX);
         DD.DDy = parseInt(xy.top) - (document.all ? event.clientY : e.clientY);
         DD.DDobj.style.marginLeft = "0px";
         */
        DD.DDobj.style.left = DD.DDx + e.clientX + "px";
        DD.DDobj.style.top = DD.DDy + e.clientY + "px";
        DD.DDmove = true;
        addEvent(document, 'mousemove', DD.drag_drop);
        //addEvent(DD.DDobj0, 'mouseup', DD.stop);
    },
    stop: function (e) {
        DD.DDmove = false;
        removeEvent(document, 'mousemove', DD.drag_drop);
        //removeEvent(DD.DDobj0, 'mouseup', DD.stop);
        return true;
    },
    drag_drop: function (e) {
        if (DD.DDmove) {
            e = e || window.event;
            DD.DDobj.style.left = DD.DDx + e.clientX + "px";
            DD.DDobj.style.top = DD.DDy + e.clientY + "px";
            return false;
        }
        return true;
    },
    full: function (e) {
        var s=DD.DDobj.style;
        var c=window.getComputedStyle(DD.DDobj, null);
        var d=getElementsByClass('dragbar',DD.DDobj,'DIV');
        if(DD.DDobj.getAttribute('data-left')){
            s.left = DD.DDobj.getAttribute('data-left');
            s.top = DD.DDobj.getAttribute('data-top');
            s.width=DD.DDobj.getAttribute('data-width');
            s.height=DD.DDobj.getAttribute('data-height');
            s.marginLeft=DD.DDobj.getAttribute('data-marginLeft');
            if(d&&d.length>0)d[0].style.width=DD.DDobj.getAttribute('data-dragbar');
            DD.DDobj.removeAttribute('data-left');
            DD.DDobj.removeAttribute('data-top');
            DD.DDobj.removeAttribute('data-width');
            DD.DDobj.removeAttribute('data-height');
            DD.DDobj.removeAttribute('data-marginLeft');
        }else{
            DD.DDobj.setAttribute('data-left',c.left);
            DD.DDobj.setAttribute('data-top',c.top);
            DD.DDobj.setAttribute('data-width',c.width);
            DD.DDobj.setAttribute('data-height',c.height);
            DD.DDobj.setAttribute('data-marginLeft',c.marginLeft);
            if(d&&d.length>0){
                DD.DDobj.setAttribute('data-dragbar',d[0].style.width);
                d[0].style.width=(wW()-50)+'px';
            }
            s.left = "0px";
            s.top = "0px";
            s.width=(wW()-10)+'px';
            s.height=(wH()-10)+'px';
            s.marginLeft=0;
        }
        return false;
    }
};

function fb_full(id){
    var o1=getElementsByClass('fb-win1',fb_modal,'DIV'); o1=o1[0]; if(!o1)return;
    DD.DDobj=o1;
    DD.full();
    var o=getObj(id);
    var s=o.style;
    var c=window.getComputedStyle(o, null);
    if(o.getAttribute('data-left')){
        s.left = o.getAttribute('data-left');
        s.top = o.getAttribute('data-top');
        s.width=o.getAttribute('data-width');
        s.height=o.getAttribute('data-height');
        s.marginLeft=o.getAttribute('data-marginLeft');
        s.position=o.getAttribute('data-position');
        o.removeAttribute('data-left');
        o.removeAttribute('data-top');
        o.removeAttribute('data-width');
        o.removeAttribute('data-height');
        o.removeAttribute('data-marginLeft');
        o.removeAttribute('data-position');
        removeID('NormalBlock');
    }else{
        o.setAttribute('data-left',c.left);
        o.setAttribute('data-top',c.top);
        o.setAttribute('data-width',c.width);
        o.setAttribute('data-height',c.height);
        o.setAttribute('data-marginLeft',c.marginLeft);
        o.setAttribute('data-position',c.position);
        s.position='absolute';
        s.left = "0px";
        s.top = "0px";
        s.width=(wW()-10)+'px';
        s.height=(wH()-10)+'px';
        s.marginLeft=0;
        var d=document.createElement('div');
        d.setAttribute('id','NormalBlock');
        addClass(d,'button');
        addEvent(d, 'click', function(){fb_full(id)});
        s=d.style;
        s.position='fixed';
        s.right = "0";
        s.top = "0";
        s.zIndex=999999;
        document.body.appendChild(d);
        d.innerHTML='Спахнуть';
    }
    return false;

}

function edit(t,id){
    var o = getObj(t);
    show(o);
    hide('add');
    for (var i=0; i < o.length; i++)if(o[i].type=='submit' || o[i].classList.contains('submit'))o[i].value='Сохранить';
    return ajaxLoad(t,t+'.php?edit='+id);
}

function add(o){
    if(typeof(o)=="string")o=getObj(o);
    for (var i=0; i < o.length; i++) {
        if(o[i].type == 'submit' || o[i].classList.contains('submit'))o[i].value = "Добавить";
    else if(o[i].type != 'reset') o[i].value = '';
        else if(o[i].name == 'id') o[i].value = '';
    }
    show(o);
    return false;
}

/*
function is_phone(t){
    var s = t.value.substr(0, 3);
    if(s=='221')t.value='903401'+t.value.substr(3);
    if(s=='256')t.value='903406'+t.value.substr(3);
    if(s=='275')t.value='918555'+t.value.substr(3);
    if(s=='298')t.value='918558'+t.value.substr(3);
    if(s=='294')t.value='918554'+t.value.substr(3);
    if(s=='226'||s=='296'||s=='270'||s=='279')t.value='928'+t.value;
    if(t.value.substr(0,1)!='9'){
        t.setCustomValidity("Номер телефона должен начинаться с 9!");
    }else if(t.value.length==10){
        t.style.borderColor='green';
        t.setCustomValidity("");
    }else{ t.style.borderColor='red';
        if(t.value.length>10)t.setCustomValidity("Лишняя цифра в номере телефона!");
        else if(t.value.length<10)t.setCustomValidity("Не хватает цифры в номере телефона!");
    }
    ajaxLoad(t.form,'/adm/users.php?tel='+t.value);
}*/

// Определение координаты элемента
/*
 function pageX(obj) {
 var c=window.getComputedStyle(obj, null);
 if(c.position=='fixed')return 0;
 return obj.offsetParent ?
 obj.offsetLeft + pageX( obj.offsetParent ) :
 obj.offsetLeft;
 }
 function pageY(obj) {
 var c=window.getComputedStyle(obj, null);
 if(c.position=='fixed')return 0;
 return obj.offsetParent&&obj.style.position!='fixed' ?
 obj.offsetTop + pageY( obj.offsetParent ) :
 obj.offsetTop;
 }
 */

function trim(s){return s.replace(/^\s+/, '').replace(/\s+$/, '').replace(/\n/g," ").replace(/\r/g," ").replace(/  +/g," ");}

function buildUrl(t){
    var u=buildParam(t,1);
    if(!u||!('id' in u) || !u.id )console.log('Ошибка определения id !');
    //return a+'?ajax='+ u.id+('tbl'in u ?'&tbl='+ u.tbl:'');
    var url=u['api']; url+=(url.indexOf('?')>=0?'&':'?')+'ajax='+u.id;
    for(var k in u)if(k!='api')url=url+(url.indexOf('?')>=0?'&':'?')+k+'='+encodeURIComponent(u[k]);
    console.log(url);
    return url;

}
/** собираю параметры за вызова обработчика
 * за пределы текущего модального окна, <form>, class='api', data-api='...' не всплываю
 * @param t
 * @param all =0, =1 - вернуть массив параметров в любом случае
 * @returns {*}|0
 */
function buildParam(t,all){
    var p={};
    var o=t;
    var a,v;
    var f=0;
    do{
        a=o.attributes;
        for (var k in a){
            /* Выбираем именно html-атрибуты */
            if (a[k].nodeName && a[k].nodeName.substr(0,5)=='data-') {
                console.log(a[k].nodeName + ': ' + a[k].nodeValue,a[k]);
                v=a[k].nodeName.substr(5);
                if(!(v in p)&&a[k].value)p[v]=a[k].value; //nodeValue
            }
        }
        if(!('id' in p) && o.nodeName=='TR'&&o.getAttribute('id')){
            console.log(o, 'id=',o.id);
            var reg=/\D+(\d+)/;
            v=reg.exec(o.id);
            if(v)p.id=v[1];
        }
        f=o.classList.contains('api')||o.classList.contains('modal');//('api'in p);
    }while(!f&&o.nodeName!='FORM'&&(o=o.parentNode)&&!('tbl'in p)&&o.nodeName!='BODY'&&!o.classList.contains('fb-win'));
    if(!all&&!f)return 0;
    console.log(p,Object.keys(p));
    //if (Object.keys(p).length < 2)return 0; // если найден только id элемента, не обрабатываю
        //if (Object.keys(p).length == 2 && 'tbl' in p && 'id' in p)return 0; //  не обрабатываю
    if(!('api'in p))p.api='/api.php';
    return p;
}

function LoadInput(t,v){
    t.onclick=null;
    var id=0, tbl='';
    var n=t;
    do{
        //console.log(n);
        if(n.getAttribute('data-tbl'))tbl=n.getAttribute('data-tbl');
        if(n.nodeName=='TR'&&t.getAttribute('id')){id=n.id.slice(1);break}
        if(n.getAttribute('data-id')){id=n.getAttribute('data-id');break}
    }while((n=n.parentNode) && (n.tagName!='BODY'));
    if(!id){alert('Ошибка определения id !');return false;}
    var b=getText(t);
    b=b.replace(/^[\s\,\.]*/, "").replace(/[\s\,\.]*$/, "");
    ajaxLoad(t,api+'?loadinput='+v+'&val='+encodeURIComponent(b)+'&id='+id+(tbl?'&tbl='+tbl:''));
}

function SendInput(t) {
    if (t.type == "number") t.setCustomValidity((/^[0-9]*$/.test(t.value) ? '' : t.ValidationMessage));
    if (!t.checkValidity())return false;
    var str;
    var url=buildUrl(t); if(!url)return false;
    if(t.tagName=='SELECT') str=encodeURIComponent(t.name)+'='+encodeURIComponent(t.options[t.selectedIndex].value);
    else if((t.tagName=='INPUT')&& ((t.type=='radio')|| (t.type=='checkbox'))){str=encodeURIComponent(t.name)+'='+(t.checked?encodeURIComponent(t.value):0);}
    else str=encodeURIComponent(t.name)+'='+encodeURIComponent(t.value);
    //console.log(api,id,tbl,str, api+'?ajax='+id+(tbl?'&tbl='+tbl:''));
    ajaxLoad(t,url,'', str);
    return false;
}

function Ok(t){
    //console.log(t);
    if(t.type=='checkbox' && t.parentNode && t.parentNode.nodeName=='LABEL')t=t.parentNode;
    addClass(t,'valid');
    removeClass(t,'invalid');
    setTimeout(function(){removeClass(t,'valid');},3000);
}

function fb_error(t){
    t.alt="Нет изображения";
    t.style.visibility="visible";
    var e=t.parentNode;
    e.style.height='200px';
}
/**
 * @param t IMG
 */
function fb_resize(t){
    if(!t.width&&!t.height){window.setTimeout(function() { fb_resize(t); }, 100 );return;}
    var w=t.width; var h=t.height;
    var addW=22; // padding 10+10 border 1+1
    var addH=22;
    var e=t.parentNode; // внешний контейнер
    //console.log(e,t);
    /*e.appendChild(document.createTextNode(' ('+w+'x'+h+')') ); // добавляю размер картинки снизу. ломается листание
     // получаю размер дополнительной информации в окне
     t.style.display='none'; // скрываю саму картинку
     var c=window.getComputedStyle(e, null);
     var addH=parseInt(c.height);
     t.style.display='block';*/
    var W=wW()-addW; var H=wH()-addH;
    if(w<W&&h<H){}
    else if(w<h){w=Math.ceil(w*H/h);h=H;}
    else {h=Math.ceil(h*W/w);w=W;}
    if(w>W){h=Math.ceil(h*W/w);w=W;}
    if(h>H){w=Math.ceil(w*H/h);h=H;}
    t.setAttribute("width", w);
    e.style.width=(w+addW)+'px';
    t.setAttribute("height", h);
    e.style.height=(h+addH)+'px';
    t.style.visibility="visible";
    getElementsByClass('dragbar',fb_modal,'DIV')[0].style.width=(parseInt(w)-45)+'px';
//console.log(e,"H=",H,", W=",W,", h=",h,", w=",w);
    h=Math.ceil((H-h)/2)+'px';
    w=Math.ceil((W-w)/2)+'px';
//console.log("h=",h,", w=",w);
    e.style.top=h;
    e.style.left=w;
    e.style.margin=0;
    e.style.overflowY='hidden';
}

var multi_image_win=0;
function openwind(fil){
    var a;
    if(fil.nodeName=='IMG'){a=fil.alt; fil=fil.getAttribute('data-src');}
    else if(fil.nodeName=='A'){a=fil.title;fil=fil.href;}else log("fil.nodeName=",fil.nodeName);
    if(multi_image_win){
        a="<img onclick='NextImg(event)' src='"+fil+"' onload='fb_resize(this)' onerror='fb_error(this)' style='visibility:hidden' alt='"+a+"'><i onclick='NextImg(event)'></i><i onclick='NextImg(event)'></i>";
        fb_win(a);
        window.setTimeout(function() { var t=fb_modal.getElementsByTagName('IMG')[0]; if(!t.width&&!t.height)fb_error(t); }, 1000 );
        NextImg(fil,!0);
    }else{
        a="<img onclick='fb_close()' src='"+fil+"' onload='fb_resize(this)' onerror='fb_error(this)' style='visibility:hidden' alt='"+a+"'/>";
        fb_win(a);
    }
    return false;
}

function NextImg(fil,prev){
    //console.log('NextImg ',fil, prev);
    var back=0;
    if(fil.type=='click'){
        var ev=fil||window.event;
        back=(ev.clientX<(wW()/2));
        fil=getEventTarget(fil);
        while(fil.tagName=='I')fil=fil.previousElementSibling;
    }
    var e;
    var s=(fil.src?fil.src:fil);
    var els=document.getElementsByTagName("IMG");
    var elsLen=els.length;
    var old='';
    var f=0;
    for (var i=0; i < elsLen*2; i++) {
        e=els[(i%elsLen)];
        var src=e.getAttribute('data-src'); if(!src)continue;
        var a=e.parentNode;
        if(f==0 && s==a.href){
            if(back){
                if(!old)continue;
                fil.style.visibility="hidden";
                fil.setAttribute("width", 'auto');
                fil.setAttribute("height", 'auto');
                fil.alt=old.title;
                fil.src=old.href;
                return !1;
            }
            f=1;
        }else if(f==1){
            if(prev){ // предзагрузка
                //console.log("предзагрузка",a.href);
                e=new Image();e.src=a.href;
            }else{
                //fb_close();openwind(a);
                if(a.nodeName=='A'){
                    fil.style.visibility="hidden";
                    fil.setAttribute("width", 'auto');
                    fil.setAttribute("height", 'auto');
                    fil.alt=a.title;
                    fil.src=a.href;
                    //fb_resize(fil);
                    //console.log(fil);
                }else{
                    s=a;
                }
                NextImg(fil,!0);
            }
            return !1;
        }
        old=a;
    }
    if(!prev)fb_close();
    return !1;
}

var _frm={/*загрузка файлов*/
    old_action:null,
    old_target:null,
    wait:null,

    drop:function(e){ // функции для загрузки изображения на сервер
        e=e||event;
        if(e.type=="dragleave"){
            removeClass(getEventTarget(e,'FORM'),'LoadFile');
            return false;
        }
        var f=getEventTarget(e,'FORM');
        if(e.type!="drop"){
            if(e.preventDefault){e.preventDefault();
                addClass(f,'LoadFile');
            }
            return false;
        }
        removeClass(f,'LoadFile');
        var dt=e.dataTransfer;
        //console.log('e=',e,'dt=',dt);
        if(!dt){console.log('Нет dt',e); return false;}
        var src,i;
        try{src=dt.getData("text/html")||dt.getData("URL")||dt.getData("text")||dt.getData("text/plain");}catch(e){src=null;} // url = dt.getData("text/uri-list");
        //console.log("dt=",dt);
        //console.log(dt.getData("text/html"), dt.getData("URL"), dt.getData("text"), dt.getData("text/plain") );
        var url=buildUrl(f,1);
        if(src){
            if(e.stopPropagation)e.stopPropagation();
            else event.cancelBubble=true;
            if(e.preventDefault)e.preventDefault();
            f=strip_tags(src); if(f.length>10){var o=getObj('submit_file');if(o)o=o.form.comment; if(o)o.value=f;}
            // todo отслеживать конструкции <a href=><img src=></a> и если в href картинка, то брать её, а не из src
            var re0=new RegExp ("src=[\'\"]([^\'\"]+)[\'\"]","ig"); f=re0.exec(src); // картинок может быть несколько! и могут быть href на изображение!!!
            if(f==null){fb_err("Нет изображения!");return false;}
            while(f!=null){
                //console.log("src=",f);
                f= f[1];
                ajaxLoad('img_block',url+(url.indexOf('?')>=0?'&':'?')+'link='+f,'Загрузка '+f+' на сервер...');
                f=re0.exec(src);
            }
            return false;}
        if(dt.files){
            var files=dt.files;
            dt.dropEffect="copy";
            for(i=0; i < files.length; i++){
                //console.log("files","[",i,"]=",files[i]);
                //console.log("Processing IMAGE: ", file, ", ", file.name, ", ", file.type, ", ", file.size);
                _frm.UpLoad('img_block',url+'&img=',files[i],'Загрузка на сервер...');
            }
            e.stopPropagation();
            e.preventDefault();
            return false;
        }
        if(dt.items){
            var data=dt.items;
            for (i = 0; i < data.length; i += 1) {
                //console.log("data","[",i,"]=",data[i]);
                if ((data[i].kind == 'file') /*&& (data[i].type.match('^image/'))*/) {
                    _frm.UpLoad('img_block',url+'&img=',data[i].getAsFile(),'Загрузка на сервер...');
                    /*var img = new Image();
                     img.src = window.createObjectURL(data[i].getAsFile());
                     element.appendChild(img);*/
                }
            }
            e.stopPropagation();
            e.preventDefault();
            return false;
        }
        fb_err("Нет изображения!");return false;
    },

    load_frame:function(e){ // обработчик onload и onerror на frame
        //log("load_frame:",_frm.old_action);
        ajaxLoad('img_block','/api.php?del_img');getObj('file').value='';
        if(_frm.old_action){
            var f=getObj('submit_file').form;
            f.action=_frm.old_action;
            f.target=_frm.old_target;
            _frm.old_action=null;
        }
    },
    change:function(e){ // обработчик onchange на file
        e.stopPropagation();
        e.preventDefault();
        var f=getEventTarget(e);
        var files = f.files;
        var url=buildUrl(f,1); if(!url)return false;
        for(var i=0;i<files.length;i++){
            //_frm.UpLoad('img_block','/api.php?img=',files[i]);
            _frm.UpLoad('img_block',url+(url.indexOf('?')>=0?'&':'?')+'img=',files[i]);
        }
        f.value="";
        return false;
    },
    load_file:function(e){ // обработчик onclick на кнопке загрузки
        var f=getObj('submit_file').form;
        //log("load_file:",f.action," -> ",'/api.php?load_file');
        _frm.old_action=f.action;
        _frm.old_target=f.target;
        f.action='/api.php?load_file';
        f.target='upload_frame';

    },

    UpLoad:function(obj,url,file,defMessage){
        //if (defMessage) document.getElementById(obj).innerHTML=defMessage;
        var name= file.fileName || file.name;
        if (!file.type || !file.type.match(/image.*/)){
            alert('Загружаемый файл '+name+' имеет недопустимый тип '+file.type+'!');
            return;
        }
        _frm.send(obj, url+name, file);
    },

    set_event:function(e){
        addEvent('upload_frame', 'load', _frm.load_frame);
        addEvent('upload_frame', 'error', _frm.load_frame);
    },

    GetImg:function(fil0){
        var im=document.images;
        var imLen=im.length;
        var fil=document.location.protocol+'//'+window.location.hostname+fil0;
        var filLen = fil.length;
        for (var i=0; i < imLen; i++) {
            //console.log(fil, im[i].src, im[i].getAttribute('data-src'));
            if(im[i].src.substr(0,filLen)==fil )return im[i];
            var t;
            if(t=im[i].getAttribute('data-src'))if( t==fil0 )return im[i];
        }
        console.error('Не найдена картинка ',fil);
        return null;
    },

    AltImg:function(fil,alt){ // возвращает или устанавливает Alt-текст к картинке
        var o=_frm.GetImg(fil);
        if(o){
            //console.log(alt,typeof alt);
            if(typeof alt == 'string')o.alt=alt;
            if(o)return o.alt;
        }
        return '';
    },

    edit_desc:function (fil){
        fb_win('Описание файла<br><form method="post" action="/api.php?desc_img='+fil+'" onsubmit="_frm.AltImg(\''+fil+'\',this.desc.value);return SendForm(\'answer\',this);">'+
            '<textarea name="desc" cols="80" rows="2" autofocus>'+_frm.AltImg(fil)+'</textarea><br><input value="Сохранить" class="button" type="submit">'+
            '</form>',1);
        return false;
    },

    send:function(obj, url, file, base64 ){
        if(typeof(obj)!="object")obj=document.getElementById(obj); if(obj && !this.wait){
            //<progress value="0">Загружено 0 объема файла</progress>
            /*
             this.wait=document.createElement('progress');
             this.wait.setAttribute('value','0');
             this.wait.innerHTML="Загружено 0% объема файла";
             */
            this.wait=document.createElement('img');
            //this.wait.setAttribute('class','left');
            this.wait.setAttribute('src','/images/loading.gif');

            obj.appendChild(this.wait);
        }
        var fileSize = parseInt(('size' in file ? file.size : file.fileSize )/100000)/10;
        if(fileSize>1){
            //alert('Загружаемый файл '+name+' имеет размер '+fileSize+' Мегабайт.\nДопустимо не более 2 Мб!');
            this.resizeImage(obj,url,file);
            return;
        }

        var ajaxObj = new XMLHttpRequest();
        ajaxObj.onreadystatechange=ajaxCallBack(obj,ajaxObj,null);
        ajaxObj.open('POST', url, true);
        //try{ajaxObj.setRequestHeader("Referer", encodeURIComponent(window.location.href));}catch(e){console.log("Не смог установить Referer");}
        if(base64){
            ajaxObj.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        }else{
            ajaxObj.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            //ajaxObj.setRequestHeader("X-File-Name", encodeURIComponent(name));
            ajaxObj.setRequestHeader("Content-Type", "application/octet-stream");
        }
        if (ajaxObj.upload && ajaxObj.upload.addEventListener) {
            ajaxObj.upload.obj=obj;
            ajaxObj.upload.addEventListener( // Создаем обработчик события в процессе загрузки.
                'progress',
                function(e) {
                    if (e.lengthComputable) {
                        console.log('Загружено ', e.loaded,' из ',e.total, e);
                        // e.loaded — сколько байтов загружено.
                        // e.total — общее количество байтов загружаемых файлов.
                        _frm.Progress(e.loaded/e.total*100,e);
                    }else{
                        console.log('Загружено ',e.loaded,'байт', t);
                    }
                },
                false
            );
            ajaxObj.upload.onloadend = function(e) {
                console.log('Загружено ',e.loaded,'байт',e);
                _frm.Progress(100,e);
            }
        }
        ajaxObj.send(file); // data:image/jpeg;base64,
    },
    Progress:function(percent, e){
        var t=getEventTarget(e);
        percent=Number(percent).toFixed();
        var p=t.obj;
        while(p&&p.tagName!='PROGRESS')p=p.nextElementSibling;
        if(p){p.value=percent;p.innerHTML="Загружено "+percent+" объема файла";}//else console.error("Нет PROGRESS в "+t.obj);
    },
    resizeImage:function(obj,url,file) {
        var reader = new FileReader();
        reader.onloadend = function() {
            var tempImg = new Image();
            tempImg.src = reader.result;
            tempImg.onload = function() {
                var MAX_WIDTH = 640;
                var MAX_HEIGHT = 640;
                var tempW = tempImg.width;
                var tempH = tempImg.height;
                if (tempW > tempH) {
                    if (tempW > MAX_WIDTH) {
                        tempH *= MAX_WIDTH / tempW;
                        tempW = MAX_WIDTH;
                    }
                } else {
                    if (tempH > MAX_HEIGHT) {
                        tempW *= MAX_HEIGHT / tempH;
                        tempH = MAX_HEIGHT;
                    }
                }

                var canvas = document.createElement('canvas');
                canvas.width = tempW;
                canvas.height = tempH;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(this, 0, 0, tempW, tempH);
                canvas.toBlob(function(blob){_frm.send(obj, url, blob );}, "image/jpeg", 0.95); // JPEG at 95% quality
                //получаем данные в виде base64, второй параметр задает качество (от 0 до 1)
                //var dataURL = canvas.toDataURL("image/jpeg",1);
                //_frm.send(obj, url+name, dataURL, !0 );
            }
        };
        reader.readAsDataURL(file);
    }

};

// ToBlob
(function(a){var b=a.HTMLCanvasElement&&a.HTMLCanvasElement.prototype,c=function(){try{return!!(new Blob)}catch(a){return!1}}(),d=a.BlobBuilder||a.WebKitBlobBuilder||a.MozBlobBuilder||a.MSBlobBuilder,e=(c||d)&&a.atob&&a.ArrayBuffer&&a.Uint8Array&&function(a){var b,e,f,g,h,i;a.split(",")[0].indexOf("base64")>=0?b=atob(a.split(",")[1]):b=decodeURIComponent(a.split(",")[1]),e=new ArrayBuffer(b.length),f=new Uint8Array(e);for(g=0;g<b.length;g+=1)f[g]=b.charCodeAt(g);return i=a.split(",")[0].split(":")[1].split(";")[0],c?new Blob([e],{type:i}):(h=new d,h.append(e),h.getBlob(i))};a.HTMLCanvasElement&&!b.toBlob&&(b.mozGetAsFile?b.toBlob=function(a,b){a(this.mozGetAsFile("blob",b))}:b.toDataURL&&e&&(b.toBlob=function(a,b){a(e(this.toDataURL(b)))})),typeof define!="undefined"&&define.amd?define(function(){return e}):a.dataURLtoBlob=e})(window);



function addRow(o){
    //if(typeof(o)!="object")o=document.getElementById(o);
    //var o = o.firstChild;
    console.log('clone: ',o);
    //o.parentNode.appendChild(o.cloneNode(true));
    o.parentNode.insertBefore(o.cloneNode(true), o.nextElementSibling);
    return !1;
}

/*window.onerror = function (msg, url, line) {
 try {
 if (msg && url && typeof url != "undefined") if (url.indexOf(window.location.hostname) >= 0) {
 var data = new FormData();
 data.append('msg', msg);
 data.append('url', url);
 data.append('ref', document.location.href);
 data.append('line', line);
 var xhr = new XMLHttpRequest();
 xhr.open('POST', '/jserr.php', false);
 xhr.send(data);
 return true;
 }
 console.log(msg, url, line);
 } catch (e) {
 console.error(e);
 }
 return true;
 };*/

function Banner_Fix(id){
    var cm=(document.compatMode=="CSS1Compat"),de=document.documentElement,db=document.body;
    var o=getObj(id); var o1=o; if(!o1/*||wH()<680*/)return;
    var pos=0; while(o1.offsetParent){ pos+=parseInt(o1.offsetTop); o1=o1.offsetParent;}// позиция банера на странице
    o.fix=0;

    var v2_scroll = function(o,pos){ return function () {
        var st = self.pageYOffset || cm && de.scrollTop || db.scrollTop; // на сколько прокрутили
        if(o.fix<0)return;
        if(st>pos){
            if(!o.fix){
                var c=window.getComputedStyle(o, null);
                o.parentNode.style.height= c.height;
                addClass(o,'fix');
                o.fix=1;
                if(c.position!='fixed'){o.fix=2;o.style.top='2px';}
            }
        }else if(st<pos){
            if(o.fix){removeClass(o,'fix');
                o.parentNode.style.height='auto';
                if(o.fix==2)o.style.top=pos+'px';
                o.fix=0;}
        }
    };
    }(o,pos);
    addEvent(window, 'scroll', v2_scroll);
    v2_scroll();
}

onDomReady(function(){oef();Banner_Fix("Basket");});

function log(e){if("console" in window && "log" in window.console) try{window.console.log(e);}catch(e){} }



function oef_d(e){
    e=e||window.event;
    var o = getEventTarget(e);
    var b=(e.which?(e.which < 2):(e.button < 2));
    var u;
    //console.log(b,o,o.classList);
    //alert('stop '+ o.nodeName);
    if(b&&o.classList.contains('confirm')){
        isConfirm(e);
    }else if(b&&o.classList.contains('modal') && !LoadMain(e) ){

    }else if(b&&o.nodeName=='A'&&o.classList.contains('ajax') ){
        ajaxLoad((o.getAttribute('target')?o.getAttribute('target'):''),o.href);
    }else if(b && (u=o.getAttribute('data-src')) ){
        if(o.nodeName=='A'){
            o.href=u;
            o.removeAttribute('data-src');
            return; // перейти по ссылке
        }else if(o.nodeName=='IMG'){
            openwind(o);
        }else{
            return;
        }

    }else if(b && (u=buildParam(o)) ){
        var url=u['api'];
        for(var k in u)if(k!='api')url=url+(url.indexOf('?')>=0?'&':'?')+k+'='+encodeURIComponent(u[k]);
        console.log(url);
        ajaxLoad('', url );

    }else if(!document.getElementById('main')|| o.nodeName!='A'){
        return;
    // далее обрабатываю только <a ... при наличии блока id='main'
    }else if(o.pathname.indexOf('function')>=0 && o.onclick==null  && !LoadMain(e) ){

    }else if(o.pathname.indexOf('/example/')>=0 && o.pathname.indexOf('/index.')<0 && o.pathname.substring(lp.length-1)!='/' && o.onclick==null && !InWin(e)){

    }else{
        return;
    }
    e.preventDefault ? e.preventDefault() : (e.returnValue=false);
    e.stopPropagation ? e.stopPropagation() : (event.cancelBubble=true);
}

function isConfirm(e){
    e=e||window.event;
    var o = getEventTarget(e);
    if(e.stopPropagation)e.stopPropagation();
    else event.cancelBubble=true;
    if(e.preventDefault)e.preventDefault();
    if(e && e.shiftKey || confirm((o.title?o.title:'Удалить')+'?'))ajaxLoad((o.getAttribute('target')?o.getAttribute('target'):''),o.href);
    return false;
}

var _scroller;
_scroller = function () { // scroller
    return{
        speed:20, /*скорость, чем больше значние, тем медленнее движение*/
        direct:-1,/* -1 - движение влево, +1 - вправо*/
        position:0,
        t:null,
        // Инициализация скроллера
        init: function () {
            var el;
            // Установка обработчика колесика мыши
            el = document.getElementById('scroller_container');
            if(el){
                _scroller.addEvent(el, 'mousewheel', _scroller.wheel);
                _scroller.addEvent(el, 'DOMMouseScroll', _scroller.wheel);
                _scroller.timer(_scroller.direct); // запускаю скроллер
            }else console.error("Нет #scroller_container!");
        },

        // Обработчик колесика мыши
        wheel: function (e) {
            _scroller.stop();
            e = e ? e : window.event;
            /*var wheelElem = e.target ? e.target : e.srcElement;*/
            var wheelData = e.detail ? e.detail * -1 : e.wheelDelta / 40;

            // В движке WebKit возвращается значение в 100 раз больше
            if (Math.abs(wheelData) > 100) {
                wheelData = Math.round(wheelData / 100);
            }
            //_scroller.scroll(wheelData*10);
            _scroller.direct=wheelData>0?1:-1;
            _scroller.timer(_scroller.direct);
            if (window.event) {
                e.cancelBubble = true;
                e.returnValue = false;
                e.cancel = true;
            }
            if (e.stopPropagation && e.preventDefault) {
                e.stopPropagation();
                e.preventDefault();
            }
            return false;
        },

        // Функция скроллера
        scroll: function (wheel) {
            var el = document.getElementById('scroller_container').firstElementChild;
            var o, oi, width;
            _scroller.position += wheel;
            if (wheel>0) {
                if (_scroller.position >= 0) { // берем последнюю картинку и вставляем ёё в начало
                    // В этот момент можно подгружать более левую картинку и удалить последнюю
                    o=el;//.firstElementChild; // контейнер с картинками
                    oi=o.lastElementChild; // последняя картинка вместе с анкором
                    width=oi.firstElementChild.clientWidth; // размер картинки
                    o.insertBefore(oi,o.firstElementChild);
                    _scroller.position-=width;
                }
            }
            else {
                o=el;//.firstElementChild; // контейнер с картинками
                oi=o.firstElementChild; // первая картинка вместе с анкором
                width=oi.firstElementChild.clientWidth; // размер картинки
                if(_scroller.position < -width){ // если картинка ушла влево из зоны видимости переношу её в конец списка
                    // В этот момент можно подгружать следующую картинку и удалить первую
                    o.appendChild(oi);
                    _scroller.position+=width;
                }
            }
            el.style.left = _scroller.position + 'px';
        },

        // Таймер скроллера
        timer: function (wheel) {
            _scroller.stop();
            _scroller.t = setInterval("_scroller.scroll(" + wheel + ");", _scroller.speed);
        },

        // Остановка скроллера
        stop: function () {
            if (_scroller.t != null) {
                clearInterval(_scroller.t);
                _scroller.t = null;
            }
        },

        // назначить обработчик события
        addEvent:function(el, evType, fn, useCapture) {
            if (el.addEventListener === "function") {
                el.addEventListener(evType, fn, useCapture);
            }else if (el.attachEvent) {
                var r = el.attachEvent('on' + evType, fn);
            }else el['on' + evType] = fn;
        }
    };
}();

// распахивание модального окна
var whOpen = { // распахивание окна
    o: null,
    h:0,
    h0:10,
    mh:0,
    t:null,
    hh: function () {
        if(this.h<this.mh){
            this.h+=this.h0;if(this.h>this.mh)this.h=this.mh;
            this.o.style.height=this.h+'px';
        }else{
            clearInterval(this.t); this.t=null;
        }
    },
    start:function(){
        var o=getElementsByClass('fb-win1',null,'DIV');
        if(o)o=o[o.length-1];
        var c=window.getComputedStyle(o, null);
        this.mh=parseInt(c.height);
        c=o.style;
        c.height=this.h+'px';
        c.overflow='hidden';
        this.o=o;
        this.h=this.h0;
        if(this.t)clearInterval(this.t);
        this.t=setInterval("whOpen.hh()",7);
        o.previousSibling.onclick=function(){whOpen.close(); return !1;};
        o=o.firstChild;
        removeEvent(document, "keydown", fb_close);
        addEvent(document, "keydown", whOpen.close);
        o.onclick=function(){whOpen.close(); return !1;};
    },
    hc: function () {
        if(this.h>this.h0){
            this.h-=this.h0;
            this.o.style.height=this.h+'px';
        }else{
            clearInterval(this.t); this.t=null;
            fb_close();
        }
    },
    close:function(e) {
        if(typeof(e)=='object'){
            e=e||window.event;
            if(e.type=='keydown'){
                if(e.keyCode==27)whOpen.close(); // Esc
                return;
            }
        }
        removeEvent(document, "keydown", whOpen.close);
        if(this.t)clearInterval(this.t);
        this.t=setInterval("whOpen.hc()",7);
    }
};

function setCursorPosition(pos, e) {
    e.focus();
    if (e.setSelectionRange) e.setSelectionRange(pos, pos);
    else if (e.createTextRange) {
        var range = e.createTextRange();
        range.collapse(true);
        range.moveEnd("character", pos);
        range.moveStart("character", pos);
        range.select()
    }
}

/** на все <input class="mask">
 * @param e
 */
function mask(e) {
    var matrix = this.placeholder,// .defaultValue
        i = 0,
        def = matrix.replace(/\D/g, ""),
        val = this.value.replace(/\D/g, "");
    def.length >= val.length && (val = def);
    matrix = matrix.replace(/[_\d]/g, function(a) {
        return val.charAt(i++) || "_"
    });
    this.value = matrix;
    i = matrix.lastIndexOf(val.substr(-1));
    i < matrix.length && matrix != this.placeholder ? i++ : i = matrix.indexOf("_");
    setCursorPosition(i, this)
}

/*
document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.which === 72) {
        // open help widget
    }
});
*/
