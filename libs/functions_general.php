<?php

// Include standard libs
include_once str_replace('\\', '/', dirname(__FILE__))."/ep/sessions.php";

function GetRunningScript()
{
	global $_SERVER;
	$file = $_SERVER["SCRIPT_NAME"];
	$break = Explode('/', $file);
	$pfile = $break[count($break) - 1];
	return $pfile;
}

function int_divide($x, $y) {
	if ($x == 0) return 0;
	if ($y == 0) return FALSE;
	return ($x - ($x % $y)) / $y;
}
function EncodingConvert($str, $encode="UTF-8") // or BIG-5
{
	$ret = null;

	$encoding_check = mb_detect_encoding($str,array('ASCII','UTF-8','BIG-5'));
	if(strcasecmp($encoding_check, $encode)!=0)
	{
		$ret = iconv($encoding_check,"$encode//TRANSLIT//IGNORE",$str);
	}
	else
	{
		$ret = $str;
	}
	return $ret;
}
function RunOnWindows()
{
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		return true;
	} else {
		return false;
	}
}
function ClientIsIE()
{
	return strpos( $_SERVER["HTTP_USER_AGENT"], 'MSIE') ? true : false;
}
function HtmlFlushOut()
{
	// get the size of the output
	$size = ob_get_length();

	header("Content-Encoding: none\r\n");
	// send headers to tell the browser to close the connection
	header("Content-Length: $size");
	header('Connection: close');

	// flush all output
	ob_end_flush();
	ob_flush();
	flush();
	ob_end_clean();
	if (session_id()) session_write_close();
}
function DebugCallback($msg)
{
	DebugHtml($msg);
	global $SysResource;
	$Logger = $SysResource->get('Logger');
	$Logger->log($msg);
}
function GetBaseURL()
{
	global $_SERVER;
	return "http://" . $_SERVER['HTTP_HOST']. dirname($_SERVER['REQUEST_URI']). DIRECTORY_SEPARATOR;
}

function GetRequestValue($key)
{
	global $_POST;
	global $_GET;
	
	if(isset($_POST[$key])==false && isset($_GET[$key])==false)
	{
		return "";
	}

	//DebugHtml($key);
	$value = trim(!isset($_POST[$key]) ? $_GET[$key] : $_POST[$key]);
	
	return gpc2sql($value);
}
// Optimization. To avoid SQL Injection
function gpc2sql($str) { 
    if(get_magic_quotes_gpc()==1) 
        return $str; 
    else 
        return addslashes($str); 
}

function GetUserIP() {
	if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$myip = $_SERVER['REMOTE_ADDR'];
	} else {
		$myip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$myip = $myip[0];
	}
	return $myip;
}

function ArrayDump($data, $seperator = ",") {
	if ($data == NULL) {
		return "";
	}
	foreach ($data as $key => $val) {
		$resultMesg .= $key . "=" . $val . $seperator;
	}
	return $resultMesg;
}

function getDebugBacktrace($NL = "<BR>") {
	$dbgTrace = debug_backtrace();
	$dbgMsg .= $NL . "Debug backtrace begin:$NL";
	foreach ($dbgTrace as $dbgIndex => $dbgInfo) {
		$dbgMsg .= "\t at $dbgIndex  " . $dbgInfo['file'] . " (line {$dbgInfo['line']}) -> {$dbgInfo['function']}(" . join(",", $dbgInfo['args']) . ")$NL";
	}
	$dbgMsg .= "Debug backtrace end" . $NL;
	return $dbgMsg;
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

function ReverseTimeStamp($time_stamp) {
	return strtotime($time_stamp);
}

function MakeTimeStamp($time) {
	return date("Y-m-d H:i:s", $time);
}

function MakeDateStamp($time)
{
	return date("Y-m-d",$time);
}

function SpecialUnixTime($type="day", $target_timestamp=0)
{
	if($target_timestamp==0)
		$target_timestamp = time();
	$date_now = getdate($target_timestamp);
	$ret = 0;
	switch ($type)
	{
		case "thisday":
			$ret = mktime(0, 0, 0, $date_now['mon'], $date_now['mday'], $date_now['year']);
			break;
		case "thisyear":
			$ret = mktime(0, 0, 0, 1, 1, $date_now['year']);
			break;
		case "thismonth":
			$ret = mktime(0, 0, 0, $date_now['mon'], 1, $date_now['year']);
			break;
		case "nextmonth":
			$ret = mktime(0, 0, 0, $date_now['mon']+1, 1, $date_now['year']);
			break;
		case "thisweek":
			$ret = mktime(0, 0, 0, $date_now['mon'], $date_now['mday'] - intval($date_now['wday']%7), $date_now['year']);
			break;
		case "thisseason":
			$ret = mktime(0, 0, 0, $date_now['mon'] - intval($date_now['mon']%3) + 1, 1, $date_now['year']);
			break;
		case "yeardiff":
			$ret = mktime(0, 0, 0, $date_now['mon'], $date_now['mday'], $date_now['year']-1);
			break;
		case "monthdiff":
			$ret = mktime(0, 0, 0, $date_now['mon']-1, $date_now['mday'], $date_now['year']);
			break;
		case "daydiff":
			$ret = mktime(0, 0, 0, $date_now['mon'], $date_now['mday']-1, $date_now['year']);
			break;
		case "daydiff3":
			$ret = mktime(0, 0, 0, date("m"), date("d")-2, date("Y"));
			break;
		case "today":
		default:
			$ret = mktime(0, 0, 0, $date_now['mon'], $date_now['mday'], $date_now['year']);
			break;
	}
	return $ret;
}

function getJS($path) {
	$prefix = '<script language="JavaScript" type="text/JavaScript"><!--';
	$postfix = '//--></script>';
	return $prefix . file_get_contents($path) . $postfix;
}

function URL_Redirect($url = ".", $exit = true) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: " . $url);
	if($exit==true)
		exit;
}

