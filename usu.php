<?php
// usu.php
session_start();

// --- INICIO: LÍNEAS PARA DEPURACIÓN (Mantenlas durante la depuración, luego elimínalas en producción) ---
error_reporting(E_ALL); // Reporta todos los errores de PHP
ini_set('display_errors', 1); // Muestra los errores en el navegador
echo "DEBUG: Inicio de usu.php\n"; // Debug 1
// --- FIN: LÍNEAS PARA DEPURACIÓN ---

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

echo "DEBUG: Conexión a la base de datos incluida.\n"; // Debug 2

if ($conn->connect_error) {
    die("DEBUG ERROR: Error de conexión a la base de datos en usu.php: " . $conn->connect_error);
}
echo "DEBUG: Conexión \$conn está activa.\n"; // Debug 3


$user_id = $_SESSION['user_id'];
$user_data = [];
$total_kg_reciclados = '0.00';
$total_recolecciones = 0;
$history_items = [];
// $premios_disponibles ya NO se carga aquí, se carga en premios.php

// Consulta para datos básicos del usuario
$stmt_user = $conn->prepare("SELECT nombre, apellido, email, puntos_acumulados, fecha_registro FROM usuarios WHERE id_usuario = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
    } else {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $stmt_user->close();
} else {
    error_log("Error al preparar la consulta de usuario: " . $conn->error);
}


// Consulta para total de recolecciones (estadística)
$stmt_recolecciones_count = $conn->prepare("SELECT COUNT(id_recoleccion) AS total_recolecciones FROM recolecciones WHERE id_usuario = ? AND estado = 'completado'"); // Contar solo completadas
if ($stmt_recolecciones_count) {
    $stmt_recolecciones_count->bind_param("i", $user_id);
    $stmt_recolecciones_count->execute();
    $result_recolecciones_count = $stmt_recolecciones_count->get_result();
    if ($result_recolecciones_count->num_rows > 0) {
        $total_recolecciones = $result_recolecciones_count->fetch_assoc()['total_recolecciones'];
    }
    $stmt_recolecciones_count->close();
} else {
    error_log("Error al preparar la consulta de conteo de recolecciones: " . $conn->error);
}


// Consulta para total de kg reciclados (estadística)
$stmt_kg_reciclados = $conn->prepare("SELECT SUM(dr.cantidad_kg) AS total_kg FROM detalle_recoleccion dr JOIN recolecciones r ON dr.id_recoleccion = r.id_recoleccion WHERE r.id_usuario = ? AND r.estado = 'completado'");
if ($stmt_kg_reciclados) {
    $stmt_kg_reciclados->bind_param("i", $user_id);
    $stmt_kg_reciclados->execute();
    $result_kg_reciclados = $stmt_kg_reciclados->get_result();
    if ($result_kg_reciclados->num_rows > 0) {
        $total_kg_reciclados = $result_kg_reciclados->fetch_assoc()['total_kg'] ?? '0.00';
    }
    $stmt_kg_reciclados->close();
} else {
    error_log("Error al preparar la consulta de kg reciclados: " . $conn->error);
}


// Obtener historial de actividades (recolecciones y canjes)
// Recolecciones
$stmt_recolecciones = $conn->prepare("SELECT 'recoleccion' AS tipo, id_recoleccion AS id, fecha_recoleccion AS fecha, observaciones_usuario AS descripcion, puntos_ganados AS puntos, estado FROM recolecciones WHERE id_usuario = ?");
if ($stmt_recolecciones) {
    $stmt_recolecciones->bind_param("i", $user_id);
    $stmt_recolecciones->execute();
    $result_recolecciones = $stmt_recolecciones->get_result();
    while ($row = $result_recolecciones->fetch_assoc()) {
        $row['fecha'] = new DateTime($row['fecha']);
        $history_items[] = $row;
    }
    $stmt_recolecciones->close();
} else {
    error_log("Error al preparar la consulta de historial de recolecciones: " . $conn->error);
}


// Canjes de premios
$stmt_canjes = $conn->prepare("SELECT 'canje' AS tipo, cp.id_canje AS id, cp.fecha_canje AS fecha, p.nombre AS descripcion, cp.puntos_usados AS puntos, cp.estado FROM canjes_premios cp JOIN premios p ON cp.id_premio = p.id_premio WHERE cp.id_usuario = ?");
if ($stmt_canjes) {
    $stmt_canjes->bind_param("i", $user_id);
    $stmt_canjes->execute();
    $result_canjes = $stmt_canjes->get_result();
    while ($row = $result_canjes->fetch_assoc()) {
        $row['fecha'] = new DateTime($row['fecha']);
        $row['puntos'] = abs($row['puntos']); // Asegúrate de que sea positivo para la visualización
        $history_items[] = $row;
    }
    $stmt_canjes->close();
} else {
    error_log("Error al preparar la consulta de historial de canjes: " . $conn->error);
}


// Ordenar el historial por fecha descendente
usort($history_items, function($a, $b) {
    return $b['fecha'] <=> $a['fecha'];
});

