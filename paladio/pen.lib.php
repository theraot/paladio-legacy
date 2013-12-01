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
		private static $unquotedStringEnd = array(' ', "\t", ':', ',', "\n", "\r", '#', ';');

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
				if (!$parser->CanConsume())
				{
					break;
				}
			}while ($parser->ConsumeWhile(PEN::$whitespace) !== '');
		}

		private static function ConsumeArray($parser, $eval = false)
		{
			$expecting = '';
			if ($parser->Consume('[') !== null)
			{
				$expecting = ']';
			}
			else if ($parser->Consume('(') !== null)
			{
				$expecting = ')';
			}
			else
			{
				return null;
			}
			$result = array();
			while ($parser->CanConsume())
			{
				PEN::ConsumeWhitespace($parser);
				if ($parser->Consume($expecting) !== null)
				{
					return $result;
				}
				if (($key = PEN::ConsumeQuotedString($parser, $eval)) !== null)
				{
					//Empty
				}
				else
				{
					$key = $parser->ConsumeUntil(array_merge(PEN::$unquotedStringEnd, array($expecting)));
				}
				PEN::ConsumeWhitespace($parser);
				if ($parser->Consume(':') !== null)
				{
					$value = PEN::ConsumeValue($parser, $expecting);
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
			if (!$parser->CanConsume())
			{
				throw new Exception ('Unexpected end of string');
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
			while ($parser->CanConsume())
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
			if (!$parser->CanConsume())
			{
				throw new Exception ('Unexpected end of string');
			}
		}

		public static function ConsumeValue($parser, $expecting, $eval = false)
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
			else if (($result = PEN::ConsumeArray($parser, $eval)) !== null)
			{
				return $result;
			}
			else
			{
				if (is_null($expecting))
				{
					return $parser->ConsumeUntil(PEN::$unquotedStringEnd);
				}
				else
				{
					return $parser->ConsumeUntil(array_merge(PEN::$unquotedStringEnd, array($expecting)));
				}
			}
		}

		public static function Encode(/*mixed*/ $value, $alternativeQuotes = false, $alternativeBrackets = false)
		{
			if (is_array($value))
			{
				$result = array();
				$keys = Utility::ArraySort(array_keys($value));
				$index = 0;
				foreach ($keys as $key)
				{
					$val = $value[$key];
					$val = PEN::Encode($val, $alternativeQuotes);
					if ($key === $index)
					{
						$result[] = $val;
						$index++;
					}
					else
					{
						if ($alternativeQuotes)
						{
							$result[] = "'" . $key."':".$val;
						}
						else
						{
							$result[] = '"' . $key.'":'.$val;
						}
					}
				}
				if ($alternativeBrackets)
				{
					return '('.implode(', ', $result).')';
				}
				else
				{
					return '['.implode(', ', $result).']';
				}
			}
			else if (is_string($value))
			{
				if ($alternativeQuotes)
				{
					return "'".String_Utility::EscapeString($value, array("\\", "\'"))."'";
				}
				else
				{
					return '"'.String_Utility::EscapeString($value, array("\\", "\"")).'"';
				}
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
			return PEN::ConsumeValue($parser, null, $eval);
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