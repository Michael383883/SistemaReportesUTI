<template>
  <div class="bg-white border border-[#e8edf2] rounded-2xl p-5">

    <h3 class="text-sm font-bold text-[#081F33] mb-4">{{ title }}</h3>

    <!-- Skeleton -->
    <div v-if="loading"
      class="w-28 h-28 rounded-full mx-auto
             bg-gradient-to-r from-[#e8edf2] via-[#f4f6f9] to-[#e8edf2]
             bg-[length:200%_100%] animate-shimmer" />

    <!-- Empty -->
    <div v-else-if="!slices.length"
      class="flex flex-col items-center justify-center gap-2 py-10
             text-gray-400 text-sm">
      <PieChart class="w-7 h-7 text-gray-300" />
      Sin datos
    </div>

    <!-- Chart -->
    <div v-else class="flex items-center gap-5">
      <svg viewBox="0 0 120 120" class="w-[120px] h-[120px] flex-shrink-0 -rotate-90">
        <circle cx="60" cy="60" r="42" fill="none" stroke="#f0f0f0" stroke-width="16" />
        <circle
          v-for="(s, i) in slices" :key="i"
          cx="60" cy="60" r="42"
          fill="none"
          :stroke="s.color"
          stroke-width="16"
          stroke-linecap="butt"
          :stroke-dasharray="`${s.arc} ${C}`"
          :stroke-dashoffset="-s.offset"
          style="transition: stroke-dasharray 1s ease"
        />
        <text x="60" y="56" text-anchor="middle" font-size="16"
              font-weight="700" fill="#081F33" style="transform:rotate(90deg) translate(0,-120px)">
          {{ total }}
        </text>
        <text x="60" y="70" text-anchor="middle" font-size="9" fill="#9ca3af"
              style="transform:rotate(90deg) translate(0,-120px)">
          grupos
        </text>
      </svg>

      <!-- Leyenda -->
      <div class="flex flex-col gap-2 flex-1">
        <div v-for="(s, i) in slices" :key="i"
          class="flex items-center gap-2 text-xs">
          <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: s.color }" />
          <span class="flex-1 text-gray-600 capitalize truncate max-w-[130px]">{{ s.label }}</span>
          <span class="font-bold text-[#081F33]">{{ s.value }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed }  from 'vue'
import { PieChart }  from 'lucide-vue-next'

const PALETTE = ['#D28B45','#081F33','#3b82f6','#10b981','#f59e0b','#8b5cf6']
const C = 2 * Math.PI * 42   // ≈ 263.9

const props = defineProps({
  title:   { type: String,  required: true },
  items:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})

const total = computed(() =>
  props.items.reduce((s, i) => s + Number(i.cantidad ?? i.value ?? 0), 0)
)
const slices = computed(() => {
  let offset = 0
  return props.items.map((item, i) => {
    const val = Number(item.cantidad ?? item.value ?? 0)
    const arc = total.value ? (val / total.value) * C : 0
    const s = { label: item.tipo_materia ?? item.label ?? `Tipo ${i+1}`,
                value: val, color: PALETTE[i % PALETTE.length], arc, offset }
    offset += arc
    return s
  })
})
</script>