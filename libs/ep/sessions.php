<?php
function ep_is_login()
{
	return !empty($_SESSION[EP_SESSION_LOGIN_ID]);
}
function ep_login_id()
{
	return $_SESSION[EP_SESSION_LOGIN_ID];
}
function ep_login_check($url = "login.php")
{
	// check if you have login
	if(ep_is_login()==false)
	{
		// go to function page
		URL_Redirect($url);
		exit;
	}
}
function ep_set_login($flag, $id)
{
	if($flag)
	{
		$_SESSION[EP_SESSION_LOGIN_ID] = $id;
	}
	else
	{
		unset($_SESSION[EP_SESSION_LOGIN_ID]);
	}
}
function ep_login_referer_set()
{
	if(strpos($_SERVER['HTTP_REFERER'], "login.php")===false)
		$_SESSION[EP_SESSION_LOGIN_REFERER] = $_SERVER['HTTP_REFERER'];
}
function ep_session_get($key)
{
	return $_SESSION[$key];
}
function ep_session_unset($key)
{
	unset($_SESSION[$key]);
}
?>