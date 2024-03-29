<?php
	/* string_utility.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	if (!function_exists('json_decode_array'))
	{
		/**
		 * variant of json_decode that always returns associative array
		 */
		function json_decode_array($string)
		{
			$result = json_decode($string, true);
			if (json_last_error() == JSON_ERROR_NONE)
			{
				return $result;
			}
			else
			{
				return array();
			}
		}
	}

	if (!function_exists('utf8_ord'))
	{
		/**
		 * UTF-8 aware replacement of ord
		 */
		function utf8_ord(/*string*/ $character)
		{
			$ord0 = ord($character{0});
			if ($ord0 >= 0 && $ord0 <= 127)
			{
				return $ord0;
			}
			else
			{
				$ord1 = ord($character{1});
				if ($ord1 >= 192 && $ord0 <= 223)
				{
					return ($ord0 - 192)*64 + ($ord1 - 128);
				}
				else
				{
					$ord2 = ord($character{2});
					if ($ord0 >= 224 && $ord0 <= 239)
					{
						return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);
					}
					else
					{
						$ord3 = ord($character{3});
						if ($ord0 >= 240 && $ord0 <= 247)
						{
							return ($ord0 - 240) * 262144 + ($ord1 - 128) * 4096 + ($ord2 - 128) * 64 + ($ord3 - 128);
						}
						else
						{
							$ord4 = ord($character{4});
							if ($ord0 >= 248 && $ord0 <= 251)
							{
								return ($ord0 - 248) * 16777216 + ($ord1 - 128) * 262144 + ($ord2 - 128) * 4096 + ($ord3 - 128) * 64 + ($ord4 - 128);
							}
							else
							{
								$ord5 = ord($character{5});
								if ($ord0 >= 252 && $ord0 <= 253)
								{
									return ($ord0 - 252) * 1073741824 + ($ord1 - 128) * 16777216 + ($ord2 - 128) * 262144 + ($ord3 - 128) * 4096 + ($ord4 - 128) * 64 + ($ord5 - 128);
								}
								else if ($ord0 >= 254 && $ord0 <= 255)
								{
									return FALSE;
								}
								else
								{
									return 0;
								}
							}
						}
					}
				}
			}
		}
	}

	if (!function_exists('utf8_chr'))
	{
		/**
		 * UTF-8 aware replacement of char
		 */
		function utf8_chr(/*int*/ $codepoint)
		{
			return mb_convert_encoding('&#'.intval($codepoint).';', 'UTF-8', 'HTML-ENTITIES');
		}
	}

	if (!function_exists('mb_str_split'))
	{
		function mb_str_split(/*string*/ $string, /*int*/ $length = 1)
		{
			if (intval($length) != $length || $length < 1)
			{
				return false;
			}
			else
			{
				$len = mb_strlen($string);
				if ($len <= $length)
				{
					return array($string);
				}
				else
				{
					$result = array();
					for ($index = 0; $index < $len; $index += $length)
					{
						$result[] = mb_substr($string, $index, $length);
					}
					return $result;
				}
			}
		}
	}

	/**
	 * String_Utility
	 * @package Paladio
	 */
	final class String_Utility
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $charset;
		private static $weekdays;
		private static $months;

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function CountCharacters(/*string*/ $string, /*string*/ $characters)
		{
			//TODO
			return strlen($string) - strlen(str_replace(str_split($characters), '', $string));
		}

		/**
		 * Escapes a character in C style.
		 *
		 * @param $data: an array, of which the first item is the character to escape.
		 *
		 * @access public
		 * @return string
		 */
		public static function EscapeCharacter(/*array*/ $data)
		{
			//TODO improve encapsulation
			$character = $data[0];
			//strict on output
			$specials = array
			(
				//These escape sequences are common to C++, Java, C#, PHP and ECMAScript
				"\f" => "\\".'f',
				"\n" => "\\".'n',
				"\r" => "\\".'r',
				"\t" => "\\".'t',
			);
			if (array_key_exists($character, $specials))
			{
				return $specials[$character];
			}
			else
			{
				$ord = utf8_ord($character);
				if ($ord !== false)
				{
					$hex = dechex($ord);
					if (strlen($hex) == 0)
					{
						//Empty
					}
					else if (strlen($hex) == 1)
					{
						return "\\".'u000'.$hex;
					}
					else if (strlen($hex) == 2)
					{
						return "\\".'u00'.$hex;
					}
					else if (strlen($hex) == 3)
					{
						return "\\".'u0'.$hex;
					}
					else
					{
						return "\\".'u'.$hex;
					}
				}
				else
				{
					throw new Exception ('Unsuported character');
				}
			}
		}

		/**
		 * Escapes a string in C style.
		 *
		 * @param $string: the string to escape.
		 * @param $characters: the charactes to escape.
		 * @param $escapeControlCharacters: if true all control characters will be escaped.
		 *
		 * @access public
		 * @return string
		 */
		public static function EscapeString(/*string*/ $string, /*array*/ $characters, /*bool*/ $escapeControlCharacters = true)
		{
			$class = '';
			if ($escapeControlCharacters)
			{
				$class .= '\x00-\x1F\x7F\x80-\x9F';
			}
			if (is_array($characters))
			{
				$class .= implode('', array_map('preg_quote', $characters));
			}
			if ($class != '')
			{
				return preg_replace_callback('@['.$class.']@u', array('String_Utility', 'EscapeCharacter'), $string);
			}
			else
			{
				return $string;
			}
		}

		/**
		 * Unescapes a character in C style.
		 *
		 * @param $data: an array, of which the first item is the character to escape.
		 *
		 * @access public
		 * @return string
		 */
		public static function UnescapeCharacter(/*array*/ $data)
		{
			//TODO improve encapsulation
			$character = $data[0];
			$specials = array
			(
				//These escape sequences are common to C++, Java, C#, PHP and ECMAScript
				"\\".'f' => "\f",
				"\\".'n' => "\n",
				"\\".'r' => "\r",
				"\\".'t' => "\t"
			);
			if (array_key_exists($character, $specials))
			{
				return $specials[$character];
			}
			else
			{
				$len = mb_strlen($character);
				if
				(
					(
						$len == 6 &&
						mb_substr($character, 1, 1) == 'u'
					)
				)
				{
					$var = mb_substr($character, 2);
					return utf8_chr(hexdec($var));
				}
				else if ($len == 2)
				{
					return mb_substr($character, 1);
				}
				else
				{
					throw new Exception ('Unsuported character');
				}
			}
		}

		/**
		 * Unescapes a string in C style.
		 *
		 * @param $string: the string to unescape.
		 *
		 * @access public
		 * @return string
		 */
		public static function UnescapeString(/*string*/ $string)
		{
			$result = preg_replace_callback
			(
				'@\x5Cu[0-9a-fA-F]{4}|\x5Cu[fnrt]@u',
				'String_Utility::UnescapeCharacter',
				$string
			);
			return $result;
		}

		//------------------------------------------------------------

		/**
		 * Verifies if a string ends with another string.
		 *
		 * Returns true of the string $string ends with $with.
		 *
		 * @param $string: the string to verify.
		 * @param $with: the ending to verify.
		 *
		 * @access public
		 * @return bool
		 */
		public static function EndsWith (/*string*/ $string, /*string*/ $with)
		{
			if (substr($string, strlen($string) - strlen($with)) == $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Returns a new string equal to $string that ends with $ending.
		 *
		 * If $string ends with $ending, the returned string is $string, otherwise $string.$ending.
		 *
		 * @param $string: the string to verify.
		 * @param $ending: the ending to ensure.
		 *
		 * @access public
		 * @return string
		 */
		public static function EnsureEnd(/*string*/ $string, /*string*/ $ending)
		{
			if (!String_Utility::EndsWith($string, $ending))
			{
				return $string.$ending;
			}
			else
			{
				return $string;
			}
		}

		/**
		 * Returns a new string equal to $string that starts with $start.
		 *
		 * If $string starts with $start, the returned string is $string, otherwise $start.$string.
		 *
		 * @param $string: the string to verify.
		 * @param $start: the start to ensure.
		 *
		 * @access public
		 * @return string
		 */
		public static function EnsureStart(/*string*/ $string, /*string*/ $start)
		{
			if (!String_Utility::StartsWith($string, $start))
			{
				return $start.$string;
			}
			else
			{
				return $string;
			}
		}

		/**
		 * Returns a new string equal to $string without the last $characterCount characters.
		 *
		 * Note: If the length of $string is less than $characterCount returns empty string.
		 *
		 * @param $string: the string to except.
		 * @param $characterCount: the number of characters to except from the string.
		 *
		 * @access public
		 * @return string
		 */
		public static function ExceptEnd(/*string*/ $string, /*int*/ $characterCount)
		{
			$length = mb_strlen($string);
			if ($length < $characterCount)
			{
				return '';
			}
			else
			{
				return mb_substr($string, 0, $length - $characterCount);
			}
		}

		/**
		 * Returns a new string equal to $string without the first $characterCount characters.
		 *
		 * Note: If the length of $string is less than $characterCount returns empty string.
		 *
		 * @param $string: the string to except.
		 * @param $characterCount: the number of characters to except from the string.
		 *
		 * @access public
		 * @return string
		 */
		public static function ExceptStart(/*string*/ $string, /*int*/ $characterCount)
		{
			$length = mb_strlen($string);
			if ($length < $characterCount)
			{
				return '';
			}
			else
			{
				return mb_substr($string, $characterCount);
			}
		}

		/**
		 * Returns the last $characterCount characters of $string.
		 *
		 * Note: If the length of $string is less than $characterCount returns $string.
		 *
		 * @param $string: the string to process.
		 * @param $characterCount: the number of characters to get from the string.
		 *
		 * @access public
		 * @return string
		 */
		public static function GetEnd(/*string*/ $string, /*int*/ $characterCount)
		{
			$length = mb_strlen($string);
			if ($length < $characterCount)
			{
				return $string;
			}
			else
			{
				return mb_substr($string, $length - $characterCount);
			}
		}

		/**
		 * Returns the first $characterCount characters of $string.
		 *
		 * Note: If the length of $string is less than $characterCount returns $string.
		 *
		 * @param $string: the string to process.
		 * @param $characterCount: the number of characters to get from the string.
		 *
		 * @access public
		 * @return string
		 */
		public static function GetStart(/*string*/ $string, /*int*/ $characterCount)
		{
			$length = mb_strlen($string);
			if ($length < $characterCount)
			{
				return $string;
			}
			else
			{
				return mb_substr($string, 0, $characterCount);
			}
		}

		/**
		 * Returns a new string equal to $string that doesn't end with $ending.
		 *
		 * If $string ends with $ending, the returned string is $string without $ending, $string otherwise.
		 *
		 * @param $string: the string to verify.
		 * @param $ending: the ending to neglect.
		 *
		 * @access public
		 * @return string
		 */
		public static function NeglectEnd(/*string*/ $string, /*string*/ $ending)
		{
			String_Utility::TryNeglectEnd($string, $ending, $result);
			return $result;
		}

		/**
		 * Returns a new string equal to $string that doesn't start with $start.
		 *
		 * If $string starts with $start, the returned string is $string without $start, $string otherwise.
		 *
		 * @param $string: the string to verify.
		 * @param $start: the start to neglect.
		 *
		 * @access public
		 * @return string
		 */
		public static function NeglectStart(/*string*/ $string, /*string*/ $start)
		{
			String_Utility::TryNeglectStart($string, $start, $result);
			return $result;
		}

		/**
		 * Attempts to create a a new string equal to $string that doesn't end with $ending.
		 *
		 * If $string ends with $ending, the returns true, false otherwise.
		 *
		 * @param $string: the string to verify.
		 * @param $ending: the ending to neglect.
		 * @param $result: the new string that was created.
		 *
		 * @access public
		 * @return string
		 */
		public static function TryNeglectEnd(/*string*/ $string, /*string*/ $ending, /*string*/ &$result)
		{
			$length = strlen($string);
			$endLength = strlen($ending);
			if (substr($string, $length - $endLength) == $ending)
			{
				if ($length < $endLength)
				{
					$result = '';
					return true;
				}
				else
				{
					$result = substr($string, 0, $length - $endLength);
				}
				return true;
			}
			else
			{
				$result = $string;
				return false;
			}
		}

		/**
		 * Attempts to create a a new string equal to $string that doesn't start with $start.
		 *
		 * If $string starts with $start, the returns true, false otherwise.
		 *
		 * @param $string: the string to verify.
		 * @param $ending: the start to neglect.
		 * @param $result: the new string that was created.
		 *
		 * @access public
		 * @return string
		 */
		public static function TryNeglectStart(/*string*/ $string, /*string*/ $start, /*string*/ &$result)
		{
			$startLength = strlen($start);
			if (substr($string, 0, $startLength) == $start)
			{
				$length = strlen($string);
				if ($length < $startLength)
				{
					$result = '';
					return true;
				}
				else
				{
					$result = substr($string, $startLength);
				}
				return true;
			}
			else
			{
				$result = $string;
				return false;
			}
		}

		/**
		 * Verifies if a string starts with another string.
		 *
		 * Returns true of the string $string starts with $with.
		 *
		 * @param $string: the string to verify.
		 * @param $with: the ending to verify.
		 *
		 * @access public
		 * @return bool
		 */
		public static function StartsWith (/*string*/ $string, /*string*/ $with)
		{
			if (substr($string, 0, strlen($with)) == $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		/**
		 * Formats a date value the same way the function date does, with custom weekdays and months names.
		 *
		 * @param $date: the date to format.
		 * @param $format: the format to apply to the string.
		 * @param $weekdays: an array containing the names of the days of the week, if null the value $weekdays set at String_Utility::Configure is used.
		 * @param $months: an array containing the names of the months, if null the value $months set at String_Utility::Configure is used.
		 *
		 * @access public
		 * @return string
		 */
		public static function FormatDate($date, $format, $weekdays = null, $months = null)
		{
			$pattern = array();
			$replace = array();
			if ($weekdays === null)
			{
				$weekdays = String_Utility::$weekdays;
			}
			if ($weekdays !== null)
			{
				$_weekdays = array_map('trim', $weekdays);
				$weekday = date('w', $date);
				$weekday_string = implode('\\',mb_str_split($_weekdays[$weekday], 1));
				if ($weekday_string != '')
				{
					$weekday_string = '\\'.$weekday_string;
				}
				$pattern[] = '@(?<!\x5C)l@u';
				$replace[] = $weekday_string;
			}
			if ($months === null)
			{
				$months = String_Utility::$months;
			}
			if ($months !== null)
			{
				$_months = array_map('trim', $months);
				$month = date('n', $date);
				$month_string = implode('\\',mb_str_split($_months[$month - 1], 1));
				if ($month_string != '')
				{
					$month_string = '\\'.$month_string;
				}
				$pattern[] = '@(?<!\x5C)F@u';
				$replace[] = $month_string;
			}
			if (count($pattern) > 0)
			{
				$format = preg_replace($pattern, $replace, $format);
			}
			return date($format, $date);
		}

		//------------------------------------------------------------

		/**
		 * Creates a new random string.
		 *
		 * @param $length: the length of the new string.
		 * @param $characters: an string that contains the possible characters for the new string.
		 *
		 * @access public
		 * @return string
		 */
		public static function RandomString($length, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
		{
			$result = '';
			if($length > 0)
			{
				$array = mb_str_split($characters, 1);
				$count = count($array);
				mt_srand((double)microtime() * 1000000);
				for($index = 0; $index < $length; $index++)
				{
					$number = mt_rand(1, $count);
					$result .= $array[$number - 1];
				}
			}
			return $result;
		}

		//------------------------------------------------------------

		/**
		 * Sets the configuration of String_Utility.
		 *
		 * @param $weekdays: the names of the days of the week to use in String_Utility::FormatDate
		 * @param $months: the names of the months to use in String_Utility::FormatDate
		 *
		 * @access public
		 * @return void
		 */
		public static function Configure(/*string*/ $weekdays, /*string*/ $months)
		{
			String_Utility::$weekdays = $weekdays;
			String_Utility::$months = $months;
		}
		
		/**
		 * Gets the correct charset to use in Iconv to convert from based on the current locale
		 *
		 * @access public
		 * @return string
		 */
		public static function DiscoverIconvCharset()
		{
			if (!isset(String_Utility::$charset))
			{
				$charset = explode('.', setlocale(LC_COLLATE, 0));
				if (count($charset) > 1)
				{
					$charset = $charset[1];
					if ($charset == '1250' || $charset == '1252')
					{
						$charset = 'Windows-'.$charset;
					}
					else if (String_Utility::TryNeglectStart($charset, 'utf', $result)) //test in linux
					{
						$charset = 'UTF-'.$result;
					}
					else if (String_Utility::TryNeglectStart($charset, 'iso8859', $result)) //test in linux
					{
						$charset = 'ISO-8859-'.$result;
					}
					String_Utility::$charset = $charset;
				}
				else
				{
					String_Utility::$charset = 'UTF-8';
				}
			}
			return String_Utility::$charset;
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

	require_once('configuration.lib.php');
	Configuration::Callback
	(
		'paladio-strings',
		create_function
		(
			'',
			'String_Utility::Configure
			(
				Configuration::Get(\'paladio-strings\', \'weekdays\'),
				Configuration::Get(\'paladio-strings\', \'months\')
			);'
		)
	);
?>