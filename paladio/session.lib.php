<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	/**
	 * Session
	 * @package Paladio
	 */
	final class Session
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Clears the values of the current session.
		 *
		 * @param $commit: if set to true calls session_commit();
		 *
		 * @acess public
		 * @return void
		 */
		public static function Clear($commit = true)
		{
			$keys = array_keys($_SESSION);
			foreach ($keys as $key)
			{
				unset($_SESSION[$key]);
			}
			if ($commit)
			{
				session_commit();
			}
		}

		/**
		 * Starts a new session if no previous session exists.
		 *
		 * Returns true if no previous session existed, false otherwise.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function Start($regenerate = false)
		{
			if (headers_sent())
			{
				return false;
			}
			else
			{
				if (!Session::Exists())
				{
					session_start();
					return Session::Exists();
				}
				else
				{
					return session_regenerate_id(true);
				}
			}
		}

		/**
		 * Destroys the current session.
		 *
		 * @acess public
		 * @return void
		 */
		public static function Destroy()
		{
			session_destroy();
		}

		/**
		 * Verifies if a session currently exists.
		 *
		 * Returns true if a session currently exists, false otherwise.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function Exists()
		{
			if (session_id() != '')
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		/**
		 * Clears the tokens associated with this session.
		 *
		 * Note: the tokens are stored in $_SESSION['tokens'].
		 *
		 * @acess public
		 * @return void
		 */
		public static function ClearTokens()
		{
			if (Session::Exists())
			{
				$_SESSION['tokens'] = array();
			}
		}

		/**
		 * Creates a new token and stores it in this session.
		 *
		 * Returns the new token if a session currently exists, false otherwise.
		 *
		 * Note: the tokens are stored in $_SESSION['tokens'].
		 *
		 * @param $length: the length of the new token.
		 *
		 * @acess public
		 * @return mixed
		 */
		public static function CreateToken($length = 10)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('tokens', $_SESSION) || !is_array($_SESSION['tokens']))
				{
					$_SESSION['tokens'] = array();
				}
				do
				{
					$token = Utility::Sanitize(String_Utility::RandomString($length), 'html');
				}while (in_array($token, $_SESSION));
				array_push($_SESSION['tokens'], $token);
				return $token;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Consumes a token from this session.
		 *
		 * Returns true if a session currently exists and the token was consumed, false otherwise.
		 *
		 * Note: the tokens are stored in $_SESSION['tokens'].
		 *
		 * @param $token: the token to consume.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function ClaimToken($token)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('tokens', $_SESSION) || !is_array($_SESSION['tokens']))
				{
					return false;
				}
				else if ($token === false)
				{
					return true;
				}
				else
				{
					$token = Utility::Sanitize($token, 'html');
					$index = array_search($token, $_SESSION['tokens']);
					if ($index === false)
					{
						return false;
					}
					else
					{
						array_splice($_SESSION, $index);
						return true;
					}
				}
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		/**
		 * Adds a value to the queue of this session.
		 *
		 * Returns true if a session currently exists, false otherwise.
		 *
		 * Note: the queue is stored in $_SESSION['queue'].
		 *
		 * @param $data: the value to add to the queue.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function Enqueue($data)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('queue', $_SESSION) || !is_array($_SESSION['queue']))
				{
					$_SESSION['queue'] = array($data);
				}
				else
				{
					array_unshift($_SESSION['queue'], $data);
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Attempts to retrieve a value from the queue of this session.
		 *
		 * Returns true if a session currently exists and the value was retrieved, false otherwise.
		 *
		 * Note: the queue is stored in $_SESSION['queue'].
		 *
		 * @param &$data: set to the value retrieved from the queue.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function TryDequeue(&$data)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('queue', $_SESSION) || !is_array($_SESSION['queue']) || count($_SESSION['queue']) == 0)
				{
					return false;
				}
				else
				{
					$data = array_pop($_SESSION['queue']);
					return true;
				}
			}
		}

		/**
		 * Retrieves all the values from the queue of this session.
		 *
		 * Returns the entire queue if a session currently exists, false otherwise.
		 *
		 * Note: the queue is stored in $_SESSION['queue'].
		 *
		 * @acess public
		 * @return mixed
		 */
		public static function DequeueAll()
		{
			if (Session::Exists())
			{
				if (!array_key_exists('queue', $_SESSION) || !is_array($_SESSION['queue']))
				{
					return array();
				}
				else
				{
					$data = $_SESSION['queue'];
					$_SESSION['queue'] = array();
					return $data;
				}
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		/**
		 * Gets the status of this session identified by the key $status.
		 *
		 * Returns the status if a session currently exists and the status has been set, false otherwise.
		 *
		 * Note: the status is stored in $_SESSION['status'].
		 *
		 * @param $status: the name of the status to retrieve.
		 *
		 * @acess public
		 * @return mixed
		 */
		public static function get_Status($status)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('status', $_SESSION) || !is_array($_SESSION['status']))
				{
					return false;
				}
				else if (array_key_exists($status, $_SESSION['status']))
				{
					return $_SESSION['status'][$status];
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

		/**
		 * Sets the status of this session identified by the key $status.
		 *
		 * Returns true a session currently exists, false otherwise.
		 *
		 * Note: the status is stored in $_SESSION['status'].
		 *
		 * @param $status: the name of the status to set.
		 * @param $value: the value to store in the status identified by the key $status.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function set_Status($status, $value)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('status', $_SESSION) || !is_array($_SESSION['status']))
				{
					$_SESSION['status'] = array($status => $value);
				}
				else
				{
					$_SESSION['status'][$status] = $value;
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Verifies if the status of this session identified by the key $status has been set.
		 *
		 * Returns true a session currently exists and the status has been set, false otherwise.
		 *
		 * Note: the status is stored in $_SESSION['status'].
		 *
		 * @param $status: the name of the status to verify.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function isset_Status($status)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('status', $_SESSION) || !is_array($_SESSION['status']))
				{
					return false;
				}
				else if (array_key_exists($status, $_SESSION['status']))
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

		/**
		 * Unsets the value of the status of this session identified by the key $status.
		 *
		 * Returns true a session currently exists, false otherwise.
		 *
		 * Note: the status is stored in $_SESSION['status'].
		 *
		 * @param $status: the name of the status to unset.
		 *
		 * @acess public
		 * @return bool
		 */
		public static function unset_Status($status)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('status', $_SESSION) || !is_array($_SESSION['status']))
				{
					return true;
				}
				else if (array_key_exists($status, $_SESSION['status']))
				{
					unset($_SESSION['status']);
					return true;
				}
				else
				{
					return true;
				}
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		/**
		 * Creating instances of this class is not allowed.
		 */
		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>