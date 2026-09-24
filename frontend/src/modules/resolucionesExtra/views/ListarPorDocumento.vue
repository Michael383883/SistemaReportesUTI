<template>
  <div class="bg-gray-50 pb-4">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Documentos (por archivo)</h1>
        <p class="text-sm text-gray-500">Una fila por documento, con todos los docentes vinculados</p>
      </div>
      <router-link
        :to="{ name: 'clasificaciones-nueva' }"
        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo
      </router-link>
    </div>

    <!-- Buscador + botón de filtros -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 mb-2 flex flex-wrap items-center gap-2">
      <div class="flex-1 min-w-[150px] relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="filtroTexto"
          type="text"
          placeholder="Buscar por documento o docente..."
          class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <button
        @click="mostrarFiltros = !mostrarFiltros"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
        :class="mostrarFiltros || filtrosActivosCount > 0
          ? 'bg-blue-50 border-blue-200 text-blue-700'
          : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filtros
        <span
          v-if="filtrosActivosCount > 0"
          class="inline-flex items-center justify-center w-5 h-5 bg-blue-600 text-white text-xs font-semibold rounded-full"
        >
          {{ filtrosActivosCount }}
        </span>
      </button>
    </div>

    <!-- Panel de filtros -->
    <div
      v-if="mostrarFiltros"
      class="bg-slate-100 rounded-xl border border-gray-200 p-3 mb-4 flex flex-wrap items-end gap-2"
    >
      <select v-model="filtros.categoria" @change="cargar" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white">
        <option value="">Todas las categorías</option>
        <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
      </select>
      <select v-model="filtros.nivel" @change="cargar" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white">
        <option value="">Todos los niveles</option>
        <option value="Primer nivel">Primer nivel</option>
        <option value="Segundo nivel">Segundo nivel</option>
        <option value="Tercer nivel">Tercer nivel</option>
      </select>
      <input v-model="filtros.gestion" @change="cargar" type="text" placeholder="Gestión" class="w-28 px-3 py-1.5 text-sm border border-gray-200 rounded-lg" />
      <input v-model="filtros.periodo" @change="cargar" type="text" placeholder="Periodo" class="w-28 px-3 py-1.5 text-sm border border-gray-200 rounded-lg" />
      <button @click="limpiarFiltros" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-400 hover:text-gray-600 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Limpiar
      </button>
    </div>

    <!-- Estados -->
    <div v-if="clasificacion.loading.value" class="text-center py-12 text-sm text-gray-400">
      <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      Cargando...
    </div>

    <div v-else-if="clasificacion.error.value" class="bg-white rounded-xl border border-red-200 p-8 text-center text-sm text-red-500">
      {{ clasificacion.error.value }}
    </div>

    <div v-else-if="!documentosFiltrados.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-sm text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      No hay documentos registrados
    </div>

    <!-- Tabla -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-slate-900">
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Documento</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Docentes</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Categoria</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Nivel</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Gestión</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Periodo</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">PDF</th>
              <th class="text-right font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="doc in documentosFiltrados"
              :key="doc.ID_DOCUMENTO"
              class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
            >
              <!-- Documento -->
              <td class="px-4 py-3 max-w-[220px]">
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-gray-800 truncate">{{ doc.TIPO_DOCUMENTO || '—' }}</span>
                  <span v-if="doc.DETALLE_GENERAL" class="text-xs text-gray-400 truncate">{{ doc.DETALLE_GENERAL }}</span>
                </div>
              </td>

              <!--
                Docentes vinculados: esta vista está enfocada en el DOCUMENTO,
                así que los docentes pasan a segundo plano visual.
                - 1 solo docente (o ninguno): se muestra directo, sin desplegable.
                - Más de uno: se muestra el primero + un botón desplegable
                  (clic, no hover) con TODOS los docentes (incluido el primero),
                  cada uno como link real a su vista de docente.

                👈 El badge muestra el TOTAL real de docentes (ej: "3 docentes"),
                no la cantidad "extra". El desplegable, en consecuencia, lista
                a los 3 (incluyendo al que ya se ve en el pill), para que el
                número de arriba siempre coincida con la cantidad de filas
                que aparecen al abrir.

                El panel del desplegable vive teleportado a <body> (ver más
                abajo en este archivo) para no quedar recortado por los
                overflow de la tabla/tarjeta que lo contienen.
              -->
              <td class="px-4 py-3 max-w-[200px]">
                <div v-if="doc.docentes.length <= 1" class="flex">
                  <router-link
                    v-if="doc.docentes.length"
                    :to="{ name: 'clasificaciones-docente', params: { cod_docente: doc.docentes[0].COD_DOCENTE } }"
                    class="inline-flex px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium truncate max-w-[180px]"
                  >
                    {{ doc.docentes[0].NOMBRE_DOCENTE }}
                  </router-link>
                  <span v-else class="text-gray-300 text-xs">—</span>
                </div>

                <div v-else class="relative inline-block" @click.stop>
                  <button
                    @click="toggleDocentesDropdown(doc, $event)"
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-semibold truncate max-w-[180px]"
                  >
                    <span class="truncate">{{ doc.docentes[0].NOMBRE_DOCENTE }}</span>
                    <span class="text-gray-400 flex-shrink-0">· {{ doc.docentes.length }}</span>
                    <svg
                      class="w-3 h-3 flex-shrink-0 transition-transform"
                      :class="{ 'rotate-180': dropdownAbierto === doc.ID_DOCUMENTO }"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                </div>
              </td>

              <!-- Categoria -->
              <td class="px-4 py-3">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium" :class="badgeCategoria(doc.CATEGORIA)">
                  {{ doc.CATEGORIA }}
                </span>
              </td>

              <td class="px-4 py-3 text-gray-700 text-sm font-medium">{{ doc.NIVEL || '—' }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ doc.GESTION || '—' }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ doc.PERIODO || '—' }}</td>

              <!-- PDF -->
              <td class="px-4 py-3">
                <button
                  v-if="doc.NOMBRE_ARCHIVO"
                  @click="verPdf(doc.ID_DOCUMENTO)"
                  :disabled="abriendoPdfId === doc.ID_DOCUMENTO"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-800 hover:bg-blue-900 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-60"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  {{ abriendoPdfId === doc.ID_DOCUMENTO ? 'Abriendo...' : 'Ver PDF' }}
                </button>
                <span v-else class="text-gray-300 text-sm">—</span>
              </td>

              <!-- Acciones -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="editarDocumento(doc)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition-colors"
                    title="Editar documento completo"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                  </button>
                  <button
                    @click="confirmarEliminar(doc)"
                    class="inline-flex items-center p-2 text-red-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Eliminar documento completo"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 py-2.5 bg-gray-50/80 border-t border-gray-100 text-xs text-gray-400">
        Mostrando {{ documentosFiltrados.length }} documento(s)
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <Teleport to="body">
      <div v-if="mostrarModalEliminar" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cerrarModalEliminar">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
          <div class="flex items-center justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
              <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
          </div>
          <h3 class="text-center text-lg font-semibold text-gray-900 mb-2">¿Eliminar documento?</h3>
          <p class="text-center text-sm text-gray-500 mb-6">
            Se eliminará <span class="font-medium text-gray-700">"{{ docAEliminar?.TIPO_DOCUMENTO || 'este documento' }}"</span>,
            <span v-if="(docAEliminar?.docentes?.length || 0) > 1">
              junto con los {{ docAEliminar.docentes.length }} docentes vinculados,
            </span>
            sus materias, título y referencias.
          </p>
          <div class="flex gap-3">
            <button @click="cerrarModalEliminar" class="flex-1 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Cancelar
            </button>
            <button
              @click="eliminarDocumento"
              :disabled="eliminando"
              class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg v-if="eliminando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ eliminando ? 'Eliminando...' : 'Sí, eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!--
      Dropdown de docentes (teleportado a <body>).

      El panel ya no vive dentro de la celda de la tabla como `position:
      absolute` (quedaba recortado por los `overflow-x-auto` /
      `overflow-hidden` de sus ancestros). Con Teleport + `position: fixed`
      calculado desde `getBoundingClientRect()` del botón, se dibuja directo
      sobre <body>, en coordenadas de viewport, sin depender de overflow
      alguno.
    -->
    <Teleport to="body">
      <div
        v-if="dropdownAbierto"
        class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[190px] max-h-56 overflow-y-auto"
        :style="{ top: dropdownPos.top + 'px', left: dropdownPos.left + 'px' }"
        @click.stop
      >
        <router-link
          v-for="d in dropdownDocentes"
          :key="d.ID_CLASIFICACION_DOCENTE"
          :to="{ name: 'clasificaciones-docente', params: { cod_docente: d.COD_DOCENTE } }"
          class="block px-3 py-1.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 truncate"
          @click="dropdownAbierto = null"
        >
          {{ d.NOMBRE_DOCENTE }}
        </router-link>
      </div>
    </Teleport>

    <!-- Modal de edición completa -->
    <EditarDocumentoModal
      v-if="docEditando"
      :doc="docEditando"
      @cerrar="docEditando = null"
      @guardado="onGuardadoEdicion"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useClasificacion } from '../composables/useClasificacion'
