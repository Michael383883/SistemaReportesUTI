<template>
  <div class="relative rounded-xl border border-slate-200 bg-white shadow-sm mb-6">
    <div class="absolute inset-0 rounded-xl overflow-hidden pointer-events-none">
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600"/>
    </div>

    <div class="relative flex items-center gap-3 py-4 pr-6 pl-7 flex-wrap">

      <!-- ── Identidad (nombre + código) ─────────────────────────────────── -->
      <div class="flex items-center gap-2 min-w-0">
        <h2 class="text-base font-semibold text-slate-800 m-0 tracking-tight whitespace-nowrap truncate">
          {{ reporte.docente?.nombre }}
        </h2>
        <span class="text-[0.82rem] font-semibold text-indigo-600 whitespace-nowrap">
          · SIS {{ reporte.docente?.codigo }}
        </span>
      </div>

      <div class="w-px h-5 bg-slate-300 mx-1 hidden sm:block"/>

      <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800 whitespace-nowrap">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Desde {{ anioDesde }}
      </div>

      <span class="text-slate-300">•</span>

      <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-600 font-medium whitespace-nowrap">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        {{ reporte.total }} materia{{ reporte.total !== 1 ? 's' : '' }}
      </div>

      <div class="flex-1"/>

      <!-- ── Botón Habilitar Categorías (click directo, sin Aplicar) ─────── -->
      <div v-if="tieneCategorias" class="relative" ref="categoriasMenuRef">
        <button
          type="button"
          :disabled="loadingCategorias"
          @click="mostrarDropdown = !mostrarDropdown"
          class="
            inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
            border shadow-sm transition-all duration-150 cursor-pointer whitespace-nowrap
            active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed
            bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100
          "
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
          </svg>
          {{ etiquetaBoton }}
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :class="mostrarDropdown ? 'rotate-180' : ''" class="transition-transform">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <div
          v-if="mostrarDropdown"
          class="absolute right-0 z-20 mt-1 w-60 rounded-lg border border-slate-200 bg-white shadow-lg py-1"
        >
          <!-- Todas: un click, toggle inmediato -->
          <button
            type="button"
            @click="clickTodas"
            class="w-full flex items-center gap-2 text-left px-3 py-2 text-xs font-semibold hover:bg-slate-50 border-b border-slate-100"
            :class="todasSeleccionadas ? 'text-amber-700 bg-amber-50' : 'text-slate-700'"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="shrink-0">
              <polyline v-if="todasSeleccionadas" points="20 6 9 17 4 12"/>
            </svg>
            Todas
          </button>

          <!-- Cada categoría: un click, toggle inmediato -->
          <button
            v-for="cat in categorias" :key="cat"
            type="button"
            @click="clickCategoria(cat)"
            class="w-full flex items-center gap-2 text-left px-3 py-2 text-xs hover:bg-slate-50"
            :class="categoriasSeleccionadas.includes(cat) ? 'font-semibold text-amber-700 bg-amber-50' : 'text-slate-700'"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="shrink-0">
              <polyline v-if="categoriasSeleccionadas.includes(cat)" points="20 6 9 17 4 12"/>
            </svg>
            {{ cat }}
          </button>
        </div>
      </div>

      <!-- ── Habilitar periodo (existente) ───────────────────────────────── -->
      <button
        v-if="periodoPendiente"
        :disabled="loading"
        type="button"
        class="
          inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
          border shadow-sm transition-all duration-150 cursor-pointer whitespace-nowrap
          active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100
        "
        :class="habilitacionAplicada
          ? 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100'
          : 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100'"
        :title="habilitacionAplicada
          ? `Mostrando ${periodoPendiente.label} (aún no concluye) — click para volver a ocultarlo`
          : `${periodoPendiente.label} aún no concluye y está oculto — click para mostrarlo`"
        @click="onToggle"
      >
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path v-if="habilitacionAplicada" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle v-if="habilitacionAplicada" cx="12" cy="12" r="3"/>
          <path v-else d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.9 18.9 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
          <line v-if="!habilitacionAplicada" x1="1" y1="1" x2="23" y2="23"/>
        </svg>
        {{ habilitacionAplicada ? `Mostrando ${periodoPendiente.label}` : `Habilitar ${periodoPendiente.label}` }}
      </button>

      <button
        type="button"
        class="
          inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
          border border-slate-300 text-slate-600 bg-slate-50 shadow-sm
          hover:bg-slate-100 hover:text-slate-900 hover:border-slate-400
          active:scale-[0.97] transition-all duration-150 cursor-pointer whitespace-nowrap
        "
        @click="$emit('volver')"
      >
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Volver
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  reporte: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  categorias: { type: Array, default: () => [] },
  tieneCategorias: { type: Boolean, default: false },
  loadingCategorias: { type: Boolean, default: false },
  categoriasSeleccionadas: { type: Array, default: () => [] },
})

