<template>
  <div class="flex flex-wrap items-end gap-3">
    <!-- Año desde (admite periodo: 2016 o 2016/1) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">
        Desde el año
      </label>
      <input
        v-model="anioLocal"
        type="text"
        inputmode="numeric"
        placeholder="Ej: 2016 o 2016/1"
        maxlength="9"
        class="
          w-40 bg-slate-800 border border-slate-700 rounded-lg
          text-slate-100 text-sm px-3 py-2 outline-none
          placeholder-slate-500 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Año hasta (admite periodo: 2024 o 2024/2) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">
        Hasta el año
      </label>
      <input
        v-model="anioHastaLocal"
        type="text"
        inputmode="numeric"
        placeholder="Ej: 2024 o 2024/2"
        maxlength="9"
        class="
          w-40 bg-slate-800 border border-slate-700 rounded-lg
          text-slate-100 text-sm px-3 py-2 outline-none
          placeholder-slate-500 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Materia (código o nombre) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">
        Materia
      </label>
      <input
        v-model="materiaLocal"
        type="text"
        placeholder="Código o nombre, ej: 1301033 / Cálculo"
        maxlength="60"
        class="
          w-56 bg-slate-800 border border-slate-700 rounded-lg
          text-slate-100 text-sm px-3 py-2 outline-none
          placeholder-slate-500 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Grupo -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">
        Grupo
      </label>
      <input
        v-model="grupoLocal"
        type="text"
        placeholder="Ej: 01"
        maxlength="10"
        class="
          w-28 bg-slate-800 border border-slate-700 rounded-lg
          text-slate-100 text-sm px-3 py-2 outline-none
          placeholder-slate-500 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Botón regenerar -->
    <button
      :disabled="loading"
      class="
        inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
        bg-amber-500 hover:bg-amber-400 active:bg-amber-600
        text-slate-900 transition-all duration-150 cursor-pointer border-none
        disabled:opacity-50 disabled:cursor-not-allowed
        shadow-lg shadow-amber-500/20
      "
      @click="onGenerar"
    >
      <svg
        :class="loading ? 'animate-spin' : ''"
        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
      >
        <polyline v-if="!loading" points="1 4 1 10 7 10"/>
        <path     v-if="!loading" d="M3.51 15a9 9 0 1 0 .49-4.5"/>
        <circle   v-if="loading"  cx="12" cy="12" r="9"/>
        <path     v-if="loading"  d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
      </svg>
      {{ loading ? 'Generando...' : 'Re-generar' }}
    </button>

    <!-- Limpiar filtros -->
    <button
      v-if="anioLocal || anioHastaLocal || materiaLocal || grupoLocal"
      class="
        inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium
        border border-slate-700 text-slate-400 bg-transparent hover:bg-white/5
        hover:text-slate-200 transition-all duration-150 cursor-pointer
      "
      @click="limpiarFiltros"
    >
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
      Quitar filtros
    </button>

    <!-- Botón PDF con menú desplegable -->
<div class="relative ml-auto" ref="pdfMenuRef">
  <div class="inline-flex rounded-lg overflow-hidden shadow-lg shadow-red-900/20 border border-red-700/40">
    <button
      :disabled="!reporte || loading"
      class="
        inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
        bg-red-700 hover:bg-red-600 active:bg-red-800
        text-white transition-all duration-150 cursor-pointer border-none
        disabled:opacity-40 disabled:cursor-not-allowed
      "
      @click.stop="toggleMenu"
    >
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      Generar Reporte PDF
    </button>

    <button
      :disabled="!reporte || loading"
      class="
        inline-flex items-center px-2.5 py-2
        bg-red-800 hover:bg-red-700 active:bg-red-900
        text-white border-l border-red-900/50 transition-all duration-150
        cursor-pointer border-t-0 border-b-0 border-r-0
        disabled:opacity-40 disabled:cursor-not-allowed
      "
      @click.stop="toggleMenu"
    >
      <svg
        width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
        :class="['transition-transform duration-200', menuOpen ? 'rotate-180' : '']"
      >
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </button>
  </div>

  <Transition
    enter-active-class="transition ease-out duration-150"
    enter-from-class="opacity-0 translate-y-1 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 translate-y-1 scale-95"
  >
    <div
      v-if="menuOpen"
      class="
        absolute right-0 mt-1.5 w-52 rounded-xl
        bg-slate-800 border border-slate-700
        shadow-2xl shadow-black/40 z-50 overflow-hidden
      "
    >
      <template v-for="opt in pdfOpciones" :key="opt.action">
        <!-- Separador -->
        <div
          v-if="opt.action === 'divider'"
          class="mx-3 my-1 border-t border-slate-700"
        />
        <!-- Opción normal -->
        <button
          v-else
          class="
            w-full flex items-center gap-2.5 px-3.5 py-2.5
            text-xs font-medium text-slate-300 hover:bg-white/[0.06] hover:text-slate-100
            transition-colors duration-100 cursor-pointer border-none bg-transparent text-left
          "
          @click="onPDF(opt.action)"
        >
          <span class="text-slate-500" v-html="opt.icon"/>
          {{ opt.label }}
        </button>
      </template>
    </div>
  </Transition>
