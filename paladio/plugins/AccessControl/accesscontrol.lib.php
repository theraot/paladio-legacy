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
	}

	final class AccessControl
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $rules;
		private static $table;
		private static $idField;
		private static $passwordField;
		private static $saltField;
		private static $roleField;
		private static $hashAlgorithm;
		private static $current;

		private static function CheckCanAccess(/*mixed*/ &$result)
		{
			$value = true;
			if ($result === null)
			{
				$result = array();
			}
			else if (is_array($result))
			{
				if (array_key_exists('allow', $result))
				{
					$value = $result['allow'];
				}
				else
				{
					$result['allow'] = true;
				}
			}
			else if (is_bool($result))
			{
				$value = $result;
				$result = array('allow' => $result);
			}
			if ($value)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		private static function ExtractQuery($solved, &$file, &$query)
		{
			$tmp = explode('?', $solved);
			$file = $tmp[0];
			$hasQuery = count($tmp) > 1;
			if ($hasQuery)
			{
				$query = $tmp[1];
			}
			else
			{
				$query = null;
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
					if (AccessControl::CheckCanAccess($list[$key]))
					{
						$solved = FileSystem::ResolveRelativePath($path, $key);
						AccessControl::ExtractQuery($solved, $fileCheck, $queryCheck);
						$paths = FileSystem::GetFolderFiles($fileCheck, null);
						$count = String_Utility::CountCharacters($fileCheck, '*');
						if ($count == 0)
						{
							if (FileSystem::UriExists($solved))
							{
								$result[$key] = $list[$key];
								$result[$key]['path'] = $solved;
							}
						}
						else
						{
							$result[$key] = $list[$key];
							$result[$key]['path'] = FileSystem::GetFolderFiles($solved, null);
						}
					}
				}
			}
			return $result;
		}

		private static function Match(/*string*/ $pattern, /*string*/ $item)
		{
			if ($item !== null)
			{
				$regexPattern = '@^'.str_replace(array('\*'), array('.*'), preg_quote($pattern)).'$@u';
				return preg_match($regexPattern, $item);
			}
			else
			{
				return 1;
			}
		}

		private static function TryGetListedData(/*string*/ $file, /*string*/ $query, /*array*/ $list, /*string*/ $path, /*mixed*/ &$result)
		{
			$bestCount = -1;
			$bestKey = null;
			if (is_array($list))
			{
				$keys = array_keys($list);
				foreach($keys as $fullCheck)
				{
					$solved = FileSystem::ResolveRelativePath($path, $fullCheck);
					AccessControl::ExtractQuery($solved, $fileCheck, $queryCheck);
					$paths = FileSystem::GetFolderFiles($fileCheck, null);
					$count = String_Utility::CountCharacters($fileCheck, '*');
					if ($query !== null)
					{
						$count += String_Utility::CountCharacters($queryCheck, '*');
					}
					foreach ($paths as $option)
					{
						if ($option === $file)
						{
							if (AccessControl::Match($queryCheck, $query) === 1)
							{
								if ($bestKey === null || $bestCount === -1 || $count < $bestCount)
								{
									$bestKey = $fullCheck;
									$bestCount = $count;
								}
							}
							else
							{
								if ($bestKey === null || $bestCount === -1)
								{
									$bestKey = $fullCheck;
									$bestCount = -1;
								}
							}
						}
						if ($bestCount === 0)
						{
							break;
						}
					}
				}
			}
			if ($bestKey === null)
			{
				return false;
			}
			else
			{
				$result = $list[$bestKey];
				if (is_bool($result))
				{
					$result = array('allow' => $result);
				}
				$result['path'] = $file;
				if ($query !== null)
				{
					$result['path'] .= '?'.$query;
				}
				return true;
			}
		}

		private static function Preserve()
		{
			if (class_exists('Session'))
			{
				Session::Start(true);
				$current = AccessControl::$current;
				if ($current === null)
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
			if (AccessControl::$rules === null)
			{
				return array();
			}
			else
			{
				if (array_key_exists($categoryName, AccessControl::$rules))
				{
					$category = AccessControl::$rules[$categoryName];
					if (is_array($category))
					{
						return $category;
					}
					else
					{
						return array();
					}
				}
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

		public static function Configure(/*string*/ $table, /*string*/ $idField, /*string*/ $passwordField, /*string*/ $saltField, /*string*/ $roleField, /*string*/ $hashAlgorithm, /*array*/ $rules)
		{
			AccessControl::$table = $table;
			AccessControl::$idField = $idField;
			AccessControl::$passwordField = $passwordField;
			AccessControl::$saltField = $saltField;
			AccessControl::$roleField = $roleField;
			AccessControl::$hashAlgorithm = $hashAlgorithm;
			AccessControl::$rules = $rules;
			if (AccessControl::$rules === null)
			{
				AccessControl::$rules = array();
			}
		}

		public static function CurrentSession()
		{
			return AccessControl::$current;
		}
		
		public static function Hash($data, $salt)
		{
			if (AccessControl::$hashAlgorithm === null || AccessControl::$hashAlgorithm === 'none')
			{
				return $salt.$data;
			}
			else if (AccessControl::$hashAlgorithm == 'md5')
			{
				return md5($salt.$data);
			}
			else if (AccessControl::$hashAlgorithm == 'sha1')
			{
				return sha1($salt.$data);
			}
			else
			{
				return hash(AccessControl::$hashAlgorithm, $salt.$data);
			}
		}

		public static function Fallback()
		{
			$current = AccessControl::$current;
			$path = FileSystem::FolderInstallation();
			$test = AccessControl::ReadCategory('__fallback');
			foreach($test as $full)
			{
				$solved = FileSystem::ResolveRelativePath($path, $full);
				AccessControl::ExtractQuery($solved, $file, $query);
				if (AccessControl::CanAccess($file, $query))
				{
					$result = '/'.FileSystem::CreateRelativePath(FileSystem::DocumentRoot(), $file, '/');
					if ($query !== null)
					{
						$result .= '?'.$query;
					}
					return $result;
				}
			}
			return false;
		}

		public static function TryGetData(/*string*/ $file, /*string*/ $query, /*mixed*/ &$result)
		{
			if (FileSystem::UriExists($file))
			{
				$current = AccessControl::$current;
				$path = FileSystem::FolderInstallation();
				$test = AccessControl::ReadCategory('__all');
				if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
				{
					return true;
				}
				if (AccessControl::$table === null || AccessControl::$passwordField === null || AccessControl::$idField === null)
				{
					$test = AccessControl::ReadCategory('__unconfigured');
					if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
					{
						return true;
					}
					else
					{
						$test = AccessControl::ReadCategory('__anonymous');
						if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
						{
							return true;
						}
					}
				}
				else
				{
					$test = AccessControl::ReadCategory('__configured');
					if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
					{
						return true;
					}
					if ($current === null)
					{
						$test = AccessControl::ReadCategory('__anonymous');
						if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
						{
							return true;
						}
						else
						{
							$test = AccessControl::ReadCategory('__unauthenticated');
							if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
							{
								return true;
							}
						}
					}
					else
					{
						$test = AccessControl::ReadCategory('__authenticated');
						if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
						{
							return true;
						}
						if ($current['role'] !== null)
						{
							$test = AccessControl::ReadCategory('role:'.$current['role']);
							if (AccessControl::TryGetListedData($file, $query, $test, $path, $result))
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

		public static function Open(/*string*/ $id, /*string*/ $password)
		{
			if (AccessControl::$table !== null && AccessControl::$passwordField !== null && AccessControl::$idField !== null)
			{
				$fields = array(AccessControl::$passwordField);
				if (AccessControl::$roleField !== null)
				{
					$fields[] = AccessControl::$roleField;
				}
				if (AccessControl::$saltField !== null)
				{
					$fields[] = AccessControl::$saltField;
				}
				if (Database::CanConnect() && Database::TryReadOneRecord
				(
					$record,
					AccessControl::$table,
					$fields,
					array(AccessControl::$idField => $id)
				))
				{
					if (AccessControl::$saltField === null)
					{
						$salt = '';
					}
					else
					{
						$salt = $record[AccessControl::$saltField];
					}
					$pass = AccessControl::Hash($password, $salt);
					if ($pass == $record[AccessControl::$passwordField])
					{
						if (AccessControl::$roleField === null)
						{
							AccessControl::$current = array('id' => $id, 'password' => $password, 'role' => null);
						}
						else
						{
							AccessControl::$current = array('id' => $id, 'password' => $password, 'role' => $record[AccessControl::$roleField]);
						}
						AccessControl::Preserve();
						return true;
					}
				}
			}
			AccessControl::$current = null;
			AccessControl::Preserve();
			return false;
		}

		public static function UserInfo()
		{
			$current = AccessControl::$current;
			if ($current === null)
			{
				return null;
			}
			else
			{
				return array('id' => $current['id'], 'role' => $current['role']);
			}
		}

		public static function ValidUris()
		{
			$current = AccessControl::$current;
			$path = FileSystem::FolderInstallation();
			if ($current === null)
			{
				if (AccessControl::$table === null || AccessControl::$passwordField === null || AccessControl::$idField === null)
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__anonymous'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__unconfigured'), $path)
					);
				}
				else
				{
					return array_merge
					(
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__all'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__anonymous'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__configured'), $path),
						AccessControl::GetListedFiles(AccessControl::ReadCategory('__unauthenticated'), $path)
					);
				}
			}
			else
			{
				if ($current['role'] === null)
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
						AccessControl::GetListedFiles(AccessControl::ReadCategory('role:'.$current['role']), $path)
					);
				}
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

	Configuration::Callback
	(
		'paladio-accesscontrol',
		create_function
		(
			'',
			'
				AccessControl::Configure
				(
					Configuration::Get(\'paladio-accesscontrol\', \'table\'),
					Configuration::Get(\'paladio-accesscontrol\', \'id_field\'),
					Configuration::Get(\'paladio-accesscontrol\', \'password_field\'),
					Configuration::Get(\'paladio-accesscontrol\', \'salt_field\'),
					Configuration::Get(\'paladio-accesscontrol\', \'role_field\'),
					Configuration::Get(\'paladio-accesscontrol\', \'hash_algorithm\'),
					Configuration::Get(\'paladio-accesscontrol\', \'rules\')
				);
				Paladio::Request
				(
					\'Session\',
					create_function
					(
						\'\',
						\'
							Session::Start();
							if (Session::isset_Status(\\\'user_id\\\') && Session::isset_Status(\\\'user_password\\\'))
							{
								$id = Session::get_Status(\\\'user_id\\\');
								$password = Session::get_Status(\\\'user_password\\\');
								AccessControl::Open($id, $password);
							}
							if (!AccessControl::CanAccess(FileSystem::ScriptPath(), $_SERVER[\\\'QUERY_STRING\\\']))
							{
								$fallback = AccessControl::Fallback();
								if ($fallback === false)
								{
									header(\\\'HTTP/1.0 403 Forbidden\\\');
								}
								else
								{
									header(\\\'Location: \\\'.$fallback, true, 307);
								}
								exit();
							}
						\'
					)
				);
			'
		)
	);
?>