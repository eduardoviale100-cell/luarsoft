/**
 * assets/js/voz.js
 * ------------------------------------------------------------------
 * Accesibilidad por Comando de Voz — disponible en TODO el sistema.
 * Se carga una sola vez desde includes/layout_bottom.php, que a su vez
 * se incluye en todas las páginas del panel (Panel Principal, POS,
 * Clientes, Productos, Técnicos, Órdenes, Ventas, Reportes, Usuarios,
 * Compras, Galería, etc.), así que este archivo no necesita tocar
 * ningún módulo existente: solo se agrega un único <script> nuevo.
 *
 * Funcionamiento:
 *   1. Inyecta un botón flotante de micrófono (abajo a la derecha) en
 *      cualquier página donde se cargue este script.
 *   2. Al hacer clic, activa el reconocimiento de voz del navegador
 *      (Web Speech API) en español y escucha UNA orden.
 *   3. Compara lo dicho contra un diccionario de comandos de
 *      navegación y acciones, y ejecuta el primero que coincida.
 *   4. Da retroalimentación visual (toast) y hablada (síntesis de voz)
 *      de lo que entendió antes de actuar.
 *
 * Limitación honesta: el reconocimiento de voz del navegador
 * (SpeechRecognition / webkitSpeechRecognition) solo está disponible
 * en navegadores basados en Chromium (Chrome, Edge, Opera, Brave).
 * Firefox y Safari no lo soportan de forma nativa; en esos casos el
 * botón de micrófono simplemente no aparece, en vez de mostrar un
 * botón roto que no hace nada.
 */
