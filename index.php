<?php
	require_once('./paladio/paladio.php');
	if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		header('Location: .', true, 303);
		exit();
	}
?>
<@template lang="es" title="Paladio" stylesheets="main.css">
	<header>
		<h1>Paladio</h1>
	</header>
	<@menu selected-class="selected"/>
	<@post id="intro" title="Prueba" date="<?php echo time(); ?>" date-format="l, j \d\e F \d\e Y" author="Alfonos Ramos">
		<p>Ésta página fue creada con Paladio. Paladio es un Framework de bajo nivel para PHP.</p>
	</@post>
	<div id="content">
		<div id="mainContent">
			<@post class="post" title="Post" level="2" date="<?php echo time(); ?>" date-format="l, j \d\e F \d\e Y" author="Alfonos Ramos">
				<div>
					<p>
						Paladio permite definir etiquetas del lado del servidor, las cuales serán procesadas por paladio utilizando archivos PET (Paladio Element Template). Ésta característica facilita la refactorización de contenido del lado del servidor, haciendo fácil reutilizar prociones HTML.
					</p>
					<p>
						Al ocultar detalles de implementación con las PET se logra mantener limpio el código del lado del servidor. Puede utilizar las PET para crear componentes personalizados y utilizarlos en cualquier parte de su aplicación web.
					</p>
					<p>
						Además, Paladio ofrece un ORM (Object Rational Mapping) que le permitirá acceder a su base de datos mediante una interfaz orientada a objetos. Puede declarar las clases de entidad con muy poco código, o si lo prefiere puede configurar a Paladio para que genere sus clases de entidad de forma automática, o incluso puede configurarlo para cree las tablas de la base de datos.
					</p>
					<p>
						Paladio construye las sentencias SQL de forma agnóstica al motor de base de datos utilizando características estándar que se encuentran disponibles para todos los motores. Aunque Paladio solo trae código para acceder a MySQL, este código se encuentra aislado en un solo archivo con el objetivo de hacer fácil cambiar el código para soportar otro motor.
					</p>
					<p>
						El ORM de paladio no requiere que su base de datos siga alguna convención de nombres, ni siquiera necesita que sus llaves primarias se llamen de alguna forma. Cualquier esquema es valido para Paladio, incluso si su llave primaria está compuesta de varios campos podrá crear clases de entidad para sus tablas sin mayor inconveniente.
					</p>
					<p>
						¿Necesita entidades que accedan a más de una tabla? Para esos casos en que declarar relaciones entre sus entidades no es suficiente, puede recurrir a la herencia de clases de entidad. Cuando tiene objetos cuyas clase de entidad hereden unas de otras, Paladio mapeará el acceso a los datos de dichos objetos a las diferentes tablas de sus clases de entidad según sea necesario.
					</p>
					<p>
						Combine el ORM con el poder las PET para crear componentes de acceso a datos reutilizables. No hay limite para lo que puede crear con las PET: desde el registro de notas de un colegio, o el control de cardex de un inventario, hasta la visualización de posts y comentarios en un blog, cualquier pieza de contenido y comportamiento se puede convertir en un componente reutilizable.
					</p>
					<p>
						No siempre que utiliza las PET tiene que invocarlas una a una, es posible invocar PETs múltiples que serán descubiertas en tiempo de ejecución. Por ejemplo, si desea agregar componentes a un área de su página (por ejemplo en una columna lateral o al final) podrá definir un elemento PET múltiples y así podrá cambiar el contenido de esa área sin tener que modificar el código de su página.
					</p>
					<p>
						Con Paladio el control de acceso ya no será un problema, pues Paladio incluye un sistema de listas de control de acceso por roles que será aplicado de forma automática, sin necesidad de cambiar el código de su página PHP.
					</p>
					<p>
						Puede que tenga que empezar a pensar diferente para sacar todo el potencial de Paladio. Por ejemplo considere que la barra de navegación en la parte superior de ésta página es generada de forma dinámica utilizando listas de control de acceso utilizando una PET.
					</p>
					<p>
						Paladio no necesita ninguna configuración especial de su servidor web, permitiéndole ejecutar en cualquiera de las opciones de alojamiento disponibles. Incluso si su servidor no es Apache, puesto que Paladio no necesita archivos .htaccess para funcionar.
					</p>
					<p>
						La clase de manejo de sesiones de Paladio le facilitará pasar mensajes entre páginas o guardar variables de estado.
					</p>
					<p>
						Además Paladio le facilitará crear e intercambiar temas con tan solo unas cuantas lineas de configuración. Incluso podrá hacer que su sitio cambie de tema de forma automática.
					</p>
					<p>
						Toda la configuración de Paladio son archivos INI para facilitar su interpretación y modificación.
					</p>
					<p>
						Paladio puede ser extendido fácilmente. Además de las PET, Paladio soporta la carga de plugins para agregar funciones nuevas al sistema.
					</p>
					<p>
						Paladio está diseñado para trabajar con UTF-8. Solo indique que utiliza UTF-8 en la cabecera de la página y no volverá a tener problemas de codificación de caracters.
					</p>
					<p>
						Paladio ha sido probado en Linux y en Windows.
					</p>
				</div>
			</@post>
			<!--
			<section id="comments">
				
			</section>
			<form>
				
			</form>
			-->
		</div>
		<aside>
			<@@sidebar/>
		</aside>
	</div>
</@template>