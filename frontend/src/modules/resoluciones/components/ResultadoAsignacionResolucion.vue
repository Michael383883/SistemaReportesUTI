<!-- components/ResultadoAsignacionResolucion.vue -->
<template>
  <div class="space-y-5 px">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="px-6 py-4 flex items-center gap-3 bg-slate-900">
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
          :class="gruposActualizados.length > 0 ? 'bg-emerald-500/15' : 'bg-amber-500/15'"
        >
          <svg v-if="gruposActualizados.length > 0" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <svg v-else class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div>
          <h2 class="text-sm font-semibold text-white m-0">
            {{ gruposActualizados.length > 0 ? 'Resolución asignada y aplicada en grupos' : 'Resolución asignada, pero no se aplicó en grupos' }}
          </h2>
          <p class="text-xs text-slate-100 m-0 mt-0.5">
            {{ ultimasAsignadas.length }} materia{{ ultimasAsignadas.length !== 1 ? 's' : '' }} vinculada{{ ultimasAsignadas.length !== 1 ? 's' : '' }} a {{ resolucionNro }}
            · {{ gruposActualizados.length }} registro{{ gruposActualizados.length !== 1 ? 's' : '' }} actualizados en grupos
          </p>
        </div>
      </div>

      <!-- Tabla de grupos actualizados -->
      <div>
        <div class="px-6 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center gap-2">
          <p class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-900">Registros actualizados en grupos</p>
          <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-semibold">
            {{ gruposActualizados.length }}
          </span>
        </div>

        <div v-if="gruposActualizados.length > 0" class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-slate-900 border-b border-slate-100">
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Año</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Per.</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Plan</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Materia</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Grupo</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Docente</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Tipo</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Tipo de ingreso</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Resolución</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Designación</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Reporte</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(g, i) in gruposActualizados" :key="i" class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-2.5 text-slate-900 font-mono">{{ g.anio }}</td>
                <td class="px-4 py-2.5 text-slate-900">{{ g.periodo }}</td>
                <td class="px-4 py-2.5 text-slate-900 font-mono">{{ g.plan }}</td>
                <td class="px-4 py-2.5 text-slate-900 font-mono">{{ g.materia }}</td>
                <td class="px-4 py-2.5 text-slate-900">{{ g.grupo }}</td>
                <td class="px-4 py-2.5 text-slate-900 font-medium" :title="`Código: ${g.docente}`">
                  {{ nombreDocentePorCodigo(g.docente) }}
                </td>
                <td class="px-4 py-2.5 text-slate-900">{{ g.tipo }}</td>
                <td class="px-4 py-2.5 text-slate-500">{{ g.tipoIngreso || '—' }}</td>
                <td class="px-4 py-2.5 text-amber-700 font-medium">{{ g.resolucion }}</td>
                <td class="px-4 py-2.5 text-slate-900 max-w-xs truncate" :title="g.designacion">{{ g.designacion }}</td>
                <td class="px-4 py-2.5">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold
                           border border-slate-200 text-slate-800 hover:border-blue-900 hover:text-blue-700
                           hover:bg-blue-100 transition-colors"
                    title="Abrir reporte de materias dictadas de este docente"
                    @click="$emit('ver-reporte', g)"
                  >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                      <polyline points="14 2 14 8 20 8"/>
                      <line x1="16" y1="13" x2="8" y2="13"/>
                      <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Ver reporte
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-6">
          <div class="flex items-start gap-3 px-4 py-3.5 rounded-lg bg-amber-50 border border-amber-200">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
              <p class="text-xs font-semibold text-amber-800 m-0">
                La resolución se guardó, pero no se actualizó ningún registro en grupos
              </p>
              <p class="text-xs text-amber-700/80 m-0 mt-1 leading-relaxed">
                Las {{ ultimasAsignadas.length }} materia{{ ultimasAsignadas.length !== 1 ? 's' : '' }} qued{{ ultimasAsignadas.length !== 1 ? 'aron' : 'ó' }} vinculada{{ ultimasAsignadas.length !== 1 ? 's' : '' }} a
                <span class="font-medium text-amber-900">{{ resolucionNro }}</span>, pero en la tabla de grupos no existe
                ningún registro con ese mismo año y periodo para esa combinación de docente, plan, materia y grupo.
                Esto suele pasar cuando la materia marcada corresponde a una gestión distinta a la de la resolución.
              </p>
            </div>
          </div>

          <!-- Detalle de lo que se intentó vincular, para que el usuario pueda revisar qué falló -->
          <div v-if="ultimasAsignadas.length > 0" class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                  <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">Docente</th>
                  <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">Plan</th>
                  <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
                  <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">Grupo</th>
                  <th class="px-3 py-2 text-left text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">Gestión marcada</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(m, i) in ultimasAsignadas" :key="i">
                  <td class="px-3 py-2 text-slate-600" :title="`Código: ${m.cod_docente}`">
                    {{ nombreDocentePorCodigo(m.cod_docente) }}
                  </td>
                  <td class="px-3 py-2 text-slate-500 font-mono">{{ m.cod_plan }}</td>
                  <td class="px-3 py-2 text-slate-500 font-mono">{{ m.cod_materia }}</td>
                  <td class="px-3 py-2 text-slate-500">{{ m.grupo ?? '—' }}</td>
                  <td class="px-3 py-2 text-red-600 font-medium">{{ m.gestion ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <p class="text-[0.68rem] text-slate-400 mt-3 mb-0">
            Revisá en la tabla de grupos si existe un registro para este docente/materia/grupo con el mismo año y periodo que la resolución
            ({{ resolucionAnioPeriodoLabel }}). Si la materia corresponde a otra gestión, puede que necesites otra resolución o corregir el dato en grupos.
          </p>
        </div>
      </div>

      <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 bg-slate-50 gap-3">
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-medium border border-slate-400 text-slate-900 hover:text-slate-100 hover:bg-amber-500 hover:border-amber-400 transition-colors"
          @click="$emit('asignar-otra')"
        >
          Asignar otra resolución
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm"
          @click="$emit('ir-a-listado')"
        >
          Ir al listado de resoluciones
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  gruposActualizados: { type: Array, default: () => [] },
  ultimasAsignadas: { type: Array, default: () => [] },
  resolucionNro: { type: String, default: '' },
  resolucionAnioPeriodoLabel: { type: String, default: '—' },
  // Código y nombre completo del docente que estaba seleccionado al momento
  // de terminar la asignación. aplicarEnGrupos solo devuelve el CODIGO
  // del docente (no hace join con DOCENTES), así que este par se usa
  // para mostrar el nombre en vez del código crudo en las tablas.
  docenteAsignadoCodigo: { type: [Number, String], default: '' },
  docenteAsignadoNombre: { type: String, default: '' },
})

defineEmits(['ver-reporte', 'asignar-otra', 'ir-a-listado'])

function nombreDocentePorCodigo(codigo) {
  if (
    props.docenteAsignadoCodigo &&
    String(codigo) === String(props.docenteAsignadoCodigo) &&
    props.docenteAsignadoNombre
  ) {
    return props.docenteAsignadoNombre
  }
  // Fallback: si por algún motivo el código no coincide (no debería pasar,
  // ya que todo lo asignado es del mismo docente seleccionado), mostramos
  // el código tal cual para no perder la información.
  return codigo ?? '—'
}
</script>