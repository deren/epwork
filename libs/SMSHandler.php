<?php
/*
 * Send SMS for provider KOTSMS (http://www.kotsms.com.tw/)
 *
 * 2012/Jan/17, Deren Wu - Create
 */

class SMSHandler{

	const URL_QUICK = "http://202.39.48.216/kotsmsapi-1.php";	// 666 secs / 1000 sms
	const URL_MASS = "http://202.39.48.216/kotsmsapi-2.php";	// 	60 secs / 2666 sms
	var $API_TYPE = array( self::URL_QUICK, self::URL_MASS);
	var $need_mass = 0;

	var $username = "deren";
	var $password = "derenderen";
	var $dstaddr = null;	// 0918201892
	var $smbody = null;		// 簡訊王api簡訊測試
	var $smbody_src = null;	// Take care about the source text before encoding change
	var $dlvtime = 0;
	var $vldtime = 1800;
	var $response = "";

	var $SendURL = null;

	const URLTemplate_Send = "%s?username=%s&password=%s&dstaddr=%s&smbody=%s&dlvtime=%s&vldtime=%s&response=%s";
	const URLTemplate_LeftPoints = "http://mail2sms.com.tw/memberpoint.php?username=%s&password=%s";
	const URLTemplate_SMSStatus = "http://mail2sms.com.tw/msgstatus.php?username=%s&password=%s&kmsgid=%s";

	var $DoDebug = null; // Prototype : DebugFunc($msg)

	var $DB = null;
	var $LogIndex = 0;

	function __construct() {
		$this->response = URL_FRONTEND."/sms_response.php";
	}
	////////////////// Main API functions ///////////////////////////////////////

