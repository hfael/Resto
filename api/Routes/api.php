<?php
namespace API\Routes;

require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Controllers/AuthApiController.php';
require_once __DIR__ . '/../Controllers/RestaurantApiController.php';
require_once __DIR__ . '/../Middleware/JwtMiddleware.php';
require_once __DIR__ . '/../Controllers/ReservationApiController.php';

use API\Helpers\Response;
use API\Controllers\AuthApiController;
use API\Controllers\RestaurantApiController;
use API\Middleware\JwtMiddleware;
use API\Controllers\ReservationApiController;

class ApiRouter {

    public function dispatch($method, $uri) {
        $path = parse_url($uri, PHP_URL_PATH);

        file_put_contents('/var/www/html/api_debug.txt', $_SERVER['REQUEST_URI']);
        file_put_contents('/var/www/html/api_path.txt', $path);
        file_put_contents('/var/www/html/api_method.txt', $_SERVER['REQUEST_METHOD']);



        // AUTH
        if ($path === '/api/auth/register' && $method === 'POST') {
            (new AuthApiController)->register();
            return;
        }

        if ($path === '/api/auth/login' && $method === 'POST') {
            (new AuthApiController)->login();
            return;
        }

        // RESTAURANTS
        if ($path === '/api/restaurants' && $method === 'GET') {
            (new RestaurantApiController)->index();
            return;
        }

        if ($path === '/api/restaurants' && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->store($user);
            return;
        }

        if (preg_match('#^/api/restaurants/([0-9]+)$#', $path, $m)) {

            if ($method === 'GET') {
                (new RestaurantApiController)->show($m[1]);
                return;
            }

            if ($method === 'PUT') {
                (new RestaurantApiController)->update($m[1]);
                return;
            }

            if ($method === 'DELETE') {
                (new RestaurantApiController)->delete($m[1]);
                return;
            }
        }
        if ($path === '/api/reservations' && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->store($user);
            return;
        }

        if ($path === '/api/reservations/user' && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->userReservations($user);
            return;
        }

        if (preg_match('#^/api/reservations/restaurant/([0-9]+)$#', $path, $m) && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->restaurantReservations($user, $m[1]);
            return;
        }

        if (preg_match('#^/api/reservations/([0-9]+)$#', $path, $m) && $method === 'DELETE') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->delete($user, $m[1]);
            return;
        }

        Response::json(["error" => "Route not found"], 404);
    }
}
