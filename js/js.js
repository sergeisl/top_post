$(function() {

    var e=document.createElement("link");
    e.href="https://fonts.googleapis.com/css?family=Cormorant+Garamond|Ubuntu:400,400i,500,500i";
    e.rel="stylesheet";
    document.getElementsByTagName('head')[0].appendChild(e);

    $('form.delivery input[type=radio]').on('change', function() {
        $('form.delivery div.content').hide().find('input').attr('disabled', 'disabled');
        $(this).parent().parent().find('div.content').show().find('input').removeAttr("disabled");
        console.log($(this).parent().parent().find('div.content').show().find('input'))
    });
    $('form.delivery input[type=radio]:checked').change();

    $('#price,#count').on('change', function() {
        var s = parseInt( $('#count').val()) * $('#price').find('option:selected').attr('data-price');
        $('#amount').html( s > 0 ? s+' руб' : 'формируется в зависимости<br>от объема');
    });

    $("#toTop").on("click", function () {
        $('body,html').animate({scrollTop: 0}, 1500);
    });

    $("#menu").on("click","a", function (event) {

        var el, id  = $(this).attr('href');
        el = $('#'+id.substr(1));
        if (el.length) {
            event.preventDefault();
            var top = el.offset().top;
            $('body,html').animate({scrollTop: top}, 1500);
            return!1;
        }
    });

    $(document).on('click', '.accordion h4 a', function() {
        var t = $(this).parent().parent().toggleClass('_up').hasClass('_up');
        $(this).parent().next().animate({ 'max-height': t ? 500 : 0 }, 100);;
        return!1;
    });

});
