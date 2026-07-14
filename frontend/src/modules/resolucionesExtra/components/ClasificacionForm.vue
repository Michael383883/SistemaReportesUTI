<template>
  <div class="bg-white rounded-xl border border-gray-200">
    <div class="p-4 space-y-4">

      <!-- Datos generales -->
      <div>
        <h3 class="text-[12px] font-semibold text-gray-700 mb-2">Datos generales</h3>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

          <!-- Tipo o Número de Documento -->
          <div class="sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">
              Tipo o Número de Documento *
            </label>
            <input
              v-model="form.tipo_documento"
              type="text"
              placeholder="Insertar tipo de documento o número"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Descripción general (antes: detalle_general del documento) -->
          <div class="sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Descripción general</label>
            <input
              v-model="form.detalle_general"
              type="text"
              placeholder="Ej. Observación adicional, número de folio, etc."
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Categoria (Docentes Titulares / Temporales) -->
          <div class="relative">
  <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Categoria *</label>

  <div class="relative">
    <input
      v-model="form.categoria"
      type="text"
      placeholder="Selecciona o escribe una categoria"
      class="w-full px-2.5 py-1.5 pr-8 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      @focus="categoriaDropdownOpen = true"
      @blur="onBlurCategoria"
    />
    <button
      type="button"
      tabindex="-1"
      @mousedown.prevent="toggleCategoriaDropdown"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>
  </div>

  <!-- Dropdown -->
  <div
    v-if="categoriaDropdownOpen"
    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
  >
    <button
      v-for="opcion in opcionesCategoria"
      :key="opcion"
      type="button"
      @mousedown.prevent="seleccionarCategoria(opcion)"
      class="w-full text-left px-3 py-2 text-[13px] text-gray-700 hover:bg-blue-50 transition-colors"
      :class="form.categoria === opcion ? 'bg-blue-50 text-blue-600 font-medium' : ''"
    >
      {{ opcion }}
    </button>
  </div>
