<template>
  <div>

    <!-- Barra superior -->
    <div class="flex items-center gap-3">

      <!-- Selector de gestión (año/periodo) -->
      <div class="flex items-center gap-1.5 shrink-0">
        <select
          class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
          title="Periodo académico"
          :value="modelValue.periodo"
          @change="actualizar('periodo', $event.target.value)"
        >
          <option v-for="p in PERIODOS" :key="p.value" :value="p.value">
            {{ p.label }}
          </option>
        </select>

        <select
          class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
          title="Año"
          :value="modelValue.anio"
          @change="actualizar('anio', $event.target.value)"
        >
          <option value="2026">Año 2026</option>
          <option value="2025">Año 2025</option>
          <option value="2024">Año 2024</option>
        </select>
      </div>

      <!-- Buscador -->
      <div class="relative flex-1">
        <svg xmlns="http://www.w3.org/2000/svg"
          class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>

        </svg>

        <input
          type="text"
          placeholder="Buscar estudiante o código..."
          class="w-full h-10 rounded-xl border border-slate-200 bg-white
                pl-11 pr-4 text-sm
                text-slate-700
                placeholder:text-slate-400
                focus:outline-none
                focus:ring-2
                focus:ring-blue-100
                focus:border-blue-500
                transition"
          :value="modelValue.busqueda"
          @input="actualizar('busqueda', $event.target.value)"
        />
      </div>

      <!-- Botón filtros -->
      <button
        @click="mostrarFiltros = !mostrarFiltros"
        class="flex items-center gap-2 h-10 px-4 rounded-xl
              border border-slate-200 bg-white
              hover:bg-slate-50
              transition
              shrink-0">

        <svg xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-slate-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L15 12v6l-6 2v-8L3.2 4.6A1 1 0 013 4z"/>

        </svg>

        <span class="text-sm font-medium text-slate-700">
          Filtros
        </span>

        <!-- Badge cantidad -->
        <span
          v-if="modelValue.plan || modelValue.nivel"
          class="flex items-center justify-center
                min-w-[20px] h-5 px-1
                rounded-full
                bg-blue-600
                text-white
                text-[11px]
                font-semibold">

          {{
            [modelValue.plan, modelValue.nivel]
            .filter(Boolean).length
          }}

        </span>

        <svg xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-slate-500 transition duration-200"
          :class="{ 'rotate-180': mostrarFiltros }"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"/>

        </svg>

      </button>

    </div>

    <!-- Panel filtros -->
    <Transition
      enter-active-class="transition-all duration-200"
      leave-active-class="transition-all duration-150"
      enter-from-class="opacity-0 -translate-y-2"
      leave-to-class="opacity-0 -translate-y-2">

      <div
        v-if="mostrarFiltros"
        class="mt-3 rounded-xl border border-slate-200
              bg-slate-50 p-4">

        <div class="flex items-center justify-between mb-3">

          <span class="text-sm font-semibold text-slate-700">
            Filtros avanzados
          </span>

          <button
            v-if="modelValue.plan || modelValue.nivel"
            @click="limpiarFiltros"
            class="text-xs font-medium
                  text-blue-600
                  hover:text-blue-700
                  transition">

            Restablecer

          </button>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

          <!-- Carrera -->
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">
              Carrera
            </label>

            <select
              class="w-full h-10 rounded-lg
                    border border-slate-200
                    bg-white
                    px-3
                    text-sm"
              :value="modelValue.plan"
              @change="actualizar('plan', $event.target.value)">

              <option value="">Todas las carreras</option>

              <option
                v-for="p in planesOptions"
                :key="p.codigo"
                :value="p.codigo">

                {{ p.nombre }}

              </option>

            </select>
          </div>

          <!-- Nivel -->
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">
              Nivel
            </label>

            <select
              class="w-full h-10 rounded-lg
                    border border-slate-200
                    bg-white
                    px-3
                    text-sm"
              :value="modelValue.nivel"
              @change="actualizar('nivel', $event.target.value)">

              <option value="">Todos los niveles</option>

              <option
                v-for="n in NIVELES"
                :key="n"
                :value="n">

                Nivel {{ n }}

              </option>

            </select>
          </div>

        </div>

      </div>

    </Transition>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { PLANES, PERIODOS, NIVELES } from '../services/estudiantesInscritosService.js'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    // { anio, periodo, plan, nivel, busqueda }
  },
})

const emit = defineEmits(['update:modelValue', 'limpiar'])

const mostrarFiltros = ref(false)

function actualizar(campo, valor) {
  emit('update:modelValue', { ...props.modelValue, [campo]: valor })
}

const planesOptions = computed(() =>
  Object.entries(PLANES).map(([codigo, nombre]) => ({ codigo, nombre }))
)

function limpiarFiltros() {
  emit('limpiar')
}
</script>