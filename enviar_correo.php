<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'gondola.proxy.rlwy.net');
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'CfnqFJthAzWFYgNgDDmrmNMWyjClhaHv');
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'railway');
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 55448));

$conn = new mysqli($host, $user,$pass, $db,$port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$smtpHost = trim(getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'smtp.gmail.com'));
$smtpPort = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587));
$smtpUser = trim(getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? ''));
$smtpPass = str_replace(' ', '', trim(getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? '')));

if ($smtpUser === '' \vert{}\vert{}$smtpPass === '') {
    die("Error: Configura SMTP_USER y SMTP_PASS en las variables de entorno de Render.");
}

function enviarGmailSMTP($host, $port,$user, $pass,$toEmail, $toName,$subject, $htmlBody) {$readCmd = function ($fp) {$data = '';
        while ($line = fgets($fp, 515)) {
            $data .=$line;
            if (isset($line[3]) &&$line[3] === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCmd = function ($fp,$cmd, $expectedCode) use ($readCmd) {
        fwrite($fp,$cmd . "\r\n");
        $resp = $readCmd($fp);
        $code = (int)substr(trim($resp), 0, 3);
        if ($code !==$expectedCode) {
            throw new Exception("SMTP error en '{$cmd}': {$resp}");
        }
        return $resp;
    };

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]
    ]);

    $transport = ($port === 465) ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
    $fp = @stream_socket_client($transport,$errno, $errstr, 15, STREAM_CLIENT_CONNECT,$ctx);
    if (!$fp) {
        throw new Exception("No se pudo conectar a {$transport}: {$errstr} ({$errno})");
    }

    stream_set_timeout($fp, 15);$greeting = $readCmd($fp);
    if ((int)substr(trim($greeting), 0, 3) !== 220) {
        fclose($fp);
        throw new Exception("Saludo SMTP inválido: {$greeting}");
    }
