<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 p-6 transition-colors">
    <div class="mb-6">
      <h1 class="text-[20px] font-bold text-gray-1000 dark:text-white">Reporte de Tipo de Ingreso</h1>
      <p class="text-[14px] text-gray-600 dark:text-slate-400 mt-1">
        Docentes activos del periodo, su materia y la categoría con la que ingresaron
      </p>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 mb-6">
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-800 dark:bg-slate-950 rounded-t-xl">
        <span class="text-[13px] font-bold text-white">Filtros</span>
      </div>

      <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Gestión (año) *</label>
          <input v-model.number="filtros.anio" type="number"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400" />
        </div>

        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Periodo *</label>
          <select v-model="filtros.periodo"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400">
            <option value="1">1</option>
            <option value="4">Invierno</option>
            <option value="2">2</option>
            <option value="3">Verano</option>
          </select>
        </div>

        <!-- Tipo de ingreso: selección múltiple -->
        <div class="md:col-span-2 relative" ref="dropRef">
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">
            Tipo de ingreso (puedes elegir varios)
          </label>
          <button type="button" @click="abierto = !abierto"
            class="mt-1 w-full flex items-center justify-between border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 text-left focus:outline-none focus:border-blue-600">
            <span class="truncate">{{ textoSeleccion }}</span>
            <span class="ml-2 text-gray-500">▾</span>
          </button>

          <div v-if="abierto"
            class="absolute z-20 mt-1 w-full max-h-64 overflow-auto bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-lg shadow-lg p-1">
            <label v-for="o in opciones" :key="o.value"
              class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-slate-800 text-[13px] text-gray-800 dark:text-slate-200 cursor-pointer">
              <input type="checkbox" :value="o.value" v-model="filtros.categorias" />
              {{ o.label }}
            </label>
            <div class="flex justify-between border-t border-gray-200 dark:border-slate-700 mt-1 pt-1 px-2 text-[12px]">
              <button type="button" class="text-blue-700 dark:text-blue-300 py-1" @click="filtros.categorias = []">Todas</button>
              <button type="button" class="text-gray-600 dark:text-slate-400 py-1" @click="abierto = false">Cerrar</button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 dark:bg-slate-800/60 border-t border-gray-200 dark:border-slate-700 rounded-b-xl">
        <button @click="onBuscar" :disabled="loading || !filtros.anio || !filtros.periodo"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-800 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          <svg v-if="loading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Generar lista
        </button>

        <button @click="onPdf('open')" :disabled="!datosFiltrados.length"
          class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          Ver PDF
        </button>

        <button @click="onPdf('save')" :disabled="!datosFiltrados.length"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors">
          Descargar PDF
        </button>

        <button @click="onExcel" :disabled="!datosFiltrados.length"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Descargar Excel
        </button>
      </div>
    </div>

    <div v-if="error || errorDoc"
      class="mb-4 flex items-center gap-2 p-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/40 rounded-lg text-red-600 dark:text-red-300 text-[12px]">
      {{ error || errorDoc }}
    </div>

    <!-- Resultados -->
    <div v-if="datos.length">
      <!-- Chips resumen por categoría -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span v-for="r in resumenCategorias" :key="r.categoria"
          class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-800 dark:bg-indigo-500/15 dark:border-indigo-500/40 dark:text-indigo-300 text-[12px] font-bold uppercase">
          {{ r.categoria }}
          <span class="rounded-full px-1.5 text-[10px] bg-white/80 dark:bg-white/15">{{ r.docentes }}</span>
        </span>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="flex flex-wrap items-center gap-2">
          <p class="text-[13px] font-semibold text-gray-700 dark:text-slate-300">
            Resultados ({{ datosFiltrados.length }}<span v-if="datosFiltrados.length !== datos.length"> de {{ datos.length }}</span>)
          </p>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-300 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/40">
            {{ totalDocentes }} docente{{ totalDocentes === 1 ? '' : 's' }}
          </span>
        </div>

        <div class="relative w-full sm:w-72">
          <input v-model="busqueda" type="text" placeholder="Buscar código, docente o materia…"
            class="w-full border border-gray-300 dark:border-slate-600 rounded-lg pl-3 pr-8 py-1.5 text-[12px] text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-700 dark:focus:border-blue-400" />
          <button v-if="busqueda" @click="busqueda = ''"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:text-slate-500 dark:hover:text-slate-200 text-[14px] leading-none">✕</button>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="bg-slate-800 dark:bg-slate-950 text-white text-left">
                <th class="px-3 py-2.5 font-semibold">Nº</th>
                <th class="px-3 py-2.5 font-semibold">Código</th>
                <th class="px-3 py-2.5 font-semibold">Docente</th>
                <th class="px-3 py-2.5 font-semibold">Materia</th>
                <th class="px-3 py-2.5 font-semibold text-center">Grp</th>
                <th class="px-3 py-2.5 font-semibold">Tipo de ingreso</th>
                <th class="px-3 py-2.5 font-semibold text-center">Documento</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in datosFiltrados"
                :key="`${row.cod_docente}-${row.plan}-${row.materia_codigo}-${row.grp}-${row.categoria}-${i}`"
                class="border-t border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 align-top transition-colors hover:bg-sky-100/70 dark:hover:bg-sky-500/10"
                :class="i % 2 === 1 ? 'bg-sky-50 dark:bg-slate-700/40' : 'bg-white dark:bg-slate-800'">
                <td class="px-3 py-2">{{ i + 1 }}</td>
                <td class="px-3 py-2">{{ row.cod_docente }}</td>

                <!-- Docente: clic abre el menú de acciones (inscritos / horario) -->
                <td class="px-3 py-2 uppercase">
                  <button @click="abrirMenuDocente(row)" title="Ver opciones del docente"
                    class="text-left font-semibold text-blue-800 dark:text-blue-300 hover:underline uppercase">
                    {{ row.apellidos }} {{ row.nombres }}
                  </button>
                </td>

                <td class="px-3 py-2">
                  <span class="text-gray-500 dark:text-slate-400">{{ row.materia_codigo }}</span>
                  {{ row.materia }}
                </td>
                <td class="px-3 py-2 text-center">{{ row.grp }}</td>
                <td class="px-3 py-2">
                  <span class="inline-block px-2 py-0.5 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-800 dark:bg-indigo-500/15 dark:border-indigo-500/40 dark:text-indigo-300 text-[11px] font-bold uppercase">
                    {{ row.categoria || 'Sin categoría' }}
                  </span>
                  <p v-if="row.resolucion || row.detalle" class="mt-1 text-gray-500 dark:text-slate-400">
                    <span v-if="row.resolucion" class="font-semibold">{{ row.resolucion }}</span>
                    <span v-if="row.resolucion && row.detalle"> · </span>
                    <span v-if="row.detalle">{{ row.detalle }}</span>
                  </p>
                </td>
                <td class="px-3 py-2 text-center">
                  <button v-if="row.tiene_archivo && row.id" @click="ver({ id: row.id })"
                    :disabled="cargandoId === row.id"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-300 font-semibold whitespace-nowrap transition-colors disabled:opacity-50">
                    {{ cargandoId === row.id ? 'Abriendo…' : 'Ver PDF' }}
                  </button>
                  <span v-else class="text-gray-400 dark:text-slate-500">Sin PDF</span>
                </td>
              </tr>

              <tr v-if="!datosFiltrados.length">
                <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-slate-400">
                  Ningún resultado coincide con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="buscoAlMenosUnaVez && !loading"
      class="text-center py-10 text-[13px] text-gray-800 dark:text-slate-300">
      No se encontraron docentes activos con ese tipo de ingreso en el periodo.
    </div>

    <!-- ═════ Menú de acciones del docente (abre en pestaña nueva) ═════ -->
    <div v-if="docenteMenu"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="docenteMenu = null">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-xl w-full max-w-sm overflow-hidden">

        <!-- Cabecera con avatar -->
        <div class="relative flex items-center gap-3 pl-6 pr-4 py-3.5 bg-slate-800 dark:bg-slate-950 text-white">
          <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600"></div>

          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-700 to-violet-600 text-white text-[13px] font-bold flex items-center justify-center shrink-0 tracking-wide">
            {{ iniciales(docenteMenu) }}
          </div>

          <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-bold leading-tight uppercase truncate">
              {{ docenteMenu.apellidos }} {{ docenteMenu.nombres }}
            </h2>
            <p class="text-[11px] text-slate-300 mt-0.5">
              SIS: {{ docenteMenu.cod_docente }} · {{ filtros.anio }} - {{ nombrePeriodo }}
            </p>
          </div>

          <button @click="docenteMenu = null" aria-label="Cerrar"
            class="w-7 h-7 flex items-center justify-center rounded-full text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Opciones: solo inscritos y horario -->
        <div class="p-2.5 space-y-1.5">
          <button v-for="a in accionesDocente" :key="a.key" @click="irA(a.key)"
            class="group w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-left transition-all"
            :class="a.hover">
            <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="a.tile">
              <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" :d="a.icono" />
              </svg>
            </span>

            <span class="min-w-0 flex-1">
              <span class="block text-[13px] font-semibold text-gray-900 dark:text-slate-100 leading-tight">{{ a.titulo }}</span>
              <span class="block text-[11px] text-gray-500 dark:text-slate-400 mt-0.5">{{ a.desc }}</span>
            </span>

            <svg class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:text-gray-500 dark:group-hover:text-slate-300 group-hover:translate-x-0.5 transition-all shrink-0"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useTipoIngreso, SIN_CATEGORIA, CATEGORIA_DEFECTO } from '../composables/useTiposIngreso'
