<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	
	final class DatabaseOperator_Equal implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '=';}}
	final class DatabaseOperator_Different implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<>';}}
	final class DatabaseOperator_GreaterThan implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '>';}}
	final class DatabaseOperator_LessThan implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<';}}
	final class DatabaseOperator_GreaterOrEqual implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '>=';}}
	final class DatabaseOperator_LessOrEqual implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '<=';}}
	final class DatabaseOperator_Addition implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return '+';}}
	final class DatabaseOperator_Substraction implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '-';}}
	final class DatabaseOperator_Multiplication implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return '*';}}
	final class DatabaseOperator_Division implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return '/';}}
	final class DatabaseOperator_Modulus implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'MOD';}}
	final class DatabaseOperator_Conjuntion implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return 'AND';}}
	final class DatabaseOperator_Disjuntion implements IDatabaseOperator {function Type(){return 'n-ary';} public function __toString(){return 'OR';}}
	final class DatabaseOperator_Negation implements IDatabaseOperator {function Type(){return 'unary';} public function __toString(){return 'NOT';}}
	final class DatabaseOperator_Like implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'LIKE';}}
	final class DatabaseOperator_IsNull implements IDatabaseOperator {function Type(){return 'unary';} public function __toString(){return 'ISNULL';}}
	final class DatabaseOperator_IN implements IDatabaseOperator {function Type(){return 'binary';} public function __toString(){return 'IN';}}
	final class DatabaseOperator_Count implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'COUNT';}}
	final class DatabaseOperator_Average implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'AVG';}}
	final class DatabaseOperator_Sumation implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'SUM';}}
	final class DatabaseOperator_Minimun implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'MIN';}}
	final class DatabaseOperator_Maximun implements IDatabaseOperator {function Type(){return 'aggregation';} public function __toString(){return 'MAX';}}

	final class DB
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $ADD;
		private static $AND;
		private static $AVG;
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

		public static function _ADD(){return DB::$ADD;}
		public static function _AND(){return DB::$AND;}
		public static function _AVG(){return DB::$AVG;}
		public static function _COUNT(){return DB::$COUNT;}
		public static function _DIV(){return DB::$DIV;}
		public static function _EQ(){return DB::$EQ;}
		public static function _GE(){return DB::$GE;}
		public static function _GN(){return DB::$GN;}
		public static function _ISNULL(){return DB::$ISNULL;}
		public static function _LE(){return DB::$LE;}
		public static function _LIKE(){return DB::$LIKE;}
		public static function _LN(){return DB::$LN;}
		public static function _MAX(){return DB::$MAX;}
		public static function _MUL(){return DB::$MUL;}
		public static function _MIN(){return DB::$MIN;}
		public static function _MOD(){return DB::$MOD;}
		public static function _NE(){return DB::$NE;}
		public static function _NOT(){return DB::$NOT;}
		public static function _OR(){return DB::$OR;}
		public static function _SUB(){return DB::$SUB;}
		public static function _SUM(){return DB::$SUM;}

		public static function __init()
		{
			DB::$ADD = new DatabaseOperator_Addition();
			DB::$AND = new DatabaseOperator_Conjuntion();
			DB::$AVG = new DatabaseOperator_Average();
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
		
		public static function Connect(/*string*/ $server, /*int*/ $port, /*string*/ $user, /*string*/ $key, /*string*/ $database)
		{
			if (!is_numeric($port))
			{
				$severstring = $server;
			}
			else
			{
				$severstring = $server.':'.$port;
			}
			$connection = mysqli_connect($severstring, $user, $key);
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
		
		public static function Disconnect(/*object*/ $connection)
		{
			return mysqli_close($connection);
		}
		
		public static function GetRecord(/*object*/ $result)
		{
			return mysqli_fetch_assoc($result);
		}
		
		public static function Query(/*object*/ $connection, /*string*/ $query)
		{
			return mysqli_query($connection, $query);
		}
		
		public static function Release(/*object*/ $result)
		{
			if (is_resource($result))
			{
				return mysql_free_result($result);
			}
			else
			{
				return false;
			}
		}
		
		//------------------------------------------------------------
		
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
				return null;
			}
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
	
	DB::__init();
?>