<template>
  <div class="bg-white rounded-xl border border-gray-200">

    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100">
      <h2 class="text-base font-semibold text-gray-800">Detalle de Materias Acéfalas</h2>
      <p class="text-xs text-gray-400 mt-0.5">
        Agrega las filas del PDF. Busca el docente, completa los campos y presiona "+ Agregar fila".
      </p>
    </div>

    <div class="p-6 space-y-6">

      <!-- ─── FORMULARIO DE FILA NUEVA ─── -->
      <div class="border border-gray-200 rounded-xl p-4 space-y-4 bg-gray-50">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nueva fila</p>

        <!-- ── BUSCADOR DE DOCENTE (estilo archivo 2) ── -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            Docente <span class="text-red-500">*</span>
          </label>

          <div class="relative" ref="docenteContainerRef">

            <!-- Input con botón "Todos" -->
            <div
              class="flex items-center bg-white border rounded-xl overflow-hidden transition-all duration-200"
              :class="[
                docenteInputFocused
                  ? 'border-blue-500 ring-2 ring-blue-100'
                  : erroresFila.docente
                    ? 'border-red-300 ring-2 ring-red-100'
                    : 'border-gray-300'
              ]"
            >
              <!-- Botón "Todos" -->
              <button
                type="button"
                class="flex items-center gap-1.5 px-3 py-2.5 text-gray-500 text-sm font-medium cursor-pointer whitespace-nowrap shrink-0 hover:text-gray-700 hover:bg-gray-50 transition-colors border-r border-gray-200"
                :class="{ 'text-gray-700 bg-gray-50': dropdownOpen }"
                @click="toggleDropdown"
                title="Ver todos los docentes"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Todos</span>
                <svg
                  class="w-3.5 h-3.5 transition-transform duration-200"
                  :class="{ 'rotate-180': dropdownOpen }"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                >
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </button>

              <!-- Campo de búsqueda -->
              <div class="flex-1 flex items-center px-2.5 gap-2">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                  ref="docenteInputRef"
                  v-model="searchQuery"
                  type="text"
                  placeholder="Buscar por nombre o código..."
                  autocomplete="off"
                  class="flex-1 bg-transparent border-none outline-none text-sm text-gray-800 py-2.5 min-w-0 placeholder-gray-400"
                  @focus="onDocenteFocus"
                  @blur="docenteInputFocused = false"
                  @input="onDocenteInput"
                />
                <!-- Spinner -->
                <svg v-if="loading" class="w-4 h-4 text-blue-400 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <!-- Limpiar -->
                <button
                  v-if="searchQuery && !loading"
                  type="button"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                  @click="onLimpiarDocente"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Dropdown -->
            <Transition
              enter-active-class="transition-all duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1.5"
              leave-active-class="transition-all duration-150 ease-in"
              leave-to-class="opacity-0 -translate-y-1.5"
            >
              <div
                v-if="dropdownOpen"
                class="absolute top-full left-0 right-0 mt-1.5 z-50 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden"
              >
                <!-- Cargando -->
                <div v-if="loading" class="flex items-center gap-2 px-4 py-4 text-sm text-gray-400">
                  <span class="w-3.5 h-3.5 border-2 border-gray-200 border-t-blue-500 rounded-full animate-spin shrink-0"/>
                  Cargando docentes...
                </div>

                <!-- Sin resultados -->
                <div
                  v-else-if="filteredDocentes.length === 0"
                  class="flex flex-col items-center gap-1.5 px-4 py-6 text-sm text-gray-400"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                  Sin resultados para "<strong class="text-gray-600">{{ searchQuery }}</strong>"
                </div>

                <!-- Lista -->
                <ul v-else class="list-none m-0 p-1.5 max-h-56 overflow-y-auto" role="listbox">
                  <li
                    v-for="doc in filteredDocentes"
                    :key="doc.cod_docente ?? doc.id"
                    role="option"
                    class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-colors"
                    :class="selectedDocente?.cod_docente === doc.cod_docente
                      ? 'bg-blue-50'
                      : 'hover:bg-gray-50'"
                    @mousedown.prevent="onSeleccionarDocente(doc)"
                  >
                    <!-- Avatar iniciales -->
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                      {{ inicialesDocente(doc) }}
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                      <span class="text-sm font-medium text-gray-800 truncate">
                        {{ doc.nombres }} {{ doc.apellidos }}
                      </span>
                      <span class="text-[0.68rem] font-semibold tracking-wide bg-indigo-50 text-indigo-600 px-1.5 py-px rounded w-fit">
                        SIS: {{ doc.codigo }}
                      </span>
                    </div>
                    <svg
                      v-if="selectedDocente?.cod_docente === doc.cod_docente"
                      class="w-4 h-4 text-blue-500 shrink-0"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                    >
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                  </li>
                </ul>

                <!-- Footer contador -->
                <div class="px-4 py-1.5 text-xs text-gray-400 border-t border-gray-100 text-right">
                  {{ filteredDocentes.length }} docente{{ filteredDocentes.length !== 1 ? 's' : '' }} encontrado{{ filteredDocentes.length !== 1 ? 's' : '' }}
                </div>
              </div>
            </Transition>
          </div>

          <!-- Badge docente seleccionado -->
          <div
            v-if="filaActual.docente"
            class="mt-1.5 flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-lg"
          >
            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs text-blue-700 font-medium">
              {{ filaActual.docente.nombres }} {{ filaActual.docente.apellidos }}
            </span>
            <button type="button" @click="onLimpiarDocente" class="ml-auto text-blue-400 hover:text-blue-600">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <p v-if="erroresFila.docente" class="text-xs text-red-500 mt-1">{{ erroresFila.docente }}</p>
        </div>
        <!-- ── FIN BUSCADOR ── -->

        <!-- Fila: cod_plan | cod_materia | grupo | tipo -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
              Cod. Plan <span class="text-red-500">*</span>
            </label>
            <input
              v-model="filaActual.cod_plan"
              type="text"
              placeholder="109401"
              class="w-full px-3 py-2 text-sm border rounded-lg outline-none transition-colors placeholder-gray-300"
              :class="erroresFila.cod_plan
                ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
              @input="erroresFila.cod_plan = ''"
            />
            <p v-if="erroresFila.cod_plan" class="text-xs text-red-500 mt-1">{{ erroresFila.cod_plan }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
              Cod. Materia <span class="text-red-500">*</span>
            </label>
            <input
              v-model="filaActual.cod_materia"
              type="text"
              placeholder="1301027"
              class="w-full px-3 py-2 text-sm border rounded-lg outline-none transition-colors placeholder-gray-300"
              :class="erroresFila.cod_materia
                ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
              @input="erroresFila.cod_materia = ''"
            />
            <p v-if="erroresFila.cod_materia" class="text-xs text-red-500 mt-1">{{ erroresFila.cod_materia }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Grupo</label>

            <input
                v-model="filaActual.grupo"
                type="text"
                placeholder="20"
                class="w-full px-3 py-2 text-sm border rounded-lg outline-none transition-colors placeholder-gray-300"
                :class="erroresFila.grupo
                ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
                @input="erroresFila.grupo = ''"
            />

            <p v-if="erroresFila.grupo" class="text-xs text-red-500 mt-1">
                {{ erroresFila.grupo }}
            </p>
            </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
            <input
              v-model="filaActual.tipo"
              type="text"
              placeholder="TP"
              maxlength="2"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-colors placeholder-gray-300"
            />
          </div>
        </div>

        <!-- Compartido -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="filaActual.esCompartido"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
              />
              <span class="text-sm text-gray-700">Compartido</span>
            </label>
            <span class="text-xs text-gray-400">(si está marcado se guardará "COMPARTIDO" en observación)</span>
          </div>
        </div>

        <!-- Botón agregar -->
        <div class="flex justify-end pt-1">
          <button
            type="button"
            @click="agregarFila"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M12 4v16m8-8H4"/>
            </svg>
            Agregar fila
          </button>
        </div>
      </div>

      <!-- ─── TABLA DE FILAS AGREGADAS ─── -->
      <div v-if="filas.length > 0">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Filas agregadas ({{ filas.length }})
          </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200">
          <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">N°</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Docente</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Plan</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Materia</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Grupo</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Tipo</th>
                <th class="px-3 py-2.5 text-left text-gray-500 font-semibold">Observación</th>
                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">Acc.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(fila, i) in filas"
                :key="i"
                class="hover:bg-gray-50 transition-colors"
                :class="fila.esCompartido ? 'bg-amber-50/60' : ''"
              >
                <td class="px-3 py-2.5 text-gray-400 font-mono">{{ i + 1 }}</td>
                <td class="px-3 py-2.5 text-gray-800 font-medium">
                  {{ fila.docente.nombres }} {{ fila.docente.apellidos }}
                </td>
                <td class="px-3 py-2.5 text-gray-600 font-mono">{{ fila.cod_plan }}</td>
                <td class="px-3 py-2.5 text-gray-600 font-mono">{{ fila.cod_materia }}</td>
                <td class="px-3 py-2.5 text-gray-600">{{ fila.grupo || '—' }}</td>
                <td class="px-3 py-2.5 text-gray-600">{{ fila.tipo || '—' }}</td>
                <td class="px-3 py-2.5">
                  <span
                    v-if="fila.esCompartido"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700"
                  >
                    COMPARTIDO
                  </span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-3 py-2.5 text-center">
                  <button
                    type="button"
                    @click="eliminarFila(i)"
                    class="text-gray-300 hover:text-red-400 transition-colors"
                    title="Eliminar fila"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="errorFilas" class="text-xs text-red-500 mt-2">{{ errorFilas }}</p>
      </div>

      <!-- Estado vacío -->
      <div v-else class="flex flex-col items-center gap-2 py-8 text-gray-300">
        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path d="M9 17v-2m3 2v-4m3 4v-6M3 3h18M3 8h18M3 13h9"/>
        </svg>
        <p class="text-sm">Aún no hay filas. Agrega la primera.</p>
      </div>

    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
      <button
        type="button"
        @click="$emit('back')"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>

      <button
        type="button"
        @click="submitTablas"
        class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
      >
        Siguiente
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useDocentes } from '../../docentes/composables/useDocentes'

const emit = defineEmits(['submit', 'back'])

// ─── Composable de docentes ───────────────────────────────────────
const {
  loading,
  searchQuery,
  dropdownOpen,
  filteredDocentes,
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection,
} = useDocentes()

// ─── Refs del buscador ────────────────────────────────────────────
const docenteContainerRef  = ref(null)
const docenteInputRef      = ref(null)
const docenteInputFocused  = ref(false)

// ─── Fila actual (formulario) ─────────────────────────────────────
const filaActual = reactive({
  docente:      null,
  cod_plan:     '',
  cod_materia:  '',
  grupo:        '',
  tipo:         '',
  esCompartido: false,
})

const erroresFila = reactive({
  docente:     '',
  cod_plan:    '',
  cod_materia: '',
})

// ─── Filas confirmadas ────────────────────────────────────────────
const filas      = ref([])
const errorFilas = ref('')

// ─── Helpers buscador ─────────────────────────────────────────────
const inicialesDocente = (d) =>
  ((d.nombres?.[0] || '') + (d.apellidos?.[0] || '')).toUpperCase() || '?'

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
  if (dropdownOpen.value) docenteInputRef.value?.focus()
}

