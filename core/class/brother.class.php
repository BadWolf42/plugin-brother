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
    /** @var brother $eqLogic */
    foreach (self::byType(__CLASS__, true) as $eqLogic)
      $eqLogic->refreshInfo();
    self::pluginStats();
  }

  public static function pluginStats($_reason = 'cron') {
    // Check last reporting (or if forced)
    $nextStats = @cache::byKey('brother::nextStats')->getValue(0);
    if ($_reason === 'cron' && (time() < $nextStats)) { // No reason to force send stats
      // log::add(__CLASS__,'debug', "No need before " . date('Y-m-d H:i:s', $nextStats));
      return;
    }
    // Ensure between 5 and 10 minutes before next attempt
    cache::set('brother::nextStats', time() + 300 + rand(0, 300));
    // Avoid getting all stats exactly at the same time
    sleep(rand(0, 10));

    $url = 'https://stats.bad.wf/v1/query';
    $data = array();
    $data['plugin'] = 'brother';
    $data['hardwareKey'] = jeedom::getHardwareKey();
    // Ensure system unicity using a rotating UUID
    $data['lastUUID'] = config::byKey('installUUID', __CLASS__, $data['hardwareKey']);
    $data['UUID'] = base64_encode(hash('sha384', microtime() . random_bytes(107), true));
    $data['hardwareName'] = jeedom::getHardwareName();
    if ($data['hardwareName'] == 'diy')
      $data['hardwareName'] = trim(shell_exec('systemd-detect-virt'));
    if ($data['hardwareName'] == 'none')
      $data['hardwareName'] = 'diy';
    $data['distrib'] = trim(shell_exec('. /etc/*-release && echo $ID $VERSION_ID'));
    $data['phpVersion'] = phpversion();
    $data['pythonVersion'] = trim(shell_exec("python3 -V | cut -d ' ' -f 2"));
    $data['jeedom'] = jeedom::version();
    $data['lang'] = config::byKey('language', 'core', 'fr_FR');
    $data['lang'] = ($data['lang'] != '') ? $data['lang'] : 'fr_FR';
    $plugin = update::byLogicalId(__CLASS__);
    $data['source'] = $plugin->getSource();
    $data['branch'] = $plugin->getConfiguration('version', 'unknown');
    $data['configVersion'] = config::byKey('version', __CLASS__, -1);
    $data['reason'] = $_reason;
    if ($_reason == 'uninstall' || $_reason == 'noStats')
      $data['next'] = 0;
    else
      $data['next'] = time() + 432000 + rand(0, 172800); // Next stats in 5-7 days
    $encoded = json_encode($data);
    $options = array(
      'http' => array(
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $encoded
      )
    );
    log::add(__CLASS__, 'debug', "Anonymous statistical data have been sent: " . $encoded);
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === false) {
      // Could not send or invalid data
      log::add(__CLASS__, 'debug', "Unable to communicate with the statistics server (Response: false)");
      return;
    }
    $response = @json_decode($result, true);
    if (!isset($response['status']) || $response['status'] != 'success') {
      // Could not send or invalid data
      log::add(__CLASS__, 'debug', 'Unable to communicate with the statistics server (Response: ' . $result .')');
    } else {
      config::save('installUUID', $data['UUID'], __CLASS__);
      if ($data['next'] == 0) {
        log::add(__CLASS__, 'info', __('Données statistiques supprimées', __FILE__));
        cache::set('brother::nextStats', PHP_INT_MAX);
      } else {
        log::add(__CLASS__, 'debug', 'Statistical data sent (Response: ' . $result .')');
        // Set last sent datetime
        cache::set('brother::nextStats', $data['next']);
      }
    }
  }

  /**
   * Provides dependancy information
   */
  public static function dependancy_info() {
    $depLogFile = __CLASS__ . '_dep';
    $depProgressFile = jeedom::getTmpFolder(__CLASS__) . '/dependancy';

    $return = array();
    $return['log'] = log::getPathToLog($depLogFile);
    $return['progress_file'] = $depProgressFile;
    $return['state'] = 'ok';

    if (file_exists($depProgressFile)) {
      log::add(__CLASS__, 'debug', sprintf(__("Dépendances en cours d'installation... (%s%%)", __FILE__), trim(file_get_contents($depProgressFile))));
      $return['state'] = 'nok';
      return $return;
    }

    if (!file_exists(__DIR__ . '/../../resources/venv/bin/pip3') || !file_exists(__DIR__ . '/../../resources/venv/bin/python3')) {
      log::add(__CLASS__, 'debug', __("Relancez les dépendances, le venv Python n'a pas encore été créé", __FILE__));
      $return['state'] = 'nok';
    } else {
      exec(__DIR__ . '/../../resources/venv/bin/pip3 freeze --no-cache-dir -r '.__DIR__ . '/../../resources/requirements.txt 2>&1 >/dev/null', $output);
      if (count($output) > 0) {
        log::add(__CLASS__, 'error', __('Relancez les dépendances, au moins une bibliothèque Python requise est manquante dans le venv :', __FILE__).' <br/>'.implode('<br/>', $output));
        $return['state'] = 'nok';
      }
    }

    if ($return['state'] == 'ok')
      log::add(__CLASS__, 'debug', sprintf(__('Dépendances installées.', __FILE__)));
    return $return;
  }

  /**
   * Provides dependancy installation script
   */
  public static function dependancy_install() {
    $depLogFile = __CLASS__ . '_dep';
    $depProgressFile = jeedom::getTmpFolder(__CLASS__) . '/dependancy';

    log::add(__CLASS__, 'info', sprintf(__('Installation des dépendances, voir log dédié (%s)', __FILE__), $depLogFile));

    $update = update::byLogicalId(__CLASS__);
    shell_exec(
      'echo "\n\n================================================================================\n'.
      '== Jeedom '.jeedom::version().' '.jeedom::getHardwareName().
      ' in $(lsb_release -d -s | xargs echo -n) on $(arch | xargs echo -n)/'.
      '$(dpkg --print-architecture | xargs echo -n)/$(getconf LONG_BIT | xargs echo -n)bits\n'.
      '== $(python3 -VV | xargs echo -n)\n'.
      '== '.__CLASS__.' v'.config::byKey('version', __CLASS__, 'unknown', true).
      ' ('.$update->getLocalVersion().') branch:'.$update->getConfiguration()['version'].
      '" >> '.log::getPathToLog($depLogFile)
    );

    return array(
      'script' => __DIR__ . '/../../resources/install_#stype#.sh ' . $depProgressFile,
      'log' => log::getPathToLog($depLogFile)
    );
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
      $this->refreshInfo();
  }

  public function preInsert() {
    $this->setIsVisible(1);
    $this->setConfiguration('brotherWidget', 1);
    $this->setDisplay('height','192px');
    $this->setDisplay('width', '312px');
    $this->setIsEnable(1);
  }

  public function refreshInfo() {
    if (!$this->getIsEnable())
      return;

    $cmd  = 'LOGLEVEL=' . log::convertLogLevel(log::getLogLevel(__CLASS__)) . ' ';
    $port = config::byKey('internalPort', 'core', 80);
    $comp = trim(config::byKey('internalComplement', 'core', ''), '/');
    if ($comp !== '') $comp .= '/';
    $cmd .= "CALLBACK='http://localhost:".$port."/".$comp."plugins/brother/core/php/callback.php";
    $cmd .= "?apikey=".jeedom::getApiKey(__CLASS__)."&eqId=".$this->getId()."' ";
    $cmd .= realpath(__DIR__ . '/../../resources/venv/bin') . '/python3 ';
    $cmd .= realpath(__DIR__ . '/../../resources') . '/jeeBrother.py ';
    $cmd .= $this->getConfiguration('brotherAddress') . ' ';
    $cmd .= $this->getConfiguration('brotherType') . ' ';
    $cmd .= ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &';
    log::add(__CLASS__, 'info', 'Lancement script Brother : ' . $cmd);
    exec($cmd);
    $this->checkAndUpdateCmd('status', __('Actualisation', __FILE__));
  }

  public function recordData($output) {
    if (!$this->getIsEnable()) {
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' is disabled trashing received data... ');
      return;
    }

    if ($output === false || strlen($output) == 0) {
      log::add(__CLASS__, 'info', $this->getHumanName() . ' no data received');
      $output = '{"unreachable": true}';
    } else {
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' data content: ' . $output);
    }

    $data = json_decode($output, true);
    if ($data === null) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' JSON decode impossible');
      return;
    }
    if (isset($data['msg'])) {
      log::add(__CLASS__, 'error', $this->getHumanName() . ' error while executing Python script: ' . $data['message']);
      return;
    }

    // Check if device is unreachable
    if (isset($data['unreachable'])) {
      $this->checkAndUpdateCmd('status', __('Injoignable', __FILE__));
      log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for status: ' . __('Injoignable', __FILE__));
      return;
    }
    // List keys to fetch in $data
    $pType = ($this->getConfiguration('brotherType') == 'laser') ? 'toner' : 'ink';
    $infos = ['model' => 'model', 'serial' => 'serial', 'firmware' => 'firmware', 'status' => 'status'];
    $infos += ['counter' => 'page_counter', 'black' => 'black_'.$pType.'_remaining'];
    if ($this->getConfiguration('brotherColorType') != 0) {
      $infos += ['cyan' => 'cyan_'.$pType.'_remaining'];
      $infos += ['magenta' => 'magenta_'.$pType.'_remaining'];
      $infos += ['yellow' => 'yellow_'.$pType.'_remaining'];
    }
    // Backup last counter value
    $lastCounterVal = $this->getCmd(null, 'counter')->execCmd();

    // Fetch keys and set cmds
    foreach ($infos as $logicalId => $key) {
      if (isset($data[$key]) && !is_null($data[$key])) {
        $this->checkAndUpdateCmd($logicalId, $data[$key]);
        log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for ' . $logicalId . ': ' . $data[$key]);
      } else {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' null value for ' . $key);
      }
    }

    // Calculate 'lastprints', if possible
    if (isset($data['page_counter']) && !is_null($data['page_counter']) && !is_null($lastCounterVal)) {
      $lastPrintsValue = 0;
      $cmdLastPrints = $this->getCmd(null, 'lastprints');
      if (is_null($cmdLastPrints) || !is_null($cmdLastPrints->execCmd()))
        $lastPrintsValue = $data['page_counter'] - $lastCounterVal;
      $this->checkAndUpdateCmd('lastprints', $lastPrintsValue);
      log::add(__CLASS__, 'info', $this->getHumanName() . ' record value for last prints: ' . $lastPrintsValue);
    } else {
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' null value for page_counter and/or last counter');
    }
  }

  private function prepareReplace(&$r, $cmdName) {
    $cmd = $this->getCmd(null, $cmdName);
    if (!is_null($cmd) && $cmd->getIsVisible() == 1) {
      $r['#'.$cmdName.'_id#'] = $cmd->getId();
      $r['#'.$cmdName.'_value#'] = $cmd->execCmd();
      $r['#'.$cmdName.'_valueDate#'] = $cmd->getValueDate();
      $r['#'.$cmdName.'_collectDate#'] = $cmd->getCollectDate();
      $r['#'.$cmdName.'_hidden#'] = '';
    } else {
      $r['#'.$cmdName.'_id#'] = '';
      $r['#'.$cmdName.'_hidden#'] = 'hidden';
    }
  }

  public function toHtml($_version = 'dashboard') {
    if ($this->getConfiguration('brotherWidget') != 1)
      return parent::toHtml($_version);
    $replace = $this->preToHtml($_version);
    if (!is_array($replace))
      return $replace;

    $this->prepareReplace($replace, 'refresh');
    $this->prepareReplace($replace, 'status');
    $this->prepareReplace($replace, 'counter');
    $this->prepareReplace($replace, 'lastprints');

    $this->prepareReplace($replace, 'black');
    $this->prepareReplace($replace, 'cyan');
    $this->prepareReplace($replace, 'magenta');
    $this->prepareReplace($replace, 'yellow');

    $html = template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), 'brother.template', __CLASS__));
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
    /** @var brother $eqLogic */
    $eqLogic = $this->getEqLogic();
    if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1)
      throw new Exception(sprintf(__("Equipement desactivé impossible d'éxecuter la commande : %s", __FILE__), $this->getHumanName()));
    log::add(brother::class, 'debug', 'Execution de la commande ' . $this->getLogicalId());
    switch ($this->getLogicalId()) {
      case "refresh":
        $eqLogic->refreshInfo();
        break;
    }
  }

}
?>
