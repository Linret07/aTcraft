<?php
// Allow TurboWarp to fetch the Scratch project file from another origin.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$filePath = __DIR__ . '/game.sb3';

if (!is_file($filePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Game file not found.';
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=3600');

readfile($filePath);
exit;
