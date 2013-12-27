<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('paladio.lib.php');
	}

	ob_start();
	include(FileSystem::ScriptPath());
	$document = ob_get_contents();
	ob_end_clean();

	if (isset($document) && $document !== '')
	{
		$result = Paladio::ProcessDocument($document, FileSystem::ScriptPath(), $_SERVER['QUERY_STRING']);
		ini_set('default_charset', 'UTF-8');
		echo $result;
	}

	exit();
?>