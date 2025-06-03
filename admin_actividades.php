<?php

$titulo_pagina = "Gestión de Actividades";
include 'admin_layout.php'; 
include 'conexion.php';

$mensaje = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_activity') {
    $nombre_actividad = $_POST['nombre_actividad'];
    $descripcion_actividad = $_POST['descripcion_actividad'];
    $fecha_actividad = $_POST['fecha_actividad'];
    $puntos_por_participacion = $_POST['puntos_por_participacion'];

    if (empty($nombre_actividad) || empty($descripcion_actividad) || empty($fecha_actividad)) {
        $mensaje = "<div class='alert alert-error'>Todos los campos de la actividad son obligatorios.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO actividades (nombre, descripcion, fecha_actividad, puntos_por_participacion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre_actividad, $descripcion_actividad, $fecha_actividad, $puntos_por_participacion);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Actividad '" . htmlspecialchars($nombre_actividad) . "' añadida con éxito.</div>";
            
            $_POST = array(); 
        } else {
            $mensaje = "<div class='alert alert-error'>Error al añadir actividad: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// --- Lógica para ELIMINAR ACTIVIDAD ---
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id_actividad_a_eliminar = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM actividades WHERE id_actividad = ?");
    $stmt->bind_param("i", $id_actividad_a_eliminar); 

    if ($stmt->execute()) {
        $mensaje = "<div class='alert alert-success'>Actividad eliminada con éxito.</div>";
    } else {
        $mensaje = "<div class='alert alert-error'>Error al eliminar actividad: " . $stmt->error . "</div>";
    }
    $stmt->close();
}



$actividades = [];
$stmt_actividades = $conn->query("SELECT id_actividad, nombre, descripcion, fecha_actividad, puntos_por_participacion FROM actividades ORDER BY fecha_actividad DESC");
if ($stmt_actividades && $stmt_actividades->num_rows > 0) {
    $actividades = $stmt_actividades->fetch_all(MYSQLI_ASSOC);
} else {
    $mensaje .= "<div class='alert alert-info'>No hay actividades registradas aún.</div>";
}
if (isset($stmt_actividades) && $stmt_actividades) $stmt_actividades->close();
?>

<div class="admin-content-section">
    <h2><?php echo $titulo_pagina; ?></h2>

    <?php if ($mensaje): ?>
        <?php echo $mensaje; ?>
    <?php endif; ?>

    <div class="card">
        <h3>Añadir Nueva Actividad</h3>
        <form method="POST" action="admin_actividades.php">
            <input type="hidden" name="action" value="add_activity">
            <div class="form-group">
                <label for="nombre_actividad">Nombre de la Actividad:</label>
                <input type="text" id="nombre_actividad" name="nombre_actividad" required>
            </div>
            <div class="form-group">
                <label for="descripcion_actividad">Descripción:</label>
                <textarea id="descripcion_actividad" name="descripcion_actividad" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="fecha_actividad">Fecha y Hora:</label>
                <input type="datetime-local" id="fecha_actividad" name="fecha_actividad" required>
            </div>
            <div class="form-group">
                <label for="puntos_por_participacion">Puntos por Participación:</label>
                <input type="number" id="puntos_por_participacion" name="puntos_por_participacion" value="0" min="0" required>
            </div>
            <button type="submit" class="btn-primary">Añadir Actividad</button>
        </form>
    </div>

    <div class="card mt-4">
        <h3>Actividades Existentes</h3>
        <?php if (!empty($actividades)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Puntos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actividades as $act): ?>
                <tr>
                    <td><?php echo htmlspecialchars($act['id_actividad']); ?></td>
                    <td><?php echo htmlspecialchars($act['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($act['descripcion']); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($act['fecha_actividad'])); ?></td>
                    <td><?php echo htmlspecialchars($act['puntos_por_participacion']); ?></td>
                    <td>
                        <a href="admin_actividades.php?accion=eliminar&id=<?php echo htmlspecialchars($act['id_actividad']); ?>" 
                           class="action-delete" title="Eliminar Actividad" 
                           onclick="return confirm('¿Estás seguro de que quieres eliminar esta actividad?');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No hay actividades registradas.</p>
        <?php endif; ?>
    </div>
</div>

<?php

if ($conn) $conn->close();
?>
            </section>
        </main>
    </div>
</body>
</html>