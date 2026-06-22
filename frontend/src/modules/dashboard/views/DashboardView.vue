<template>
  <div class="min-h-screen bg-[#f4f6f9] p-6">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-[#081F33]">Dashboard</h1>
      <p class="text-sm text-gray-400 mt-1">
        Resumen general del sistema · Gestión I-2026
      </p>
    </div>

    <!-- Error -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700
                              text-sm rounded-xl px-4 py-3">
      {{ error }}
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
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

    <!-- Fila inferior -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Tabla docentes (ocupa 2 cols) -->
      <div class="lg:col-span-2">
        <DocentesTable :items="topDocentes" :loading="loading" />
      </div>

      <!-- Donut -->
      <div>
        <DonutChart
          title="Distribución por Tipo"
          :items="distribucionTipo"
          :loading="loading"
        />
      </div>

      <!-- Resoluciones recientes (full width) -->
      <div class="lg:col-span-3">
        <ResolucionesRecientes :items="resolucionesRecientes" :loading="loading" />
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useDashboard } from '../composables/useDashboard'
import StatCard             from '../components/StatCard.vue'
import DocentesTable        from '../components/DocentesTable.vue'
import DonutChart           from '../components/DonutChart.vue'
import ResolucionesRecientes from '../components/ResolucionesRecientes.vue'

const {
  loading, error, resumen,
  topDocentes, resolucionesRecientes,
  distribucionTipo, porcentajeDocActivos,
  fetchKpis,
} = useDashboard()

onMounted(fetchKpis)
</script>