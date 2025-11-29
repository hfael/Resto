<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Helpers/Response.php';
require_once __DIR__ . '/Routes/api.php';

use API\Routes\ApiRouter;

$router = new ApiRouter();
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
