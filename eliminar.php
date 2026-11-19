<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "applocal_db";

$conn = new mysqli($host, $user, $pass, $db);

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