import { useDocumentos } from '../composables/useDocumentos'
import { useCategorias } from '../composables/useCategorias'
import EditarDocumentoModal from '../components/EditarDocumentoModal.vue'

// Se pasa esta MISMA instancia a useDocumentos() para que `documentos`
// se derive del mismo listado que carga esta vista con listar().
const clasificacion = useClasificacion()
const { documentos } = useDocumentos(clasificacion)
const { categorias, cargarCategorias } = useCategorias()

// ─── Búsqueda y filtros ───
const filtroTexto = ref('')
const mostrarFiltros = ref(false)
const filtros = ref({ categoria: '', nivel: '', gestion: '', periodo: '' })

const filtrosActivosCount = computed(() => Object.values(filtros.value).filter(Boolean).length)

async function cargar() {
  await clasificacion.listar({
    categoria: filtros.value.categoria || undefined,
    nivel: filtros.value.nivel || undefined,
    gestion: filtros.value.gestion || undefined,
    periodo: filtros.value.periodo || undefined,
  })
}

function limpiarFiltros() {
  filtroTexto.value = ''
  filtros.value = { categoria: '', nivel: '', gestion: '', periodo: '' }
  cargar()
}

const documentosFiltrados = computed(() => {
  const term = filtroTexto.value.trim().toLowerCase()
  if (!term) return documentos.value

  return documentos.value.filter(doc => {
    const enDoc =
      (doc.TIPO_DOCUMENTO || '').toLowerCase().includes(term) ||
      (doc.DETALLE_GENERAL || '').toLowerCase().includes(term)
    const enDocentes = doc.docentes.some(d => (d.NOMBRE_DOCENTE || '').toLowerCase().includes(term))
    return enDoc || enDocentes
  })
})

