<template>
  <div class="min-h-screen bg-gray-50 p-1">

    <!-- Header -->
    <div class="mb-4">
      <h1 class="text-[18px] font-semibold text-gray-1000">Digitalizar Documentos de Docente</h1>
      <p class="text-[12px] text-gray-700 mt-0.5">Carga y registro de documentos (Web sis 2001)</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center">
        <template v-for="(step, i) in steps" :key="i">
          <div class="flex flex-col items-center">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-medium border-2 transition-all duration-300"
              :class="
                currentStep > i
                  ? 'bg-blue-600 border-blue-600 text-white'
                  : currentStep === i
                    ? 'bg-white border-blue-600 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-400'
              "
            >
              <svg v-if="currentStep > i" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span class="text-[10px] mt-1 font-medium" :class="currentStep === i ? 'text-blue-600' : 'text-gray-400'">
              {{ step }}
            </span>
          </div>
          <div
            v-if="i < steps.length - 1"
            class="h-0.5 w-10 mb-4 transition-all duration-500"
            :class="currentStep > i ? 'bg-blue-500' : 'bg-gray-200'"
          />
        </template>
      </div>

      <!-- Archivo subido, visible junto al stepper en el paso 2 -->
      <div v-if="currentStep === 1 && archivo" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
        <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-[12px] font-medium text-blue-700 truncate max-w-[220px]">{{ archivo.name }}</span>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 0: Subir PDF                              -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 0">
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        <div
          class="p-6 text-center border-b border-gray-100 transition-colors duration-200"
          :class="isDragging ? 'bg-blue-50' : 'bg-white'"
          @dragover.prevent="isDragging = true"
          @dragleave="isDragging = false"
          @drop.prevent="handleDrop"
        >
          <div class="flex flex-col items-center gap-2.5">
            <div class="w-11 h-11 rounded-2xl bg-blue-50 flex items-center justify-center">
              <svg class="w-5.5 h-5.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>

            <div v-if="archivo" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
              <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-[12px] font-medium text-blue-700 truncate max-w-xs">{{ archivo.name }}</span>
              <button @click="limpiarArchivo" class="text-blue-400 hover:text-red-500 transition-colors ml-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <div v-else>
              <p class="text-[13px] font-medium text-gray-700">Arrastra el PDF aquí</p>
              <p class="text-[12px] text-gray-400 mt-0.5">Selecciona el documento desde tu dispositivo</p>
            </div>

            <label class="cursor-pointer">
              <input type="file" accept=".pdf" class="hidden" @change="handleFileSelect"/>
              <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[13px] font-medium rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                {{ archivo ? 'Cambiar PDF' : 'Seleccionar PDF' }}
              </span>
            </label>

            <p class="text-[10px] text-gray-400">Solo archivos .pdf · Máximo 20 MB (fotocopia de título/documento respaldo)</p>
          </div>
        </div>

        <div class="flex items-center justify-end px-4 py-2.5 bg-gray-50">
          <button
            @click="irAlPaso2"
            :disabled="!archivo"
            class="inline-flex items-center gap-2 px-4 py-1.5 text-[13px] font-medium rounded-lg transition-colors"
            :class="archivo
              ? 'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer'
              : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
          >
            Continuar
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="uploadError" class="mt-3 flex items-center gap-2 p-2.5 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[12px]">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ uploadError }}
      </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- PASO 1: Formulario                             -->
    <!-- ══════════════════════════════════════════════ -->
    <div v-if="currentStep === 1">

      <div v-if="successMessage" class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <div class="w-11 h-11 mx-auto rounded-full bg-green-50 flex items-center justify-center mb-3">
          <svg class="w-5.5 h-5.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="text-[14px] font-semibold text-gray-900">{{ successMessage }}</p>
        <p class="text-[12px] text-gray-400 mt-1">
  Clasificación registrada correctamente (Documento ID: {{ ultimoId }}, docentes vinculados: {{ docentesRegistrados }}).</p>
  <!-- Estado de asignación a GRUPOS -->
<p v-if="aplicadoAGrupos === true" class="text-[12px] text-green-600 mt-1 font-medium">
  ✓ Datos aplicados en GRUPOS correctamente.
</p>
<p v-else-if="aplicadoAGrupos === false" class="text-[12px] text-amber-600 mt-1 font-medium">
  ⚠️ La clasificación se guardó, pero no se pudo aplicar en GRUPOS. Puedes intentarlo luego desde el listado.
