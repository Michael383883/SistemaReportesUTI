<template>
  <div class="transition-colors duration-300">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-5 transition-colors duration-300">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Dashboard</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestión I-2026</p>
        </div>
        <div class="flex items-center gap-3">
          <button 
            @click="fetchKpis"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            Actualizar
          </button>
        </div>
      </div>
    </div>

    <div class="p-6 space-y-6">
      <!-- Error -->
      <div
        v-if="error"
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-xl px-4 py-3"
      >
        {{ error }}
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard
          icon="users"
          label="Docentes Registrados"
          :value="resumen.total_docentes ?? 0"
          sub="activos este periodo"
          :progress="porcentajeDocActivos"
          color="#D28B45"
          :loading="loading"
        />

        <StatCard
          icon="book-open"
          label="Materias Activas"
          :value="resumen.total_materias ?? 0"
          color="#081F33"
          :loading="loading"
        />

        <StatCard
          icon="layers"
          label="Grupos Asignados"
          :value="resumen.total_grupos ?? 0"
          color="#3b82f6"
          :loading="loading"
        />

        <StatCard
          icon="file-text"
          label="Resoluciones"
          :value="resumen.total_resoluciones ?? 0"
          color="#10b981"
          :loading="loading"
        />
      </div>

      <!-- Resoluciones recientes -->
      <ResolucionesRecientes
        :items="resolucionesRecientes"
        :loading="loading"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RefreshCw } from 'lucide-vue-next'

import { useDashboard } from '../composables/useDashboard'

import StatCard from '../components/StatCard.vue'
import ResolucionesRecientes from '../components/ResolucionesRecientes.vue'

const {
  loading,
  error,
  resumen,
  resolucionesRecientes,
  porcentajeDocActivos,
  fetchKpis,
} = useDashboard()

onMounted(() => {
  fetchKpis()
})
</script>