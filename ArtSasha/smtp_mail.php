<?php
function sendSmtpEmail(string $to, string $subject, string $message, bool $html = false, string $fromEmailOverride = '', string $fromNameOverride = ''): bool
{
    $host = getenv('BREVO_SMTP_HOST') ?: 'smtp-relay.brevo.com';
    $port = (int)(getenv('BREVO_SMTP_PORT') ?: 587);
    $username = (string)(getenv('BREVO_SMTP_USER') ?: '');
    $password = (string)(getenv('BREVO_SMTP_PASSWORD') ?: '');
    $fromEmail = $fromEmailOverride ?: (getenv('BREVO_ARTSASHA_FROM_EMAIL') ?: (getenv('BREVO_SMTP_FROM_EMAIL') ?: $username));
    $fromName = $fromNameOverride ?: (getenv('BREVO_ARTSASHA_FROM_NAME') ?: 'ArtSasha');

    if ($username === '' || $password === '' || $fromEmail === '') return false;
    $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $error, 15);
    if (!$socket) return false;
    stream_set_timeout($socket, 15);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    };
    $send = static function (string $command) use ($socket, $read): bool {
        fwrite($socket, $command . "\r\n");
        $response = $read();
        return isset($response[0]) && in_array($response[0], ['2', '3'], true);
    };

    if (!str_starts_with($read(), '2') || !$send('EHLO artsasha.atcraft.com.ua') || !$send('STARTTLS')) {
        fclose($socket); return false;
    }
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$send('EHLO artsasha.atcraft.com.ua') || !$send('AUTH LOGIN') || !$send(base64_encode($username)) || !$send(base64_encode($password)) || !$send('MAIL FROM:<' . $fromEmail . '>') || !$send('RCPT TO:<' . $to . '>') || !$send('DATA')) {
        fclose($socket); return false;
    }

    $headers = [
        'From: ' . '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: ' . ($html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.\r\n");
    $sent = str_starts_with($read(), '2');
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return $sent;
}
