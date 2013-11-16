<?php
/*
 * Setup configuration and send mail out
 *
 * 2012/Jan/20, Deren Wu - Create
 *
 * For Gmail only now
 *
 * How to:
require_once ROOT_PATH.'/libs/MailHandler.php';
$MailHandler = new MailHandler();
$PHPMailer = $SysResource->get('PHPMailer');
$MailHandler->SetPHPMailer($PHPMailer);
$MailHandler->SetDatabase($DB);
//$MailHandler->SetDebug("DebugCallback");
$MailHandler->sendsend($to, $to_name, $subject, $body);
 */

class MailHandler{

	var $DB = null;
	var $LogIndex = 0;
	var $PHPMailer = null;

	var $DoDebug = null; // Prototype : DebugFunc($msg)

	function send($to, $to_name, $subject, $body)
	{
		$this->config();

		$this->PHPMailer->Subject = $subject;
		//$this->PHPMailer->Body = $body;	// Not support HTML format
		$this->PHPMailer->MsgHTML($body);
		$this->PHPMailer->AddAddress($to, $to_name);

		$ret = $this->PHPMailer->Send();
		$ret_msg = null;
		if($ret==false) {
			$ret_msg = "Mail error: " . $this->PHPMailer->ErrorInfo;
		}else {
			$ret_msg = "Mail sent";
		}
		$this->DoDebug($ret_msg);
		
		if($this->DB!=null)
		{
			$data['from'] = $this->PHPMailer->From;
			$data['to'] = $to;
			$data['subject'] = $this->PHPMailer->Subject;
			$data['body'] = $this->PHPMailer->Body;
			$data['time'] = "NOW()";
			$data['result'] = $ret_msg;
			$this->LogIndex = $this->DB->query_insert("Log_SendMail", $data);
		}
		return $ret;
	}

	function config()
	{
		// 設定為 SMTP 方式寄信
		$this->PHPMailer->IsSMTP();

		// SMTP 伺服器的設定，以及驗證資訊
		
		if(strpos($this->PHPMailer->Username, "@gmail")!==false)
		{
			// For Gamil
			$this->PHPMailer->SMTPAuth = true;
			$this->PHPMailer->SMTPSecure = "ssl";
			$this->PHPMailer->Host = "smtp.gmail.com";
			$this->PHPMailer->Port = 465;
		}
		else
		{
			$this->PHPMailer->SMTPAuth = true;
			$this->PHPMailer->Port = 25;
		}

		// 信件內容的編碼方式
		$this->PHPMailer->CharSet = "utf-8";

		// 信件處理的編碼方式
		$this->PHPMailer->Encoding = "base64";

		// 信件內容設定
		$this->PHPMailer->From = $this->PHPMailer->Username;
		$this->PHPMailer->IsHTML(true);
	}


	function SetDebug($cb)
	{
		$this->DoDebug = $cb;
	}
	function SetDatabase($db)
	{
		$this->DB = $db;
	}
	function SetPHPMailer($mailer)
	{
		$this->PHPMailer = $mailer;
	}

	////////////////// Callback functions //////////////////////////////////
	function DoDebug($msg)
	{
		if($this->DoDebug!=null)
		{
			call_user_func($this->DoDebug, $msg);
		}
	}
}
?>