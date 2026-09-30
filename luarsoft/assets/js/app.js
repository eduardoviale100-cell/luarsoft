/**
 * assets/js/app.js
 * ------------------------------------------------------------------
 * Lógica global de interfaz: sidebar plegable, submenús, overlay móvil
 * y helpers de notificaciones (toasts) usados en todos los módulos.
 * ------------------------------------------------------------------
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    initSidebarToggle();
    initSubmenus();
    initMobileOverlay();
    initFlashToast();
    initThemeToggle();
  });

  /* ---------------- Modo Claro / Oscuro ---------------- */
  function initThemeToggle() {
    const btn = document.getElementById("themeToggleBtn");
    if (!btn) return; // esta página no tiene topbar (ej. login)

    function actualizarTitulo() {
      const esOscuro = document.documentElement.getAttribute("data-bs-theme") === "dark";
      btn.title = esOscuro ? "Cambiar a modo claro" : "Cambiar a modo oscuro";
    }
    actualizarTitulo();

    btn.addEventListener("click", function () {
      const esOscuroActual = document.documentElement.getAttribute("data-bs-theme") === "dark";
      const nuevoTema = esOscuroActual ? "light" : "dark";

      if (nuevoTema === "dark") {
        document.documentElement.setAttribute("data-bs-theme", "dark");
      } else {
        document.documentElement.removeAttribute("data-bs-theme");
      }
      try { localStorage.setItem("luarsoft_tema", nuevoTema); } catch (e) { /* sin localStorage: el cambio solo dura esta carga de página */ }

      actualizarTitulo();
      // Avisa a cualquier gráfico (Chart.js) u otro componente en la
      // página actual que el tema cambió, para que ajuste sus propios
      // colores (los gráficos se dibujan en <canvas>, fuera del alcance
      // de las variables CSS).
      document.dispatchEvent(new CustomEvent("erp:theme-changed", { detail: { tema: nuevoTema } }));
    });
  }

  /* ---------------- Sidebar plegable (desktop) ---------------- */
  function initSidebarToggle() {
    const btn = document.getElementById("toggleSidebar");
    if (!btn) return;
    const stored = localStorage.getItem("erp_sidebar_collapsed") === "1";
    if (stored) document.body.classList.add("sidebar-collapsed");

    btn.addEventListener("click", function () {
      if (window.innerWidth <= 992) {
        document.body.classList.toggle("sidebar-mobile-open");
        document.getElementById("sidebarOverlay")?.classList.toggle("show");
      } else {
        document.body.classList.toggle("sidebar-collapsed");
        localStorage.setItem(
          "erp_sidebar_collapsed",
          document.body.classList.contains("sidebar-collapsed") ? "1" : "0"
        );
      }
    });
  }

  function initMobileOverlay() {
    const overlay = document.getElementById("sidebarOverlay");
    if (!overlay) return;
    overlay.addEventListener("click", function () {
      document.body.classList.remove("sidebar-mobile-open");
      overlay.classList.remove("show");
    });
  }

  /* ---------------- Submenús del sidebar ---------------- */
  function initSubmenus() {
    document.querySelectorAll("[data-toggle-submenu]").forEach(function (trigger) {
      trigger.addEventListener("click", function (e) {
        e.preventDefault();
        const targetId = trigger.getAttribute("data-toggle-submenu");
        const submenu = document.getElementById(targetId);
        if (!submenu) return;
        const willOpen = !submenu.classList.contains("show");

        // Cierra otros submenús abiertos (acordeón)
        document.querySelectorAll(".nav-submenu.show").forEach(function (el) {
          if (el !== submenu) {
            el.classList.remove("show");
            const otherTrigger = document.querySelector('[data-toggle-submenu="' + el.id + '"]');
            otherTrigger && otherTrigger.classList.remove("open");
          }
        });

        submenu.classList.toggle("show", willOpen);
        trigger.classList.toggle("open", willOpen);
      });
    });
  }

  /* ---------------- Resalta el ítem de menú activo ---------------- */
  /* Nota: el resaltado del menú activo ahora se calcula 100% en PHP
     (ver includes/sidebar.php), comparando la ruta completa del script
     actual — no solo el nombre del archivo. Esto evita que páginas con
     el mismo nombre en distintos módulos (ej. "listado.php" en
     clientes, productos, técnicos y órdenes) se marquen todas como
     activas a la vez. */

  /* ---------------- Toast de mensaje flash (post-redirect) ---------------- */
  function initFlashToast() {
    const el = document.getElementById("flash-data");
    if (!el) return;
    const tipo = el.getAttribute("data-tipo");
    const mensaje = el.getAttribute("data-mensaje");
    if (mensaje) {
      ERP.toast(tipo || "success", mensaje);
    }
  }

  /* ---------------- API pública ERP.* ---------------- */
  window.ERP = {
    /**
     * Abre el modal de documentos (boleta/factura/ticket/recibo) cargando
     * `url` dentro de un iframe, sin salir de la pestaña ni recargar la
     * página. `volverUrl` es la ruta a la que apuntará el botón
     * "Pantalla completa" para volver ("Volver al listado").
     */
    verDocumento: function (url, titulo, volverUrl) {
      const modalEl = document.getElementById('modalDocumento');
      // Sello de tiempo único: evita que un proxy/CDN del hosting o el
      // navegador reusen una respuesta anterior cacheada para esta URL.
      const separador = url.includes('?') ? '&' : '?';
      const urlSinCache = url + separador + '_ts=' + Date.now();
      if (!modalEl) { window.open(urlSinCache, '_blank'); return; }

      document.getElementById('modalDocumentoTitulo').textContent = titulo || 'Documento';
      document.getElementById('modalDocumentoFrame').src = urlSinCache;

      const base = window.ERP_BASE_URL || '/';
      const volver = volverUrl || window.location.pathname;
      const pantallaCompletaUrl = base + 'includes/visor.php?src=' + encodeURIComponent(urlSinCache.replace(base, '')) +
        '&titulo=' + encodeURIComponent(titulo || 'Documento') +
        '&volver=' + encodeURIComponent(volver.replace(base, ''));
      document.getElementById('modalDocumentoPantallaCompleta').href = pantallaCompletaUrl;

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    },

    /** Envía la orden de impresión al iframe del modal de documentos activo. */
    imprimirDocumentoModal: function () {
      const frame = document.getElementById('modalDocumentoFrame');
      try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
      } catch (e) {
        ERP.toast('warning', 'No se pudo iniciar la impresión automáticamente. Usa Ctrl+P dentro de la vista previa.');
      }
    },

    /** true si el Modo Oscuro está activo en este momento. */
    esTemaOscuro: function () {
      return document.documentElement.getAttribute("data-bs-theme") === "dark";
    },

    /** Muestra un toast moderno (usa SweetAlert2 si está disponible). */
    toast: function (tipo, mensaje) {
      const iconMap = { success: "success", error: "error", warning: "warning", info: "info" };
      if (window.Swal) {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: iconMap[tipo] || "success",
          title: mensaje,
          showConfirmButton: false,
          timer: 3200,
          timerProgressBar: true,
          background: ERP.esTemaOscuro() ? "#1A2620" : "#ffffff",
          color: ERP.esTemaOscuro() ? "#F1F7F4" : "#131C16",
        });
      } else {
        alert(mensaje);
      }
    },

    /** Diálogo de confirmación moderno para eliminar registros. Devuelve una Promise<boolean>. */
    confirmarEliminar: function (mensaje, opciones) {
      mensaje = mensaje || "Esta acción no se puede deshacer.";
      opciones = opciones || {};
      const titulo = opciones.titulo || "¿Eliminar registro?";
      const textoConfirmar = opciones.textoConfirmar || "Sí, eliminar";
      if (window.Swal) {
        return Swal.fire({
          title: titulo,
          text: mensaje,
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: textoConfirmar,
          cancelButtonText: "Cancelar",
          confirmButtonColor: "#C81E38",
          cancelButtonColor: "#5b6579",
          background: ERP.esTemaOscuro() ? "#1A2620" : "#ffffff",
          color: ERP.esTemaOscuro() ? "#F1F7F4" : "#131C16",
        }).then((r) => r.isConfirmed);
      }
      return Promise.resolve(window.confirm(mensaje));
    },

    /** Intercepta enlaces con data-confirm-delete="mensaje" para pedir confirmación antes de navegar. */
    bindDeleteLinks: function (selector) {
      document.querySelectorAll(selector || "[data-confirm-delete]").forEach(function (link) {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          const href = link.getAttribute("href");
          const msg = link.getAttribute("data-confirm-delete");
          ERP.confirmarEliminar(msg).then(function (ok) {
            if (ok) window.location.href = href;
          });
        });
      });
    },
    /**
     * Reemplaza cualquier <img> que apunte a uploads/ y no haya podido
     * cargar (archivo físico ausente en este equipo) por un aviso
     * claro, en vez de dejar el ícono de "imagen rota" del navegador.
     * Esto pasa típicamente al copiar solo la base de datos a otra
     * computadora sin copiar también la carpeta uploads/: el nombre del
     * archivo queda registrado, pero el archivo en sí no existe en el
     * disco de ese equipo. Cubre las imágenes ya presentes al cargar la
     * página (listados, fichas); las que se cargan dinámicamente por
     * JS (ej. el visor de evidencia en video) manejan su propio caso
     * puntual donde se asignan, para no tener que observar todo el DOM.
     */
    manejarMediaFaltante: function () {
      document.querySelectorAll('img[src*="/uploads/"]').forEach(function (img) {
        if (img.dataset.mediaWatch) return;
        img.dataset.mediaWatch = "1";
        img.addEventListener("error", function () {
          const marcador = document.createElement("span");
          marcador.className = "media-faltante";
          marcador.style.cssText = img.getAttribute("style") || "";
          marcador.title = "Esta imagen no está disponible en este equipo (revisa que la carpeta uploads/ se haya copiado junto con el sistema)";
          marcador.innerHTML = '<i class="bi bi-image"></i>';
          img.replaceWith(marcador);
        });
      });
    },
  };

  document.addEventListener("DOMContentLoaded", function () {
    ERP.bindDeleteLinks();
    ERP.manejarMediaFaltante();
  });
})();
