<template>
  <div class="bg-white rounded-xl border border-gray-200">
    <div class="p-4 space-y-4">

      <!-- Datos generales -->
      <div>
        <h3 class="text-[12px] font-semibold text-gray-700 mb-2">Datos generales</h3>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

          <div class="sm:col-span-4">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Número de resolución *</label>
            <input
              v-model="form.detalle_general"
              type="text"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Categoria (Docentes Titulares / Temporales) -->
          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Categoria *</label>
            <select
              v-model="form.categoria"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null" disabled>Selecciona una categoria</option>
              <option value="Docentes Titulares">Docentes Titulares</option>
              <option value="Docentes Temporales">Docentes Temporales</option>
            </select>
          </div>

          <!-- Nivel - Guarda el texto, no el número -->
          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Nivel *</label>
            <select
              v-model="form.nivel"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null" disabled>Selecciona un nivel</option>
              <option value="PRIMER NIVEL">PRIMER NIVEL</option>
              <option value="SEGUNDO NIVEL">SEGUNDO NIVEL</option>
              <option value="TERCER NIVEL">TERCER NIVEL</option>
            </select>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Gestión *</label>
            <input
              v-model="form.gestion"
              type="text"
              placeholder="Ej. 2023"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Periodo</label>
            <select
              v-model="form.periodo"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null" disabled>Selecciona</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3 - Verano">3 - Verano</option>
              <option value="4 - Invierno">4 - Invierno</option>
            </select>
          </div>

          <!-- Docente -->
          <div class="relative sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Docente *</label>
            <div
              v-if="selectedDocente"
              class="w-full flex items-center justify-between px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg bg-gray-50"
            >
              <span class="truncate">
                {{ selectedDocente.apellidos }} {{ selectedDocente.nombres }} 
                <span class="text-gray-400 text-[11px]">({{ selectedDocente.codigo }})</span>
              </span>
              <button type="button" class="text-gray-400 hover:text-red-500 flex-shrink-0 ml-2" @click="onClearDocente">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div v-else>
              <input
                ref="inputDocenteRef"
                v-model="searchQuery"
                type="text"
                placeholder="Buscar docente..."
                class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @focus="dropdownOpen = true"
                @blur="onBlurBusqueda"
                @keydown.down.prevent="moverSeleccion(1)"
                @keydown.up.prevent="moverSeleccion(-1)"
                @keydown.enter.prevent="confirmarSeleccion"
                @keydown.esc="dropdownOpen = false"
              />
              <div
                v-if="dropdownOpen"
                class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
              >
                <div v-if="loadingDocentes" class="px-2.5 py-1.5 text-[12px] text-gray-400">Cargando...</div>
                <div v-else-if="!filteredDocentes.length" class="px-2.5 py-1.5 text-[12px] text-gray-400">Sin resultados</div>
                <button
                  v-for="(d, idx) in filteredDocentes"
                  :key="d.id ?? d.codigo"
                  type="button"
                  class="w-full text-left px-2.5 py-1.5 text-[12px] border-b border-gray-100 last:border-b-0"
                  :class="idx === highlightIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50'"
                  @mousedown.prevent="onSelectDocente(d)"
                  @mouseenter="highlightIndex = idx"
                >
                  {{ d.apellidos }} {{ d.nombres }}
                  <span class="text-gray-400">({{ d.codigo }})</span>
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Detalles</label>
            <input
              v-model="form.observacion"
              type="text"
              maxlength="300"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Observación</label>
            <input
              v-model="form.observacion2"
              type="text"
              maxlength="300"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <!-- BUSCADOR DE MATERIAS -->
      <BuscadorMaterias 
        :gestion="form.gestion"
        :periodo="form.periodo"
        :materiasSeleccionadas="form.materias"
        @agregar-materia="onAgregarMateria" 
      />

      <!-- Materias seleccionadas -->
      <div v-if="form.materias.length">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-[12px] font-semibold text-gray-700">Materias seleccionadas ({{ form.materias.length }})</h3>
          <button
            @click="form.materias = []"
            class="text-[11px] font-medium text-red-600 hover:text-red-700"
          >
            Limpiar todas
          </button>
        </div>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="(m, i) in form.materias"
            :key="i"
            class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-[12px] text-blue-700"
          >
            <span>{{ m.nombre_materia }}</span>
            <span v-if="m.cod_materia" class="text-blue-400 text-[10px]">({{ m.cod_materia }})</span>
            
            <input
              v-model="m.nota"
              type="text"
              inputmode="numeric"
              placeholder="Nota"
              class="w-14 px-1.5 py-0.5 text-[11px] border border-blue-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white text-center"
              @keypress="soloNumeros"
            />
            
            <button
              @click="form.materias.splice(i, 1)"
              class="text-blue-400 hover:text-red-500 ml-0.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Referencias -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-[12px] font-semibold text-gray-700">Referencias</h3>
        </div>

        <BuscadorReferencias 
          :referenciasSeleccionadas="form.referencias"
          @agregar-referencia="onAgregarReferencia" 
        />

        <div v-if="form.referencias.length" class="mt-3">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-[11px] font-medium text-gray-600">Referencias seleccionadas ({{ form.referencias.length }})</h4>
            <button
              @click="form.referencias = []"
              class="text-[11px] font-medium text-red-600 hover:text-red-700"
            >
              Limpiar todas
            </button>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="(r, i) in form.referencias"
              :key="i"
              class="inline-flex items-center gap-1.5 px-2 py-1 bg-green-50 border border-green-200 rounded text-[12px] text-green-700"
            >
              {{ r.nro_referencia }}
              <span v-if="r.id_resolucion" class="text-green-400 text-[10px]">(ID: {{ r.id_resolucion }})</span>
              <button
                @click="form.referencias.splice(i, 1)"
                class="text-green-400 hover:text-red-500 ml-0.5"
              >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </span>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="flex items-center gap-2 p-2.5 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[12px]">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ error }}
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 rounded-b-xl">
      <button
        @click="$emit('back')"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium text-gray-600 hover:text-gray-800 rounded-lg transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>

      <button
        :disabled="saving || !esValido"
        @click="$emit('guardar', formCopiado())"
        class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-medium rounded-lg transition-colors"
      >
        <svg v-if="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Guardar clasificación
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, watch } from 'vue'
import { useDocentesReportes } from '../composables/useDocentesReportes'
import BuscadorMaterias from './BuscadorMaterias.vue'
import BuscadorReferencias from './BuscadorReferencias.vue'

