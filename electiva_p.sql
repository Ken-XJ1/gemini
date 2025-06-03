-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-05-2025 a las 14:35:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `electiva_p`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_CanjearPremio` (IN `p_id_usuario` INT, IN `p_id_premio` INT, IN `p_puntos_usados` INT, IN `p_ip_cliente` VARCHAR(45))   BEGIN
    DECLARE v_current_points INT;
    DECLARE v_premio_cost INT;
    DECLARE v_premio_nombre VARCHAR(255);

    -- Iniciar la transacción
    START TRANSACTION;

    -- 1. Verificar puntos del usuario y bloquear la fila para evitar concurrencia
    SELECT puntos_acumulados INTO v_current_points FROM usuarios WHERE id_usuario = p_id_usuario FOR UPDATE;

    -- 2. Obtener costo y nombre del premio
    SELECT costo_puntos, nombre INTO v_premio_cost, v_premio_nombre FROM premios WHERE id_premio = p_id_premio;

    -- Verificar si el usuario tiene suficientes puntos
    IF v_current_points >= p_puntos_usados THEN
        -- 3. Registrar el canje en la tabla 'canjes_premios'
        INSERT INTO canjes_premios (id_usuario, id_premio, fecha_canje, puntos_usados, estado)
        VALUES (p_id_usuario, p_id_premio, NOW(), p_puntos_usados, 'pendiente'); -- O 'completado' si el canje es instantáneo

        -- 4. Actualizar los puntos del usuario
        UPDATE usuarios
        SET puntos_acumulados = puntos_acumulados - p_puntos_usados
        WHERE id_usuario = p_id_usuario;

        -- 5. Registrar la auditoría de éxito
        INSERT INTO auditoria (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen)
        VALUES (p_id_usuario, 'Canje de Premio', 'canjes_premios', LAST_INSERT_ID(), CONCAT('Canjeado premio "', v_premio_nombre, '" (ID: ', p_id_premio, ') por ', p_puntos_usados, ' puntos.'), p_ip_cliente);

        -- Confirmar la transacción
        COMMIT;
    ELSE
        -- Registrar la auditoría de fallo por puntos insuficientes
        INSERT INTO auditoria (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion, ip_origen)
        VALUES (p_id_usuario, 'Intento de Canje Fallido', 'canjes_premios', NULL, CONCAT('Intento de canje del premio "', v_premio_nombre, '" (ID: ', p_id_premio, ') por ', p_puntos_usados, ' puntos. Puntos insuficientes. Puntos actuales: ', v_current_points), p_ip_cliente);

        -- Revertir la transacción
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Puntos insuficientes para canjear este premio.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SP_ListarPremiosDisponibles` ()   BEGIN
    SELECT id_premio, nombre, descripcion, costo_puntos AS puntos_requeridos
    FROM premios
    ORDER BY costo_puntos ASC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id_actividad` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_actividad` datetime NOT NULL,
  `puntos_por_participacion` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id_actividad`, `nombre`, `descripcion`, `fecha_actividad`, `puntos_por_participacion`) VALUES
(1, 'Charla publica', 'se llevara acabo en el malecon y se tendra a cabo una charla respecto la web y sus usarios', '2025-05-30 16:00:00', 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_registro_auditoria` int(11) NOT NULL,
  `id_usuario_afectado` varchar(20) DEFAULT NULL,
  `accion_realizada` varchar(255) NOT NULL,
  `tabla_modificada` varchar(100) DEFAULT NULL,
  `id_registro_modificado` varchar(50) DEFAULT NULL,
  `detalles_accion` text DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_hora_auditoria` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_registro_auditoria`, `id_usuario_afectado`, `accion_realizada`, `tabla_modificada`, `id_registro_modificado`, `detalles_accion`, `ip_origen`, `fecha_hora_auditoria`) VALUES
