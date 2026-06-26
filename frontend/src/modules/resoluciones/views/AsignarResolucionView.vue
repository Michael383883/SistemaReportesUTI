<template>
  <div class="px-6 py-2 max-w-6xl">

    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div>
            <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
            Asignación de Resoluciones a Docentes
        </h1>
        <p class="text-xs text-slate-400 m-0">
          Buscá un docente, elegí la resolución y marcá las materias correspondientes con un click.
        </p>
      </div>
    </div>

    <!-- Vista de resultado final: detalles guardados + grupos actualizados -->
    <div v-if="fase === 'resultado'" class="space-y-5">
      <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-700 flex items-center gap-3">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
            :class="gruposActualizados.length > 0 ? 'bg-emerald-500/15' : 'bg-amber-500/15'"
          >
            <svg v-if="gruposActualizados.length > 0" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-medium text-slate-100 m-0">
              {{ gruposActualizados.length > 0 ? 'Resolución asignada y aplicada en grupos' : 'Resolución asignada, pero no se aplicó en grupos' }}
            </h2>
            <p class="text-xs text-slate-400 m-0 mt-0.5">
              {{ ultimasAsignadas.length }} materia{{ ultimasAsignadas.length !== 1 ? 's' : '' }} vinculada{{ ultimasAsignadas.length !== 1 ? 's' : '' }} a {{ resolucionAsignadaNro }}
              · {{ gruposActualizados.length }} registro{{ gruposActualizados.length !== 1 ? 's' : '' }} actualizados en grupos
            </p>
          </div>
        </div>

        <!-- Tabla de grupos actualizados -->
        <div>
          <div class="px-6 py-3 bg-slate-900/40 flex items-center gap-2">
            <p class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Registros actualizados en grupos</p>
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] font-semibold">
              {{ gruposActualizados.length }}
            </span>
          </div>

          <div v-if="gruposActualizados.length > 0" class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="bg-slate-900/40 border-b border-slate-700">
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Año</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Per.</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Plan</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Grupo</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Docente</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Tipo</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Tipo de ingreso</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Resolución</th>
                  <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Designación</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700/60">
                <tr v-for="(g, i) in gruposActualizados" :key="i" class="hover:bg-emerald-500/[0.04] transition-colors">
                  <td class="px-4 py-2.5 text-slate-300 font-mono">{{ g.anio }}</td>
                  <td class="px-4 py-2.5 text-slate-400">{{ g.periodo }}</td>
                  <td class="px-4 py-2.5 text-slate-400 font-mono">{{ g.plan }}</td>
                  <td class="px-4 py-2.5 text-slate-400 font-mono">{{ g.materia }}</td>
                  <td class="px-4 py-2.5 text-slate-400">{{ g.grupo }}</td>
                  <td class="px-4 py-2.5 text-slate-300 font-mono">{{ g.docente }}</td>
                  <td class="px-4 py-2.5 text-slate-400">{{ g.tipo }}</td>
                  <td class="px-4 py-2.5 text-sky-400">{{ g.tipoIngreso || '—' }}</td>
                  <td class="px-4 py-2.5 text-amber-400 font-medium">{{ g.resolucion }}</td>
                  <td class="px-4 py-2.5 text-slate-400 max-w-xs truncate" :title="g.designacion">{{ g.designacion }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="px-6 py-6">
            <div class="flex items-start gap-3 px-4 py-3.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
              <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <div>
                <p class="text-xs font-semibold text-amber-300 m-0">
                  La resolución se guardó, pero no se actualizó ningún registro en grupos
                </p>
                <p class="text-xs text-slate-400 m-0 mt-1 leading-relaxed">
                  Las {{ ultimasAsignadas.length }} materia{{ ultimasAsignadas.length !== 1 ? 's' : '' }} qued{{ ultimasAsignadas.length !== 1 ? 'aron' : 'ó' }} vinculada{{ ultimasAsignadas.length !== 1 ? 's' : '' }} a
                  <span class="font-medium text-slate-300">{{ resolucionAsignadaNro }}</span>, pero en la tabla de grupos no existe
                  ningún registro con ese mismo año y periodo para esa combinación de docente, plan, materia y grupo.
                  Esto suele pasar cuando la materia marcada corresponde a una gestión distinta a la de la resolución.
                </p>
              </div>
            </div>

            <!-- Detalle de lo que se intentó vincular, para que el usuario pueda revisar qué falló -->
            <div v-if="ultimasAsignadas.length > 0" class="mt-4 overflow-x-auto rounded-lg border border-slate-700">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-slate-900/40 border-b border-slate-700">
                    <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-500">Docente</th>
                    <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-500">Plan</th>
                    <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-500">Materia</th>
                    <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-500">Grupo</th>
                    <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-500">Gestión marcada</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                  <tr v-for="(m, i) in ultimasAsignadas" :key="i">
                    <td class="px-3 py-2 text-slate-300 font-mono">{{ m.cod_docente }}</td>
                    <td class="px-3 py-2 text-slate-400 font-mono">{{ m.cod_plan }}</td>
                    <td class="px-3 py-2 text-slate-400 font-mono">{{ m.cod_materia }}</td>
                    <td class="px-3 py-2 text-slate-400">{{ m.grupo ?? '—' }}</td>
                    <td class="px-3 py-2 text-red-400 font-medium">{{ m.gestion ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p class="text-[0.68rem] text-slate-500 mt-3 mb-0">
              Revisá en la tabla de grupos si existe un registro para este docente/materia/grupo con el mismo año y periodo que la resolución
              ({{ resolucionAnioPeriodoLabel }}). Si la materia corresponde a otra gestión, puede que necesites otra resolución o corregir el dato en grupos.
            </p>
          </div>
        </div>

        <div class="flex items-center justify-end px-6 py-4 border-t border-slate-700 bg-slate-900/30 gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-medium border border-slate-700 text-slate-300 hover:bg-white/5 transition-colors"
            @click="asignarOtraMas"
          >
            Asignar otra resolución
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 text-xs font-semibold rounded-lg transition-colors"
            @click="$router.push({ name: 'resoluciones-listado' })"
          >
            Ir al listado de resoluciones
          </button>
        </div>
      </div>
    </div>

    <!-- Flujo principal -->
    <template v-else>
      <!-- Paso 1 y 2: Docente + Resolución lado a lado -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 items-start">
        <!-- Paso 1: Docente -->
        <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden h-full flex flex-col">
          <div class="px-5 py-3 border-b border-slate-700">
            <h3 class="text-sm font-semibold text-slate-100 m-0">1. Buscá el docente</h3>
            <p class="text-xs text-slate-400 m-0 mt-0.5">Encontrá al docente y mirá sus materias dictadas</p>
          </div>
          <div class="px-5 py-3 flex-1">
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

        <!-- Paso 2: Resolución -->
        <ResolucionSearchPicker
          :filas="filasResolucion"
          :loading="loadingResoluciones"
          :error="errorResoluciones"
          :busqueda="busquedaResolucion"
          :resolucion-activa="resolucionActiva"
          :bloqueado="resolucionBloqueada"
          @buscar="buscar"
          @limpiar-busqueda="limpiarBusqueda"
          @select="onSeleccionarResolucion"
          @limpiar="onLimpiarResolucion"
        />
      </div>

      <!-- Materias del docente seleccionado -->
      <div v-if="selectedDocente" class="mb-4">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Materias dictadas — {{ selectedDocente.nombres ?? selectedDocente.NOMBRES }} {{ selectedDocente.apellidos ?? selectedDocente.APELLIDOS }}
          </p>

          <!-- Filtros por año y gestión -->
          <div class="flex items-center gap-2">
            <span
              v-if="resolucionActiva && (filtroAnio || filtroGestion)"
              class="text-[0.65rem] text-amber-400/80 flex items-center gap-1"
              title="Filtro aplicado automáticamente según el periodo de la resolución seleccionada"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              según resolución
            </span>

            <select
              v-model="filtroAnio"
              class="bg-slate-800 border border-slate-700 text-slate-300 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition-colors
                     cursor-pointer"
            >
              <option value="">Todos los años</option>
              <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
            </select>

            <select
              v-model="filtroGestion"
              class="bg-slate-800 border border-slate-700 text-slate-300 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition-colors
                     cursor-pointer"
            >
              <option value="">Todas las gestiones</option>
              <option v-for="g in gestionesDisponibles" :key="g" :value="g">{{ g }}</option>
            </select>

            <button
              v-if="filtroAnio || filtroGestion"
              type="button"
              class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-200 transition-colors"
              @click="filtroAnio = ''; filtroGestion = ''"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              Limpiar
            </button>
          </div>

          <span v-if="!resolucionActiva" class="text-xs text-slate-500 flex items-center gap-1.5">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Elegí una resolución para poder asignar
          </span>
        </div>

        <div v-if="loadingReporte" class="h-40 rounded-xl bg-slate-800 border border-slate-700 animate-pulse"/>
        <div v-else-if="errorReporte" class="px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">
          {{ errorReporte }}
        </div>
        <MateriasAsignarTabla
          v-else-if="reporte"
          :materias="materiasFiltradas"
          :resolucion-activa="resolucionActiva"
          :marcadas-keys="materiasMarcadas.map(m => m.key)"
          :docente-cod="docenteCodActual"
          @toggle="(m) => toggleMateria(selectedDocente, m)"
        />
      </div>

      <!-- Resumen / confirmar -->
      <MateriasMarcadasResumen
        :materias="materiasMarcadas"
        :guardando="guardando"
        :error="errorGuardado || errorLocal"
        @quitar="quitarMateria"
        @limpiar-todo="limpiarTodo"
        @terminar="handleTerminar"
      />
    </template>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import DocenteSearch from '../../docentes/components/DocenteSearch.vue'
import { useDocentes } from '../../docentes/composables/useDocentes'
import { useReporte } from '../../reportes/composables/useReporte'
import { useResolucionListado } from '../composables/useResolucionListado'
import { useAsignacionResolucion } from '../composables/useAsignacionResolucion'
import ResolucionSearchPicker from '../components/ResolucionSearchPicker.vue'
import MateriasAsignarTabla from '../components/MateriasAsignarTabla.vue'
import MateriasMarcadasResumen from '../components/MateriasMarcadasResumen.vue'

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

// ─── Resoluciones (buscador) ──────────────────────────────────────
const {
  filas: filasResolucion,
  loading: loadingResoluciones,
  error: errorResoluciones,
  busqueda: busquedaResolucion,
  buscar,
  limpiarBusqueda,
} = useResolucionListado()

// ─── Orquestador de asignación ────────────────────────────────────
const {
  resolucionActiva,
  materiasMarcadas,
  resolucionBloqueada,
  guardando,
  errorGuardado,
  seleccionarResolucion,
  limpiarResolucion,
  toggleMateria,
  quitarMateria,
  limpiarTodo,
  confirmarAsignacion,
  aplicarEnGrupos,
} = useAsignacionResolucion()

const errorLocal = ref('')

function onSeleccionarResolucion(r) {
  seleccionarResolucion(r)
}

function onLimpiarResolucion() {
  limpiarResolucion()
}

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

// ─── Auto-filtro por año/periodo de la resolución activa ─────────
// Cuando se elige una resolución, si tiene anio/periodo definidos y
// alguno de esos valores existe entre las opciones disponibles del
// docente actual, se preseleccionan los filtros automáticamente.
// Es solo un valor por defecto: el usuario puede cambiarlo después.
watch(resolucionActiva, (nueva) => {
  if (!nueva) return

  const anioResolucion = String(nueva.anio ?? '').trim()
  const periodoResolucion = String(nueva.periodo ?? '').trim()

  if (anioResolucion && aniosDisponibles.value.includes(anioResolucion)) {
    filtroAnio.value = anioResolucion
  }
  if (periodoResolucion && gestionesDisponibles.value.includes(periodoResolucion)) {
    filtroGestion.value = periodoResolucion
  }
})

// También se aplica si el docente se selecciona/cambia después de
// ya haber elegido la resolución (el orden de los pasos es libre).
watch(materiasDelReporte, () => {
  if (!resolucionActiva.value) return

  const anioResolucion = String(resolucionActiva.value.anio ?? '').trim()
  const periodoResolucion = String(resolucionActiva.value.periodo ?? '').trim()

  if (anioResolucion && aniosDisponibles.value.includes(anioResolucion) && !filtroAnio.value) {
    filtroAnio.value = anioResolucion
  }
  if (periodoResolucion && gestionesDisponibles.value.includes(periodoResolucion) && !filtroGestion.value) {
    filtroGestion.value = periodoResolucion
  }
})

// ─── Fase final ────────────────────────────────────────────────────
const fase = ref('formulario') // 'formulario' | 'resultado'
const ultimasAsignadas = ref([])
const resolucionAsignadaNro = ref('')
const resolucionAsignadaAnio = ref('')
const resolucionAsignadaPeriodo = ref('')
const gruposActualizados = ref([])

const resolucionAnioPeriodoLabel = computed(() => {
  if (!resolucionAsignadaAnio.value && !resolucionAsignadaPeriodo.value) return '—'
  return `${resolucionAsignadaAnio.value || '—'} / ${resolucionAsignadaPeriodo.value || '—'}`
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

async function handleTerminar() {
  errorLocal.value = ''
  try {
    resolucionAsignadaNro.value = resolucionActiva.value?.nroResolucion ?? ''
    resolucionAsignadaAnio.value = resolucionActiva.value?.anio ?? ''
    resolucionAsignadaPeriodo.value = resolucionActiva.value?.periodo ?? ''
    ultimasAsignadas.value = [...materiasMarcadas.value]

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
  limpiarBusqueda()
  gruposActualizados.value = []
  fase.value = 'formulario'
}
</script>