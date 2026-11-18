import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { defineCustomElements } from '@ionic/pwa-elements/loader'

// PWA Elements: da interfaz web a plugins nativos como Camera cuando
// la app corre en el navegador (no en Android/iOS).
defineCustomElements(window)

createApp(App).mount('#app')
console.log(import.meta.env.VITE_PAGOS_KEY)
