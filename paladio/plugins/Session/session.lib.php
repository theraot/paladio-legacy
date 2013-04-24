<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	final class Session
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

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

		public static function Start()
		{
			if (!Session::Exists() && !headers_sent())
			{
				session_start();
				return Session::Exists();
			}
			else
			{
				return false;
			}
		}

		public static function Destroy()
		{
			session_destroy();
		}

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

		public static function ClearTokens()
		{
			if (Session::Exists())
			{
				$_SESSION['tokens'] = array();
			}
		}

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

		public static function TryDequeue(&$data)
		{
			if (Session::Exists())
			{
				if (!array_key_exists('queue', $_SESSION) || !is_array($_SESSION['queue']))
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
		
		public static function DequeueAll()
		{
			if (Session::Exists())
			{
				if (!array_key_exists('queue', $_SESSION) || !is_array($_SESSION['queue']))
				{
					return false;
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

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>