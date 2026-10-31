# Konstruktor · Landing page

Página de ventas de Konstruktor (`public_html/` del repositorio, sin la carpeta `panel/`), constructora con presupuesto por precio unitario, programa de obra,
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

Ver el `README.md` de la raíz del repositorio: la landing está en `public_html/` y se sube junto con el sistema.

## Después de subir

- Abre el dominio con `https://`. Si cPanel aún no tiene certificado (**SSL/TLS Status** › **Run AutoSSL**),
  comenta en `.htaccess` las 3 líneas de "Forzar HTTPS" mientras tanto.
- Prueba el formulario: debe abrir WhatsApp con el número de `CONFIG.whatsapp`.
- Si ya hay otro sitio en `public_html`, respáldalo antes: estos archivos reemplazan `index.html` y `.htaccess`.
