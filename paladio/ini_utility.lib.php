<?php
	/* ini_utility.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	/**
	 * FileSystem
	 * @package INI
	 */
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

		/**
		 * Escapes a string in C style.
		 *
		 * Note 1: The escaped characters are: null, tab, carriage return, line feed, backslash, double quotes, single quotes, comman and semicolon.
		 * Note 2: $string is expected to be string, no check is performed.
		 *
		 * Returns the string $string escaped in C style.
		 *
		 * @access public
		 * @return string
		 */
		public static function Escape(/*string*/ $string)
		{
			$before = array("\\"     , "\0"    , "\t"    , "\r"    , "\n"    , '"'     , "'"     , ','     , ';'     );
			$after =  array("\\"."\\", "\\".'0', "\\".'t', "\\".'r', "\\".'n', "\\".'"', "\\"."'", "\\".',', "\\".';');
			//ONLY UTF-8
			$result = str_replace($before, $after, $string);
			return $result;
		}

		/**
		 * Processes an INI line.
		 *
		 * Sets the $fieldName to the name of the readed field, and $fieldValue to the value of the readed field.
		 * If the value is in single quotes or double quotes, $extra is set to what is found after the quotes.
		 *
		 * Note: $line is expected to be string, no check is performed.
		 *
		 * Returns true if readed a value, false otherwise.
		 *
		 * @access public
		 * @return bool
		 */
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

		/**
		 * Processes a value.
		 *
		 * If $value was in single quotes or double quotes, sets $value to the part in quotes unscaped and $extra to what was after the quotes.
		 *
		 * Note: $value is expected to be string, no check is performed.
		 *
		 * Returns true if $value was in quotes, false otherwise.
		 *
		 * @access public
		 * @return bool
		 */
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

		/**
		 * Unscapes a string C Style
		 *
		 * Note 1: Suports escape sequences in the form \x## (where ## is an hexa value) and also \\, \0, \t, \r, \n, \", \', \, and \;
		 * Note 2: $string is expected to be string, no check is performed.
		 *
		 * Returns the string $string unescaped in C style.
		 *
		 * @access public
		 * @return string
		 */
		public static function Unescape(/*string*/ $string)
		{
			//ONLY UTF-8
			$result = preg_replace_callback
			(
					'#(\\\\x[0-9a-fA-F]{2})#u',
					'INI_Utility::EscapeSequence',
					$string
			);
			$before = array("\\"."\\", "\\".'0', "\\".'t', "\\".'r', "\\".'n', "\\".'"', "\\"."'", "\\".',', "\\".';');
			$after =  array("\\"     , "\0"    , "\t"    , "\r"    , "\n"    , '"'     , "'"     , ','     , ';'     );
			//ONLY UTF-8
			$result = str_replace($before, $after, $string);
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