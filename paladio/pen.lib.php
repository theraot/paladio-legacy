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
		private static $whitespace = array(' ', "\t");
		private static $unquotedStringEnd = array(' ', "\t", ':', ',', ']');
		
		private static function EncodeMapEntry($key, $value)
		{
			return '"' . $key.'" : '.PEN::Encode($value);
		}
		
		private static function ConsumeArray($parser)
		{
			$result = array();
			while (true)
			{
				$parser->ConsumeWhile(PEN::$whitespace);
				if ($parser->Consume(']') !== null)
				{
					return $result;
				}
				if ($parser->Consume('"') !== null)
				{
					$key = PEN::ConsumeQuotedString($parser);
				}
				else
				{
					$key = $parser->ConsumeUntil(PEN::$unquotedStringEnd);
				}
				$parser->ConsumeWhile(PEN::$whitespace);
				if ($parser->Consume(':') !== null)
				{
					$value = $parser->ConsumeValue($parser);
					$result[$key] = $value;
				}
				else
				{
					$result[] = $key;
				}
				$parser->ConsumeWhile(PEN::$whitespace);
				if ($parser->Consume(',') === null)
				{
					//Ignore
				}
			}
		}
		
		private static function ConsumeQuotedString($parser)
		{
			$result = '';
			while (true)
			{
				$result .= $parser->ConsumeUntil('"', '\\');
				if ($parser->Consume('"') !== null)
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
				else if ($parser->Consume('\\') !== null)
				{
					if ($parser->Consume('\\') !== null)
					{
						$result .= '\\';
					}
					else if ($parser->Consume('"') !== null)
					{
						$result .= '"';
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
		
		public static function ConsumeValue($parser)
		{
			$parser->ConsumeWhile(PEN::$whitespace);
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
			if ($parser->Consume('"') !== null)
			{
				return PEN::ConsumeQuotedString($parser);
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
				return '['.implode(', ', array_map('PEN::EncodeMapÈntry', $value)).']';
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
		
		public static function Decode (/*string*/ $value)
		{
			$parser = new Parser($value);
			return PEN::ConsumeValue($parser);
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