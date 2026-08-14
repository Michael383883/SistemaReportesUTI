<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
    <!-- Header oscuro, igual estilo que el modal "Nuevo usuario" -->
    <div class="bg-slate-900 px-5 py-2 rounded-t-2xl">
      <h3 class="text-[15px] font-semibold text-white">Datos generales</h3>
    </div>

    <div class="p-5 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

        <!-- Tipo o Número de Documento -->
        <div class="sm:col-span-2">
          <label class="block text-[12px] font-medium text-slate-900 mb-1">
            Tipo o Número de Documento
          </label>
          <input
            v-model="form.tipo_documento"
            type="text"
            placeholder="Insertar tipo de documento o número o RCF N "
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          />
        </div>

        <!-- Descripción general -->
        <div class="sm:col-span-2">
          <label class="block text-[12px] font-medium text-slate-900 mb-1">Descripción general</label>
          <input
            v-model="form.detalle_general"
            type="text"
            placeholder="Ej. Observación adicional, número de folio, etc."
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          />
        </div>

        <!-- Categoria: combobox (seleccionar, buscar o crear una nueva) -->
        <div class="relative">
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Categoria</label>

          <div class="relative">
            <input
              v-model="form.categoria"
              type="text"
              placeholder="Selecciona o escribe una categoria"
              class="w-full px-3 py-2.5 pr-9 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
              @focus="categoriaDropdownOpen = true"
              @input="categoriaDropdownOpen = true"
              @blur="onBlurCategoria"
            />
            <button
              type="button"
              tabindex="-1"
              @mousedown.prevent="toggleCategoriaDropdown"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-800"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
          </div>

          <div
            v-if="categoriaDropdownOpen"
            class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden max-h-56 overflow-y-auto"
          >
            <button
              v-for="opcion in opcionesCategoriaFiltradas"
              :key="opcion"
              type="button"
              @mousedown.prevent="seleccionarCategoria(opcion)"
              class="w-full text-left px-3 py-2 text-[13px] text-slate-700 hover:bg-orange-50 transition-colors"
              :class="form.categoria === opcion ? 'bg-orange-50 text-orange-600 font-medium' : ''"
            >
              {{ opcion }}
            </button>

            <button
              v-if="mostrarCrearCategoria"
              type="button"
              @mousedown.prevent="seleccionarCategoria(form.categoria)"
              class="w-full text-left px-3 py-2 text-[13px] text-amber-600 font-medium hover:bg-amber-50 transition-colors border-t border-gray-100"
            >
              + Crear "{{ form.categoria.trim() }}"
            </button>

            <div v-if="!opcionesCategoriaFiltradas.length && !mostrarCrearCategoria" class="px-3 py-2 text-[12px] text-gray-400 italic">
              Sin coincidencias
            </div>
          </div>
        </div>

        <!-- Nivel -->
        <div>
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Nivel</label>
          <select
            v-model="form.nivel"
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          >
            <option value="">Sin nivel</option>
            <option value="Primer nivel">Primer nivel</option>
            <option value="Segundo nivel">Segundo nivel</option>
            <option value="Tercer nivel">Tercer nivel</option>
          </select>
        </div>

        <div>
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Gestión *</label>
          <input
            v-model="form.gestion"
            type="text"
            inputmode="numeric"
            maxlength="4"
            placeholder="Ej. 2023"
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
            @keypress="soloNumerosGestion"
            @input="onInputGestion"
            @paste="onPasteGestion"
          />
        </div>

        <!-- Periodo -->
        <div class="relative">
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Periodo *</label>

          <div class="relative">
            <input
              v-model="form.periodo"
              type="text"
              placeholder="Selecciona o escribe"
              class="w-full px-3 py-2.5 pr-9 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
              @focus="periodoDropdownOpen = true"
              @blur="onBlurPeriodo"
            />
            <button
              type="button"
              tabindex="-1"
              @mousedown.prevent="togglePeriodoDropdown"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
          </div>

          <div
            v-if="periodoDropdownOpen"
            class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
          >
            <button
              v-for="opcion in ['1', '2', '3', '4']"
              :key="opcion"
              type="button"
              @mousedown.prevent="seleccionarPeriodo(opcion)"
              class="w-full text-left px-3 py-2 text-[13px] text-slate-700 hover:bg-orange-50 transition-colors"
              :class="form.periodo === opcion ? 'bg-orange-50 text-orange-600 font-medium' : ''"
            >
              {{ opcion }}
            </button>
          </div>
        </div>

        <!-- Docente general (por defecto) -->
        <div class="relative sm:col-span-2">
          <label class="block text-[12px] font-medium text-slate-800 mb-1">
            Docente (por defecto)
            <span class="text-gray-700 font-normal">— se asigna a cada materia nueva, editable por materia</span>
          </label>
          <div
            v-if="selectedDocente"
            class="w-full flex items-center justify-between px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl bg-gray-50"
          >
            <span class="truncate">
              {{ selectedDocente.apellidos }} {{ selectedDocente.nombres }}
              <span class="text-gray-700 text-[12px]">({{ selectedDocente.codigo }})</span>
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
              class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
              @focus="dropdownOpen = true"
              @blur="onBlurBusqueda"
              @keydown.down.prevent="moverSeleccion(1)"
              @keydown.up.prevent="moverSeleccion(-1)"
              @keydown.enter.prevent="confirmarSeleccion"
              @keydown.esc="dropdownOpen = false"
            />
            <div
              v-if="dropdownOpen"
              class="absolute z-30 mt-1 w-full max-h-72 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg"
            >
              <div v-if="loadingDocentes" class="px-3 py-2 text-[12px] text-gray-400">Cargando...</div>

              <template v-else>
                <div v-if="!docentesOrdenados.length" class="px-3 py-2 text-[12px] text-gray-400">Sin resultados</div>

                <button
                  v-for="(d, idx) in docentesOrdenados"
                  :key="d.id ?? d.codigo"
                  type="button"
                  class="w-full text-left px-3 py-2 text-[12px] border-b border-gray-100 last:border-b-0"
                  :class="[
                    idx === highlightIndex ? 'bg-orange-50 text-orange-700' : 'hover:bg-gray-50',
                    esPorDesignar(d) ? 'font-medium text-slate-700' : ''
                  ]"
                  @mousedown.prevent="onSelectDocente(d)"
                  @mouseenter="highlightIndex = idx"
                >
                  {{ d.apellidos }} {{ d.nombres }}
                  <span class="text-gray-400">({{ d.codigo }})</span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Observación 1</label>
          <input
            v-model="form.observacion"
            type="text"
            maxlength="300"
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          />
        </div>

        <div>
          <label class="block text-[12px] font-medium text-slate-800 mb-1">Observación 2</label>
          <input
            v-model="form.observacion2"
            type="text"
            maxlength="300"
            class="w-full px-3 py-2.5 text-[13px] bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useCategorias } from '../../composables/useCategorias'

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

