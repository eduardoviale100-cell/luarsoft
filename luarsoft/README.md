# LuarSoft · Eros Tecnología — Sistema Refactorizado v3.0

Sistema de ventas (POS), clientes, productos, técnicos, boletas/facturas,
órdenes de reparación (con ficha técnica para laptops/PC) y reportes,
con una interfaz visual premium de nivel SaaS bajo la identidad de marca
**Eros** (marino corporativo `#0F1D38` + acento azul `#3B6FE0`, con el
rojo de marca `#E0102B` reservado para el logo y acciones destructivas).

---

## 🆕 Novedades de la versión 5.0 (Código de Barras, Dictado por Voz, correcciones)

- **Código de Barras (nuevo, en Productos):** cada producto puede tener un
  código de barras (además de su código/SKU interno de siempre), con 3
  formas de conseguirlo — combinadas, no excluyentes:
  1. **Escanear con la cámara** (celular o laptop) — botón nuevo en
     Productos y en el **POS**, usa la librería ZXing auto-alojada.
  2. **Lector físico USB** — funciona automático: el POS tiene un campo
     dedicado de escaneo; un lector USB "escribe" el código como si fuera
     un teclado y presiona Enter, y el sistema lo busca y agrega al
     carrito solo (sumando cantidad si el producto ya estaba en la venta).
  3. **Generar código interno** — para productos sin código de fábrica
     (ej. servicios): un botón genera un código EAN-13 único válido.
  - Nueva ficha imprimible de etiqueta con el código de barras dibujado
    (librería JsBarcode), para pegar en el producto o el estante.
  - Comando de voz nuevo: *"escanear código"* abre la cámara automáticamente
    si estás en el POS o en un formulario de Producto.
