<?php

$databaseName = getenv('UNIT_DB_NAME') ?: '';
$unit = $argv[1] ?? '';
if (!preg_match('/^codex_railway_[a-z0-9_]+$/', $databaseName)) {
    fwrite(STDERR, "Refusing to run outside a codex_railway_* disposable database.\n");
    exit(2);
}
if (!in_array($unit, ['daycare', 'tkit', 'sdit', 'smpit'], true)) {
    fwrite(STDERR, "Unknown unit.\n");
    exit(2);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
require dirname(__DIR__, 2) . '/' . $unit . '/bootstrap.php';
$pdo = unit_db();

foreach (['unit_users', 'unit_settings', 'unit_content', 'unit_gallery_albums', 'unit_gallery_photos', 'unit_enrollments'] as $table) {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->execute([$table]);
    if ((int) $statement->fetchColumn() !== 1) throw new RuntimeException('Missing expected table: ' . $table);
}
$seed = $pdo->prepare('SELECT COUNT(*) FROM unit_content WHERE unit_slug=?');
$seed->execute([$unit]);
if ((int) $seed->fetchColumn() < 1) throw new RuntimeException('Unit seed is missing: ' . $unit);

echo $unit . " bootstrap ok\n";
