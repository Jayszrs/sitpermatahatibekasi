<?php

function admin_audit_schema(PDO $pdo): void
{
    static $ready = [];
    if (isset($ready[spl_object_id($pdo)])) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, actor_scope VARCHAR(12) NOT NULL,
        actor_id INT NULL, actor_username VARCHAR(80) NOT NULL, actor_role VARCHAR(60) NOT NULL,
        unit_slug VARCHAR(24) NULL, action VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL,
        outcome VARCHAR(12) NOT NULL DEFAULT 'success', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        legacy_portal_id BIGINT NULL UNIQUE,
        INDEX audit_date(created_at,id), INDEX audit_unit(unit_slug,created_at),
        INDEX audit_actor(actor_scope,actor_username,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready[spl_object_id($pdo)] = true;
}

function admin_audit(PDO $pdo, string $scope, ?array $actor, ?string $unit, string $action, string $description, string $outcome = 'success'): void
{
    admin_audit_schema($pdo);
    $pdo->prepare('INSERT INTO admin_audit_events (actor_scope,actor_id,actor_username,actor_role,unit_slug,action,description,outcome) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$scope,$actor['id'] ?? null,mb_substr($actor['username'] ?? '(tidak dikenal)',0,80),
            $actor['role_key'] ?? $actor['role'] ?? '-', $unit,mb_substr($action,0,100),mb_substr($description,0,255),$outcome]);
}

function admin_audit_import_portal(PDO $pdo): void
{
    admin_audit_schema($pdo);
    $pdo->exec("INSERT IGNORE INTO admin_audit_events (actor_scope,actor_id,actor_username,actor_role,action,description,created_at,legacy_portal_id)
        SELECT 'portal',l.user_id,COALESCE(u.username,'(akun dihapus)'),COALESCE(u.role,'-'),l.action,l.description,l.created_at,l.id
        FROM portal_activity_logs l LEFT JOIN portal_users u ON u.id=l.user_id
        LEFT JOIN admin_audit_events a ON a.legacy_portal_id=l.id WHERE a.id IS NULL");
}
