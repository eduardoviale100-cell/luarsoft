-- ==================================================================
-- luarsoft_master.sql — Base de Datos Central Multi-Tenant (LuarSoft SaaS)
-- ------------------------------------------------------------------
-- Esta base de datos gestiona todas las empresas/negocios registrados
-- en la plataforma LuarSoft, sus estados de suscripción, módulos
-- contratados y las credenciales de los Super Administradores del Panel Master.
-- ==================================================================

CREATE DATABASE IF NOT EXISTS `luarsoft_master` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `luarsoft_master`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `sys_negocios`;
DROP TABLE IF EXISTS `sys_superadmins`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Tabla: `sys_negocios`
-- Almacena la información técnica y comercial de cada cliente/negocio.
-- --------------------------------------------------------
CREATE TABLE `sys_negocios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_comercial` VARCHAR(150) NOT NULL COMMENT 'Nombre público del negocio (ej. Multiservicios Eros)',
  `razon_social` VARCHAR(200) NOT NULL COMMENT 'Razón social para facturación/SUNAT',
  `ruc` VARCHAR(11) NOT NULL COMMENT 'RUC de 11 dígitos',
  `direccion` TEXT DEFAULT NULL COMMENT 'Dirección principal del establecimiento',
  `slug` VARCHAR(50) NOT NULL COMMENT 'Identificador único (usado en URL, subdominio o carpeta)',
  `db_name` VARCHAR(100) NOT NULL COMMENT 'Nombre de la BD MySQL asignada al tenant (ej. luarsoft o erp_eros)',
  `db_host` VARCHAR(100) DEFAULT 'localhost' COMMENT 'Host de la BD del tenant',
  `db_user` VARCHAR(100) DEFAULT 'root' COMMENT 'Usuario de acceso a la BD del tenant',
  `db_pass` VARCHAR(255) DEFAULT '' COMMENT 'Contraseña de la BD del tenant',
  `admin_user` VARCHAR(100) DEFAULT 'admin' COMMENT 'Usuario administrador inicial del ERP',
  `admin_pass` VARCHAR(100) DEFAULT 'Admin123*' COMMENT 'Contraseña administrador inicial del ERP',
  `logo_url` VARCHAR(255) DEFAULT NULL COMMENT 'Ruta o URL del logotipo del negocio',
  `modulos_activos` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array JSON con los módulos habilitados',
  `estado` ENUM('activo', 'suspendido') NOT NULL DEFAULT 'activo' COMMENT 'Estado del servicio',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ruc` (`ruc`),
  UNIQUE KEY `uk_slug` (`slug`),
  UNIQUE KEY `uk_db_name` (`db_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de empresas/tenants en LuarSoft';

-- --------------------------------------------------------
-- Tabla: `sys_superadmins`
-- Usuarios con acceso al Panel Master (Super Administradores).
-- --------------------------------------------------------
CREATE TABLE `sys_superadmins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario` (`usuario`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Super Administradores del Panel Master';

-- --------------------------------------------------------
-- Datos Iniciales (Semilla / Seeding)
-- --------------------------------------------------------

-- 1. Instancia Base: Multiservicios Eros (Primer negocio registrado)
INSERT INTO `sys_negocios` (
  `id`,
  `nombre_comercial`,
  `razon_social`,
  `ruc`,
  `direccion`,
  `slug`,
  `db_name`,
  `admin_user`,
  `admin_pass`,
  `logo_url`,
  `modulos_activos`,
  `estado`
) VALUES (
  1,
  'Multiservicios Eros',
  'Eros Tecnología S.A.C.',
  '20600000001',
  'Jr. 28 de Julio 123, Imperial, Cañete, Lima',
  'eros',
  'luarsoft',
  'admin',
  'Admin123*',
  'assets/img/eros.jpg',
  '["dashboard", "pos", "clientes", "productos", "tecnicos", "ordenes", "ventas", "reportes", "usuarios", "compras"]',
  'activo'
);

-- 2. Usuario SuperAdmin Inicial para el Panel Master
-- Contraseña por defecto: AdminMaster2026* (Hasheada con BCRYPT)
INSERT INTO `sys_superadmins` (`id`, `nombre`, `usuario`, `email`, `password`, `estado`) VALUES
(1, 'Super Admin LuarSoft', 'adminmaster', 'master@luarsoft.com', '$2y$10$FZsgXtbEx94Z/k7q7pcw1upx5XsgWREndf/2Y07oa9m5sCUuIxD42', 'activo');
