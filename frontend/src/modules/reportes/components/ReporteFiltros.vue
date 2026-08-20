<template>
  <div class="flex flex-wrap items-end gap-3">
    <!-- Año desde (admite periodo: 2016 o 2016/1) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-900">
        Desde el año
      </label>
      <input
        v-model="anioLocal"
        type="text"
        inputmode="numeric"
        placeholder="Ej: 2016 o 2016/1"
        maxlength="9"
        class="
          w-40 bg-white border border-slate-300 rounded-lg
          text-slate-800 text-sm px-3 py-2 outline-none
          placeholder-slate-400 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Año hasta (admite periodo: 2024 o 2024/2) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-900">
        Hasta el año
      </label>
      <input
        v-model="anioHastaLocal"
        type="text"
        inputmode="numeric"
        placeholder="Ej: 2024 o 2024/2"
        maxlength="9"
        class="
          w-40 bg-white border border-slate-300 rounded-lg
          text-slate-800 text-sm px-3 py-2 outline-none
          placeholder-slate-400 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Materia (código o nombre) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-900">
        Materia
      </label>
      <input
        v-model="materiaLocal"
        type="text"
        placeholder="Código o nombre, ej: 1301033 / Cálculo"
        maxlength="60"
        class="
          w-56 bg-white border border-slate-300 rounded-lg
          text-slate-800 text-sm px-3 py-2 outline-none
          placeholder-slate-400 transition-all duration-150
          focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
        "
        @keyup.enter="onGenerar"
      />
    </div>

    <!-- Grupo -->
    <div class="flex flex-col gap-1.5">
      <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-900">
        Grupo
      </label>
      <input
        v-model="grupoLocal"
        type="text"
        placeholder="Ej: 01"
        maxlength="10"
        class="
          w-28 bg-white border border-slate-300 rounded-lg
          text-slate-800 text-sm px-3 py-2 outline-none
          placeholder-slate-400 transition-all duration-150
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
        text-slate-100 transition-all duration-150 cursor-pointer border-none
        disabled:opacity-50 disabled:cursor-not-allowed
        shadow-sm shadow-amber-500/20
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
        border border-slate-300 text-slate-500 bg-white hover:bg-slate-50
        hover:text-slate-700 transition-all duration-150 cursor-pointer
      "
      @click="limpiarFiltros"
    >
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
      Quitar filtros
    </button>

    <!-- ═══════════════════════════════════════════════════════════════════
         Botón(es) PDF
         - Modo documentos + hay categorías seleccionadas → un solo botón
           "Generar Reporte de Documento" con su propio mini-menú.
         - Modo normal → el menú de 3 secciones (estándar / modalidad /
           compartido) de siempre.
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="relative ml-auto" ref="pdfMenuRef">

      <!-- ── Modo documentos: botón único ────────────────────────────────── -->
      <template v-if="soloDocumentos">
        <div class="inline-flex rounded-lg overflow-hidden shadow-sm shadow-red-900/10 border border-red-700/40">
          <button
            :disabled="!reporte || loading"
            class="
              inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
              bg-red-700 hover:bg-red-600 active:bg-red-800
              text-white transition-all duration-150 cursor-pointer border-none
              disabled:opacity-40 disabled:cursor-not-allowed
            "
            @click.stop="onPDF('open-documento')"
          >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
              <polyline points="10 9 9 9 8 9"/>
            </svg>
            Generar Reporte de Documento
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
              absolute right-0 mt-1.5 w-44 rounded-xl
              bg-white border border-slate-800
              shadow-xl shadow-black/10 z-50 overflow-hidden
            "
          >
            <div class="flex flex-col gap-2 px-3.5 py-3">
              <span class="text-[0.72rem] font-semibold text-slate-900">
                Reporte de documento
              </span>
              <div class="flex items-center gap-2">
                <button
                  title="Ver / Imprimir en el navegador"
                  class="
                    group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                    text-blue-800 bg-slate-50 border border-slate-300
                    hover:bg-slate-200 hover:text-slate-800 hover:border-slate-300
                    focus-visible:bg-slate-200 focus-visible:text-slate-800
                    active:bg-slate-300
                    transition-colors duration-100 cursor-pointer outline-none
                  "
                  @click="onPDF('open-documento')"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <button
                  title="Imprimir directamente"
                  class="
                    group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                    text-slate-800 bg-slate-200 border border-slate-200
                    hover:bg-slate-200 hover:text-slate-400 hover:border-slate-300
                    focus-visible:bg-slate-200 focus-visible:text-slate-800
                    active:bg-slate-300
                    transition-colors duration-100 cursor-pointer outline-none
                  "
                  @click="onPDF('print-documento')"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                  </svg>
                </button>

                <button
                  title="Descargar PDF"
                  class="
                    group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                    text-amber-600 bg-amber-50 border border-amber-200
                    hover:bg-amber-200 hover:text-amber-800 hover:border-amber-300
                    focus-visible:bg-amber-200 focus-visible:text-amber-800
                    active:bg-amber-300
                    transition-colors duration-100 cursor-pointer outline-none
                  "
                  @click="onPDF('save-documento')"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </template>

      <!-- ── Modo normal: menú de 3 secciones ────────────────────────────── -->
      <template v-else>
        <div class="inline-flex rounded-lg overflow-hidden shadow-sm shadow-red-900/10 border border-red-700/40">
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
              absolute right-0 mt-1.5 w-60 rounded-xl
              bg-white border border-slate-800
              shadow-xl shadow-black/10 z-50 overflow-hidden
            "
          >
            <template v-for="(sec, i) in pdfSecciones" :key="sec.key">
              <div v-if="i > 0" class="mx-3.5 border-t border-slate-100"/>

              <div class="flex flex-col gap-2 px-3.5 py-3">
                <span class="text-[0.72rem] font-semibold text-slate-900">
                  {{ sec.titulo }}
                </span>

                <div class="flex items-center gap-2">
                  <button
                    title="Ver / Imprimir en el navegador"
                    class="
                      group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                      text-blue-800 bg-slate-50 border border-slate-300
                      hover:bg-slate-200 hover:text-slate-800 hover:border-slate-300
                      focus-visible:bg-slate-200 focus-visible:text-slate-800
                      active:bg-slate-300
                      transition-colors duration-100 cursor-pointer outline-none
                    "
                    @click="onPDF(sec.acciones.open)"
                  >
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>

                  <button
                    title="Imprimir directamente"
                    class="
                      group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                      text-slate-800 bg-slate-200 border border-slate-200
                      hover:bg-slate-200 hover:text-slate-400 hover:border-slate-300
                      focus-visible:bg-slate-200 focus-visible:text-slate-800
                      active:bg-slate-300
                      transition-colors duration-100 cursor-pointer outline-none
                    "
                    @click="onPDF(sec.acciones.print)"
                  >
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="6 9 6 2 18 2 18 9"/>
                      <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                      <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                  </button>

                  <button
                    title="Descargar PDF"
                    class="
                      group inline-flex items-center justify-center flex-1 h-9 rounded-lg
                      text-amber-600 bg-amber-50 border border-amber-200
                      hover:bg-amber-200 hover:text-amber-800 hover:border-amber-300
                      focus-visible:bg-amber-200 focus-visible:text-amber-800
                      active:bg-amber-300
                      transition-colors duration-100 cursor-pointer outline-none
                    "
                    @click="onPDF(sec.acciones.save)"
                  >
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="7 10 12 15 17 10"/>
                      <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </div>
        </Transition>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { generarPDF } from '../composables/useGenerarPDF'
