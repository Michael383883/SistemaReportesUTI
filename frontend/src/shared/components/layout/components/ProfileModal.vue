<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150"
      leave-active-class="transition-opacity duration-100"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/40 backdrop-blur-[2px] flex items-center justify-center z-[1000] p-4"
        @click.self="$emit('update:modelValue', false)"
      >
        <div class="bg-white rounded-2xl w-[820px] max-w-full max-h-[85vh] overflow-y-auto shadow-[0_24px_70px_rgba(15,23,42,0.18)] border border-gray-100">
          <!-- Header del modal -->
          <div class="flex items-center justify-between py-5 px-6 bg-[#0b1437] sticky top-0 z-10">
            <h2 class="m-0 text-[15px] font-bold text-white tracking-tight">Perfil</h2>
            <button
              @click="$emit('update:modelValue', false)"
              class="border-none bg-transparent cursor-pointer w-7 h-7 rounded-md flex items-center justify-center text-gray-300 transition-colors duration-150 hover:bg-white/10 hover:text-white"
              aria-label="Cerrar"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Contenido del modal -->
          <div class="p-6">
            <AdminProfileView />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { X } from 'lucide-vue-next'
import AdminProfileView from '@/modules/users/views/AdminProfileView.vue'

defineProps({
  modelValue: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>