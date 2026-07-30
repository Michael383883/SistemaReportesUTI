<template>
  <BaseModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" width="320px">
    <div class="p-7 text-center bg-white dark:bg-[rgb(8,31,51)] rounded-xl overflow-hidden transition-colors">
      <div class="w-11 h-11 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mx-auto mb-4">
        <Trash2 class="w-5 h-5 text-red-500 dark:text-red-400" />
      </div>

      <h2 class="text-[15px] font-bold text-navy dark:text-white mb-2">
        ¿Eliminar usuario?
      </h2>

      <p class="text-[12px] text-gray-600 dark:text-gray-300 mb-1 leading-relaxed">
        Esta acción es irreversible.
      </p>

      <p class="text-[13px] font-medium text-gray-600 dark:text-gray-200 mb-6">
        {{ userName }}
      </p>

      <div class="flex gap-2 justify-center">
        <button
          @click="$emit('update:modelValue', false)"
          class="px-4 py-2 text-[12px] text-gray-500 dark:text-gray-300 border border-gray-400 dark:border-gray-500 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
        >
          Cancelar
        </button>
        <button
          @click="$emit('confirm')"
          :disabled="loading"
          class="px-5 py-2 bg-red-600 text-white text-[13px] rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60"
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