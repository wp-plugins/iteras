(function($) {
    var truncate = require("truncate");
    window.iterasPaywallContent = function(wall) {
        var box = $("#iteras-paywall-box");
        box.show();
        var content = $(".iteras-content-wrapper");
        content.html(truncate(content.html(), box.data("snippet-size") || 300));
    };
})(jQuery);
