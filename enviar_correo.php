<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'gondola.proxy.rlwy.net';
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'CfnqFJthAzWFYgNgDDmrmNMWyjClhaHv';
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 55448);

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$smtpHost = trim(getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'smtp.gmail.com'));
$smtpPort = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587));
$smtpUser = trim(getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? ''));
$smtpPass = str_replace(' ', '', trim(getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? '')));

if ($smtpUser === '' || $smtpPass === '') {
    die("Error: Configura SMTP_USER y SMTP_PASS en las variables de entorno de Render.");
}

function enviarGmailSMTP($host, $port, $user, $pass, $toEmail, $toName, $subject, $htmlBody) {
    $readCmd = function ($fp) {
        $data = '';
        while ($line = fgets($fp, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCmd = function ($fp, $cmd, $expectedCode) use ($readCmd) {
        fwrite($fp, $cmd . "\r\n");
        $resp = $readCmd($fp);
        $code = (int)substr(trim($resp), 0, 3);
        if ($code !== $expectedCode) {
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
    $fp = @stream_socket_client($transport, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        throw new Exception("No se pudo conectar a {$transport}: {$errstr} ({$errno})");
    }

    stream_set_timeout($fp, 15);
    $greeting = $readCmd($fp);
    if ((int)substr(trim($greeting), 0, 3) !== 220) {
        fclose($fp);
        throw new Exception("Saludo SMTP inválido: {$greeting}");
    }

    try {
        $hostname = gethostname() ?: 'localhost';
        $sendCmd($fp, "EHLO {$hostname}", 250);

        if ($port !== 465) {
            $sendCmd($fp, "STARTTLS", 220);
            $cryptoOk = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$cryptoOk) {
                throw new Exception("No se pudo iniciar cifrado TLS con Gmail.");
            }
            $sendCmd($fp, "EHLO {$hostname}", 250);
        }

        $sendCmd($fp, "AUTH LOGIN", 334);
        $sendCmd($fp, base64_encode($user), 334);
        $sendCmd($fp, base64_encode($pass), 235);

        $sendCmd($fp, "MAIL FROM:<{$user}>", 250);
        $sendCmd($fp, "RCPT TO:<{$toEmail}>", 250);
        $sendCmd($fp, "DATA", 354);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $safeToName = str_replace(["\r", "\n", '"'], '', $toName);

        $headers  = "Date: " . date('r') . "\r\n";
        $headers .= "From: DataSync Pro <{$user}>\r\n";
        $headers .= "To: \"{$safeToName}\" <{$toEmail}>\r\n";
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        $bodyB64 = chunk_split(base64_encode($htmlBody));
        $message = $headers . "\r\n" . $bodyB64 . "\r\n.";

        $sendCmd($fp, $message, 250);
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
    } catch (Exception $e) {
        fclose($fp);
        throw $e;
    }
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT nombre, correo, interes FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $toEmail = trim($row['correo']);
        $toName  = trim($row['nombre']);
        $interes = trim($row['interes']);
        $subject = 'Notificacion desde DataSync Pro';
        $html    = "Hola <b>" . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . "</b>,<br><br>" .
                   "Este es el mensaje enviado desde el sistema DataSync Pro.<br>" .
                   "Area de interes registrada: <b>" . htmlspecialchars($interes, ENT_QUOTES, 'UTF-8') . "</b><br><br>" .
                   "¡Gracias por utilizar nuestro sistema!";

        try {
            enviarGmailSMTP($smtpHost, $smtpPort, $smtpUser, $smtpPass, $toEmail, $toName, $subject, $html);
        } catch (Exception $e) {
            echo "Error al enviar correo: " . htmlspecialchars($e->getMessage());
            exit();
        }
    }
    $stmt->close();
}

header("Location: index.php");
exit();
?>
