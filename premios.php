<?php

session_start();


error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "DEBUG: Inicio de premios.php\n";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'conexion.php'; 

$user_id = $_SESSION['user_id'];
$user_points = 0;
$premios_disponibles = [];


$stmt_points = $conn->prepare("SELECT puntos_acumulados FROM usuarios WHERE id_usuario = ?");
if ($stmt_points) {
    $stmt_points->bind_param("i", $user_id);
    $stmt_points->execute();
    $result_points = $stmt_points->get_result();
    if ($result_points->num_rows > 0) {
        $user_points = $result_points->fetch_assoc()['puntos_acumulados'];
    }
    $stmt_points->close();
} else {
    error_log("Error al preparar la consulta de puntos en premios.php: " . $conn->error);
}


echo "DEBUG: Intentando preparar la sentencia para SP_ListarPremiosDisponibles().\n";

$stmt_premios = $conn->prepare("CALL SP_ListarPremiosDisponibles()");

if ($stmt_premios) {
    echo "DEBUG: Sentencia preparada con éxito. Intentando ejecutar.\n";
    if ($stmt_premios->execute()) {
        echo "DEBUG: Procedimiento ejecutado con éxito. Obteniendo resultados.\n";
        $result_premios = $stmt_premios->get_result();
        while ($row = $result_premios->fetch_assoc()) {
            $premios_disponibles[] = $row;
        }
        $stmt_premios->close();
        echo "DEBUG: Premios obtenidos y sentencia cerrada.\n";
    } else {
        echo "DEBUG ERROR: Error al ejecutar el procedimiento: " . $stmt_premios->error . "\n";
        error_log("Error al ejecutar SP_ListarPremiosDisponibles en premios.php: " . $stmt_premios->error);
    }
} else {
    echo "DEBUG ERROR: Error al preparar la sentencia para listar premios: " . $conn->error . "\n";
    error_log("Error al preparar SP_ListarPremiosDisponibles en premios.php: " . $conn->error);
}

