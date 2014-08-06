<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	if (Session::TryDequeue($message))
	{
		echo '<div';
		if (array_key_exists('id', $message))
		{
			echo ' id="'.$message['id'].'"';
		}
		echo ' id="__message" ';
		if (array_key_exists('class', $message))
		{
			echo ' class="'.$message['class'].' __message"';
		}
		else
		{
			echo ' class="__message"';
		}
		echo '>';
		echo $message['text'];
		echo '<button onclick="var node = this.parentNode; node.parentNode.removeChild(node);"><span class="fa">&#xf00d;</span></button>';
		echo '</div>';
		Session::DequeueAll();
	}
?>