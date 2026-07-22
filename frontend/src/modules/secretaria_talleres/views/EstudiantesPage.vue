<template>
  <div class="min-h-screen bg-slate-50">

    <!-- ===== HEADER ===== -->
    <div class="border-b border-slate-200 ">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <!-- Título -->
        <div>
          <h1 class="text-xl font-bold text-slate-1000 tracking-tight">
            Estudiantes en Talleres
          </h1>
          <p class="text-sm text-slate-500 mt-0.5 flex items-center gap-2">
            <template v-if="filtros.anio && filtros.periodo">
              Gestión · {{ PERIODOS[filtros.periodo] || filtros.periodo }} / {{ filtros.anio }}
              <span
                v-if="!gestionEsAutomatica"
                class="text-[11px] font-semibold text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5"
              >
                seleccionado manualmente
              </span>
            </template>
            <template v-else>
              Gestión · cargando período actual...
            </template>
          </p>
        </div>

        <!-- Zona derecha: badge + botón Generar -->
        <div class="flex flex-wrap items-center gap-2">

          <!-- Badge total -->
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-blue-700 text-sm font-semibold border border-blue-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 11-4 0 2 2 0 014 0zM5 16a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ estudiantesFiltrados.length }} estudiante{{ estudiantesFiltrados.length !== 1 ? 's' : '' }}
          </div>

          <!-- ===== Botón GENERAR (agrupa Excel / Excel+Contacto / Reporte PDF) ===== -->
          <div class="relative" ref="generarDropdownRef">

            <div
              class="inline-flex rounded-full overflow-hidden border border-emerald-700/30 shadow-sm shadow-emerald-900/10"
              :class="!estudiantesFiltrados.length ? 'opacity-40 pointer-events-none' : ''"
            >
              <!-- Botón principal -->
              <button
                @click.stop="mostrarMenuGenerar = !mostrarMenuGenerar"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold
                       bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white
                       transition-all duration-150"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Generar
              </button>

              <!-- Flecha -->
              <button
                @click.stop="mostrarMenuGenerar = !mostrarMenuGenerar"
                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white
                       border-l border-emerald-500/50 transition-all duration-150"
                aria-label="Más opciones"
              >
                <svg
                  width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                  :style="mostrarMenuGenerar ? 'transform: rotate(180deg);' : ''"
                  style="transition: transform 0.15s"
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
            </div>

            <!-- Backdrop para cerrar al hacer click fuera -->
            <div v-if="mostrarMenuGenerar" class="fixed inset-0 z-40" @click="mostrarMenuGenerar = false" />

            <!-- Menú desplegable -->
            <Transition
              enter-active-class="transition-all duration-150 ease-out"
              enter-from-class="opacity-0 scale-95 -translate-y-1"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition-all duration-100 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="mostrarMenuGenerar"
                class="absolute right-0 top-full mt-1.5 z-50
                       bg-white border border-slate-200 rounded-xl
                       shadow-xl overflow-hidden w-72"
              >
                <!-- ── Lista de estudiantes (Excel simple) ── -->
                <div class="px-4 pt-3 pb-1">
                  <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                    Lista de estudiantes
                  </p>
                </div>

                <button
                  @click="exportarNormal('ver'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Ver lista</div>
                    <div class="text-xs text-slate-400 mt-0.5">Vista previa rápida</div>
                  </div>
                </button>

                <button
                  @click="exportarNormal('descargar'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Descargar Excel</div>
                    <div class="text-xs text-slate-400 mt-0.5">Nombre, código y carrera</div>
                  </div>
                </button>

                <div class="border-t border-slate-100 mx-4"></div>

                <!-- ── Lista con datos de contacto (Excel detalle) ── -->
                <div class="px-4 pt-3 pb-1">
                  <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                    Lista con datos de contacto
                  </p>
                </div>

                <button
                  @click="exportarDetalle('ver'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Ver lista</div>
                    <div class="text-xs text-slate-400 mt-0.5">Incluye correo y celular</div>
                  </div>
                </button>

                <button
                  @click="exportarDetalle('descargar'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Descargar Excel</div>
                    <div class="text-xs text-slate-400 mt-0.5">Con datos de contacto</div>
                  </div>
                </button>

                <div class="border-t border-slate-100 mx-4"></div>

                <!-- ── Reporte por materia (PDF) ── -->
                <div class="px-4 pt-3 pb-1">
                  <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                    Reporte por materia
                  </p>
                </div>

                <button
                  @click="generarReporte('ver'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Ver PDF</div>
                    <div class="text-xs text-slate-400 mt-0.5">Abrir en nueva pestaña</div>
                  </div>
                </button>

                <button
                  @click="generarReporte('descargar'); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 pb-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Descargar PDF</div>
                    <div class="text-xs text-slate-400 mt-0.5">Guardar en tu equipo</div>
                  </div>
                </button>
              </div>
            </Transition>

          </div>
          <!-- ===== FIN Botón GENERAR ===== -->

        </div>
      </div>
    </div>

    <!-- =======================================================
     BUSCADOR + FILTROS
      ======================================================== -->
      <div >

        <!-- Barra superior -->
        <div class="flex items-center gap-3">

          <!-- Selector de gestión (año/periodo) editable -->
          <div class="flex items-center gap-1.5 shrink-0">
            <select
              v-model="filtros.periodo"
              class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
              title="Periodo académico"
            >
              <option v-for="(nombre, codigo) in PERIODOS" :key="codigo" :value="codigo">
                {{ nombre }}
              </option>
            </select>

            <select
              v-model="filtros.anio"
              class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
              title="Año"
            >
              <option v-for="a in aniosDisponibles" :key="a" :value="a">
                {{ a }}
              </option>
            </select>

            <button
              v-if="!gestionEsAutomatica"
              @click="volverAGestionActual"
              title="Volver a la gestión actual detectada por el sistema"
              class="h-10 px-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition shrink-0"
            >
              Hoy
            </button>
          </div>

          <!-- Buscador -->
          <div class="relative flex-1">
            <svg xmlns="http://www.w3.org/2000/svg"
              class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">

              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>

            </svg>

            <input
              v-model="filtros.busqueda"
              type="text"
              placeholder="Buscar estudiante, docente o código..."
              class="w-full h-10 rounded-xl border border-slate-200 bg-white
                    pl-11 pr-4 text-sm
                    text-slate-700
                    placeholder:text-slate-400
                    focus:outline-none
                    focus:ring-2
                    focus:ring-blue-100
                    focus:border-blue-500
                    transition"/>
          </div>

          <!-- Botón filtros -->
          <button
            @click="mostrarFiltros = !mostrarFiltros"
            class="flex items-center gap-2 h-10 px-4 rounded-xl
                  border border-slate-200 bg-white
                  hover:bg-slate-50
                  transition
                  shrink-0">

            <svg xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4 text-slate-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">

              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L15 12v6l-6 2v-8L3.2 4.6A1 1 0 013 4z"/>

            </svg>

            <span class="text-sm font-medium text-slate-700">
              Filtros
            </span>

            <!-- Badge cantidad -->
            <span
              v-if="filtros.plan || filtros.materia || filtros.grupo"
              class="flex items-center justify-center
                    min-w-[20px] h-5 px-1
                    rounded-full
                    bg-blue-600
                    text-white
                    text-[11px]
                    font-semibold">

              {{
                [filtros.plan, filtros.materia, filtros.grupo]
                .filter(Boolean).length
              }}

            </span>

            <svg xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4 text-slate-500 transition duration-200"
              :class="{ 'rotate-180': mostrarFiltros }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">

              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"/>

            </svg>

          </button>

        </div>

        <!-- Panel filtros -->
        <Transition
          enter-active-class="transition-all duration-200"
          leave-active-class="transition-all duration-150"
          enter-from-class="opacity-0 -translate-y-2"
          leave-to-class="opacity-0 -translate-y-2">

          <div
            v-if="mostrarFiltros"
            class="mt-3 rounded-xl border border-slate-200
                  bg-slate-50 p-4">

            <div class="flex items-center justify-between mb-3">

              <span class="text-sm font-semibold text-slate-700">
                Filtros avanzados
              </span>

              <button
                v-if="filtros.plan || filtros.materia || filtros.grupo"
                @click="limpiarFiltros"
                class="text-xs font-medium
                      text-blue-600
                      hover:text-blue-700
                      transition">

                Restablecer

              </button>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

              <!-- Carrera -->
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Carrera
                </label>

                <select
                  v-model="filtros.plan"
                  class="w-full h-10 rounded-lg
                        border border-slate-200
                        bg-white
                        px-3
                        text-sm">

                  <option value="">Todas las carreras</option>

                  <option
                    v-for="(nombre,codigo) in PLANES"
                    :key="codigo"
                    :value="codigo">

                    {{ nombre }}

                  </option>

                </select>
              </div>

              <!-- Materia -->
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Materia
                </label>

                <select
                  v-model="filtros.materia"
                  class="w-full h-10 rounded-lg
                        border border-slate-200
                        bg-white
                        px-3
                        text-sm">

                  <option value="">Todas las materias</option>

                  <option
                    v-for="m in materias"
                    :key="m.codigo"
                    :value="m.codigo">

                    {{ m.nombre }}

                  </option>

                </select>
              </div>

              <!-- Grupo -->
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Grupo
                </label>

                <select
                  v-model="filtros.grupo"
                  class="w-full h-10 rounded-lg
                        border border-slate-200
                        bg-white
                        px-3
                        text-sm">

                  <option value="">Todos los grupos</option>

                  <option
                    v-for="g in gruposDisponibles"
                    :key="g"
                    :value="g">

                    Grupo {{ g }}

                  </option>

                </select>
              </div>

            </div>

          </div>

        </Transition>

      </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

      <!-- Estado cargando -->
      <div v-if="cargando" class="flex items-center justify-center py-24">
        <div class="flex flex-col items-center gap-3 text-slate-400">
          <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <span class="text-sm">Cargando estudiantes...</span>
        </div>
      </div>

      <!-- Sin datos en el backend para esta gestión (año/periodo actual) -->
      <div v-else-if="sinDatosDeGestion" class="flex flex-col items-center justify-center py-24 text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-base font-medium text-slate-600">
          No hay estudiantes inscritos en talleres
        </p>
        <p class="text-sm mt-1 text-center max-w-sm">
          <template v-if="filtros.anio && filtros.periodo">
            Aún no se registran inscripciones para la gestión {{ filtros.periodo }}/{{ filtros.anio }}.
          </template>
          <template v-else>
            Aún no se registran inscripciones para la gestión actual.
          </template>
        </p>
      </div>

      <!-- Hay datos, pero el filtro/búsqueda no encontró nada -->
      <div v-else-if="!estudiantesFiltrados.length" class="flex flex-col items-center justify-center py-24 text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-base font-medium">Sin resultados</p>
        <p class="text-sm mt-1">Intenta ajustar los filtros de búsqueda.</p>
        <button
          @click="limpiarFiltros"
          class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-700 transition"
        >
          Limpiar filtros
        </button>
      </div>

      <!-- Tarjetas agrupadas por DOCENTE (cada una despliega sus materias, y cada materia despliega sus estudiantes) -->
      <div v-else class="space-y-6">
        <GrupoDocenteCard
          v-for="grupo in gruposPorDocente"
          :key="grupo.docente"
          :docente="grupo.docente"
          :cod_docente="grupo.cod_docente"
          :materias="grupo.materias"
          :total-estudiantes="grupo.totalEstudiantes"
          @ver-contacto="verContacto"
        />
      </div>
    </div>

    <!-- ===== MODAL CONTACTO ===== -->
    <ContactoEstudianteCard
      :visible="modalVisible"
      :estudiante="estudianteSeleccionado"
      :contacto="contactoData"
      @close="cerrarModal"
    />

  </div>