</div>


  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { generarPDF } from '../composables/useGenerarPDF'
import { generarPDFConTipoIngreso } from '../composables/useGenerarPDFConTipoIngreso'

const props = defineProps({
  anio:      { type: [Number, String], default: null }, // ej: 2016 o "2016/1"
  anioHasta: { type: [Number, String], default: null }, // ej: 2024 o "2024/2"
  materia:   { type: String, default: '' },              // código o nombre
  grupo:     { type: String, default: '' },
  loading:   { type: Boolean, default: false },
  reporte:   { type: Object, default: null },
})

const emit = defineEmits([
  'generar',
  'update:anio',
  'update:anioHasta',
  'update:materia',
  'update:grupo',
])

const anioLocal      = ref(props.anio      || '')
const anioHastaLocal = ref(props.anioHasta || '')
const materiaLocal   = ref(props.materia   || '')
const grupoLocal     = ref(props.grupo     || '')

// ── Menú PDF ──────────────────────────────────────────────────────────────────
const menuOpen   = ref(false)
const pdfMenuRef = ref(null)

const pdfOpciones = [
  {
    action: 'open',
    label:  'Ver / Imprimir PDF',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
               <circle cx="12" cy="12" r="3"/>
             </svg>`,
  },
  {
    action: 'save',
    label:  'Descargar PDF',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
               <polyline points="7 10 12 15 17 10"/>
               <line x1="12" y1="15" x2="12" y2="3"/>
             </svg>`,
  },
  {
    action: 'print',
    label:  'Imprimir directamente',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <polyline points="6 9 6 2 18 2 18 9"/>
               <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
               <rect x="6" y="14" width="12" height="8"/>
             </svg>`,
  },
  { action: 'divider' },
  {
    action: 'open-tipo-ingreso',
    label:  'Ver con modalidad ingreso',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <polyline points="14 2 14 8 20 8"/>
               <line x1="12" y1="18" x2="12" y2="12"/>
               <line x1="9" y1="15" x2="15" y2="15"/>
             </svg>`,
  },
  {
    action: 'save-tipo-ingreso',
    label:  'Descargar con modalidad',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
               <polyline points="7 10 12 15 17 10"/>
               <line x1="12" y1="15" x2="12" y2="3"/>
             </svg>`,
  },
  {
    action: 'print-tipo-ingreso',
    label:  'Imprimir con modalidad',
    icon:   `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <polyline points="6 9 6 2 18 2 18 9"/>
               <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
               <rect x="6" y="14" width="12" height="8"/>
             </svg>`,
  },
]

const toggleMenu = () => { menuOpen.value = !menuOpen.value }

const onClickOutside = (e) => {
  if (pdfMenuRef.value && !pdfMenuRef.value.contains(e.target)) {
    menuOpen.value = false
  }
}
onMounted(()  => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

const onPDF = (action) => {
  menuOpen.value = false
  if (!props.reporte) return

  if (action === 'open-tipo-ingreso')   return generarPDFConTipoIngreso(props.reporte, { action: 'open' })
  if (action === 'save-tipo-ingreso')   return generarPDFConTipoIngreso(props.reporte, { action: 'save' })
  if (action === 'print-tipo-ingreso')  return generarPDFConTipoIngreso(props.reporte, { action: 'print' })

  generarPDF(props.reporte, { action })
}

// ── Parseo de año/periodo ─────────────────────────────────────────────────────
// Acepta "2016", "2016/1", "2016-1", "2016 1" → { anio: 2016, periodo: '1' | null }
function parseAnioPeriodo(valorCrudo) {
  const valor = (valorCrudo || '').toString().trim()
  if (!valor) return { anio: null, periodo: null }

  const match = valor.match(/^(\d{4})\s*[\/\-\s]?\s*([1-4])?$/)
  if (!match) return { anio: Number(valor) || null, periodo: null }

  const [, anioStr, periodoStr] = match
  return {
    anio:    Number(anioStr),
    periodo: periodoStr || null,
  }
}

// ── Acciones ──────────────────────────────────────────────────────────────────
const onGenerar = () => {
  const { anio, periodo }            = parseAnioPeriodo(anioLocal.value)
  const { anio: anioHasta, periodo: periodoHasta } = parseAnioPeriodo(anioHastaLocal.value)
  const materia = materiaLocal.value.trim() || null
  const grupo   = grupoLocal.value.trim()   || null

 

  

  emit('update:anio',      anioLocal.value      || null)
  emit('update:anioHasta', anioHastaLocal.value || null)
  emit('update:materia',   materia)
  emit('update:grupo',     grupo)

  emit('generar', { anio, periodo, anioHasta, periodoHasta, materia, grupo })
}

const limpiarFiltros = () => {
  anioLocal.value      = ''
  anioHastaLocal.value = ''
  materiaLocal.value   = ''
  grupoLocal.value     = ''
  emit('update:anio',      null)
  emit('update:anioHasta', null)
  emit('update:materia',   null)
  emit('update:grupo',     null)
}
</script>