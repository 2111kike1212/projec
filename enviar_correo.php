<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'gondola.proxy.rlwy.net';
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'CfnqFJthAzWFYgNgDDmrmNMWyjClhaHv';
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 55448);

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM usuarios WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'enriquedejesusxd@gmail.com';
            $mail->Password   = 'zxnr vwep awzg vicu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Remitente (debe ser el mismo correo de arriba) y Destinatario
            $mail->setFrom('enriquedejesusxd@gmail.com', 'DataSync Pro');
            $mail->addAddress($row['correo'], $row['nombre']);

            // Contenido del correo corregido
            $mail->isHTML(true);
            $mail->Subject = 'Notificacion desde DataSync Pro';
            $mail->Body    = "Hola <b>{$row['nombre']}</b>,<br><br>Este es el mensaje enviado desde el sistema DataSync Pro.<br>Area de interes registrada: <b>{$row['interes']}</b><br><br>¡Gracias por utilizar nuestro sistema!";

            $mail->send();
        } catch (Exception $e) {
            echo "Error detallado de PHPMailer: {$mail->ErrorInfo}";
            exit();
        }
    }
}

header("Location: index.php");
exit();
?>
