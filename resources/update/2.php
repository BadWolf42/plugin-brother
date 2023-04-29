<?php

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

?>