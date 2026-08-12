<template>
  <div class="flex items-center gap-1.5">
    <select
      :value="mes"
      @change="onMesChange($event.target.value)"
      class="rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900
             text-slate-800 dark:text-slate-200 text-[12px] px-2 py-1.5
             focus:outline-none focus:ring-2 focus:ring-amber-400"
    >
      <option v-for="(nombre, i) in meses" :key="i" :value="pad(i + 1)">{{ nombre }}</option>
    </select>
    <select
      :value="dia"
      @change="onDiaChange($event.target.value)"
      class="rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900
             text-slate-800 dark:text-slate-200 text-[12px] px-2 py-1.5 w-16
             focus:outline-none focus:ring-2 focus:ring-amber-400"
    >
      <option v-for="d in diasDelMes" :key="d" :value="pad(d)">{{ d }}</option>
    </select>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// modelValue en formato 'MM-DD', ej: '02-10'
const props = defineProps({
  modelValue: { type: String, required: true }
})
const emit = defineEmits(['update:modelValue'])

const meses = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
]

const pad = (n) => String(n).padStart(2, '0')

const mes = computed(() => props.modelValue?.split('-')[0] ?? '01')
const dia = computed(() => props.modelValue?.split('-')[1] ?? '01')

// El año es irrelevante en estos rangos (se repiten cada gestión), así
// que usamos el máximo de días razonable por mes solo para la lista
// del selector. 29 días fijos en febrero para cubrir años bisiestos.
const diasDelMes = computed(() => {
  const treintaDias = ['04', '06', '09', '11']
  if (mes.value === '02') return Array.from({ length: 29 }, (_, i) => i + 1)
  if (treintaDias.includes(mes.value)) return Array.from({ length: 30 }, (_, i) => i + 1)
  return Array.from({ length: 31 }, (_, i) => i + 1)
})

function onMesChange(nuevoMes) {
  emit('update:modelValue', `${nuevoMes}-${dia.value}`)
}
function onDiaChange(nuevoDia) {
  emit('update:modelValue', `${mes.value}-${nuevoDia}`)
}
</script>