</template>

<script setup>
defineOptions({ name: 'EstudiantesPage' })

import { ref, reactive, computed, onMounted, watch } from 'vue'

import estudiantesService, { PLANES } from '../services/estudiantesService'
import ContactoEstudianteCard from '../components/ContactoEstudianteCard.vue'
import GrupoDocenteCard from '../components/GrupoDocenteCard.vue'
import { exportarExcelNormal, exportarExcelDetalle } from '../services/exportExcelService'
import { generarReporteInscritos } from '../services/reporteInscritosService'
import { abreviarPlan, colorPlan } from '../components/utils/planStyles'

// ─────────────────────────────────────────────
// Estado
// ─────────────────────────────────────────────
const cargando               = ref(false)
const estudiantes            = ref([])
const materias                = ref([])
const modalVisible           = ref(false)
const estudianteSeleccionado = ref({})
const contactoData           = ref(null)

// Menú desplegable del botón "Generar"
const mostrarMenuGenerar = ref(false)
const generarDropdownRef = ref(null)

// anio/periodo arrancan en null: el backend calcula la gestión actual
// automáticamente (PeriodoAcademicoService) en la primera carga.
// El usuario puede después cambiarlos con los selects — en ese caso
// se le pasan al backend como override vía query string.
const filtros = reactive({
  anio:     null,
  periodo:  null,
  busqueda: '',
  plan:     '',
  materia:  '',
  grupo:    '',
})

