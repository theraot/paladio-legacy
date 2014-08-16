<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
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

		private static $data;
		private static $callbacks;

		/**
		 * Internally used to call the callbacks.
		 * @see Configuration::Callback
		 * @access private
		 */
		private static function Dispatch(/*string*/ $categoryName)
		{
			//TODO: create a central callback system for Configuration and Paladio
			if (is_array(Configuration::$callbacks))
			{
				if (array_key_exists($categoryName, Configuration::$callbacks))
				{
					foreach($Configuration::$callbacks[$categoryName] as $callback)
					{
						if (is_callable($callback))
						{
							call_user_func($callback);
						}
						else
						{
							include($callback);
						}
					}
					unset(Configuration::$callbacks[$categoryName]);
				}
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Add(/*array*/ $configuration)
		{
			if (Configuration::$data === null)
			{
				Configuration::$data = array();
			}
			if (is_array($configuration))
			{
				$keys = array_keys($configuration);
				foreach ($keys as $key)
				{
					$val = $configuration[$key];
					if (is_array($val))
					{
						$found = array_key_exists($key, Configuration::$data);
						Configuration::$data[$key] = $val;
						if ($found)
						{
							Configuration::Dispatch($key);
						}
					}
					else
					{
						if (!array_key_exists('', Configuration::$data) || !is_array(Configuration::$data['']))
						{
							Configuration::$data[''] = array();
						}
						Configuration::$data[''][$key] = $val;
					}
				}
			}
		}

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
				//--
				if (!is_array(Configuration::$callbacks))
				{
					Configuration::$callbacks = array();
				}
				//--
				if (is_array($categoryName))
				{
					$categoryNames = $categoryName;
				}
				else
				{
					$categoryNames = array($categoryName);
				}
				foreach($categoryNames as $categoryName)
				{
					if (array_key_exists($categoryName, Configuration::$callbacks))
					{
						Configuration::$callbacks[$categoryName][] = $callback;
					}
					else
					{
						Configuration::$callbacks[$categoryName] = array($callback);
					}
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
			if (Configuration::$data  === null)
			{
				return false;
			}
			else
			{
				if (is_string($categoryName))
				{
					return array_key_exists($categoryName, Configuration::$data);
				}
				else if (is_array($categoryName))
				{
					$categoryNames = $categoryName;
					foreach ($categoryName as $categoryName)
					{
						if (!array_key_exists($categoryName, Configuration::$data))
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
			if (Configuration::$data === null)
			{
				return false;
			}
			else
			{
				if (is_string($categoryName))
				{
					return array_key_exists($categoryName, Configuration::$data) && array_key_exists($fieldName, Configuration::$data[$categoryName]);
				}
				else if (is_array($categoryName))
				{
					$categoryNames = $categoryName;
					foreach ($categoryName as $categoryName)
					{
						if (!array_key_exists($categoryName, Configuration::$data) && array_key_exists($fieldName, Configuration::$data[$categoryName]))
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
			if (Configuration::$data !== null && array_key_exists($categoryName, Configuration::$data) && array_key_exists($fieldName, Configuration::$data[$categoryName]))
			{
				return Configuration::$data[$categoryName][$fieldName];
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
			if (Configuration::$data !== null && array_key_exists($categoryName, Configuration::$data) && array_key_exists($fieldName, Configuration::$data[$categoryName]))
			{
				$result = Configuration::$data[$categoryName][$fieldName];
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
?>