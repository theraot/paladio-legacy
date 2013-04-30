<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('utility.lib.php');
	}

	interface IDatabaseOperator
	{
		public function Type();
	}

	/**
	 * Database_Field
	 * @package Paladio
	 */
	final class Database_Field
	{
		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------
		
		private $fieldName;
		
		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------
		
		/**
		 * Convert this instance to string 
		 */
		public function __toString()
		{
			return $this->fieldName;
		}
		
		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------
		
		/**
		 * Creates a new instance of Database_Field
		 * @param $fieldName: the name of the field to refer to.
		 */
		public function __construct(/*string*/ $fieldName)
		{
			$this->fieldName = (string)$fieldName;
		}
	}

	/**
	 * Database_Utility
	 * @package Paladio
	 */
	final class Database_Utility
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		/**
		 * Internally used to process values.
		 * @access private
		 */
		private static function TryProcessValue(/*mixed*/ $value, /*string*/ &$result)
		{
			if (is_string($value))
			{
				$result = '"'.Utility::Sanitize($value, 'html').'"';
				return true;
			}
			else if (is_numeric($value))
			{
				$result = Utility::Sanitize((string)$value, 'html');
				return true;
			}
			else if (is_null($value))
			{
				$result = 'NULL';
				return true;
			}
			else if (is_array($value))
			{
				$result = Database_Utility::ProcessExpression(null, $value);
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Internally used to process unary expressions.
		 * @access private
		 */
		private static function ProcessExpressionUnary($operator, $parameter)
		{
			return $operator.'('.Database_Utility::ProcessValue($parameter).')';
		}

		/**
		 * Internally used to process aggregation expressions.
		 * @access private
		 */
		private static function ProcessExpressionAggregation($operator, $parameter)
		{
			if (is_null($parameter))
			{
				return $operator.'(*)';
			}
			else
			{
				return $operator.'('.Database_Utility::ProcessValue($parameter).')';
			}
		}

		/**
		 * Internally used to process binary expressions.
		 * @access private
		 */
		private static function ProcessExpressionBinary($operator, $parameterA, $parameterB)
		{
			return '('.Database_Utility::ProcessValue($parameterA).' '.$operator.' '.Database_Utility::ProcessValue($parameterB).')';
		}

		/**
		 * Internally used to process n-ary expressions.
		 * @access private
		 */
		private static function ProcessExpressionNAry($operator, $parameters)
		{
			$processed = array();
			foreach($parameters as $parameter)
			{
				$processed[] = Database_Utility::ProcessValue($parameter);
			}
			//ONLY UTF-8
			return '('.implode(' '.$operator.' ', $processed).')';
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Creates a string that contains an alias of a field or value.
		 *
		 * @param $alias: the alias to give to the field or value.
		 * @param $value: the value to alias, use Database_Field to refer to a field inteads to a string.
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateAlias(/*string*/ $alias, /*mixed*/ $value)
		{
			$value = Database_Utility::ProcessValue($value);
			if (is_string($alias))
			{
				return $value.' '.DB::Alias().' "'.Utility::Sanitize($alias, 'html').'"';
			}
			else
			{
				return $value;
			}
		}

		/**
		 * Creates a string that contains an equation of a field and a value or field.
		 *
		 * @param $field: the field to equate to.
		 * @param $value: the value to equate, use Database_Field to refer to a field inteads to a string.
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateEquation(/*string*/ $field, /*mixed*/ $value)
		{
			$value = Database_Utility::ProcessValue($value);
			if (is_string($field))
			{
				return Utility::Sanitize($field, 'html').' = '.$value;
			}
			else
			{
				return $value;
			}
		}
		
		/**
		 * Combine two where conditions into one.
		 *
		 * @param $whereA: the first where condition.
		 * @param $whereB: the second where condition.
		 *
		 * @access public
		 * @return mixed
		 */
		public static function MergeWheres(/*array*/ $whereA, /*array*/ $whereB)
		{
			$result = array();
			$whereAKeys = array_keys($whereA);
			$whereBKeys = array_keys($whereB);
			foreach ($whereAKeys as $whereAKey)
			{
				if (is_numeric($whereAKey))
				{
					$result[] = $whereA[$whereAKey];
				}
				else if (in_array($whereAKey, $whereBKeys))
				{
					$result[] = array
					(
						DB::_AND(),
						array
						(
							$whereAKey => $whereA[$whereAKey]
						),
						array
						(
							$whereAKey => $whereB[$whereAKey]
						)
					);
				}
				else
				{
					$result[$whereAKey] = $whereA[$whereAKey];
				}
			}
			foreach ($whereBKeys as $whereBKey)
			{
				if (is_numeric($whereBKey))
				{
					$result[] = $whereB[$whereBKey];
				}
				else if (!in_array($whereBKey, $whereAKeys))
				{
					$result[$whereBKey] = $whereB[$whereBKey];
				}
			}
			return $result;
		}

		/**
		 * Creates a string that contains the string representation of a value.
		 *
		 * @param $value: the value to process, use Database_Field to refer to a field inteads to a string.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessValue(/*mixed*/ $value)
		{
			if ($value instanceof IDatabaseOperator)
			{
				$value = Database_Utility::ProcessExpression(null, $value);
			}
			else if ($value instanceof Database_Field)
			{
				$value = Utility::Sanitize((string)$value, 'html');
			}
			else
			{
				if(!Database_Utility::TryProcessValue($value, $value))
				{
					$value = Utility::Sanitize((string)$value, 'html');
				}
			}
			return $value;
		}

		/**
		 * Creates a string that contains the string representation of a expression.
		 *
		 * @param $field: the field over which the expression is applied.
		 * @param $expression: the expression.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessExpression(/*string*/ $field, /*mixed*/ $expression)
		{
			if ($expression instanceof IDatabaseOperator)
			{
				$operator = (string)$expression;
				if ($expression->Type() == 'unary')
				{
					if (is_null($field))
					{
						throw new Exception ('Invalid operation');
					}
					else
					{
						return Database_Utility::ProcessExpressionUnary($operator, new Database_Field($field));
					}
				}
				else if ($expression->Type() == 'aggregation')
				{
					$result = Database_Utility::ProcessExpressionAggregation($operator, null);
					if (is_null($field))
					{
						return $result;
					}
					else
					{
						return Utility::Sanitize((string)$field, 'html').' = '.$result;
					}
				}
				else
				{
					throw new Exception ('Invalid operation');
				}
			}
			else if (is_array($expression))
			{
				if (array_key_exists(0, $expression) && $expression[0] instanceof IDatabaseOperator)
				{
					$operator = (string)$expression[0];
					if ($expression[0]->Type() == 'unary')
					{
						$result = Database_Utility::ProcessExpressionUnary($operator, $expression[1]);
						if (is_null($field))
						{
							return $result;
						}
						else
						{
							return Utility::Sanitize((string)$field, 'html').' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'aggregation')
					{
						$result = Database_Utility::ProcessExpressionAggregation($operator, $expression[1]);
						if (is_null($field))
						{
							return $result;
						}
						else
						{
							return Utility::Sanitize((string)$field, 'html').' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'binary')
					{
						$result = Database_Utility::ProcessExpressionBinary($operator, $expression[1], $expression[2]);
						if (is_null($field))
						{
							return $result;
						}
						else
						{
							return Utility::Sanitize((string)$field, 'html').' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'n-ary')
					{
						$result = Database_Utility::ProcessExpressionNAry($operator, array_splice($expression, 1));
						if (is_null($field))
						{
							return $result;
						}
						else
						{
							return Utility::Sanitize((string)$field, 'html').' = '.$result;
						}
					}
					else
					{
						throw new Exception ('Invalid operation');
					}
				}
				if (array_key_exists(0, $expression))
				{
					return '('.implode(', ', $expression).')';
				}
				else if (count($expression) > 0)
				{
					$processed = Database_Utility::ProcessFragment($expression, 'Database_Utility::ProcessExpression');
					if (count($processed) == 1)
					{
						return $processed[0];
					}
					else
					{
						//ONLY UTF-8
						return '('.implode(') '.((string)DB::_AND()).' (', $processed).')';
					}
				}
				else
				{
					return '';
				}
			}
			else
			{
				$expression = Database_Utility::ProcessValue($expression);
				if (is_null($field))
				{
					return $expression;
				}
				else
				{
					return Utility::Sanitize((string)$field, 'html').' = '.$expression;
				}
			}
		}

		/**
		 * Partially process a expression or subexpression.
		 *
		 * Intended for internal use only
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessFragment($fragment, $callback)
		{
			if (is_callable($callback))
			{
				$keys = array_keys($fragment);
				$processed = array();
				foreach ($keys as $key)
				{
					if (is_string($key))
					{
						$value = $fragment[$key];
						$processed[] = call_user_func($callback, $key, $value);
					}
					else
					{
						$key = $fragment[$key];
						if (is_string($key))
						{
							$value = new Database_Field($key);
						}
						else
						{
							$value = $key;
						}
						$processed[] = call_user_func($callback, null, $value);
					}
				}
				return $processed;
			}
			else
			{
				throw new Exception('Invalid Callback');
			}
		}

		//------------------------------------------------------------
		// Public (Constructors)
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