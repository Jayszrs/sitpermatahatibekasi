<?php

require_once __DIR__ . '/environment.php';

function unit_database_unavailable(string $reason): never
{
    error_log('[unit-database] ' . $reason);
    if (PHP_SAPI === 'cli') throw new RuntimeException('Unit database is unavailable.');
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 30');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Layanan sementara tidak tersedia</title><body><main><h1>Layanan sementara tidak tersedia</h1><p>Website unit sedang dipersiapkan. Silakan coba kembali beberapa saat lagi.</p></main></body></html>';
    exit;
}

function unit_database_connection(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) return $connection;

    $railway = app_env('MYSQLHOST') !== null;
    $host = app_env($railway ? 'MYSQLHOST' : 'DB_HOST');
    $port = app_env($railway ? 'MYSQLPORT' : 'DB_PORT', '3306');
    $user = app_env($railway ? 'MYSQLUSER' : 'DB_USER');
    $pass = app_env($railway ? 'MYSQLPASSWORD' : 'DB_PASS', '');
    $name = app_env('UNIT_DB_NAME', $railway ? null : 'school_units_portal');

    if ($host === null || $user === null || $name === null) unit_database_unavailable('Required connection settings are missing.');
    if (!ctype_digit((string) $port) || (int) $port < 1 || (int) $port > 65535) unit_database_unavailable('Invalid port.');
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) unit_database_unavailable('Invalid database name.');
    if (!extension_loaded('pdo_mysql')) unit_database_unavailable('The pdo_mysql extension is not loaded.');

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, (int) $port), $user, $pass, $options);
        $server->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $name));
        $connection = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int) $port, $name), $user, $pass, $options);
        return $connection;
    } catch (Throwable $exception) {
        unit_database_unavailable('Connection failed (' . get_class($exception) . ').');
    }
}