</div>

          <!-- Nivel - Guarda el texto, no el número -->
          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Nivel</label>
            <select
              v-model="form.nivel"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Sin nivel</option>
              <option value="Primer nivel">Primer nivel</option>
              <option value="Segundo nivel">Segundo nivel</option>
              <option value="Tercer nivel">Tercer nivel</option>
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

          <div class="relative">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Periodo</label>

            <div class="relative">
              <input
                v-model="form.periodo"
                type="text"
                placeholder="Selecciona o escribe"
                class="w-full px-2.5 py-1.5 pr-8 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @focus="periodoDropdownOpen = true"
                @blur="onBlurPeriodo"
              />
              <button
                type="button"
                tabindex="-1"
                @mousedown.prevent="togglePeriodoDropdown"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>

            <!-- Dropdown -->
            <div
              v-if="periodoDropdownOpen"
              class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
            >
              <button
                v-for="opcion in ['1', '2', '3', '4']"
                :key="opcion"
                type="button"
                @mousedown.prevent="seleccionarPeriodo(opcion)"
                class="w-full text-left px-3 py-2 text-[13px] text-gray-700 hover:bg-blue-50 transition-colors"
                :class="form.periodo === opcion ? 'bg-blue-50 text-blue-600 font-medium' : ''"
              >
                {{ opcion }}
              </button>
            </div>
          </div>

          <!-- Docente general (por defecto) -->
          <div class="relative sm:col-span-2">
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">
              Docente (por defecto)
              <span class="text-gray-400 font-normal">— se asigna a cada materia nueva, editable por materia</span>
            </label>
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
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Observación 1</label>
            <input
              v-model="form.observacion"
              type="text"
              maxlength="300"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Observación 2</label>
            <input
              v-model="form.observacion2"
              type="text"
              maxlength="300"
              class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <!-- Materias -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-[12px] font-semibold text-gray-700">Materias</h3>

          <!-- checkbox "No regenta materia en la FCE" -->
          <label class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 cursor-pointer select-none">
            <input
              type="checkbox"
              :checked="noRegentaFCE"
              @change="toggleNoRegenta"
              class="w-3.5 h-3.5 accent-blue-600"
            />
            No regenta materia en la FCE
          </label>
        </div>

        <!-- BUSCADOR DE MATERIAS (oculto si está marcado "no regenta") -->
        <BuscadorMaterias
                v-if="!noRegentaFCE"
                :docente="form.cod_docente"
                :gestion="form.gestion"
                :periodo="form.periodo"
                :materiasSeleccionadas="form.materias"
                @agregar-materia="onAgregarMateria"
              />

        <!-- Materias seleccionadas -->
        <div v-if="form.materias.length && !noRegentaFCE" class="flex flex-wrap gap-2 mt-2">
          <div
            v-for="(m, i) in form.materias"
            :key="i"
            class="flex flex-col gap-1.5 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-[12px] text-blue-700 min-w-[230px]"
          >
            <!-- Línea 1: nombre materia + nota + eliminar -->
            <div class="flex items-center justify-between gap-2">
              <span class="truncate">
                {{ m.nombre_materia }}
                <span v-if="m.cod_materia" class="text-blue-400 text-[10px]">({{ m.cod_materia }})</span>
                <span v-if="m.grupo" class="text-blue-500 text-[10px] font-semibold ml-1">Grupo {{ m.grupo }}</span>
              </span>

              <button
                @click="form.materias.splice(i, 1)"
                class="text-blue-400 hover:text-red-500 flex-shrink-0"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model="m.nota"
                type="text"
                inputmode="numeric"
                placeholder="Nota"
                class="w-14 px-1.5 py-0.5 text-[11px] border rounded focus:outline-none focus:ring-1 bg-white text-center"
                :class="notaInvalida(m)
                  ? 'border-red-400 focus:ring-red-500'
                  : 'border-blue-300 focus:ring-blue-500'"
                @keypress="soloNumeros"
              />
            </div>

            <!-- Línea 2: docente asignado a ESTA materia -->
            <div class="pt-1.5 border-t border-blue-200/70">
              <div v-if="docenteEditIndex !== i" class="flex items-center justify-between gap-2">
                <span class="text-[11px] leading-tight">
                  Docente:
                  <strong v-if="m.docente">{{ m.docente.apellidos }} {{ m.docente.nombres }}</strong>
                  <span v-else class="text-red-500 font-medium">Sin asignar</span>
                </span>
                <button
                  type="button"
                  class="text-[10px] text-blue-500 hover:text-blue-700 underline flex-shrink-0"
                  @mousedown.prevent="abrirEdicionDocente(i)"
                >
                  cambiar
                </button>
              </div>

              <div v-else class="relative">
                <input
                  ref="inputMateriaDocenteRef"
                  v-model="searchQueryMateria"
                  type="text"
                  placeholder="Buscar docente..."
                  class="w-full px-2 py-1 text-[11px] border border-blue-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                  @blur="onBlurMateriaDocente"
                  @keydown.esc="cerrarEdicionDocente"
                />
                <div
                  class="absolute z-10 mt-1 w-56 max-h-40 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
                >
                  <div v-if="loadingDocentesMateria" class="px-2 py-1 text-[11px] text-gray-400">Cargando...</div>
                  <div v-else-if="!filteredDocentesMateria.length" class="px-2 py-1 text-[11px] text-gray-400">Sin resultados</div>
                  <button
                    v-for="d in filteredDocentesMateria"
                    :key="d.id ?? d.codigo"
                    type="button"
                    class="w-full text-left px-2 py-1 text-[11px] hover:bg-blue-50"
                    @mousedown.prevent="asignarDocenteMateria(i, d)"
                  >
                    {{ d.apellidos }} {{ d.nombres }}
                    <span class="text-gray-400">({{ d.codigo }})</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Aviso: nota no válida (debajo de la card) -->
            <div v-if="notaInvalida(m)" class="text-[10px] text-red-600 font-medium pt-0.5">
              ⚠️ Nota no válida
            </div>
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
          @click="$emit('guardar', formCopiado(), asignaAGrupos)"
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
import { reactive, computed, ref, watch, nextTick } from 'vue'
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

// Determina si, tras guardar, corresponde llamar a aplicarEnGrupos().
// Reglas:
//  - Si es "no regenta materia en la FCE" -> NO se toca GRUPOS.
//  - Si no hay materias -> NO se toca GRUPOS.
//  - Si hay materias pero les falta cod_plan, cod_materia o grupo -> NO se
//    puede cruzar contra GRUPOS, así que tampoco se aplica.
const asignaAGrupos = computed(() => {
  if (noRegentaFCE.value) return false
  if (form.materias.length === 0) return false
  return form.materias.every(m => m.cod_materia && m.cod_plan && m.grupo)
})

// ─── Combobox de Periodo ───
const periodoDropdownOpen = ref(false)

function togglePeriodoDropdown() {
  periodoDropdownOpen.value = !periodoDropdownOpen.value
}

function onBlurPeriodo() {
  setTimeout(() => {
    periodoDropdownOpen.value = false
  }, 150)
}

function seleccionarPeriodo(valor) {
  form.periodo = valor
  periodoDropdownOpen.value = false
}


// ─── Combobox de Categoria (editable, con sugerencias fijas) ───
const opcionesCategoria = [
  'Docentes Titulares',
  'Docentes Temporales',
  'Examen de suficiencia',
  'Acefala',
  'Sin Examen de suficiencia',
]

const categoriaDropdownOpen = ref(false)

function toggleCategoriaDropdown() {
  categoriaDropdownOpen.value = !categoriaDropdownOpen.value
}

