<template>
  <div class="px-6 py-1 w-full">

    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight m-0 mb-1">
          {{ tipo === 'resolucion' ? 'Asignación de Resoluciones a Docentes' : 'Asignar Documento a Otros Docentes' }}
        </h1>
        <p class="text-sm text-slate-500 m-0">
          Buscá un docente, elegí {{ tipo === 'resolucion' ? 'la resolución' : 'el documento de clasificación' }} y marcá las materias correspondientes con un click.
        </p>
      </div>
    </div>

    <!-- Vista de resultado final: detalles guardados + grupos actualizados -->
    <ResultadoAsignacionResolucion
      v-if="fase === 'resultado'"
      :grupos-actualizados="gruposActualizados"
      :ultimas-asignadas="ultimasAsignadas"
      :resolucion-nro="origenAsignadoNro"
      :resolucion-anio-periodo-label="origenAnioPeriodoLabel"
      :docente-asignado-codigo="docenteAsignadoCodigo"
      :docente-asignado-nombre="docenteAsignadoNombre"
      @ver-reporte="verReporte"
      @asignar-otra="asignarOtraMas"
      @ir-a-listado="$router.push({ name: rutaListado })"
    />

    <!-- Flujo principal -->
    <template v-else>
      <!-- Paso 1 y 2: Docente + Origen (Resolución/Documento) lado a lado -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5 items-stretch">
        <!-- Paso 1: Docente -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 bg-slate-900 flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-900 text-[11px] font-bold flex items-center justify-center flex-shrink-0">1</span>
            <div>
              <h3 class="text-sm font-semibold text-white m-0">Buscá el docente</h3>
            </div>
          </div>
          <div class="px-5 py-4 flex-1">
            <DocenteSearch
              v-model:searchQuery="searchQuery"
              v-model:dropdownOpen="dropdownOpen"
              :filteredDocentes="filteredDocentes"
              :selectedDocente="selectedDocente"
              :loading="loadingDocentes"
              @select="onSeleccionarDocente"
              @clear="onLimpiarDocente"
            />
          </div>
        </div>

        <!-- Paso 2: Origen (Resolución o Documento) -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 bg-slate-900 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-900 text-[11px] font-bold flex items-center justify-center flex-shrink-0">2</span>
              <div>
                <h3 class="text-sm font-semibold text-white m-0">Asignar {{ tipo === 'resolucion' ? 'resolución' : 'documento' }}</h3>
              </div>
            </div>
            <span
              v-if="origenActivo"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/15 text-emerald-400"
            >
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Seleccionado
            </span>
          </div>
          <div class="px-5 py-4 flex-1">
            <OrigenPicker
              :tipo="tipo"
              :bloqueado-cambio-tipo="bloqueadoCambioTipo"
              :filas="tipo === 'resolucion' ? filasResolucion : filasDocumento"
              :loading="tipo === 'resolucion' ? loadingResoluciones : loadingDocumentos"
              :error="tipo === 'resolucion' ? errorResoluciones : errorDocumentos"
              :busqueda="tipo === 'resolucion' ? busquedaResolucion : busquedaDocumento"
              :origen-activo="origenActivo"
              :bloqueado="origenBloqueado"
              @cambiar-tipo="onCambiarTipo"
              @buscar="onBuscarOrigen"
              @limpiar-busqueda="onLimpiarBusquedaOrigen"
              @select="onSeleccionarOrigen"
              @limpiar="onLimpiarOrigen"
            />
          </div>
        </div>
      </div>

      <!-- Materias del docente seleccionado -->
      <div v-if="selectedDocente" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-1 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
          <p class="text-xs font-semibold text-slate-800 uppercase tracking-wider m-0">
            Materias dictadas: {{ selectedDocente.apellidos ?? selectedDocente.APELLIDOS }} {{ selectedDocente.nombres ?? selectedDocente.NOMBRES }}
          </p>

          <!-- Filtros por año y gestión -->
          <div class="flex items-center gap-2">
            <span
              v-if="origenActivo && (filtroAnio || filtroGestion)"
              class="text-[0.85rem] text-amber-600 flex items-center gap-1"
              title="Filtro aplicado automáticamente según el periodo del origen seleccionado"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              según selección
            </span>

            <select
              v-model="filtroAnio"
              class="bg-white border border-slate-200 text-slate-600 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors
                     cursor-pointer"
            >
              <option value="">Todos los años</option>
              <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
            </select>

            <select
              v-model="filtroGestion"
              class="bg-white border border-slate-200 text-slate-600 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors
                     cursor-pointer"
            >
              <option value="">Todas las gestiones</option>
              <option v-for="g in gestionesDisponibles" :key="g" :value="g">{{ g }}</option>
            </select>

            <button
              v-if="filtroAnio || filtroGestion"
              type="button"
              class="inline-flex items-center gap-1 text-xs text-slate-700 hover:text-slate-600 transition-colors"
              @click="filtroAnio = ''; filtroGestion = ''"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              Limpiar
            </button>

            <span v-if="!origenActivo" class="text-xs text-slate-400 flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              Elegí {{ tipo === 'resolucion' ? 'una resolución' : 'un documento' }} para poder asignar
            </span>
          </div>
        </div>

        <div class="p-5">
          <div v-if="loadingReporte" class="h-32 rounded-lg bg-slate-50 border border-slate-100 animate-pulse"/>
          <div v-else-if="errorReporte" class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">
            {{ errorReporte }}
          </div>
          <MateriasAsignarTabla
            v-else-if="reporte"
            :materias="materiasFiltradas"
            :resolucion-activa="origenActivo"
            :marcadas-keys="materiasMarcadas.map(m => m.key)"
            :docente-cod="docenteCodActual"
            @toggle="(m) => toggleMateria(selectedDocente, m)"
            @tipo-ingreso-change="(m) => actualizarTipoIngreso(selectedDocente, m)"
          />
        </div>
      </div>

      <!-- Resumen / confirmar -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 bg-slate-900 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-900 text-[11px] font-bold flex items-center justify-center flex-shrink-0">3</span>
            <div>
              <h3 class="text-sm font-semibold text-white m-0">Confirmá la asignación</h3>
            </div>
          </div>
          <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-400">
            {{ materiasMarcadas.length }} materia{{ materiasMarcadas.length !== 1 ? 's' : '' }}
          </span>
        </div>
        <MateriasMarcadasResumen
          :materias="materiasMarcadas"
          :guardando="guardando"
          :error="errorGuardado || errorLocal"
          @quitar="quitarMateria"
          @limpiar-todo="limpiarTodo"
          @terminar="handleTerminar"
        />
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DocenteSearch from '../../docentes/components/DocenteSearch.vue'
import { useDocentes } from '../../docentes/composables/useDocentes'
import { useReporte } from '../../reportes/composables/useReporte'
import { useResolucionListado } from '../../resoluciones/composables/useResolucionListado' // ajustá la ruta real
import { useDocumentoListado } from '../composables/useDocumentoListado' // ajustá la ruta real
import { useAsignacionOrigen } from '../composables/useAsignacionOrigen' // ajustá la ruta real
import OrigenPicker from '../components/OrigenPicker.vue'
import MateriasAsignarTabla from '../../resoluciones/components/MateriasAsignarTabla.vue' // ajustá la ruta real
import MateriasMarcadasResumen from '../../resoluciones/components/MateriasMarcadasResumen.vue' // ajustá la ruta real
import ResultadoAsignacionResolucion from '../../resoluciones/components/ResultadoAsignacionResolucion.vue' // ajustá la ruta real

