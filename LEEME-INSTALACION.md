# Eros Tecnología — Sistema + Web pública

Ya está todo pre-configurado para esta estructura de carpetas. Solo hacen falta 2 pasos.

## Paso 1 — Copia la carpeta

Copia toda la carpeta **`eros-proyecto`** (tal cual, sin renombrarla) dentro de tu `htdocs`
de XAMPP. Debe quedar así:

```
htdocs/
  eros-proyecto/
    luarsoft/   → tu sistema (POS, órdenes, inventario)
    web/        → la web pública nueva
```

## Paso 2 — Importa UN SOLO archivo SQL

En phpMyAdmin, importa únicamente:

```
eros-proyecto/luarsoft/database/luarsoft_completo.sql
```

Este archivo ya trae tu sistema completo **y** la tabla nueva `clientes_web` juntos —
no hace falta importar nada más ni crear la base de datos a mano, el script la crea sola.

Eso es todo. Ya puedes entrar a:

- **Web pública**: `http://localhost/eros-proyecto/web/`
- **Sistema (LuarSoft)**: `http://localhost/eros-proyecto/luarsoft/`

## Cómo iniciar sesión

- **Cliente nuevo**: en la web pública → "Registrarme" → completa el formulario → te lleva
  a un botón de WhatsApp para avisarle al admin.
- **Administrador**: en la web pública, entra con el mismo usuario y contraseña que ya usas
  en LuarSoft (ej. `Luis Fernández`, que ya existe en tu base de datos). La web te reconoce
  como admin sola y te muestra el botón **"Ir al panel de gestión"** arriba a la derecha —
  entras directo al sistema sin volver a escribir tu contraseña.
- **Para aprobar registros de clientes**: como admin, haz clic en "Solicitudes" (aparece
  junto a tu nombre en la web) y aprueba o rechaza con un clic.
- **Seguimiento de equipo**: cualquier visitante puede consultarlo con N° de orden + teléfono,
  sin necesidad de cuenta.

## Antes de publicarlo en internet de verdad (no solo para la demo)

Esto es opcional para tu sustentación, pero importante si algún día lo subes a un hosting real:

- Cambia `CLAVE_SSO_COMPARTIDA` en `web/config/conexion_web.php` y en
  `luarsoft/sso_login.php` (debe ser la misma en ambos) por una clave propia.
- Crea un usuario de MySQL de solo lectura (`SELECT` sobre `productos`, `clientes_web`
  y `ordenes` nada más) y úsalo en `web/config/conexion_web.php` en vez de `root`.
- Instala HTTPS.

## Qué es cada archivo nuevo

| Archivo | Qué hace |
|---|---|
| `luarsoft/database/luarsoft_completo.sql` | Sistema completo + tabla `clientes_web`, en un solo import |
| `luarsoft/sso_login.php` | Recibe al admin desde la web y crea su sesión real del sistema |
| `web/config/conexion_web.php` | Conexión a la BD + generador del enlace de acceso al sistema |
| `web/index.php` | Home pública — catálogo real con candado para visitantes sin cuenta |
| `web/login.php` | Login único: revisa primero `usuarios` (staff) y luego `clientes_web` |
| `web/registro.php` | Registro + enlace de WhatsApp para validar la cuenta |
| `web/seguimiento.php` | Consulta pública del estado de una orden (N° de orden + teléfono) |
| `web/cuenta.php` | Área simple del cliente ya aprobado |
| `web/admin/solicitudes.php` | Panel donde el admin aprueba/rechaza registros |

Todo `web/` es responsivo (PC, laptop, tablet, celular) con los mismos puntos de quiebre
(900px y 640px) que ya usa tu sistema.
