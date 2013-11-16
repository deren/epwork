<?php
/*
* Created on 2011/09/09
*
* Define basic objects for this platform，such as: database, session, template.....etc.
*/

/************************ session init ************************/
define('EP_SESSION_CONFIG_NAME', "EP_SESSION_CONFIG_NAME");
define('EP_SESSION_LOGIN_ID', "EP_SESSION_LOGIN_ID");
define('EP_SESSION_LOGIN_REFERER', "EP_SESSION_LOGIN_REFERER");
define('EP_ESSION_CREATE_TIME', "ep_session_create_time");
define('EP_SESSION_EXPIRE_TIME', "ep_session_expire_time");
define('EP_SESSION_EXPIRE_TIME_LEN', "3600");
define('EP_SESSION_DIRECTORY', ROOT_PATH."/cache/sessions/");
define('EP_SESSION_VARIFICATION_CODE', "EP_SESSION_VARIFICATION_CODE");
//session_name(EP_SESSION_CONFIG_NAME);
file_exists(EP_SESSION_DIRECTORY) ? null : mkdir(EP_SESSION_DIRECTORY, 0777, TRUE);
ini_set('session.save_path', EP_SESSION_DIRECTORY);
ini_set("session.gc_maxlifetime", intval(EP_SESSION_EXPIRE_TIME_LEN)*24);
ini_set('session.cookie_lifetime',0);
session_start();

/************************ Include general functions ************************/
include_once ROOT_PATH."/libs/functions_general.php";

/************************ System Reources init ************************/
include_once ROOT_PATH."/libs/ep/SysRsources.php";
$SysResource = new SysResource();
$SysResource->add('GET', $_GET);
$SysResource->add('POST', $_POST);

/************************ time zoon definition ********************/
if(ENABLE_CONF_TWTIME)
{
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
	{
		date_default_timezone_set("Asia/Taipei");	// php5+
	}
	else
	{
		setlocale(LC_ALL, 'zh_TW');	// php4+
	}
}

/************************ Include framework functions ************************/
include_once ROOT_PATH."/libs/functions_framework.php";

/************************ Include Platform-dependent functions ************************/
include_once ROOT_PATH."/libs/functions_platform.php";

/************************ Turn-off error messages ************************/
define('IS_DEV_MODE', 1);
if(!IS_DEV_MODE)error_reporting(0);

/************************ Use ouput cache ********************/
ob_start();
?>
