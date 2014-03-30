<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html <?php echo PET_Utility::BuildAttributesString($_ELEMENT['attributes'], 'lang') ?>>
	<head>
		<meta charset="UTF-8" />
		<?php
			echo PET_Utility::InvokePET('title', Utility::ArrayTake($_ELEMENT['attributes'], 'title'));
			@PaladioTheme::RequestStyleSheet($_ELEMENT['attributes']['stylesheets']);
			@PaladioTheme::RequestScript($_ELEMENT['attributes']['scripts']);
			PaladioTheme::EmitStyleSheets();
			PaladioTheme::EmitScripts();
		?>
	</head>
	<body>
		<div id="__wrap">
			<@header/>
			<?php echo PET_Utility::InvokePET('menu', array('id' => '__menu', 'class' => 'menu', 'selected-class' => 'selected', 'active-class' => 'selected')); ?>
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
				<@aside/>
			</div>
			<@footer/>
		</div>
	</body>
</html>