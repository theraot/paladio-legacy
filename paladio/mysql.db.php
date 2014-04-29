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
		public function Connect(/*string*/ $server, /*int*/ $port, /*string*/ $user, /*string*/ $password, /*string*/ $database, /*string*/ $charset, /*bool*/ $persist)
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
				if ($persist)
				{
					$attributes = array(PDO::ATTR_PERSISTENT => true);
				}
				else
				{
					$attributes = array(PDO::ATTR_PERSISTENT => false);
				}
				$connection = new PDO($DSN, $user, $password, $attributes);
				if ($charset !== null)
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
		 * Executes a query.
		 *
		 * If the operation is successful, returns a Iterator object to traverse the result.
		 * Otherwise: returns false
		 *
		 * @param $connection: the database connection object.
		 * @param $query: the query or statement to execute.
		 * @param $autoClose: indicates to close the connection once the iteration is over.
		 *
		 * @access public
		 * @return mixed
		 */
		public function Query(/*object*/ $connection, /*mixed*/ $query)
		{
			try
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
						if ($result === false || !$result->execute($query['parameters']))
						{
							return false;
						}
					}
					else
					{
						$result = $connection->query($query['statement']);
					}
					return new DBIterator($result);
				}
				else
				{
					return false;
				}
			}
			catch (PDOException $e)
			{
				return false;
			}
		}

		/**
		 * Executes a statement.
		 *
		 * If the operation is successful, returns a true.
		 * Otherwise: returns false
		 *
		 * @param $connection: the database connection object.
		 * @param $query: the query or statement to execute.
		 *
		 * @access public
		 * @return mixed
		 */
		public function Execute(/*object*/ $connection, /*mixed*/ $statement)
		{
			try
			{
				if (is_string($statement))
				{
					$result = $connection->query($statement);
				}
				else if
				(
					is_array($statement)
					&& array_key_exists('statement', $statement) && is_string($statement['statement'])
					&& array_key_exists('parameters', $statement) && is_array($statement['parameters'])
				)
				{
					if (count($statement['parameters']) > 0)
					{
						$result = $connection->prepare($statement['statement']);
						if ($result === false || !$result->execute($statement['parameters']))
						{
							return false;
						}
					}
					else
					{
						$result = $connection->query($statement['statement']);
					}
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
					return false;
				}
			}
			catch (PDOException $e)
			{
				return false;
			}
		}

		//------------------------------------------------------------

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
						$statement .= ' LIMIT '.$options['fetch'].' OFFSET '.$options['offset'];
					}
					else
					{
						$statement .= ' OFFSET '.$options['offset'];
					}
				}
				else
				{
					if (array_key_exists('fetch', $options))
					{
						$statement .= ' LIMIT '.$options['fetch'];
					}
				}
			}
			return array('statement' => $statement, 'parameters' => $_parameters);
		}

		//------------------------------------------------------------

		public function QuoteIdentifier($identifier)
		{
			return '`' . str_replace('`', '``', $identifier) . '`';
		}
	}
?>