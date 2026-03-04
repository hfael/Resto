<?php

class Database
{
    private static $pdo = null;

    private static function config()
    {
        $fileConfig = [];
        $configPath = __DIR__ . '/../config/database.php';

        if (file_exists($configPath)) {
            $loadedConfig = require $configPath;
            if (is_array($loadedConfig)) {
                $fileConfig = $loadedConfig;
            }
        }

        return [
            'host' => getenv('DB_HOST') ?: ($fileConfig['host'] ?? 'mysql'),
            'port' => getenv('DB_PORT') ?: ($fileConfig['port'] ?? '3306'),
            'dbname' => getenv('DB_NAME') ?: ($fileConfig['dbname'] ?? 'resto'),
            'user' => getenv('DB_USER') ?: ($fileConfig['user'] ?? 'app'),
            'pass' => getenv('DB_PASS') ?: ($fileConfig['pass'] ?? 'app'),
            'charset' => getenv('DB_CHARSET') ?: ($fileConfig['charset'] ?? 'utf8mb4'),
        ];
    }

    public static function getConnection()
    {
        if (self::$pdo === null) {
            $db = self::config();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['dbname'],
                $db['charset']
            );

            $retries = (int) (getenv('DB_CONNECT_RETRIES') ?: 20);
            $retryDelayMs = (int) (getenv('DB_CONNECT_RETRY_DELAY_MS') ?: 500);
            $retries = max(1, $retries);
            $retryDelayUs = max(0, $retryDelayMs) * 1000;
            $lastException = null;

            for ($attempt = 1; $attempt <= $retries; $attempt++) {
                try {
                    self::$pdo = new PDO(
                        $dsn,
                        $db['user'],
                        $db['pass'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        ]
                    );
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                    if ($attempt < $retries && $retryDelayUs > 0) {
                        usleep($retryDelayUs);
                    }
                }
            }

            if (self::$pdo === null && $lastException !== null) {
                throw $lastException;
            }
        }

        return self::$pdo;
    }
}
