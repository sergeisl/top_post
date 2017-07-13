/**
 * Created by Zver on 13.07.2017.
 */
var router = new HashRouter();

function open_modal(id) {
    let modals = document.getElementById(id);
    if(modals != undefined){
        document.body.style.overflowY = 'hidden';
        modals.classList.add('modal_visible');
    }
    return false;
}
function close_modal(id) {
    history.replaceState(3, "Title 2", "/");
    document.body.style.overflowY = 'auto';
    let modals = document.getElementById(id);
    modals.classList.remove('modal_visible');
}

router.add('m', function (params) {
    open_modal(params.id);
});

function page(page, cur) {
    $('.load_page').css({'display':'inline-flex'});
    $('#next_page_but').remove();
    $('#prev_page_but').remove();
    $.ajax({
        url: 'https://sp2all.ru/api/getSuppliers/?&page='+page+'&format=json',
        type: 'get',
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(data){
            console.log(data)
            if(data){
                var str = '';
                data.items.forEach(function(item){
                    var imgs1 = '';
                    item.imgs.forEach(function(img) {
                        if (img[0]) {
                            imgs1 += '<li><img src="https://sp2all.ru/' + img[0] + '"></li>';
                        }
                    });
            str += '  ' +
                '<div class="row__col row__col_xs_12 row__col_sm_6 row__col_lg_3">' +
                    '<div class="item">  ' +
                        '<a href="?page='+page+'#m?id='+item.id+'" data-id="'+item.id+'" class="noLink">'+item.title+'</a>' +
                        '<br><br>' +
                        '<div>' +
                            '<img src="https://sp2all.ru/'+item.imgs[0][0]+'" class="imgs">' +
                        '</div>' +
                        '<div class="modal modal_theme_grey " id="'+item.id+'">'+
                            '<div class="modal__wrapper">'+
                                '<div class="modal__dialog">' +
                                    '<div class="modal__close" onclick="return close_modal('+item.id+');"></div>'+
                                    '<ul class="bxslider">'+imgs1+
                                    '</ul>'+
                                    ' <p style="">'+item.desc+'</p>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</div>' +
                '</div>'

                });
                if(cur){
                    $('.row').append(str).appendTo('.row');
                } else {
                    $('.row').prepend(str).appendTo('.row');
                }
            }
            $('.load_page').css({'display':'none'});

            $('.next_page')
                .append('<a href="?page='+(pagen)+'" onclick="pagen++;page(pagen, true);return false;" id="next_page_but">Ещё</a>')
                .appendTo('.next_page');

            if( parseInt(pagep)>2 ) {
                $('.prev_page')
                    .append('<a href="?page='+(pagep-1)+'" onclick="pagep--;page(pagep, false);return false;" id="prev_page_but">Ещё</a>')
                    .appendTo('.prev_page');
            }
            $('.bxslider').bxSlider({
                minSlides: 1,
                maxSlides: 4,
                slideWidth: 200,
                slideMargin: 10
            });
        }
    });

}
function get_reviews(id) {
    $.ajax({
        url: 'https://sp2all.ru/api/login?login=wertu&password=12345678',
        type: 'get',
        dataType: 'json',
        success: function(data){
            console.log(data)
            if(data) {
                $.ajax({
                    url: 'https://sp2all.ru/api/Karma/?id='+id+'&type=user_supplier&act=get&format=json&uid='+data.id+'&key='+data.key+'&login='+data.login+'',
                    type: 'get',
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(data){
                        console.log(data)
                        if(data) {
                        }
                    }
                });
            }
        }
    });

}
$('body').on('click','.noLink',(e)=>{
    e.preventDefault();
    history.replaceState(3, "Title", e.target.href);
    open_modal($(e.target).data('id'));
});

$('body').on('click','.reviews',(e)=>{
    e.preventDefault();
    get_reviews($(e.target).data('id'));
});