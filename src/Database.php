<?php

class Database
{
    private static $pdo = null;

    public static function getConnection()
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "mysql:host=mysql-db;dbname=resto;charset=utf8",
                "app",
                "app",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );
        }

        return self::$pdo;
    }
}