import { useDocumentoTitulo } from '../composables/useDocumentoTitulo'
import { norm } from '../composables/utilsTitulo'
import { generarPDF } from '../composables/useGenerarPDF'
import { exportarReporteTitulos } from '../composables/exportarXlsx'
import { usePeriodoAcademicoActual } from '@/modules/periodos-academicos/composables/usePeriodoAcademicoActual'

const router = useRouter()

const { categorias, datos, loading, error, cargarCategorias, buscar } = useTipoIngreso()
const { cargandoId, errorDoc, ver } = useDocumentoTitulo()

// Año y periodo actuales (fallback por mes + resolución contra la BD)
const { anio: anioActual, periodo: periodoActual, promesa: promesaPeriodoActual } = usePeriodoAcademicoActual()

const filtros = reactive({
  anio: anioActual.value,
  periodo: String(periodoActual.value),
  categorias: [CATEGORIA_DEFECTO], // vacío = todas
})

promesaPeriodoActual.then(() => {
  filtros.anio = anioActual.value
  filtros.periodo = String(periodoActual.value)
})

const buscoAlMenosUnaVez = ref(false)
const busqueda = ref('')

const nombrePeriodo = computed(() => {
  const nombres = { 1: '1', 2: '2', 3: 'Verano', 4: 'Invierno' }
  return nombres[Number(filtros.periodo)] ?? filtros.periodo
})

