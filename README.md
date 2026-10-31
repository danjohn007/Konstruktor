# Konstruktor · Landing page

Página de ventas de Konstruktor, constructora con presupuesto por precio unitario, programa de obra,
reporte fotográfico diario y pagos por avance real.

Es HTML estático: no necesita PHP, base de datos ni compilación. El formulario de cotización abre
WhatsApp con los datos de la obra ya escritos.

## Archivos

| Archivo | Qué es |
|---|---|
| `index.html` | La landing completa (estilos y scripts incluidos) |
| `404.html` | Página de error para rutas que no existen |
| `assets/` | Logo y favicon |
| `.htaccess` | HTTPS forzado, caché, compresión y bloqueo de archivos internos |
| `robots.txt` | Permite que Google indexe el sitio |
| `.cpanel.yml` | Instrucciones de despliegue para cPanel › Git Version Control |

## Antes de publicar: datos de contacto

Al final de `index.html` está el objeto `CONFIG`. Cambia esos valores y se actualizan en toda la página:

```js
const CONFIG = {
    whatsapp: '520000000000',              // 52 + 10 dígitos, sin espacios ni +
    telefono: '00 0000 0000',              // como se muestra en la página
    email: 'contacto@konstruktor.mx',
    zona: 'tu ciudad y zona metropolitana',
    mensajeWhatsApp: 'Hola Konstruktor, quiero cotizar una obra.'
};
```

`whatsapp` también se usa para el botón "Llámanos".

## Subir a cPanel

### Opción A: Administrador de archivos (la más rápida)

1. Descarga el ZIP del repositorio: **Code › Download ZIP** en GitHub.
2. En cPanel abre **Administrador de archivos** › `public_html` (o la carpeta del dominio).
3. **Cargar** el ZIP y luego clic derecho › **Extraer**.
4. Si quedó dentro de una subcarpeta (`Konstruktor-main/`), entra a ella, **Seleccionar todo** › **Mover** a `public_html`.
5. En **Configuración** (arriba a la derecha) activa **Mostrar archivos ocultos** y confirma que `.htaccess` quedó en `public_html`.

### Opción B: Git Version Control (actualizaciones con un clic)

1. En cPanel abre **Git™ Version Control** › **Create**.
2. Activa **Clone a Repository** y pega `https://github.com/danjohn007/Konstruktor.git`.
3. En **Repository Path** pon una carpeta fuera de `public_html`, por ejemplo `repositories/Konstruktor`.
4. **Create**. Luego en **Manage** › **Pull or Deploy** › **Deploy HEAD Commit**.

`.cpanel.yml` copia la página a `public_html`. Si el sitio va en un subdominio o dominio adicional,
cambia `DEPLOYPATH` en ese archivo antes de desplegar. Para publicar cambios: sube a GitHub, y en cPanel
**Update from Remote** › **Deploy HEAD Commit**.

## Después de subir

- Abre el dominio con `https://`. Si cPanel aún no tiene certificado (**SSL/TLS Status** › **Run AutoSSL**),
  comenta en `.htaccess` las 3 líneas de "Forzar HTTPS" mientras tanto.
- Prueba el formulario: debe abrir WhatsApp con el número de `CONFIG.whatsapp`.
- Si ya hay otro sitio en `public_html`, respáldalo antes: estos archivos reemplazan `index.html` y `.htaccess`.
