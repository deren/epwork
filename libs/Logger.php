<?php

class Logger{
	var $Log2File = TRUE;
	var $Log2Database = FALSE;
	var $Log2Syslog = FALSE;

	var $BaseDir = ".";
	var $ClassType = "debug";
	
	var $NewLine = "\r\n";

	function __construct() {
	}
	function BaseDir($dir)
	{
		$this->BaseDir = $dir;
	}
	function ClassType($type)
	{
		$this->ClassType = $type;
	}

	function IsDebug()
	{
		$this->ClassType("debug");
	}
	function IsSystem()
	{
		$this->ClassType("system");
	}


	function log($msg){
		if($this->Log2File==TRUE){$this->_Log2File_($msg, $this->ClassType, 2);}
		if($this->Log2Database==TRUE){}
		if($this->Log2Syslog==TRUE){}
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Private functions
	function _Log2File_($msg, $catagory = "debug", $debug_level = 2) {
		if (ENABLE_FUNC_Logger == false) {
			return;
		}
		$this->CreateDirIfNotExist($this->BaseDir . "/syslog/");
		$this->CreateDirIfNotExist($this->BaseDir . "/syslog/$catagory/");
		$today = date("Y_m_d");
	
		if (is_array($msg) == TRUE) {
			$msg = var_export($msg, TRUE);
		}
	
		if (stristr($catagory, "debug") == TRUE) {
			$caller_trace = $this->caller_debug_backtrace($debug_level);
			
			$file_patten = "%s/syslog/%s/%s_%s.txt";
			$filename = sprintf($file_patten, $this->BaseDir, $catagory, basename($caller_trace['file']), $today);
			
			$str_patten = "[%s] %s (Func:%s, Line:%s)".$this->NewLine;
			$str = sprintf($str_patten, date("Y/M/d H:i:s", time()), $msg, $caller_trace['function'], $caller_trace['line']);
		} else {
			$file_patten = "%s/syslog/%s/%s.txt";
			$filename = sprintf($file_patten, $this->BaseDir, $catagory, $today);
		
			$str_patten = "[%s] %s".$this->NewLine;
			$str = sprintf($str_patten, date("Y/M/d H:i:s", time()), $msg);
		}
		file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);
	}
	////////////////// Utility /////////////////////////////////////////////
	function CreateDirIfNotExist($filepath = null) {
		if ($filepath != null) {
			file_exists($filepath) ? "" : mkdir($filepath, 0777, TRUE);
		}
	}
	function caller_debug_backtrace($level = 1) {
		$trace_all = debug_backtrace();
		$trace_caller = null;
		if ((count($trace_all)) > $level) {
			$trace_caller = $trace_all[$level];
		} else {
			$trace_caller = "No caller($level)";
		}
		return $trace_caller;
	}
}
?>