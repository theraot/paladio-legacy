<?php header('HTTP/1.0 404 Not Found'); exit(); //Not to be seen ?>

# [paladio-database] # Configuración para acceder a la base de datos
# server = localhost
# port =
# database =
# user =
# password =
# query_user =
# query_password =

[paladio] #Configuración global
timezone = America/Bogota
sitename = Paladio

[paladio-paths] #Configuración de rutas de acceso
entities = ../files/entities
themes = ../files/themes
plugins = /plugins

[paladio-strings] #Configuración de cadenas de texto
weekdays = [Domingo, Lunes, Martes, Miércoles, Jueves, Viernes, Sábado]
months = [Enero, Febrero, Marzo, Abril, Mayo, Junio, Julio, Agosto, Septiembre, Octubre, Noviembre, Diciembre]