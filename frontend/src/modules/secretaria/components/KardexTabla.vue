<template>
  <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-slate-900 border-b border-slate-800">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Materia</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-14">GRP</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro ?? i"
            class="border-b border-slate-100 transition-colors hover:bg-slate-100"
            :class="i % 2 === 0 ? 'bg-white' : 'bg-sky-100'"
          >
            <td class="px-4 py-3 text-slate-800 font-medium text-[13px] tabular-nums">{{ m.nro }}</td>

            <td class="px-4 py-3">
              <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                {{ m.gestion }}
              </span>
            </td>

            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide bg-indigo-50 text-indigo-700">
                {{ planAbrev(m) }}
              </span>
            </td>

            <td class="px-4 py-3 text-slate-800 font-medium">{{ m.materia }}</td>

            <td class="px-4 py-3">
              <span
                v-if="m.compartido === 'COMPARTIDO'"
                :title="'Grupo compartido'"
                class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-50 text-violet-600"
              >Compartido</span>
              <span v-else class="text-slate-400 text-xs">—</span>
            </td>

            <td class="px-4 py-3 tabular-nums text-slate-700 font-semibold text-xs">{{ m.grp || '—' }}</td>
          </tr>

          <tr v-if="!materias.length">
            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
              Este docente no tiene materias dictadas registradas.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50 text-xs text-slate-500 text-right">
      {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  materias: { type: Array, default: () => [] },
})

// Mismo mapa que usa el PDF
const PLAN_MAP = {
  '089801': 'CCP',
  '109401': 'ADM',
  '125091': 'COM',
  '126091': 'FIN',
  '059801': 'ECO',
}

const planAbrev = (m) => {
  if (String(m.materia_codigo ?? '').trim().startsWith('240')) return 'DESC'
  const plan = String(m.plan ?? '').trim()
  return PLAN_MAP[plan] || plan || '—'
}
</script>
