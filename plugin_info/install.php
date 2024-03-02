<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../../core/php/core.inc.php';


function brother_install() {
  log::add('brother', 'debug', 'install.php: brother_install()');
  brother_update(false);
}

function brother_update($_direct=true) {
  if ($_direct)
    log::add('brother', 'debug', 'install.php: brother_update()');

  // if version info is not in DB, it means it is a fresh install of brother
  // and so we don't need to run these functions to adapt eqLogic structure/config
  // (even if plugin is disabled the config key stays)
  try {
    $content = file_get_contents(__DIR__ . '/info.json');
    $data = json_decode($content, true);
    $pluginVersion = $data['pluginVersion'];
  } catch (Throwable $e) {
    log::add('brother', 'warning', __("Impossible de récupérer le numéro de version dans le fichier info.json, ceci ce devrait pas arriver !", __FILE__));
    $pluginVersion = 0;
  }

  $version = @intval(config::byKey('version', 'brother', $pluginVersion));

  while (++$version <= $pluginVersion) {
    try {
      $file = __DIR__ . '/../resources/update/' . $version . '.php';
      if (file_exists($file)) {
        log::add('brother', 'debug', sprintf(__("Version %d : Application des modifications", __FILE__), $version));
        include $file;
        log::add('brother', 'debug', sprintf(__("Version %d : Modifications appliquées", __FILE__), $version));
      }
    } catch (Throwable $e) {
      log::add('brother', 'error', str_replace("\n",'<br />',
        sprintf(__("Version %1\$d : Exception lors de l'application des modifications : %2\$s", __FILE__)."<br />@Stack: %3\$s.",
                $version, $e->getMessage(), $e->getTraceAsString())
        ));
    }
  }

  config::save('version', $pluginVersion, 'brother');

  brother::pluginStats($_direct ? 'update' : 'install');
}

function brother_remove() {
  brother::pluginStats('uninstall');
}

?>
