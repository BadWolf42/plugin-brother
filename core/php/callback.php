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

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'brother')) { // Security
	echo 'Unauthorized access.';
	if (init('apikey') != '')
		log::add('brother', 'error', sprintf(__("AccÃ¨s non autorisÃ© depuis %1\$s, avec une clÃ© API commenÃ§ant par %2\$.8s...", __FILE__), $_SERVER['REMOTE_ADDR'], init('apikey')));
	else
		log::add('brother', 'error', sprintf(__("AccÃ¨s non autorisÃ© depuis %s (pas de clÃ© API)", __FILE__), $_SERVER['REMOTE_ADDR']));
	die();
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') { // NOT POST, used by ping, we just close the connection
	die();
}
$eqId = init('eqId'); // Collect corresponding eqId

$eqLogic = brother::byId($eqId);
if (!is_object($eqLogic)) {
	self::logger('warning', sprintf(__("L'Ã©quipement %s n'existe plus (ou n'est pas associÃ© au plugin brother)", __FILE__), $eqId));
	die();
}

$received = file_get_contents("php://input"); // Get page full content
log::add('brother', 'debug', sprintf(__("DonnÃ©es reÃ§ues en callback '%s'", __FILE__), $received));

$eqLogic->recordData($received);
