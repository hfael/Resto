<?php

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $uri);

$controller = $segments[0] ?: 'home';
$method     = $segments[1] ?? 'index';

$controllerName = ucfirst($controller) . 'Controller';
$controllerFile = __DIR__ . '/../src/Controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    exit('404');
}

require $controllerFile;

$instance = new $controllerName();

if (!method_exists($instance, $method)) {
    http_response_code(404);
    exit('404');
}

$instance->$method();