// NO CERRAR LA CONEXIÓN AQUÍ. Se cerrará al final del script.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - ReciclApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/user_style.css">
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <i class="fas fa-recycle"></i>
            <span>ReciclApp</span>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="logout.php" class="btn-login">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <div class="user-dashboard-container">
        <section class="profile-header">
            <h1>Hola, <?php echo htmlspecialchars($user_data['nombre']); ?>!</h1>
            <p>Bienvenido a tu panel de usuario de ReciclApp.</p>
        </section>

        <section class="user-summary-card">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2><?php echo htmlspecialchars($user_data['nombre'] . ' ' . $user_data['apellido']); ?></h2>
            <p class="email"><?php echo htmlspecialchars($user_data['email']); ?></p>
            <div class="user-stats">
                <div class="stat-item">
                    <i class="fas fa-coins"></i>
                    <h3>Puntos Acumulados</h3>
                    <p class="points-display" id="userPoints"><?php echo htmlspecialchars(number_format($user_data['puntos_acumulados'], 0, ',', '.')); ?></p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-leaf"></i>
                    <h3>Recolecciones Realizadas</h3>
                    <p><?php echo htmlspecialchars(number_format($total_recolecciones, 0, ',', '.')); ?></p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-trash-alt"></i>
                    <h3>Total Reciclado (KG)</h3>
                    <p><?php echo htmlspecialchars(number_format($total_kg_reciclados, 2, ',', '.')); ?></p>
                </div>
            </div>
        </section>

        <section class="user-actions container">
            <div class="action-card" onclick="openRecoleccionModal()">
                <i class="fas fa-plus-circle"></i>
                <h3>Registrar Recolección</h3>
                <p>Reporta tus residuos para ganar puntos.</p>
            </div>
            <div class="action-card" onclick="window.location.href='premios.php'">
                <i class="fas fa-gift"></i>
                <h3>Canjear Premios</h3>
                <p>Usa tus puntos para obtener recompensas.</p>
            </div>
            <div class="action-card" onclick="showMap()">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Puntos de Reciclaje</h3>
                <p>Encuentra centros de acopio cercanos.</p>
            </div>
            <div class="action-card" onclick="showHistory()">
                <i class="fas fa-history"></i>
                <h3>Historial de Actividad</h3>
                <p>Revisa tus recolecciones y canjes.</p>
            </div>
        </section>

        <section id="mapContainer" class="container map-container" style="display: none;">
            <h2>Puntos de Reciclaje Cercanos</h2>
            <p>Cargando mapa...</p>
        </section>

        <section id="historyContainer" class="container history-container" style="display: none;">
            <h2>Tu Historial de Actividad</h2>
            <?php if (!empty($history_items)): ?>
                    <ul class="history-list">
                        <?php foreach ($history_items as $item): ?>
                            <li class="history-item">
                                <div class="icon">
                                    <?php if ($item['tipo'] === 'recoleccion'): ?>
                                        <i class="fas fa-leaf"></i>
                                    <?php elseif ($item['tipo'] === 'canje'): ?>
                                        <i class="fas fa-gift"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="details">
                                    <p class="item-title">
                                        <?php
                                            if ($item['tipo'] === 'recoleccion') {
                                                echo 'Recolección de Residuos';
                                            } elseif ($item['tipo'] === 'canje') {
                                                echo 'Canje de Premio: ' . htmlspecialchars($item['descripcion']);
                                            }
                                        ?>
                                    </p>
                                    <p class="item-description"><?php echo htmlspecialchars($item['descripcion']); ?></p>
                                    <p class="item-date"><?php echo $item['fecha']->format('d \d\e F \d\e Y, h:i A'); ?></p>
                                </div>
                                <?php if ($item['tipo'] === 'recoleccion' && $item['estado'] === 'completado'): ?>
                                    <div class="points gained">+<?php echo htmlspecialchars($item['puntos']); ?> pts</div>
                                <?php elseif ($item['tipo'] === 'canje'): ?>
                                    <div class="points spent">-<?php echo htmlspecialchars($item['puntos']); ?> pts</div>
                                <?php endif; ?>
                                <div class="status <?php echo strtolower(htmlspecialchars($item['estado'])); ?>">
                                    <?php echo htmlspecialchars($item['estado']); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-history">Aún no tienes actividad registrada en tu historial.</p>
                <?php endif; ?>
            </div>
        </section>

        <div id="recoleccionModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeRecoleccionModal()">&times;</span>
                <h2>Registrar Nueva Recolección</h2>
                <form id="recoleccionForm" action="api.php?resource=recolecciones&action=add" method="POST">
                    <div class="form-group">
                        <label for="fecha_recoleccion">Fecha de Recolección:</label>
                        <input type="datetime-local" id="fecha_recoleccion" name="fecha_recoleccion" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_residuo">Tipo de Residuo:</label>
                        <select id="tipo_residuo" name="tipo_residuo" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="1">Plástico</option>
                            <option value="2">Papel y Cartón</option>
                            <option value="3">Vidrio</option>
                            <option value="4">Metal</option>
                            <option value="5">Orgánico</option>
                            <option value="6">Pilas</option>
                            <option value="7">Aceite de Cocina Usado</option>
                            <option value="8">Textiles</option>
                            <option value="9">Aparatos Electrónicos (E-waste)</option>
                            <option value="10">Madera</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cantidad_kg">Cantidad (KG):</label>
                        <input type="number" id="cantidad_kg" name="cantidad_kg" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="observaciones_usuario">Observaciones (Opcional):</label>
                        <textarea id="observaciones_usuario" name="observaciones_usuario" rows="3"></textarea>
                    </div>
                     <input type="hidden" id="ip_publica_recoleccion" name="ip_publica" value="">
                    <button type="submit" class="btn-primary">Enviar Recolección</button>
                </form>
                <div class="message-area" id="recoleccionMessageArea"></div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container"><div class="footer-bottom"><p>&copy; <?php echo date("Y"); ?> ReciclApp. Todos los derechos reservados.</p></div></div>
    </footer>

    <script src="js/user_script.js"></script>
    <script>
        // Obtener la IP pública al cargar la página (sigue siendo necesaria para la recolección)
        document.addEventListener('DOMContentLoaded', function() {
            fetch('https://api.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('ip_publica_recoleccion').value = data.ip;
                })
                .catch(error => {
                    console.error('Error al obtener la IP pública:', error);
                    document.getElementById('ip_publica_recoleccion').value = 'API_ERROR';
                });
        });

        // Lógica para el modal de recolección (ya existente)
        function openRecoleccionModal() {
            document.getElementById('recoleccionModal').style.display = 'flex';
        }

        function closeRecoleccionModal() {
            document.getElementById('recoleccionModal').style.display = 'none';
        }

        // Manejar el envío del formulario de recolección (AJAX)
        document.getElementById('recoleccionForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Evitar el envío normal del formulario

            const form = event.target;
            const formData = new FormData(form);
            const messageArea = document.getElementById('recoleccionMessageArea');

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageArea.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    form.reset(); // Limpiar el formulario
                    setTimeout(() => {
                        window.location.reload(); // Recargar para ver los puntos actualizados y el historial
                    }, 1500);
                } else {
                    messageArea.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
                setTimeout(() => {
                    messageArea.innerHTML = '';
                }, 5000); // Ocultar mensaje después de 5 segundos
            })
            .catch(error => {
                console.error('Error:', error);
                messageArea.innerHTML = `<div class="alert alert-error">Ocurrió un error al registrar la recolección.</div>`;
                setTimeout(() => {
                    messageArea.innerHTML = '';
                }, 5000);
            });
        });

        // Funciones de user_script.js que pueden haber sido ignoradas
        // Las estoy moviendo aquí directamente porque user_script.js solo contenía funciones de visualización.
        // Las funciones showRewards() y showHistory() se mantienen, pero showRewards() ahora solo redirige.

        function showMap() {
            const mapContainer = document.getElementById('mapContainer');
            const historyContainer = document.getElementById('historyContainer'); // Ahora se mantiene el historial aquí
            if (!mapContainer) {
                console.error("El contenedor del mapa 'mapContainer' no se encontró.");
                return;
            }
            // Ocultar otras secciones si están visibles
            if (historyContainer) historyContainer.style.display = 'none';


            // Verificar si ya hay un iframe para evitar recargar el mapa
            if (mapContainer.querySelector('iframe')) {
                mapContainer.style.display = 'block';
                mapContainer.scrollIntoView({ behavior: 'smooth' });
                return;
            }
            // Coordenadas para Quibdó, Chocó, Colombia (ajustadas para un mejor centrado)
            const lat = 5.6947; // Latitud de Quibdó
            const lon = -76.6586; // Longitud de Quibdó
            const zoom = 14;
            const iframe = document.createElement('iframe');
            iframe.width = "100%";
            iframe.height = "450";
            iframe.frameBorder = "0";
            iframe.scrolling = "no";
            iframe.style.borderRadius = "var(--border-radius)";
            iframe.style.boxShadow = "var(--box-shadow)";
            // URL de OpenStreetMap para embeber el mapa
            iframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${lon-0.01},${lat-0.01},${lon+0.01},${lat+0.01}&layer=mapnik&marker=${lat},${lon}`;
            mapContainer.innerHTML = ''; // Limpiar cualquier contenido existente
            mapContainer.appendChild(iframe);
            mapContainer.style.display = 'block';
            mapContainer.scrollIntoView({ behavior: 'smooth' });
        }

        function showHistory() {
            const historyContainer = document.getElementById('historyContainer');
            const mapContainer = document.getElementById('mapContainer');

            // Ocultar otras secciones
            if (mapContainer) mapContainer.style.display = 'none';

            if (!historyContainer) {
                console.error("El contenedor de historial 'historyContainer' no se encontró.");
                return;
            }
            if (historyContainer.style.display === 'block') {
                historyContainer.style.display = 'none';
            } else {
                historyContainer.style.display = 'block';
                historyContainer.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
<?php
// CERRAR LA CONEXIÓN AL FINAL DEL SCRIPT
if ($conn) {
    echo "DEBUG: Conexión a la base de datos cerrada al final de usu.php.\n"; // Debug 10
    $conn->close();
}
?>