<template>
  <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="border-b border-slate-700 bg-slate-900/60">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-14">GRP</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Resolución</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Designación</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Modalidad de ingreso</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Documento</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro"
            class="border-b border-slate-700/60 transition-colors hover:bg-white/[0.025]"
            :class="i % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20'"
          >
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-500 font-medium text-[13px] tabular-nums">{{ m.nro }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded"
                    :class="tipoGestion(m.gestion).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGestion(m.gestion).dot"/>
                {{ m.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide"
                    :class="tipoGrp(m.plan).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(m.plan).dot"/>
                {{ tipoGrp(m.plan).label }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-200 font-medium">{{ m.materia }}</td>

            <!-- Compartido: se marca solo si esta fila fue detectada como HIJA -->
            <td class="px-4 py-3">
              <span v-if="hijasIndices.has(i)"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300">
                Compartido
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-emerald-400 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- Designación -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="m.designacion"
                    class="text-xs text-slate-400 leading-relaxed line-clamp-2"
                    :title="m.designacion">
                {{ m.designacion }}
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

             <!-- Modalidad de ingreso -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="m.tipo_ingreso"
                    class="text-xs text-slate-400 leading-relaxed line-clamp-2"
                    :title="m.tipo_ingreso">
                {{ m.tipo_ingreso }}
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- Documento PDF -->
            <td class="px-4 py-3">
              <template v-if="m.resolucion && m.designacion">
                <div class="flex items-center gap-1.5">

                  <!-- Botón Ver -->
                  <button
                    :disabled="loadingPdf[m.nro]"
                    @click="handleVer(m)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Ver PDF"
                  >
                    <svg v-if="loadingPdf[m.nro]"
                         class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Ver
                  </button>

                  <!-- Botón Descargar -->
                  <button
                    :disabled="loadingPdf[m.nro]"
                    @click="handleDescargar(m)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Descargar PDF"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                  </button>

                </div>
              </template>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 text-xs text-slate-500 text-right">
      {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useReporte } from '../composables/useReporte'
import { useReporteClasificacion } from '../composables/useReporteClasificacion'

const props = defineProps({
  materias: { type: Array, default: () => [] },
  codDocente: { type: [String, Number], default: null },
})

const { verPdfResolucion } = useReporte()
const { verPdfClasificacion } = useReporteClasificacion()

const loadingPdf = ref({})

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

// ── Detecta qué filas son "hijas" (compartidas) para marcarlas con
// el badge "Compartido", SIN quitarlas de la tabla ni agruparlas.
//
// PASO 1 — Semestre regular (1 y 2): tabla GRUPOS_COMPARTIDOS
// (comp='0' padre, comp='1' hija), agrupado por orden_comparte+gestión
// (el mismo orden_comparte se repite en varias gestiones, así que se
// escopa por gestión para no mezclar años distintos).
//
// PASO 2 — Verano/Invierno (3 y 4): no hay orden_comparte, se agrupa
// por gestión. El flag compartido="COMPARTIDO" marca al PADRE; la
// otra materia de la misma gestión, sin ese flag, es la HIJA. ──────
const hijasIndices = computed(() => {
  const materias = props.materias ?? []
  const hijas = new Set()

  // ── PASO 1: compartidos de semestre regular (1 y 2) ──
  const porClave = new Map()
  materias.forEach((m, idx) => {
    const orden = norm(m.orden_comparte)
    if (!orden) return
    const clave = `${orden}__${norm(m.gestion)}`
    if (!porClave.has(clave)) porClave.set(clave, [])
    porClave.get(clave).push(idx)
  })

  const resueltoEnPaso1 = new Array(materias.length).fill(false)

  for (const [, indices] of porClave) {
    if (indices.length < 2) continue
    const origenes = indices.filter(i => norm(materias[i].comp) === '0')
    const derivadas = indices.filter(i => norm(materias[i].comp) === '1')

    if (origenes.length === 1 && derivadas.length >= 1) {
      derivadas.forEach(i => { hijas.add(i); resueltoEnPaso1[i] = true })
      resueltoEnPaso1[origenes[0]] = true
    } else {
      const pares = Math.min(origenes.length, derivadas.length)
      for (let p = 0; p < pares; p++) {
        hijas.add(derivadas[p])
        resueltoEnPaso1[derivadas[p]] = true
        resueltoEnPaso1[origenes[p]] = true
      }
    }
  }

  // ── PASO 2: compartidos de verano/invierno (3 y 4) ──
  const esCompartido = (m) => norm(m.compartido) === 'COMPARTIDO'

  const porGestionVI = new Map()
  materias.forEach((m, idx) => {
    if (resueltoEnPaso1[idx]) return
    if (norm(m.orden_comparte)) return // ya resuelto en el PASO 1
    const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
    if (!esVI) return
    const clave = norm(m.gestion)
    if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
    porGestionVI.get(clave).push(idx)
  })

  for (const [, indices] of porGestionVI) {
    if (indices.length < 2) continue
    const padres = indices.filter(i => esCompartido(materias[i]))   // CON flag → padre
    const hijasVI = indices.filter(i => !esCompartido(materias[i])) // SIN flag → hijo

    if (padres.length === 1 && hijasVI.length >= 1) {
      hijasVI.forEach(i => hijas.add(i))
    }
  }

  return hijas
})

async function verPdfConFallback(nro, descargar) {
  try {
    await verPdfResolucion(nro, descargar)
  } catch (e) {
    console.warn('[resoluciones] fallo, probando clasificación →', e.response?.status, e.response?.data)
    try {
      await verPdfClasificacion(nro, props.codDocente, descargar)
    } catch (e2) {
  let backendMsg = null
  try {
    if (e2.response?.data instanceof Blob) {
      backendMsg = JSON.parse(await e2.response.data.text())
    } else {
      backendMsg = e2.response?.data
    }
  } catch (_) {}
  console.error('[clasificacion] también falló →', e2.response?.status, backendMsg, e2.config?.url)
  alert('No se encontró el PDF en ninguna de las dos fuentes.')
}
  }
}

async function handleVer(m) {
  loadingPdf.value[m.nro] = true
  try {
    await verPdfConFallback(m.resolucion, false)
  } finally {
    loadingPdf.value[m.nro] = false
  }
}

async function handleDescargar(m) {
  loadingPdf.value[m.nro] = true
  try {
    await verPdfConFallback(m.resolucion, true)
  } finally {
    loadingPdf.value[m.nro] = false
  }
}

const tipoGestion = (gestion) => {
  if (gestion?.includes('Verano'))
    return { class: 'bg-orange-500/10 text-orange-400', dot: 'bg-orange-400' }
  if (gestion?.includes('Invierno'))
    return { class: 'bg-sky-500/10 text-sky-400', dot: 'bg-sky-400' }
  return { class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
}

const GRP_MAP = {
  '059801': { label: 'CCP', class: 'bg-violet-500/15 text-violet-300', dot: 'bg-violet-400' },
  '109401': { label: 'ADM', class: 'bg-blue-500/15 text-blue-300',     dot: 'bg-blue-400'   },
  '125091': { label: 'COM', class: 'bg-green-500/15 text-green-300',   dot: 'bg-green-400'  },
  '126091': { label: 'FIN', class: 'bg-teal-500/15 text-teal-300',     dot: 'bg-teal-400'   },
  '089801': { label: 'ECO', class: 'bg-amber-500/15 text-amber-300',   dot: 'bg-amber-400'  },
}

const tipoGrp = (plan) =>
  GRP_MAP[plan] ?? { label: plan, class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
</script>