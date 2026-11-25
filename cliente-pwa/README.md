# Gestión de Cursos · Cliente PWA

Cliente web en **Vue 3 + Vite** que consume la API Laravel de `gestion-cursos`, convertido en **PWA instalable** y empaquetado con **Capacitor**.

## Stack

- Vue 3 (`<script setup>`) + Vite 8
- `vite-plugin-pwa` 2 + Workbox 7
- Capacitor 8 (`@capacitor/core`, `@capacitor/android`)
- Plugins: `@capacitor/network`, `@capacitor/camera`
- `@ionic/pwa-elements` para la cámara en el navegador
- API: Laravel 13 + Sanctum (`/api/login`, `/api/cursos`)

## Requisitos

- Node.js 22 o superior (probado con Node 26)
- API Laravel corriendo en `http://127.0.0.1:8000`
- npm (en esta red se usó el mirror `https://registry.npmmirror.com`)

## Configuración

```bash
npm install
cp .env.example .env
npm run dev
```

`.env`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

La app queda en **http://localhost:5173**.

## Credenciales demo

```
demo@continental.edu.pe
password
```

El cliente hace `POST /api/login`, guarda el token Sanctum en `localStorage` y lo envía como
`Authorization: Bearer <token>` al pedir `GET /api/cursos`.

## API usada

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/login` | Devuelve `{ token }` |
| GET | `/api/cursos` | Lista paginada: `{ data: [...] }` |

Campos que muestra el cliente: `nombre`, `docente`, `creditos`, `matriculados`, `creado`.

CORS permite únicamente `http://localhost:5173` y `http://localhost:4173` sobre `api/*`.

## PWA

```bash
npm run build
npm run preview   # http://localhost:4173
```

- Manifest: `Gestión de Cursos` / `Cursos`, `display: standalone`, iconos 192 y 512.
- Service worker generado en `dist/sw.js` al compilar.
- Estrategia `NetworkFirst` para `/api/`, caché `api-cache`, timeout 3 s, 50 entradas / 24 h.
- Sin conexión se muestra el aviso y el último listado guardado.

Prueba offline:

1. Abrir `http://localhost:4173`, iniciar sesión y esperar el listado.
2. DevTools → Application → Service Workers: debe estar *activated*.
3. Application → Cache Storage → `api-cache`: debe aparecer `/api/cursos`.
4. Detener Laravel o activar *Offline* en DevTools y recargar: se ven los cursos cacheados.

## Capacitor

```bash
npm run build
npx cap sync android
```

El proyecto nativo está en `android/` y la web compilada se copia a:

```
android/app/src/main/assets/public/
```

Esta parte del laboratorio **no compila el APK**: el equipo no tiene Android Studio/SDK. Para
abrir el proyecto nativo se necesitaría Android Studio 2025.2.1+ y un SDK Android.

### Capas de la app híbrida

| Capa | Dónde está en este proyecto |
|---|---|
| Interfaz web | `src/App.vue`, `src/style.css`, Vue 3 + Vite |
| WebView | `android/` (Capacitor carga `dist/` dentro del WebView) |
| Puente | `@capacitor/core`; `Capacitor` conecta JavaScript con el contenedor nativo |
| Plugins | `@capacitor/network` (estado de red), `@capacitor/camera` (foto), `@ionic/pwa-elements` (UI web de cámara) |

En el navegador, `Network` y `Camera` usan su implementación web; en Android usarían el código
nativo a través del puente.

### Si más adelante compilas para Android

- El emulador no ve `127.0.0.1` del host: usa `http://10.0.2.2:8000/api`.
- El WebView de Capacitor sirve la app en `https://localhost`; para consumir una API HTTP local
  hay que habilitar `CapacitorHttp` o usar `androidScheme: "http"` en `capacitor.config.json`.

## Scripts

```bash
npm run dev       # Vite en 5173
npm run build     # build + service worker
npm run preview   # vista previa PWA en 4173
```

## Notas

- El token se guarda en `localStorage` (suficiente para el laboratorio; en producción conviene
  valorar cookies `HttpOnly`).
- Al cerrar sesión se borra la caché `api-cache` para no mezclar datos entre usuarios.
- La foto se muestra localmente; la API aún no tiene endpoint de subida.

## Seguridad en producciónnp

Valores que deben aplicarse al desplegar, sin cambiarlos en el entorno local de desarrollo:

```env
APP_ENV=production
APP_DEBUG=false
VITE_API_URL=https://api.dominio-real.example/api
```

- CORS debe aceptar únicamente el dominio real del cliente, por ejemplo `https://app.dominio-real.example`, sin usar `*` en `allowed_origins`.
- Laravel debe servirse exclusivamente por HTTPS; no habilites HTTP cleartext en producción.
- En `capacitor.config.json` no debe existir un bloque `server` con contenido HTTP inseguro ni `cleartext` habilitado.
- `config/sanctum.php` debe mantener una expiración definida para los tokens. El valor actual es `60 * 8` minutos (480 minutos).
- Los secretos y claves reales se almacenan solo en el `.env` de Laravel y no como variables `VITE_*` del cliente.
- Para una clave expuesta: revócala en el proveedor, emite una nueva y reemplázala únicamente en el servidor.
