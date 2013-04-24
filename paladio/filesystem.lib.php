<?php
	/* filesystem.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

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

		private static function GetFolderItemsRelative(/*mixed*/ $files, /*string*/ $path, /*bool*/ $folders)
		{
			$result = array();
			if (is_array($files))
			{
				foreach($files as $file)
				{
					$_file = FileSystem::ResolveRelativePath($path, $file);
					if (is_file($_file))
					{
						$result[] = $_file;
					}
					else
					{
						$result = array_merge($result, FileSystem::GetFolderItemsRelative($file, $path, $folders));
					}
				}
			}
			else if (is_string($files))
			{
				//ONLY UTF-8
				$files = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $files);
				$position = mb_strpos($files, DIRECTORY_SEPARATOR);
				if ($position > 0)
				{
					$folder = mb_substr($files, 0, $position + 1);
					$pattern = mb_substr($files, $position + 1);
				}
				else
				{
					//ONLY UTF-8
					if (preg_match('#^[A-Za-z0-9 ._,]*$#u', $files) == 0)
					{
						$folder = '.'.DIRECTORY_SEPARATOR;
						$pattern = $files;
					}
					else
					{
						if (is_dir($files))
						{
							$folder = $files;
							$pattern = '*';
						}
						else
						{
							$folder = '.'.DIRECTORY_SEPARATOR;
							$pattern = $files;
						}
					}
				}
				$folder = FileSystem::ResolveRelativePath($path, $folder);
				if (is_dir($folder) && ($handle = opendir($folder)) !== false)
				{
					$regexPattern = '@^'.str_replace(array('\*', '\?'), array('.*', '.'), preg_quote($pattern)).'$@u';
					while (($file = readdir($handle)) !== false)
					{
						if (!FileSystem::StartsWith($file, '.'))
						{
							$file = $folder.$file;
							$isDir = is_dir($file);
							if (is_null($folders) || ($folders && $isDir) || (!$folders && !$isDir))
							{
								//ONLY UTF-8
								if (preg_match($regexPattern, $file))
								{
									$result[] = $file;
								}
							}
						}
					}
					closedir($handle);
				}
			}
			return $result;
		}

		private static function EndsWith (/*string*/ $string, /*string*/ $with)
		{
			if (mb_substr($string, mb_strlen($string) - mb_strlen($with)) == $with)
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
			if (mb_substr($string, 0, mb_strlen($with)) == $with)
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

		public static function DocumentRoot()
		{
			//ONLY UTF-8
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']);
		}

		public static function FolderCore()
		{
			return FileSystem::PreparePath(dirname(__FILE__));
		}

		public static function FolderInstallation()
		{
			$folderCore = FileSystem::FolderCore();
			$currentFolder = getcwd(); chdir($folderCore);
			{
				$result = FileSystem::PreparePath(realpath('..'));
			}chdir($currentFolder);
			return $result;
		}

		public static function GetFolderFiles(/*mixed*/ $files, /*string*/ $path = null)
		{
			if (func_num_args() == 1)
			{
				$path = FileSystem::FolderInstallation();
			}
			$result = FileSystem::GetFolderItemsRelative($files, $path, false);
			return $result;
		}

		public static function GetFolderItems(/*mixed*/ $files, /*string*/ $path = null)
		{
			if (func_num_args() == 1)
			{
				$path = FileSystem::FolderInstallation();
			}
			$result = FileSystem::GetFolderItemsRelative($files, $path, null);
			return $result;
		}

		public static function GetFolderFolders(/*mixed*/ $files, /*string*/ $path = null)
		{
			if (func_num_args() == 1)
			{
				$path = FileSystem::FolderInstallation();
			}
			$result = FileSystem::GetFolderItemsRelative($files, $path, true);
			return $result;
		}

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

		public static function PreparePath(/*string*/ $path)
		{
			$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
			if (!FileSystem::EndsWith($path, DIRECTORY_SEPARATOR))
			{
				$path .= DIRECTORY_SEPARATOR;
			}
			return $path;
		}

		public static function ProcessAbsolutePath(/*string*/ $absolutePath)
		{
			$absolutePath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $absolutePath);
			if (FileSystem::EndsWith($absolutePath, DIRECTORY_SEPARATOR))
			{
				$absolutePath = mb_substr($absolutePath, 0, mb_strlen($absolutePath) - 1);
			}
			//ONLY UTF-8
			$folders = explode(DIRECTORY_SEPARATOR, $absolutePath);
			return FileSystem::_ProcessPath($folders);
		}

		public static function ProcessRelativePath(/*string*/ $relativePath)
		{
			$relativePath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relativePath);
			if (FileSystem::StartsWith($relativePath, DIRECTORY_SEPARATOR))
			{
				$relativePath = mb_substr($relativePath, 1);
			}
			//ONLY UTF-8
			$folders = explode(DIRECTORY_SEPARATOR, $relativePath);
			return FileSystem::_ProcessPath($folders);
		}

		public static function RequireAll(/*mixed*/ $files, /*string*/ $path = null)
		{
			$__REQUIRES = FileSystem::GetFolderItemsRelative($files, $path, false);
			foreach($__REQUIRES as $__REQUIRE)
			{
				require_once($__REQUIRE);
			}
		}

		public static function ResolveRelativePath(/*string*/ $absolutePath, /*string*/ $relativePath)
		{
			$reference = FileSystem::ProcessAbsolutePath($absolutePath);
			$relative = FileSystem::ProcessRelativePath($relativePath);
			$result = implode(DIRECTORY_SEPARATOR, FileSystem::_ProcessPath(array_merge($reference, $relative)));
			return $result;
		}

		public static function ScriptUri(/*string*/ $from = null)
		{
			if (is_null($from))
			{
				$from = FileSystem::DocumentRoot();
			}
			return DIRECTORY_SEPARATOR.FileSystem::CreateRelativePath($from, FileSystem::ScriptPath());
		}

		public static function ScriptPath()
		{
			//ONLY UTF-8
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
		}


		public static function UriExists(/*string*/ $uri, $path = null)
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