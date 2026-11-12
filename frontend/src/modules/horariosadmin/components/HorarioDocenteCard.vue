<template>
  <div
    translate="no"
    class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente -->
    <div
      class="flex justify-between items-center px-5 py-1 bg-slate-800 text-white"
    >
      <div class="flex flex-col min-w-0">
        <h2 class="text-[15px] font-bold truncate">
          {{ docente.docente }} - {{ docente.apellidos }} {{ docente.nombres }}
        </h2>
      </div>

      <div class="flex items-center gap-3 shrink-0 ml-4">
        <div
          class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1"
        >
          {{ materiasVisibles.length }} materias
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table 
        translate="no"
        class="w-full border-collapse text-xs" 
        style="table-layout: auto;"
      >
        <thead>
          <tr class="bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700">
            <th 
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 whitespace-nowrap"
            >
              Plan - Niv
            </th>
            <th 
              translate="no"
              class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 min-w-[160px]"
            >
              Materia
            </th>
            <th
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 whitespace-nowrap"
            >
              Tipo
            </th>
            <th 
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              Grp
            </th>
            <th
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              v-show="tieneSesionesEnDia(dia)"
              translate="no"
              class="px-1 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              {{ dia }}
            </th>
            <th 
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              CH
            </th>
            <th 
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              Ins.
            </th>
            <th 
              translate="no"
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              Comp.
            </th>
          </tr>
        </thead>

        <tbody>
          <!-- filasOrdenadas: cada padre (COMP 0) seguido de sus hijos (COMP 1) -->
          <tr
            v-for="({ mat, anidado }, i) in filasOrdenadas"
            :key="i"
            class="border-b border-slate-200 dark:border-slate-700 transition-colors"
            :class="filaClase(i)"
          >
            <td 
              translate="no"
              class="px-2 py-1.5 text-center"
            >
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border whitespace-nowrap"
                :style="{
                  background: colorCarrera(mat.carrera).bg,
                  color: colorCarrera(mat.carrera).text,
                  borderColor: colorCarrera(mat.carrera).border
                }"
              >
                {{ mat.carrera }} - {{ mat.nivel }}
              </span>
            </td>

            <td 
              translate="no"
              class="py-1.5 pr-3"
              :class="anidado ? 'pl-8' : 'pl-3'"
            >
              <div class="flex items-start gap-1.5">
                <span
                  v-if="anidado"
                  class="text-indigo-600 dark:text-indigo-400 text-lg font-extrabold leading-none shrink-0 -ml-5 mt-0 w-4"
                  aria-hidden="true"
                >↳</span>
                <div class="min-w-0">
                  <span class="block font-semibold text-slate-800 dark:text-slate-100 leading-snug text-[12px]">
                    {{ mat.nombre }}
                  </span>
                  <span class="block text-[12px] font-semibold text-slate-800 dark:text-slate-900">
                    {{ mat.materia }}
                  </span>
                </div>
              </div>
            </td>

            <!-- Tipo de ingreso (viene de GRUPOS.TIPO_INGRESO) -->
            <td
              translate="no"
              class="text-center px-2 py-1.5"
            >
              <span
                v-if="mat.tipoIngreso"
                class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap"
                :title="mat.tipoIngreso"
              >
                {{ abreviarIngreso(mat.tipoIngreso) }}
              </span>
              <span v-else class="text-slate-300 dark:text-slate-600">—</span>
            </td>

            <td 
              translate="no"
              class="text-center px-2 py-1.5 font-semibold text-slate-600 dark:text-slate-300"
            >
              {{ mat.grupo }}
            </td>

            <td
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              v-show="tieneSesionesEnDia(dia)"
              translate="no"
              class="py-1 px-1 align-middle"
            >
              <template v-if="mat.sesiones.filter(x => x.dia === dia).length > 0">
                <div
                  v-for="s in mat.sesiones.filter(x => x.dia === dia)"
                  :key="`${s.horario}-${s.ambiente}`"
                  translate="no"
                  class="rounded px-1.5 py-1 mb-0.5 border-l-4 bg-transparent"
                  :style="{ borderLeftColor: colorBloqueHorario(mat, s) }"
                >
                  <span 
                    translate="no"
                    class="block text-[10px] font-bold whitespace-nowrap text-slate-800 dark:text-slate-100 dark-color-carrera"
                    :style="{ '--carrera-color': colorBloqueHorario(mat, s) }"
                  >
                    {{ s.horario }}
                  </span>
                  <span 
                    translate="no"
                    class="block text-[10px] font-semibold text-slate800 dark:text-slate-800 dark-color-carrera"
                    :style="{ '--carrera-color': colorBloqueHorario(mat, s) }"
                  >
                    {{ s.ambiente }}
                  </span>
                </div>
              </template>
              <template v-else>
                <div 
                  translate="no"
                  class="h-8 rounded bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 min-w-[80px]" 
                />
              </template>
            </td>

            <td 
              translate="no"
              class="text-center px-2 py-1.5"
            >
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 font-bold"
              >
                {{ chMostrada({ mat, anidado }) }}
              </span>
            </td>

            <td 
              translate="no"
              class="text-center px-2 py-1.5 font-semibold text-slate-600 dark:text-slate-300"
            >
              {{ mat.inscritos }}
            </td>

            <td 
              translate="no"
              class="text-center px-2 py-1.5"
            >
              <span
                v-if="mat.comp"
                translate="no"
                class="inline-block px-2 py-1 text-[10px] font-semibold rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600"
                :title="mat.compartido"
              >
                {{ mat.comp }}
              </span>
              <span v-else class="text-slate-300 dark:text-slate-600">—</span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-100 dark:bg-slate-800 border-t-2 border-slate-200 dark:border-slate-700">
            <td
              translate="no"
              :colspan="4 + diasConSesiones.length"
              class="text-right px-4 py-1 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              Total
            </td>
            <td 
              translate="no"
              class="text-center py-2.5"
            >
              <span 
                translate="no"
                class="inline-block px-3 py-1 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-sm font-extrabold"
              >
                {{ totalChReal }}
              </span>
              <span
                translate="no"
                class="inline-block px-3 py-1 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-700 text-sm font-extrabold"
              >
                Mes({{ totalChReal * 4 }})
              </span>
            </td>
            <td 
              translate="no"
              class="text-center py-2.5"
            >
              <span 
                translate="no"
                class="inline-block px-3 py-1 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 text-sm font-extrabold"
              >
                {{ totalInscritos }}
              </span>
            </td>
            <td translate="no" />
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { useHorarioAdmin } from '../composables/useHorarioAdmin'

