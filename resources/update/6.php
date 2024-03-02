<?php

// Remove packages.json (replaced by dependancy_* function in main class)
exec('sudo rm -f ' . realpath(__DIR__ . '/../../plugin_info/packages.json'));

?>
