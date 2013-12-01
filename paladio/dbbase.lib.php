<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
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
			if (is_null($result))
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
			if (!is_null($this->result))
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
			return new $class();
		}

		//------------------------------------------------------------
		// Protected (Instance)
		//------------------------------------------------------------

		protected $_OP;

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public function OP($op){return $this->_OP[$op];}

		public function Alias(){return "AS";}

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