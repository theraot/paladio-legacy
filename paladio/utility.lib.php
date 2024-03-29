<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('string_utility.lib.php');
	}

	if (!function_exists('array_replace_recursive'))
	{
		function array_replace_recursive($array /*, $arrays*/)
		{
			$args = func_get_args();
			if (is_array($array))
			{
				for ($index = 1; $index < count($args); $index++)
				{
					$target = $args[$index];
					if (is_array($target))
					{
						foreach ($target as $key => $value)
						{
							if
							(
								array_key_exists($key, $array)
								&& is_array($array[$key])
								&& is_array($target[$key])
							)
							{
								$array[$key] = array_replace_recursive($array[$key], $target[$key]);
							}
							else
							{
								$array[$key] = $target[$key];
							}
						}
					}
				}
			}
			return $array;
		}
	}

	if (!function_exists('array_replace'))
	{
		function array_replace($array /*, $arrays*/)
		{
			$args = func_get_args();
			if (is_array($array))
			{
				for ($index = 1; $index < count($args); $index++)
				{
					$target = $args[$index];
					if (is_array($target))
					{
						foreach ($target as $key => $value)
						{
							$array[$key] = $target[$key];
						}
					}
					else
					{
						return null;
					}
				}
			}
			return $array;
		}
	}

	/**
	 * Utility
	 * @package Paladio
	 */
	final class Utility
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		/**
		 * Used internally to sort arrays
		 */
		private static function Cmp($a, $b)
		{
			if ($a === $b)
			{
				return 0;
			}
			else
			{
				if (is_string($a))
				{
					if (is_string($b))
					{
						return strcoll($a, $b);
					}
					else
					{
						return 1;
					}
				}
				else
				{
					if (is_string($b))
					{
						return -1;
					}
					else
					{
						return ($a < $b) ? -1 : 1;
					}
				}
			}
		}

		/**
		 * Used internally to sort arrays
		 */
		private static function CmpIconv($a, $b)
		{
			if ($a === $b)
			{
				return 0;
			}
			else
			{
				if (is_string($a))
				{
					if (is_string($b))
					{
						$charset = $charset = String_Utility::DiscoverIconvCharset();
						return strcoll(iconv('utf-8', $charset, $a), iconv('utf-8', $charset, $b));
					}
					else
					{
						return 1;
					}
				}
				else
				{
					if (is_string($b))
					{
						return -1;
					}
					else
					{
						return ($a < $b) ? -1 : 1;
					}
				}
			}
		}
		
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Creates a new array with the numeric keys arranged and nulls removed.
		 *
		 * @param $array: the array to process.
		 *
		 * @access public
		 * @return array
		 */
		public static function ArrayCompact(/*array*/ $array)
		{
			$result = array();
			$keys = Utility::ArraySort(array_keys($array));
			$index = 0;
			foreach ($keys as $key)
			{
				$value = $array[$key];
				if ($value !== null)
				{
					if ($index === $key)
					{
						$result[] = $value;
						$index++;
					}
					else
					{
						$result[$key] = $value;
					}
				}
			}
			return $result;
		}

		/**
		 * Creates a new array containing items except those that match the criteria.
		 *
		 * @param $array: the array to process.
		 * @param $criteria: the criteria to remove.
		 *
		 * @access public
		 * @return array
		 */
		public static function ArrayRemove(/*array*/ $array, /*mixed*/ $criteria)
		{
			$result = array();
			if (!is_array($criteria))
			{
				$criteria = array($criteria);
			}
			foreach ($array as $key => $value)
			{
				foreach ($criteria as $predicate)
				{
					if (is_callable($predicate))
					{
						if (call_user_func($predicate, $value))
						{
							continue;
						}
					}
					else if ($predicate === $value)
					{
						continue;
					}
					$result[$key] = $value;
				}
			}
			return $result;
		}

		/**
		 * Creates a new array containing items except those that belong to the given keys.
		 *
		 * @param $array: the array to process.
		 * @param $keys: the keys to skip.
		 *
		 * @access public
		 * @return array
		 */
		public static function ArraySkip(/*array*/ $array, /*mixed*/ $keys)
		{
			$result = array();
			if ($keys === null)
			{
				return $array;
			}
			else if (is_string($keys))
			{
				foreach ($array as $key => $value)
				{
					if ($key === $keys)
					{
						if ($value !== null)
						{
							$result[$key] = $value;
						}
						break;
					}
				}
				return $result;
			}
			else
			{
				foreach ($array as $key => $value)
				{
					if (!in_array($key, $keys))
					{
						if ($value !== null)
						{
							$result[$key] = $value;
						}
					}
				}
				return $result;
			}
		}
		
		/**
		 * Creates a new array containing items sorted by value.
		 *
		 * Note 1: Keys are not preserved.
		 * Note 2: Default sorting will put numbers in ascending value first and then strings in lexicological order.
		 *
		 * @param $array: the array to process.
		 * @param $compare: the comparison method to sort the items.
		 *
		 * @access public
		 * @return array
		 */
		public static function ArraySort(/*array*/ $array, /*function*/ $compare = null)
		{
			if ($compare === null)
			{
				$charset = String_Utility::DiscoverIconvCharset();
				if ($charset === 'UTF-8')
				{
					usort($array, array('Utility', 'Cmp'));
				}
				else
				{
					usort($array, array('Utility', 'CmpIconv'));
				}
			}
			else
			{
				usort($array, $compare);
			}
			return $array;
		}

		/**
		 * Creates a new array containing only the items that belong to the given keys.
		 *
		 * @param $array: the array to process.
		 * @param $keys: the keys to take.
		 *
		 * @access public
		 * @return array
		 */
		public static function ArrayTake(/*array*/ $array, /*mixed*/ $keys = null)
		{
			if ($keys === null)
			{
				return array();
			}
			else if (is_string($keys))
			{
				return array($keys => $array[$keys]);
			}
			else
			{
				$result = array();
				$_keys = Utility::ArraySort(array_keys($keys));
				$index = 0;
				foreach ($_keys as $key)
				{
					$alias = $keys[$key];
					if ($index === $key)
					{
						$key = $alias;
						$index++;
					}
					if (array_key_exists($key, $array))
					{
						$value = $array[$key];
						if ($value !== null)
						{
							$result[$alias] = $value;
						}
					}
				}
				return $result;
			}
		}

		/**
		 * Creates a new string with the dangerous characters escaped. Use this function to present data to the user.
		 *
		 * Note 1: There are two supported escape encodings: html and url.
		 * Note 2: The following charactes are cosidered dangerous:
		 *  - Control characters
		 *  - quotations: " and '
		 *  - delimiters: <, >, (, ), {, }, [, ]
		 *  - other symbols: \, %, &, ;, :, @
		 *
		 * @param $data: the input to sanitize.
		 * @param $encoding: the desired escape encoding.
		 *
		 * @access public
		 * @return mixed
		 */
		public static function Sanitize(/*mixed*/ $data, /*string*/ $encoding = 'html')
		{
			if (!is_string($encoding))
			{
				throw new Exception('invalid encoding');
			}
			$encoding = mb_strtolower($encoding);
			if ($encoding != 'html' && $encoding != 'url')
			{
				throw new Exception('unknown encoding');
			}

			if (is_array($data))
			{
				$result = array();
				$items = array_keys($data);
				foreach ($items as $item)
				{
					if (array_key_exists($item, $data))
					{
						$result[$item] = Utility::Sanitize($data[$item], $encoding);
					}
					else
					{
						$result[$item] = '';
					}
				}
				return $result;
			}
			else
			{
				//"\0" : dangerous to C++ and SQL
				//"\n", "\r" : new line removed from html and dangerous to SQL
				//"\\", "'", '"', "\x1a" : dangerous to SQL
				//"%" : url encoding
				//"&", ";" : html encoding
				// "<", ">" : dangerous to html
				//"(", ")" : used in SQL injection
				//"[", "]", ":" : dangerous to PEN and JSON
				//"{", "}" : dangerous to JSON
				//"@" : dangerous to paladio

				$data = trim($data);

				//\x00-\x1F
				//0 -> 6    : non-printable                    -> removed
				//7         : bell (\a)                        -> removed
				//8         : backspace (\b)                   -> removed
				//9         : horizontal tab (\t)              -> removed
				//10        : line feed (\n)                   -> removed
				//11        : vertical tab (\v)                -> removed
				//12        : from feed (\f)                   -> removed
				//13        : carriage return (\r)             -> removed
				//14 -> 26  : non-printable                    -> removed
				//27        : escape (\e)                      -> removed
				//28 -> 31  : non-printable                    -> removed
				//\x7F-\x9F
				//127       : delete                           -> removed
				//\x80-\x9F
				//128 - 159 : Latin-1 supplement non-printable -> removed
				$data = preg_replace('/[\x00-\x1F\x7F\x80-\x9F]/u', '', $data);

				if ($encoding == 'url')
				{
					$danger = array("\\"   , "'"    , '"'     , '%'     , '&'    ,  '<'  , '>'   , '('    , ')'    , '{'     , '}'     , '['    , ']'    , ':'    , '@'    );
					$secure = array('%5C' , '%27'   , '%22'   , '%25'   , '%26'  , '%3C' , '%3E' , '%28'  , '%29'  , '%7B'   , '%7D'   , '%5B'  , '%5D'  , '%3A'  , '%40'  );
				}
				else if ($encoding == 'html')
				{
					$danger = array("\\"   , "'"    , '"'     , '%'     , '&'    ,  '<'  , '>'   , '('    , ')'    , '{'     , '}'     , '['    , ']'    , ':'    , '@'    );
					$secure = array('&#92;', '&#39;', '&quot;', '&#37;' , '&amp;', '&lt;', '&gt;', '&#40;', '&#41;', '&#123;', '&#125;', '&#91;', '&#93;', '&#58;', '&#64;');
				}
				$data = str_replace($danger, $secure, $data);

				return $data;
			}
		}

		/**
		 * Validates if the given string is a valid email
		 *
		 * @param $email: the input to validate.
		 *
		 * @access public
		 * @return bool
		 */
		public static function ValidateEmail(/*string*/ $email)
		{
			$email = mb_strtolower($email); //solo minusculas
			//$caracter = '[a-zA-Z0-9_%+-]'; //character ::= {a-z} | {A-Z} | {0-9} | "_" | "%" | "+" | "-"
			$character = '[a-z0-9_%+-]'; //character ::= {a-z} | {0-9} | "_" | "%" | "+" | "-"
			$text = $character.'+'; //text ::= character+ //One or more
			$domain = $text.'(\.'.$text.')*'; //domain :: = text ("." + text)* /*text followed by none or many ("." followed by text).
			$pattern = $domain.'@'.$domain; //pattern ::= domain "@" domain /*domain followed by "@" followed by domain
			if(preg_match('#^'.$pattern.'$#u', $email))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Validates if the given string is a valid number in the given range.
		 *
		 * @param $number: the input to validate.
		 * @param $min: the minimun valid value.
		 * @param $max: the maximun valid value.
		 *
		 * @access public
		 * @return bool
		 */
		public static function ValidateNumber(/*float*/ $number, /*float*/ $min, /*float*/ $max)
		{
			if (is_numeric($number))
			{
				$number = floatval($number);
				if ($number >= $min && $number <= $max)
				{
					return true;
				}
			}
			return false;
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		/**
		 * Creating instances of this class is not allowed.
		 */
		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>