<!-- src/modules/secretaria_talleres/components/DashboardCards.vue -->
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
        class="absolute -top-4 -right-4 w-24 h-24 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"
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

          <!-- Badge de categoría -->
          <span
            class="text-xs font-semibold px-2 py-1 rounded-full"
            :style="{ backgroundColor: card.color + '15', color: card.color }"
          >
            {{ card.categoria }}
          </span>
        </div>

        <div>
          <template v-if="loading">
            <div class="h-8 bg-slate-200 rounded animate-pulse w-20 mb-1" />
          </template>
          <template v-else>
            <div class="text-3xl font-bold text-slate-800 mb-0.5">
              {{ typeof card.value === 'number' ? card.value.toLocaleString() : card.value }}
            </div>
          </template>
          <p class="text-sm text-slate-500 font-medium">{{ card.label }}</p>
        </div>

        <!-- Barra de progreso -->
        <div class="mt-3 h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700"
            :style="{ width: card.percentage + '%', backgroundColor: card.color }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, GraduationCap, BookOpen, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  kpis: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})

defineEmits(['cardClick'])

const totalEstudiantes = computed(() =>
  props.kpis.estudiantes?.total ?? 0
)

const totalDocentes = computed(() =>
  props.kpis.docentes?.total ?? 0
)

const totalTalleres = computed(() =>
  props.kpis.talleres?.total ?? 0
)

const totalAlertas = computed(() =>
  props.kpis.alertas?.length ?? 0
)

const cards = computed(() => [
  {
    id: 'estudiantes',
    label: 'Estudiantes Inscritos',
    value: totalEstudiantes.value,
    icon: GraduationCap,
    color: '#6366f1',
    categoria: 'Estudiantes',
    percentage: Math.min((totalEstudiantes.value / 600) * 100, 100)
  },
  {
    id: 'docentes',
    label: 'Docentes Activos',
    value: totalDocentes.value,
    icon: Users,
    color: '#0d9488',
    categoria: 'Docentes',
    percentage: Math.min((totalDocentes.value / 40) * 100, 100)
  },
  {
    id: 'talleres',
    label: 'Talleres Activos',
    value: totalTalleres.value,
    icon: BookOpen,
    color: '#f59e0b',
    categoria: 'Talleres',
    percentage: Math.min((totalTalleres.value / 30) * 100, 100)
  },
  {
    id: 'alertas',
    label: 'Alertas Activas',
    value: totalAlertas.value,
    icon: AlertCircle,
    color: '#ef4444',
    categoria: 'Alertas',
    percentage: totalAlertas.value > 0 ? 100 : 0
  }
])
</script>