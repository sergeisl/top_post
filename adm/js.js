"use strict";
var _cs = {// выпадающий список
    // таймер
    c: null,
    d: function () {
        var a = (document.all) ? document.all : document.getElementsByTagName("INPUT");
        var aLength = a.length;
        var s;
        for (var i = 0; i < aLength; i++) {
            if (/*a[i].nodeName == "INPUT" &&*/ a[i].getAttribute("href")) {
                //console.log("cs:", a[i].getAttribute('name'), a[i].getAttribute('href'));
                a[i].setAttribute("autocomplete", "off");
                a[i].setAttribute("autofocus", true);
                addEvent(a[i], "keyup", _cs.ch);
                addEvent(a[i], "focus", _cs.focus);
                if (a[i].getAttribute('after')) {
                    addEvent(a[i], "blur", _cs.blur);
                    //console.log("after:", a[i].getAttribute('name'), a[i].getAttribute('after'));
                }
                s = eval('a[i].form.' + a[i].name + '_cs');
                if (!s) {
                    s = document.createElement("select");
                    s.setAttribute("name", a[i].name + "_cs");
                    a[i].form.appendChild(s);
                    s.setAttribute('size', 5);
                    s.style.visibility = 'hidden';
                    s.style.position = 'absolute';
                    s.style.zIndex = 9999;
                    addEvent(s, "change", _cs.cha);
                    addEvent(s, "keyup", _cs.chs);
                    addEvent(s, "dblclick", function (e) {
                        console.log('dblclick:');
                        _cs.cha(e);
                        _cs.chd();
                    });
                }
            }// else continue;
            //addEvent(a[i], "mouseover", _cs.s);
            //addEvent(a[i], "mouseout", _cs.h);
        }
    },
    /*dd: function(e){
     e.stopPropagation(); e.preventDefault();
     },*/
    cho: null, // прошлое значение переменной
    cht: null, // объект для которого выпадает список
    chi: null, // текущий select
    cha: function (e) {
        if (_cs.chi)_cs.cht.value = _cs.cho = _cs.chi.options[_cs.chi.selectedIndex].text;//.value;
    },
    chd: function () {// удаление списка и выбор элемента
        _cs.chi.style.visibility = 'hidden'; // спрячем select
        _cs.cht.focus();
        var o;
        if (_cs.cht.value && (o = _cs.cht.getAttribute('after')))eval(o);
        _cs.cht = null;
    },
    ch: function (e) {// отпускание клавиши в input для которого нужно сделать select
        var d = getEventTarget(e);
        var f = (e.keyCode == 40 || e.keyCode == 13);
        if (_cs.cht == null) {
            if (!f && d.value.length < 3)return;
            _cs.cht = d;
            _cs.chi = null;
        }
        if (_cs.cht.readOnly)return;
        var s, y, x;
        if (_cs.chi == null) {
            s = eval('d.form.' + d.name + '_cs');
            var c = window.getComputedStyle(d, null);
            s.style.width = parseInt(c.width) + "px";
            var dd=getOffset(d,!0);
            //var wd=getOffset(fb_modal.firstElementChild.nextElementSibling); // относительно модального окна
            s.style.top = dd.top/*-wd.top*/ + d.clientHeight + 1 + "px";
            s.style.left = dd.left/*-wd.left*/ + "px";
            _cs.chi = s;
        }
        if (e.keyCode == 40 && _cs.chi.length > 0) {
            s = _cs.chi;
            s.focus();
            s.selectedIndex = 0;
            _cs.cha();
            return;
        } // Down
        if (_cs.cho == d.value)return; // если ничего не изменилось не "замучить" сервер
        console.log('CH:',_cs.cho ,'~', d.value);
        _cs.cho = d.value;
        if (_cs.c) {
            clearTimeout(_cs.c);
            _cs.c = null;
        }
        if (!f && _cs.cho.length < 3) {
            _cs.chd();
            return;
        }
        _cs.c = window.setTimeout('_cs.chl()', 1000);  // загружаю через 1 секунду после последнего нажатия клавиши
    },
    chs: function (e) {// // вызывается при нажатии клавиши в select
        var d = getEventTarget(e); // объект для которого вызывно
        if (e.keyCode == 13 || e.keyCode == 27) { // Enter
            _cs.chd();
            return;
        }
        if (e.keyCode == 38 && d.selectedIndex == 0) { // Up
            /*_cs.cht.focus();
             _cs.chi.style.visibility = 'hidden'; // спрячем select
             _cs.cht=null;*/
        }
    },
    chl: function () {// вызывается через 1 секунду после последнего нажатия клавиши
        console.log('chl:');
        _cs.c = null;
        var o = _cs.chi;
        o.options.length = 0;
        ajaxLoad(o, _cs.cht.getAttribute('href') + encodeURIComponent(_cs.cho), '', '', '');
        //o.style.visibility = 'visible';
    },
    focus: function (e) {/* вызывается при получении фокуса поля*/
        var d = getEventTarget(e);
        d.value=trim(d.value);
        _cs.cho = d.value;
        this.select();
    },
    blur: function (e) {/* вызывается при потере фокуса поля*/
        console.log("blur:", e);
        var d = getEventTarget(e);// ==cht
        d.value=trim(d.value);
        _cs.cho=trim(_cs.cho);
        if ((_cs.cho == d.value) || (d.value==''))return; // если ничего не изменилось не "замучить" сервер
        //if (_cs.cht.value && (o = _cs.cht.getAttribute('after')))eval(o);
        console.log("blur:", e, _cs.cho, "<>", d.value, _cs.cho.length, d.value.length);
        //alert( _cs.cho, "\n", d.value);
        ajaxLoad(d.form, d.getAttribute('href') + encodeURIComponent(d.value), '', '', '');
        //if(parseInt(d.value)<1)return;
    }

};

