<?php

$host = "localhost";
$dbname = "gestion_budget";
$username = "root";
$password = "";


try {
    $connection = new PDO("mysql:host=$host;dbname=$dbname",$username,$password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $connection;
} catch(PDOException $e) {
    die("connection failed: " .$e->getMessage());
}

?>