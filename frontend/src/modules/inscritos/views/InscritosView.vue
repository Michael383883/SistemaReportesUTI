<template>
  <div class="min-h-screen bg-slate-100">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-3 px-8 pt-4">
      <h1 class="text-xl font-bold text-black tracking-tight m-0 mb-0.5">
        Lista de Inscritos
      </h1>
    </div>

    <!-- FILTROS -->
    <div class="border-b border-slate-700 px-8 pb-4 flex flex-wrap gap-3 items-end">

      <!-- Año -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Año</label>
        <input
          v-model.number="filtros.anio"
          type="number"
          min="2000"
          max="2099"
          @keyup.enter="handleBuscar"
          class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 placeholder-slate-500 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
                 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        />
      </div>

      <!-- Período -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Período</label>
        <select
          v-model.number="filtros.periodo"
          @change="handleBuscar"
          class="w-36 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
        >
          <option :value="1">1</option>
          <option :value="2">2</option>
          <option :value="3">3</option>
          <option :value="4">4</option>
        </select>
      </div>

      <!-- Área (multi-selección) -->
      <div class="flex flex-col gap-1.5 relative" ref="areaDropdownRef">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Área</label>

        <button
          type="button"
          @click.stop="mostrarMenuArea = !mostrarMenuArea"
          class="w-56 flex items-center justify-between gap-2 bg-slate-800 border border-slate-700 rounded-lg
                 text-slate-100 text-sm px-3 py-2 outline-none transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
        >
          <span class="truncate text-left">
            {{ areaLabelResumen }}
          </span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
               :style="mostrarMenuArea ? 'transform: rotate(180deg);' : ''" style="transition: transform 0.15s"
               class="shrink-0 text-slate-400">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>

        <!-- Backdrop -->
        <div v-if="mostrarMenuArea" class="fixed inset-0 z-40" @click="mostrarMenuArea = false" />

        <Transition
          enter-active-class="transition-all duration-150 ease-out"
          enter-from-class="opacity-0 scale-95 -translate-y-1"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition-all duration-100 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
          <div
            v-if="mostrarMenuArea"
            class="absolute left-0 top-full mt-1.5 z-50 w-64
                   bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden max-h-80 overflow-y-auto"
          >
            <div class="px-4 pt-3 pb-1 flex items-center justify-between sticky top-0 bg-white">
              <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                Áreas
              </p>
              <button
                v-if="filtros.area.length > 0"
                type="button"
                @click="filtros.area = []"
                class="text-[0.65rem] font-semibold text-amber-600 hover:text-amber-700"
              >
                Limpiar
              </button>
            </div>

            <label
              v-for="a in AREAS"
              :key="a.value"
              class="w-full flex items-center gap-3 px-4 py-2 text-sm text-slate-700
                     hover:bg-slate-50 transition-colors cursor-pointer select-none"
            >
              <input
                type="checkbox"
                :value="a.value"
                v-model="filtros.area"
                class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500/40 focus:ring-offset-0 cursor-pointer"
              />
              <div>
                <div class="font-medium leading-tight">{{ a.value }}</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ a.label }}</div>
              </div>
            </label>
          </div>
        </Transition>
      </div>

      <!-- Buscar Docente (botón unificado: Buscar / Ver todos) -->
      <div class="flex-1 min-w-[260px] flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Buscar Docente</label>
        <div class="flex gap-2">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Código o apellidos..."
            @keyup.enter="handleBuscar"
            class="flex-1 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                   placeholder-slate-500 transition-all duration-150
                   focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
          <button
            @click="handleBuscar"
            :disabled="loading"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                   bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-100
                   transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed
                   shadow-lg shadow-amber-500/20"
          >
            <svg :class="loading ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5">
              <template v-if="loading">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
              </template>
              <template v-else-if="busqueda.trim()">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </template>
              <template v-else>
                <line x1="8" y1="6" x2="21" y2="6"/>
                <line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/>
                <line x1="3" y1="18" x2="3.01" y2="18"/>
              </template>
            </svg>
            {{ loading ? 'Buscando...' : (busqueda.trim() ? 'Buscar' : 'Ver todos') }}
          </button>
        </div>
      </div>

      <!-- Botón PDF con menú desplegable (compactado) -->
      <div class="flex flex-col" ref="pdfDropdownRef">
        <label class="text-[0.68rem] invisible">PDF</label>

        <div class="relative">

          <!-- Botón partido: ambos abren el menú -->
          <div
            class="inline-flex rounded-full overflow-visible border border-red-700/40 shadow-lg shadow-red-900/20"
            :class="(loadingPdf || dataFiltrada.length === 0) ? 'opacity-40 pointer-events-none' : ''"
          >
            <!-- Botón principal -->
            <button
              @click.stop="mostrarMenuPdf = !mostrarMenuPdf"
              class="inline-flex items-center gap-2 pl-5 pr-4 py-2 text-sm font-semibold
                     bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     rounded-l-full transition-all duration-150"
            >
              <svg
                :class="loadingPdf ? 'animate-spin' : ''"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <template v-if="loadingPdf">
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round" />
                </template>
                <template v-else>
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </template>
              </svg>

              Generar PDF
            </button>

            <!-- Flecha -->
            <button
              @click.stop="mostrarMenuPdf = !mostrarMenuPdf"
              class="px-3 py-2 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     border-l border-red-600/60 rounded-r-full transition-all duration-150"
              aria-label="Más opciones de PDF"
            >
              <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                :style="mostrarMenuPdf ? 'transform: rotate(180deg);' : ''"
                style="transition: transform 0.15s"
              >
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>
          </div>

          <!-- Backdrop -->
          <div
            v-if="mostrarMenuPdf"
            class="fixed inset-0 z-40"
            @click="mostrarMenuPdf = false"
          />

          <!-- Menú desplegable (compacto, con scroll interno) -->
          <Transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
          >
            <div
              v-if="mostrarMenuPdf"
              class="absolute right-0 top-full mt-1.5 z-50
                     bg-white border border-slate-200 rounded-xl
                     shadow-xl overflow-hidden w-56 max-h-[26rem] overflow-y-auto"
            >
              <!-- Lista completa -->
              <div class="px-3 pt-2.5 pb-1 sticky top-0 bg-white z-10">
                <p class="text-[0.6rem] font-semibold tracking-widest uppercase text-slate-400">
                  Lista completa
                </p>
              </div>

              <!-- Ver PDF Lista -->
              <button
                @click="generarPDFLista('ver'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Ver lista de estudiantes</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Lista -->
              <button
                @click="generarPDFLista('descargar'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 pb-2 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Descargar lista de estudiantes</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Guardar en tu equipo</div>
                </div>
              </button>

              <div class="border-t border-slate-100 mx-3"></div>

              <div class="px-3 pt-2.5 pb-1">
                <p class="text-[0.6rem] font-semibold tracking-widest uppercase text-slate-400">
                  Aprobados/reprobados completo
                </p>
              </div>

              <!-- Ver PDF Aprobados/Reprobados Completo -->
              <button
                @click="generarPDFAprobadosResumido('ver'); mostrarMenuPdf = false"
                :disabled="generandoAprobadosResumido"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Ver completo</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Aprobados/Reprobados Completo -->
              <button
                @click="generarPDFAprobadosResumido('descargar'); mostrarMenuPdf = false"
                :disabled="generandoAprobadosResumido"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 pb-2 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Descargar completo</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Guardar en tu equipo</div>
                </div>
              </button>

              <div class="border-t border-slate-100 mx-3"></div>

              <!-- Resumen de totales -->
              <div class="px-3 pt-2.5 pb-1">
                <p class="text-[0.6rem] font-semibold tracking-widest uppercase text-slate-400">
                  Totales por docente
                </p>
              </div>

              <!-- Ver PDF Totales -->
              <button
                @click="generarPDFTotales('ver'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Ver totales</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Totales -->
              <button
                @click="generarPDFTotales('descargar'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 pb-2 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Descargar totales</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Guardar en tu equipo</div>
                </div>
              </button>

              <div class="border-t border-slate-100 mx-3"></div>

              <!-- Aprobados y reprobados resumido -->
              <div class="px-3 pt-2.5 pb-1">
                <p class="text-[0.6rem] font-semibold tracking-widest uppercase text-slate-400">
                  Aprobados/reprobados resumido
                </p>
              </div>

              <!-- Ver PDF Aprobados/Reprobados -->
              <button
                @click="generarPDFAprobados('ver'); mostrarMenuPdf = false"
                :disabled="generandoAprobados"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Ver resumido</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Aprobados/Reprobados -->
              <button
                @click="generarPDFAprobados('descargar'); mostrarMenuPdf = false"
                :disabled="generandoAprobados"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 pb-2.5 text-[0.8rem] text-slate-700
                       hover:bg-slate-50 transition-colors text-left disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div class="min-w-0">
                  <div class="font-medium leading-tight truncate">Descargar resumido</div>
                  <div class="text-[0.65rem] text-slate-400 leading-tight">Guardar en tu equipo</div>
                </div>
              </button>
            </div>
          </Transition>

        </div>
      </div>

    </div>

    <!-- ERROR -->
    <div
      v-if="error"
      class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center gap-3"
    >
      <span>⚠️ {{ error }}</span>
      <button @click="handleBuscar" class="text-xs font-bold underline ml-auto">Reintentar</button>
    </div>

    <!-- LOADING -->
    <div
      v-if="loading"
      class="flex flex-col items-center gap-3 py-20 text-slate-500"
    >
      <div class="w-10 h-10 border-4 border-slate-200 border-t-amber-500 rounded-full animate-spin" />
      <span>Cargando inscritos...</span>
    </div>

    <!-- EMPTY STATE -->
    <div
      v-else-if="!loading && dataFiltrada.length === 0 && !error"
      class="flex flex-col items-center gap-3 py-24 text-slate-400"
    >
      <template v-if="!yaSeBusco">
        <span class="text-6xl">📋</span>
        <p>Seleccioná año y período, luego hacé clic en <strong class="text-slate-600">Ver todos</strong> o escribí un docente y hacé clic en <strong class="text-slate-600">Buscar</strong>.</p>
      </template>

      <template v-else-if="ultimaBusquedaFueDocente">
        <span class="text-6xl">🔍</span>
        <p>Docente no encontrado para <strong class="text-slate-600">{{ filtros.anio }}-{{ filtros.periodo }}</strong>.</p>
      </template>

      <template v-else>
        <span class="text-6xl">📋</span>
        <p>No hay lista de inscritos en dicha gestión y periodo (<strong class="text-slate-600">{{ filtros.anio }}-{{ filtros.periodo }}</strong>).</p>
      </template>
    </div>

    <!-- CONTENIDO -->
    <div v-else-if="!loading && dataFiltrada.length" id="reporte-imprimible" class="px-8 py-4">

      <!-- CABECERA REPORTE -->
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex flex-wrap justify-between items-center mb-3 text-sm text-slate-500 gap-2">
        <span>Generado: <strong class="text-slate-700">{{ fechaActual }}</strong></span>
        <span class="text-slate-300 hidden sm:block">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ dataFiltrada.length }}</strong></span>
        <span class="text-slate-300 hidden sm:block">|</span>
        <span>Total inscritos:
          <strong class="text-slate-700">{{ totalGlobal }}</strong>
        </span>
      </div>

      <!-- CARDS -->
      <DocenteInscritosCard
        v-for="docente in dataFiltrada"
        :key="docente.cod_docente"
        :docente="docente"
      />

      <!-- FOOTER -->
      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · La carga incluye todos los grupos asignados.
      </div>
    </div>

  </div>
