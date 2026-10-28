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
          v-for="m in form.materias"
          :key="keyDe(m)"
          class="flex flex-col gap-1.5 px-3 py-2 bg-orange-50 border border-orange-200 rounded-xl text-[12px] text-orange-700 min-w-[230px] font-semibold"
        >
          <div class="flex items-center justify-between gap-2">
            <!-- Nombre de la materia: editable SOLO si no tiene código (manual) -->
            <div class="flex-1 min-w-0">
              <template v-if="nombreEditKey === keyDe(m)">
                <input
                  :ref="el => setNombreInputRef(el, keyDe(m))"
                  v-model="nombreEditValor"
                  type="text"
                  class="w-full px-1.5 py-0.5 text-[12px] border border-orange-400 rounded focus:outline-none focus:ring-1 focus:ring-orange-500 bg-white text-slate-800 font-semibold"
                  @keydown.enter.prevent="confirmarEdicionNombre(m)"
                  @keydown.esc="cancelarEdicionNombre"
                  @blur="confirmarEdicionNombre(m)"
                />
              </template>
              <template v-else>
                <span class="truncate">
                  {{ m.nombre_materia }}
                  <span v-if="m.cod_materia" class="text-orange-600 text-[12px]">({{ m.cod_materia }})</span>
                  <span v-if="m.grupo" class="text-orange-500 text-[12px] font-semibold ml-1">Grupo {{ m.grupo }}</span>
                </span>
                <span v-if="!m.cod_materia" class="flex items-center gap-1.5 mt-0.5">
                  <span class="text-[10px] text-slate-500 font-normal">
                    Sin código · no se aplicará en GRUPOS
                  </span>
                  <button
                    type="button"
                    class="text-[10px] text-orange-500 hover:text-orange-700 underline flex-shrink-0"
                    @click="abrirEdicionNombre(m)"
                  >
                    editar nombre
                  </button>
                </span>
              </template>
            </div>

            <button
              type="button"
              @click="quitarMateria(m)"
              class="text-orange-400 hover:text-red-500 flex-shrink-0"
              title="Quitar materia"
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
              v-if="!obsAbierta(m) && !m.detalle"
              type="button"
              class="text-[10px] text-orange-500 hover:text-orange-700 underline"
              @click="abrirObs(m)"
            >
              + Agregar obs
            </button>

            <span
              v-else-if="!obsAbierta(m) && m.detalle"
              class="inline-flex items-center gap-1 text-[10px] text-orange-600"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Obs. agregada
              <button type="button" class="underline hover:text-orange-800" @click="abrirObs(m)">editar</button>
            </span>
          </div>

          <!-- Textarea de observación, ligado a m.detalle -->
          <div v-if="obsAbierta(m)" class="pt-1">
            <textarea
              :ref="el => setObsInputRef(el, keyDe(m))"
              v-model="m.detalle"
              rows="2"
              placeholder="Observación de esta materia..."
              class="w-full px-2 py-1.5 text-[11px] border border-orange-300 rounded focus:outline-none focus:ring-1 focus:ring-orange-400 resize-none bg-white text-slate-800 font-normal"
              @keydown.esc="cerrarObs(m)"
            ></textarea>
            <div class="flex items-center justify-end gap-2 mt-1">
              <button
                type="button"
                class="text-[10px] text-gray-500 hover:text-gray-700"
                @click="quitarObs(m)"
              >
                Quitar
              </button>
              <button
                type="button"
                class="text-[10px] px-2 py-0.5 bg-orange-500 hover:bg-orange-600 text-white rounded"
                @click="cerrarObs(m)"
              >
                Listo
              </button>
            </div>
          </div>

          <!-- Docente asignado (solo lectura). Para quitar la materia se usa la "X" de arriba. -->
          <div class="pt-1.5 border-t border-orange-200/70">
            <span class="text-[11px] leading-tight">
              Docente:
              <strong v-if="m.docente">{{ m.docente.apellidos }} {{ m.docente.nombres }}</strong>
              <span v-else class="text-red-500 font-medium">Sin asignar</span>
            </span>
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
import { computed, ref, nextTick, toRaw } from 'vue'
import BuscadorMaterias from '../BuscadorMaterias.vue'

const props = defineProps({
  form: { type: Object, required: true },
  // Docente "por defecto" del formulario general, usado para pre-asignar
  // a cada materia nueva que se agregue.
  selectedDocente: { type: Object, default: null },
})

const emit = defineEmits(['cerrar'])

const form = props.form // objeto reactive compartido; se muta directamente

// ─── Identificador estable por materia ───
// FIX: antes el estado de "obs abierta" / "editar nombre" y el :key usaban el
// ÍNDICE. Al quitar una materia, los índices se corrían y el estado (y los
// inputs) quedaban pegados a otra materia. Ahora cada materia tiene un id
// propio (no se guarda en el objeto, así no viaja en el payload).
const uids = new WeakMap()
let contadorUid = 0
function keyDe(m) {
  const raw = toRaw(m)
  if (!uids.has(raw)) uids.set(raw, ++contadorUid)
  return uids.get(raw)
}

