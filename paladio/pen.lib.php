<?php
	/* pen.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('string_utility.lib.php');
		require_once('parser.lib.php');
	}

	/**
	 * PEN (Paladio Extensible Notation)
	 * @package Paladio
	 */
	final class PEN
	{
		private static $whitespace = array(' ', "\t", "\n", "\r");
		private static $newLine = array("\n", "\r");
		private static $unquotedStringEnd = array(' ', "\t", ':', ',', ']', "\n", "\r", '#', ';');

		private static function EncodeMapEntry($key, $value)
		{
			return '"' . $key.'" : '.PEN::Encode($value);
		}

		public static function ConsumeWhitespace($parser)
		{
			$parser->ConsumeWhile(PEN::$whitespace);
			do
			{
				if (($parser->Consume(';') !== null) || ($parser->Consume('#') !== null))
				{
					$parser->ConsumeUntil(PEN::$newLine);
					$parser->Consume(PEN::$newLine);
				}
			}while ($parser->ConsumeWhile(PEN::$whitespace) !== '');
		}

		private static function ConsumeArray($parser, $eval = false)
		{
			$result = array();
			while (true)
			{
				PEN::ConsumeWhitespace($parser);
				if ($parser->Consume(']') !== null)
				{
					return $result;
				}
				if (($key = PEN::ConsumeQuotedString($parser, $eval)) !== null)
				{
					//Empty
				}
				else
				{
					$key = $parser->ConsumeUntil(PEN::$unquotedStringEnd);
				}
				PEN::ConsumeWhitespace($parser);
				if ($parser->Consume(':') !== null)
				{
					$value = PEN::ConsumeValue($parser);
					$result[$key] = $value;
				}
				else
				{
					$result[] = $key;
				}
				PEN::ConsumeWhitespace($parser);
				if ($parser->Consume(',') === null)
				{
					//Ignore
				}
			}
		}

		public static function ConsumeQuotedString($parser, $eval = false)
		{
			$expecting = '';
			if ($parser->Consume('"') !== null)
			{
				$expecting = '"';
			}
			else if ($parser->Consume("'") !== null)
			{
				$expecting = "'";
			}
			else
			{
				return null;
			}
			$result = '';
			while (true)
			{
				$result .= $parser->ConsumeUntil($expecting, '\\');
				if ($parser->Consume($expecting) !== null)
				{
					if ($eval)
					{
						if ($parser->Consume('@') !== null)
						{
							return eval($result);
						}
						else
						{
							return $result;
						}
					}
					else
					{
						return $result;
					}
				}
				else if ($parser->Consume('\\') !== null)
				{
					if ($parser->Consume('\\') !== null)
					{
						$result .= '\\';
					}
					else if ($parser->Consume($expecting) !== null)
					{
						$result .= $expecting;
					}
					else if ($parser->Consume('f') !== null)
					{
						$result .= "\f";
					}
					else if ($parser->Consume('n') !== null)
					{
						$result .= "\n";
					}
					else if ($parser->Consume('r') !== null)
					{
						$result .= "\r";
					}
					else if ($parser->Consume('t') !== null)
					{
						$result .= "\t";
					}
					else if ($parser->Consume('u') !== null)
					{
						$result .= utf8_chr(hexdec($parser->Consume(4)));
					}
				}
			}
		}

		public static function ConsumeValue($parser, $eval = false)
		{
			PEN::ConsumeWhitespace($parser);
			if ($parser->Consume('null') !== null)
			{
				return null;
			}
			else if ($parser->Consume('true') !== null)
			{
				return true;
			}
			else if ($parser->Consume('false') !== null)
			{
				return false;
			}
			if (($result = PEN::ConsumeQuotedString($parser, $eval)) !== null)
			{
				return $result;
			}
			else if ($parser->Consume('[') !== null)
			{
				return PEN::ConsumeArray($parser);
			}
			else
			{
				return $parser->ConsumeUntil(PEN::$unquotedStringEnd);
			}
		}

		public static function Encode(/*mixed*/ $value)
		{
			if (is_array($value))
			{
				return '['.implode(', ', array_map('PEN::EncodeMapEntry', $value)).']';
			}
			else if (is_string($value))
			{
				return '"'.String_Utility::EscapeString($value, array("\\", "\"")).'"';
			}
			else if (is_numeric($value))
			{
				return (string)$value;
			}
			else if (is_null($value))
			{
				return 'null';
			}
			else if (is_bool($value))
			{
				if ($value)
				{
					return 'true';
				}
				else
				{
					return 'false';
				}
			}
			else
			{
				throw new Exception('Unable to encode value');
			}
		}

		public static function Decode (/*string*/ $value, $eval = false)
		{
			$parser = new Parser($value);
			return PEN::ConsumeValue($parser, $eval);
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