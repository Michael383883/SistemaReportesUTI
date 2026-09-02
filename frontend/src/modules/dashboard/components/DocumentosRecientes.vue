<template>
  <div class="bg-white border-t-4 border-slate-900 border-x border-b border-slate-200 rounded-2xl p-5 transition-colors duration-300">

    <div class="flex items-center justify-between mb-4">
        
      <h3 class="text-sm font-bold text-slate-800">Documentos Recientes</h3>
      <router-link
        to="/clasificaciones"
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
      Sin documentos registrados
    </div>

    <!-- Lista -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1">
      <!--
        Antes: <a :href="urlPdf(doc.id)" target="_blank">.
        /api/clasificaciones/{id}/pdf ahora está protegida con auth:sanctum.
        Un <a href> normal del navegador NO manda el header Authorization,
        así que siempre iba a dar 401. Ahora es un botón que pide el PDF
        con axios (con token) y lo abre como blob.
      -->
      <button
        v-for="(doc, index) in items"
        :key="doc.id"
        @click="verPdf(doc.id)"
        :disabled="abriendoId === doc.id"
        :class="[
          'flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors text-left w-full cursor-pointer disabled:opacity-60 disabled:cursor-wait',
          index % 2 === 0 ? 'bg-white hover:bg-amber-50' : 'bg-slate-50 hover:bg-amber-50'
        ]"
        :title="doc.archivo ?? 'Documento'"
      >
        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
          <FileText class="w-4 h-4 text-amber-600" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-semibold text-slate-800 truncate">
            {{ doc.tipo || 'Documento sin tipo' }}
          </p>
          <p class="text-[12px] text-slate-800 truncate">
            {{ truncate(doc.categoria, 30) }}
            <span v-if="doc.gestion" class="text-slate-400">· Gestión {{ doc.gestion }}</span>
          </p>
        </div>
        <span class="text-[11px] text-slate-800 whitespace-nowrap flex-shrink-0">
          {{ abriendoId === doc.id ? 'Abriendo...' : formatDate(doc.fecha) }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { ArrowRight, FileText } from 'lucide-vue-next'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

defineProps({
  items:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})

// id del documento que se está abriendo ahora mismo, para deshabilitar
// su botón mientras carga y evitar doble click.
const abriendoId = ref(null)

async function verPdf(id) {
  if (abriendoId.value) return
  abriendoId.value = id
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get(`${API_BASE}/api/clasificaciones/${id}/pdf`, {
      params: { modo: 'inline' },
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      responseType: 'blob',
    })

    const blobUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    window.open(blobUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
  } catch (e) {
    console.error('No se pudo abrir el PDF:', e.response?.data ?? e.message)
    alert(e?.response?.status === 401
      ? 'Tu sesión expiró. Vuelve a iniciar sesión.'
      : 'No se pudo abrir el documento'
    )
  } finally {
    abriendoId.value = null
  }
}

function truncate(str, len) {
  if (!str) return '—'
  return str.length > len ? str.slice(0, len) + '…' : str
}
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-BO',
    { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>