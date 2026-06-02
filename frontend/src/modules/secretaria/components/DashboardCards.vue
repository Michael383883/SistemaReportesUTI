<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div
      v-for="card in cards"
      :key="card.id"
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
            class="w-11 h-11 rounded-xl flex items-center justify-center"
            :style="{ backgroundColor: card.color + '18', color: card.color }"
          >
            <component :is="card.icon" class="w-5 h-5" />
          </div>
          <!-- Badge secundario -->
          <span
            v-if="card.badge && !loading"
            class="text-xs font-medium px-2 py-1 rounded-full"
            :style="{ backgroundColor: card.color + '15', color: card.color }"
          >
            {{ card.badge }}
          </span>
        </div>

        <!-- Valor principal -->
        <div class="mt-1 mb-0.5">
          <div v-if="loading" class="h-8 bg-slate-100 rounded animate-pulse w-20 mb-1" />
          <template v-else>
            <p class="text-3xl font-bold text-slate-800 leading-none">
              {{ card.value }}
            </p>
          </template>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-3">{{ card.label }}</p>

        <!-- Subinfo -->
        <div v-if="card.sub && !loading" class="flex items-center gap-1.5">
          <component :is="card.subIcon" class="w-3.5 h-3.5 flex-shrink-0" :style="{ color: card.subColor || '#94a3b8' }" />
          <span class="text-xs" :style="{ color: card.subColor || '#94a3b8' }">{{ card.sub }}</span>
        </div>
        <div v-else-if="loading" class="h-3 bg-slate-100 rounded animate-pulse w-28" />

        <!-- Barra de progreso -->
        <div class="mt-3 h-1 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700"
            :style="{ width: card.pct + '%', backgroundColor: card.color }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, UserCheck, Clock, UserX, AlertTriangle, TrendingUp, CheckCircle } from 'lucide-vue-next'

const props = defineProps({
  kpis: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

defineEmits(['cardClick'])

const cards = computed(() => {
  const total      = props.kpis.totalDocentes     ?? 0
  const activos    = props.kpis.docentesActivos   ?? 0
  const sinCarga   = props.kpis.docentesSinCarga  ?? 0
  const horasProm  = props.kpis.horasPromedio     ?? 0
  const pctActivos = total > 0 ? Math.round((activos / total) * 100) : 0
  const pctSin     = total > 0 ? Math.round((sinCarga / total) * 100) : 0

  return [
    {
      id: 'total',
      label: 'Total Docentes',
      value: total.toLocaleString(),
      icon: Users,
      color: '#3b82f6',
      badge: 'Plantel completo',
      sub: `${activos} con carga activa`,
      subIcon: CheckCircle,
      subColor: '#10b981',
      pct: 100,
    },
    {
      id: 'activos',
      label: 'Con Carga Horaria',
      value: activos.toLocaleString(),
      icon: UserCheck,
      color: '#10b981',
      badge: pctActivos + '% del total',
      sub: `${total - activos} sin asignar`,
      subIcon: AlertTriangle,
      subColor: sinCarga > 0 ? '#f59e0b' : '#94a3b8',
      pct: pctActivos,
    },
    {
      id: 'sinCarga',
      label: 'Sin Carga Asignada',
      value: sinCarga.toLocaleString(),
      icon: UserX,
      color: sinCarga > 0 ? '#ef4444' : '#94a3b8',
      badge: pctSin + '% del plantel',
      sub: sinCarga > 0 ? 'Requieren atención' : 'Todo asignado ✓',
      subIcon: sinCarga > 0 ? AlertTriangle : CheckCircle,
      subColor: sinCarga > 0 ? '#ef4444' : '#10b981',
      pct: pctSin,
    },
    {
      id: 'horas',
      label: 'Horas Promedio',
      value: horasProm + 'h',
      icon: Clock,
      color: '#8b5cf6',
      badge: 'Por docente activo',
      sub: horasProm >= 20 ? 'Carga adecuada' : horasProm > 0 ? 'Carga baja' : 'Sin datos',
      subIcon: TrendingUp,
      subColor: horasProm >= 20 ? '#10b981' : horasProm > 0 ? '#f59e0b' : '#94a3b8',
      pct: Math.min(Math.round((horasProm / 40) * 100), 100),
    },
  ]
})
</script>