</template>
<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import DocenteInscritosCard from '../components/DocenteInscritosCard.vue'
import { useInscritos } from '../composables/useInscritos'
import { useReporteInscritosLista } from '../composables/useReporteInscritosLista'
import { useReporteInscritosTotales } from '../composables/useReporteInscritosTotales'
import { useInscritosAprobados } from '../composables/useInscritosAprobados'
import { useReporteAprobadosReprobados } from '../composables/useReporteAprobadosReprobados'

import { useResumenPorGrupo } from '../composables/useResumenPorGrupo'
import { useReporteAprobadosReprobadosResumido } from '../composables/useReporteAprobadosReprobadosResumido'
import { mostrarLoaderPdf } from '../utils/pdfLoader'

// ─── Áreas disponibles (deben coincidir con el CASE de PLAN->CARRERA del backend) ───
const AREAS = [
  { value: 'ADM', label: 'Administración de Empresas' },
  { value: 'FIN', label: 'Ingeniería Financiera' },
  { value: 'ECO', label: 'Economía' },
  { value: 'CCP', label: 'Contaduría Pública' },
  { value: 'COM', label: 'Ingeniería Comercial' },
]

const { data, loading, error, fetchInscritos } = useInscritos()
const { generandoLista, exportarListaCompleta } = useReporteInscritosLista()
const { generandoResumen, exportarResumenTotales } = useReporteInscritosTotales()
//ap
const { data: dataResumenGrupo, fetchResumenPorGrupo } = useResumenPorGrupo()
const { generandoAprobadosResumido, exportarAprobadosReprobadosResumido } = useReporteAprobadosReprobadosResumido()

