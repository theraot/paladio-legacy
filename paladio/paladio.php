<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		ini_set('default_charset', 'UTF-8');
		mb_internal_encoding('UTF-8');
		error_reporting(E_ALL | E_STRICT);
		require_once('filesystem.lib.php');
		require_once('configuration.lib.php');
		FileSystem::RequireAll('*.lib.php', FileSystem::FolderCore());
	}

	if (!function_exists('stripslashes_utf8'))
	{
		/**
		 * Un-quotes a quoted string, UTF-8 aware equivalent of stripslashes.
		 *
		 * Note: $str is expected to be string, no check is performed.
		 *
		 * @return string
		 */
		function stripslashes_utf8($str)
		{
			//ONLY UTF-8
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
					$process[$key][stripslashes_utf8($k)] = $v;
					$process[] = &$process[$key][stripslashes_utf8($k)];
				}
				else
				{
					$process[$key][stripslashes_utf8($k)] = stripslashes_utf8($v);
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
					foreach (Paladio::$extraPets[$key] as $extraPet)
					{
						//ONLY UTF-8
						if (preg_match('@.*\.'.preg_quote($petName).'\.pet\.php@u', $extraPet))
						{
							if (is_file($extraPet))
							{
								$files[] = $extraPet;
							}
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
						foreach (Paladio::$extraPets[$key] as $extraPet)
						{
							//ONLY UTF-8
							if (preg_match('@'.preg_quote(DIRECTORY_SEPARATOR).preg_quote($petName).'\.pet\.php@u', $extraPet))
							{
								if (is_file($extraPet))
								{
									return $extraPet;
								}
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
				$path = String_Utility::EnsureEnd(FileSystem::FolderCore().$pluginsFolder, DIRECTORY_SEPARATOR);
				$items = FileSystem::GetFolderItems('*', $path);
				$currentPath = getcwd();
				chdir(FileSystem::FolderCore());
				$configurationFiles = array();
				$pluginFiles = array();
				$petFiles = array($path => array());
				foreach ($items as $item)
				{
					if (is_dir($item))
					{
						Configuration::Load(FileSystem::GetFolderFiles('*.cfg.php', $item));
						FileSystem::RequireAll('*.lib.php', $item);
						$petFiles[$item] = FileSystem::GetFolderFiles('*.pet.php', $item);
					}
					else
					{
						//ONLY UTF-8
						if (preg_match('@^.*\.lib\.php$@u', $item))
						{
							$pluginFiles[] = $item;
						}
						//ONLY UTF-8
						else if (preg_match('@^.*\.cfg\.php$@u', $item))
						{
							$configurationFiles[] = $item;
						}
						//ONLY UTF-8
						else if (preg_match('@^.*\.pet\.php$@u', $item))
						{
							$petFiles[$path][] = $item;
						}
					}
				}
				foreach ($configurationFiles as $item)
				{
					Configuration::Load($item);
				}
				foreach ($pluginFiles as $item)
				{
					require_once($item);
				}
				if (!is_array(Paladio::$extraPets))
				{
					Paladio::$extraPets = array();
				}
				foreach ($petFiles as $petFile)
				{
					Paladio::$extraPets[] = $petFile;
				}
				chdir($currentPath);
			}
			Paladio::Dispatch();
		}

		private static function ProcessDocumentFragment($parser, $source, $query)
		{
			$contents = '';
			while($parser->CanConsume())
			{
				$new = $parser->ConsumeUntil(array('<@', '</@'));
				$contents .= $new;
				$elementResult = Paladio::ReadElement($parser, $source, $query, $element);
				if ($elementResult['status'] === false)
				{
					//OPEN
					$documentResult = Paladio::ProcessDocumentFragment($parser, $source, $query);
					$element['contents'] = $documentResult['contents'];
					$new = Paladio::ProcessElement($element);
					$contents .= $new;
					if (!is_null($documentResult['close']))
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

		private static function ProcessElement(/*array*/ $_ELEMENT)
		{
			if (array_key_exists('multiple', $_ELEMENT))
			{
				if ($_ELEMENT['multiple'])
				{
					return Paladio::ProcessMultipleElement($_ELEMENT);
				}
				else
				{
					return Paladio::ProcessSingleElement($_ELEMENT);
				}
			}
			else
			{
				return '';
			}
		}

		private static function ProcessFile(/*array*/ $_ELEMENT, /*string*/ $file)
		{
			ob_start();
			include($file);
			$result = ob_get_contents();
			ob_end_clean();
			return Paladio::ProcessDocument($result, $_ELEMENT['source'], $_ELEMENT['query']);
		}

		private static function ProcessSingleElement(/*array*/ $_ELEMENT)
		{
			$file = Paladio::GetPetFile($_ELEMENT['name'], false);
			if ($file === false)
			{
				if (is_null($_ELEMENT['contents']))
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
				return Paladio::ProcessFile($_ELEMENT, $file);
			}
		}

		private static function ProcessMultipleElement(/*array*/ $_ELEMENT)
		{
			$files = Paladio::GetPetFile($_ELEMENT['name'], true);
			sort($files);
			$result = '';
			foreach ($files as $file)
			{
				$result .= Paladio::ProcessFile($_ELEMENT, $file);
			}
			return $result;
		}

		private static function ReadElement($parser, $source, $query, &$element)
		{
			$whitespace = array("\t", "\n", "\r", "\f", ' ');
			$whitespaceOrClose = array("\t", "\n", "\r", "\f", ' ', '/', '>');
			$attributeNameEnd = array("\t", "\n", "\r", "\f", ' ', '/', '=', '>');
			$close = array('/', '>');
			$isClose = !is_null($parser->Consume('</@')) /*|| !is_null($parser->Consume('<@/'))*/;
			if (!$isClose && is_null($parser->Consume('<@')))
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
					$element = array('name' => $tmp, 'attributes' => array(), 'contents' => null, 'source' => $source, 'query' => $query, 'multiple' => $multiple);
					for(;;)
					{
						$dump = $parser->ConsumeWhile($whitespace);
						$character = $parser->Consume();
						if ($character == '/')
						{
							$tmp = $parser->Consume();
							if ($tmp == '>')
							{
								return array('status' => null, 'contents' => Paladio::ProcessElement($element));
							}
							else
							{
								$parser->Unconsume();
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
							$character = $parser->Consume();
							if ($character == '=')
							{
								$dump = $parser->ConsumeWhile($whitespace);
								$character = $parser->Consume();
								if ($character == '"')
								{
									$tmp = $parser->ConsumeUntil('"');
									$attributeValue = $tmp;
									$dump = $parser->Consume('"');
								}
								else if ($character == "'")
								{
									$tmp = $parser->ConsumeUntil("'");
									$attributeValue = $tmp;
									$dump = $parser->Consume("'");
								}
								else if (in_array($character, $close))
								{
									$parser->Unconsume();
								}
								else
								{
									$new = $parser->ConsumeUntil($whitespaceOrClose);
									$attributeValue = $character.$new;
									if (is_numeric($attributeValue))
									{
										$attributeValue = floatval($attributeValue);
									}
								}
							}
							else
							{
								$parser->Unconsume();
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

		private static function TryGetConfigurationA($fieldName, &$result)
		{
			if (Configuration::TryGet(Paladio::$categoryName, $fieldName, $result))
			{
				return true;
			}
			else if (Configuration::TryGet('paladio', $fieldName, $result))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		private static function TryGetConfigurationB($fieldName, &$result)
		{
			if (Configuration::TryGet('paladio', $fieldName, $result))
			{
				return true;
			}
			else
			{
				return false;
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
			if (is_null(Paladio::$mode) || !Configuration::CategoryExists($tmpCategoryName))
			{
				$function = 'Paladio::TryGetConfigurationB';
			}
			else
			{
				$function = 'Paladio::TryGetConfigurationA';
			}
			if (call_user_func_array($function, array('time_limit', &$result)))
			{
				set_time_limit($result);
			}
			if (call_user_func_array($function, array('error_reporting', &$result)))
			{
				error_reporting($result);
			}
			if (call_user_func_array($function, array('display_errors', &$result)))
			{
				ini_set('display_errors', $result);
			}
			if (call_user_func_array($function, array('timezone', &$result)))
			{
				date_default_timezone_set($result);
			}
			Paladio::LoadPlugins();
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
				if (is_null($callback))
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
				if (is_null($callback))
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

		/**
		 * Processes the string $document resolving any PETs (Paladio Element Templates).
		 *
		 * $document is the string to be procesed. $source is the uri that will be passed to any found PETs.
		 *
		 * Note 1: If the class Parses is not available $document is returned as is.
		 * Note 2: $document is expected to be string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessDocument($document, $source, $query)
		{
			if (class_exists('Parser'))
			{
				$parser = new Parser($document);
				$documentResult = Paladio::ProcessDocumentFragment($parser, $source, $query);
				return $documentResult['contents'];
			}
			else
			{
				return $document;
			}
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

	ob_start();
	include(FileSystem::ScriptPath());
	$document = ob_get_contents();
	ob_end_clean();

	$result = Paladio::ProcessDocument($document, FileSystem::ScriptPath(), $_SERVER['QUERY_STRING']);
	echo $result;

	exit();
?>