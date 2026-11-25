<?php
declare(strict_types=1);

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

/** Envuelve la librería sonata-project/google-authenticator (TOTP, compatible con Google Authenticator). */
final class Autenticador2FA
{
    private GoogleAuthenticator $g;

    public function __construct(private string $emisor = 'MiSistema2FA')
    {
        $this->g = new GoogleAuthenticator();
    }

    public function generarSecreto(): string
    {
        return $this->g->generateSecret();
    }

    /** URL de la imagen QR (la librería ya devuelve la URL completa de qrserver). */
    public function urlQr(string $correo, string $secreto): string
    {
        return GoogleQrUrl::generate($correo, $secreto, $this->emisor, 220);
    }

    public function verificar(string $secreto, string $codigo): bool
    {
        return strlen($codigo) === 6 && $this->g->checkCode($secreto, $codigo);
    }

    /** Solo para pruebas automáticas. */
    public function codigoActual(string $secreto): string
    {
        return $this->g->getCode($secreto);
    }
}