const emit = defineEmits(['volver', 'toggle-restriccion', 'seleccionar-categorias'])

const anioDesde = computed(() => {
  const materias = props.reporte.materias || []
  if (!materias.length) return '—'
  return materias.reduce((min, m) => {
    const anio = parseInt(m.ANIO)
    return anio < min ? anio : min
  }, Infinity)
})

const PERIODO_LABEL = {
  '1': 'Semestre I',
  '2': 'Semestre II',
  '3': 'Curso de Verano',
  '4': 'Curso de Invierno',
}

const restriccion = computed(() => props.reporte?.restriccion || null)

const periodoPendiente = computed(() => {
  const lista = restriccion.value?.periodos_no_concluidos || []
  if (!lista.length) return null

  const clave = restriccion.value?.habilitacion_aplicada
    ? restriccion.value.habilitacion_solicitada
    : lista[0]

  if (!clave) return null

  const [anioStr, periodo] = clave.split('-')
  return {
    anio: Number(anioStr),
    periodo,
    label: `${anioStr}/${periodo} (${PERIODO_LABEL[periodo] || periodo})`,
  }
})

const habilitacionAplicada = computed(() => !!restriccion.value?.habilitacion_aplicada)

const onToggle = () => {
  if (!periodoPendiente.value) return
  emit('toggle-restriccion', {
    anio: periodoPendiente.value.anio,
    periodo: periodoPendiente.value.periodo,
    habilitar: !habilitacionAplicada.value,
  })
}

// ── Categorías: cada click aplica al instante, sin botón "Aplicar" ───────
const mostrarDropdown = ref(false)
const categoriasMenuRef = ref(null)

const todasSeleccionadas = computed(() =>
  props.categorias.length > 0 && props.categoriasSeleccionadas.length === props.categorias.length
)

function clickCategoria(cat) {
  const yaEstaba = props.categoriasSeleccionadas.includes(cat)
  const nuevaLista = yaEstaba
    ? props.categoriasSeleccionadas.filter(c => c !== cat)
    : [...props.categoriasSeleccionadas, cat]

  emit('seleccionar-categorias', nuevaLista)
}

function clickTodas() {
  const nuevaLista = todasSeleccionadas.value ? [] : [...props.categorias]
  emit('seleccionar-categorias', nuevaLista)
}

const etiquetaBoton = computed(() => {
  const n = props.categoriasSeleccionadas.length
  if (n === 0) return 'Habilitar Categorías'
  if (n === 1) return `Categoría: ${props.categoriasSeleccionadas[0]}`
  if (n === props.categorias.length) return 'Todas las categorías'
  return `${n} categorías`
})

// ── Cierra el dropdown al hacer click fuera de él ────────────────────────
const onClickOutside = (e) => {
  if (categoriasMenuRef.value && !categoriasMenuRef.value.contains(e.target)) {
    mostrarDropdown.value = false
  }
}
onMounted(()  => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>