function onDocenteFocus() {
  docenteInputFocused.value = true
  dropdownOpen.value        = true
}

function onDocenteInput() {
  dropdownOpen.value        = true
  erroresFila.docente       = ''
  // Si el usuario escribe, deseleccionamos el docente actual
  if (filaActual.docente) {
    filaActual.docente    = null
    selectedDocente.value = null
  }
}

function onSeleccionarDocente(doc) {
  selectDocente(doc)           // actualiza composable (searchQuery, selectedDocente)
  filaActual.docente    = doc  // guarda en la fila actual
  erroresFila.docente   = ''
}

function onLimpiarDocente() {
  clearSelection()
  filaActual.docente = null
}

// ─── Cierre dropdown al click fuera ──────────────────────────────
function handleClickOutside(e) {
  if (docenteContainerRef.value && !docenteContainerRef.value.contains(e.target)) {
    dropdownOpen.value = false
  }
}

// ─── Agregar fila ─────────────────────────────────────────────────
function agregarFila() {
  erroresFila.docente     = ''
  erroresFila.cod_plan    = ''
  erroresFila.cod_materia = ''
  erroresFila.grupo = ''

  let valid = true

  if (!filaActual.docente) {
    erroresFila.docente = 'Selecciona un docente.'
    valid = false
  }
  if (!filaActual.cod_plan.trim()) {
    erroresFila.cod_plan = 'Requerido.'
    valid = false
  }
  if (!filaActual.cod_materia.trim()) {
    erroresFila.cod_materia = 'Requerido.'
    valid = false
  }
  if (!filaActual.grupo.trim()) {
    erroresFila.grupo = 'Requerido.'
    valid = false
  }

  if (!valid) return


  filas.value.push({
    docente: {
      ...filaActual.docente,
      // Normaliza: asegura que cod_docente siempre exista
      cod_docente: filaActual.docente.cod_docente
                  ?? filaActual.docente.codigo
                  ?? filaActual.docente.id_docente
                  ?? filaActual.docente.id,
    },
    cod_plan:     filaActual.cod_plan.trim(),
    cod_materia:  filaActual.cod_materia.trim(),
    grupo:        filaActual.grupo.trim(),
    tipo:         filaActual.tipo.trim(),
    esCompartido: filaActual.esCompartido,
    observacion:  filaActual.esCompartido ? 'COMPARTIDO' : null,
  })

  // Reset — mantiene el docente para agilizar entrada de varias filas del mismo
  filaActual.cod_plan     = ''
  filaActual.cod_materia  = ''
  filaActual.grupo        = ''
  filaActual.tipo         = ''
  filaActual.esCompartido = false
  errorFilas.value        = ''
}

function eliminarFila(index) {
  filas.value.splice(index, 1)
}

// ─── Submit ───────────────────────────────────────────────────────
function submitTablas() {
  console.log('filas[0]:', JSON.stringify(filas.value[0], null, 2))  
  errorFilas.value = ''

  if (filas.value.length === 0) {
    errorFilas.value = 'Debes agregar al menos una fila antes de continuar.'
    return
  }


  const payload = filas.value.map(f => ({
    cod_docente: Number(f.docente.cod_docente),  // decimal(10,0) necesita número
    cod_plan:    f.cod_plan,
    cod_materia: f.cod_materia,
    grupo:       f.grupo       || null,
    tipo:        f.tipo        || null,
    observacion: f.observacion || null,
  }))

  emit('submit', payload)
}

// ─── Lifecycle ────────────────────────────────────────────────────
onMounted(() => {
  fetchDocentes()
  document.addEventListener('mousedown', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>