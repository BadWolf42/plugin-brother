<?php

// Ensure all new cmds are created
foreach (eqLogic::byType('brother') as $eqLogic) {
    $eqLogic->save();
}

?>
