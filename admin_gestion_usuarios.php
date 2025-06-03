<?php
// admin_gestion_usuarios.php
$titulo_pagina = "Gestión de Usuarios";
include 'admin_layout.php'; // Incluye el layout para mantener la estructura del admin

include 'conexion.php'; // Incluye la conexión a la base de datos

$mensaje = ""; // Para mostrar mensajes de éxito o error

// --- Lógica para ELIMINAR USUARIO ---
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id_usuario_a_eliminar = $_GET['id'];

    // Evitar que un admin se elimine a sí mismo (opcional pero recomendado)
    if ($id_usuario_a_eliminar == $_SESSION['user_id']) {
        $mensaje = "<div class='alert alert-error'>No puedes eliminar tu propia cuenta de administrador.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("s", $id_usuario_a_eliminar); // 's' porque id_usuario es VARCHAR

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Usuario " . htmlspecialchars($id_usuario_a_eliminar) . " eliminado con éxito.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Error al eliminar usuario: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// --- Lógica para CAMBIAR ROL O ESTADO (ACTUALIZAR) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id_usuario_actualizar = $_POST['edit_user_id'];
    $nuevo_rol = $_POST['edit_rol'];
    $nuevo_estado = $_POST['edit_estado'];

    // Validar que el rol sea uno de los permitidos
    if (!in_array($nuevo_rol, ['usuario', 'administrador'])) {
        $mensaje = "<div class='alert alert-error'>Rol no válido.</div>";
    } elseif (!in_array($nuevo_estado, ['activo', 'inactivo', 'bloqueado'])) { // Asegúrate de que los estados coincidan con tu BD
        $mensaje = "<div class='alert alert-error'>Estado no válido.</div>";
    } else {
        // Preparar la consulta para actualizar el usuario
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ?, estado = ? WHERE id_usuario = ?");
        $stmt->bind_param("sss", $nuevo_rol, $nuevo_estado, $id_usuario_actualizar); // 's' para los tres parámetros

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Usuario " . htmlspecialchars($id_usuario_actualizar) . " actualizado con éxito.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Error al actualizar usuario: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// --- Obtener todos los usuarios para mostrar en la tabla ---
$users = [];
$stmt_users = $conn->query("SELECT id_usuario, nombre, apellido, email, rol, puntos_acumulados, fecha_registro, estado FROM usuarios ORDER BY fecha_registro DESC");
if ($stmt_users && $stmt_users->num_rows > 0) {
    $users = $stmt_users->fetch_all(MYSQLI_ASSOC);
} else {
    $mensaje .= "<div class='alert alert-info'>No hay usuarios registrados aún.</div>";
}
// Siempre cerrar el statement de consulta si se usó
if (isset($stmt_users) && $stmt_users) $stmt_users->close(); 
?>

<div class="admin-content-section">
    <h2><?php echo $titulo_pagina; ?></h2>

    <?php if ($mensaje): ?>
        <?php echo $mensaje; ?>
    <?php endif; ?>

    <div class="card">
        <h3>Lista de Usuarios</h3>
        <?php if (!empty($users)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Puntos</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id_usuario']); ?></td>
                    <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($user['rol'])); ?></td>
                    <td><?php echo htmlspecialchars($user['puntos_acumulados']); ?></td>
                    <td><?php echo date("d/m/Y", strtotime($user['fecha_registro'])); ?></td>
                    <td>
                        <span class="status-<?php echo ($user['estado'] == 'activo' ? 'active' : ($user['estado'] == 'inactivo' ? 'inactive' : 'blocked')); ?>">
                            <?php echo htmlspecialchars(ucfirst($user['estado'])); ?>
                        </span>
                    </td>
                    <td>
                        <a href="#" class="action-edit" title="Editar Usuario" 
                           data-id="<?php echo htmlspecialchars($user['id_usuario']); ?>"
                           data-nombre="<?php echo htmlspecialchars($user['nombre']); ?>"
                           data-apellido="<?php echo htmlspecialchars($user['apellido']); ?>"
                           data-email="<?php echo htmlspecialchars($user['email']); ?>"
                           data-rol="<?php echo htmlspecialchars($user['rol']); ?>"
                           data-estado="<?php echo htmlspecialchars($user['estado']); ?>"
                           onclick="openEditModal(this)">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($user['id_usuario'] != $_SESSION['user_id']): // Evitar eliminar al propio admin ?>
                            <a href="admin_gestion_usuarios.php?accion=eliminar&id=<?php echo htmlspecialchars($user['id_usuario']); ?>" 
                               class="action-delete" title="Eliminar Usuario" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar a <?php echo htmlspecialchars($user['nombre']); ?>?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No hay usuarios registrados.</p>
        <?php endif; ?>
    </div>
</div>

<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeEditModal()">&times;</span>
        <h2>Editar Usuario</h2>
        <form id="editUserForm" method="POST" action="admin_gestion_usuarios.php">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="edit_user_id" id="edit_user_id">

            <div class="form-group">
                <label for="display_user_name">Nombre Completo:</label>
                <input type="text" id="display_user_name" disabled>
            </div>
            <div class="form-group">
                <label for="display_user_email">Correo Electrónico:</label>
                <input type="email" id="display_user_email" disabled>
            </div>
            
            <div class="form-group">
                <label for="edit_rol">Rol:</label>
                <select id="edit_rol" name="edit_rol" required>
                    <option value="usuario">Usuario</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_estado">Estado:</label>
                <select id="edit_estado" name="edit_estado" required>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="bloqueado">Bloqueado</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary">Guardar Cambios</button>
        </form>
    </div>
</div>

<style>
/* Estilos básicos para el modal */
.modal {
    display: none; /* Oculto por defecto */
    position: fixed; /* Posición fija para cubrir toda la pantalla */
    z-index: 1000; /* Asegura que esté por encima de todo */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Permite scroll si el contenido es muy largo */
    background-color: rgba(0,0,0,0.4); /* Fondo semi-transparente */
    justify-content: center; /* Centrar horizontalmente */
    align-items: center; /* Centrar verticalmente */
}

.modal-content {
    background-color: #fefefe;
    margin: auto; /* Auto margin para centrado vertical y horizontal */
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 500px; /* Ancho máximo para el modal */
    position: relative; /* Para posicionar el botón de cerrar */
}

.close-button {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    right: 15px;
    top: 10px;
    cursor: pointer;
}

.close-button:hover,
.close-button:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.modal .form-group {
    margin-bottom: 1rem;
}

.modal .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #333;
}

.modal .form-group input[type="text"],
.modal .form-group input[type="email"],
.modal .form-group select {
    width: calc(100% - 20px); /* Ajustar el ancho considerando el padding */
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.modal .btn-primary {
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s ease;
    margin-top: 15px;
    width: 100%;
}

.modal .btn-primary:hover {
    background-color: var(--secondary-color);
}

/* Estilos de alerta (puedes tener estos en un CSS global) */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<script>
    // JavaScript para el modal de edición
    function openEditModal(button) {
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const apellido = button.getAttribute('data-apellido');
        const email = button.getAttribute('data-email');
        const rol = button.getAttribute('data-rol');
        const estado = button.getAttribute('data-estado');

        document.getElementById('edit_user_id').value = id;
        document.getElementById('display_user_name').value = nombre + ' ' + apellido;
        document.getElementById('display_user_email').value = email;
        document.getElementById('edit_rol').value = rol;
        document.getElementById('edit_estado').value = estado;

        document.getElementById('editUserModal').style.display = 'flex'; // Usar flex para centrar
    }

    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    // Cerrar el modal haciendo clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('editUserModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<?php
// Cierra la conexión a la base de datos al final del script
if ($conn) $conn->close();
?>
            </section>
        </main>
    </div>
</body>
</html>