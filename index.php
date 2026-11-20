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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = $conn->real_escape_string($_POST['nombre']);
    $correo   = $conn->real_escape_string($_POST['correo']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $interes  = $conn->real_escape_string($_POST['interes']);

    // Inserción directa solo con las columnas que existen en tu tabla de MariaDB
    $sql_insert = "INSERT INTO usuarios (nombre, correo, telefono, interes) VALUES ('$nombre', '$correo', '$telefono', '$interes')";
    $conn->query($sql_insert);
    
    header("Location: index.php");
    exit();
}

$sql = "SELECT * FROM usuarios";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataSync Pro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; }
        header { background: #1e3a8a; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        nav a { color: white; text-decoration: none; margin-left: 20px; font-size: 0.95rem; }
        .hero { background: linear-gradient(135deg, #1e3a8a, #0d9488); color: white; text-align: center; padding: 2rem 1rem; }
        .hero h1 { font-size: 1.8rem; margin-bottom: 5px; }
        .hero p { font-size: 0.9rem; }

        .container { display: flex; justify-content: center; align-items: flex-start; gap: 20px; padding: 25px 15px; flex-wrap: wrap; max-width: 1300px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex: 1; min-width: 320px; max-width: 480px; }
        .card-table { max-width: 700px; }
        .card h2 { color: #0d9488; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; color: #555; font-weight: 500; font-size: 0.85rem; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #0d9488; }

        .btn-submit { background: #0d9488; color: white; border: none; padding: 10px; width: 100%; border-radius: 6px; font-size: 0.95rem; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #0f766e; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { padding: 10px 6px; text-align: left; font-size: 0.85rem; }
        th { color: #333; border-bottom: 2px solid #e2e8f0; }
        tr:nth-child(even) { background-color: #f8fafc; }

        .btn-action { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 4px; border: none; color: white; cursor: pointer; text-decoration: none; margin-right: 2px; }
        .btn-email { background-color: #0091ff; }
        .btn-email:hover { background-color: #007ddb; }
        .btn-delete { background-color: #ff3b30; }
        .btn-delete:hover { background-color: #e02d22; }
    </style>
</head>
<body>

    <header>
        <div class="logo">✦ DataSync Pro</div>
        <nav>
            <a href="#">Inicio</a>
            <a href="#">Registro</a>
            <a href="#">Gestión de Datos</a>
        </nav>
    </header>

    <div class="hero">
        <h1>Sistema de Registro y Envío Manual</h1>
        <p>Almacena los datos y envía el correo únicamente al hacer clic en el botón correspondiente.</p>
    </div>

    <div class="container">
        <!-- Columna Izquierda: Formulario -->
        <div class="card">
            <h2>📝 Nuevo Registro</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" placeholder="Escribe tu nombre" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" placeholder="correo@example.com" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" placeholder="10 dígitos" required>
                </div>
                <div class="form-group">
                    <label>Área de Interés</label>
                    <select name="interes" required>
                        <option value="">Selecciona una opción</option>
                        <option value="Base de Datos">Base de Datos</option>
                        <option value="Sistemas">Sistemas</option>
                        <option value="Redes">Redes</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">GUARDAR REGISTRO</button>
            </form>
        </div>

        <!-- Columna Derecha: Tabla -->
        <div class="card card-table">
            <h2>📑 Usuarios Registrados (Servidor)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Interés</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['nombre']; ?></td>
                                <td><?php echo $row['correo']; ?></td>
                                <td><?php echo $row['telefono']; ?></td>
                                <td><?php echo $row['interes']; ?></td>
                                <td>
                                    <a href="enviar_correo.php?id=<?php echo $row['id']; ?>" class="btn-action btn-email" title="Enviar correo">✉</a>
                                    <a href="eliminar.php?id=<?php echo $row['id']; ?>" class="btn-action btn-delete" title="Eliminar">🗑</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No hay registros disponibles.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
