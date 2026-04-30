<template>
  <div class="min-h-screen bg-gray-50 p-6">

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Resoluciones</h1>
      <p class="text-sm text-gray-500 mt-1">Carga y procesamiento de resoluciones en PDF</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center gap-0 mb-10">
      <div v-for="(step, i) in steps" :key="i" class="flex items-center">
        <div class="flex flex-col items-center">
          <div
            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition-all duration-300"
            :class="stepCircleClass(i)"
          >
            <svg v-if="currentStep > i" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span class="text-xs mt-1.5 font-medium" :class="currentStep === i ? 'text-blue-600' : 'text-gray-400'">
            {{ step }}
          </span>
        </div>
        <div
          v-if="i < steps.length - 1"
          class="h-0.5 w-16 mb-4 transition-all duration-500"
          :class="currentStep > i ? 'bg-blue-500' : 'bg-gray-200'"
        />
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 0: Subir / capturar PDF                  -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 0">

      <!-- Tabs -->
      <div class="flex gap-1 p-1 bg-gray-100 rounded-xl mb-6 w-fit">
        <button
          @click="modoEntrada = 'camara'"
          class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200"
          :class="modoEntrada === 'camara'
            ? 'bg-white text-blue-600 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
        >
          📷 Tomar fotos
        </button>
        <button
          @click="modoEntrada = 'pdf'"
          class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200"
          :class="modoEntrada === 'pdf'
            ? 'bg-white text-blue-600 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
        >
          📄 Subir PDF
        </button>
      </div>

      <!-- Modo cámara -->
      <div v-if="modoEntrada === 'camara'">
        <CamaraCaptura
          ref="camaraRef"
          @pdf-listo="onPdfListo"
          @error="uploadError = $event"
        />
      </div>

      <!-- Modo subir PDF -->
      <div v-if="modoEntrada === 'pdf'">
        <div
          class="border-2 border-dashed rounded-xl p-12 text-center transition-colors duration-200 bg-white"
          :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400'"
          @dragover.prevent="isDragging = true"
          @dragleave="isDragging = false"
          @drop.prevent="handleDrop"
        >
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center">
              <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>

            <!-- Archivo seleccionado -->
            <div v-if="archivo" class="flex items-center gap-3 px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg">
              <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="text-sm font-medium text-blue-700 truncate max-w-xs">{{ archivo.name }}</span>
              <button @click="limpiarArchivo" class="text-blue-400 hover:text-red-500 transition-colors ml-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-else>
              <p class="text-gray-700 font-medium">Arrastra tu PDF aquí</p>
              <p class="text-gray-400 text-sm mt-1">o selecciona desde tu dispositivo</p>
            </div>

            <label class="cursor-pointer">
              <input type="file" accept=".pdf" class="hidden" @change="handleFileSelect" />
              <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                {{ archivo ? 'Cambiar PDF' : 'Seleccionar PDF' }}
              </span>
            </label>
            <p class="text-xs text-gray-400">Solo archivos .pdf · Máximo 5MB</p>
          </div>
        </div>

        <!-- Botón continuar al paso 1 (formulario) -->
        <div class="mt-4 flex justify-end" v-if="archivo">
          <button
            @click="currentStep = 1"
            class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors"
          >
            Continuar
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Error global paso 0 -->
      <div v-if="uploadError" class="mt-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"/>
        </svg>
        {{ uploadError }}
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 1: Formulario de datos de la resolución  -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 1">
      <ResolucionForm
        :initial-numero="formNumero"
        :initial-descripcion="formDescripcion"
        :initial-anio="formAnio"
        :initial-periodo="formPeriodo"
        @submit="onFormSubmit"
        @back="currentStep = 0"
      />
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 2: Carga manual de materias acéfalas     -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 2">
      <TablasForm
        @submit="onTablasSubmit"
        @back="currentStep = 1"
      />
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 3: Preview y validación                  -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 3">
      <TablaPreview
        :data="resolucionData"
        :numero="formNumero"
        :descripcion="formDescripcion"
        :loading="isMigrating"
        @migrar="onMigrar"
        @back="currentStep = 2"
      />

      <!-- Mensaje éxito -->
      <div v-if="mensajeExito" class="mt-4 flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm font-medium">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ mensajeExito }}
      </div>

      <!-- Error en migración -->
      <div v-if="errorMigracion" class="mt-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"/>
        </svg>
        {{ errorMigracion }}
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import CamaraCaptura  from '../components/CamaraCaptura.vue'
import ResolucionForm from '../components/ResolucionForm.vue'
import TablasForm     from '../components/TablasForm.vue'
import TablaPreview   from '../components/TablaPreview.vue'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

