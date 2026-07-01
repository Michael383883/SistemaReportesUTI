<template>
  <div class="flex items-center gap-3">
    <div class="flex flex-col gap-1">
      <label :for="`${idPrefix}-anio`" class="text-[11px] text-slate-400">Año</label>
      <input
        :id="`${idPrefix}-anio`"
        type="text"
        inputmode="numeric"
        placeholder="2024"
        :value="anio"
        @input="$emit('update:anio', $event.target.value)"
        class="w-24 bg-slate-900 border border-slate-700 rounded-md px-2.5 py-1.5
               text-[13px] font-mono text-slate-200 outline-none
               focus:border-indigo-400/60 focus:ring-1 focus:ring-indigo-400/40"
      />
    </div>

    <div class="flex flex-col gap-1">
      <label :for="`${idPrefix}-periodo`" class="text-[11px] text-slate-400">Periodo</label>
      <select
        :id="`${idPrefix}-periodo`"
        :value="periodo"
        @change="$emit('update:periodo', $event.target.value)"
        class="bg-slate-900 border border-slate-700 rounded-md px-2.5 py-1.5
               text-[13px] font-mono text-slate-200 outline-none
               focus:border-indigo-400/60 focus:ring-1 focus:ring-indigo-400/40"
      >
        <option value="1">1</option>
        <option value="2">2</option>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({
  anio: { type: String, required: true },
  periodo: { type: String, required: true },
  idPrefix: { type: String, default: 'semestre' },
})
defineEmits(['update:anio', 'update:periodo'])
</script>