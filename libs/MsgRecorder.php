<?php
class MsgRecorder{
	var $count = 0;
	var $Messages_int = array();
	var $Messages_str = array();
	
	function add($msg=NULL, $key="")
	{
		if($msg==NULL)
		{
			return;
		}
		if($key!=="" && is_string($key))
		{
			$this->Messages_str[$key] = $msg;
		}
		else
		{
			$this->Messages_int[$this->count] = $msg;
			$this->count++;
		}
	}
	
	function get($key="")
	{
		$ret = NULL;
		if($key!=="" && is_string($key)==true)
		{
			if(array_key_exists($key, $this->Messages_str)==true)
			{
				$ret = $this->Messages_str[$key];
			}
		}
		else if(is_int($key)==true)
		{
			if(array_key_exists($key, $this->Messages_int)==true)
			{
				$ret = $this->Messages_int[$key];
			}
		}
		else	// default. Get the last meeesage
		{
			if(array_key_exists($this->count-1, $this->Messages_int)==true)
			{
				$ret = $this->Messages_int[$this->count-1];
			}
		}
		return $ret;
	}
	
	function count()
	{
		return $this->count;
	}
}
?>