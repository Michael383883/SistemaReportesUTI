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
              <h2 class="text-base font-bold text-slate-800 truncate">Horario Semanal</h2>
              <p class="text-slate-500 text-xs truncate">{{ formatNombre(docente.nombre_docente) }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 flex-shrink-0">
            <!-- Resumen inline -->
            <div class="hidden sm:flex items-center gap-4">
              <div class="text-center">
                <p class="text-sm font-bold leading-none text-slate-800">{{ materias.length }}</p>
                <p class="text-[10px] text-slate-400 mt-1">materias</p>
              </div>
              <div class="w-px h-6 bg-slate-200" />
              <div class="text-center">
                <p class="text-sm font-bold leading-none text-slate-800">{{ docente.horas_total || cargaTotal }}h</p>
                <p class="text-[10px] text-slate-400 mt-1">totales</p>
              </div>
              <div class="w-px h-6 bg-slate-200" />
              <div class="text-center">
                <p class="text-sm font-bold leading-none text-slate-800">{{ totalSesiones }}</p>
                <p class="text-[10px] text-slate-400 mt-1">sesiones</p>
              </div>
            </div>

            <button @click="$emit('cerrar')" class="text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-lg hover:bg-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Contenido: la grilla ocupa todo el alto disponible y scrollea internamente -->
        <div class="flex-1 min-h-0 flex flex-col p-5">

          <div v-if="materias.length > 0" class="flex-1 min-h-0 flex flex-col">

            <!-- Grilla semanal con cabecera y columna de hora fijas -->
            <div class="flex-1 min-h-0 overflow-auto rounded-xl border border-slate-200">
              <table class="w-full border-collapse min-w-[600px]">
                <thead>
                  <tr>
                    <th class="sticky top-0 left-0 z-20 w-20 p-2.5 text-[11px] font-medium text-slate-400 text-right border-b border-r border-slate-200 bg-slate-50">
                      Hora
                    </th>
                    <th
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="sticky top-0 z-10 p-2.5 text-center border-b border-r last:border-r-0 border-slate-200 bg-slate-50"
                    >
                      <p class="text-sm font-semibold text-slate-700">{{ dia.label }}</p>
                      <p class="text-[11px] text-slate-400">{{ dia.key }}</p>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="slot in SLOTS" :key="slot" class="group">
                    <td class="sticky left-0 z-10 p-2.5 text-[11px] text-slate-400 text-right border-r border-b border-slate-100 bg-slate-50/95 align-middle whitespace-nowrap">
                      {{ slot.split(' - ')[0] }}
                    </td>
                    <td
                      v-for="dia in DIAS"
                      :key="dia.key"
                      class="border-r last:border-r-0 border-b border-slate-100 p-1 align-top"
                      style="height: 60px; min-width: 110px;"
                    >
                      <template v-if="gridMap[dia.key] && gridMap[dia.key][slot]">
                        <div
                          class="h-full w-full rounded-lg p-2 flex flex-col justify-between cursor-default transition-colors border"
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
                        <div class="h-full w-full rounded-lg bg-slate-50/60 group-hover:bg-slate-100 transition-colors" />
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Leyenda de materias -->
            <div class="mt-3 flex flex-wrap gap-2 flex-shrink-0">
              <div
                v-for="(mat, idx) in materias"
                :key="idx"
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 text-xs text-slate-600 bg-white"
              >
                <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: legendDotColor(idx) }" />
                <span class="font-medium">{{ mat.nombre }}</span>
                <span class="text-slate-400">Gr. {{ mat.grupo }}</span>
              </div>
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
          <span v-if="materias.length > 0" class="text-xs text-slate-400">
            {{ totalSesiones }} sesiones semanales · {{ materias.length }} materias
          </span>
          <span v-else />
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

const LEGEND_DOTS = ['#0d9488', '#2563eb', '#7c3aed', '#d97706', '#e11d48', '#db2777']

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

/** Clases Tailwind para las tarjetas dentro de la grilla (tema claro) */
function cardClass(col) {
  return {
    lunes:     'bg-teal-50   border-teal-200   text-teal-800',
    martes:    'bg-blue-50   border-blue-200   text-blue-800',
    miercoles: 'bg-violet-50 border-violet-200 text-violet-800',
    jueves:    'bg-amber-50  border-amber-200  text-amber-800',
    viernes:   'bg-rose-50   border-rose-200   text-rose-800',
    sabado:    'bg-pink-50   border-pink-200   text-pink-800',
  }[col] || 'bg-slate-50 border-slate-200 text-slate-700'
}
</script>