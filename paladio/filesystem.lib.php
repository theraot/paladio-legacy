<?php
	/* filesystem.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) === 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	//TODO: Make _GetFolderItems and _GetFolerItemsRelative return an Iterable instead of an array

	/**
	 * FileSystem
	 * @package FileSystem
	 */
	final class FileSystem
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function _ProcessPath(/*array*/ $folders)
		{
			$result = array();
			$count = 0;
			while (($folder = array_shift($folders)) !== null)
			{
				if ($folder === '.')
				{
					continue;
				}
				if ($folder === '..')
				{
					if ($count > 0)
					{
						array_pop($result);
						$count--;
						continue;
					}
					else
					{
						array_push($result, '..');
						continue;
					}
				}
				array_push($result, $folder);
				$count++;
			}
			return $result;
		}
		
		private static function _CreateRelativePath($absolute, $target, $directory_separator)
		{
			$absoluteLength = count($absolute);
			$targetLength = count($target);
			$result = array();
			$commonLength = min($absoluteLength, $targetLength);
			for ($index = 0; $index < $commonLength; $index++)
			{
				if ($absolute[$index] !== $target[$index])
				{
					break;
				}
			}
			if ($index < $absoluteLength)
			{
				for ($index2 = $index; $index2 < $absoluteLength; $index2++)
				{
					$result[] = '..';
				}
			}
			if ($index < $targetLength)
			{
				for ($index2 = $index; $index2 < $targetLength; $index2++)
				{
					$result[] = $target[$index2];
				}
			}
			if (count($result) === 0)
			{
				$result[] = '.';
			}
			if ($directory_separator === null)
			{
				$directory_separator = DIRECTORY_SEPARATOR;
			}
			return implode($directory_separator, $result);
		}

		private static function _GetFolderItems(/*mixed*/ $pattern, /*string*/ $path, /*bool*/ $folders)
		{
			if (is_array($pattern))
			{
				$result = array();
				foreach($pattern as $pattern)
				{
					$result = array_merge($result, FileSystem::_GetFolderItems($pattern, $path, $folders));
				}
				return $result;
			}
			else
			{
				if (is_string($pattern))
				{
					$pattern = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $pattern);
				}
				else
				{
					$pattern = '*';
				}
				//---
				if (is_string($path))
				{
					$pattern = FileSystem::ResolveRelativePath($path, $pattern, DIRECTORY_SEPARATOR);
				}
				//---
				$result = array();
				if ($folders === true)
				{
					foreach (glob($pattern, GLOB_MARK | GLOB_NOSORT | GLOB_ONLYDIR) as $item)
					{
						$result[] = $item;
					}
				}
				else
				{
					foreach (glob($pattern, GLOB_MARK | GLOB_NOSORT) as $item)
					{
						$isDir = substr($item,-1) === DIRECTORY_SEPARATOR;
						if ($folders === null || !$isDir)
						{
							$result[] = $item;
						}
					}
				}
				return $result;
			}
		}

		public static function _GetFolderItemsRecursive(/*mixed*/ $pattern, /*string*/ $path, $folders)
		{
			if ($folders === true)
			{
				$result = array();
			}
			else
			{
				$result = FileSystem::_GetFolderItems($pattern, $path, false);
			}
			$queue = array($path);
			$branches = null;
			$branches_index = -1;
			$branches_length = -1;
			while (true)
			{
				if ($branches === null)
				{
					if (count($queue) > 0)
					{
						$found = array_shift($queue);
						$branches = FileSystem::_GetFolderItems('*', $found, true);
						$branches_index = -1;
						$branches_length = count($branches);
					}
					else
					{
						break;
					}
				}
				else
				{
					$advanced = false;
					$branches_index++;
					if ($branches_index < $branches_length)
					{
						$advanced = true;
					}
					if ($advanced)
					{
						$found = $branches[$branches_index];
						if ($folders !== false)
						{
							$result[] = $found;
						}
						if ($folders !== true)
						{
							$new = FileSystem::_GetFolderItems($pattern, $found, false);
							$result = array_merge($result, $new);
						}
						$queue[] = $found;
					}
					else
					{
						$branches = null;
						$branches_index = -1;
						$branches_length = -1;
					}
				}
			}
			return $result;
		}

		private static function EndsWith (/*string*/ $string, /*string*/ $with)
		{
			if (substr($string, strlen($string) - strlen($with)) === $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		private static function StartsWith (/*string*/ $string, /*string*/ $with)
		{
			if (substr($string, 0, strlen($with)) === $with)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		private static function UriExistsRelative(/*string*/ $uri)
		{
			if (is_string($uri))
			{
				$tmp = explode('?', $uri);
				$uri = $tmp[0];
				$tmp = explode('#', $uri);
				$uri = $tmp[0];
				$handle = @fopen($uri, 'r');
				if($handle !== false)
				{
					fclose($handle);
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
		
		//------------------------------------------------------------

		private static function __RequireOnce(/*string*/ $_REQUIRE)
		{
			require_once($_REQUIRE);
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Returns a GUID that unique for this installation of Paladio in this machine.
		 *
		 * Returns string that identifies this Paladio installation.
		 *
		 * @access public
		 * @return string
		 */
		public static function AppGUID()
		{
			$path = dirname(__FILE__);
			$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
			$salt = filemtime($path);
			$uname = php_uname('n');
			$md5 = md5($uname.$path.$salt);
			return '{'.substr($md5, 0, 8).'-'.substr($md5, 8, 4).'-'.substr($md5, 12, 4).'-'.substr($md5, 16, 4).'-'.substr($md5, 20).'}';
		}

		/**
		 * Creates the relative path thats needed to go from $absolutePath to $targetPath.
		 *
		 * Note 1: both $absolutePath and $targetPath are expected to be string, no check is performed.
		 * Note 2: On Windows, if $absolutePath and $targetPath are on diferent volumes the resulting path is invalid.
		 *
		 * Returns a path that can be used to go from $absolutePath to $targetPath, the path uses DIRECTORY_SEPARATOR as separator and does not include the ending DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateRelativePath(/*string*/ $absolutePath, /*string*/ $targetPath, /*string*/ $directory_separator = null)
		{
			$absolute = FileSystem::ProcessAbsolutePath($absolutePath);
			$target = FileSystem::ProcessAbsolutePath($targetPath);
			return FileSystem::_CreateRelativePath($absolute, $target, $directory_separator);
		}

		/**
		 * Retrieves the path of the root folder of the web server.
		 *
		 * Returns the path of the root folder of the web server, the path uses DIRECTORY_SEPARATOR as separator and does include the ending DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function DocumentRoot()
		{
			return FileSystem::PreparePath($_SERVER['DOCUMENT_ROOT']);
		}

		/**
		 * Retrieves the path of the folder in which this file is stored.
		 *
		 * Returns the path of the folder in which this file is stored, the path uses DIRECTORY_SEPARATOR as separator and does include the ending DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function FolderCore()
		{
			return FileSystem::PreparePath(dirname(__FILE__));
		}

		/**
		 * Retrieves the path of one directory level above the folder in which this file is stored.
		 *
		 * Returns the path of one directory level above the folder in which this file is stored, the path uses DIRECTORY_SEPARATOR as separator and does include the ending DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function FolderInstallation()
		{
			$folderCore = FileSystem::FolderCore();
			$currentFolder = getcwd(); chdir($folderCore);
			{
				$result = FileSystem::PreparePath(realpath('..'));
			}chdir($currentFolder);
			return $result;
		}

		/**
		 * Retrieves the files that match the pattern in $pattern and are in the folder $path.
		 *
		 * If $pattern is string: it will be interpreted as a relative path followed by a Windows file search pattern.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 * Otherwise, it will be interpretated as the Windows file search pattern "*".
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the files.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderFiles(/*mixed*/ $pattern, /*string*/ $path)
		{
			return FileSystem::_GetFolderItems($pattern, $path, false);
		}

		/**
		 * Recursively retrieves the files that match the pattern in $pattern and are in the folder $path.
		 *
		 * If $pattern is string: it will be interpreted as a relative path followed by a Windows file search pattern.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 * Otherwise, it will be interpretated as the Windows file search pattern "*".
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the files.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderFilesRecursive(/*mixed*/ $pattern, /*string*/ $path)
		{
			return FileSystem::_GetFolderItemsRecursive($pattern, $path, false);
		}

		/**
		 * Retrieves the files and folders that match the pattern in $pattern and are in the folder $path.
		 *
		 * If $pattern is string: it will be interpreted as a relative path followed by a Windows file search pattern.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 * Otherwise, it will be interpretated as the Windows file search pattern "*".
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the files and folders.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderItems(/*mixed*/ $pattern, /*string*/ $path)
		{
			return FileSystem::_GetFolderItems($pattern, $path, null);
		}

		/**
		 * Recursively retrieves the files and folders that match the pattern in $pattern and are in the folder $path.
		 *
		 * If $pattern is string: it will be interpreted as a relative path followed by a Windows file search pattern.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 * Otherwise, it will be interpretated as the Windows file search pattern "*".
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the files and folders.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderItemsRecursive(/*mixed*/ $pattern, /*string*/ $path)
		{
			return FileSystem::_GetFolderItemsRecursive($pattern, $path, null);
		}

		/**
		 * Retrieves the folders that match the pattern in $pattern and are in the folder $path.
		 *
		 * Returns an array that contains the absolute path of the folders.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderFolders(/*string*/ $path)
		{
			return FileSystem::_GetFolderItems('*', $path, true);
		}

		/**
		 * Recursively retrieves the folders that match the pattern in $pattern and are in the folder $path.
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the folders.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderFoldersRecursive(/*string*/ $path)
		{
			return FileSystem::_GetFolderItemsRecursive('*', $path, true);
		}

		/**
		 * Retrieves the file where the most recent include happened.
		 *
		 * Returns the absolute path of the file where "include", "include_once", "require" or "require_once" was called if any, false otherwise.
		 *
		 * @access public
		 * @return string or false
		 */
		public static function GetIncludingFile()
		{
			$file = false;
			$backtrace =  debug_backtrace();
			$include_functions = array('include', 'include_once', 'require', 'require_once');
			for ($index = 0; $index < count($backtrace); $index++)
			{
				$function = $backtrace[$index]['function'];
				if (in_array($function, $include_functions))
				{
					$file = $backtrace[$index]['file'];
					break;
				}
			}
			return $file;
		}

		/**
		 * Retrieves the file that was included with the most recent include happened.
		 *
		 * Returns the absolute path of the file that was included where "include", "include_once", "require" or "require_once" was called if any, false otherwise.
		 *
		 * @access public
		 * @return string or false
		 */
		public static function GetIncludedFile()
		{
			$file = false;
			$backtrace =  debug_backtrace();
			$include_functions = array('include', 'include_once', 'require', 'require_once');
			for ($index = 0; $index < count($backtrace); $index++)
			{
				$function = $backtrace[$index]['function'];
				if (in_array($function, $include_functions))
				{
					$file = $backtrace[$index - 1]['file'];
					break;
				}
			}
			return $file;
		}

		/**
		 * Verifies if the item matches the given windows file search pattern.
		 *
		 * Returns true if the item matches the windows file search pattern, false otherwise.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 *
		 * @param $pattern: the windows file search pattern.
		 * @param $item: the item to verify.
		 *
		 * @access public
		 * @return bool
		 */
		public static function Match(/*string*/ $pattern, /*string*/ $item)
		{
			$regexPattern = '@^'.str_replace(array('\*', '\?'), array('.*', '.'), preg_quote($pattern)).'$@u';
			return preg_match($regexPattern, $item);
		}

		/**
		 * Converts any "/" or "\" to DIRECTORY_SEPARATOR and appends DIRECTORY_SEPARATOR at the end if not present.
		 *
		 * Note: $path is expected to be string, no check is performed.
		 *
		 * Returns the string $path with both "/" and "\" converted to DIRECTORY_SEPARATOR and DIRECTORY_SEPARATOR and the end.
		 *
		 * @access public
		 * @return string
		 */
		public static function PreparePath(/*string*/ $path)
		{
			$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
			if (!FileSystem::EndsWith($path, DIRECTORY_SEPARATOR))
			{
				$path .= DIRECTORY_SEPARATOR;
			}
			return $path;
		}

		/**
		 * Extracts the components of the absolute path $absolutePath.
		 *
		 * Note: $absolutePath is expected to be string, no check is performed.
		 *
		 * Returns an array that containts each component of the absolute path $absolutePath.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function ProcessAbsolutePath(/*string*/ $absolutePath)
		{
			$absolutePath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $absolutePath);
			$separatorLen = strlen(DIRECTORY_SEPARATOR);
			if (FileSystem::EndsWith($absolutePath, DIRECTORY_SEPARATOR))
			{
				$absolutePath = substr($absolutePath, 0, strlen($absolutePath) - $separatorLen);
			}
			$folders = explode(DIRECTORY_SEPARATOR, $absolutePath);
			return FileSystem::_ProcessPath($folders);
		}

		/**
		 * Extracts the components of the relative path $relativePath.
		 *
		 * Note: $relativePath is expected to be string, no check is performed.
		 *
		 * Returns an array that containts each component of the relative path $relativePath.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function ProcessRelativePath(/*string*/ $relativePath)
		{
			$relativePath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relativePath);
			$separatorLen = strlen(DIRECTORY_SEPARATOR);
			if (FileSystem::StartsWith($relativePath, DIRECTORY_SEPARATOR))
			{
				$relativePath = substr($relativePath, $separatorLen);
			}
			if ($relativePath === '')
			{
				return array('.');
			}
			else
			{
				$folders = explode(DIRECTORY_SEPARATOR, $relativePath);
				return FileSystem::_ProcessPath($folders);
			}
		}

		/**
		 * Includes using require_once the files as given by the return value of FileSystem::GetFolderFiles($pattern, $path).
		 *
		 * Note: the included files will have a variable $_REQUIRE with their absolute path
		 *
		 * @access public
		 * @return void
		 */
		public static function RequireAll(/*mixed*/ $pattern, /*string*/ $path, $recursive = false)
		{
			if ($recursive)
			{
				$__REQUIRE = FileSystem::_GetFolderItemsRecursive($pattern, $path, false);
			}
			else
			{
				$__REQUIRE = FileSystem::_GetFolderItems($pattern, $path, false);
			}
			foreach ($__REQUIRE as $_REQUIRE)
			{
				FileSystem::__RequireOnce($_REQUIRE);
			}
		}

		/**
		 * Creates the path resulting from following $relativePath starting in $absolutePath.
		 *
		 * Note: both $absolutePath and $relativePath are expected to be string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ResolveRelativePath(/*string*/ $absolutePath, /*string*/ $relativePath, /*string*/ $directory_separator = null)
		{
			$absolute = FileSystem::ProcessAbsolutePath($absolutePath);
			$relative = FileSystem::ProcessRelativePath($relativePath);
			if ($directory_separator === null)
			{
				$directory_separator = DIRECTORY_SEPARATOR;
			}
			$result = implode($directory_separator, FileSystem::_ProcessPath(array_merge($absolute, $relative)));
			return $result;
		}

		/**
		 * Creates the relative path thats needed to go from $newAbsolutePath to the location of following $relativePath starting in $oldAbsolutePath
		 *
		 * Note: $oldAbsolutePath, $newAbsolutePath and $relativePath are expected to be string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function RebaseRelativePath(/*string*/ $newAbsolutePath, /*string*/ $oldAbsolutePath, /*string*/ $relativePath, /*string*/ $directory_separator = null)
		{
			$newAbsolute = FileSystem::ProcessAbsolutePath($newAbsolutePath);
			$oldAbsolute = FileSystem::ProcessAbsolutePath($oldAbsolutePath);
			$relative = FileSystem::ProcessRelativePath($relativePath);
			$target = FileSystem::_ProcessPath(array_merge($oldAbsolute, $relative));
			$result = FileSystem::_CreateRelativePath($newAbsolute, $target, $directory_separator);
			return $result;
		}

		/**
		 * Retrieves the absolute path of the requested script.
		 *
		 * Returns the value of $_SERVER['SCRIPT_FILENAME'] with any "/" or "\" replaced to DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function ScriptPath()
		{
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
		}

		/**
		 * Creates the relative path needed to go from $absolutePath to the absolute path of the requested script as given by FileSystem::ScriptPath().
		 *
		 * If $absolutePath is null: Creates the relative path needed to go from FileSystem::DocumentRoot() to FileSystem::ScriptPath().
		 * Otehrwise: Assumes $absolutePath is string and creates the relative path needed to go from $absolutePath to FileSystem::ScriptPath().
		 *
		 * Note: $absolutePath is expected to be null or string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ScriptPathRelative(/*string*/ $absolutePath = null, $directory_separator = '/')
		{
			if ($absolutePath === null)
			{
				$absolutePath = FileSystem::DocumentRoot();
			}
			return $directory_separator.FileSystem::CreateRelativePath($absolutePath, FileSystem::ScriptPath(), $directory_separator);
		}

		/**
		 * Verifies if a given URI $uri can be accesed.
		 *
		 * 1: If $path is missing $path is set to FileSystem::FolderInstallation().
		 * 2: If $path is a folder changes the current directory to $path
		 * 3: Takes the part of $uri before any "?" or "#" and attempt to read it.
		 * 4: Set the current directory to what it was before step 2
		 * 5: Returns whatever or not the step 3 was succeful
		 *
		 * Note: $uri is expected to be null or string, no check is performed.
		 *
		 * @access public
		 * @return bool
		 */
		public static function UriExists(/*string*/ $uri, /*string*/ $path = null)
		{
			if (func_num_args() === 1)
			{
				$path = FileSystem::FolderInstallation();
			}
			$currentFolder = getcwd();
			if (is_dir($path))
			{
				chdir($path);
			}
			$result = FileSystem::UriExistsRelative($uri);
			chdir($currentFolder);
			return $result;
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>