const props = defineProps({
  // Permite abrir la vista ya fijada en un tipo, ej. desde dos entradas de
  // menú distintas ("Asignar resolución" / "Asignar documento") que apuntan
  // a la misma vista con distinto valor inicial.
  tipoInicial: { type: String, default: 'resolucion' }, // 'resolucion' | 'documento'
})

const router = useRouter()
const route = useRoute()

// ─── Docentes ───────────────────────────────────────────────────
const {
  loading: loadingDocentes,
  searchQuery,
  dropdownOpen,
  filteredDocentes,
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection: clearSelectionDocente,
} = useDocentes()

fetchDocentes()

const docenteCodActual = computed(() =>
  selectedDocente.value
    ? (selectedDocente.value.cod_docente ?? selectedDocente.value.CODIGO ?? selectedDocente.value.codigo)
    : null
)

// ─── Reporte de materias del docente seleccionado ────────────────
const { reporte, loading: loadingReporte, error: errorReporte, generarReporte, limpiarReporte } = useReporte()

function onSeleccionarDocente(doc) {
  selectDocente(doc)
  const codigo = doc.codigo ?? doc.CODIGO
  if (codigo) generarReporte(codigo)
}

function onLimpiarDocente() {
  clearSelectionDocente()
  limpiarReporte()
  filtroAnio.value = ''
  filtroGestion.value = ''
}

