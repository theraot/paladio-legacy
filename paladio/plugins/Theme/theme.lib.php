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

	final class Theme
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
			Theme::$currentTheme = $currentTheme;
			Theme::$themesFolder = FileSystem::PreparePath($themesFolder);
		}

		public static function EmitImage(/*string*/ $path, /*mixed*/ $arguments = null, /*mixed*/ $attributes = null)
		{
			$uri = Theme::GetUri($path, $arguments);
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
			if (is_array(Theme::$scripts))
			{
				foreach (Theme::$scripts as $entry)
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
			if (is_array(Theme::$styleSheets))
			{
				foreach (Theme::$styleSheets as $entry)
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
			$resolvedPath = FileSystem::ResolveRelativePath(Theme::$themesFolder.Theme::$currentTheme, $path);
			$result = Theme::BuildUri($resolvedPath, $arguments);
			if (Theme::$currentTheme != 'default')
			{
				if ($result === false)
				{
					$resolvedPath = FileSystem::ResolveRelativePath(Theme::$themesFolder.'default', $path);
					$result = Theme::BuildUri($resolvedPath, $arguments);
				}
			}
			if ($result === false)
			{
				$result = Theme::BuildUri($path, $arguments);
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
					Theme::RequestScript($item, $arguments, $attributes);
				}
				return true;
			}
			else
			{
				$uri = Theme::GetUri($path, $arguments);
				if ($uri === false)
				{
					return false;
				}
				else
				{
					$entry = array('uri' => $uri, 'attributes' => $attributes);
					if (is_array(Theme::$scripts))
					{
						Theme::$scripts[] = $entry;
					}
					else
					{
						Theme::$scripts = array($entry);
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
					Theme::RequestStyleSheet($item, $arguments, $attributes);
				}
				return true;
			}
			else
			{
				$uri = Theme::GetUri($path, $arguments);
				if ($uri === false)
				{
					return false;
				}
				else
				{
					$entry = array('uri' => $uri, 'attributes' => $attributes);
					if (is_array(Theme::$styleSheets))
					{
						Theme::$styleSheets[] = $entry;
					}
					else
					{
						Theme::$styleSheets = array($entry);
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
	Theme::Configure('default', FileSystem::FolderCore().'themes');
	$currentTheme = 'default';
	$themesFolder = 'themes';
	function Theme_Configure()
	{
		Theme::Configure
		(
			Configuration::Get('paladio-theme', 'current', 'default'),
			FileSystem::FolderCore().Configuration::Get('paladio-paths', 'themes', 'themes')
		);
	}
	Configuration::Callback('paladio-paths', 'Theme_Configure');
	Configuration::Callback('paladio-theme', 'Theme_Configure');
?>