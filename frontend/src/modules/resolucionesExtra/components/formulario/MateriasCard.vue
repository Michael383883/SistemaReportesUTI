<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
    <!-- Header oscuro, mismo estilo que "Datos generales" -->
    <div class="bg-slate-900 px-5 py-2 rounded-t-2xl flex items-center justify-between">
      <h3 class="text-[15px] font-semibold text-white">
        Materias
        <span class="text-[12px] font-normal text-gray-400">(opcional)</span>
      </h3>

      <div class="flex items-center gap-3">
        <label class="inline-flex items-center gap-1.5 text-[11px] text-gray-300 cursor-pointer select-none">
          <input
            type="checkbox"
            :checked="noRegentaFCE"
            @change="toggleNoRegenta"
            class="w-3.5 h-3.5 accent-orange-400"
          />
          No regenta materia en la FCE
        </label>

        <button
          type="button"
          class="text-gray-400 hover:text-red-400"
          title="Quitar esta sección"
          @click="onCerrar"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="p-5 space-y-3">
      <BuscadorMaterias
        v-if="!noRegentaFCE"
        :docente="form.cod_docente"
        :gestion="form.gestion"
        :periodo="form.periodo"
        :materiasSeleccionadas="form.materias"
        @agregar-materia="onAgregarMateria"
      />

      <!-- Materias seleccionadas -->
      <div v-if="form.materias.length && !noRegentaFCE" class="flex flex-wrap gap-2 mt-2">
        <div
          v-for="(m, i) in form.materias"
          :key="i"
          class="flex flex-col gap-1.5 px-3 py-2 bg-orange-50 border border-orange-200 rounded-xl text-[12px] text-orange-700 min-w-[230px] font-semibold"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="truncate">
              {{ m.nombre_materia }}
              <span v-if="m.cod_materia" class="text-orange-600 text-[12px]">({{ m.cod_materia }})</span>
              <span v-if="m.grupo" class="text-orange-500 text-[12px] font-semibold ml-1">Grupo {{ m.grupo }}</span>
              <span v-if="!m.cod_materia" class="block text-[10px] text-slate-500 font-normal mt-0.5">
                Sin código · no se aplicará en GRUPOS
              </span>
            </span>

            <button
              @click="form.materias.splice(i, 1)"
              class="text-orange-400 hover:text-red-500 flex-shrink-0"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Nota + botón para agregar observación (va a DETALLE en la BD) -->
          <div class="flex items-center gap-2 flex-wrap">
            <input
              v-model="m.nota"
              type="text"
              inputmode="numeric"
              placeholder="Nota"
              class="w-14 px-1.5 py-0.5 text-[11px] border rounded focus:outline-none focus:ring-1 bg-white text-center font-semibold text-slate-800"
              :class="notaInvalida(m)
                ? 'border-slate-400 focus:ring-red-500'
                : 'border-orange-300 focus:ring-orange-400'"
              @keypress="soloNumeros"
            />

            <button
              v-if="!obsAbierta(i) && !m.detalle"
              type="button"
              class="text-[10px] text-orange-500 hover:text-orange-700 underline"
              @click="abrirObs(i)"
            >
              + Agregar obs
            </button>

            <span
              v-else-if="!obsAbierta(i) && m.detalle"
              class="inline-flex items-center gap-1 text-[10px] text-orange-600"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Obs. agregada
              <button type="button" class="underline hover:text-orange-800" @click="abrirObs(i)">editar</button>
            </span>
          </div>

          <!-- Textarea de observación, ligado a m.detalle -->
          <div v-if="obsAbierta(i)" class="pt-1">
            <textarea
              :ref="el => setObsInputRef(el, i)"
              v-model="m.detalle"
              rows="2"
              placeholder="Observación de esta materia..."
              class="w-full px-2 py-1.5 text-[11px] border border-orange-300 rounded focus:outline-none focus:ring-1 focus:ring-orange-400 resize-none bg-white text-slate-800 font-normal"
              @keydown.esc="cerrarObs(i)"
            ></textarea>
            <div class="flex items-center justify-end gap-2 mt-1">
              <button
                type="button"
                class="text-[10px] text-gray-500 hover:text-gray-700"
                @click="quitarObs(i)"
              >
                Quitar
              </button>
              <button
                type="button"
                class="text-[10px] px-2 py-0.5 bg-orange-500 hover:bg-orange-600 text-white rounded"
                @click="cerrarObs(i)"
              >
                Listo
              </button>
            </div>
          </div>

          <div class="pt-1.5 border-t border-orange-200/70">
            <div v-if="docenteEditIndex !== i" class="flex items-center justify-between gap-2">
              <span class="text-[11px] leading-tight">
                Docente:
                <strong v-if="m.docente">{{ m.docente.apellidos }} {{ m.docente.nombres }}</strong>
                <span v-else class="text-red-500 font-medium">Sin asignar</span>
              </span>
              <button
                type="button"
                class="text-[10px] text-orange-500 hover:text-orange-700 underline flex-shrink-0"
                @mousedown.prevent="abrirEdicionDocente(i)"
              >
                cambiar
              </button>
            </div>

            <div v-else class="relative">
              <input
                ref="inputMateriaDocenteRef"
                v-model="searchQueryMateria"
                type="text"
                placeholder="Buscar docente..."
                class="w-full px-2 py-1 text-[11px] border border-orange-300 rounded focus:outline-none focus:ring-1 focus:ring-orange-400"
                @blur="onBlurMateriaDocente"
                @keydown.esc="cerrarEdicionDocente"
              />
              <div class="absolute z-30 mt-1 w-56 max-h-40 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                <div v-if="loadingDocentesMateria" class="px-2 py-1 text-[11px] text-gray-400">Cargando...</div>
                <div v-else-if="!filteredDocentesMateria.length" class="px-2 py-1 text-[11px] text-gray-400">Sin resultados</div>
                <button
                  v-for="d in filteredDocentesMateria"
                  :key="d.id ?? d.codigo"
                  type="button"
                  class="w-full text-left px-2 py-1 text-[11px] hover:bg-orange-50"
                  @mousedown.prevent="asignarDocenteMateria(i, d)"
                >
                  {{ d.apellidos }} {{ d.nombres }}
                  <span class="text-gray-400">({{ d.codigo }})</span>
                </button>
              </div>
            </div>
          </div>

          <div v-if="notaInvalida(m)" class="text-[12px] text-red-600 font-medium pt-0.5">
             Nota no válida
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, nextTick } from 'vue'
import { useDocentesReportes } from '../../composables/useDocentesReportes'
import BuscadorMaterias from '../BuscadorMaterias.vue'

