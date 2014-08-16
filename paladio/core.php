<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('paladio.lib.php');
	}

	if (!function_exists('utf8_stripslashes'))
	{
		/**
		 * Un-quotes a quoted string, UTF-8 aware equivalent of stripslashes.
		 *
		 * Note: $str is expected to be string, no check is performed.
		 *
		 * @return string
		 */
		function utf8_stripslashes($str)
		{
			return preg_replace(array('@\x5C(?!\x5C)@u', '@\x5C\x5C@u'), array('','\\'), $str);
		}
	}

	//Disabling magic quotes at runtime taken from http://php.net/manual/en/security.magicquotes.disabling.php
	if (get_magic_quotes_gpc())
	{
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process))
		{
			foreach ($val as $k => $v)
			{
				unset($process[$key][$k]);
				if (is_array($v))
				{
					$process[$key][utf8_stripslashes($k)] = $v;
					$process[] = &$process[$key][utf8_stripslashes($k)];
				}
				else
				{
					$process[$key][utf8_stripslashes($k)] = utf8_stripslashes($v);
				}
			}
		}
		unset($process);
	}

	ob_start();
	include(FileSystem::ScriptPath());
	$document = ob_get_contents();
	ob_end_clean();

	if (isset($document) && $document !== '')
	{
		$result = Paladio::ProcessDocument($document, FileSystem::ScriptPath(), $_SERVER['QUERY_STRING']);
		ini_set('default_charset', 'UTF-8');
		echo $result;
	}

	exit();
?>