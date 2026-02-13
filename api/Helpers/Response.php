<?php
namespace API\Helpers;

class Response
{
    public static function json($data, int $status = 200)
    {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    public static function error(string $message, int $status = 500, array $debug = [])
    {
        http_response_code($status);
        echo json_encode([
            "error" => $message,
            "debug" => $debug
        ]);
        exit;
    }
}
