<template>
  <div class="flex flex-col gap-5 p-1 w-full">

    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-0.5">Base de datos</h1>
      <p class="text-sm text-gray-500">Estado de conexión y tablas del sistema</p>
    </div>

    <!-- Estado de conexión -->
    <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm" aria-labelledby="section-status">
      <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
        <span id="section-status" class="text-white font-bold text-sm uppercase tracking-wide">Estado de conexión</span>
        <button
          type="button"
          :disabled="loading"
          :aria-busy="loading"
          @click="loadAll"
          class="flex items-center gap-1.5 text-[11px] text-slate-300 hover:text-white transition-colors outline-none
                 focus-visible:ring-1 focus-visible:ring-slate-400 rounded disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <RefreshCw class="w-3.5 h-3.5 transition-transform" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
          Actualizar
        </button>
      </div>

      <div class="p-6 flex items-center gap-4 bg-gray-50 border-t border-gray-200">
        <div class="w-11 h-11 rounded-lg flex items-center justify-center shrink-0 bg-blue-500/10 text-blue-600" aria-hidden="true">
          <Database class="w-5 h-5" />
        </div>
        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
          <div class="flex flex-col">
            <span class="text-[11px] text-gray-500">Base de datos</span>
            <strong class="text-[13px] text-gray-900 font-mono">{{ status.database || '—' }}</strong>
          </div>
          <div class="flex flex-col">
            <span class="text-[11px] text-gray-500">Host</span>
            <strong class="text-[13px] text-gray-900 font-mono">{{ status.host || '—' }}</strong>
          </div>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold shrink-0"
          :class="status.connected ? 'bg-emerald-500/15 text-emerald-600' : 'bg-red-500/15 text-red-600'"
        >
          <i class="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
          {{ status.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>
    </section>

    <div v-if="!status.connected" role="alert" class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[12px]">
      <AlertTriangle class="w-4 h-4 shrink-0" aria-hidden="true" />
      <p class="m-0 leading-relaxed">No hay conexión activa con la base de datos.</p>
    </div>

    <!-- Tablas migradas (colapsado por default) -->
    <section v-if="tablas.length" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm" aria-labelledby="section-tablas">
      <button
        type="button"
        @click="tablasAbiertas = !tablasAbiertas"
        :aria-expanded="tablasAbiertas"
        class="w-full bg-slate-900 px-6 py-4 flex items-center justify-between outline-none focus-visible:ring-1 focus-visible:ring-slate-400"
      >
        <span id="section-tablas" class="text-white font-bold text-sm uppercase tracking-wide">
          Tablas migradas ({{ tablas.length }})
        </span>
        <ChevronDown class="w-4 h-4 text-slate-300 transition-transform" :class="tablasAbiertas ? 'rotate-180' : ''" aria-hidden="true" />
      </button>

      <ul v-show="tablasAbiertas" class="divide-y divide-gray-100 list-none p-0 m-0">
        <li v-for="t in tablas" :key="t.tabla" class="flex items-center justify-between px-6 py-2.5">
          <span class="font-mono text-[12.5px] font-medium text-gray-900">{{ t.tabla }}</span>
          <span class="text-[12px] text-gray-500">{{ t.filas }} filas</span>
        </li>
      </ul>
    </section>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Database, AlertTriangle, RefreshCw, ChevronDown } from 'lucide-vue-next'
import { databaseService } from '../services/databaseService'

const status = ref({ connected: false, host: '', database: '' })
const tablas = ref([])
const loading = ref(false)
const tablasAbiertas = ref(false) // colapsado por default

async function loadAll() {
  loading.value = true
  try {
    const [statusData, tablasData] = await Promise.all([
      databaseService.getStatus(),
      databaseService.getTables(),
    ])
    status.value = statusData
    if (tablasData?.success) tablas.value = tablasData.tablas
  } catch (e) {
    console.error('Error al cargar datos de BD:', e)
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)
</script>