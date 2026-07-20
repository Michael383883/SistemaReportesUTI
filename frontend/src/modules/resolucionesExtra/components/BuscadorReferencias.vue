<template>
  <div class="bg-white rounded-xl border border-gray-200 p-4 relative">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-[12px] font-semibold text-gray-700">Buscador de Referencias</h3>
      <span class="text-[11px] text-gray-400">Selecciona una referencia para asignar</span>
    </div>

    <!-- Buscador -->
    <div class="relative">
      <input
        ref="inputRef"
        v-model="searchTerm"
        type="text"
        placeholder="Buscar por número de referencia o descripción..."
        class="w-full px-3 py-2 pl-9 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        @focus="onFocus"
        @blur="onBlurBusqueda"
        @keydown.down.prevent="moverSeleccion(1)"
        @keydown.up.prevent="moverSeleccion(-1)"
        @keydown.enter.prevent="confirmarSeleccion"
        @keydown.esc="cerrarDropdown"
        @input="onSearchInput"
      />
      <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      
      <!-- Indicador de carga -->
      <div v-if="loading" class="absolute right-3 top-2.5">
        <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>
    </div>

    <!-- Indicador de resultados -->
    <div class="text-[11px] text-gray-500 mt-1">
      <span v-if="referenciasFiltradas.length > 0" class="text-blue-600 font-medium">
        {{ referenciasFiltradas.length }} referencias disponibles
      </span>
      <span v-else-if="!loading && searchTerm" class="text-gray-500">
        No se encontraron referencias
      </span>
      <span v-else class="text-gray-500">
        Escribe para buscar referencias
      </span>
    </div>

    <!-- Mensaje de alerta por duplicado -->
    <div v-if="mensajeDuplicado" class="mt-2 flex items-center gap-2 p-2 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-[12px]">
      <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      <span>{{ mensajeDuplicado }}</span>
      <button @click="mensajeDuplicado = ''" class="ml-auto text-amber-400 hover:text-amber-600">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Dropdown de resultados -->
    <div
      v-if="dropdownOpen && (referenciasFiltradas.length || loading)"
      class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
      style="position: absolute; left: 0;"
    >
      <!-- Cargando -->
      <div v-if="loading" class="px-3 py-3 text-[12px] text-gray-500 text-center">
        <svg class="w-4 h-4 animate-spin inline mr-2" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Cargando referencias...
      </div>

      <!-- Sin resultados -->
      <div v-else-if="!referenciasFiltradas.length && searchTerm" class="px-3 py-3 text-[12px] text-gray-500 text-center">
        No se encontraron referencias para "{{ searchTerm }}"
      </div>

      <!-- Lista de referencias -->
      <div v-else-if="referenciasFiltradas.length">
        <div class="sticky top-0 bg-gray-50 px-3 py-1.5 text-[10px] text-gray-600 border-b border-gray-100">
          {{ referenciasFiltradas.length }} referencias disponibles
        </div>
        <button
          v-for="(ref, idx) in referenciasFiltradas"
          :key="ref.id"
          type="button"
          class="w-full text-left px-3 py-2 text-[12px] border-b border-gray-100 last:border-b-0 flex items-center justify-between transition-colors"
          :class="[
            idx === highlightIndex
              ? 'bg-blue-100 border-l-4 border-l-blue-500 ring-1 ring-inset ring-blue-200'
              : 'hover:bg-gray-50',
            referenciaYaSeleccionada(ref.id) ? 'opacity-60 cursor-not-allowed bg-green-50/40' : ''
          ]"
          @mousedown.prevent="onSelectReferencia(ref)"
          @mouseenter="highlightIndex = idx"
          :disabled="referenciaYaSeleccionada(ref.id)"
        >
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span
                class="text-gray-800"
                :class="idx === highlightIndex ? 'font-semibold' : 'font-medium'"
              >{{ ref.nro_referencia }}</span>
              <span
                class="text-[10px]"
                :class="idx === highlightIndex ? 'text-gray-700 font-medium' : 'text-gray-600'"
              >Año: <strong class="text-gray-800">{{ ref.anio || 'N/A' }}</strong></span>
              <span
                v-if="referenciaYaSeleccionada(ref.id)"
                class="text-[10px] font-semibold text-green-600 bg-green-100 px-1.5 py-0.5 rounded"
              >
                Ya registrada
              </span>
            </div>
            <div
              class="text-[11px] truncate mt-0.5"
              :class="idx === highlightIndex ? 'text-gray-700 font-medium' : 'text-gray-600'"
            >
              {{ ref.descripcion || 'Sin descripción' }}
            </div>
          </div>
          <!-- Círculo a la derecha -->
          <span class="ml-3 flex-shrink-0">
            <svg v-if="referenciaYaSeleccionada(ref.id)" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg
              v-else
              class="w-5 h-5"
              :class="idx === highlightIndex ? 'text-blue-600' : 'text-blue-400'"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" :stroke-width="idx === highlightIndex ? 2 : 1.5"
            >
              <circle cx="12" cy="12" r="9" stroke="currentColor"/>
            </svg>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useReferencias } from '../composables/useReferencias'

