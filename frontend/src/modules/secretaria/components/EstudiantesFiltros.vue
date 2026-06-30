<template>
  <div class="flex flex-wrap items-center gap-3">

    <!-- Buscador -->
    <div class="relative flex-1 min-w-[220px]">
      <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
      </svg>
      <input
        type="text"
        placeholder="Buscar estudiante o código..."
        class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
        :value="modelValue.busqueda"
        @input="actualizar('busqueda', $event.target.value)"
      />
    </div>

    <!-- Año -->
    <div class="relative">
      <select
        class="pl-3 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
        :value="modelValue.anio"
        @change="actualizar('anio', $event.target.value)"
      >
        <option value="2026">Año 2026</option>
        <option value="2025">Año 2025</option>
        <option value="2024">Año 2024</option>
      </select>
    </div>

    <!-- Periodo -->
    <div class="relative">
      <select
        class="pl-3 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
        :value="modelValue.periodo"
        @change="actualizar('periodo', $event.target.value)"
      >
        <option v-for="p in PERIODOS" :key="p.value" :value="p.value">
          {{ p.label }}
        </option>
      </select>
    </div>

    <!-- Plan / Carrera -->
    <div class="relative">
      <select
        class="pl-3 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
        :value="modelValue.plan"
        @change="actualizar('plan', $event.target.value)"
      >
        <option value="">Todas las carreras</option>
        <option v-for="p in planesOptions" :key="p.codigo" :value="p.codigo">
          {{ p.nombre }}
        </option>
      </select>
    </div>

    <!-- Nivel -->
    <div class="relative">
      <select
        class="pl-3 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
        :value="modelValue.nivel"
        @change="actualizar('nivel', $event.target.value)"
      >
        <option value="">Todos los niveles</option>
        <option v-for="n in NIVELES" :key="n" :value="n">
          Nivel {{ n }}
        </option>
      </select>
    </div>

    <!-- Limpiar filtros -->
    <button
      type="button"
      class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-sm font-medium py-2.5 px-4 transition active:scale-95"
      @click="limpiarFiltros"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.635 19A9 9 0 104.582 9H4" />
      </svg>
      Limpiar filtros
    </button>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PLANES, PERIODOS, NIVELES } from '../services/estudiantesInscritosService.js'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    // { anio, periodo, plan, nivel, busqueda }
  },
})

const emit = defineEmits(['update:modelValue', 'limpiar'])

function actualizar(campo, valor) {
  emit('update:modelValue', { ...props.modelValue, [campo]: valor })
}

const planesOptions = computed(() =>
  Object.entries(PLANES).map(([codigo, nombre]) => ({ codigo, nombre }))
)

function limpiarFiltros() {
  emit('limpiar')
}
</script>