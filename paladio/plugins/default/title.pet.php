<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	echo '<title>';
	if (isset($_ELEMENT['attributes']['title']))
	{
		echo $_ELEMENT['attributes']['title'];
		if (Configuration::TryGet('paladio', 'sitename', $sitename))
		{
			echo ' - '.$sitename;
		}
	}
	echo '</title>';
?>
