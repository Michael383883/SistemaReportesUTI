<template>
  <button
    @click="$emit('click')"
    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700
           hover:bg-slate-50 transition-colors text-left"
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         class="text-slate-400 shrink-0">
      <template v-if="icon === 'ver'">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>
      </template>
      <template v-else>
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
      </template>
    </svg>
    <div>
      <div class="font-medium leading-tight">{{ titulo }}</div>
      <div class="text-xs text-slate-400 mt-0.5">{{ subtitulo }}</div>
    </div>
  </button>
</template>

<script setup>
defineProps({
  icon: { type: String, default: 'ver' }, // 'ver' | 'descargar'
  titulo: String,
  subtitulo: String,
})
defineEmits(['click'])
</script>