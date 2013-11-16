<?php

class LoginHandler{

	const LOGIN_ID = "LoginHandler_LOGIN_ID";
	const LOGIN_EXPIRETIME = "LoginHandler_LOGIN_EXPIRETIME";
	const LOGIN_REFERER = "LoginHandler_LOGIN_REFERER";
	const LOGIN_LEVEL_INDEX = "LoginHandler_LOGIN_LEVEL_INDEX";
	const LOGIN_TABLE_INDEX = "LoginHandler_LOGIN_TABLE_INDEX";

	var $session_id;
	var $AdminPermission = NULL;

	function __construct() {
		$this->SetSessionId();
		
		if(self::SessionExist(self::LOGIN_ID))
		{
			include_once 'AdminPermission.php';
			$this->AdminPermission = new AdminPermission($this->GetUserID());
		}
	}
	
	static function SessionExist($key)
	{
		if(array_key_exists($key, $_SESSION)==true && empty($_SESSION[$key])==false)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function GetUserID()
	{
		if(array_key_exists(self::LOGIN_ID, $_SESSION)==true && empty($_SESSION[self::LOGIN_ID])==false)
		{
			return $_SESSION[self::LOGIN_ID];
		}
		else
		{
			return null;
		}
	}
	function GetUserLevel()
	{
		if(array_key_exists(self::LOGIN_LEVEL_INDEX, $_SESSION)==true && empty($_SESSION[self::LOGIN_LEVEL_INDEX])==false)
		{
			return intval($_SESSION[self::LOGIN_LEVEL_INDEX]);
		}
		else
		{
			return null;
		}
	}
	function SetSessionId($sid=null)
	{
		if($sid==null)
		$this->session_id = session_id();
		else
		$this->session_id = session_id($sid);
	}

	function SetLogin($uid)
	{
		$_SESSION[self::LOGIN_ID] = $uid;
		$this->ExpiredTimeRefresh();
		$this->UnsetReferer();
		
		include_once 'AdminPermission.php';
		$this->AdminPermission = new AdminPermission($this->GetUserID());
		
		// Update client side
//		setcookie(session_name(), session_id(), 3600);
	}
	function SetLogout($sid = null)
	{
		// Update client side. This sessino ID should be stop now
//		setcookie(session_name(), session_id(), time());
		
		if(array_key_exists(self::LOGIN_TABLE_INDEX, $_SESSION)==true)
		{
			$this->LogoutDatabaseRecord($_SESSION[self::LOGIN_TABLE_INDEX], time());
		}
		@session_destroy();
		@unlink(session_save_path()."/sess_".$this->session_id);
		
		//寫入logout log
		global $SysResource;
		$DB = $SysResource->get('DB');
		$loginHandler = $SysResource->get('LoginHandler');
		
		$data['Account'] = $loginHandler->GetUserID();
		$data['LoginIP'] = $this->getRealIp();
		$data['SessionID'] = $this->session_id;
		$data['LogoutTime'] = MakeTimeStamp(time());
		$DB->query_insert('LogoutLog', $data);
	}
	function GetSessionFile($session_id=NULL)
	{
		if($session_id==NULL)
		{
			return session_save_path()."/sess_".$this->session_id;
		}
		else
		{
			return session_save_path()."/sess_".$session_id;
		}
	}

	function ExpiredTimeRefresh()
	{
		// Update server side
		// check if this file going to expire
		$session_file = $this->GetSessionFile();
		$expire_time = time()+intval(EP_SESSION_EXPIRE_TIME_LEN);
		//if ((filemtime($session_file)+intval(ini_get("session.gc_maxlifetime"))/2)>time())touch($session_file);
		touch($session_file);
		$_SESSION[self::LOGIN_EXPIRETIME] = $expire_time;

		if(array_key_exists(self::LOGIN_TABLE_INDEX, $_SESSION)==true)
		{
			$this->LogoutDatabaseRecord($_SESSION[self::LOGIN_TABLE_INDEX], $_SESSION[self::LOGIN_EXPIRETIME]);
		}
		// Update client side
//		setcookie(session_name(), session_id(), $expire_time);
	}
	function DetoryOtherLogin($uid, $where="1")
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		//$DB->SetDebug("DebugCallback");
		$sql = "SELECT AutoID,SessionID,LogoutTime FROM `LoginLog` WHERE Account='$uid' and $where ORDER BY `AutoID` DESC";
		$record = $DB->query_first($sql);
		if(isset($record['SessionID'])==true && ReverseTimeStamp($record['LogoutTime'])>time())
		{
			$this->LogoutDatabaseRecord($record['AutoID'], time());
			$this->SetForceLogout($record['AutoID']);
			if($this->session_id!=$record['SessionID'])@unlink(session_save_path()."/sess_".$record['SessionID']);
//			@unlink(session_save_path()."/sess_".$record['SessionID']);
		}
	}
	function LogoutDatabaseRecord($record_id, $unix_time)
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		$data["LogoutTime"] = MakeTimeStamp($unix_time);
		$DB->query_update("LoginLog", $data, "AutoID=$record_id");
	}
	function SetForceLogout($record_id)
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		$data["ForceLogout"] = 1;
		$DB->query_update("LoginLog", $data, "AutoID=$record_id");
	}
	function SetReferer($url)
	{
		$_SESSION[self::LOGIN_REFERER] = $url;
	}
	function GetReferer($url)
	{
		if(array_key_exists(self::LOGIN_REFERER, $_SESSION)==true && strlen($_SESSION[self::LOGIN_REFERER])>0)
		{
			return $_SESSION[self::LOGIN_REFERER];
		}
		else
		{
			return null;
		}
	}
	function UnsetReferer()
	{
		unset($_SESSION[self::LOGIN_REFERER]);
	}
	function IsLogined()
	{
		$ret = false;
		if(array_key_exists(self::LOGIN_EXPIRETIME, $_SESSION)==true)
		{
			if($_SESSION[self::LOGIN_EXPIRETIME]>time())
			{
				$ret = true;
			}
			else
			{
				$this->SetLogout();
			}
		}
		return $ret;
	}

	function LoginIndex()
	{

	}


	function getRealIp()
	{
		//check ip from share internet
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
		{
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		//to check ip is pass from proxy
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
	///////////////////////////////////////////////////////////////////////////////////////////
	function checkLogin($loginpage = 'login.php')
	{
		$pos = strpos($_SERVER['REQUEST_URI'], $loginpage);
		if($this->IsLogined() == false && $pos === false)
		{
			showMsg('請先登入', $loginpage);
		}
	}
	function checkMinLevel($MinLevel)
	{
		$min_level = 100;
		if(isset($_SESSION[self::LOGIN_LEVEL_INDEX])==false)
		{
			$record = HAUser::GetUserInfo($_SESSION[self::LOGIN_ID], "Phone");
			$min_level = intval($record['Level']);
			$_SESSION[self::LOGIN_LEVEL_INDEX] = intval($record['Level']);
		}
		else
		{
			$min_level = intval($_SESSION[self::LOGIN_LEVEL_INDEX]);
		}
		return ($min_level<=intval($MinLevel))?TRUE:FALSE;
	}
	function UserLogin($username, $password, $where="1")
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		//$DB->SetDebug("DebugCallback");

		//$record = self::GetUserInfo($username, 'Phone_Admin');
		$sql = "SELECT * FROM `User` WHERE Account='$username' and $where";
		$record = $DB->query_first($sql);
		if(isset($record['Account'])==false)
		{
			return false;
		}
		// 完成手機註冊後才可登入
		if( intval($record['IsVerifiedPhone'])==0)
		{
			return false;
		}

		$hashedPwd = hash('sha256', $record['Salt'].$password);
		$currentTime = MakeTimeStamp(time());

		// Prepare login log
		$dataLogin['Account'] = $username;
		$dataLogin['LoginIP'] = $this->getRealIp();
		$dataLogin['SessionID'] = $this->session_id;
		$dataLogin['LoginTime'] = $currentTime;
		$dataLogin['LogoutTime'] = MakeTimeStamp(time()+intval(ini_get('session.gc_maxlifetime')));
		if($record['Pwd']==$hashedPwd)
		{
			$dataUser['LoginTimes'] = $record['LoginTimes'] + 1;
			$dataUser['LastLoginTime'] = $currentTime;
			$DB->query_update('User', $dataUser, "Account='$username'");

			$this->DetoryOtherLogin($username);
			$this->SetLogin($username);

			//login log
			$dataLogin['IsPassed'] = '1';
			$_SESSION[self::LOGIN_TABLE_INDEX] = $DB->query_insert('LoginLog', $dataLogin);
			//showMsg('登入成功', 'index.php');
			return true;
		}
		else
		{
			//login log
			$dataLogin['IsPassed'] = '0';
			$_SESSION[self::LOGIN_TABLE_INDEX] = $DB->query_insert('LoginLog', $dataLogin);
			//showMsg('登入失敗', $_SERVER['HTTP_REFERER']);
			return false;
		}
	}
	
	function IsFirstThreeDays()
	{
		$Account = $this->GetUserID();
		$user_info = HAUser::GetUserInfo($Account, 'Account');
		$reg_time = ReverseTimeStamp($user_info['RegisterTime']);
		if(time()-$reg_time>60*60*24*3){
			return false;
		}
		else{
			return true;
		}
	}
	
	function GetHeaderHtml($header_file)
	{
		global $SysResource;
		$TPL = $SysResource->get('TPL');
		
		$Account = $this->GetUserID();
		if(empty($Account)==false)
		{
			$TPL->assign('PhoneNum',$Account);

			$user_info = HAUser::GetUserInfo($Account, 'Account');
			$TPL->assign('RealName',$user_info['FirstName'].$user_info['LastName']);


			//算出總和點數抱括：虛擬點數和實際點數
			require_once './libs/AccountingPoint.php';
			$pointAccount = new AccountingPoint($Account);
			$totalPoint = $pointAccount->AccountTotal();
			$TPL->assign('TotalPoints',$totalPoint);

			//顯示距離月費到期日還有幾天
			require_once ROOT_PATH.'/libs/HAProduct.php';
			$tmpExpiredTime = HAProduct::GetLastExpiredTime($Account,'BuyDays');
			if($tmpExpiredTime>time())
			{
				$remianDays = intval(($tmpExpiredTime-time())/(60*60*24));
				if($remianDays<=0)
					$remianDays = round(doubleval($tmpExpiredTime-time())/(60*60*24), 1);
			}
			else
				$remianDays = 0;
			$TPL->assign('RemainDays',$remianDays);
		}
		
		$TPL->assign('TotalUserCount', HAUser::NumberOfVarifiedUsers()+200);
		
		return $TPL->fetch($header_file);
	}

	function ConfirmPwd($username, $password)
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		$sql = "SELECT Account, Salt, Pwd, LoginTimes, LastLoginTime FROM `User` WHERE Account='".$username."'";

		$record = $DB->query_first($sql);
		if(isset($record['Account']) == false)
		{
			return false;
		}

		$hashedPwd = hash('sha256', $record['Salt'].$password);
		if($record['Pwd']==$hashedPwd)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function AdminPermissionCheck($func_name, $msg = NULL, $referer=NULL)
	{
		if($this->AdminPermission->IsValid($func_name)==false)
		{
			$_msg = ($msg==NULL)?'權限不足無法操作，請洽詢管理員！':$msg;
			$_referer = ($referer==NULL)?$_SERVER['HTTP_REFERER']:$referer;
			showMsg($_msg, $_referer);
			exit;
		}
		return true;
	}
	function AdminPermissionSetupVisibleHtml($TPL)
	{
		$this->AdminPermission->SetupVisibleHtml($TPL);
	}
	
	function UserPermissionSetupVisibleHtml($TPL)
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		//判斷是否為群組管理者
		$sql = 'SELECT GroupID, GroupName, SharedPoint FROM `Group` WHERE ManagerAccount = '.$this->GetUserID();
		$row = $DB->query($sql);
		$TPL->assign("UserPermission_group", $DB->affected_rows);
	}
	
	static function WarningLogins($data, $type="List")
	{
		global $SysResource;
		$DB = $SysResource->get('DB');
		
		switch ($type) {
			case "List":
				$sql = "SELECT * FROM `LoginLog` WHERE `Account`='$data' AND `ForceLogout`='1'";
				return $DB->fetch_all_array($sql);
			break;
			
			case "Count":
				return $DB->query_count("LoginLog", "`Account`='$data' AND `ForceLogout`='1'");
			break;
			
			default:
				return false;
			break;
		}
	}
}
?>
