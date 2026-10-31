# Konstruktor · Sistema web de cotización de obra

Versión web del *Cotizador Konstruktor 2026* (Excel). Calcula precios unitarios con las mismas
fórmulas y redondeos que la plantilla: el concepto de ejemplo 1.01 da **$4,170.61**, igual que el Excel.

Stack: PHP 8.2 + MySQL/MariaDB, MVC propio sin framework (misma estructura que el sistema de Notaría),
Bootstrap 5 y API JSON.

## Fase 1 — qué incluye

| Módulo | Equivale en el Excel a |
|---|---|
| **Configuración**: datos de la empresa, % por defecto, tasas del factor de salario, textos del presupuesto | DATOS |
| **Insumos**: 306 materiales y 15 equipos cargados de BASICOS, con categoría y marca “precio sin IVA” | BASICOS |
| **Cuadrillas**: integrantes y salario semanal → costo por jornada (nómina, aguinaldo, prima, ISN, IMSS, INFONAVIT, AFORE) | BASICOS (mano de obra) |
| **Clientes** | — |
| **Cotizaciones**: partidas y conceptos, análisis de P.U. por concepto, números generadores | CATALOGO, ANALISIS, PU, VOLUMENES |
| Explosión de insumos | BASICOS (cantidad/importe) |
| Programa de obra (Gantt por día) con importe programado por semana | PROGRAMA |
| **Presupuesto** imprimible / PDF desde el navegador | PRESUPUESTO |

Diferencias deliberadas respecto al Excel:

- Cada cotización **copia** los % (indirectos, utilidad, IVA…) al crearse; cambiar la configuración no altera cotizaciones ya enviadas.
- El precio de cada insumo se **congela** en el análisis al agregarlo. El botón “Actualizar precios” trae los precios vigentes del catálogo.
- Las claves de concepto son texto: `1.10` y `1.1` ya no se confunden.
- Folios automáticos `KON-26-001`, `KON-26-002`… (prefijo configurable).

## Fase 2 — ejecución de obra

| Módulo | Qué hace |
|---|---|
| **Estimaciones** | Cantidades por concepto y periodo; importe, amortización del anticipo (topada al anticipo otorgado), IVA, fondo de garantía y neto a pagar; anterior / acumulado / por ejecutar con volumen excedente en rojo; carátula imprimible con firmas. Estados: borrador → presentada → autorizada → pagada. |
| **Control de obra** (hojas AVANCE y CONTROL) | Ingresos y egresos por obra con categoría, partida, proveedor y RFC; gasto contra presupuesto por tipo (materiales, mano de obra, equipo, indirectos) y por partida; avance estimado contra programado a la fecha; saldo de caja. “Registrar cobro” en una estimación crea el ingreso automáticamente. |
| **Usuarios** | Alta de usuarios Admin / Cotizador, activar/desactivar, restablecer contraseña; cada quien cambia la suya (ícono de llave). Un usuario desactivado pierde la sesión de inmediato. |

Reglas de negocio:

- Solo se estiman cotizaciones **aceptadas**. Al aceptarla, catálogo, análisis y porcentajes quedan fijos (es el contrato); mientras no tenga estimaciones puede regresarse a “enviada”.
- Solo puede haber una estimación en borrador a la vez, y solo se elimina la última si sigue en borrador.
- El fondo de garantía (% en Configuración y en cada cotización) se descuenta del importe de cada estimación; por defecto es 0 %.

**Base de datos existente de Fase 1:** se actualiza sola (ver *Migraciones automáticas*).

## Fase 3 — app móvil, avance diario y flujo de efectivo

| Módulo | Equivale en el Excel 2024 a |
|---|---|
| **Avance** (pestaña de la cotización): cantidad ejecutada por concepto y día, con foto; % ejecutado contra lo que el programa dice a hoy; costo real por concepto, diferencia y P.U. real; resumen semanal programado / ejecutado / gastado / cobrado | AVANCE |
| **Compras y flujo** (pestaña de la cotización): materiales a comprar por semana, jornadas de cuadrilla por semana y flujo de efectivo programado (anticipo + estimaciones contra egresos) junto al real | MATERIAL, MANO DE OBRA y FLUJO DE EFECTIVO |
| **Control de obra**: cada gasto puede ligarse a un concepto del catálogo (columna CATALOGO del Excel) y llevar la foto del ticket o factura | CONTROL |
| **Diseño adaptado a celular**: menú lateral plegable, tablas con desplazamiento y ventanas a pantalla completa | — |
| **API para la app móvil** (`app-movil/` en la raíz del repositorio): login por token, lista de obras, avance, movimientos y subida de fotos | — |

Reglas y cálculos:

