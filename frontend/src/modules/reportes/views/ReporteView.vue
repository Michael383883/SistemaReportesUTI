<template>
  <div class="px-6 py- max-w-6xl00">

    <!-- Header de página -->
    <div class="flex items-start justify-between mb-7">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-0.5"> 
          Reporte de  Kardex Docente</h1>
        <p class="text-xs text-slate-800 m-0">Materias dictadas registradas en el SISS a partir de 2001</p>
      </div>

      <!-- Toggle Version 2 (Compartidos) -->
      <button
        type="button"
        @click="onToggleVersion"
        :disabled="loading || loadingCom"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
        :class="verCompartidos
          ? 'bg-blue-500 border-indigo-800 text-slate-100 hover:bg-amber-700'
          : 'bg-blue-800 border-slate-700 text-slate-100 hover:bg-amber-700'"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
          <path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
        </svg>
        {{ verCompartidos ? 'Versión 2 (Compartidos)' : 'Ver versión sin (Compartidos)' }}
      </button>
    </div>

    <!-- Error -->
    <div
      v-if="errorActivo"
      class="flex items-center gap-2 px-3.5 py-2.5 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm mb-5"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ errorActivo }}
    </div>

    <!-- Loading skeleton -->
    <div v-if="loadingActivo" class="space-y-3 animate-pulse">
      <div class="h-24 rounded-xl bg-slate-800 border border-slate-700"/>
      <div class="h-10 rounded-lg bg-slate-800/60 border border-slate-700/50"/>
      <div class="h-64 rounded-xl bg-slate-800 border border-slate-700"/>
    </div>

    <!-- Reporte cargado -->
    <template v-else-if="reporteActivo">
      <!-- Header del docente -->
      <ReporteHeader
        :reporte="reporteActivo"
        :loading="loadingActivo"
        :categorias="categorias"
        :tiene-categorias="tieneCategorias"
        :loading-categorias="loadingCategorias"
        :categorias-seleccionadas="categoriasSeleccionadas"
        @volver="$router.back()"
        @toggle-restriccion="onToggleRestriccion"
        @seleccionar-categorias="onSeleccionarCategorias"
      />

      <!-- Filtros: SIEMPRE visibles, tanto en modo normal como en modo documento -->
      <div class="mb-5">
        <ReporteFiltros
          v-model:anio="anioFiltro"
          v-model:anio-hasta="anioHastaFiltro"
          v-model:materia="materiaFiltro"
          v-model:grupo="grupoFiltro"
          :loading="loadingActivo"
          :reporte="reporteActivo"
          :documentos-categoria="documentosCategoria"
          :categorias-seleccionadas="categoriasSeleccionadas"
          :modo-documento="modoDocumento"
          @generar="reGenerar"
        />
      </div>

      <!-- Tabla de materias: solo en el modo normal (no en modo documento) -->
      <ReporteTabla
        v-if="!modoDocumento"
        :materias="reporteActivo.materias"
        :cod-docente="reporteActivo.docente?.codigo"
        :agrupar-compartidos="verCompartidos"
      />

      <!-- Tabla de clasificación por categoría:
           - Camino 1 (modoDocumento): se muestra sola, reemplaza a la de materias.
           - Camino 2 (modo normal): se muestra DEBAJO de la tabla de materias,
             en cuanto el usuario elige categorías desde ReporteHeader. -->
      <div v-if="modoDocumento || categoriasSeleccionadas.length > 0" :class="{ 'mt-5': !modoDocumento }">
        <ClasificacionCategoriasTabla
          :documentos="documentosCategoria"
          :loading="loadingDocumentos"
          :categorias="categoriasSeleccionadas"
          @ver-pdf="verPdfCategoria"
        />
      </div>
    </template>

    <!-- Empty -->
    <div v-else-if="!loadingActivo && !errorActivo" class="flex flex-col items-center justify-center py-20 text-center text-slate-400">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mb-3">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
      </svg>
      <p class="text-sm font-medium">No hay reporte disponible</p>
      <p class="text-xs mt-1">Seleccioná un docente desde la lista</p>
    </div>

  </div>
</template>

