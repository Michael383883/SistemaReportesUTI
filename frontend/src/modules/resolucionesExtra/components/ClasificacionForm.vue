<template>
  <div class="space-y-3">

    <!-- Aviso cuando se está reutilizando el PDF de otro documento (alta, no edición) -->
    <div v-if="reutilizandoArchivo" class="flex items-center gap-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-[12px]">
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Se reutilizará el PDF ya subido <strong>({{ archivoNombre }})</strong> — no se volverá a almacenar el archivo, solo esta nueva clasificación.
    </div>

    <DatosGeneralesCard
      :form="form"
      :loading-docentes="loadingDocentes"
      :filtered-docentes="filteredDocentes"
      :selected-docente="docenteActual"
      v-model:search-query="searchQuery"
      v-model:dropdown-open="dropdownOpen"
      @select-docente="onSelectDocente"
      @clear-docente="onClearDocente"
    />

    <!-- Botones para agregar las secciones opcionales -->
    <div class="flex flex-wrap gap-2">
      <button
        v-if="!mostrarMaterias"
        type="button"
        @click="abrirMaterias"
        class="bg-amber-600 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-bold text-slate-100 border border-dashed border-amber-500 rounded-lg hover:bg-amber-500 hover:text-slate-100 transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar materia
      </button>

      <button
        v-if="!mostrarReferencias"
        type="button"
        @click="abrirReferencias"
        class="bg-amber-600 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-bold text-slate-100 border border-dashed border-amber-400 rounded-lg hover:bg-amber-500 hover:text-slate-100 transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar referencia
      </button>

      <button
        v-if="!esTitulo"
        type="button"
        @click="abrirTitulo"
        class="bg-amber-600 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-bold text-slate-100 border border-dashed border-amber-500 rounded-lg hover:bg-amber-500 hover:text-slate-100 transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar título
      </button>
    </div>

    <!-- Aviso: falta Categoria/Gestión/Periodo -->
    <div v-if="avisoValidacion" class="flex items-center gap-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-[12px]">
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      {{ avisoValidacion }}
    </div>

    <TituloCard
      v-if="esTitulo"
      :form="form"
      :selected-docente="docenteActual"
      @cerrar="onCerrarTitulo"
    />

    <MateriasCard
      v-if="mostrarMaterias"
      :form="form"
      :selected-docente="docenteActual"
      @cerrar="mostrarMaterias = false"
    />

    <ReferenciasCard
      v-if="mostrarReferencias"
      :form="form"
      @cerrar="mostrarReferencias = false"
    />

    <!-- Error -->
    <div v-if="error" class="flex items-center gap-2 p-2.5 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[12px]">
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      {{ error }}
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200">
      <button
        type="button"
        @click="$emit('back')"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium text-slate-600 hover:text-slate-800 rounded-lg transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>

      <button
        type="button"
        :disabled="saving || !esValidoGenerales"
        @click="mostrarPreview = true"
        class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors"
      >
        <svg v-if="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Guardar clasificación
      </button>
    </div>

    <ClasificacionPreviewModal
      v-if="mostrarPreview"
      :form="form"
      :nombre-docente="docenteActual ? `${docenteActual.apellidos} ${docenteActual.nombres}` : ''"
      :archivo-nombre="archivoNombre"
      :asigna-a-grupos="asignaAGrupos"
      :saving="saving"
      @cerrar="mostrarPreview = false"
      @confirmar="onConfirmarGuardar"
      @confirmar-y-asignar="onConfirmarGuardarYAsignar"
    />

  </div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { useDocentesReportes } from '../composables/useDocentesReportes'
import DatosGeneralesCard from './formulario/DatosGeneralesCard.vue'
import MateriasCard from './formulario/MateriasCard.vue'
import ReferenciasCard from './formulario/ReferenciasCard.vue'
import TituloCard from './formulario/TituloCard.vue'
import ClasificacionPreviewModal from './ClasificacionPreviewModal.vue'

const props = defineProps({
  saving:              { type: Boolean, default: false },
  error:               { type: String, default: '' },
  archivoNombre:       { type: String, default: '' },
  reutilizandoArchivo: { type: Boolean, default: false },
  initial:             { type: Object, default: () => ({}) },
})

const emit = defineEmits(['guardar', 'back'])

// `initial` puede llegar null si el padre aún no cargó: se protege.
const initial = props.initial ?? {}

const mostrarPreview = ref(false)

