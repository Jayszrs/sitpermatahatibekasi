<?php

$databaseName = getenv('DB_NAME') ?: '';
if (!preg_match('/^codex_railway_[a-z0-9_]+$/', $databaseName)) {
    fwrite(STDERR, "Refusing to run outside a codex_railway_* disposable database.\n");
    exit(2);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
require dirname(__DIR__, 2) . '/backend/config/database.php';
require dirname(__DIR__, 2) . '/backend/helpers/functions.php';
require dirname(__DIR__, 2) . '/backend/auth.php';

$requiredTables = ['news', 'site_content_items', 'job_vacancies', 'portal_users', 'spmb_registrations'];
foreach ($requiredTables as $table) {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->execute([$table]);
    if ((int) $statement->fetchColumn() !== 1) throw new RuntimeException('Missing expected table: ' . $table);
}
if ((int) $pdo->query('SELECT COUNT(*) FROM job_vacancies')->fetchColumn() < 1) throw new RuntimeException('Demo vacancy seed is missing.');
if ((int) $pdo->query('SELECT COUNT(*) FROM portal_users')->fetchColumn() < 3) throw new RuntimeException('Demo role seed is missing.');

echo "main bootstrap ok\n";
