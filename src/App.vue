<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Network } from '@capacitor/network'
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera'

const API = import.meta.env.VITE_API_URL

const token = ref(localStorage.getItem('token') || '')
const credenciales = ref({
  email: 'demo@continental.edu.pe',
  password: 'password',
})
const items = ref([])
const estado = ref('')
const errorLogin = ref('')
const actualizado = ref(null)
const enLinea = ref(true)
const foto = ref(null)
const cargando = ref(false)
const enviando = ref(false)

let listenerRed = null
let alVolver = null

async function iniciarSesion() {
  errorLogin.value = ''
  enviando.value = true

  try {
    const res = await fetch(`${API}/login`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(credenciales.value),
    })
    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
      throw new Error(json.message || `No se pudo iniciar sesión (HTTP ${res.status})`)
    }

    token.value = json.token
    localStorage.setItem('token', json.token)
    await cargar()
  } catch (e) {
    errorLogin.value = e.message
  } finally {
    enviando.value = false
  }
}

async function cargar() {
  if (!token.value) return

  cargando.value = true
  estado.value = 'Cargando cursos…'

  try {
    const res = await fetch(`${API}/cursos`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token.value}`,
      },
    })

    if (res.status === 401) {
      await cerrarSesion()
      throw new Error('La sesión expiró. Vuelve a iniciar sesión.')
    }

    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    const json = await res.json()
    items.value = json.data ?? json
    actualizado.value = new Date().toLocaleString('es-PE')
    estado.value = ''
  } catch (e) {
    estado.value =
      e.message === 'Failed to fetch'
        ? 'No se pudo conectar con la API. Revisa que Laravel esté encendido.'
        : e.message
  } finally {
    cargando.value = false
  }
}

async function cerrarSesion() {
  token.value = ''
  items.value = []
  foto.value = null
  actualizado.value = null
  estado.value = ''
  localStorage.removeItem('token')

  // Evita que el siguiente usuario vea datos cacheados del anterior.
  if ('caches' in window) {
    await caches.delete('api-cache')
  }
}

async function tomarFoto() {
  try {
    const img = await Camera.getPhoto({
      resultType: CameraResultType.Uri,
      source: CameraSource.Prompt,
      quality: 80,
    })
    foto.value = img.webPath || img.path
  } catch (e) {
    if (!/cancel/i.test(e.message || '')) {
      estado.value = 'No se pudo abrir la cámara: ' + (e.message || 'error desconocido')
    }
  }
}

function actualizarConexion(conectado) {
  enLinea.value = conectado
}

onMounted(async () => {
  if (token.value) cargar()

  try {
    const status = await Network.getStatus()
    actualizarConexion(status.connected)
    listenerRed = await Network.addListener('networkStatusChange', (s) => {
      actualizarConexion(s.connected)
    })
  } catch {
    actualizarConexion(navigator.onLine)
  }

  alVolver = () => {
    if (navigator.onLine) cargar()
  }
  window.addEventListener('online', alVolver)
})

onUnmounted(() => {
  listenerRed?.remove()
  if (alVolver) window.removeEventListener('online', alVolver)
})
</script>

<template>
  <main class="app">
    <section v-if="!token" class="login">
      <div class="brand">
        <img src="/icons/192.png" alt="Logo Gestión de Cursos" width="72" height="72" />
        <div>
          <h1>Gestión de Cursos</h1>
          <p>Cliente PWA · API Laravel + Sanctum</p>
        </div>
      </div>

      <form class="card" @submit.prevent="iniciarSesion">
        <h2>Iniciar sesión</h2>

        <label>
          Correo
          <input v-model="credenciales.email" type="email" required autocomplete="username" />
        </label>

        <label>
          Contraseña
          <input
            v-model="credenciales.password"
            type="password"
            required
            autocomplete="current-password"
          />
        </label>

        <p v-if="errorLogin" class="error">{{ errorLogin }}</p>

        <button type="submit" :disabled="enviando">
          {{ enviando ? 'Ingresando…' : 'Ingresar' }}
        </button>

        <small class="hint">Demo: demo@continental.edu.pe · password</small>
      </form>
    </section>

    <template v-else>
      <header class="topbar">
        <div class="brand small">
          <img src="/icons/192.png" alt="Logo Gestión de Cursos" width="44" height="44" />
          <div>
            <h1>Cursos</h1>
            <p>{{ enLinea ? 'Conectado' : 'Sin conexión' }} · {{ items.length }} cursos</p>
          </div>
        </div>

        <div class="actions">
          <button class="ghost" :disabled="cargando" @click="cargar">Actualizar</button>
          <button class="ghost danger" @click="cerrarSesion">Salir</button>
        </div>
      </header>

      <p v-if="!enLinea" class="banner offline">
        Sin conexión. Se muestran los últimos datos guardados.
      </p>

      <section class="toolbar">
        <button class="camera" @click="tomarFoto">📷 Tomar foto</button>
        <img v-if="foto" :src="foto" width="240" alt="Foto tomada" class="photo" />
      </section>
      <small v-if="foto" class="hint">
        La foto se muestra localmente; la API todavía no tiene un endpoint para subirla.
      </small>

      <p v-if="estado" class="estado" :class="{ error: /No se pudo|sesión/i.test(estado) }">
        {{ estado }}
      </p>

      <section class="grid">
        <article v-for="curso in items" :key="curso.id" class="card curso">
          <span class="badge">{{ curso.creditos }} créditos</span>
          <h2>{{ curso.nombre }}</h2>
          <p class="docente">👩‍🏫 {{ curso.docente }}</p>
          <p class="meta">
            <span>👥 {{ curso.matriculados ?? 0 }} matriculados</span>
            <span v-if="curso.creado">🗓 {{ curso.creado }}</span>
          </p>
        </article>
      </section>

      <p v-if="!items.length && !cargando && !estado" class="estado">
        No hay cursos para mostrar.
      </p>

      <small v-if="actualizado" class="actualizado">Actualizado: {{ actualizado }}</small>

      <details class="capas card">
        <summary>Capas de esta app híbrida</summary>
        <ul>
          <li><b>Interfaz web:</b> Vue 3 + Vite (este archivo y sus componentes).</li>
          <li><b>WebView:</b> el contenedor Android generado por Capacitor carga <code>dist/</code>.</li>
          <li><b>Puente:</b> <code>@capacitor/core</code> conecta JavaScript con código nativo.</li>
          <li>
            <b>Plugins:</b> <code>@capacitor/network</code> y <code>@capacitor/camera</code>; en el
            navegador usan la implementación web y PWA Elements.
          </li>
        </ul>
      </details>
    </template>
  </main>
</template>
