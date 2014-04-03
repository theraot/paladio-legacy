<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('utility.lib.php');
	}
	
	/**
	 * Pet_Utility
	 * @package Paladio
	 */
	final class PET_Utility
	{
		/**
		 * Creates a string that represent the attributes.
		 *
		 * @param $attributes: the attributes to process.
		 *
		 * @access public
		 * @return string
		 */
		public static function BuildAttributesString(/*array*/ $attributes, /*mixed*/ $filter = null)
		{
			if ($attributes === null)
			{
				return '';
			}
			else
			{
				if ($filter !== null)
				{
					$attributes = Utility::ArrayTake($attributes, $filter);
				}
				$result = array();
				$keys = Utility::ArraySort(array_keys($attributes));
				$index = 0;
				foreach($keys as $attributeName)
				{
					$attributeValue = $attributes[$attributeName];
					if ($attributeValue !== null)
					{
						if ($index === $attributeName)
						{
							$result[] = $attributeValue;
							$index++;
						}
						else
						{
							$result[] = $attributeName.' = "'.$attributeValue.'"';
						}
					}
				}
				if (count($result) === 0)
				{
					return '';
				}
				else
				{
					return ' '.implode(' ', $result);
				}
			}
		}
		
		/**
		 * Creates a string that represent the classes.
		 *
		 * @param $classes: the classes to process.
		 *
		 * @access public
		 * @return string
		 */
		public static function BuildClassesString($classes)
		{
			if ($classes === null)
			{
				return '';
			}
			else
			{
				$result = array();
				foreach($classes as $class)
				{
					if ($class !== null)
					{
						$result[] = $class;
					}
				}
				if (count($result) === 0)
				{
					return '';
				}
				else
				{
					return ' class = "'.implode(' ', $result).'"';
				}
			}
		}
		
		/**
		 * Invokes a PET and returns the resulting output.
		 *
		 * @param $pet: the name of the PET to invoke.
		 * @param $attributes: the attributes to pass to the PET.
		 * @param $contents: the contents to pass to the PET.
		 * @param $multiple: indicates if the requested PET is a multi-PET.
		 *
		 * @access public
		 * @returns string
		 */
		public static function PETInvoke($pet, $attributes = null, $contents = null, $multiple = false)
		{
			if ($attributes === null)
			{
				$attributes = array();
			}
			if (!is_array($attributes))
			{
				$attributes = array($attributes);
			}
			$source = FileSystem::ScriptPath();
			$query = $_SERVER['QUERY_STRING'];
			$path = FileSystem::CreateRelativePath(dirname($source), FileSystem::FolderInstallation(), '/');
			$element = array('name' => $pet, 'attributes' => $attributes, 'contents' => $contents, 'path' => $path, 'source' => $source, 'query' => $query, 'multiple' => $multiple);
			return Paladio::ProcessElement($element, false);
		}
		
		/**
		 * Invokes a PET and returns the resulting output.
		 *
		 * Note: returns empty string if the PET has been already invoked during the current request.
		 *
		 * @param $pet: the name of the PET to invoke.
		 * @param $attributes: the attributes to pass to the PET.
		 * @param $contents: the contents to pass to the PET.
		 * @param $multiple: indicates if the requested PET is a multi-PET.
		 *
		 * @access public
		 * @returns string
		 */
		public static function PETInvokeOnce($pet, $attributes = null, $contents = null, $multiple = false)
		{
			if ($attributes === null)
			{
				$attributes = array();
			}
			if (!is_array($attributes))
			{
				$attributes = array($attributes);
			}
			$source = FileSystem::ScriptPath();
			$query = $_SERVER['QUERY_STRING'];
			$path = FileSystem::CreateRelativePath(dirname($source), FileSystem::FolderInstallation(), '/');
			$element = array('name' => $pet, 'attributes' => $attributes, 'contents' => $contents, 'path' => $path, 'source' => $source, 'query' => $query, 'multiple' => $multiple);
			return Paladio::ProcessElement($element, true);
		}
	}
?>