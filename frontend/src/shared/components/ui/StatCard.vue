<template>
  <div class="bg-white rounded-xl border border-gray-100 p-4">
    <div
      class="w-7 h-[3px] rounded mb-3"
      :class="{
        'bg-gold':    accent === 'gold',
        'bg-navy':    accent === 'navy',
        'bg-red-500': accent === 'red',
      }"
    />
    <div class="text-[22px] font-medium text-navy leading-none">{{ value }}</div>
    <div class="text-[10px] text-gray-400 mt-1.5">{{ label }}</div>
  </div>
</template>

<script setup>
defineProps({
  label:  { type: String,           required: true },
  value:  { type: [Number, String], required: true },
  accent: { type: String,           default: 'navy' },
})
</script>
