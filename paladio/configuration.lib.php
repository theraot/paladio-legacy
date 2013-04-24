<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
		require_once('ini.lib.php');
	}

	final class Configuration
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $INI;
		private static $loadedFiles;
		private static $entries;

		private static function Dispatch()
		{
			if (is_array(Configuration::$entries))
			{
				$keys = array_keys(Configuration::$entries);
				foreach ($keys as $key)
				{
					$entry = Configuration::$entries[$key];
					if(Configuration::CategoryExists($entry['check']))
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
						unset(Configuration::$entries[$key]);
					}
				}
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Callback(/*string*/ $categoryName, /*function*/ $callback = null)
		{
			if (Configuration::CategoryExists($categoryName))
			{
				call_user_func($callback);
			}
			else
			{
				if (is_null($callback))
				{
					$callback = FileSystem::GetIncluderFile();
				}
				else if (!is_callable($callback))
				{
					throw new Exception ('Invalid callback');
				}
				$entry = array('check' => $categoryName, 'callback' => $callback);
				if (is_array(Configuration::$entries))
				{
					Configuration::$entries[] = $entry;
				}
				else
				{
					Configuration::$entries = array($entry);
				}
			}
		}

		public static function CategoryExists(/*mixed*/ $categoryName)
		{
			if (is_string($categoryName))
			{
				if (is_null(Configuration::$INI))
				{
					return false;
				}
				else
				{
					return Configuration::$INI->isset_Category($categoryName);
				}
			}
			else if (is_array($categoryName))
			{
				$categoryNames = $categoryName;
				foreach ($categoryName as $categoryName)
				{
					if (!Configuration::$INI->isset_Category($categoryName))
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

		public static function Load($basePath)
		{
			if (!is_array(Configuration::$loadedFiles))
			{
				Configuration::$loadedFiles = array();
			}
			if (is_null(Configuration::$INI))
			{
				Configuration::$INI = new INI();
			}
			if (is_array($basePath))
			{
				foreach ($basePath as $currentBasePath)
				{
					Configuration::Load($currentBasePath);
				}
			}
			else
			{
				if (is_dir($basePath))
				{
					$files = FileSystem::GetFolderFiles('*.cfg.php', $basePath);
				}
				else
				{
					$files = array($basePath);
				}
				foreach ($files as $file)
				{
					if (!in_array($file, Configuration::$loadedFiles))
					{
						Configuration::$INI->Load($file, 1);
						Configuration::$loadedFiles[] = $file;
					}
				}
				if (Configuration::TryGet('paladio-paths', 'configuration', $extraPath))
				{
					Load($extraPath);
				}
				Configuration::Dispatch();
			}
		}

		public static function Get(/*string*/ $categoryName, /*string*/ $fieldName, /*mixed*/ $default = null)
		{
			if (!is_null(Configuration::$INI) && Configuration::$INI->isset_Field($categoryName, $fieldName))
			{
				return Configuration::$INI->get_Field($categoryName, $fieldName);
			}
			else
			{
				return $default;
			}
		}

		public static function TryGet(/*string*/ $categoryName, /*string*/ $fieldName, /*mixed*/ &$result)
		{
			if (!is_null(Configuration::$INI) && Configuration::$INI->isset_Field($categoryName, $fieldName))
			{
				$result = Configuration::$INI->get_Field($categoryName, $fieldName);
				return true;
			}
			else
			{
				return false;
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

	Configuration::Load(FileSystem::FolderCore());
?>