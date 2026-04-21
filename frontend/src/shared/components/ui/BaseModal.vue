<template>
  <teleport to="body">
    <transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background: rgba(8,31,51,0.5)"
        @click.self="$emit('update:modelValue', false)"
      >
        <div
          class="bg-white rounded-2xl w-full mx-4 shadow-none"
          :style="{ maxWidth: width }"
        >
          <slot />
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, required: true },
  width:      { type: String,  default: '420px' },
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from,  .modal-leave-to      { opacity: 0; }
</style>
