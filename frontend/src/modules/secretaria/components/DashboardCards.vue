<!-- src/modules/secretaria/components/DashboardCards.vue -->
<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div
      v-for="card in cards"
      :key="card.label"
      class="relative bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:border-slate-300 transition-all duration-300 group cursor-pointer overflow-hidden"
      @click="$emit('cardClick', card.id)"
    >
      <!-- Fondo decorativo -->
      <div 
        class="absolute -top-4 -right-4 w-20 h-20 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"
        :style="{ backgroundColor: card.color }"
      />
      
      <div class="relative">
        <div class="flex items-start justify-between mb-3">
          <div 
            class="w-12 h-12 rounded-xl flex items-center justify-center"
            :style="{ backgroundColor: card.color + '20', color: card.color }"
          >
            <component :is="card.icon" class="w-6 h-6" />
          </div>
          <span 
            v-if="card.trend && !loading" 
            :class="[
              'text-xs font-semibold px-2 py-1 rounded-full flex items-center gap-1',
              card.trend > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
            ]"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path 
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                :d="card.trend > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6'"
              />
            </svg>
            {{ Math.abs(card.trend) }}%
          </span>
        </div>
        
        <div>
          <div class="text-3xl font-bold text-slate-800 mb-1">
            <template v-if="loading">
              <div class="h-8 bg-slate-200 rounded animate-pulse w-20" />
            </template>
            <template v-else>
              {{ typeof card.value === 'number' ? card.value.toLocaleString() : card.value }}
            </template>
          </div>
          <p class="text-sm text-slate-500 font-medium">{{ card.label }}</p>
        </div>
        
        <!-- Indicador visual pequeño -->
        <div class="mt-3 h-1 bg-slate-100 rounded-full overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :style="{ width: card.percentage + '%', backgroundColor: card.color }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, UserCheck, Clock, AlertCircle, TrendingUp } from 'lucide-vue-next'

const props = defineProps({
  kpis: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

defineEmits(['cardClick'])

const cards = computed(() => [
  {
    id: 'total',
    label: 'Total Docentes',
    value: props.kpis.totalDocentes ?? 0,
    icon: Users,
    color: '#3b82f6',
    trend: 5.2,
    percentage: 85
  },
  {
    id: 'activos',
    label: 'Docentes Activos',
    value: props.kpis.docentesActivos ?? 0,
    icon: UserCheck,
    color: '#10b981',
    trend: 2.1,
    percentage: 70
  },
  {
    id: 'horas',
    label: 'Horas Promedio',
    value: props.kpis.horasPromedio ? props.kpis.horasPromedio + 'h' : '0h',
    icon: Clock,
    color: '#8b5cf6',
    trend: -1.5,
    percentage: 60
  },
  {
    id: 'alertas',
    label: 'Alertas Activas',
    value: props.kpis.alertas?.length ?? 0,
    icon: AlertCircle,
    color: '#f59e0b',
    trend: null,
    percentage: props.kpis.alertas?.length > 0 ? 100 : 0
  }
])
</script>