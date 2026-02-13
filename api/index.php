<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/Routes/api.php';
require_once __DIR__ . '/../../api/Helpers/Response.php';

use API\Routes\ApiRouter;
use API\Helpers\Response;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri);
if ($uri === '') $uri = '/';

try {
    (new ApiRouter())->dispatch($method, $uri);
} catch (Throwable $e) {
    Response::error('server_error', 500, [
        'message' => $e->getMessage()
    ]);
}
