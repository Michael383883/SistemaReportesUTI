<template>
  <div class="flex flex-col gap-4">

    <div v-if="cargando" class="text-center py-10 text-slate-400 text-sm">
      Cargando estudiantes...
    </div>

    <div v-else-if="!grupos.length" class="text-center py-10 text-slate-400 text-sm">
      No se encontraron estudiantes con los filtros seleccionados.
    </div>

    <template v-else>

      <!-- Barra de acciones: expandir/colapsar todo -->
      <div class="flex items-center justify-between px-1">
        <span class="text-xs text-slate-400">
          {{ gruposOrdenados.length }} grupo{{ gruposOrdenados.length !== 1 ? 's' : '' }}
        </span>
        <div class="flex items-center gap-3">
          <button @click="expandirTodos" class="text-xs font-medium text-blue-600 hover:text-blue-700 transition">
            Expandir todo
          </button>
          <span class="text-slate-300">|</span>
          <button @click="colapsarTodos" class="text-xs font-medium text-slate-500 hover:text-slate-700 transition">
            Colapsar todo
          </button>
        </div>
      </div>

      <!-- Tarjeta por grupo (materia + nivel + grupo) — una por fila, ancho completo -->
      <div
        v-for="grupo in gruposOrdenados"
        :key="grupo.clave"
        class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden"
      >
        <!-- Encabezado: sigla, materia, nombre materia, grupo, docente y toggle en una sola franja -->
        <div class="flex items-center justify-between gap-3 px-6 py-4 bg-slate-800 text-white">
          <div class="flex items-center gap-3 min-w-0">

             <span :class="colorPlan(grupo.siglaPlan)" class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold">
              {{ grupo.siglaPlan }}
            </span>
            <span class="shrink-0 rounded-full bg-slate-700 px-2.5 py-0.5 text-xs font-semibold text-slate-200">
              Nivel {{ grupo.nivel }}
            </span>
           
            <h2 class="font-bold text-sm truncate">
              {{ grupo.materia }} — {{ grupo.nombreMateria }}
            </h2>
          </div>

          <span class="shrink-0 rounded-full bg-slate-700 px-3 py-1 text-xs">
            Grupo {{ grupo.grupo }}
          </span>
        </div>

        <!-- Franja del docente + toggle de estudiantes, lado a lado para aprovechar el ancho -->
        <button
          type="button"
          @click="toggle(grupo.clave)"
          class="w-full flex items-center justify-between gap-3 px-6 py-3 bg-slate-50 hover:bg-slate-100 transition-colors"
        >
          <div class="min-w-0 text-left">
            <p v-if="grupo.docente" class="text-sm font-medium text-slate-800 truncate">
              <span class="text-slate-400 font-normal">COD: {{ grupo.docente.codDocente }}</span>
              — {{ grupo.docente.docente }}
            </p>
            <p v-else class="text-sm text-slate-400 italic">Sin docente asignado</p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <span class="text-sm font-medium text-slate-700">
              Estudiantes
              <span class="ml-1.5 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700 font-semibold">
                {{ grupo.estudiantes.length }}
              </span>
            </span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4 text-slate-400 transition-transform duration-200"
              :class="{ 'rotate-180': estaAbierto(grupo.clave) }"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </button>

        <!-- Tabla de estudiantes (solo si esta abierto) -->
        <div v-if="estaAbierto(grupo.clave)" class="overflow-x-auto border-t border-slate-100">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100">
                <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3 w-10">Nro</th>
                <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Codigo</th>
                <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nombre</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr
                v-for="(est, idx) in grupo.estudiantes"
                :key="est.codEstudiante"
                class="hover:bg-blue-50/40 transition-colors"
              >
                <td class="px-6 py-3 text-slate-400 text-xs">{{ idx + 1 }}</td>
                <td class="px-4 py-3 text-slate-800 font-medium">{{ est.codEstudiante }}</td>
                <td class="px-4 py-3 text-slate-800 font-medium">{{ est.estudiante }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  // Grupos ya armados por el backend/servicio:
  // { clave, siglaPlan, materia, nombreMateria, grupo, nivel, docente, estudiantes }
  grupos: {
    type: Array,
    required: true,
    default: () => [],
  },
  cargando: {
    type: Boolean,
    default: false,
  },
})

// Orden de despliegue: primero los niveles mas bajos, luego materia y grupo
const gruposOrdenados = computed(() => {
  return [...props.grupos].sort((a, b) => {
    if (a.nivel !== b.nivel) return a.nivel.localeCompare(b.nivel)
    if (a.materia !== b.materia) return String(a.materia).localeCompare(String(b.materia))
    return String(a.grupo).localeCompare(String(b.grupo))
  })
})

// ─────────────────────────────────────────────
// Estado de expandido/colapsado por grupo
// ─────────────────────────────────────────────
const abiertos = reactive(new Set())

function estaAbierto(clave) {
  return abiertos.has(clave)
}

function toggle(clave) {
  if (abiertos.has(clave)) abiertos.delete(clave)
  else abiertos.add(clave)
}

function expandirTodos() {
  gruposOrdenados.value.forEach((g) => abiertos.add(g.clave))
}

function colapsarTodos() {
  abiertos.clear()
}

// Si cambian los filtros/datos, empieza todo colapsado de nuevo
watch(
  () => props.grupos,
  () => abiertos.clear()
)

const COLORES = {
  ADM: 'bg-blue-100 text-blue-700',
  COM: 'bg-emerald-100 text-emerald-700',
  CON: 'bg-orange-100 text-orange-700',
  FIN: 'bg-violet-100 text-violet-700',
  ECO: 'bg-rose-100 text-rose-700',
}

const colorPlan = (sigla) => COLORES[sigla] || 'bg-slate-100 text-slate-700'
</script>