// ─── Formulario (fuente única de verdad, compartida por las cards) ───
const form = reactive({
  cod_docente:     initial.cod_docente     ?? null,
  categoria:       initial.categoria       ?? '',
  nivel:           initial.nivel           ?? '',
  gestion:         initial.gestion         ?? '',
  periodo:         initial.periodo         ?? '',
  tipo_documento:  initial.tipo_documento  ?? null,
  detalle_general: initial.detalle_general ?? '',
  observacion:     initial.observacion     ?? '',
  observacion2:    initial.observacion2    ?? '',
  materias:        initial.materias        ?? [],
  referencias:     initial.referencias     ?? [],
  titulo:          initial.titulo          ?? null,
})
const esTitulo = ref(!!form.titulo)

// ─── Búsqueda del docente general (docente "por defecto") ───
const {
  loading: loadingDocentes,
  searchQuery,
  dropdownOpen,
  filteredDocentes,
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection,
} = useDocentesReportes()

fetchDocentes()

// FIX: en edición, `selectedDocente` empieza vacío aunque el documento ya
// tenga docente. Se usa el docente principal que viene en `initial` como
// respaldo mientras el usuario no elija otro (y solo si sigue siendo el mismo).
const docenteActual = computed(() => {
  if (selectedDocente.value) return selectedDocente.value
  const p = initial.docente_principal
  if (p && form.cod_docente != null && String(p.codigo) === String(form.cod_docente)) {
    return p
  }
  return null
})

function onSelectDocente(docente) {
  selectDocente(docente)
  form.cod_docente = docente.codigo
}

function onClearDocente() {
  clearSelection()
  form.cod_docente = null
}

function onCerrarTitulo() {
  esTitulo.value = false
  form.titulo = null
}

// ─── Visibilidad de las cards opcionales ───
const mostrarMaterias    = ref(form.materias.length > 0)
const mostrarReferencias = ref(form.referencias.length > 0)

// ─── Aviso cuando falta Categoria/Gestión/Periodo ───
const avisoValidacion = ref('')

function requiereGestionPeriodo() {
  if (!form.gestion || !form.periodo || !form.categoria) {
    avisoValidacion.value = 'Debes completar Categoria, Gestión y Periodo antes de agregar materia, referencia o título.'
    return false
  }
  avisoValidacion.value = ''
  return true
}

function abrirMaterias() {
  if (!requiereGestionPeriodo()) return
  mostrarMaterias.value = true
}

function abrirReferencias() {
  if (!requiereGestionPeriodo()) return
  mostrarReferencias.value = true
}

function abrirTitulo() {
  if (!requiereGestionPeriodo()) return
  esTitulo.value = true
}

// ─── Validación: el botón de guardar depende de Categoria, Gestión y Periodo ───
const esValidoGenerales = computed(() =>
  !!(form.categoria && form.gestion && form.periodo)
)

// ─── "No regenta materia en la FCE" (derivado de form.materias) ───
const noRegentaFCE = computed(() =>
  form.materias.length === 1 &&
  !form.materias[0].cod_materia &&
  (form.materias[0].esNoRegenta === true ||
    form.materias[0].nombre_materia === 'NO REGENTA MATERIA EN LA FCE')
)

// ¿Tras guardar corresponde llamar a aplicarEnGrupos()?
// Un documento de título nunca cruza contra GRUPOS.
const asignaAGrupos = computed(() => {
  if (esTitulo.value) return false
  if (noRegentaFCE.value) return false
  if (form.materias.length === 0) return false
  return form.materias.some(m => m.cod_materia && m.cod_plan && m.grupo)
})

function formCopiado() {
  const copia = JSON.parse(JSON.stringify(form))
  // El docente del título se resuelve aquí, tomando siempre el docente general vigente
  if (esTitulo.value && copia.titulo) {
    copia.titulo.cod_docente = form.cod_docente
  } else {
    copia.titulo = null
  }
  return copia
}

function armarPayload() {
  const payload = formCopiado()
  // Nombre del docente general (no viaja en form) como fallback del filtro del listado.
  payload.nombre_docente_general = docenteActual.value
    ? `${docenteActual.value.apellidos} ${docenteActual.value.nombres}`
    : ''
  return payload
}

// ─── Confirmación desde el preview ───
// FIX: se cierra el preview ANTES de emitir; si no, los errores, el
// "Guardado cancelado" y el confirm de desvinculación quedaban escondidos detrás.
function onConfirmarGuardar() {
  mostrarPreview.value = false
  emit('guardar', armarPayload(), asignaAGrupos.value)
}

function onConfirmarGuardarYAsignar() {
  mostrarPreview.value = false
  emit('guardar', armarPayload(), asignaAGrupos.value, true) // 3er parámetro = ir a asignar extra
}
</script>