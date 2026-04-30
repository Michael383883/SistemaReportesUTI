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
            placeholder="Ej: RR Nº 34/2026"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg transition-colors outline-none"
            :class="errors.numero
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @input="errors.numero = ''"
          />
        </div>
        <p v-if="errors.numero" class="text-xs text-red-500 mt-1">{{ errors.numero }}</p>
        <p v-else class="text-xs text-gray-400 mt-1">Formato sugerido: RR Nº 034/2026</p>
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
          <span class="text-xs text-gray-400 ml-auto">{{ form.descripcion.length }}/500</span>
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
            :max="new Date().getFullYear()"
            placeholder="Ej: 2024"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg outline-none transition-colors"
            :class="errors.anio
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @input="errors.anio = ''"
          />
        </div>
        <p v-if="errors.anio" class="text-xs text-red-500 mt-1">{{ errors.anio }}</p>
      </div>

      <!-- Periodo -->
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
          <input
            v-model.number="form.periodo"
            type="number"
            min="1"
            step="1"
            placeholder="Ej: 1"
            class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-lg outline-none transition-colors"
            :class="errors.periodo
              ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
              : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'"
            @input="errors.periodo = ''"
          />
        </div>
        <p v-if="errors.periodo" class="text-xs text-red-500 mt-1">{{ errors.periodo }}</p>
      </div>

      <!-- Info extraída automáticamente -->
      <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-100 rounded-lg">
        <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <p class="text-xs text-blue-700">
          Los campos son igual al del PDF. Verifica que la información sea correcta antes de continuar.
        </p>
      </div>

    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
      <button
        @click="$emit('back')"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Volver
      </button>

      <button
        @click="submitForm"
        class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
      >
        Continuar
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  initialNumero:     { type: String, default: '' },
  initialDescripcion: { type: String, default: '' },
  initialAnio:       { type: Number, default: null },
  initialPeriodo:    { type: Number, default: null },
})

const emit = defineEmits(['submit', 'back'])

const form = reactive({
  numero:     props.initialNumero,
  descripcion: props.initialDescripcion,
  anio:       props.initialAnio,
  periodo:    props.initialPeriodo,
})

const errors = reactive({
  numero:     '',
  descripcion: '',
  anio:       '',
  periodo:    '',
})

watch(() => props.initialNumero,      val => { form.numero      = val })
watch(() => props.initialDescripcion, val => { form.descripcion = val })
watch(() => props.initialAnio,        val => { form.anio        = val })
watch(() => props.initialPeriodo,     val => { form.periodo     = val })

function submitForm() {
  errors.numero     = ''
  errors.descripcion = ''
  errors.anio       = ''
  errors.periodo    = ''

  let valid = true

  if (!form.numero.trim()) {
    errors.numero = 'El número de resolución es requerido.'
    valid = false
  }

  if (!form.descripcion.trim()) {
    errors.descripcion = 'La descripción es requerida.'
    valid = false
  } else if (form.descripcion.length > 500) {
    errors.descripcion = 'Máximo 500 caracteres.'
    valid = false
  }

  if (!form.anio) {
    errors.anio = 'El año es requerido.'
    valid = false
  } else if (form.anio < 1900 || form.anio > new Date().getFullYear()) {
    errors.anio = `El año debe estar entre 1900 y ${new Date().getFullYear()}.`
    valid = false
  }

  if (!form.periodo) {
    errors.periodo = 'El periodo es requerido.'
    valid = false
  } else if (form.periodo < 1) {
    errors.periodo = 'El periodo debe ser mayor a 0.'
    valid = false
  }

  if (!valid) return

  emit('submit', {
    numero:     form.numero.trim(),
    descripcion: form.descripcion.trim(),
    anio:       form.anio,
    periodo:    form.periodo,
  })
}
</script>