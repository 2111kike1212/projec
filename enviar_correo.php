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

$scriptUrl = trim(
    getenv('APPS_SCRIPT_URL') ?: ($_ENV['APPS_SCRIPT_URL'] ?? ($_SERVER['APPS_SCRIPT_URL'] ?? ''))
);

if ($scriptUrl === '') {
    die("Error: APPS_SCRIPT_URL está vacía o no se cargó en Render.");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT nombre, correo, interes FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $payload = [
            'to'      => $row['correo'],
            'subject' => 'Notificacion desde DataSync Pro',
            'html'    => "Hola <b>" . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . "</b>,<br><br>" .
                         "Este es el mensaje enviado desde el sistema DataSync Pro.<br>" .
                         "Area de interes registrada: <b>" . htmlspecialchars($row['interes'], ENT_QUOTES, 'UTF-8') . "</b><br><br>" .
                         "¡Gracias por utilizar nuestro sistema!"
        ];

        $ch = curl_init($scriptUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo "Error de red cURL: " . htmlspecialchars($curlError);
            exit();
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            echo "Error al enviar correo (HTTP {$httpCode}): " . htmlspecialchars($response);
            exit();
        }
    }
    $stmt->close();
}

header("Location: index.php");
exit();
?>
