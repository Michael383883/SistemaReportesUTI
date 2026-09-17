<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="$emit('cerrar')">
      <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[92vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100">
          <div class="min-w-0">
            <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Editar documento completo</p>
            <h3 class="text-lg font-semibold text-gray-900 truncate">
              {{ doc.TIPO_DOCUMENTO || 'Documento' }}
            </h3>
            <div class="flex flex-wrap gap-1.5 mt-1.5">
              <span
                v-for="d in doc.docentes"
                :key="d.ID_CLASIFICACION_DOCENTE"
                class="inline-flex items-center px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium"
              >
                {{ d.NOMBRE_DOCENTE }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              v-if="doc.NOMBRE_ARCHIVO"
              @click="verArchivo"
              :disabled="abriendoPdf"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-800 hover:bg-blue-900 text-white text-sm font-semibold rounded-lg disabled:opacity-60"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
              </svg>
              {{ abriendoPdf ? 'Abriendo...' : 'Ver PDF' }}
            </button>
            <button @click="$emit('cerrar')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Aviso: títulos de otros docentes (este form solo edita el del docente principal) -->
        <div v-if="otrosTitulos.length" class="mx-6 mt-3 flex items-start gap-2 p-2.5 bg-purple-50 border border-purple-200 rounded-lg text-purple-700 text-[12px]">
          <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <span>
            También hay título(s) registrados para otro(s) docente(s) de este documento (no editables desde aquí):
            <strong v-for="(t, i) in otrosTitulos" :key="i">
              {{ t.docente }} ({{ t.TIPO_TITULO }})<template v-if="i < otrosTitulos.length - 1">, </template>
            </strong>
          </span>
        </div>

        <!-- Contenido -->
        <div class="flex-1 overflow-y-auto px-6 py-4">
          <div v-if="cargando" class="text-center py-16 text-sm text-gray-400">
            <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Cargando información completa del documento...
          </div>

          <div v-else-if="errorCarga" class="text-center py-16 text-sm text-red-500">
            {{ errorCarga }}
          </div>

          <ClasificacionForm
            v-else
            :saving="guardando"
            :error="errorGuardar"
            :archivo-nombre="doc.NOMBRE_ARCHIVO"
            :reutilizando-archivo="true"
            :initial="initial"
            @guardar="onGuardar"
            @back="$emit('cerrar')"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ClasificacionForm from './ClasificacionForm.vue'
import { useDocumentos } from '../composables/useDocumentos.js'

const props = defineProps({
  doc: { type: Object, required: true }, // fila de `documentos` (ver useDocumentos)
})
const emit = defineEmits(['cerrar', 'guardado'])

// Instancia propia: no necesita compartir el listado general de la vista.
const { clasificacion, obtenerCompleto } = useDocumentos()

const cargando = ref(true)
const errorCarga = ref(null)
const initial = ref(null)
const otrosTitulos = ref([])
const idClasificacionPrincipal = ref(null)

const guardando = ref(false)
const errorGuardar = ref('')

const abriendoPdf = ref(false)

async function verArchivo() {
  if (abriendoPdf.value) return
  abriendoPdf.value = true
  try {
    await clasificacion.verPdf(props.doc.ID_DOCUMENTO, 'inline')
  } catch (e) {
    alert('No se pudo abrir el PDF')
  } finally {
    abriendoPdf.value = false
  }
}

async function cargar() {
  cargando.value = true
  errorCarga.value = null
  try {
    const resultado = await obtenerCompleto(props.doc)
    initial.value = resultado.initial
    otrosTitulos.value = resultado.otrosTitulos
    idClasificacionPrincipal.value = resultado.idClasificacionPrincipal
  } catch (e) {
    console.error('Error al cargar documento completo:', e)
    errorCarga.value = 'No se pudo cargar la información completa del documento'
  } finally {
    cargando.value = false
  }
}

// ClasificacionForm emite: guardar(payload, asignaAGrupos, irAAsignarExtra)
async function onGuardar(payload, asignaAGrupos) {
    guardando.value = true
    errorGuardar.value = ''
    try {
        await intentarGuardar(payload, asignaAGrupos, false)
    } catch (e) {
        if (e.message === 'confirmar_desvinculacion') {
            const nombres = e.docentesADesvincular.map(d => d.nombre || d.cod_docente).join(', ')
            const confirmar = confirm(
                `${e.mensaje}\n\nDocente(s): ${nombres}\n\n¿Continuar?`
            )
            if (confirmar) {
                try {
                    await intentarGuardar(payload, asignaAGrupos, true)
                } catch (e2) {
                    // 👈 el reintento también puede fallar (otro 409, validación,
                    // red, lo que sea). Antes esto quedaba como promesa
                    // rechazada sin capturar y el modal se quedaba "mudo".
                    errorGuardar.value = mensajeDeError(e2)
                }
            } else {
                errorGuardar.value = 'Guardado cancelado.'
            }
        } else {
            errorGuardar.value = mensajeDeError(e)
        }
    } finally {
        guardando.value = false
    }
}

// Centraliza cómo se arma el mensaje de error, para no duplicar lógica
// entre el intento normal y el reintento tras confirmar_desvinculacion.
function mensajeDeError(e) {
    if (e?.message === 'confirmar_desvinculacion') {
        // Caso raro: el backend volvió a pedir confirmación incluso mandando
        // confirmar_desvinculacion=1. No debería pasar, pero si pasa, que
        // el mensaje sea entendible en vez de "[object Object]" o similar.
        return e.mensaje || 'El servidor volvió a pedir confirmación de desvinculación.'
    }
    return clasificacion.error.value
        || e?.response?.data?.error
        || e?.message
        || 'No se pudo guardar los cambios'
}

async function intentarGuardar(payload, asignaAGrupos, confirmarDesvinculacion) {
    const resultado = await clasificacion.actualizarClasificacion(
        idClasificacionPrincipal.value, payload, confirmarDesvinculacion
    )
    if (asignaAGrupos) {
        try {
            await clasificacion.aplicarEnGrupos(resultado.id_documento ?? props.doc.ID_DOCUMENTO)
        } catch (e) {
            console.error('El documento se guardó, pero falló al aplicar en GRUPOS:', e)
        }
    }
    emit('guardado')
}

onMounted(cargar)
</script>