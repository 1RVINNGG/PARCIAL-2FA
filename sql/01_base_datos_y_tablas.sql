-- =====================================================================
-- Lab 2FA - Paso 1: Base de datos y tablas
-- Ejecutar UNA vez con una cuenta administrativa (root) desde consola o phpMyAdmin:
--   mysql -u root -p < sql/01_base_datos_y_tablas.sql
-- =====================================================================
-- Si ya tenías una versión anterior de la BD/tablas (columnas distintas), descomenta la línea siguiente:
-- DROP DATABASE IF EXISTS login2fa;
CREATE DATABASE IF NOT EXISTS login2fa
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE login2fa;

-- Tabla 1: usuarios
CREATE TABLE IF NOT EXISTS usuarios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nombre         VARCHAR(60)  NOT NULL,
  Apellido       VARCHAR(60)  NOT NULL,
  Correo         VARCHAR(120) NOT NULL,
  Usuario        VARCHAR(30)  NOT NULL,
  HashMagic      VARCHAR(255) NOT NULL,           -- contraseña con hash (bcrypt/argon2)
  secret_2fa     VARCHAR(255) NULL,               -- secreto TOTP (base32) para Google Authenticator
  confirmado_2fa TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = ya escaneó el QR y confirmó un código válido
  Sexo           ENUM('M','F') NOT NULL,
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo (Correo),
  UNIQUE KEY uq_usuarios_usuario (Usuario)
) ENGINE=InnoDB;

-- Tabla 2: intentos_login (bitácora de intentos de password y de código 2FA)
CREATE TABLE IF NOT EXISTS intentos_login (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Usuario   VARCHAR(120) NOT NULL,
  ipRemoto  VARCHAR(45)  NOT NULL,
  etapa     ENUM('PASSWORD','2FA','REGISTRO_2FA') NOT NULL,
  exitoso   TINYINT(1)   NOT NULL,
  fecha     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_intentos_usuario_fecha (Usuario, fecha)
) ENGINE=InnoDB;