<script setup>
import { onMounted, ref, computed,watch  } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReporte } from '../composables/useReporte'
import { useReporteCom } from '../composables/useReporteCom'
import ReporteHeader  from '../components/ReporteHeader.vue'
import ReporteFiltros from '../components/ReporteFiltros.vue'
import ReporteTabla   from '../components/ReporteTabla.vue'
import ClasificacionCategoriasTabla from '../components/ClasificacionCategoriasTabla.vue'
import { useClasificacionCategorias } from '../composables/useClasificacionCategorias'

const route  = useRoute()
const router = useRouter()

// Modo "solo documentos": viene desde el botón "Generar Reporte de Documento"
// en la lista de docentes, ej: router.push({ query: { codigo, modo: 'documentos' } })
const modoDocumento = computed(() => route.query.modo === 'documentos')

// Version 1: reporte normal
const { reporte, loading, error, generarReporte } = useReporte()

// Version 2: reporte de compartidos
const { reporte: reporteCom, loading: loadingCom, error: errorCom, generarReporte: generarReporteCom } = useReporteCom()

// Cuál versión se muestra actualmente
const verCompartidos = ref(false)

// Datos "activos" según la versión elegida — todo el template los usa a través
// de estos computed en vez de referenciar reporte/reporteCom directamente
const reporteActivo  = computed(() => verCompartidos.value ? reporteCom.value  : reporte.value)
const loadingActivo  = computed(() => verCompartidos.value ? loadingCom.value  : loading.value)
const errorActivo    = computed(() => verCompartidos.value ? errorCom.value    : error.value)

// anioFiltro guarda el string crudo, ej: "2016" o "2016/1" (lo que el usuario tipeó)
const anioFiltro      = ref(null)
const anioHastaFiltro = ref(null)
const materiaFiltro   = ref(null)
const grupoFiltro     = ref(null)

//ss
const {
  categorias, tieneDocumentos: tieneCategorias, documentos: documentosCategoria,
  categoriasSeleccionadas, loadingCategorias, loadingDocumentos,
  cargarCategorias, cargarDocumentos, limpiar: limpiarCategorias, verPdf: verPdfCategoria,
} = useClasificacionCategorias()

const onSeleccionarCategorias = (cats) => {
  const codigo = reporteActivo.value?.docente?.codigo
  if (!codigo) return
  cargarDocumentos(codigo, cats)
}

// ── Estado de habilitación de periodo restringido ──────────────────────────
// Se activa solo con el botón de ReporteHeader. Se mantiene en memoria para
// que, si el usuario cambia otros filtros y regenera, el periodo habilitado
// siga apareciendo (si no, se volvería a ocultar en cada regeneración).
const habilitarRestriccion = ref(false)
const anioHabilitado       = ref(null)
const periodoHabilitado    = ref(null)

// Parsea "2016", "2016/1", "2016-2" → { anio, periodo }
function parseAnioPeriodo(valorCrudo) {
  const valor = (valorCrudo ?? '').toString().trim()
  if (!valor) return { anio: null, periodo: null }

  const match = valor.match(/^(\d{4})\s*[\/\-\s]?\s*([1-4])?$/)
  if (!match) return { anio: Number(valor) || null, periodo: null }

  const [, anioStr, periodoStr] = match
  return { anio: Number(anioStr), periodo: periodoStr || null }
}

// Arma los parámetros actuales de filtros/restricción para llamar a generarReporte(Com)
function paramsActuales() {
  const { anio: anioNum, periodo }                   = parseAnioPeriodo(anioFiltro.value)
  const { anio: anioHastaNum, periodo: periodoHasta } = parseAnioPeriodo(anioHastaFiltro.value)

  return {
    anio: anioNum,
    periodo,
    anioHasta: anioHastaNum,
    periodoHasta,
    materia: materiaFiltro.value,
    grupo: grupoFiltro.value,
    habilitarRestriccion: habilitarRestriccion.value,
    anioHabilitado:       anioHabilitado.value,
    periodoHabilitado:    periodoHabilitado.value,
  }
}

