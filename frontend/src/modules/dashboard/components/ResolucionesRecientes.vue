<template>
  <div class="bg-white border-t-4 border-slate-900 border-x border-b border-slate-200 rounded-2xl p-5 transition-colors duration-300">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-800">Resoluciones Recientes</h3>
      <router-link
        to="/resoluciones/listado"
        class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:underline transition-colors"
      >
        Ver todas <ArrowRight class="w-3 h-3" />
      </router-link>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="flex flex-col gap-2">
      <div v-for="n in 4" :key="n"
        class="h-14 rounded-xl bg-gradient-to-r from-slate-200 via-slate-100 to-slate-200 bg-[length:200%_100%] animate-shimmer" />
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length"
      class="flex flex-col items-center justify-center gap-2 py-10 text-slate-400 text-sm">
      <FileText class="w-7 h-7 text-slate-300" />
      Sin resoluciones registradas
    </div>

    <!-- Lista -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1">
      <button
        v-for="(res, index) in items"
        :key="res.id"
        type="button"
        :disabled="!res.id || abriendoId === res.id"
        :class="[
          'flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors text-left w-full',
          index % 2 === 0
            ? 'bg-white hover:bg-amber-50'
            : 'bg-slate-50 hover:bg-amber-50',
          res.id ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'
        ]"
        :title="res.id ? `Ver ${res.archivo ?? 'PDF'}` : 'PDF no disponible'"
        @click="verResolucion(res)"
      >
        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
          <FileText class="w-4 h-4 text-amber-600" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-semibold text-slate-800">
            {{ res.numero }}
          </p>
          <p class="text-[12px] text-slate-800 truncate">
            {{ truncate(res.descripcion, 44) }}
          </p>
        </div>
        <span class="text-[11px] text-slate-800 whitespace-nowrap flex-shrink-0">
          {{ abriendoId === res.id ? 'Abriendo...' : formatDate(res.fecha) }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { ArrowRight, FileText } from 'lucide-vue-next'

const API_BASE = import.meta.env.VITE_API_URL

const props = defineProps({
  items:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})

function truncate(str, len) {
  if (!str) return '—'
  return str.length > len ? str.slice(0, len) + '…' : str
}
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-BO',
    { day: '2-digit', month: 'short', year: 'numeric' })
}

function authHeaders() {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}

const abriendoId = ref(null)

// Abre el PDF de la resolución en una nueva pestaña vía axios (blob),
// para que el token Bearer viaje en el header de la petición.
// Reemplaza el viejo window.open(res.url_pdf) que era una navegación
// directa del navegador y por lo tanto nunca mandaba el token.
async function verResolucion(res) {
  if (!res.id || abriendoId.value) return
  abriendoId.value = res.id
  try {
    const { data } = await axios.get(
      `${API_BASE}/api/resoluciones/${res.id}/pdf`,
      {
        headers: authHeaders(),
        responseType: 'blob',
      }
    )
    const blobUrl = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    window.open(blobUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
  } catch (e) {
    console.error('No se pudo abrir el PDF:', e.response?.data ?? e.message)
  } finally {
    abriendoId.value = null
  }
}
</script>