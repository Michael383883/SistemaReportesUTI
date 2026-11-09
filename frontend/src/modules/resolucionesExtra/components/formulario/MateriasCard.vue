<template>
  <div ref="raiz" class="bg-white rounded-2xl border border-gray-200 shadow-sm">
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
      <!-- El buscador queda SIEMPRE arriba y visible: no hay que bajar ni subir -->
      <BuscadorMaterias
        v-if="!noRegentaFCE"
        :docente="form.cod_docente"
        :gestion="form.gestion"
        :periodo="form.periodo"
        :materiasSeleccionadas="form.materias"
        @agregar-materia="onAgregarMateria"
      />

      <template v-if="form.materias.length && !noRegentaFCE">
        <!-- Resumen + atajos por docente (clic = salta a su grupo) -->
        <div class="flex flex-wrap items-center gap-1.5 text-[12px]">
          <span class="font-semibold text-slate-700">
            {{ form.materias.length }} {{ form.materias.length === 1 ? 'materia' : 'materias' }}
          </span>
          <span class="text-gray-300">·</span>
          <span class="text-slate-500 mr-1">
            {{ grupos.length }} {{ grupos.length === 1 ? 'docente' : 'docentes' }}
          </span>

          <button
            v-for="g in grupos"
            :key="'chip-' + g.key"
            type="button"
            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[11px] font-semibold transition hover:brightness-95"
            :class="g.color.chip"
            :title="'Ir a: ' + g.titulo"
            @click="irAGrupo(g.key)"
          >
            <span class="w-2 h-2 rounded-full" :class="g.color.dot"></span>
            <span class="max-w-[150px] truncate">{{ g.titulo }}</span>
            <span class="opacity-70">{{ g.items.length }}</span>
          </button>
        </div>

        <!-- Barra de herramientas: filtro rápido + colapsar/expandir todo -->
        <div class="flex items-center gap-2">
          <div v-if="form.materias.length > 5" class="relative flex-1">
            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
              v-model="filtro"
              type="text"
              placeholder="Filtrar entre las materias agregadas..."
              class="w-full pl-8 pr-7 py-1.5 text-[12px] border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400"
            />
            <button
              v-if="filtro"
              type="button"
              class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600 text-[12px]"
              @click="filtro = ''"
            >✕</button>
          </div>
          <span v-else class="flex-1"></span>

          <button
            v-if="grupos.length > 1"
            type="button"
            class="text-[11px] text-slate-500 hover:text-slate-800 underline flex-shrink-0"
            @click="todosColapsados ? expandirTodo() : colapsarTodo()"
          >
            {{ todosColapsados ? 'Expandir todo' : 'Colapsar todo' }}
          </button>
        </div>

        <p v-if="filtro && !gruposVisibles.length" class="text-center text-[12px] text-gray-500 py-4">
          Ninguna materia agregada coincide con "{{ filtro }}"
        </p>

        <!-- Lista con scroll PROPIO (usa casi toda la altura disponible). Dentro del modal
             de edición no hay scroll interno: usa el scroll del modal. -->
        <div
          ref="listaRef"
          class="relative space-y-3"
          :class="enModal
            ? 'max-h-[55vh] min-h-[10rem] overflow-y-auto pr-1'
            : 'max-h-[calc(100vh-17rem)] min-h-[14rem] overflow-y-auto pr-1'"
        >
          <section
            v-for="g in gruposVisibles"
            :key="g.key"
            :ref="el => setGrupoRef(el, g.key)"
            class="rounded-xl border overflow-hidden bg-white"
            :class="g.color.border"
          >
            <!-- Cabecera del docente (clic = colapsar/expandir) -->
            <button
              type="button"
              class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-left sticky top-0 z-[1]"
              :class="g.color.header"
              @click="toggleGrupo(g.key)"
            >
              <span class="flex items-center gap-2 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="g.color.dot"></span>
                <span class="text-[12px] font-bold truncate">{{ g.titulo }}</span>
              </span>
              <span class="flex items-center gap-1.5 text-[11px] font-semibold flex-shrink-0">
                {{ g.items.length }}
                <svg
                  class="w-3.5 h-3.5 transition-transform"
                  :class="colapsados.has(g.key) ? '-rotate-90' : ''"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </span>
            </button>

            <ul v-show="!colapsados.has(g.key)" class="divide-y" :class="g.color.divide">
              <li
                v-for="m in g.items"
                :key="keyDe(m)"
                :ref="el => setFilaRef(el, keyDe(m))"
                class="px-3 py-1.5 transition-colors duration-500"
                :class="recienAgregada === keyDe(m) ? g.color.flash : 'bg-white hover:bg-gray-50'"
              >
                <!-- Fila compacta: nombre · nota · obs · quitar -->
                <div class="flex items-center gap-2">
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
                      <div class="text-[12px] font-semibold text-slate-800 truncate" :title="m.nombre_materia">
                        {{ m.nombre_materia }}
                        <span v-if="m.cod_materia" class="text-slate-500 font-medium">({{ m.cod_materia }})</span>
                        <span
                          v-if="m.grupo"
                          class="ml-1 px-1.5 py-px rounded text-[10px] font-bold align-middle"
                          :class="g.color.badge"
                        >Gr. {{ m.grupo }}</span>
                      </div>
                      <div v-if="!m.cod_materia" class="flex items-center gap-1.5">
                        <span class="text-[10px] text-slate-500 font-normal">Sin código · no se aplicará en GRUPOS</span>
                        <button
                          type="button"
                          class="text-[10px] text-orange-500 hover:text-orange-700 underline"
                          @click="abrirEdicionNombre(m)"
                        >editar nombre</button>
                      </div>
                    </template>
                  </div>

                  <input
                    v-model="m.nota"
                    type="text"
                    inputmode="numeric"
                    placeholder="Nota"
                    data-nota
                    class="w-16 px-1.5 py-1 text-[12px] border rounded focus:outline-none focus:ring-2 text-center font-bold text-slate-800 flex-shrink-0"
                    :class="notaInvalida(m)
                      ? 'border-red-400 bg-red-50 focus:ring-red-400'
                      : (m.nota !== null && m.nota !== '' && m.nota !== undefined)
                        ? 'border-green-400 bg-green-50 focus:ring-green-400'
                        : 'border-gray-300 bg-white focus:ring-orange-400'"
                    @keypress="soloNumeros"
                    @keydown.enter.prevent="siguienteNota($event)"
                  />

                  <button
                    type="button"
                    class="flex-shrink-0 p-1 rounded hover:bg-gray-100"
                    :class="m.detalle ? 'text-orange-600' : 'text-gray-400'"
                    :title="m.detalle ? 'Editar observación' : 'Agregar observación'"
                    @click="obsAbierta(m) ? cerrarObs(m) : abrirObs(m)"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6l3-3h10a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v13z"/>
                    </svg>
                  </button>

                  <button
                    type="button"
                    @click="quitarMateria(m)"
                    class="flex-shrink-0 p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50"
                    title="Quitar materia"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>

                <p v-if="notaInvalida(m)" class="text-[11px] text-red-600 font-medium mt-0.5">Nota no válida (máx. 100)</p>

                <!-- Observación (va a DETALLE en la BD) -->
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
                    <button type="button" class="text-[10px] text-gray-500 hover:text-gray-700" @click="quitarObs(m)">
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
              </li>
            </ul>
          </section>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, nextTick, toRaw, onMounted } from 'vue'
