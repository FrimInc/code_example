$(document).ready(function () {

    setValues = function (_picker) {
        let _val = _picker.find('input:checked').attr('value');
        $(_picker.attr('data-input')).val(_val);
        $(_picker.attr('data-all_div')).text(_val);
        $(_picker.attr('data-date_div')).text((_val = _val.split(' '))[0]);
        $(_picker.attr('data-time_div')).text(_val[1]);

    }

    $(document).on('click', '.ipol_tpicker_cont .day.selectable', function () {

        $('.ipol_tpicker_cont .day.selected').removeClass('selected');
        let _day = $(this);
        _day.addClass('selected');

        let _picker = _day.parents('.picker');
        let _data_day = _day.attr('data-day');

        _picker.find('.slot.selected').removeClass('selected');
        let _cSlot = _picker.find('.slot[data-day="' + _data_day + '"]');
        _cSlot.addClass('selected');

        if (_cSlot.find('input[date-isdef]:not([disabled])').length) {
            _cSlot.find('input[date-isdef]:not([disabled])').prop('checked', true);
        } else {
            _cSlot.find('input:not([disabled])').eq(0).prop('checked', true);
        }

        setValues(_picker);

    }).ajaxSuccess(function () {
        setValHandler();
    });

    setValHandler = function () {
        window.setPickValInterval = setInterval(function () {
            try {
                setValues($('.ipol_tpicker_cont .picker'));
                clearInterval(window.setPickValInterval);
            } catch (e) {

            }
        }, 500);
    }

    setValHandler();


});