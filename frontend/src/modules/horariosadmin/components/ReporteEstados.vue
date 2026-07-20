<template>
  <div v-if="error" class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
    ⚠️ {{ error }}
  </div>

  <div v-if="loading" class="flex flex-col items-center gap-3 py-20 text-slate-500">
    <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-600 rounded-full animate-spin" />
    <span>{{ loadingLabel }}</span>
  </div>

  <div v-else-if="!loading && total === 0 && !error" class="flex flex-col items-center gap-3 py-24 text-slate-400">
    <template v-if="terminoBuscado">
      <span class="text-6xl">🔍</span>
      <p>No se encontró ningún docente con <strong class="text-slate-500">"{{ terminoBuscado }}"</strong>.</p>
      <p class="text-xs text-slate-300">Verifica el código o los apellidos e intenta de nuevo.</p>
    </template>
    <template v-else>
      <span class="text-6xl">📋</span>
      <p>{{ emptyLabel }}</p>
    </template>
  </div>
</template>

<script setup>
defineProps({
  error: String,
  loading: Boolean,
  total: Number,
  terminoBuscado: String,
  loadingLabel: { type: String, default: 'Cargando...' },
  emptyLabel: { type: String, default: 'Busca un docente o carga todos para ver el reporte.' },
})
</script>