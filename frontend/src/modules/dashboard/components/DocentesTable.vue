<template>
  <div class="bg-white border border-[#e8edf2] rounded-2xl p-5">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-[#081F33]">Top Docentes por Carga</h3>
      <router-link
        to="/reportes/docentes"
        class="inline-flex items-center gap-1 text-xs font-semibold
               text-[#D28B45] hover:underline"
      >
        Ver todos <ArrowRight class="w-3 h-3" />
      </router-link>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="flex flex-col gap-2">
      <div v-for="n in 5" :key="n"
        class="h-14 rounded-xl bg-gradient-to-r from-[#e8edf2] via-[#f4f6f9]
               to-[#e8edf2] bg-[length:200%_100%] animate-shimmer" />
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length"
      class="flex flex-col items-center justify-center gap-2 py-10
             text-gray-400 text-sm">
      <Users class="w-7 h-7 text-gray-300" />
      Sin datos de docentes
    </div>

    <!-- Lista -->
    <div v-else class="flex flex-col gap-1">
      <div
        v-for="(doc, i) in items"
        :key="doc.codigo"
        class="grid grid-cols-[28px_1fr_auto] gap-x-2.5 items-center
               px-2 py-2.5 rounded-xl hover:bg-gray-50 transition-colors"
      >
        <!-- Rank -->
        <span
          class="w-6 h-6 rounded-md flex items-center justify-center
                 text-[11px] font-bold"
          :class="rankClass(i)"
        >{{ i + 1 }}</span>

        <!-- Info -->
        <div>
          <p class="text-[13px] font-semibold text-gray-800 truncate max-w-[200px]">
            {{ doc.nombre }}
          </p>
          <p class="text-[11px] text-gray-400">Cód. {{ doc.codigo }}</p>
        </div>

        <!-- Right -->
        <div class="text-right">
          <span class="inline-flex items-center gap-1 text-[11px] font-semibold
                       text-[#D28B45] bg-amber-50 px-2 py-0.5 rounded-full">
            <Clock class="w-3 h-3" />
            {{ doc.total_horas ?? '—' }} hrs/sem
          </span>
          <p class="text-[11px] text-gray-400 mt-0.5">
            {{ doc.total_grupos }} grupos
          </p>
        </div>

        <!-- Barra (full row) -->
        <div class="col-span-3 h-[3px] bg-gray-100 rounded-full mt-1 overflow-hidden">
          <div
            class="h-full bg-[#D28B45] rounded-full transition-all duration-1000"
            :style="{ width: pct(doc.total_horas) + '%' }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed }              from 'vue'
import { ArrowRight, Users, Clock } from 'lucide-vue-next'

const props = defineProps({
  items:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})

const maxHoras = computed(() =>
  Math.max(...props.items.map(d => d.total_horas ?? 0), 1)
)
function pct(h)   { return Math.round(((h ?? 0) / maxHoras.value) * 100) }
function rankClass(i) {
  return i === 0 ? 'bg-amber-100 text-amber-700'
       : i === 1 ? 'bg-gray-200 text-gray-600'
       : i === 2 ? 'bg-orange-100 text-orange-700'
       :           'bg-gray-100 text-gray-500'
}
</script>