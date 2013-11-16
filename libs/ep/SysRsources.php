<?php
class SysResource {
	
	var $Resources = array();
	
	function get($type = "", $config = NULL)
	{
		if(strcmp($type, "")==0)
		{
			// invalid type
			return null;
		}
		return $this->_get($type, $config);
	}
	function add($type, $res)
	{
		$this->Resources[$type] = $res;
	}
	
	////////////////// Private functions ///////////////////////////////
	function _get($type, $config = NULL)
	{
		if(array_key_exists($type, $this->Resources))
		{
			// use old one
			return $this->Resources[$type];
		}
		// We really need to init
		return $this->_init($type, $config);
	}
	function _init($type, $config = NULL)
	{
		$res = NULL;
		if(strcmp($type, "TPL")==0)
		{
			$res = $this->_Init_TPL($config);
		}
		else if(strcmp($type, "DB")==0)
		{
			$res = $this->_Init_Database($config);
		}
		else if(strcmp($type, "TextSanitizer")==0)
		{
			$res = $this->_Init_TextSanitizer($config);
		}
		else if(strcmp($type, "GoogleC2DM")==0)
		{
			$res = $this->_Init_GoogleC2DM($config);
		}
		else if(strcmp($type, "Logger")==0)
		{
			$res = $this->_Init_Logger($config);
		}
		else if(strcmp($type, "PHPMailer")==0)
		{
			$res = $this->_Init_PHPMailer($config);
		}
		else if(strcmp($type, "PHPExcel")==0)
		{
			$res = $this->_Init_PHPExcel($config);
		}
		else if(strcmp($type, "LoginHandler")==0)
		{
			$res = $this->_Init_LoginHandler($config);
		}
		else if(strcmp($type, "ErrorMsg")==0)
		{
			$res = $this->_Init_ErrorMsg($config);
		}
		else
		{
			return NULL;
		}
		
		$this->add($type, $res);
		return $res;
	}
	////////////////// Resource init functions ///////////////////////////////
	function _Init_TPL($config)
	{
		include_once ROOT_PATH."/libs/Smarty-2.6.26/libs/Smarty.class.php";
		$TPL = new Smarty();
		
		$TPL->template_dir = ROOT_PATH . "/templates/";
		$TPL->compile_dir = ROOT_PATH . "/templates_c/";
		$TPL->config_dir = ROOT_PATH . "/configs/";
		$TPL->cache_dir = ROOT_PATH . "/cache/";
		$TPL->left_delimiter = '{{';
		$TPL->right_delimiter = '}}';
		
		// create directories if need
		CreateDirIfNotExist(ROOT_PATH . "/templates/");
		CreateDirIfNotExist(ROOT_PATH . "/templates_c/");
		CreateDirIfNotExist(ROOT_PATH . "/configs/");
		CreateDirIfNotExist(ROOT_PATH . "/cache/");
		
		return $TPL;
	}
	function _Init_Database($config)
	{
		include_once ROOT_PATH."/configs/config_database.php";
		include_once ROOT_PATH."/libs/php_mysql_class/Database.class.php";
		$DB = new Database(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE, DB_DATABASE_PREFIX); // create the $db object 
		$DB->connect(); // connect to the server 
		
		return $DB;
	}
	function _Init_TextSanitizer($config)
	{
		include_once ROOT_PATH."/libs/TextSanitizer.php";
		$TextSanitizer = new TextSanitizer(); 
		
		return $TextSanitizer;
	}
	function _Init_GoogleC2DM($config)
	{
		include_once ROOT_PATH."/configs/config_google_c2dm.php";
		include_once ROOT_PATH."/libs/GoogleC2DMClass.php";
		$GoogleC2DM = new GoogleC2DMClass();
		$GoogleC2DM->GoogleAccountConfig(C2DM_AUTHER_MAIL, C2DM_AUTHER_PASSWORD);
		
		return $GoogleC2DM;
	}
	function _Init_Logger($config)
	{
		include_once ROOT_PATH."/libs/Logger.php";
		$Logger = new Logger();

		return $Logger;
		/*
		 * How to:
		 * $Logger = $SysResource->get('Logger');
		 * $Logger->IsDebug()(default) or $Logger->IsSystem() 
		 * $Logger->log("Some debug info");
		 * The log info would be showed in ROOT/syslog/catagory/filename.txt
		 * */
	}
	function _Init_PHPMailer($config)
	{
		include_once ROOT_PATH."/libs/PHPMailer_5.2.1/class.phpmailer.php";
		include_once ROOT_PATH."/configs/config_mail.php";
		$PHPMailer = new PHPMailer();
		$PHPMailer->Username   = MAIL_ACCOUNT_USERNAME;
		$PHPMailer->Password   = MAIL_ACCOUNT_PASSWORD;
		$PHPMailer->FromName   = MAIL_ACCOUNT_SENDER_NAME;
		$PHPMailer->Host   = MAIL_SMTP_HOST;

		return $PHPMailer;
	}
	function _Init_PHPExcel($config)
	{
		include_once ROOT_PATH."/libs/PHPExcel_1.7.6/PHPExcel.php";
		$PHPExcel = new PHPExcel();
		$PHPExcel->getProperties()->setCreator("Deren Wu")
							 ->setLastModifiedBy("Deren Wu")
							 ->setTitle("Office Excel Document")
							 ->setSubject("Office Excel Test Document")
							 ->setDescription("Test document for Office Excel, generated using PHP classes.")
							 ->setKeywords("office Excel php")
							 ->setCategory("Deren Wu");
		PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
		return $PHPExcel;
	}
	function _Init_LoginHandler($config)
	{
		require_once ROOT_PATH.'/libs/LoginHandler.php';
		$LoginHandler = new LoginHandler();
		return $LoginHandler;
	}
	function _Init_ErrorMsg($config)
	{
		require_once ROOT_PATH.'/libs/MsgRecorder.php';
		$ErrorMsg = new MsgRecorder();
		return $ErrorMsg;
	}

}
?>
