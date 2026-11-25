<?php
declare(strict_types=1);

/**
 * Formulario de Registro de Datos.
 * Cada método tiene UNA responsabilidad: cargar, sanitizar, validar un aspecto,
 * hashear o guardar. La base de datos se accede solo vía UsuarioRepository.
 */
final class RegistroUsuario
{
    private array $datos = [];
    private string $clave = '';
    private string $confirmacion = '';
    private array $errores = [];

    public function __construct(
        private UsuarioRepository $repo,
        private HashInterface $hasher
    ) {}

    public function cargar(array $post): self
    {
        $this->datos = $this->sanitizar($post);
        $this->clave = SanitizarEntrada::limpiarClave($post['clave'] ?? '');
        $this->confirmacion = SanitizarEntrada::limpiarClave($post['clave2'] ?? '');
        return $this;
    }

    private function sanitizar(array $post): array
    {
        return [
            'nombre'   => SanitizarEntrada::limpiarNombre($post['nombre'] ?? ''),
            'apellido' => SanitizarEntrada::limpiarNombre($post['apellido'] ?? ''),
            'correo'   => SanitizarEntrada::limpiarEmail($post['correo'] ?? ''),
            'usuario'  => SanitizarEntrada::limpiarUsuario($post['usuario'] ?? ''),
            'sexo'     => SanitizarEntrada::limpiarSexo($post['sexo'] ?? ''),
        ];
    }

    public function validar(): bool
    {
        $this->errores = [];
        $this->validarRequeridos();
        $this->validarCorreo();
        $this->validarUsuario();
        $this->validarClave();
        if (!$this->errores) {
            $this->validarNoDuplicados();
        }
        return !$this->errores;
    }

    private function error(string $campo, string $mensaje): void
    {
        $this->errores[$campo] ??= $mensaje;
    }

    private function validarRequeridos(): void
    {
        foreach (['nombre', 'apellido', 'correo', 'usuario', 'sexo'] as $c) {
            if ($this->datos[$c] === '') {
                $this->error($c, 'Este campo es obligatorio.');
            }
        }
        if ($this->clave === '')        { $this->error('clave', 'La contraseña es obligatoria.'); }
        if ($this->confirmacion === '') { $this->error('clave2', 'Confirme la contraseña.'); }
    }

    private function validarCorreo(): void
    {
        if ($this->datos['correo'] !== '' && !filter_var($this->datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $this->error('correo', 'Correo electrónico no válido.');
        }
    }

    private function validarUsuario(): void
    {
        if ($this->datos['usuario'] !== '' && !preg_match('/^[A-Za-z0-9._\-]{4,30}$/', $this->datos['usuario'])) {
            $this->error('usuario', 'Use 4 a 30 caracteres: letras, números, . _ -');
        }
    }

    private function validarClave(): void
    {
        if ($this->clave === '') {
            return;
        }
        if (strlen($this->clave) < 8 || !preg_match('/[a-z]/', $this->clave)
            || !preg_match('/[A-Z]/', $this->clave) || !preg_match('/\d/', $this->clave)) {
            $this->error('clave', 'Mínimo 8 caracteres con mayúscula, minúscula y número.');
        }
        if ($this->confirmacion !== '' && !hash_equals($this->clave, $this->confirmacion)) {
            $this->error('clave2', 'Las contraseñas no coinciden.');
        }
    }

    private function validarNoDuplicados(): void
    {
        if ($this->repo->existeCorreo($this->datos['correo'])) {
            $this->error('correo', 'Este correo ya está registrado.');
        }
        if ($this->repo->existeUsuario($this->datos['usuario'])) {
            $this->error('usuario', 'Este nombre de usuario ya existe.');
        }
    }

    private function generarHash(): string
    {
        return $this->hasher->generar($this->clave);
    }

    /** Guarda el usuario y devuelve su id (o null si hay errores). */
    public function guardar(): ?int
    {
        if (!$this->validar()) {
            return null;
        }
        return $this->repo->crear($this->datos + ['hash' => $this->generarHash()]);
    }

    public function getErrores(): array { return $this->errores; }
    public function getDatos(): array   { return $this->datos; }
}
