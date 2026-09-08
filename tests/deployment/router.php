<?php

$root = dirname(__DIR__, 2);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$candidate = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
if ($path !== '/' && (is_file($candidate) || is_dir($candidate))) return false;
require $root . '/index.php';