import BuscadorMaterias from '../BuscadorMaterias.vue'

const props = defineProps({
  form: { type: Object, required: true },
  // Docente "por defecto" del formulario general, usado para pre-asignar
  // a cada materia nueva que se agregue.
  selectedDocente: { type: Object, default: null },
})

const emit = defineEmits(['cerrar'])

const form = props.form // objeto reactive compartido; se muta directamente

// ─── Identificador estable por materia (no viaja en el payload) ───
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

// ─── Colores automáticos por docente ───
// Clases escritas completas (Tailwind no detecta clases armadas con strings).
const PALETA = [
  { dot: 'bg-sky-500',     header: 'bg-sky-100 text-sky-800',         border: 'border-sky-300',     divide: 'divide-sky-100',     chip: 'bg-sky-50 border-sky-300 text-sky-700',             badge: 'bg-sky-100 text-sky-700',         flash: 'bg-sky-100' },
  { dot: 'bg-emerald-500', header: 'bg-emerald-100 text-emerald-800', border: 'border-emerald-300', divide: 'divide-emerald-100', chip: 'bg-emerald-50 border-emerald-300 text-emerald-700', badge: 'bg-emerald-100 text-emerald-700', flash: 'bg-emerald-100' },
  { dot: 'bg-violet-500',  header: 'bg-violet-100 text-violet-800',   border: 'border-violet-300',  divide: 'divide-violet-100',  chip: 'bg-violet-50 border-violet-300 text-violet-700',    badge: 'bg-violet-100 text-violet-700',   flash: 'bg-violet-100' },
  { dot: 'bg-rose-500',    header: 'bg-rose-100 text-rose-800',       border: 'border-rose-300',    divide: 'divide-rose-100',    chip: 'bg-rose-50 border-rose-300 text-rose-700',          badge: 'bg-rose-100 text-rose-700',       flash: 'bg-rose-100' },
  { dot: 'bg-amber-500',   header: 'bg-amber-100 text-amber-800',     border: 'border-amber-300',   divide: 'divide-amber-100',   chip: 'bg-amber-50 border-amber-300 text-amber-700',       badge: 'bg-amber-100 text-amber-700',     flash: 'bg-amber-100' },
  { dot: 'bg-teal-500',    header: 'bg-teal-100 text-teal-800',       border: 'border-teal-300',    divide: 'divide-teal-100',    chip: 'bg-teal-50 border-teal-300 text-teal-700',          badge: 'bg-teal-100 text-teal-700',       flash: 'bg-teal-100' },
  { dot: 'bg-fuchsia-500', header: 'bg-fuchsia-100 text-fuchsia-800', border: 'border-fuchsia-300', divide: 'divide-fuchsia-100', chip: 'bg-fuchsia-50 border-fuchsia-300 text-fuchsia-700', badge: 'bg-fuchsia-100 text-fuchsia-700', flash: 'bg-fuchsia-100' },
  { dot: 'bg-indigo-500',  header: 'bg-indigo-100 text-indigo-800',   border: 'border-indigo-300',  divide: 'divide-indigo-100',  chip: 'bg-indigo-50 border-indigo-300 text-indigo-700',    badge: 'bg-indigo-100 text-indigo-700',   flash: 'bg-indigo-100' },
]
const COLOR_SIN_DOCENTE = {
  dot: 'bg-gray-400', header: 'bg-gray-100 text-gray-700', border: 'border-gray-300', divide: 'divide-gray-100',
  chip: 'bg-gray-50 border-gray-300 text-gray-600', badge: 'bg-gray-100 text-gray-600', flash: 'bg-gray-100',
}