- **Avance:** solo se registra en cotizaciones **aceptadas**. Cada quien corrige o borra sus registros; el Admin, todos.
- **Reparto en el tiempo:** como en el Excel, el costo de cada concepto se reparte por igual entre los días de calendario de su programa, de inicio a término.
- **P.U. real:** reparte los gastos sin concepto (gastos generales) en proporción al gasto total, igual que la hoja AVANCE.
- **Flujo programado:** el anticipo entra la semana de inicio y el resto en *N* estimaciones iguales repartidas a lo largo del programa. Los importes son sin IVA.
- **Archivos:** las fotos y comprobantes se guardan en `app/storage/uploads`, que no es accesible por URL, y se sirven solo a usuarios con sesión desde `/archivos/{tipo}/{id}`.
  - Se aceptan JPG, PNG, WEBP o PDF de hasta 10 MB.
  - La web y la app reducen las fotos a 1600 px antes de subirlas, pero conviene que el hosting tenga `upload_max_filesize` y `post_max_size` de al menos 10M.
  - Al borrar un movimiento, un avance o una cotización, sus archivos también se borran.

**Base de datos existente de Fase 2:** se actualiza sola (ver *Migraciones automáticas*).

### API de la app móvil

La app manda `Authorization: Bearer <token>`. Si el hosting borra ese encabezado, la manda también como `X-Auth-Token`; el `.htaccess` ya lo reenvía a PHP.

- **Tokens:**
  - Los tokens duran 180 días.
  - En la tabla `api_tokens` se guarda solo su SHA-256.
  - Al desactivar a un usuario, sus tokens dejan de funcionar.
- **Sesión:** `POST /api/auth/token` (`{email, password, dispositivo}` → `{token, user}`), `DELETE /api/auth/token` (cerrar sesión) y `GET /api/auth/yo`.
- **Consulta:**
  - `GET /api/obras`: cotizaciones aceptadas con % ejecutado, % programado, cobrado y gastado.
  - `GET /api/cotizaciones/{id}/avance`, `GET /api/cotizaciones/{id}/flujo` y `GET /api/cotizaciones/{id}/control`.
- **Avances:**
  - `POST /api/cotizaciones/{id}/avances` con `{concepto_id, fecha, cantidad, notas, uuid}`.
  - `PUT` y `DELETE /api/avances/{id}`, y `POST /api/avances/{id}/foto` (multipart, campo `archivo`).
- **Movimientos:**
  - `POST /api/cotizaciones/{id}/movimientos` acepta además `concepto_id` y `uuid`.
  - `POST` y `DELETE /api/movimientos/{id}/comprobante`.
- **Sin duplicados:** si la app reintenta un `POST` con el mismo `uuid`, el servidor devuelve el registro existente y no lo duplica.
- **Subidas desde el navegador:** deben llevar `X-Requested-With: konstruktor` (`apiUpload()` en `assets/js/app.js`). Un multipart no exige preflight CORS, así que este encabezado frena envíos desde otros sitios.

## Fase 4 — datos del Excel 2024

| Cambio | Qué hace |
|---|---|
| **Insumos nuevos** | 10 materiales que se usaron en 2024 y no estaban en el catálogo (tinaco 1100 L y accesorios, ventanas de aluminio por medida, puerta prefabricada, viga IPR 8x4", PTR 2", lámina lisa), con su precio de 2024. |
| **Retiro de utilidad y préstamos** | Nuevas categorías de movimiento: *Retiro de utilidad* (egreso, solo en la web) y *Préstamo* (egreso o ingreso, también en la app). Cuentan en el saldo de caja y en el flujo real, pero no como gasto de la obra ni como cobro al cliente, así que no mueven el P.U. real ni el gasto contra presupuesto. No llevan partida ni concepto. |
| **Importar obras del Excel 2024** | `herramientas/importar_excel_2024.py` convierte un libro con el formato de *Presupuestos Konstruktor 2024.xlsx* en un `.sql` que crea la obra completa (ver abajo). |

**Base de datos existente de Fase 3:** se actualiza sola (ver *Migraciones automáticas*).

### Importar una obra del Excel 2024

Se corre en la computadora (Python 3.10+ con `pip install openpyxl`), no en el hosting:

```
python herramientas/importar_excel_2024.py "Presupuestos Konstruktor 2024.xlsx" obra.sql --cliente "PERLA RODRÍGUEZ" --iva 0
```

El `.sql` resultante se importa en phpMyAdmin. Crea la cotización *aceptada* con su catálogo, análisis de P.U., generadores, programa, los movimientos de CONTROL y cada concepto como terminado (`--sin-avance` para una obra en curso). Si el folio ya existe no cambia nada.

