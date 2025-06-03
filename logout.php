<?php
// logout.php
session_start(); // Inicia la sesión para poder acceder a ella

// session_unset() libera todas las variables de sesión.
session_unset();

// session_destroy() destruye toda la información registrada de una sesión.
session_destroy();

// Redirige al usuario a la página de login.
header("Location: login.php");
exit(); // Asegura que el script se detenga después de la redirección.
?>