// ─── Badge de categoria (mismo criterio que el resto de vistas) ───
function badgeCategoria(categoria) {
  if (categoria === 'DOCENTES TITULARES') return 'bg-emerald-50 text-emerald-700'
  if (categoria === 'DOCENTES TEMPORALES') return 'bg-amber-50 text-amber-700'
  if (categoria === 'EXAMEN SUFICIENCIA') return 'bg-blue-50 text-blue-700'
  if (categoria === 'ACÉFALA') return 'bg-gray-50 text-gray-700'
  if (categoria === 'SIN CATEGORIA') return 'bg-orange-50 text-red-700'
  if (categoria === 'CERTIFICADO') return 'bg-violet-50 text-violet-700'
  if (categoria === 'CONCURSO DE MÉRITO') return 'bg-fuchsia-50 text-fuchsia-700'
  if (categoria === 'PLANILLA DE CALIFICACIÓN') return 'bg-cyan-50 text-cyan-700'
  return 'bg-gray-50 text-gray-600'
}

// ─── Dropdown de docentes (solo cuando hay más de uno vinculado) ───
// Enfoque de esta vista: el documento es lo principal, los docentes pasan
// a segundo plano. Si hay más de un docente, se colapsan detrás de un
// desplegable por clic (no hover) que se cierra solo al hacer click afuera
// o al elegir un docente.
//
// El panel se renderiza UNA sola vez (teleportado a <body>, ver template),
// no uno por fila. Estos refs guardan:
//   - dropdownAbierto: qué ID_DOCUMENTO está mostrando su panel (o null)
//   - dropdownDocentes: el array de docentes a listar en el panel abierto
//     (el TOTAL, incluido el primero, para que coincida con el número
//     mostrado en el pill: "N docentes")
//   - dropdownPos: coordenadas fixed (viewport) donde dibujar el panel,
//     calculadas a partir del botón que se clickeó
const dropdownAbierto = ref(null)
const dropdownDocentes = ref([])
const dropdownPos = ref({ top: 0, left: 0 })

