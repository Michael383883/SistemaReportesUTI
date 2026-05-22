<template>
  <div>

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-[22px] font-semibold text-navy">
        Dashboard
      </h1>

      <p class="text-[13px] text-gray-400 mt-1">
        Resumen general del sistema · Gestión I-2026
      </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <StatCard
        label="Docentes registrados"
        :value="124"
        accent="gold"
      />

      <StatCard
        label="Documentos cargados"
        :value="38"
        accent="navy"
      />

      <StatCard
        label="Pendientes"
        :value="5"
        accent="red"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">

      <!-- Header tabla -->
      <div
        class="px-5 py-4 border-b border-gray-50 flex items-center justify-between"
      >
        <h2 class="text-[14px] font-semibold text-navy">
          Actividad reciente
        </h2>

        <span class="text-[12px] text-gray-400">
          Últimos 7 días
        </span>
      </div>

      <!-- Tabla -->
      <table class="w-full text-[14px]">

        <thead>
          <tr class="border-b border-gray-50">

            <th
              class="px-5 py-3 text-left text-[12px] text-gray-400 font-semibold"
            >
              Documento
            </th>

            <th
              class="py-3 text-left text-[12px] text-gray-400 font-semibold"
            >
              Usuario
            </th>

            <th
              class="py-3 text-left text-[12px] text-gray-400 font-semibold"
            >
              Fecha
            </th>

            <th
              class="py-3 text-left text-[12px] text-gray-400 font-semibold"
            >
              Estado
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="row in recent"
            :key="row.doc"
            class="
              border-b border-gray-50
              last:border-0
              hover:bg-gray-50/50
              transition-colors
            "
          >

            <td class="px-5 py-4 text-gray-700">
              {{ row.doc }}
            </td>

            <td class="py-4 text-gray-600">
              {{ row.user }}
            </td>

            <td class="py-4 text-gray-500">
              {{ row.date }}
            </td>

            <td class="py-4">
              <span
                class="
                  px-2.5 py-1
                  rounded-full
                  text-[12px]
                  font-medium
                "
                :class="
                  row.status === 'Procesado'
                    ? 'bg-green-50 text-green-700'
                    : 'bg-amber-50 text-amber-700'
                "
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
  {
    doc: 'Resolución 045/2026',
    user: 'Ana Flores',
    date: '08 abr 2026',
    status: 'Procesado',
  },

  {
    doc: 'Generación de Reporte 044/2026',
    user: 'Luis Quispe',
    date: '07 abr 2026',
    status: 'Pendiente',
  },

  {
    doc: 'Resolución 043/2026',
    user: 'Ana Flores',
    date: '06 abr 2026',
    status: 'Procesado',
  },

  {
    doc: 'Resolución 042/2026',
    user: 'Carlos M.',
    date: '05 abr 2026',
    status: 'Procesado',
  },
]
</script>