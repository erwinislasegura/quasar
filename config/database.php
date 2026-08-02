<?php

declare(strict_types=1);

/**
 * Centralized database connection.
 *
 * The closure returns null while DB_HOST is not configured so local TXT mode
 * continues to work. In a database-backed environment it returns one shared
 * PDO connection and lets connection errors surface instead of hiding them.
 *
 * @return Closure(): ?PDO
 */
return static function (): ?PDO {
    static $connection = null;

    $host = getenv('DB_HOST');
    if ($host === false || $host === '') {
        return null;
    }

    if ($connection instanceof PDO) {
        return $connection;
    }

    $driver = getenv('DB_DRIVER') ?: 'mysql';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'quasar';
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $driver,
        $host,
        $port,
        $database,
        $charset
    );

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
};