function onBlurCategoria() {
  setTimeout(() => {
    categoriaDropdownOpen.value = false
  }, 150)
}

function seleccionarCategoria(valor) {
  form.categoria = valor
  categoriaDropdownOpen.value = false
}

// ─── Buscador de docente GENERAL (docente "por defecto") ───
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

// ─── Buscador de docente POR MATERIA (asignación individual) ───
// Se usa una segunda instancia del composable para no interferir con
// la búsqueda/selección del docente general de arriba.
const {
  loading: loadingDocentesMateria,
  searchQuery: searchQueryMateria,
  filteredDocentes: filteredDocentesMateria,
  fetchDocentes: fetchDocentesMateria,
} = useDocentesReportes()

fetchDocentesMateria()

const docenteEditIndex = ref(null)   // índice de la materia que se está editando (null = ninguna)
const inputMateriaDocenteRef = ref(null)

function abrirEdicionDocente(i) {
  docenteEditIndex.value = i
  searchQueryMateria.value = ''
  nextTick(() => {
    // cuando hay varias materias, inputMateriaDocenteRef es un array de refs (v-for)
    const el = Array.isArray(inputMateriaDocenteRef.value)
      ? inputMateriaDocenteRef.value[0]
      : inputMateriaDocenteRef.value
    el?.focus()
  })
}

function cerrarEdicionDocente() {
  docenteEditIndex.value = null
}

function onBlurMateriaDocente() {
  setTimeout(() => { docenteEditIndex.value = null }, 150)
}

function asignarDocenteMateria(i, docente) {
  form.materias[i].docente = {
    cod_docente: docente.codigo,
    nombres: docente.nombres,
    apellidos: docente.apellidos,
  }
  docenteEditIndex.value = null
}

// ─── Formulario ───
const form = reactive({
  cod_docente:     props.initial.cod_docente     ?? null,
  categoria:       props.initial.categoria       ?? '',  // Docentes Titulares / Temporales
  nivel:           props.initial.nivel           ?? '',  // PRIMER NIVEL, SEGUNDO NIVEL, TERCER NIVEL (texto)
  gestion:         props.initial.gestion         ?? '',
  periodo:         props.initial.periodo         ?? '',
  tipo_documento:  props.initial.tipo_documento  ?? null,  // <-- nuevo
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

// ─── Validación de NOTA (no debe ser mayor a 100) ───
// Solo verifica y muestra el aviso debajo de la card; no bloquea el guardado.
function notaInvalida(m) {
  if (m.nota === null || m.nota === undefined || m.nota === '') return false
  const valor = Number(m.nota)
  if (Number.isNaN(valor)) return false
  return valor > 100
}

// ─── "No regenta materia en la FCE" ───
// Se guarda como una "materia" más (sin cod_materia), reutilizando la misma
// tabla/columna que ya existe. No se agrega ningún campo nuevo en la BD.
const NO_REGENTA_LABEL = 'NO REGENTA MATERIA EN LA FCE'

const noRegentaFCE = computed(() =>
  form.materias.length === 1 &&
  form.materias[0].nombre_materia === NO_REGENTA_LABEL &&
  !form.materias[0].cod_materia
)

function toggleNoRegenta(event) {
  const checked = event.target.checked
  if (checked) {
    form.materias = [{
      cod_materia: null,
      nombre_materia: NO_REGENTA_LABEL,
      cod_plan: null,
      grupo: null,          // <-- nuevo
      nota: null,
      detalle: null,
      docente: null,
    }]
  } else {
    form.materias = []
  }
}

// ─── Manejo de materias ───
function onAgregarMateria(materiaData) {
  const existe = form.materias.some(m => m.cod_materia === materiaData.cod_materia)
  if (existe) {
    return
  }
  form.materias.push({
    ...materiaData,
    grupo: materiaData.grupo ?? null,   // <-- asegura que siempre exista la llave
    docente: selectedDocente.value
      ? {
          cod_docente: selectedDocente.value.codigo,
          nombres: selectedDocente.value.nombres,
          apellidos: selectedDocente.value.apellidos,
        }
      : null,
  })
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
  if (!form.categoria || !form.tipo_documento || !form.gestion) return false
  if (form.materias.some(m => !m.nombre_materia?.trim())) return false
  if (form.referencias.some(r => !r.nro_referencia?.trim())) return false

  if (noRegentaFCE.value || form.materias.length === 0) {
    // Sin materias registradas (ya sea "no regenta" o un documento simple
    // como un certificado): basta con el docente general del formulario.
    if (!form.cod_docente) return false
  } else {
    // Hay materias reales: cada una debe tener su propio docente asignado.
    if (form.materias.some(m => !m.docente?.cod_docente)) return false
  }
  return true
})

function formCopiado() {
  return JSON.parse(JSON.stringify(form))
}
</script>