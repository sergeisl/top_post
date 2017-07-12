var _u = {
    isBusy: function (t) {
        var v = t.value, e='';
        if (/^\s*\d/.test(v)) {
            e='Логин не может начинаться с цифры';
        }else if (/^[a-zA-Z0-9@\.\-\+]+$/.test(v) === false) {
            e='В логине должны быть только латинские буквы и "@",".","-","+"';
        }else if (v.length < 3 || v.length > 32) {
            e='Логин должен быть от 3 до 32 символов';
        }else if (/^[a-zA-z]{1}[a-zA-Z0-9@\.\-\+]{2,31}$/.test(v) === false) {
            e='Не верный логин';
        }else if (v.length > 2) {
            ajaxLoad('isbusy', '/user/api.php?isbusy&name=' + encodeURIComponent(v), 'Проверка...');
        }else{
            e='Длина не менее 3-х символов!';
        }
        return this.ret(e,'isbusy',t);
    },
    ret: function(e,id,t){
        if(e) {
            t.setCustomValidity(e);
            updateObj(id, e.indexOf('<')<0?'<span class="red">' + e + '</span>' : e );
            addClass(t, 'invalid');
            removeClass(t, 'valid');
            return false;
        }
        updateObj(id, '');
        t.setCustomValidity('');
        Ok(t);
        return true;
    },
    isPassword: function (t,n) { //n=true - регистрация
        var p = t.form.pass1.value, e='';
        if (p.length < 4){
            e='Длина не менее 4-х символов!';
        }else if(!n&&(p.length==0)){
            e='<span class="green">пароль не будет изменен</span>';
        }
        return this.ret(e,'ispass',t);
    },

    isEmpty: function (t,n) { //n=true - регистрация
        var o= t.parentNode.nextElementSibling;
        var m = getValue(t);
        if(!m){
            updateObj(o, '<span class="red">Обязательное поле!</span>');
            t.setCustomValidity("Обязательное поле не может быть пустым!");
        }else{
            updateObj(o, '<span class="green b">&radic;</span>');
            t.setCustomValidity("");
        }
    },

    isEmail: function (t,n) { //n=true - регистрация
//        var o= t.parentNode.nextElementSibling;
        var m = t.value,e='';
        if(!m){
            if(!t.required) return this.ret(e,'ismail',t);
            e='Обязательное поле!';
        }else if(IsMail(t, !t.required)){
            //t.setCustomValidity("");
            if(n){
                var id=getValue(t.form.id);
                ajaxLoad('ismail', '/user/api.php?isbusy&mail=' + encodeURIComponent(m)+(id?'&id='+id:''), 'Проверка...');
            }else if(!n && m == t.defaultValue){
                //updateObj('ismail','');
            }else {
                updateObj('ismail', '<span>До подтверждения нового E-mail возможности работы будут ограничены</span>');
            }
        }else{
            e='E-mail неверный!';
        }
        return this.ret(e,'ismail',t);
    },

    isTel: function (t,n) {
        var m = t.value,e='';
        var p=t.getAttribute('pattern');
        if(!p){
            p='+?[78][0-9]{10}';
            m=t.value=m.replace(/[\+\(\)\- ]+/g, '');
        }
        if(m.substr(0,1)=='8')t.value=m='7'+ m.substr(1);
        if(m.substr(0,2)=='+8')t.value=m='+7'+ m.substr(2);
        var re=new RegExp('^'+p+'$',"i");
        //console.log(m,re.exec(m),p);
        if(!m){ // пустое
            if(!t.required) {t.setCustomValidity(""); updateObj('istel', ''); return;}
            e='Обязательное поле!';
        }else if(re.exec(m) !== null){
            if(!n && m == t.defaultValue) {
                //updateObj('istel','<span class="check"></span>');
            }else{
                var id=getValue(t.form.id);
                ajaxLoad('istel', '/user/api.php?isbusy&tel=' + encodeURIComponent(m)+(id?'&id='+id:''), 'Проверка...');
            }
        }else{
            e='Неверный номер телефона!';
        }
        return this.ret(e,'istel',t);
    },

    Cost: function (t) {
        //if(t.type=="number") t.setCustomValidity((/^[0-9]*$/.test(t.value)?'':t.ValidationMessage));
        if(!t.checkValidity())return false;
        var p=BuildHttp(getObj('frm'));
        ajaxLoad('frm', '/api.php?cost&'+p);
    },

    isSend: function (v) {
        if (v.name.value == '') {
            alert("Не указан логин!");
            return false;
        }
        if (v.mail.value == '') {
            alert("Не указан электронный адрес!");
            return false;
        }
        var p = v.pass1.value;
        if (p.length < 4) {
            alert("Не указан пароль!");
            return false;
        }
        if (/^[a-zA-z]{1}[a-zA-Z0-9@\.\-\+]{2,31}$/.test(v.name.value) === false) {
            alert('Не верный логин!');
            return false;
        }
        if (!IsMail(v.mail, false)) {
            alert("Укажите корректный e-mail, на него придет запрос подтверждения!");
            return false;
        }
        if(verifyGrecaptcha==undefined){alert('Подтвердите, что Вы - не робот!');return false;}
//if(v.keystring.value.length<4) {alert("Введите текст с картинки!");return false;}
        return true;
    }
};
