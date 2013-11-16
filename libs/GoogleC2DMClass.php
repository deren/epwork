<?php

class GoogleC2DMClass {
	
	const GOOGLE_URL_AUTH = "https://www.google.com/accounts/ClientLogin";
	const GOOGLE_URL_C2DM = "https://android.apis.google.com/c2dm/send";
	
	var $ProgramAuthorMail = '';
	var $ProgramAuthorPassword = '';
	var $GoogleAuthToken = '';
	
	function GoogleAccountConfig($email, $password)
	{
		$this->ProgramAuthorMail = $email;
		$this->ProgramAuthorPassword = $password;
	}
	
	function GetGoogleAuthToken()
	{
		// Setup Post data
		$POST_DATA = array();
		$POST_DATA['accountType']=('HOSTED_OR_GOOGLE');
		$POST_DATA['Email']=($this->ProgramAuthorMail);
		$POST_DATA['Passwd']=($this->ProgramAuthorPassword);
		$POST_DATA['service']=('ac2dm');
		$POST_DATA['source']=('bupt-c2dmdemo-1.0');
		
		//var_dump($POST_DATA);
		
		// Config cUrl object
		$ch = curl_init();
	    if(!$ch){
	        return false;
	    }
		curl_setopt($ch, CURLOPT_URL, self::GOOGLE_URL_AUTH);
		curl_setopt($ch, CURLOPT_POST, true); // Set to POST method
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return output data
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $POST_DATA ) ); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$output = curl_exec($ch); 
		curl_close($ch);
		
		// Get Google auth key
		$output = explode("=", $output);
		if(empty($output[3]))
		{
			return false;
		}
		$this->GoogleAuthToken = trim($output[3]);
		
		return $this->GoogleAuthToken;
	}

	function SendMessage2Phone($DeviceRegistrationId, $TransactionIndex, $CtrlData, $MsgData, $CollapseKey='1')
	{
		
		$HEADER_DATA = array();
		$HEADER_DATA[]="Authorization:GoogleLogin auth=".$this->GoogleAuthToken;
		
		// Setup Post data
		$POST_DATA = array();
		$POST_DATA['registration_id']=$DeviceRegistrationId;
		$POST_DATA['collapse_key']=$CollapseKey;
		$POST_DATA['data.id']=$TransactionIndex;
		$POST_DATA['data.ctrl']=$CtrlData;
		$POST_DATA['data.msg']=$MsgData;
		
		// Confi cUrl object
		$ch = curl_init();
	    if(!$ch){
	        return false;
	    }
		curl_setopt($ch, CURLOPT_URL, self::GOOGLE_URL_C2DM);
		curl_setopt($ch, CURLOPT_POST, true); // Set to POST method
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return output data
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $POST_DATA ) );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $HEADER_DATA);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$output = curl_exec($ch); 
		curl_close($ch);
		
		
		return $output;
	}

}

?>