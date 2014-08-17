<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('configuration.lib.php');
	}
	Configuration::Add
	(
		array
		(
			//Configuración para control de acceso
			'paladio-accesscontrol' => array
			(
				// 'table' =>
				// 'id_field' =>
				// 'password_field' =>
				// 'salt_field' =>
				// 'role_field' =>
				// 'hash_algorithm' => 'sha1'
			)
		)
	);
?>