// Cada docente conserva su color mientras dura el formulario (por orden de aparición).
const colorPorDocente = new Map()
function colorDe(key) {
  if (key === 'sin') return COLOR_SIN_DOCENTE
  if (!colorPorDocente.has(key)) colorPorDocente.set(key, colorPorDocente.size)
  return PALETA[colorPorDocente.get(key) % PALETA.length]
}

function grupoKeyDe(m) {
  const cod = m.docente?.cod_docente
  return cod !== null && cod !== undefined && cod !== '' ? String(cod) : 'sin'
}

function tituloDocente(key, docente) {
  if (key === 'sin') return 'Sin docente asignado'
  const nombre = `${docente?.apellidos ?? ''} ${docente?.nombres ?? ''}`.trim()
  return nombre || `Docente ${key}`
}

// Materias agrupadas por docente, en orden de aparición
const grupos = computed(() => {
  const mapa = new Map()
  for (const m of form.materias) {
    const key = grupoKeyDe(m)
    if (!mapa.has(key)) {
      mapa.set(key, { key, titulo: tituloDocente(key, m.docente), color: colorDe(key), items: [] })
    } else if (key !== 'sin' && mapa.get(key).titulo.startsWith('Docente ') && m.docente?.apellidos) {
      mapa.get(key).titulo = tituloDocente(key, m.docente)
    }
    mapa.get(key).items.push(m)
  }
  return [...mapa.values()]
})

// ─── Scroll interno de la lista ───
const listaRef = ref(null)
const grupoRefs = {}
const filaRefs = {}
const colapsados = ref(new Set())
const recienAgregada = ref(null)
let flashTimer = null

const raiz = ref(null)
const enModal = ref(false)
const filtro = ref('')

onMounted(() => {
  // Dentro del modal de edición (overlay fijo) se usa el scroll del modal, sin scroll anidado
  enModal.value = !!raiz.value?.closest('.fixed.inset-0')
})

const gruposVisibles = computed(() => {
  const q = filtro.value.trim().toLowerCase()
  if (!q) return grupos.value
  return grupos.value
    .map(g => ({
      ...g,
      items: g.items.filter(m =>
        (m.nombre_materia || '').toLowerCase().includes(q) ||
        String(m.cod_materia || '').toLowerCase().includes(q) ||
        String(m.grupo || '').toLowerCase() === q
      ),
    }))
    .filter(g => g.items.length)
})

const todosColapsados = computed(() =>
  grupos.value.length > 0 && grupos.value.every(g => colapsados.value.has(g.key))
)
function colapsarTodo() { colapsados.value = new Set(grupos.value.map(g => g.key)) }
function expandirTodo() { colapsados.value = new Set() }

