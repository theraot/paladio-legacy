<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('database_utility.lib.php');
		FileSystem::RequireAll('*.db.php', FileSystem::FolderCore());
	}

	/**
	 * IDatabaseOperator
	 * @package Paladio
	 */
	interface IDatabaseOperator
	{
		/**
		 * Returns the type of the operator.
		 */
		public function Type();
	}

	final class DatabaseOperator implements IDatabaseOperator
	{
		private $type;
		private $symbol;

		function Type()
		{
			return $this->type;
		}

		public function __toString()
		{
			return $this->symbol;
		}

		public function __construct(/*string*/ $type, /*string*/ $symbol)
		{
			$this->type = $type;
			$this->symbol = $symbol;
		}
	}

	final class DBIterator implements Iterator
	{
		private $result;
		private $position;
		private $current;

		public function __construct(/*object*/ $result)
		{
			if ($result === null)
			{
				throw new Exception ('Invalid result');
			}
			else
			{
				$this->result = $result;
				$this->position = 0;
				$this->current = null;
			}
		}

		public function __destruct()
		{
			$this->close();
		}

		function errorCode()
		{
			return $this->result->errorCode();
		}

		function rewind()
		{
			$this->next();
		}

		function current()
		{
			return $this->current;
		}

		function key()
		{
			return $this->position;
		}

		function next()
		{
			if (null !== ($this->current = $this->result->fetch(PDO::FETCH_ASSOC)))
			{
				++$this->position;
			}
		}

		function valid()
		{
			return $this->current !== false;
		}

		function close()
		{
			if ($this->result !== null)
			{
				$this->result->closeCursor();
				$this->current = null;
				$this->result = null;
			}
		}
	}

	/**
	 * DB
	 *
	 * @package Paladio
	 */
	class DBBase
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Create($engine)
		{
			$class = 'DB_'.$engine;
			$file = FileSystem::FolderCore().'db.'.mb_strtolower($engine).'.lib.php';
			if (file_exists($file))
			{
				require_once($file);
			}
			if (class_exists($class))
			{
				return new $class();
			}
			else
			{
				return null;
			}
		}

		//------------------------------------------------------------
		// Protected (Instance)
		//------------------------------------------------------------

		protected $_OP;

		/**
		 * Internally used to process a field list.
		 * @access protected
		 */
		protected function ProcessFields(/*mixed*/ $fields = null, /*array*/ &$_parameters)
		{
			if (is_array($fields) && count($fields) > 0)
			{
				$processed = Database_Utility::ProcessFragment($this, $fields, array('Database_Utility', 'CreateAlias'), $_parameters);
				return implode(', ', $processed);
			}
			else if ($fields === null || (is_array($fields) && count($fields) == 0))
			{
				return '*';
			}
			else
			{
				return $this->ProcessFields(array($fields), $_parameters);
			}
		}

		/**
		 * Internally used to create an assignment expression.
		 * @access protected
		 */
		protected function CreateAssignment(/*array*/ $record, /*array*/ &$_parameters)
		{
			$assignment = '';
			if (is_array($record) && count($record) > 0)
			{
				$assignment = 'SET ';
				$fields = array_keys($record);
				$array = array();
				foreach ($fields as $field)
				{
					$array[] = $this->QuoteIdentifier($field).' = ?';
					$_parameters[] = $record[$field];
				}
				$assignment .= implode(', ', $array);
			}
			return $assignment;
		}

		/**
		 * Internally used to process a where list.
		 * @access protected
		 */
		protected function ProcessWhere(/*mixed*/ $where, /*array*/ &$_parameters)
		{
			if (is_array($where) && count($where) > 0)
			{
				$processed = Database_Utility::ProcessFragment($this, $where, array('Database_Utility', 'ProcessExpression'), $_parameters);
				if (count($processed) == 1)
				{
					return ' WHERE '.$processed[0];
				}
				else
				{
					return ' WHERE ('.implode(') '.($this->OP('AND')->__toString()).' (', $processed).')';
				}
			}
			else if ($where === null || (is_array($where) && count($where) == 0))
			{
				return '';
			}
			else
			{
				$result = $this->ProcessWhere(array($where), $_parameters);
				return $result;
			}
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public function OP($op){return $this->_OP[$op];}

		public function Alias(){return "AS";}

		/**
		 * Creates a string with a query that can be used to counts the records in the table $table that match the condition $where.
		 *
		 * Returns a string with a query to get the number of entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table where the entries will be counted.
		 * @param $where: The condition the entries need to match to be counted.
		 *
		 * @access public
		 * @return string
		 */
		public function CreateQueryCountRecord(/*string*/ $table, /*mixed*/ $where = null)
		{
			return $this->CreateQueryRead($table, array('_amount' => $this->OP('COUNT')), $where);
		}

		/**
		 * Creates a string with a query that can be used read fields $fields of the entries of table $table that match the condition $where.
		 *
		 * Returns a string with a query to get the fields $fields of the entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table to be read.
		 * @param $fields: The fields that will be returned in the query.
		 * @param $where: The condition the entries need to match to be read.
		 *
		 * @access public
		 * @return string
		 */
		public function CreateQueryRead(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*array*/ $options = null)
		{
			$_parameters = array();
			$statement = 'SELECT '.$this->ProcessFields($fields, $_parameters).' FROM '.$this->QuoteIdentifier($table).$this->ProcessWhere($where, $_parameters);
			if (is_array($options))
			{
				if (array_key_exists('offset', $options))
				{
					if (array_key_exists('fetch', $options))
					{
						$statement .= ' OFFSET '.$options['offset'].' ROWS FETCH FIRST '.$options['fetch'].' ROWS ONLY';
					}
					else
					{
						$statement .= ' OFFSET '.$options['offset'].' ROWS';
					}
				}
				else
				{
					if (array_key_exists('fetch', $options))
					{
						$statement .= ' FETCH FIRST '.$options['fetch'].' ROWS ONLY';
					}
				}
			}
			return array('statement' => $statement, 'parameters' => $_parameters);
		}

		/**
		 * Creates a string with a statement that can be used delete the entries of table $table that match the condition $where.
		 *
		 * Returns a string with a statement to delete the values of the entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table to be deleted.
		 * @param $where: The condition the entries need to match to be deleted.
		 *
		 * @access public
		 * @return string
		 */
		public function CreateStatementDelete(/*string*/ $table, /*mixed*/ $where = null)
		{
			$_parameters = array();
			$statement = 'DELETE FROM '.$this->QuoteIdentifier($table).$this->ProcessWhere($where, $_parameters);
			return array('statement' => $statement, 'parameters' => $_parameters);
		}

		/**
		 * Creates a string with a statement that can be used insert the values of $record into the table $table.
		 *
		 * Returns a string with a statement to insert the values of $record into the table $table.
		 *
		 * @param $record: The values to be inserted.
		 * @param $table: The table to which insert.
		 *
		 * @access public
		 * @return string
		 */
		public function CreateStatementInsert(/*array*/ $record, /*string*/ $table)
		{
			if (!is_array($record))
			{
				$record = array($record);
			}
			$record = Utility::ArrayCompact($record);
			$fields = array_keys($record);
			$_parameters = array();
			$statement = 'INSERT INTO '.$table.' ('.implode(', ', $fields).') VALUES (';
			$array = array();
			foreach ($fields as $field)
			{
				$value = $record[$field];
				$array[] = Database_Utility::ProcessValue($this, $value, $_parameters);
			}
			$statement .= implode(', ', $array).')';
			return array('statement' => $statement, 'parameters' => $_parameters);
		}

		/**
		 * Creates a string with a statement that can be used update the entries of table $table that match the condition $where with the values of $record.
		 *
		 * Returns a string with a statement to update the values of the entries in the table $table that match the condition $where with the values of $record.
		 *
		 * @param $record: The values to be set.
		 * @param $table: The table to be updated.
		 * @param $where: The condition the entries need to match to be updated.
		 *
		 * @access public
		 * @return string
		 */
		public function CreateStatementUpdate(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null)
		{
			$_parameters = array();
			$statement = 'UPDATE '.$this->QuoteIdentifier($table).' '.$this->CreateAssignment($record, $_parameters).$this->ProcessWhere($where, $_parameters);
			return array('statement' => $statement, 'parameters' => $_parameters);
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		/**
		 * Creating instances of this class is not allowed.
		 */
		public function __construct()
		{
			$this->_OP =
			array
			(
				'ADD' => new DatabaseOperator('n-ary', '+'),
				'AND' => new DatabaseOperator('n-ary', 'AND'),
				'AVG' => new DatabaseOperator('aggregation', 'AVG'),
				'CONCAT' => new DatabaseOperator('function', 'CONCAT'),
				'COUNT' =>  new DatabaseOperator('aggregation', 'COUNT'),
				'DIV' => new DatabaseOperator('binary', '/'),
				'EQ' => new DatabaseOperator('binary', '='),
				'GE' => new DatabaseOperator('binary', '>='),
				'GN' => new DatabaseOperator('binary', '>'),
				'IN' => new DatabaseOperator('binary', 'IN'),
				'ISNULL' => new DatabaseOperator('unary', 'ISNULL'),
				'LE' => new DatabaseOperator('binary', '<='),
				'LIKE' => new DatabaseOperator('binary', 'LIKE'),
				'LN' => new DatabaseOperator('binary', '<'),
				'MAX' => new DatabaseOperator('aggregation', 'MAX'),
				'MUL' => new DatabaseOperator('n-ary', '*'),
				'MIN' => new DatabaseOperator('aggregation', 'MIN'),
				'MOD' => new DatabaseOperator('binary', 'MOD'),
				'NE' => new DatabaseOperator('binary', '<>'),
				'NOT' => new DatabaseOperator('unary', 'NOT'),
				'OR' => new DatabaseOperator('n-ary', 'OR'),
				'SUB' => new DatabaseOperator('binary', '-'),
				'SUM' => new DatabaseOperator('aggregation', 'SUM')
			);
		}
	}
?>