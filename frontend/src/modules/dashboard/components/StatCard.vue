<template>
  <div
    class="rounded-xl border overflow-hidden shadow-sm transition-all duration-200
           hover:shadow-md hover:-translate-y-0.5
           bg-white border-slate-200
           dark:bg-slate-800 dark:border-slate-700"
    :style="accentStyle"
  >
    <div class="p-5 flex gap-4 items-start">

      <!-- Icono -->
      <div
        class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
        :style="{ backgroundColor: color + '22' }"
      >
        <component
          :is="iconComponent"
          class="w-5 h-5"
          :style="{ color }"
        />
      </div>

      <!-- Contenido -->
      <div class="flex-1 min-w-0">

        <p
          class="text-[11px] font-bold uppercase tracking-widest
                 text-slate-500 dark:text-slate-400 mb-1"
        >
          {{ label }}
        </p>

        <div class="flex items-center gap-2">

          <!-- Skeleton -->
          <span
            v-if="loading"
            class="inline-block w-20 h-8 rounded-md
                   bg-slate-200 dark:bg-slate-700
                   animate-pulse"
          />

          <span
            v-else
            class="text-4xl font-extrabold leading-none
                   text-slate-900 dark:text-white"
          >
            {{ formatted }}
          </span>

        </div>

        <p
          v-if="sub"
          class="text-sm mt-1 text-slate-500 dark:text-slate-400"
        >
          {{ sub }}
        </p>

        <!-- Barra -->
        <div
          v-if="progress != null"
          class="mt-4 h-1.5 rounded-full overflow-hidden
                 bg-slate-200 dark:bg-slate-700"
        >
          <div
            class="h-full rounded-full transition-all duration-1000"
            :style="{
              width: progress + '%',
              backgroundColor: color
            }"
          />
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Users,
  BookOpen,
  Layers,
  FileText,
  BarChart2
} from 'lucide-vue-next'

const ICONS = {
  users: Users,
  'book-open': BookOpen,
  layers: Layers,
  'file-text': FileText,
  bar: BarChart2
}

const props = defineProps({
  icon: {
    type: String,
    default: 'bar'
  },
  label: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    default: 0
  },
  sub: {
    type: String,
    default: ''
  },
  progress: {
    type: Number,
    default: null
  },
  color: {
    type: String,
    default: '#D28B45'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const iconComponent = computed(() => ICONS[props.icon] ?? BarChart2)

const formatted = computed(() =>
  typeof props.value === 'number'
    ? props.value.toLocaleString('es-BO')
    : props.value ?? '—'
)

const accentStyle = computed(() => ({
  borderTop: `4px solid ${props.color}`
}))
</script>