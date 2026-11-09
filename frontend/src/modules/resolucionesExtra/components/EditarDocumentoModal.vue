<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="$emit('cerrar')">
      <div
        class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[92vh] flex flex-col border-t-4 transition-colors"
        :class="modo === 'copia' ? 'border-emerald-500' : 'border-blue-500'"
      >

        <!-- Header -->
        <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100">
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide" :class="modo === 'copia' ? 'text-emerald-600' : 'text-blue-500'">
              {{ modo === 'copia' ? 'Registrar copia con el mismo PDF' : 'Editar documento completo' }}
            </p>
            <h3 class="text-lg font-semibold text-gray-900 truncate flex items-center gap-2">
              <span class="truncate">{{ doc.TIPO_DOCUMENTO || 'Documento' }}</span>
              <span
                v-if="modo === 'copia'"
                class="flex-shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-emerald-100 text-emerald-700"
              >
                Nuevo registro
              </span>
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
              <span v-if="modo === 'copia' && doc.docentes?.length" class="text-[11px] text-gray-400">
                Docentes del original:
              </span>
              <span
                v-for="d in doc.docentes"
                :key="d.ID_CLASIFICACION_DOCENTE"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="modo === 'copia' ? 'bg-gray-100 text-gray-500' : 'bg-indigo-50 text-indigo-700'"
              >
                {{ d.NOMBRE_DOCENTE }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              v-if="doc.NOMBRE_ARCHIVO"
              type="button"
              @click="verArchivo"
              :disabled="abriendoPdf"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-800 hover:bg-blue-900 text-white text-sm font-semibold rounded-lg disabled:opacity-60"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
              </svg>
              {{ abriendoPdf ? 'Abriendo...' : 'Ver PDF' }}
            </button>
            <button type="button" @click="$emit('cerrar')" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Pestañas de modo (solo si el documento tiene PDF que reutilizar) -->
        <div v-if="doc.NOMBRE_ARCHIVO && !cargando && !errorCarga && !avisoGrupos" class="px-6 pt-3 flex flex-wrap gap-2">
          <button
            type="button"
            @click="cambiarModo('editar')"
            :disabled="guardando"
            class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
            :class="modo === 'editar' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
          >
            Editar este documento
          </button>
          <button
            type="button"
            @click="cambiarModo('copia')"
            :disabled="guardando"
            class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
            :class="modo === 'copia' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
          >
            + Registrar copia (mismo PDF) para otro docente / tipo
          </button>
        </div>

        <!-- Explicación del modo copia -->
        <div v-if="modo === 'copia' && !avisoGrupos" class="mx-6 mt-3 p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-[12px]">
          Se creará un <strong>documento nuevo</strong> reutilizando el PDF
          <strong>{{ doc.NOMBRE_ARCHIVO }}</strong> (no se sube ni se duplica el archivo).
          El documento original <strong>no se modifica</strong>. Cambia el docente, la categoría, el tipo
          o las materias como necesites antes de guardar.
          <span v-if="otrosTitulos.length">
            Los títulos de otros docentes del documento original no se copian.
          </span>
          <div class="flex flex-wrap items-center gap-3 mt-2 pt-2 border-t border-emerald-200 text-[11px]">
            <span class="inline-flex items-center gap-1">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Nuevo
            </span>
            <span class="inline-flex items-center gap-1 text-gray-500">
              <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> Del documento original
            </span>
          </div>
        </div>

        <!-- Aviso: títulos de otros docentes (solo en edición) -->
        <div v-if="modo === 'editar' && otrosTitulos.length" class="mx-6 mt-3 flex items-start gap-2 p-2.5 bg-purple-50 border border-purple-200 rounded-lg text-purple-700 text-[12px]">
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
        <div
          class="flex-1 overflow-y-auto px-6 py-4 transition-colors"
          :class="modo === 'copia' ? 'bg-emerald-50/30' : ''"
        >
          <div v-if="cargando" class="text-center py-16 text-sm text-gray-400">
            <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Cargando información completa del documento...
          </div>

          <div v-else-if="errorCarga" class="text-center py-16 text-sm text-red-500">
            {{ errorCarga }}
            <div class="mt-3">
              <button type="button" @click="cargar" class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                Reintentar
              </button>
            </div>
          </div>

          <!-- Guardó el documento, pero falló aplicar en GRUPOS -->
          <div v-else-if="avisoGrupos" class="py-8 max-w-xl mx-auto text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
            <h4 class="text-base font-semibold text-gray-900 mb-1">Guardado, pero con una advertencia</h4>
            <p class="text-sm text-gray-600 mb-2">
              Los datos del documento <strong>sí se guardaron</strong>, pero <strong>no se pudieron aplicar en GRUPOS</strong>.
            </p>
            <p class="text-xs text-red-600 whitespace-pre-line bg-red-50 border border-red-100 rounded-lg p-2.5 mb-4">{{ avisoGrupos }}</p>
            <p class="text-xs text-gray-500 mb-4">Vuelve a intentarlo luego con la opción "Aplicar en grupos" del documento.</p>
            <button type="button" @click="$emit('guardado')" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg">
              Entendido, cerrar
            </button>
          </div>

          <!-- :key="modo" vuelve a montar el form al cambiar de pestaña
               (ClasificacionForm lee `initial` una sola vez al crearse) -->
          <ClasificacionForm
            v-else
            :key="modo"
            :saving="guardando"
            :error="''"
            :archivo-nombre="doc.NOMBRE_ARCHIVO"
            :reutilizando-archivo="modo === 'copia'"
            :modo-copia="modo === 'copia'"
            :initial="modo === 'copia' ? initialCopia : initial"
            @guardar="onGuardar"
            @back="$emit('cerrar')"
          />
        </div>

        <!-- Error visible siempre (fuera del scroll) -->
        <div v-if="errorGuardar" class="mx-6 mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
          <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <p class="flex-1 text-xs text-red-700 whitespace-pre-line">{{ errorGuardar }}</p>
          <button type="button" @click="errorGuardar = ''" class="text-red-400 hover:text-red-600 text-xs">Cerrar</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ClasificacionForm from './ClasificacionForm.vue'
import { useDocumentos } from '../composables/useDocumentos.js'

const props = defineProps({
  doc: { type: Object, required: true }, // fila de `documentos` (ver useDocumentos)
})
const emit = defineEmits(['cerrar', 'guardado'])

// Instancia propia: no comparte el listado general de la vista.
const { clasificacion, obtenerCompleto } = useDocumentos()

// 'editar' = modifica este documento | 'copia' = documento nuevo reutilizando el PDF
const modo = ref('editar')

const cargando = ref(true)
const errorCarga = ref(null)
const initial = ref(null)
const otrosTitulos = ref([])
const idClasificacionPrincipal = ref(null)

const guardando = ref(false)
const errorGuardar = ref('')
const avisoGrupos = ref('')

const abriendoPdf = ref(false)

// Copia profunda de `initial` con las materias marcadas como NUEVAS
// (en la copia ninguna "ya está registrada") y sin título de otros docentes.
const initialCopia = computed(() => {
  if (!initial.value) return null
  const copia = JSON.parse(JSON.stringify(initial.value))
  copia.materias = (copia.materias || []).map(m => ({ ...m, yaRegistrada: false }))
  return copia
})

function cambiarModo(nuevo) {
  if (guardando.value || modo.value === nuevo) return
  modo.value = nuevo
  errorGuardar.value = ''
}

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

function mensajeDeError(e) {
  if (e?.message === 'confirmar_desvinculacion') {
    return e.mensaje || 'El servidor volvió a pedir confirmación de desvinculación.'
  }
  return clasificacion.error.value
    || e?.response?.data?.error
    || e?.message
    || 'No se pudo guardar los cambios'
}

// Intenta aplicar en GRUPOS. Devuelve null si salió bien o un texto si falló.
async function aplicarEnGruposSeguro(idDocumento) {
  try {
    await clasificacion.aplicarEnGrupos(idDocumento)
    return null
  } catch (e) {
    console.error('El documento se guardó, pero falló al aplicar en GRUPOS:', e)
    return clasificacion.error.value || e?.message || 'Error desconocido al aplicar en GRUPOS'
  }
}

// ClasificacionForm emite: guardar(payload, asignaAGrupos, irAAsignarExtra)
async function onGuardar(payload, asignaAGrupos) {
  if (guardando.value) return
  guardando.value = true
  errorGuardar.value = ''
  try {
    if (modo.value === 'copia') {
      await guardarComoCopia(payload, asignaAGrupos)
    } else {
      await guardarEdicion(payload, asignaAGrupos)
    }
  } catch (e) {
    if (e?.message === 'confirmar_desvinculacion' && modo.value === 'editar') {
      await manejarDesvinculacion(e, payload, asignaAGrupos)
    } else {
      errorGuardar.value = mensajeDeError(e)
    }
  } finally {
    guardando.value = false
  }
}

async function manejarDesvinculacion(e, payload, asignaAGrupos) {
  const lista = (e.docentesADesvincular || [])
    .map(d => {
      const nombre = d.nombre || d.cod_docente
      return d.tiene_titulo ? `${nombre} (también se eliminará su título)` : nombre
    })
    .join('\n- ')

  const confirmar = confirm(`${e.mensaje}\n\nDocente(s):\n- ${lista}\n\n¿Continuar?`)
  if (!confirmar) {
    errorGuardar.value = 'Guardado cancelado. No se modificó nada.'
    return
  }
  try {
    await guardarEdicion(payload, asignaAGrupos, true)
  } catch (e2) {
    errorGuardar.value = mensajeDeError(e2)
  }
}

// ── Modo EDITAR ──
async function guardarEdicion(payload, asignaAGrupos, confirmarDesvinculacion = false) {
  const resultado = await clasificacion.actualizarClasificacion(
    idClasificacionPrincipal.value, payload, confirmarDesvinculacion
  )
  const idDoc = resultado.id_documento ?? props.doc.ID_DOCUMENTO

  if (asignaAGrupos) {
    const fallo = await aplicarEnGruposSeguro(idDoc)
    if (fallo) {
      avisoGrupos.value = fallo
      return // no cierra: el usuario ve la advertencia y cierra con el botón
    }
  }

  if (resultado.gestion_o_periodo_cambio && !asignaAGrupos) {
    alert('Cambió la gestión o el periodo: GRUPOS se limpió para la combinación anterior. Recuerda usar "Aplicar en grupos" para la nueva.')
  }
  emit('guardado')
}

// ── Modo COPIA: documento nuevo, reutilizando el PDF (no se vuelve a subir) ──
async function guardarComoCopia(payload, asignaAGrupos) {
  const resultado = await clasificacion.guardarClasificacion({
    ...payload,
    id_documento_origen: props.doc.NOMBRE_ARCHIVO ? props.doc.ID_DOCUMENTO : undefined,
  })

  if (asignaAGrupos) {
    const fallo = await aplicarEnGruposSeguro(resultado.idDocumento)
    if (fallo) {
      avisoGrupos.value = fallo
      return
    }
  }
  emit('guardado')
}

onMounted(cargar)
</script>