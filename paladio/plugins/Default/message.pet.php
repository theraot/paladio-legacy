<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	if (Session::TryDequeue($message))
	{
		echo '<div id="__message" ';
		if ($message['type'] == 'good')
		{
			echo 'class="good"';
		}
		else
		{
			echo 'class="bad"';
		}
		echo '>';
		echo $message['text'];
		echo '<button style="float:right;" onclick="var node = document.getElementById(\'__message\'); node.parentNode.removeChild(node);"><span class="ui-icon ui-icon-close">X</span></button>';
		echo '</div>';
		Session::DequeueAll();
	}
?>