(1, '00000000', 'NUEVO REGISTRO DE USUARIO', 'usuarios', '00000000', 'Se ha registrado un nuevo usuario con ID: 00000000 y correo: prueba@gmail.com.', NULL, '2025-05-28 03:44:35'),
(2, '00000000', 'LOGIN FALLIDO (CREDENCIALES INCORRECTAS)', 'usuarios', '00000000', 'Intento de inicio de sesión fallido por credenciales incorrectas.', '::1', '2025-05-28 03:45:05'),
(3, '00000000', 'LOGIN FALLIDO (CREDENCIALES INCORRECTAS)', 'usuarios', '00000000', 'Intento de inicio de sesión fallido por credenciales incorrectas.', '::1', '2025-05-28 03:45:07'),
(4, '00000000', 'ESTADO DE USUARIO MODIFICADO', 'usuarios', '00000000', 'El estado del usuario con ID: 00000000 (prueba@gmail.com) cambió de \"activo\" a \"bloqueado\".', NULL, '2025-05-28 03:45:09'),
(5, '00000000', 'CUENTA BLOQUEADA', 'usuarios', '00000000', 'La cuenta del usuario con ID: 00000000 (prueba@gmail.com) fue bloqueada hasta 2025-05-28 10:50:09.', NULL, '2025-05-28 03:45:09'),
(6, '00000000', 'CUENTA BLOQUEADA (INTENTOS FALLIDOS)', 'usuarios', '00000000', 'Cuenta bloqueada automáticamente por exceso de intentos fallidos.', '::1', '2025-05-28 03:45:09'),
(7, '00000000', 'LOGIN FALLIDO (CREDENCIALES INCORRECTAS)', 'usuarios', '00000000', 'Intento de inicio de sesión fallido por credenciales incorrectas.', '::1', '2025-05-28 03:45:09'),
(8, '00000000', 'INTENTO DE LOGIN (CUENTA BLOQUEADA)', 'usuarios', '00000000', 'Intento de inicio de sesión fallido en cuenta bloqueada.', '::1', '2025-05-28 03:45:11'),
(9, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '::1', '2025-05-28 03:45:21'),
(10, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-28 04:01:48'),
(11, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-28 04:04:52'),
(12, '00000000', 'ESTADO DE USUARIO MODIFICADO', 'usuarios', '00000000', 'El estado del usuario con ID: 00000000 (prueba@gmail.com) cambió de \"bloqueado\" a \"activo\".', NULL, '2025-05-28 04:06:39'),
(13, '00000000', 'CUENTA DESBLOQUEADA', 'usuarios', '00000000', 'La cuenta del usuario con ID: 00000000 (prueba@gmail.com) fue desbloqueada.', NULL, '2025-05-28 04:06:39'),
(14, '00000000', 'LOGIN EXITOSO', 'usuarios', '00000000', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-28 04:06:39'),
(15, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-28 04:06:48'),
(16, NULL, 'LOGIN FALLIDO (EMAIL NO EXISTENTE)', NULL, NULL, 'Intento de inicio de sesión con un correo electrónico no registrado: kenneth@gmail.com', '179.1.24.41', '2025-05-28 14:43:07'),
(17, NULL, 'LOGIN FALLIDO (EMAIL NO EXISTENTE)', NULL, NULL, 'Intento de inicio de sesión con un correo electrónico no registrado: kenneth1002@gmail.com', '179.1.24.41', '2025-05-28 14:43:12'),
(18, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 14:43:21'),
(19, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 14:43:27'),
(20, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:46:41'),
(21, NULL, 'LOGIN FALLIDO (EMAIL NO EXISTENTE)', NULL, NULL, 'Intento de inicio de sesión con un correo electrónico no registrado: kenneth@gmail.com', '179.1.24.41', '2025-05-28 15:48:04'),
(22, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:48:08'),
(23, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:48:38'),
(24, NULL, 'LOGIN FALLIDO (EMAIL NO EXISTENTE)', NULL, NULL, 'Intento de inicio de sesión con un correo electrónico no registrado: kenneth@gmail.com', '179.1.24.41', '2025-05-28 15:49:46'),
(25, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:49:50'),
(26, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:52:39'),
(27, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:52:47'),
(28, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:53:33'),
(29, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.24.41', '2025-05-28 15:55:55'),
(30, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '191.156.43.126', '2025-05-29 08:40:55'),
(31, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-30 01:53:24'),
(32, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-30 02:08:37'),
(33, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.201', '2025-05-30 02:16:37'),
(34, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.205', '2025-05-30 02:46:01'),
(35, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-30 03:00:26'),
(36, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.203', '2025-05-30 03:02:55'),
(37, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.201', '2025-05-30 03:27:41'),
(38, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.201', '2025-05-30 04:04:04'),
(39, '1077427620', 'LOGIN EXITOSO', 'usuarios', '1077427620', 'El usuario ha iniciado sesión correctamente.', '179.1.126.205', '2025-05-30 07:28:52'),
(40, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.205', '2025-05-30 07:29:06'),
(41, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.201', '2025-05-30 07:34:47'),
(42, '1077427621', 'LOGIN EXITOSO', 'usuarios', '1077427621', 'El usuario ha iniciado sesión correctamente.', '179.1.126.201', '2025-05-30 07:35:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canjes_premios`
--

CREATE TABLE `canjes_premios` (
  `id_canje` int(11) NOT NULL,
  `id_usuario` varchar(20) NOT NULL,
  `id_premio` int(11) NOT NULL,
  `fecha_canje` datetime DEFAULT current_timestamp(),
  `estado` varchar(50) DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_recoleccion`
--

CREATE TABLE `detalle_recoleccion` (
  `id_detalle` int(11) NOT NULL,
  `id_recoleccion` int(11) NOT NULL,
  `id_tipo_residuo` int(11) NOT NULL,
  `cantidad_kg` decimal(10,2) DEFAULT NULL,
  `puntos_ganados` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logros`
--

CREATE TABLE `logros` (
  `id_logro` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `puntos_requeridos` int(11) DEFAULT 0,
  `nombre_logro` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

CREATE TABLE `premios` (
  `id_premio` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `costo_puntos` int(11) NOT NULL,
  `cantidad_disponible` int(11) DEFAULT 0,
  `ruta_imagen` varchar(255) DEFAULT 'uploads/default_premio.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `premios`
--

INSERT INTO `premios` (`id_premio`, `nombre`, `descripcion`, `costo_puntos`, `cantidad_disponible`, `ruta_imagen`) VALUES
(1, 'Entradas para Cineland', 'Disfruta de la magia del cine con entradas dobles para Cineland.', 3000, 0, 'media/entradas_cineland.png'),
(2, 'Un Mes Gratis de Gimnasio', 'Mantente en forma con un mes de acceso gratuito a un gimnasio aliado en Quibdó.', 5000, 0, 'media/gym_gratis.png'),
(3, 'Vale de $30.000 en Mercadiario', 'Descuento de $30.000 pesos en compras de supermercado en Mercadiario, Quibdó. Mínimo de compra $100.000.', 1500, 0, 'media/vale_mercadiario.png'),
(4, 'Descuento 20% en Tierra Santa', 'Aplica en tu próxima compra de ropa en Tienda Tierra Santa, Quibdó. No acumulable con otras ofertas.', 1200, 0, 'media/descuento_tierra_santa.png'),
(5, 'Bono $25.000 El Bombazo', 'Bono canjeable por $25.000 pesos en cualquier producto de El Bombazo, Quibdó.', 1000, 0, 'media/bono_el_bombazo.png'),
(6, 'Tarjeta de Regalo $50.000 Koaj', 'Tarjeta de regalo digital por $50.000 pesos para usar en Koaj Quibdó.', 2500, 0, 'media/cupon.jpg'),
(8, 'Recarga Celular $10.000 (Operador Local)', 'Recarga de saldo para tu celular de $10.000 pesos (válido para Claro, Tigo, Movistar Colombia).', 400, 0, 'media/recarga_celular.png'),
(9, 'Kit Ecológico de Bambú', 'Incluye cepillo de dientes de bambú, set de cubiertos reutilizables y bolsa de tela.', 600, 0, 'media/kit_bambu.png'),
(11, 'Desayuno Sorpresa a Domicilio', 'Un delicioso desayuno sorpresa entregado en tu casa en Quibdó.', 900, 0, 'media/desayuno_domicilio.png'),
(12, 'Asesoría en Huerto Casero Sostenible', 'Una hora de asesoría personalizada para crear tu propio huerto urbano.', 500, 0, 'media/huerto_casero.png'),
(13, 'Consola PlayStation 5 (PS5) - Sorteo Mensual', 'Participa en el sorteo mensual de una PS5. ¡Tus puntos son tu boleto!', 75000, 0, 'uploads/default_premio.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recolecciones`
--

CREATE TABLE `recolecciones` (
  `id_recoleccion` int(11) NOT NULL,
  `id_usuario` varchar(20) NOT NULL,
  `fecha_recoleccion` datetime DEFAULT current_timestamp(),
  `peso_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `puntos_ganados` int(11) NOT NULL DEFAULT 0,
  `estado` varchar(50) DEFAULT 'pendiente',
  `observaciones_usuario` text DEFAULT NULL,
  `observaciones_admin` text DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_residuos`
--

CREATE TABLE `tipos_residuos` (
  `id_tipo_residuo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `puntos_por_kg` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` varchar(50) DEFAULT 'usuario',
  `puntos_acumulados` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` varchar(50) DEFAULT 'activo',
  `intentos_fallidos` int(11) DEFAULT 0,
  `ultimo_intento_fallido` datetime DEFAULT NULL,
  `bloqueo_hasta` datetime DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `puntos_totales` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `email`, `contrasena_hash`, `rol`, `puntos_acumulados`, `fecha_registro`, `estado`, `intentos_fallidos`, `ultimo_intento_fallido`, `bloqueo_hasta`, `fecha_nacimiento`, `puntos_totales`) VALUES
('00000000', 'Prueba', 'bloqueo', 'prueba@gmail.com', '$2y$10$VIUNuyEJ2HkDILalED/dReqUE036JrapL.70CxYyDSpaACmAv1VA.', 'usuario', 0, '2025-05-28 03:44:35', 'activo', 0, NULL, NULL, NULL, 0),
('1077427620', 'Kenneth Ranses', 'Arriaga Wnfried', 'kennethr1002@gmail.com', '$2y$10$NyVsHgFrrqT.G3KfTliiN.P6mbtvkOPUuAFCTI00lwdrLKwcMwbei', 'usuario', 0, '2025-05-28 02:29:09', 'activo', 0, NULL, NULL, NULL, 0),
('1077427621', 'Ramses', 'Wnfried', 'admin@gmail.com', '$2y$10$0qA9XF2qtTy3aAtFVQJunenRZ3zu0bkuMeQTj9HUtKEH2WR0vBMh2', 'administrador', 0, '2025-05-28 02:38:02', 'activo', 0, NULL, NULL, NULL, 0);

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `actualizar_usuario_auditoria` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
    -- Registro de cambio de rol
    IF OLD.rol <> NEW.rol THEN
        INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
        VALUES (
            OLD.id_usuario,
            'ROL DE USUARIO MODIFICADO',
            'usuarios',
            OLD.id_usuario,
            CONCAT('El rol del usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') cambió de "', OLD.rol, '" a "', NEW.rol, '".')
        );
    END IF;

    -- Registro de cambio de estado (activo/inactivo)
    IF OLD.estado <> NEW.estado THEN
        INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
        VALUES (
            OLD.id_usuario,
            'ESTADO DE USUARIO MODIFICADO',
            'usuarios',
            OLD.id_usuario,
            CONCAT('El estado del usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') cambió de "', OLD.estado, '" a "', NEW.estado, '".')
        );
    END IF;

    -- Registro de cuenta bloqueada
    IF OLD.bloqueo_hasta IS NULL AND NEW.bloqueo_hasta IS NOT NULL THEN
        INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
        VALUES (
            OLD.id_usuario,
            'CUENTA BLOQUEADA',
            'usuarios',
            OLD.id_usuario,
            CONCAT('La cuenta del usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') fue bloqueada hasta ', NEW.bloqueo_hasta, '.')
        );
    END IF;

    -- Registro de cuenta desbloqueada (ya sea manual o por tiempo)
    IF OLD.bloqueo_hasta IS NOT NULL AND NEW.bloqueo_hasta IS NULL THEN
         INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
        VALUES (
            OLD.id_usuario,
            'CUENTA DESBLOQUEADA',
            'usuarios',
            OLD.id_usuario,
            CONCAT('La cuenta del usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') fue desbloqueada.')
        );
    END IF;
    
    -- Opcional: Registro de cambio de contraseña (si esto se maneja directamente en la DB por un admin)
    -- IF OLD.contrasena_hash <> NEW.contrasena_hash THEN
    --    INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
    --    VALUES (
    --        OLD.id_usuario,
    --        'CONTRASEÑA MODIFICADA',
    --        'usuarios',
    --        OLD.id_usuario,
    --        CONCAT('La contraseña del usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') fue modificada.')
    --    );
    -- END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `crear_usuario_auditoria` AFTER INSERT ON `usuarios` FOR EACH ROW BEGIN
    INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
    VALUES (
        NEW.id_usuario,
        'NUEVO REGISTRO DE USUARIO',
        'usuarios',
        NEW.id_usuario,
        CONCAT('Se ha registrado un nuevo usuario con ID: ', NEW.id_usuario, ' y correo: ', NEW.email, '.')
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `eliminar_usuario_auditoria` AFTER DELETE ON `usuarios` FOR EACH ROW BEGIN
    INSERT INTO `auditoria` (id_usuario_afectado, accion_realizada, tabla_modificada, id_registro_modificado, detalles_accion)
    VALUES (
        OLD.id_usuario,
        'USUARIO ELIMINADO',
        'usuarios',
        OLD.id_usuario,
        CONCAT('El usuario con ID: ', OLD.id_usuario, ' (', OLD.email, ') ha sido eliminado del sistema.')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_logros`
--

CREATE TABLE `usuario_logros` (
  `id_logro_usuario` int(11) NOT NULL,
  `id_usuario` varchar(20) NOT NULL,
  `id_logro` int(11) NOT NULL,
  `fecha_obtencion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_historial_recolecciones_completo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_historial_recolecciones_completo` (
`id_recoleccion` int(11)
,`id_usuario` varchar(20)
,`nombre_usuario` varchar(100)
,`apellido_usuario` varchar(100)
,`email_usuario` varchar(255)
,`fecha_solicitud` datetime
,`fecha_recoleccion` datetime
,`estado` varchar(50)
,`detalles_residuos` mediumtext
,`observaciones_admin` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_puntos_por_residuo_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_puntos_por_residuo_usuario` (
`id_usuario` varchar(20)
,`nombre_usuario` varchar(100)
,`apellido_usuario` varchar(100)
,`nombre_residuo` text
,`total_kg_reciclado` decimal(32,2)
,`puntos_obtenidos` decimal(37,4)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_recolecciones_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_recolecciones_pendientes` (
`id_recoleccion` int(11)
,`id_usuario` varchar(20)
,`nombre_usuario` varchar(100)
,`apellido_usuario` varchar(100)
,`email_usuario` varchar(255)
,`fecha_solicitud` datetime
,`fecha_recoleccion` datetime
,`estado` varchar(50)
,`detalles_residuos` mediumtext
,`observaciones_admin` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_usuarios_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_usuarios_activos` (
`id_usuario` varchar(20)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`email` varchar(255)
,`rol` varchar(50)
,`puntos_acumulados` int(11)
,`fecha_registro` datetime
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_historial_recolecciones_completo`
--
DROP TABLE IF EXISTS `vista_historial_recolecciones_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_historial_recolecciones_completo`  AS SELECT `r`.`id_recoleccion` AS `id_recoleccion`, `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre_usuario`, `u`.`apellido` AS `apellido_usuario`, `u`.`email` AS `email_usuario`, `r`.`fecha_solicitud` AS `fecha_solicitud`, `r`.`fecha_recoleccion` AS `fecha_recoleccion`, `r`.`estado` AS `estado`, group_concat(concat(`tr`.`descripcion`,' (',`dr`.`cantidad_kg`,' kg)') separator ',') AS `detalles_residuos`, `r`.`observaciones_admin` AS `observaciones_admin` FROM (((`recolecciones` `r` join `usuarios` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) left join `detalle_recoleccion` `dr` on(`r`.`id_recoleccion` = `dr`.`id_recoleccion`)) left join `tipos_residuos` `tr` on(`dr`.`id_tipo_residuo` = `tr`.`id_tipo_residuo`)) GROUP BY `r`.`id_recoleccion`, `u`.`id_usuario`, `u`.`nombre`, `u`.`apellido`, `u`.`email`, `r`.`fecha_solicitud`, `r`.`fecha_recoleccion`, `r`.`estado`, `r`.`observaciones_admin` ORDER BY `r`.`fecha_solicitud` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_puntos_por_residuo_usuario`
--
DROP TABLE IF EXISTS `vista_puntos_por_residuo_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_puntos_por_residuo_usuario`  AS SELECT `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre_usuario`, `u`.`apellido` AS `apellido_usuario`, `tr`.`descripcion` AS `nombre_residuo`, sum(`dr`.`cantidad_kg`) AS `total_kg_reciclado`, sum(`dr`.`cantidad_kg` * `tr`.`puntos_por_kg`) AS `puntos_obtenidos` FROM (((`usuarios` `u` join `recolecciones` `r` on(`u`.`id_usuario` = `r`.`id_usuario`)) join `detalle_recoleccion` `dr` on(`r`.`id_recoleccion` = `dr`.`id_recoleccion`)) join `tipos_residuos` `tr` on(`dr`.`id_tipo_residuo` = `tr`.`id_tipo_residuo`)) WHERE `r`.`estado` = 'completada' GROUP BY `u`.`id_usuario`, `u`.`nombre`, `u`.`apellido`, `tr`.`descripcion` ORDER BY `u`.`id_usuario` ASC, `tr`.`descripcion` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_recolecciones_pendientes`
--
DROP TABLE IF EXISTS `vista_recolecciones_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_recolecciones_pendientes`  AS SELECT `r`.`id_recoleccion` AS `id_recoleccion`, `u`.`id_usuario` AS `id_usuario`, `u`.`nombre` AS `nombre_usuario`, `u`.`apellido` AS `apellido_usuario`, `u`.`email` AS `email_usuario`, `r`.`fecha_solicitud` AS `fecha_solicitud`, `r`.`fecha_recoleccion` AS `fecha_recoleccion`, `r`.`estado` AS `estado`, group_concat(concat(`tr`.`descripcion`,' (',`dr`.`cantidad_kg`,' kg)') separator ',') AS `detalles_residuos`, `r`.`observaciones_admin` AS `observaciones_admin` FROM (((`recolecciones` `r` join `usuarios` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) left join `detalle_recoleccion` `dr` on(`r`.`id_recoleccion` = `dr`.`id_recoleccion`)) left join `tipos_residuos` `tr` on(`dr`.`id_tipo_residuo` = `tr`.`id_tipo_residuo`)) WHERE `r`.`estado` = 'pendiente' GROUP BY `r`.`id_recoleccion`, `u`.`id_usuario`, `u`.`nombre`, `u`.`apellido`, `u`.`email`, `r`.`fecha_solicitud`, `r`.`fecha_recoleccion`, `r`.`estado`, `r`.`observaciones_admin` ORDER BY `r`.`fecha_solicitud` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_usuarios_activos`
--
DROP TABLE IF EXISTS `vista_usuarios_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_usuarios_activos`  AS SELECT `usuarios`.`id_usuario` AS `id_usuario`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`rol` AS `rol`, `usuarios`.`puntos_acumulados` AS `puntos_acumulados`, `usuarios`.`fecha_registro` AS `fecha_registro` FROM `usuarios` WHERE `usuarios`.`estado` = 'activo' ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id_actividad`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_registro_auditoria`),
  ADD KEY `idx_id_usuario_afectado` (`id_usuario_afectado`),
  ADD KEY `idx_accion_realizada` (`accion_realizada`);

--
-- Indices de la tabla `canjes_premios`
--
ALTER TABLE `canjes_premios`
  ADD PRIMARY KEY (`id_canje`),
  ADD KEY `canjes_premios_ibfk_1` (`id_usuario`),
  ADD KEY `canjes_premios_ibfk_2` (`id_premio`);

--
-- Indices de la tabla `detalle_recoleccion`
--
ALTER TABLE `detalle_recoleccion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `detalle_recoleccion_ibfk_1` (`id_recoleccion`),
  ADD KEY `detalle_recoleccion_ibfk_2` (`id_tipo_residuo`);

--
-- Indices de la tabla `logros`
--
ALTER TABLE `logros`
  ADD PRIMARY KEY (`id_logro`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`id_premio`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  ADD PRIMARY KEY (`id_recoleccion`),
  ADD KEY `recolecciones_ibfk_1` (`id_usuario`);

--
-- Indices de la tabla `tipos_residuos`
--
ALTER TABLE `tipos_residuos`
  ADD PRIMARY KEY (`id_tipo_residuo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuario_logros`
--
ALTER TABLE `usuario_logros`
  ADD PRIMARY KEY (`id_logro_usuario`),
  ADD KEY `usuario_logros_ibfk_1` (`id_usuario`),
  ADD KEY `usuario_logros_ibfk_2` (`id_logro`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_registro_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `canjes_premios`
--
ALTER TABLE `canjes_premios`
  MODIFY `id_canje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_recoleccion`
--
ALTER TABLE `detalle_recoleccion`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logros`
--
ALTER TABLE `logros`
  MODIFY `id_logro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `id_premio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  MODIFY `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_residuos`
--
ALTER TABLE `tipos_residuos`
  MODIFY `id_tipo_residuo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario_logros`
--
ALTER TABLE `usuario_logros`
  MODIFY `id_logro_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `canjes_premios`
--
ALTER TABLE `canjes_premios`
  ADD CONSTRAINT `canjes_premios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `canjes_premios_ibfk_2` FOREIGN KEY (`id_premio`) REFERENCES `premios` (`id_premio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_recoleccion`
--
ALTER TABLE `detalle_recoleccion`
  ADD CONSTRAINT `detalle_recoleccion_ibfk_1` FOREIGN KEY (`id_recoleccion`) REFERENCES `recolecciones` (`id_recoleccion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalle_recoleccion_ibfk_2` FOREIGN KEY (`id_tipo_residuo`) REFERENCES `tipos_residuos` (`id_tipo_residuo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  ADD CONSTRAINT `recolecciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_logros`
--
ALTER TABLE `usuario_logros`
  ADD CONSTRAINT `usuario_logros_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_logros_ibfk_2` FOREIGN KEY (`id_logro`) REFERENCES `logros` (`id_logro`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
