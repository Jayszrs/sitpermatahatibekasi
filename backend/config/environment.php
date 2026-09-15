<?php

/**
 * Loads the local .env file without overriding variables injected by Railway.
 */
function app_load_environment(string $path): void
{
    if (!is_file($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '' || getenv($name) !== false) continue;

        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function app_env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value === false && array_key_exists($name, $_ENV)) $value = $_ENV[$name];
    if ($value === false || $value === null || trim((string) $value) === '') return $default;
    return (string) $value;
}

function app_request_is_secure(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/**
 * Baseline header keamanan untuk website publik dan portal.
 *
 * CSP tetap mengizinkan media Instagram, video YouTube, Google Maps, dan font
 * yang memang dipakai antarmuka. Fitur browser yang tidak dibutuhkan sekolah
 * ditutup tanpa memakai directive lama seperti `unload` atau
 * `attribution-reporting` yang sebelumnya memenuhi console dengan warning.
 */
function app_send_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' https://www.instagram.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: https:; media-src 'self' blob: https:; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://www.openstreetmap.org https://www.instagram.com; connect-src 'self' https://www.instagram.com https://graph.instagram.com https://*.cdninstagram.com https://*.fbcdn.net");

    if (app_request_is_secure()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

app_load_environment(dirname(__DIR__, 2) . '/.env');
app_send_security_headers();
