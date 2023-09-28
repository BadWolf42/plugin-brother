<?php

// Remove packages.json (replaced by dependancy_* function in main class)
exec('sudo rm -f ' . realpath(__FILE__ . '/../../plugin_info/packages.json'));

?>
