<?php
// api.php - Este archivo servirá como tu API RESTful para datos en JSON

// --- INICIO: LÍNEAS PARA DEPURACIÓN (ELIMINAR EN PRODUCCIÓN) ---
error_reporting(E_ALL); // Reporta todos los errores de PHP
ini_set('display_errors', 1); // Muestra los errores en el navegador
// --- FIN: LÍNEAS PARA DEPURACIÓN ---

// Incluir la conexión a la base de datos
include 'conexion.php'; // Asegúrate que conexion.php esté en la misma carpeta

// Establecer la cabecera para que la respuesta sea JSON
header('Content-Type: application/json');

// Función para enviar una respuesta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}


$resource = $_GET['resource'] ?? '';
$action = $_GET['action'] ?? ''; 

switch ($resource) {
    case 'usuarios':
        if ($action == 'get_by_id' && isset($_GET['id'])) {
            $id_usuario = $_GET['id'];
            
            $stmt = $conn->prepare("CALL SP_ObtenerDetallesUsuario(?)");
            if ($stmt) {
                $stmt->bind_param("s", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();

                if ($user_data) {
                    sendJsonResponse(['success' => true, 'data' => $user_data]);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Error al preparar la consulta del usuario.'], 500);
            }
        } else if ($action == 'puntos' && isset($_GET['id'])) {
            $id_usuario = $_GET['id'];
            $stmt = $conn->prepare("CALL SP_ObtenerDetallesUsuario(?)");
            if ($stmt) {
                $stmt->bind_param("s", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();

                if ($user_data) {
                    sendJsonResponse(['success' => true, 'puntos_acumulados' => $user_data['puntos_acumulados']]);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Usuario no encontrado o sin puntos.'], 404);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Error al preparar la consulta de puntos.'], 500);
            }
        }
        
        break;

    case 'recolecciones':
        if ($action == 'pendientes') {
            
            $stmt = $conn->prepare("CALL SP_ObtenerRecoleccionesPendientes()");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $recolecciones = [];
                while ($row = $result->fetch_assoc()) {
                    $recolecciones[] = $row;
                }
                $stmt->close();
                sendJsonResponse(['success' => true, 'data' => $recolecciones]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Error al obtener recolecciones pendientes.'], 500);
            }
        }
        
        break;

    case 'actividades':
        if ($action == 'listar') {
            
            $stmt = $conn->prepare("CALL SP_ListarActividades()");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $actividades = [];
                while ($row = $result->fetch_assoc()) {
                    $actividades[] = $row;
                }
                $stmt->close();
                sendJsonResponse(['success' => true, 'data' => $actividades]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Error al listar actividades.'], 500);
            }
        }
        break;

    case 'premios':
        if ($action == 'listar') {
            
            $stmt = $conn->prepare("CALL SP_ListarPremiosDisponibles()");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $premios = [];
                while ($row = $result->fetch_assoc()) {
                    $premios[] = $row;
                }
                $stmt->close();
                sendJsonResponse(['success' => true, 'data' => $premios]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Error al listar premios.'], 500);
            }
        }
        break;

    default:
        
        sendJsonResponse(['success' => false, 'message' => 'Endpoint no válido o recurso no especificado.'], 400);
        break;
}


if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>