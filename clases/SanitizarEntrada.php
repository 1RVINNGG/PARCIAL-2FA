<?php
declare(strict_types=1);

/** Métodos estáticos para sanitizar los datos de entrada (una sola responsabilidad cada uno). */
final class SanitizarEntrada
{
    /** Quita espacios, etiquetas HTML/PHP, caracteres de control y espacios repetidos. */
    public static function limpiarCadena(?string $cadena): string
    {
        $cadena = trim(strip_tags((string)$cadena));
        $cadena = preg_replace('/[\x00-\x1F\x7F]/u', '', $cadena) ?? '';
        return preg_replace('/\s+/u', ' ', $cadena) ?? '';
    }

    /** Nombres/apellidos: solo letras (con tildes), espacios, apóstrofe y guion. */
    public static function limpiarNombre(?string $cadena): string
    {
        $cadena = self::limpiarCadena($cadena);
        return trim(preg_replace("/[^\p{L}\s'\-]/u", '', $cadena) ?? '');
    }

    /** Correo en minúsculas y sin caracteres inválidos. */
    public static function limpiarEmail(?string $correo): string
    {
        return (string)filter_var(strtolower(trim((string)$correo)), FILTER_SANITIZE_EMAIL);
    }

    /** Nombre de usuario: letras, números, punto, guion y guion bajo. */
    public static function limpiarUsuario(?string $usuario): string
    {
        return preg_replace('/[^A-Za-z0-9._\-]/', '', trim((string)$usuario)) ?? '';
    }

    /** Código TOTP: solo dígitos, máximo 6. */
    public static function limpiarCodigo(?string $codigo): string
    {
        return substr(preg_replace('/\D/', '', (string)$codigo) ?? '', 0, 6);
    }

    /** Secreto 2FA (base32): solo A-Z, 2-7 y '='. Si no cumple el formato devuelve cadena vacía. */
    public static function limpiarSecreto(?string $secreto): string
    {
        $secreto = strtoupper(trim((string)$secreto));
        return preg_match('/^[A-Z2-7=]+$/', $secreto) ? $secreto : '';
    }

    /** Sexo: solo 'M' o 'F'; cualquier otra cosa queda vacía. */
    public static function limpiarSexo(?string $sexo): string
    {
        $sexo = strtoupper(trim((string)$sexo));
        return in_array($sexo, ['M', 'F'], true) ? $sexo : '';
    }

    /** Contraseñas: NO se alteran (solo se elimina el byte nulo) para no debilitarlas. */
    public static function limpiarClave(?string $clave): string
    {
        return str_replace("\0", '', (string)$clave);
    }

    /** Escapa para imprimir en HTML (previene XSS). */
    public static function escapar(?string $texto): string
    {
        return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
