<template>
  <div>
    <div class="mb-5">
      <h1 class="text-[16px] font-medium text-navy">Dashboard</h1>
      <p class="text-[11px] text-gray-400 mt-0.5">
        Resumen general del sistema · Gestión I-2026
      </p>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-5">
      <StatCard label="Docentes registrados" :value="124" accent="gold" />
      <StatCard label="Documentos cargados"  :value="38"  accent="navy" />
      <StatCard label="Pendientes"           :value="5"   accent="red"  />
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-50 flex items-center justify-between">
        <h2 class="text-[12px] font-medium text-navy">Actividad reciente</h2>
        <span class="text-[10px] text-gray-400">Últimos 7 días</span>
      </div>
      <table class="w-full text-[11px]">
        <thead>
          <tr class="border-b border-gray-50">
            <th class="px-4 py-2.5 text-left text-[10px] text-gray-400 font-medium">Documento</th>
            <th class="py-2.5 text-left text-[10px] text-gray-400 font-medium">Usuario</th>
            <th class="py-2.5 text-left text-[10px] text-gray-400 font-medium">Fecha</th>
            <th class="py-2.5 text-left text-[10px] text-gray-400 font-medium">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in recent"
            :key="row.doc"
            class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-4 py-2.5 text-gray-700">{{ row.doc }}</td>
            <td class="py-2.5 text-gray-500">{{ row.user }}</td>
            <td class="py-2.5 text-gray-400">{{ row.date }}</td>
            <td class="py-2.5">
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-medium"
                :class="row.status === 'Procesado'
                  ? 'bg-green-50 text-green-700'
                  : 'bg-amber-50 text-amber-700'"
              >
                {{ row.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import StatCard from '@/shared/components/ui/StatCard.vue'

const recent = [
  { doc: 'Resolución 045/2026', user: 'Ana Flores',   date: '08 abr 2026', status: 'Procesado' },
  { doc: 'Generacion de Reporte 044/2026', user: 'Luis Quispe',  date: '07 abr 2026', status: 'Pendiente'  },
  { doc: 'Resolución 043/2026', user: 'Ana Flores',   date: '06 abr 2026', status: 'Procesado' },
  { doc: 'Resolución 042/2026', user: 'Carlos M.',    date: '05 abr 2026', status: 'Procesado' },
]
</script>
