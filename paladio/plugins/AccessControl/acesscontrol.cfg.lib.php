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
				// 'hash_algorithm' => 'sha1',
				/*'rules' => array
				(
					'__all' => array
					(
						'index.php' => true,
					),
					'__anonymous' => array
					(
					),
					'__unconfigured' => array
					(
					),
					'__unauthenticated' => array
					(
					),
					'__authenticated' => array
					(
					),
					'__fallback' => array
					(
						'index.php'
					)
				)*/
			)
		)
	);
?>