- **Dictado Guiado por Voz (ampliación importante de Comandos de Voz):**
  ahora se puede **crear un Cliente o un Producto completo hablando**, con
  el sistema preguntando un dato a la vez ("¿Cuál es el nombre del
  cliente?", "¿Cuál es su teléfono?"...), leyendo un resumen antes de
  guardar y pidiendo tu confirmación ("¿Confirmo y guardo? Di sí o no").
  Actívalo diciendo *"nuevo cliente por voz"* o *"nuevo producto por voz"*.
  - **Alcance honesto:** se implementó para Clientes y Productos como los
    casos más solicitados; el mismo motor (`ERPDictado`) es reutilizable
    y queda listo para conectarse a Técnicos u Órdenes en una próxima
    ronda — no se afirma que "todo" el sistema ya se controle por voz,
    eso sería una simplificación poco realista de lo que se entregó.
- **Corrección: el comando de voz cortaba la frase antes de terminar de
  hablar.** Antes navegaba a un tiempo fijo (450 ms) sin importar cuánto
  durara el audio; ahora espera al evento real de "fin de habla" del
  navegador antes de redirigir (con un límite de seguridad por si el
  navegador no lo dispara).
- **Corrección: modo oscuro con SweetAlert2.** Los toasts de notificación
  (guardado, error, confirmaciones) se quedaban siempre con fondo blanco
  sin importar el tema activo, ya que SweetAlert2 no sigue automáticamente
  el modo oscuro de Bootstrap. Ahora toman los colores correctos según el
  tema activo en cada momento.

Requiere ejecutar `database/migracion_v2.9_codigo_barras.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.9 (Modo Oscuro + Dashboard pulido)

- **Modo Claro / Oscuro:** nuevo botón (ícono de luna/sol) en el topbar,
  disponible en todo el panel (mismo alcance que los comandos de voz: se
  agregó en el punto compartido por todas las pantallas). Usa el sistema
  de color nativo de Bootstrap 5.3+ (`data-bs-theme`), así que formularios,
  modales y tablas cambian de tema automáticamente además de las tarjetas,
  el sidebar y los KPIs propios de LuarSoft. La preferencia se recuerda
  (localStorage) y se aplica antes de pintar la página, sin parpadeos. Los
  2 gráficos del Panel Principal (Top Productos, Top Clientes) también
  cambian sus colores de cuadrícula y texto al alternar el tema, ya que
  se dibujan en `<canvas>` y no siguen las variables CSS por sí solos.
- **Tarjetas del Panel Principal con sombra suave de Bootstrap 5:** las
  13 tarjetas del dashboard (accesos rápidos, alertas, KPIs y gráficos)
  ahora usan la utilidad nativa `.shadow-sm` de Bootstrap, más una ligera
  elevación al pasar el mouse, para un look más moderno y pulido.

---

## 🆕 Novedades de la versión 4.8 (Comandos de Voz, Bootstrap moderno, Media faltante)

- **Accesibilidad por Comando de Voz — en todo el sistema:** nuevo botón
  flotante de micrófono (abajo a la derecha), visible en **las 34
  pantallas interactivas** del panel (Panel Principal, POS, Clientes,
  Productos, Galería, Técnicos, Órdenes, Ventas, Reportes, Usuarios,
  Compras — todo lo que no sea un documento para imprimir). Se agregó en
  un único punto compartido por todas las vistas (`includes/layout_bottom.php`),
  así que no fue necesario tocar cada módulo por separado.
  - Un clic activa el reconocimiento de voz (en español) y ejecuta comandos
    como *"punto de venta"*, *"nuevo cliente"*, *"galería de productos"*,
    *"kardex"*, *"cerrar sesión"*, etc. — con confirmación visual (toast)
    y hablada antes de navegar. El botón **"?"** junto al micrófono (o
    decir *"ayuda"*) muestra la lista completa de comandos disponibles.
  - **Limitación honesta:** el reconocimiento de voz del navegador solo
    funciona en navegadores basados en Chromium (Chrome, Edge, Opera,
    Brave). Firefox y Safari no lo soportan de forma nativa — en esos
    casos el botón de micrófono simplemente no aparece, en vez de
    mostrar algo que no va a funcionar.
- **Bootstrap actualizado a la versión estable más reciente (5.3.8) y
  Bootstrap Icons a la 1.13.1**, ambos auto-alojados dentro del propio
  proyecto (ya no dependen de un CDN externo, así que también funcionan
  sin conexión a internet). Esto corrige un problema real de fondo: el
  sistema traía el CSS de Bootstrap **v4.1.3** (2018) mezclado con el
  JavaScript de Bootstrap **v5.3.3** cargado desde un CDN — dos versiones
  distintas del mismo framework a la vez, lo que podía causar
  inconsistencias visuales sutiles en formularios, modales y espaciados.
  Se verificaron los 104 íconos y las clases modernas (`form-select`,
  `gap-2`, `btn-close`, etc.) que ya usaba el sistema: todos existen en
  la versión nueva, cero íconos rotos.
- **Manejo de imágenes/videos faltantes:** si el archivo de una imagen o
  video no existe físicamente en el equipo (ver nota importante más
  abajo), ahora se muestra un aviso claro en vez del ícono de "imagen
  rota" del navegador.

### ⚠️ Nota importante y honesta sobre imágenes/videos en otro equipo

Las imágenes y videos que subes (Productos, Clientes, Técnicos, Usuarios,
Órdenes) se guardan como **archivos físicos** en la carpeta `uploads/`
del servidor — la base de datos solo guarda el *nombre* del archivo, no
el archivo en sí. Esto significa que:

- Si copias el sistema a otra computadora usando **solo el archivo
  `.sql`** (por ejemplo, exportando e importando la base de datos en
  phpMyAdmin), la base de datos en la computadora nueva va a seguir
  diciendo "esta orden tiene la foto `equipo_123.jpg`", pero **el archivo
  `equipo_123.jpg` no existe** en esa computadora — porque nunca se copió.
- **La solución real:** cuando muevas el sistema a otra computadora,
  copia **la carpeta del proyecto completa**, incluyendo `uploads/` —
  no solo la base de datos. Con XAMPP, eso significa copiar toda la
  carpeta `htdocs/luarsoft/` (con `uploads/` adentro) de una computadora
  a la otra, además de importar el `.sql`.
- Esto no es algo que se pueda arreglar solo con código: son archivos
  que físicamente tienen que existir en el disco de cada computadora
  donde quieras verlos. Lo que sí se corrigió es que, si falta un
  archivo, el sistema lo indique con un aviso claro en vez de un ícono
  roto.

---

## 🆕 Novedades de la versión 4.7 (Galería de Productos)

- **Galería de Productos (submódulo nuevo):** `Productos > Galería de
  Productos`. Vista tipo catálogo en cuadrícula, pensada para mostrarle al
  cliente — no solo para gestión interna: imagen, nombre, precio, filtro
  por categoría (chips) y buscador instantáneo, todo sin recargar la
  página. Incluye "cintas" visuales de Agotado / Pocas unidades.
  - Al hacer clic en un producto se abre una ficha con **dos pestañas**,
    tal como se pidió: **Características** (descripción corta y vendible)
    y **Especificaciones Técnicas** (detalle técnico denso) — dos campos
    nuevos y opcionales en Productos (Nuevo/Editar).
  - Botón **Compartir por WhatsApp**: arma el mensaje con nombre, precio,
    características y el enlace a la imagen, y abre el selector de
    contactos de WhatsApp (no envía a un número fijo, para poder elegir a
    quién). *Nota:* por la misma limitación de la API de WhatsApp ya
    documentada en Evidencia de Video, no se puede adjuntar la imagen de
    forma automática — el enlace directo cumple esa función.
- **Corrección de ícono:** el ícono de "Punto de Venta (POS)" no se veía
  en el menú lateral ni en la Matriz de Permisos de Usuarios porque el
  nombre de ícono usado (`bi-cash-register`) no existe en Bootstrap
  Icons. Se reemplazó por `bi-cart-check-fill`, un ícono válido.

Requiere ejecutar `database/migracion_v2.8_galeria_productos.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.6 (Relaciones de Base de Datos)

A raíz de una consulta directa sobre el diseñador de phpMyAdmin: el
esquema original tenía muy pocas relaciones (`FOREIGN KEY`) reales entre
tablas — la mayoría de los vínculos (ej. qué producto se vendió, qué
producto se compró) dependían solo de que el código PHP los mantuviera
consistentes, sin que la base de datos los protegiera.

- **Nuevas relaciones agregadas** (verificadas contra los datos reales
  antes de aplicarlas, para no romper nada):
  - `detalle_venta.producto_codigo` → `productos.codigo`
  - `compras.producto_codigo` → `productos.codigo`
  - `orden_repuestos.producto_codigo` → `productos.codigo`
- **Efecto real y esperado:** ya no se puede eliminar un producto que
  tenga historial de ventas, compras o que esté usado como repuesto en
  una orden — antes sí se podía, y ese historial quedaba con un código de
  producto "huérfano" sin que nadie se enterara. Ahora, si lo intentas,
  el sistema te avisa con un mensaje claro en vez de dejarlo pasar en
  silencio o mostrar un error críptico de MySQL.
- **Lo que NO se relacionó (a propósito, y por qué):** `ordenes.cliente`
  y `ventas.documento_cliente` guardan el nombre/documento del cliente
  como texto libre, no como un `id_cliente`. Esto es intencional en el
  diseño actual — permite vender o registrar una orden a alguien que no
  está en tu lista de Clientes (ej. "Público General"). Convertirlo en
  una relación real es un cambio más grande (nueva columna, decidir qué
  hacer con el historial que no coincide con ningún cliente registrado,
  actualizar POS y Órdenes) que no se aplicó aquí para no arriesgar esa
  flexibilidad sin que se decida explícitamente.

Requiere ejecutar `database/migracion_v2.7_relaciones_productos.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.5 (Fotos en Técnicos, tamaños e integridad)

- **Técnicos** se suma a los módulos con imagen de referencia (foto de
  perfil): en Nuevo/Editar Técnico, en el listado (miniatura) y en la ficha
  impresa — igual que Productos, Clientes, Órdenes y Usuarios.
- **Miniaturas más grandes en todo el sistema**, de forma consistente:
  listados 34-38px → **44px**; vistas previas al editar 90px → **110px**;
  fichas impresas 70px → **85px**; avatar de sesión (topbar) 32px → **38px**.
- **Limpieza de archivos huérfanos:** eliminar un producto, cliente,
  técnico, usuario u orden ahora también borra su imagen (y, en el caso de
  órdenes, su video de evidencia) del servidor — antes el registro se
  borraba de la base de datos pero el archivo se quedaba ocupando espacio.
- **Verificación integral:** se revisaron los 71 archivos PHP del sistema
  (balance de HTML, consistencia de cada `bind_param`, columnas vs. valores
  en el `.sql` completo) y se corrigió una inconsistencia real encontrada:
  al esquema completo `luarsoft.sql` le faltaba la columna `foto_equipo` en
  la tabla `ordenes` (ya la traía la migración v2.5, pero no el `.sql`
  completo) — una instalación nueva desde cero habría fallado al guardar
  la foto de un equipo. Ya corregido.

Requiere ejecutar `database/migracion_v2.6_foto_usuarios_tecnicos.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.4 (Imágenes, Compras, Kardex y nuevos Reportes)

Ronda de mejoras solicitada directamente por el equipo técnico, también
**estrictamente aditiva**: ningún módulo, vista o lógica de negocio
existente fue eliminado o alterado.

- **Imágenes de referencia:** ahora se puede subir una imagen/foto en
  **Productos** (ficha, listado con miniatura), **Clientes** (ficha,
  listado con avatar, ficha impresa) y **Órdenes de Reparación** (foto del
  equipo recibido, listado con miniatura). Formatos JPG/PNG/WEBP, máximo
  5 MB, con validación y limpieza automática del archivo anterior al
  reemplazar.
- **Registro de Compras (módulo nuevo):** `Productos > Registrar Compra` /
  `Registro de Compras`. Cada compra a un proveedor aumenta el stock del
  producto automáticamente (y opcionalmente actualiza su precio de
  compra). Eliminar una compra revierte el stock que había sumado.
- **Kardex de Inventario (reporte nuevo):** historial cronológico de
  entradas (compras) y salidas (ventas) de un producto, con saldo
  acumulado.
- **Productos Sin Stock:** acceso directo en Reportes al listado de
  productos agotados (ya existía el filtro; ahora tiene su propio enlace).
- **Servicios Realizados en el Mes (reporte nuevo):** listado de órdenes
  de reparación con movimiento (ingreso o entrega) en un mes, con totales.
- **Flujo de Ingresos (reporte nuevo):** ingresos (ventas) vs egresos
  (compras) día a día, con saldo neto acumulado del período.

Requiere ejecutar `database/migracion_v2.5_imagenes_compras_kardex.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.3 (Login, Roles y Permisos por Módulo)

Nueva ronda de mejoras, también **estrictamente aditiva**: ningún módulo,
vista o lógica de negocio existente (POS, inventario, clientes, técnicos,
órdenes de reparación, envío de WhatsApp, reportes, etc.) fue eliminado o
alterado. Todo lo que ya funcionaba sigue funcionando exactamente igual
para la cuenta Administrador.

- **Login rediseñado (pantalla dividida):** el lado izquierdo usa la foto
  real de la fachada de Multiservicios Eros como fondo, con un overlay
  degradado verde oscuro. El lado derecho tiene el formulario de acceso
  limpio, con el logo, campos Usuario/Contraseña, botón "Iniciar Sesión"
  en verde corporativo, y un enlace "¿Olvidaste tu contraseña?" que abre
  un modal informativo indicando contactar al Administrador (sin exponer
  ningún flujo de recuperación automática, por seguridad).
- **Roles y Matriz de Permisos:** en `Sistema > Usuarios` (Nuevo/Editar)
  ahora se elige un **Rol** (`Administrador` o `Cajero / Usuario`) y, para
  el rol Cajero, una matriz de checkboxes con los 9 módulos del sistema
  (Dashboard, POS, Clientes, Productos, Técnicos, Órdenes, Ventas,
  Reportes, Usuarios/Sistema). Un Administrador siempre tiene acceso
  total automáticamente.
- **Aplicación real de los permisos:**
  - El **Menú Lateral** oculta automáticamente cualquier módulo o
    submódulo al que el usuario en sesión no tenga acceso.
  - Si un Cajero escribe la URL de un módulo no autorizado directamente
    en el navegador, `includes/auth.php` lo detecta y lo redirige a la
    primera sección a la que sí tiene acceso (o al login si no tiene
    ninguna), con un aviso.
- **Dashboard diferenciado por Rol:**
  - **Administrador:** mantiene el panel completo actual (Top 5
    Productos, Top 5 Mejores Clientes, alertas de stock bajo, órdenes sin
    movimiento y tarjetas KPI).
  - **Cajero / Usuario:** panel operativo simple, sin ninguna métrica
    financiera ni de mejores clientes. Solo botones de acceso rápido a
    las tareas que sí tiene permitidas (Nueva Venta, Registrar Cliente,
    Crear Orden, Consultar Estado de Equipo).

Requiere ejecutar `database/migracion_v2.4_roles_permisos.sql` en
instalaciones existentes (ver sección 1). El usuario `admin` conserva
acceso total automáticamente tras la migración (rol Administrador por
defecto) — cero pérdida de acceso para la cuenta existente.

---

## 🆕 Novedades de la versión 4.2 (mejoras para entorno de pruebas)

Nueva ronda de mejoras, también **estrictamente aditiva**: nada de lo que
ya funcionaba fue modificado o eliminado.

- **Gestión de usuarios desde la interfaz:** nuevo módulo `Sistema >
  Usuarios` (crear, editar, cambiar contraseña, eliminar). Ya no hace
  falta usar phpMyAdmin para administrar quién puede iniciar sesión.
  Protecciones básicas: no se puede eliminar el último usuario del
  sistema ni la cuenta con la que tienes la sesión abierta.
- **Método de pago en las ventas:** el POS ahora permite elegir Efectivo,
  Tarjeta, Yape/Plin o Transferencia. Se guarda con la venta, se muestra
  en el comprobante impreso y en los listados de boletas/facturas, y es
  editable desde `Editar Boleta` / `Editar Factura`.
- **Cotización de reparaciones:** las órdenes ahora tienen un campo
  opcional "Monto Estimado". Desde `Órdenes > Editar` o el listado se
  puede imprimir una **Cotización** formal (con condiciones y espacio de
  firma) antes de aprobar el trabajo.
- **Exportar reportes a Excel/CSV:** los 5 submódulos de Reportes tienen
  un botón "Exportar a Excel" que descarga un CSV (se abre directo en
  Excel) respetando los filtros aplicados en pantalla.
- **Alertas en el Panel Principal:** al entrar al dashboard se muestran
  avisos de productos con stock bajo y de órdenes de reparación sin
  movimiento hace más de 15 días, con acceso directo al reporte/listado
  correspondiente.
- **Redes sociales en el pie de página:** iconos de Instagram, Facebook,
  WhatsApp, TikTok y YouTube. *Nota:* los enlaces de Instagram, Facebook
  y TikTok están como marcador de posición (`#`) — reemplázalos por las
  URLs reales de Eros Tecnología cuando las tengas; el de WhatsApp ya usa
  el número de contacto que figura en los documentos impresos del sistema.

Requiere ejecutar `database/migracion_v2.3_pagos_cotizacion.sql` en
instalaciones existentes (ver sección 1).

---

## 🆕 Novedades de la versión 4.1 (correcciones y mejoras puntuales)

Esta actualización es **estrictamente aditiva**: no se tocó ninguna otra
vista, módulo, tabla ni flujo de trabajo existente. Solo se corrigieron 2
errores puntuales y se agregaron 2 mejoras específicas.

**Bugs corregidos:**
- **POS · Búsqueda por DNI en Factura:** al buscar un DNI (8 dígitos)
  estando en la pestaña **Factura**, el sistema ya no salta a la pestaña
  **Boleta**. Ahora se conserva la pestaña Factura y el DNI se carga como
  dato opcional del cliente (`modules/ventas/pos.php`). La búsqueda por RUC
  sigue funcionando exactamente igual que antes.
- **Técnicos · Impresión individual:** el botón de imprimir de cada técnico
  en `Técnicos > Lista de Técnicos` generaba siempre un reporte con **todos**
  los técnicos. Ahora envía el `id_tecnico` correcto y abre únicamente la
  ficha de ese técnico (`modules/tecnicos/imprimir.php`, nuevo archivo).

**Mejoras agregadas:**
- **Evidencia en Video y WhatsApp (Órdenes de Reparación):** nueva acción
  "Ver / Cargar Evidencia" en `Órdenes > Lista de Órdenes`. Abre un modal con
  reproductor de video (subida directa del video de prueba), ficha resumen
  (cliente, equipo/modelo, falla reportada, solución aplicada) y un botón de
  WhatsApp que arma automáticamente el mensaje al cliente. *Nota honesta:* la
  API pública de WhatsApp (`api.whatsapp.com/send`) solo admite pre-rellenar
  **texto**, no adjuntar archivos de forma automática; por eso el mensaje
  incluye el enlace directo al video para que el cliente lo abra con un clic.
  Requiere ejecutar `database/migracion_v2.2_evidencia_video.sql` en
  instalaciones existentes (ver sección 1).
- **Impresión individual en Reportes:** los 5 submódulos de Reportes
  (Lo más vendido, Mejores clientes, Reporte de clientes, Reporte de
  productos, Reporte general de ventas) ahora tienen una acción por fila
  para imprimir/ver en PDF la ficha de ese registro puntual, sin necesidad
  de imprimir el listado completo (`modules/reportes/imprimir_individual.php`,
  nuevo archivo; el Reporte de Ventas reutiliza el comprobante ya existente).

---

## 🆕 Novedades de la versión 4.0

### Paleta de colores verde ambiental
- Se reemplazó la paleta marino/azul de la v3 por tonos **verdes profesionales**
  con degradados suaves hacia tonos claros/blancos, aplicados en el sidebar,
  botones principales, tarjetas KPI y encabezados de tarjetas.
- El **azul y el rojo de la marca Eros se mantienen como acentos secundarios**
  (botones de información, badges de facturas, acciones destructivas), tal
  como pide la identidad del logo.

### Módulo de Reportes completo
Se agregaron 3 reportes nuevos (antes solo existían "Lo Más Vendido" y
"Mejores Clientes"):
- **Reporte de Clientes** (`modules/reportes/clientes.php`): historial de
  compras por cliente, filtrable por rango de fechas y por texto de búsqueda.
- **Reporte de Productos** (`modules/reportes/productos.php`): filtrable por
  categoría y nivel de stock (normal / bajo / agotado), con **valorización
  de inventario** (valor al costo y valor de venta, por producto y total).
- **Reporte General de Ventas** (`modules/reportes/ventas.php`): boletas y
  facturas emitidas unificadas, filtrable por tipo de documento y periodo,
  con totales resumidos.

### Mejoras del Punto de Venta (POS)
- **Autocompletado con teclado**: en el buscador de productos ahora puedes
  navegar los resultados con las flechas ↑/↓, y confirmar la selección con
  `Tab` o `Enter` — se completa automáticamente el precio, importe y stock
  visible, quedando el cursor listo en el campo de cantidad.
- **Persistencia temporal del carrito**: si sales del POS hacia otra pantalla
  (por ejemplo, a revisar un producto) y regresas, el cliente y los productos
  que ya habías agregado siguen ahí — se guardan automáticamente en el
  navegador (`localStorage`) hasta que registras la venta o presionas el
  nuevo botón **"Cancelar / Limpiar Venta"**.


### Rediseño visual completo
- Paleta corporativa sobria (marinos/grises) en vez del rojo saturado de la v2.
- **Iconografía 100% vectorial** (Bootstrap Icons): se eliminaron todos los
  emojis del sistema.
- **Bug del sidebar corregido de raíz**: el estado "activo" ahora se calcula
  en PHP comparando la URL real de cada página (`includes/sidebar.php`), no
  solo con CSS. Solo el enlace exacto recibe el marcador (borde lateral +
  color de acento); el grupo padre solo tiñe su ícono, sin bloques rojos en
  cascada.
- Pie de página corporativo: `© 2026 Eros Tecnología · LuarSoft.` +
  estado del servidor.

### Documentos sin salir de la pestaña
- Boletas, facturas, tickets, fichas de cliente, constancias de recepción y
  tickets de entrega de órdenes ahora se abren en un **modal de vista previa**
  (`includes/document_modal.php` + `ERP.verDocumento()` en `app.js`), con
  botones Imprimir / Descargar PDF / Cerrar — sin pestañas nuevas ni recargas.
- Alternativa "Pantalla completa" (`includes/visor.php`): abre el documento
  en la misma pestaña conservando el Header/Sidebar, con una flecha
  "← Volver al listado" para regresar con un clic.
- *Nota honesta:* "Descargar PDF" usa el diálogo de impresión nativo del
  navegador (Guardar como PDF), ya que el sistema no incluye una librería de
  generación de PDF en el servidor.

### Servicio técnico de PC y Laptops (Órdenes de Reparación)
- **Ficha técnica de ingreso ampliada**: tipo de equipo (Laptop/PC/AIO/
  Impresora), marca, modelo, N° de serie, contraseña del sistema/BIOS
  (opcional), accesorios dejados y observaciones del estado físico.
- **Flujo de estados visual** (línea de tiempo): Ingresado → En Diagnóstico
  → Esperando Aprobación de Presupuesto → En Reparación/Cambio de Pieza →
  Listo para Entrega → Entregado. Los estados antiguos ("En espera", "En
  reparación", "Reparado") se siguen reconociendo en órdenes ya existentes.
- **Constancia de Recepción de Equipo** (documento nuevo, `modules/ordenes/recibo.php`):
  falla reportada, estado físico, accesorios y línea de firma del cliente.
- **Asignación de repuestos del inventario a la orden**: descuenta el stock
  automáticamente (transaccional, igual que el POS) y se puede revertir
  quitando el repuesto de la orden.
- **Garantía del servicio** (en días), visible en el Ticket de Entrega final
  junto con el detalle de repuestos utilizados y su total.

---

## 1. Instalación / Actualización

### Instalación nueva
Sigue igual que en la v2: importa `database/luarsoft.sql` completo (ya
incluye todos los campos nuevos) y ajusta `config/conexion.php`.

### Si ya tienes el sistema instalado (v2 → v3)
**No necesitas reimportar toda la base de datos.** Solo ejecuta la migración
no-destructiva en phpMyAdmin → tu base `luarsoft` → pestaña SQL:

```
database/migracion_v2.1_orden_tecnica.sql
database/migracion_v2.2_evidencia_video.sql
database/migracion_v2.3_pagos_cotizacion.sql
database/migracion_v2.4_roles_permisos.sql
database/migracion_v2.5_imagenes_compras_kardex.sql
database/migracion_v2.6_foto_usuarios_tecnicos.sql
database/migracion_v2.7_relaciones_productos.sql
database/migracion_v2.8_galeria_productos.sql
database/migracion_v2.9_codigo_barras.sql
```

Estos scripts únicamente **agregan** columnas nuevas o relaciones sobre
datos que ya las cumplen (la v2.1 agrega los campos de ficha técnica y la
tabla `orden_repuestos`; la v2.2 agrega `telefono_cliente`,
`solucion_aplicada` y `evidencia_video` a `ordenes` para el módulo de
Evidencia en Video; la v2.3 agrega `metodo_pago` a `ventas` y
`monto_estimado` a `ordenes`; la v2.4 agrega `rol` y `permisos` a `usuarios`
para la Matriz de Permisos; la v2.5 agrega imágenes de referencia a
`productos`, `clientes` y `ordenes`, y crea la tabla `compras` para el
Registro de Compras y el Kardex; la v2.6 agrega `foto` a `usuarios` y
`tecnicos`; la v2.7 agrega las relaciones `producto_codigo` → `productos`
en `detalle_venta`, `compras` y `orden_repuestos`; la v2.8 agrega
`caracteristicas` y `especificaciones_tecnicas` a `productos` para la
Galería de Productos; la v2.9 agrega `codigo_barras` a `productos`).
Ninguno borra ni modifica ninguna tabla, columna o dato que ya tengas.

Luego reemplaza los archivos de código por los de este ZIP (puedes
sobrescribir toda la carpeta del sistema; `config/conexion.php` con tu
`BASE_URL` ya viene configurado igual que en tu instalación actual).



### Credenciales de acceso por defecto
```
Usuario:    admin
Contraseña: admin123
```
*(La tabla `usuarios` no existía en la base de datos entregada — el login
nunca pudo funcionar en el sistema original. Se agregó con esta cuenta
inicial. Cámbiala apenas ingreses.)*

---

## 2. Arquitectura del proyecto

```
SISTEMA_LUARSOFT/
├── index.php               Panel principal (dashboard con KPIs y gráficos)
├── login.php                Inicio de sesión
├── logout.php                Cierre de sesión
│
├── config/
│   └── conexion.php          Conexión BD + BASE_URL + sesión
│
├── includes/
│   ├── auth.php               Middleware: exige sesión iniciada
│   ├── funciones.php          Helpers (url, h, moneda, flash, etc.)
│   ├── sidebar.php             Menú lateral común a todo el sistema
│   ├── header.php               Barra superior (topbar)
│   ├── footer.php                Pie de página corporativo
│   ├── layout_top.php             Apertura del layout general
│   └── layout_bottom.php           Cierre del layout general
│
├── assets/
│   ├── css/style.css          Sistema de diseño (tema Eros)
│   ├── js/app.js               Sidebar, submenús, toasts (SweetAlert2)
│   ├── img/                     eros.jpg, logo_impresora.png
│   └── vendor/                   bootstrap.min.css, phpqrcode.php
│
├── modules/
│   ├── clientes/      nuevo, listado, buscar, editar, eliminar, imprimir, buscar_doc (API)
│   ├── productos/     nuevo, listado, editar, eliminar, control_stock, buscar_autocomplete (API)
│   ├── tecnicos/      nuevo, listado, editar, eliminar
│   ├── ventas/        pos, guardar_venta (API), ticket, imprimir,
│   │                  lista_boletas, editar_boleta, eliminar_boleta,
│   │                  lista_facturas, editar_factura, eliminar_factura
│   ├── ordenes/       nuevo, listado, editar, eliminar, imprimir
│   └── reportes/      mejores_clientes, productos_mas_vendidos
│
├── legacy/             Formularios/archivos del sistema original que
│                       NUNCA estuvieron enlazados desde el menú
│                       (se conservan funcionando, sin acceso directo
│                       en la nueva navegación, por transparencia).
│
└── database/
    └── luarsoft.sql  Base de datos completa y actualizada
```

---

## 3. Archivos nuevos en la v3

| Archivo | Función |
|---|---|
| `includes/document_modal.php` | Modal reutilizable de vista previa de documentos |
| `includes/visor.php` | Vista "pantalla completa" con Header/Sidebar + botón Volver |
| `modules/ordenes/_estados.php` | Catálogo del flujo de estados (con compatibilidad hacia atrás) |
| `modules/ordenes/recibo.php` | Constancia de Recepción de Equipo (nuevo documento) |
| `modules/ordenes/agregar_repuesto.php` | API: asigna repuesto del inventario a una orden (descuenta stock) |
| `modules/ordenes/eliminar_repuesto.php` | API: quita un repuesto y devuelve el stock |
| `database/migracion_v2.1_orden_tecnica.sql` | Migración no-destructiva para instalaciones ya existentes |

`modules/ordenes/imprimir.php` fue **reescrito** (ahora es el Ticket de
Entrega final, con repuestos y garantía) y `modules/ordenes/nuevo.php` /
`editar.php` / `listado.php` fueron **ampliados** con la ficha técnica y el
nuevo flujo de estados — nada de la lógica original (clientes, productos,
técnicos, POS, boletas, facturas) fue eliminado ni alterado en su
comportamiento base.

## 4. Garantía de continuidad funcional
Todo lo que hacía el sistema original sigue existiendo, solo que ahora
en rutas ordenadas y con una interfaz consistente. Tabla de equivalencia:

| Archivo original                     | Nuevo archivo                                  |
|---------------------------------------|-------------------------------------------------|
| `1.php`                                | `index.php`                                      |
| `login.html` + `login.php`             | `login.php` (unificado)                          |
| `clientes.php`                         | `modules/clientes/nuevo.php`                     |
| `listado_clientes.php`                 | `modules/clientes/listado.php`                   |
| `buscar_clientes.php`                  | `modules/clientes/buscar.php`                    |
| `editar_cliente.php`                   | `modules/clientes/editar.php`                    |
| `eliminar_cliente.php`                 | `modules/clientes/eliminar.php`                  |
| `imprimir_cliente.php`                 | `modules/clientes/imprimir.php`                  |
| `buscar_cliente_doc.php`               | `modules/clientes/buscar_doc.php`                |
| `agregar_cliente.php` / `nuevo_cliente.php` | `legacy/clientes_formulario_alterno.php`    |
| `a#U00f1adir_producto.php` (roto)      | `modules/productos/nuevo.php`                    |
| `guardar_producto.php`                 | *(unificado dentro de `modules/productos/nuevo.php`)* |
| `listado_productos.php`                | `modules/productos/listado.php`                  |
| `editar_producto.php`                  | `modules/productos/editar.php`                   |
| `eliminar_producto.php`                | `modules/productos/eliminar.php`                 |
| `control_stock.php`                    | `modules/productos/control_stock.php`            |
| `buscar_productos_autocomplete.php`    | `modules/productos/buscar_autocomplete.php`      |
| `tecnicos.php`                         | `modules/tecnicos/listado.php`                   |
| `nuevo_tecnico.php`                    | `modules/tecnicos/nuevo.php`                     |
| `editar_tecnico.php`                   | `modules/tecnicos/editar.php`                    |
| `eliminar_tecnico.php`                 | `modules/tecnicos/eliminar.php`                  |
| `pos.php`                              | `modules/ventas/pos.php`                         |
| `guardar_venta.php`                    | `modules/ventas/guardar_venta.php`               |
| `ticket.php`                           | `modules/ventas/ticket.php`                      |
| `imprimir_boleta.php` (+ `imprimir_factura.php`, roto) | `modules/ventas/imprimir.php` (sirve ambos) |
| `lista_boletas.php` / `editar_boleta.php` / `eliminar_boleta.php` | `modules/ventas/lista_boletas.php` / `editar_boleta.php` / `eliminar_boleta.php` |
| `lista_facturas.php` / `editar_factura.php` / `eliminar_factura.php` | `modules/ventas/lista_facturas.php` / `editar_factura.php` / `eliminar_factura.php` |
| `mostrar_articulo.php`                 | `modules/ordenes/listado.php`                    |
| `agregar.php` / `editar.php` / `eliminar.php` / `imprimir.php` (órdenes) | `modules/ordenes/nuevo.php` / `editar.php` / `eliminar.php` / `imprimir.php` |
| `mejores_clientes.php`                 | `modules/reportes/mejores_clientes.php`          |
| `productos_mas_vendidos.php`           | `modules/reportes/productos_mas_vendidos.php`    |
| `sidebar.php`, `nota_venta.php`, `imprimir_ticket.php` (nunca enlazados) | `legacy/*.txt` (referencia, no ejecutables) |

---

## 5. Mejoras aplicadas (sin quitar nada)

- **Seguridad:** todas las consultas fueron migradas a sentencias
  preparadas (antes varias concatenaban `$_GET`/`$_POST` directo en el SQL).
- **Login funcional:** el formulario original enviaba los datos a
  `1.html` en vez de a `login.php` — nunca autenticaba. Ahora es un
  único flujo funcional con `password_hash`/`password_verify`.
- **Enlace roto corregido:** `añadir_producto.php` estaba guardado con
  el nombre corrupto `a#U00f1adir_producto.php`, rompiendo el acceso
  desde el menú. Ya funciona con normalidad.
- **`imprimir_factura.php` faltante:** `lista_facturas.php` enlazaba a
  un archivo que no existía en el paquete. Se unificó con la plantilla
  de impresión (que ya soportaba ambos tipos de documento).
- **Tablas faltantes:** `usuarios` (login) y `ordenes` (reparaciones)
  no estaban en el `.sql` entregado; ya están creadas con datos base.
- **QR real en el ticket:** el ticket original referenciaba una imagen
  `qr.png` que nunca existía. Ahora se genera un QR real con los datos
  del comprobante usando la librería `phpqrcode` (ya incluida en el
  proyecto original pero sin usar).
- **Sesión obligatoria:** ninguna página validaba si había una sesión
  iniciada; ahora todos los módulos exigen login.
- **Dashboard:** se agregaron 4 tarjetas KPI (clientes, productos,
  stock bajo, ventas del día) sin tocar los gráficos originales.
- **Notificaciones modernas:** SweetAlert2 para confirmaciones de
  eliminación y mensajes de éxito/error (antes `alert()` nativo).

---

## 6. Notas técnicas

- El sistema asume PHP 7.4+ / 8.x con extensión `mysqli` habilitada.
- Bootstrap 5 (JS) y Chart.js/SweetAlert2 se cargan por CDN — se
  requiere conexión a internet en el navegador del usuario final para
  esos recursos. El CSS de Bootstrap va incluido localmente.
- El diseño es responsivo (sidebar colapsable en escritorio, menú
  deslizable en móvil).
