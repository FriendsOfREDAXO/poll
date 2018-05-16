$(document).on('rex:ready', function () {
    $('.rex-poll .progress-bar').each(function(){
        $(this).css('width',$(this).data('percent') + '%');
    })
});