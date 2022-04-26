import $ from 'jquery';

$(document).ready(function () {

    $(document).on('submit', "form.ajax-form-html", function (e) {
        if (typeof e != 'undefined' && typeof e.preventDefault == 'function') {
            e.preventDefault();
        }

        let _form = $(this);

        $.ajax({
            url: _form.attr('action') ? _form.attr('action') : window.location.href,
            type: _form.attr('method') ? _form.attr('method') : 'post',
            data: _form.serialize(),
            success: function (html) {
                let _block = _form.closest('[rel=ajax_block]');
                let blockFind = '';
                if (html === 'ok') {
                    _block.load(window.location.href + ' #' + _block.attr('id'), function () {
                        App.onReload();
                    });
                } else if ((blockFind = $(html).find('#' + _block.attr('id'))).length) {
                    _block.html(blockFind.eq(0).html());

                } else {
                    addAlert(_form, html);
                }

            },
            error: function (html) {
                addAlert(_form, html.responseText);
            }
        })

        return false;
    }).on('click', "a.ajax-delete-link", function (e) {
        if (typeof e != 'undefined' && typeof e.preventDefault == 'function') {
            e.preventDefault();
        }

        let _link = $(this);

        $.ajax({
            url: _link.attr('href'),
            type: 'get',
            success: function (html) {
                if (html === 'ok') {
                    let _block = _link.closest('[rel=ajax_block]');
                    _block.load(window.location.href + ' #' + _block.attr('id'), function () {
                        App.onReload();
                    });
                } else {
                    addAlert(_link, html);
                }
            },
            error: function (html) {
                addAlert(_link, html.responseText);
            }
        })

        return false;
    }).on('keyup', 'form.ajax-form-send-return', function (e) {
        if (e.keyCode === 13) {
            $(this).submit();
        }
    }).on('click', '.editable', function () {
        let _block = $(this);
        $.ajax({
            type: 'get',
            url: _block.attr('rel'),
            success: function (html) {
                _block.replaceWith(html);
                App.onReload();
            }
        });
    });

});