onMounted(async () => {
  const codigo       = route.query.codigo
  const anio         = route.query.anio || null
  const anioHasta    = route.query.anioHasta || null
  const materia      = route.query.materia || null
  const grupo        = route.query.grupo || null

  if (!codigo) { router.replace({ name: 'docentes' }); return }

  anioFiltro.value      = anio
  anioHastaFiltro.value = anioHasta
  materiaFiltro.value   = materia
  grupoFiltro.value     = grupo

  const { anio: anioNum, periodo }                   = parseAnioPeriodo(anio)
  const { anio: anioHastaNum, periodo: periodoHasta } = parseAnioPeriodo(anioHasta)

  await generarReporte(codigo, {
    anio: anioNum,
    periodo,
    anioHasta: anioHastaNum,
    periodoHasta,
    materia,
    grupo,
  })
})

const reGenerar = async ({ anio, periodo, anioHasta, periodoHasta, materia, grupo }) => {

  const codigo = route.query.codigo
  if (!codigo) return

  const anioQuery      = periodo      ? `${anio}/${periodo}`           : (anio ?? null)
  const anioHastaQuery = periodoHasta ? `${anioHasta}/${periodoHasta}` : (anioHasta ?? null)


  router.replace({
    query: {
      codigo,
      ...(anioQuery      ? { anio: anioQuery }           : {}),
      ...(anioHastaQuery ? { anioHasta: anioHastaQuery } : {}),
      ...(materia         ? { materia }                  : {}),
      ...(grupo           ? { grupo }                    : {}),
      // Preserva el modo (documentos) al re-generar, si estaba presente
      ...(route.query.modo ? { modo: route.query.modo } : {}),
    }
  })

  const payload = {
    anio, periodo, anioHasta, periodoHasta, materia, grupo,
    // Se reenvía el estado de habilitación vigente, para que no se pierda
    // al cambiar otros filtros (año, materia, grupo, etc.)
    habilitarRestriccion: habilitarRestriccion.value,
    anioHabilitado:       anioHabilitado.value,
    periodoHabilitado:    periodoHabilitado.value,
  }

  // Regenera la versión que está visible actualmente
  if (verCompartidos.value) {
    await generarReporteCom(codigo, payload)
  } else {
    await generarReporte(codigo, payload)
  }
}

// ── Click en el botón de ReporteHeader ──────────────────────────────────────
// { anio, periodo, habilitar } viene del componente ReporteHeader.vue
const onToggleRestriccion = async ({ anio, periodo, habilitar }) => {
  habilitarRestriccion.value = habilitar
  anioHabilitado.value       = habilitar ? anio : null
  periodoHabilitado.value    = habilitar ? periodo : null

  const codigo = route.query.codigo
  if (!codigo) return

  const params = paramsActuales()

  if (verCompartidos.value) {
    await generarReporteCom(codigo, params)
  } else {
    await generarReporte(codigo, params)
  }
}

// ── Click en el botón "Versión 2 (Compartidos)" ─────────────────────────────
// Cambia qué tabla se muestra. Siempre regenera la versión recién activada
// con los filtros vigentes, para que no se muestre una copia en caché que
// quedó desactualizada respecto a los filtros aplicados mientras se veía
// la otra versión (por eso antes había que apretar "Re-generar" a mano).
const onToggleVersion = async () => {
  verCompartidos.value = !verCompartidos.value

  const codigo = route.query.codigo
  if (!codigo) return

  const params = paramsActuales()

  if (verCompartidos.value) {
    await generarReporteCom(codigo, params)
  } else {
    await generarReporte(codigo, params)
  }
}

watch(
  () => reporteActivo.value?.docente?.codigo,
  (codigo) => {
    limpiarCategorias()
    if (codigo) cargarCategorias(codigo)
  }
)

// ── Modo "Generar Reporte de Documento": al llegar con ?modo=documentos,
// apenas se cargan las categorías del docente, se seleccionan TODAS
// automáticamente (mismo efecto que tocar "Todas" en el dropdown de
// ReporteHeader), para no obligar al usuario a habilitarlas a mano. ──────
watch(
  categorias,
  (cats) => {
    if (!modoDocumento.value) return
    if (!cats.length) return
    // Sólo auto-selecciona si todavía no hay nada elegido (evita pisar
    // una deselección manual que haga el usuario después)
    if (categoriasSeleccionadas.value.length > 0) return

    onSeleccionarCategorias([...cats])
  },
  { immediate: true }
)

</script>