import { generarPDFConTipoIngreso } from '../composables/useGenerarPDFConTipoIngreso'
import { generarPDFCompartido } from '../composables/useGenerarPDFCompartido'

const props = defineProps({
  anio:      { type: [Number, String], default: null },
  anioHasta: { type: [Number, String], default: null },
  materia:   { type: String, default: '' },
  grupo:     { type: String, default: '' },
  loading:   { type: Boolean, default: false },
  reporte:   { type: Object, default: null },
  documentosCategoria:     { type: Array, default: () => [] },
  categoriasSeleccionadas: { type: Array, default: () => [] },
  // true cuando venimos del modo "documentos" (query.modo === 'documentos')
  modoDocumento: { type: Boolean, default: false },
})

// Sólo mostramos el botón único de "Generar Reporte de Documento" cuando
// estamos en modo documento Y hay al menos una categoría seleccionada.
const soloDocumentos = computed(() =>
  props.modoDocumento && props.categoriasSeleccionadas.length > 0
)

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

const pdfSecciones = [
  {
    key: 'estandar',
    titulo: 'Reporte estándar',
    acciones: { open: 'open', print: 'print', save: 'save' },
  },
  {
    key: 'modalidad',
    titulo: 'Con modalidad de ingreso',
    acciones: { open: 'open-tipo-ingreso', print: 'print-tipo-ingreso', save: 'save-tipo-ingreso' },
  },
  {
    key: 'compartido',
    titulo: 'Con compartidos',
    acciones: { open: 'open-compartido', print: 'print-compartido', save: 'save-compartido' },
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

  const opts = {
    documentosCategoria: props.documentosCategoria,
    categoriasSeleccionadas: props.categoriasSeleccionadas,
  }

  if (action === 'open-tipo-ingreso')   return generarPDFConTipoIngreso(props.reporte, { action: 'open', ...opts })
  if (action === 'save-tipo-ingreso')   return generarPDFConTipoIngreso(props.reporte, { action: 'save', ...opts })
  if (action === 'print-tipo-ingreso')  return generarPDFConTipoIngreso(props.reporte, { action: 'print', ...opts })

  if (action === 'open-compartido')     return generarPDFCompartido(props.reporte, { action: 'open', ...opts })
  if (action === 'save-compartido')     return generarPDFCompartido(props.reporte, { action: 'save', ...opts })
  if (action === 'print-compartido')    return generarPDFCompartido(props.reporte, { action: 'print', ...opts })

  // ── Modo "solo documento": el mismo generador estándar, pero indicándole
  // que omita la tabla de materias y sólo emita la tabla de documentos ──
  if (action === 'open-documento')  return generarPDF(props.reporte, { action: 'open',  soloDocumentos: true, ...opts })
  if (action === 'save-documento')  return generarPDF(props.reporte, { action: 'save',  soloDocumentos: true, ...opts })
  if (action === 'print-documento') return generarPDF(props.reporte, { action: 'print', soloDocumentos: true, ...opts })

  generarPDF(props.reporte, {
    action,
    ...opts,
  })
}

// ── Parseo de año/periodo ─────────────────────────────────────────────────────
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