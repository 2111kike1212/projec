<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'gondola.proxy.rlwy.net');
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'CfnqFJthAzWFYgNgDDmrmNMWyjClhaHv');
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'railway');
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 55448));

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$resendApiKey = trim(getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? ''));
$fromEmail = trim(getenv('MAIL_FROM') ?: ($_ENV['MAIL_FROM'] ?? 'DataSync Pro <onboarding@resend.dev>'));

if ($resendApiKey === '') {
    die("Error: Configura RESEND_API_KEY en las variables de entorno de Render.");
}

function enviarCorreoResend($apiKey, $from, $toEmail, $subject, $htmlBody) {
    $payload = json_encode([
        'from' => $from,
        'to' => [$toEmail],
        'subject' => $subject,
        'html' => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Error cURL: " . $curlErr);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Resend HTTP {$httpCode}: " . $response);
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
            enviarCorreoResend($resendApiKey, $fromEmail, $toEmail, $subject, $html);
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
