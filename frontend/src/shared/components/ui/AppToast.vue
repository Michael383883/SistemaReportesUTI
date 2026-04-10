<template>
  <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
    <transition-group name="toast">
      <div
        v-for="n in notifications"
        :key="n.id"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-[12px] font-medium shadow-sm pointer-events-auto min-w-[260px] max-w-[360px]"
        :class="{
          'bg-white border border-green-200 text-green-800': n.type === 'success',
          'bg-white border border-red-200   text-red-700':   n.type === 'error',
          'bg-white border border-blue-200  text-blue-800':  n.type === 'info',
        }"
      >
        <span class="shrink-0">
          <CheckCircle v-if="n.type === 'success'" class="w-4 h-4 text-green-500" />
          <XCircle     v-else-if="n.type === 'error'"   class="w-4 h-4 text-red-500" />
          <Info        v-else                            class="w-4 h-4 text-blue-500" />
        </span>
        <span class="flex-1">{{ n.message }}</span>
        <button @click="remove(n.id)" class="ml-1 opacity-50 hover:opacity-100 transition-opacity">
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { CheckCircle, XCircle, Info, X } from 'lucide-vue-next'
import { useNotify } from '@/shared/composables/useNotify'

const { notifications, remove } = useNotify()
</script>

<style scoped>
.toast-enter-active { transition: all 0.25s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(20px); }
.toast-leave-to     { opacity: 0; transform: translateX(20px); }
</style>
