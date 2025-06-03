<?php
session_start();
include 'conexion.php';

$error_message = "";
$email = '';
$password = '';


function registrar_auditoria($conn, $id_usuario_afectado, $accion, $detalles, $tabla_modificada = NULL, $id_registro_modificado = NULL, $ip_cliente = NULL) {
    $ip_origen = $ip_cliente ?? ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_PHP_IP');
    // Eliminamos $user_agent

    $null_id_usuario_afectado = $id_usuario_afectado === null ? null : (string)$id_usuario_afectado;

    $stmt = $conn->prepare("INSERT INTO auditoria (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssss", $null_id_usuario_afectado, $accion, $tabla_modificada, $id_registro_modificado, $detalles, $ip_origen);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Error al preparar la auditoría en login.php: " . $conn->error);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip_publica_cliente = $_POST['ip_publica'] ?? null; 

    if (empty($email) || empty($password)) {
        $error_message = "Por favor, ingresa tu correo y contraseña.";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario, nombre, email, contrasena_hash, rol, estado, intentos_fallidos, bloqueo_hasta FROM usuarios WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if ($user['bloqueo_hasta'] && strtotime($user['bloqueo_hasta']) > time()) {
                    $tiempo_restante = round((strtotime($user['bloqueo_hasta']) - time()) / 60); // en minutos
                    $error_message = "Tu cuenta está bloqueada. Intenta de nuevo en " . $tiempo_restante . " minutos.";
                    registrar_auditoria($conn, $user['id_usuario'], 'INTENTO DE LOGIN (CUENTA BLOQUEADA)', 'Intento de inicio de sesión fallido en cuenta bloqueada.', 'usuarios', $user['id_usuario'], $ip_publica_cliente);
                } else {
                    if (password_verify($password, $user['contrasena_hash'])) {
                        if ($user['intentos_fallidos'] > 0 || ($user['bloqueo_hasta'] && strtotime($user['bloqueo_hasta']) <= time())) {
                            $update_stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueo_hasta = NULL, ultimo_intento_fallido = NULL, estado = 'activo' WHERE id_usuario = ?");
                            if ($update_stmt) {
                                $update_stmt->bind_param("s", $user['id_usuario']);
                                $update_stmt->execute();
                                $update_stmt->close();
                            }
                        }

                        $_SESSION['user_id'] = $user['id_usuario'];
                        $_SESSION['user_name'] = $user['nombre'];
                        $_SESSION['user_role'] = $user['rol'];
                        $_SESSION['user_email'] = $user['email'];

                        registrar_auditoria($conn, $user['id_usuario'], 'LOGIN EXITOSO', 'El usuario ha iniciado sesión correctamente.', 'usuarios', $user['id_usuario'], $ip_publica_cliente);

                   
                        if ($user['rol'] === 'administrador') {
                            header("Location: admin.php");
                        } else {
                            header("Location: usu.php");
                        }
                        exit();

                    } else {
                    
                        $max_intentos = 3; 
                        $bloqueo_minutos = 5; 

                        $new_intentos = $user['intentos_fallidos'] + 1;
                        $update_stmt_fail = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ?, ultimo_intento_fallido = NOW() WHERE id_usuario = ?");
                        if ($update_stmt_fail) {
                            $update_stmt_fail->bind_param("is", $new_intentos, $user['id_usuario']);
                            $update_stmt_fail->execute();
                            $update_stmt_fail->close();
                        }

                        if ($new_intentos >= $max_intentos) {
                            $bloqueo_hasta = date('Y-m-d H:i:s', strtotime("+$bloqueo_minutos minutes"));
                            $update_stmt_block = $conn->prepare("UPDATE usuarios SET bloqueo_hasta = ?, estado = 'bloqueado' WHERE id_usuario = ?");
                            if ($update_stmt_block) {
                                $update_stmt_block->bind_param("ss", $bloqueo_hasta, $user['id_usuario']);
                                $update_stmt_block->execute();
                                $update_stmt_block->close();
                            }
                            $error_message = "Contraseña incorrecta. Tu cuenta ha sido bloqueada por " . $bloqueo_minutos . " minutos debido a demasiados intentos fallidos.";
                            
                            registrar_auditoria($conn, $user['id_usuario'], 'CUENTA BLOQUEADA (INTENTOS FALLIDOS)', 'Cuenta bloqueada automáticamente por exceso de intentos fallidos.', 'usuarios', $user['id_usuario'], $ip_publica_cliente);
                        } else {
                            $intentos_restantes = $max_intentos - $new_intentos;
                            $error_message = "Contraseña incorrecta. Te quedan " . $intentos_restantes . " intentos.";
                        }
                      
                        registrar_auditoria($conn, $user['id_usuario'], 'LOGIN FALLIDO (CREDENCIALES INCORRECTAS)', 'Intento de inicio de sesión fallido por credenciales incorrectas.', 'usuarios', $user['id_usuario'], $ip_publica_cliente);
                    }
                }
            } else {
                
                $error_message = "Correo electrónico o contraseña incorrectos.";
                registrar_auditoria($conn, NULL, 'LOGIN FALLIDO (EMAIL NO EXISTENTE)', 'Intento de inicio de sesión con un correo electrónico no registrado: ' . htmlspecialchars($email), NULL, NULL, $ip_publica_cliente);
            }
            $stmt->close();
        } else {
            $error_message = "Error en la preparación de la consulta.";
            error_log("Error en login.php: " . $conn->error);
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - ReciclApp</title>
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
                <h2>Iniciar Sesión</h2>
                <p>Ingresa tus credenciales para acceder</p>
            </div>

            <form class="auth-form" method="POST" action="login.php">

                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <input type="hidden" id="ip_publica" name="ip_publica" value="">

                <button type="submit" class="auth-btn">Iniciar Sesión</button>
            </form>
            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
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