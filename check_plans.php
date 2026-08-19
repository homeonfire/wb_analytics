<?php
$host = '45.141.78.143';
$db   = 'postgres';
$user = 'postgres';
$pass = 'Ujg2o829LjdPGNc6LvFDNqFk1sMjkVix';
$port = "54322";

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
$stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'product_plans'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
