<?php

session_start();
include 'conexion.php'; 

header('Content-Type: application/json'); 

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function registrar_auditoria($conn, $id_usuario_afectado, $accion, $detalles, $tabla_modificada = NULL, $id_registro_modificado = NULL, $ip_cliente = NULL) {
    $ip_origen = $ip_cliente ?? ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_PHP_IP');
    $null_id_usuario_afectado = $id_usuario_afectado === null ? null : (string)$id_usuario_afectado;

    $stmt = $conn->prepare("INSERT INTO auditoria (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssss", $null_id_usuario_afectado, $accion, $tabla_modificada, $id_registro_modificado, $detalles, $ip_origen);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Error al preparar la auditoría en canjear_premio.php: " . $conn->error);
    }
}


if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Usuario no autenticado.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $id_premio = $_POST['id_premio'] ?? null;
    $puntos_premio = $_POST['puntos_requeridos'] ?? null;
    $ip_cliente = $_POST['ip_publica'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    $nombre_premio = $_POST['nombre_premio'] ?? 'Premio Desconocido'; 

    if ($id_premio === null || $puntos_premio === null) {
        sendJsonResponse(['success' => false, 'message' => 'ID de premio o puntos del premio no proporcionados.'], 400);
    }

    $id_premio = (int)$id_premio;
    $puntos_premio = (int)$puntos_premio;

    try {
        $stmt = $conn->prepare("CALL SP_CanjearPremio(?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar la llamada al SP_CanjearPremio: " . $conn->error);
        }
        $stmt->bind_param("iiis", $user_id, $id_premio, $puntos_premio, $ip_cliente);

        if (!$stmt->execute()) {
            $error_message = $stmt->error;
            if (strpos($error_message, 'Puntos insuficientes') !== false) {
                 throw new Exception("Puntos insuficientes para canjear este premio.");
            }
            throw new Exception("Error al ejecutar el canje del premio: " . $error_message);
        }

        $stmt->close(); 

        $stmt_new_points = $conn->prepare("SELECT puntos_acumulados FROM usuarios WHERE id_usuario = ?");
        $stmt_new_points->bind_param("i", $user_id);
        $stmt_new_points->execute();
        $result_new_points = $stmt_new_points->get_result();
        $user_data = $result_new_points->fetch_assoc();
        $stmt_new_points->close();

        $new_points = $user_data['puntos_acumulados'] ?? 0;

        sendJsonResponse(['success' => true, 'message' => '¡Premio canjeado con éxito! Tus puntos han sido actualizados.', 'new_points' => $new_points]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error al procesar el canje: ' . $e->getMessage()], 500);
    } finally {
        if ($conn) $conn->close();
    }
} else {
    sendJsonResponse(['success' => false, 'message' => 'Método de solicitud no permitido.'], 405);
}
?>