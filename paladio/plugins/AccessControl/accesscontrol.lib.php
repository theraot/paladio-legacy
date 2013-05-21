<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
		require_once('configuration.lib.php');
		require_once('pen.lib.php');
	}

	final class AccessControl
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $INI;
		private static $loadedFiles;
		private static $table;
		private static $idField;
		private static $passwordField;
		private static $saltField;
		private static $roleField;
		private static $hashAlgorithm;
		private static $current;
		
		private static function CheckCanAccess(/*mixed*/ $result)
		{
			if (is_null($result))
			{
				return true;
			}
			if (is_array($result))
			{
				if (array_key_exists('accesscontrol', $result))
				{
					$value = $result['accesscontrol'];
					if ($value)
					{
						return true;
					}
					else
					{
						return false;
					}
				}
				return true;
			}
			else if (is_bool($result))
			{
				return $result;
			}
			else
			{
				return true;
			}
		}

		private static function Decode(/*mixed*/ $value)
		{
			if (is_string($value))
			{
				return PEN::Decode($value);
			}
			else
			{
				return $value;
			}
		}

		private static function GetListedFiles(/*array*/ $list, /*string*/ $path)
		{
			$result = array();
			if (is_array($list))
			{
				$keys = array_keys($list);
				foreach($keys as $key)
				{
					$file = FileSystem::ResolveRelativePath($path, $key);
					if (FileSystem::UriExists($file))
					{
						if (AccessControl::CheckCanAccess($list[$key]))
						{
							$result[$key] = $list[$key];
							$result[$key]['path'] = $file;
						}
					}
				}
			}
			return $result;
		}

		private static function TryGetListedData(/*string*/ $file, /*array*/ $list, /*string*/ $path, /*mixed*/ &$result)
		{
			if (is_array($list))
			{
				$keys = array_keys($list);
				foreach($keys as $key)
				{
					if (FileSystem::ResolveRelativePath($path, $key) == $file)
					{
						$result = $list[$key];
						$result['path'] = $file;
						return true;
					}
				}
			}
			return false;
		}

		private static function Preserve()
		{
			if (class_exists('Session'))
			{
				Session::Start();
				$current = AccessControl::$current;
				if (is_null($current))
				{
					Session::unset_Status('user_id');
					Session::unset_Status('user_password');
					Session::unset_Status('user_role');
				}
				else
				{
					Session::set_Status('user_id', $current['id']);
					Session::set_Status('user_password', $current['password']);
					Session::set_Status('user_role', $current['role']);
				}
			}
		}
		
		private static function ReadCategory(/*string*/ $categoryName)
		{
			$category = AccessControl::$INI->get_Category($categoryName);
			if (is_array($category))
			{
				return array_map('AccessControl::Decode', $category);
			}
			else
			{
				return array();
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function CanAccess(/*string*/ $file, /*string*/ $query)
		{
			if (AccessControl::TryGetData($file, $query, $result))
			{
				return AccessControl::CheckCanAccess($result);
			}
			else
			{
				return false;
			}
		}

		public static function Close()
		{
			AccessControl::$current = null;
			AccessControl::Preserve();
		}

		public static function Configure(/*string*/ $table, /*string*/ $idField, /*string*/ $passwordField, /*string*/ $saltField, /*string*/ $roleField, /*string*/ $hashAlgorithm)
		{
			AccessControl::$table = $table;
			AccessControl::$idField = $idField;
			AccessControl::$passwordField = $passwordField;
			AccessControl::$saltField = $saltField;
			AccessControl::$roleField = $roleField;
			AccessControl::$hashAlgorithm = $hashAlgorithm;
		}
		
		public static function CurrentSession()
		{
			return AccessControl::$current;
		}

		public static function Fallback()
		{
			$current = AccessControl::$current;
			$path = FileSystem::FolderInstallation();
			$test = AccessControl::ReadCategory('__fallback');
			$keys = array_keys($test);
			foreach($keys as $key)
			{
				$file = FileSystem::ResolveRelativePath($path, $key);
				$tmp = explode('?', $file);
				$file = $tmp[0];
				if (count($tmp) > 1)
				{
					$query = $tmp[1];
				}
				else
				{
					$query = '';
				}
				if (AccessControl::CanAccess($file, $query))
				{
					return $key;
				}
			}
			return false;
		}
		
		public static function TryGetData(/*string*/ $file, /*string*/ $query, /*mixed*/ &$result)
		{
			$full = $file;
			if ($query !== '')
			{
				$full .= '?'.$query;
			}
			if (FileSystem::UriExists($file))
			{
				$current = AccessControl::$current;
				$path = FileSystem::FolderInstallation();
				$test = AccessControl::ReadCategory('__all');
				if (AccessControl::TryGetListedData($full, $test, $path, $result))
				{
					return true;
				}
				if (is_null(AccessControl::$table) || is_null(AccessControl::$passwordField) || is_null(AccessControl::$idField))
				{
					$test = AccessControl::ReadCategory('__unconfigured');
					if (AccessControl::TryGetListedData($full, $test, $path, $result))
					{
						return true;
					}
				}
				else
				{
					$test = AccessControl::ReadCategory('__configured');
					if (AccessControl::TryGetListedData($full, $test, $path, $result))
					{
						return true;
					}
					if (is_null($current))
					{
						$test = AccessControl::ReadCategory('__unauthenticated');
						if (AccessControl::TryGetListedData($full, $test, $path, $result))
						{
							return true;
						}
					}
					else
					{
						$test = AccessControl::ReadCategory('__authenticated');
						if (AccessControl::TryGetListedData($full, $test, $path, $result))
						{
							return true;
						}
						if (!is_null($current['role']))
						{
							$test = AccessControl::ReadCategory('rol:'.$current['role']);
							if (AccessControl::TryGetListedData($full, $test, $path, $result))
							{
								return true;
							}
						}
					}
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		public static function Load(/*string*/ $basePath)
		{
			if (!is_array(AccessControl::$loadedFiles))
			{
				AccessControl::$loadedFiles = array();
			}
			if (is_null(AccessControl::$INI))
			{
				AccessControl::$INI = new INI();
			}
			if (is_dir($basePath))
			{
				$files = FileSystem::GetFolderFiles('*.nav.php', $basePath);
			}
			else
			{
				$files = array($basePath);
			}
			foreach ($files as $file)
			{
				if (!in_array($file, AccessControl::$loadedFiles))
				{
					AccessControl::$INI->Load($file, 1);
					AccessControl::$loadedFiles[] = $file;
				}
			}
			if (Configuration::TryGet('paladio-paths', 'accesscontrol', $extraPath))
			{
				Load($extraPath);
			}
		}

		public static function Open(/*string*/ $id, /*string*/ $password)
		{
			if (is_null(AccessControl::$table) || is_null(AccessControl::$passwordField) || is_null(AccessControl::$idField))
			{
				AccessControl::$current = null;
				AccessControl::Preserve();
				return false;
			}
			else
			{
				$fields = array(AccessControl::$passwordField);
				if (!is_null(AccessControl::$roleField))
				{
					$fields[] = AccessControl::$roleField;
				}
				if (!is_null(AccessControl::$saltField))
				{
					$fields[] = AccessControl::$saltField;
				}
				Database::TryReadOneRecord
				(
					$record,
					AccessControl::$table,
					$fields,
					array(AccessControl::$idField => $id)
				);
				if (is_null(AccessControl::$saltField))
				{
					$salt = '';
				}
				else
				{
					$salt = $record[AccessControl::$saltField];
				}
				if (is_null(AccessControl::$hashAlgorithm) || AccessControl::$hashAlgorithm == 'none')
				{
					$pass = $salt.$password;
				}
				else if (AccessControl::$hashAlgorithm == 'md5')
				{
					$pass = md5($salt.$password);
				}
				else if (AccessControl::$hashAlgorithm == 'sha1')
				{
					$pass = sha1($salt.$password);
				}
				else
				{
					$pass = hash(AccessControl::$hashAlgorithm, $salt.$password);
				}
				if ($pass == $record[AccessControl::$passwordField])
				{
					if (is_null(AccessControl::$roleField))
					{
						AccessControl::$current = array('id' => $id, 'password' => $password, 'role' => null);
					}
					else
					{
						AccessControl::$current = array('id' => $id, 'password' => $password, 'role' => $record[AccessControl::$roleField]);
					}
					AccessControl::Preserve();
					if (is_null(AccessControl::$roleField))
					{
						return true;
					}
					else
					{
						return $record[AccessControl::$roleField];
					}
				}
				else
				{
					AccessControl::$current = null;
					AccessControl::Preserve();
					return false;
				}
			}
		}

		public static function ValidUris()
		{
			$current = AccessControl::$current;
			$path = FileSystem::FolderInstallation();
			if (is_null($current))
			{
				if (is_null(AccessControl::$table) || is_null(AccessControl::$passwordField) || is_null(AccessControl::$idField))
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__unconfigured'), $path)
					);
				}
				else
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__configured'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__unauthenticated'), $path)
					);
				}
			}
			else
			{
				if (is_null($current['role']))
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__configured'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__authenticated'), $path)
					);
				}
				else
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__configured'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__authenticated'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('rol:'.$current['role']), $path)
					);
				}
			}
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __constrct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}

	AccessControl::Load(FileSystem::FolderCore());
	AccessControl::Load(dirname(__FILE__));

	function AccessControl_Session()
	{
		Session::Start();
		if (Session::isset_Status('user_id') && Session::isset_Status('user_password'))
		{
			$id = Session::get_Status('user_id');
			$password = Session::get_Status('user_password');
			AccessControl::Open($id, $password);
		}
		if (!AccessControl::CanAccess(FileSystem::ScriptPath(), $_SERVER['QUERY_STRING']))
		{
			$fallback = AccessControl::Fallback();
			if ($fallback === false)
			{
				header('HTTP/1.0 403 Forbidden');
			}
			else
			{
				header('Location: '.$fallback, true, 307);
			}
			exit();
		}
	}
	function AccessControl_Configure()
	{
		AccessControl::Configure
		(
			Configuration::Get('paladio-accesscontrol', 'table'),
			Configuration::Get('paladio-accesscontrol', 'id_field'),
			Configuration::Get('paladio-accesscontrol', 'password_field'),
			Configuration::Get('paladio-accesscontrol', 'salt_field'),
			Configuration::Get('paladio-accesscontrol', 'role_field'),
			Configuration::Get('paladio-accesscontrol', 'hash_algorithm')
		);
		if (is_file('session.lib.php'))
		{
			require_once('session.lib.php');
		}
		Paladio::Request('Session', 'AccessControl_Session');
	}
	Configuration::Callback('paladio-accesscontrol', 'AccessControl_Configure');
?>