const props = defineProps({
  referenciasSeleccionadas: { type: Array, default: () => [] }
})

const emit = defineEmits(['agregar-referencia'])

const { loading, referencias, anios, listar, obtenerAnios } = useReferencias()

const searchTerm = ref('')
const dropdownOpen = ref(false)
const highlightIndex = ref(-1)
const inputRef = ref(null)
const mensajeDuplicado = ref('')

const referenciasFiltradas = computed(() => {
  return referencias.value
})

// Verificar si una referencia ya está seleccionada
function referenciaYaSeleccionada(id) {
  return props.referenciasSeleccionadas.some(
    r => Number(r.id_resolucion) === Number(id)
  )
}
// ─── Cargar referencias al hacer focus ───
// ─── Cargar referencias al hacer focus ───
async function onFocus() {
  dropdownOpen.value = true
  // Siempre carga (aunque no haya texto), así el docente ve de una las que ya tiene
  await cargarReferencias()
}
// ─── Cargar referencias con filtros ───
async function cargarReferencias() {
  const params = {}
  
  if (searchTerm.value) {
    params.search = searchTerm.value
  }
  
  await listar(params)
}

// ─── Navegación del dropdown ───
function moverSeleccion(delta) {
  if (!dropdownOpen.value) {
    dropdownOpen.value = true
    return
  }
  const total = referenciasFiltradas.value.length
  if (!total) return
  highlightIndex.value = (highlightIndex.value + delta + total) % total
}

function confirmarSeleccion() {
  if (!dropdownOpen.value || !referenciasFiltradas.value.length) return
  const ref = referenciasFiltradas.value[highlightIndex.value] ?? referenciasFiltradas.value[0]
  if (ref) onSelectReferencia(ref)
}

function onBlurBusqueda() {
  setTimeout(() => { 
    dropdownOpen.value = false 
  }, 200)
}

function cerrarDropdown() {
  dropdownOpen.value = false
}

// ─── Selección de referencia ───
function onSelectReferencia(ref) {
  // Verificar si ya está seleccionada
  if (referenciaYaSeleccionada(ref.id)) {
    mensajeDuplicado.value = `⚠️ La referencia "${ref.nro_referencia}" ya está seleccionada`
    setTimeout(() => {
      mensajeDuplicado.value = ''
    }, 3000)
    return
  }
  
  const referenciaData = {
    id_resolucion: ref.id,
    nro_referencia: ref.nro_referencia
  }
  
  emit('agregar-referencia', referenciaData)
  
  // Limpiar búsqueda y cerrar dropdown
  searchTerm.value = ''
  dropdownOpen.value = false
  referencias.value = []
  highlightIndex.value = -1
}

// ─── Búsqueda con debounce ───
let timeoutId = null
function onSearchInput() {
  clearTimeout(timeoutId)
  timeoutId = setTimeout(() => {
    cargarReferencias()
  }, 300)
}

// ─── Resetear cuando cambia la lista de seleccionados ───
watch(() => props.referenciasSeleccionadas, () => {
  // No resetear automáticamente, solo actualizar el estado visual
}, { deep: true })

// ─── Cargar años al montar ───
onMounted(async () => {
  await obtenerAnios()
})
</script>