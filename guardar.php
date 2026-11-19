<?php
$conexion = new mysqli("localhost", "remoto", "1234", "applocal_db");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$interes = $_POST['interes'];

$sql = "INSERT INTO usuarios (nombre, correo, telefono, interes) VALUES ('$nombre', '$correo', '$telefono', '$interes')";

if ($conexion->query($sql) === TRUE) {
    header("Location: index.php"); // Te regresa a la página principal
} else {
    echo "Error: " . $sql . "<br>" . $conexion->error;
}

$conexion->close();
?>
