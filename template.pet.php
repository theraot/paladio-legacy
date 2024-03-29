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
			echo PET_Utility::PETInvoke('title', Utility::ArrayTake($_ELEMENT['attributes'], 'title'));
			PaladioTheme::RequestStyleSheet('base.css');
			PaladioTheme::RequestStyleSheet('../../fonts/font-awesome.min.css');
			@PaladioTheme::RequestStyleSheet($_ELEMENT['attributes']['stylesheets']);
			PaladioTheme::RequestScript('thoth.js');
			@PaladioTheme::RequestScript($_ELEMENT['attributes']['scripts']);
			PaladioTheme::EmitStyleSheets();
			PaladioTheme::EmitScripts();
		?>
		<script> thoth.configure(<?php echo json_encode(PaladioTheme::GetRootUris()); ?>); </script>
		<!--[if lt IE 9]><?php
			@PaladioTheme::RequestScript('css3-mediaqueries.js');
			PaladioTheme::EmitScripts();
		?><![endif]-->
	</head>
	<body>
		<div id="__wrap">
			<@header/>
			<?php echo PET_Utility::PETInvoke('menu', array('id' => '__menu', 'class' => 'menu', 'selected-class' => 'selected', 'active-class' => 'selected')); ?>
			<div id="__main"><?php echo PET_Utility::PETInvoke('message'); ?>
				<div id="__content">
					<?php echo $_ELEMENT['contents']; ?>
				</div>
				<@aside/>
			</div>
			<@footer/>
		</div>
	</body>
</html>