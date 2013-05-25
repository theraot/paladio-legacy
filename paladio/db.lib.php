<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
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

	/**
	 * DatabaseOperator_Equal
	 * @package Paladio
	 */
	final class DatabaseOperator_Equal implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '=';}}
	/**
	 * DatabaseOperator_Different
	 * @package Paladio
	 */
	final class DatabaseOperator_Different implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<>';}}
	/**
	 * DatabaseOperator_GreaterThan
	 * @package Paladio
	 */
	final class DatabaseOperator_GreaterThan implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '>';}}
	/**
	 * DatabaseOperator_LessThan
	 * @package Paladio
	 */
	final class DatabaseOperator_LessThan implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<';}}
	/**
	 * DatabaseOperator_GreaterOrEqual
	 * @package Paladio
	 */
	final class DatabaseOperator_GreaterOrEqual implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '>=';}}
	/**
	 * DatabaseOperator_LessOrEqual
	 * @package Paladio
	 */
	final class DatabaseOperator_LessOrEqual implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<=';}}
	/**
	 * DatabaseOperator_Addition
	 * @package Paladio
	 */
	final class DatabaseOperator_Addition implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return '+';}}
	/**
	 * DatabaseOperator_Substraction
	 * @package Paladio
	 */
	final class DatabaseOperator_Substraction implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '-';}}
	/**
	 * DatabaseOperator_Multiplication
	 * @package Paladio
	 */
	final class DatabaseOperator_Multiplication implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return '*';}}
	/**
	 * DatabaseOperator_Division
	 * @package Paladio
	 */
	final class DatabaseOperator_Division implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '/';}}
	/**
	 * DatabaseOperator_Modulus
	 * @package Paladio
	 */
	final class DatabaseOperator_Modulus implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'MOD';}}
	/**
	 * DatabaseOperator_Conjuntion
	 * @package Paladio
	 */
	final class DatabaseOperator_Conjuntion implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return 'AND';}}
	/**
	 * DatabaseOperator_Disjuntion
	 * @package Paladio
	 */
	final class DatabaseOperator_Disjuntion implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return 'OR';}}
	/**
	 * DatabaseOperator_Negation
	 * @package Paladio
	 */
	final class DatabaseOperator_Negation implements IDatabaseOperator {function Type(){return 'unary';} public function __toString(){return 'NOT';}}
	/**
	 * DatabaseOperator_Like
	 * @package Paladio
	 */
	final class DatabaseOperator_Like implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'LIKE';}}
	/**
	 * DatabaseOperator_IsNull
	 * @package Paladio
	 */
	final class DatabaseOperator_IsNull implements IDatabaseOperator {function Type(){return 'unary';} public function __toString(){return 'ISNULL';}}
	/**
	 * DatabaseOperator_IN
	 * @package Paladio
	 */
	final class DatabaseOperator_IN implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'IN';}}
	/**
	 * DatabaseOperator_Count
	 * @package Paladio
	 */
	final class DatabaseOperator_Count implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'COUNT';}}
	/**
	 * DatabaseOperator_Average
	 * @package Paladio
	 */
	final class DatabaseOperator_Average implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'AVG';}}
	/**
	 * DatabaseOperator_Sumation
	 * @package Paladio
	 */
	final class DatabaseOperator_Sumation implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'SUM';}}
	/**
	 * DatabaseOperator_Minimun
	 * @package Paladio
	 */
	final class DatabaseOperator_Minimun implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'MIN';}}
	/**
	 * DatabaseOperator_Minimun
	 * @package Paladio
	 */
	final class DatabaseOperator_Maximun implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'MAX';}}
	/**
	 * DatabaseOperator_Concat
	 * @package Paladio
	 */
	final class DatabaseOperator_Concat implements IDatabaseOperator {function Type(){return 'function';} public function __toString(){return 'CONCAT';}}

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
			if (null !== ($this->current = mysqli_fetch_assoc($this->result)))
			{
				++$this->position;
			}
		}

		function valid()
		{
			return !(is_null($this->current));
		}
		
		function close()
		{
			if (!is_null($this->result))
			{
				mysqli_free_result($this->result);
				$this->current = null;
				$this->result = null;
			}
		}
	}

	/**
	 * DB
	 *
	 * MySQL specific code
	 *
	 * @package Paladio
	 */
	final class DB
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $ADD;
		private static $AND;
		private static $AVG;
		private static $CONCAT;
		private static $COUNT;
		private static $DIV;
		private static $EQ;
		private static $GE;
		private static $GN;
		private static $ISNULL;
		private static $LE;
		private static $LIKE;
		private static $LN;
		private static $MAX;
		private static $MUL;
		private static $MIN;
		private static $MOD;
		private static $NE;
		private static $NOT;
		private static $OR;
		private static $SUB;
		private static $SUM;

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * ADD (addition) operator (binary)
		 */
		public static function _ADD(){return DB::$ADD;}
		/**
		 * AND (conjuntion) operator (n-ary)
		 */
		public static function _AND(){return DB::$AND;}
		/**
		 * AVG (average) aggreation
		 */
		public static function _AVG(){return DB::$AVG;}
		/**
		 * CONCAT (concatenation) operator (n-ary)
		 */
		public static function _CONCAT(){return DB::$CONCAT;}
		/**
		 * COUNT (count) aggregation
		 */
		public static function _COUNT(){return DB::$COUNT;}
		/**
		 * DIV (division) operator (binary)
		 */
		public static function _DIV(){return DB::$DIV;}
		/**
		 * EQ (equal) operator (binary)
		 */
		public static function _EQ(){return DB::$EQ;}
		/**
		 * GE (greater or equal) operator (binary)
		 */
		public static function _GE(){return DB::$GE;}
		/**
		 * GN (greater than) operator (binary)
		 */
		public static function _GN(){return DB::$GN;}
		/**
		 * ISNULL operator (unary)
		 */
		public static function _ISNULL(){return DB::$ISNULL;}
		/**
		 * LE (less or equal) operator (binary)
		 */
		public static function _LE(){return DB::$LE;}
		/**
		 * LIKE (like) operator (binary)
		 */
		public static function _LIKE(){return DB::$LIKE;}
		/**
		 * LN (less than) operator (binary)
		 */
		public static function _LN(){return DB::$LN;}
		/**
		 * MAX (maximun) aggregation
		 */
		public static function _MAX(){return DB::$MAX;}
		/**
		 * MUL (multiplication) operator (binary)
		 */
		public static function _MUL(){return DB::$MUL;}
		/**
		 * MAX (minimun) aggregation
		 */
		public static function _MIN(){return DB::$MIN;}
		/**
		 * MOD (modulus) operator (binary)
		 */
		public static function _MOD(){return DB::$MOD;}
		/**
		 * NE (not equal) operator (binary)
		 */
		public static function _NE(){return DB::$NE;}
		/**
		 * NOT (negation) operator (unary)
		 */
		public static function _NOT(){return DB::$NOT;}
		/**
		 * OR (disjuntion) operator (n-ary)
		 */
		public static function _OR(){return DB::$OR;}
		/**
		 * SUB (substraction) operator (binary)
		 */
		public static function _SUB(){return DB::$SUB;}
		/**
		 * SUM (summarion) aggregation
		 */
		public static function _SUM(){return DB::$SUM;}

		/**
		 * Alias
		 */
		public static function Alias(){return "AS";}

		/**
		 * Initializes static fields
		 */
		public static function __init()
		{
			DB::$ADD = new DatabaseOperator_Addition();
			DB::$AND = new DatabaseOperator_Conjuntion();
			DB::$AVG = new DatabaseOperator_Average();
			DB::$CONCAT = new DatabaseOperator_Concat();
			DB::$COUNT = new DatabaseOperator_Count();
			DB::$DIV = new DatabaseOperator_Division();
			DB::$EQ = new DatabaseOperator_Equal();
			DB::$GE = new DatabaseOperator_GreaterOrEqual();
			DB::$GN = new DatabaseOperator_GreaterThan();
			DB::$ISNULL = new DatabaseOperator_IsNull();
			DB::$LE = new DatabaseOperator_LessOrEqual();
			DB::$LIKE = new DatabaseOperator_Like();
			DB::$LN = new DatabaseOperator_LessThan();
			DB::$MAX = new DatabaseOperator_Maximun();
			DB::$MUL = new DatabaseOperator_Multiplication();
			DB::$MIN = new DatabaseOperator_Minimun();
			DB::$MOD = new DatabaseOperator_Modulus();
			DB::$NE = new DatabaseOperator_Different();
			DB::$NOT = new DatabaseOperator_Negation();
			DB::$OR = new DatabaseOperator_Disjuntion();
			DB::$SUB = new DatabaseOperator_Substraction();
			DB::$SUM = new DatabaseOperator_Sumation();
		}

		//------------------------------------------------------------

		/**
		 * Connects to the database
		 *
		 * Returns the database connection object on success, false otherwise.
		 *
		 * @param $server: the ip or domain of the server
		 * @param $port: the port of the server
		 * @param $user: the user of the database
		 * @param $password: the password of the database
		 * @param $database: the name of the database
		 *
		 * @access public
		 * @return mixed
		 */
		public static function Connect(/*string*/ $server, /*int*/ $port, /*string*/ $user, /*string*/ $password, /*string*/ $database)
		{
			if (!is_numeric($port))
			{
				$severstring = $server;
			}
			else
			{
				$severstring = $server.':'.$port;
			}
			$connection = mysqli_connect($severstring, $user, $password);
			if ($connection === false)
			{
				return false;
			}
			else
			{
				if (mysqli_select_db($connection, $database))
				{
					return $connection;
				}
				else
				{
					mysqli_close($connection);
					return false;
				}
			}
		}

		/**
		 * Closes the connection to the database
		 *
		 * Returns the true on success, false otherwise.
		 *
		 * @param $connection: the database connection object.
		 *
		 * @access public
		 * @return bool
		 */
		public static function Disconnect(/*object*/ $connection)
		{
			return mysqli_close($connection);
		}

		/**
		 * Executes a query or statement.
		 *
		 * If the operation is successful, and $query is a query: returns a Iterator object to traverse the result.
		 * If the operation is successful, and $query is a statement: returns a true.
		 * Otherwise: returns false
		 *
		 * @param $connection: the database connection object.
		 * @param $query: the query or statement to execute.
		 *
		 * @access public
		 * @return mixed
		 */
		public static function Query(/*object*/ $connection, /*string*/ $query)
		{
			$result = mysqli_query($connection, $query);
			if (is_bool($result))
			{
				if ($result)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return new DBIterator($result);
			}
		}

		//------------------------------------------------------------

		/**
		 * Gets the database specific name of a common datatype.
		 *
		 * Returns an string with the name of the datatype if the operation is successful, false otherwise.
		 *
		 * @param $result: the query result to release.
		 *
		 * @access public
		 * @return bool
		 */
		public static function MapType($type)
		{
			$types = array
			(
				//SQL standard types
				'character' => 'char',
				'character varying' => 'varchar',
				'bit' => 'bit',
				'bit varying' => null,
				'numeric' => 'decimal',
				'decimal' => 'decimal',
				'integer' => 'int',
				'smallint' => 'smallint',
				'float' => 'float',
				'double precision' => 'double',
				'date' => 'date',
				'time' => 'time',
				'timestamp' => 'timestamp',
				//.NET / Java
				'byte' => 'tinyint',
				'int' => 'int',
				'short' => 'smallint',
				'long' => 'bigint',
				'float' => 'float',
				'double' => 'double',
				'char' => 'char',
				'bool' => 'tinyint',
				'string' => 'varchar',
				//ECMAScript
				'number' => 'decimal',
				'boolean' => 'tinyint',
				//C++
				'short int' => 'smallint',
				'long int' => 'bigint',
				//others
				'real' => 'double',
				'varchar' => 'varchar',
				'tinyint' => 'tinyint',
				'bigint' => 'bigint'
			);
			if (array_key_exists($type, $types))
			{
				return $types[$type];
			}
			else
			{
				return false;
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

	DB::__init();
?>