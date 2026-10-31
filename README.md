# Konstruktor

| Carpeta | Qué es |
|---|---|
| `public_html/` | Lo que se sube al hosting: la landing (`index.html`) y el sistema de cotización y control de obra (`panel/`) |
| `app-movil/` | App para celular (Expo / React Native) que usa la API del sistema |
| `herramientas/` | Script para importar obras del Excel 2024 (se corre en la computadora) |
| `docs/` | Documentación de la landing y del sistema web |

Una vez subido:

- **Landing:** `https://konstruktor.com.mx`
- **Sistema:** `https://konstruktor.com.mx/panel`

## Instalar en cPanel

Pasos 1 y 4 solo se hacen la primera vez.

1. **Base de datos.** En cPanel › **Bases de datos MySQL**:
   - crea una base (p. ej. `konstruktor`);
   - crea un usuario con contraseña;
   - en *Agregar usuario a la base de datos*, dale **TODOS LOS PRIVILEGIOS**.

   Anota los tres datos. cPanel les pone tu usuario delante, por ejemplo `konstruktorcom_konstruktor`.
2. **Descargar.** En GitHub, **Code › Download ZIP**.
3. **Subir y abrir.** En cPanel › **Administrador de archivos**:
   1. Entra a `public_html`, **Cargar** el ZIP y luego clic derecho › **Extraer**.
   2. Entra a `Konstruktor-main/public_html/` y elige **Seleccionar todo** › **Mover**. Escribe `/public_html` como destino.
   3. Borra la carpeta `Konstruktor-main` y el ZIP.

   Antes de mover, activa *Configuración › Mostrar archivos ocultos*: los `.htaccess` también tienen que pasar.
4. **Instalador.** Abre `https://konstruktor.com.mx/panel`. Te lleva solo al instalador. Ahí escribe:
   - los datos de la base del paso 1 (en *Servidor* deja `localhost`);
   - el correo y la contraseña con que vas a entrar.

   Pulsa **Instalar**. Esto crea las tablas, carga el catálogo de insumos, crea tu usuario administrador y se borra solo.
5. **Entrar** en `https://konstruktor.com.mx/panel/login`.

Este correo y contraseña también sirven en la app. En la app el servidor es `https://konstruktor.com.mx/panel`.

### Datos de prueba

La instalación trae un usuario de prueba por rol, con la contraseña `Demo2026!`:
- `admin@demo.konstruktor.mx`
- `cotizador@demo.konstruktor.mx`
- `ingeniero@demo.konstruktor.mx`

También trae clientes, cotizaciones y dos obras en ejecución.

Bórralos al terminar de probar en *Configuración → Base de datos → Borrar datos de prueba*. Este repositorio es público y la contraseña está escrita aquí.

### Actualizar

Repite los pasos 2 y 3. Cuando pregunte, elige sobrescribir.

- La conexión a la base y las fotos subidas no vienen en el ZIP, así que se conservan.
- Los cambios a la base de datos se aplican solos en la primera visita.
- Si el instalador vuelve a aparecer en el servidor, no hace nada: el sistema ya está instalado y se borra solo al abrirlo.

### Otra opción: Git Version Control

Con esta opción las actualizaciones son de un clic:

1. En cPanel › **Git™ Version Control** › **Create**.
2. Clona `https://github.com/danjohn007/Konstruktor.git` en una carpeta fuera de `public_html`, p. ej. `repositories/Konstruktor`.
3. En **Manage** › **Pull or Deploy** › **Deploy HEAD Commit**. `.cpanel.yml` copia `public_html/` del repositorio a tu `public_html`.
4. Sigue con el paso 4 de arriba.

Para actualizar: **Update from Remote** › **Deploy HEAD Commit**.

## Requisitos del hosting

- **PHP 8.2 o más** (cPanel › *Select PHP Version*).
- **MySQL o MariaDB.**
- **SSL activo** (cPanel › *SSL/TLS Status* › *Run AutoSSL*): la landing y el sistema fuerzan HTTPS.
- **Para fotos de obra:** en *Select PHP Version › Options*, sube `upload_max_filesize` y `post_max_size` a `10M`.

Los Excel, PDF y ZIP de trabajo no se suben al repositorio (está en `.gitignore`), porque el repositorio es público.

Más detalle en [docs/sistema-web.md](docs/sistema-web.md), [docs/landing.md](docs/landing.md) y [app-movil/README.md](app-movil/README.md).
