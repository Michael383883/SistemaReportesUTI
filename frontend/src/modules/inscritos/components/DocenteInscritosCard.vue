
<template>
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

    <!-- ── Cabecera del docente ──────────────────────────── -->
    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-slate-800 to-slate-700 text-white">
      <!-- Avatar iniciales -->
      <div class="w-11 h-11 rounded-full bg-white/15 border-2 border-white/25 flex items-center justify-center font-bold text-sm tracking-wider shrink-0">
        {{ iniciales }}
      </div>

      <!-- Nombre y código -->
      <div class="flex-1 min-w-0">
        <span class="text-xs text-white/60 tracking-wide uppercase block">Cód. {{ docente.cod_docente }}</span>
        <h2 class="font-bold text-base truncate leading-tight mt-0.5">
          {{ docente.apellidos }}, {{ docente.nombres }}
        </h2>
      </div>

      <!-- Badge total -->
      <div class="flex flex-col items-center bg-white/10 rounded-xl px-4 py-2 shrink-0">
        <span class="text-2xl font-extrabold leading-none">{{ docente.total_inscritos }}</span>
        <span class="text-[10px] uppercase tracking-widest text-white/70 mt-0.5">inscritos</span>
      </div>
    </div>

    <!-- ── Grilla de carreras (chips/cards compactos) ─────── -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-px bg-slate-100">
      <div
        v-for="carrera in docente.carreras"
        :key="carrera.plan + carrera.carrera"
        class="bg-white"
      >
        <!-- Botón para desplegar/colapsar -->
        <button
          class="w-full text-left px-4 py-3 flex items-center gap-2 hover:bg-slate-50 transition-colors"
          @click="toggleCarrera(carrera.carrera + carrera.plan)"
        >
          <!-- Sigla con color -->
          <span
            class="text-xs font-extrabold tracking-wider px-2 py-0.5 rounded-md shrink-0"
            :class="colorClasses[carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'"
          >
            {{ carrera.carrera }}
          </span>

          <!-- Plan -->
          <span class="text-[11px] text-slate-400 flex-1 truncate">P.{{ carrera.plan }}</span>

          <!-- Contador de inscritos — lo protagonico -->
          <span class="text-lg font-extrabold tabular-nums" :class="colorClasses[carrera.carrera.toLowerCase()]?.num ?? 'text-slate-700'">
            {{ carrera.subtotal }}
          </span>

          <!-- Chevron -->
          <svg
            class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200"
            :class="{ 'rotate-180': abiertos.has(carrera.carrera + carrera.plan) }"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Lista desplegable con transición -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-96"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 max-h-96"
          leave-to-class="opacity-0 max-h-0"
        >
          <div
            v-if="abiertos.has(carrera.carrera + carrera.plan)"
            class="overflow-hidden border-t border-slate-100"
          >
            <ul class="divide-y divide-slate-50 max-h-60 overflow-y-auto">
              <li
                v-for="(est, idx) in carrera.inscritos"
                :key="est.codigo"
                class="flex items-center gap-2 px-4 py-2 text-xs hover:bg-slate-50"
              >
                <span class="text-slate-300 w-4 text-right shrink-0 tabular-nums">{{ idx + 1 }}</span>
                <span class="font-mono text-slate-400 w-20 shrink-0">{{ est.codigo }}</span>
                <span class="text-slate-700 font-medium truncate">{{ est.nombre }}</span>
              </li>
            </ul>
          </div>
        </Transition>
      </div>
    </div>

    <!-- ── Fila de totales por carrera + gran total ───────── -->
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 bg-slate-50 border-t border-slate-200">
      <!-- Subtotales visuales por carrera -->
      <div class="flex flex-wrap gap-2">
        <span
          v-for="carrera in docente.carreras"
          :key="'tot-' + carrera.carrera + carrera.plan"
          class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
          :class="colorClasses[carrera.carrera.toLowerCase()]?.pill ?? 'bg-slate-100 text-slate-600'"
        >
          {{ carrera.carrera }}
          <span class="font-extrabold">{{ carrera.subtotal }}</span>
        </span>
      </div>

      <!-- Gran total -->
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-bold tracking-widest uppercase text-slate-400">Total</span>
        <span class="text-xl font-extrabold text-slate-800 bg-blue-100 text-blue-800 px-4 py-0.5 rounded-full tabular-nums">
          {{ docente.total_inscritos }}
        </span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  docente: { type: Object, required: true },
})

// Set de claves abiertas (carrera+plan)
const abiertos = ref(new Set())

function toggleCarrera(key) {
  const next = new Set(abiertos.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  abiertos.value = next
}

const iniciales = computed(() => {
  const a = props.docente.apellidos?.[0] ?? ''
  const n = props.docente.nombres?.[0] ?? ''
  return (a + n).toUpperCase()
})

// Paleta de colores por sigla de carrera (Tailwind classes)
const colorClasses = {
  adm: { badge: 'bg-blue-100 text-blue-700',   num: 'text-blue-600',   pill: 'bg-blue-100 text-blue-700'   },
  eco: { badge: 'bg-emerald-100 text-emerald-700', num: 'text-emerald-600', pill: 'bg-emerald-100 text-emerald-700' },
  ccp: { badge: 'bg-purple-100 text-purple-700', num: 'text-purple-600', pill: 'bg-purple-100 text-purple-700' },
  com: { badge: 'bg-orange-100 text-orange-700', num: 'text-orange-600', pill: 'bg-orange-100 text-orange-700' },
  fin: { badge: 'bg-yellow-100 text-yellow-700', num: 'text-yellow-600', pill: 'bg-yellow-100 text-yellow-700' },
  nn:  { badge: 'bg-slate-100 text-slate-600',  num: 'text-slate-500',  pill: 'bg-slate-100 text-slate-600'  },
}
</script>
