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
			//Configuración para enviar correo con PHPMailer
			'paladio-mail' => array
			(
				// 'server' => 'localhost'
				// 'port =>
				// 'user =>
				// 'password =>
				// 'secure' => 'tls'
				// 'sender' =>
				// 'sender_name =>
			)
		)
	);
?>
