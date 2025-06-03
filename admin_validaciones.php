<?php
// admin_validaciones.php
$titulo_pagina = "Validación de Recolecciones";
include 'admin_layout.php'; // Incluye el layout principal del administrador
include 'conexion.php';    // Incluye la conexión a la base de datos

// Lógica para manejar la aprobación o rechazo de una recolección
$message = '';
$message_type = ''; // 'success' o 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $id_recoleccion = (int)$_POST['id_recoleccion'];
    $action = htmlspecialchars($_POST['action']); // 'approve' o 'reject'
    $observaciones_admin = isset($_POST['observaciones_admin']) ? htmlspecialchars($_POST['observaciones_admin']) : null;

    $conn->begin_transaction(); // Inicia una transacción para asegurar la consistencia

    try {
        // Obtener la información de la recolección
        $stmt_recoleccion = $conn->prepare("SELECT id_usuario, puntos_ganados, estado FROM recolecciones WHERE id_recoleccion = ? FOR UPDATE"); // FOR UPDATE bloquea la fila
        $stmt_recoleccion->bind_param("i", $id_recoleccion);
        $stmt_recoleccion->execute();
        $result_recoleccion = $stmt_recoleccion->get_result();
        $recoleccion = $result_recoleccion->fetch_assoc();
        $stmt_recoleccion->close();

        if (!$recoleccion) {
            throw new Exception("Recolección no encontrada.");
        }

        if ($recoleccion['estado'] !== 'pendiente') {
            throw new Exception("Esta recolección ya fue procesada.");
        }

        if ($action == 'approve') {
            // 1. Actualizar el estado de la recolección a 'aprobada' y añadir observaciones
            $stmt_update_recoleccion = $conn->prepare("UPDATE recolecciones SET estado = 'aprobada', observaciones_admin = ?, fecha_validacion = NOW() WHERE id_recoleccion = ?");
            $stmt_update_recoleccion->bind_param("si", $observaciones_admin, $id_recoleccion);
            if (!$stmt_update_recoleccion->execute()) {
                throw new Exception("Error al actualizar la recolección: " . $stmt_update_recoleccion->error);
            }
            $stmt_update_recoleccion->close();

            // 2. Sumar los puntos_ganados al usuario
            $puntos_ganados = $recoleccion['puntos_ganados'];
            $id_usuario = $recoleccion['id_usuario'];

            $stmt_update_user_points = $conn->prepare("UPDATE usuarios SET puntos_acumulados = puntos_acumulados + ? WHERE id_usuario = ?");
            $stmt_update_user_points->bind_param("ii", $puntos_ganados, $id_usuario);
            if (!$stmt_update_user_points->execute()) {
                throw new Exception("Error al actualizar puntos del usuario: " . $stmt_update_user_points->error);
            }
            $stmt_update_user_points->close();

            $message = "Recolección aprobada y puntos asignados exitosamente.";
            $message_type = "success";

        } elseif ($action == 'reject') {
            // 1. Actualizar el estado de la recolección a 'rechazada' y añadir observaciones
            $stmt_update_recoleccion = $conn->prepare("UPDATE recolecciones SET estado = 'rechazada', observaciones_admin = ?, fecha_validacion = NOW() WHERE id_recoleccion = ?");
            $stmt_update_recoleccion->bind_param("si", $observaciones_admin, $id_recoleccion);
            if (!$stmt_update_recoleccion->execute()) {
                throw new Exception("Error al actualizar la recolección: " . $stmt_update_recoleccion->error);
            }
            $stmt_update_recoleccion->close();

            $message = "Recolección rechazada exitosamente.";
            $message_type = "success";
        } else {
            throw new Exception("Acción inválida.");
        }

        $conn->commit(); // Confirma la transacción
    } catch (Exception $e) {
        $conn->rollback(); // Deshace la transacción en caso de error
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// --- Lógica para OBTENER las recolecciones para la tabla ---
$recolecciones = [];
$filter_status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'pendiente'; // Default to 'pendiente'
$search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';


$sql = "SELECT r.id_recoleccion, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, 
               r.fecha_recoleccion, r.peso_kg, r.puntos_ganados, r.estado, r.observaciones_usuario, r.observaciones_admin,
               GROUP_CONCAT(tr.nombre SEPARATOR ', ') AS tipos_residuos
        FROM recolecciones r
        JOIN usuarios u ON r.id_usuario = u.id_usuario
        LEFT JOIN detalle_recoleccion dr ON r.id_recoleccion = dr.id_recoleccion
        LEFT JOIN tipos_residuos tr ON dr.id_tipo_residuo = tr.id_tipo_residuo
        WHERE 1=1"; // Clausula para permitir filtros dinámicos

$params = [];
$types = "";

if (!empty($filter_status) && $filter_status !== 'todos') {
    $sql .= " AND r.estado = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " GROUP BY r.id_recoleccion ORDER BY r.fecha_recoleccion DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recolecciones[] = $row;
    }
    $stmt->close();
} else {
    $message = "Error al preparar la consulta de recolecciones: " . $conn->error;
    $message_type = "error";
}

?>

