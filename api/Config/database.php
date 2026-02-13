<?php

$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'resto';
$user = getenv('DB_USER') ?: 'app';
$pass = getenv('DB_PASS') ?: 'app';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error"=>"server_error",
        "debug"=>$e->getMessage()
    ]);
    exit;
}
