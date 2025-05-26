<?php
// Este archivo se encargará de la conexión a la base de datos
$host = "localhost";
$port = "5432";
$dbname = "Gestion_Cine"; 
$user = "lizzardi";
$password = "";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Conexión fallida: " . pg_last_error());
}

// Opcional: para establecer el charset a utf8
pg_set_client_encoding($conn, "UTF8");
?>