// ─── Listados de origen (resolución / documento) ─────────────────
const {
  filas: filasResolucion,
  loading: loadingResoluciones,
  error: errorResoluciones,
  busqueda: busquedaResolucion,
  buscar: buscarResolucion,
  limpiarBusqueda: limpiarBusquedaResolucion,
} = useResolucionListado()

const {
  filas: filasDocumento,
  loading: loadingDocumentos,
  error: errorDocumentos,
  busqueda: busquedaDocumento,
  buscar: buscarDocumento,
  limpiarBusqueda: limpiarBusquedaDocumento,
} = useDocumentoListado()

function onBuscarOrigen(termino) {
  tipo.value === 'resolucion' ? buscarResolucion(termino) : buscarDocumento(termino)
}
function onLimpiarBusquedaOrigen() {
  tipo.value === 'resolucion' ? limpiarBusquedaResolucion() : limpiarBusquedaDocumento()
}

// ─── Orquestador unificado de asignación ─────────────────────────
const {
  tipo,
  setTipo,
  bloqueadoCambioTipo,
  origenActivo,
  origenBloqueado,
  materiasMarcadas,
  guardando,
  errorGuardado,
  seleccionarOrigen,
  limpiarOrigen,
  toggleMateria,
  actualizarTipoIngreso,
  quitarMateria,
  limpiarTodo,
  confirmarAsignacion,
  aplicarEnGrupos,
} = useAsignacionOrigen(props.tipoInicial)

const errorLocal = ref('')

function onCambiarTipo(nuevo) {
  setTipo(nuevo)
}

function onSeleccionarOrigen(o) {
  seleccionarOrigen(o)
}

function onLimpiarOrigen() {
  limpiarOrigen()
}

const rutaListado = computed(() =>
  tipo.value === 'resolucion' ? 'resoluciones-listado' : 'clasificaciones-listado'
)

// ─── Filtros de materias ──────────────────────────────────────────
const filtroAnio = ref('')
const filtroGestion = ref('')

const materiasDelReporte = computed(() => reporte.value?.materias ?? [])

const aniosDisponibles = computed(() => {
  const set = new Set()
  materiasDelReporte.value.forEach(m => {
    const anio = String(m.gestion ?? '').split('/')[0]?.trim()
    if (anio) set.add(anio)
  })
  return [...set].sort((a, b) => b - a)
})

const gestionesDisponibles = computed(() => {
  const set = new Set()
  materiasDelReporte.value.forEach(m => {
    const partes = String(m.gestion ?? '').split('/')
    const parte = partes.slice(1).join('/').trim()
    if (parte) set.add(parte)
  })
  return [...set].sort()
})

const materiasFiltradas = computed(() => {
  return materiasDelReporte.value.filter(m => {
    const partes = String(m.gestion ?? '').split('/')
    const anio = partes[0]?.trim()
    const gest = partes.slice(1).join('/').trim()
    if (filtroAnio.value && anio !== filtroAnio.value) return false
    if (filtroGestion.value && gest !== filtroGestion.value) return false
    return true
  })
})

// ─── Auto-filtro por año/periodo del origen activo ────────────────
// Cuando se elige resolución o documento, si tiene anio/periodo definidos y
// alguno de esos valores existe entre las opciones disponibles del docente
// actual, se preseleccionan los filtros automáticamente. Es solo un valor
// por defecto: el usuario puede cambiarlo después.
watch(origenActivo, (nuevo) => {
  if (!nuevo) return
  const anio = String(nuevo.anio ?? '').trim()
  const periodo = String(nuevo.periodo ?? '').trim()
  if (anio && aniosDisponibles.value.includes(anio)) {
    filtroAnio.value = anio
  }
  if (periodo && gestionesDisponibles.value.includes(periodo)) {
    filtroGestion.value = periodo
  }
})

// También se aplica si el docente se selecciona/cambia después de
// ya haber elegido el origen (el orden de los pasos es libre).
watch(materiasDelReporte, () => {
  if (!origenActivo.value) return
  const anio = String(origenActivo.value.anio ?? '').trim()
  const periodo = String(origenActivo.value.periodo ?? '').trim()
  if (anio && aniosDisponibles.value.includes(anio) && !filtroAnio.value) {
    filtroAnio.value = anio
  }
  if (periodo && gestionesDisponibles.value.includes(periodo) && !filtroGestion.value) {
    filtroGestion.value = periodo
  }
})

// ─── Fase final ────────────────────────────────────────────────────
const fase = ref('formulario') // 'formulario' | 'resultado'
const ultimasAsignadas = ref([])
const origenAsignadoNro = ref('')
const origenAsignadoAnio = ref('')
const origenAsignadoPeriodo = ref('')
const gruposActualizados = ref([])

