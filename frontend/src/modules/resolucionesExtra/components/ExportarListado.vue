<template>
  <div class="relative">
    <!-- Overlay para cerrar al hacer clic fuera -->
    <div v-if="abierto" class="fixed inset-0 z-20" @click="abierto = false"></div>

    <button
      type="button"
      @click="abierto = !abierto"
      :disabled="!listado.length"
      class="relative z-30 inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      :class="abierto
        ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
      title="Exportar lo que se ve en la tabla"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      Exportar
      <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': abierto }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <div
      v-if="abierto"
      class="absolute left-0 top-full mt-1 w-60 z-30 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden"
    >
      <p class="px-3 py-2 text-[11px] text-gray-400 bg-gray-50 border-b border-gray-100">
        {{ listado.length }} registro(s) de la tabla
      </p>

      <button
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 transition-colors"
        @click="accion('pdf-ver')"
      >
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-[10px] font-bold flex-shrink-0 bg-red-50 text-red-600">PDF</span>
        <span>
          <span class="block text-sm font-medium text-gray-800">Ver PDF</span>
          <span class="block text-[11px] text-gray-400">Abrir en una pestaña nueva</span>
        </span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 transition-colors"
        @click="accion('pdf-descargar')"
      >
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-[10px] font-bold flex-shrink-0 bg-red-50 text-red-600">PDF</span>
        <span>
          <span class="block text-sm font-medium text-gray-800">Descargar PDF</span>
          <span class="block text-[11px] text-gray-400">Guardar el archivo</span>
        </span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 transition-colors"
        @click="accion('excel')"
      >
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-[10px] font-bold flex-shrink-0 bg-green-50 text-green-700">XLS</span>
        <span>
          <span class="block text-sm font-medium text-gray-800">Descargar Excel</span>
          <span class="block text-[11px] text-gray-400">Archivo .xlsx con filtros</span>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { exportarPDF, exportarExcel } from '../composables/useExportarListado'

const props = defineProps({
  // Lo que se muestra en la tabla (listadoFiltrado)
  listado: { type: Array, default: () => [] },
  // Texto de los filtros activos, ej: "Categoría: TITULARES · Gestión: 2025"
  filtrosTexto: { type: String, default: '' },
})

const abierto = ref(false)

function accion(tipo) {
  abierto.value = false
  const opts = { filtrosTexto: props.filtrosTexto }
  try {
    if (tipo === 'pdf-ver') exportarPDF(props.listado, { ...opts, action: 'open' })
    else if (tipo === 'pdf-descargar') exportarPDF(props.listado, { ...opts, action: 'save' })
    else if (tipo === 'excel') exportarExcel(props.listado, opts)
  } catch (e) {
    console.error('Error exportando listado:', e)
    alert('No se pudo generar el reporte')
  }
}
</script>