// Datos y reporte de aprobados/reprobados (fetch propio, endpoint distinto)
const { data: dataAprobados, fetchAprobadosReprobados } = useInscritosAprobados()
const { generandoAprobados, exportarAprobadosReprobados } = useReporteAprobadosReprobados()

const filtros = ref({
  anio: new Date().getFullYear(),
  periodo: 1,
  area: [], // array vacío = todas las áreas
})
const busqueda = ref('')

const mostrarMenuPdf = ref(false)
const pdfDropdownRef = ref(null)
const mostrarMenuArea = ref(false)
const areaDropdownRef = ref(null)

// ─── Estado para distinguir los mensajes del empty state ─────────────────
const yaSeBusco = ref(false)
const ultimaBusquedaFueDocente = ref(false)

const loadingPdf = computed(() =>
  generandoLista.value || generandoResumen.value || generandoAprobados.value || generandoAprobadosResumido.value
)
// Texto resumen del botón: "Todas las áreas" / "ADM" / "ADM, FIN" / "3 áreas seleccionadas"
const areaLabelResumen = computed(() => {
  const sel = filtros.value.area
  if (sel.length === 0) return 'Todas las áreas'
  if (sel.length <= 2) return sel.join(', ')
  return `${sel.length} áreas seleccionadas`
})

