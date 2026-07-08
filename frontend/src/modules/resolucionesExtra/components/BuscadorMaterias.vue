<template>
  <div class="bg-white rounded-xl border border-gray-200 p-4 relative">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-[12px] font-semibold text-gray-700">Buscador de Materias</h3>
      <span class="text-[11px] text-gray-400">Selecciona materias para asignar</span>
    </div>

    <!-- Buscador -->
    <div class="relative">
      <input
        ref="inputRef"
        v-model="searchTerm"
        type="text"
        placeholder="Buscar materia por nombre, código o sigla..."
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

    <!-- Indicador de filtros -->
    <div class="text-[11px] text-gray-400 mt-1">
      <span v-if="gestionActual">
        Gestión: <strong>{{ gestionActual }}</strong>
        <span v-if="periodoActual"> · Periodo: <strong>{{ periodoActual }}</strong></span>
        <span v-if="materiasFiltradas.length > 0" class="ml-2 text-blue-600">
          ({{ materiasFiltradas.length }} materias encontradas)
        </span>
      </span>
      <span v-else class="text-amber-600">⚠️ Selecciona una gestión en los datos generales</span>
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
      v-if="dropdownOpen && (materiasFiltradas.length || loading || !gestionActual)"
      class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
      style="position: absolute; left: 0;"
    >
      <!-- Mensaje cuando no hay gestión -->
      <div v-if="!gestionActual" class="px-3 py-4 text-center text-[12px] text-gray-400">
        <svg class="w-6 h-6 mx-auto text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Selecciona una gestión en los datos generales
      </div>

      <!-- Cargando -->
      <div v-else-if="loading" class="px-3 py-3 text-[12px] text-gray-400 text-center">
        <svg class="w-4 h-4 animate-spin inline mr-2" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Cargando materias...
      </div>

      <!-- Sin resultados -->
      <div v-else-if="!materiasFiltradas.length && searchTerm" class="px-3 py-3 text-[12px] text-gray-400 text-center">
        No se encontraron materias para "{{ searchTerm }}"
      </div>

      <!-- Lista de materias -->
      <div v-else-if="materiasFiltradas.length">
        <div class="sticky top-0 bg-gray-50 px-3 py-1.5 text-[10px] text-gray-400 border-b border-gray-100">
          {{ materiasFiltradas.length }} materias disponibles
        </div>
        <button
          v-for="(m, idx) in materiasFiltradas"
          :key="m.codigo"
          type="button"
          class="w-full text-left px-3 py-2 text-[12px] border-b border-gray-100 last:border-b-0 flex items-center justify-between hover:bg-gray-50 transition-colors"
          :class="[
            idx === highlightIndex ? 'bg-blue-50' : '',
            materiaYaSeleccionada(m.codigo) ? 'opacity-50 cursor-not-allowed' : ''
          ]"
          @mousedown.prevent="onSelectMateria(m)"
          @mouseenter="highlightIndex = idx"
          :disabled="materiaYaSeleccionada(m.codigo)"
        >
          <div class="flex-1 min-w-0">
            <span class="font-medium text-gray-800">{{ m.nombre }}</span>
            <span v-if="m.sigla" class="text-gray-400 ml-1">({{ m.sigla }})</span>
            <div class="text-[10px] text-gray-400 truncate">
              Código: {{ m.codigo }} · Periodo: {{ m.periodo }}
              <span v-if="m.nombre_plan"> · Plan: {{ m.nombre_plan }}</span>
            </div>
          </div>
          <!-- Círculo a la derecha -->
          <span class="ml-3 flex-shrink-0">
            <svg v-if="materiaYaSeleccionada(m.codigo)" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <circle cx="12" cy="12" r="9" stroke="currentColor"/>
            </svg>
          </span>
        </button>
      </div>
    </div>

    <!-- Mensaje cuando no hay gestión (fuera del dropdown) -->
    <div v-if="!gestionActual && !dropdownOpen" class="text-center py-4 text-[12px] text-gray-400 border border-dashed border-gray-200 rounded-lg mt-2">
      <svg class="w-6 h-6 mx-auto text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Selecciona una gestión en los datos generales para buscar materias
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, toRefs } from 'vue'
import { useMaterias } from '../composables/useMaterias'

