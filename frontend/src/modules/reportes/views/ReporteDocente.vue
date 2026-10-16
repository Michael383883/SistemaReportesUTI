<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 p-6 transition-colors">
    <div class="mb-6">
      <h1 class="text-[20px] font-bold text-gray-1000 dark:text-white">Reporte de Docentes con Título</h1>
      <p class="text-[14px] text-gray-600 dark:text-slate-400 mt-1">Docentes del periodo seleccionado y sus títulos registrados</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 overflow-hidden mb-6">
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-800 dark:bg-slate-950">
        <span class="text-[13px] font-bold text-white">Filtros</span>
      </div>

      <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Gestión (año) *</label>
          <input v-model.number="filtros.anio" type="number"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400" />
        </div>

        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Periodo *</label>
          <select v-model="filtros.periodo"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400">
            <option value="1">1</option>
            <option value="4">Invierno</option>
            <option value="2">2</option>
            <option value="3">Verano</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Tipo de título</label>
          <select v-model="filtros.tipo_titulo"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400">
            <option value="">Todos</option>
            <option v-for="t in tiposTitulo" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>

      <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <p class="text-[12px] font-semibold text-gray-700 dark:text-slate-300 mb-2">Datos adicionales del docente</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposDocenteDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer transition-colors"
              :class="filtros.campos.includes(c.value)
                ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-500/15 dark:border-amber-500/60 dark:text-amber-300'
                : 'border-gray-300 text-gray-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/50'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos" />
              {{ c.label }}
            </label>
          </div>
        </div>

        <div>
          <p class="text-[12px] font-semibold text-gray-700 dark:text-slate-300 mb-2">Datos adicionales del título</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposTituloDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer transition-colors"
              :class="filtros.campos_titulo.includes(c.value)
                ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-500/15 dark:border-amber-500/60 dark:text-amber-300'
                : 'border-gray-300 text-gray-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/50'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos_titulo" />
              {{ c.label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 dark:bg-slate-800/60 border-t border-gray-200 dark:border-slate-700">
        <button @click="onBuscar" :disabled="loading || !filtros.anio || !filtros.periodo"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-800 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          <svg v-if="loading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Generar lista
        </button>

        <button @click="onBuscarPorGrado" :disabled="loading || !filtros.anio || !filtros.periodo"
          title="Muestra solo el Diploma Académico de cada docente (el resto de títulos queda en el contador)"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors"
          :class="gradoActivo ? 'ring-2 ring-offset-1 ring-emerald-400 dark:ring-offset-slate-800' : ''">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.5M12 14L5.84 10.578A12.083 12.083 0 003 17.5M12 14v7" />
          </svg>
          Generar por grado: Diploma académico
        </button>

        <!-- NUEVO: documentos por categoría de título -->
        <button @click="abrirCategorias" :disabled="loading || !datos.length"
          title="Elige una categoría de título y mira los documentos de los docentes"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-600 dark:hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
          </svg>
          Categorías de títulos
        </button>

        <button @click="onGenerarExcel" :disabled="!filtros.anio || !filtros.periodo || generandoExcel"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors">
          <svg v-if="generandoExcel" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Generar Excel
        </button>
      </div>
    </div>

    <div v-if="error || errorExcel"
      class="mb-4 flex items-center gap-2 p-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/40 rounded-lg text-red-600 dark:text-red-300 text-[12px]">
      {{ error || errorExcel }}
    </div>

    <!-- Resultados -->
    <div v-if="datos.length">
      <TituloResumen
        :items="resumenTitulos"
        :sin-titulo="sinTituloCount"
        :seleccionado="tituloSeleccionado"
        @select="tituloSeleccionado = ($event === tituloSeleccionado ? null : $event)"
      />

      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="flex flex-wrap items-center gap-2">
          <p class="text-[13px] font-semibold text-gray-700 dark:text-slate-300">
            Resultados ({{ datosFiltrados.length }}<span v-if="datosFiltrados.length !== datos.length"> de {{ datos.length }}</span>)
          </p>
          <template v-if="gradoActivo">
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/40">
              Con diploma académico: {{ conDiploma }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600">
              Sin diploma académico: {{ sinDiploma }}
            </span>
          </template>
        </div>

        <div class="relative w-full sm:w-72">
          <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
          </svg>
          <input v-model="busqueda" type="text" placeholder="Buscar código, nombre o título…"
            class="w-full border border-gray-300 dark:border-slate-600 rounded-lg pl-8 pr-8 py-1.5 text-[12px] text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-700 dark:focus:border-blue-400" />
          <button v-if="busqueda" @click="busqueda = ''"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:text-slate-500 dark:hover:text-slate-200 text-[14px] leading-none">✕</button>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="bg-slate-800 dark:bg-slate-950 text-white text-left">
                <th class="px-3 py-2.5 font-semibold">Código</th>
                <th class="px-3 py-2.5 font-semibold">Apellidos</th>
                <th class="px-3 py-2.5 font-semibold">Nombres</th>
                <th v-for="c in filtros.campos" :key="c" class="px-3 py-2.5 font-semibold">{{ etiqueta(c) }}</th>
                <th class="px-3 py-2.5 font-semibold">{{ gradoActivo ? 'Diploma académico' : 'Títulos registrados' }}</th>
                <th class="px-3 py-2.5 font-semibold text-center">Detalle</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in datosFiltrados" :key="row.CODIGO"
                class="border-t border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 align-top transition-colors hover:bg-sky-100/70 dark:hover:bg-sky-500/10"
                :class="i % 2 === 1 ? 'bg-sky-50 dark:bg-slate-700/40' : 'bg-white dark:bg-slate-800'">
                <td class="px-3 py-2">{{ row.CODIGO }}</td>
                <td class="px-3 py-2">{{ row.APELLIDOS }}</td>
                <td class="px-3 py-2">{{ row.NOMBRES }}</td>
                <td v-for="c in filtros.campos" :key="c" class="px-3 py-2">{{ celda(row, c) }}</td>
                <td class="px-3 py-2 min-w-[260px]">
                  <!-- Modo "por grado": solo el Diploma Académico + contador del resto -->
                  <template v-if="gradoActivo">
                    <TitulosCelda v-if="diplomaDe(row)" :titulos="[diplomaDe(row)]" :extras="filtros.campos_titulo" />
                    <p v-else class="text-gray-500 dark:text-slate-400">
                      No tiene diploma académico registrado
                    </p>

                    <button v-if="otrosCount(row) > 0" @click="toggleOtros(row)"
                      :title="abiertos[row.CODIGO] ? 'Ocultar los otros títulos' : 'Mostrar los otros títulos'"
                      class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-300 text-[11px] font-semibold whitespace-nowrap transition-colors">
                      {{ textoContador(row) }}
                      <svg class="w-3 h-3 transition-transform duration-150" :class="abiertos[row.CODIGO] ? 'rotate-180' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9" />
                      </svg>
                    </button>

                    <!-- Otros títulos del docente (desplegable) -->
                    <div v-if="otrosCount(row) > 0 && abiertos[row.CODIGO]"
                      class="mt-2 pl-3 border-l-2 border-blue-200 dark:border-blue-500/40">
                      <TitulosCelda :titulos="otrosDe(row)" :extras="filtros.campos_titulo" />
                    </div>
                  </template>

                  <TitulosCelda v-else :titulos="row.titulos" :extras="filtros.campos_titulo" />
                </td>
                <td class="px-3 py-2 text-center">
                  <button @click="verTitulosDocente(row)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-300 font-semibold whitespace-nowrap transition-colors">
                    Ver títulos
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </td>
              </tr>

              <tr v-if="!datosFiltrados.length">
                <td :colspan="5 + filtros.campos.length"
                  class="px-3 py-8 text-center text-gray-500 dark:text-slate-400">
                  Ningún resultado coincide con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="buscoAlMenosUnaVez && !loading"
      class="text-center py-10 text-[13px] text-gray-800 dark:text-slate-300">
      No se encontraron docentes con carga en ese periodo.
    </div>

    <!-- ═════ Modal: documentos por categoría de título ═════ -->
    <div v-if="modalCategorias"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="cerrarCategorias">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-xl w-full max-w-4xl max-h-[88vh] flex flex-col overflow-hidden">

        <!-- Cabecera -->
        <div class="flex items-center justify-between px-5 py-3 bg-slate-800 dark:bg-slate-950 text-white">
          <div>
            <h2 class="text-[15px] font-bold leading-tight">Documentos por categoría de título</h2>
            <p class="text-[11px] text-slate-300">Periodo {{ filtros.anio }} - {{ nombrePeriodo }}</p>
          </div>
          <button @click="cerrarCategorias" aria-label="Cerrar"
            class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors text-[16px] leading-none">✕</button>
        </div>

        <!-- Categorías registradas -->
        <div class="px-5 py-3 border-b border-gray-200 dark:border-slate-700">
          <p class="text-[12px] font-semibold text-gray-700 dark:text-slate-300 mb-2">
            Elige una categoría ({{ categoriasRegistradas.length }} registradas)
          </p>
          <div class="flex flex-wrap gap-2">
            <button v-for="c in categoriasRegistradas" :key="c.tipo" @click="seleccionarCategoria(c.tipo)"
              class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[12px] font-bold uppercase transition-all"
              :class="catSel === c.tipo
                ? 'bg-indigo-600 border-indigo-600 text-white ring-2 ring-offset-1 ring-indigo-300 dark:ring-offset-slate-800'
                : 'bg-indigo-50 border-indigo-200 text-indigo-800 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:border-indigo-500/40 dark:text-indigo-300 dark:hover:bg-indigo-500/25'">
              {{ c.tipo }}
              <span class="rounded-full px-1.5 text-[10px]"
                :class="catSel === c.tipo ? 'bg-white/25' : 'bg-white/80 dark:bg-white/15'">{{ c.docentes }}</span>
            </button>
          </div>
        </div>

        <!-- Contenido -->
        <div class="flex-1 overflow-y-auto px-5 py-3">
          <p v-if="!catSel" class="py-10 text-center text-[13px] text-gray-500 dark:text-slate-400">
            Selecciona una categoría para ver los documentos de los docentes.
          </p>

          <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-[13px] font-semibold text-gray-700 dark:text-slate-300">
                  {{ docentesPorCategoria.length }} docente{{ docentesPorCategoria.length === 1 ? '' : 's' }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/40">
                  Con PDF: {{ resumenCategoria.conPdf }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600">
                  Sin PDF: {{ resumenCategoria.sinPdf }}
                </span>
              </div>

              <div class="relative w-full sm:w-64">
                <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input v-model="busquedaCat" type="text" placeholder="Buscar docente o título…"
                  class="w-full border border-gray-300 dark:border-slate-600 rounded-lg pl-8 pr-3 py-1.5 text-[12px] text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-400" />
              </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
              <table class="w-full text-[12px]">
                <thead>
                  <tr class="bg-slate-100 dark:bg-slate-900 text-gray-700 dark:text-slate-300 text-left">
                    <th class="px-3 py-2 font-semibold w-24">Código</th>
                    <th class="px-3 py-2 font-semibold">Docente</th>
                    <th class="px-3 py-2 font-semibold">{{ catSel }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(x, i) in docentesPorCategoria" :key="x.row.CODIGO"
                    class="border-t border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 align-top"
                    :class="i % 2 === 1 ? 'bg-sky-50 dark:bg-slate-700/40' : 'bg-white dark:bg-slate-800'">
                    <td class="px-3 py-2">{{ x.row.CODIGO }}</td>
                    <td class="px-3 py-2 uppercase">{{ x.row.APELLIDOS }} {{ x.row.NOMBRES }}</td>
                    <td class="px-3 py-2 min-w-[260px]">
                      <TitulosCelda :titulos="x.titulos" :extras="filtros.campos_titulo" />
                    </td>
                  </tr>
                  <tr v-if="!docentesPorCategoria.length">
                    <td colspan="3" class="px-3 py-8 text-center text-gray-500 dark:text-slate-400">
                      Ningún docente coincide con la búsqueda.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useReporteDocente } from '../composables/useReporteDocente'
import { usePeriodoTitulos } from '../composables/usePeriodoTitulos'
import { norm, fmtFecha } from '../composables/utilsTitulo'
import { exportarReporteTitulos } from '../composables/exportarXlsx'
import TituloResumen from '../components/TituloResumen.vue'
import TitulosCelda from '../components/TitulosCelda.vue'
import { usePeriodoAcademicoActual } from '@/modules/periodos-academicos/composables/usePeriodoAcademicoActual'

const router = useRouter()

// Del composable antiguo solo usamos tipos de título y el Excel
const { tiposTitulo, cargarTiposTitulo, descargarExcel, error: errorExcel } = useReporteDocente()
const { datos, loading, error, buscar } = usePeriodoTitulos()

// Año y período actuales (fallback instantáneo por mes + resolución contra la BD)
const { anio: anioActual, periodo: periodoActual, promesa: promesaPeriodoActual } = usePeriodoAcademicoActual()

const filtros = reactive({
  anio: anioActual.value,
  // El <select> de esta vista usa value="1" (string), por eso String(...)
  periodo: String(periodoActual.value),
  tipo_titulo: '',
  campos: ['TITULO'],
  campos_titulo: [],
})

// El valor inicial de arriba es el fallback instantáneo (por mes).
// En cuanto se resuelve contra la BD real, corregimos los filtros
// (por si el rango real de algún periodo no calza con el fallback).
promesaPeriodoActual.then(() => {
  filtros.anio = anioActual.value
  filtros.periodo = String(periodoActual.value)
})

const camposDocenteDisponibles = [
  { value: 'CI', label: 'CI' },
  { value: 'FECHA_NAC', label: 'Fecha de nacimiento' },
  { value: 'SEXO', label: 'Sexo' },
  { value: 'TITULO', label: 'Título (abrev.)' },
  { value: 'FECHA_NOMBRAMIENTO', label: 'Fecha de nombramiento' },
]

// El nombre del título siempre se muestra, por eso no es opción
const camposTituloDisponibles = [
  { value: 'UNIVERSIDAD', label: 'Universidad' },
  { value: 'PAIS', label: 'País' },
  { value: 'FECHA_TITULO', label: 'Fecha del título' },
  { value: 'NUMERO', label: 'Número' },
]

const etiqueta = (campo) =>
  camposDocenteDisponibles.find(c => c.value === campo)?.label || campo

function celda(row, c) {
  if (c === 'FECHA_NAC' || c === 'FECHA_NOMBRAMIENTO') return fmtFecha(row[c])
  return row[c] ?? ''
}

const buscoAlMenosUnaVez = ref(false)
const generandoExcel = ref(false)
const tituloSeleccionado = ref(null)
const busqueda = ref('')

// ── Modo "por grado": solo se muestra el Diploma Académico de cada docente ──
const GRADO_DIPLOMA = 'DIPLOMA ACADÉMICO'
const gradoActivo = ref(null) // null = lista normal | 'DIPLOMA ACADÉMICO'

// Compara sin tildes ni mayúsculas (ej. "Diploma Academico" == "DIPLOMA ACADÉMICO")
const sinTildes = (s) =>
  String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toUpperCase()

const esDiploma = (t) => sinTildes(t.tipo_titulo) === sinTildes(GRADO_DIPLOMA)

// Primer diploma académico del docente (o null si no tiene)
const diplomaDe = (row) => row.titulos.find(esDiploma) || null

// Títulos que quedan fuera de la celda principal (otros grados + diplomas repetidos)
const otrosDe = (row) => {
  const d = diplomaDe(row)
  return row.titulos.filter(t => t !== d)
}
const otrosCount = (row) => otrosDe(row).length

// Filas con el contador desplegado (clave: CODIGO del docente)
const abiertos = ref({})
const toggleOtros = (row) => {
  abiertos.value[row.CODIGO] = !abiertos.value[row.CODIGO]
}

function textoContador(row) {
  const n = otrosCount(row)
  if (diplomaDe(row)) return `+${n} ${n === 1 ? 'título más' : 'títulos más'}`
  return `Tiene ${n} ${n === 1 ? 'otro título' : 'otros títulos'}`
}

const conDiploma = computed(() => datos.value.filter(r => diplomaDe(r)).length)
const sinDiploma = computed(() => datos.value.length - conDiploma.value)

// ── Documentos por categoría de título (modal) ──────────────────────────────
// Las categorías salen de los títulos realmente registrados en los docentes
// del periodo cargado (campo tipo_titulo), con el número de docentes de cada una.
const modalCategorias = ref(false)
const catSel = ref('')
const busquedaCat = ref('')

const claveTipo = (t) => t.tipo_titulo || 'Sin tipo'

const categoriasRegistradas = computed(() => {
  const mapa = new Map() // tipo -> Set de CODIGO de docentes
  for (const r of datos.value) {
    for (const t of r.titulos) {
      const k = claveTipo(t)
      if (!mapa.has(k)) mapa.set(k, new Set())
      mapa.get(k).add(r.CODIGO)
    }
  }
  return [...mapa.entries()]
    .map(([tipo, set]) => ({ tipo, docentes: set.size }))
    .sort((a, b) => b.docentes - a.docentes || a.tipo.localeCompare(b.tipo))
})

// Cada docente con SOLO los títulos de la categoría elegida (+ buscador)
const docentesPorCategoria = computed(() => {
  if (!catSel.value) return []
  const q = norm(busquedaCat.value)

  return datos.value
    .map(row => ({ row, titulos: row.titulos.filter(t => claveTipo(t) === catSel.value) }))
    .filter(x => x.titulos.length)
    .filter(x => {
      if (!q) return true
      return [x.row.CODIGO, x.row.APELLIDOS, x.row.NOMBRES, ...x.titulos.map(t => t.nombre_titulo)]
        .map(norm).join(' ').includes(q)
    })
})

const resumenCategoria = computed(() => {
  let conPdf = 0
  let sinPdf = 0
  for (const x of docentesPorCategoria.value) {
    for (const t of x.titulos) {
      if (t.tiene_archivo) conPdf++
      else sinPdf++
    }
  }
  return { conPdf, sinPdf }
})

const nombrePeriodo = computed(() => {
  const nombres = { 1: '1', 2: '2', 3: 'Verano', 4: 'Invierno' }
  return nombres[Number(filtros.periodo)] ?? filtros.periodo
})

function abrirCategorias() {
  busquedaCat.value = ''
  modalCategorias.value = true
}

function cerrarCategorias() {
  modalCategorias.value = false
}

function seleccionarCategoria(tipo) {
  busquedaCat.value = ''
  catSel.value = catSel.value === tipo ? '' : tipo
}

async function ejecutarBusqueda(grado = null) {
  buscoAlMenosUnaVez.value = true
  tituloSeleccionado.value = null
  busqueda.value = ''
  abiertos.value = {}
  catSel.value = ''
  busquedaCat.value = ''
  // Por grado se traen TODOS los docentes (sin filtrar por tipo en el backend),
  // para poder avisar a quienes no tienen diploma académico.
  await buscar({
    anio: filtros.anio,
    periodo: filtros.periodo,
    tipo_titulo: grado ? '' : filtros.tipo_titulo,
  })
  gradoActivo.value = grado
}

async function onBuscar() {
  await ejecutarBusqueda(null)
}

async function onBuscarPorGrado() {
  await ejecutarBusqueda(GRADO_DIPLOMA)
}

// Valor de cada "dato adicional del título" para un título dado
function valorExtraTitulo(t, campo) {
  switch (campo) {
    case 'UNIVERSIDAD': return t.universidad ?? ''
    case 'PAIS': return t.pais ?? ''
    case 'FECHA_TITULO': return fmtFecha(t.fecha_titulo) ?? ''
    case 'NUMERO': return t.numero ?? ''
    default: return ''
  }
}

// Excel en modo "por grado": mismo formato del reporte original, con
// EXACTAMENTE lo que muestra la tabla (respeta chip y buscador), una fila por
// docente con su diploma académico y sin los contadores.
function excelPorGrado() {
  const extras = camposTituloDisponibles.filter(c => filtros.campos_titulo.includes(c.value))

  const encabezados = [
    'Nº', 'Código', 'Apellidos', 'Nombres',
    'Tipo de Título',
    ...filtros.campos.map(etiqueta),
    'Nombre del Título',
    ...extras.map(c => c.label),
  ]

  const filas = datosFiltrados.value.map((row, i) => {
    const d = diplomaDe(row)
    return [
      i + 1, row.CODIGO, row.APELLIDOS, row.NOMBRES,
      d ? (d.tipo_titulo || GRADO_DIPLOMA) : '—',
      ...filtros.campos.map(c => celda(row, c)),
      d ? (d.nombre_titulo || 'Sin nombre de título') : 'No tiene diploma académico registrado',
      ...extras.map(c => (d ? valorExtraTitulo(d, c.value) : '')),
    ]
  })

  exportarReporteTitulos({
    nombre: `Reporte_Docentes_Diploma_Academico_${filtros.anio}-${filtros.periodo}.xlsx`,
    hoja: 'Diploma académico',
    anio: filtros.anio,
    periodo: filtros.periodo,
    encabezados,
    filas,
    columnasCentradas: [0, 1],
  })
}

async function onGenerarExcel() {
  generandoExcel.value = true
  try {
    if (gradoActivo.value && datosFiltrados.value.length) {
      // Por grado: se arma en el navegador con el formato del reporte
      excelPorGrado()
    } else {
      // Lista normal: Excel del backend, como siempre
      await descargarExcel({
        ...filtros,
        campos_titulo: [...new Set(['NOMBRE_TITULO', ...filtros.campos_titulo])],
      })
    }
  } catch (e) {
    // el error queda en errorExcel
  } finally {
    generandoExcel.value = false
  }
}

// ── Chips ──
const resumenTitulos = computed(() => {
  const mapa = {}
  for (const r of datos.value) {
    if (!r.titulos.length) continue
    const clave = r.TITULO || 'Sin abrev.'
    mapa[clave] = (mapa[clave] || 0) + 1
  }
  return Object.entries(mapa)
    .map(([titulo, docentes]) => ({ titulo, docentes }))
    .sort((a, b) => b.docentes - a.docentes)
})

const sinTituloCount = computed(() => datos.value.filter(r => !r.titulos.length).length)

// ── Filtro por chip + buscador ──
const datosFiltrados = computed(() => {
  let lista = datos.value

  if (tituloSeleccionado.value === '__SIN__') {
    lista = lista.filter(r => !r.titulos.length)
  } else if (tituloSeleccionado.value) {
    lista = lista.filter(r => r.titulos.length && (r.TITULO || 'Sin abrev.') === tituloSeleccionado.value)
  }

  const q = norm(busqueda.value)
  if (q) {
    lista = lista.filter(r =>
      [r.CODIGO, r.APELLIDOS, r.NOMBRES, r.TITULO, ...r.titulos.map(t => t.nombre_titulo), ...r.titulos.map(t => t.tipo_titulo)]
        .map(norm).join(' ').includes(q)
    )
  }
  return lista
})

function verTitulosDocente(row) {
  router.push({
    name: 'DocenteTitulos',
    params: { codigo: row.CODIGO },
    query: { anio: filtros.anio, periodo: filtros.periodo },
  })
}

onMounted(async () => {
  cargarTiposTitulo()

  // Espera a que el año/período se resuelvan contra la BD real
  // y recién entonces genera la lista automáticamente.
  try {
    await promesaPeriodoActual
  } catch (e) {
    // si falla, seguimos con el fallback por mes que ya está en filtros
  }

  if (filtros.anio && filtros.periodo) {
    onBuscar()
  }
})
</script>