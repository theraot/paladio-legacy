<?php header('HTTP/1.0 404 Not Found'); exit(); //Not to be seen ?>

[paladio-database] # Configuración para acceder a la base de datos
# engine = MySQL
# server = localhost
# port =
# database =
# charset = UTF8
# user =
# password =
# query_user =
# query_password =
persist=persistent

[paladio] #Configuración global
timezone = America/Bogota
locale = 'Spanish_Colombia'
sitename = Paladio

[paladio-paths] #Configuración de rutas de acceso
entities = ../files/entities
themes = ../files/themes
plugins = /plugins
#configurarion = 

[paladio-strings] #Configuración de cadenas de texto
weekdays = [Domingo, Lunes, Martes, Miércoles, Jueves, Viernes, Sábado]
months = [Enero, Febrero, Marzo, Abril, Mayo, Junio, Julio, Agosto, Septiembre, Octubre, Noviembre, Diciembre]