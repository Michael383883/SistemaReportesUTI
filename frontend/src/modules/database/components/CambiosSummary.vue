<template>
  <div v-if="cambios" class="flex flex-col gap-1.5">

    <!-- Forma "catálogo": antes → después -->
    <div v-if="cambios.filas_antes !== undefined" class="flex items-center gap-2 text-[11px] font-mono">
      <span class="text-slate-500">{{ cambios.filas_antes }}</span>
      <ArrowRight class="w-3 h-3 text-slate-600" aria-hidden="true" />
      <span class="text-slate-300 font-semibold">{{ cambios.filas_despues }}</span>
      <span class="font-semibold" :class="diffClass">({{ diffLabel }})</span>
    </div>

    <!-- Forma "insert/update/delete" (MERGE o DELETE+INSERT) -->
    <div v-else class="flex flex-wrap items-center gap-1.5">
      <span v-if="cambios.insertados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-emerald-500/15 text-emerald-400">
        <Plus class="w-3 h-3" aria-hidden="true" /> {{ cambios.insertados }} nuevo{{ cambios.insertados === 1 ? '' : 's' }}
      </span>
      <span v-if="cambios.actualizados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-blue-500/15 text-blue-400">
        <Pencil class="w-3 h-3" aria-hidden="true" /> {{ cambios.actualizados }} actualizado{{ cambios.actualizados === 1 ? '' : 's' }}
      </span>
      <span v-if="cambios.eliminados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-red-500/15 text-red-400">
        <Trash2 class="w-3 h-3" aria-hidden="true" /> {{ cambios.eliminados }} eliminado{{ cambios.eliminados === 1 ? '' : 's' }}
      </span>
      <span v-if="sinCambios" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-slate-700/50 text-slate-400">
        <CheckCircle2 class="w-3 h-3" aria-hidden="true" /> Sin cambios
      </span>
    </div>

    <p v-if="cambios.nota" class="text-[10.5px] text-slate-500 italic m-0 leading-relaxed">
      {{ cambios.nota }}
    </p>

    <!-- Detalle fila por fila (solo GRUPOS lo trae) -->
    <details v-if="cambios.detalle?.length" class="group mt-0.5">
      <summary class="cursor-pointer select-none text-[11px] text-slate-400 hover:text-slate-200 transition-colors flex items-center gap-1 w-fit list-none">
        <ChevronRight class="w-3 h-3 transition-transform group-open:rotate-90" aria-hidden="true" />
        Ver detalle ({{ cambios.detalle.length }})
      </summary>
      <ul class="flex flex-col gap-1 mt-2 max-h-48 overflow-y-auto pr-1 list-none p-0 m-0">
        <li
          v-for="(row, i) in cambios.detalle"
          :key="i"
          class="flex items-center gap-2 text-[10.5px] font-mono bg-slate-950/40 rounded px-2 py-1"
        >
          <component :is="accionIcon(row.accion)" class="w-3 h-3 shrink-0" :class="accionColor(row.accion)" aria-hidden="true" />
          <span class="text-slate-400 truncate">
            {{ row.plan ?? '—' }} · {{ row.materia ?? '—' }} · {{ row.grupo ?? '—' }}
          </span>
        </li>
      </ul>
    </details>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Plus, Pencil, Trash2, ChevronRight, ArrowRight, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({
  // Puede venir en 3 formas desde el backend:
  //  - { insertados, actualizados, eliminados, detalle?, nota? }  (GRUPOS, MERGE)
  //  - { insertados, eliminados, nota? }                          (HORARIOS2/KARDEX_EXT)
  //  - { filas_antes, filas_despues, diferencia, nota? }          (catálogos)
  cambios: { type: Object, default: null },
})

const sinCambios = computed(() => {
  if (!props.cambios || props.cambios.filas_antes !== undefined) return false
  const { insertados = 0, actualizados = 0, eliminados = 0 } = props.cambios
  return insertados === 0 && actualizados === 0 && eliminados === 0
})

const diffClass = computed(() => {
  const d = props.cambios?.diferencia ?? 0
  if (d > 0) return 'text-emerald-400'
  if (d < 0) return 'text-red-400'
  return 'text-slate-500'
})

const diffLabel = computed(() => {
  const d = props.cambios?.diferencia ?? 0
  return d > 0 ? `+${d}` : `${d}`
})

function accionIcon(accion) {
  if (accion === 'INSERT') return Plus
  if (accion === 'UPDATE') return Pencil
  if (accion === 'DELETE') return Trash2
  return Plus
}

function accionColor(accion) {
  if (accion === 'INSERT') return 'text-emerald-400'
  if (accion === 'UPDATE') return 'text-blue-400'
  if (accion === 'DELETE') return 'text-red-400'
  return 'text-slate-400'
}
</script>