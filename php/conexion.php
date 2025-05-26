<?php
// Este archivo se encargará de la conexión a la base de datos
$host = "btcq1ilgyasmbpre8a0s-mysql.services.clever-cloud.com";  
$port = "3306";           
$dbname = "btcq1ilgyasmbpre8a0s"; 
$user = "ueqzaivmbgljiu7p"; 
$password = "y6iBGJK2p1HSr7bAYCh5";      

// Crear la conexión con MySQL usando mysqli
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer el charset a utf8
$conn->set_charset("utf8");


?>