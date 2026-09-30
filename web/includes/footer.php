<footer id="nosotros">
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <img src="<?= RUTA_SISTEMA_WEB ?>assets/img/eros.jpg" alt="Eros Tecnología">
        <span>EROS TECNOLOGÍA</span>
      </div>
      <div class="foot-cols">
        <div><p>Ubicación</p><p>Jirón Progreso con Av. La Mar</p><p>Imperial, Cañete</p></div>
        <div><p>Contacto</p><p>Cel: 949 092 352</p><p>WhatsApp disponible</p></div>
        <div><p>Horario</p><p>Lun a sáb: 9am – 8pm</p><p>Domingo: 10am – 2pm</p></div>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© 2026 Eros Tecnología</span>
      <span>Multiservicios · Soporte técnico · Accesorios</span>
    </div>
  </div>
</footer>

<div class="asistente" id="asistenteEros">
  <div class="asistente-panel">
    <div class="asistente-head">
      <span class="dot2"></span>
      <div><p>Asistente Eros</p><p class="sub">Responde en segundos</p></div>
    </div>
    <div class="asistente-body">
      <div class="bubble bot">Hola 👋 ¿Tu equipo tiene algún problema o buscas un accesorio?</div>
      <div class="bubble user">Mi laptop no enciende</div>
      <div class="bubble bot">Puede ser la fuente o la placa. Te conecto con un técnico por WhatsApp para revisarlo hoy mismo.</div>
    </div>
    <div class="asistente-input">
      <input type="text" placeholder="Escribe tu consulta...">
      <button type="button" aria-label="Enviar">➤</button>
    </div>
  </div>
  <button type="button" class="asistente-fab" aria-label="Abrir asistente" onclick="document.getElementById('asistenteEros').classList.toggle('abierto')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
  </button>
</div>

<script>
// Menú móvil: abre/cierra el panel y gira el ícono de hamburguesa a "X".
(function () {
  var boton = document.getElementById('menuToggle');
  var panel = document.getElementById('menuMovil');
  if (!boton || !panel) return;
  boton.addEventListener('click', function () {
    var abierto = panel.classList.toggle('abierto');
    boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
  });
  // Si tocan un enlace dentro del panel, se cierra solo.
  panel.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () {
      panel.classList.remove('abierto');
      boton.setAttribute('aria-expanded', 'false');
    });
  });
})();
</script>

<script>
// Animación de aparición al hacer scroll (aplica a toda la web)
if ('IntersectionObserver' in window) {
  var observador = new IntersectionObserver(function (entradas) {
    entradas.forEach(function (entrada) {
      if (entrada.isIntersecting) {
        entrada.target.classList.add('visible');
        observador.unobserve(entrada.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(function (el) { observador.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('visible'); });
}
</script>

<script>
(function () {
  var cuerpo   = document.querySelector('#asistenteEros .asistente-body');
  var input    = document.querySelector('#asistenteEros .asistente-input input');
  var boton    = document.querySelector('#asistenteEros .asistente-input button');

  var respuestas = [
    { claves: ['no enciende', 'no prende', 'no arranca'], texto: 'Puede ser la fuente de poder o la placa. Te conviene traerlo para un diagnóstico gratuito — ¿quieres que te derive a WhatsApp?' },
    { claves: ['impresora', 'imprim', 'tinta'], texto: 'Vendemos tinta compatible y hacemos mantenimiento de impresoras. ¿Qué marca es tu equipo?' },
    { claves: ['precio', 'cuanto cuesta', 'cuánto cuesta', 'costo'], texto: 'Los precios varían según el diagnóstico. Si te registras puedes ver el catálogo completo con precios reales.' },
    { claves: ['horario', 'abren', 'atienden'], texto: 'Atendemos de lunes a sábado de 9am a 8pm, y domingos de 10am a 2pm.' },
    { claves: ['ubicacion', 'ubicación', 'direccion', 'dirección', 'donde quedan', 'dónde quedan'], texto: 'Estamos en Jirón Progreso con Av. La Mar, Imperial, Cañete.' },
    { claves: ['gracias'], texto: '¡De nada! Cualquier otra consulta, aquí estoy. 🙂' }
  ];

  function respuestaPara(mensaje) {
    var m = mensaje.toLowerCase();
    for (var i = 0; i < respuestas.length; i++) {
      for (var j = 0; j < respuestas[i].claves.length; j++) {
        if (m.indexOf(respuestas[i].claves[j]) !== -1) return respuestas[i].texto;
      }
    }
    return 'Gracias por escribir. Para revisar esto con detalle, te conviene hablar directo con un técnico por WhatsApp: <a href="https://wa.me/51949092352" target="_blank" style="color:var(--azul); font-weight:600;">escríbenos aquí</a>.';
  }

  function agregarBurbuja(texto, clase) {
    var burbuja = document.createElement('div');
    burbuja.className = 'bubble ' + clase;
    burbuja.innerHTML = texto;
    cuerpo.appendChild(burbuja);
    cuerpo.scrollTop = cuerpo.scrollHeight;
  }

  function enviar() {
    var texto = (input.value || '').trim();
    if (!texto) return;
    agregarBurbuja(texto, 'user');
    input.value = '';
    setTimeout(function () {
      agregarBurbuja(respuestaPara(texto), 'bot');
    }, 500);
  }

  boton.addEventListener('click', enviar);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); enviar(); }
  });
})();
</script>

</body>
</html>