const props = defineProps({
  saving:        { type: Boolean, default: false },
  error:         { type: String, default: '' },
  archivoNombre: { type: String, default: '' },
  initial:       { type: Object, default: () => ({}) },
})

const emit = defineEmits(['guardar', 'back'])

// ─── Buscador de docente ───
const {
  loading: loadingDocentes,
  searchQuery,
  dropdownOpen,
  filteredDocentes,
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection,
} = useDocentesReportes()

fetchDocentes()

const inputDocenteRef = ref(null)
const highlightIndex = ref(-1)

watch(filteredDocentes, () => {
  highlightIndex.value = filteredDocentes.value.length ? 0 : -1
})

function moverSeleccion(delta) {
  if (!dropdownOpen.value) {
    dropdownOpen.value = true
    return
  }
  const total = filteredDocentes.value.length
  if (!total) return
  highlightIndex.value = (highlightIndex.value + delta + total) % total
}

function confirmarSeleccion() {
  if (!dropdownOpen.value || !filteredDocentes.value.length) return
  const docente = filteredDocentes.value[highlightIndex.value] ?? filteredDocentes.value[0]
  if (docente) onSelectDocente(docente)
}

function onBlurBusqueda() {
  setTimeout(() => { dropdownOpen.value = false }, 150)
}

function onSelectDocente(docente) {
  selectDocente(docente)
  form.cod_docente = docente.codigo
}

function onClearDocente() {
  clearSelection()
  form.cod_docente = null
  highlightIndex.value = -1
  setTimeout(() => inputDocenteRef.value?.focus(), 0)
}

// ─── Formulario ───
const form = reactive({
  cod_docente:     props.initial.cod_docente     ?? null,
  categoria:       props.initial.categoria       ?? null,  // Docentes Titulares / Temporales
  nivel:           props.initial.nivel           ?? null,  // PRIMER NIVEL, SEGUNDO NIVEL, TERCER NIVEL (texto)
  gestion:         props.initial.gestion         ?? '',
  periodo:         props.initial.periodo         ?? '',
  detalle_general: props.initial.detalle_general ?? '',
  observacion:     props.initial.observacion     ?? '',
  observacion2:    props.initial.observacion2    ?? '',
  materias:        props.initial.materias        ?? [],
  referencias:     props.initial.referencias     ?? [],
})

// ─── Validación de solo números para NOTA ───
function soloNumeros(event) {
  const charCode = event.which ? event.which : event.keyCode
  if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
    event.preventDefault()
  }
}

// ─── Manejo de materias ───
function onAgregarMateria(materiaData) {
  const existe = form.materias.some(m => m.cod_materia === materiaData.cod_materia)
  if (existe) {
    return
  }
  form.materias.push(materiaData)
}

// ─── Manejo de referencias ───
function onAgregarReferencia(referenciaData) {
  const existe = form.referencias.some(r => r.id_resolucion === referenciaData.id_resolucion)
  if (existe) {
    return
  }
  form.referencias.push(referenciaData)
}

const esValido = computed(() => {
  if (!form.cod_docente || !form.categoria || !form.nivel || !form.gestion) return false
  if (form.materias.some(m => !m.nombre_materia?.trim())) return false
  if (form.referencias.some(r => !r.nro_referencia?.trim())) return false
  return true
})

function formCopiado() {
  return JSON.parse(JSON.stringify(form))
}
</script>