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

app_load_environment(dirname(__DIR__, 2) . '/.env');
