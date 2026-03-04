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

        if ($path === '/auth/me' && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new AuthApiController)->me($user);
            return;
        }

        // RESTAURANTS
        if ($path === '/restaurants' && $method === 'GET') {
            (new RestaurantApiController)->index();
            return;
        }

        if ($path === '/restaurants/search' && $method === 'GET') {
            (new RestaurantApiController)->search();
            return;
        }

        if ($path === '/restaurants/mine' && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->mine($user);
            return;
        }

        if ($path === '/restaurants/pending' && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->pending($user);
            return;
        }

        if (preg_match('#^/restaurants/([0-9]+)/bookings$#', $path, $m) && $method === 'GET') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->bookings($user, $m[1]);
            return;
        }

        if (preg_match('#^/restaurants/([0-9]+)/cancel$#', $path, $m) && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->cancel($user, $m[1]);
            return;
        }

        if (preg_match('#^/restaurants/([0-9]+)/accept$#', $path, $m) && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->accept($user, $m[1]);
            return;
        }

        if (preg_match('#^/restaurants/([0-9]+)/reject$#', $path, $m) && $method === 'POST') {
            $user = JwtMiddleware::protect();
            (new RestaurantApiController)->reject($user, $m[1]);
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
            if ($method === 'PUT' || $method === 'POST') {
                $user = JwtMiddleware::protect();
                (new RestaurantApiController)->update($user, $m[1]);
                return;
            }
            if ($method === 'DELETE') {
                $user = JwtMiddleware::protect();
                (new RestaurantApiController)->delete($user, $m[1]);
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

        Response::json(["error" => "Route not found", "path" => $path], 404);
    }
}