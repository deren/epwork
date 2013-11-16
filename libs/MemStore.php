<?php


class MemStore{
	var $DoDebug = null; // Prototype : DebugFunc($msg)

	const TYPE_Session = 0;
	const TYPE_Memcache = 1;
	var $type = self::TYPE_Session;

	var $data_lifetime = 3600;

	var $my_key_prefix = null;
	var $global_key_prefix = null;
	
	var $Category = NULL;

	// For memcache config
	var $memcache = null;
	var $memcache_options = array(
    'servers' => array('soho.pona.tw:11211'),
    'debug' => true,
    'compress_threshold' => 10240,
    'persistant' => false
	);


	function __construct($Category = "GlobalData") {
		$this->init(self::TYPE_Session);
		$this->Category = $Category;
	}

	function init($type)
	{
		$this->CreateKeyPrefix();
		switch ($type)
		{
			case self::TYPE_Session:
				{
					break;
				}
			case self::TYPE_Memcache:
				{
					if(extension_loaded("memcache")==false)
					{
						$this->DoDebug("No memcache extension. Init session mode.");
						return $this->SetMode(self::TYPE_Session);
					}
					$this->memcache = $this->_init_Memcache($this->memcache_options);
					break;
				}
		}
		// 0 => Session, 1 => Memcache
		$this->type = $type;
	}

	////////////////// Major public functions //////////////////////////////////
	function SetMode($mode)
	{
		return $this->init($mode);
	}

	function MySet($_key, $_value)
	{
		$ret = false;
		$key = $this->my_key_prefix.$_key;
		$this->DoDebug("Before serialize = ".var_export($_value, true));
		$value = base64_encode(gzdeflate(serialize($_value)));
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					$_SESSION[$key] = $value;
					break;
				}
			case self::TYPE_Memcache:
				{
					$old_value = $this->memcache->get($key);
					if($old_value===false)
					{
						$this->memcache->set($key, $value, false, $this->data_lifetime) or die ("Failed to save data at the server");
					}
					else
					{
						$this->memcache->replace($key, $value, false, $this->data_lifetime) or die ("Failed to save data at the server");
					}
					break;
				}
		}
		$this->DoDebug("After serialize = ".$value);
		return $value;
	}

	function MyGet($_key)
	{
		$ret = false;
		$value = null;
		$key = $this->my_key_prefix.$_key;
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					if(array_key_exists($key, $_SESSION)==true)
					{
						$value = $_SESSION[$key];
					}
					break;
				}
			case self::TYPE_Memcache:
				{
					$value = $this->memcache->get($key);
					if($value===false)
					{
						$value = null;
					}
					break;
				}
		}
		$this->DoDebug("Before unserialize = ".$value);
		if(isset($value)==true)
		{
			$value = unserialize(gzinflate(base64_decode($value)));
			$this->DoDebug("After unserialize = ".var_export($value, true));
		}

		return $value;
	}
	
	function MyDelete($_key)
	{
		$key = $this->my_key_prefix.$_key;
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					if(array_key_exists($key, $_SESSION)==true)
					{
						unset($_SESSION[$key]);
					}
					break;
				}
			case self::TYPE_Memcache:
				{
					$this->memcache->delete($key);
					break;
				}
		}
	}

	function GlobalSet($_key, $_value)
	{
		$key = $this->global_key_prefix.$_key;
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					$filename = session_save_path()."/".$this->Category;
					$data = SafeFileRead($filename);
					$data_arr_src = json_decode($data, true);
					$data_arr_src[$key] = $_value;
					$data_arr_dst = json_encode($data_arr_src);
					SafeFileWrite($filename, $data_arr_dst);
					break;
				}
			case self::TYPE_Memcache:
				{
					break;
				}
		}
		
	}
	function GlobalGet($_key)
	{
		$value = null;
		$key = $this->global_key_prefix.$_key;
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					$filename = session_save_path()."/".$this->Category;
					$data = SafeFileRead($filename);
					$data_arr_src = json_decode($data, true);
					if(empty($data_arr_src)==false && array_key_exists($key, $data_arr_src)==true)
					{
						$value = $data_arr_src[$key];
					}
					break;
				}
			case self::TYPE_Memcache:
				{
					break;
				}
		}
		return $value;
	}
	function GlobalDelete($_key)
	{
		$key = $this->global_key_prefix.$_key;
		switch ($this->type)
		{
			case self::TYPE_Session:
				{
					$filename = session_save_path()."/".$this->Category;
					
					$data = SafeFileRead($filename);
					$data_arr_src = json_decode($data, true);
					
					if(array_key_exists($key, $data_arr_src)==true)
					{
						unset($data_arr_src[$key]);
					}
					
					$data_arr_dst = json_encode($data_arr_src);
					SafeFileWrite($filename, $data_arr_dst);
					
					break;
				}
			case self::TYPE_Memcache:
				{
					break;
				}
		}

	}

	////////////////// Callback functions //////////////////////////////////
	function SetDebug($cb)
	{
		$this->DoDebug = $cb;
	}
	function DoDebug($msg)
	{
		if($this->DoDebug!=null)call_user_func($this->DoDebug, $msg);
	}


	////////////////// Internal utility //////////////////////////////////
	function _init_Memcache($memcache_options)
	{
		$memcache = new Memcache;
		$servers = $memcache_options['servers'];
		foreach ($servers as $server)
		{
			$memcache->addServer($server) or die ("Could not connect");
		}
		$memcache->setCompressThreshold(20000, 0.2);

		$version = $memcache->getVersion();
		$this->DoDebug("Server's version: ".$version);
		$stats = $memcache->getStats();
		$this->DoDebug("Stats: ".var_export($stats, true));

		return $memcache;
	}
	function CreateKeyPrefix()
	{
		$server = array_key_exists('SERVER_ADDR', $_SERVER)?$_SERVER['SERVER_ADDR']:"localhost";
		$this->global_key_prefix = $server."_";
		$this->my_key_prefix = $this->global_key_prefix.session_id()."_";
	}
}


?>