// Enter en una nota → salta a la nota de la siguiente materia visible
// (la última devuelve el foco al buscador). Registro de notas solo con teclado.
function siguienteNota(e) {
  const root = raiz.value
  if (!root) return
  const inputs = [...root.querySelectorAll('input[data-nota]')].filter(i => i.offsetParent !== null)
  const sig = inputs[inputs.indexOf(e.target) + 1]
  if (sig) {
    sig.focus()
    sig.select()
  } else {
    root.querySelector('input[placeholder^="Buscar materia"]')?.focus()
  }
}

function setGrupoRef(el, key) {
  if (el) grupoRefs[key] = el
}
function setFilaRef(el, k) {
  if (el) filaRefs[k] = el
}

function scrollDentroDeLista(el) {
  const cont = listaRef.value
  if (!el) return
  if (!cont) {
    el.scrollIntoView({ block: 'center', behavior: 'smooth' })
    return
  }
  const destino = el.offsetTop - cont.clientHeight / 2 + el.clientHeight / 2
  cont.scrollTo({ top: Math.max(destino, 0), behavior: 'smooth' })
}

function destacar(m) {
  const k = keyDe(m)
  recienAgregada.value = k
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => { recienAgregada.value = null }, 1800)
  nextTick(() => scrollDentroDeLista(filaRefs[k]))
}

function expandirGrupo(key) {
  if (!colapsados.value.has(key)) return
  const n = new Set(colapsados.value)
  n.delete(key)
  colapsados.value = n
}

function toggleGrupo(key) {
  const n = new Set(colapsados.value)
  n.has(key) ? n.delete(key) : n.add(key)
  colapsados.value = n
}

function irAGrupo(key) {
  expandirGrupo(key)
  nextTick(() => {
    const cont = listaRef.value
    const el = grupoRefs[key]
    if (!el) return
    if (!cont) el.scrollIntoView({ block: 'start', behavior: 'smooth' })
    else cont.scrollTo({ top: el.offsetTop, behavior: 'smooth' })
  })
}

// ─── "No regenta materia en la FCE" ───
const NO_REGENTA_LABEL = 'NO REGENTA MATERIA EN LA FCE'

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
  const existente = materiaData.cod_materia
    ? form.materias.find(
        m =>
          m.cod_materia === materiaData.cod_materia &&
          (m.grupo ?? null) === (materiaData.grupo ?? null) &&
          (m.cod_plan ?? null) === (materiaData.cod_plan ?? null)
      )
    : null
  if (existente) {
    // Ya estaba: se muestra dónde está en vez de ignorarlo en silencio
    expandirGrupo(grupoKeyDe(existente))
    destacar(existente)
    return
  }

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

  // La materia recién agregada se centra sola en la lista y parpadea con el color de su docente
  const nueva = form.materias[form.materias.length - 1]
  expandirGrupo(grupoKeyDe(nueva))
  destacar(nueva)
}

// ─── Validación de solo números para NOTA ───
function soloNumeros(event) {
  const charCode = event.which ? event.which : event.keyCode
  if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
    event.preventDefault()
  }
}

// Solo muestra el aviso; no bloquea el guardado.
function notaInvalida(m) {
  if (m.nota === null || m.nota === undefined || m.nota === '') return false
  const valor = Number(m.nota)
  if (Number.isNaN(valor)) return false
  return valor > 100
}

// ─── Quitar una materia de la lista ───
function quitarMateria(m) {
  if (m?.yaRegistrada) {
    const ok = confirm('Esta materia ya está registrada en el documento. ¿Seguro que deseas eliminarla?')
    if (!ok) return
  }
  const i = indiceDe(m)
  if (i === -1) return

  const k = keyDe(m)
  if (nombreEditKey.value === k) nombreEditKey.value = null
  if (obsAbiertas.value.has(k)) {
    const nuevo = new Set(obsAbiertas.value)
    nuevo.delete(k)
    obsAbiertas.value = nuevo
  }
  delete nombreInputRefs[k]
  delete obsInputRefs[k]
  delete filaRefs[k]

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
  if (nombreEditKey.value !== keyDe(m)) return
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
  colapsados.value = new Set()
}

// El botón "x" de la cabecera: si ya hay materias, cerrar la sección las vacía.
function onCerrar() {
  if (form.materias.length && !confirm('¿Quitar esta sección? Se perderán las materias agregadas.')) return
  limpiarEstadoUI()
  form.materias = []
  emit('cerrar')
}
</script>