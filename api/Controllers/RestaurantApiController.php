<?php
namespace API\Controllers;

require_once '/var/www/src/Models/Restaurant.php';
require_once __DIR__ . '/../Helpers/Response.php';

use API\Helpers\Response;

class RestaurantApiController {

    public function index() {
        Response::json(["status" => "restaurants index OK"]);
    }

    public function store() {
        Response::json(["status" => "restaurant store OK"]);
    }

    public function show($id) {
        Response::json(["status" => "restaurant show OK", "id" => $id]);
    }

    public function update($id) {
        Response::json(["status" => "restaurant update OK", "id" => $id]);
    }

    public function delete($id) {
        Response::json(["status" => "restaurant delete OK", "id" => $id]);
    }
}
