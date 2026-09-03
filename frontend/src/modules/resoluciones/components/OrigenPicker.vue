<!-- components/OrigenPicker.vue -->
<template>
  <div class="space-y-3">
    <!-- Switch Resolución / Documento -->
    <div class="flex items-center justify-between gap-3">
      <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
          :class="tipo === 'resolucion' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'"
          :disabled="bloqueadoCambioTipo && tipo !== 'resolucion'"
          @click="$emit('cambiar-tipo', 'resolucion')"
        >
          Resolución
        </button>
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
          :class="tipo === 'documento' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'"
          :disabled="bloqueadoCambioTipo && tipo !== 'documento'"
          @click="$emit('cambiar-tipo', 'documento')"
        >
          Documento
        </button>
      </div>

      <span v-if="bloqueadoCambioTipo" class="text-[0.7rem] text-amber-600 italic">
        Quita las materias marcadas para cambiar el tipo de origen
      </span>
    </div>

    <!-- Picker de resolución -->
    <ResolucionSearchPicker
      v-if="tipo === 'resolucion'"
      :filas="filas"
      :loading="loading"
      :error="error"
      :busqueda="busqueda"
      :resolucion-activa="origenActivo"
      :bloqueado="bloqueado"
      @buscar="$emit('buscar', $event)"
      @limpiar-busqueda="$emit('limpiar-busqueda')"
      @select="$emit('select', $event)"
      @limpiar="$emit('limpiar')"
    />

    <!-- Picker de documento -->
    <DocumentoSearchPicker
      v-else
      :filas="filas"
      :loading="loading"
      :error="error"
      :busqueda="busqueda"
      :documento-activa="origenActivo"
      :bloqueado="bloqueado"
      @buscar="$emit('buscar', $event)"
      @limpiar-busqueda="$emit('limpiar-busqueda')"
      @select="$emit('select', $event)"
      @limpiar="$emit('limpiar')"
    />
  </div>
</template>

<script setup>
import ResolucionSearchPicker from '../../resoluciones/components/ResolucionSearchPicker.vue' // ajustá la ruta real
import DocumentoSearchPicker from './DocumentoSearchPicker.vue' // ajustá la ruta real

defineProps({
  tipo: { type: String, required: true }, // 'resolucion' | 'documento'
  bloqueadoCambioTipo: { type: Boolean, default: false },

  // Se re-emiten tal cual al picker que corresponda. La vista padre debe
  // pasar la lista/estado del listado ACTIVO según "tipo" (resolución o
  // documento), ya que cada uno viene de un composable de listado distinto.
  filas: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  busqueda: { type: String, default: '' },
  origenActivo: { type: Object, default: null },
  bloqueado: { type: Boolean, default: false },
})

defineEmits(['cambiar-tipo', 'buscar', 'limpiar-busqueda', 'select', 'limpiar'])
</script>