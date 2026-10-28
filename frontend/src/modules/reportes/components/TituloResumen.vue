<template>
  <div class="flex flex-wrap items-center gap-1.5 mb-3">
    <span class="text-[12px] font-semibold text-gray-600 dark:text-slate-400 mr-1">Título (abrev.):</span>

    <button v-for="it in items" :key="it.titulo" @click="$emit('select', it.titulo)"
      class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-0.5 rounded-full border text-[12px] font-medium transition-colors"
      :class="seleccionado === it.titulo
        ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-500/15 dark:border-amber-500/60 dark:text-amber-300'
        : 'bg-white border-gray-300 text-gray-800 hover:border-amber-400 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:border-amber-500/60'">
      {{ it.titulo }}
      <span class="min-w-[22px] text-center rounded-full px-1.5 py-px text-[11px] font-bold text-white"
        :class="seleccionado === it.titulo ? 'bg-amber-500' : 'bg-slate-800 dark:bg-slate-600'">{{ it.docentes }}</span>
    </button>

    <button v-if="sinTitulo" @click="$emit('select', '__SIN__')"
      class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-0.5 rounded-full border text-[12px] font-medium transition-colors"
      :class="seleccionado === '__SIN__'
        ? 'bg-red-100 border-red-400 text-red-700 dark:bg-red-500/25 dark:border-red-400/70 dark:text-red-200'
        : 'bg-red-50 border-red-200 text-red-600 hover:border-red-400 dark:bg-red-500/10 dark:border-red-500/40 dark:text-red-300 dark:hover:border-red-400/70'">
      Sin título registrado
      <span class="min-w-[22px] text-center rounded-full px-1.5 py-px text-[11px] font-bold text-white bg-red-500">{{ sinTitulo }}</span>
    </button>

    <button v-if="seleccionado" @click="$emit('select', null)"
      class="text-[12px] text-blue-800 dark:text-blue-400 font-semibold hover:underline ml-1">✕ Quitar filtro</button>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
  seleccionado: { type: String, default: null },
  sinTitulo: { type: Number, default: 0 },
})
defineEmits(['select'])
</script>