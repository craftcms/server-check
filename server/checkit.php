<?php

require_once(__DIR__.'/requirements/RequirementsChecker.php');

$checker = new RequirementsChecker();
$checker->checkCraft()->render();
