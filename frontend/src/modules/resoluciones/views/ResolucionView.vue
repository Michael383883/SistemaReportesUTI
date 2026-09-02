<template>
  <div class="py-1 min-h-screen bg-gray-50 p-6">

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-[20px] font-bold text-gray-1000">Digitalizar Resoluciones</h1>
      <p class="text-[14px] text-gray-700 mt-1">Carga y procesamiento de resoluciones en PDF</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center mb-10">
      <template v-for="(step, i) in steps" :key="i">
        <div class="flex flex-col items-center">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center text-[12px] font-medium border-2 transition-all duration-300"
            :class="
              currentStep > i
                ? 'bg-blue-600 border-blue-600 text-white'
                : currentStep === i
                  ? 'bg-white border-blue-600 text-blue-600'
                  : 'bg-white border-gray-300 text-gray-400'
            "
          >
            <svg v-if="currentStep > i" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span
            class="text-[11px] mt-1.5 font-medium"
            :class="currentStep === i ? 'text-blue-600' : 'text-gray-400'"
          >
            {{ step }}
          </span>
        </div>
        <div
          v-if="i < steps.length - 1"
          class="h-0.5 w-14 mb-5 transition-all duration-500"
          :class="currentStep > i ? 'bg-blue-500' : 'bg-gray-200'"
        />
      </template>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 0: Subir / capturar PDF                  -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 0">

      <!-- Tabs -->
      <div class="flex gap-1 p-1 bg-gray-100 rounded-xl mb-6 w-fit">
        <button
          @click="modoEntrada = 'camara'"
          class="px-5 py-2 text-[14px] font-medium rounded-lg transition-all duration-200"
          :class="modoEntrada === 'camara'
            ? 'bg-white text-blue-600 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
        >
          📷 Tomar fotos
        </button>
        <button
          @click="modoEntrada = 'pdf'"
          class="px-5 py-2 text-[14px] font-medium rounded-lg transition-all duration-200"
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
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

          <!-- Zona de drop -->
          <div
            class="p-10 text-center border-b border-gray-100 transition-colors duration-200"
            :class="isDragging ? 'bg-blue-50' : 'bg-white'"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
          >
            <div class="flex flex-col items-center gap-4">

              <!-- Ícono -->
              <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>

              <!-- Archivo seleccionado -->
              <div
                v-if="archivo"
                class="flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg"
              >
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-[13px] font-medium text-blue-700 truncate max-w-xs">{{ archivo.name }}</span>
                <button @click="limpiarArchivo" class="text-blue-400 hover:text-red-500 transition-colors ml-1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Placeholder -->
              <div v-else>
                <p class="text-[15px] font-semibold text-gray-700">Arrastra tu PDF aquí</p>
                <p class="text-[14px] text-gray-800 mt-1">o selecciona desde tu dispositivo</p>
              </div>

              <!-- Botón seleccionar -->
              <label class="cursor-pointer">
                <input type="file" accept=".pdf" class="hidden" @change="handleFileSelect"/>
                <span class="inline-flex items-center gap-2 px-5 py-2 bg-blue-800 hover:bg-blue-700 text-white text-[15px] font-semibold rounded-lg transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                  </svg>
                  {{ archivo ? 'Cambiar PDF' : 'Seleccionar PDF' }}
                </span>
              </label>

              <p class="text-[13px] text-gray-700">Solo archivos .pdf · Máximo 20 MB</p>
            </div>
          </div>

          <!-- Footer del panel -->
          <div class="flex items-center justify-end px-6 py-3 bg-gray-50">
            <button
              v-if="archivo"
              @click="currentStep = 1"
              class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 text-white text-[14px] font-medium rounded-lg transition-colors"
            >
              Continuar
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Error paso 0 -->
      <div
        v-if="uploadError"
        class="mt-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[12px]"
      >
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ uploadError }}
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 1: Formulario + Guardar / Guardar+Asignar -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 1">

      <!-- Mensaje de éxito (solo "Guardar resolución") -->
      <div
        v-if="successMessage"
        class="bg-white rounded-xl border border-gray-200 p-10 text-center"
      >
        <div class="w-14 h-14 mx-auto rounded-full bg-green-50 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="text-[15px] font-semibold text-gray-900">{{ successMessage }}</p>
        <p class="text-[13px] text-gray-400 mt-1">
          Resolución <strong>{{ formNumero }}</strong> registrada correctamente (ID: {{ resolucion.resolucionId.value }}).
        </p>
        <button
          @click="resetAll"
          class="mt-6 inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-[14px] font-medium rounded-lg transition-colors"
        >
          Registrar otra resolución
        </button>
      </div>

      <!-- Formulario: el PDF YA fue elegido en el Paso 0 (archivo.value).
           Solo se le pasa el nombre como referencia visual; el File real
           se sigue manejando acá y se envía en onGuardar/onGuardarYAsignar. -->
      <ResolucionForm
        v-else
        :initial-numero="formNumero"
        :initial-descripcion="formDescripcion"
        :initial-anio="formAnio"
        :initial-periodo="formPeriodo"
        :saving="resolucion.loading.value"
        :error="resolucion.error.value"
        :archivo-nombre="archivo?.name || ''"
        @guardar="onGuardar"
        @guardar-asignar="onGuardarYAsignar"
        @back="currentStep = 0"
      />
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CamaraCaptura  from '../components/CamaraCaptura.vue'
import ResolucionForm from '../components/ResolucionForm.vue'
import { useResolucion } from '../composables/useResolucion'

