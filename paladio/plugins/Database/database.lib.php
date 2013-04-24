<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('utility.lib.php');
		require_once('database_utility.lib.php');
		require_once('db.lib.php');
	}

	final class Database
	{

		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $server;
		private static $port;
		private static $database;
		private static $queryUser;
		private static $queryKey;
		private static $executeUser;
		private static $executeKey;

		private static function CallbackListRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$result[] = $record['value'];
		}

		private static function CallbackListRecordsEx(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$result[] = $record;
		}

		private static function CallbackGraphRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$source = $record['source'];
			$target = $record['target'];
			if (!array_key_exists($source, $result))
			{
				$node = new GraphNode();
				$node->id = $source;
				$result[$source] = $node;
			}
			if (!array_key_exists($target, $result))
			{
				$node = new GraphNode();
				$node->id = $target;
				$result[$target] = $node;
			}
			$sourceNode = $result[$source];
			$targetNode = $result[$target];
			$sourceNode->outgoing[] = $targetNode;
			$targetNode->incoming[] = $sourceNode;
		}

		private static function CallbackMapRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$key = $record['key'];
			$value = $record['value'];
			$result[$key] = $value;
		}


		private static function CallbackMapRecordsEx(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$nameKeyField = $context;
			$key = $record[$nameKeyField];
			$result[$key] = $record;
		}

		private static function Connect(/*string*/ $user, /*string*/ $key)
		{
			$connection = DB::Connect(Database::$server, Database::$port, $user, $key, Database::$database);
			if ($connection === false)
			{
				return false;
			}
			else
			{
				return $connection;
			}
		}

		private static function CreateAssignment(/*array*/ $record)
		{
			$assignment = '';
			if (is_array($record) && count($record) > 0)
			{
				$assignment = 'SET ';
				$fields = array_keys($record);
				$array = array();
				foreach ($fields as $field)
				{
					$value = $record[$field];
					$array[] = Database_Utility::CreateEquation($field, $value);
				}
				//ONLY UTF-8
				$assignment .= implode(', ', $array);
			}
			return $assignment;
		}

		private static function Disconnect(/*mixed*/ $connection)
		{
			if (is_array($connection))
			{
				foreach($connection as $item)
				{
					Database::Disconnect($item);
				}
			}
			else
			{
				DB::Disconnect($connection);
			}
		}

		private static function ProcessFields(/*mixed*/ $fields = null)
		{
			if (is_array($fields) && count($fields) > 0)
			{
				$processed = Database_Utility::ProcessFragment($fields, 'Database_Utility::CreateAlias');
				//ONLY UTF-8
				return implode(', ', $processed);
			}
			else if (is_null($fields) || (is_array($fields) && count($fields) == 0))
			{
				return '*';
			}
			else
			{
				return Database::ProcessFields(array($fields));
			}
		}

		private static function ProcessWhere(/*mixed*/ $where)
		{
			if (is_array($where) && count($where) > 0)
			{
				$processed = Database_Utility::ProcessFragment($where, 'Database_Utility::ProcessExpression');
				if (count($processed) == 1)
				{
					return 'WHERE '.$processed[0];
				}
				else
				{
					//ONLY UTF-8
					return 'WHERE ('.implode(') '.((string)DB::_AND()).' (', $processed).')';
				}
			}
			else if (is_null($where) || (is_array($where) && count($where) == 0))
			{
				return '';
			}
			else
			{
				return Database::ProcessWhere(null, array($where));
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function CanConnect()
		{
			$connection = Database::ConnectQuery()->get_Connection();
			if ($connection === false)
			{
				return false;
			}
			else
			{
				Database::Disconnect($connection);
				return true;
			}
		}

		public static function Configure(/*string*/ $server, /*string*/ $port, /*string*/ $database, /*string*/ $executeUser, /*string*/ $executeKey, /*string*/ $queryUser = null, /*string*/ $queryKey = null)
		{
			Database::$server = $server;
			Database::$port = $port;
			Database::$database = $database;
			if (!is_null($queryUser) && !is_null($queryKey))
			{
				Database::$queryUser = $queryUser;
				Database::$queryKey = $queryKey;
			}
			else
			{
				Database::$queryUser = $executeUser;
				Database::$queryKey = $executeKey;
			}
			Database::$executeUser = $executeUser;
			Database::$executeKey = $executeKey;
		}

		public static function ConnectQuery()
		{
			return new Database(Database::$queryUser, Database::$queryKey);
		}

		public static function ConnectExecute()
		{
			return new Database(Database::$executeUser, Database::$queryKey);
		}

		public static function CountRecords(/*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			if (Database::Query(Database::CreateQueryCountRecords($table, $where), $result, $database))
			{
				$record = Database::GetRecord($result);
				$count = $record['conteo'];
				Database::ReleaseResult($result);
				return $count;
			}
			else
			{
				return 0;
			}
		}

		public static function CreateQueryCountRecords(/*string*/ $table, /*mixed*/ $where = null)
		{
			return Database::CreateQueryRead($table, array('conteo' => DB::_COUNT()), $where);
		}

		public static function CreateQueryRead(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null)
		{
			return 'SELECT '.Database::ProcessFields($fields).' FROM '.Utility::Sanitize($table, 'html').' '.Database::ProcessWhere($where);
		}

		public static function CreateStatementUpdate(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null)
		{
			return 'UPDATE '.Utility::Sanitize($table, 'html').' '.Database::CreateAssignment($record).' '.Database::ProcessWhere($where);
		}

		public static function CreateStatementDelete(/*string*/ $table, /*mixed*/ $where = null)
		{
			return 'DELETE FROM '.Utility::Sanitize($table, 'html').' '.Database::ProcessWhere($where);
		}

		public static function CreateStatementInsert(/*array*/ $record, /*string*/ $table)
		{
			$fields = array_keys($record);
			//ONLY UTF-8
			$statement = 'INSERT INTO '.$table.' ('.implode(', ', $fields).') VALUE (';
			$array = array();
			foreach ($fields as $field)
			{
				$value = $record[$field];
				$array[] = Database_Utility::ProcessValue($value);
			}
			//ONLY UTF-8
			$statement .= implode(', ', $array).')';
			return $statement;
		}

		public static function Delete(/*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Execute(Database::CreateStatementDelete($table, $where), $database);
		}

		public static function Enumerate(/*function*/ $callback, /*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null, /*mixed*/ $context = null)
		{
			if (is_callable($callback))
			{
				$result = array();
				$databaseResult = Database::Read($table, $fields, $where, $database);
				if ($databaseResult !== false)
				{
					while (true)
					{
						$record = Database::GetRecord($databaseResult);
						if (is_null($record))
						{
							break;
						}
						else
						{
							call_user_func_array($callback, array(&$result, $record, $context));
						}
					}
				}
				return $result;
			}
			else
			{
				throw new Exception('invalid callback');
			}
		}

		public static function Execute(/*string*/ $statement, /*Database*/ $database = null)
		{
			if (is_null($database))
			{
				$connection = Database::ConnectExecute()->get_Connection();
			}
			else
			{
				$connection = $database->get_Connection();
			}
			if ($connection === false)
			{
				return false;
			}
			else
			{
				$ok = false;
				$result = DB::Query($connection, $statement);
				if ($result === true)
				{
					$ok = true;
				}
				else if ($result === false)
				{
					$ok = false;
				}
				else
				{
					DB::Release($result);
					$ok = false;
				}
				if (is_null($database))
				{
					Database::Disconnect($connection);
				}
				return $ok;
			}
		}

		public static function GetRecord(/*object*/ $result)
		{
			return DB::GetRecord($result);
		}

		public static function GraphRecords(/*string*/ $table, /*string*/ $sourceField, /*string*/ $targetField, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Enumerate('Database::CallbackGraphRecords', $table, array('source' => $sourceField, 'target' => $targetField), $where, $database);
		}

		public static function HasFields(/*string*/ $table, /*mixed*/ $fields, /*Database*/ $database = null)
		{
			$consulta = Database::CreateQueryRead($table, $fields, array())/*.' LIMIT 0, 1'*/;
			if (Database::Query($consulta, $result, $database))
			{
				Database::ReleaseResult($result);
				return true;
			}
			else
			{
				return false;
			}
		}

		public static function Insert($record, $table, $database = null)
		{
			return Database::Execute(Database::CreateStatementInsert($record, $table), $database);
		}

		public static function ListRecords(/*string*/ $table, /*mixed*/ $field, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			if (is_array($field))
			{
				return Database::Enumerate('Database::CallbackListRecordsEx', $table, $field, $where, $database);
			}
			else if (is_null($field))
			{
				return Database::Enumerate('Database::CallbackListRecordsEx', $table, null, $where, $database);
			}
			else
			{
				return Database::Enumerate('Database::CallbackListRecords', $table, array('value' => (string)$field), $where, $database);
			}
		}

		public static function MapRecords(/*string*/ $table, /*string*/ $keyField, /*string*/ $field, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$nameKeyField = 'key';
			if (is_array($field))
			{
				while (array_key_exists($nameKeyField, $field) || in_array($nameKeyField, $fields))
				{
					$nameKeyField = '_'.$nameKeyField;
				}
				$fields[$nameKeyField] = $keyField;
				return Database::Enumerate('Database::CallbackMapRecordsEx', $table, $fields, $where, $database, $nameKeyField);
			}
			else
			{
				return Database::Enumerate('Database::CallbackMapRecords', $table, array($nameKeyField => $keyField, 'value' => (string)$field), $where, $database);
			}
		}

		public static function Query(/*string*/ $query, /*object*/ &$result, /*Database*/ $database = null)
		{
			if (is_null($database))
			{
				$connection = Database::ConnectQuery()->get_Connection();
			}
			else
			{
				$connection = $database->get_Connection();
			}
			if ($connection === false)
			{
				return false;
			}
			else
			{
				$result = DB::Query($connection, $query);
				$ok = $result !== false;
				if (is_null($database))
				{
					Database::Disconnect($connection);
				}
				return $ok;
			}
		}

		public static function Read(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			if (Database::Query(Database::CreateQueryRead($table, $fields, $where), $result, $database))
			{
				return $result;
			}
			else
			{
				return false;
			}
		}

		public static function ReadOneRecord(/*array*/ &$record, /*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$result = Database::Read($table, $fields, $where, $database);
			if ($result === false)
			{
				return false;
			}
			else
			{
				$record = Database::GetRecord($result);
				Database::ReleaseResult($result);
				if (!is_null($record))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		public static function ReleaseResult(/*object*/ $result)
		{
			return DB::Release($result);
		}
		
		public static function TableExists(/*string*/ $table, /*Database*/ $database = null)
		{
			$result = Database::Read($table, null, null, $database);
			if ($result === false)
			{
				return false;
			}
			else
			{
				Database::ReleaseResult($result);
				return true;
			}
		}


		public static function Update(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Execute(Database::CreateStatementUpdate($record, $table, $where), $database);
		}

		public static function Write(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			if (is_null($database))
			{
				$_database = Database::ConnectExecute();
			}
			else
			{
				$_database = $database;
			}
			$result = false;
			if (Database::CountRecords($table, $where, $_database) == 1)
			{
				$result = Database::Update($record, $table, $where, $_database);
			}
			else
			{
				$keys = array_keys($where);
				foreach ($keys as $key)
				{
					if
					(
						is_string($key) &&
						!($where[$key] instanceof IDatabaseOperator) &&
						!(is_array($where[$key]))
					)
					{
						$record[$key] = $where[$key];
					}
				}
				$result = Database::Insert($record, $table, $_database);
			}
			if (is_null($database))
			{
				$_database->Close();
			}
			return $result;
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $connection;

		private function get_Connection()
		{
			return $this->connection;
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public function Close()
		{
			Database::Disconnect($this->connection);
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __construct(/*string*/ $user, /*string*/ $key)
		{
			$this->connection = Database::Connect($user, $key);
		}
	}

	final class GraphNode
	{
		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public $id;
		public $incoming;
		public $outgoing;
	}

	require_once('configuration.lib.php');
	function Database_Configure()
	{
		Database::Configure
		(
			Configuration::Get('paladio-database', 'server'),
			Configuration::Get('paladio-database', 'port'),
			Configuration::Get('paladio-database', 'database'),
			Configuration::Get('paladio-database', 'user'),
			Configuration::Get('paladio-database', 'key'),
			Configuration::Get('paladio-database', 'query_user'),
			Configuration::Get('paladio-database', 'query_key')
		);
	}
	Configuration::Callback('paladio-database', 'Database_Configure');
?>