	function LeftPoints()
	{
		// Setup URL
		$SendURL = sprintf(self::URLTemplate_LeftPoints,
		rawurlencode($this->username),
		rawurlencode($this->password));

		$result_s = file_get_contents($SendURL);
		$this->DoDebug("LeftPoints result : $result_s");

		return $result_s;
	}
	function SMSStatus($kmsgid)
	{
		// Setup URL
		$SendURL = sprintf(self::URLTemplate_SMSStatus,
		rawurlencode($this->username),
		rawurlencode($this->password),
		$kmsgid);

		$result_s = file_get_contents($SendURL);
		$this->DoDebug("SMSStatus result : $result_s");

		$pos = strpos($result_s, "SUCCESSED");
		if ($pos === false)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	function send()
	{
		// Make sure we are Big5
		$this->smbody = $this->Convert2Big5($this->smbody);

		// Setup URL
		$this->SendURL = sprintf(self::URLTemplate_Send,
		$this->API_TYPE[$this->need_mass],
		rawurlencode($this->username),
		rawurlencode($this->password),
		rawurlencode($this->dstaddr),
		rawurlencode($this->smbody),
		rawurlencode($this->dlvtime),
		rawurlencode($this->vldtime),
		rawurlencode($this->response));
		
		
		$this->DoDebug("Send URL : ".$this->SendURL);
		// Send log
		$data['username'] = $this->username;
		$data['password'] = $this->password;
		$data['dstaddr'] = $this->dstaddr;
		$data['smbody'] = $this->smbody;
		$data['smbody_src'] = $this->smbody_src;
		$data['dlvtime'] = $this->dlvtime;
		$data['vldtime'] = $this->vldtime;
		$data['response'] = $this->response;
		$data['RequestURL'] = $this->SendURL;
		$data['time_send'] = "NOW()";
		$data['points_before'] = $this->LeftPoints();
		if($this->DB!=null)
		{
			$this->LogIndex = $this->DB->query_insert("Log_SendSMS", $data);
		}
		// request to server
		//$result_s = "kmsgid=debug";
		//return;
		$result_s = file_get_contents($this->SendURL);

		$this->DoDebug("Send result : $result_s");
		// Splite string into array
		$result_a = explode("=", trim($result_s));

		// Get trasaction ID!
		if(count($result_a)==2){
			$kmsgid = $result_a[1];
		}
		else {
			$this->DoDebug("Invalid format of kmsgid. Force to set -100.");
			$kmsgid = -100;
		}

		// Finish log
		unset($data);
		$data['kmsgid'] = intval($kmsgid);
		$data['time_finish'] = "NOW()";
		$data['points_after'] = $this->LeftPoints();
		if($this->DB!=null)
		{
			$this->DB->query_update("Log_SendSMS", $data, "AutoID='".$this->LogIndex."'");
		}

		// Text Log
		/*$today = date("Y/M/d G:i:s");
		 $fp = fopen("send_log.txt","a+");
		 fwrite( $fp, "$today - $result_s($this->SendURL)\n\r");
		 fclose($fp);*/

		$this->DoDebug("Send finish");

		return $kmsgid;
	}

	function response()
	{
		global $_REQUEST;
		$data['dstaddr2'] = $_REQUEST["dstaddr"];
		$data['dlvtime2'] = $_REQUEST["dlvtime"];
		$data['donetime'] = $_REQUEST["donetime"];
		$data['time_response'] = "NOW()";
		if($this->DB!=null)
		{
			$kmsgid = $this->DB->escape($_REQUEST["kmsgid"]);
			$this->DB->query_update("Log_SendSMS", $data, "kmsgid='$kmsgid'");
		}
	}

	function info()
	{

	}

	////////////////// Set functions ///////////////////////////////////////
	function SetUsername($data)
	{
		$this->username = trim($data);
	}
	function SetPassword($data)
	{
		$this->password = trim($data);
	}
	function SetDstPhonenum($data)
	{
		$this->dstaddr = trim($data);
	}
	function SetBody($data)
	{
		$this->smbody_src = $data;
		// Big5 only
		$this->smbody = $this->Convert2Big5($data);
	}
	function SetSendTime($data)
	{
		// Format - YYYY/MM/DD hh24:mm:ss
		$this->dlvtime = trim($data);
	}
	function SetExpireTime($data)
	{
		// Format 1 - YYYY/MM/DD hh24:mm:ss
		// Format 2 - 1800 ~ 28800 (secs)
		$this->vldtime = trim($data);
	}
	function SetResponseURL($data)
	{
		$this->response = trim($data);
	}
	function SetQuickAPI()
	{
		$this->need_mass = 0;
	}
	function SetMassAPI()
	{
		$this->need_mass = 1;
	}
	function SetDebug($cb)
	{
		$this->DoDebug = $cb;
	}
	function SetDatabase($db)
	{
		$this->DB = $db;
	}

	////////////////// Callback functions //////////////////////////////////
	function DoDebug($msg)
	{
		if($this->DoDebug!=null)
		{
			call_user_func($this->DoDebug, $msg);
		}
	}
	////////////////// Utility /////////////////////////////////////////////
	function Convert2Big5($str)
	{
		$ret = null;

		$encoding_check = mb_detect_encoding($str,array('ASCII','BIG-5','UTF-8'));
		if(strcasecmp($encoding_check, "BIG-5")!=0)
		{
			$this->DoDebug("Convet $encoding_check to BIG-5");
			$ret = iconv($encoding_check,"BIG-5//TRANSLIT//IGNORE",$str);
		}
		else
		{
			$this->DoDebug("Is BIG-5");
			$ret = $str;
		}

		return $ret;
	}
}

/*
 * Log table schema
 * CREATE TABLE IF NOT EXISTS `Log_SendSMS` (
 `AutoID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 `password` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
 `dstaddr` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
 `smbody` varchar(255) CHARACTER SET big5 NOT NULL,
 `smbody_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `dlvtime` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
 `vldtime` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
 `response` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `RequestURL` text COLLATE utf8_unicode_ci NOT NULL,
 `time_send` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `kmsgid` bigint(9) NOT NULL,
 `time_finish` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `dstaddr2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
 `dlvtime2` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
 `donetime` varchar(24) COLLATE utf8_unicode_ci NOT NULL,
 `time_response` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 PRIMARY KEY (`AutoID`)
 ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Log for SMS sent' AUTO_INCREMENT=1 ;
 */

?>