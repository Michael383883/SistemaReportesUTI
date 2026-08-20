<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4" @mousedown.self="$emit('cerrar')">
    <div class="w-full max-w-xs bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden">

      <!-- Header compacto -->
      <div class="flex items-center justify-between px-3.5 py-2 bg-slate-900">
        <span class="text-[13px] font-bold text-white">
          {{ esEdicion ? 'Editar categoría' : 'Nueva categoría' }}
        </span>
        <button type="button" @click="$emit('cerrar')" class="text-slate-300 hover:text-white">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="p-3.5 space-y-2">
        <input
          ref="inputRef"
          v-model="nombre"
          type="text"
          maxlength="60"
          placeholder="Nombre de la categoría"
          class="w-full px-3 py-2 text-[13px] bg-gray-50 border border-gray-200 rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent focus:bg-white transition-colors"
          @keydown.enter.prevent="onGuardar"
        />
        <p v-if="errorDuplicado" class="text-[11px] text-red-500">
          Ya existe una categoría con ese nombre.
        </p>
        <p v-else-if="esEdicion" class="text-[11px] text-gray-400">
          Se renombrará en todas las materias que la usan.
        </p>
      </div>

      <div class="flex items-center justify-end gap-2 px-3.5 py-2.5 border-t border-gray-100 bg-gray-50">
        <button
          type="button"
          @click="$emit('cerrar')"
          class="px-3 py-1.5 rounded-lg text-[12px] font-semibold text-slate-600 hover:bg-gray-100 transition-colors"
        >
          Cancelar
        </button>
        <button
          type="button"
          :disabled="!puedeGuardar || guardando"
          @click="onGuardar"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-[12px] font-bold
                 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-100
                 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="guardando" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ esEdicion ? 'Guardar' : 'Agregar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'

const props = defineProps({
  // 'crear' | 'editar'
  mode: { type: String, required: true },
  // Requerido solo en modo 'editar': { id, nombre }
  categoria: { type: Object, default: null },
  // Lista de nombres ya existentes (para bloquear duplicados en el cliente)
  categoriasExistentes: { type: Array, default: () => [] },
  // Controlado por el padre mientras espera la respuesta del backend
  guardando: { type: Boolean, default: false },
})

const emit = defineEmits(['guardar', 'cerrar'])

const esEdicion = computed(() => props.mode === 'editar')

const nombre = ref(esEdicion.value ? (props.categoria?.nombre ?? '') : '')
const inputRef = ref(null)

onMounted(() => {
  nextTick(() => inputRef.value?.focus())
})

const errorDuplicado = computed(() => {
  const actual = nombre.value.trim().toLowerCase()
  if (!actual) return false
  const nombreOriginal = (props.categoria?.nombre ?? '').trim().toLowerCase()
  return props.categoriasExistentes.some((c) => {
    const n = (c.nombre ?? c).toString().trim().toLowerCase()
    return n === actual && n !== nombreOriginal
  })
})

const puedeGuardar = computed(() => nombre.value.trim().length > 0 && !errorDuplicado.value)

function onGuardar() {
  if (!puedeGuardar.value) return
  emit('guardar', {
    id: props.categoria?.id ?? null,
    nombre: nombre.value.trim(),
  })
}
</script>