// ─── Filtra por una o varias áreas (lista de inscritos) ──────────────────
const dataFiltrada = computed(() => {
  if (filtros.value.area.length === 0) return data.value

  return data.value
    .map(docente => {
      const carrerasFiltradas = docente.carreras.filter(
        c => filtros.value.area.includes(c.carrera)
      )
      if (carrerasFiltradas.length === 0) return null

      const total_inscritos = carrerasFiltradas.reduce((s, c) => s + (c.subtotal ?? 0), 0)
      const total_examen_mesa = carrerasFiltradas.reduce((s, c) => s + (c.subtotal_examen_mesa ?? 0), 0)

      return {
        ...docente,
        carreras: carrerasFiltradas,
        total_inscritos,
        total_examen_mesa,
      }
    })
    .filter(Boolean)
})

// ─── Filtra por una o varias áreas (aprobados/reprobados) ────────────────
const dataAprobadosFiltrada = computed(() => {
  if (filtros.value.area.length === 0) return dataAprobados.value

  return dataAprobados.value
    .map(docente => {
      const carrerasFiltradas = docente.carreras.filter(
        c => filtros.value.area.includes(c.carrera)
      )
      if (carrerasFiltradas.length === 0) return null

      const total_inscritos = carrerasFiltradas.reduce((s, c) => s + (c.subtotal_inscritos ?? 0), 0)
      const total_aprobados = carrerasFiltradas.reduce((s, c) => s + (c.subtotal_aprobados ?? 0), 0)
      const total_reprobados = carrerasFiltradas.reduce((s, c) => s + (c.subtotal_reprobados ?? 0), 0)

      return {
        ...docente,
        carreras: carrerasFiltradas,
        total_inscritos,
        total_aprobados,
        total_reprobados,
      }
    })
    .filter(Boolean)
})

const totalGlobal = computed(() =>
  dataFiltrada.value.reduce((s, d) => s + (d.total_inscritos ?? 0), 0)
)

