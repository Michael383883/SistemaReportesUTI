<template>
  <div class="bg-white border border-[#e8edf2] rounded-2xl p-5">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-[#081F33]">{{ title }}</h3>
      <slot name="action" />
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="flex items-end gap-2 h-40 px-1">
      <div
        v-for="n in 6" :key="n"
        class="flex-1 rounded-t-md bg-gradient-to-r from-[#e8edf2]
               via-[#f4f6f9] to-[#e8edf2] bg-[length:200%_100%] animate-shimmer"
        :style="{ height: (30 + n * 10) + '%' }"
      />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!labels.length"
      class="h-40 flex flex-col items-center justify-center gap-2
             text-gray-400 text-sm"
    >
      <BarChart2 class="w-8 h-8 text-gray-300" />
      <span>Sin datos disponibles</span>
    </div>

    <!-- Chart -->
    <div v-else class="overflow-hidden">
      <svg
        :viewBox="`0 0 ${svgW} ${svgH}`"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full overflow-visible"
        style="height:160px"
      >
        <!-- Grid lines -->
        <line
          v-for="(y, i) in gridYs" :key="'g'+i"
          :x1="padL" :y1="y"
          :x2="svgW - padR" :y2="y"
          stroke="#e8edf2" stroke-width="1"
        />

        <!-- Bars -->
        <g v-for="(v, i) in values" :key="'b'+i">
          <rect
            :x="barX(i)" :y="barY(v)"
            :width="barW" :height="barH(v)"
            :rx="4" :fill="color"
            :opacity="hovered === i ? 1 : 0.72"
            style="transition: opacity 0.15s, y 0.4s, height 0.4s"
            @mouseenter="hovered = i"
            @mouseleave="hovered = null"
          />

          <!-- Tooltip -->
          <g v-if="hovered === i">
            <rect
              :x="barX(i) + barW / 2 - 28"
              :y="barY(v) - 28"
              width="56" height="22" rx="6"
              :fill="color"
            />
            <text
              :x="barX(i) + barW / 2"
              :y="barY(v) - 13"
              text-anchor="middle"
              fill="#fff"
              font-size="11"
              font-weight="600"
            >{{ v.toLocaleString('es-BO') }}</text>
          </g>

          <!-- Label X -->
          <text
            :x="barX(i) + barW / 2"
            :y="svgH - 4"
            text-anchor="middle"
            fill="#9ca3af"
            font-size="10"
          >{{ labels[i] }}</text>
        </g>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { BarChart2 }     from 'lucide-vue-next'

const props = defineProps({
  title:   { type: String,  required: true },
  labels:  { type: Array,   default: () => [] },
  values:  { type: Array,   default: () => [] },
  color:   { type: String,  default: '#D28B45' },
  loading: { type: Boolean, default: false },
})

const hovered = ref(null)

const svgW = 480, svgH = 180
const padL = 8,   padR = 8
const padT = 28,  padB = 22

const maxVal = computed(() => Math.max(...props.values, 1))
const n      = computed(() => props.values.length || 1)
const barW   = computed(() => Math.max(16, (svgW - padL - padR) / n.value * 0.6))
const gap    = computed(() => (svgW - padL - padR - barW.value * n.value) / (n.value + 1))
const chartH = computed(() => svgH - padT - padB)

function barX(i) { return padL + gap.value * (i + 1) + barW.value * i }
function barY(v) { return padT + chartH.value * (1 - v / maxVal.value) }
function barH(v) { return chartH.value * (v / maxVal.value) }

const gridYs = computed(() =>
  [0.25, 0.5, 0.75, 1].map(f => padT + chartH.value * (1 - f))
)
</script>