const props = defineProps({
  form: { type: Object, required: true },
  // Docente "por defecto" del formulario general, usado para pre-asignar
  // a cada materia nueva que se agregue.
  selectedDocente: { type: Object, default: null },
})

const emit = defineEmits(['cerrar'])

const { form } = props

// ─── "No regenta materia en la FCE" ───
const NO_REGENTA_LABEL = 'NO REGENTA MATERIA EN LA FCE'

const noRegentaFCE = computed(() =>
  form.materias.length === 1 &&
  form.materias[0].nombre_materia === NO_REGENTA_LABEL &&
  !form.materias[0].cod_materia
)

function toggleNoRegenta(event) {
  const checked = event.target.checked
  if (checked) {
    form.materias = [{
      cod_materia: null,
      nombre_materia: NO_REGENTA_LABEL,
      cod_plan: null,
      grupo: null,
      nota: null,
      detalle: null,
      docente: null,
    }]
  } else {
    form.materias = []
  }
}

// ─── Manejo de materias ───
function onAgregarMateria(materiaData) {
  const existe = materiaData.cod_materia
    ? form.materias.some(m => m.cod_materia === materiaData.cod_materia)
    : false // las manuales ya se confirmaron en BuscadorMaterias
  if (existe) return

  form.materias.push({
    ...materiaData,
    grupo: materiaData.grupo ?? null,
    detalle: materiaData.detalle ?? null,
    manual: materiaData.manual ?? !materiaData.cod_materia,
    docente: props.selectedDocente
      ? {
          cod_docente: props.selectedDocente.codigo,
          nombres: props.selectedDocente.nombres,
          apellidos: props.selectedDocente.apellidos,
        }
      : null,
  })
}

// ─── Validación de solo números para NOTA ───
function soloNumeros(event) {
  const charCode = event.which ? event.which : event.keyCode
  if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
    event.preventDefault()
  }
}

// Solo muestra el aviso debajo de la card; no bloquea el guardado.
function notaInvalida(m) {
  if (m.nota === null || m.nota === undefined || m.nota === '') return false
  const valor = Number(m.nota)
  if (Number.isNaN(valor)) return false
  return valor > 100
}

// ─── Observación por materia (columna DETALLE en CLASIFICACION_MATERIA) ───
// Guarda qué tarjetas tienen el textarea de observación abierto.
const obsAbiertasIdx = ref(new Set())
const obsInputRefs = ref({})

function obsAbierta(i) {
  return obsAbiertasIdx.value.has(i)
}

function setObsInputRef(el, i) {
  if (el) obsInputRefs.value[i] = el
}

function abrirObs(i) {
  obsAbiertasIdx.value.add(i)
  // forzar reactividad del Set
  obsAbiertasIdx.value = new Set(obsAbiertasIdx.value)
  nextTick(() => obsInputRefs.value[i]?.focus())
}

function cerrarObs(i) {
  obsAbiertasIdx.value.delete(i)
  obsAbiertasIdx.value = new Set(obsAbiertasIdx.value)
}

function quitarObs(i) {
  form.materias[i].detalle = null
  cerrarObs(i)
}

// ─── Buscador de docente POR MATERIA ───
const {
  loading: loadingDocentesMateria,
  searchQuery: searchQueryMateria,
  filteredDocentes: filteredDocentesMateria,
  fetchDocentes: fetchDocentesMateria,
} = useDocentesReportes()

fetchDocentesMateria()

const docenteEditIndex = ref(null)
const inputMateriaDocenteRef = ref(null)

function abrirEdicionDocente(i) {
  docenteEditIndex.value = i
  searchQueryMateria.value = ''
  nextTick(() => {
    const el = Array.isArray(inputMateriaDocenteRef.value)
      ? inputMateriaDocenteRef.value[0]
      : inputMateriaDocenteRef.value
    el?.focus()
  })
}

function cerrarEdicionDocente() {
  docenteEditIndex.value = null
}

function onBlurMateriaDocente() {
  setTimeout(() => { docenteEditIndex.value = null }, 150)
}

function asignarDocenteMateria(i, docente) {
  form.materias[i].docente = {
    cod_docente: docente.codigo,
    nombres: docente.nombres,
    apellidos: docente.apellidos,
  }
  docenteEditIndex.value = null
}

// El botón "x" de la cabecera solo se muestra si aún no hay materias
// cargadas; si ya hay datos, cerrar la sección los vacía primero.
function onCerrar() {
  if (form.materias.length && !confirm('¿Quitar esta sección? Se perderán las materias agregadas.')) return
  form.materias = []
  emit('cerrar')
}
</script>