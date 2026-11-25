/* Validaciones del lado del cliente (frontend) para el formulario de registro. */
(function () {
  'use strict';
  const f = document.getElementById('formRegistro');
  if (!f) return;
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const $ = (id) => document.getElementById(id);
  const campos = ['nombre', 'apellido', 'correo', 'usuario', 'sexo', 'clave', 'clave2'];
  const duplicado = { correo: false, usuario: false };
  const reEmail = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  const reNombre = /^[\p{L}][\p{L}\s'\-]*$/u;
  const reUsuario = /^[A-Za-z0-9._\-]{4,30}$/;

  function marcar(id, mensaje, estado) {
    const el = $(id), ay = $('ay_' + id);
    el.classList.toggle('invalido', estado === 'error');
    el.classList.toggle('valido', estado === 'ok');
    ay.textContent = mensaje || '';
    ay.className = 'ayuda' + (estado === 'error' ? ' error' : estado === 'ok' ? ' ok' : '');
    return estado !== 'error';
  }

  const reglas = {
    nombre:   (v) => !v ? 'El nombre es obligatorio.' : !reNombre.test(v) ? 'Solo letras y espacios.' : '',
    apellido: (v) => !v ? 'El apellido es obligatorio.' : !reNombre.test(v) ? 'Solo letras y espacios.' : '',
    correo:   (v) => !v ? 'El correo es obligatorio.' : !reEmail.test(v) ? 'Correo electrónico no válido.'
                     : duplicado.correo ? 'Este correo ya está registrado.' : '',
    usuario:  (v) => !v ? 'El usuario es obligatorio.' : !reUsuario.test(v) ? '4-30 caracteres: letras, números, . _ -'
                     : duplicado.usuario ? 'Este nombre de usuario ya existe.' : '',
    sexo:     (v) => !v ? 'Seleccione una opción.' : '',
    clave:    (v) => !v ? 'La contraseña es obligatoria.'
                     : (v.length < 8 || !/[a-z]/.test(v) || !/[A-Z]/.test(v) || !/\d/.test(v))
                       ? 'Mínimo 8 caracteres con mayúscula, minúscula y número.' : '',
    clave2:   (v) => !v ? 'Confirme la contraseña.' : v !== $('clave').value ? 'Las contraseñas no coinciden.' : '',
  };

  function validar(id) {
    const v = id.startsWith('clave') ? $(id).value : $(id).value.trim();
    const msg = reglas[id](v);
    return marcar(id, msg, msg ? 'error' : 'ok');
  }

  // Comprobación de duplicados contra el servidor (AJAX)
  const temporizadores = {};
  function revisarDuplicado(campo) {
    const valor = $(campo).value.trim();
    duplicado[campo] = false;
    if (reglas[campo](valor)) { validar(campo); return; }
    clearTimeout(temporizadores[campo]);
    temporizadores[campo] = setTimeout(async () => {
      try {
        const r = await fetch('check_duplicado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': token },
          body: new URLSearchParams({ campo, valor }),
        });
        const j = await r.json();
        duplicado[campo] = !!j.existe;
      } catch (_) { /* si falla la red, el servidor igual valida */ }
      validar(campo);
    }, 350);
  }

  ['correo', 'usuario'].forEach((c) => $(c).addEventListener('input', () => revisarDuplicado(c)));
  ['nombre', 'apellido', 'sexo'].forEach((c) => $(c).addEventListener('input', () => validar(c)));
  $('clave').addEventListener('input', () => { validar('clave'); medirFuerza(); if ($('clave2').value) validar('clave2'); });
  $('clave2').addEventListener('input', () => validar('clave2'));

  function medirFuerza() {
    const v = $('clave').value;
    let p = 0;
    if (v.length >= 8) p++; if (v.length >= 12) p++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) p++;
    if (/\d/.test(v)) p++; if (/[^A-Za-z0-9]/.test(v)) p++;
    const barra = $('fuerza').firstElementChild;
    barra.style.width = (p / 5 * 100) + '%';
    barra.style.background = p <= 2 ? '#ef4444' : p <= 3 ? '#f59e0b' : '#22c55e';
  }

  document.querySelectorAll('.ojo').forEach((b) => b.addEventListener('click', () => {
    const i = $(b.dataset.para);
    const oculto = i.type === 'password';
    i.type = oculto ? 'text' : 'password';
    b.textContent = oculto ? 'Ocultar' : 'Mostrar';
  }));

  f.addEventListener('submit', (ev) => {
    const todosOk = campos.map(validar).every(Boolean);
    if (!todosOk || duplicado.correo || duplicado.usuario) {
      ev.preventDefault();
      const primero = f.querySelector('.invalido');
      if (primero) primero.focus();
    }
  });
})();
