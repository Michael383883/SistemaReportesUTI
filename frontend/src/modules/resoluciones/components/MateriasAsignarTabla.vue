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
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-24">Asignar</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro"
            class="border-b border-slate-700/60 transition-colors hover:bg-white/[0.025]"
            :class="[
              i % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20',
              estaMarcada(m) ? 'bg-amber-500/[0.06]' : ''
            ]"
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

            <!-- Compartido -->
            <td class="px-4 py-3">
              <span v-if="m.compartido"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300">
                Compartido
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución (existente, ya designada) -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-emerald-400 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- Asignar (checkbox) -->
            <td class="px-4 py-3 text-center">
              <button
                type="button"
                :disabled="!resolucionActiva"
                :title="checkTitle(m)"
                class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150
                       disabled:opacity-30 disabled:cursor-not-allowed"
                :class="estaMarcada(m)
                  ? 'bg-amber-500 border-amber-500 text-slate-900'
                  : 'bg-transparent border-slate-600 text-transparent hover:border-amber-500/60'"
                @click="$emit('toggle', m)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </button>
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
const props = defineProps({
  materias: { type: Array, default: () => [] },
  resolucionActiva: { type: Object, default: null },
  marcadasKeys: { type: Array, default: () => [] }, // keys ya marcadas globalmente (de este u otro docente)
  docenteCod: { type: [Number, String], default: null },
})

defineEmits(['toggle'])

function keyDe(m) {
  return `${props.docenteCod}__${m.plan}__${m.materia}__${m.grp}__${m.gestion}`
}

function estaMarcada(m) {
  return props.marcadasKeys.includes(keyDe(m))
}

function checkTitle(m) {
  if (!props.resolucionActiva) return 'Seleccioná primero una resolución'
  return estaMarcada(m) ? 'Quitar asignación' : 'Asignar resolución a esta materia'
}

const tipoGestion = (gestion) => {
  if (gestion?.includes('Verano'))
    return { class: 'bg-orange-500/10 text-orange-400', dot: 'bg-orange-400' }
  if (gestion?.includes('Invierno'))
    return { class: 'bg-sky-500/10 text-sky-400', dot: 'bg-sky-400' }
  return { class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
}

const GRP_MAP = {
  '059801': { label: 'CON', class: 'bg-violet-500/15 text-violet-300', dot: 'bg-violet-400' },
  '109401': { label: 'ADM', class: 'bg-blue-500/15 text-blue-300',     dot: 'bg-blue-400'   },
  '125091': { label: 'COM', class: 'bg-green-500/15 text-green-300',   dot: 'bg-green-400'  },
  '126091': { label: 'FIN', class: 'bg-teal-500/15 text-teal-300',     dot: 'bg-teal-400'   },
  '089801': { label: 'ECO', class: 'bg-amber-500/15 text-amber-300',   dot: 'bg-amber-400'  },
}

const tipoGrp = (plan) =>
  GRP_MAP[plan] ?? { label: plan, class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
</script>