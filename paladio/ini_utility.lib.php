<?php
	/* ini_utility.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	final class INI_Utility
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function EscapeSequence(/*array*/ $data)
		{
			$var = mb_substr($data[1], 2);
			//ONLY UTF-8
			return rawurldecode('%'.$var);
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Escape(/*string*/ $value)
		{
			$before = array("\\"     , "\0"    , "\t"    , "\r"    , "\n"    , '"'     , "'"     , ','     , ';'     );
			$after =  array("\\"."\\", "\\".'0', "\\".'t', "\\".'r', "\\".'n', "\\".'"', "\\"."'", "\\".',', "\\".';');
			//ONLY UTF-8
			$result = str_replace($before, $after, $result);
			return $result;
		}

		public static function ProcessLine(/*string*/ $line, /*string*/ &$fieldName, /*string*/ &$fieldValue, /*mixed*/ &$extra)
		{
			$fieldName = '';
			$fieldValue = '';
			$extra = false;

			/*explicitily ignore blank line*/
			if ($line == '')
			{
				return false;
			}
			else
			{
				/*field=value*/
				//ONLY UTF-8
				if(preg_match('#(.+?)=(.*)#u', $line, $match))
				{
					$fieldName = trim($match[1]);
					if ($match[2] == '')
					{
						$fieldValue = null;
						return false;
					}
					else
					{
						$fieldValue = trim($match[2]);
						INI_Utility::ProcessValue($fieldValue, $extra);
						return true;
					}
				}
				else
				{
					return false;
				}
			}
		}

		public static function ProcessValue(/*string*/ &$value, /*mixed*/ &$extra)
		{
			//ONLY UTF-8
			if
			(
				(preg_match('#^"((?:\\\"|[^"])*)"(.*)#u', $value, $subConditionals))
				||
				(preg_match('#^\'((?:\\\'|[^\'])*)\'(.*)#u', $value, $subConditionals))
			)
			{
				$value = INI_Utility::Unescape($subConditionals[1]);
				$extra = $subConditionals[2];
				return true;
			}
			else
			{
				$extra = false;
				return false;
			}
		}

		public static function Unescape(/*string*/ $value)
		{
			//ONLY UTF-8
			$result = preg_replace_callback
			(
					'#(\\\\x[0-9a-fA-F]{2})#u',
					'INI_Utility::EscapeSequence',
					$value
			);
			$before = array("\\"."\\", "\\".'0', "\\".'t', "\\".'r', "\\".'n', "\\".'"', "\\"."'", "\\".',', "\\".';');
			$after =  array("\\"     , "\0"    , "\t"    , "\r"    , "\n"    , '"'     , "'"     , ','     , ';'     );
			//ONLY UTF-8
			$result = str_replace($before, $after, $result);
			return $result;
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>