function indiceDe(m) {
  const raw = toRaw(m)
  return form.materias.findIndex(x => toRaw(x) === raw)
}

// ─── "No regenta materia en la FCE" ───
const NO_REGENTA_LABEL = 'NO REGENTA MATERIA EN LA FCE'

// Depende de la bandera `esNoRegenta` (la pone el checkbox al crear, y
// useDocumentos al cargar un documento existente en edición).
const noRegentaFCE = computed(() =>
  form.materias.length === 1 &&
  form.materias[0].esNoRegenta === true
)

function toggleNoRegenta(event) {
  const checked = event.target.checked
  limpiarEstadoUI()
  if (checked) {
    form.materias = [{
      cod_materia: null,
      nombre_materia: NO_REGENTA_LABEL,
      cod_plan: null,
      grupo: null,
      nota: null,
      detalle: null,
      docente: null,
      esNoRegenta: true,
    }]
  } else {
    form.materias = []
  }
}

// ─── Manejo de materias ───
// Duplicado = misma materia + grupo + plan (dos grupos distintos son entradas distintas).
function onAgregarMateria(materiaData) {
  const existe = materiaData.cod_materia
    ? form.materias.some(
        m =>
          m.cod_materia === materiaData.cod_materia &&
          (m.grupo ?? null) === (materiaData.grupo ?? null) &&
          (m.cod_plan ?? null) === (materiaData.cod_plan ?? null)
      )
    : false
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
      : (form.cod_docente ? { cod_docente: form.cod_docente, nombres: '', apellidos: '' } : null),
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

// ─── Quitar una materia de la lista ───
// Si ya estaba registrada en el documento (`yaRegistrada`), pide confirmación.
function quitarMateria(m) {
  if (m?.yaRegistrada) {
    const ok = confirm('Esta materia ya está registrada en el documento. ¿Seguro que deseas eliminarla?')
    if (!ok) return
  }
  const i = indiceDe(m)
  if (i === -1) return

  // Limpia el estado de UI asociado a esta materia antes de sacarla
  const k = keyDe(m)
  if (nombreEditKey.value === k) nombreEditKey.value = null
  if (obsAbiertas.value.has(k)) {
    const nuevo = new Set(obsAbiertas.value)
    nuevo.delete(k)
    obsAbiertas.value = nuevo
  }
  delete nombreInputRefs[k]
  delete obsInputRefs[k]

  form.materias.splice(i, 1)
}

// ─── Editar nombre de materia MANUAL (sin cod_materia) ───
const nombreEditKey = ref(null)
const nombreEditValor = ref('')
const nombreInputRefs = {}

function setNombreInputRef(el, k) {
  if (el) nombreInputRefs[k] = el
}

function abrirEdicionNombre(m) {
  const k = keyDe(m)
  nombreEditKey.value = k
  nombreEditValor.value = m.nombre_materia
  nextTick(() => {
    const el = nombreInputRefs[k]
    el?.focus()
    el?.select()
  })
}

function confirmarEdicionNombre(m) {
  if (nombreEditKey.value !== keyDe(m)) return // ya se cerró (p. ej. por Esc/Enter)
  const nuevo = nombreEditValor.value.trim()
  if (nuevo) {
    m.nombre_materia = nuevo
  }
  nombreEditKey.value = null
}

function cancelarEdicionNombre() {
  nombreEditKey.value = null
}

// ─── Observación por materia (columna DETALLE en CLASIFICACION_MATERIA) ───
const obsAbiertas = ref(new Set())
const obsInputRefs = {}

function obsAbierta(m) {
  return obsAbiertas.value.has(keyDe(m))
}

function setObsInputRef(el, k) {
  if (el) obsInputRefs[k] = el
}

function abrirObs(m) {
  const k = keyDe(m)
  obsAbiertas.value = new Set(obsAbiertas.value).add(k)
  nextTick(() => obsInputRefs[k]?.focus())
}

function cerrarObs(m) {
  const nuevo = new Set(obsAbiertas.value)
  nuevo.delete(keyDe(m))
  obsAbiertas.value = nuevo
}

function quitarObs(m) {
  m.detalle = null
  cerrarObs(m)
}

function limpiarEstadoUI() {
  nombreEditKey.value = null
  obsAbiertas.value = new Set()
}

// El botón "x" de la cabecera: si ya hay materias, cerrar la sección las vacía.
function onCerrar() {
  if (form.materias.length && !confirm('¿Quitar esta sección? Se perderán las materias agregadas.')) return
  limpiarEstadoUI()
  form.materias = []
  emit('cerrar')
}
</script>