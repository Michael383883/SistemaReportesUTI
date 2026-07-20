<template>
  <div
    v-for="card in cards"
    :key="card.id"
    class="relative w-full bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:border-slate-300 transition-all duration-300 group cursor-pointer overflow-hidden"
    @click="$emit('cardClick', card.id)"
  >
    <!-- Ilustración decorativa (sin datos, solo estética) -->
    <component
      :is="card.decorIcon"
      class="hidden md:block absolute -top-4 -right-4 w-24 h-24 opacity-[0.06] text-slate-900 pointer-events-none"
      stroke-width="1"
    />

    <div class="relative flex items-center gap-4">
      <div
        class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
        :style="{ backgroundColor: card.color + '15', color: card.color }"
      >
        <component :is="card.icon" class="w-6 h-6" />
      </div>

      <div class="min-w-0">
        <div class="flex items-center gap-3 flex-wrap">
          <div v-if="loading" class="h-8 bg-slate-100 rounded-lg animate-pulse w-24" />
          <p v-else class="text-3xl font-extrabold text-slate-900 leading-none tabular-nums">
            {{ card.value }}
          </p>
          <span
            v-if="card.badge && !loading"
            class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
            :style="{ backgroundColor: card.color + '12', color: card.color }"
          >
            {{ card.badge }}
          </span>
        </div>
        <p class="text-sm text-slate-500 font-medium mt-1">{{ card.label }}</p>
        <div v-if="card.sub && !loading" class="flex items-center gap-1.5 mt-1.5">
          <component :is="card.subIcon" class="w-3.5 h-3.5 flex-shrink-0" :style="{ color: card.subColor || '#94a3b8' }" />
          <span class="text-xs font-medium" :style="{ color: card.subColor || '#94a3b8' }">{{ card.sub }}</span>
        </div>
        <div v-else-if="loading" class="h-3 bg-slate-100 rounded-full animate-pulse w-28 mt-1.5" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, CheckCircle, GraduationCap } from 'lucide-vue-next'

const props = defineProps({
  kpis: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

defineEmits(['cardClick'])

const cards = computed(() => {
  const total = props.kpis.totalDocentes ?? 0

  return [
    {
      id: 'total',
      label: 'Total Docentes',
      value: total.toLocaleString(),
      icon: Users,
      decorIcon: GraduationCap,
      color: '#2563eb', // blue-600, mismo azul del botón "Actualizar"
      badge: 'Plantel completo',
      subIcon: CheckCircle,
      subColor: '#10b981',
      sub: 'Registrados en el sistema',
      pct: 100,
    },
  ]
})
</script>