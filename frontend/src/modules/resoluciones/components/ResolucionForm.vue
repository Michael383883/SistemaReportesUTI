<template>
  <div class="bg-white rounded-xl border border-gray-200">

    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100">
      <h2 class="text-base font-semibold text-gray-800">Datos de la Resolución</h2>
      <p class="text-xs text-gray-400 mt-0.5">Verifica o corrige los datos extraídos automáticamente del PDF</p>
    </div>

    <div class="p-6 space-y-5">

      <!-- Número de resolución -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Número de Resolución
          <span class="text-red-500 ml-0.5">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
            </svg>
          </div>
          <input
            v-model="form.numero"
            type="text"
            maxlength="50"
            placeholder="Ej: RR Nº 34/2026"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg transition-colors outline-none"
            :class="errors.numero
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @input="errors.numero = ''"
          />
        </div>
        <p v-if="errors.numero" class="text-xs text-red-500 mt-1">{{ errors.numero }}</p>
        <p v-else class="text-xs text-gray-400 mt-1">Formato sugerido: RR Nº 034/2026 · máx. 50 caracteres</p>
      </div>

      <!-- Descripción -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Descripción / Vigencia
          <span class="text-red-500 ml-0.5">*</span>
        </label>
        <textarea
          v-model="form.descripcion"
          rows="3"
          maxlength="200"
          placeholder="Ej: Designación de materias acéfalas del 27 de febrero al 07 de julio de 2026..."
          class="w-full px-4 py-2.5 text-sm border rounded-lg resize-none transition-colors outline-none"
          :class="errors.descripcion
            ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
            : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
          @input="errors.descripcion = ''"
        />
        <div class="flex justify-between mt-1">
          <p v-if="errors.descripcion" class="text-xs text-red-500">{{ errors.descripcion }}</p>
          <p v-else class="text-xs text-gray-400">Descripción del alcance o vigencia de la resolución</p>
          <!-- contador: rojo cuando llega al límite -->
          <span
            class="text-xs ml-auto"
            :class="form.descripcion.length >= 190 ? 'text-red-400 font-medium' : 'text-gray-400'"
          >
            {{ form.descripcion.length }}/200
          </span>
        </div>
      </div>

      <!-- Año -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Año de la Resolución
          <span class="text-red-500 ml-0.5">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <input
            v-model.number="form.anio"
            type="number"
            min="1900"
            :max="currentYear"
            placeholder="Ej: 2026"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg outline-none transition-colors"
            :class="errors.anio
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @input="errors.anio = ''"
          />
        </div>
        <p v-if="errors.anio" class="text-xs text-red-500 mt-1">{{ errors.anio }}</p>
      </div>

      <!-- Periodo — select en lugar de number para garantizar string "1" o "2" -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Periodo de la Resolución
          <span class="text-red-500 ml-0.5">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <!-- SELECT: garantiza que se mande "1" o "2" como string, nunca un número largo -->
          <select
            v-model="form.periodo"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg outline-none transition-colors appearance-none bg-white"
            :class="errors.periodo
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @change="errors.periodo = ''"
          >
            <option value="" disabled>Selecciona el periodo</option>
            <option value="1">1 — Primer periodo</option>
            <option value="2">2 — Segundo periodo</option>
          </select>
          <!-- Flecha del select -->
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </div>
        </div>
        <p v-if="errors.periodo" class="text-xs text-red-500 mt-1">{{ errors.periodo }}</p>
        <p v-else class="text-xs text-gray-400 mt-1">Primer o segundo semestre/periodo académico</p>
      </div>

      <!-- Info -->
      <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-100 rounded-lg">
        <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <p class="text-xs text-blue-700">
          Verifica que la información sea correcta antes de continuar. Al presionar <strong>Continuar</strong> se guardará el PDF en el servidor.
        </p>
      </div>

    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
      <button
        @click="$emit('back')"
        :disabled="saving"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors disabled:opacity-40"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Volver
      </button>

      <button
        @click="submitForm"
        :disabled="saving"
        class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
      >
        <!-- Spinner mientras guarda -->
        <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span>{{ saving ? 'Guardando…' : 'Continuar' }}</span>
        <svg v-if="!saving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
  initialNumero:      { type: String,  default: '' },
  initialDescripcion: { type: String,  default: '' },
  initialAnio:        { type: Number,  default: null },
  initialPeriodo:     { type: [Number, String], default: '' },
  saving:             { type: Boolean, default: false },   // ← spinner externo
})

const emit = defineEmits(['submit', 'back'])

// Año máximo permitido
const currentYear = computed(() => new Date().getFullYear())

const form = reactive({
  numero:      props.initialNumero,
  descripcion: props.initialDescripcion,
  anio:        props.initialAnio,
  // Periodo siempre como string para que el select funcione
  periodo:     props.initialPeriodo ? String(props.initialPeriodo) : '',
})

const errors = reactive({
  numero:      '',
  descripcion: '',
  anio:        '',
  periodo:     '',
})

watch(() => props.initialNumero,      val => { form.numero      = val })
watch(() => props.initialDescripcion, val => { form.descripcion = val })
watch(() => props.initialAnio,        val => { form.anio        = val })
watch(() => props.initialPeriodo,     val => { form.periodo     = val ? String(val) : '' })

function submitForm() {
  // Reset errores
  Object.keys(errors).forEach(k => errors[k] = '')

  let valid = true

  if (!form.numero.trim()) {
    errors.numero = 'El número de resolución es requerido.'
    valid = false
  } else if (form.numero.trim().length > 50) {
    errors.numero = 'Máximo 50 caracteres.'
    valid = false
  }

  if (!form.descripcion.trim()) {
    errors.descripcion = 'La descripción es requerida.'
    valid = false
  } else if (form.descripcion.length > 200) {
    errors.descripcion = 'Máximo 200 caracteres.'
    valid = false
  }

  if (!form.anio) {
    errors.anio = 'El año es requerido.'
    valid = false
  } else if (form.anio < 1900 || form.anio > currentYear.value) {
    errors.anio = `El año debe estar entre 1900 y ${currentYear.value}.`
    valid = false
  }

  if (!form.periodo) {
    errors.periodo = 'El periodo es requerido.'
    valid = false
  }

  if (!valid) return

  emit('submit', {
    numero:      form.numero.trim(),
    descripcion: form.descripcion.trim(),
    anio:        form.anio,
    periodo:     form.periodo,   // string "1" o "2" — correcto para el backend
  })
}
</script>