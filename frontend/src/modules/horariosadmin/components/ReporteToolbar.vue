<template>
  <div class="border-b border-slate-700 px-8 py-2.5 flex flex-wrap gap-3 items-end">

    <!-- Año -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Año</label>
      <input
        :value="anio"
        @input="$emit('update:anio', Number($event.target.value))"
        @keyup.enter="$emit('buscar')"
        type="number" min="2020" max="2030"
        class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
               placeholder-slate-500 transition-all duration-150
               focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
               [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
      />
    </div>

    <!-- Período -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Período</label>
      <select
        :value="periodo"
        @change="$emit('update:periodo', Number($event.target.value))"
        @keyup.enter="$emit('buscar')"
        class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
               transition-all duration-150
               focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
      >
        <option :value="1">1</option>
        <option :value="2">2</option>
      </select>
    </div>

    <!-- Buscar Docente -->
    <div class="flex-1 min-w-[260px] flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Buscar Docente</label>
      <div class="flex gap-2">
        <input
          :value="busqueda"
          @input="$emit('update:busqueda', $event.target.value)"
          type="text" placeholder="Código o apellidos..."
          @keyup.enter="$emit('buscar')"
          class="flex-1 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 placeholder-slate-500 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
        />
        <button
          @click="$emit('buscar')"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-100
                 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed
                 shadow-lg shadow-amber-500/20"
        >
          <svg :class="loading ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2.5">
            <template v-if="loading">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
            </template>
            <template v-else-if="busqueda.trim()">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </template>
            <template v-else>
              <line x1="8" y1="6" x2="21" y2="6"/>
              <line x1="8" y1="12" x2="21" y2="12"/>
              <line x1="8" y1="18" x2="21" y2="18"/>
              <line x1="3" y1="6" x2="3.01" y2="6"/>
              <line x1="3" y1="12" x2="3.01" y2="12"/>
              <line x1="3" y1="18" x2="3.01" y2="18"/>
            </template>
          </svg>
          {{ loading ? 'Buscando...' : (busqueda.trim() ? 'Buscar' : 'Ver todos') }}
        </button>
      </div>
    </div>

    <!-- Zona reservada para el botón de PDF, cada vista pone el suyo -->
    <slot name="pdf" />
  </div>
</template>

<script setup>
defineProps({
  anio: Number,
  periodo: Number,
  busqueda: String,
  loading: Boolean,
})
defineEmits(['update:anio', 'update:periodo', 'update:busqueda', 'buscar'])
</script>