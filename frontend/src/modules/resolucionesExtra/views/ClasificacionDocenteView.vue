<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-5 bg-white rounded-xl border border-gray-200 p-4">
      <router-link
        :to="{ name: 'clasificaciones-listado' }"
        class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
        title="Volver al listado"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </router-link>

      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide">
          Archivos enlazados al docente
        </p>
        <h1 class="text-xl font-semibold text-gray-900 truncate leading-tight">
          {{ nombreDocente }}
        </h1>
        <p class="text-sm text-gray-500">
          Código: {{ codigoDocente || '—' }} ·
          {{ clasificacionesFiltradas.length }}<span v-if="busqueda"> de {{ clasificaciones.length }}</span>
          documentos adjuntados
        </p>
      </div>

      <!-- Buscador (escritorio) -->
      <div class="relative flex-shrink-0 w-full max-w-[260px] hidden sm:block">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar documento..."
          class="w-full pl-9 pr-8 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-colors"
        />
        <button
          v-if="busqueda"
          @click="busqueda = ''"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          title="Limpiar búsqueda"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Toggle de vista (escritorio) -->
      <div class="hidden sm:flex items-center gap-1 bg-gray-100 rounded-lg p-1 flex-shrink-0">
        <button
          @click="vista = 'timeline'"
          :class="vista === 'timeline' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
          class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
          title="Vista de línea de tiempo"
        >
          <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            <circle cx="4" cy="6" r="1.6" fill="currentColor" stroke="none"/>
            <circle cx="4" cy="12" r="1.6" fill="currentColor" stroke="none"/>
            <circle cx="4" cy="18" r="1.6" fill="currentColor" stroke="none"/>
          </svg>
        </button>
        <button
          @click="vista = 'lista'"
          :class="vista === 'lista' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
          class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
          title="Vista de lista de documentos"
        >
          <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Buscador (móvil) -->
    <div class="flex items-center gap-2 mb-4 sm:hidden" v-if="clasificaciones.length">
      <div class="relative flex-1">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar documento..."
          class="w-full pl-9 pr-8 py-2.5 text-sm bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-colors"
        />
        <button
          v-if="busqueda"
          @click="busqueda = ''"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Toggle de vista (móvil) -->
      <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1 flex-shrink-0">
        <button
          @click="vista = 'timeline'"
          :class="vista === 'timeline' ? 'bg-blue-50 text-blue-600' : 'text-gray-400'"
          class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors"
          title="Línea de tiempo"
        >
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            <circle cx="4" cy="6" r="1.6" fill="currentColor" stroke="none"/>
            <circle cx="4" cy="12" r="1.6" fill="currentColor" stroke="none"/>
            <circle cx="4" cy="18" r="1.6" fill="currentColor" stroke="none"/>
          </svg>
        </button>
        <button
          @click="vista = 'lista'"
          :class="vista === 'lista' ? 'bg-blue-50 text-blue-600' : 'text-gray-400'"
          class="flex items-center justify-center w-9 h-9 rounded-lg transition-colors"
          title="Lista"
        >
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Estados -->
    <div v-if="loading" class="text-center py-12 text-sm text-gray-400">
      <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      Cargando...
    </div>

    <div v-else-if="errorMsg" class="bg-white rounded-xl border border-red-200 p-8 text-center text-sm text-red-500">
      {{ errorMsg }}
    </div>

    <div v-else-if="!clasificaciones.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-sm text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Este docente no tiene clasificaciones registradas
    </div>

    <div v-else-if="!clasificacionesFiltradas.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-sm text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
      </svg>
      No se encontraron documentos para "<span class="text-gray-500 font-medium">{{ busqueda }}</span>"
      <div class="mt-3">
        <button @click="busqueda = ''" class="text-sm text-blue-500 hover:underline">Limpiar búsqueda</button>
      </div>
    </div>

    <!-- LÍNEA DE TIEMPO -->
    <div v-else-if="vista === 'timeline'" class="relative">
      <!-- Línea vertical continua -->
      <div class="absolute left-[15px] top-2 bottom-2 w-0.5 bg-gray-200"></div>

      <div class="space-y-3">
        <div
          v-for="c in clasificacionesFiltradas"
          :key="c.ID_CLASIFICACION"
          class="relative pl-9"
        >
          <!-- Punto de la línea de tiempo -->
          <span
            class="absolute left-[10px] top-5 w-3 h-3 rounded-full ring-4 ring-gray-50 z-10"
            :class="dotCategoria(c.CATEGORIA)"
          ></span>

          <!-- Card -->
          <div class="bg-white rounded-xl border border-gray-200 hover:border-blue-200 transition-colors overflow-hidden">

            <!-- CABECERA -->
            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50/50 border-b border-gray-100">
              <div class="flex items-center gap-3 min-w-0 flex-1 flex-wrap">
                <!-- CATEGORIA -->
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0" :class="badgeCategoria(c.CATEGORIA)">
                  {{ c.CATEGORIA }}
                </span>
                <!-- NIVEL (texto normal, sin colores) -->
                <span class="text-sm font-medium text-gray-600 flex-shrink-0">
                  {{ c.NIVEL || '—' }}
                </span>
                <span class="text-base font-medium text-gray-800 truncate flex-1 min-w-[120px]">
                  {{ c.DETALLE_GENERAL || 'Sin título' }}
                </span>
                <span v-if="c.FECHA_REGISTRO" class="text-xs text-gray-400 flex-shrink-0 hidden sm:inline">
                  {{ formatearFecha(c.FECHA_REGISTRO) }}
                </span>
              </div>

              <!-- Acciones -->
              <div class="flex items-center gap-2 flex-shrink-0">
                <a
                  v-if="c.NOMBRE_ARCHIVO"
                  :href="clasificacion.urlPdf(c.ID_CLASIFICACION, 'inline')"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  Ver PDF
                </a>
                <span v-else class="text-sm text-gray-300 px-2">Sin PDF</span>

                <button
                  @click="confirmarEliminar(c)"
                  class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Eliminar clasificación"
                >
                  <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- CONTENIDO -->
            <div class="px-4 py-3 space-y-2">

              <div v-if="c.OBSERVACION || c.OBSERVACION2" class="flex flex-wrap gap-2 mb-1">
                <span v-if="c.OBSERVACION" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-600 leading-snug">
                  {{ c.OBSERVACION }}
                </span>
                <span v-if="c.OBSERVACION2" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-600 leading-snug">
                  {{ c.OBSERVACION2 }}
                </span>
              </div>

              <!-- FILA COMPLETA: Gestión/Periodo | Materias | Referencias -->
              <div
                v-if="c.GESTION || c.PERIODO || c.materias?.length || c.referencias?.length"
                class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm"
              >
                <!-- Gestión / Periodo -->
                <div v-if="c.GESTION || c.PERIODO" class="flex items-center gap-3 text-gray-500 flex-shrink-0">
                  <span v-if="c.GESTION" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ c.GESTION }}
                  </span>
                  <span v-if="c.PERIODO" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Periodo {{ c.PERIODO }}
                  </span>
                </div>

                <span v-if="(c.GESTION || c.PERIODO) && (c.materias?.length || c.referencias?.length)" class="text-gray-200 select-none">|</span>

                <!-- Materias -->
                <div v-if="c.materias?.length" class="flex flex-wrap items-center gap-2 flex-1 min-w-[160px]">
                  <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide flex-shrink-0">Materias:</span>
                  <span
                    v-for="m in c.materias"
                    :key="m.ID_DETALLE"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 border border-blue-100 rounded text-sm text-blue-700"
                  >
                    {{ m.NOMBRE_MATERIA }}
                    <span v-if="m.NOTA !== null && m.NOTA !== undefined" class="text-xs font-semibold text-blue-500">
                      {{ m.NOTA }}
                    </span>
                  </span>
                </div>

                <span v-if="c.materias?.length && c.referencias?.length" class="text-gray-200 select-none">|</span>

                <!-- Referencias -->
                <div v-if="c.referencias?.length" class="flex flex-wrap items-center gap-2 flex-1 min-w-[160px]">
                  <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide flex-shrink-0">Referencias:</span>
                  <template v-for="r in c.referencias" :key="r.ID_REF">
                    <a
                      v-if="r.ID_RESOLUCION"
                      :href="`${API_BASE}/api/resoluciones/${r.ID_RESOLUCION}/pdf`"
                      target="_blank"
                      class="group inline-flex items-center bg-green-50 border border-green-100 hover:border-green-200 rounded-lg text-sm text-green-700 font-medium transition-colors overflow-hidden"
                    >
                      <span class="px-2.5 py-1">{{ r.NRO_REFERENCIA }}</span>
                      <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-600 group-hover:bg-green-700 text-white text-xs font-semibold h-full transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                        </svg>
                        Ver PDF
                      </span>
                    </a>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-1 bg-gray-50 border border-gray-100 rounded text-sm text-gray-500"
                    >
                      {{ r.NRO_REFERENCIA }}
                    </span>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LISTA DE DOCUMENTOS -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="divide-y divide-gray-100">
        <div
          v-for="c in clasificacionesFiltradas"
          :key="c.ID_CLASIFICACION"
          class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors"
        >
          <!-- Icono de archivo -->
          <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-50 flex-shrink-0">
            <svg class="w-4.5 h-4.5 text-gray-400" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
            </svg>
          </div>

          <!-- Nombre + categoría -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0" :class="badgeCategoria(c.CATEGORIA)">
                {{ c.CATEGORIA }}
              </span>
              <p class="text-sm font-medium text-gray-800 truncate">
                {{ c.NOMBRE_ARCHIVO || c.DETALLE_GENERAL || 'Sin archivo' }}
              </p>
            </div>
          </div>

          <!-- Referencias -->
          <div v-if="c.referencias?.length" class="hidden md:flex items-center gap-1.5 flex-shrink-0 max-w-[280px] flex-wrap">
            <span
              v-for="r in c.referencias"
              :key="r.ID_REF"
              class="inline-flex items-center px-2 py-0.5 bg-gray-50 border border-gray-100 rounded text-xs text-gray-500"
            >
              {{ r.NRO_REFERENCIA }}
            </span>
          </div>
          <span v-else class="hidden md:inline text-xs text-gray-300 flex-shrink-0">Sin referencias</span>

          <!-- Ver PDF -->
          <a
            v-if="c.NOMBRE_ARCHIVO"
            :href="clasificacion.urlPdf(c.ID_CLASIFICACION, 'inline')"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors flex-shrink-0"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
            </svg>
            Ver PDF
          </a>
          <span v-else class="text-sm text-gray-300 px-2 flex-shrink-0">Sin PDF</span>

          <!-- Eliminar -->
          <button
            @click="confirmarEliminar(c)"
            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0"
            title="Eliminar clasificación"
          >
            <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cerrarModal">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6 transition-all duration-200 ease-out">
        <div class="flex items-center justify-center mb-4">
          <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
        </div>

        <h3 class="text-center text-lg font-semibold text-gray-900 mb-2">
          ¿Eliminar clasificación?
        </h3>
        <p class="text-center text-sm text-gray-500 mb-6">
          ¿Estás seguro de eliminar la clasificación <br>
          <span class="font-medium text-gray-700">"{{ itemAEliminar?.DETALLE_GENERAL || 'Sin título' }}"</span>?
          <br>
          <span class="text-sm text-red-500">Esta acción eliminará todas las materias y referencias asociadas.</span>
        </p>

        <div class="flex gap-3">
          <button
            @click="cerrarModal"
            class="flex-1 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="eliminarClasificacion"
            :disabled="eliminando"
            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <svg v-if="eliminando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ eliminando ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReporteClasificacion } from '../composables/useReporteClasificacion'