function toggleDocentesDropdown(doc, event) {
  if (dropdownAbierto.value === doc.ID_DOCUMENTO) {
    dropdownAbierto.value = null
    return
  }
  const rect = event.currentTarget.getBoundingClientRect()
  dropdownPos.value = { top: rect.bottom + 4, left: rect.left }
  dropdownDocentes.value = doc.docentes // TODOS, incluido el primero (así el conteo del pill es el total real)
  dropdownAbierto.value = doc.ID_DOCUMENTO
}

function cerrarDropdownGlobal() {
  dropdownAbierto.value = null
}

onMounted(() => {
  document.addEventListener('click', cerrarDropdownGlobal)
  // Con el panel en position:fixed, si el usuario scrollea (la página, o el
  // propio div.overflow-x-auto de la tabla) el panel se queda "flotando" en
  // la posición vieja si no lo cerramos. `capture: true` es necesario para
  // enterarnos también del scroll interno de la tabla, que no burbujea
  // hasta window como evento normal.
  window.addEventListener('scroll', cerrarDropdownGlobal, true)
  window.addEventListener('resize', cerrarDropdownGlobal)
})
onUnmounted(() => {
  document.removeEventListener('click', cerrarDropdownGlobal)
  window.removeEventListener('scroll', cerrarDropdownGlobal, true)
  window.removeEventListener('resize', cerrarDropdownGlobal)
})

// ─── Ver PDF ───
const abriendoPdfId = ref(null)
async function verPdf(idDocumento) {
  if (abriendoPdfId.value) return
  abriendoPdfId.value = idDocumento
  try {
    await clasificacion.verPdf(idDocumento, 'inline')
  } catch (e) {
    alert('No se pudo abrir el PDF')
  } finally {
    abriendoPdfId.value = null
  }
}

// ─── Editar documento completo ───
const docEditando = ref(null)
function editarDocumento(doc) {
  docEditando.value = doc
}
async function onGuardadoEdicion() {
  docEditando.value = null
  await cargar()
}

// ─── Eliminar documento completo ───
const mostrarModalEliminar = ref(false)
const docAEliminar = ref(null)
const eliminando = ref(false)

function confirmarEliminar(doc) {
  docAEliminar.value = doc
  mostrarModalEliminar.value = true
}
function cerrarModalEliminar() {
  mostrarModalEliminar.value = false
  docAEliminar.value = null
}
async function eliminarDocumento() {
  if (!docAEliminar.value) return
  eliminando.value = true
  try {
    const result = await clasificacion.eliminar(docAEliminar.value.ID_DOCUMENTO)
    if (result?.ok) {
      await cargar()
      cerrarModalEliminar()
    } else {
      alert(result?.error || 'Error al eliminar')
    }
  } catch (e) {
    alert('Error al eliminar el documento')
  } finally {
    eliminando.value = false
  }
}

onMounted(async () => {
  await cargarCategorias()
  await cargar()
})
</script>