<?php

class iniFileLibs{

	var $FileName = null;

	function read($filename = self::FileName, $process_sections = false, $scanner_mode = INI_SCANNER_NORMAL){
		if($filename==null)
		{
			throw new Exception("No ini filename");
			return;
		}
		return parse_ini_file($filename, $process_sections, $scanner_mode);
	}
	function write($assoc_arr, $filename = self::FileName, $has_sections = FALSE){
		if($filename==null)
		{
			throw new Exception("No ini filename");
			return;
		}
		write_ini_file($assoc_arr, $filename, $has_sections);
	}


	function write_ini_file($assoc_arr, $path, $has_sections = FALSE)
	{
		$content = "";

		if ($has_sections) {
			foreach ($assoc_arr as $key=>$elem) {
				$content .= "[".$key."]\n";
				foreach ($elem as $key2=>$elem2)
				{
					if(is_array($elem2))
					{
						for($i=0;$i<count($elem2);$i++)
						{
							$content .= $key2."[] = \"".$elem2[$i]."\"\n";
						}
					}
					else if($elem2=="") $content .= $key2." = \n";
					else $content .= $key2." = \"".$elem2."\"\n";
				}
			}
		}
		else
		{
			foreach ($assoc_arr as $key=>$elem) {
				if(is_array($elem))
				{
					for($i=0;$i<count($elem);$i++)
					{
						$content .= $key2."[] = \"".$elem[$i]."\"\n";
					}
				}
				else if($elem=="") $content .= $key2." = \n";
				else $content .= $key2." = \"".$elem."\"\n";
			}
		}

		if (!$handle = fopen($path, 'w'))
		{
			return false;
		}

		if (!fwrite($handle, $content))
		{
			return false;
		}

		fclose($handle);
		return true;
	}
}

?>