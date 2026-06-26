<!-- components/TipoIngresoTabla -->
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
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-14">GRP</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Resolución</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-44">Tipo de ingreso</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro ?? i"
            class="border-b border-slate-700/60 transition-colors"
            :class="tieneCambioPendiente(m)
              ? 'bg-emerald-500/[0.07] hover:bg-emerald-500/[0.1]'
              : (i % 2 === 0 ? 'bg-transparent hover:bg-white/[0.025]' : 'bg-slate-900/20 hover:bg-white/[0.025]')"
          >
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-500 font-medium text-[13px] tabular-nums">{{ m.nro ?? i + 1 }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded bg-slate-700/60 text-slate-300">
                {{ m.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3 text-slate-300 font-mono text-xs">{{ m.plan }}</td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-200 font-medium">{{ m.materia }}</td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución (informativo, de solo lectura) -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-amber-400 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-600 text-xs">Sin resolución</span>
            </td>

            <!-- Tipo de ingreso (editable) -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <select
                  :value="valorActual(m)"
                  class="w-full rounded-lg border text-xs px-2 py-1.5 outline-none transition-colors cursor-pointer
                         focus:ring-2 focus:ring-amber-500/30"
                  :class="tieneCambioPendiente(m)
                    ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-300 focus:border-emerald-500'
                    : 'border-slate-700 bg-slate-900 text-slate-200 focus:border-amber-500'"
                  @change="onCambiar(m, $event.target.value)"
                >
                  <option value="">-- Seleccionar --</option>
                  <option value="ACEFALIA">ACEFALIA</option>
                  <option value="TEMPORAL">TEMPORAL</option>
                  <option value="TITULAR">TITULAR</option>
                  <option value="EXAMEN SUFICIENCIA">EXAMEN SUFICIENCIA</option>
                  <option value="EXAMEN COMPETENCIA">EXAMEN COMPETENCIA</option>
                </select>

                <span
                  v-if="tieneCambioPendiente(m)"
                  class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 shrink-0"
                  title="Cambio pendiente de aplicar"
                >
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 flex items-center justify-between">
      <span class="text-xs text-slate-500">
        {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
      </span>
      <span v-if="cantidadCambios > 0" class="text-xs text-emerald-400 font-medium flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"/>
        {{ cantidadCambios }} cambio{{ cantidadCambios !== 1 ? 's' : '' }} pendiente{{ cantidadCambios !== 1 ? 's' : '' }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  materias: { type: Array, default: () => [] },
  // Mapa de cambios pendientes: key -> nuevo tipo_ingreso elegido
  cambios: { type: Object, default: () => ({}) },
  docenteCod: { type: [Number, String], default: null },
})

const emit = defineEmits(['cambiar'])

function keyDe(m) {
  return `${props.docenteCod}__${m.plan}__${m.materia}__${m.grp}__${m.gestion}`
}

function tieneCambioPendiente(m) {
  const key = keyDe(m)
  return key in props.cambios && props.cambios[key] !== (m.tipo_ingreso ?? '')
}

// Valor mostrado en el select: el cambio pendiente si existe, sino el valor original guardado.
function valorActual(m) {
  const key = keyDe(m)
  return key in props.cambios ? props.cambios[key] : (m.tipo_ingreso ?? '')
}

function onCambiar(m, nuevoValor) {
  emit('cambiar', { key: keyDe(m), materia: m, valor: nuevoValor })
}

const cantidadCambios = computed(() => {
  return props.materias.filter(tieneCambioPendiente).length
})
</script>