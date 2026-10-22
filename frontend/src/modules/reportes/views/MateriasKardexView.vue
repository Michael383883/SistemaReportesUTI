<template>
  <div>
    <!-- Leyenda -->
    <div class="flex flex-wrap gap-x-4 gap-y-1.5 mb-3 text-[12px] text-slate-600">
      <span v-for="(e, k) in ESTADOS" :key="k" class="inline-flex items-center gap-1.5">
        <span class="w-2.5 h-2.5 rounded-full" :class="e.dot"></span>{{ e.label }}
      </span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200">
      <table class="w-full text-sm">
        <thead class="bg-slate-800 text-slate-200">
          <tr class="text-left text-[0.68rem] uppercase tracking-widest">
            <th class="px-4 py-2.5 font-semibold">Gestión</th>
            <th class="px-4 py-2.5 font-semibold">Periodo</th>
            <th class="px-4 py-2.5 font-semibold">Docente</th>
            <th class="px-4 py-2.5 font-semibold">Horario</th>
            <th class="px-4 py-2.5 font-semibold">Aula</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="f in filas"
            :key="f.idx"
            class="border-t border-slate-100 transition-colors"
            :class="ESTADOS[f.estado].row"
          >
            <!-- Barra lateral de color -->
            <td class="px-4 py-2.5 border-l-4" :class="ESTADOS[f.estado].borde">{{ f.gestion }}</td>
            <td class="px-4 py-2.5">{{ f.periodo }}</td>

            <td class="px-4 py-2.5">
              <div :class="f.estado === 'continua' ? 'text-slate-400' : 'text-slate-900 font-medium'">
                {{ f.docente }}
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-1.5">
                <span
                  v-if="f.estado !== 'continua'"
                  class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                  :class="ESTADOS[f.estado].badge"
                >
                  {{ ESTADOS[f.estado].label }}
                </span>
                <span v-if="f.docenteAnterior" class="text-[11px] text-slate-500">
                  antes: {{ f.docenteAnterior }}
                </span>
              </div>
            </td>

            <td
              class="px-4 py-2.5"
              :class="f.cambios.horario ? 'bg-amber-100 font-semibold text-amber-900' : 'text-slate-500'"
              :title="f.cambios.horario ? `Antes: ${f.cambios.horario}` : ''"
            >
              {{ f.horario }}
            </td>
            <td
              class="px-4 py-2.5"
              :class="f.cambios.aula ? 'bg-amber-100 font-semibold text-amber-900' : 'text-slate-500'"
              :title="f.cambios.aula ? `Antes: ${f.cambios.aula}` : ''"
            >
              {{ f.aula }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  registros: { type: Array, default: () => [] },
  masRecientePrimero: { type: Boolean, default: true },
})

const ESTADOS = {
  inicial:  { label: 'Primer registro',   dot: 'bg-slate-400',  row: '',               borde: 'border-l-slate-300',  badge: 'bg-slate-100 text-slate-600' },
  continua: { label: 'Sin cambios',       dot: 'bg-slate-200',  row: '',               borde: 'border-l-transparent', badge: '' },
  cambio:   { label: 'Cambio de docente', dot: 'bg-amber-500',  row: 'bg-amber-50',    borde: 'border-l-amber-500',  badge: 'bg-amber-100 text-amber-800' },
  retorna:  { label: 'Retoma la materia', dot: 'bg-violet-500', row: 'bg-violet-50',   borde: 'border-l-violet-500', badge: 'bg-violet-100 text-violet-800' },
}

// Campos extra a comparar con el periodo anterior (se resaltan solo en la celda)
const CAMPOS = ['horario', 'aula']

const filas = computed(() => {
  // 1) orden cronológico ascendente para comparar contra el anterior
  const orden = [...props.registros].sort(
    (a, b) => String(a.gestion).localeCompare(String(b.gestion)) ||
              String(a.periodo).localeCompare(String(b.periodo))
  )

  const vistos = new Set()
  const res = orden.map((r, i) => {
    const prev = orden[i - 1]
    let estado = 'continua'
    let docenteAnterior = null

    if (!prev) estado = 'inicial'
    else if (r.docente !== prev.docente) {
      estado = vistos.has(r.docente) ? 'retorna' : 'cambio'
      docenteAnterior = prev.docente
    }
    vistos.add(r.docente)

    const cambios = {}
    if (prev) CAMPOS.forEach(c => { if (r[c] !== prev[c]) cambios[c] = prev[c] ?? '—' })

    return { ...r, idx: i, estado, docenteAnterior, cambios }
  })

  // 2) mostrar lo más reciente arriba (opcional)
  return props.masRecientePrimero ? res.reverse() : res
})
</script>