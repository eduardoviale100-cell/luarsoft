-- ==================================================================
-- luarsoft_completo.sql — instalación en UN SOLO PASO
-- Incluye: el sistema LuarSoft completo + la tabla clientes_web
-- de la web pública. Solo hace falta importar ESTE archivo.
-- ==================================================================
DROP DATABASE IF EXISTS `luarsoft`;
CREATE DATABASE `luarsoft` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `luarsoft`;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-09-2026 a las 02:28:45
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
-- Base de datos: `luarsoft`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_documento` varchar(20) DEFAULT NULL,
  `numero_documento` varchar(30) DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `telefono`, `direccion`, `email`, `tipo_documento`, `numero_documento`, `ciudad`, `region`, `foto`) VALUES
(1, 'Eduardo Alberto Ramos Ticona', '913291289', 'Urb. Santa Rosa Mz. A Lote 16', 'eduardo.ramos@gmail.com', 'DNI', '73788195', 'Imperial', 'Lima', NULL),
(2, 'Luis Fernández Padilla', '922325860', 'Cerro Alegre', 'luisfaustino@gmail.com', 'DNI', '72158211', 'Imperial', 'Lima', NULL),
(3, 'María López Salazar', '987654321', 'Jr. Grau 245', 'maria.lopez@gmail.com', 'Pasaporte', 'P4521133', 'San Vicente', 'Lima', NULL),
(4, 'Yanina Barcia Calzado', '933607983', 'Urb. Alameda de Montalván', 'yanina.barcia@gmail.com', 'DNI', '41892744', 'Imperial', 'Lima', NULL),
(5, 'Rosa Elena Quispe Mamani', '925365879', 'Urb. Las Palmeras Mz. B', 'rosa.quispe@gmail.com', 'DNI', '45123687', 'Nuevo Imperial', 'Lima', NULL),
(6, 'Jorge Luis Salvatierra Díaz', '987112233', 'Av. Mariscal Castilla 512', 'jorge.salvatierra@gmail.com', 'DNI', '70456123', 'San Vicente', 'Lima', NULL),
(7, 'Carmen Rosa Injante Villar', '954112288', 'Jr. Bolognesi 340', 'carmen.injante@empresacanete.com', 'RUC', '20548712369', 'Imperial', 'Lima', NULL),
(8, 'Comercial Tecno Cañete S.A.C.', '015689321', 'Av. La Mar 890', 'ventas@tecnocanete.com', 'RUC', '20601234567', 'Imperial', 'Lima', NULL),
(9, 'Fiorella Nayeli Torres Cárdenas', '945678123', 'Urb. Miraflores Mz. C Lote 4', 'fiorella.torres@gmail.com', 'DNI', '76541233', 'Imperial', 'Lima', NULL),
(10, 'Percy Alonso Guillén Rojas', '936541287', 'Jr. Ayacucho 155', 'percy.guillen@gmail.com', 'DNI', '43219087', 'San Vicente', 'Lima', NULL),
(11, 'Distribuidora Eros Norte E.I.R.L.', '015645123', 'Panamericana Sur Km 143', 'contacto@erosnorte.pe', 'RUC', '20512398741', 'Imperial', 'Lima', NULL),
(12, 'Katherine Milagros Ccahuana Espinoza', '912345678', 'Urb. San Martín Mz. D', 'katherine.ccahuana@gmail.com', 'DNI', '71234598', 'Nuevo Imperial', 'Lima', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id_compra` int(11) NOT NULL,
  `producto_codigo` varchar(50) NOT NULL,
  `descripcion_producto` varchar(255) NOT NULL,
  `proveedor` varchar(150) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id_compra`, `producto_codigo`, `descripcion_producto`, `proveedor`, `cantidad`, `costo_unitario`, `total`, `fecha`, `observaciones`, `fecha_registro`) VALUES
(1, 'REP-CAR-001', 'Cartucho de Tinta Negra HP 662', 'Distribuidora Cañete Digital', 20, 32.00, 640.00, '2026-01-05', 'Compra inicial de stock', '2026-01-05 09:00:00'),
(2, 'REP-TON-001', 'Tóner HP 85A Negro Original', 'Distribuidora Cañete Digital', 10, 145.00, 1450.00, '2026-01-08', NULL, '2026-01-08 10:30:00'),
(3, 'ACC-MIC-001', 'Micrófono USB para PC', 'Importadora Lima Tech', 15, 45.00, 675.00, '2026-02-01', NULL, '2026-02-01 11:15:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correlativos_documentos`
--

