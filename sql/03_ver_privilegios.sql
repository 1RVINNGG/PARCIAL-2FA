-- Comandos para ver los privilegios de los usuarios creados (mostrar en pantalla)
SHOW GRANTS FOR 'app_2fa'@'localhost';
SHOW GRANTS FOR 'lector_2fa'@'localhost';
SHOW GRANTS FOR CURRENT_USER();     -- usuario con el que estás conectado
SELECT User, Host FROM mysql.user WHERE User IN ('app_2fa','lector_2fa');

-- Limpieza (opcional): quitar privilegios y eliminar usuarios
-- REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'app_2fa'@'localhost';
-- DROP USER 'app_2fa'@'localhost';
-- DROP USER 'lector_2fa'@'localhost';
