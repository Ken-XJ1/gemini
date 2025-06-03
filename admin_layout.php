<?php
// admin_layout.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

$admin_nombre = $_SESSION['user_name'] ?? 'Admin';
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($titulo_pagina ?? 'Panel de Admin'); ?> - ReciclApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="logo">
                <i class="fas fa-recycle"></i>
                <span>ReciclApp Admin</span>
            </div>
            <ul class="nav-links">
                <li>
                    <a href="admin.php" class="<?php echo ($pagina_actual == 'admin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_gestion_usuarios.php" class="<?php echo ($pagina_actual == 'admin_gestion_usuarios.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Gestión de Usuarios
                    </a>
                </li>
                <li>
                    <a href="admin_validaciones.php" class="<?php echo ($pagina_actual == 'admin_validaciones.php') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Validaciones
                    </a>
                </li>
     
                    <li>
                    <a href="admin_actividades.php" class="<?php echo ($pagina_actual == 'admin_actividades.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Actividades
                    </a>
                </li>
                <li>
                    <a href="admin_auditoria.php" class="<?php echo ($pagina_actual == 'admin_auditoria.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> Auditoría
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </aside>
        <main class="admin-content">
            <header class="admin-header">
                <h1><?php echo $titulo_pagina ?? 'Dashboard'; ?></h1>
                <div class="admin-user-info">
                    <span>Hola, <?php echo htmlspecialchars($admin_nombre); ?></span> | 
                    <a href="logout.php">Cerrar Sesión</a>
                </div>
            </header>
            <section class="main-content"></section>