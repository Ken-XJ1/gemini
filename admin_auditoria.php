<?php

$titulo_pagina = "Auditoría del Sistema";
include 'admin_layout.php'; 
include 'conexion.php';     

$registros_por_pagina = 10; 
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $registros_por_pagina;


$filtro_usuario = isset($_GET['usuario']) ? $conn->real_escape_string($_GET['usuario']) : '';
$filtro_accion = isset($_GET['accion']) ? $conn->real_escape_string($_GET['accion']) : '';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($filtro_usuario)) {
    $where_clauses[] = "id_usuario_afectado = ?";
    $params[] = $filtro_usuario;
    $param_types .= 's'; 
}
if (!empty($filtro_accion)) {
    $where_clauses[] = "accion_realizada LIKE ?";
    $params[] = '%' . $filtro_accion . '%';
    $param_types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}


$total_registros = 0;
$query_count = "SELECT COUNT(*) AS total FROM auditoria" . $where_sql;
$stmt_count = $conn->prepare($query_count);

if ($stmt_count && !empty($params)) {
    $params_count = $params;
    $stmt_count->bind_param($param_types, ...$params_count);
}
if ($stmt_count) {
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_registros = $result_count->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();
}

$total_paginas = ceil($total_registros / $registros_por_pagina);


$registros_auditoria = [];

$query_auditoria = "SELECT id_registro_auditoria, id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen, fecha_hora_auditoria FROM auditoria" . $where_sql . " ORDER BY fecha_hora_auditoria DESC LIMIT ?, ?";
$stmt_auditoria = $conn->prepare($query_auditoria);

if ($stmt_auditoria) {
    $params_final = array_merge($params, [$offset, $registros_por_pagina]);
    $param_types_final = $param_types . 'ii';

    if (empty($params)) {
        $stmt_auditoria->bind_param("ii", $offset, $registros_por_pagina);
    } else {
        $stmt_auditoria->bind_param($param_types_final, ...$params_final);
    }
    
    $stmt_auditoria->execute();
    $result_auditoria = $stmt_auditoria->get_result();
    $registros_auditoria = $result_auditoria->fetch_all(MYSQLI_ASSOC);
    $stmt_auditoria->close();
}


if ($conn) $conn->close();
?>

<div class="audit-container">
    <h2>Filtros de Auditoría</h2>
    <form method="GET" action="admin_auditoria.php" class="filter-form">
        <div class="form-group">
            <label for="usuario">ID de Usuario Afectado:</label>
            <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($filtro_usuario); ?>" placeholder="Ej: user123">
        </div>
        <div class="form-group">
            <label for="accion">Acción Realizada:</label>
            <input type="text" id="accion" name="accion" value="<?php echo htmlspecialchars($filtro_accion); ?>" placeholder="Ej: NUEVO REGISTRO">
        </div>
        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
        <a href="admin_auditoria.php" class="btn btn-secondary">Limpiar Filtros</a>
    </form>

    <h2>Registros de Auditoría</h2>
    <?php if (!empty($registros_auditoria)): ?>
    <div class="table-responsive">
        <table class="table audit-table">
            <thead>
                <tr>
                    <th>ID Reg.</th>
                    <th>ID Usuario Afectado</th>
                    <th>Acción Realizada</th>
                    <th>Tabla Modificada</th>
                    <th>ID Reg. Modificado</th>
                    <th>Detalles</th>
                    <th>IP Origen</th>
                    <th>Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros_auditoria as $registro): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registro['id_registro_auditoria']); ?></td>
                    <td><?php echo htmlspecialchars($registro['id_usuario_afectado'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($registro['accion_realizada']); ?></td>
                    <td><?php echo htmlspecialchars($registro['tabla_modificada'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($registro['id_registro_modificado'] ?? 'N/A'); ?></td>
                    <td title="<?php echo htmlspecialchars($registro['detalles_accion'] ?? ''); ?>">
                        <?php echo htmlspecialchars(substr($registro['detalles_accion'] ?? 'Sin detalles', 0, 70)); ?>
                        <?php if (strlen($registro['detalles_accion'] ?? '') > 70) echo '...'; ?>
                    </td>
                    <td><?php echo htmlspecialchars($registro['ip_origen'] ?? 'N/A'); ?></td>
                    <td><?php echo date("d/m/Y H:i:s", strtotime($registro['fecha_hora_auditoria'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($pagina > 1): ?>
            <a href="admin_auditoria.php?pagina=<?php echo $pagina - 1; ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>&accion=<?php echo htmlspecialchars($filtro_accion); ?>" class="btn-pagination">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="admin_auditoria.php?pagina=<?php echo $i; ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>&accion=<?php echo htmlspecialchars($filtro_accion); ?>" class="btn-pagination <?php echo ($i == $pagina) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
            <a href="admin_auditoria.php?pagina=<?php echo $pagina + 1; ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>&accion=<?php echo htmlspecialchars($filtro_accion); ?>" class="btn-pagination">Siguiente</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <p>No hay registros de auditoría disponibles.</p>
    <?php endif; ?>
</div>

</section>