const { colorCarrera, agruparPorMateriaGrupo, DIAS_ORDEN } = useHorarioAdmin()

const props = defineProps({
  docente: { type: Object, required: true },
})

const materiasAgrupadas = computed(() => agruparPorMateriaGrupo(props.docente.horarios))

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

// ─── Padres e hijos de grupos compartidos ──────────────────────────────────
// Una derivada (COMP 1) se une a su origen (COMP 0) así:
//   1) Mismo horario exacto (mismos día + hora + aula) y distinto plan.
//      Si hay varios candidatos, gana el que tenga el mismo ORDEN.
//   2) Si ninguno coincide en horario: mismo ORDEN y un único origen en ese ORDEN.
//   3) Si no hay pareja, queda como fila normal (conserva su CH).

function firmaHorario(mat) {
  return mat.sesiones
    .map(s => `${s.dia}|${s.horario}|${s.ambiente}`)
    .sort()
    .join(';')
}

// Orden estable: ORDEN → MATERIA → GRUPO → PLAN
const matsBackend = computed(() =>
  [...materiasAgrupadas.value].sort((a, b) =>
    norm(a.orden).localeCompare(norm(b.orden), 'es', { numeric: true }) ||
    norm(a.materia).localeCompare(norm(b.materia), 'es', { numeric: true }) ||
    norm(a.grupo).localeCompare(norm(b.grupo), 'es', { numeric: true }) ||
    norm(a.plan).localeCompare(norm(b.plan), 'es', { numeric: true })
  )
)

