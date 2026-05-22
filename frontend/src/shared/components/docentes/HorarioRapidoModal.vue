<!-- src/modules/secretaria/components/HorarioRapidoModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('cerrar')">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="$emit('cerrar')" />

      <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
          <div>
            <h2 class="text-lg font-bold text-white">Horario Semanal</h2>
            <p class="text-blue-200 text-sm">{{ formatNombre(docente.nombre_docente) }}</p>
          </div>
          <button @click="$emit('cerrar')" class="text-blue-200 hover:text-white transition-colors p-1 rounded-lg hover:bg-blue-500/40">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Contenido scrollable -->
        <div class="flex-1 overflow-y-auto p-6">

          <!-- Resumen -->
          <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ materias.length }}</p>
              <p class="text-xs text-slate-500 mt-1">Materias</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ docente.horas_total || cargaTotal }}h</p>
              <p class="text-xs text-slate-500 mt-1">Horas totales</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ totalSesiones }}</p>
              <p class="text-xs text-slate-500 mt-1">Sesiones</p>
            </div>
          </div>

          <!-- Grilla semanal -->
          <div v-if="materias.length > 0">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Horario semanal</h3>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
              <table class="w-full border-collapse min-w-[600px]">
                <thead>
                  <tr>
                    <!-- Columna de hora -->
                    <th class="w-24 p-3 text-xs font-medium text-slate-400 text-right border-b border-r border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                      Hora
                    </th>
                    <!-- Columna por día -->
                    <th
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="p-3 text-center border-b border-r last:border-r-0 border-slate-200 dark:border-slate-700"
                      :class="diaHeaderClass(dia.col)"
                    >
                      <p class="text-sm font-semibold">{{ dia.label }}</p>
                      <p class="text-xs opacity-60">{{ dia.key }}</p>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="slot in SLOTS" :key="slot" class="group">
                    <!-- Hora -->
                    <td class="p-3 text-xs text-slate-400 text-right border-r border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/60 align-middle whitespace-nowrap">
                      {{ slot.split(' - ')[0] }}
                    </td>
                    <!-- Celda por día -->
                    <td
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="border-r last:border-r-0 border-b border-slate-100 dark:border-slate-800 p-1 align-top"
                      style="height: 72px; min-width: 110px;"
                    >
                      <template v-if="gridMap[dia.key] && gridMap[dia.key][slot]">
                        <div
                          class="h-full w-full rounded-lg p-2 flex flex-col justify-between cursor-default transition-all hover:brightness-95"
                          :class="cardClass(gridMap[dia.key][slot].color)"
                        >
                          <p class="text-xs font-semibold leading-tight line-clamp-2">
                            {{ gridMap[dia.key][slot].nombre }}
                          </p>
                          <p class="text-[10px] opacity-70 leading-tight">
                            {{ gridMap[dia.key][slot].aula }} · Gr. {{ gridMap[dia.key][slot].grupo }}
                          </p>
                        </div>
                      </template>
                      <template v-else>
                        <div class="h-full w-full rounded-lg bg-slate-50 dark:bg-slate-800/30 group-hover:bg-slate-100/60 dark:group-hover:bg-slate-800/60 transition-colors" />
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Leyenda de materias -->
            <div class="mt-4 flex flex-wrap gap-2">
              <div
                v-for="(mat, idx) in materias"
                :key="idx"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800"
              >
                <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: legendDotColor(idx) }" />
                <span class="font-medium">{{ mat.nombre }}</span>
                <span class="text-slate-400">Gr. {{ mat.grupo }}</span>
              </div>
            </div>
          </div>

          <!-- Sin materias -->
          <div v-else class="flex flex-col items-center justify-center py-16 text-center">
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
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex-shrink-0 flex items-center justify-between">
          <span v-if="materias.length > 0" class="text-xs text-slate-400">
            {{ totalSesiones }} sesiones semanales · {{ materias.length }} materias
          </span>
          <span v-else />
          <button
            @click="$emit('cerrar')"
            class="px-5 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg transition-colors"
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

const LEGEND_DOTS = ['#378ADD', '#1D9E75', '#7F77DD', '#EF9F27', '#D85A30', '#D4537E']

// ─── Computed ────────────────────────────────────────────────────────────────

const materias = computed(() => {
  const lista = props.docente?.horario_completo?.materias ?? props.docente?.materias ?? []
  // Asignar color por índice
  return lista.map((m, i) => ({ ...m, color: COLORES[i % COLORES.length] }))
})

const cargaTotal = computed(() =>
  materias.value.reduce((sum, m) => sum + (m.carga_horaria || 0), 0)
)

const totalSesiones = computed(() =>
  materias.value.reduce((sum, m) => sum + (m.horarios?.length || 0), 0)
)

/**
 * Construye un mapa { DIA: { SLOT: { ...materia, aula } } }
 * para renderizar la grilla en O(1) por celda.
 */
const gridMap = computed(() => {
  const map = {}

  DIAS.forEach(d => {
    map[d.key] = {}
  })

  materias.value.forEach(mat => {
    mat.horarios?.forEach(h => {
      const diaKey = h.dia?.toUpperCase()
      if (!map[diaKey]) return

      // Construir la clave del slot: "HH:MM - HH:MM"
      const slot = h.hora_inicio && h.hora_fin
        ? `${h.hora_inicio} - ${h.hora_fin}`
        : null

      if (slot) {
        map[diaKey][slot] = {
          nombre:  mat.nombre,
          grupo:   mat.grupo,
          aula:    h.ambiente || 'Sin aula',
          color:   mat.color,
        }
      }
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

function legendDotColor(idx) {
  return LEGEND_DOTS[idx % LEGEND_DOTS.length]
}

/** Clases Tailwind para el encabezado de cada día */
function diaHeaderClass(col) {
  return {
    lunes:     'bg-blue-50   dark:bg-blue-950/40  text-blue-700   dark:text-blue-300',
    martes:    'bg-teal-50   dark:bg-teal-950/40  text-teal-700   dark:text-teal-300',
    miercoles: 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300',
    jueves:    'bg-amber-50  dark:bg-amber-950/40  text-amber-700  dark:text-amber-300',
    viernes:   'bg-rose-50   dark:bg-rose-950/40   text-rose-700   dark:text-rose-300',
    sabado:    'bg-pink-50   dark:bg-pink-950/40   text-pink-700   dark:text-pink-300',
  }[col] || 'bg-slate-50 text-slate-600'
}

/** Clases Tailwind para las tarjetas dentro de la grilla */
function cardClass(col) {
  return {
    lunes:     'bg-blue-100   dark:bg-blue-900/60  text-blue-800   dark:text-blue-200',
    martes:    'bg-teal-100   dark:bg-teal-900/60  text-teal-800   dark:text-teal-200',
    miercoles: 'bg-violet-100 dark:bg-violet-900/60 text-violet-800 dark:text-violet-200',
    jueves:    'bg-amber-100  dark:bg-amber-900/60  text-amber-800  dark:text-amber-200',
    viernes:   'bg-rose-100   dark:bg-rose-900/60   text-rose-800   dark:text-rose-200',
    sabado:    'bg-pink-100   dark:bg-pink-900/60   text-pink-800   dark:text-pink-200',
  }[col] || 'bg-slate-100 text-slate-700'
}
</script>