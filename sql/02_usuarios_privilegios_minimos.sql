-- =====================================================================
-- Lab 2FA - Paso 2: Usuarios de MySQL con PRIVILEGIOS MÍNIMOS
-- (la aplicación NUNCA usa root). Ejecutar con root:
--   mysql -u root -p < sql/02_usuarios_privilegios_minimos.sql
-- =====================================================================

-- Usuario 1: la aplicación web (registro + login + 2FA)
CREATE USER IF NOT EXISTS 'app_2fa'@'localhost' IDENTIFIED BY 'App2FA#2026!';
GRANT SELECT, INSERT, UPDATE ON login2fa.usuarios        TO 'app_2fa'@'localhost';
GRANT SELECT, INSERT         ON login2fa.intentos_login  TO 'app_2fa'@'localhost';
-- (sin DELETE, DROP, ALTER, CREATE, GRANT OPTION ni acceso a otras bases)

-- Usuario 2: solo lectura para reportes, SIN acceso a HashMagic ni secret_2fa
CREATE USER IF NOT EXISTS 'lector_2fa'@'localhost' IDENTIFIED BY 'Lector2FA#2026!';
GRANT SELECT (id, Nombre, Apellido, Correo, Usuario, Sexo, fecha_registro, confirmado_2fa)
      ON login2fa.usuarios TO 'lector_2fa'@'localhost';
GRANT SELECT ON login2fa.intentos_login TO 'lector_2fa'@'localhost';

FLUSH PRIVILEGES;

-- ---- Mostrar los privilegios concedidos ----
SHOW GRANTS FOR 'app_2fa'@'localhost';
SHOW GRANTS FOR 'lector_2fa'@'localhost';
