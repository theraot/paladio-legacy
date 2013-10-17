<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	/**
	 * Utility
	 * @package Paladio
	 */
	final class Utility
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Creates a new array with the numeric keys arranged.
		 *
		 * @param $array: the array to process.
		 *
		 * @access public
		 * @return array
		 */
		public static function CompactArray(/*array*/ $array)
		{
			$result = array();
			$keys = array_keys($array);
			foreach ($keys as $key)
			{
				$value = $array[$key];
				if (!is_null($value))
				{
					if (is_string($key))
					{
						$result[$key] = $value;
					}
					else
					{
						$result[] = $value;
					}
				}
			}
			return $result;
		}

		/**
		 * Creates a new string with the dangerous characters escaped.
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

				//ONLY UTF-8
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
				//ONLY UTF-8
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
				//ONLY UTF-8
				$data = str_replace($danger, $secure, $data);

				return $data;
			}
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
		public static function SubArray(/*array*/ $array, /*array*/ $keys)
		{
			$result = array();
			foreach ($keys as $key => $alias)
			{
				if (!is_string($key))
				{
					$key = $alias;
				}
				if (array_key_exists($key, $array))
				{
					$value = $array[$key];
					if (!is_null($value))
					{
						$result[$alias] = $value;
					}
				}
			}
			return $result;
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
			//ONLY UTF-8
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