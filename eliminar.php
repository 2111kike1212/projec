<?php
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
    $sql = "DELETE FROM usuarios WHERE id = $id";
    $conn->query($sql);
}

header("Location: index.php");
exit();
?>