// Lista plana: padre (anidado:false) seguido de sus hijas (anidado:true)
const filasOrdenadas = computed(() => {
  const mats = matsBackend.value
  const firmas = mats.map(firmaHorario)

  const origenes = []
  const derivadas = []
  mats.forEach((m, i) => {
    const c = norm(m.comp)
    if (c === '0') origenes.push(i)
    else if (c === '1') derivadas.push(i)
  })

  const hijosDe = new Map() // índice origen -> [índices derivadas]
  const esHija = new Set()

  function vincular(o, d) {
    if (!hijosDe.has(o)) hijosDe.set(o, [])
    hijosDe.get(o).push(d)
    esHija.add(d)
  }

  for (const d of derivadas) {
    const md = mats[d]
    const ordenD = norm(md.orden)

    // Candidatos: otro plan distinto
    const candidatos = origenes.filter(o => norm(mats[o].plan) !== norm(md.plan))

    // 1) Mismo horario exacto (y no vacío)
    let porHorario = firmas[d]
      ? candidatos.filter(o => firmas[o] === firmas[d])
      : []

    let elegido = null
    if (porHorario.length === 1) {
      elegido = porHorario[0]
    } else if (porHorario.length > 1) {
      elegido =
        porHorario.find(o => ordenD && norm(mats[o].orden) === ordenD) ??
        porHorario[0]
    } else if (ordenD) {
      // 2) Sin coincidencia de horario: mismo ORDEN y un único origen
      const mismoOrden = candidatos.filter(o => norm(mats[o].orden) === ordenD)
      if (mismoOrden.length === 1) elegido = mismoOrden[0]
    }

    if (elegido !== null) vincular(elegido, d)
  }

  // 3) Lista final: cada fila que no es hija, seguida de sus hijas
  const salida = []
  mats.forEach((mat, idx) => {
    if (esHija.has(idx)) return
    salida.push({ mat, anidado: false })
    ;(hijosDe.get(idx) ?? []).forEach(h => salida.push({ mat: mats[h], anidado: true }))
  })
  return salida
})

// Contador "N materias" = filas principales
const materiasVisibles = computed(() => filasOrdenadas.value.filter(f => !f.anidado))

function tieneSesionesEnDia(dia) {
  return materiasAgrupadas.value.some(mat =>
    mat.sesiones.some(s => s.dia === dia)
  )
}

const diasConSesiones = computed(() => DIAS_ORDEN.filter(d => tieneSesionesEnDia(d)))

const COLOR_COMPARTIDO = '#eab308'

const PALETTE_MATERIA = [
  '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6',
  '#f97316', '#22c55e', '#06b6d4', '#a855f7', '#f43f5e',
  '#0ea5e9', '#84cc16', '#d946ef', '#6366f1', '#10b981',
]

const clavesCompartidas = computed(() => {
  const grupos = new Map()

  materiasAgrupadas.value.forEach(mat => {
    mat.sesiones.forEach(s => {
      const key = `${s.dia}|${s.horario}|${s.ambiente}`
      if (!grupos.has(key)) grupos.set(key, new Set())
      grupos.get(key).add(mat)
    })
  })

  const claves = new Set()
  grupos.forEach((filas, key) => {
    if (filas.size > 1) claves.add(key)
  })
  return claves
})

function colorMateria(mat) {
  const key = `${mat.carrera}-${mat.nivel}-${mat.materia}-${mat.grupo}`
  let hash = 0
  for (let i = 0; i < key.length; i++) {
    hash = key.charCodeAt(i) + ((hash << 5) - hash)
    hash |= 0
  }
  const idx = Math.abs(hash) % PALETTE_MATERIA.length
  return PALETTE_MATERIA[idx]
}

function colorBloqueHorario(mat, s) {
  const key = `${s.dia}|${s.horario}|${s.ambiente}`
  return clavesCompartidas.value.has(key)
    ? COLOR_COMPARTIDO
    : colorMateria(mat)
}

// ─── Tipo de ingreso (GRUPOS.TIPO_INGRESO) ─────────────────────────────────
const ABREV_INGRESO = {
  // 'TITULAR': 'TIT',
  // 'INVITADO': 'INV',
  // 'INTERINO': 'INT',
}

function abreviarIngreso(texto) {
  const t = String(texto ?? '').trim()
  if (!t) return ''
  const exacto = ABREV_INGRESO[t.toUpperCase()]
  if (exacto) return exacto
  return t.length > 10 ? t.slice(0, 9) + '…' : t
}

// Solo las hijas anidadas ponen CH = 0; una derivada sin padre conserva su CH
function chMostrada({ mat, anidado }) {
  return anidado ? 0 : mat.carga
}

function filaClase(i) {
  const base = i % 2 === 0
    ? 'bg-white dark:bg-slate-900'
    : 'bg-gray-100 dark:bg-slate-800'
  return `${base} hover:bg-blue-50 dark:hover:bg-slate-700/60`
}

const totalChReal = computed(() =>
  filasOrdenadas.value.reduce(
    (acc, f) => acc + (Number(chMostrada(f)) || 0),
    0
  )
)

const totalInscritos = computed(() =>
  materiasAgrupadas.value.reduce((acc, mat) => acc + (Number(mat.inscritos) || 0), 0)
)
</script>