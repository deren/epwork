<?php
/*
* Created on 2011/06/23
*
* Basic definitions
*/
 
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)));

// This configuration is for Taiwan only. If you are not in GMT+8 timezone, please stop or re-config it.
define('ENABLE_CONF_TWTIME', true);

// include require files/functions in this project
require_once ROOT_PATH."/configs/common.php";

/*
 * In your code, you may access some system resource objects by following ways....
 * $DB = $SysResource->get('DB');
 * $TPL = $SysResource->get('TPL');
 * */
?>