function w_discount(){// вызывается после ввода скидки
    var price = parseInt(getText('price'));
    var kol = parseInt(document.work.kol.value); if(isNaN(kol))kol=1;
    var discount = parseFloat(document.work.discount.value);  if(isNaN(discount))discount=0;
    //alert(price);
    price=price*(100-discount)/100; price = (price<5 ? Math.ceil((price-0.01)*10)/10 : Math.ceil(price-0.01));
    document.work.price2.value=price;
    updateObj('summ',kol*price);
    var o=getObj('comment').parentNode.parentNode;
    console.log(o,discount);
    if(discount)removeClass(o,'hide');
    else addClass(o,'hide');
}

function w_price(){// вызывается после ввода цены со скидкой
    var price=parseInt(getText('price'));
    var kol=parseInt(document.work.kol.value); if(isNaN(kol))kol=1;
    var price2=parseInt(document.work.price2.value); if(price2<5)price2 = parseFloat(document.work.price2.value);
    //console.log("price=",price,"price2=",price2);
    var discount=price>0.01?Math.floor((price-price2)/price*10000000+0.4)/100000:0;
    document.work.discount.value=discount;
    updateObj('summ',kol*price2);
    var o=getObj('comment').parentNode.parentNode;
    console.log(o,discount);
    if(discount)removeClass(o,'hide');
    else addClass(o,'hide');

}

function w_tovar(t){// вызывается после выбора товара из select
    var re1=new RegExp("\\-\\ (\\d+)руб","gim");
    var price=re1.exec(t.options[t.selectedIndex].text);
//console.log("w_tovar: t=",t.options[t.selectedIndex].text,"price=",price);
    if(price!=null){
        price=price[1];
        updateObj('price',price);
        var kol=parseInt(document.work.kol.value); if(isNaN(kol))kol=1;
        updateObj('summ',kol*price);
    }}

function afterTovar(){
    var o=document.work.tovar_cs;
    if(o){
        o= (o.selectedIndex>=0 ? o.options[o.selectedIndex].value : 0 );
        if(o>0){
            var k=document.work.klient_cs;
            k= (k&&k.selectedIndex>=0 ? k=k.options[k.selectedIndex].value : 0 );
            ajaxLoad('work', '/shop/api.php?tovar='+o+(k>0?'&user='+k : ''));
        }
    }
}

function afterKlient(){
    //log("afterKlient:");
    var o=document.work.tovar_cs;
    var k=document.work.klient_cs;
    if(o&&k)if(o.selectedIndex>=0&&k.selectedIndex>=0){
        o=o.options[o.selectedIndex].value;
        k=k.options[k.selectedIndex].value;
	if(o>0&&k>0)ajaxLoad('work', '/shop/api.php?tovar='+o+'&user='+k);
    }
}

/**
 *
 * @param o - this.form
 * @param anonim - 1 - анониму нельзя
 * @returns {*}
 */
function sendSale(o,anonim){
    if(getValue(o.klient_cs)<1) {
        if( anonim ){
            //o.klient.setCustomValidity("Продажа абонемента анонимному клиенту невозможна!");
            alert("Продажа абонемента анонимному клиенту невозможна!");
            return false;
        }else if(trim(getValue(o.klient))!=''){
            return false; // возможно не внесен клиент, но не выбран в select или ещё не вернулся ответ от сервера
        }
    }
    //o.klient.setCustomValidity("");
    return SendForm('work',o);
}

function LastKlient(){
    return ajaxLoad(document.work,'/shop/api.php?lastklient');
}

function w_type(){
    var t=document.tovar.type;
    var o=t.options[t.selectedIndex].value;
    if(o==0||o==3)updateObj('isrok','Заказ, штук:');
    else if(o==2) updateObj('isrok','Срок действия, мес:');
    else getObj('isrok').parentNode.style.display='none';
}

function w_gr(){
    var t=document.tovar.gr;
    if(t){
        var o=t.options[t.selectedIndex].value;
        if(o==0||o==3)updateObj('isrok','Заказ, штук:');
        else if(o==2) updateObj('isrok','Срок действия, мес:');
        else getObj('isrok').parentNode.style.display='none';
    }
}

/**
 * @return {boolean}
 */
function ShowKlient(f){
    if(f.klient_kart && f.klient_kart.value)
        ajaxLoad('','/shop/api.php?show&kart='+f.klient_kart.value);
    else if(f.klient_cs && f.klient_cs.value)
        ajaxLoad('','/user/api.php?ushow='+f.klient_cs.value);
    else {alert('Не выбран клиент!');return false;}
}


function Visa(s){
    fb_win('<br><form method="post" action="/api.php" onsubmit="return SendForm(\'answer\',this);">'+
        '<label>Сумма списания с банковской карты: <input type="number" name="visa" value="'+s+'" onfocus="this.select()"></label><br>'+
        '<br class="clear"><input value="Сохранить" type="submit" class="button right" style="width:auto;"></form>',1);
    return false;
}

function editon(t,id){
    t.style.display='none';
    var o=document.createElement('img');
    o.setAttribute('src','/images/loading.gif');
    t.parentNode.insertBefore(o,t);
    LoadScript("/ckeditor/ckeditor.js",function(){ removeID(o); CKEDITOR.replace( id ); });
    return false;
}
function editSave(t){
    if(typeof CKEDITOR == 'object'){
        var instance;
        for ( instance in CKEDITOR.instances )
            CKEDITOR.instances[instance].updateElement();
        /*var editor = CKEDITOR.instances.description;
         getObj('description').value=editor.getData();*/
    }
}
