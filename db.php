<?php
function atcraft_db_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'name' => getenv('DB_NAME') ?: 'news',
    ];
}

function atcraft_db_connect(?string $database = null)
{
    if (!function_exists('mysqli_connect')) {
        return null;
    }

    $config = atcraft_db_config();
    $dbName = $database ?: $config['name'];

    return @mysqli_connect($config['host'], $config['user'], $config['password'], $dbName);
}
