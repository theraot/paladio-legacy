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

	/**
	 * Database
	 * @package Paladio
	 */
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

		/**
		 * Internally used to process list entries.
		 * @see Database::ListRecords
		 * @access private
		 */
		private static function CallbackListRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$result[] = $record['_value'];
		}

		/**
		 * Internally used to process list entries.
		 * @see Database::ListRecords
		 * @access private
		 */
		private static function CallbackListRecordsEx(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$result[] = $record;
		}

		/**
		 * Internally used to process graph entries.
		 * @see Database::GraphRecords
		 * @access private
		 */
		private static function CallbackGraphRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$source = $record['_source'];
			$target = $record['_target'];
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

		/**
		 * Internally used to process map entries.
		 * @see Database::MapRecords
		 * @access private
		 */
		private static function CallbackMapRecords(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$key = $record['_key'];
			$value = $record['_value'];
			$result[$key] = $value;
		}

		/**
		 * Internally used to process map entries.
		 * @see Database::MapRecords
		 * @access private
		 */
		private static function CallbackMapRecordsEx(/*array*/ &$result, /*array*/ $record, /*mixed*/ $context)
		{
			$nameKeyField = $context;
			$key = $record[$nameKeyField];
			$result[$key] = $record;
		}

		/**
		 * Internally used to connect to the database.
		 * @see Database::__construct
		 * @see Database::ConnectQuery
		 * @see Database::ConnectExecute
		 * @access private
		 */
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

		/**
		 * Internally used to create an assignment expression.
		 * @see Database::CreateStatementUpdate
		 * @access private
		 */
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

		/**
		 * Internally used to disconnect from the database.
		 * @access private
		 */
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

		/**
		 * Internally used to process a field list.
		 * @see Database::CreateQueryRead
		 * @access private
		 */
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

		/**
		 * Internally used to process a where list.
		 * @see Database::CreateQueryRead
		 * @see Database::CreateStatementUpdate
		 * @see Database::CreateStatementDelete
		 * @access private
		 */
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

		/**
		 * Verifies if the connection to the database is available.
		 *
		 * If the connection is available returns true, false otherwise.
		 *
		 * @access public
		 * @return bool
		 */
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

		/**
		 * Sets the configuration of Database.
		 *
		 * @param $server: the ip or domain of the database server.
		 * @param $port: the port of the database server.
		 * @param $database: the name of the database.
		 * @param $executeUser: the name of the user used to execute statements.
		 * @param $executeKey: the password of the user used to execute statements.
		 * @param $queryUser: the name of the user used to execute queries, if null $executeUser is used.
		 * @param $queryKey: the password of the user used to execute queries, if null $executeKey is used.
		 *
		 * @access public
		 * @return void
		 */
		public static function Configure(/*string*/ $server, /*string*/ $port, /*string*/ $database, /*string*/ $executeUser, /*string*/ $executeKey, /*string*/ $queryUser = null, /*string*/ $queryKey = null)
		{
			Database::$server = $server;
			Database::$port = $port;
			Database::$database = $database;
			if (is_null($queryUser))
			{
				Database::$queryUser = $executeUser;
			}
			else
			{
				Database::$queryUser = $queryUser;
			}
			if (is_null($queryKey))
			{
				Database::$queryKey = $executeKey;
			}
			else
			{
				Database::$queryKey = $queryKey;
			}
			Database::$executeUser = $executeUser;
			Database::$executeKey = $executeKey;
		}

		/**
		 * Connects to the dabase to execute queries.
		 *
		 * Connects to the database using $queryUser and $queryKey set in Database::Configure.
		 *
		 * Returns a database object that can be used to execute queries.
		 *
		 * @see Database::Configure
		 * @see Database::__construct
		 *
		 * @access public
		 * @return Database
		 */
		public static function ConnectQuery()
		{
			return new Database(Database::$queryUser, Database::$queryKey);
		}

		/**
		 * Connects to the dabase to execute statements.
		 *
		 * Connects to the database using $executeUser and $executeKey set in Database::Configure.
		 *
		 * Returns a database object that can be used to execute queries.
		 *
		 * @see Database::Configure
		 * @see Database::__construct
		 *
		 * @access public
		 * @return Database
		 */
		public static function ConnectExecute()
		{
			return new Database(Database::$executeUser, Database::$queryKey);
		}

		/**
		 * Counts the records in the table $table that match the condition $where.
		 *
		 * Returns the number of entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table where the entries will be counted.
		 * @param $where: The condition the entries need to match to be counted.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made..
		 *
		 * @see Database::ConnectQuery
		 * @see Database::CreateQueryCountRecords
		 *
		 * @access public
		 * @return int
		 */
		public static function CountRecords(/*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			if (Database::Query(Database::CreateQueryCountRecords($table, $where), $result, $database))
			{
				$record = Database::GetRecord($result);
				$count = $record['_amount'];
				Database::ReleaseResult($result);
				return $count;
			}
			else
			{
				return 0;
			}
		}

		/**
		 * Creates a string with a query that can be used to counts the records in the table $table that match the condition $where.
		 *
		 * Returns a string with a query to get the number of entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table where the entries will be counted.
		 * @param $where: The condition the entries need to match to be counted.
		 *
		 * @see Database::CountRecords
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateQueryCountRecords(/*string*/ $table, /*mixed*/ $where = null)
		{
			return Database::CreateQueryRead($table, array('_amount' => DB::_COUNT()), $where);
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
		 * @see Database::Read
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateQueryRead(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null)
		{
			return 'SELECT '.Database::ProcessFields($fields).' FROM '.Utility::Sanitize($table, 'html').' '.Database::ProcessWhere($where);
		}

		/**
		 * Creates a string with a statement that can be used delete the entries of table $table that match the condition $where.
		 *
		 * Returns a string with a statement to delete the values of the entries in the table $table that match the condition $where.
		 *
		 * @param $table: The table to be deleted.
		 * @param $where: The condition the entries need to match to be deleted.
		 *
		 * @see Database::Delete
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateStatementDelete(/*string*/ $table, /*mixed*/ $where = null)
		{
			return 'DELETE FROM '.Utility::Sanitize($table, 'html').' '.Database::ProcessWhere($where);
		}

		/**
		 * Creates a string with a statement that can be used insert the values of $record into the table $table.
		 *
		 * Returns a string with a statement to insert the values of $record into the table $table.
		 *
		 * @param $record: The values to be inserted.
		 * @param $table: The table to which insert.
		 *
		 * @see Database::Insert
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateStatementInsert(/*array*/ $record, /*string*/ $table)
		{
			$fields = array_keys($record);
			//ONLY UTF-8
			$statement = 'INSERT INTO '.$table.' ('.implode(', ', $fields).') VALUES (';
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

		/**
		 * Creates a string with a statement that can be used update the entries of table $table that match the condition $where with the values of $record.
		 *
		 * Returns a string with a statement to update the values of the entries in the table $table that match the condition $where with the values of $record.
		 *
		 * @param $record: The values to be set.
		 * @param $table: The table to be updated.
		 * @param $where: The condition the entries need to match to be updated.
		 *
		 * @see Database::Update
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateStatementUpdate(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null)
		{
			return 'UPDATE '.Utility::Sanitize($table, 'html').' '.Database::CreateAssignment($record).' '.Database::ProcessWhere($where);
		}

		/**
		 * Deletes the entries of table $table that match the condition $where.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @param $table: The table to be deleted.
		 * @param $where: The condition the entries need to match to be deleted.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::CreateStatementDelete
		 *
		 * @access public
		 * @return bool
		 */
		public static function Delete(/*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Execute(Database::CreateStatementDelete($table, $where), $database);
		}

		/**
		 * Executes a callback for each entry in the table $table that matches the condition $where.
		 *
		 * If $context is passed the expected signature of the callback is: function callback(&$result, $record, $context), where $result is array, $record is array, and $context is mixed.
		 * If $context is not passed the expected signature of the callback is: function callback(&$result, $record), where $result is array, $record is array.
		 *
		 * Note: the callbacks will recieve an array $result as first parameter, the final value of $result will be returned.
		 *
		 * @param $callback: The function to be executed per each entry.
		 * @param $table: The table to iterate over.
		 * @param $fields: The fields to retrieve. The values of the fields will be passed as second parameters to the callback.
		 * @param $where: The condition the entries need to match to be passed to the callback.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 * @param $context: if passed, $context will be passed to the callbacks as third parameter.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::CreateStatementDelete
		 *
		 * @access public
		 * @return array of mixed
		 */
		public static function Enumerate(/*function*/ $callback, /*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null, /*mixed*/ $context = null)
		{
			if (is_callable($callback))
			{
				$use_context = func_num_args > 5;
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
							if ($use_context)
							{
								call_user_func_array($callback, array(&$result, $record, $context));
							}
							else
							{
								call_user_func_array($callback, array(&$result, $record));
							}
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

		/**
		 * Executes a statement.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @param $statement: A string with the statement to execute.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::CreateStatementDelete
		 *
		 * @access public
		 * @return bool
		 */
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
				$result = DB::Query($connection, (string)$statement);
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

		/**
		 * Reads the values of the entry of a result of a query.
		 *
		 * Returns an associative array with the values of the entry.
		 *
		 * @param $result: The result of a query.
		 *
		 * @access public
		 * @return array of mixed
		 */
		public static function GetRecord(/*object*/ $result)
		{
			return DB::GetRecord($result);
		}

		/**
		 * Builds a graph with the entries of a table.
		 *
		 * Returns an associative array of GraphNode objects.
		 *
		 * Note: a graph is a data structure that represents the relations between items, it can be used to represent networks connections.
		 *
		 * @param $table: the table that contains the relationships entries to include.
		 * @param $sourceField: the field that contains the source of the relationship.
		 * @param $targetField: the field that contains the target of the relationship.
		 * @param $where: the condition the entries need to match to be included.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::Enumerate
		 *
		 * @access public
		 * @return array of GraphNode
		 */
		public static function GraphRecords(/*string*/ $table, /*string*/ $sourceField, /*string*/ $targetField, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Enumerate('Database::CallbackGraphRecords', $table, array('source' => $sourceField, 'target' => $targetField), $where, $database);
		}

		/**
		 * Verifies if the table $table has the specified fields.
		 *
		 * Performe a query to table $table requesting the field $fields.
		 *
		 * @param $table: the table to query.
		 * @param $fields: the fields to query.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::CreateQueryRead
		 *
		 * @access public
		 * @return bool
		 */
		public static function HasFields(/*string*/ $table, /*mixed*/ $fields, /*Database*/ $database = null)
		{
			//TODO: where to false
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

		/**
		 * Insert the values of $record into the table $table.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @param $record: The values to be inserted.
		 * @param $table: The table to which insert.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::CreateStatementInsert
		 *
		 * @access public
		 * @return bool
		 */
		public static function Insert($record, $table, $database = null)
		{
			return Database::Execute(Database::CreateStatementInsert($record, $table), $database);
		}

		/**
		 * Builds a list with the entries of a table.
		 *
		 * @param $table: the table that contains entries to include.
		 * @param $field: the field to include.
		 * @param $where: the condition the entries need to match to be included.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::Enumerate
		 *
		 * @access public
		 * @return array of mixed
		 */
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
				return Database::Enumerate('Database::CallbackListRecords', $table, array('_value' => (string)$field), $where, $database);
			}
		}

		/**
		 * Builds a map (associative array) with the entries of a table.
		 *
		 * @param $table: the table that contains entries to include.
		 * @param $keyField: the field to use as key of the map.
		 * @param $field: the field to include.
		 * @param $where: the condition the entries need to match to be included.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::Enumerate
		 *
		 * @access public
		 * @return array of mixed
		 */
		public static function MapRecords(/*string*/ $table, /*string*/ $keyField, /*string*/ $field, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$nameKeyField = '_key';
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
				return Database::Enumerate('Database::CallbackMapRecords', $table, array($nameKeyField => $keyField, '_value' => (string)$field), $where, $database);
			}
		}

		/**
		 * Executes a query.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @param $query: A string with the query to execute.
		 * @param &$result: Set to the query result if the operation was successful, false otherwise.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 *
		 * @access public
		 * @return bool
		 */
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

		/**
		 * Reads fields $fields of the entries of table $table that match the condition $where.
		 *
		 * Returns a query result if the operation was successful, false otherwise.
		 *
		 * @param $table: The table to be read.
		 * @param $fields: The fields that will be returned in the query.
		 * @param $where: The condition the entries need to match to be read.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::Query
		 * @see Database::CreateQueryRead
		 *
		 * @access public
		 * @return string
		 */
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

		/**
		 * Reads one entry of table $table that match the condition $where.
		 *
		 * Returns a true if the operation was successful, false otherwise.
		 *
		 * @param &$result: Set to the values of the readed entry if the operation was successful, left untouched otherwise.
		 * @param $table: The table to be read.
		 * @param $fields: The fields that will be returned in the query.
		 * @param $where: The condition the entries need to match to be read.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::Query
		 * @see Database::CreateQueryRead
		 *
		 * @access public
		 * @return string
		 */
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

		/**
		 * Releases a query result that will no longer be used.
		 *
		 * Returns a true if the operation was successful, false otherwise.
		 *
		 * @param $result: the query result to release.
		 *
		 * @access public
		 * @return string
		 */
		public static function ReleaseResult(/*object*/ $result)
		{
			return DB::Release($result);
		}

		/**
		 * Verifies if the table $table exists.
		 *
		 * Performe a query to table $table.
		 *
		 * @param $table: the table to query.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectQuery
		 * @see Database::CreateQueryRead
		 *
		 * @access public
		 * @return bool
		 */
		public static function TableExists(/*string*/ $table, /*Database*/ $database = null)
		{
			//TODO request no fields
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

		/**
		 * Updates the entries of table $table that match the condition $where with the values of $record.
		 *
		 * Returns a true if the operation was successful, false otherwise.
		 *
		 * @param $record: The values to be set.
		 * @param $table: The table to be updated.
		 * @param $where: The condition the entries need to match to be updated.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::CreateStatementUpdate
		 *
		 * @access public
		 * @return string
		 */
		public static function Update(/*array*/ $record, /*string*/ $table, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			return Database::Execute(Database::CreateStatementUpdate($record, $table, $where), $database);
		}

		/**
		 * Inserts or Updates the entries of table $table that match the condition $where with the values of $record.
		 *
		 * Returns a true if the operation was successful, false otherwise.
		 *
		 * Note: not all values of $where are valid, the values of the fields used in $where must be trivial to retrive.
		 *
		 * @param $record: The values to be set.
		 * @param $table: The table to be updated.
		 * @param $where: The condition the entries need to match to be updated.
		 * @param $database: A database object that will be used to execute the operation, if null a new connection will be made.
		 *
		 * @see Database::ConnectExecute
		 * @see Database::Update
		 * @see Database::Insert
		 *
		 * @access public
		 * @return string
		 */
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

		/**
		 * Internally used to get the connection to the database
		 * @access private
		 * @return database specific connection object
		 */
		private function get_Connection()
		{
			return $this->connection;
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		/**
		 * Closes this instance of Database
		 *
		 * @see Database::Disconnect
		 *
		 * @access public
		 * @return void
		 */
		public function Close()
		{
			Database::Disconnect($this->connection);
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		/**
		 * Creates a new instance of Database
		 * @param $user: the user to connect to the server;
		 * @param $key: the password to connect to the server;
		 */
		public function __construct(/*string*/ $user, /*string*/ $key)
		{
			$this->connection = Database::Connect($user, $key);
		}
	}

	/**
	 * GraphNode
	 * @package Paladio
	 */
	final class GraphNode
	{
		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		/**
		 * The id of the node
		 */
		public $id;

		/**
		 * The incomming relationships
		 */
		public $incoming;

		/**
		 * The outgoing relationships
		 */
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