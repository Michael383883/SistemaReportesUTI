<template>
  <div class="space-y-3">

    <DatosGeneralesCard
      :form="form"
      :loading-docentes="loadingDocentes"
      :filtered-docentes="filteredDocentes"
      :selected-docente="selectedDocente"
      v-model:search-query="searchQuery"
      v-model:dropdown-open="dropdownOpen"
      @select-docente="onSelectDocente"
      @clear-docente="onClearDocente"
    />

    <!-- Botones para agregar las secciones opcionales -->
    <div v-if="esValidoGenerales" class="flex flex-wrap gap-2">
      <button
        v-if="!mostrarMaterias"
        type="button"
        @click="mostrarMaterias = true"
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
        @click="mostrarReferencias = true"
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
  @click="esTitulo = true"
  class="bg-amber-600 inline-flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-bold text-slate-100 border border-dashed border-amber-500 rounded-lg hover:bg-amber-500 hover:text-slate-100 transition-colors"
>
  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
  </svg>
  Agregar título
</button>
</div>

    <TituloCard
  v-if="esTitulo"
  :form="form"
  :selected-docente="selectedDocente"
  @cerrar="onCerrarTitulo"
/>

    <MateriasCard
      v-if="mostrarMaterias"
      :form="form"
      :selected-docente="selectedDocente"
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
        @click="$emit('back')"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-[13px] font-medium text-slate-600 hover:text-slate-800 rounded-lg transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>

      <button
        :disabled="saving || !esValidoGenerales"
        @click="$emit('guardar', formCopiado(), asignaAGrupos)"
        class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors"
      >
        <svg v-if="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Guardar clasificación
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { useDocentesReportes } from '../composables/useDocentesReportes'
import DatosGeneralesCard from './formulario/DatosGeneralesCard.vue'
import MateriasCard from './formulario/MateriasCard.vue'
import ReferenciasCard from './formulario/ReferenciasCard.vue'
import TituloCard from './formulario/TituloCard.vue'

const props = defineProps({
  saving:        { type: Boolean, default: false },
  error:         { type: String, default: '' },
  archivoNombre: { type: String, default: '' },
  initial:       { type: Object, default: () => ({}) },
})

const emit = defineEmits(['guardar', 'back'])

// ─── Formulario (fuente única de verdad, compartida por las cards) ───
const form = reactive({
  cod_docente:     props.initial.cod_docente     ?? null,
  categoria:       props.initial.categoria       ?? '',
  nivel:           props.initial.nivel           ?? '',
  gestion:         props.initial.gestion         ?? '',
  periodo:         props.initial.periodo         ?? '',
  tipo_documento:  props.initial.tipo_documento  ?? null,
  detalle_general: props.initial.detalle_general ?? '',
  observacion:     props.initial.observacion     ?? '',
  observacion2:    props.initial.observacion2    ?? '',
  materias:        props.initial.materias        ?? [],
  referencias:     props.initial.referencias     ?? [],
  titulo:         props.initial.titulo         ?? null,
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

function onSelectDocente(docente) {
  selectDocente(docente)
  form.cod_docente = docente.codigo
}

function onCerrarTitulo() {
  esTitulo.value = false
  form.titulo = null
}


function onClearDocente() {
  clearSelection()
  form.cod_docente = null
}

// ─── Visibilidad de las cards opcionales ───
const mostrarMaterias    = ref(form.materias.length > 0)
const mostrarReferencias = ref(form.referencias.length > 0)
const mostrarTitulos = ref(!!form.titulo)

// ─── Validación: el botón de guardar solo depende de la card 1 completa ───
const esValidoGenerales = computed(() =>
  !!(form.tipo_documento && form.categoria && form.gestion)
)

// ─── "No regenta materia en la FCE" (derivado de form.materias) ───
const noRegentaFCE = computed(() =>
  form.materias.length === 1 &&
  form.materias[0].nombre_materia === 'NO REGENTA MATERIA EN LA FCE' &&
  !form.materias[0].cod_materia
)

// Determina si, tras guardar, corresponde llamar a aplicarEnGrupos().
// Un documento de título nunca cruza contra GRUPOS.
const asignaAGrupos = computed(() => {
 if (esTitulo.value) return false
  if (noRegentaFCE.value) return false
  if (form.materias.length === 0) return false
  return form.materias.every(m => m.cod_materia && m.cod_plan && m.grupo)
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

</script>