<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	header('Content-Type: text/html; charset=UTF-8');
	if (array_key_exists('menu', $_ELEMENT['attributes']))
	{
		$__menu = $_ELEMENT['attributes'][$menu];
	}
	else
	{
		$__menu = '';
	}
?><!DOCTYPE html>
<html <?php
	if(isset($_ELEMENT['attributes']['lang']))
	{
		echo 'lang = "'.$_ELEMENT['attributes']['lang'].'"';
	}
	?>>
	<head>
		<meta charset="UTF-8" />
		<title><?php
			if(isset($_ELEMENT['attributes']['title']))
			{
				echo $_ELEMENT['attributes']['title'];
				if (Configuration::TryGet('paladio', 'sitename', $sitename))
				{
					echo ' - '.$sitename;
				}
			}
		?></title><?php
			if(isset($_ELEMENT['attributes']['stylesheets']))
			{
				$stylesheets = array_map('trim', explode(';', $_ELEMENT['attributes']['stylesheets']));
				PaladioTheme::RequestStyleSheet($stylesheets);
				PaladioTheme::EmitStyleSheets();
			}
	?></head>
	<body>
		<div id="__wrap">
			<@header><?php
				if ($__menu == 'header')
				{
					echo '<@menu id="__menu" class="menu" selected-class="selected" active-class="selected"/>';
				}
			?></@header>
			<div id="__main"><?php if (Session::TryDequeue($message))
					{
						echo '<div id="__message" class="'.$message['type'].'">';
						echo $message['text'];
						echo '<input type="button" style="float:right;" value="X" onclick="var node = document.getElementById(\'__message\'); node.parentNode.removeChild(node);">';
						echo '</div>';
						Session::DequeueAll();
					}
				?><div id="__content">
					<?php echo $_ELEMENT['contents']; ?>
				</div>
				<@aside><?php
					if ($__menu == 'aside')
					{
						echo '<@menu id="__menu" class="menu" selected-class="selected" active-class="selected"/>';
					}
				?></@aside>
			</div>
			<hr/>
			<@footer><?php
				if ($__menu == 'footer')
				{
					echo '<@menu id="__menu" class="menu" selected-class="selected" active-class="selected"/>';
				}
			?></@footer>
		</div>
	</body><?php
		if(isset($_ELEMENT['attributes']['scripts']))
		{
			$scripts = array_map('trim', explode(';', $_ELEMENT['attributes']['scripts']));
			PaladioTheme::RequestStyleSheet($scripts);
			PaladioTheme::EmitScripts();
		}
?></html>