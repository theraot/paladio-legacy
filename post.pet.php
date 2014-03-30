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
?><section<?php echo PET_Utility::BuildAttributesString($_ELEMENT['attributes'], 'id'); ?>>
	<article<?php echo PET_Utility::BuildAttributesString($_ELEMENT['attributes'], 'class'); ?>>
		<header>
			<h<?php echo $level ?>><?php if(array_key_exists('title', $_ELEMENT['attributes'])) echo $_ELEMENT['attributes']['title']; ?></h<?php echo $level ?>>
			<?php
				$isoformat = 'Y-m-d\TH:i:s';
				$date = array_key_exists('date', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['date'] : false;
				$dateformat = array_key_exists('date-format', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['date-format'] : $isoformat;
				$author = array_key_exists('author', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['author'] : false;
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