<?php
	/* string_utility.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	if (!function_exists('utf8_str_split'))
	{
		function utf8_str_split($string, $split_length = 1)
		{
			if (!is_numeric($split_length) || $split_length < 1)
			{
				return false;
			}
			else
			{
				$len = mb_strlen($string);
				if ($len <= $split_length)
				{
					return array($string);
				}
				else
				{
					preg_match_all('@.{'.$split_length.'}|[^\x00]{1,'.$split_length.'}$@us', $string, $match);
					return $match[0];
				}
			}
		}
	}
	
	if (!function_exists('json_decode_array'))
	{
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

	if (!function_exists('chr_utf8'))
	{
		function chr_utf8(/*int*/ $codepoint)
		{
			return mb_convert_encoding('&#'.intval($codepoint).';', 'UTF-8', 'HTML-ENTITIES');
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

		private static $weekdays;
		private static $months;

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------
		
		public static function EscapeCharacter(/*array*/ $data)
		{
			$character = $data[0];
			//strict on output
			$specials = array
			(
				//These escape sequences are common to C++, Java, C#, PHP and ECMAScript
				'\f' => "\\".'f',
				'\n' => "\\".'n',
				'\r' => "\\".'r',
				'\t' => "\\".'t',
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
					if (mb_strlen($hex) == 0)
					{
						echo 'here';
					}
					else if (mb_strlen($hex) == 1)
					{
						return "\\".'u000'.$hex;
					}
					else if (mb_strlen($hex) == 2)
					{
						return "\\".'u00'.$hex;
					}
					else if (mb_strlen($hex) == 3)
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
		
		public static function EscapeString(/*string*/ $string, /*array*/ $characters)
		{
			//ONLY UTF-8
			if (is_array($characters))
			{
				return preg_replace_callback('@['.implode('', array_map('preg_quote', $characters)).']@u', 'String_Utility::EscapeCharacter', $string);
			}
			else
			{
				return $string;
			}
		}
		
		public static function UnescapeCharacter(/*array*/ $data)
		{
			$character = $data[0];
			$specials = array
			(
				//These escape sequences are common to C++, Java, C#, PHP and ECMAScript
				"\\".'f' => '\f',
				"\\".'n' => '\n',
				"\\".'r' => '\r',
				"\\".'t' => '\t'
				
			);
			if (array_key_exists($character, $specials))
			{
				return $specials[$character];
			}
			else if
			(
				(
					mb_strlen($character) == 6 &&
					mb_substr($character, 1, 1) == 'u'
				)
			)
			{
				$var = mb_substr($character, 2);
				return chr_utf8(hexdec($var));
			}
			else if (mb_strlen($character) == 2)
			{
				return mb_substr($character, 1);
			}
			else
			{
				throw new Exception ('Unsuported character');
			}
		}
		
		public static function UnescapeString(/*string*/ $string)
		{
			//ONLY UTF-8
			$result = preg_replace_callback
			(
				'@\x5Cu[0-9a-fA-F]{4}|\x5Cu[fnrt]@u',
				'String_Utility::UnescapeCharacter',
				$string
			);
			return $result;
		}
		
		//------------------------------------------------------------

		public static function EndsWith (/*string*/ $string, /*string*/ $with)
		{
			if (mb_substr($string, mb_strlen($string) - mb_strlen($with)) == $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		public static function EnsureEnd(/*string*/ $string, /*string*/ $end)
		{
			if (!String_Utility::EndsWith($string, $end))
			{
				return $string.$end;
			}
			else
			{
				return $string;
			}
		}

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

		public static function NeglectEnd(/*string*/ $string, /*string*/ $end)
		{
			if (String_Utility::EndsWith($string, $end))
			{
				return StringUtility::ExceptEnd($string, mb_strlen($end));
			}
			else
			{
				return $string;
			}
		}

		public static function NeglectStart(/*string*/ $string, /*string*/ $start)
		{
			if (String_Utility::StartsWith($string, $start))
			{
				return String_Utility::ExceptStart($string, mb_strlen($start));
			}
			else
			{
				return $string;
			}
		}

		public static function StartsWith (/*string*/ $string, /*string*/ $with)
		{
			if (mb_substr($string, 0, mb_strlen($with)) == $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		public static function FormatDate($date, $format, $weekdays = null, $months = null)
		{
			$pattern = array();
			$replace = array();
			if (is_null($weekdays))
			{
				$weekdays = String_Utility::$weekdays;
			}
			if (!is_null($weekdays))
			{
				//ONLY UTF-8
				$_weekdays = array_map('trim', explode(',', $weekdays));
				$weekday = date('w', $date);
				//ONLY UTF-8
				$weekday_string = implode('\\',utf8_str_split($_weekdays[$weekday], 1));
				if ($weekday_string != '')
				{
					$weekday_string = '\\'.$weekday_string;
				}
				$pattern[] = '@(?<!\x5C)l@u';
				$replace[] = $weekday_string;
			}
			if (is_null($months))
			{
				$months = String_Utility::$months;
			}
			if (!is_null($months))
			{
				//ONLY UTF-8
				$_months = array_map('trim', explode(',', $months));
				$month = date('n', $date);
				//ONLY UTF-8
				$month_string = implode('\\',utf8_str_split($_months[$month - 1], 1));
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

		public static function Configure(/*string*/ $weekdays, /*string*/ $months)
		{
			String_Utility::$weekdays = $weekdays;
			String_Utility::$months = $months;
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}

	require_once('configuration.lib.php');
	function String_Utility_Configure()
	{
		String_Utility::Configure
		(
			Configuration::Get('paladio-strings', 'weekdays'),
			Configuration::Get('paladio-strings', 'months')
		);
	}
	Configuration::Callback('paladio-strings', 'String_Utility_Configure');
?>