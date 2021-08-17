#!/usr/bin/env php
<?php

// Turn it all on.
error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', '1');

require_once(__DIR__.'/requirements/RequirementsChecker.php');

$checker = new RequirementsChecker();
$checker->checkCraft()->render();

$args = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
$strict = in_array('--strict', $args);

if ($checker->result['summary']['errors'] || ($strict && $checker->result['summary']['warnings'])) {
    exit(1);
}

exit(0);
