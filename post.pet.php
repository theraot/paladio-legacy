<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	if(isset($_ELEMENT['attributes']['level']))
	{
		$level = intval($_ELEMENT['attributes']['level']);
	}
	else
	{
		$level = 1;
	}
?><section<?php if(isset($_ELEMENT['attributes']['id'])) echo ' id = "'.$_ELEMENT['attributes']['id'].'"'; ?>>
	<article<?php if(isset($_ELEMENT['attributes']['class'])) echo ' class = "'.$_ELEMENT['attributes']['class'].'"'; ?>>
		<header>
			<h<?php echo $level ?>><?php if(isset($_ELEMENT['attributes']['title'])) echo $_ELEMENT['attributes']['title']; ?></h<?php echo $level ?>>
			<?php
				$isoformat = 'Y-m-d\TH:i:s';
				$date = isset($_ELEMENT['attributes']['date']) ? $_ELEMENT['attributes']['date'] : false;
				$dateformat = isset($_ELEMENT['attributes']['date-format']) ? $_ELEMENT['attributes']['date-format'] : $isoformat;
				$author = isset($_ELEMENT['attributes']['author']) ? $_ELEMENT['attributes']['author'] : false;
				if ($date === false)
				{
					if ($author !== false)
					{
						echo '<p>';
						echo '<span class="author">'.$author.'</span>:';
						echo '</p>';
					}
				}
				else
				{
					echo '<p>';
					if ($author !== false)
					{
						echo '<span class="author">'.$author.'</span>: ';
					}
					echo '<time datetime="'.String_Utility::FormatDate($date, $isoformat).'">'.String_Utility::FormatDate($date, $dateformat).'</time>';
					echo '</p>';
				}
			?>
		</header>
		<?php echo $_ELEMENT['contents']; ?>
	</article>
</section>