// ─── Gestión: solo acepta año numérico (1950, 2026, etc.) ───
function soloNumerosGestion(event) {
  const charCode = event.which ? event.which : event.keyCode
  if (charCode < 48 || charCode > 57) {
    event.preventDefault()
  }
}

function onInputGestion(event) {
  // Filtra cualquier carácter no numérico (cubre autocompletado, IME, etc.)
  // y limita a 4 dígitos, ya que un año siempre tiene ese formato.
  const soloDigitos = event.target.value.replace(/\D/g, '').slice(0, 4)
  form.gestion = soloDigitos
  event.target.value = soloDigitos
}

function onPasteGestion(event) {
  event.preventDefault()
  const texto = (event.clipboardData || window.clipboardData).getData('text')
  form.gestion = texto.replace(/\D/g, '').slice(0, 4)
}

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

// ─── Combobox de Categoria (seleccionar / buscar / crear) ───
const { categorias: opcionesCategoria, loading: loadingCategorias, cargarCategorias, crearCategoria } = useCategorias()

onMounted(() => {
  cargarCategorias()
})

const categoriaDropdownOpen = ref(false)

const opcionesCategoriaFiltradas = computed(() => {
  const q = (form.categoria || '').trim().toLowerCase()
  if (!q) return opcionesCategoria.value
  return opcionesCategoria.value.filter(c => c.toLowerCase().includes(q))
})

// Muestra "+ Crear..." solo si lo escrito no coincide exactamente con
// ninguna categoría ya existente (comparación case-insensitive)
const mostrarCrearCategoria = computed(() => {
  const q = (form.categoria || '').trim()
  if (!q) return false
  return !opcionesCategoria.value.some(c => c.toLowerCase() === q.toLowerCase())
})

function toggleCategoriaDropdown() {
  categoriaDropdownOpen.value = !categoriaDropdownOpen.value
}

async function onBlurCategoria() {
  const valor = (form.categoria || '').trim()
  if (valor) {
    try {
      await crearCategoria(valor)
    } catch (e) {
      // opcional: manejar error
    }
  }
  setTimeout(() => { categoriaDropdownOpen.value = false }, 150)
}

async function seleccionarCategoria(valor) {
  const v = (valor || '').trim()
  form.categoria = v
  if (v) {
    try {
      await crearCategoria(v)
    } catch (e) {
      // opcional: mostrar un toast/error si falla el guardado
    }
  }
  categoriaDropdownOpen.value = false
}

// ─── Buscador de docente general (docente "por defecto") ───
const inputDocenteRef = ref(null)
const highlightIndex = ref(-1)

// "POR DESIGNAR DOCENTE" / "POR DESIGNAR AUXILIAR" son docentes reales
// (con código propio) que sirven como placeholder cuando la persona todavía
// no está identificada. Los fijamos siempre primero en la lista para que,
// con un solo click al abrir el buscador, estén a la mano.
function esPorDesignar(d) {
  const nombre = `${d.apellidos ?? ''} ${d.nombres ?? ''}`.toUpperCase()
  return nombre.includes('POR DESIGNAR')
}

const docentesOrdenados = computed(() => {
  const lista = props.filteredDocentes
  const fijos = lista.filter(esPorDesignar)
  const resto = lista.filter(d => !esPorDesignar(d))
  return [...fijos, ...resto]
})

watch(docentesOrdenados, (list) => {
  highlightIndex.value = list.length ? 0 : -1
})

function moverSeleccion(delta) {
  if (!dropdownOpen.value) {
    dropdownOpen.value = true
    return
  }
  const total = docentesOrdenados.value.length
  if (!total) return
  highlightIndex.value = (highlightIndex.value + delta + total) % total
}

function confirmarSeleccion() {
  if (!dropdownOpen.value || !docentesOrdenados.value.length) return
  const docente = docentesOrdenados.value[highlightIndex.value] ?? docentesOrdenados.value[0]
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