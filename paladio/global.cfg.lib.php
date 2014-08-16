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
			// Configuración para acceder a la base de datos
			'paladio-database' => array
			(
				// 'engine => 'MySQL',
				// 'server => 'localhost',
				// 'port' =>
				// 'database' =>
				// 'charset' => 'UTF8',
				// 'user' =>
				// 'password' =>
				// 'query_user' =>
				// 'query_password' =>
				'persist' => 'persistent',
			),
			
			//Configuración global
			'paladio' => array
			(
				'timezone' => 'America/Bogota',
				'locale' => 'Spanish_Colombia',
				'sitename' => 'Paladio',
			),
			
			//Configuración de rutas de acceso
			
			'paladio-paths' => array
			(
				'entities' => '../files/entities',
				'themes' => '../files/themes',
				'plugins' => '/plugins',
				// 'Configuraion' => 
			),
			
			//Configuración de cadenas de texto
			'paladio-strings' => array
			(
				'weekdays' => array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'),
				'months' => array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'),
			)
		)
	);
?>