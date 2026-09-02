<template>
  <div class="rounded-2xl border border-gray-300 bg-white shadow-sm overflow-hidden">

    <!-- Loading -->
    <div
      v-if="loading"
      class="py-10 text-center text-slate-400 text-sm"
    >
      Cargando resoluciones...
    </div>

    <!-- Vacío -->
    <div
      v-else-if="filas.length === 0"
      class="flex flex-col items-center justify-center py-12"
    >
      <div
        class="
        w-16 h-16
        rounded-2xl
        bg-blue-50
        border border-gray-300
        flex items-center justify-center
        text-slate-400
        mb-3
        "
      >
        <svg
          width="28"
          height="28"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
        >
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
      </div>

      <p class="text-sm font-semibold text-slate-700">
        No se encontraron resoluciones
      </p>

      <p class="text-xs text-slate-400 mt-1">
        Las resoluciones registradas aparecerán aquí.
      </p>
    </div>

    <!-- Tabla -->
    <div v-else class="overflow-x-auto">

      <table class="w-full text-sm">

        <thead>
          <tr class="bg-slate-800">
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              ID
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Resolución
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Año/Periodo
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Descripción
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Archivo
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Fecha de creación
            </th>

            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-white">
              Acciones
            </th>

            <th class="px-2 py-2 text-left">
              <button
                :disabled="loading"
                @click="$emit('recargar')"
                title="Recargar"
                class="
                  inline-flex items-center justify-center
                  w-8 h-8
                  rounded-lg
                  bg-amber-500
                  text-slate-900
                  hover:bg-amber-400
                  disabled:opacity-50
                  transition-colors
                "
              >
                <svg
                  :class="{ 'animate-spin': loading }"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
                  <polyline points="21 3 21 9 15 9"/>
                </svg>
              </button>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(fila, index) in filas"
            :key="fila.idResolucion"
            :class="[
              index % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-sky-100 dark:bg-sky-500/15'
            ]"
          >
            <td class="px-4 py-3 text-slate-700">
              {{ index + 1 }}
            </td>

            <td class="px-4 py-3">
              <span
                class="
                inline-flex
                px-2 py-0.5
                rounded
                text-[12px]
                font-bold
                bg-indigo-50
                text-indigo-800
                "
              >
                {{ fila.nroResolucion }}
              </span>
            </td>

            <td class="px-4 py-3">
              <span
                class="inline-flex px-2 py-0.5 rounded text-[12px] font-bold"
                :class="claseBadgePeriodo(fila.periodo)"
              >
                {{ fila.anio }}/{{ fila.periodo }}
              </span>
            </td>

            <td
              class="px-4 py-3 text-slate-700 max-w-sm truncate"
              :title="fila.descripcion"
            >
              {{ fila.descripcion }}
            </td>

            <td class="px-4 py-3">
              <span
                class="
                inline-flex
                px-2 py-0.5
                rounded
                text-[12px]
                font-semibold
                bg-emerald-50
                text-emerald-600
                "
              >
                PDF
              </span>
            </td>

            <td class="px-4 py-3 text-slate-600">
              {{ formatearFecha(fila.fechaSubida) }}
            </td>

            <td class="px-4 py-3">
              <div class="flex items-center gap-2">

                <!-- Ver -->
                <button
                  @click="verPdf(fila.idResolucion)"
                  title="Ver"
                  class="
                    inline-flex items-center justify-center
                    w-8 h-8
                    rounded-lg
                    bg-slate-100
                    text-slate-600
                    hover:bg-slate-200
                    transition-colors
                  "
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <!-- Descargar -->
                <button
                  @click="descargarPdf(fila.idResolucion)"
                  title="Descargar"
                  class="
                    inline-flex items-center justify-center
                    w-8 h-8
                    rounded-lg
                    bg-amber-500
                    text-slate-900
                    hover:bg-amber-400
                    transition-colors
                  "
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                  </svg>
                </button>

                <!-- Editar -->
                <button
                  title="Editar"
                  @click="$emit('editar', fila)"
                  class="
                    inline-flex items-center justify-center
                    w-8 h-8
                    rounded-lg
                    bg-blue-50
                    text-indigo-700
                    hover:bg-blue-100
                    transition-colors
                  "
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/>
                  </svg>
                </button>

                <!-- Borrar -->
                <button
                  title="Borrar"
                  @click="$emit('borrar', fila)"
                  class="
                    inline-flex items-center justify-center
                    w-8 h-8
                    rounded-lg
                    bg-red-50
                    text-red-600
                    hover:bg-red-100
                    transition-colors
                  "
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                  </svg>
                </button>

              </div>
            </td>
          </tr>
        </tbody>

      </table>

    </div>

  </div>
</template>

<script setup>
defineProps({
  filas: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  verPdf: { type: Function, required: true },
  descargarPdf: { type: Function, required: true },
  formatearFecha: { type: Function, required: true },
  claseBadgePeriodo: { type: Function, required: true },
})
defineEmits(['borrar', 'recargar', 'editar'])
</script>