<?php
// admin.php - Dashboard
$titulo_pagina = "Dashboard";
include 'admin_layout.php'; // Incluye el layout base del administrador
include 'conexion.php';     // Incluye la conexión a la base de datos

// --- 1. Obtener métricas para las tarjetas de resumen ---

// Total de Usuarios Registrados
$total_usuarios = 0;
$stmt_total_usuarios = $conn->query("SELECT COUNT(*) as total_usuarios FROM usuarios");
if ($stmt_total_usuarios) {
    $total_usuarios = $stmt_total_usuarios->fetch_assoc()['total_usuarios'] ?? 0;
    $stmt_total_usuarios->close();
}

// Total de Recolecciones Pendientes (Usando la función SQL)
$total_recolecciones_pendientes = 0;
// Si la función `contar_recolecciones_por_estado` existe:
$stmt_pendientes = $conn->query("SELECT contar_recolecciones_por_estado('pendiente') AS total_pendientes");
if ($stmt_pendientes && $stmt_pendientes->num_rows > 0) {
    $total_recolecciones_pendientes = $stmt_pendientes->fetch_assoc()['total_pendientes'] ?? 0;
    $stmt_pendientes->close();
} else {
    // Fallback si la función no existe (aunque ya la creamos)
    $stmt_pendientes_manual = $conn->query("SELECT COUNT(*) AS total FROM recolecciones WHERE estado = 'pendiente'");
    if ($stmt_pendientes_manual) {
        $total_recolecciones_pendientes = $stmt_pendientes_manual->fetch_assoc()['total'] ?? 0;
        $stmt_pendientes_manual->close();
    }
}

// Total de Recolecciones Completadas
$total_recolecciones_completadas = 0;
// Si la función `contar_recolecciones_por_estado` existe:
$stmt_completadas = $conn->query("SELECT contar_recolecciones_por_estado('completada') AS total_completadas");
if ($stmt_completadas && $stmt_completadas->num_rows > 0) {
    $total_recolecciones_completadas = $stmt_completadas->fetch_assoc()['total_completadas'] ?? 0;
    $stmt_completadas->close();
} else {
    // Fallback si la función no existe
    $stmt_completadas_manual = $conn->query("SELECT COUNT(*) AS total FROM recolecciones WHERE estado = 'completada'");
    if ($stmt_completadas_manual) {
        $total_recolecciones_completadas = $stmt_completadas_manual->fetch_assoc()['total'] ?? 0;
        $stmt_completadas_manual->close();
    }
}


// Total de Material Reciclado en kg (Necesitamos una nueva consulta)
$total_material_reciclado_kg = 0.00;
$stmt_material = $conn->query("SELECT SUM(dr.cantidad_kg) AS total_kg 
                                 FROM detalle_recoleccion dr
                                 JOIN recolecciones r ON dr.id_recoleccion = r.id_recoleccion
                                 WHERE r.estado = 'completada'"); // Solo kg de recolecciones completadas
if ($stmt_material) {
    $total_material_reciclado_kg = $stmt_material->fetch_assoc()['total_kg'] ?? 0.00;
    $stmt_material->close();
}

// Total de Puntos Otorgados (Sumar puntos_acumulados de todos los usuarios, o se podría hacer una función más compleja que sume los puntos de cada recolección completada)
$total_puntos_otorgados = 0;
$stmt_puntos = $conn->query("SELECT SUM(puntos_acumulados) AS total_puntos FROM usuarios");
if ($stmt_puntos) {
    $total_puntos_otorgados = $stmt_puntos->fetch_assoc()['total_puntos'] ?? 0;
    $stmt_puntos->close();
}


// Últimos usuarios registrados (Usando la misma consulta, está bien)
$recent_users = [];
$stmt_recent_users = $conn->query("SELECT id_usuario, nombre, email, fecha_registro, rol, estado FROM usuarios ORDER BY fecha_registro DESC LIMIT 5");
if ($stmt_recent_users && $stmt_recent_users->num_rows > 0) {
    $recent_users = $stmt_recent_users->fetch_all(MYSQLI_ASSOC);
    $stmt_recent_users->close();
}
?>

<div class="summary-cards">
    <div class="summary-card">
        <div class="icon users"><i class="fas fa-users"></i></div>
        <div class="info">
            <h4><?php echo $total_usuarios; ?></h4>
            <p>Usuarios Registrados</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="icon validations"><i class="fas fa-hourglass-half"></i></div> <div class="info">
            <h4><?php echo $total_recolecciones_pendientes; ?></h4>
            <p>Recolecciones Pendientes</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="icon completed"><i class="fas fa-check-circle"></i></div> <div class="info">
            <h4><?php echo $total_recolecciones_completadas; ?></h4>
            <p>Recolecciones Completadas</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="icon recycle-kg"><i class="fas fa-weight-hanging"></i></div> <div class="info">
            <h4><?php echo number_format($total_material_reciclado_kg, 2); ?> kg</h4>
            <p>Material Reciclado</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="icon points"><i class="fas fa-coins"></i></div> <div class="info">
            <h4><?php echo number_format($total_puntos_otorgados, 0); ?></h4>
            <p>Puntos Otorgados</p>
        </div>
    </div>
</div>

<div class="data-table-container">
    <h2>Últimos Usuarios Registrados</h2>
    <?php if (!empty($recent_users)): ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Fecha Registro</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_users as $user): ?>
            <tr>
                <td>#<?php echo htmlspecialchars($user['id_usuario']); ?></td>
                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo date("d/m/Y", strtotime($user['fecha_registro'])); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($user['rol'])); ?></td>
                <td>
                    <?php 
                        $status_class = '';
                        $status_text = '';
                        if ($user['estado'] == 'activo') {
                            $status_class = 'status-active';
                            $status_text = 'Activo';
                        } elseif ($user['estado'] == 'inactivo') {
                            $status_class = 'status-inactive';
                            $status_text = 'Inactivo';
                        } elseif ($user['estado'] == 'bloqueado') { // Asumiendo que puedes tener un estado "bloqueado"
                            $status_class = 'status-blocked'; // Necesitarías añadir este estilo en tu CSS
                            $status_text = 'Bloqueado';
                        } else {
                            $status_class = 'status-unknown'; // Para cualquier otro estado
                            $status_text = ucfirst($user['estado']);
                        }
                    ?>
                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
                <td>
                    <a href="admin_gestion_usuarios.php?accion=ver&id_usuario=<?php echo $user['id_usuario']; ?>" class="action-view" title="Ver Usuario"><i class="fas fa-eye"></i></a>
                    <a href="admin_gestion_usuarios.php?accion=editar&id_usuario=<?php echo $user['id_usuario']; ?>" class="action-edit" title="Editar Usuario"><i class="fas fa-edit"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No hay usuarios registrados recientemente o no se pudieron cargar.</p>
    <?php endif; ?>
</div>

<?php
// Cerrar la conexión al final del script
if ($conn) $conn->close();
?>
</section>