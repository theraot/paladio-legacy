<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
	}

	final class PaladioTheme
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static $currentTheme;
		private static $themesFolder;
		private static $scripts;
		private static $styleSheets;

		private static function BuildUri(/*string*/ $path, /*mixed*/ $arguments = null)
		{
			if (FileSystem::UriExists($path))
			{
				if (is_null($arguments))
				{
					return $path;
				}
				else if (is_array($arguments))
				{
					$keys = array_keys($arguments);
					$result = array();
					foreach ($keys as $key)
					{
						$result[] = $key.'='.$arguments[$key];
					}
					return $path.'?'.implode('&', $result);
				}
				else
				{
					return $path.(string)$arguments;
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

		public static function Configure(/*string*/ $currentTheme, /*string*/ $themesFolder)
		{
			PaladioTheme::$currentTheme = $currentTheme;
			PaladioTheme::$themesFolder = FileSystem::PreparePath($themesFolder);
		}

		public static function EmitImage(/*string*/ $path, /*mixed*/ $arguments = null, /*mixed*/ $attributes = null)
		{
			$uri = PaladioTheme::GetUri($path, $arguments);
			if ($uri === false)
			{
				return false;
			}
			else
			{
				$entry = array('uri' => $uri, 'attributes' => $attributes);
				echo '<img src="'.$entry['uri'].'"';
				if (is_array($entry['attributes']))
				{
					$attributes = array_keys($entry['attributes']);
					$pieces = array();
					foreach ($attributes as $attribute)
					{
						$pieces[] = $attribute.'="'.$entry['attributes'][$attribute].'"';
					}
					echo ' '.implode(' ', $pieces);
				}
				else if (is_string($entry['attributes']))
				{
					echo ' '.$entry['attributes'];
				}
				echo '/>';
				return true;
			}
		}

		public static function EmitScripts()
		{
			if (is_array(PaladioTheme::$scripts))
			{
				foreach (PaladioTheme::$scripts as $entry)
				{
					echo '<script src="'.$entry['uri'].'"';
					if (is_array($entry['attributes']))
					{
						$attributes = array_keys($entry['attributes']);
						$pieces = array();
						foreach ($attributes as $attribute)
						{
							$pieces[] = $attribute.'="'.$entry['attributes'][$attribute].'"';
						}
						echo ' '.implode(' ', $pieces);
					}
					else if (is_string($entry['attributes']))
					{
						echo ' '.$entry['attributes'];
					}
					echo '></script>';
				}
			}
		}

		public static function EmitStyleSheets()
		{
			if (is_array(PaladioTheme::$styleSheets))
			{
				foreach (PaladioTheme::$styleSheets as $entry)
				{
					echo '<link rel="stylesheet" href="'.$entry['uri'].'"';
					if (is_array($entry['attributes']))
					{
						$attributes = array_keys($entry['attributes']);
						$pieces = array();
						foreach ($attributes as $attribute)
						{
							$pieces[] = $attribute.'="'.$entry['attributes'][$attribute].'"';
						}
						echo ' '.implode(' ', $pieces);
					}
					else if (is_string($entry['attributes']))
					{
						echo ' '.$entry['attributes'];
					}
					echo '/>';
				}
			}
		}

		public static function GetUri(/*string*/ $path, /*mixed*/ $arguments = null)
		{
			$resolvedPath = FileSystem::ResolveRelativePath(PaladioTheme::$themesFolder.PaladioTheme::$currentTheme, $path);
			$result = PaladioTheme::BuildUri($resolvedPath, $arguments);
			if (PaladioTheme::$currentTheme != 'default')
			{
				if ($result === false)
				{
					$resolvedPath = FileSystem::ResolveRelativePath(PaladioTheme::$themesFolder.'default', $path);
					$result = PaladioTheme::BuildUri($resolvedPath, $arguments);
				}
			}
			if ($result === false)
			{
				$result = PaladioTheme::BuildUri($path, $arguments);
			}
			if ($result === false)
			{
				return false;
			}
			else
			{
				return DIRECTORY_SEPARATOR.FileSystem::CreateRelativePath(FileSystem::DocumentRoot(), $result);
			}
		}

		public static function RequestScript(/*string*/ $path, /*mixed*/ $arguments = null, /*mixed*/ $attributes = null)
		{
			if (is_array($path))
			{
				foreach($path as $item)
				{
					PaladioTheme::RequestScript($item, $arguments, $attributes);
				}
				return true;
			}
			else
			{
				$uri = PaladioTheme::GetUri($path, $arguments);
				if ($uri === false)
				{
					return false;
				}
				else
				{
					$entry = array('uri' => $uri, 'attributes' => $attributes);
					if (is_array(PaladioTheme::$scripts))
					{
						PaladioTheme::$scripts[] = $entry;
					}
					else
					{
						PaladioTheme::$scripts = array($entry);
					}
					return true;
				}
			}
		}

		public static function RequestStyleSheet(/*string*/ $path, /*mixed*/ $arguments = null, /*mixed*/ $attributes = null)
		{
			if (is_array($path))
			{
				foreach($path as $item)
				{
					PaladioTheme::RequestStyleSheet($item, $arguments, $attributes);
				}
				return true;
			}
			else
			{
				$uri = PaladioTheme::GetUri($path, $arguments);
				if ($uri === false)
				{
					return false;
				}
				else
				{
					$entry = array('uri' => $uri, 'attributes' => $attributes);
					if (is_array(PaladioTheme::$styleSheets))
					{
						PaladioTheme::$styleSheets[] = $entry;
					}
					else
					{
						PaladioTheme::$styleSheets = array($entry);
					}
					return true;
				}
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

	require_once('configuration.lib.php');
	PaladioTheme::Configure('default', FileSystem::FolderCore().'themes');
	$currentTheme = 'default';
	$themesFolder = 'themes';
	function Theme_Configure()
	{
		PaladioTheme::Configure
		(
			Configuration::Get('paladio-theme', 'current', 'default'),
			FileSystem::FolderCore().Configuration::Get('paladio-paths', 'themes', 'themes')
		);
	}
	Configuration::Callback('paladio-paths', 'Theme_Configure');
	Configuration::Callback('paladio-theme', 'Theme_Configure');
?>