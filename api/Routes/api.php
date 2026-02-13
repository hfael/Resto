<?php
namespace API\Routes;

require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Controllers/AuthApiController.php';
require_once __DIR__ . '/../Controllers/RestaurantApiController.php';
require_once __DIR__ . '/../Controllers/ReservationApiController.php';
require_once __DIR__ . '/../Controllers/TestApiController.php';
require_once __DIR__ . '/../Middleware/JwtMiddleware.php';

use API\Helpers\Response;
use API\Controllers\AuthApiController;
use API\Controllers\RestaurantApiController;
use API\Controllers\ReservationApiController;
use API\Controllers\TestApiController;
use API\Middleware\JwtMiddleware;

class ApiRouter {

    public function dispatch($method, $path) {

        // AUTH
        if ($path === '/auth/register' && $method === 'POST') {
            (new AuthApiController)->register();
            return;
        }

        if ($path === '/auth/login' && $method === 'POST') {
            (new AuthApiController)->login();
            return;
        }

        // RESTAURANTS
        if ($path === '/restaurants' && $method === 'GET') {
            (new RestaurantApiController)->index();
            return;
        }

        if ($path === '/restaurants' && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->store($user);
            return;
        }

        if (preg_match('#^/restaurants/([0-9]+)$#', $path, $m)) {
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

        // RESERVATIONS
        if ($path === '/reservations' && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->store($user);
            return;
        }

        if ($path === '/reservations/user' && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->userReservations($user);
            return;
        }

        if (preg_match('#^/reservations/restaurant/([0-9]+)$#', $path, $m) && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->restaurantReservations($user, $m[1]);
            return;
        }

        if (preg_match('#^/reservations/([0-9]+)$#', $path, $m) && $method === 'DELETE') {
            $user = JwtMiddleware::protect();
            (new ReservationApiController)->delete($user, $m[1]);
            return;
        }

        // TEST routes (dev only)
        if ($path === '/test/seed' && $method === 'POST') {
            (new TestApiController)->seed();
            return;
        }

        Response::json(["error" => "Route not found", "path"=>$path], 404);
    }
}