function URL_Refresh($time = 1, $url = ".") {
	header("refresh:$time;url=$url");
}

function JavascriptAlert($V1) {
	if (strlen($V1) > 0) {
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
		echo "<script>alert('$V1');</script> ";
	}
}

function JavascriptRedirect($V1) {
	echo "<meta http-equiv=refresh content=0;url=" . $V1 . ">";
	exit ;
}

function JavascriptBack() {
	$referer = $_SERVER['HTTP_REFERER'];
	if (!$referer == '') {
		echo '<p><a href="' . $referer . '" title="Return to the previous page">&laquo; Go back</a></p>';
	} else {
		echo '<p><a href="javascript:history.go(-1)" title="Return to the previous page">&laquo; Go back</a></p>';
	}
}

function JavascriptCloseWindow($delay) {
	//echo '<a href="javascript:window.close()">CLOSE WINDOW</a>';
	echo "<script type=\"text/javascript\">
 function closeWindow() {
 setTimeout(function() {
 window.close();
 }, $delay);
 }

 window.onload = closeWindow();
 </script>

 <!--<h3>Thank you</h3>-->";
}

function getFielExtension($fileName) {
	return substr($fileName, strrpos($fileName, '.') + 1);
}

function isValidURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function CreateDirIfNotExist($filepath = null) {
	if ($filepath != null) {
		file_exists($filepath) ? "" : mkdir($filepath, 0777, TRUE);
	}
}

function SafeFileWrite($fileName, $dataToSave)
{
	if ($fp = fopen($fileName, 'w'))
	{
		$startTime = microtime();
		do
		{
			$canWrite = flock($fp, LOCK_EX);
			// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			if(!$canWrite) usleep(round(rand(0, 100)*1000));
		} while ((!$canWrite)and((microtime()-$startTime) < 1000));

		//file was locked so now we can store information
		if ($canWrite)
		{
			fwrite($fp, $dataToSave);
			flock($fp, LOCK_UN);
		}
		fclose($fp);

	}
}
function SafeFileRead($fileName)
{
	if(is_readable($fileName)==TRUE)
	{
		return file_get_contents($fileName);
	}
	else
	{
		return null;
	}
}
function append_file($msg, $filename) {
	if ($msg == null || $filename == null) {
		return;
	}
	file_put_contents($filename, $msg, FILE_APPEND | LOCK_EX);
}

function DebugHtml($msg, $active = TRUE) {
	if ($active) {
		echo "<br>";
		var_dump($msg);
		echo "<br>";
	}
}
function showMsg($ErrMsg, $NextURL=null, $showCharset = "utf-8", $Exit = TRUE){
	if($NextURL == null){
		header('Content-type: text/html; charset=' . $showCharset);
		header('Vary: Accept-Language');
		echo ("<SCRIPT Language='JavaScript' charset='" . $showCharset . "'>");
		echo ("alert('" . $ErrMsg . "'); ");
		echo ("</SCRIPT>");
		if($Exit == TRUE)
		exit ();
	}else{
		if (!strlen($ErrMsg))
		{
			header('Content-type: text/html; charset=' . $showCharset);
			header('Vary: Accept-Language');
			echo ("<SCRIPT Language='JavaScript' charset='" . $showCharset . "'>");
			echo ("location='" . $NextURL . "';");
			echo ("</SCRIPT>");
			if($Exit == TRUE)
			exit ();
		}
		else
		{
			header('Content-type: text/html; charset=' . $showCharset);
			header('Vary: Accept-Language');
			echo ("<SCRIPT Language='JavaScript' charset='" . $showCharset . "'>");
			echo ("alert('" . $ErrMsg . "'); ");
			echo ("location='" . $NextURL . "';");
			echo ("</SCRIPT>");
			if($Exit == TRUE)
			exit ();
		}
	}
}
?>
