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
		private static function TryProcessValue($adapter, /*mixed*/ $value, /*string*/ &$result, /*array*/ &$_parameters)
		{
			if (is_string($value))
			{
				$result = '?';
				$_parameters[] = $value;
				return true;
			}
			else if (is_numeric($value))
			{
				$result = '?';
				$_parameters[] = $value;
				return true;
			}
			else if ($value === null)
			{
				$result = 'NULL';
				return true;
			}
			else if (is_bool($value))
			{
				if ($value)
				{
					$result = 'TRUE';
				}
				else
				{
					$result = 'FALSE';
				}
				return true;
			}
			else if (is_array($value))
			{
				$result = Database_Utility::ProcessExpression($adapter, null, $value, $_parameters);
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
		private static function ProcessExpressionUnary($adapter, $operator, $parameter, &$_parameters)
		{
			return $operator.'('.Database_Utility::ProcessValue($adapter, $parameter, $_parameters).')';
		}

		/**
		 * Internally used to process aggregation expressions.
		 * @access private
		 */
		private static function ProcessExpressionAggregation($adapter, $operator, $parameter, &$_parameters)
		{
			if ($parameter === null)
			{
				return $operator.'(*)';
			}
			else
			{
				return $operator.'('.Database_Utility::ProcessValue($adapter, $parameter, $_parameters).')';
			}
		}

		/**
		 * Internally used to process binary expressions.
		 * @access private
		 */
		private static function ProcessExpressionBinary($adapter, $operator, $parameterA, $parameterB, &$_parameters)
		{
			return '('.Database_Utility::ProcessValue($adapter, $parameterA, $_parameters).' '.$operator.' '.Database_Utility::ProcessValue($adapter, $parameterB, $_parameters).')';
		}

		/**
		 * Internally used to process function expressions.
		 * @access private
		 */
		private static function ProcessExpressionFunction($adapter, $operator, $parameters, &$_parameters)
		{
			$processed = array();
			foreach($parameters as $parameter)
			{
				$processed[] = Database_Utility::ProcessValue($adapter, $parameter, $_parameters);
			}
			return $operator.'('.implode(', ', $processed).')';
		}

		/**
		 * Internally used to process n-ary expressions.
		 * @access private
		 */
		private static function ProcessExpressionNAry($adapter, $operator, $parameters, &$_parameters)
		{
			$processed = array();
			foreach($parameters as $parameter)
			{
				$processed[] = Database_Utility::ProcessValue($adapter, $parameter, $_parameters);
			}
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
		 * @param $_parameters: an array used to store the found values to be used in a prepared statement.
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateAlias($adapter, /*string*/ $alias, /*mixed*/ $value, /*array*/ &$_parameters)
		{
			$value = Database_Utility::ProcessValue($adapter, $value, $_parameters);
			if ($alias === null)
			{
				return $value;
			}
			else
			{
				return $value.' '.$adapter->Alias().' '.$adapter->QuoteIdentifier((string)$alias);
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
		public static function MergeWheres($adapter, /*array*/ $whereA, /*array*/ $whereB)
		{
			$result = array();
			$whereAKeys = Utility::ArraySort(array_keys($whereA));
			$index = 0;
			$whereBKeys = array_keys($whereB);
			foreach ($whereAKeys as $whereAKey)
			{
				if ($whereAKey === $index)
				{
					$result[] = $whereA[$whereAKey];
					$index++;
				}
				else if (in_array($whereAKey, $whereBKeys))
				{
					$result[] = array
					(
						$adapter->OP('AND'),
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
		 * @param $parameters: an array used to store the found values to be used in a prepared statement.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessValue($adapter, /*mixed*/ $value, /*array*/ &$_parameters)
		{
			if ($value instanceof IDatabaseOperator)
			{
				$value = Database_Utility::ProcessExpression($adapter, null, $value, $_parameters);
			}
			else if ($value instanceof Database_Field)
			{
				$value = $adapter->QuoteIdentifier($value->__toString());
			}
			else
			{
				if(!Database_Utility::TryProcessValue($adapter, $value, $value, $_parameters))
				{
					$_parameters[] = $value;
					$value = '?';
				}
			}
			return $value;
		}

		/**
		 * Creates a string that contains the string representation of a expression.
		 *
		 * @param $field: the field over which the expression is applied.
		 * @param $expression: the expression.
		 * @param $_parameters: an array used to store the found values to be used in a prepared statement.
		 *
		 * @access public
		 * @return string
		 */
		public static function ProcessExpression($adapter, /*string*/ $field, /*mixed*/ $expression, /*array*/ &$_parameters)
		{
			if ($expression instanceof IDatabaseOperator)
			{
				$operator = $expression->__toString();
				if ($expression->Type() == 'unary')
				{
					if ($field === null)
					{
						throw new Exception ('Invalid operation');
					}
					else
					{
						return Database_Utility::ProcessExpressionUnary($adapter, $operator, new Database_Field($field), $_parameters);
					}
				}
				else if ($expression->Type() == 'aggregation')
				{
					$result = Database_Utility::ProcessExpressionAggregation($adapter, $operator, null, $_parameters);
					if ($field === null)
					{
						return $result;
					}
					else
					{
						return $adapter->QuoteIdentifier((string)$field).' = '.$result;
					}
				}
				else if ($expression->Type() == 'function')
				{
					$result = Database_Utility::ProcessExpressionFunction($operator, array(), $_parameters);
					if ($field === null)
					{
						return $result;
					}
					else
					{
						return $adapter->QuoteIdentifier((string)$field).' = '.$result;
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
					$operator = $expression[0]->__toString();
					if ($expression[0]->Type() == 'unary')
					{
						$result = Database_Utility::ProcessExpressionUnary($adapter, $operator, $expression[1], $_parameters);
						if ($field === null)
						{
							return $result;
						}
						else
						{
							return $adapter->QuoteIdentifier((string)$field).' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'aggregation')
					{
						$result = Database_Utility::ProcessExpressionAggregation($adapter, $operator, $expression[1], $_parameters);
						if ($field === null)
						{
							return $result;
						}
						else
						{
							return $adapter->QuoteIdentifier((string)$field).' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'binary')
					{
						$result = Database_Utility::ProcessExpressionBinary($operator, $expression[1], $expression[2], $_parameters);
						if ($field === null)
						{
							return $result;
						}
						else
						{
							return $adapter->QuoteIdentifier((string)$field).' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'n-ary')
					{
						$result = Database_Utility::ProcessExpressionNAry($operator, array_splice($expression, 1), $_parameters);
						if ($field === null)
						{
							return $result;
						}
						else
						{
							return $adapter->QuoteIdentifier((string)$field).' = '.$result;
						}
					}
					else if ($expression[0]->Type() == 'function')
					{
						$result = Database_Utility::ProcessExpressionFunction($operator, array_splice($expression, 1), $_parameters);
						if ($field === null)
						{
							return $result;
						}
						else
						{
							return $adapter->QuoteIdentifier((string)$field).' = '.$result;
						}
					}
					else
					{
						throw new Exception ('Invalid operation');
					}
				}
				else if (count($expression) > 0)
				{
					if ($field === null)
					{
						$processed = Database_Utility::ProcessFragment($adapter, $expression, array('Database_Utility', 'ProcessExpression'), $_parameters);
						if (count($processed) == 1)
						{
							return $processed[0];
						}
						else
						{
							return '('.implode(') '.($adapter->OP('OR')->__toString()).' (', $processed).')';
						}
					}
					else
					{
						if (count($expression) == 1)
						{
							return Database_Utility::ProcessExpression($adapter, $field, $expression[0], $_parameters);
						}
						else
						{
							$processed = array();
							foreach ($expression as $exp)
							{
								$processed[] = Database_Utility::ProcessExpression($adapter, $field, $exp, $_parameters);
							}
							return '('.implode(') '.($adapter->OP('OR')->__toString()).' (', $processed).')';
						}
					}
				}
				else
				{
					return '';
				}
			}
			else
			{
				$expression = Database_Utility::ProcessValue($adapter, $expression, $_parameters);
				if ($field === null)
				{
					return $expression;
				}
				else
				{
					return $adapter->QuoteIdentifier((string)$field).' = '.$expression;
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
		public static function ProcessFragment($adapter, $fragment, $callback, &$_parameters)
		{
			if (is_callable($callback))
			{
				$keys = Utility::ArraySort(array_keys($fragment));
				$index = 0;
				$processed = array();
				foreach ($keys as $key)
				{
					if ($index === $key)
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
						$processed[] = call_user_func_array($callback, array($adapter, null, $value, &$_parameters));
						$index++;
					}
					else
					{
						$value = $fragment[$key];
						$processed[] = call_user_func_array($callback, array($adapter, $key, $value, &$_parameters));
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