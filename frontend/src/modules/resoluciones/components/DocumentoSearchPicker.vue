<template>
  <div>
    <!-- Documento ya elegido y bloqueado -->
    <div v-if="documentoActiva" class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200">
      <div class="min-w-0">
        <p class="text-xs font-semibold text-amber-900 truncate m-0">
          {{ documentoActiva.nroResolucion || 'Documento #' + documentoActiva.idDocumento }}
        </p>
        <p class="text-[0.7rem] text-amber-700 m-0 mt-0.5 truncate">
          <span v-if="documentoActiva.descripcion">{{ documentoActiva.detalleGeneral }} · </span>{{ documentoActiva.anio }} / {{ documentoActiva.periodo }}
        </p>
      </div>
      <button
        v-if="!bloqueado"
        type="button"
        class="shrink-0 inline-flex items-center gap-1 text-[0.7rem] text-amber-700 hover:text-amber-900 transition-colors"
        @click="$emit('limpiar')"
      >
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
        Cambiar
      </button>
      <span v-else class="shrink-0 text-[0.65rem] text-amber-500 italic">bloqueado (hay materias marcadas)</span>
    </div>

    <!-- Buscador -->
    <div v-else>
      <div class="relative">
        <input
          type="text"
          :value="busqueda"
          @input="$emit('buscar', $event.target.value)"
          placeholder="Buscar por tipo, descripción, gestión..."
          class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg outline-none
                 focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
        />
        <button
          v-if="busqueda"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
          @click="$emit('limpiar-busqueda')"
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div v-if="loading" class="mt-2 space-y-1.5">
        <div v-for="i in 3" :key="i" class="h-9 rounded-lg bg-slate-50 animate-pulse"/>
      </div>

      <div v-else-if="error" class="mt-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[0.7rem]">
        {{ error }}
      </div>

      <div v-else-if="filas.length === 0" class="mt-2 px-3 py-2 text-[0.7rem] text-slate-400">
        No se encontraron documentos.
      </div>

      <ul v-else class="mt-2 max-h-56 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-100">
        <li
          v-for="d in filas"
          :key="d.idClasificacionDocente ?? d.idDocumento"
          class="px-3 py-2 hover:bg-amber-50 cursor-pointer transition-colors"
          @click="$emit('select', d)"
        >
          <p class="text-xs font-semibold text-slate-800 m-0 truncate">
            {{ d.tipoDocumento || 'Documento #' + d.idDocumento }}
          </p>
          <p class="text-[0.7rem] text-slate-500 m-0 mt-0.5 truncate">
            <span v-if="d.detalleGeneral || d.descripcion">{{ d.detalleGeneral ?? d.descripcion }} · </span>{{ d.gestion }} / {{ d.periodo }}
          </p>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
defineProps({
  filas: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  busqueda: { type: String, default: '' },
  documentoActiva: { type: Object, default: null },
  bloqueado: { type: Boolean, default: false },
})
defineEmits(['buscar', 'limpiar-busqueda', 'select', 'limpiar'])
</script>