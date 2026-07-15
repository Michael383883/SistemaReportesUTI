<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="p-6 space-y-5">

      <div>
        <label class="block text-[13px] font-medium text-gray-700 mb-1.5">Número de resolución *</label>
        <input
          v-model="numero"
          type="text"
          placeholder="Ej: RR Nº 266/2024"
          class="w-full px-3.5 py-2.5 text-[14px] border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100"
          :class="errores.numero ? 'border-red-300' : 'border-gray-200 focus:border-blue-400'"
        />
        <p v-if="errores.numero" class="text-[11px] text-red-500 mt-1">{{ errores.numero }}</p>
      </div>

      <div>
        <label class="block text-[13px] font-medium text-gray-700 mb-1.5">Descripción</label>
        <textarea
          v-model="descripcion"
          rows="3"
          placeholder="Descripción Ejm: (Del 26 febrero al 19 de abril de 2024. El semestre 1/2024 inició el 26 de febrero y terminó el 09 de julio del 2024.)"
          class="w-full px-3.5 py-2.5 text-[14px] border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 resize-none"
        ></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-medium text-gray-600 mb-0.5">
            Año *
          </label>

          <input
            v-model.number="anio"
            type="text"
            inputmode="numeric"
            placeholder="Ej. 2023"
            class="w-full px-2.5 py-1.5 text-[13px] border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="errores.anio ? 'border-red-300' : 'border-gray-300'"
          />

          <p v-if="errores.anio" class="text-[11px] text-red-500 mt-1">
            {{ errores.anio }}
          </p>
        </div>

        <div class="relative">
  <label class="block text-[11px] font-medium text-gray-600 mb-0.5">
    Periodo *
  </label>

  <div class="relative">
    <input
      v-model.number="periodo"
      type="text"
      inputmode="numeric"
      placeholder="Ej. 1"
      class="w-full px-2.5 py-1.5 pr-8 text-[13px] border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      :class="errores.periodo ? 'border-red-300' : 'border-gray-300'"
      @focus="periodoDropdownOpen = true"
      @blur="onBlurPeriodo"
    />

    <button
      type="button"
      tabindex="-1"
      @mousedown.prevent="togglePeriodoDropdown"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
    >
      <svg
        class="w-4 h-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>
  </div>

  <!-- Dropdown -->
  <div
    v-if="periodoDropdownOpen"
    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
  >
    <button
      v-for="opcion in [1, 2, 3, 4]"
      :key="opcion"
      type="button"
      @mousedown.prevent="seleccionarPeriodo(opcion)"
      class="w-full text-left px-3 py-2 text-[13px] text-gray-700 hover:bg-blue-50 transition-colors"
      :class="periodo === opcion ? 'bg-blue-50 text-blue-600 font-medium' : ''"
    >
       {{ opcion }}
    </button>
  </div>

  <p v-if="errores.periodo" class="text-[11px] text-red-500 mt-1">
    {{ errores.periodo }}
  </p>
</div>


      </div>

      <!-- El PDF ya se seleccionó en el paso anterior; aquí solo se muestra como referencia -->
      <div v-if="archivoNombre">
        <label class="block text-[13px] font-medium text-gray-700 mb-1.5">Archivo PDF</label>
        <div class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-lg">
          <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span class="text-[13px] text-gray-600 truncate">{{ archivoNombre }}</span>
        </div>
      </div>
    </div>

    <!-- Mensaje de error general (fallas del servidor, red, etc.) -->
    <div
      v-if="error"
      class="mx-6 mb-2 px-3.5 py-2.5 text-[13px] text-red-700 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2"
    >
      <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
      </svg>
      <span>{{ error }}</span>
    </div>

    <div class="flex items-center justify-between px-6 py-3 bg-gray-50 gap-3 flex-wrap">
      <button
        type="button"
        @click="$emit('back')"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-[13px] font-medium text-gray-500 hover:text-gray-700 transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>

      <div class="flex items-center gap-3">
        <button
          type="button"
          :disabled="saving"
          @click="enviar('guardar')"
          class="px-5 py-2 text-[14px] font-medium rounded-lg border border-blue-600 text-blue-600 hover:bg-blue-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ saving && accion === 'guardar' ? 'Guardando...' : 'Guardar resolución' }}
        </button>
        <button
          type="button"
          :disabled="saving"
          @click="enviar('guardar-asignar')"
          class="px-5 py-2 text-[14px] font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ saving && accion === 'guardar-asignar' ? 'Guardando...' : 'Guardar y asignar docentes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  initialNumero:      { type: String, default: '' },
  initialDescripcion: { type: String, default: '' },
  initialAnio:        { type: [String, Number], default: null },
  initialPeriodo:      { type: [String, Number], default: null },
  saving:              { type: Boolean, default: false },
  error:               { type: String, default: '' }, // mensaje de error proveniente del composable (fallas de red/servidor)
  archivoNombre:       { type: String, default: '' }, // nombre del PDF ya seleccionado en el paso anterior (solo lectura)
})

const emit = defineEmits(['guardar', 'guardar-asignar', 'back'])

const numero      = ref(props.initialNumero || '')
const descripcion = ref(props.initialDescripcion || '')
const anio         = ref(props.initialAnio || null)
const periodo      = ref(props.initialPeriodo || null)
const accion       = ref(null)
const errores      = ref({})

const periodoDropdownOpen = ref(false);

const seleccionarPeriodo = (valor) => {
  periodo.value = valor;
  periodoDropdownOpen.value = false;
};

const togglePeriodoDropdown = () => {
  periodoDropdownOpen.value = !periodoDropdownOpen.value;
};

const onBlurPeriodo = () => {
  setTimeout(() => {
    periodoDropdownOpen.value = false;
  }, 150);
};

function validar() {
  errores.value = { ...errores.value, numero: '', anio: '', periodo: '' }
  if (!numero.value.trim()) errores.value.numero = 'El número de resolución es obligatorio.'
  if (!anio.value) errores.value.anio = 'El año es obligatorio.'
  if (!periodo.value) {
    errores.value.periodo = 'Selecciona o ingresa un periodo.'
  } else if (![1, 2, 3, 4].includes(Number(periodo.value))) {
    errores.value.periodo = 'El periodo debe ser 1, 2, 3 o 4.'
  }
  // Limpia las claves vacías para que v-if deje de mostrarlas
  Object.keys(errores.value).forEach(k => { if (!errores.value[k]) delete errores.value[k] })
  return Object.keys(errores.value).length === 0
}

function enviar(tipo) {
  if (!validar()) return
  accion.value = tipo
  emit(tipo, {
    numero: numero.value.trim(),
    descripcion: descripcion.value.trim(),
    anio: anio.value,
    periodo: periodo.value,
  })
}
</script>