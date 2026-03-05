<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Csrf.php';

class View
{
    private static $twig = null;

    private static function init()
    {
        if (self::$twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/Views/twig');

            self::$twig = new \Twig\Environment($loader, [
                'cache' => false,
                'autoescape' => 'html'
            ]);
        }
    }

    public static function render($template, $data = [])
    {
        self::init();
        $data['csrf_token'] = Csrf::token();
        $data['session'] = $data['session'] ?? $_SESSION;
        echo self::$twig->render($template, $data);
    }
}
