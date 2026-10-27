<?php
require_once __DIR__ . '/db.php';

function brevo_smtp_send(string $to, string $subject, string $body): bool
{
    $host = getenv('BREVO_SMTP_HOST') ?: 'smtp-relay.brevo.com';
    $port = (int)(getenv('BREVO_SMTP_PORT') ?: 587);
    $user = (string)getenv('BREVO_SMTP_USER');
    $password = (string)getenv('BREVO_SMTP_PASSWORD');
    $from = (string)(getenv('BREVO_ATCRAFT_FROM_EMAIL') ?: $user);
    if ($user === '' || $password === '') {
        return false;
    }

    $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $error, 15);
    if (!$socket) {
        return false;
    }
    stream_set_timeout($socket, 15);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    };
    $command = static function (string $value) use ($socket, $read): bool {
        fwrite($socket, $value . "\r\n");
        $response = $read();
        return isset($response[0]) && in_array($response[0], ['2', '3'], true);
    };

    $greeting = $read();
    if (!str_starts_with($greeting, '2')) { fclose($socket); return false; }
    if (!$command('EHLO atcraft.com.ua')) { fclose($socket); return false; }
    if (!$command('STARTTLS')) { fclose($socket); return false; }
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($socket); return false; }
    if (!$command('EHLO atcraft.com.ua')) { fclose($socket); return false; }
    if (!$command('AUTH LOGIN')) { fclose($socket); return false; }
    if (!$command(base64_encode($user))) { fclose($socket); return false; }
    if (!$command(base64_encode($password))) { fclose($socket); return false; }
    if (!$command('MAIL FROM:<' . $from . '>')) { fclose($socket); return false; }
    if (!$command('RCPT TO:<' . $to . '>')) { fclose($socket); return false; }
    if (!$command('DATA')) { fclose($socket); return false; }

    $headers = [
        'From: aTcraft <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
    $sent = str_starts_with($read(), '2');
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return $sent;
}

$email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$redirect = '/?subscribe=';

if (!$email) {
    header('Location: ' . $redirect . 'error#footer');
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
$link = atcraft_db_connect();
if (!$link) {
    header('Location: ' . $redirect . 'error#footer');
    exit;
}

$create = mysqli_query($link, 'CREATE TABLE IF NOT EXISTS subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$stmt = $create ? mysqli_prepare($link, 'INSERT INTO subscribers (email) VALUES (?)') : false;
$result = false;
$duplicate = false;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $email);
    $result = mysqli_stmt_execute($stmt);
    $duplicate = mysqli_stmt_errno($stmt) === 1062;
    mysqli_stmt_close($stmt);
}
mysqli_close($link);

if ($result) {
    brevo_smtp_send(
        $email,
        'Подписка на обновления aTcraft',
        "Дякуємо за підписку на оновлення aTcraft!\n\nМи повідомимо вас про важливі новини сайту."
    );
    header('Location: ' . $redirect . 'ok#footer');
} elseif (!empty($duplicate)) {
    header('Location: ' . $redirect . 'exists#footer');
} else {
    header('Location: ' . $redirect . 'error#footer');
}
exit;