- Los precios de cada análisis quedan como en 2024.
- La herramienta menor del Excel es un % distinto por concepto: entra como una línea de cada análisis y la cotización queda con 0 % de herramienta.
- Las cuadrillas de 2024 entran como insumos inactivos `CUAD-n-2024` y no tocan las cuadrillas vigentes.
- En CONTROL, `GENTE` pasa a mano de obra, `HERRAMIENTA` a equipo y herramienta, la fila con CATALOGO = `UTILIDAD` a *Retiro de utilidad* y `PRESTAMO` a *Préstamo*.
- El script se detiene si algún P.U. calculado con las reglas del sistema no es igual al del Excel.

La obra KON-23-00 importada así da los mismos P.U., subtotal ($559,385.00), compras de material por semana, costo real y P.U. real por concepto (p. ej. muros 1.01: $742.22) que el Excel. La única diferencia es la mano de obra de la semana del 18/12/2023: en el Excel la fila de 1.03 Cadenas no tiene fórmulas esa semana; el sistema sí reparte esos $15,405.75.

Pendiente para fases siguientes: órdenes de compra, control de cambios (aditivas/deductivas),
lista de raya semanal, tablero de varias obras, notificaciones push en la app.

## Estructura

El código está en `public_html/panel/` del repositorio; las rutas de abajo son relativas a esa carpeta.

```
index.php                 Rutas (páginas y API)
assets/                   CSS, JS y logo
app/config.php            Conexión a BD (variables de entorno DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
app/config.local.php      Conexión del hosting, la escribe el instalador (no está en el repositorio)
app/database/schema.sql   Esquema
app/database/insumos_iniciales.sql   Catálogo tomado del Excel
app/database/seed.php     Instalador por terminal: tablas, carga inicial y usuario administrador
app/src/Core/             Router, Auth, Database, Request, Response, Config
app/src/Services/         Calculadora (P.U., totales, explosión, programa), Nomina (costo de cuadrillas),
                          Estimaciones (amortización, retención y acumulados), Programa (avance, compras
                          y flujo semanal), Archivos (fotos y comprobantes) e Instalador
app/database/migraciones/  Cambios a la base que se aplican solos (002 Fase 2, 003 Fase 3, 004 insumos 2024…)
app/storage/uploads/      Fotos y comprobantes subidos (bloqueado por app/.htaccess)
app/src/Controllers/      Un controlador por módulo
app/src/Views/            Vistas PHP + JS
seed_web.php              Instalador desde el navegador (se borra solo al terminar)
```

**Carpeta:** el sistema funciona en la raíz de un dominio o en una subcarpeta (`konstruktor.com.mx/panel`).
`BASE` (en `app/src/autoload.php`) es la carpeta donde quedó `index.php`. Las vistas la ponen en
`<base href>`, por eso sus enlaces, `fetch` y redirecciones de JS son relativos (`api/clientes`, no
`/api/clientes`). Las redirecciones de PHP la anteponen: `header('Location: ' . BASE . '/login')`.

## Instalación local (XAMPP)

1. Crear la base: `CREATE DATABASE konstruktor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Desde `public_html/panel/app/`: `php database/seed.php tu@correo.com "TuClaveSegura"` (crea las tablas, el catálogo y el administrador).
3. Servir `public_html/` con Apache y abrir `http://localhost/panel/`, o para desarrollo
   `php -S 127.0.0.1:8085` desde `public_html/panel` con un router que sirva los archivos estáticos.

Si MySQL no usa el puerto 3306, define `DB_PORT`.

## Publicar en cPanel

Ver el `README.md` de la raíz del repositorio: se sube `public_html/` tal cual y el sistema queda en
`https://konstruktor.com.mx/panel`, donde arranca el instalador.

- El instalador (`seed_web.php`) pide los datos de la base y del administrador, guarda la conexión en
  `app/config.local.php`, crea las tablas, carga el catálogo y **se borra solo**. Si el hosting no lo deja,
  avisa que se borre a mano. Ya instalado no vuelve a cambiar nada aunque se suba de nuevo.
- PHP debe poder escribir en `app/` (para `config.local.php`) y en `app/storage/uploads` (permisos 755).
  En *Select PHP Version → Options* conviene subir `upload_max_filesize` y `post_max_size` a 10M o más.
- La app móvil publicada solo se conecta por HTTPS (AutoSSL en cPanel). `.htaccess` fuerza HTTPS fuera de `localhost`.
- `app/.htaccess` bloquea el acceso directo a `app/` (configuración, código y SQL).

**Actualizar una instalación:** subir los archivos nuevos encima. `config.local.php` y las fotos no vienen en
el repositorio, así que se conservan. Los cambios a la base se aplican solos en la primera visita; su estado se
ve en *Configuración → Base de datos*.

## Migraciones automáticas

Como `php artisan migrate` en Laravel: cada archivo de `app/database/migraciones/` se aplica una sola vez, en orden de nombre, y queda registrado en la tabla `migraciones`.

