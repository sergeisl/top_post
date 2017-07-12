$(function(){
    page(1);
});
function page(page) {
    //https://sp2all.ru/api/registr/?&page='+page+'&uid=231587&format=json&email=wer@mail.ru&login=wer&pass1=1234
    $('.eee').css({'display':'inline-flex'});
    $('.www').css({'display':'none'});
    $.ajax({
        url: 'https://sp2all.ru/api/getSuppliers/?&page='+page+'&format=json',
        type: 'get',
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(data){
            console.log(data)
            if(data){
                data.items.forEach(function(item){
                    $('.addSuppliers')
                        .append('<div class="item">'+item.title+'<br><br><div><img src="https://sp2all.ru/'+item.imgs[0][100]+'"></div></div>')
                        .appendTo('.addSuppliers');
                });
            }
            $('.eee').css({'display':'none'});
            $('.www').css({'display':'inline-flex'});
        }
    });
}
