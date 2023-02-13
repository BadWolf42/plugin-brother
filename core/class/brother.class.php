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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';


class brother extends eqLogic {

  public static $_widgetPossibility = array('custom' => true);

  /* * *************************Attributs****************************** */

  /* * ***********************Methode static*************************** */

  public static function cronHourly() {
    foreach (self::byType(__CLASS__, true) as $eqLogic)
        $eqLogic->refreshInfo();
    self::pluginStats();
  }

  public static function pluginStats($_reason = 'cron') {
    // Check last reporting (or if forced)
    $nextStats = @cache::byKey('brother::nextStats')->getValue(0);
    if ($_reason === 'cron' && (time() < $nextStats)) { // No reason to force send stats
      // log::add(__CLASS__, 'debug', sprintf(__("Aucune raison d'envoyer des données statistiques avant le %s", __FILE__), date('Y-m-d H:i:s', $nextStats)));
      return;
    }
    // Ensure next attempt will be in at least 5 minutes
    cache::set('brother::nextStats', time() + 300 + rand(0, 300)); ; // in 5-10 mins

    $url = 'https://stats.bad.wf/brother.php';
    $data = array();
    $data['version'] = 1;
    $data['hardwareKey'] = jeedom::getHardwareKey();
    $data['hardwareName'] = jeedom::getHardwareName();
    $data['distrib'] = system::getDistrib();
    $data['phpVersion'] = phpversion();
    $data['jeedom'] = jeedom::version();
    $data['lang'] = translate::getLanguage();
    $plugin = update::byLogicalId(__CLASS__);
    $data['source'] = $plugin->getSource();
    $data['branch'] = $plugin->getConfiguration('version', 'unknown');
    $data['localVersion'] = $plugin->getLocalVersion();
    $data['remoteVersion'] = $plugin->getRemoteVersion();
    $data['reason'] = $_reason;
    if ($_reason == 'uninstall' || $_reason == 'noStats')
      $data['removeMe'] = true;
    else
      $data['next'] = time() + 432000 + rand(0, 172800); // Next stats in 5-7 days
    $options = array('http' => array(
      'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
      'method'  => 'POST',
      'content' => http_build_query($data)
    ));
    log::add(__CLASS__, 'debug', sprintf(__('Transmission des données statistiques suivantes : %s', __FILE__), json_encode($data)));
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === false) {
      // Could not send or invalid data
      log::add(__CLASS__, 'debug', __('Impossible de communiquer avec le serveur de statistiques (Réponse : false)', __FILE__));
      return;
    }
    $response = @json_decode($result, true);
    if (!isset($response['status']) || $response['status'] != 'success') {
      // Could not send or invalid data
      log::add(__CLASS__, 'debug', sprintf(__('Impossible de communiquer avec le serveur de statistiques (Réponse : %s)', __FILE__), $result));
    } else {
      if ($data['removeMe']) {
        log::add(__CLASS__, 'info', __('Données statistiques supprimées', __FILE__));
        cache::set('brother::nextStats', PHP_INT_MAX);
      } else {
        log::add(__CLASS__, 'debug', sprintf(__('Données statistiques envoyées (Réponse : %s)', __FILE__), $result));
        // Set last sent datetime
        cache::set('brother::nextStats', $data['next']);
      }
    }
  }

  public static function executeManualRefresh() {
    self::cronHourly();
    log::add(__CLASS__, 'debug', 'Manual refresh executed');
    $cron = cron::byClassAndFunction(__CLASS__, 'manualRefresh');
    if (is_object($cron)) {
      $cron->stop();
      $cron->remove();
      log::add(__CLASS__, 'debug', 'Manual refresh cron deleted');
    }
  }

  public function postSave() {
    $cmd = $this->getCmd(null, 'model');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Modèle', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('model');
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setGeneric_type('GENERIC_INFO');
      $cmd->setIsVisible(1);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'serial');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Numéro de série', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('serial');
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setGeneric_type('GENERIC_INFO');
      $cmd->setIsVisible(1);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'firmware');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Firmware', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('firmware');
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setGeneric_type('GENERIC_INFO');
      $cmd->setIsVisible(1);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'status');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Statut', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('status');
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setGeneric_type('GENERIC_INFO');
      $cmd->setIsVisible(1);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'counter');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Nombre de pages', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('counter');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setTemplate('dashboard','tile');
      $cmd->setTemplate('mobile','tile');
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'black');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Noir', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('black');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setUnite('%');
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->setConfiguration('minValue', 0);
      $cmd->setConfiguration('maxValue', 100);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'cyan');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Cyan', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('cyan');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setUnite('%');
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->setConfiguration('minValue', 0);
      $cmd->setConfiguration('maxValue', 100);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'magenta');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Magenta', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('magenta');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setUnite('%');
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->setConfiguration('minValue', 0);
      $cmd->setConfiguration('maxValue', 100);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'yellow');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Jaune', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('yellow');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setIsHistorized(1);
      $cmd->setIsVisible(1);
      $cmd->setUnite('%');
      $cmd->setGeneric_type('CONSUMPTION');
      $cmd->setConfiguration('minValue', 0);
      $cmd->setConfiguration('maxValue', 100);
      $cmd->save();
    }
    $cmd = $this->getCmd(null, 'lastprints');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Dernières impressions', __FILE__));
      $cmd->setEqLogic_id($this->getId());
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
    $cmd = $this->getCmd(null, 'refresh');
    if (!is_object($cmd)) {
      $cmd = new brotherCmd();
      $cmd->setName(__('Rafraichir', __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId('refresh');
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->save();
    }
    if ($this->getIsEnable() == 1)
      $this->setManualRrefresh();
  }

  public function preInsert() {
    $this->setIsVisible(1);
    $this->setConfiguration('brotherWidget', 1);
    $this->setDisplay('height','192px');
    $this->setDisplay('width', '312px');
    $this->setIsEnable(1);
  }

  public function setManualRrefresh() {
    $cron = cron::byClassAndFunction(__CLASS__, 'manualRefresh');
    $now = new DateTime('NOW');
    $now->modify('+1 minute');
    $cronExpr = $now->format('i H j n') . ' *';
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass(__CLASS__);
      $cron->setFunction('executeManualRefresh');
      $cron->setEnable(1);
      $cron->setDeamon(0);
      $cron->setOnce(1);
      $cron->setSchedule($cronExpr);
      $cron->save();
    } else {
      $cron->setSchedule($cronExpr);
      $cron->save();
    }
    log::add(__CLASS__, 'debug', 'Manual refresh cron scheduled : ' . $cronExpr);
  }

  public function pullBrother() {
    $brother_path = dirname(__FILE__) . '/../..';
    $host = $this->getConfiguration('brotherAddress');
    $type = $this->getConfiguration('brotherType');

    $cmd = 'python3 ' . $brother_path . '/resources/jeeBrother.py ' . $host . ' ' . $type . ' ' . $brother_path . ' ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
    log::add(__CLASS__, 'info', 'Lancement script Brother : ' . $cmd);

    exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1');
    $string = file_get_contents($brother_path . '/data/output.json');
    log::add(__CLASS__, 'debug', $this->getHumanName() . ' file content: ' . $string);
    if ($string === false) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' file content empty');
      return null;
    }
    $json_a = json_decode($string);
    if ($json_a === null) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' JSON decode impossible');
      return null;
    }
    if (isset($json_a->msg)) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' error while executing Python script: ' . $obj->message);
      return null;
    }
    return $json_a;
  }

  public function refreshInfo() {
    $obj = $this->pullBrother();
    if (!is_null($obj))
      $this->recordData($obj);
  }

  public function recordData($obj) {
    if ($this->getIsEnable()) {
      if (!is_null($obj->model)) {
        $this->checkAndUpdateCmd('model', $obj->model);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for model: ' . $obj->model);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for model');
      }
      if (!is_null($obj->serial)) {
        $this->checkAndUpdateCmd('serial', $obj->serial);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for serial: ' . $obj->serial);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for serial');
      }
      if (!is_null($obj->firmware)) {
        $this->checkAndUpdateCmd('firmware', $obj->firmware);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for firmware: ' . $obj->firmware);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for firmware');
      }
      if (!is_null($obj->status)) {
        $this->checkAndUpdateCmd('status', $obj->status);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for status: ' . $obj->status);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for status');
      }

      $type = $this->getConfiguration('brotherType');
      $printertype = "ink";
      if ($type == "laser")
        $printertype = "toner";
      $colors = ["black", "cyan", "magenta", "yellow"];
      $colorType = $this->getConfiguration('brotherColorType');
      if ($colorType == 0)
        $colors = ["black"];
      foreach ($colors as $color) {
        $index = $color . '_' . $printertype . '_remaining';
        if (!is_null($obj->$index)) {
          $this->checkAndUpdateCmd($color, $obj->$index);
          log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for ' . $color . ': ' . $obj->$index);
        } else {
          log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for ' . $index);
        }
      }

      $cmdCounter = $this->getCmd(null, 'counter');
      $curCounterValue = $cmdCounter->execCmd();
      if (!is_null($obj->page_counter) && !is_null($curCounterValue)) {
        $cmdLasPrints = $this->getCmd(null, 'lastprints');
        if (!is_null($cmdLasPrints) && is_null($cmdLasPrints->execCmd()))
          $lastprintsValue = 0;
        else
          $lastprintsValue = $obj->page_counter - $curCounterValue;
        $this->checkAndUpdateCmd('lastprints', $lastprintsValue);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for last prints: ' . $lastprintsValue);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for page_counter and/or last counter');
      }

      if (!is_null($obj->page_counter)) {
        $this->checkAndUpdateCmd('counter', $obj->page_counter);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for counter: ' . $obj->page_counter);
      } else {
        log::add(__CLASS__, 'error', $this->getHumanName() . ' null value for page_counter');
      }
    }
  }

  public function toHtml($_version = 'dashboard') {
    if ($this->getConfiguration('brotherWidget') != 1)
      return parent::toHtml($_version);
    $replace = $this->preToHtml($_version);
    if (!is_array($replace))
      return $replace;
    $version = jeedom::versionAlias($_version);

    $refreshCmd = $this->getCmd(null, 'refresh');
    $replace['#refresh_id#'] = ($refreshCmd->getIsVisible() != 1) ? '' : $refreshCmd->getId();

    $blackCmd = $this->getCmd(null, 'black');
    if (!is_null($blackCmd) && $blackCmd->getIsVisible() == 1) {
      $replace['#black_level#'] = $blackCmd->execCmd();
      $replace['#black_id#'] = $blackCmd->getId();
      $replace['#black_visible#'] = 1;
      $replace['#black_bkg#'] = 0.1;
    } else {
      $replace['#black_level#'] = 0;
      $replace['#black_visible#'] = 0;
      $replace['#black_bkg#'] = 0;
    }

    $cyanCmd = $this->getCmd(null, 'cyan');
    if (!is_null($cyanCmd) && $cyanCmd->getIsVisible() == 1) {
      $replace['#cyan_level#'] = $cyanCmd->execCmd();
      $replace['#cyan_id#'] = $cyanCmd->getId();
      $replace['#cyan_visible#'] = 1;
      $replace['#cyan_bkg#'] = 0.1;
    } else {
      $replace['#cyan_level#'] = 0;
      $replace['#cyan_visible#'] = 0;
      $replace['#cyan_bkg#'] = 0;
    }

    $magentaCmd = $this->getCmd(null, 'magenta');
    if (!is_null($magentaCmd) && $magentaCmd->getIsVisible() == 1) {
      $replace['#magenta_level#'] = $magentaCmd->execCmd();
      $replace['#magenta_id#'] = $magentaCmd->getId();
      $replace['#magenta_visible#'] = 1;
      $replace['#magenta_bkg#'] = 0.1;
    } else {
      $replace['#magenta_level#'] = 0;
      $replace['#magenta_visible#'] = 0;
      $replace['#magenta_bkg#'] = 0;
    }

    $yellowCmd = $this->getCmd(null, 'yellow');
    if (!is_null($yellowCmd) && $yellowCmd->getIsVisible() == 1) {
      $replace['#yellow_level#'] = $yellowCmd->execCmd();
      $replace['#yellow_id#'] = $yellowCmd->getId();
      $replace['#yellow_visible#'] = 1;
      $replace['#yellow_bkg#'] = 0.1;
    } else {
      $replace['#yellow_level#'] = 0;
      $replace['#yellow_visible#'] = 0;
      $replace['#yellow_bkg#'] = 0;
    }

    $statusCmd = $this->getCmd(null, 'status');
    if ($statusCmd->getIsVisible() == 1) {
      $replace['#brother_status#'] = $statusCmd->execCmd();
      $replace['#brother_status_id#'] = $statusCmd->getId();
      $replace['#brother_status_uid#'] = $statusCmd->getId();
      $replace['#brother_status_eqid#'] = $replace['#uid#'];
      $replace['#brother_status_valueDate#'] = $statusCmd->getValueDate();
      $replace['#brother_status_collectDate#'] = $statusCmd->getCollectDate();
    } else {
      $replace['#brother_status_id#'] = '';
    }
    $pagesCmd = $this->getCmd(null, 'counter');
    if ($pagesCmd->getIsVisible() == 1) {
      $replace['#brother_counter#'] = $pagesCmd->execCmd();
      $replace['#brother_counter_id#'] = $pagesCmd->getId();
      $replace['#brother_counter_uid#'] = $pagesCmd->getId();
      $replace['#brother_counter_eqid#'] = $replace['#uid#'];
      $replace['#brother_counter_valueDate#'] = $pagesCmd->getValueDate();
      $replace['#brother_counter_collectDate#'] = $pagesCmd->getCollectDate();
    } else {
      $replace['#brother_counter_id#'] = '';
    }
    $lastprintsCmd = $this->getCmd(null, 'lastprints');
    if ($lastprintsCmd->getIsVisible() == 1) {
      $replace['#brother_lastprints#'] = $lastprintsCmd->execCmd();
      $replace['#brother_lastprints_id#'] = $lastprintsCmd->getId();
      $replace['#brother_lastprints_uid#'] = $lastprintsCmd->getId();
      $replace['#brother_lastprints_eqid#'] = $replace['#uid#'];
      $replace['#brother_lastprints_valueDate#'] = $lastprintsCmd->getValueDate();
      $replace['#brother_lastprints_collectDate#'] = $lastprintsCmd->getCollectDate();
    } else {
      $replace['#brother_lastprints_id#'] = '';
    }
    $html = template_replace($replace, getTemplate('core', $version, 'brother.template', __CLASS__));
    cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
    return $html;
  }

}

class brotherCmd extends cmd {

  public function preSave() {
    if ($this->getLogicalId() == 'cyan' || $this->getLogicalId() == 'yellow' || $this->getLogicalId() == 'magenta') {
      $eqLogic = $this->getEqLogic();
      $visible = $eqLogic->getConfiguration('brotherColorType',1);
      $this->setIsVisible($visible);
    }
}

  public function dontRemoveCmd() {
    return true;
  }

  public function execute($_options = null) {
    $eqLogic = $this->getEqLogic();
    if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1)
      throw new Exception(sprintf(__("Equipement desactivé impossible d'éxecuter la commande : %s", __FILE__), $this->getHumanName()));
    log::add(__CLASS__, 'debug', 'Execution de la commande ' . $this->getLogicalId());
    switch ($this->getLogicalId()) {
      case "refresh":
        $eqLogic->setManualRrefresh();
        break;
    }
  }

}
?>
