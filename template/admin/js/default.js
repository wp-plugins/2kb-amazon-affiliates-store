(function($) {
    $(function() {
        $('.kb-amz-checkbox-toggle .btn').click(function() {
            $($(this).attr('data-target')).find('input[type="checkbox"]').each(function() {
                $(this).prop("checked", !$(this).prop("checked"));
            });
        });
    });
})(jQuery);

/**
 * Trigger event delayed.
 * @author Ivan Gospodinow
 * @site http://www.ivangospodinow.com
 * @mail ivangospodinow@gmail.com
 * @date 01.02.2013
 */
(function($) {
    $.fn.onDelay = function(type, delay, funct) {
        delay = undefined === delay ? 500 : parseInt(delay);
        var timeOut;
        $(this).unbind(type)[type](function(e,x,y,z) {
            clearTimeout(timeOut);
            var self = this;
            timeOut = setTimeout(function(){
                funct.call(self,e,x,y,z);
            },delay);
        });
    };
})(jQuery);