<!-- src/modules/secretaria_talleres/components/DashboardCards.vue -->
<template>
  <div :class="vertical ? 'flex flex-col gap-4' : 'grid grid-cols-2 lg:grid-cols-4 gap-4'">
    <div
      v-for="(card, idx) in cards"
      :key="card.id"
      class="relative bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:-translate-y-0.5 hover:border-slate-300 transition-all duration-300 group cursor-pointer overflow-hidden"
      :style="{ transitionDelay: loading ? '0ms' : `${idx * 40}ms` }"
      @click="$emit('cardClick', card.id)"
    >
      <!-- Fondo decorativo -->
      <div
        class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-[0.07] group-hover:opacity-[0.14] group-hover:scale-110 transition-all duration-500"
        :style="{ backgroundColor: card.bgDecor }"
      />
      <!-- Barra de acento superior -->
      <div
        class="absolute top-0 left-0 right-0 h-1 scale-x-0 group-hover:scale-x-100 origin-left transition-transform duration-300"
        :style="{ backgroundColor: card.bgDecor }"
      />

      <div class="relative">
        <div class="flex items-start justify-between mb-4">
          <div
            class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-105"
            :class="card.iconBoxClass"
          >
            <component :is="card.icon" class="w-6 h-6" />
          </div>

          <span
            class="text-xs font-semibold px-2 py-1 rounded-full"
            :class="card.badgeClass"
          >
            {{ card.categoria }}
          </span>
        </div>

        <div>
          <template v-if="loading">
            <div class="h-8 bg-slate-200 rounded-lg animate-pulse w-20 mb-2" />
            <div class="h-3.5 bg-slate-100 rounded animate-pulse w-28" />
          </template>
          <template v-else>
            <div class="flex items-baseline gap-1.5 mb-0.5">
              <span class="text-3xl font-bold text-slate-800 tabular-nums">
                {{ typeof card.value === 'number' ? card.value.toLocaleString('es-BO') : card.value }}
              </span>
            </div>
            <p class="text-sm text-slate-500 font-medium">{{ card.label }}</p>
          </template>
        </div>

        <!-- Barra de progreso -->
        <div class="mt-4 h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-700 ease-out"
            :class="[card.barClass, loading && 'w-0']"
            :style="{ width: loading ? '0%' : card.percentage + '%' }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, GraduationCap, BookOpen } from 'lucide-vue-next'

const props = defineProps({
  kpis: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  vertical: { type: Boolean, default: false }
})

defineEmits(['cardClick'])

const totalEstudiantes = computed(() => props.kpis.estudiantes?.total ?? 0)
const totalDocentes    = computed(() => props.kpis.docentes?.total ?? 0)
const totalTalleres    = computed(() => props.kpis.talleres?.total ?? 0)

const cards = computed(() => [
  {
    id: 'estudiantes',
    label: 'Estudiantes Inscritos',
    value: totalEstudiantes.value,
    icon: GraduationCap,
    bgDecor:      '#4f46e5',
    iconBoxClass: 'bg-indigo-50 text-indigo-700',
    badgeClass:   'bg-indigo-50 text-indigo-800',
    barClass:     'bg-gradient-to-r from-indigo-500 to-indigo-600',
    categoria:    'Estudiantes',
    percentage: Math.min((totalEstudiantes.value / 600) * 100, 100)
  },
  {
    id: 'docentes',
    label: 'Docentes Activos',
    value: totalDocentes.value,
    icon: Users,
    bgDecor:      '#0d9488',
    iconBoxClass: 'bg-teal-50 text-teal-700',
    badgeClass:   'bg-teal-50 text-teal-800',
    barClass:     'bg-gradient-to-r from-teal-500 to-teal-600',
    categoria:    'Docentes',
    percentage: Math.min((totalDocentes.value / 40) * 100, 100)
  },
  {
    id: 'talleres',
    label: 'Talleres Activos',
    value: totalTalleres.value,
    icon: BookOpen,
    bgDecor:      '#f59e0b',
    iconBoxClass: 'bg-amber-50 text-amber-700',
    badgeClass:   'bg-amber-50 text-amber-800',
    barClass:     'bg-gradient-to-r from-amber-400 to-amber-500',
    categoria:    'Talleres',
    percentage: Math.min((totalTalleres.value / 30) * 100, 100)
  }
])
</script>