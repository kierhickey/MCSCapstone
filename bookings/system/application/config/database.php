<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

$active_group = "default";

// Is dev environment?
$host = $_SERVER["SERVER_NAME"];

if ($host == "localhost") {
    // Defaults for devenvironment
    $db['default']['hostname'] = "localhost";
    $db['default']['username'] = "root";
    $db['default']['password'] = "";
    $db['default']['database'] = "heroku_891758f1ded0ba4";
} else {
    // Get HEROKU info
    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $db['default']['hostname'] = $url["host"];
    $db['default']['username'] = $url["user"];
    $db['default']['password'] = $url["pass"];
    $db['default']['database'] = substr($url["host"], 1);
}

$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = '';
$db['default']['active_r'] = TRUE;
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = FALSE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = 'dbcache';

echo json_encode($db);

?>
