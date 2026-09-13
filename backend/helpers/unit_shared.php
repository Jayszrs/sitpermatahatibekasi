<?php
/**
 * Jembatan dari portal unit (daycare/tkit/sdit/smpit, yang biasanya hanya
 * memakai database unit_db()/school_units_portal) ke database utama yayasan,
 * dipakai untuk fitur yang datanya memang harus tunggal/gabungan lintas unit
 * (SPMB -> spmb_registrations, Karir -> job_vacancies/job_applications).
 *
 * require_once dipanggil di dalam fungsi ini (bukan di top-level file unit)
 * supaya variabel $pdo yang didefinisikan backend/config/database.php tetap
 * lokal terhadap fungsi ini dan tidak menimpa $pdo milik unit_db() di scope
 * pemanggil.
 */
function unit_connect_main_site_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/spmb_shared.php';
    return $pdo;
}