import { useClasificacion } from '../composables/useClasificacion'

const API_BASE = import.meta.env.VITE_API_URL ?? ''
const route = useRoute()
const router = useRouter()
const reporte = useReporteClasificacion()
const clasificacion = useClasificacion()

const loading = ref(true)
const docente = ref(null)
const clasificaciones = ref([])
const errorMsg = ref(null)
const codigoDocente = ref(null)

// Buscador
const busqueda = ref('')

// Vista: 'timeline' (línea de tiempo) | 'lista' (lista compacta de documentos)
const vista = ref('timeline')

// Modal
const mostrarModal = ref(false)
const itemAEliminar = ref(null)
const eliminando = ref(false)

const nombreDocente = computed(() => {
  if (!docente.value) return 'Docente'
  if (docente.value.NOMBRE) return docente.value.NOMBRE
  return `${docente.value.APELLIDOS || ''} ${docente.value.NOMBRES || ''}`.trim() || 'Docente'
})

function normalizar(str) {
  return (str ?? '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

const clasificacionesFiltradas = computed(() => {
  const termino = normalizar(busqueda.value.trim())
  if (!termino) return clasificaciones.value

  return clasificaciones.value.filter(c => {
    const campos = [
      c.DETALLE_GENERAL,
      c.CATEGORIA,
      c.NIVEL,
      c.GESTION,
      c.PERIODO,
      c.OBSERVACION,
      c.OBSERVACION2,
      c.NOMBRE_ARCHIVO,
    ]

    const coincideCampos = campos.some(campo => normalizar(campo).includes(termino))
    const coincideMaterias = c.materias?.some(m => normalizar(m.NOMBRE_MATERIA).includes(termino))
    const coincideReferencias = c.referencias?.some(r => normalizar(r.NRO_REFERENCIA).includes(termino))

    return coincideCampos || coincideMaterias || coincideReferencias
  })
})

// ─── Badge de Categoria ───
function badgeCategoria(categoria) {
  if (categoria === 'Docentes Titulares')  return 'bg-emerald-50 text-emerald-700'
  if (categoria === 'Docentes Temporales') return 'bg-amber-50 text-amber-700'
  return 'bg-gray-50 text-gray-600'
}

// ─── Dot de Categoria ───
function dotCategoria(categoria) {
  if (categoria === 'Docentes Titulares')  return 'bg-emerald-500'
  if (categoria === 'Docentes Temporales') return 'bg-amber-500'
  return 'bg-gray-400'
}

function formatearFecha(fecha) {
  if (!fecha) return ''
  const d = new Date(fecha)
  if (isNaN(d.getTime())) return fecha
  return d.toLocaleDateString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// ─── Eliminar Clasificación ───
function confirmarEliminar(item) {
  itemAEliminar.value = item
  mostrarModal.value = true
}

function cerrarModal() {
  mostrarModal.value = false
  itemAEliminar.value = null
}

async function eliminarClasificacion() {
  if (!itemAEliminar.value) return

  eliminando.value = true

  try {
    const result = await clasificacion.eliminar(itemAEliminar.value.ID_CLASIFICACION)

    if (result?.ok) {
      clasificaciones.value = clasificaciones.value.filter(
        c => c.ID_CLASIFICACION !== itemAEliminar.value.ID_CLASIFICACION
      )
      cerrarModal()
      console.log('Clasificación eliminada correctamente')
    } else {
      alert(result?.error || 'Error al eliminar la clasificación')
    }
  } catch (e) {
    console.error('Error al eliminar:', e)
    alert('Error al eliminar la clasificación')
  } finally {
    eliminando.value = false
  }
}

// ─── Cargar datos ───
onMounted(async () => {
  const codDocente = route.params.cod_docente

  if (!codDocente || codDocente === 'undefined' || !/^\d+$/.test(String(codDocente))) {
    errorMsg.value = 'No se especificó un docente válido'
    loading.value = false
    console.error('cod_docente inválido en la ruta:', codDocente)
    return
  }

  codigoDocente.value = codDocente

  try {
    const data = await reporte.porDocente(codDocente)
    docente.value = data.docente
    clasificaciones.value = data.clasificaciones
  } catch (e) {
    console.error('Error cargando clasificaciones del docente:', e)
    errorMsg.value = reporte.error.value || 'No se pudo cargar la línea de tiempo'
  } finally {
    loading.value = false
  }
})
</script>