</p>
<!-- 👇 NUEVO: detalle técnico del error al aplicar en GRUPOS -->
<div v-if="errorGrupos" class="mt-2 mx-auto max-w-md text-left p-3 rounded-lg bg-red-50 border border-red-200 text-[11px] font-mono text-red-700 space-y-1">
  <p v-if="errorGrupos.sqlstate"><strong>SQLSTATE:</strong> {{ errorGrupos.sqlstate }}</p>
  <p v-if="errorGrupos.codigoDriver"><strong>Código driver:</strong> {{ errorGrupos.codigoDriver }}</p>
  <p v-if="errorGrupos.mensajeDriver"><strong>Mensaje SQL Server:</strong> {{ errorGrupos.mensajeDriver }}</p>
  <p v-if="errorGrupos.mensaje"><strong>Mensaje:</strong> {{ errorGrupos.mensaje }}</p>
  <details v-if="errorGrupos.sql" class="cursor-pointer">
    <summary class="text-red-600">Ver SQL y bindings</summary>
    <pre class="whitespace-pre-wrap mt-1">{{ errorGrupos.sql }}</pre>
    <pre class="whitespace-pre-wrap mt-1">Bindings: {{ JSON.stringify(errorGrupos.bindings) }}</pre>
  </details>
</div>
        <div class="flex items-center justify-center gap-3 mt-5">
          <button
            @click="resetAll"
            class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[13px] font-medium rounded-lg transition-colors"
          >
            Registrar otra clasificación
          </button>
          <router-link
            :to="{ name: 'clasificaciones-listado' }"
            class="inline-flex items-center gap-2 px-4 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-[13px] font-medium rounded-lg transition-colors"
          >
            Ver listado
          </router-link>
        </div>
      </div>

      <ClasificacionForm
        v-else
        :docentes="docentes"
        :saving="clasificacion.loading.value"
        :error="clasificacion.error.value"
        :archivo-nombre="archivo?.name || ''"
        @guardar="onGuardar"
        @back="currentStep = 0"
      />
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import ClasificacionForm from '../components/ClasificacionForm.vue'
import { useClasificacion } from '../composables/useClasificacion'
import { useDocentesReportes } from '../composables/useDocentesReportes' // ajusta la ruta

const clasificacion = useClasificacion()

const {
  docentes,
  fetchDocentes,
} = useDocentesReportes()

fetchDocentes()

const steps       = ['Subir PDF', 'Datos']
const currentStep = ref(0)

const isDragging  = ref(false)
const uploadError = ref('')
const archivo     = ref(null)

const successMessage = ref('')
const ultimoId        = ref(null)
const docentesRegistrados = ref(0) // nuevo
const aplicadoAGrupos = ref(null)
const errorGrupos = ref(null)
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
  if (file.size > 20 * 1024 * 1024) {
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

// El PDF es obligatorio para avanzar al paso 2 (Datos)
function irAlPaso2() {
  if (!archivo.value) {
    uploadError.value = 'Debes subir el PDF antes de continuar.'
    return
  }
  uploadError.value = ''
  currentStep.value = 1
}

async function onGuardar(formData, debeAplicarAGrupos) {
  try {
    const resultado = await clasificacion.guardarClasificacion({ ...formData, archivo: archivo.value })
    ultimoId.value = resultado.idDocumento
    docentesRegistrados.value = resultado.idsClasificacionDocente.length

    if (debeAplicarAGrupos && resultado.materiasInsertadas > 0) {
      try {
        const resGrupos = await clasificacion.aplicarEnGrupos(resultado.idDocumento)
        aplicadoAGrupos.value = resGrupos.filas_afectadas > 0
        errorGrupos.value = null // 👈 limpio si salió bien
      } catch (e) { // 👈 antes decía "catch {" y perdía el error
        aplicadoAGrupos.value = false
        errorGrupos.value = clasificacion.errorDetalle.value // 👈 guardo el detalle
      }
    } else {
      aplicadoAGrupos.value = null
      errorGrupos.value = null
    }

    successMessage.value = 'Clasificación guardada exitosamente'
  } catch {
    // error visible vía :error en ClasificacionForm
  }
}

function resetAll() {
  clasificacion.reset()
  currentStep.value    = 0
  archivo.value        = null
  uploadError.value    = ''
  successMessage.value = ''
  ultimoId.value        = null
  docentesRegistrados.value = 0
  aplicadoAGrupos.value = null
   errorGrupos.value = null // 👈 nuevo

}
</script>