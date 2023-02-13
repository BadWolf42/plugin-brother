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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
function brother_install() {
  brother::pluginStats('install');
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function brother_update() {
  foreach (eqLogic::byType('brother') as $eqLogic) {
    $cmd = $eqLogic->getCmd(null, 'lastprints');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName('Dernières impressions');
      $cmd->setEqLogic_id($eqLogic->getId());
      $cmd->setLogicalId('lastprints');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->setTemplate('dashboard','tile');
      $cmd->setTemplate('mobile','tile');
      $cmd->save();
    }
    $eqLogic->save();
  }
  foreach (eqLogic::byType('brother') as $eqLogic) {
    if ($eqLogic->getConfiguration('brotherColorType', 'unset') === 'unset')
      $eqLogic->setConfiguration('brotherColorType', 1);
  }

  $cmd = 'sudo rm -f ' . realpath(__FILE__ . '/../data/output.json');
  exec($cmd);

  brother::pluginStats('update');
}

// Fonction exécutée automatiquement après la suppression du plugin
function brother_remove() {
  brother::pluginStats('uninstall');
}

?>
