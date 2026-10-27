<?php
/** Local configuration for ArtSasha. Secrets belong in .env, never in Git. */
function artsasha_env(string $key, string $default = ''): string
{
    static $values;

    if ($values === null) {
        $values = [];
        $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                $separator = strpos($line, '=');
                if ($separator === false) continue;
                $name = trim(substr($line, 0, $separator));
                $value = trim(substr($line, $separator + 1));
                if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                    $value = substr($value, 1, -1);
                }
                $values[$name] = $value;
            }
        }
    }

    $systemValue = getenv($key);
    return $systemValue !== false ? (string)$systemValue : ($values[$key] ?? $default);
}
