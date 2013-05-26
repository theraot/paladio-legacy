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
		private static $queryPassword;
		private static $executeUser;
		private static $executePassword;


		/**
		 * Internally used to connect to the database.
		 * @see Database::__construct
		 * @see Database::ConnectQuery
		 * @see Database::ConnectExecute
		 * @access private
		 */
		private static function Connect(/*string*/ $user, /*string*/ $password)
		{
			$connection = DB::Connect(Database::$server, Database::$port, $user, $password, Database::$database);
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
		 * To connect uses $queryUser and $queryPassword set in Database::Configure.
		 *
		 * If the connection is available returns true, false otherwise.
		 *
		 * @see Database::Configure
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
		 * @param $executePassword: the password of the user used to execute statements.
		 * @param $queryUser: the name of the user used to execute queries, if null $executeUser is used.
		 * @param $queryPassword: the password of the user used to execute queries, if null $executePassword is used.
		 *
		 * @access public
		 * @return void
		 */
		public static function Configure(/*string*/ $server, /*string*/ $port, /*string*/ $database, /*string*/ $executeUser, /*string*/ $executePassword, /*string*/ $queryUser = null, /*string*/ $queryPassword = null)
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
			if (is_null($queryPassword))
			{
				Database::$queryPassword = $executePassword;
			}
			else
			{
				Database::$queryPassword = $queryPassword;
			}
			Database::$executeUser = $executeUser;
			Database::$executePassword = $executePassword;
		}

		/**
		 * Connects to the dabase to execute queries.
		 *
		 * Connects to the database using $queryUser and $queryPassword set in Database::Configure.
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
			return new Database(Database::$queryUser, Database::$queryPassword);
		}

		/**
		 * Connects to the dabase to execute statements.
		 *
		 * Connects to the database using $executeUser and $executePassword set in Database::Configure.
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
			return new Database(Database::$executeUser, Database::$queryPassword);
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
				$result->rewind();
				$record = $result->current();
				$count = $record['_amount'];
				$result->close();
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
					$result->close();
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
			$consulta = Database::CreateQueryRead($table, $fields, array(false));
			if (Database::Query($consulta, $result, $database))
			{
				$result->close();
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
					$ok = true;
				}
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
		 * @return mixed
		 */
		public static function Read(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$query = Database::CreateQueryRead($table, $fields, $where);
			if (Database::Query($query, $result, $database))
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
		 * Returns the readed entry if the operation was successful, null otherwise.
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
		public static function ReadOneRecord(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$result = Database::Read($table, $fields, $where, $database);
			if ($result === false)
			{
				return null;
			}
			else
			{
				$result->rewind();
				$record = $result->current();
				$result->close();
				if (!is_null($record))
				{
					return $record;
				}
				else
				{
					return null;
				}
			}
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
			$result = Database::Read($table, array(0), null, $database);
			if ($result === false)
			{
				return false;
			}
			else
			{
				$result->close();
				return true;
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
		public static function TryReadOneRecord(/*array*/ &$record, /*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*Database*/ $database = null)
		{
			$result = Database::Read($table, $fields, $where, $database);
			if ($result === false)
			{
				return false;
			}
			else
			{
				$result->rewind();
				$record = $result->current();
				$result->close();
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
		 * @param $user: the user to connect to the server.
		 * @param $password: the password to connect to the server.
		 */
		public function __construct(/*string*/ $user, /*string*/ $password)
		{
			$this->connection = Database::Connect($user, $password);
		}
	}

	require_once('configuration.lib.php');

	/**
	 * Intended for internal use only
	 */
	function Database_Configure()
	{
		Database::Configure
		(
			Configuration::Get('paladio-database', 'server'),
			Configuration::Get('paladio-database', 'port'),
			Configuration::Get('paladio-database', 'database'),
			Configuration::Get('paladio-database', 'user'),
			Configuration::Get('paladio-database', 'password'),
			Configuration::Get('paladio-database', 'query_user'),
			Configuration::Get('paladio-database', 'query_password')
		);
	}
	Configuration::Callback('paladio-database', 'Database_Configure');
?>