<template>
  <div class="bg-white rounded-xl border border-gray-200">
    <div class="p-4 space-y-4">
      <h3 class="text-[13px] font-semibold text-slate-800 mb-1">Datos generales</h3>

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

        <!-- Tipo o Número de Documento -->
        <div class="sm:col-span-2">
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">
            Tipo o Número de Documento *
          </label>
          <input
            v-model="form.tipo_documento"
            type="text"
            placeholder="Insertar tipo de documento o número"
            class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Descripción general -->
        <div class="sm:col-span-2">
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Descripción general</label>
          <input
            v-model="form.detalle_general"
            type="text"
            placeholder="Ej. Observación adicional, número de folio, etc."
            class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Categoria -->
        <div class="relative">
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Categoria *</label>

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

          <div
            v-if="categoriaDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
          >
            <button
              v-for="opcion in opcionesCategoria"
              :key="opcion"
              type="button"
              @mousedown.prevent="seleccionarCategoria(opcion)"
              class="w-full text-left px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 transition-colors"
              :class="form.categoria === opcion ? 'bg-blue-50 text-blue-600 font-medium' : ''"
            >
              {{ opcion }}
            </button>
          </div>
        </div>

        <!-- Nivel -->
        <div>
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Nivel</label>
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
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Gestión *</label>
          <input
            v-model="form.gestion"
            type="text"
            placeholder="Ej. 2023"
            class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Periodo -->
        <div class="relative">
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Periodo</label>

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

          <div
            v-if="periodoDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
          >
            <button
              v-for="opcion in ['1', '2', '3', '4']"
              :key="opcion"
              type="button"
              @mousedown.prevent="seleccionarPeriodo(opcion)"
              class="w-full text-left px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 transition-colors"
              :class="form.periodo === opcion ? 'bg-blue-50 text-blue-600 font-medium' : ''"
            >
              {{ opcion }}
            </button>
          </div>
        </div>

        <!-- Docente general (por defecto) -->
        <div class="relative sm:col-span-2">
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">
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
            <button type="button" class="text-gray-400 hover:text-red-500 flex-shrink-0 ml-2" @click="$emit('clear-docente')">
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
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Observación 1</label>
          <input
            v-model="form.observacion"
            type="text"
            maxlength="300"
            class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Observación 2</label>
          <input
            v-model="form.observacion2"
            type="text"
            maxlength="300"
            class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },
  loadingDocentes: { type: Boolean, default: false },
  filteredDocentes: { type: Array, default: () => [] },
  selectedDocente: { type: Object, default: null },
})

const emit = defineEmits(['select-docente', 'clear-docente'])

// Sincronizados con el composable de búsqueda de docente que vive en el padre
// (ClasificacionForm.vue), para que la lista filtrada se recalcule ahí mismo.
const searchQuery = defineModel('searchQuery', { default: '' })
const dropdownOpen = defineModel('dropdownOpen', { default: false })

const { form } = props

// ─── Combobox de Periodo ───
const periodoDropdownOpen = ref(false)

function togglePeriodoDropdown() {
  periodoDropdownOpen.value = !periodoDropdownOpen.value
}

function onBlurPeriodo() {
  setTimeout(() => { periodoDropdownOpen.value = false }, 150)
}

function seleccionarPeriodo(valor) {
  form.periodo = valor
  periodoDropdownOpen.value = false
}

// ─── Combobox de Categoria ───
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
  setTimeout(() => { categoriaDropdownOpen.value = false }, 150)
}

function seleccionarCategoria(valor) {
  form.categoria = valor
  categoriaDropdownOpen.value = false
}

// ─── Buscador de docente general (docente "por defecto") ───
const inputDocenteRef = ref(null)
const highlightIndex = ref(-1)

watch(() => props.filteredDocentes, (list) => {
  highlightIndex.value = list.length ? 0 : -1
})

function moverSeleccion(delta) {
  if (!dropdownOpen.value) {
    dropdownOpen.value = true
    return
  }
  const total = props.filteredDocentes.length
  if (!total) return
  highlightIndex.value = (highlightIndex.value + delta + total) % total
}

function confirmarSeleccion() {
  if (!dropdownOpen.value || !props.filteredDocentes.length) return
  const docente = props.filteredDocentes[highlightIndex.value] ?? props.filteredDocentes[0]
  if (docente) onSelectDocente(docente)
}

function onBlurBusqueda() {
  setTimeout(() => { dropdownOpen.value = false }, 150)
}

function onSelectDocente(docente) {
  emit('select-docente', docente)
  highlightIndex.value = -1
}
</script>