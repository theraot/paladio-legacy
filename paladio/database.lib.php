<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
		require_once('utility.lib.php');
		require_once('database_utility.lib.php');
		require_once('dbbase.lib.php');
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

		private static $adapter;
		private static $server;
		private static $port;
		private static $database;
		private static $charset;
		private static $persistent;
		private static $modes;
		private static $fallbackMode;


		/**
		 * Internally used to connect to the database.
		 * @see Database::__construct
		 * @see Database::ConnectQuery
		 * @see Database::ConnectExecute
		 * @access private
		 */
		private static function Connect(/*string*/ $user, /*string*/ $password, /*bool*/ $persist)
		{
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			$connection = Database::$adapter->Connect(Database::$server, Database::$port, $user, $password, Database::$database, Database::$charset, $persist === 'persistent');
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
			else if ($connection !== null)
			{
				Database::$adapter->Disconnect($connection);
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
		 * @see Database::Configure
		 *
		 * @access public
		 * @return bool
		 */
		public static function CanConnect()
		{
			try
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
			catch (PDOException $e)
			{
				return false;
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		/**
		 * Sets the configuration of Database.
		 *
		 * @param $engine: the name of the database engine to use.
		 * @param $server: the ip or domain of the database server.
		 * @param $port: the port of the database server.
		 * @param $database: the name of the database.
		 * @param $charset: the charset of the database.
		 * @param $persistent: null to disable persistance, 'instance' to enable connection reutilization, 'persistent' to request a persistent connection if available.
		 * @param $modes: array of supported modes, each mode has a user and a password.
		 * @param $fallbackMode: the mode used when the requested mode is not available.
		 *
		 * @access public
		 * @return void
		 */
		public static function Configure(/*string*/ $engine, /*string*/ $server, /*string*/ $port, /*string*/ $database, /*string*/ $charset, $persistent = null, $modes = null, $fallbackMode = null)
		{
			Database::$adapter = DBBase::Create($engine);
			Database::$server = $server;
			Database::$port = $port;
			Database::$database = $database;
			Database::$charset = $charset;
			Database::$persistent = $persistent;
			Database::$fallbackMode = $fallbackMode;
			Database::$modes = $modes;
			if (Database::$modes === null)
			{
				Database::$modes = array();
			}
		}

		/**
		 * Connects to the dabase in a given mode.
		 *
		 * Returns a database object.
		 *
		 * @param $mode: the name of the mode.
		 *
		 * @see Database::ConfigureMode
		 *
		 * @access public
		 * @return Database
		 */
		Public static function ConnectMode ($mode)
		{
			if (array_key_exists($mode, Database::$modes))
			{
				$selectedMode = $mode;
				$_mode = Database::$modes[$mode];
			}
			else if (array_key_exists(Database::$fallbackMode, Database::$modes))
			{
				$selectedMode = Database::$fallbackMode;
				$_mode = Database::$modes[Database::$fallbackMode];
			}
			else
			{
				throw new Exception ('The database configurarion is invalid.');
			}
			$recycleConnection = Database::$persistent !== null;
			if ($recycleConnection && array_key_exists('instance', $_mode))
			{
				$connection = $_mode['instance'];
			}
			else
			{
				$connection = new Database($_mode['user'], $_mode['password'], Database::$persistent);
				if ($recycleConnection)
				{
					Database::$modes[$selectedMode]['instance'] = $connection;
				}
			}
			return $connection;
		}

		/**
		 * Connects to the dabase to execute queries.
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
			return Database::ConnectMode('query');
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
			return Database::ConnectMode('execute');
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			return Database::$adapter->CreateQueryCountRecord($table, $where);
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
		public static function CreateQueryRead(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*array*/ $options = null)
		{
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			return Database::$adapter->CreateQueryRead($table, $fields, $where, $options);
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			return Database::$adapter->CreateStatementDelete($table, $where);
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			return Database::$adapter->CreateStatementInsert($record, $table);
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			return Database::$adapter->CreateStatementUpdate($record, $table, $where);
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			if ($database === null)
			{
				$database = Database::ConnectExecute();
				$close = Database::$persistent === null;
			}
			else
			{
				$close = false;
			}
			$connection = $database->get_Connection();
			if ($connection === false)
			{
				return false;
			}
			else
			{
				$ok = false;
				$result = Database::$adapter->Execute($connection, $statement);
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
					$ok = $result->errorCode() === '00000';
					$result->close();
				}
				if ($close)
				{
					$database->Close();
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
			$query = Database::CreateQueryRead($table, $fields, array(false));
			if (Database::Query($query, $result, $database))
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
			if (Database::$adapter === null)
			{
				throw new Exception('Database not available - review configuration.');
			}
			if ($database === null)
			{
				$database = Database::ConnectQuery();
			}
			$connection = $database->get_Connection();
			if ($connection === false)
			{
				return false;
			}
			else
			{
				$result = Database::$adapter->Query($connection, $query);
				$ok = $result !== false;
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
		public static function Read(/*string*/ $table, /*mixed*/ $fields = null, /*mixed*/ $where = null, /*array*/ $options = null, /*Database*/ $database = null)
		{
			$query = Database::CreateQueryRead($table, $fields, $where, $options);
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
				return $record;
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
				if ($record !== null)
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
			if ($database === null)
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
				if (is_array($where))
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
				}
				$result = Database::Insert($record, $table, $_database);
			}
			if ($database === null)
			{
				$_database->Close();
			}
			return $result;
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $connection;
		private $persist;

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
		 * Retrieves the last error message
		 *
		 * @access public
		 * @return string
		 */
		public function ErrorMessage()
		{
			$info = $this->connection->errorInfo();
			return $info[2];
		}

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
			if ($this->persist === null)
			{
				Database::Disconnect($this->connection);
			}
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		/**
		 * Creates a new instance of Database
		 * @param $user: the user to connect to the server.
		 * @param $password: the password to connect to the server.
		 */
		public function __construct(/*string*/ $user, /*string*/ $password, /*bool*/ $persist)
		{
			$this->connection = Database::Connect($user, $password, $persist);
			$this->persist = $persist;
		}

		//------------------------------------------------------------
		// Public (Destructors)
		//------------------------------------------------------------

		public function __destruct()
		{
			if ($this->persist === null || $this->persist === 'instance')
			{
				Database::Disconnect($this->connection);
			}
		}
	}

	require_once('configuration.lib.php');

	Configuration::Callback
	(
		'paladio-database',
		create_function
		(
			'',
			'
				Database::Configure
				(
					Configuration::Get(\'paladio-database\', \'engine\'),
					Configuration::Get(\'paladio-database\', \'server\'),
					Configuration::Get(\'paladio-database\', \'port\'),
					Configuration::Get(\'paladio-database\', \'database\'),
					Configuration::Get(\'paladio-database\', \'charset\'),
					Configuration::Get(\'paladio-database\', \'persist\'),
					Configuration::Get(\'paladio-database\', \'modes\'),
					Configuration::Get(\'paladio-database\', \'fallback_mode\', \'execute\')
				);
			'
		)
	);
?>