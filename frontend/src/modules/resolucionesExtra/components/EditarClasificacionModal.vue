<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50" @click="intentarCerrar"></div>

    <!-- Modal -->
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">

      <!-- Header -->
      <div class="bg-slate-900 px-5 py-3 flex items-center justify-between flex-shrink-0">
        <div class="min-w-0">
          <h3 class="text-[15px] font-semibold text-white">Editar clasificación</h3>
          <p v-if="datosIniciales" class="text-[12px] text-slate-300 truncate">
            {{ datosIniciales.nombreDocente }} · Documento #{{ datosIniciales.idDocumento }}
          </p>
        </div>
        <button type="button" @click="intentarCerrar" class="text-slate-300 hover:text-white flex-shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 overflow-y-auto">

        <!-- Cargando -->
        <div v-if="cargando" class="text-center py-12 text-sm text-gray-400">
          <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Cargando clasificación...
        </div>

        <!-- Error de carga -->
        <div v-else-if="errorCarga" class="p-8 text-center text-sm text-red-500">
          {{ errorCarga }}
        </div>

        <!-- Éxito -->
        <div v-else-if="successMessage" class="p-6 text-center">
          <div class="w-14 h-14 mx-auto rounded-full bg-green-50 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <p class="text-[15px] font-semibold text-gray-900">{{ successMessage }}</p>

          <p v-if="aplicadoAGrupos === true" class="text-[13px] text-green-600 mt-2 font-medium">
            ✓ Cambios aplicados en GRUPOS correctamente.
          </p>
          <p v-else-if="aplicadoAGrupos === false" class="text-[13px] text-amber-600 mt-2 font-medium">
            ⚠️ Se guardó la edición, pero no se pudo actualizar en GRUPOS.
          </p>

          <button
            @click="$emit('guardado')"
            class="mt-6 inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-[14px] font-medium rounded-lg transition-colors"
          >
            Cerrar
          </button>
        </div>

        <!-- Formulario -->
        <ClasificacionForm
          v-else
          :initial="datosIniciales"
          :saving="clasificacion.loading.value"
          :error="clasificacion.error.value"
          :archivo-nombre="nombreArchivoActual"
          @guardar="onActualizar"
          @back="intentarCerrar"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ClasificacionForm from './ClasificacionForm.vue'
import { useClasificacion } from '../composables/useClasificacion'

const props = defineProps({
  id: { type: [String, Number], required: true }, // ID_CLASIFICACION_DOCENTE
})

const emit = defineEmits(['cerrar', 'guardado'])

const clasificacion = useClasificacion()

const cargando = ref(true)
const errorCarga = ref(null)
const datosIniciales = ref(null)
const nombreArchivoActual = ref('')

const successMessage = ref('')
const aplicadoAGrupos = ref(null)

const materiasOriginales = ref([])
const huboCambiosGuardados = ref(false)

function mapearDatos(data) {
  const materias = (data.materias || []).map(m => ({
    id_detalle: m.ID_DETALLE,
    cod_materia: m.COD_MATERIA,
    nombre_materia: m.NOMBRE_MATERIA,
    cod_plan: m.COD_PLAN,
    grupo: m.GRUPO,
    nota: m.NOTA,
    detalle: m.DETALLE,
    docente: {
      cod_docente: data.COD_DOCENTE,
      apellidos: data.APELLIDOS,
      nombres: data.NOMBRES,
    },
  }))

  const t = data.titulos?.[0] ?? null
  const titulo = t ? {
    tipo_titulo: t.TIPO_TITULO,
    nombre_titulo: t.NOMBRE_TITULO,
    universidad: t.UNIVERSIDAD,
    pais: t.PAIS,
    fecha_titulo: t.FECHA_TITULO,
    numero: t.NUMERO,
    cod_docente: data.COD_DOCENTE,
  } : null

  const referencias = (data.referencias || []).map(r => ({
    id_ref: r.ID_REF,
    nro_referencia: r.NRO_REFERENCIA,
    id_resolucion: r.ID_RESOLUCION,
  }))

  return {
    cod_docente: data.COD_DOCENTE,
    categoria: data.CATEGORIA,
    nivel: data.NIVEL,
    gestion: data.GESTION,
    periodo: data.PERIODO,
    tipo_documento: data.TIPO_DOCUMENTO,
    detalle_general: data.DETALLE_GENERAL,
    observacion: data.OBSERVACION,
    observacion2: data.OBSERVACION2,
    materias,
    referencias,
    titulo,
    idDocumento: data.ID_DOCUMENTO,
    codDocente: data.COD_DOCENTE,
    nombreDocente: `${data.APELLIDOS ?? ''} ${data.NOMBRES ?? ''}`.trim() || data.NOMBRE_DOCENTE,
  }
}

onMounted(async () => {
  try {
    const data = await clasificacion.obtener(props.id)
    datosIniciales.value = mapearDatos(data)
    nombreArchivoActual.value = data.NOMBRE_ARCHIVO || ''
    materiasOriginales.value = data.materias || []
  } catch (e) {
    errorCarga.value = clasificacion.error.value || 'No se pudo cargar la clasificación'
  } finally {
    cargando.value = false
  }
})

async function onActualizar(formData, debeAplicarAGrupos) {
  const idDocumento = datosIniciales.value.idDocumento

  try {
    const idsMateriaViejas = materiasOriginales.value
      .filter(m => m.COD_MATERIA)
      .map(m => m.ID_DETALLE)

    if (idsMateriaViejas.length) {
      try {
        await clasificacion.quitarDeGrupos(idDocumento, idsMateriaViejas)
      } catch (e) {
        console.warn('No se pudo limpiar GRUPOS antes de editar:', e)
      }
    }

    await clasificacion.actualizarClasificacion(props.id, formData)
    huboCambiosGuardados.value = true

    if (debeAplicarAGrupos) {
      try {
        const resGrupos = await clasificacion.aplicarEnGrupos(idDocumento)
        aplicadoAGrupos.value = resGrupos.filas_afectadas > 0
      } catch (e) {
        aplicadoAGrupos.value = false
      }
    } else {
      aplicadoAGrupos.value = null
    }

    successMessage.value = 'Clasificación actualizada correctamente'
  } catch {
    // error visible vía :error en ClasificacionForm
  }
}

function intentarCerrar() {
  emit(huboCambiosGuardados.value ? 'guardado' : 'cerrar')
}
</script>