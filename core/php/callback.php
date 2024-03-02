<?php

require_once __DIR__ . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'brother')) { // Security
	echo 'Unauthorized access.';
	if (init('apikey') != '')
		log::add('brother', 'error', sprintf(__("Accès non autorisé depuis %1\$s, avec une clé API commençant par %2\$.8s...", __FILE__), $_SERVER['REMOTE_ADDR'], init('apikey')));
	else
		log::add('brother', 'error', sprintf(__("Accès non autorisé depuis %s (pas de clé API)", __FILE__), $_SERVER['REMOTE_ADDR']));
	die();
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') { // If NOT POST, Then just close the connection
	die();
}
$eqId = init('eqId'); // Collect corresponding eqId

$eqLogic = brother::byId($eqId);
/** @var brother $eqLogic */
if (!is_object($eqLogic)) {
	self::logger('warning', sprintf(__("L'équipement %s n'existe pas/plus (ou n'est pas associé au plugin brother)", __FILE__), $eqId));
	die();
}

$received = file_get_contents("php://input"); // Get page full content
log::add('brother', 'debug', sprintf(__("Données reçues en callback '%s'", __FILE__), $received));

$eqLogic->recordData($received);
