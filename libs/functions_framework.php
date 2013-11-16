<?php

function SystemLog($msg) {
	_LogAction_($msg, "main", 2);
}
function DebugLog($msg) {
	_LogAction_($msg, "debug", 2);
}

////////////////////////////////////////////////////////////////////////////////////////
// Private functions
function _LogAction_($msg, $catagory = "main", $debug_level = 2) {
	if (ENABLE_FUNC_Logger == false) {
		return;
	}
	CreateDirIfNotExist(ROOT_PATH . "/syslog/");
	CreateDirIfNotExist(ROOT_PATH . "/syslog/$catagory/");
	$today = date("Y_m_d");
	$file_patten = "%s/syslog/%s/%s.txt";
	$filename = sprintf($file_patten, ROOT_PATH, $catagory, $today);

	if (is_array($msg) == TRUE) {
		$msg = var_export($msg, TRUE);
	}

	if (stristr($catagory, "debug") != FALSE) {
		$caller_trace = caller_debug_backtrace($debug_level);
		$str_patten = "[%s] %s (%s, %s, %s)";
		$str = sprintf($str_patten, date("Y/m/d h:i:s", mktime()), $msg, $caller_trace['file'], $caller_trace['function'], $caller_trace['line']);
	} else {
		$str_patten = "[%s] %s";
		$str = sprintf($str_patten, date("Y/m/d h:i:s", mktime()), $msg);
	}
	append_file($str, $filename);
}

?>