if ($conn) $conn->close();
echo "DEBUG: Conexión a la base de datos cerrada al final de premios.php (pre-HTML).\n";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Premios - ReciclApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/user_style.css">
    <link rel="stylesheet" href="css/premios_style.css">
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <i class="fas fa-recycle"></i>
            <span>ReciclApp</span>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="usu.php">Mi Panel</a></li>
                <li><a href="logout.php" class="btn-login">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <div class="premios-page-container container">
        <section class="premios-header">
            <h1>Tienda de Premios</h1>
            <p>¡Canjea tus puntos por increíbles recompensas!</p>
            <div class="user-points-display">
                Tus Puntos: <span id="userPointsDisplay"><?php echo htmlspecialchars(number_format($user_points, 0, ',', '.')); ?></span> <i class="fas fa-coins"></i>
            </div>
        </section>

        <section class="premios-grid-section">
            <?php if (!empty($premios_disponibles)): ?>
                <div class="premios-grid">
                    <?php foreach ($premios_disponibles as $premio):
                        $image_path = 'media/default_premio.png'; 

                   
                        switch ($premio['nombre']) {
                            case 'Bono $25.000 El Bombazo':
                                $image_path = 'media/bombazo.jpg';
                                break;
                            case 'Cupón de Café y Pan en Panadería Tolimá':
                                $image_path = 'media/cafe_pan.png';
                                break;
                            case 'Descuento 20% en Tierra Santa':
                                $image_path = 'media/tierrasanta.png';
                                break;
                            case 'Entradas para Cineland': 
                                $image_path = 'media/cineland.jpg';
                                break;
                            case 'Recarga Celular $10.000 (Operador Local)':
                                $image_path = 'media/recarga.png';
                                break;
                            case 'Kit Ecológico de Bambú':
                                $image_path = 'media/bambu.png';
                                break;
                            case 'Desayuno Sorpresa a Domicilio':
                                $image_path = 'media/desayuno.jpg';
                                break;
                            case 'Un Mes Gratis de Gimnasio':
                                $image_path = 'media/hym.png';
                                break;
                            case 'Asesoría en Huerto Casero Sostenible':
                                $image_path = 'media/huerto.jpg';
                                break;
                            case 'Tarjeta de Regalo $50.000 Koaj':
                                $image_path = 'media/tarjeta-de-regalo-de-50000.jpg';
                                break;
                            case 'Vale de $30.000 en Mercadiario':
                                $image_path = 'media/cupon.jpg';
                                break;
                            case 'Consola PlayStation 5 (PS5) - Sorteo Mensual':
                                $image_path = 'media/ps5.png';
                                break;
                         
                            default:
                                $image_path = 'media/default_premio.png'; 
                                break;
                        }
                    ?>
                        <div class="premio-card">
                            <div class="premio-image-container">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($premio['nombre']); ?>">
                            </div>
                            <div class="premio-info">
                                <h3><?php echo htmlspecialchars($premio['nombre']); ?></h3>
                                <p class="descripcion"><?php echo htmlspecialchars($premio['descripcion']); ?></p>
                                <div class="premio-costo">
                                    <i class="fas fa-coins"></i> <?php echo htmlspecialchars(number_format($premio['puntos_requeridos'], 0, ',', '.')); ?> puntos
                                </div>
                                <button class="btn-canjear"
                                        data-id-premio="<?php echo htmlspecialchars($premio['id_premio']); ?>"
                                        data-puntos-requeridos="<?php echo htmlspecialchars($premio['puntos_requeridos']); ?>"
                                        data-nombre-premio="<?php echo htmlspecialchars($premio['nombre']); ?>"
                                        <?php echo ($user_points < $premio['puntos_requeridos']) ? 'disabled' : ''; ?>>
                                    Canjear Ahora
                                </button>
                                <?php if ($user_points < $premio['puntos_requeridos']): ?>
                                    <p class="not-enough-points">Puntos insuficientes</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-premios">No hay premios disponibles en este momento. ¡Sigue reciclando para ganar más puntos!</p>
            <?php endif; ?>
            <div class="message-area" id="rewardMessageArea"></div>
        </section>
    </div>

    <footer>
        <div class="container"><div class="footer-bottom"><p>&copy; <?php echo date("Y"); ?> ReciclApp. Todos los derechos reservados.</p></div></div>
    </footer>

    <script>
 
        let publicIp = 'UNKNOWN';
        document.addEventListener('DOMContentLoaded', function() {
            fetch('https://api.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    publicIp = data.ip;
                })
                .catch(error => {
                    console.error('Error al obtener la IP pública:', error);
                });

            // Lógica para el canje de premios
            const premiosGrid = document.querySelector('.premios-grid');
            if (premiosGrid) {
                premiosGrid.addEventListener('click', function(event) {
                    const targetButton = event.target.closest('.btn-canjear');
                    if (targetButton && !targetButton.disabled) {
                        const idPremio = targetButton.dataset.idPremio;
                        const puntosRequeridos = targetButton.dataset.puntosRequeridos;
                        const nombrePremio = targetButton.dataset.nombrePremio;

                        if (confirm(`¿Estás seguro de que quieres canjear "${nombrePremio}" por ${puntosRequeridos} puntos?`)) {
                            canjearPremio(idPremio, puntosRequeridos, nombrePremio, publicIp, targetButton);
                        }
                    }
                });
            }
        });

        function canjearPremio(idPremio, puntosRequeridos, nombrePremio, ipPublica, buttonElement) {
            const rewardMessageArea = document.getElementById('rewardMessageArea');
            rewardMessageArea.innerHTML = ''; 

            fetch('canjear_premio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_premio=${idPremio}&puntos_requeridos=${puntosRequeridos}&nombre_premio=${encodeURIComponent(nombrePremio)}&ip_publica=${encodeURIComponent(ipPublica)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    rewardMessageArea.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    const userPointsElement = document.getElementById('userPointsDisplay');
                    if (userPointsElement && data.new_points !== undefined) {
                        userPointsElement.textContent = data.new_points.toLocaleString('es-CO');
                    }
                    buttonElement.disabled = true;
                    buttonElement.textContent = 'Canjeado!';
                    buttonElement.style.backgroundColor = '#ccc';
                    buttonElement.style.cursor = 'not-allowed';

                    setTimeout(() => {
                         window.location.reload();
                    }, 1500);

                } else {
                    rewardMessageArea.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                    if (data.message.includes('Puntos insuficientes')) {
                        buttonElement.disabled = true;
                        if (!buttonElement.nextElementSibling || !buttonElement.nextElementSibling.classList.contains('not-enough-points')) {
                             const insuf = document.createElement('p');
                             insuf.classList.add('not-enough-points');
                             insuf.textContent = 'Puntos insuficientes';
                             buttonElement.parentNode.appendChild(insuf);
                        }
                    }
                }
                setTimeout(() => {
                    rewardMessageArea.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                rewardMessageArea.innerHTML = `<div class="alert alert-error">Ocurrió un error al canjear el premio.</div>`;
                setTimeout(() => {
                    rewardMessageArea.innerHTML = '';
                }, 5000);
            });
        }
    </script>
</body>
</html>
<?php

if ($conn) {
    echo "DEBUG: Conexión a la base de datos cerrada al final de premios.php.\n";
    $conn->close();
}
?>