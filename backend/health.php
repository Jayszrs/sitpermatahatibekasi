<?php

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/config/database.php';

$checks = [
    'php' => version_compare(PHP_VERSION, '8.3.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'database' => false,
    'schema' => false,
];

try {
    $checks['database'] = (int) $pdo->query('SELECT 1')->fetchColumn() === 1;
    $requiredTables = ['news', 'site_content_items', 'job_vacancies', 'spmb_registrations'];
    $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME IN ($placeholders)");
    $statement->execute(array_merge([DB_NAME], $requiredTables));
    $checks['schema'] = (int) $statement->fetchColumn() === count($requiredTables);
} catch (Throwable $exception) {
    error_log('[health] Dependency check failed (' . get_class($exception) . ').');
}

$healthy = !in_array(false, $checks, true);
http_response_code($healthy ? 200 : 503);
echo json_encode(['status' => $healthy ? 'ok' : 'unavailable', 'checks' => $checks], JSON_UNESCAPED_SLASHES);