// ── Multi-select de tipo de ingreso ──
const abierto = ref(false)
const dropRef = ref(null)

const opciones = computed(() => [
  ...categorias.value.map((c) => ({ value: c, label: c })),
  { value: SIN_CATEGORIA, label: 'Sin tipo de ingreso' },
])

const textoSeleccion = computed(() => {
  if (!filtros.categorias.length) return 'Todas'
  return filtros.categorias
    .map((v) => (v === SIN_CATEGORIA ? 'Sin tipo de ingreso' : v))
    .join(', ')
})

function cerrarAlClickFuera(e) {
  if (dropRef.value && !dropRef.value.contains(e.target)) abierto.value = false
}

// ── Menú de acciones por docente (solo inscritos y horario) ─────────────────
const docenteMenu = ref(null)

const accionesDocente = [
  {
    key: 'inscritos', titulo: 'Ver sus inscritos', desc: 'Lista de inscritos del periodo',
    icono: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    tile: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    hover: 'hover:border-emerald-300 hover:bg-emerald-50/40 dark:hover:border-emerald-500/50 dark:hover:bg-emerald-500/5',
  },
  {
    key: 'horario', titulo: 'Ver su horario', desc: 'Carga horaria del periodo',
    icono: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    tile: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    hover: 'hover:border-amber-300 hover:bg-amber-50/40 dark:hover:border-amber-500/50 dark:hover:bg-amber-500/5',
  },
]

// Iniciales para el avatar de la cabecera del menú
const iniciales = (d) =>
  ((d?.nombres?.[0] ?? '') + (d?.apellidos?.[0] ?? '')).toUpperCase() || '?'

const abrirMenuDocente = (row) => { docenteMenu.value = row }

function irA(destino) {
  const d = docenteMenu.value
  if (!d) return

  // Búsqueda por "APELLIDOS NOMBRES" (no por código) para inscritos y horario
  const buscarTexto = `${d.apellidos} ${d.nombres}`.trim()
  const periodo = { anio: filtros.anio, periodo: filtros.periodo }

  const destinos = {
    inscritos: { name: 'inscritos',                query: { buscar: buscarTexto, ...periodo } },
    horario:   { name: 'reporte-horario-completo', query: { buscar: buscarTexto, ...periodo } },
  }

  // Abre en una pestaña nueva
  const { href } = router.resolve(destinos[destino])
  window.open(href, '_blank')

  docenteMenu.value = null
}