// true mientras filtros.anio/periodo son los que detectó el sistema;
// false en cuanto el usuario los cambia manualmente con los selects.
const gestionEsAutomatica = ref(true)

const PERIODOS = {
  '1': 'I',
  '2': 'II',
}

// Rango razonable de años para el selector (año actual del navegador
// como techo, unos años atrás como piso).
const aniosDisponibles = computed(() => {
  const actual = new Date().getFullYear()
  const desde = actual - 5
  const anios = []
  for (let a = actual + 1; a >= desde; a--) anios.push(a)
  return anios
})

const mostrarFiltros = ref(false)
// ─────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────
const gruposDisponibles = computed(() => {
  let datos = estudiantes.value
  if (filtros.materia) {
    datos = datos.filter(e => e.materia === filtros.materia)
  }
  return [...new Set(datos.map(e => e.grupo))].sort()
})

const estudiantesFiltrados = computed(() => {
  const texto = filtros.busqueda.toLowerCase().trim()

  return estudiantes.value.filter(est => {
    const matchGrupo = !filtros.grupo || est.grupo === filtros.grupo

    const searchable = [
      est.nom_estudiante,
      est.cod_estudiante,
      est.docente,
      est.nom_materia,
      est.materia,
      est.grupo,
      PLANES[est.plan], // nombre de la carrera si existe
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()

    return matchGrupo && (!texto || searchable.includes(texto))
  })
})

// true solo cuando ya terminó de cargar y el backend no trajo
// NINGÚN estudiante para la gestión actual (sin importar filtros de
// búsqueda; esto es antes de aplicar el filtro de texto/grupo).
const sinDatosDeGestion = computed(() => !cargando.value && estudiantes.value.length === 0)

// ─────────────────────────────────────────────
// NUEVO: agrupación por DOCENTE → materias → estudiantes.
// Reemplaza al antiguo `gruposPorMateria` (plano, sin agrupar por
// docente). Cada docente puede tener varias materias/grupos, y cada
// materia tiene su propia lista de estudiantes.
// ─────────────────────────────────────────────
const gruposPorDocente = computed(() => {
  const acc = {}

  estudiantesFiltrados.value.forEach(est => {
    const docenteKey = est.docente || 'Sin docente asignado'

    if (!acc[docenteKey]) {
        acc[docenteKey] = {
          docente: est.docente,
          cod_docente: est.cod_docente,
          materiasMap: {},
        }
      }
    const materiaKey = `${est.materia}_${est.grupo}`
    if (!acc[docenteKey].materiasMap[materiaKey]) {
      acc[docenteKey].materiasMap[materiaKey] = {
      plan:          est.plan,
      materia:       est.nom_materia,
      codigoMateria: est.materia,
      grupo:         est.grupo,
      estudiantes:   [],
    }
    }

    acc[docenteKey].materiasMap[materiaKey].estudiantes.push(est)
  })

  return Object.values(acc)
    .map(grupo => {
      const materiasArr = Object.values(grupo.materiasMap)
      return {
        docente: grupo.docente,
        cod_docente: grupo.cod_docente,
        materias: materiasArr,
        totalEstudiantes: materiasArr.reduce((sum, m) => sum + m.estudiantes.length, 0),
      }
    })
    // opcional: ordenar por docente alfabéticamente
    .sort((a, b) => (a.docente || '').localeCompare(b.docente || ''))
})

const filtrosActivos = computed(() => {
  const activos = []
  if (filtros.plan) {
    activos.push({ key: 'plan', label: abreviarPlan(filtros.plan) })
  }
  if (filtros.materia) {
    const m = materias.value.find(m => m.codigo === filtros.materia)
    activos.push({ key: 'materia', label: m?.nombre || filtros.materia })
  }
  if (filtros.busqueda) {
    activos.push({ key: 'busqueda', label: `"${filtros.busqueda}"` })
  }
  return activos
})

// ─────────────────────────────────────────────
// Carga de datos
// ─────────────────────────────────────────────
const cargarEstudiantes = async () => {
  cargando.value = true
  try {
    const resultado = await estudiantesService.getInscritos({
      plan:    filtros.plan    || null,
      materia: filtros.materia || null,
      // Solo se envían si el usuario ya los sabe (carga inicial = null,
      // deja que el backend calcule la gestión automáticamente).
      anio:    filtros.anio    || null,
      periodo: filtros.periodo || null,
    })
    estudiantes.value = resultado.data || []

    // El backend informa qué año/periodo usó para armar esta lista
    // (automático por PeriodoAcademicoService, o el override que mandamos).
    if (resultado.anio)    filtros.anio    = resultado.anio
    if (resultado.periodo) filtros.periodo = String(resultado.periodo)
    gestionEsAutomatica.value = resultado.automatico ?? true

    const materiasMap = new Map()
    estudiantes.value.forEach(est => {
      if (!materiasMap.has(est.materia)) {
        materiasMap.set(est.materia, {
          codigo: est.materia,
          nombre: est.nom_materia,
          nivel:  est.nivel,
        })
      }
    })
    materias.value = [...materiasMap.values()]
  } catch (error) {
    console.error('Error cargando estudiantes:', error)
  } finally {
    cargando.value = false
  }
}

onMounted(() => cargarEstudiantes())

watch(
  () => [filtros.plan, filtros.materia],
  () => cargarEstudiantes(),
)

// Cambiar el select de Año o Periodo dispara una nueva carga con ese
// override; a partir de ahí gestionEsAutomatica queda en false hasta
// que el usuario presione "Hoy".
watch(
  () => [filtros.anio, filtros.periodo],
  (nuevo, viejo) => {
    // Evita recargar en el primer render (cuando pasan de null -> valor
    // automático recién llegado del backend).
    if (!viejo[0] && !viejo[1]) return
    gestionEsAutomatica.value = false
    cargarEstudiantes()
  },
)

const volverAGestionActual = () => {
  filtros.anio = null
  filtros.periodo = null
  gestionEsAutomatica.value = true
  cargarEstudiantes()
}

// ─────────────────────────────────────────────
// Acciones – filtros
// ─────────────────────────────────────────────
const limpiarFiltros = () => {
  filtros.busqueda = ''
  filtros.plan     = ''
  filtros.materia  = ''
  filtros.grupo    = ''
}

const quitarFiltro = key => {
  filtros[key] = ''
}

// ─────────────────────────────────────────────
// Acciones – modal contacto
// ─────────────────────────────────────────────
const verContacto = est => {
  estudianteSeleccionado.value = est
  contactoData.value = { email: est.correo, celular: est.celular }
  modalVisible.value = true
}

const cerrarModal = () => {
  modalVisible.value = false
}

// ─────────────────────────────────────────────
// Acciones – exportación
// El parámetro `modo` puede ser 'ver' (abrir en nueva pestaña,
// usando window.open sobre un blob) o 'descargar' (forzar
// descarga con un <a download>). Los servicios deben soportarlo.
// ─────────────────────────────────────────────
const exportarNormal = (modo = 'descargar') => {
  exportarExcelNormal(estudiantesFiltrados.value, {
    anio:    filtros.anio,
    periodo: filtros.periodo,
    modo,
  })
}

const exportarDetalle = (modo = 'descargar') => {
  exportarExcelDetalle(estudiantesFiltrados.value, {
    anio:    filtros.anio,
    periodo: filtros.periodo,
    modo,
  })
}

const generarReporte = (modo = 'descargar') => {
  generarReporteInscritos(estudiantesFiltrados.value, {
    anio:    filtros.anio,
    periodo: filtros.periodo,
    action:  modo === 'ver' ? 'open' : 'save',   // ← el fix
  })
}
</script>