<div class="main-content-inner">
    <h2>Validación de Recolecciones</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="action-bar">
        <form action="admin_validaciones.php" method="GET" class="filter-form">
            <label for="status-filter">Filtrar por Estado:</label>
            <select name="status" id="status-filter" onchange="this.form.submit()">
                <option value="pendiente" <?php echo ($filter_status == 'pendiente') ? 'selected' : ''; ?>>Pendientes</option>
                <option value="aprobada" <?php echo ($filter_status == 'aprobada') ? 'selected' : ''; ?>>Aprobadas</option>
                <option value="rechazada" <?php echo ($filter_status == 'rechazada') ? 'selected' : ''; ?>>Rechazadas</option>
                <option value="todos" <?php echo ($filter_status == 'todos') ? 'selected' : ''; ?>>Todos</option>
            </select>
        </form>
        <form action="admin_validaciones.php" method="GET" class="search-form">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
            <input type="text" name="search" placeholder="Buscar por usuario..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit"><i class="fas fa-search"></i> Buscar</button>
            <?php if (!empty($search_query)): ?>
                <a href="admin_validaciones.php?status=<?php echo htmlspecialchars($filter_status); ?>" class="btn-clear-search"><i class="fas fa-times"></i> Limpiar Búsqueda</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($recolecciones)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Recolección</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th>Tipo(s) de Residuos</th>
                    <th>Peso (Kg)</th>
                    <th>Puntos Ganados</th>
                    <th>Estado</th>
                    <th>Observaciones Usuario</th>
                    <th>Observaciones Admin</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recolecciones as $rec): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($rec['id_recoleccion']); ?></td>
                    <td><?php echo htmlspecialchars($rec['usuario_nombre'] . ' ' . $rec['usuario_apellido']); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($rec['fecha_recoleccion'])); ?></td>
                    <td><?php echo htmlspecialchars($rec['tipos_residuos']); ?></td>
                    <td><?php echo htmlspecialchars($rec['peso_kg']); ?></td>
                    <td><?php echo htmlspecialchars($rec['puntos_ganados']); ?></td>
                    <td>
                        <span class="status-<?php echo strtolower($rec['estado']); ?>">
                            <?php echo htmlspecialchars(ucfirst($rec['estado'])); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($rec['observaciones_usuario'] ?: 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($rec['observaciones_admin'] ?: 'N/A'); ?></td>
                    <td class="action-buttons">
                        <?php if ($rec['estado'] == 'pendiente'): ?>
                            <a href="#" class="action-approve" title="Aprobar Recolección" 
                               data-id="<?php echo $rec['id_recoleccion']; ?>"
                               data-observaciones-usuario="<?php echo htmlspecialchars($rec['observaciones_usuario'] ?: ''); ?>"
                               onclick="openValidationModal(this, 'approve')">
                               <i class="fas fa-check-circle"></i>
                            </a>
                            <a href="#" class="action-reject" title="Rechazar Recolección"
                               data-id="<?php echo $rec['id_recoleccion']; ?>"
                               data-observaciones-usuario="<?php echo htmlspecialchars($rec['observaciones_usuario'] ?: ''); ?>"
                               onclick="openValidationModal(this, 'reject')">
                               <i class="fas fa-times-circle"></i>
                            </a>
                        <?php else: ?>
                            <span>Procesado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay recolecciones para validar o que coincidan con la búsqueda/filtro.</p>
    <?php endif; ?>

    <div id="validationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeValidationModal()">&times;</span>
            <h2 id="modalTitle"></h2>
            <form id="validationForm" method="POST" action="admin_validaciones.php">
                <input type="hidden" id="modal_id_recoleccion" name="id_recoleccion">
                <input type="hidden" id="modal_action" name="action">
                
                <div class="form-group">
                    <label>Observaciones del Usuario:</label>
                    <p id="modal_observaciones_usuario" class="read-only-field"></p>
                </div>
                <div class="form-group">
                    <label for="modal_observaciones_admin">Tus Observaciones (Opcional):</label>
                    <textarea id="modal_observaciones_admin" name="observaciones_admin" rows="4"></textarea>
                </div>
                <button type="submit" class="btn-save" id="modalSubmitButton">Confirmar</button>
            </form>
        </div>
    </div>

</div>

<?php

if ($conn) $conn->close();
?>

<script>
    var validationModal = document.getElementById("validationModal");
    var modalTitle = document.getElementById("modalTitle");
    var modalObservacionesUsuario = document.getElementById("modal_observaciones_usuario");
    var modalObservacionesAdmin = document.getElementById("modal_observaciones_admin");
    var modalIdRecoleccion = document.getElementById("modal_id_recoleccion");
    var modalAction = document.getElementById("modal_action");
    var modalSubmitButton = document.getElementById("modalSubmitButton");
    var closeButton = document.querySelector("#validationModal .close-button");

    function openValidationModal(element, actionType) {
        var id = element.getAttribute("data-id");
        var userObs = element.getAttribute("data-observaciones-usuario");

        modalIdRecoleccion.value = id;
        modalAction.value = actionType;
        modalObservacionesUsuario.textContent = userObs || 'El usuario no añadió observaciones.';
        modalObservacionesAdmin.value = ''; 

        if (actionType === 'approve') {
            modalTitle.textContent = "Aprobar Recolección";
            modalSubmitButton.textContent = "Aprobar y Asignar Puntos";
            modalSubmitButton.style.backgroundColor = "var(--primary-color)";
        } else if (actionType === 'reject') {
            modalTitle.textContent = "Rechazar Recolección";
            modalSubmitButton.textContent = "Rechazar Recolección";
            modalSubmitButton.style.backgroundColor = "#e74c3c";
        }
        
        validationModal.style.display = "flex";
    }

    function closeValidationModal() {
        validationModal.style.display = "none";
    }

    
    window.onclick = function(event) {
        if (event.target == validationModal) {
            validationModal.style.display = "none";
        }
    }

    
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000); 
        });
    });
</script>