const router = useRouter()
const resolucion = useResolucion()

const steps       = ['Subir PDF', 'Datos']
const currentStep = ref(0)

const isDragging      = ref(false)
const uploadError     = ref('')
const modoEntrada     = ref('pdf')
const camaraRef       = ref(null)
const archivo         = ref(null) // único origen de verdad para el PDF: se llena en el Paso 0

const formNumero      = ref('')
const formDescripcion = ref('')
const formAnio        = ref(null)
const formPeriodo     = ref(null)
const successMessage  = ref('')

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
  if (file.size > 20  * 1024 * 1024) {
    uploadError.value = 'El archivo supera 20 MB.'
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

// Botón "Guardar resolución"
async function onGuardar({ numero, descripcion, anio, periodo }) {
  formNumero.value      = numero
  formDescripcion.value = descripcion
  formAnio.value        = anio
  formPeriodo.value     = periodo

  try {
    await resolucion.guardarResolucion({ numero, descripcion, anio, periodo, archivo: archivo.value })
    successMessage.value = 'Resolución guardada exitosamente'
  } catch {
    // error visible vía :error en ResolucionForm (resolucion.error.value)
  }
}

// Botón "Guardar y asignar docentes"
async function onGuardarYAsignar({ numero, descripcion, anio, periodo }) {
  formNumero.value      = numero
  formDescripcion.value = descripcion
  formAnio.value        = anio
  formPeriodo.value     = periodo

  try {
    const idResolucion = await resolucion.guardarResolucion({ numero, descripcion, anio, periodo, archivo: archivo.value })
    router.push({
      name: 'resoluciones-asignar',
      query: {
        resolucion: idResolucion,
        nro: numero,
        anio,
        periodo,
      }
    })
  } catch {
    // error visible vía :error en ResolucionForm (resolucion.error.value)
  }
}

function resetAll() {
  camaraRef.value?.reset?.()
  resolucion.reset()
  currentStep.value     = 0
  archivo.value         = null
  uploadError.value     = ''
  modoEntrada.value     = 'pdf'
  formNumero.value      = ''
  formDescripcion.value = ''
  formAnio.value        = null
  formPeriodo.value     = null
  successMessage.value  = ''
}
</script>