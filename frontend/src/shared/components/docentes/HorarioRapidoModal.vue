<!-- src/modules/secretaria/components/HorarioRapidoModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4" @click.self="$emit('cerrar')">
      <div class="absolute inset-0 bg-slate-900/40 z-0" @click="$emit('cerrar')" />

      <div class="relative z-10 bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-5xl h-[88vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <div class="h-9 w-9 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <h2 class="text-[20px] font-semibold text-slate-800 uppercase tracking-wide truncate">Horario Semanal</h2>
              <p class="text-slate-600 text-lg font-bold truncate">{{ formatNombre(docente.nombre_docente) }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 flex-shrink-0">
            

            <button @click="$emit('cerrar')" class="text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-lg hover:bg-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Contenido: la grilla ocupa todo el alto disponible y scrollea internamente -->
        <div class="flex-1 min-h-0 flex flex-col p-5">

          <div v-if="materiasConHorario.length > 0" class="flex-1 min-h-0 flex flex-col gap-3">

            <!-- Grilla semanal con cabecera y columna de hora fijas -->
            <div class="flex-1 min-h-0 overflow-auto rounded-xl border border-slate-300">
              <table class="w-full border-collapse min-w-[680px]">
                <thead>
                  <tr>
                    <th class="sticky top-0 left-0 z-20 w-20 p-2.5 text-xs font-bold text-slate-700 text-right border-b-2 border-r-2 border-slate-300 bg-slate-100">
                      Hora
                    </th>
                    <th
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="sticky top-0 z-10 p-2.5 text-center border-b-2 border-r-2 last:border-r-0 border-slate-300 bg-slate-100"
                    >
                      <p class="text-sm font-bold text-slate-800">{{ dia.label }}</p>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="slot in SLOTS" :key="slot" class="group">
                    <td class="sticky left-0 z-10 p-2.5 text-[13px] font-bold text-slate-700 text-right border-r-2 border-b border-slate-300 bg-slate-100 align-middle whitespace-nowrap">
                      {{ slot.split(' - ')[0] }}
                    </td>
                    <td
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="border-r border-r-slate-200 last:border-r-0 border-b border-b-slate-200 p-1 align-top"
                      style="min-height: 84px; min-width: 130px;"
                    >
                      <template v-if="gridMap[dia.key] && gridMap[dia.key][slot] && gridMap[dia.key][slot].length">
                        <!-- Celda con 1 sola materia: tarjeta completa -->
                        <div
                          v-if="gridMap[dia.key][slot].length === 1"
                          class="h-full w-full rounded-lg pl-2.5 pr-2 py-2 flex flex-col justify-center gap-1.5 cursor-default transition-shadow border-l-4 shadow-sm hover:shadow-md"
                          :class="cardClass(gridMap[dia.key][slot][0].color)"
                        >
                          <p class="text-[12.5px] font-bold leading-snug break-words">
                            {{ gridMap[dia.key][slot][0].nombre }}
                          </p>
                          <p class="flex items-center gap-1 text-[11px] font-semibold leading-tight opacity-90">
                            <span>Aula {{ gridMap[dia.key][slot][0].aula }} · Gr. {{ gridMap[dia.key][slot][0].grupo }}</span>
                          </p>
                        </div>

                        <!-- Celda compartida: varias materias en el mismo horario, fusionadas en una sola tarjeta apilada -->
                        <div
                          v-else
                          class="h-full w-full rounded-lg overflow-hidden flex flex-col shadow-sm hover:shadow-md transition-shadow relative divide-y divide-white/70"
                        >
                          <div
                            class="absolute top-1 right-1 z-10 flex items-center gap-0.5 bg-slate-800/90 text-white text-[8px] font-bold px-1.5 py-[1px] rounded-full leading-none"
                            :title="`${gridMap[dia.key][slot].length} materias comparten este horario`"
                          >
                            {{ gridMap[dia.key][slot].length }}×
                          </div>
                          <div
                            v-for="(item, idx) in gridMap[dia.key][slot]"
                            :key="idx"
                            class="flex-1 min-h-0 pr-5 flex flex-col justify-center border-l-4"
                            :class="[
                              cardClass(item.color),
                              gridMap[dia.key][slot].length >= 3 ? 'pl-1.5 py-0.5 gap-0' : 'pl-2.5 py-1.5 gap-0.5'
                            ]"
                          >
                            <p
                              class="font-bold leading-tight truncate"
                              :class="gridMap[dia.key][slot].length >= 3 ? 'text-[9.5px]' : 'text-[11px]'"
                              :title="item.nombre"
                            >
                              {{ item.nombre }}
                            </p>
                            <p
                              class="font-semibold leading-tight opacity-90 truncate"
                              :class="gridMap[dia.key][slot].length >= 3 ? 'text-[8.5px]' : 'text-[10px]'"
                            >
                              {{ item.aula }} · Gr. {{ item.grupo }}
                            </p>
                          </div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="h-full w-full rounded-lg bg-slate-50/60 group-hover:bg-slate-100 transition-colors" />
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>

          <!-- Sin materias -->
          <div v-else class="flex-1 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <p class="text-slate-600 font-medium">Sin horario asignado</p>
            <p class="text-slate-400 text-sm mt-1">El docente no tiene materias en este período</p>
          </div>

        </div>

        <!-- Footer -->
        <div class="px-6 py-3.5 border-t border-slate-100 flex-shrink-0 flex items-center justify-between">
          
          
          <button
            @click="$emit('cerrar')"
            class="px-5 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
  docente: { type: Object, required: true }
})

