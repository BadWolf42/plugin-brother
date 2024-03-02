<?php

// Re-remove output.json (replaced by daemon)
exec('sudo rm -f ' . realpath(__DIR__ . '/../data/output.json'));

// Re-remove packages.json (replaced by dependancy_* function in main class)
exec('sudo rm -f ' . realpath(__DIR__ . '/../../plugin_info/packages.json'));

?>
