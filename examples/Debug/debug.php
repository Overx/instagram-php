<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../../vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true; // This enables CLI debug.
$truncatedDebug = false; // In case you want to truncate debug log, you will set this
                         // to true. However, most of the times you want to leave it
                         // false.
//////////////////////

// There is another way to get debug logs without enabling CLI debug.
// You need to enable the following global var:
\InstagramAPI\Debug::$debugLog = true;

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
