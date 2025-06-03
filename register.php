<?php
// register.php
session_start();
include 'conexion.php';

$error_message = "";
$success_message = "";

// Eliminamos la función obtener_user_agent()

// Función para registrar una auditoría (MODIFICADA: Se elimina el parámetro y el uso de navegador_agente)
function registrar_auditoria($conn, $id_usuario_afectado, $accion, $detalles, $tabla_modificada = NULL, $id_registro_modificado = NULL, $ip_cliente = NULL) {
    $ip_origen = $ip_cliente ?? ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_PHP_IP');
    // Eliminamos $user_agent
    
    $null_id_usuario_afectado = $id_usuario_afectado === null ? null : (string)$id_usuario_afectado;

    // Se eliminó 'navegador_agente' de la lista de columnas y de los VALUES
    $stmt = $conn->prepare("INSERT INTO auditoria (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        // Se ajustó el bind_param: se quitó 's' para navegador_agente y el parámetro correspondiente
        $stmt->bind_param("ssssss", $null_id_usuario_afectado, $accion, $tabla_modificada, $id_registro_modificado, $detalles, $ip_origen);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Error al preparar la auditoría en register.php: " . $conn->error);
    }
}


// Procesar el formulario solo si se envía con el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capturamos la IP pública enviada desde JS
    $ip_publica_cliente = $_POST['ip_publica'] ?? null;

    // Validar que los campos no están vacíos
    if (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['email']) || empty($_POST['password'])) {
        $error_message = "Todos los campos son obligatorios.";
    } else {
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Verificar si el correo ya existe para evitar duplicados
        $stmt_check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "El correo electrónico ya está registrado.";
            registrar_auditoria($conn, NULL, 'REGISTRO FALLIDO (EMAIL DUPLICADO)', 'Intento de registro con email ya existente: ' . htmlspecialchars($email), 'usuarios', NULL, $ip_publica_cliente);
        } else {
            // El correo no existe, procedemos a registrar
            
            // Hashear la contraseña por seguridad. NUNCA guardes contraseñas en texto plano.
            $contrasena_hash = password_hash($password, PASSWORD_DEFAULT);
            $rol_default = 'usuario'; // Rol por defecto para nuevos usuarios
            $estado_default = 'activo'; // Estado por defecto

            $stmt_insert = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, contrasena_hash, rol, estado) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                $stmt_insert->bind_param("ssssss", $nombre, $apellido, $email, $contrasena_hash, $rol_default, $estado_default);
                if ($stmt_insert->execute()) {
                    $id_nuevo_usuario = $conn->insert_id; // Obtener el ID del usuario recién insertado
                    $success_message = "¡Registro exitoso! Ya puedes iniciar sesión.";
                    // Registrar auditoría de nuevo usuario
                    registrar_auditoria($conn, $id_nuevo_usuario, 'NUEVO REGISTRO DE USUARIO', 'Usuario ' . htmlspecialchars($email) . ' ha sido registrado con rol ' . $rol_default, 'usuarios', $id_nuevo_usuario, $ip_publica_cliente);

                    // Redirigir al usuario al login después de un breve momento
                    // header("Location: login.php"); // Puedes quitar esta línea si quieres un mensaje y luego redirigir con JS
                    // exit();
                } else {
                    $error_message = "Error al registrar el usuario: " . $stmt_insert->error;
                    registrar_auditoria($conn, NULL, 'ERROR EN REGISTRO DE USUARIO', 'Fallo en la inserción en la tabla usuarios: ' . $stmt_insert->error . ' Email: ' . htmlspecialchars($email), 'usuarios', NULL, $ip_publica_cliente);
                }
                $stmt_insert->close();
            } else {
                $error_message = "Error en la preparación de la consulta de registro: " . $conn->error;
                error_log("Error en register.php: " . $conn->error);
            }
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - ReciclApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login_style.css">
</head>
<body>
    <div class="auth-background"></div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-recycle"></i> ReciclApp
                </div>
                <h2>Regístrate</h2>
                <p>Crea una nueva cuenta para empezar a reciclar</p>
            </div>

            <form class="auth-form" method="POST" action="register.php">
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" placeholder="Tu apellido" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <input type="hidden" id="ip_publica" name="ip_publica" value="">

                <button type="submit" class="auth-btn">Registrarse</button>
            </form>
            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('https://api.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('ip_publica').value = data.ip;
                })
                .catch(error => {
                    console.error('Error al obtener la IP pública:', error);
                    document.getElementById('ip_publica').value = 'API_ERROR';
                });
        });
    </script>
</body>
</html>