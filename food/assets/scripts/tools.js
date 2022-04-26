import $ from 'jquery';

const alert_block = '<div class="alert alert-danger is-alert-block" role="alert">' +
    '    This is a danger alert—check it out!' +
    '</div>';

window.addAlert = function (element, message) {

    $('.alert').remove();
    $(element).after($(alert_block).html(message ? message : 'Неизвестная ошибка'));
}

$(document).on('form', 'submit', function () {
    $('.alert').remove();
});