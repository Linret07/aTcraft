<?php
// Fixed-target same-origin proxy for the HTTP BlueMap server.
$path = (string)($_GET['path'] ?? '/');
if ($path === '' || $path[0] !== '/' || str_contains($path, "\0") || str_contains($path, '..')) {
    http_response_code(400);
    exit('Invalid map path');
}

$query = $_GET;
unset($query['path']);
$queryString = http_build_query($query);
$target = 'http://77.120.47.16:8100' . $path . ($queryString !== '' ? '?' . $queryString : '');
$responseHeaders = [];
$curl = curl_init($target);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_ENCODING => '',
    CURLOPT_HEADERFUNCTION => static function ($curl, $header) use (&$responseHeaders) {
        $length = strlen($header);
        $header = trim($header);
        if ($header === '' || str_starts_with($header, 'HTTP/')) return $length;
        [$name, $value] = array_pad(explode(':', $header, 2), 2, '');
        $name = strtolower(trim($name));
        if (in_array($name, ['content-type', 'cache-control', 'etag', 'last-modified'], true)) {
            $responseHeaders[$name] = trim($value);
        }
        return $length;
    },
]);
$body = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$error = curl_error($curl);
curl_close($curl);
if ($body === false) {
    http_response_code(502);
    exit('Map server unavailable: ' . $error);
}
http_response_code($status > 0 ? $status : 200);
foreach ($responseHeaders as $name => $value) header($name . ': ' . $value);
echo $body;
