<?php
// Este archivo se encargará de la conexión a la base de datos
$host = "212.1.208.199";  
$port = "3306";           
$dbname = "u312507976_db87"; 
$user = "u312507976_user8"; 
$password = "4Ag824-2";      

// Crear la conexión con MySQL usando mysqli
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer el charset a utf8
$conn->set_charset("utf8");


?>