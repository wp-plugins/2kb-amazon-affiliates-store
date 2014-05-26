(function($) {
    $(function() {
        $('.kb-amz-checkbox-toggle .btn').click(function() {
            $($(this).attr('data-target')).find('input[type="checkbox"]').each(function() {
                $(this).prop("checked", !$(this).prop("checked"));
            });
        });
    });
})(jQuery);