<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	$validUris = AccessControl::ValidUris();
	$keys = array_keys($validUris);
	echo '<nav>';
	if (isset($_ELEMENT['attributes']['class']))
	{
		echo '<ul class="'.$_ELEMENT['attributes']['class'].'">';
	}
	else
	{
		echo '<ul>';
	}
	$selectedClass = isset($_ELEMENT['attributes']['selected-class']) ? $_ELEMENT['attributes']['selected-class'] : false;
	$itemClass = isset($_ELEMENT['attributes']['item-class']) ? $_ELEMENT['attributes']['item-class'] : false;
	foreach ($keys as $key)
	{
		$title = isset($validUris[$key]['menu-title']) ? $validUris[$key]['menu-title'] : false;
		if ($title !== false)
		{
			echo '<li';
			if ($selectedClass === false)
			{
				if ($itemClass === false)
				{
					//Empty
				}
				else
				{
					echo ' class="'.$itemClass.'"';
				}
			}
			else if ($_ELEMENT['source'] == $validUris[$key]['path'])
			{
				if ($itemClass === false)
				{
					echo ' class="'.$selectedClass.'"';
				}
				else
				{
					echo ' class="'.$itemClass.' '.$selectedClass.'"';
				}
			}
			if (isset($validUris[$key]['menu-id']))
			{
				echo ' id="'.$validUris[$key]['menu-id'].'"';
			}
			echo '><a href="'.$key.'">'.$title.'</a></li>';
		}
	}
	echo '</ul></nav>';
?>