(function () {
  "use strict";

  const SpeechRecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognitionAPI) {
    // Navegador sin soporte (Firefox, Safari, navegadores antiguos): no se
    // muestra ningún botón, para no ofrecer una función que no funcionará.
    return;
  }

  const BASE = window.ERP_BASE_URL || "/";
  function ir(ruta) { return BASE.replace(/\/$/, "") + "/" + ruta.replace(/^\//, ""); }

  /** Quita tildes/acentos para que el reconocimiento sea más tolerante. */
  function normalizar(texto) {
    return texto
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  /** Habla un texto en español usando la síntesis de voz del navegador (si existe). */
  function hablar(texto, alTerminar) {
    const continuar = alTerminar || function () {};
    if (!window.speechSynthesis) { continuar(); return; }
    try {
      window.speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(texto);
      u.lang = "es-PE";
      u.rate = 1.02;

      let yaContinuo = false;
      const continuarUnaVez = function () {
        if (yaContinuo) return;
        yaContinuo = true;
        continuar();
      };

      u.onend = continuarUnaVez;
      u.onerror = continuarUnaVez;
      // Límite de seguridad: si por algún motivo el navegador nunca dispara
      // "onend" (pasa en algunos Android/WebView), no se queda esperando
      // para siempre — se calcula un tiempo generoso según el largo del
      // texto y se continúa de todos modos.
      const tiempoMaximo = Math.max(1200, texto.length * 90);
      setTimeout(continuarUnaVez, tiempoMaximo);

      window.speechSynthesis.speak(u);
    } catch (e) {
      continuar();
    }
  }

  function avisar(mensaje) {
    if (window.ERP && typeof window.ERP.toast === "function") {
      window.ERP.toast("success", mensaje);
    }
  }

  function avisarError(mensaje) {
    if (window.ERP && typeof window.ERP.toast === "function") {
      window.ERP.toast("error", mensaje);
    } else {
      alert(mensaje);
    }
  }

  // --------------------------------------------------------------------
  // Diccionario de comandos: cada uno tiene frases que lo activan (ya
  // normalizadas, sin tildes) y una acción a ejecutar. Se revisan en
  // orden, así que los comandos más específicos van primero.
  // --------------------------------------------------------------------
  const COMANDOS = [
    { frases: ["ayuda", "que puedo decir", "que comandos hay", "lista de comandos"], etiqueta: "Ayuda", accion: () => mostrarAyuda() },

    { frases: ["nueva venta", "punto de venta", "abrir pos", "ir al pos", "vender"], etiqueta: "Punto de Venta", accion: () => navegar("modules/ventas/pos.php", "Abriendo el Punto de Venta") },
    { frases: ["escanear codigo", "escanear producto", "escanear", "leer codigo de barras"], etiqueta: "Escanear Código de Barras", accion: () => activarEscaner() },
    { frases: ["nuevo cliente por voz", "crear cliente por voz", "registrar cliente por voz"], etiqueta: "Nuevo Cliente por Voz", accion: () => navegarADictado("modules/clientes/nuevo.php", "nuevo cliente") },
    { frases: ["nuevo cliente", "agregar cliente", "registrar cliente"], etiqueta: "Nuevo Cliente", accion: () => navegar("modules/clientes/nuevo.php", "Abriendo formulario de nuevo cliente") },
    { frases: ["buscar cliente"], etiqueta: "Buscar Cliente", accion: () => navegar("modules/clientes/buscar.php", "Abriendo buscador de clientes") },
    { frases: ["clientes", "lista de clientes", "listado de clientes", "ver clientes"], etiqueta: "Clientes", accion: () => navegar("modules/clientes/listado.php", "Abriendo Clientes") },

    { frases: ["nuevo producto por voz", "crear producto por voz", "registrar producto por voz"], etiqueta: "Nuevo Producto por Voz", accion: () => navegarADictado("modules/productos/nuevo.php", "nuevo producto") },
    { frases: ["nuevo producto", "agregar producto", "registrar producto"], etiqueta: "Nuevo Producto", accion: () => navegar("modules/productos/nuevo.php", "Abriendo formulario de nuevo producto") },
    { frases: ["galeria de productos", "galeria", "catalogo de productos", "catalogo"], etiqueta: "Galería de Productos", accion: () => navegar("modules/productos/galeria.php", "Abriendo la Galería de Productos") },
    { frases: ["control de stock", "ver stock"], etiqueta: "Control de Stock", accion: () => navegar("modules/productos/control_stock.php", "Abriendo Control de Stock") },
    { frases: ["productos", "lista de productos", "listado de productos", "ver productos", "inventario"], etiqueta: "Productos", accion: () => navegar("modules/productos/listado.php", "Abriendo Productos") },

    { frases: ["registrar compra", "nueva compra"], etiqueta: "Nueva Compra", accion: () => navegar("modules/compras/nuevo.php", "Abriendo formulario de nueva compra") },
    { frases: ["registro de compras", "lista de compras", "ver compras"], etiqueta: "Registro de Compras", accion: () => navegar("modules/compras/listado.php", "Abriendo Registro de Compras") },

    { frases: ["nuevo tecnico", "agregar tecnico", "registrar tecnico"], etiqueta: "Nuevo Técnico", accion: () => navegar("modules/tecnicos/nuevo.php", "Abriendo formulario de nuevo técnico") },
    { frases: ["tecnicos", "lista de tecnicos", "listado de tecnicos", "ver tecnicos"], etiqueta: "Técnicos", accion: () => navegar("modules/tecnicos/listado.php", "Abriendo Técnicos") },

    { frases: ["nueva orden", "nueva reparacion", "registrar orden", "crear orden"], etiqueta: "Nueva Orden", accion: () => navegar("modules/ordenes/nuevo.php", "Abriendo formulario de nueva orden de reparación") },
    { frases: ["ordenes de reparacion", "ordenes", "reparaciones", "lista de ordenes", "ver ordenes"], etiqueta: "Órdenes de Reparación", accion: () => navegar("modules/ordenes/listado.php", "Abriendo Órdenes de Reparación") },

    { frases: ["boletas", "lista de boletas", "ver boletas"], etiqueta: "Boletas", accion: () => navegar("modules/ventas/lista_boletas.php", "Abriendo listado de Boletas") },
    { frases: ["facturas", "lista de facturas", "ver facturas"], etiqueta: "Facturas", accion: () => navegar("modules/ventas/lista_facturas.php", "Abriendo listado de Facturas") },

    { frases: ["mejores clientes"], etiqueta: "Mejores Clientes", accion: () => navegar("modules/reportes/mejores_clientes.php", "Abriendo reporte de Mejores Clientes") },
    { frases: ["mas vendidos", "lo mas vendido", "productos mas vendidos"], etiqueta: "Lo Más Vendido", accion: () => navegar("modules/reportes/productos_mas_vendidos.php", "Abriendo reporte de lo más vendido") },
    { frases: ["kardex"], etiqueta: "Kardex", accion: () => navegar("modules/reportes/kardex.php", "Abriendo el Kardex de Inventario") },
    { frases: ["flujo de ingresos", "flujo de caja"], etiqueta: "Flujo de Ingresos", accion: () => navegar("modules/reportes/flujo_ingresos.php", "Abriendo Flujo de Ingresos") },
    { frases: ["servicios del mes"], etiqueta: "Servicios del Mes", accion: () => navegar("modules/reportes/servicios_mes.php", "Abriendo Servicios del Mes") },
    { frases: ["reportes", "ver reportes"], etiqueta: "Reportes", accion: () => navegar("modules/reportes/productos_mas_vendidos.php", "Abriendo Reportes") },

    { frases: ["usuarios", "gestion de usuarios", "ver usuarios"], etiqueta: "Usuarios", accion: () => navegar("modules/usuarios/listado.php", "Abriendo Usuarios") },

    { frases: ["panel principal", "inicio", "dashboard", "ir al panel", "pagina principal"], etiqueta: "Panel Principal", accion: () => navegar("index.php", "Abriendo el Panel Principal") },

    { frases: ["cerrar sesion", "salir del sistema", "salir"], etiqueta: "Cerrar Sesión", accion: () => cerrarSesion() },
  ];

  function navegar(ruta, mensajeHablado) {
    avisar(mensajeHablado + "…");
    hablar(mensajeHablado, function () {
      window.location.href = ir(ruta);
    });
  }

  function navegarADictado(ruta, etiqueta) {
    avisar("Preparando dictado guiado: " + etiqueta + "…");
    hablar("Vamos a " + etiqueta + " por voz.", function () {
      window.location.href = ir(ruta) + (ruta.includes("?") ? "&" : "?") + "dictado=1";
    });
  }

  function cerrarSesion() {
    avisar("Cerrando sesión…");
    hablar("Cerrando sesión", function () {
      window.location.href = ir("logout.php");
    });
  }

  function activarEscaner() {
    if (!window.ERPEscaner) {
      avisarError("El módulo de escaneo no está disponible en esta página.");
      return;
    }
    const inputPos = document.getElementById("inputEscanerPos");
    const inputProducto = document.getElementById("inputCodigoBarras");

    if (inputPos && typeof window.procesarCodigoEscaneado === "function") {
      avisar("Abriendo el escáner de cámara…");
      hablar("Apunta la cámara al código de barras");
      window.ERPEscaner.abrir(window.procesarCodigoEscaneado);
    } else if (inputProducto) {
      avisar("Abriendo el escáner de cámara…");
      hablar("Apunta la cámara al código de barras");
      window.ERPEscaner.abrir(function (codigo) { inputProducto.value = codigo; });
    } else {
      avisarError("Ve primero al Punto de Venta o a un formulario de Producto para poder escanear.");
      hablar("Ve primero al Punto de Venta o a un formulario de producto para poder escanear.");
    }
  }

  function buscarComando(transcripcion) {
    const texto = normalizar(transcripcion);
    for (const cmd of COMANDOS) {
      for (const frase of cmd.frases) {
        if (texto.includes(frase)) { return cmd; }
      }
    }
    return null;
  }

  // --------------------------------------------------------------------
  // Reconocimiento de voz — motor genérico reutilizable. `modoComandos`
  // controla si el resultado se interpreta como un comando de navegación
  // (true, el modo normal) o se entrega en crudo a quien esté escuchando
  // en ese momento (false, usado por el Dictado Guiado más abajo).
  // --------------------------------------------------------------------
  const reconocimiento = new SpeechRecognitionAPI();
  reconocimiento.lang = "es-PE";
  reconocimiento.continuous = false;
  reconocimiento.interimResults = false;
  reconocimiento.maxAlternatives = 3;

  let escuchando = false;
  let modoComandos = true;
  let callbackDictado = null;

  reconocimiento.onstart = function () {
    escuchando = true;
    actualizarBoton();
  };

  reconocimiento.onend = function () {
    escuchando = false;
    actualizarBoton();
  };

  reconocimiento.onerror = function (evento) {
    escuchando = false;
    actualizarBoton();
    if (!modoComandos && callbackDictado) {
      const cb = callbackDictado;
      callbackDictado = null;
      cb(null, evento.error);
      return;
    }
    if (evento.error === "no-speech") {
      avisarError("No escuché ninguna orden. Intenta de nuevo.");
    } else if (evento.error === "not-allowed" || evento.error === "service-not-allowed") {
      avisarError("Debes permitir el uso del micrófono en tu navegador para usar comandos de voz.");
    }
    // otros errores (network, aborted) se ignoran silenciosamente
  };

  reconocimiento.onresult = function (evento) {
    let mejorTranscripcion = evento.results[0][0].transcript;

    if (!modoComandos) {
      const cb = callbackDictado;
      callbackDictado = null;
      if (cb) cb(mejorTranscripcion, null);
      return;
    }

    const comando = buscarComando(mejorTranscripcion);
    if (comando) {
      comando.accion();
    } else {
      avisarError('No reconocí esa orden ("' + mejorTranscripcion + '"). Di "ayuda" para ver ejemplos.');
      hablar('No reconocí esa orden. Di ayuda para ver ejemplos.');
    }
  };

  /**
   * Escucha UNA respuesta hablada y la entrega en crudo (sin interpretar
   * comandos) a `callback(texto, error)`. Usado por el Dictado Guiado
   * para capturar la respuesta a cada pregunta del formulario.
   */
  function escucharRespuesta(callback) {
    modoComandos = false;
    callbackDictado = callback;
    try {
      reconocimiento.start();
    } catch (e) {
      modoComandos = true;
      callback(null, "start-failed");
    }
  }

  function iniciarEscucha() {
    if (escuchando) {
      reconocimiento.stop();
      return;
    }
    modoComandos = true;
    try {
      reconocimiento.start();
    } catch (e) {
      // Algunos navegadores lanzan error si start() se llama muy seguido; se ignora.
    }
  }

  // --------------------------------------------------------------------
  // Interfaz: botón flotante + modal de ayuda (inyectados por JS, sin
  // tocar ningún archivo PHP existente)
  // --------------------------------------------------------------------
  let btnVoz, badgeVoz;

  function crearBoton() {
    const contenedor = document.createElement("div");
    contenedor.id = "vozFlotante";
    contenedor.innerHTML =
      '<button type="button" id="vozBtn" title="Comandos de voz (clic y habla)" aria-label="Activar comandos de voz">' +
      '  <i class="bi bi-mic-fill"></i>' +
      '</button>' +
      '<button type="button" id="vozAyudaBtn" title="Ver comandos de voz disponibles" aria-label="Ayuda de comandos de voz">?</button>';
    document.body.appendChild(contenedor);

    btnVoz = document.getElementById("vozBtn");
    badgeVoz = document.getElementById("vozAyudaBtn");

    btnVoz.addEventListener("click", iniciarEscucha);
    badgeVoz.addEventListener("click", mostrarAyuda);
  }

  function actualizarBoton() {
    if (!btnVoz) return;
    btnVoz.classList.toggle("escuchando", escuchando);
    btnVoz.title = escuchando ? "Escuchando… (clic para cancelar)" : "Comandos de voz (clic y habla)";
  }

  function mostrarAyuda() {
    let modal = document.getElementById("modalAyudaVoz");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "modalAyudaVoz";
      modal.className = "modal fade";
      modal.tabIndex = -1;
      modal.innerHTML =
        '<div class="modal-dialog modal-dialog-centered modal-lg">' +
        '  <div class="modal-content">' +
        '    <div class="modal-header">' +
        '      <h5 class="modal-title"><i class="bi bi-mic-fill"></i> Comandos de Voz Disponibles</h5>' +
        '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
        '    </div>' +
        '    <div class="modal-body">' +
        '      <p class="text-muted small mb-3">Haz clic en el micrófono flotante y di, por ejemplo, cualquiera de estas frases:</p>' +
        '      <div class="voz-ayuda-grid">' +
        COMANDOS.map(function (c) {
          return '<div class="voz-ayuda-item"><i class="bi bi-chat-quote"></i> <strong>' + c.etiqueta + '</strong><span>"' + c.frases[0] + '"</span></div>';
        }).join("") +
        '      </div>' +
        '    </div>' +
        '    <div class="modal-footer">' +
        '      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>' +
        '    </div>' +
        '  </div>' +
        '</div>';
      document.body.appendChild(modal);
    }
    if (window.bootstrap && window.bootstrap.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
  }

  document.addEventListener("DOMContentLoaded", crearBoton);

  // ========================================================================
  // DICTADO GUIADO POR VOZ — llena formularios completos (Nuevo Cliente,
  // Nuevo Producto, etc.) preguntando un campo a la vez, en vez de intentar
  // adivinar 8 datos en una sola frase (poco confiable). Al final relee un
  // resumen y pide confirmación antes de guardar.
  //
  // Uso desde cualquier página (ver ejemplo real en clientes/nuevo.php y
  // productos/nuevo.php):
  //   ERPDictado.iniciar({
  //     titulo: "nuevo cliente",
  //     pasos: [
  //       { campo: "nombre", pregunta: "¿Cuál es el nombre del cliente?", tipo: "texto" },
  //       { campo: "stock", pregunta: "¿Cuánto stock tiene?", tipo: "numero" },
  //       { campo: "tipo_documento", pregunta: "¿DNI o RUC?", tipo: "opcion", opciones: ["DNI","RUC"] },
  //     ],
  //     onCompletar: function (respuestas) { /* respuestas.nombre, respuestas.stock, ... */ }
  //   });
  // ========================================================================
  const NUMEROS_TEXTO = {
    cero: 0, un: 1, uno: 1, una: 1, dos: 2, tres: 3, cuatro: 4, cinco: 5,
    seis: 6, siete: 7, ocho: 8, nueve: 9, diez: 10, once: 11, doce: 12,
    trece: 13, catorce: 14, quince: 15, veinte: 20, treinta: 30,
    cuarenta: 40, cincuenta: 50, sesenta: 60, setenta: 70, ochenta: 80, noventa: 90,
    cien: 100, ciento: 100, doscientos: 200, trescientos: 300, quinientos: 500,
    mil: 1000,
  };

  /** Convierte un texto hablado con números en palabras (español) a un valor numérico. Si ya viene en dígitos, los usa directo. */
  function textoANumero(texto) {
    const soloDigitos = texto.replace(/[^\d.]/g, "");
    if (soloDigitos !== "" && /\d/.test(soloDigitos)) { return parseFloat(soloDigitos); }

    const palabras = normalizar(texto).split(/\s+|\by\b/).filter(Boolean);
    let total = 0, parcial = 0;
    palabras.forEach(function (p) {
      if (NUMEROS_TEXTO[p] !== undefined) {
        const val = NUMEROS_TEXTO[p];
        if (val === 1000) { parcial = (parcial || 1) * 1000; total += parcial; parcial = 0; }
        else if (val >= 100) { parcial += val; }
        else { parcial += val; }
      }
    });
    return total + parcial;
  }

  function hacerPregunta(paso, intentos, resolver) {
    if (intentos >= 3) {
      hablar("No logré entenderte en ese campo, lo dejamos en blanco y lo puedes completar a mano.");
      resolver("");
      return;
    }
    hablar(paso.pregunta, function () {
      avisar("Escuchando: " + paso.pregunta);
      escucharRespuesta(function (texto, error) {
        if (!texto) {
          hablar("No te escuché bien, ¿puedes repetirlo?", function () {
            hacerPregunta(paso, intentos + 1, resolver);
          });
          return;
        }
        if (paso.tipo === "numero") {
          const n = textoANumero(texto);
          resolver(isNaN(n) ? "" : n);
        } else if (paso.tipo === "opcion") {
          const t = normalizar(texto);
          const lista = (paso.opciones || []).map(function (op) {
            return typeof op === "string" ? { valor: op, frases: [op] } : op;
          });
          const encontrada = lista.find(function (op) {
            return op.frases.some(function (f) { return t.includes(normalizar(f)); });
          });
          if (encontrada) { resolver(encontrada.valor); }
          else {
            hablar("No reconocí esa opción. " + paso.pregunta, function () {
              hacerPregunta(paso, intentos + 1, resolver);
            });
          }
        } else {
          // Capitaliza la primera letra para que se vea prolijo en el formulario.
          const limpio = texto.trim();
          resolver(limpio.charAt(0).toUpperCase() + limpio.slice(1));
        }
      });
    });
  }

  function ejecutarPasos(pasos, indice, respuestas, onCompletar) {
    if (indice >= pasos.length) {
      confirmarYCompletar(pasos, respuestas, onCompletar);
      return;
    }
    const paso = pasos[indice];
    hacerPregunta(paso, 0, function (valor) {
      respuestas[paso.campo] = valor;
      ejecutarPasos(pasos, indice + 1, respuestas, onCompletar);
    });
  }

  function confirmarYCompletar(pasos, respuestas, onCompletar, intentosConfirmacion) {
    intentosConfirmacion = intentosConfirmacion || 0;
    const resumen = pasos.map(function (p) { return p.pregunta.replace(/[¿?]/g, "") + ": " + respuestas[p.campo]; }).join(". ");
    hablar("Esto es lo que anoté: " + resumen + ". ¿Confirmo y guardo? Di sí o no.", function () {
      avisar("¿Confirmas los datos? Di sí o no.");
      escucharRespuesta(function (texto) {
        const t = normalizar(texto || "");
        if (t.includes("si") || t.includes("correcto") || t.includes("confirmo") || t.includes("dale") || t.includes("guarda")) {
          hablar("Listo, guardando.");
          onCompletar(respuestas);
        } else if (t.includes("no") || t.includes("cancela")) {
          hablar("Se canceló. No se guardó nada.");
          avisar("Dictado cancelado.");
        } else if (intentosConfirmacion < 2) {
          confirmarYCompletar(pasos, respuestas, onCompletar, intentosConfirmacion + 1);
        } else {
          hablar("No quedó claro, se canceló por seguridad.");
        }
      });
    });
  }

  window.ERPDictado = {
    iniciar: function (config) {
      avisar('Dictado por voz: "' + config.titulo + '" — responde cada pregunta después del tono.');
      hablar("Vamos a registrar un " + config.titulo + " por voz. Te haré unas preguntas, una por una.", function () {
        ejecutarPasos(config.pasos, 0, {}, config.onCompletar);
      });
    },
  };
})();
