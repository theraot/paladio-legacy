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

	/**
	 * Configuration
	 * @package Paladio
	 */
	final class Configuration
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $INI;
		private static $loadedFiles;
		private static $entries;

		/**
		 * Internally used to call the callbacks.
		 * @see Configuration::Callback
		 * @access private
		 */
		private static function Dispatch()
		{
			//TODO: create a central callback system for Configuration and Paladio
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

		/**
		 * Creates a notification callback to be executed when the specified category with the name $categoryName becomes available.
		 *
		 * If $callback is null: the including file will be included to handle the notification.
		 * If $callback is callable: it will be called to handle the notification.
		 * Otherwise: throws an exception with the message "Invalid callback".
		 *
		 * @param $categoryName: The name of the requested category. Or an array of names of the requested categories.
		 * @param $callback: The function callback to be executed when the category is available. If null, the requested file will be included.
		 *
		 * @access public
		 * @return bool
		 */
		public static function Callback(/*mixed*/ $categoryName, /*function*/ $callback = null)
		{
			if (Configuration::CategoryExists($categoryName))
			{
				if ($callback === null)
				{
					return false;
				}
				else if (!is_callable($callback))
				{
					throw new Exception ('Invalid callback');
				}
				call_user_func($callback);
				return true;
			}
			else
			{
				if ($callback  === null)
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
				return true;
			}
		}

		/**
		 * Verifies if the category with the name $categoryName is available.
		 *
		 * If the category with the name $categoryName exists returns true, false otherwise.
		 *
		 * @param $categoryName: The name of the requested category.
		 *
		 * @access public
		 * @return bool
		 */
		public static function CategoryExists(/*mixed*/ $categoryName)
		{
			//TODO: move multiple category checks to INI [low priority]
			if (Configuration::$INI  === null)
			{
				return false;
			}
			else
			{
				if (is_string($categoryName))
				{
					return Configuration::$INI->isset_Category($categoryName);
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
		}

		/**
		 * Verifies if the field with the name $fieldName in the category with the name $categoryName is available.
		 *
		 * If the category with the name $categoryName exists and contains a field with the name $fieldName then returns true, false otherwise.
		 *
		 * @param $categoryName: The name of the requested category.
		 * @param $fieldName: The name of the requested category.
		 *
		 * @access public
		 * @return bool
		 */
		public static function FieldExists(/*mixed*/ $categoryName, /*string*/ $fieldName)
		{
			//TODO: move multiple field check to INI [low priority]
			if (Configuration::$INI === null)
			{
				return false;
			}
			else
			{
				if (is_string($categoryName))
				{
					return Configuration::$INI->isset_Field($categoryName, $fieldName);
				}
				else if (is_array($categoryName))
				{
					$categoryNames = $categoryName;
					foreach ($categoryName as $categoryName)
					{
						if (!Configuration::$INI->isset_Field($categoryName, $fieldName))
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
		}

		/**
		 * Loads configuration from the path given in $path. Each file is only loaded once.
		 *
		 * If $path is array: Configuration::Load is called recursively on each element of the array.
		 * If $path is a folder: Loads configuration from each file in the folder with a name in the form "*.cfg.php"
		 * If $path is a file: Loads configuration from the file.
		 * Otherwise: does nothing.
		 *
		 * Note 1: if the loaded configuration includes a configuration field "configuration" in the category "paladio-paths" Configuration::Load is called recursively on the value of the configuration field.
		 * Note 2: if attempts to load a file that was previously loaded the file is skipped.
		 * Note 3: all the loaded files are readed skipping the first line.
		 *
		 * @param $path: The path to load configuration from.
		 *
		 * @access public
		 * @return void
		 */
		public static function Load(/*mixed*/ $path, $recursive = false)
		{
			if (Configuration::$INI === null)
			{
				Configuration::$INI = new INI();
			}
			$count = Paladio::Load($path, '*.cfg.php', array(Configuration::$INI, 'Load'), Configuration::$loadedFiles, $recursive);
			if ($count > 0)
			{
				Configuration::Dispatch();
			}
		}

		/**
		 * Reads the value of the configuration field identified by $fieldName in the category with the name $categoryName.
		 *
		 * Returns the value of the configuration field if it is available, $default otherwise.
		 *
		 * @param $categoryName: The name of the requested category.
		 * @param $fieldName: The name of the requested field.
		 * @param $default: The value to fallback when the field is not available.
		 *
		 * @access public
		 * @return mixed
		 */
		public static function Get(/*string*/ $categoryName, /*string*/ $fieldName, /*mixed*/ $default = null)
		{
			if (Configuration::$INI !== null && Configuration::$INI->isset_Field($categoryName, $fieldName))
			{
				return Configuration::$INI->get_Field($categoryName, $fieldName);
			}
			else
			{
				return $default;
			}
		}

		/**
		 * Attempts to reads the value of the configuration field identified by $fieldName in the category with the name $categoryName.
		 *
		 * Sets $result to the value of the configuration field if it is available, it is left untouched otherwise.
		 *
		 * Returns true if the configuration field is available, false otherwise.
		 *
		 * @param $categoryName: The name of the requested category.
		 * @param $fieldName: The name of the requested field.
		 * @param &$result: Set to the readed value, left untouched if the field is not available.
		 *
		 * @access public
		 * @return bool
		 */
		public static function TryGet(/*string*/ $categoryName, /*string*/ $fieldName, /*mixed*/ &$result)
		{
			if (Configuration::$INI !== null && Configuration::$INI->isset_Field($categoryName, $fieldName))
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

		/**
		 * Creating instances of this class is not allowed.
		 */
		public function __constrct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}

	Configuration::Load(FileSystem::FolderCore());
?>