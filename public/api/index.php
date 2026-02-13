<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/Helpers/Response.php';
require_once __DIR__ . '/../../api/Routes/api.php';

use API\Routes\ApiRouter;
use API\Helpers\Response;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable detailed errors in dev when DEBUG=1
if (getenv('DEBUG') === '1') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

set_exception_handler(function($e){
    Response::error("Server error", 500, ["message" => $e->getMessage(), "trace" => $e->getTraceAsString()]);
});

set_error_handler(function($severity, $message, $file, $line){
    Response::error("PHP error", 500, [
        "message" => $message,
        "file" => $file,
        "line" => $line
    ]);
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri);
if ($uri === '') $uri = '/';

$router = new ApiRouter();
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
} catch (Throwable $e) {
    Response::error("Unhandled exception", 500, ["message" => $e->getMessage(), "trace" => $e->getTraceAsString()]);
}
