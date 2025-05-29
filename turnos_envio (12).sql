-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-05-2025 a las 05:02:06
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `turnos_envio`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `asignar_turno` (IN `p_id_cliente` INT, IN `p_tipo_tramite` ENUM('envío','retiro'), IN `p_id_sucursal` INT, OUT `p_numero_turno` INT, OUT `p_fecha_turno` DATE, OUT `p_hora_turno` TIME, OUT `p_mensaje` VARCHAR(100))   BEGIN
    DECLARE v_max_turnos INT;
    DECLARE v_turnos_hoy INT;
    DECLARE v_hora_apertura TIME;
    DECLARE v_hora_cierre TIME;
    DECLARE v_dia_semana VARCHAR(10);
    DECLARE v_horario_valido INT;
    DECLARE v_estado_cliente VARCHAR(20);
    
    -- Verificar estado del cliente
    SELECT estado INTO v_estado_cliente FROM clientes WHERE id_cliente = p_id_cliente;
    
    IF v_estado_cliente = 'inactivo' THEN
        SET p_mensaje = 'Error: Cliente inactivo por múltiples turnos ausentes';
        SET p_numero_turno = NULL;
        SET p_fecha_turno = NULL;
        SET p_hora_turno = NULL;
    ELSEIF v_estado_cliente != 'activo' THEN
        SET p_mensaje = 'Error: Cliente no está activo';
        SET p_numero_turno = NULL;
        SET p_fecha_turno = NULL;
        SET p_hora_turno = NULL;
    ELSE
        -- Obtener parámetros de la sucursal
        SELECT capacidad_maxima, horario_apertura, horario_cierre 
        INTO v_max_turnos, v_hora_apertura, v_hora_cierre
        FROM sucursales WHERE id_sucursal = p_id_sucursal;
        
        -- Verificar día hábil
        SET v_dia_semana = DAYNAME(CURDATE());
        
        SELECT COUNT(*) INTO v_horario_valido
        FROM horarios_sucursal
        WHERE id_sucursal = p_id_sucursal
        AND dia_semana = v_dia_semana
        AND activo = 1;
        
        IF v_horario_valido = 0 THEN
            SET p_mensaje = 'Error: La sucursal no atiende este día';
            SET p_numero_turno = NULL;
            SET p_fecha_turno = NULL;
            SET p_hora_turno = NULL;
        ELSE
            -- Contar turnos para hoy
            SELECT COUNT(*) INTO v_turnos_hoy
            FROM turnos 
            WHERE id_sucursal = p_id_sucursal AND fecha_turno = CURDATE();
            
            -- Verificar disponibilidad
            IF v_turnos_hoy >= v_max_turnos THEN
                SET p_mensaje = 'Error: No hay disponibilidad de turnos para hoy';
                SET p_numero_turno = NULL;
                SET p_fecha_turno = NULL;
                SET p_hora_turno = NULL;
            ELSE
                -- Calcular hora estimada
                SET p_numero_turno = v_turnos_hoy + 1;
                SET p_fecha_turno = CURDATE();
                
                -- Calcular hora basada en turnos anteriores
                SELECT DATE_ADD(MAX(hora_turno), INTERVAL 15 MINUTE) INTO p_hora_turno
                FROM turnos 
                WHERE id_sucursal = p_id_sucursal AND fecha_turno = CURDATE();
                
                -- Si es el primer turno del día, usar hora apertura
                IF p_hora_turno IS NULL THEN
                    SET p_hora_turno = v_hora_apertura;
                END IF;
                
                -- Insertar el nuevo turno
                INSERT INTO turnos (
                    id_cliente, tipo_tramite, id_sucursal, numero_turno, 
                    estado, fecha_turno, hora_turno
                ) VALUES (
                    p_id_cliente, p_tipo_tramite, p_id_sucursal, p_numero_turno,
                    'pendiente', p_fecha_turno, p_hora_turno
                );
                
                SET p_mensaje = 'Turno asignado correctamente';
            END IF;
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `cambiar_estado_turno` (IN `p_id_turno` INT, IN `p_nuevo_estado` ENUM('pendiente','confirmado','completado','cancelado','ausente'), IN `p_id_empleado` INT, OUT `p_resultado` VARCHAR(100))   BEGIN
    DECLARE v_estado_actual VARCHAR(20);
    DECLARE v_id_sucursal INT;
    
    -- Obtener estado actual y sucursal
    SELECT estado, id_sucursal INTO v_estado_actual, v_id_sucursal
    FROM turnos WHERE id_turno = p_id_turno;
    
    -- Verificar si el turno existe
    IF v_estado_actual IS NULL THEN
        SET p_resultado = 'Error: Turno no encontrado';
    -- Verificar si el empleado pertenece a la sucursal del turno
    ELSEIF p_id_empleado IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM empleados 
        WHERE id_empleado = p_id_empleado AND id_sucursal = v_id_sucursal
    ) THEN
        SET p_resultado = 'Error: Empleado no pertenece a la sucursal del turno';
    ELSE
        -- Actualizar el turno
        UPDATE turnos 
        SET estado = p_nuevo_estado,
            id_empleado = p_id_empleado,
            fecha_atencion = IF(p_nuevo_estado = 'completado', NOW(), fecha_atencion),
            tiempo_atencion = IF(p_nuevo_estado = 'completado', 
                                TIMESTAMPDIFF(MINUTE, CONCAT(fecha_turno, ' ', hora_turno), NOW()),
                                tiempo_atencion)
        WHERE id_turno = p_id_turno;
        
        SET p_resultado = CONCAT('Estado cambiado a: ', p_nuevo_estado);
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `desbloquear_cliente` (IN `p_id_cliente` INT, IN `p_id_admin` INT, OUT `p_resultado` VARCHAR(100))   BEGIN
    DECLARE v_admin_valido INT;
    
    -- Verificar que el administrador existe
    SELECT COUNT(*) INTO v_admin_valido FROM administradores WHERE id_admin = p_id_admin;
    
    IF v_admin_valido = 0 THEN
        SET p_resultado = 'Error: Credenciales de administrador inválidas';
    ELSEIF NOT EXISTS (SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente) THEN
        SET p_resultado = 'Error: Cliente no encontrado';
    ELSE
        -- Desbloquear al cliente
        UPDATE clientes 
        SET intentos_fallidos = 0, 
            bloqueado_hasta = NULL 
        WHERE id_cliente = p_id_cliente;
        
        -- Registrar en auditoría
        INSERT INTO auditoria (tabla_afectada, operacion, id_registro, valores_anteriores, valores_nuevos, usuario, ip)
        VALUES ('clientes', 'UPDATE', p_id_cliente, 
                CONCAT('intentos_fallidos>0;bloqueado_hasta NOT NULL'), 
                'intentos_fallidos=0;bloqueado_hasta=NULL',
                (SELECT usuario FROM administradores WHERE id_admin = p_id_admin),
                '127.0.0.1');
        
        SET p_resultado = 'Cliente desbloqueado exitosamente';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generar_reporte_diario` (IN `p_fecha` DATE, IN `p_id_sucursal` INT)   BEGIN
    SELECT 
        t.numero_turno,
        c.nombre AS cliente,
        t.tipo_tramite,
        t.estado,
        t.hora_turno AS hora_estimada,
        t.fecha_atencion AS hora_atencion,
        t.tiempo_atencion,
        e.nombre AS empleado
    FROM 
        turnos t
    JOIN 
        clientes c ON t.id_cliente = c.id_cliente
    LEFT JOIN 
        empleados e ON t.id_empleado = e.id_empleado
    WHERE 
        t.fecha_turno = p_fecha
        AND (p_id_sucursal IS NULL OR t.id_sucursal = p_id_sucursal)
    ORDER BY 
        t.numero_turno;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generar_reporte_eficiencia` (IN `p_fecha_inicio` DATE, IN `p_fecha_fin` DATE, IN `p_id_sucursal` INT)   BEGIN
    SELECT 
        s.nombre AS sucursal,
        e.nombre AS empleado,
        COUNT(t.id_turno) AS total_turnos,
        SUM(CASE WHEN t.estado = 'completado' THEN 1 ELSE 0 END) AS turnos_completados,
        SUM(CASE WHEN t.estado = 'ausente' THEN 1 ELSE 0 END) AS turnos_ausentes,
        AVG(t.tiempo_atencion) AS tiempo_promedio_atencion,
        (SUM(CASE WHEN t.estado = 'completado' THEN 1 ELSE 0 END) / COUNT(t.id_turno) * 100) AS porcentaje_eficiencia
    FROM 
        turnos t
    JOIN 
        sucursales s ON t.id_sucursal = s.id_sucursal
    LEFT JOIN 
        empleados e ON t.id_empleado = e.id_empleado
    WHERE 
        t.fecha_turno BETWEEN p_fecha_inicio AND p_fecha_fin
        AND (p_id_sucursal IS NULL OR t.id_sucursal = p_id_sucursal)
        AND t.estado IN ('completado', 'ausente')
    GROUP BY 
        s.nombre, e.nombre
    ORDER BY 
        porcentaje_eficiencia DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_cliente` (IN `p_nombre` VARCHAR(100), IN `p_cedula` VARCHAR(20), IN `p_telefono` VARCHAR(20), OUT `p_id_cliente` INT, OUT `p_codigo_verificacion` VARCHAR(6), OUT `p_mensaje` VARCHAR(100))   BEGIN
    DECLARE v_existe_telefono INT;
    DECLARE v_existe_cedula INT;
    DECLARE v_codigo VARCHAR(6);
    
    -- Verificar si el teléfono ya existe
    SELECT COUNT(*) INTO v_existe_telefono
    FROM clientes 
    WHERE telefono = p_telefono;
    
    -- Verificar si la cédula ya existe
    SELECT COUNT(*) INTO v_existe_cedula
    FROM clientes 
    WHERE cedula = p_cedula;
    
    IF v_existe_telefono > 0 THEN
        SET p_mensaje = 'Error: El teléfono ya está registrado';
        SET p_id_cliente = NULL;
        SET p_codigo_verificacion = NULL;
    ELSEIF v_existe_cedula > 0 THEN
        SET p_mensaje = 'Error: La cédula ya está registrada';
        SET p_id_cliente = NULL;
        SET p_codigo_verificacion = NULL;
    ELSE
        -- Generar código de verificación
        SET v_codigo = LPAD(FLOOR(RAND() * 1000000), 6, '0');
        
        -- Insertar nuevo cliente
        INSERT INTO clientes (nombre, cedula, telefono, codigo_verificacion, estado)
        VALUES (p_nombre, p_cedula, p_telefono, v_codigo, 'pendiente');
        
        SET p_id_cliente = LAST_INSERT_ID();
        SET p_codigo_verificacion = v_codigo;
        SET p_mensaje = 'Cliente registrado correctamente. Se ha enviado un código de verificación.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `verificar_cliente` (IN `p_id_cliente` INT, IN `p_codigo_verificacion` VARCHAR(6), OUT `p_resultado` BOOLEAN, OUT `p_mensaje` VARCHAR(100))   BEGIN
    DECLARE v_codigo_correcto VARCHAR(6);
    DECLARE v_estado_actual VARCHAR(20);

    -- Obtener código y estado actual
    SELECT codigo_verificacion, estado INTO v_codigo_correcto, v_estado_actual
    FROM clientes
    WHERE id_cliente = p_id_cliente;

    IF v_codigo_correcto IS NULL THEN
        SET p_resultado = FALSE;
        SET p_mensaje = 'Error: Cliente no encontrado';
    ELSEIF v_estado_actual = 'activo' THEN
        SET p_resultado = TRUE;
        SET p_mensaje = 'El cliente ya está verificado';
    ELSEIF v_codigo_correcto = p_codigo_verificacion THEN
        -- Actualizar estado del cliente
        UPDATE clientes
        SET verificado = 1,
            estado = 'activo',
            codigo_verificacion = NULL
        WHERE id_cliente = p_id_cliente;

        SET p_resultado = TRUE;
        SET p_mensaje = 'Cliente verificado correctamente';
    ELSE
        SET p_resultado = FALSE;
        SET p_mensaje = 'Error: Código de verificación incorrecto';
    END IF;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_tiempo_espera` (`p_id_sucursal` INT, `p_fecha` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_turnos_pendientes INT;
    DECLARE v_intervalo INT;
    
    -- Obtener intervalo entre turnos
    SELECT intervalo_minutos INTO v_intervalo
    FROM limites_turnos WHERE sucursal_id = p_id_sucursal;
    
    -- Contar turnos pendientes
    SELECT COUNT(*) INTO v_turnos_pendientes
    FROM turnos 
    WHERE id_sucursal = p_id_sucursal 
    AND fecha_turno = p_fecha
    AND estado = 'pendiente';
    
    -- Calcular tiempo estimado en minutos
    RETURN v_turnos_pendientes * v_intervalo;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `verificar_disponibilidad_turno` (`p_id_sucursal` INT, `p_fecha` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_turnos_asignados INT;
    DECLARE v_max_turnos INT;
    
    -- Obtener capacidad máxima de la sucursal
    SELECT capacidad_maxima INTO v_max_turnos
    FROM sucursales WHERE id_sucursal = p_id_sucursal;
    
    -- Contar turnos para la fecha
    SELECT COUNT(*) INTO v_turnos_asignados
    FROM turnos 
    WHERE id_sucursal = p_id_sucursal AND fecha_turno = p_fecha;
    
    -- Retornar disponibilidad
    RETURN v_max_turnos - v_turnos_asignados;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_admin`, `nombre`, `usuario`, `contrasena`) VALUES
(6, 'Ad', 'ad123', '$2y$10$6EtITly9FoOmuS6oS9mLYec0p81TNpK/Fgo0QDNoPVG9TeVSPUwcG');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `operacion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `valores_anteriores` text DEFAULT NULL,
  `valores_nuevos` text DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_auditoria`, `tabla_afectada`, `operacion`, `id_registro`, `valores_anteriores`, `valores_nuevos`, `usuario`, `fecha_hora`, `ip`) VALUES
(1, 'clientes', 'INSERT', 25, NULL, 'nombre=Fernando realr;telefono=3112578975;estado=pendiente', 'root@localhost', '2025-05-23 08:01:48', 'localhost'),
(2, 'clientes', 'UPDATE', 25, 'nombre=Fernando realr;telefono=3112578975;estado=pendiente', 'nombre=Fernando realr;telefono=3112578975;estado=activo', 'root@localhost', '2025-05-23 08:02:32', 'localhost'),
(3, 'turnos', 'UPDATE', 84, 'estado=pendiente;empleado=NULL', 'estado=completado;empleado=NULL', 'root@localhost', '2025-05-23 08:05:08', 'localhost'),
(4, 'turnos', 'UPDATE', 84, 'estado=completado;empleado=NULL', 'estado=confirmado;empleado=6', 'root@localhost', '2025-05-24 16:17:59', 'localhost'),
(5, 'clientes', 'INSERT', 26, NULL, 'nombre=Adrián;telefono=activo;estado=pendiente', 'root@localhost', '2025-05-24 16:18:55', 'localhost'),
(6, 'clientes', 'UPDATE', 19, 'nombre=21;telefono=4554623211;estado=activo', 'nombre=21;telefono=9874561322;estado=activo', 'root@localhost', '2025-05-24 16:20:53', 'localhost'),
(7, 'clientes', 'INSERT', 27, NULL, 'nombre=Jesús Manuel Díaz;telefono=3125964875;estado=pendiente', 'root@localhost', '2025-05-24 17:31:08', 'localhost'),
(8, 'clientes', 'INSERT', 28, NULL, 'nombre=Jesús Manuel Díaz;telefono=8562121224;estado=pendiente', 'root@localhost', '2025-05-24 17:35:09', 'localhost'),
(9, 'clientes', 'INSERT', 29, NULL, 'nombre=Ar der fer;telefono=3001145545;estado=pendiente', 'root@localhost', '2025-05-24 17:36:09', 'localhost'),
(10, 'clientes', 'INSERT', 30, NULL, 'nombre=t;telefono=3256897456;estado=pendiente', 'root@localhost', '2025-05-24 17:43:17', 'localhost'),
(11, 'clientes', 'UPDATE', 28, 'nombre=Jesús Manuel Díaz;telefono=8562121224;estado=pendiente', 'nombre=Jesús Manuel Díaz;telefono=8562121224;estado=activo', 'root@localhost', '2025-05-24 17:45:34', 'localhost'),
(12, 'clientes', 'INSERT', 31, NULL, 'nombre=Jesús Manuel Díaz;telefono=3214567895;estado=pendiente', 'root@localhost', '2025-05-24 17:52:23', 'localhost'),
(13, 'clientes', 'UPDATE', 31, 'nombre=Jesús Manuel Díaz;telefono=3214567895;estado=pendiente', 'nombre=Jesús Manuel Díaz;telefono=3214567895;estado=activo', 'root@localhost', '2025-05-24 17:52:44', 'localhost'),
(14, 'clientes', 'UPDATE', 27, 'nombre=Jesús Manuel Díaz;telefono=3125964875;estado=pendiente', 'nombre=Jesús Manuel Díaz;telefono=3125964875;estado=inactivo', 'root@localhost', '2025-05-24 17:58:17', 'localhost'),
(15, 'clientes', 'INSERT', 32, NULL, 'nombre=Fer;telefono=3215689744;estado=pendiente', 'root@localhost', '2025-05-26 09:45:12', 'localhost'),
(16, 'clientes', 'UPDATE', 32, 'nombre=Fer;telefono=3215689744;estado=pendiente', 'nombre=Fer;telefono=3215689744;estado=activo', 'root@localhost', '2025-05-26 09:46:16', 'localhost'),
(17, 'clientes', 'INSERT', 33, NULL, 'nombre=hj;telefono=1234567891;estado=pendiente', 'root@localhost', '2025-05-26 12:01:08', 'localhost'),
(18, 'clientes', 'UPDATE', 33, 'nombre=hj;telefono=1234567891;estado=pendiente', 'nombre=hj;telefono=1234567891;estado=activo', 'root@localhost', '2025-05-26 12:01:41', 'localhost'),
(19, 'clientes', 'INSERT', 34, NULL, 'nombre=ty;telefono=7894561235;estado=pendiente', 'root@localhost', '2025-05-26 12:09:58', 'localhost'),
(20, 'clientes', 'UPDATE', 34, 'nombre=ty;telefono=7894561235;estado=pendiente', 'nombre=ty;telefono=7894561235;estado=activo', 'root@localhost', '2025-05-26 12:10:31', 'localhost'),
(21, 'clientes', 'INSERT', 35, NULL, 'nombre=rt;telefono=3216549877;estado=pendiente', 'root@localhost', '2025-05-26 12:11:39', 'localhost'),
(22, 'clientes', 'UPDATE', 35, 'nombre=rt;telefono=3216549877;estado=pendiente', 'nombre=rt;telefono=3216549877;estado=activo', 'root@localhost', '2025-05-26 12:11:59', 'localhost'),
(23, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=activo', 'nombre=adrian;telefono=3113843233;estado=activo', 'root@localhost', '2025-05-28 01:05:20', 'localhost'),
(24, 'turnos', 'UPDATE', 84, 'estado=confirmado;empleado=6', 'estado=completado;empleado=6', 'root@localhost', '2025-05-28 01:10:20', 'localhost'),
(25, 'turnos', 'UPDATE', 82, 'estado=completado;empleado=NULL', 'estado=cancelado;empleado=6', 'root@localhost', '2025-05-28 01:11:07', 'localhost'),
(26, 'turnos', 'UPDATE', 83, 'estado=ausente;empleado=6', 'estado=cancelado;empleado=NULL', 'root@localhost', '2025-05-28 03:00:59', 'localhost'),
(27, 'clientes', 'DELETE', 6, 'nombre=fg;telefono=3113843285;estado=activo', NULL, 'root@localhost', '2025-05-28 12:06:47', 'localhost'),
(28, 'turnos', 'UPDATE', 82, 'estado=cancelado;empleado=6', 'estado=ausente;empleado=NULL', 'root@localhost', '2025-05-28 13:10:09', 'localhost'),
(29, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=activo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:10:09', 'localhost'),
(30, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:41:39', 'localhost'),
(31, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:43:05', 'localhost'),
(32, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:43:12', 'localhost'),
(33, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:43:12', 'localhost'),
(34, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:56:47', 'localhost'),
(35, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 13:56:47', 'localhost'),
(36, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:06:40', 'localhost'),
(37, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:07:49', 'localhost'),
(38, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:21:36', 'localhost'),
(39, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:21:56', 'localhost'),
(40, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:22:13', 'localhost'),
(41, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:22:13', 'localhost'),
(42, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:22:22', 'localhost'),
(43, 'clientes', '', 2147483647, NULL, NULL, NULL, '2025-05-28 14:22:22', NULL),
(44, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:22:26', 'localhost'),
(45, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 14:38:01', 'localhost'),
(46, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 15:22:00', 'localhost'),
(47, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 15:22:00', 'localhost'),
(48, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 16:29:00', 'localhost'),
(49, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 21:43:46', 'localhost'),
(50, 'clientes', 'UPDATE', 1, 'nombre=adrian;telefono=3113843233;estado=inactivo', 'nombre=adrian;telefono=3113843233;estado=inactivo', 'root@localhost', '2025-05-28 21:44:16', 'localhost');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `codigo_verificacion` varchar(6) NOT NULL,
  `verificado` tinyint(4) DEFAULT 0,
  `estado` enum('activo','pendiente','inactivo') DEFAULT 'pendiente',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `cedula`, `telefono`, `fecha_registro`, `codigo_verificacion`, `verificado`, `estado`, `deleted_at`, `intentos_fallidos`, `bloqueado_hasta`) VALUES
(1, 'adrian', '177023456', '3113843233', '2025-04-15 20:56:55', '379608', 1, 'inactivo', NULL, 3, '2025-05-28 21:47:16'),
(2, 'Fernado', NULL, '3126602593', '2025-04-15 21:12:50', '999463', 1, 'activo', NULL, 0, NULL),
(3, 'Diego', NULL, '3113834435', '2025-04-15 22:12:36', '229032', 1, 'activo', NULL, 0, NULL),
(4, 'Yesid', NULL, '3113854864', '2025-04-21 09:31:01', '905602', 1, 'activo', NULL, 0, NULL),
(5, 'adar', NULL, '3234123212', '2025-04-21 09:35:11', '114616', 1, 'activo', NULL, 0, NULL),
(7, 'gt', NULL, '3126602556', '2025-04-21 16:12:39', '977672', 1, 'activo', NULL, 0, NULL),
(8, 'Amauri valois', NULL, '32450235230', '2025-04-25 07:17:51', '516902', 1, 'activo', NULL, 0, NULL),
(9, 'derrrf', NULL, '3122344233', '2025-04-25 09:15:13', '552642', 1, 'activo', NULL, 0, NULL),
(10, 'Yarlin Valencia', NULL, '3202841297', '2025-04-25 11:35:35', '518354', 1, 'activo', NULL, 0, NULL),
(11, 'adrian', NULL, '323812321', '2025-04-27 22:25:02', '345700', 1, 'activo', NULL, 0, NULL),
(12, 'Der', NULL, '3113456514', '2025-05-10 15:35:00', '476283', 1, 'activo', NULL, 0, NULL),
(13, 'tr', NULL, '3113845456', '2025-05-14 00:17:32', '945058', 1, 'activo', NULL, 0, NULL),
(14, 'fr', NULL, '31138432855', '2025-05-16 15:57:59', '405448', 1, 'activo', NULL, 0, NULL),
(15, 'Ad', NULL, '3214895644', '2025-05-16 16:03:17', '608277', 0, 'pendiente', NULL, 0, NULL),
(16, 'Adrian Quinto', NULL, '32341232125', '2025-05-17 01:13:46', '653861', 0, 'pendiente', NULL, 0, NULL),
(17, 'Fernado', NULL, '7834738439', '2025-05-17 01:20:24', '858304', 0, 'pendiente', NULL, 0, NULL),
(18, 'e', NULL, '1326548944', '2025-05-17 01:31:12', '811807', 1, 'activo', NULL, 0, NULL),
(19, '21', NULL, '9874561322', '2025-05-17 01:53:57', '117360', 1, 'activo', NULL, 0, NULL),
(20, 'ds', NULL, '5645655455', '2025-05-17 01:55:15', '515479', 1, 'activo', NULL, 0, NULL),
(21, 'adrian', NULL, '2323244424', '2025-05-17 23:02:44', '352429', 0, 'pendiente', NULL, 0, NULL),
(22, 'adrian', NULL, '4512231223', '2025-05-17 23:03:18', '135889', 1, 'activo', NULL, 0, NULL),
(23, 'jhasghs', NULL, '3113844412', '2025-05-17 23:15:13', '687907', 1, 'activo', NULL, 0, NULL),
(24, 'f', NULL, '6551874564', '2025-05-17 23:53:00', '426197', 1, 'activo', NULL, 0, NULL),
(25, 'Fernando realr', '112334568', '3112578975', '2025-05-23 08:01:48', '085232', 1, 'activo', NULL, 0, NULL),
(26, 'Adrián', '3113851232', 'activo', '2025-05-24 16:18:55', '427505', 0, 'pendiente', NULL, 0, NULL),
(27, 'Jesús Manuel Díaz', '302568756', '3125964875', '2025-05-24 17:31:08', '672192', 0, 'inactivo', NULL, 0, NULL),
(28, 'Jesús Manuel Díaz', '45454545', '8562121224', '2025-05-24 17:35:09', '066785', 0, 'activo', NULL, 0, NULL),
(29, 'Ar der fer', '45656445', '3001145545', '2025-05-24 17:36:09', '542034', 0, 'pendiente', NULL, 0, NULL),
(30, 't', '123456789', '3256897456', '2025-05-24 17:43:17', '839501', 0, 'pendiente', NULL, 0, NULL),
(31, 'Jesús Manuel Díaz', '456321123', '3214567895', '2025-05-24 17:52:23', '305702', 1, 'activo', NULL, 0, NULL),
(32, 'Fer', '78945612', '3215689744', '2025-05-26 09:45:12', '764699', 1, 'activo', NULL, 0, NULL),
(33, 'hj', '789456', '1234567891', '2025-05-26 12:01:08', '900210', 1, 'activo', NULL, 0, NULL),
(34, 'ty', '7896188', '7894561235', '2025-05-26 12:09:58', '284211', 1, 'activo', NULL, 0, NULL),
(35, 'rt', '789456123', '3216549877', '2025-05-26 12:11:39', '660570', 1, 'activo', NULL, 0, NULL);

--
-- Disparadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `auditoria_clientes_delete` AFTER DELETE ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, operacion, id_registro, valores_anteriores, usuario, ip)
    VALUES ('clientes', 'DELETE', OLD.id_cliente, 
            CONCAT('nombre=', OLD.nombre, ';telefono=', OLD.telefono, ';estado=', OLD.estado),
            CURRENT_USER(), SUBSTRING_INDEX(USER(), '@', -1));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `auditoria_clientes_insert` AFTER INSERT ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, operacion, id_registro, valores_nuevos, usuario, ip)
    VALUES ('clientes', 'INSERT', NEW.id_cliente, 
            CONCAT('nombre=', NEW.nombre, ';telefono=', NEW.telefono, ';estado=', NEW.estado),
            CURRENT_USER(), SUBSTRING_INDEX(USER(), '@', -1));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `auditoria_clientes_update` AFTER UPDATE ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, operacion, id_registro, valores_anteriores, valores_nuevos, usuario, ip)
    VALUES ('clientes', 'UPDATE', NEW.id_cliente, 
            CONCAT('nombre=', OLD.nombre, ';telefono=', OLD.telefono, ';estado=', OLD.estado),
            CONCAT('nombre=', NEW.nombre, ';telefono=', NEW.telefono, ';estado=', NEW.estado),
            CURRENT_USER(), SUBSTRING_INDEX(USER(), '@', -1));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'limite_turnos_diario', '20', 'Límite máximo de turnos que se pueden agendar por día', '2025-05-15 19:39:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `id_sucursal` int(11) DEFAULT NULL,
  `rol` enum('admin','empleado','supervisor') DEFAULT 'empleado',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleado`, `nombre`, `apellido`, `telefono`, `correo`, `contrasena`, `id_sucursal`, `rol`, `activo`, `fecha_creacion`) VALUES
(3, 'Fernando', 'Q', '3214864557', 'fer@gmail.com', '$2y$10$3Yl.aPB4viwY3NlINaYOru1oEqngEG36c8VR/8xu7omkpSW6eO2HW', 1, 'empleado', 1, '2025-05-18 03:03:24'),
(4, 'Adrián', NULL, NULL, 'ly@gmailc.om', '$2y$10$wzWY7vIXUvXJen0n3l4ceO8td9jxn92SA7lULDZiAVNYno4JVXN2i', 1, 'empleado', 1, '2025-05-18 05:31:02'),
(5, 'Ad', NULL, NULL, 'andres@gmail.com', '$2y$10$ZG5fepaGlmlNaoM90LwJfuOMNMDsVHtNPl8PUyDOi8REUcVLyzAh6', 1, 'empleado', 1, '2025-05-23 12:59:59'),
(6, 'adrian23', NULL, NULL, 'andress@gmail.com', '$2y$10$glOxg/sasmsBmljbdwRxsugGRJANU9kYich7gjfSaJsYtMqATVF4.', 1, 'empleado', 1, '2025-05-23 13:42:21'),
(8, 'Adrián', 'Q', '3113843233', 'ferna@gmail.com', '$2y$10$FPer945MvIxq3sJfRxF86OK3Rsb1YbJFft/upezSicr/uXA/t8SGy', 2, 'empleado', 1, '2025-05-24 22:59:52'),
(9, 'Adrián', 'Q', '3113843289', 'sharly@gmailc.om', '$2y$10$4HS1F23b6uVIaLDtlXvS9OVKT2XciyhjiF65zgyS3nYWGgiwSGzUS', 2, 'empleado', 1, '2025-05-26 17:15:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_sucursal`
--

CREATE TABLE `horarios_sucursal` (
  `id_horario` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios_sucursal`
--

INSERT INTO `horarios_sucursal` (`id_horario`, `id_sucursal`, `dia_semana`, `hora_apertura`, `hora_cierre`, `activo`) VALUES
(1, 1, 'Lunes', '08:00:00', '18:00:00', 1),
(2, 1, 'Martes', '08:00:00', '18:00:00', 1),
(3, 1, 'Miércoles', '08:00:00', '18:00:00', 1),
(4, 1, 'Jueves', '08:00:00', '18:00:00', 1),
(5, 1, 'Viernes', '08:00:00', '18:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `limites_turnos`
--

CREATE TABLE `limites_turnos` (
  `id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `max_turnos` int(11) NOT NULL COMMENT 'Máximo de turnos por día',
  `intervalo_minutos` int(11) NOT NULL COMMENT 'Tiempo entre turnos en minutos',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `limites_turnos`
--

INSERT INTO `limites_turnos` (`id`, `sucursal_id`, `max_turnos`, `intervalo_minutos`, `fecha_actualizacion`) VALUES
(1, 1, 5, 1, '2025-05-18 03:33:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id_log` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL COMMENT 'Puede ser admin o empleado',
  `tipo_usuario` enum('admin','empleado') DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `detalles` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_turno` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parametros_sistema`
--

CREATE TABLE `parametros_sistema` (
  `id_parametro` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `editable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `parametros_sistema`
--

INSERT INTO `parametros_sistema` (`id_parametro`, `clave`, `valor`, `descripcion`, `editable`) VALUES
(1, 'turnos_por_dia', '20', 'Máximo número de turnos por día por sucursal', 1),
(2, 'tiempo_minimo_atencion', '15', 'Tiempo mínimo estimado por atención (minutos)', 1),
(3, 'dias_habiles', '1,2,3,4,5', 'Días hábiles (1=Lunes,...,7=Domingo)', 1),
(4, 'notificar_turno', '1', 'Activar notificaciones de turnos', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id_sucursal` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `horario_apertura` time DEFAULT NULL,
  `horario_cierre` time DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `capacidad_maxima` int(11) DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id_sucursal`, `nombre`, `direccion`, `horario_apertura`, `horario_cierre`, `telefono`, `ciudad`, `activo`, `capacidad_maxima`) VALUES
(1, 'Sucursal Principal', 'Calle 123', '08:00:00', '18:00:00', NULL, NULL, 1, 20),
(2, 'Sucursal segundaria', 'CRA 6#34-1', '08:30:00', '17:30:00', '3224567898', 'Quibdó', 1, 50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id_turno` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `tipo_tramite` enum('envío','retiro') NOT NULL,
  `id_sucursal` int(11) DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `numero_turno` int(11) DEFAULT NULL,
  `estado` enum('pendiente','confirmado','completado','cancelado','ausente') DEFAULT 'pendiente',
  `fecha_turno` date DEFAULT NULL,
  `hora_turno` time DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha` date DEFAULT NULL,
  `fecha_atencion` datetime DEFAULT NULL,
  `tiempo_atencion` int(11) DEFAULT NULL COMMENT 'En minutos',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id_turno`, `id_cliente`, `tipo_tramite`, `id_sucursal`, `id_empleado`, `numero_turno`, `estado`, `fecha_turno`, `hora_turno`, `fecha_creacion`, `fecha`, `fecha_atencion`, `tiempo_atencion`, `observaciones`) VALUES
(1, 1, 'retiro', 1, NULL, 1, '', '2025-04-16', '20:58:05', '2025-04-16 01:58:05', NULL, NULL, NULL, NULL),
(2, 2, 'retiro', 1, NULL, 2, '', '2025-04-16', '21:13:20', '2025-04-16 02:13:20', NULL, NULL, NULL, NULL),
(3, 3, 'retiro', 1, NULL, 3, '', '2025-04-16', '22:13:12', '2025-04-16 03:13:12', NULL, NULL, NULL, NULL),
(4, 1, 'envío', 1, NULL, 4, '', '2025-04-16', '22:34:25', '2025-04-16 03:34:25', NULL, NULL, NULL, NULL),
(5, 4, 'envío', 1, NULL, 1, '', '2025-04-21', '09:31:39', '2025-04-21 14:31:39', NULL, NULL, NULL, NULL),
(6, 5, 'retiro', 1, NULL, 2, '', '2025-04-21', '09:36:32', '2025-04-21 14:36:32', NULL, NULL, NULL, NULL),
(8, 7, 'envío', 1, NULL, 4, '', '2025-04-21', '16:13:12', '2025-04-21 21:13:12', NULL, NULL, NULL, NULL),
(9, 1, 'envío', 1, NULL, 1, '', '2025-04-25', '07:17:22', '2025-04-25 12:17:22', NULL, NULL, NULL, NULL),
(10, 1, 'envío', 1, NULL, 2, '', '2025-04-25', '07:17:29', '2025-04-25 12:17:29', NULL, NULL, NULL, NULL),
(11, 8, 'retiro', 1, NULL, 3, '', '2025-04-25', '07:18:29', '2025-04-25 12:18:29', NULL, NULL, NULL, NULL),
(12, 8, 'envío', 1, NULL, 4, '', '2025-04-25', '07:26:20', '2025-04-25 12:26:20', NULL, NULL, NULL, NULL),
(13, 9, 'envío', 1, NULL, 5, '', '2025-04-25', '09:16:15', '2025-04-25 14:16:15', NULL, NULL, NULL, NULL),
(14, 10, 'envío', 1, NULL, 6, '', '2025-04-25', '11:36:53', '2025-04-25 16:36:53', NULL, NULL, NULL, NULL),
(15, 10, 'envío', 1, NULL, 7, '', '2025-04-25', '11:37:34', '2025-04-25 16:37:34', NULL, NULL, NULL, NULL),
(16, 11, 'retiro', 1, NULL, 1, '', '2025-04-28', '22:32:06', '2025-04-28 03:32:06', NULL, NULL, NULL, NULL),
(17, 1, 'retiro', 1, NULL, 1, '', '2025-04-30', '01:47:54', '2025-04-30 06:47:54', NULL, NULL, NULL, NULL),
(18, 1, 'retiro', 1, NULL, 2, '', '2025-04-30', '02:02:24', '2025-04-30 07:02:24', NULL, NULL, NULL, NULL),
(19, 1, 'envío', 1, NULL, 3, '', '2025-04-30', '12:29:17', '2025-04-30 17:29:17', NULL, NULL, NULL, NULL),
(20, 2, 'retiro', 1, NULL, 4, '', '2025-04-30', '12:46:00', '2025-04-30 17:46:00', NULL, NULL, NULL, NULL),
(21, 2, 'envío', 1, NULL, 1, '', '2025-05-02', '18:56:09', '2025-05-01 23:56:09', NULL, NULL, NULL, NULL),
(22, 1, 'retiro', 1, NULL, 2, '', '2025-05-02', '19:15:56', '2025-05-02 00:15:56', NULL, NULL, NULL, NULL),
(23, 4, 'envío', 1, NULL, 3, '', '2025-05-02', '20:23:07', '2025-05-02 01:23:07', NULL, NULL, NULL, NULL),
(24, 5, 'envío', 1, NULL, 4, '', '2025-05-02', '20:24:05', '2025-05-02 01:24:05', NULL, NULL, NULL, NULL),
(26, 1, 'retiro', 1, NULL, 6, '', '2025-05-02', '22:39:32', '2025-05-02 03:39:32', NULL, NULL, NULL, NULL),
(27, 1, 'envío', 1, NULL, 7, '', '2025-05-02', '22:40:59', '2025-05-02 03:40:59', NULL, NULL, NULL, NULL),
(28, 1, 'envío', 1, NULL, 8, '', '2025-05-02', '06:29:11', '2025-05-02 11:29:11', NULL, NULL, NULL, NULL),
(29, 5, 'envío', 1, NULL, 1, '', '2025-05-05', '00:07:37', '2025-05-05 05:07:37', NULL, NULL, NULL, NULL),
(30, 1, 'retiro', 1, NULL, 1, '', '2025-05-07', '14:23:46', '2025-05-07 19:23:46', NULL, NULL, NULL, NULL),
(31, 1, 'retiro', 1, NULL, 1, '', '2025-05-09', '21:11:03', '2025-05-09 02:11:03', NULL, NULL, NULL, NULL),
(32, 1, 'envío', 1, NULL, 2, '', '2025-05-09', '21:11:29', '2025-05-09 02:11:29', NULL, NULL, NULL, NULL),
(37, 5, 'retiro', 1, NULL, 3, '', '2025-05-09', '07:00:48', '2025-05-09 12:00:48', NULL, NULL, NULL, NULL),
(38, 5, 'envío', 1, NULL, 4, '', '2025-05-09', '09:55:47', '2025-05-09 14:55:47', NULL, NULL, NULL, NULL),
(39, 5, 'envío', 1, NULL, 5, 'ausente', '2025-05-09', '10:21:57', '2025-05-09 15:21:57', NULL, NULL, NULL, NULL),
(40, 12, 'envío', 1, NULL, 1, '', '2025-05-10', '15:38:18', '2025-05-10 20:38:18', NULL, NULL, NULL, NULL),
(41, 12, 'retiro', 1, NULL, 2, '', '2025-05-10', '15:39:11', '2025-05-10 20:39:11', NULL, NULL, NULL, NULL),
(42, 12, 'envío', 1, NULL, 1, '', '2025-05-11', '20:14:13', '2025-05-11 01:14:13', NULL, NULL, NULL, NULL),
(43, 1, 'retiro', 1, NULL, 2, 'ausente', '2025-05-11', '23:57:53', '2025-05-11 04:57:53', NULL, NULL, NULL, NULL),
(44, 1, 'retiro', 1, NULL, 3, '', '2025-05-11', '00:08:05', '2025-05-11 05:08:05', NULL, NULL, NULL, NULL),
(45, 1, 'retiro', 1, NULL, 4, '', '2025-05-11', '00:08:13', '2025-05-11 05:08:13', NULL, NULL, NULL, NULL),
(46, 1, 'envío', 1, NULL, 5, '', '2025-05-11', '00:08:29', '2025-05-11 05:08:29', NULL, NULL, NULL, NULL),
(47, 1, 'envío', 1, NULL, 6, '', '2025-05-11', '00:08:41', '2025-05-11 05:08:41', NULL, NULL, NULL, NULL),
(48, 5, 'retiro', 1, NULL, 7, '', '2025-05-11', '00:09:13', '2025-05-11 05:09:13', NULL, NULL, NULL, NULL),
(49, 2, 'retiro', 1, NULL, 8, '', '2025-05-11', '00:09:25', '2025-05-11 05:09:25', NULL, NULL, NULL, NULL),
(50, 3, 'retiro', 1, NULL, 9, '', '2025-05-11', '00:09:37', '2025-05-11 05:09:37', NULL, NULL, NULL, NULL),
(51, 4, 'envío', 1, NULL, 10, 'ausente', '2025-05-11', '00:09:52', '2025-05-11 05:09:52', NULL, NULL, NULL, NULL),
(52, 1, 'envío', 1, NULL, 11, 'ausente', '2025-05-11', '13:38:12', '2025-05-11 18:38:12', NULL, NULL, NULL, NULL),
(53, 13, 'retiro', 1, NULL, 1, '', '2025-05-14', '00:19:39', '2025-05-14 05:19:39', NULL, NULL, NULL, NULL),
(54, 1, 'envío', 1, NULL, 2, '', '2025-05-14', '00:46:06', '2025-05-14 05:46:06', NULL, NULL, NULL, NULL),
(55, 2, 'envío', 1, NULL, 3, '', '2025-05-14', '00:46:17', '2025-05-14 05:46:17', NULL, NULL, NULL, NULL),
(56, 1, 'envío', 1, NULL, 1, 'ausente', '2025-05-15', '00:53:05', '2025-05-15 05:53:05', NULL, NULL, NULL, NULL),
(57, 1, 'retiro', 1, NULL, 2, '', '2025-05-15', '01:04:58', '2025-05-15 06:04:58', NULL, NULL, NULL, NULL),
(58, 1, 'retiro', 1, NULL, 3, '', '2025-05-15', '14:17:16', '2025-05-15 19:17:16', NULL, NULL, NULL, NULL),
(59, 1, 'envío', 1, NULL, 4, '', '2025-05-15', '14:17:43', '2025-05-15 19:17:43', NULL, NULL, NULL, NULL),
(60, 5, 'retiro', 1, NULL, 5, '', '2025-05-15', '14:18:03', '2025-05-15 19:18:03', NULL, NULL, NULL, NULL),
(61, 5, 'envío', 1, NULL, 1, '', '2025-05-16', '19:34:40', '2025-05-16 00:34:40', NULL, NULL, NULL, NULL),
(62, 5, 'retiro', 1, NULL, 2, '', '2025-05-16', '19:40:50', '2025-05-16 00:40:50', NULL, NULL, NULL, NULL),
(63, 5, 'envío', 1, NULL, 3, '', '2025-05-16', '19:47:52', '2025-05-16 00:47:52', NULL, NULL, NULL, NULL),
(64, 5, 'envío', 1, NULL, 4, '', '2025-05-16', '20:40:55', '2025-05-16 01:40:55', NULL, NULL, NULL, NULL),
(65, 2, 'retiro', 1, NULL, 5, '', '2025-05-16', '20:41:16', '2025-05-16 01:41:16', NULL, NULL, NULL, NULL),
(66, 2, 'retiro', 1, NULL, 6, 'ausente', '2025-05-16', '21:33:28', '2025-05-16 02:33:28', NULL, NULL, NULL, NULL),
(67, 1, 'envío', 1, NULL, 7, 'completado', '2025-05-16', '16:19:17', '2025-05-16 21:19:17', NULL, NULL, NULL, NULL),
(68, 1, 'envío', 1, NULL, 1, 'ausente', '2025-05-17', '13:37:43', '2025-05-17 18:37:43', NULL, NULL, NULL, NULL),
(69, 1, 'retiro', 1, NULL, 2, 'ausente', '2025-05-17', '13:37:54', '2025-05-17 18:37:54', NULL, NULL, NULL, NULL),
(70, 1, 'envío', 1, NULL, 3, 'ausente', '2025-05-17', '13:56:15', '2025-05-17 18:56:15', NULL, NULL, NULL, NULL),
(71, 1, 'envío', 1, NULL, 4, 'ausente', '2025-05-17', '14:11:16', '2025-05-17 19:11:16', NULL, NULL, NULL, NULL),
(72, 1, 'retiro', 1, NULL, 5, 'ausente', '2025-05-17', '14:16:55', '2025-05-17 19:16:55', NULL, NULL, NULL, NULL),
(73, 1, 'retiro', 1, NULL, 6, 'ausente', '2025-05-17', '14:17:04', '2025-05-17 19:17:04', NULL, NULL, NULL, NULL),
(74, 1, 'retiro', 1, NULL, 7, 'ausente', '2025-05-17', '14:23:35', '2025-05-17 19:23:35', NULL, NULL, NULL, NULL),
(75, 1, 'retiro', 1, NULL, 8, 'ausente', '2025-05-17', '14:31:47', '2025-05-17 19:31:47', NULL, NULL, NULL, NULL),
(76, 1, 'retiro', 1, NULL, 9, 'completado', '2025-05-17', '14:32:35', '2025-05-17 19:32:35', NULL, NULL, NULL, NULL),
(77, 1, 'envío', 1, NULL, 1, 'ausente', '2025-05-18', '17:11:32', '2025-05-17 22:11:32', NULL, NULL, NULL, NULL),
(78, 1, 'envío', 1, NULL, 2, 'completado', '2025-05-18', '19:20:52', '2025-05-18 00:20:52', NULL, NULL, NULL, NULL),
(79, 1, 'envío', 1, NULL, 3, 'completado', '2025-05-18', '21:09:21', '2025-05-18 02:09:21', NULL, '2025-05-21 00:41:39', NULL, NULL),
(80, 1, 'envío', 1, NULL, 4, 'completado', '2025-05-18', '21:53:37', '2025-05-18 02:53:37', NULL, '2025-05-21 00:41:40', NULL, NULL),
(81, 1, 'retiro', 1, 6, 5, 'completado', '2025-05-18', '23:10:12', '2025-05-18 04:10:12', NULL, '2025-05-27 23:57:18', 13007, NULL),
(82, 1, 'retiro', 1, NULL, 1, 'ausente', '2025-05-21', '12:51:48', '2025-05-21 17:51:48', NULL, '2025-05-21 13:30:31', NULL, NULL),
(83, 1, 'retiro', 1, NULL, 2, 'cancelado', '2025-05-21', '13:40:50', '2025-05-21 18:40:50', NULL, '2025-05-21 13:41:51', NULL, NULL),
(84, 1, '', 1, 6, 3, 'completado', '2025-05-21', '13:55:50', '2025-05-21 19:09:25', NULL, '2025-05-28 01:10:20', 9314, NULL);

--
-- Disparadores `turnos`
--
DELIMITER $$
CREATE TRIGGER `actualizar_estado_cliente` AFTER UPDATE ON `turnos` FOR EACH ROW BEGIN
    DECLARE v_turnos_ausentes INT;
    
    -- Si el turno se marca como ausente
    IF NEW.estado = 'ausente' AND (OLD.estado IS NULL OR OLD.estado != 'ausente') THEN
        -- Contar turnos ausentes del cliente
        SELECT COUNT(*) INTO v_turnos_ausentes
        FROM turnos
        WHERE id_cliente = NEW.id_cliente
        AND estado = 'ausente'
        AND fecha_turno >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
        
        -- Si tiene 3 o más turnos ausentes en 30 días, cambiar estado a inactivo
        IF v_turnos_ausentes >= 3 THEN
            UPDATE clientes
            SET estado = 'inactivo'
            WHERE id_cliente = NEW.id_cliente;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `auditoria_turnos_update` AFTER UPDATE ON `turnos` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado OR OLD.id_empleado != NEW.id_empleado THEN
        INSERT INTO auditoria (tabla_afectada, operacion, id_registro, valores_anteriores, valores_nuevos, usuario, ip)
        VALUES ('turnos', 'UPDATE', NEW.id_turno, 
                CONCAT('estado=', OLD.estado, ';empleado=', IFNULL(OLD.id_empleado, 'NULL')),
                CONCAT('estado=', NEW.estado, ';empleado=', IFNULL(NEW.id_empleado, 'NULL')),
                CURRENT_USER(), SUBSTRING_INDEX(USER(), '@', -1));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validar_horario_turno` BEFORE INSERT ON `turnos` FOR EACH ROW BEGIN
    DECLARE v_hora_apertura TIME;
    DECLARE v_hora_cierre TIME;
    DECLARE v_dia_semana VARCHAR(10);
    DECLARE v_horario_valido INT;
    
    -- Obtener día de la semana
    SET v_dia_semana = DAYNAME(NEW.fecha_turno);
    
    -- Obtener horario de la sucursal para ese día
    SELECT COUNT(*) INTO v_horario_valido
    FROM horarios_sucursal
    WHERE id_sucursal = NEW.id_sucursal
    AND dia_semana = v_dia_semana
    AND NEW.hora_turno BETWEEN hora_apertura AND hora_cierre
    AND activo = 1;
    
    IF v_horario_valido = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No se puede asignar turno fuera del horario de atención de la sucursal';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_clientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_clientes` (
`id_cliente` int(11)
,`nombre` varchar(100)
,`cedula` varchar(20)
,`telefono` varchar(20)
,`estado` enum('activo','pendiente','inactivo')
,`verificado` tinyint(4)
,`fecha_registro` datetime
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estadisticas_sucursales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_estadisticas_sucursales` (
`id_sucursal` int(11)
,`sucursal` varchar(100)
,`total_turnos` bigint(21)
,`turnos_completados` decimal(22,0)
,`turnos_ausentes` decimal(22,0)
,`tiempo_promedio_atencion` decimal(14,4)
,`porcentaje_eficiencia` decimal(29,4)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_turnos_diarios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_turnos_diarios` (
`id_turno` int(11)
,`numero_turno` int(11)
,`cliente` varchar(123)
,`sucursal` varchar(100)
,`tipo_tramite` varchar(6)
,`estado` enum('pendiente','confirmado','completado','cancelado','ausente')
,`fecha_turno` date
,`hora_turno` time
,`empleado_asignado` varchar(100)
,`tiempo_atencion` int(11)
,`minutos_espera` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_turnos_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_turnos_pendientes` (
`id_turno` int(11)
,`numero_turno` int(11)
,`cliente` varchar(100)
,`telefono` varchar(20)
,`sucursal` varchar(100)
,`tipo_tramite` varchar(6)
,`fecha_turno` date
,`hora_turno` time
,`minutos_espera` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_usuarios_bloqueados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_usuarios_bloqueados` (
`id_cliente` int(11)
,`nombre` varchar(100)
,`cedula` varchar(20)
,`telefono` varchar(20)
,`intentos_fallidos` int(11)
,`bloqueado_hasta` datetime
,`segundos_restantes` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_clientes`
--
DROP TABLE IF EXISTS `vista_clientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_clientes`  AS SELECT `clientes`.`id_cliente` AS `id_cliente`, `clientes`.`nombre` AS `nombre`, `clientes`.`cedula` AS `cedula`, `clientes`.`telefono` AS `telefono`, `clientes`.`estado` AS `estado`, `clientes`.`verificado` AS `verificado`, `clientes`.`fecha_registro` AS `fecha_registro` FROM `clientes` ORDER BY `clientes`.`nombre` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estadisticas_sucursales`
--
DROP TABLE IF EXISTS `vista_estadisticas_sucursales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_sucursales`  AS SELECT `s`.`id_sucursal` AS `id_sucursal`, `s`.`nombre` AS `sucursal`, count(`t`.`id_turno`) AS `total_turnos`, sum(case when `t`.`estado` = 'completado' then 1 else 0 end) AS `turnos_completados`, sum(case when `t`.`estado` = 'ausente' then 1 else 0 end) AS `turnos_ausentes`, avg(`t`.`tiempo_atencion`) AS `tiempo_promedio_atencion`, sum(case when `t`.`estado` = 'completado' then 1 else 0 end) / count(`t`.`id_turno`) * 100 AS `porcentaje_eficiencia` FROM (`sucursales` `s` left join `turnos` `t` on(`s`.`id_sucursal` = `t`.`id_sucursal`)) GROUP BY `s`.`id_sucursal`, `s`.`nombre` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_turnos_diarios`
--
DROP TABLE IF EXISTS `vista_turnos_diarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_turnos_diarios`  AS SELECT `t`.`id_turno` AS `id_turno`, `t`.`numero_turno` AS `numero_turno`, concat(`c`.`nombre`,' (',`c`.`telefono`,')') AS `cliente`, `s`.`nombre` AS `sucursal`, CASE WHEN `t`.`tipo_tramite` = 'envío' THEN 'Envío' WHEN `t`.`tipo_tramite` = 'retiro' THEN 'Retiro' ELSE `t`.`tipo_tramite` END AS `tipo_tramite`, `t`.`estado` AS `estado`, `t`.`fecha_turno` AS `fecha_turno`, `t`.`hora_turno` AS `hora_turno`, ifnull(`e`.`nombre`,'No asignado') AS `empleado_asignado`, `t`.`tiempo_atencion` AS `tiempo_atencion`, timestampdiff(MINUTE,`t`.`fecha_atencion`,current_timestamp()) AS `minutos_espera` FROM (((`turnos` `t` join `clientes` `c` on(`t`.`id_cliente` = `c`.`id_cliente`)) join `sucursales` `s` on(`t`.`id_sucursal` = `s`.`id_sucursal`)) left join `empleados` `e` on(`t`.`id_empleado` = `e`.`id_empleado`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_turnos_pendientes`
--
DROP TABLE IF EXISTS `vista_turnos_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_turnos_pendientes`  AS SELECT `t`.`id_turno` AS `id_turno`, `t`.`numero_turno` AS `numero_turno`, `c`.`nombre` AS `cliente`, `c`.`telefono` AS `telefono`, `s`.`nombre` AS `sucursal`, CASE WHEN `t`.`tipo_tramite` = 'envío' THEN 'Envío' WHEN `t`.`tipo_tramite` = 'retiro' THEN 'Retiro' ELSE `t`.`tipo_tramite` END AS `tipo_tramite`, `t`.`fecha_turno` AS `fecha_turno`, `t`.`hora_turno` AS `hora_turno`, timestampdiff(MINUTE,concat(`t`.`fecha_turno`,' ',`t`.`hora_turno`),current_timestamp()) AS `minutos_espera` FROM ((`turnos` `t` join `clientes` `c` on(`t`.`id_cliente` = `c`.`id_cliente`)) join `sucursales` `s` on(`t`.`id_sucursal` = `s`.`id_sucursal`)) WHERE `t`.`estado` = 'pendiente' ORDER BY `t`.`fecha_turno` ASC, `t`.`hora_turno` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_usuarios_bloqueados`
--
DROP TABLE IF EXISTS `vista_usuarios_bloqueados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_usuarios_bloqueados`  AS SELECT `c`.`id_cliente` AS `id_cliente`, `c`.`nombre` AS `nombre`, `c`.`cedula` AS `cedula`, `c`.`telefono` AS `telefono`, `c`.`intentos_fallidos` AS `intentos_fallidos`, `c`.`bloqueado_hasta` AS `bloqueado_hasta`, timestampdiff(SECOND,current_timestamp(),`c`.`bloqueado_hasta`) AS `segundos_restantes` FROM `clientes` AS `c` WHERE `c`.`bloqueado_hasta` is not null AND `c`.`bloqueado_hasta` > current_timestamp() ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `telefono` (`telefono`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `correo_UNIQUE` (`correo`),
  ADD KEY `id_sucursal` (`id_sucursal`);

--
-- Indices de la tabla `horarios_sucursal`
--
ALTER TABLE `horarios_sucursal`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `horario_unico` (`id_sucursal`,`dia_semana`);

--
-- Indices de la tabla `limites_turnos`
--
ALTER TABLE `limites_turnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sucursal_unica` (`sucursal_id`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id_log`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_turno` (`id_turno`);

--
-- Indices de la tabla `parametros_sistema`
--
ALTER TABLE `parametros_sistema`
  ADD PRIMARY KEY (`id_parametro`),
  ADD UNIQUE KEY `clave_UNIQUE` (`clave`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id_sucursal`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id_turno`),
  ADD KEY `fk_turno_cliente` (`id_cliente`),
  ADD KEY `fk_turno_sucursal` (`id_sucursal`),
  ADD KEY `fk_turno_empleado` (`id_empleado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `horarios_sucursal`
--
ALTER TABLE `horarios_sucursal`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `limites_turnos`
--
ALTER TABLE `limites_turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parametros_sistema`
--
ALTER TABLE `parametros_sistema`
  MODIFY `id_parametro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id_sucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`);

--
-- Filtros para la tabla `horarios_sucursal`
--
ALTER TABLE `horarios_sucursal`
  ADD CONSTRAINT `horarios_sucursal_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`);

--
-- Filtros para la tabla `limites_turnos`
--
ALTER TABLE `limites_turnos`
  ADD CONSTRAINT `limites_turnos_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id_sucursal`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`);

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `fk_turno_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turno_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turno_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turnos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