CREATE TABLE `correlativos_documentos` (
  `serie` varchar(5) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `correlativos_documentos`
--

INSERT INTO `correlativos_documentos` (`serie`, `ultimo_numero`) VALUES
('B001', 17),
('F001', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_nota_venta`
--

CREATE TABLE `detalle_nota_venta` (
  `id_detalle` int(11) NOT NULL,
  `id_nota_venta` int(11) NOT NULL,
  `codigo_producto` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `producto_codigo` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle`, `id_venta`, `producto_codigo`, `descripcion`, `cantidad`, `precio_unitario`, `importe`) VALUES
(1, 1, 'REP-CAR-001', 'Cartucho de Tinta Negra HP 662', 2.00, 38.00, 76.00),
(2, 1, 'ACC-CAB-001', 'Cable USB 2.0 tipo A-B para Impresora', 1.00, 12.00, 12.00),
(3, 2, 'TEC-MOU-001', 'Mouse Óptico USB Inalámbrico', 1.00, 32.00, 32.00),
(4, 3, 'ACC-AUD-001', 'Audífonos con Micrófono 3.5mm', 2.00, 25.00, 50.00),
(5, 4, 'REP-TON-001', 'Tóner HP 85A Negro Original', 3.00, 220.00, 660.00),
(6, 4, 'OTR-RES-001', 'Resma de Papel Bond A4 75gr', 10.00, 18.00, 180.00),
(7, 5, 'SER-SER-002', 'Servicio de Recarga de Tinta (por cartucho)', 1.00, 15.00, 15.00),
(8, 6, 'ACC-CAB-002', 'Cable HDMI 1.5 metros', 1.00, 18.00, 18.00),
(9, 6, 'ACC-CAR-001', 'Cargador para Auto (Cigarrera) Doble USB', 1.00, 22.00, 22.00),
(10, 7, 'TEC-TEC-001', 'Teclado USB Estándar Español', 1.00, 45.00, 45.00),
(11, 8, 'TEC-HUB-001', 'Hub USB 2.0 de 4 Puertos', 5.00, 20.00, 100.00),
(12, 8, 'ACC-CAB-001', 'Cable USB 2.0 tipo A-B para Impresora', 5.00, 12.00, 60.00),
(13, 9, 'ACC-ANT-001', 'Antena WiFi USB 300Mbps', 1.00, 32.00, 32.00),
(14, 10, 'SER-SER-001', 'Servicio de Mantenimiento Preventivo de Impresora', 1.00, 45.00, 45.00),
(15, 10, 'REP-CAR-002', 'Cartucho de Tinta Tricolor HP 662', 1.00, 42.00, 42.00),
(16, 11, 'REP-CAR-001', 'Cartucho de Tinta Negra HP 662', 20.00, 38.00, 760.00),
(17, 11, 'REP-CAR-003', 'Cartucho de Tinta Epson T664 Negro', 20.00, 32.00, 640.00),
(18, 12, 'ACC-MIC-001', 'Micrófono USB para PC', 1.00, 38.00, 38.00),
(19, 13, 'ACC-CAB-003', 'Cable de Poder Universal 3 Pines', 2.00, 10.00, 20.00),
(20, 14, 'REP-CAR-002', 'Cartucho de Tinta Tricolor HP 662', 1.00, 42.00, 42.00),
(21, 14, 'REP-CAR-001', 'Cartucho de Tinta Negra HP 662', 1.00, 38.00, 38.00),
(22, 15, 'TEC-MOU-001', 'Mouse Óptico USB Inalámbrico', 10.00, 32.00, 320.00),
(23, 15, 'TEC-TEC-001', 'Teclado USB Estándar Español', 10.00, 45.00, 450.00),
(24, 16, 'SER-SER-002', 'Servicio de Recarga de Tinta (por cartucho)', 2.00, 15.00, 30.00),
(25, 17, 'TEC-HUB-001', 'Hub USB 2.0 de 4 Puertos', 1.00, 20.00, 20.00),
(26, 18, 'REP-TON-001', 'Tóner HP 85A Negro Original', 1.00, 220.00, 220.00),
(27, 19, 'OTR-RES-001', 'Resma de Papel Bond A4 75gr', 30.00, 18.00, 540.00),
(28, 20, 'ACC-AUD-001', 'Audífonos con Micrófono 3.5mm', 1.00, 25.00, 25.00),
(29, 20, 'ACC-CAB-002', 'Cable HDMI 1.5 metros', 1.00, 18.00, 18.00),
(32, 23, 'REP-CAR-001', 'Cartucho de Tinta Negra HP 662', 1.00, 38.00, 38.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota_venta`
--

CREATE TABLE `nota_venta` (
  `id_nota_venta` int(11) NOT NULL,
  `serie` varchar(5) NOT NULL DEFAULT 'NV01',
  `numero` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'EMITIDA',
  `nombre_cliente` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes`
--

CREATE TABLE `ordenes` (
  `id_orden` int(11) NOT NULL,
  `cliente` varchar(150) NOT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `tipo_equipo` varchar(30) DEFAULT NULL,
  `modelo_impresora` varchar(150) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `password_equipo` varchar(100) DEFAULT NULL,
  `accesorios_dejados` varchar(255) DEFAULT NULL,
  `problema` text NOT NULL,
  `observaciones_estado` text DEFAULT NULL,
  `solucion_aplicada` text DEFAULT NULL,
  `evidencia_video` varchar(255) DEFAULT NULL,
  `foto_equipo` varchar(255) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Ingresado',
  `garantia_dias` int(11) NOT NULL DEFAULT 0,
  `monto_estimado` decimal(10,2) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ordenes`
--

INSERT INTO `ordenes` (`id_orden`, `cliente`, `telefono_cliente`, `tipo_equipo`, `modelo_impresora`, `marca`, `numero_serie`, `password_equipo`, `accesorios_dejados`, `problema`, `observaciones_estado`, `solucion_aplicada`, `evidencia_video`, `foto_equipo`, `estado`, `garantia_dias`, `monto_estimado`, `fecha_ingreso`, `fecha_entrega`) VALUES
(1, 'Eduardo Alberto Ramos Ticona', NULL, 'Impresora', 'HP DeskJet 2135', 'HP', NULL, NULL, 'Cable de poder', 'No imprime, tinta corrida en el cabezal.', 'Carcasa en buen estado.', NULL, NULL, NULL, 'Entregado', 30, 80.00, '2026-01-10', '2026-01-13'),
(2, 'Luis Fernández Padilla', NULL, 'Impresora', 'Epson L3150', 'Epson', NULL, NULL, 'Cable USB, cargador de tinta', 'Atasco de papel constante en la bandeja.', 'Bandeja de papel con rajadura leve.', NULL, NULL, NULL, 'Entregado', 30, 60.00, '2026-01-14', '2026-01-16'),
(3, 'María López Salazar', NULL, 'PC Escritorio', 'Canon Pixma G2100', 'Canon', 'SN-CN2100-4471', NULL, 'Cable de poder', 'No enciende, posible falla en la fuente de poder.', 'Gabinete con polvo acumulado.', NULL, NULL, NULL, 'En Reparación / Cambio de Piez', 60, 150.00, '2026-02-03', NULL),
(4, 'Comercial Tecno Cañete S.A.C.', NULL, 'Impresora', 'HP LaserJet Pro M15w', 'HP', NULL, NULL, 'Ninguno', 'Impresión con líneas verticales, requiere cambio de tóner.', 'Equipo con desgaste normal de uso.', NULL, NULL, NULL, 'Entregado', 30, 120.00, '2026-02-08', '2026-02-10'),
(5, 'Rosa Elena Quispe Mamani', NULL, 'Impresora', 'Epson L395', 'Epson', NULL, NULL, 'Cable USB', 'Cabezal de impresión obstruido, tinta seca.', 'Pantalla intacta, sin golpes visibles.', NULL, NULL, NULL, 'Ingresado', 0, NULL, '2026-02-20', NULL),
(6, 'Jorge Luis Salvatierra Díaz', NULL, 'Impresora', 'Brother DCP-T420W', 'Brother', NULL, NULL, 'Ninguno', 'No conecta por WiFi, requiere reconfiguración de red.', 'Sin observaciones adicionales.', NULL, NULL, NULL, 'Entregado', 15, 40.00, '2026-02-25', '2026-02-26'),
(7, 'Distribuidora Eros Norte E.I.R.L.', NULL, 'Impresora', 'HP LaserJet M404dn', 'HP', NULL, NULL, 'Ninguno', 'Mantenimiento preventivo programado, cambio de fusor.', 'Equipo de uso intensivo, buen estado general.', NULL, NULL, NULL, 'Esperando Aprobación de Presup', 0, 320.00, '2026-03-05', NULL),
(8, 'Percy Alonso Guillén Rojas', NULL, 'Impresora', 'Epson EcoTank L3250', 'Epson', NULL, NULL, 'Cable de poder, cable USB', 'Manchas de tinta en las hojas impresas.', 'Tapa superior rayada.', NULL, NULL, NULL, 'En Diagnóstico', 0, NULL, '2026-03-11', NULL),
(9, 'Katherine Milagros Ccahuana Espinoza', NULL, 'Laptop', 'Lenovo IdeaPad 3', 'Lenovo', 'PF-3KLQ0912', '1234', 'Cargador original', 'Laptop no enciende, posible falla de tarjeta madre o RAM.', 'Carcasa con golpe leve en la esquina superior derecha, pantalla intacta.', NULL, NULL, NULL, 'En Diagnóstico', 0, 250.00, '2026-03-14', NULL),
(10, 'Percy Alonso Guillén Rojas', '936541287', 'PC Escritorio', 'Ensamblado Gamer', 'AMD', NULL, NULL, 'Ninguno', 'Lentitud extrema y cuelgues frecuentes, cliente solicita upgrade de almacenamiento.', 'Gabinete con buena ventilación, sin daños visibles.', '', 'orden_10_1788391533.mp4', NULL, 'Listo para Entrega', 90, 180.00, '2026-03-18', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_repuestos`
--

CREATE TABLE `orden_repuestos` (
  `id_detalle` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `producto_codigo` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `importe` decimal(10,2) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_repuestos`
--

INSERT INTO `orden_repuestos` (`id_detalle`, `id_orden`, `producto_codigo`, `descripcion`, `cantidad`, `precio_unitario`, `importe`, `fecha_asignacion`) VALUES
(1, 10, 'TEC-HUB-001', 'Hub USB 2.0 de 4 Puertos', 1.00, 20.00, 20.00, '2026-08-15 02:51:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `caracteristicas` text DEFAULT NULL,
  `especificaciones_tecnicas` text DEFAULT NULL,
  `tipo_impuesto` varchar(50) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `codigo_barras`, `descripcion`, `precio_compra`, `precio_venta`, `stock`, `categoria`, `imagen`, `caracteristicas`, `especificaciones_tecnicas`, `tipo_impuesto`, `fecha_registro`) VALUES
(1, 'REP-CAR-001', '7750182001015', 'Cartucho de Tinta Negra HP 662', 22.00, 38.00, 12, 'Repuestos', 'TIN-001_1786762413.jpg', 'Cartucho original HP, rendimiento estándar, fácil instalación sin necesidad de configuración adicional.', 'Compatibilidad: HP DeskJet 1015/1515/2515/2545/3545/4645 y series similares.\r\nRendimiento aproximado: 120 páginas al 5% de cobertura.\r\nTipo de tinta: base acuosa, color negro.', '10', '2026-01-05 09:00:00'),
(2, 'REP-CAR-002', '7750182001022', 'Cartucho de Tinta Tricolor HP 662', 24.00, 42.00, 12, 'Repuestos', 'TIN-002_1786762421.jpg', 'Cartucho original HP tricolor (cian, magenta, amarillo), ideal para fotos e impresiones a color.', 'Compatibilidad: HP DeskJet 1015/1515/2515/2545/3545/4645 y series similares.\r\nRendimiento aproximado: 100 páginas al 5% de cobertura por color.\r\nTipo de tinta: base acuosa, tricolor.', '10', '2026-01-05 09:02:00'),
(3, 'REP-CAR-003', '7750182001039', 'Cartucho de Tinta Epson T664 Negro', 18.00, 32.00, 20, 'Repuestos', 'TIN-003_1786762429.jpg', 'Botella de tinta original Epson para sistema EcoTank, alto rendimiento por sol/vida útil.', 'Compatibilidad: Epson L100/L200/L300/L400/L500/L600 (sistema EcoTank).\r\nCapacidad: 70 ml.\r\nRendimiento aproximado: 4,000 páginas.', '10', '2026-01-05 09:05:00'),
(4, 'REP-TON-001', '7750182001046', 'Tóner HP 85A Negro Original', 145.00, 220.00, 6, 'Repuestos', 'TIN-004_1786762441.png', 'Tóner original HP de alto rendimiento, calidad de impresión nítida para uso de oficina.', 'Compatibilidad: HP LaserJet Pro M1132/M1212/P1102/M1217.\r\nRendimiento aproximado: 1,600 páginas al 5% de cobertura.\r\nTipo: tóner láser negro.', '10', '2026-01-05 09:07:00'),
(5, 'ACC-CAB-001', '7750182002012', 'Cable USB 2.0 tipo A-B para Impresora', 6.50, 12.00, 30, 'Accesorios', 'CAB-001_1786762452.webp', 'Cable robusto para conexión de impresora a PC, conectores reforzados.', 'Longitud: 1.5 metros.\r\nTipo: USB 2.0 A-B.\r\nVelocidad de transferencia: hasta 480 Mbps.', '10', '2026-01-06 10:10:00'),
(6, 'ACC-CAB-002', '7750182002029', 'Cable HDMI 1.5 metros', 8.00, 18.00, 25, 'Accesorios', 'CAB-002_1786762467.webp', 'Cable HDMI de alta definición, ideal para conectar laptop a TV o proyector.', 'Longitud: 1.5 metros.\r\nResolución soportada: hasta 4K a 30Hz.\r\nVersión: HDMI 1.4.', '10', '2026-01-06 10:12:00'),
(7, 'ACC-CAB-003', '7750182002036', 'Cable de Poder Universal 3 Pines', 5.00, 10.00, 40, 'Accesorios', 'CAB-003_1786762474.webp', 'Cable de poder estándar para PC, monitores e impresoras.', 'Longitud: 1.2 metros.\r\nConector: 3 pines tipo C13.\r\nVoltaje: 220V.', '10', '2026-01-06 10:15:00'),
(8, 'TEC-CAR-001', '7750182003019', 'Cargador Universal para Laptop 65W', 35.00, 65.00, 10, 'Tecnologia', 'CAR-001_1786762482.jpg', 'Cargador universal con múltiples conectores intercambiables, compatible con la mayoría de marcas.', 'Potencia: 65W.\r\nConectores incluidos: 8 puntas intercambiables.\r\nVoltaje de salida: 15V-20V ajustable.', '10', '2026-01-07 11:20:00'),
(9, 'ACC-CAR-001', '7750182003026', 'Cargador para Auto (Cigarrera) Doble USB', 12.00, 22.00, 18, 'Accesorios', 'CAR-002_1786762495.webp', 'Cargador compacto para auto con dos puertos USB, carga rápida para celular y tablet.', 'Entrada: 12V-24V (cigarrera).\r\nSalida: 2x USB, 5V/2.4A cada puerto.\r\nProtección contra sobrecarga incluida.', '10', '2026-01-07 11:22:00'),
(10, 'TEC-MOU-001', '7750182004016', 'Mouse Óptico USB Inalámbrico', 18.00, 32.00, 22, 'Tecnologia', 'MOU-001_1786762507.jpg', 'Mouse ergonómico inalámbrico, ideal para oficina y uso diario, batería de larga duración.', 'Conectividad: USB inalámbrico 2.4GHz.\r\nResolución: 1200 DPI.\r\nAlcance: hasta 10 metros.\r\nBatería: 1 pila AA (no incluida).', '10', '2026-01-08 12:00:00'),
(11, 'TEC-TEC-001', '7750182004023', 'Teclado USB Estándar Español', 25.00, 45.00, 14, 'Tecnologia', 'TEC-001_1786762734.jpg', 'Teclado con distribución en español (Ñ), teclas silenciosas, conexión plug and play.', 'Conectividad: USB con cable.\r\nDistribución: español (Latinoamérica).\r\nTeclas: 104 teclas estándar.', '10', '2026-01-08 12:05:00'),
(12, 'ACC-AUD-001', '7750182005013', 'Audífonos con Micrófono 3.5mm', 12.00, 25.00, 28, 'Accesorios', 'AUD-001_1786762796.webp', 'Audífonos cómodos con micrófono integrado, ideales para llamadas y videoconferencias.', 'Conector: jack 3.5mm.\r\nMicrófono: omnidireccional con reducción de ruido básica.\r\nCable: 1.2 metros.', '10', '2026-01-09 09:30:00'),
(13, 'ACC-MIC-001', '7750182005020', 'Micrófono USB para PC', 20.00, 38.00, 9, 'Accesorios', 'MIC-001_1786763100.webp', 'Micrófono plug and play de fácil instalación, buena captación de voz para videollamadas.', 'Conectividad: USB 2.0.\r\nPatrón polar: cardioide.\r\nFrecuencia: 100Hz - 16kHz.', '10', '2026-01-09 09:35:00'),
(14, 'ACC-ANT-001', '7750182006010', 'Antena WiFi USB 300Mbps', 15.00, 32.00, 11, 'Accesorios', 'WIFI-001_1786763150.jpg', 'Adaptador WiFi compacto, mejora la conectividad de PC de escritorio sin WiFi integrado.', 'Velocidad: hasta 300 Mbps.\r\nEstándar: 802.11 b/g/n.\r\nConectividad: USB 2.0.', '10', '2026-01-10 15:40:00'),
(15, 'TEC-HUB-001', '7750182006027', 'Hub USB 2.0 de 4 Puertos', 10.00, 20.00, 16, 'Tecnologia', 'HUB-001_1786763373.jpg', 'Multiplicador de puertos USB, práctico para conectar varios dispositivos a la vez.', 'Puertos: 4x USB 2.0.\r\nVelocidad: hasta 480 Mbps.\r\nAlimentación: vía USB (sin fuente externa).', '10', '2026-01-10 15:45:00'),
(16, 'OTR-RES-001', '7750182007017', 'Resma de Papel Bond A4 75gr', 12.50, 18.00, 50, 'Otros', 'PAP-001_1786763411.jpg', 'Papel bond de uso general para impresión y fotocopiado, blancura óptima.', 'Tamaño: A4 (210 x 297 mm).\r\nGramaje: 75 gr/m².\r\nHojas por resma: 500.', '10', '2026-01-12 08:50:00'),
(17, 'SER-SER-001', '2007883814739', 'Servicio de Mantenimiento Preventivo de Impresora', 0.00, 45.00, 999, 'Servicios', 'SERV-001_1786763437.jpg', 'Limpieza interna, revisión de cabezal y calibración general de tu impresora.', '', '10', '2026-01-13 09:00:00'),
(18, 'SER-SER-002', '2007883814838', 'Servicio de Recarga de Tinta (por cartucho)', 0.00, 15.00, 999, 'Servicios', 'SERV-002_1786763476.jpg', 'Recarga de tinta compatible, con prueba de impresión incluida.', '', '10', '2026-01-13 09:02:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnicos`
--

CREATE TABLE `tecnicos` (
  `id_tecnico` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tecnicos`
--

INSERT INTO `tecnicos` (`id_tecnico`, `nombre`, `especialidad`, `telefono`, `email`, `foto`) VALUES
(1, 'Carlos Injante Rojas', 'Impresoras láser y multifuncionales', '944123567', 'carlos.injante@luarsoft.pe', NULL),
(2, 'Milagros Huamán Ccora', 'Impresoras de tinta continua', '956234891', 'milagros.huaman@luarsoft.pe', NULL),
(3, 'Jhonatan Rivas Salcedo', 'Redes y conectividad WiFi', '978345120', 'jhonatan.rivas@luarsoft.pe', NULL),
(4, 'Deysi Ccorimanya Paucar', 'Mantenimiento de equipos de cómputo', '969456702', 'deysi.ccorimanya@luarsoft.pe', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'Administrador',
  `permisos` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `contraseña` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `usuario`, `rol`, `permisos`, `foto`, `contraseña`, `fecha_creacion`) VALUES
(1, 'Luis Fernández', 'Administrador', '', 'Luis_Fern__ndez_1787943231.png', '$2y$10$4yax1JUs560Z5A8JNT8bC.rK3EozIvWow.hcHduiEYrI.gs68cPHu', '2026-08-15 02:51:54'),
(2, 'Eduardo Viale', 'Cajero / Usuario', 'dashboard,pos', 'Eduardo_Viale_1788384881.jpg', '$2y$10$H5VAHuIAhaSydCyrNfWd1u3ENgZiEnd1WfacGiShn1xil6BOhBa86', '2026-08-15 02:51:54'),
(3, 'eduardo', 'Cajero / Usuario', 'dashboard,pos', 'eduardo_1788384780.png', '$2y$10$nj01dbujs3CVSu3qEZGhueU8xXWRWof7RXtacJCBJVBx5mW1QjvTa', '2026-09-02 21:31:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `serie_documento` varchar(5) NOT NULL,
  `numero_documento` varchar(15) NOT NULL,
  `fecha` datetime NOT NULL,
  `nombre_cliente` varchar(255) NOT NULL,
  `documento_cliente` varchar(20) NOT NULL,
  `direccion_cliente` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total_venta` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT 'Registrada',
  `metodo_pago` varchar(30) DEFAULT NULL,
  `referencia_pago` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `tipo_documento`, `serie_documento`, `numero_documento`, `fecha`, `nombre_cliente`, `documento_cliente`, `direccion_cliente`, `subtotal`, `igv`, `total_venta`, `estado`, `metodo_pago`, `referencia_pago`) VALUES
(1, 'BOLETA', 'B001', 'B001 - 00001', '2026-01-15 09:15:00', 'Eduardo Alberto Ramos Ticona', '73788195', 'Urb. Santa Rosa Mz. A Lote 16', 88.00, 15.84, 103.84, 'Registrada', 'Efectivo', NULL),
(2, 'BOLETA', 'B001', 'B001 - 00002', '2026-01-16 11:30:00', 'Luis Fernández Padilla', '72158211', 'Cerro Alegre', 32.00, 5.76, 37.76, 'Registrada', 'Efectivo', NULL),
(3, 'BOLETA', 'B001', 'B001 - 00003', '2026-01-18 15:05:00', 'María López Salazar', 'P4521133', 'Jr. Grau 245', 50.00, 9.00, 59.00, 'Registrada', 'Yape / Plin', NULL),
(4, 'FACTURA', 'F001', 'F001 - 00001', '2026-01-20 10:00:00', 'Comercial Tecno Cañete S.A.C.', '20601234567', 'Av. La Mar 890', 840.00, 151.20, 991.20, 'REGISTRADA', 'Transferencia', NULL),
(5, 'BOLETA', 'B001', 'B001 - 00004', '2026-01-22 09:40:00', 'Yanina Barcia Calzado', '41892744', 'Urb. Alameda de Montalván', 15.00, 2.70, 17.70, 'Registrada', 'Efectivo', NULL),
(6, 'BOLETA', 'B001', 'B001 - 00005', '2026-01-25 16:20:00', 'Rosa Elena Quispe Mamani', '45123687', 'Urb. Las Palmeras Mz. B', 40.00, 7.20, 47.20, 'Registrada', 'Tarjeta', NULL),
(7, 'BOLETA', 'B001', 'B001 - 00006', '2026-02-02 13:10:00', 'Jorge Luis Salvatierra Díaz', '70456123', 'Av. Mariscal Castilla 512', 45.00, 8.10, 53.10, 'Registrada', 'Efectivo', NULL),
(8, 'FACTURA', 'F001', 'F001 - 00002', '2026-02-05 09:50:00', 'Carmen Rosa Injante Villar', '20548712369', 'Jr. Bolognesi 340', 160.00, 28.80, 188.80, 'REGISTRADA', 'Transferencia', NULL),
(9, 'BOLETA', 'B001', 'B001 - 00007', '2026-02-08 12:00:00', 'Fiorella Nayeli Torres Cárdenas', '76541233', 'Urb. Miraflores Mz. C Lote 4', 32.00, 5.76, 37.76, 'Registrada', 'Yape / Plin', NULL),
(10, 'BOLETA', 'B001', 'B001 - 00008', '2026-02-10 17:45:00', 'Percy Alonso Guillén Rojas', '43219087', 'Jr. Ayacucho 155', 87.00, 15.66, 102.66, 'Registrada', 'Efectivo', NULL),
(11, 'FACTURA', 'F001', 'F001 - 00003', '2026-02-14 10:30:00', 'Distribuidora Eros Norte E.I.R.L.', '20512398741', 'Panamericana Sur Km 143', 1400.00, 252.00, 1652.00, 'REGISTRADA', 'Transferencia', NULL),
(12, 'BOLETA', 'B001', 'B001 - 00009', '2026-02-17 11:15:00', 'Katherine Milagros Ccahuana Espinoza', '71234598', 'Urb. San Martín Mz. D', 38.00, 6.84, 44.84, 'Registrada', 'Tarjeta', NULL),
(13, 'BOLETA', 'B001', 'B001 - 00010', '2026-02-20 09:05:00', 'Eduardo Alberto Ramos Ticona', '73788195', 'Urb. Santa Rosa Mz. A Lote 16', 20.00, 3.60, 23.60, 'Registrada', 'Efectivo', NULL),
(14, 'BOLETA', 'B001', 'B001 - 00011', '2026-02-22 14:40:00', 'Luis Fernández Padilla', '72158211', 'Cerro Alegre', 80.00, 14.40, 94.40, 'Registrada', 'Yape / Plin', NULL),
(15, 'FACTURA', 'F001', 'F001 - 00004', '2026-02-25 16:00:00', 'Comercial Tecno Cañete S.A.C.', '20601234567', 'Av. La Mar 890', 770.00, 138.60, 908.60, 'REGISTRADA', 'Transferencia', NULL),
(16, 'BOLETA', 'B001', 'B001 - 00012', '2026-03-01 10:10:00', 'María López Salazar', 'P4521133', 'Jr. Grau 245', 30.00, 5.40, 35.40, 'Registrada', 'Efectivo', NULL),
(17, 'BOLETA', 'B001', 'B001 - 00013', '2026-03-04 12:25:00', 'Rosa Elena Quispe Mamani', '45123687', 'Urb. Las Palmeras Mz. B', 20.00, 3.60, 23.60, 'Registrada', 'Tarjeta', NULL),
(18, 'BOLETA', 'B001', 'B001 - 00014', '2026-03-07 15:50:00', 'Percy Alonso Guillén Rojas', '43219087', 'Jr. Ayacucho 155', 220.00, 39.60, 259.60, 'Registrada', 'Efectivo', NULL),
(19, 'FACTURA', 'F001', 'F001 - 00005', '2026-03-10 09:30:00', 'Distribuidora Eros Norte E.I.R.L.', '20512398741', 'Panamericana Sur Km 143', 540.00, 97.20, 637.20, 'REGISTRADA', 'Transferencia', NULL),
(20, 'BOLETA', 'B001', 'B001 - 00015', '2026-03-12 18:00:00', 'Jorge Luis Salvatierra Díaz', '70456123', 'Av. Mariscal Castilla 512', 43.00, 7.74, 50.74, 'Registrada', 'Yape / Plin', NULL),
(23, 'BOLETA', 'B001', 'B001 - 00017', '2026-09-03 19:24:47', 'Luis Fernández Padilla', '72158211', 'Cerro Alegre', 32.20, 5.80, 38.00, 'REGISTRADA', 'Yape / Plin', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `compras_producto_fk` (`producto_codigo`);

--
-- Indices de la tabla `correlativos_documentos`
--
ALTER TABLE `correlativos_documentos`
  ADD PRIMARY KEY (`serie`);

--
-- Indices de la tabla `detalle_nota_venta`
--
ALTER TABLE `detalle_nota_venta`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_nota_venta` (`id_nota_venta`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `detalle_venta_producto_fk` (`producto_codigo`);

--
-- Indices de la tabla `nota_venta`
--
ALTER TABLE `nota_venta`
  ADD PRIMARY KEY (`id_nota_venta`);

--
-- Indices de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD PRIMARY KEY (`id_orden`);

--
-- Indices de la tabla `orden_repuestos`
--
ALTER TABLE `orden_repuestos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_orden` (`id_orden`),
  ADD KEY `orden_repuestos_producto_fk` (`producto_codigo`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD UNIQUE KEY `codigo_barras_unico` (`codigo_barras`);

--
-- Indices de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`id_tecnico`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD UNIQUE KEY `uq_serie_numero` (`serie_documento`,`numero_documento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle_nota_venta`
--
ALTER TABLE `detalle_nota_venta`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `nota_venta`
--
ALTER TABLE `nota_venta`
  MODIFY `id_nota_venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  MODIFY `id_orden` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `orden_repuestos`
--
ALTER TABLE `orden_repuestos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `id_tecnico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_producto_fk` FOREIGN KEY (`producto_codigo`) REFERENCES `productos` (`codigo`);

--
-- Filtros para la tabla `detalle_nota_venta`
--
ALTER TABLE `detalle_nota_venta`
  ADD CONSTRAINT `detalle_nota_venta_ibfk_1` FOREIGN KEY (`id_nota_venta`) REFERENCES `nota_venta` (`id_nota_venta`);

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`),
  ADD CONSTRAINT `detalle_venta_producto_fk` FOREIGN KEY (`producto_codigo`) REFERENCES `productos` (`codigo`);

--
-- Filtros para la tabla `orden_repuestos`
--
ALTER TABLE `orden_repuestos`
  ADD CONSTRAINT `orden_repuestos_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `ordenes` (`id_orden`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_repuestos_producto_fk` FOREIGN KEY (`producto_codigo`) REFERENCES `productos` (`codigo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ---------- agregado para la web pública ----------
-- Es independiente de la tabla `clientes` del sistema (que usan
-- los técnicos al crear órdenes) para no arriesgar esa tabla.
CREATE TABLE IF NOT EXISTS `clientes_web` (
  `id_cliente_web` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `estado` enum('pendiente','activo','rechazado') NOT NULL DEFAULT 'pendiente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cliente_web`),
  UNIQUE KEY `telefono` (`telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
