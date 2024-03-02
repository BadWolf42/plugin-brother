function whenLoaded() {
  // Display the real version number just before the plugin version number (YYYY-MM-DD hh:mm:ss)
  let sVersion = document.querySelector('#span_plugin_install_date')
  let dateVersion = sVersion.innerText
  sVersion.innerText = "v" + version + " (" + dateVersion + ")";

  // Add a link to the plugin rating
  let btRefresh = document.querySelector('.bt_refreshPluginInfo')
  btRefresh.insertAdjacentHTML('afterEnd', '<a class="btn btn-success btn-sm" target="_blank" href="https://market.jeedom.com/index.php?v=d&p=market_display&id=4266"><i class="fas fa-comment-dots "></i> {{Avis}}</a>')

  // Hide unused Configuration pane
  document.querySelector('#div_plugin_configuration').closest('div.panel.panel-primary').hidden = true

  // Disable Heartbeat field
  document.querySelector('.configKey[data-l1key="heartbeat::delay::brother"]').disabled = true
}

// TODO: Remove jQuery $(document) backward compatibility when Core 4.3 deprecated
if (typeof domUtils !== 'undefined') {
  domUtils(whenLoaded)
} else {
  $(document).ready(whenLoaded)
}
