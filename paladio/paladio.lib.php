<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		mb_internal_encoding('UTF-8');
		error_reporting(E_ALL | E_STRICT);
		require_once('filesystem.lib.php');
		require_once('configuration.lib.php');
		require_once('parser.lib.php');
		FileSystem::RequireAll('*.lib.php', FileSystem::FolderCore());
	}

	//TODO: create forms.lib.php

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

	/**
	 * Paladio
	 * @package Paladio
	 */
	final class Paladio
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $mode;
		private static $categoryName;
		private static $entries;
		private static $extraPets;
		
		private static function _Load($path, $pattern, $callback, &$done, $recursive)
		{
			$return = 0;
			$currentPath = getcwd();
			chdir(FileSystem::FolderCore());
			if (is_dir($path))
			{
				if ($recursive)
				{
					$files = FileSystem::GetFolderFilesRecursive($pattern, $path);
				}
				else
				{
					$files = FileSystem::GetFolderFiles($pattern, $path);
				}
			}
			else
			{
				$files = array($path);
			}
			foreach ($files as $file)
			{
				if (!in_array($file, $done))
				{
					call_user_func($callback, $file);
					$done[] = $file;
					$return++;
				}
			}
			chdir($currentPath);
			return $return;
		}

		private static function ClassExists(/*string*/ $className)
		{
			if (is_string($className))
			{
				return class_exists($className);
			}
			else if (is_array($className))
			{
				$classNames = $className;
				foreach($classNames as $className)
				{
					if (!class_exists($className))
					{
						return false;
					}
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		private static function Dispatch()
		{
			if (is_array(Paladio::$entries))
			{
				$keys = array_keys(Paladio::$entries);
				foreach ($keys as $key)
				{
					$entry = Paladio::$entries[$key];
					if (Paladio::ClassExists($entry['check']))
					{
						$callback = $entry['callback'];
						if (is_callable($callback))
						{
							call_user_func($callback);
						}
						else
						{
							include($callback);
						}
						unset(Paladio::$entries[$key]);
					}
				}
			}
		}

		private static function GetPetFile(/*string*/ $petName, /*bool*/ $multiple)
		{
			if ($multiple)
			{
				$files = FileSystem::GetFolderFiles('*.'.$petName.'.pet.php', FileSystem::FolderInstallation());
				$keys = array_keys(Paladio::$extraPets);
				foreach ($keys as $key)
				{
					$extraPet = Paladio::$extraPets[$key];
					if (preg_match('@.*\.'.preg_quote($petName).'\.pet\.php@u', $extraPet))
					{
						if (is_file($extraPet))
						{
							$files[] = $extraPet;
						}
					}
				}
				return $files;
			}
			else
			{
				$file = FileSystem::FolderInstallation().$petName.'.pet.php';
				if (is_file($file))
				{
					return $file;
				}
				else
				{
					$keys = array_keys(Paladio::$extraPets);
					foreach ($keys as $key)
					{
						$extraPet = Paladio::$extraPets[$key];
						if (preg_match('@'.preg_quote(DIRECTORY_SEPARATOR).preg_quote($petName).'\.pet\.php@u', $extraPet))
						{
							if (is_file($extraPet))
							{
								return $extraPet;
							}
						}
					}
					return false;
				}
			}
		}

		private static function LoadPlugins()
		{
			if (Configuration::TryGet('paladio-paths', 'plugins', $pluginsFolder))
			{
				$path = String_Utility::EnsureEnd(FileSystem::ResolveRelativePath(FileSystem::FolderCore(), $pluginsFolder, DIRECTORY_SEPARATOR) , DIRECTORY_SEPARATOR);
				$currentPath = getcwd();
				chdir(FileSystem::FolderCore());
				if (!is_array(Paladio::$extraPets))
				{
					Paladio::$extraPets = array();
				}
				FileSystem::RequireAll(array('*.db.php', '*.lib.php'), $path, true);
				chdir($currentPath);
				Paladio::Dispatch();
				$petFiles = array();
				Paladio::Load($path, '*.pet.php', array('Paladio', 'RegisterPET'), $petFiles, true);
				Configuration::Load($path, true);
			}
		}

		private static function ProcessDocumentFragment($parser, $path, $source, $query, $parent)
		{
			$contents = '';
			while($parser->CanConsume())
			{
				$new = $parser->ConsumeUntil(array('<@', '</@'));
				$contents .= $new;
				$elementResult = Paladio::ReadElement($parser, $path, $source, $query, $element);
				if ($elementResult['status'] === false)
				{
					//OPEN
					$element['__parent'] = $parent;
					$documentResult = Paladio::ProcessDocumentFragment($parser, $path, $source, $query, $element);
					$element['contents'] = $documentResult['contents'];
					$new = Paladio::ProcessElement($element, false);
					$contents .= $new;
					if ($documentResult['close'] !== null)
					{
						if ($element['name'] != $documentResult['close'])
						{
							return array('close' => $element['name'], 'contents' => $contents);
						}
					}
				}
				else if ($elementResult['status'] === true)
				{
					//CLOSE
					if ($elementResult['name'] == '')
					{
						return array('close' => null, 'contents' => $contents);
					}
					else
					{
						$name = $elementResult['name'];
						return array('close' => $name, 'contents' => $contents);
					}
				}
				else if ($elementResult['status'] === null)
				{
					$new = $elementResult['contents'];
					$contents .= $new;
				}
			}
			return array('close' => null, 'contents' => $contents);
		}

		private static function ProcessFile(/*array*/ $_ELEMENT, /*string*/ $file, /*bool*/ $once)
		{
			ob_start();
			if ($once)
			{
				include_once($file);
			}
			else
			{
				include($file);
			}
			$result = ob_get_contents();
			ob_end_clean();
			return Paladio::ProcessDocument($result, $_ELEMENT['source'], $_ELEMENT['query']);
		}

		private static function ProcessSingleElement(/*array*/ $_ELEMENT, /*bool*/ $once)
		{
			$file = Paladio::GetPetFile($_ELEMENT['name'], false);
			if ($file === false)
			{
				if ($_ELEMENT['contents'] === null)
				{
					return '';
				}
				else
				{
					return $_ELEMENT['contents'];
				}
			}
			else
			{
				return Paladio::ProcessFile($_ELEMENT, $file, $once);
			}
		}

		private static function ProcessMultipleElement(/*array*/ $_ELEMENT, /*bool*/ $once)
		{
			$files = Paladio::GetPetFile($_ELEMENT['name'], true);
			sort($files);
			$result = '';
			foreach ($files as $file)
			{
				$result .= Paladio::ProcessFile($_ELEMENT, $file, $once);
			}
			return $result;
		}

		private static function ReadElement($parser, $path, $source, $query, &$element)
		{
			$whitespace = array("\t", "\n", "\r", "\f", ' ');
			$whitespaceOrClose = array("\t", "\n", "\r", "\f", ' ', '/', '>');
			$attributeNameEnd = array("\t", "\n", "\r", "\f", ' ', '/', '=', '>');
			$close = array('/', '>');
			$isClose = $parser->Consume('</@') !== null;
			if (!$isClose && $parser->Consume('<@') === null)
			{
				$result = $parser->ConsumeAll();
				if ($result === false)
				{
					return array('status' => null, 'contents' => '');
				}
				else
				{
					return array('status' => null, 'contents' => $result);
				}
			}
			else
			{
				if ($parser->Consume('@'))
				{
					$multiple = true;
				}
				else
				{
					$multiple = false;
				}
				if ($isClose)
				{
					$tmp = $parser->ConsumeUntil('>');
					$name = trim($tmp);
					$dump = $parser->Consume('>');
					return array('status' => true, 'name' => $name);
				}
				else
				{
					$tmp = $parser->ConsumeUntil($whitespaceOrClose);
					$element = array('name' => $tmp, 'attributes' => array(), 'contents' => null, 'path' => $path, 'source' => $source, 'query' => $query, 'multiple' => $multiple);
					for(;;)
					{
						$dump = $parser->ConsumeWhile($whitespace);
						$character = $parser->Consume();
						if ($character == '/')
						{
							$tmp = $parser->Peek();
							if ($tmp == '>')
							{
								$parser->Consume();
								return array('status' => null, 'contents' => Paladio::ProcessElement($element, false));
							}
						}
						if ($character == '>')
						{
							return array('status' => false);
						}
						else if ($character == false)
						{
							return array('status' => null, 'contents' => '');
						}
						else
						{
							$new = $parser->ConsumeUntil($attributeNameEnd);
							if ($character == '/')
							{
								$attributeName = $new;
							}
							else
							{
								$attributeName = $character.$new;
							}
							$attributeValue = null;
							$dump = $parser->ConsumeWhile($whitespace);
							$character = $parser->Peek();
							if ($character == '=')
							{
								$parser->Consume();
								$dump = $parser->ConsumeWhile($whitespace);
								$character = $parser->Peek();
								if ($character == '"')
								{
									$parser->Consume();
									$tmp = $parser->ConsumeUntil('"');
									$attributeValue = $tmp;
									$dump = $parser->Consume('"');
								}
								else if ($character == "'")
								{
									$parser->Consume();
									$tmp = $parser->ConsumeUntil("'");
									$attributeValue = $tmp;
									$dump = $parser->Consume("'");
								}
								else if (in_array($character, $close))
								{
									//Skip
								}
								else
								{
									$attributeValue = $parser->ConsumeUntil($whitespaceOrClose);
									if (is_numeric($attributeValue))
									{
										$attributeValue = floatval($attributeValue);
									}
								}
							}
							if (!array_key_exists($attributeName, $element['attributes']))
							{
								$element['attributes'][$attributeName] = $attributeValue;
							}
						}
					}
				}
			}
		}

		private static function RegisterPET($petFile)
		{
			Paladio::$extraPets[] = $petFile;
		}
		
		private static function SetLocale($locale)
		{
			$codeset = "utf8";
			$attempts = array($locale.'.'.$codeset, $locale);
			$found = setlocale (LC_ALL, $attempts);
			if ($found)
			{
				putenv('LANG='.$found);
				putenv('LANGUAGE='.$found);
				setlocale (LC_COLLATE, $found);
				setlocale (LC_CTYPE, $found);
				setlocale (LC_MONETARY, $found);
				setlocale (LC_NUMERIC, $found);
				setlocale (LC_TIME, $found);
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Performes static initialization.
		 *
		 * @access public
		 * @return void
		 */
		public static function __Init()
		{
			Paladio::$mode = null;
			if (Configuration::CategoryExists('paladio'))
			{
				Configuration::TryGet('paladio', 'mode', Paladio::$mode);
			}
			$tmpCategoryName = 'paladio-'.Paladio::$mode;
			if (Paladio::$mode === null || !Configuration::CategoryExists($tmpCategoryName))
			{
				Paladio::$categoryName = 'paladio';
				if (Configuration::TryGet('paladio', 'time_limit', $result))
				{
					set_time_limit($result);
				}
				if (Configuration::TryGet('paladio', 'error_reporting', $result))
				{
					error_reporting($result);
				}
				if (Configuration::TryGet('paladio', 'display_errors', $result))
				{
					ini_set('display_errors', $result);
				}
				if (Configuration::TryGet('paladio', 'timezone', $result))
				{
					date_default_timezone_set($result);
				}
				if (Configuration::TryGet('paladio', 'locale', $result))
				{
					Paladio::SetLocale($result);
				}
			}
			else
			{
				Paladio::$categoryName = $tmpCategoryName;
				if (Configuration::TryGet(Paladio::$categoryName, 'time_limit', $result) || Configuration::TryGet('paladio', 'time_limit', $result))
				{
					set_time_limit($result);
				}
				if (Configuration::TryGet(Paladio::$categoryName, 'time_limit', $result) || Configuration::TryGet('paladio', 'error_reporting', $result))
				{
					error_reporting($result);
				}
				if (Configuration::TryGet(Paladio::$categoryName, 'time_limit', $result) || Configuration::TryGet('paladio', 'display_errors', $result))
				{
					ini_set('display_errors', $result);
				}
				if (Configuration::TryGet(Paladio::$categoryName, 'time_limit', $result) || Configuration::TryGet('paladio', 'timezone', $result))
				{
					date_default_timezone_set($result);
				}
				if (Configuration::TryGet(Paladio::$categoryName, 'time_limit', $result) || Configuration::TryGet('paladio', 'locale', $result))
				{
					Paladio::SetLocale($result);
				}
			}
			Paladio::LoadPlugins();
		}

		public static function Load($path, $pattern, $callback, &$done, $recursive = false)
		{
			$return = 0;
			if (is_string($pattern) && is_callable($callback))
			{
				if (!is_array($done))
				{
					$done = array();
				}
				if (is_array($path))
				{
					foreach ($path as $currentPath)
					{
						$return += Paladio::_Load($currentPath, $pattern, $callback, $done, $recursive);
					}
				}
				else
				{
					$return += Paladio::_Load($path, $pattern, $callback, $done, $recursive);
				}
			}
			Paladio::Dispatch();
			return $return;
		}

		/**
		 * Creates a notification callback to be executed when the specified class with the name $className becomes available.
		 *
		 * If $callback is null: the including file will be included to handle the notification.
		 * If $callback is callable: it will be called to handle the notification.
		 * Otherwise: throws an exception with the message "Invalid callback".
		 *
		 * @access public
		 * @return void
		 */
		public static function Request($className, $callback = null)
		{
			if (Paladio::ClassExists($className))
			{
				if ($callback === null)
				{
					return false;
				}
				else if (!is_callable($callback))
				{
					throw new Exception('invalid callback');
				}
				call_user_func($callback);
				return true;
			}
			else
			{
				if ($callback === null)
				{
					$callback = FileSystem::GetIncludedFile();
				}
				else if (!is_callable($callback))
				{
					throw new Exception('invalid callback');
				}
				$entry = array('check' => $className, 'callback' => $callback);
				if (is_array(Paladio::$entries))
				{
					Paladio::$entries[] = $entry;
				}
				else
				{
					Paladio::$entries = array($entry);
				}
				return true;
			}
		}

		public static function ProcessElement(/*array*/ $_ELEMENT, /*bool*/ $once)
		{
			if (array_key_exists('multiple', $_ELEMENT))
			{
				if ($_ELEMENT['multiple'])
				{
					return Paladio::ProcessMultipleElement($_ELEMENT, $once);
				}
				else
				{
					return Paladio::ProcessSingleElement($_ELEMENT, $once);
				}
			}
			else
			{
				return '';
			}
		}

		/**
		 * Processes the string $document resolving any PETs (Paladio Element Templates).
		 *
		 * $document is the string to be procesed. $source is the uri that will be passed to any found PETs.
		 *
		 * Note 1: If the class Parser is not available $document is returned as is.
		 * Note 2: $document is expected to be string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessDocument($document, $source, $query)
		{
			$parser = new Parser($document);
			$path = FileSystem::CreateRelativePath(dirname($source), FileSystem::FolderInstallation(), '/');
			$documentResult = Paladio::ProcessDocumentFragment($parser, $path, $source, $query, null);
			return $documentResult['contents'];
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __constrct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}

	Paladio::__Init();
?>