<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	final class Utility
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function CompactArray(/*array*/ $array)
		{
			$result = array();
			$keys = array_keys($array);
			foreach ($keys as $key)
			{
				$value = $array[$key];
				if (!is_null($value))
				{
					if (is_numeric($key))
					{
						$result[] = $value;
					}
					else
					{
						$result[$key] = $value;
					}
				}
			}
			return $result;
		}

		public static function Sanitize(/*string*/ $data, /*string*/ $encoding = 'html')
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
				//"{", "}", "[", "]", ":" : dangerous to JSON
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

		public static function ValidateEmail(/*string*/ $emailSinValidar, /*string*/ &$email)
		{
			if (!Utileria::Validar($emailSinValidar, $email))
			{
				return false;
			}
			$email = mb_strtolower($email); //solo minusculas
			//$caracter = '[a-zA-Z0-9_%+-]'; //caracter ::= {a-z} | {A-Z} | {0-9} | "_" | "%" | "+" | "-"
			$caracter = '[a-z0-9_%+-]'; //caracter ::= {a-z} | {0-9} | "_" | "%" | "+" | "-"
			$texto = $caracter.'+'; //texto ::= caracter+ //Uno o más caracter
			$dominio = $texto.'(\.'.$texto.')*'; //dominio :: = texto ("." + texto)* /*texto seguido de ninguna o más veces ("." seguido de texto).
			$correo = $dominio.'@'.$dominio; //correo ::= dominio "@" dominio /*dominio seguido de "@" seguido de dominio
			//ONLY UTF-8
			if(preg_match('#^'.$correo.'$#u', $email))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		public static function ValidateNumber(/*float*/ $numeroSinValidar, /*float*/ $min, /*float*/ $max, /*float*/ &$numero)
		{
			if (is_numeric($numeroSinValidar))
			{
				$numero = floatval($numeroSinValidar);
				if ($numero >= $min && $numero <= $max)
				{
					return true;
				}
			}
			return false;
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