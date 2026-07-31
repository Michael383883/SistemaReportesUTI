<!-- composables/ResolucionSearchPicker -->
<template>
  <div class="space-y-4">
    <!-- Input búsqueda -->
    <div class="relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input
        v-model="terminoLocal"
        type="text"
        placeholder="Buscar por número de resolución..."
        :disabled="bloqueado"
        autocomplete="off"
        class="w-full bg-gray-50 border border-gray-200 rounded-lg pl-9 pr-9 py-2.5 text-sm text-gray-800
               placeholder-gray-400 outline-none transition-colors
               focus:border-amber-400 focus:ring-2 focus:ring-amber-100
               disabled:opacity-50 disabled:cursor-not-allowed"
        @input="onInput"
      />
      <svg v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <button
        v-else-if="terminoLocal && !bloqueado"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
        @click="onLimpiar"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <!-- Bloqueado: mensaje explicativo -->
    <p v-if="bloqueado" class="text-xs text-red-700 flex items-center gap-1.5 m-0">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Ya marcaste materias con esta resolución. Quita todas las materias para poder cambiarla.
    </p>

    <!-- Tarjeta resolución activa -->
    <div
      v-if="resolucionActiva"
      class="flex items-center gap-3 px-4 py-3 bg-blue-800 border border-blue-800 rounded-lg"
    >
      <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-blue-900">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-100 m-0 flex items-center gap-1.5">
          {{ resolucionActiva.nroResolucion }}
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-emerald-600 shrink-0">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </p>
        <p class="text-xs text-gray-100 m-0 mt-0.5">
          {{ resolucionActiva.anio }} · {{ resolucionActiva.periodo }}
        </p>
      </div>
      <button
        v-if="!bloqueado"
        type="button"
        class="text-gray-100 hover:text-red-500 transition-colors p-1"
        title="Quitar selección"
        @click="$emit('limpiar')"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <!-- Lista de resultados -->
    <div v-else>
      <div v-if="loading" class="flex items-center gap-2 px-3 py-6 text-sm text-gray-400 justify-center">
        <span class="w-3.5 h-3.5 border-2 border-gray-200 border-t-amber-500 rounded-full animate-spin shrink-0"/>
        Buscando resoluciones...
      </div>

      <div v-else-if="error" class="px-3 py-4 text-xs text-red-600">
        {{ error }}
      </div>

      <div v-else-if="filas.length === 0" class="flex flex-col items-center gap-1.5 px-3 py-8 text-sm text-gray-400">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        {{ busqueda ? `Sin resultados para "${busqueda}"` : 'Escribí un número para buscar' }}
      </div>

      <ul v-else class="list-none m-0 p-0 space-y-1.5 max-h-64 overflow-y-auto">
        <li
          v-for="r in filas"
          :key="r.idResolucion ?? r.id_resolucion"
          class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg cursor-pointer transition-colors
                 bg-white hover:bg-amber-50/60 border border-gray-200 hover:border-amber-300"
          @click="$emit('select', r)"
        >
          <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-500">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 m-0 truncate">{{ r.nroResolucion }}</p>
            <p class="text-xs text-gray-400 m-0 mt-0.5">{{ r.anio }} · {{ r.periodo }}</p>
          </div>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 shrink-0">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  filas: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  busqueda: { type: String, default: '' },
  resolucionActiva: { type: Object, default: null },
  bloqueado: { type: Boolean, default: false },
})

const emit = defineEmits(['buscar', 'limpiar-busqueda', 'select', 'limpiar'])

const terminoLocal = ref(props.busqueda)

watch(() => props.busqueda, (v) => { terminoLocal.value = v })

function onInput() {
  emit('buscar', terminoLocal.value)
}

function onLimpiar() {
  terminoLocal.value = ''
  emit('limpiar-busqueda')
}
</script>