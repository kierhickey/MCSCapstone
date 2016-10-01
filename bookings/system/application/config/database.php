<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This is for connecting to ClearDB via Heroku
$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

$active_group = "default";

$db['default']['hostname'] = $url["host"];
$db['default']['username'] = $url["user"];
$db['default']['password'] = $url["pass"];
$db['default']['database'] = substr($url["path"], 1);
$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = '';
$db['default']['active_r'] = TRUE;
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = FALSE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = 'dbcache';

?>
