<?php

require_once __DIR__ . '/../vendor/autoload.php';

class View
{
    private static $twig = null;

    private static function init()
    {
        if (self::$twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/Views/twig');

            self::$twig = new \Twig\Environment($loader, [
                'cache' => false,
                'autoescape' => false
            ]);
        }
    }

    public static function render($template, $data = [])
    {
        self::init();
        echo self::$twig->render($template, $data);
    }
}
