class AppClass {

    init() {
        this.makeInputs();
        $(document).on('select2:open', '.select2-enable', function () {
            $('.select2-search__field').focus();
        })
    }

    makeInputs() {
        $('.select2-enable').select2();

        $('.autocomplete-w-wars').autocomplete({
            source: function (term, callBack) {

                $.ajax({
                    type: 'post',
                    url: '/autocomplete/' + $(this.element).attr('rel'),
                    dataType: 'json',
                    data: 'search=' + term.term,
                    success: function (res) {
                        callBack(res);
                    }

                })
            }
        });
    }

    onReload() {
        this.makeInputs();
    }

}

$(document).ready(function () {

    window.App = new AppClass();

    window.App.init();

    $(document).on('click', '.link_cloneLast', function () {
        let currentElement = $(this);
        let clonableElement = currentElement.prev().clone();
        if (clonableElement.is('input')) {
            clonableElement.val('')
        } else {
            clonableElement.find('input').val('');
        }
        currentElement.before(clonableElement);
        App.makeInputs();
    });

    $(document).on("keydown", "form", function (event) {
        return event.key !== "Enter";
    });
});