// ── Stepper ───────────────────────────────────────────────
const steps       = ['Subir PDF', 'Datos', 'Materias', 'Validar']
const currentStep = ref(0)

// ── Estado ────────────────────────────────────────────────
const isDragging      = ref(false)
const uploadError     = ref('')
const errorMigracion  = ref('')
const mensajeExito    = ref('')
const isMigrating     = ref(false)
const modoEntrada     = ref('pdf')   // 'camara' | 'pdf'
const camaraRef       = ref(null)

const archivo          = ref(null)
const formNumero       = ref('')
const formDescripcion  = ref('')
const formAnio         = ref(null)
const formPeriodo      = ref(null)
const formDetalles     = ref([])     // payload de TablasForm
const resolucionData   = ref(null)   // construido tras onTablasSubmit

// ── Helpers de archivo ────────────────────────────────────
function limpiarArchivo() {
  archivo.value     = null
  uploadError.value = ''
}

function validarArchivo(file) {
  if (!file) return false
  if (file.type !== 'application/pdf') {
    uploadError.value = 'Solo se permiten archivos PDF.'
    return false
  }
  if (file.size > 5 * 1024 * 1024) {
    uploadError.value = 'El archivo supera 5MB.'
    return false
  }
  return true
}

function handleFileSelect(e) {
  uploadError.value = ''
  const file = e.target.files[0]
  if (validarArchivo(file)) archivo.value = file
}

function handleDrop(e) {
  isDragging.value  = false
  uploadError.value = ''
  const file = e.dataTransfer.files[0]
  if (validarArchivo(file)) archivo.value = file
}

function onPdfListo(file) {
  uploadError.value = ''
  archivo.value     = file
  currentStep.value = 1
}

// ── Paso 1 → ResolucionForm enviado ──────────────────────
function onFormSubmit({ numero, descripcion, anio, periodo }) {
  formNumero.value      = numero
  formDescripcion.value = descripcion
  formAnio.value        = anio
  formPeriodo.value     = periodo
  currentStep.value     = 2
}

// ── Paso 2 → TablasForm enviado ───────────────────────────
function onTablasSubmit(detalles) {
  formDetalles.value = detalles

  // Arma resolucionData para que TablaPreview pueda mostrarlo
  resolucionData.value = {
    numero:      formNumero.value,
    descripcion: formDescripcion.value,
    anio:        formAnio.value,
    periodo:     formPeriodo.value,
    detalles,
  }

  currentStep.value = 3
}

// ── POST guardar resolución completa ─────────────────────
async function onMigrar() {
  isMigrating.value    = true
  errorMigracion.value = ''
  mensajeExito.value   = ''

  try {
    const token = localStorage.getItem('token')

    const form = new FormData()
    form.append('nro_resolucion', formNumero.value)
    form.append('descripcion',    formDescripcion.value)
    form.append('anio',           formAnio.value)
    form.append('periodo',        formPeriodo.value)
    form.append('detalles',       JSON.stringify(formDetalles.value))

    if (archivo.value) {
      form.append('archivo_pdf',    archivo.value)
      form.append('nombre_archivo', archivo.value.name)
      form.append('tamanio_kb',     Math.round(archivo.value.size / 1024))
    }

    const { data } = await axios.post(
      `${API_BASE}/resoluciones`,
      form,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
      }
    )

    if (!data.success) throw new Error(data.message ?? 'Error al guardar.')

    mensajeExito.value = data.message ?? 'Resolución guardada correctamente.'
    setTimeout(() => resetAll(), 2000)

  } catch (e) {
    console.error('onMigrar error:', e)
    errorMigracion.value = e.response?.data?.message ?? e.message ?? 'Error al guardar.'
  } finally {
    isMigrating.value = false
  }
}

// ── Reset completo ────────────────────────────────────────
function resetAll() {
  camaraRef.value?.reset?.()
  currentStep.value     = 0
  archivo.value         = null
  uploadError.value     = ''
  errorMigracion.value  = ''
  mensajeExito.value    = ''
  formNumero.value      = ''
  formDescripcion.value = ''
  formAnio.value        = null
  formPeriodo.value     = null
  formDetalles.value    = []
  resolucionData.value  = null
  isMigrating.value     = false
}

// ── Stepper styles ────────────────────────────────────────
function stepCircleClass(i) {
  if (currentStep.value > i)   return 'bg-blue-600 border-blue-600 text-white'
  if (currentStep.value === i) return 'bg-white border-blue-600 text-blue-600'
  return 'bg-white border-gray-300 text-gray-400'
}
</script>