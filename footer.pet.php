<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
?><footer>
	<?php echo $_ELEMENT['contents']; ?>
</footer>