<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
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
		public static function BuildAttributesString($attributes)
		{
			if (is_null($attributes))
			{
				return '';
			}
			else
			{
				$result = array();
				foreach($attributes as $attributeName => $attributeValue)
				{
					if (!is_null($attributeValue))
					{
						if (is_string($attributeName))
						{
							$result[] = $attributeName.' = "'.$attributeValue.'"';
						}
						else
						{
							$result[] = $attributeValue;
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
			if (is_null($classes))
			{
				return '';
			}
			else
			{
				$result = array();
				foreach($classes as $class)
				{
					if (!is_null($class))
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
	}
?>