// Guardamos el código y nombre completo del docente que estaba
// seleccionado al momento de terminar la asignación. La consulta de
// aplicarEnGrupos solo devuelve el CODIGO del docente (no hace join
// con DOCENTES), así que usamos este mapa para mostrar el nombre en
// vez del código en las tablas de resultado (dentro de ResultadoAsignacionResolucion).
const docenteAsignadoCodigo = ref('')
const docenteAsignadoNombre = ref('')

const origenAnioPeriodoLabel = computed(() => {
  if (!origenAsignadoAnio.value && !origenAsignadoPeriodo.value) return '—'
  return `${origenAsignadoAnio.value || '—'} / ${origenAsignadoPeriodo.value || '—'}`
})

// El controller aplicarEnGrupos usa DB::select() crudo (sin pasar por
// mapKeys/toCamel), así que la respuesta viene con las columnas tal
// cual están en SQL Server: ANIO, PERIODO, PLAN, MATERIA, GRUPO,
// DOCENTE, TIPO, RESOLUCION, DESIGNACION (todo en MAYÚSCULAS).
// Normalizamos a minúsculas acá para que el template sea simple
// y no dependa del casing exacto que devuelva el backend.
function normalizarGrupo(g) {
  return {
    anio: g.anio ?? g.ANIO ?? '',
    periodo: g.periodo ?? g.PERIODO ?? '',
    plan: g.plan ?? g.PLAN ?? '',
    materia: g.materia ?? g.MATERIA ?? '',
    grupo: g.grupo ?? g.GRUPO ?? '',
    docente: g.docente ?? g.DOCENTE ?? '',
    tipo: g.tipo ?? g.TIPO ?? '',
    tipoIngreso: g.tipoIngreso ?? g.tipo_ingreso ?? g.TIPO_INGRESO ?? '',
    resolucion: g.resolucion ?? g.RESOLUCION ?? '',
    designacion: g.designacion ?? g.DESIGNACION ?? '',
  }
}

// Abre, en una pestaña nueva, el reporte de materias dictadas del
// docente correspondiente a esa fila de la tabla de grupos actualizados.
function verReporte(g) {
  const anioQuery = g.periodo ? `${g.anio}/${g.periodo}` : (g.anio || undefined)

  const ruta = router.resolve({
    name: 'reporte',
    query: {
      codigo: g.docente,
      ...(anioQuery ? { anio: anioQuery } : {}),
    },
  })
  window.open(ruta.href, '_blank')
}

async function handleTerminar() {
  errorLocal.value = ''
  try {
    origenAsignadoNro.value = origenActivo.value?.nroResolucion ?? ''
    origenAsignadoAnio.value = origenActivo.value?.anio ?? ''
    origenAsignadoPeriodo.value = origenActivo.value?.periodo ?? ''
    ultimasAsignadas.value = [...materiasMarcadas.value]

    // Capturamos código y nombre del docente ANTES de que se pueda
    // limpiar la selección, para poder mostrarlo en la fase de
    // resultado en vez del código crudo que devuelve aplicarEnGrupos.
    docenteAsignadoCodigo.value = docenteCodActual.value ?? ''
    const nombres = selectedDocente.value?.nombres ?? selectedDocente.value?.NOMBRES ?? ''
    const apellidos = selectedDocente.value?.apellidos ?? selectedDocente.value?.APELLIDOS ?? ''
    docenteAsignadoNombre.value = `${nombres} ${apellidos}`.trim()

    const { idResolucion } = await confirmarAsignacion()
    const resultado = await aplicarEnGrupos(idResolucion)

    gruposActualizados.value = (resultado?.grupos ?? []).map(normalizarGrupo)

    fase.value = 'resultado'
  } catch (e) {
    errorLocal.value = e?.message ?? 'Error al guardar la asignación.'
  }
}

function asignarOtraMas() {
  limpiarTodo()
  onLimpiarDocente()
  onLimpiarBusquedaOrigen()
  gruposActualizados.value = []
  fase.value = 'formulario'
}

// Preselección por query string (solo tiene sentido para resoluciones,
// que es el flujo que ya venía desde un link externo con ?resolucion=).
onMounted(() => {
  const { resolucion: idResolucion, nro, anio, periodo } = route.query
  if (idResolucion && tipo.value === 'resolucion') {
    seleccionarOrigen({
      idResolucion,       // ResolucionSearchPicker usa r.idResolucion ?? r.id_resolucion
      nroResolucion: nro,
      anio,
      periodo,
    })
  }
})
</script>