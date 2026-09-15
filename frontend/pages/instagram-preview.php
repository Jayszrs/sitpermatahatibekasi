<?php
require_once __DIR__ . '/../../backend/helpers/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=1800, stale-while-revalidate=3600');

$postUrl = trim((string) ($_GET['url'] ?? ''));
if (!instagram_embed_url($postUrl)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'URL Instagram tidak valid.']);
    exit;
}

$media = instagram_public_media($postUrl);
if (!$media) {
    // Bukan error aplikasi: Instagram kadang membatasi preview sebuah post.
    // Tetap 200 agar fallback visual tidak memenuhi console dengan 404.
    echo json_encode(['ok' => false, 'message' => 'Preview belum tersedia.']);
    exit;
}

echo json_encode(['ok' => true, 'media' => $media], JSON_UNESCAPED_SLASHES);