const props = defineProps({
  gestion: { type: String, default: '' },
  periodo: { type: String, default: '' },
  materiasSeleccionadas: { type: Array, default: () => [] }
})

const emit = defineEmits(['agregar-materia'])

const { loading, materias, periodos, listar, obtenerPeriodos } = useMaterias()

const searchTerm = ref('')
const dropdownOpen = ref(false)
const highlightIndex = ref(-1)
const inputRef = ref(null)
const mensajeDuplicado = ref('')

const gestionActual = computed(() => props.gestion)
const periodoActual = computed(() => props.periodo)

const materiasFiltradas = computed(() => {
  return materias.value
})

// Verificar si una materia ya está seleccionada
function materiaYaSeleccionada(codigo) {
  return props.materiasSeleccionadas.some(m => m.cod_materia === codigo)
}

// ─── Cargar materias al hacer focus ───
async function onFocus() {
  if (!gestionActual.value) {
    dropdownOpen.value = true
    return
  }
  
  dropdownOpen.value = true
  await cargarMaterias()
}

// ─── Cargar materias con filtros actuales ───
async function cargarMaterias() {
  if (!gestionActual.value) {
    materias.value = []
    return
  }
  
  const params = {
    anio: gestionActual.value
  }
  
  if (periodoActual.value) {
    params.periodo = periodoActual.value
  }
  
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
  const total = materiasFiltradas.value.length
  if (!total) return
  highlightIndex.value = (highlightIndex.value + delta + total) % total
}

function confirmarSeleccion() {
  if (!dropdownOpen.value || !materiasFiltradas.value.length) return
  const materia = materiasFiltradas.value[highlightIndex.value] ?? materiasFiltradas.value[0]
  if (materia) onSelectMateria(materia)
}

function onBlurBusqueda() {
  setTimeout(() => { 
    dropdownOpen.value = false 
  }, 200)
}

function cerrarDropdown() {
  dropdownOpen.value = false
}

// ─── Selección de materia ───
function onSelectMateria(materia) {
  // Verificar si ya está seleccionada
  if (materiaYaSeleccionada(materia.codigo)) {
    mensajeDuplicado.value = `⚠️ La materia "${materia.nombre}" ya está seleccionada`
    // Ocultar mensaje después de 3 segundos
    setTimeout(() => {
      mensajeDuplicado.value = ''
    }, 3000)
    return
  }
  
  const materiaData = {
    cod_materia: materia.codigo,
    nombre_materia: materia.nombre,
    cod_plan: materia.plan || null,
    nombre_plan: materia.nombre_plan || null,
    nota: null,
    detalle: ''
  }
  
  emit('agregar-materia', materiaData)
  
  // Limpiar búsqueda y cerrar dropdown
  searchTerm.value = ''
  dropdownOpen.value = false
  materias.value = []
  highlightIndex.value = -1
}

// ─── Búsqueda con debounce ───
let timeoutId = null
function onSearchInput() {
  clearTimeout(timeoutId)
  timeoutId = setTimeout(() => {
    if (gestionActual.value) {
      cargarMaterias()
    }
  }, 300)
}

// ─── Resetear cuando cambia gestión/periodo ───
watch(() => [props.gestion, props.periodo], () => {
  searchTerm.value = ''
  materias.value = []
  dropdownOpen.value = false
  highlightIndex.value = -1
  mensajeDuplicado.value = ''
})

// ─── Cargar periodos al montar ───
onMounted(async () => {
  await obtenerPeriodos()
})
</script>