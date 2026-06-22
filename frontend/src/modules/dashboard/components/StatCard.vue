<template>
  <div
    class="bg-white border border-[#e8edf2] rounded-2xl p-5 flex gap-4
           items-start hover:-translate-y-0.5 hover:shadow-lg
           transition-all duration-200 cursor-default"
    :style="accentStyle"
  >
    <!-- Icono -->
    <div
      class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
      :style="{ background: color + '1a' }"
    >
      <component :is="iconComponent" class="w-5 h-5" :style="{ color }" />
    </div>

    <!-- Cuerpo -->
    <div class="flex-1 min-w-0">
      <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
        {{ label }}
      </p>

      <div class="flex items-center gap-2">
        <!-- Skeleton -->
        <span v-if="loading"
          class="inline-block w-16 h-7 rounded-md bg-gradient-to-r
                 from-[#e8edf2] via-[#f4f6f9] to-[#e8edf2]
                 bg-[length:200%_100%] animate-shimmer" />
        <span v-else class="text-[26px] font-bold text-[#081F33] leading-none">
          {{ formatted }}
        </span>
      </div>

      <p v-if="sub" class="text-xs text-gray-400 mt-1">{{ sub }}</p>

      <!-- Barra -->
      <div v-if="progress != null"
        class="mt-3 h-1 bg-[#f0f0f0] rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-1000"
          :style="{ width: progress + '%', background: color }"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed }                                    from 'vue'
import { Users, BookOpen, Layers, FileText, BarChart2 } from 'lucide-vue-next'

const ICONS = { users: Users, 'book-open': BookOpen, layers: Layers,
                'file-text': FileText, bar: BarChart2 }

const props = defineProps({
  icon:     { type: String,          default: 'bar' },
  label:    { type: String,          required: true },
  value:    { type: [Number,String], default: 0 },
  sub:      { type: String,          default: '' },
  progress: { type: Number,          default: null },
  color:    { type: String,          default: '#D28B45' },
  loading:  { type: Boolean,         default: false },
})

const iconComponent = computed(() => ICONS[props.icon] ?? BarChart2)
const formatted     = computed(() =>
  typeof props.value === 'number'
    ? props.value.toLocaleString('es-BO')
    : (props.value ?? '—')
)
const accentStyle = computed(() => ({
  borderColor: props.color + '30',
  background:  `linear-gradient(135deg, ${props.color}0f 0%, #fff 60%)`,
}))
</script>

<style>
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.animate-shimmer { animation: shimmer 1.4s infinite; }
</style>