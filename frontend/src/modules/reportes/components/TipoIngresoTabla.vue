<!-- components/TipoIngresoTabla -->
<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-900">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Materia</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-14">GRP</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Resolución</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-44">Tipo de ingreso</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro ?? i"
            class="border-b border-slate-100 transition-colors"
            :class="tieneCambioPendiente(m)
              ? 'bg-emerald-50 hover:bg-emerald-100/70'
              : (i % 2 === 0 ? 'bg-white hover:bg-slate-50' : 'bg-slate-50/60 hover:bg-slate-50')"
          >
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-400 font-medium text-[13px] tabular-nums">{{ m.nro ?? i + 1 }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                {{ m.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ m.plan }}</td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-800 font-medium">{{ m.materia }}</td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-600 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución (informativo, de solo lectura) -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-amber-600 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-400 text-xs">Sin resolución</span>
            </td>

            <!-- Tipo de ingreso (editable) -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <select
                  :value="valorActual(m)"
                  class="w-full rounded-lg border text-xs px-2 py-1.5 outline-none transition-colors cursor-pointer
                         focus:ring-2 focus:ring-amber-500/20"
                  :class="tieneCambioPendiente(m)
                    ? 'border-emerald-400 bg-emerald-50 text-emerald-700 focus:border-emerald-500'
                    : 'border-slate-300 bg-white text-slate-700 focus:border-amber-500'"
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
                  class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 shrink-0"
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
    <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
      <span class="text-xs text-slate-500">
        {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
      </span>
      <span v-if="cantidadCambios > 0" class="text-xs text-emerald-600 font-medium flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"/>
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