<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

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