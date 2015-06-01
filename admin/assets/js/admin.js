(function($) {
    "use strict";

    $(function () {
        $("#paywall_display_type").change(function() {
            $(".box-type").toggle($(this).val() == "samepage");
        }).change();
    });
}(jQuery));
