<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
?><!DOCTYPE html>
<html <?php if(isset($_ELEMENT['attributes']['lang'])) echo 'lang = "'.$_ELEMENT['attributes']['lang'].'"'; ?>>
	<head>
		<meta charset="UTF-8" />
		<title><?php if(isset($_ELEMENT['attributes']['title'])) echo $_ELEMENT['attributes']['title']; ?></title>
		<?php
			if(isset($_ELEMENT['attributes']['stylesheets']))
			{
				$stylesheets = array_map('trim', explode(';', $_ELEMENT['attributes']['stylesheets']));
				Theme::RequestStyleSheet($stylesheets);
				Theme::EmitStyleSheets();
			}
		?>
	</head>
	<body><div id="wrap"><?php echo $_ELEMENT['contents']; ?><footer>
		<h3>About</h3>
		<p>Demostración de Paladio creada por <span class="author">Alfonso J. Ramos.</span><time datetime="2013-04-23">Martes, 23 de Abril de 2013</time></p>
	</footer></div></body>
</html>