- **Cuándo:** en cada visita el sistema compara la carpeta con la tabla (una consulta) y aplica lo que falte. Un candado de MySQL evita que dos visitas apliquen lo mismo a la vez. En una base recién creada espera a que corra el instalador (`seed.php` / `seed_web.php`), que también las aplica.
- **Si una falla:** el sitio sigue funcionando, el error queda en *Configuración → Base de datos* y en el log de errores del servidor, y las siguientes no se aplican. Se reintenta sola cada 10 minutos, o al momento con el botón *Aplicar pendientes*.
- **El usuario MySQL del hosting** necesita permisos para crear y modificar tablas (en cPanel, *ALL PRIVILEGES* sobre la base).

Para agregar un cambio a la base:

1. Crear `app/database/migraciones/005_lo_que_hace.sql` (siguiente número).
2. Escribirlo de forma que se pueda correr más de una vez: `CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, `INSERT IGNORE`… Si falla a medias, se vuelve a correr desde el principio.
3. Poner el mismo cambio en `schema.sql` y agregar el archivo al `INSERT INTO migraciones` del final de `schema.sql`, para que una instalación nueva no lo repita.

Sin `DELIMITER`, procedimientos ni triggers: cada sentencia termina en `;` al final de la línea.

## Roles

- **Admin**: todo, incluida Configuración y eliminar cotizaciones, clientes e insumos.
- **Cotizador**: cotizaciones, estimaciones, control de obra, clientes, insumos y cuadrillas (sin Configuración, Usuarios ni eliminaciones de catálogo o de movimientos).
- **Ingeniero** (residente de obra), en la web y en la app:
  - Solo ve las cotizaciones **aceptadas** (en el menú, *Obras*). En cada una consulta el catálogo, el programa, el avance y el control de obra.
  - Registra **avance** con foto y **gastos** de la obra con su comprobante. Solo corrige los registros que él hizo.
  - No ve Inicio, clientes, insumos, cuadrillas, datos generales, estimaciones, flujo ni el presupuesto imprimible.
  - No registra ingresos, préstamos ni retiros de utilidad.
  - Los permisos están en `RUTAS_INGENIERO` (`index.php`): una ruta nueva le responde 403 hasta agregarla ahí.

  El rol se agrega solo a las bases existentes (migración `005_rol_ingeniero.sql`). Se le asigna a un usuario en *Usuarios*.

## Diseño

- **Colores:** variables en `assets/css/app.css` (`--navy-*`, `--brand-*` y los de estado). Los colores de Bootstrap se toman de ahí.
- **Tipografía:** Inter.
- **Estructura** al estilo de ID-Hub:
  - barra lateral navy con grupos;
  - barra superior fija con menú de usuario;
  - tarjetas `.kpi` (ícono, etiqueta, valor y nota);
  - pestañas subrayadas con `.cuenta`;
  - filtros rápidos `.chip`.
- **Gráficas:** ECharts.
  - Una vista las pide con `'graficas' => true`.
  - Se dibujan con `grafica(id, opciones)` de `assets/js/app.js`, que ya trae la paleta, los ejes en pesos (`ejeMontos()`) y el redibujado al cambiar de tamaño.
- **Scripts de las vistas:** `app.js` carga **después** del contenido. Un script de vista no puede usar sus constantes (`COLORES`…) fuera de `DOMContentLoaded`, ni declarar funciones con el mismo nombre.

## Datos de prueba

La migración `006_datos_prueba.php` carga datos para revisar el sistema con cada rol. Se aplica sola, igual que las demás migraciones.

| Correo | Rol | Contraseña |
|---|---|---|
| `admin@demo.konstruktor.mx` | Admin | `Demo2026!` |
| `cotizador@demo.konstruktor.mx` | Cotizador | `Demo2026!` |
| `ingeniero@demo.konstruktor.mx` | Ingeniero | `Demo2026!` |

- **Clientes:** 3 clientes de prueba.
- **Cotizaciones:** DEMO-01 a DEMO-05, en todos los estados.
- **Obras en ejecución:** DEMO-01 (atrasada) y DEMO-05 (al corriente), con programa, avance registrado por el ingeniero, gastos, anticipo y estimaciones.
- **Fechas:** se calculan desde el día en que se aplica la migración.

**Para quitarlos:** *Configuración → Base de datos → Borrar datos de prueba*. Se borran los usuarios y clientes con correo `@demo.konstruktor.mx` y las cotizaciones `DEMO-…` (con sus fotos). No se vuelven a cargar. Bórralos al terminar de probar: el repositorio es público y la contraseña está aquí.

Las migraciones `.php` devuelven `function (PDO $db): void`. Corren en una transacción y pueden usar los servicios del sistema (`Calculadora`, `Estimaciones`…).
