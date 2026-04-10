<template>
  <BaseModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" width="320px">
    <div class="p-7 text-center">
      <div class="w-11 h-11 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
        <Trash2 class="w-5 h-5 text-red-500" />
      </div>
      <h2 class="text-[14px] font-medium text-navy mb-2">¿Eliminar usuario?</h2>
      <p class="text-[11px] text-gray-400 mb-1 leading-relaxed">
        Esta acción es irreversible.
      </p>
      <p class="text-[12px] font-medium text-gray-600 mb-6">{{ userName }}</p>
      <div class="flex gap-2 justify-center">
        <button
          @click="$emit('update:modelValue', false)"
          class="px-4 py-2 text-[12px] text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
        >
          Cancelar
        </button>
        <button
          @click="$emit('confirm')"
          :disabled="loading"
          class="px-5 py-2 bg-red-600 text-white text-[12px] rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60"
        >
          <span v-if="!loading">Eliminar</span>
          <span v-else class="flex items-center gap-1.5">
            <Loader2 class="w-3.5 h-3.5 animate-spin" /> Eliminando...
          </span>
        </button>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { Trash2, Loader2 } from 'lucide-vue-next'
import BaseModal from '@/shared/components/ui/BaseModal.vue'

defineProps({
  modelValue: { type: Boolean, required: true },
  userName:   { type: String,  default: '' },
  loading:    { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'confirm'])
</script>
