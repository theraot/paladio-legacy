<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	/**
	 * DB
	 *
	 * MySQL specific code
	 *
	 * @package Paladio
	 */
	final class DB_MySQL extends DBBase
	{
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
		public function Connect(/*string*/ $server, /*int*/ $port, /*string*/ $user, /*string*/ $password, /*string*/ $database, /*string*/ $charset)
		{
			$DSN = 'mysql:host='.Parser::StringConsumeUntil((string)$server, 0, array(':', ';'), $length).';';
			if (!is_numeric($port))
			{
				$extra = substr($server, $length);
				if (String_Utility::TryNeglectStart($extra, ':', $result))
				{
					$port = $result;
				}
			}
			if (is_numeric($port))
			{
				$DSN .= 'port='.$port.';';
			}
			$database = (string)$database;
			if (strlen($database) > 0)
			{
				$DSN .= 'dbname='.Parser::StringConsumeUntil($database, 0, ';').';';
			}
			try
			{
				$connection = new PDO($DSN, $user, $password);
				if (!is_null($charset))
				{
					$connection->query('set charset '.$this->QuoteIdentifier($charset));
				}
			}
			catch (PDOException $e)
			{
				return false;
			}
			return $connection;
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
		public function Disconnect(/*object*/ $connection)
		{
			return true;
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
		public function Query(/*object*/ $connection, /*mixed*/ $query)
		{
			if (is_string($query))
			{
				$result = $connection->query($query);
			}
			else if
			(
				is_array($query)
				&& array_key_exists('statement', $query) && is_string($query['statement'])
				&& array_key_exists('parameters', $query) && is_array($query['parameters'])
			)
			{
				if (count($query['parameters']) > 0)
				{
					$result = $connection->prepare($query['statement']);
					if (!$result->execute($query['parameters']))
					{
						$result = false;
					}
				}
				else
				{
					$result = $connection->query($query['statement']);
				}
			}
			
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
		
		public function QuoteIdentifier($identifier)
		{
			return '`' . str_replace('`', '``', $identifier) . '`';
		}
	}
?>