// ── Buscar ──
async function onBuscar() {
  abierto.value = false
  buscoAlMenosUnaVez.value = true
  busqueda.value = ''
  await buscar({
    anio: filtros.anio,
    periodo: filtros.periodo,
    categorias: [...filtros.categorias],
  })
}

// ── Buscador local ──
const datosFiltrados = computed(() => {
  const q = norm(busqueda.value)
  if (!q) return datos.value
  return datos.value.filter((r) =>
    [r.cod_docente, r.apellidos, r.nombres, r.materia_codigo, r.materia, r.categoria]
      .map(norm).join(' ').includes(q),
  )
})

const totalDocentes = computed(() => new Set(datosFiltrados.value.map((r) => r.cod_docente)).size)

const resumenCategorias = computed(() => {
  const mapa = new Map()
  for (const r of datos.value) {
    const k = r.categoria || 'Sin categoría'
    if (!mapa.has(k)) mapa.set(k, new Set())
    mapa.get(k).add(r.cod_docente)
  }
  return [...mapa.entries()]
    .map(([categoria, set]) => ({ categoria, docentes: set.size }))
    .sort((a, b) => b.docentes - a.docentes)
})

// ── PDF (reutiliza generarPDF en modo "materia": columna DOCENTE + columna extra) ──
function onPdf(action) {
  const gestion = `${filtros.anio} - ${nombrePeriodo.value}`
  const etiquetaCat = filtros.categorias.length
    ? filtros.categorias.map((v) => (v === SIN_CATEGORIA ? 'SIN TIPO DE INGRESO' : v)).join(', ')
    : 'TODAS LAS CATEGORÍAS'

  const reporte = {
    docente: null,
    materias: datosFiltrados.value.map((r, i) => ({
      nro: i + 1,
      gestion,
      plan: r.plan,
      materia_codigo: r.materia_codigo,
      materia: r.materia,
      grp: r.grp,
      cod_docente: r.cod_docente,
      docente_nombre: `${r.apellidos} ${r.nombres}`.trim(),
      tipo_ingreso: r.categoria || 'Sin categoría',
    })),
    __extraColumnas: [{ header: 'TIPO DE INGRESO', dataKey: 'tipo_ingreso' }],
  }

  generarPDF(reporte, {
    action,
    modoMateria: true,
    sinResolucionDesignacion: true,
    ocultarCompartido: true,
    tituloPersonalizado: 'REPORTE DE TIPO DE INGRESO DE DOCENTES',
    descripcionPersonalizada:
      'Docentes activos en el periodo seleccionado, con su materia y la categoría de ingreso registrada en el SISS.',
    sujetoPersonalizado: `PERIODO : ${gestion}   ·   TIPO DE INGRESO : ${etiquetaCat}`,
    nombreArchivo: `reporte_tipo_ingreso_${filtros.anio}-${filtros.periodo}.pdf`,
  })
}

// ── Excel (reutiliza exportarReporteTitulos, igual que el reporte de títulos) ──
function onExcel() {
  const encabezados = [
    'Nº', 'Código', 'Apellidos', 'Nombres',
    'Código materia', 'Materia', 'Grp',
    'Tipo de ingreso', 'Resolución', 'Detalle', 'Documento',
  ]

  const filas = datosFiltrados.value.map((r, i) => [
    i + 1,
    r.cod_docente,
    r.apellidos,
    r.nombres,
    r.materia_codigo,
    r.materia,
    r.grp,
    r.categoria || 'Sin categoría',
    r.resolucion || '',
    r.detalle || '',
    r.tiene_archivo && r.id ? 'Con PDF' : 'Sin PDF',
  ])

  exportarReporteTitulos({
    nombre: `Reporte_Tipo_Ingreso_${filtros.anio}-${filtros.periodo}.xlsx`,
    hoja: 'Tipo de ingreso',
    anio: filtros.anio,
    periodo: filtros.periodo,
    encabezados,
    filas,
    columnasCentradas: [0, 1, 4, 6, 10],
  })
}

onMounted(async () => {
  document.addEventListener('click', cerrarAlClickFuera)
  cargarCategorias()
  try {
    await promesaPeriodoActual
  } catch (e) {
    // seguimos con el fallback por mes
  }
  if (filtros.anio && filtros.periodo) onBuscar()
})

onBeforeUnmount(() => document.removeEventListener('click', cerrarAlClickFuera))
</script>