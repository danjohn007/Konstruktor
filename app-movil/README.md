# Konstruktor · App móvil de obra

App para Android e iPhone (Expo SDK 58, Expo Router, TypeScript) que usa el personal en campo.
Trabaja contra la API del sistema web (`../public_html/panel`) con el mismo usuario y contraseña.

## Qué hace

| Pantalla | Equivale en el Excel a |
|---|---|
| **Obras**: las cotizaciones *aceptadas*, con avance real contra programado y gasto | — |
| **Resumen** de la obra: avance físico, semana actual, cobrado, gastado y saldo de caja | AVANCE / CONTROL |
| **Avance**: cada concepto con lo ejecutado, lo que debería ir según el programa y su estado; registrar la cantidad ejecutada del día con foto | AVANCE |
| **Gastos**: movimientos de la obra; registrar gasto o ingreso con foto del ticket, ligado a un concepto del catálogo | CONTROL (columna CATALOGO) |
| **Pendientes**: lo capturado sin señal que falta enviar | — |

**Sin señal en obra:** todo lo que se guarda entra primero a una cola en el teléfono y se envía solo al recuperar la conexión o al volver a abrir la app. Cada registro lleva un `uuid`, así que si un envío se repite el servidor no lo duplica. Las pantallas de consulta muestran la última copia descargada.

La sesión dura 180 días. Se guarda en el almacenamiento seguro del teléfono (Keychain en iPhone, Keystore en Android). Si se desactiva al usuario en la web, la app lo saca en la siguiente petición.

## Requisitos del servidor

El sistema web debe tener la **Fase 3** o posterior; al subir la versión nueva, su base se actualiza sola (ver *Migraciones automáticas* en `docs/sistema-web.md`).

Para las tiendas, el servidor tiene que estar en **HTTPS**: Android e iOS bloquean `http://` en apps publicadas.

## Desarrollo

```bash
npm install
cp .env.example .env.local      # y pon EXPO_PUBLIC_API_URL
npx expo start                  # abre en Expo Go escaneando el QR, o pulsa "a" para el emulador de Android
```

- **Emulador de Android** con el sistema en tu PC: `EXPO_PUBLIC_API_URL=http://10.0.2.2:8085`. Sirve la web con `php -S 0.0.0.0:8085` desde `public_html/panel` (con un router que sirva los archivos estáticos, ver `docs/sistema-web.md`).
- **Teléfono físico:** usa la IP de tu PC en la misma red Wi-Fi (`http://192.168.x.x:8085`).

Antes de entregar cambios:

```bash
npx tsc --noEmit
npx eslint src
npx expo-doctor
```

### Estructura

```
src/app/_layout.tsx            Sesión: con usuario → (app), sin usuario → login (Stack.Protected)
src/app/login.tsx
src/app/(app)/index.tsx        Lista de obras
src/app/(app)/obra/[id].tsx    Resumen · Avance · Gastos
src/app/(app)/avance.tsx       Registrar avance
src/app/(app)/movimiento.tsx   Registrar gasto / ingreso
src/app/(app)/comprobante.tsx  Ver la foto de un ticket o de un avance
src/app/(app)/pendientes.tsx   Cola de envíos pendientes
src/lib/api.ts                 Cliente de la API (token Bearer, caché para consultas sin señal)
src/lib/cola.ts                Cola de pendientes y sincronización
src/lib/sesion.tsx             Login / logout
src/lib/imagen.ts              Cámara / galería y reducción de fotos a 1600 px JPEG
```

## Publicar en Play Store y App Store

Las compilaciones se hacen en la nube con **EAS**, sin Android Studio ni Mac. Se necesita una cuenta gratuita en <https://expo.dev>.

### 1. Antes de la primera compilación (una sola vez)

1. **Identificador de la app:** en `app.json`, cambia `ios.bundleIdentifier` y `android.package` (hoy `mx.konstruktor.obra`). Pon uno basado en tu dominio. **Ya no se puede cambiar después de publicar.**
2. **Íconos:** los de `assets/images` son provisionales (una "K" sobre azul marino). Reemplázalos por el logo en alta resolución:
   - `icon.png`: 1024×1024 sin transparencia.
   - `android-icon-foreground.png`: 1024×1024 transparente, con el logo dentro del 66 % central.
   - `android-icon-monochrome.png`: igual que el anterior, en blanco.
   - `splash-icon.png`: transparente.
3. **Servidor de producción:** en `eas.json`, agrega a los perfiles `preview` y `production` lo siguiente:
   ```json
   "env": { "EXPO_PUBLIC_API_URL": "https://konstruktor.com.mx/panel" }
   ```
4. Ejecuta:
   ```bash
   npx eas-cli@latest login
   npx eas-cli@latest init          # liga el proyecto a tu cuenta de Expo (agrega extra.eas.projectId a app.json)
   ```

### 2. Probar en teléfonos antes de publicar

```bash
npx eas-cli@latest build --profile preview --platform android
```

Genera un **APK** que se instala directo en cualquier Android: compartes el link que da EAS y listo.

En iPhone, las pruebas se hacen con **TestFlight**: compila con el paso 3 y súbelo con `submit`.

### 3. Compilación para tiendas

```bash
npx eas-cli@latest build --profile production --platform android   # .aab para Play Store
npx eas-cli@latest build --profile production --platform ios       # .ipa para App Store
```

El número de versión interno (`versionCode` / `buildNumber`) lo sube EAS solo en cada compilación (`appVersionSource: remote`). Al cambiar la versión visible, edita `version` en `app.json`.

### 4. Subir a las tiendas

```bash
npx eas-cli@latest submit --platform android
npx eas-cli@latest submit --platform ios
```

| | Google Play | Apple App Store |
|---|---|---|
| Cuenta de desarrollador | Google Play Console, **$25 USD una vez** | Apple Developer Program, **$99 USD al año** |
| Primera vez | El primer `.aab` se sube a mano en Play Console. Las cuentas personales nuevas deben hacer una prueba cerrada con testers antes de publicar | EAS crea los certificados; crea la app en App Store Connect |
| Revisión | Horas a pocos días | 1 a 3 días aprox. |

Lo que piden ambas tiendas:

- **Cuenta de prueba para los revisores:** la app no funciona sin usuario. Crea un usuario de demostración con una obra de ejemplo y pon sus datos en las notas de revisión.
- **Política de privacidad:** una URL pública. La app guarda correo, nombre, fotos de tickets y de obra y montos. No hay rastreo ni publicidad.
- **Formularios de datos:** "Seguridad de los datos" en Play y "App Privacy" en Apple. Declara fotos, nombre y correo, usados solo para la funcionalidad de la app.
- **Eliminación de cuenta:** Apple exige que, si la app tiene cuentas, se pueda pedir eliminarla. Como las cuentas las crea el Admin en la web, indica en las notas que la app es de uso interno para empleados y cómo se solicita la baja.
- **Uso interno:** si la app es solo para tu empresa, considera publicarla como **no listada** (Apple) o en **prueba interna / cerrada** (Play). Así no aparece en búsquedas.

### Actualizaciones

- **Cambios solo en el código de la app** (pantallas, textos): se pueden enviar sin pasar por la tienda con `eas update`. Requiere configurarlo una vez con `npx eas-cli@latest update:configure`.
- **Cambios de módulos nativos o de `app.json`** (permisos, íconos): necesitan una nueva compilación y revisión de la tienda.
