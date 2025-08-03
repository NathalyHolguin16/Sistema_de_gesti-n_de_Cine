<?php
// Este archivo se encargará de la conexión a la base de datos PostgreSQL
$host = "aws-0-us-east-2.pooler.supabase.com";  
$port = "5432";           
$dbname = "postgres"; 
$user = "postgres.etureuqikqzbrvlkuvds"; 
$password = "8G9K2ZkqCRDgnZOO";      

try {
    // Crear la conexión con PostgreSQL usando PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
} catch (PDOException $e) {
    die("Error de conexión a PostgreSQL: " . $e->getMessage());
}

?>