$(document).ready(function() {
    // Display the real version number just before the plugin version number (YYYY-MM-DD hh:mm:ss)
    var dateVersion = $("#span_plugin_install_date").html();
    $("#span_plugin_install_date").empty().append("v" + version + " (" + dateVersion + ")");

    // Add a link to the plugin rating
    $('.bt_refreshPluginInfo').after('<a class="btn btn-success btn-sm" target="_blank" href="https://market.jeedom.com/index.php?v=d&p=market_display&id=4266"><i class="fas fa-comment-dots "></i> {{Avis}}</a>');
});
