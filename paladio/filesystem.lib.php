<?php
	/* filesystem.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	//TODO: Make get GetFolderItemsRelative return an Iterable instead of an array

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
			while (($folder = array_shift($folders)) !== null)
			{
				if ($folder === '.')
				{
					continue;
				}
				if ($folder === '..')
				{
					array_pop($result);
					continue;
				}
				array_push($result, $folder);
			}
			return $result;
		}

		private static function _RequireOnce(/*string*/ $_REQUIRE)
		{
			require_once($_REQUIRE);
		}

		private static function GetFolderItemsRelative(/*mixed*/ $pattern, /*string*/ $path, /*bool*/ $folders)
		{
			if (is_array($pattern))
			{
				$result = array();
				foreach($pattern as $pattern)
				{
					$result = array_merge($result, FileSystem::GetFolderItemsRelative($pattern, $path, $folders));
				}
				return $result;
			}
			else if (is_string($pattern))
			{
				if (is_string($pattern))
				{
					//ONLY UTF-8
					$pattern = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $pattern);
					$_file = FileSystem::ResolveRelativePath($path, $pattern);
					if (is_file($_file))
					{
						return array($_file);
					}
					else
					{
						$separatorLen = strlen(DIRECTORY_SEPARATOR);
						$position = strpos($pattern, DIRECTORY_SEPARATOR);
						if ($position > 0)
						{
							$folder = substr($pattern, 0, $position + $separatorLen);
							$pattern = substr($pattern, $position + $separatorLen);
						}
						else
						{
							$folder = '.'.DIRECTORY_SEPARATOR;
						}
					}
				}
				else
				{
					$pattern = '*';
				}
				$result = array();
				//ONLY UTF-8
				$folder = FileSystem::ResolveRelativePath($path, $folder);
				if (is_dir($folder) && ($handle = opendir($folder)) !== false)
				{
					$regexPattern = '@^'.str_replace(array('\*', '\?'), array('.*', '.'), preg_quote($pattern)).'$@u';
					while (($item = readdir($handle)) !== false)
					{
						if (!FileSystem::StartsWith($item, '.'))
						{
							$item = $folder.$item;
							$isDir = is_dir($item);
							if (is_null($folders) || ($folders && $isDir) || (!$folders && !$isDir))
							{
								//ONLY UTF-8
								if (preg_match($regexPattern, $item))
								{
									$result[] = $item;
								}
							}
						}
					}
					closedir($handle);
				}
				return $result;
			}
		}

		private static function EndsWith (/*string*/ $string, /*string*/ $with)
		{
			if (substr($string, strlen($string) - strlen($with)) == $with)
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
			if (substr($string, 0, strlen($with)) == $with)
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
			$path = FileSystem::FolderInstallation();
			$salt = "RjfWpx6R";
			$uname = php_uname('n');
			$md5 = md5($uname.$path.$salt);
			return '{'.substr($md5, 0, 8).'-'.substr($md5, 8, 4).'-'.substr($md5, 12, 4).'-'.substr($md5, 16, 4).'-'.substr($md5, 20).'}';
		}

		/**
		 * Creates the relative path thats needed to go from $referencePath to $absolutePath.
		 *
		 * Note 1: both $referencePath and $absolutePath are expected to be string, no check is performed.
		 * Note 2: On Windows, if $referencePath and $absolutePath are on diferent volumes the resulting path is invalid.
		 *
		 * Returns a path that can be used to go from $referencePath to $absolutePath, the path uses DIRECTORY_SEPARATOR as separator and does not include the ending DIRECTORY_SEPARATOR.
		 *
		 * @access public
		 * @return string
		 */
		public static function CreateRelativePath(/*string*/ $referencePath, /*string*/ $absolutePath)
		{
			$reference = FileSystem::ProcessAbsolutePath($referencePath);
			$absolute = FileSystem::ProcessAbsolutePath($absolutePath);
			$referenceLength = count($reference);
			$absoluteLength = count($absolute);
			$result = array();
			$commonLength = min($referenceLength, $absoluteLength);
			for ($index = 0; $index < $commonLength; $index++)
			{
				if ($reference[$index] != $absolute[$index])
				{
					break;
				}
			}
			if ($index < $referenceLength)
			{
				for ($index2 = $index; $index2 < $referenceLength; $index2++)
				{
					$result[] = '..';
				}
			}
			if ($index < $absoluteLength)
			{
				for ($index2 = $index; $index2 < $absoluteLength; $index2++)
				{
					$result[] = $absolute[$index2];
				}
			}
			if (count($result) === 0)
			{
				$result[] = '.';
			}
			//ONLY UTF-8
			return implode(DIRECTORY_SEPARATOR, $result);
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
			//ONLY UTF-8
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
			return FileSystem::GetFolderItemsRelative($pattern, $path, false);
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
			return FileSystem::GetFolderItemsRelative($pattern, $path, null);
		}

		/**
		 * Retrieves the folders that match the pattern in $pattern and are in the folder $path.
		 *
		 * If $pattern is string: it will be interpreted as a relative path followed by a Windows file search pattern.
		 * The Windows file search pattern uses:
		 * "?" : any character
		 * "*" : any character, zero or more times
		 * Otherwise, it will be interpretated as the Windows file search pattern "*".
		 *
		 * Note: files which name starts with "." are ignored.
		 *
		 * Returns an array that contains the absolute path of the folders.
		 *
		 * @access public
		 * @return array of string
		 */
		public static function GetFolderFolders(/*mixed*/ $pattern, /*string*/ $path)
		{
			return FileSystem::GetFolderItemsRelative($pattern, $path, true);
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
			//ONLY UTF-8
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
			//ONLY UTF-8
			$folders = explode(DIRECTORY_SEPARATOR, $relativePath);
			return FileSystem::_ProcessPath($folders);
		}

		/**
		 * Includes using require_once the files as given by the return value of FileSystem::GetFolderFiles($pattern, $path).
		 *
		 * Note: the included files will have a variable $_REQUIRE with their absolute path
		 *
		 * @access public
		 * @return void
		 */
		public static function RequireAll(/*mixed*/ $pattern, /*string*/ $path)
		{
			array_map('FileSystem::_RequireOnce', FileSystem::GetFolderItemsRelative($pattern, $path, false));
		}

		/**
		 * Creates the path resulting from following $relativePath starting in $absolutePath.
		 *
		 * Note: both $absolutePath and $relativePath are expected to be string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ResolveRelativePath(/*string*/ $absolutePath, /*string*/ $relativePath)
		{
			$reference = FileSystem::ProcessAbsolutePath($absolutePath);
			$relative = FileSystem::ProcessRelativePath($relativePath);
			$result = implode(DIRECTORY_SEPARATOR, FileSystem::_ProcessPath(array_merge($reference, $relative)));
			return $result;
		}

		/**
		 * Creates the relative path needed to go from $referencePath to the absolute path of the requested script as given by FileSystem::ScriptPath().
		 *
		 * If $referencePath is null: Creates the relative path needed to go from FileSystem::DocumentRoot() to FileSystem::ScriptPath().
		 * Otehrwise: Assumes $referencePath is string and creates the relative path needed to go from $referencePath to FileSystem::ScriptPath().
		 *
		 * Note: $referencePath is expected to be null or string, no check is performed.
		 *
		 * @access public
		 * @return string
		 */
		public static function ScriptUri(/*string*/ $referencePath = null)
		{
			if (is_null($referencePath))
			{
				$referencePath = FileSystem::DocumentRoot();
			}
			return DIRECTORY_SEPARATOR.FileSystem::CreateRelativePath($referencePath, FileSystem::ScriptPath());
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
			//ONLY UTF-8 $_SERVER['SCRIPT_FILENAME']
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
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
			if (func_num_args() == 1)
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