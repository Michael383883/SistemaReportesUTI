<template>
  <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden">

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-slate-900 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Materia</th>
            <!-- Modo materia: docente que dictó -->
            <th v-if="mostrarDocente" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Docente</th>
            <th v-if="!agruparCompartidos" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-14">GRP</th>
            <th v-if="agruparCompartidos" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 min-w-[320px]">Comparte</th>
            <th v-if="!ocultarResolucionDesignacion" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Resolución</th>
            <th v-if="!ocultarResolucionDesignacion" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Designación</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Modalidad de ingreso</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Documento</th>
            <!-- Referencias / compartidos (solo si algún registro tiene) -->
            <th
              v-if="tieneReferencias"
              class="px-2 py-3 w-10 text-center text-slate-100"
              title="Documentos referenciados"
            >
              <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
              </svg>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(fila, i) in filas"
            :key="fila.principal.nro ?? i"
            class="border-b border-slate-100 dark:border-slate-800 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700/40"
            :class="[
              claseFila(i),
              esCambioDocente(i) ? ['border-t-2', claseLinea(i)] : ''
            ]"
          >
            <!-- Nº (modo materia: barra lateral con el color del docente) -->
            <td
              class="px-4 py-3 text-slate-800 dark:text-slate-500 font-medium text-[13px] tabular-nums"
              :class="mostrarDocente ? ['border-l-4', claseBarra(i)] : ''"
            >{{ fila.nro }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded" :class="tipoGestion(fila.principal.gestion).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGestion(fila.principal.gestion).dot"/>
                {{ fila.principal.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3">
              <span
                v-if="fila.principal.plan"
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide"
                :class="tipoGrp(fila.principal.plan, fila.principal.materia_codigo).class"
              >
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(fila.principal.plan, fila.principal.materia_codigo).dot"/>
                {{ tipoGrp(fila.principal.plan, fila.principal.materia_codigo).label }}
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-500"
              >
                Sin grupo
              </span>
            </td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-800 dark:text-slate-200 font-medium">{{ fila.principal.materia }}</td>

            <!-- Docente (modo materia): punto del color asignado a ese docente -->
            <td v-if="mostrarDocente" class="px-4 py-3 text-slate-900 dark:text-slate-100 font-medium">
              <span class="inline-block w-2 h-2 rounded-full mr-1.5 align-middle" :class="clasePunto(i)"/>
              <span class="text-indigo-600 dark:text-indigo-400 text-xs font-semibold mr-1.5 tabular-nums">{{ fila.principal.cod_docente }}</span>{{ fila.principal.docente_nombre }}
            </td>

            <!-- Compartido (modo plano) -->
            <td v-if="!agruparCompartidos" class="px-4 py-3">
              <span v-if="fila.esHija" :title="fila.principal.comparte || 'Compartido'" class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
  Compartido
</span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP: al pasar el mouse pregunta si quiere ver el historial; click → nueva pestaña -->
            <td class="px-4 py-3 tabular-nums text-slate-700 dark:text-slate-300 font-semibold text-xs">
              <button
                v-if="puedeVerHistorial(fila.principal)"
                type="button"
                class="group relative inline-flex items-center px-1.5 py-0.5 -mx-1.5 rounded font-semibold tabular-nums
                       cursor-pointer border-none bg-transparent text-inherit
                       hover:bg-amber-100 hover:text-amber-700 dark:hover:bg-amber-500/15 dark:hover:text-amber-300
                       transition-colors"
                @click.stop="verHistorialMateria(fila.principal)"
              >
                {{ fila.principal.grp }}

                <span
                  class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 z-30
                         hidden group-hover:block whitespace-nowrap rounded-md bg-slate-900 text-slate-100
                         text-[11px] font-medium px-2.5 py-1.5 shadow-lg"
                >
                  ¿Quieres ver el historial de la materia?
                  <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900"/>
                </span>
              </button>
              <template v-else>{{ fila.principal.grp ?? '—' }}</template>
            </td>

            <!-- Comparte (modo agrupado) -->
            <td v-if="agruparCompartidos" class="px-4 py-3">
              <div v-if="fila.hermanas?.length" class="flex flex-col gap-1">
                <div v-for="(h, hi) in fila.hermanas" :key="hi" class="flex items-center gap-1.5 flex-wrap text-[11px] leading-snug">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.65rem] font-bold tracking-wide" :class="tipoGrp(h.plan, h.materia_codigo).class">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(h.plan, h.materia_codigo).dot"/>
                    {{ tipoGrp(h.plan, h.materia_codigo).label }}
                  </span>
                  <span class="text-slate-800 dark:text-slate-300 font-medium truncate">{{ h.materia }}</span>
                  <span class="text-slate-500 whitespace-nowrap">· Grp {{ h.grp }}</span>
                </div>
              </div>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Resolución -->
            <td v-if="!ocultarResolucionDesignacion" class="px-4 py-3">
              <span v-if="fila.principal.resolucion" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ fila.principal.resolucion }}</span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Designación -->
            <td v-if="!ocultarResolucionDesignacion" class="px-4 py-3 max-w-xs">
              <span v-if="fila.principal.designacion" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2" :title="fila.principal.designacion">
                {{ fila.principal.designacion }}
              </span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Modalidad de ingreso -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="fila.principal.tipo_ingreso" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2" :title="fila.principal.tipo_ingreso">
                {{ fila.principal.tipo_ingreso }}
              </span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Documento PDF -->
            <td class="px-4 py-3">
              <template v-if="fila.principal.resolucion && fila.principal.designacion">
                <div class="flex items-center gap-1.5">
                  <button
                    :disabled="loadingPdf[fila.principal.nro]"
                    @click="handleVer(fila.principal, fila.principal.cod_docente || codDocente)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Ver PDF"
                  >
                    <svg v-if="loadingPdf[fila.principal.nro]" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Ver
                  </button>

                  <button
                    :disabled="loadingPdf[fila.principal.nro]"
                    @click="handleDescargar(fila.principal, fila.principal.cod_docente || codDocente)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Descargar PDF"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                  </button>
                </div>
              </template>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Referencias / compartidos -->
            <td v-if="tieneReferencias" class="px-2 py-3 text-center">
              <button
                v-if="fila.principal.referencias?.length"
                type="button"
                @click.stop="onClickReferencias($event, fila.principal.referencias)"
                class="relative inline-flex items-center justify-center w-7 h-7 rounded-md transition-colors
                       bg-violet-50 text-violet-600 hover:bg-violet-100
                       dark:bg-violet-500/15 dark:text-violet-300 dark:hover:bg-violet-500/25"
                :title="fila.principal.referencias.length === 1
                  ? 'Abrir ' + fila.principal.referencias[0].nro
                  : fila.principal.referencias.length + ' documentos referenciados'"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <span
                  v-if="fila.principal.referencias.length > 1"
                  class="absolute -top-1 -right-1 min-w-[14px] h-[14px] px-0.5 rounded-full bg-violet-600 text-white text-[9px] font-bold leading-[14px]"
                >{{ fila.principal.referencias.length }}</span>
              </button>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-xs text-slate-500 dark:text-slate-500 flex items-start gap-4">
      <!-- Leyenda (modo materia): un color por docente -->
      <div v-if="mostrarDocente && leyendaDocentes.length" class="flex flex-wrap items-center gap-x-4 gap-y-1.5 min-w-0">
        <span class="font-medium text-slate-600 dark:text-slate-400">Docentes clasificados:</span>
        <span
          v-for="d in leyendaDocentes"
          :key="d.clave"
          class="inline-flex items-center gap-1.5 max-w-[16rem]"
          :title="d.nombre"
        >
          <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="d.punto"/>
          <span class="truncate">{{ d.nombre }}</span>
        </span>
      </div>

      <span class="ml-auto whitespace-nowrap">
        {{ filas.length }} registro{{ filas.length !== 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Desplegable de referencias (Teleport para que overflow-x-auto no lo recorte) -->
    <Teleport to="body">
      <div
        v-if="popover.abierto"
        class="fixed z-50 w-64 max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700
               bg-white dark:bg-slate-900 shadow-xl py-1"
        :style="{ top: popover.top + 'px', left: popover.left + 'px' }"
        @click.stop
      >
        <p class="px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-widest text-slate-500">
          Documentos referenciados
        </p>
        <button
          v-for="r in popover.refs"
          :key="r.id_ref"
          type="button"
          @click="abrirReferencia(r)"
          :disabled="loadingRef[r.id_ref]"
          class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-slate-700 dark:text-slate-200
                 hover:bg-violet-50 dark:hover:bg-violet-500/15 disabled:opacity-50"
        >
          <svg v-if="loadingRef[r.id_ref]" class="w-3.5 h-3.5 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          <span class="truncate font-medium">{{ r.nro }}</span>
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, toRef, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useReporte } from '../composables/useReporte'
import { useReporteCom } from '../composables/useReporteCom'
import { useTablaFormato } from '../composables/reporte/useTablaFormato'
import { useAgrupacionCompartidos } from '../composables/reporte/useAgrupacionCompartidos'
import { usePdfMateria } from '../composables/reporte/usePdfMateria'

const props = defineProps({
  materias: { type: Array, default: () => [] },
  codDocente: { type: [String, Number], default: null },
  // false (default) → tabla plana, badge "Compartido" en la fila hija
  // true             → hija colgada de la fila del padre, columna "Comparte"
  agruparCompartidos: { type: Boolean, default: false },
  // true → se ocultan las columnas "Resolución" y "Designación" de la tabla.
  // Solo afecta a lo visible: la columna "Documento" sigue usando los datos
  // de cada fila (resolucion && designacion).
  ocultarResolucionDesignacion: { type: Boolean, default: false },
  // true → (modo materia) muestra la columna "Docente" con cod_docente + docente_nombre
  //        y pinta cada docente con su propio color
  mostrarDocente: { type: Boolean, default: false },
})

const router = useRouter()

// Solo filas con grupo y materia reales (no "Sin grupo"), y no en el kardex de materia,
// donde ya se está viendo ese historial
const puedeVerHistorial = (m) =>
  !props.mostrarDocente &&
  !!m.materia_codigo &&
  m.grp !== null && m.grp !== undefined && String(m.grp).trim() !== ''

// Abre el kardex de la materia + grupo en una NUEVA pestaña
const verHistorialMateria = (m) => {
  const { href } = router.resolve({
    name: 'reporte',
    query: {
      modo: 'materia',
      materia: String(m.materia_codigo).trim(),
      grupo: String(m.grp).trim(),
    },
  })
  window.open(href, '_blank')
}

const { tipoGestion, tipoGrp } = useTablaFormato()

const materiasRef = toRef(props, 'materias')
const { hijasIndices, filasAgrupadas } = useAgrupacionCompartidos(materiasRef)

// Arma un array uniforme de "filas" para el template sea cual sea el modo
const filas = computed(() => {
  if (props.agruparCompartidos) return filasAgrupadas.value

  return props.materias.map((m, i) => ({
    nro: m.nro,
    principal: m,
    esHija: hijasIndices.value.has(i),
    hermanas: [],
  }))
})

// ── Kardex materia: un color por docente ────────────────────────────────
// Cada docente recibe un color de la paleta (en orden de aparición).
// - Filas seguidas del mismo docente → mismo color (forman un bloque).
// - Si cambia el docente → color distinto al del bloque anterior, con una
//   línea gruesa arriba para marcar el cambio.
// - Si un docente vuelve a dictar más adelante, recupera su mismo color.
// Las clases van escritas completas para que Tailwind las detecte.
const PALETA = [
  { fila: 'bg-white dark:bg-slate-900',            barra: 'border-l-slate-400',   linea: 'border-t-slate-500',   punto: 'bg-slate-400' },
  { fila: 'bg-indigo-100/70 dark:bg-indigo-500/15',  barra: 'border-l-indigo-500',  linea: 'border-t-indigo-500',  punto: 'bg-indigo-500' },
  { fila: 'bg-emerald-100/70 dark:bg-emerald-500/15', barra: 'border-l-emerald-500', linea: 'border-t-emerald-500', punto: 'bg-emerald-500' },
  { fila: 'bg-rose-100/70 dark:bg-rose-500/15',      barra: 'border-l-rose-500',    linea: 'border-t-rose-500',    punto: 'bg-rose-500' },
  { fila: 'bg-cyan-100/70 dark:bg-cyan-500/15',      barra: 'border-l-cyan-500',    linea: 'border-t-cyan-500',    punto: 'bg-cyan-500' },
  { fila: 'bg-violet-100/70 dark:bg-violet-500/15',  barra: 'border-l-violet-500',  linea: 'border-t-violet-500',  punto: 'bg-violet-500' },
]

// Filas en orden cronológico (por gestión), sin importar cómo se muestre la tabla
const ordenCronologico = computed(() => {
  if (!props.mostrarDocente) return []

  return filas.value
    .map((f, i) => ({
      i,
      gestion: String(f.principal.gestion ?? ''),
      clave: String(f.principal.cod_docente ?? f.principal.docente_nombre ?? '').trim(),
      cod: f.principal.cod_docente,
      nombre: f.principal.docente_nombre,
    }))
    .sort((a, b) => a.gestion.localeCompare(b.gestion, undefined, { numeric: true }) || a.i - b.i)
})

// Por cada fila (índice original): { cambio, color }
const infoDocente = computed(() => {
  const res = []
  const colorDe = new Map()
  let siguiente = 0
  let colorPrevio = -1

  ordenCronologico.value.forEach((o, n, arr) => {
    const cambio = n > 0 && o.clave !== arr[n - 1].clave

    if (!colorDe.has(o.clave)) {
      let c = siguiente % PALETA.length
      // Un docente nuevo nunca repite el color del bloque inmediato anterior
      if (c === colorPrevio) c = (c + 1) % PALETA.length
      colorDe.set(o.clave, c)
      siguiente = c + 1
    }

    const color = colorDe.get(o.clave)
    colorPrevio = color
    res[o.i] = { cambio, color }
  })

  return res
})

const esCambioDocente = (i) => !!infoDocente.value[i]?.cambio
const paletaDe = (i) => PALETA[infoDocente.value[i]?.color ?? 0]

// Fondo de la fila: color del docente (modo materia) o zebra (modo docente)
const claseFila = (i) => {
  if (props.mostrarDocente) return paletaDe(i).fila
  return i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-sky-100 dark:bg-sky-500/15'
}
const claseBarra = (i) => paletaDe(i).barra
const claseLinea = (i) => paletaDe(i).linea
const clasePunto = (i) => paletaDe(i).punto

// Leyenda del pie: un chip por docente, en orden de aparición
const leyendaDocentes = computed(() => {
  const vistos = new Set()
  const lista = []
  ordenCronologico.value.forEach((o) => {
    if (vistos.has(o.clave)) return
    vistos.add(o.clave)
    lista.push({
      clave: o.clave,
      nombre: String(o.nombre ?? o.cod ?? '').trim(),
      punto: PALETA[infoDocente.value[o.i]?.color ?? 0].punto,
    })
  })
  return lista
})

// El endpoint de PDF depende de qué versión del reporte se está mostrando
const { verPdfResolucion: verPdfNormal }     = useReporte()
const { verPdfResolucion: verPdfCompartido } = useReporteCom()
const verPdfActivo = computed(() => (props.agruparCompartidos ? verPdfCompartido : verPdfNormal))

const { loadingPdf, handleVer, handleDescargar } = usePdfMateria(
  (nro, descargar) => verPdfActivo.value(nro, descargar)
)

// ── Columna de referencias ──────────────────────────────────────────────
// Solo se muestra si al menos una fila del reporte tiene referencias.
const tieneReferencias = computed(() =>
  props.materias.some(m => m.referencias?.length)
)

const popover = reactive({ abierto: false, top: 0, left: 0, refs: [] })
const loadingRef = reactive({})

const cerrarPopover = () => { popover.abierto = false }

const abrirReferencia = async (r) => {
  loadingRef[r.id_ref] = true
  try {
    await verPdfActivo.value(r.nro, false)
  } catch (e) {
    console.error('[ReporteTabla] No se pudo abrir la referencia', r.nro, e)
    alert(`No se encontró el documento "${r.nro}" en resoluciones.`)
  } finally {
    loadingRef[r.id_ref] = false
  }
}

const onClickReferencias = (event, refs) => {
  // Una sola referencia: se abre directo, sin desplegable
  if (refs.length === 1) {
    cerrarPopover()
    abrirReferencia(refs[0])
    return
  }

  const rect = event.currentTarget.getBoundingClientRect()
  popover.refs = refs
  popover.top = rect.bottom + 4
  popover.left = Math.max(8, Math.min(rect.left - 200, window.innerWidth - 272))
  popover.abierto = true
}

onMounted(() => {
  document.addEventListener('click', cerrarPopover)
  window.addEventListener('resize', cerrarPopover)
  window.addEventListener('scroll', cerrarPopover, true)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', cerrarPopover)
  window.removeEventListener('resize', cerrarPopover)
  window.removeEventListener('scroll', cerrarPopover, true)
})
</script>