const fechaActual = computed(() =>
  new Date().toLocaleString('es-BO', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
)


// ─── Aprobados/Reprobados Completo (detalle plano por grupo) ────────────
async function generarPDFAprobadosResumido(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
    mostrarLoaderPdf(ventana, 'Generando reporte completo...')
  }

  await fetchResumenPorGrupo(filtros.value.anio, filtros.value.periodo)

  let filas = dataResumenGrupo.value

  // Filtro de área (client-side, porque este endpoint no lo soporta en backend)
  if (filtros.value.area.length > 0) {
    filas = filas.filter(f => filtros.value.area.includes(f.CARRERA))
  }

  // Filtro de búsqueda de docente
  const q = busqueda.value.trim()
  if (q) {
    if (/^\d+$/.test(q)) {
      filas = filas.filter(f => String(f.COD_DOCENTE) === q) // 👈 verificá el nombre real del campo
    } else {
      filas = filas.filter(f =>
        (f.NOMBRE_DOCENTE ?? '').toLowerCase().includes(q.toLowerCase())
      )
    }
  }

  await exportarAprobadosReprobadosResumido(
    filas,
    filtros.value.anio,
    filtros.value.periodo,
    modo,
    ventana
  )
}

// Cierra ambos menús al hacer click fuera
function onClickFuera(e) {
  if (pdfDropdownRef.value && !pdfDropdownRef.value.contains(e.target)) {
    mostrarMenuPdf.value = false
  }
  if (areaDropdownRef.value && !areaDropdownRef.value.contains(e.target)) {
    mostrarMenuArea.value = false
  }
}
onMounted(() => document.addEventListener('click', onClickFuera))
onBeforeUnmount(() => document.removeEventListener('click', onClickFuera))

// ─── Botón único: si hay texto de búsqueda filtra, si está vacío trae todos ───
async function handleBuscar() {
  const q = busqueda.value.trim()
  await fetchInscritos(filtros.value.anio, filtros.value.periodo)

  yaSeBusco.value = true
  ultimaBusquedaFueDocente.value = !!q

  if (!q) return

  if (/^\d+$/.test(q)) {
    data.value = data.value.filter(d => d.cod_docente === q)
  } else {
    data.value = data.value.filter(d =>
      `${d.apellidos} ${d.nombres}`.toLowerCase().includes(q.toLowerCase())
    )
  }
}

async function generarPDFLista(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
    mostrarLoaderPdf(ventana, 'Generando lista de inscritos...')
  }
  await exportarListaCompleta(dataFiltrada.value, filtros.value.anio, filtros.value.periodo, modo, ventana)
}

async function generarPDFTotales(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
    mostrarLoaderPdf(ventana, 'Generando resumen de totales...')
  }

  // Traer también aprobados/reprobados, porque fetchInscritos no los incluye
  await fetchAprobadosReprobados(filtros.value.anio, filtros.value.periodo)

  // Mapa cod_docente -> totales de aprobados/reprobados
  const mapaAprobados = new Map(
    dataAprobados.value.map(d => [d.cod_docente, d])
  )

  const dataConAprobados = dataFiltrada.value.map(docente => {
    const info = mapaAprobados.get(docente.cod_docente)
    return {
      ...docente,
      total_aprobados: info?.total_aprobados ?? 0,
      total_reprobados: info?.total_reprobados ?? 0,
    }
  })

  await exportarResumenTotales(dataConAprobados, filtros.value.anio, filtros.value.periodo, modo, ventana)
}

// ─── Aprobados/Reprobados resumido (matriz docente x carrera) ───────────
async function generarPDFAprobados(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
    mostrarLoaderPdf(ventana, 'Generando aprobados y reprobados...')
  }

  await fetchAprobadosReprobados(filtros.value.anio, filtros.value.periodo)

  // Aplica el mismo filtro de búsqueda que usa handleBuscar
  const q = busqueda.value.trim()
  if (q) {
    if (/^\d+$/.test(q)) {
      dataAprobados.value = dataAprobados.value.filter(d => d.cod_docente === q)
    } else {
      dataAprobados.value = dataAprobados.value.filter(d =>
        `${d.apellidos} ${d.nombres}`.toLowerCase().includes(q.toLowerCase())
      )
    }
  }

  await exportarAprobadosReprobados(
    dataAprobadosFiltrada.value, // ya filtra por área
    filtros.value.anio,
    filtros.value.periodo,
    modo,
    ventana
  )
}



</script>