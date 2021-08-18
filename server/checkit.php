<?php

// Turn it all on.
error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', '1');

require_once(__DIR__.'/requirements/RequirementsChecker.php');

$checker = new RequirementsChecker();
$checker->checkCraft()->render();
$strict = (bool) getenv('CRAFT_STRICT_SERVER_CHECK');

if ($checker->result['summary']['errors'] || ($strict && $checker->result['summary']['warnings'])) {
    exit(1);
}

exit(0);