defineEmits(['cerrar'])

// ─── Constantes ──────────────────────────────────────────────────────────────

/** Días de la semana en orden */
const DIAS = [
  { key: 'LU', label: 'Lunes',     col: 'lunes' },
  { key: 'MA', label: 'Martes',    col: 'martes' },
  { key: 'MI', label: 'Miércoles', col: 'miercoles' },
  { key: 'JU', label: 'Jueves',    col: 'jueves' },
  { key: 'VI', label: 'Viernes',   col: 'viernes' },
  { key: 'SA', label: 'Sábado',    col: 'sabado' },
]

/** Franjas horarias comunes. Ajusta según tu institución. */
const SLOTS = [
  '06:45 - 08:15',
  '08:15 - 09:45',
  '09:45 - 11:15',
  '11:15 - 12:45',
  '12:45 - 14:15',
  '14:15 - 15:45',
  '15:45 - 17:15',
  '17:15 - 18:45',
  '18:45 - 20:15',
  '20:15 - 21:45',
]

/** Colores asignados a cada índice de materia */
const COLORES = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']

// ─── Computed ────────────────────────────────────────────────────────────────

const materias = computed(() => {
  const lista = props.docente?.horario_completo?.materias ?? props.docente?.materias ?? []
  // Asignar color e identificador único por índice de origen (_uid).
  // _uid se usa más abajo para no confundir dos materias distintas que
  // coincidan por casualidad en nombre/grupo/aula (ej. dos secciones de
  // "CONTABILIDAD II" en distinto Plan-Niv).
  return lista.map((m, i) => ({ ...m, color: COLORES[i % COLORES.length], _uid: i }))
})

/**
 * Solo las materias que tienen al menos una sesión con día y horario definidos.
 * Evita que la leyenda muestre materias "fantasma" que no aparecen en la grilla.
 */
const materiasConHorario = computed(() =>
  materias.value.filter(m => (m.horarios || []).some(h => h.dia && h.hora_inicio && h.hora_fin))
)

const cargaTotal = computed(() =>
  materiasConHorario.value.reduce((sum, m) => sum + (m.carga_horaria || 0), 0)
)

const totalSesiones = computed(() =>
  materiasConHorario.value.reduce((sum, m) => sum + (m.horarios?.length || 0), 0)
)

/**
 * Construye un mapa { DIA: { SLOT: [ { ...materia, aula }, ... ] } }
 * para renderizar la grilla en O(1) por celda.
 *
 * IMPORTANTE: cada celda ahora es un ARRAY, no un objeto único, porque dos
 * (o más) materias distintas pueden compartir el mismo día+horario (por
 * ejemplo, clases combinadas/compartidas entre grupos). Antes, la segunda
 * materia sobrescribía a la primera y desaparecía de la grilla.
 */
const gridMap = computed(() => {
  const map = {}

  DIAS.forEach(d => {
    map[d.key] = {}
  })

  materiasConHorario.value.forEach(mat => {
    mat.horarios?.forEach(h => {
      const diaKey = h.dia?.toUpperCase()
      if (!map[diaKey]) return

      // Construir la clave del slot: "HH:MM - HH:MM"
      const slot = h.hora_inicio && h.hora_fin
        ? `${h.hora_inicio} - ${h.hora_fin}`
        : null

      if (!slot) return

      if (!map[diaKey][slot]) {
        map[diaKey][slot] = []
      }

      // Evitar que la MISMA materia (mismo _uid, misma fila de origen) se
      // duplique en la celda si su propio arreglo `horarios` trae una entrada
      // repetida para este día/franja. Comparar por _uid (no por nombre/grupo/
      // aula) es clave: dos materias distintas pueden coincidir en esos campos
      // (ej. dos secciones de "CONTABILIDAD II" con el mismo grupo y aula) y
      // deben seguir mostrándose ambas.
      const yaExiste = map[diaKey][slot].some(e => e._uid === mat._uid)
      if (yaExiste) return

      map[diaKey][slot].push({
        nombre: mat.nombre,
        grupo:  mat.grupo,
        aula:   h.ambiente || 'Sin aula',
        color:  mat.color,
        _uid:   mat._uid,
      })
    })
  })

  return map
})

// ─── Helpers de estilo ───────────────────────────────────────────────────────

function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre
    .split(' ')
    .map(p => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase())
    .join(' ')
}

/**
 * Clases Tailwind para las tarjetas dentro de la grilla.
 * Fondo blanco/gris muy claro + borde izquierdo de acento en tono institucional
 * (sin rellenos saturados) para un aspecto más sobrio y académico.
 */
function cardClass(col) {
  return {
    lunes:     'bg-teal-50   border-teal-600   text-teal-800',
    martes:    'bg-rose-50   border-rose-700   text-rose-800',
    miercoles: 'bg-indigo-50 border-indigo-700 text-indigo-800',
    jueves:    'bg-amber-50  border-amber-700  text-amber-800',
    viernes:   'bg-blue-50   border-blue-700   text-blue-800',
    sabado:    'bg-violet-50 border-violet-700 text-violet-800',
  }[col] || 'bg-slate-50 border-slate-400 text-slate-700'
}
</script>