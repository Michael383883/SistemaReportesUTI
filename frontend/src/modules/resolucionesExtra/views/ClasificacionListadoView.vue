<template>
  <div class="bg-gray-50 pb-4">

    <!-- Header compacto -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Documentos de Docentes</h1>
        <p class="text-sm text-gray-500">Listado de documentos registrados</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Botón para abrir vista previa del reporte Excel -->
        <button
          @click="abrirPreviewExcel"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
          title="Vista previa del reporte Excel"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
          </svg>
          Reporte Excel
        </button>

        <!-- Botón "Nuevo" -->
        <router-link
          :to="{ name: 'clasificaciones-nueva' }"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nuevo
        </router-link>
      </div>
    </div>

    <!-- Barra de búsqueda + botón de filtros agrupado -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 mb-2 flex flex-wrap items-center gap-2">
      <div class="flex-1 min-w-[150px] relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="filtroNombre"
          type="text"
          placeholder="Buscar por docente o documento..."
          class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <!-- Botón único que despliega el panel de filtros -->
      <button
        @click="mostrarFiltros = !mostrarFiltros"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
        :class="mostrarFiltros || filtrosActivosCount > 0
          ? 'bg-blue-50 border-blue-200 text-blue-700'
          : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filtros
        <span
          v-if="filtrosActivosCount > 0"
          class="inline-flex items-center justify-center w-5 h-5 bg-blue-600 text-white text-xs font-semibold rounded-full"
        >
          {{ filtrosActivosCount }}
        </span>
        <svg
          class="w-4 h-4 transition-transform"
          :class="{ 'rotate-180': mostrarFiltros }"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
    </div>

    <!-- Panel de filtros desplegable -->
    <div
      v-if="mostrarFiltros"
      class="bg-slate-100 rounded-xl border border-gray-200 p-3 mb-4 flex flex-wrap items-end gap-2"
    >
      <div class="flex flex-col gap-0.5">
        <label class="text-[10px] font-medium text-gray-400 px-0.5">Categoría de documento</label>
        <select
          v-model="filtros.categoria"
          @change="cargar"
          class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
        >
          <option value="">Todos</option>
          <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>

      <div class="flex flex-col gap-0.5">
        <label class="text-[10px] font-medium text-gray-400 px-0.5">Categoría de título</label>
        <select
          v-model="filtros.tipo_titulo"
          @change="cargar"
          class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
        >
          <option value="">Todos</option>
          <option v-for="tipo in tiposTitulo" :key="tipo" :value="tipo">{{ tipo }}</option>
        </select>
      </div>

      <select
        v-model="filtros.nivel"
        @change="cargar"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
      >
        <option value="">Todos los niveles</option>
        <option value="Primer nivel">Primer nivel</option>
        <option value="Segundo nivel">Segundo nivel</option>
        <option value="Tercer nivel">Tercer nivel</option>
      </select>
      <input
        v-model="filtros.gestion"
        @change="cargar"
        type="text"
        placeholder="Gestión"
        class="w-28 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <input
        v-model="filtros.periodo"
        @change="cargar"
        type="text"
        placeholder="Periodo"
        class="w-28 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <button
        @click="limpiarFiltros"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-400 hover:text-gray-600 rounded-lg transition-colors"
        title="Limpiar filtros"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Limpiar
      </button>
    </div>

    <!-- Estados -->
    <div v-if="clasificacion.loading.value" class="text-center py-12 text-sm text-gray-400">
      <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      Cargando...
    </div>

    <div v-else-if="clasificacion.error.value" class="bg-white rounded-xl border border-red-200 p-8 text-center text-sm text-red-500">
      {{ clasificacion.error.value }}
    </div>

    <div v-else-if="!listadoFiltrado.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-sm text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      No hay clasificaciones registradas
    </div>

    <!-- Tabla compacta -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-slate-900">
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Docente</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Documento</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Categoria</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Nivel</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Gestión</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Periodo</th>
              <th class="text-left font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">PDF</th>
              <th class="text-right font-medium text-slate-100 px-4 py-3 text-xs uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in listadoFiltrado" :key="c.ID_CLASIFICACION_DOCENTE ?? c.COD_DOCENTE ?? c.NOMBRE_DOCENTE"
              class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
            >
              <!-- Docente -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-gray-800 text-base whitespace-normal break-words">
                    {{ c.NOMBRE_DOCENTE }}
                  </span>
                  <span
                    v-if="c._totalClasificaciones > 1"
                    class="inline-flex items-center justify-center px-1.5 py-0.5 min-w-[22px] bg-gray-100 text-gray-500 text-xs font-semibold rounded-full flex-shrink-0"
                    title="Cantidad de clasificaciones registradas"
                  >
                    {{ c._totalClasificaciones }}
                  </span>
                </div>
              </td>

              <!-- Documento: TIPO_DOCUMENTO como título, DETALLE_GENERAL como detalle -->
              <td class="px-4 py-3 max-w-[220px]">
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-gray-800 truncate">
                    {{ c.TIPO_DOCUMENTO || '—' }}
                  </span>
                  <span v-if="c.DETALLE_GENERAL" class="text-xs text-gray-400 truncate">
                    {{ c.DETALLE_GENERAL }}
                  </span>
                </div>
              </td>

              <!-- Categoria -->
              <td class="px-4 py-3">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium" :class="badgeCategoria(c.CATEGORIA)">
                  {{ c.CATEGORIA }}
                </span>
              </td>

              <!-- Nivel -->
              <td class="px-4 py-3">
                <span class="text-sm font-medium text-gray-700">
                  {{ c.NIVEL || '—' }}
                </span>
              </td>

              <!-- Gestión -->
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.GESTION || '—' }}</td>

              <!-- Periodo -->
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.PERIODO || '—' }}</td>

              <!-- PDF -->
              <td class="px-4 py-3">

                <a v-if="c.NOMBRE_ARCHIVO" :href="clasificacion.urlPdf(c.ID_DOCUMENTO, 'inline')"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-800 hover:bg-blue-900 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  Ver PDF
                </a>
                <span v-else class="text-gray-300 text-sm">—</span>
              </td>

              <!-- Acciones -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- Link a documentos adjuntados: hereda el término de búsqueda como query "q" -->
                  <router-link
                    v-if="c.COD_DOCENTE"
                    :to="{
                      name: 'clasificaciones-docente',
                      params: { cod_docente: c.COD_DOCENTE },
                      query: filtroNombre.trim() ? { q: filtroNombre.trim() } : {}
                    }"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-800 hover:bg-indigo-900 text-white rounded-lg text-sm font-medium shadow-sm transition-colors"
                    title="Ver documentos adjuntados"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="hidden sm:inline">Documentos adjuntados</span>
                  </router-link>

                  <!-- Botón eliminar -->
                  <button
                    @click="confirmarEliminar(c)"
                    class="inline-flex items-center p-2 text-red-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Eliminar clasificación"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Contador de registros -->
      <div class="px-4 py-2.5 bg-gray-50/80 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
        <span>Mostrando {{ listadoFiltrado.length }} docentes</span>
        <span v-if="clasificacion.listado.value.length !== listadoFiltrado.length">
          ({{ clasificacion.listado.value.length }} clasificaciones en total)
        </span>
      </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <Teleport to="body">
      <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cerrarModal">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
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
            ¿Estás seguro de eliminar la clasificación de <br>
            <span class="font-medium text-gray-700">"{{ itemAEliminar?.NOMBRE_DOCENTE || 'Sin docente' }}"</span>?
            <br>
            <span v-if="docentesPorDocumento.get(itemAEliminar?.ID_DOCUMENTO) > 1" class="text-sm text-amber-600">
              Este documento tiene más de un docente asignado. Solo se quitará a {{ itemAEliminar?.NOMBRE_DOCENTE }}, el documento y los demás docentes se conservan.
            </span>
            <span v-else class="text-sm text-red-500">
              Esta acción eliminará el documento completo, sus materias y referencias asociadas.
            </span>
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
    </Teleport>

    <!-- Modal de Vista Previa del Reporte Excel (se superpone sobre todo) -->
    <Teleport to="body">
      <div v-if="mostrarPreviewExcel" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="cerrarPreviewExcel">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] flex flex-col">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h3 class="text-base font-semibold text-gray-900">Vista previa del reporte Excel</h3>
              <p class="text-xs text-gray-500 mt-0.5">Datos tal como se generarán en el archivo</p>
            </div>
            <button @click="cerrarPreviewExcel" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Parámetros del reporte -->
          <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-end gap-3 bg-gray-50/60">
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Gestión desde</label>
              <input
                v-model="excelParams.gestion_desde"
                type="text"
                placeholder="Ej. 2001"
                class="w-28 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Gestión hasta</label>
              <input
                v-model="excelParams.gestion_hasta"
                type="text"
                placeholder="Opcional"
                class="w-28 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Periodo</label>
              <input
                v-model="excelParams.periodo"
                type="text"
                placeholder="Opcional"
                class="w-24 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Versión</label>
              <input
                v-model="excelParams.version"
                type="text"
                placeholder="Ej. 5ta Versión"
                class="w-36 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Categoría de documento: multi-selección -->
            <div class="relative">
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Categoría de documento</label>
              <button
                type="button"
                @click="catDocDropdownOpen = !catDocDropdownOpen; catTituloDropdownOpen = false"
                class="w-56 flex items-center justify-between gap-2 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg bg-white text-left"
              >
                <span class="truncate" :class="excelParams.categorias.length ? 'text-gray-800' : 'text-gray-400'">
                  {{ excelParams.categorias.length ? `${excelParams.categorias.length} seleccionada(s)` : 'Todas' }}
                </span>
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div
                v-if="catDocDropdownOpen"
                class="absolute z-30 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
              >
                <label
                  v-for="cat in categorias"
                  :key="cat"
                  class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
                >
                  <input type="checkbox" :value="cat" v-model="excelParams.categorias" class="accent-blue-600" />
                  {{ cat }}
                </label>
                <div v-if="!categorias.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin categorías</div>
                <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
                  <button type="button" class="text-[11px] text-gray-500 hover:text-red-500" @mousedown.prevent="excelParams.categorias = []">
                    Limpiar
                  </button>
                  <button type="button" class="text-[11px] font-medium text-blue-600 hover:text-blue-700" @mousedown.prevent="catDocDropdownOpen = false">
                    Listo
                  </button>
                </div>
              </div>
            </div>

            <!-- Categoría de título: multi-selección (incluye "Sin título registrado") -->
            <div class="relative">
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Categoría de título</label>
              <button
                type="button"
                @click="catTituloDropdownOpen = !catTituloDropdownOpen; catDocDropdownOpen = false"
                class="w-56 flex items-center justify-between gap-2 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg bg-white text-left"
              >
                <span class="truncate" :class="excelParams.tiposTitulo.length ? 'text-gray-800' : 'text-gray-400'">
                  {{ excelParams.tiposTitulo.length ? `${excelParams.tiposTitulo.length} seleccionada(s)` : 'Todas' }}
                </span>
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div
                v-if="catTituloDropdownOpen"
                class="absolute z-30 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
              >
                <!-- Opción especial: docentes/clasificaciones sin ningún título registrado -->
                <label class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer border-b border-gray-100">
                  <input type="checkbox" value="__SIN_TITULO__" v-model="excelParams.tiposTitulo" class="accent-blue-600" />
                  <span class="italic text-gray-500">Sin título registrado</span>
                </label>

                <label
                  v-for="tipo in tiposTitulo"
                  :key="tipo"
                  class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
                >
                  <input type="checkbox" :value="tipo" v-model="excelParams.tiposTitulo" class="accent-blue-600" />
                  {{ tipo }}
                </label>
                <div v-if="!tiposTitulo.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin tipos de título</div>
                <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
                  <button type="button" class="text-[11px] text-gray-500 hover:text-red-500" @mousedown.prevent="excelParams.tiposTitulo = []">
                    Limpiar
                  </button>
                  <button type="button" class="text-[11px] font-medium text-blue-600 hover:text-blue-700" @mousedown.prevent="catTituloDropdownOpen = false">
                    Listo
                  </button>
                </div>
              </div>
            </div>

            <button
              @click="cargarPreviewExcel"
              class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700"
            >
              Actualizar vista previa
            </button>

            <!-- Combinar Materias -->
            <button
              @click="reporteExcel.alternarCombinarMaterias()"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
              :class="reporteExcel.materiasCombinadas.value ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              title="Combina en una sola celda las materias repetidas de un mismo docente (excepto '-' y 'NO REGENTA MATERIA EN LA FCE')"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4"/>
              </svg>
              {{ reporteExcel.materiasCombinadas.value ? 'Materias combinadas ✓' : 'Combinar Materias' }}
            </button>

            <div class="w-px h-8 bg-gray-200"></div>

            <!-- Solo Activos -->
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Año activos</label>
              <input
                v-model.number="reporteExcel.anioActivos.value"
                type="number"
                class="w-20 px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-0.5">Periodo activos</label>
              <select
                v-model.number="reporteExcel.periodoActivos.value"
                class="px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option :value="1">1</option>
                <option :value="2">2</option>
                <option :value="3">3</option>
                <option :value="4">4</option>
              </select>
            </div>

            <button
              @click="reporteExcel.alternarSoloActivos({ anio: reporteExcel.anioActivos.value, periodo: reporteExcel.periodoActivos.value })"
              :disabled="reporteExcel.cargandoActivos.value"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
              :class="reporteExcel.soloActivos.value ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              <svg v-if="reporteExcel.cargandoActivos.value" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ reporteExcel.soloActivos.value ? '✓ Solo Activos' : 'Solo Activos' }}
            </button>

            <button
              v-if="reporteExcel.soloActivos.value"
              @click="reporteExcel.cargarDocentesActivos({ anio: reporteExcel.anioActivos.value, periodo: reporteExcel.periodoActivos.value })"
              :disabled="reporteExcel.cargandoActivos.value"
              class="px-2.5 py-1.5 text-sm text-gray-500 hover:text-blue-600"
              title="Recargar lista de docentes activos"
            >
              Actualizar
            </button>

            <!-- Asignar Carga Horaria (solo visible con "Solo Activos" activo) -->
            <button
              v-if="reporteExcel.soloActivos.value"
              @click="confirmarAsignarCargaHoraria"
              :disabled="reporteExcel.cargandoCargaHoraria.value"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
              :class="reporteExcel.cargaHorariaAsignada.value ? 'bg-purple-100 text-purple-700' : 'bg-purple-600 hover:bg-purple-700 text-white'"
              title="Compara docente + nombre de materia contra la carga horaria real y la asigna si coincide"
            >
              <svg v-if="reporteExcel.cargandoCargaHoraria.value" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ reporteExcel.cargaHorariaAsignada.value ? 'CH asignada ✓' : 'Asignar Carga Horaria' }}
            </button>

            <span v-if="resultadoCargaHoraria?.asignadas !== undefined" class="text-xs text-emerald-600 font-medium">
              ✓ {{ resultadoCargaHoraria.asignadas }} materia(s) con CH asignada
              <span v-if="resultadoCargaHoraria.sinCoincidencia" class="text-amber-600">
                · {{ resultadoCargaHoraria.sinCoincidencia }} sin coincidencia
              </span>
            </span>
            <span v-if="resultadoCargaHoraria?.error" class="text-xs text-red-500 font-medium">
              {{ resultadoCargaHoraria.error }}
            </span>

            <span v-if="!reporteExcel.loading.value && !reporteExcel.error.value" class="text-xs text-gray-400 mb-1.5 ml-auto">
              {{ reporteExcel.previewMostrado.value.length }} fila(s)
              <span v-if="reporteExcel.soloActivos.value">de {{ reporteExcel.totalFilas.value }}</span>
              · {{ reporteExcel.gestionEtiqueta.value }}
            </span>
          </div>

          <p v-if="reporteExcel.errorActivos.value" class="px-6 text-xs text-red-500 pt-2">
            {{ reporteExcel.errorActivos.value }}
          </p>
          <p v-if="reporteExcel.soloActivos.value" class="px-6 text-xs text-gray-400 pt-2">
            Mostrando solo docentes con materia asignada en {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}
            ({{ reporteExcel.docentesActivos.value.length }} activos)
          </p>

          <!-- Contenido -->
          <div class="flex-1 overflow-auto px-6 py-3">
            <div v-if="reporteExcel.loading.value" class="text-center py-12 text-sm text-gray-400">
              <svg class="w-6 h-6 mx-auto animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Cargando vista previa...
            </div>

            <div v-else-if="reporteExcel.error.value" class="text-center py-12 text-sm text-red-500">
              {{ reporteExcel.error.value }}
            </div>

            <table v-else-if="reporteExcel.previewMostrado.value.length" class="w-full text-xs border-collapse">
              <thead class="sticky top-0 bg-white">
                <tr class="border-b border-gray-200">
                  <th class="text-center font-medium text-gray-500 px-2 py-2 uppercase tracking-wider w-10">Nº</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Docente</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Materia</th>
                  <th class="text-center font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">CH</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Detalle</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Categoria</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Nivel</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Fotocopia</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Obs 2</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Obs 3</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(item, i) in reporteExcel.previewMostrado.value" :key="i">
                  <tr
                    class="border-b border-gray-50"
                    :class="item.FIN_GRUPO ? 'border-b-2 border-b-gray-300' : ''"
                  >
                    <!-- Nº y Docente se fusionan visualmente con rowspan, igual que en el Excel -->
                    <td
                      v-if="item.INICIO_GRUPO"
                      :rowspan="item.FILAS_GRUPO"
                      class="px-2 py-2 text-center align-middle border-r border-gray-100 font-medium text-gray-700"
                    >
                      {{ item.N }}
                    </td>
                    <td
                      v-if="item.INICIO_GRUPO"
                      :rowspan="item.FILAS_GRUPO"
                      class="px-2 py-2 align-middle border-r border-gray-100 font-medium text-gray-800"
                    >
                      {{ item.NOMBRE_DOCENTE }}
                    </td>

                    <!-- Materia: cuando está en modo "Combinar Materias" respeta el
                         rowspan (INICIO_MATERIA/FILAS_MATERIA); en modo normal esas
                         propiedades no existen y la celda se dibuja como siempre -->
                    <td
                      v-if="item.INICIO_MATERIA !== false"
                      :rowspan="item.FILAS_MATERIA || 1"
                      class="px-2 py-2 text-gray-700 align-middle"
                      :class="item.NEGRITA ? 'font-semibold' : ''"
                    >
                      {{ item.NOMBRE_MATERIA }}
                    </td>
                    <!-- CH: cuando está combinado sigue el mismo rowspan que la materia -->
                    <td
                      v-if="item.INICIO_MATERIA !== false"
                      :rowspan="item.FILAS_MATERIA || 1"
                      class="px-2 py-2 text-center text-gray-600 align-middle"
                    >
                      {{ item.CH || '—' }}
                    </td>
                    <td class="px-2 py-2 text-gray-600">{{ item.DETALLE || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.CATEGORIA || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.NIVEL || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.FOTOCOPIA_TITULAR || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.OBS2 || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.OBS3 || '—' }}</td>
                  </tr>
                </template>
              </tbody>
            </table>

            <div v-else class="text-center py-12 text-sm text-gray-400">
              <template v-if="reporteExcel.soloActivos.value && reporteExcel.preview.value.length">
                Ningún docente coincide con la lista de activos de {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}.
                <div class="mt-2">
                  <button @click="reporteExcel.soloActivos.value = false" class="text-blue-500 hover:underline">Quitar filtro</button>
                </div>
              </template>
              <template v-else>
                No hay registros para los parámetros indicados
              </template>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100">
            <button
              @click="cerrarPreviewExcel"
              class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="descargarExcelConfirmado"
              :disabled="!reporteExcel.previewMostrado.value.length"
              class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H6a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Descargar Excel
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Confirmación: Asignar Carga Horaria (experimental) -->
    <Teleport to="body">
      <div v-if="mostrarModalCargaHoraria" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cerrarModalCargaHoraria">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
          <div class="flex items-center justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
          </div>
          <h3 class="text-center text-lg font-semibold text-gray-900 mb-2">
            ¿Asignar carga horaria automáticamente?
          </h3>
          <p class="text-center text-sm text-gray-500 mb-2">
            Esta función es <span class="font-semibold text-amber-600">experimental</span>.
          </p>
          <p class="text-center text-sm text-gray-500 mb-6">
            Se compara cada docente activo y el nombre de su materia contra la carga horaria real
            de {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}.
            Si el nombre de la materia coincide exactamente, se asigna la CH. Si no hay coincidencia,
            la columna CH queda vacía. Revisa el resultado con cuidado antes de descargar el Excel.
          </p>
          <div class="flex gap-3">
            <button
              @click="cerrarModalCargaHoraria"
              class="flex-1 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="ejecutarAsignarCargaHoraria"
              :disabled="reporteExcel.cargandoCargaHoraria.value"
              class="flex-1 px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg v-if="reporteExcel.cargandoCargaHoraria.value" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ reporteExcel.cargandoCargaHoraria.value ? 'Asignando...' : 'Sí, adelante' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useClasificacion } from '../composables/useClasificacion'
import { useReporteExcel } from '../composables/useReporteExcel'
import { useCategorias } from '../composables/useCategorias'
import { useTiposTitulo } from '../composables/useTiposTitulo'

const router = useRouter()
const route = useRoute()
const clasificacion = useClasificacion()
const reporteExcel = useReporteExcel()
const { categorias, cargarCategorias } = useCategorias()
const { tipos: tiposTitulo, cargarTipos } = useTiposTitulo()
const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Filtros de la tabla principal
const filtroNombre = ref('')
const filtros = ref({
  categoria: '',
  tipo_titulo: '',
  nivel: '',
  gestion: '',
  periodo: '',
})

// Panel de filtros colapsable
const mostrarFiltros = ref(false)
const filtrosActivosCount = computed(() => {
  let count = 0
  if (filtros.value.categoria) count++
  if (filtros.value.tipo_titulo) count++
  if (filtros.value.nivel) count++
  if (filtros.value.gestion) count++
  if (filtros.value.periodo) count++
  return count
})

// Modal de eliminación
const mostrarModal = ref(false)
const itemAEliminar = ref(null)
const eliminando = ref(false)

// ─── Cargar datos de la tabla principal ───
async function cargar() {
  try {
    await clasificacion.listar({
      categoria: filtros.value.categoria || undefined,
      tipo_titulo: filtros.value.tipo_titulo || undefined,
      nivel: filtros.value.nivel || undefined,
      gestion: filtros.value.gestion || undefined,
      periodo: filtros.value.periodo || undefined,
    })
  } catch (e) {
    console.error('Error cargando listado de clasificaciones:', e)
  }
}

function limpiarFiltros() {
  filtroNombre.value = ''
  filtros.value.categoria = ''
  filtros.value.tipo_titulo = ''
  filtros.value.nivel = ''
  filtros.value.gestion = ''
  filtros.value.periodo = ''
  cargar()
}

// Cuenta cuántos docentes distintos tiene cada documento, usando el listado ya cargado
const docentesPorDocumento = computed(() => {
  const mapa = new Map()
  for (const c of clasificacion.listado.value) {
    const docId = c.ID_DOCUMENTO
    mapa.set(docId, (mapa.get(docId) || 0) + 1)
  }
  return mapa
})

// ─── Agrupado por docente ───
const listadoPorDocente = computed(() => {
  const mapa = new Map()

  for (const c of clasificacion.listado.value) {
    const clave = c.COD_DOCENTE ?? c.NOMBRE_DOCENTE

    if (!mapa.has(clave)) {
      mapa.set(clave, { ...c, _totalClasificaciones: 1 })
      continue
    }

    const existente = mapa.get(clave)
    const total = existente._totalClasificaciones + 1

    const fechaExistente = existente.FECHA_REGISTRO ? new Date(existente.FECHA_REGISTRO) : null
    const fechaActual = c.FECHA_REGISTRO ? new Date(c.FECHA_REGISTRO) : null

    if (fechaActual && (!fechaExistente || fechaActual > fechaExistente)) {
      mapa.set(clave, { ...c, _totalClasificaciones: total })
    } else {
      mapa.set(clave, { ...existente, _totalClasificaciones: total })
    }
  }

  return Array.from(mapa.values())
})

// ─── Filtrado + agrupado en cliente ───
// Sin búsqueda: se agrupa por docente (una fila por docente, la más reciente).
// Con búsqueda: se muestra CADA documento que coincide como su propia fila,
// así si un docente tiene 2 diplomados (doc #5 y #6), aparecen los 2 en la lista.
const listadoFiltrado = computed(() => {
  const term = filtroNombre.value.trim().toLowerCase()

  if (!term) return listadoPorDocente.value

  const coincide = (c) => {
    const nombre  = (c.NOMBRE_DOCENTE  || '').toLowerCase()
    const tipoDoc = (c.TIPO_DOCUMENTO  || '').toLowerCase()
    const detalle = (c.DETALLE_GENERAL || '').toLowerCase()
    return nombre.includes(term) || tipoDoc.includes(term) || detalle.includes(term)
  }

  const documentosCoincidentes = clasificacion.listado.value.filter(coincide)

  return documentosCoincidentes.map(c => {
    const clave = c.COD_DOCENTE ?? c.NOMBRE_DOCENTE
    const totalDocumentos = clasificacion.listado.value.filter(
      x => (x.COD_DOCENTE ?? x.NOMBRE_DOCENTE) === clave
    ).length

    return { ...c, _totalClasificaciones: totalDocumentos }
  })
})

// ─── Badge de categoria ───
function badgeCategoria(categoria) {
  if (categoria === 'DOCENTES TITULARES')  return 'bg-emerald-50 text-emerald-700'
  if (categoria === 'DOCENTES TEMPORALES') return 'bg-amber-50 text-amber-700'
  if (categoria === 'EXAMEN SUFICIENCIA') return 'bg-blue-50 text-blue-700'
  if (categoria === 'ACEFALA') return 'bg-gray-50 text-gray-700'
  if (categoria === 'SIN CATEGORIA') return 'bg-orange-50 text-red-700'

  return 'bg-gray-50 text-gray-600'
}

// ─── Eliminar clasificación ───
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

  const totalDocentesEnDocumento = docentesPorDocumento.value.get(itemAEliminar.value.ID_DOCUMENTO) || 1

  try {
    let result
    if (totalDocentesEnDocumento > 1) {
      result = await clasificacion.eliminarDocente(itemAEliminar.value.ID_CLASIFICACION_DOCENTE)
    } else {
      result = await clasificacion.eliminar(itemAEliminar.value.ID_DOCUMENTO)
    }

    if (result?.ok) {
      await cargar()
      cerrarModal()
    } else {
      alert(result?.error || 'Error al eliminar')
    }
  } catch (e) {
    console.error('Error al eliminar:', e)
    alert('Error al eliminar la clasificación')
  } finally {
    eliminando.value = false
  }
}

// ─── Vista previa de Reporte Excel (usa el mismo construirDatos() del backend) ───
const mostrarPreviewExcel = ref(false)
const excelParams = ref({
  gestion_desde: '2001',
  gestion_hasta: '',
  periodo: '',
  version: '5ta Versión',
  categorias: [],    // ← array: multi-selección
  tiposTitulo: [],   // puede incluir el sentinel '__SIN_TITULO__'
})

// Estado de los dos dropdowns de checkboxes (categoría doc / categoría título)
const catDocDropdownOpen = ref(false)
const catTituloDropdownOpen = ref(false)
async function abrirPreviewExcel() {
  if (filtros.value.gestion) {
    excelParams.value.gestion_desde = filtros.value.gestion
  }
  if (filtros.value.categoria) {
    excelParams.value.categorias = [filtros.value.categoria]
  }
  if (filtros.value.tipo_titulo) {
    excelParams.value.tiposTitulo = [filtros.value.tipo_titulo]
  }
  mostrarPreviewExcel.value = true
  await cargarPreviewExcel()
}

async function cargarPreviewExcel() {
  resultadoCargaHoraria.value = null
  try {
    await reporteExcel.previsualizar({
      gestion_desde: excelParams.value.gestion_desde,
      gestion_hasta: excelParams.value.gestion_hasta,
      periodo: excelParams.value.periodo,
      version: excelParams.value.version,
      categoria: excelParams.value.categorias,     // ✅ corregido (plural)
      tipo_titulo: excelParams.value.tiposTitulo,  // ✅ corregido (puede incluir __SIN_TITULO__)
    })
  } catch (e) {
    console.error('Error cargando vista previa de Excel:', e)
  }
}

function cerrarPreviewExcel() {
  mostrarPreviewExcel.value = false
}

// ─── Asignar Carga Horaria (experimental) ───
const mostrarModalCargaHoraria = ref(false)
const resultadoCargaHoraria = ref(null) // { asignadas, sinCoincidencia } | { error }

function confirmarAsignarCargaHoraria() {
  resultadoCargaHoraria.value = null
  mostrarModalCargaHoraria.value = true
}

function cerrarModalCargaHoraria() {
  mostrarModalCargaHoraria.value = false
}

async function ejecutarAsignarCargaHoraria() {
  try {
    const resumen = await reporteExcel.asignarCargaHoraria({
      anio: reporteExcel.anioActivos.value,
      periodo: reporteExcel.periodoActivos.value,
    })
    resultadoCargaHoraria.value = resumen
    mostrarModalCargaHoraria.value = false
  } catch (e) {
    console.error('Error asignando carga horaria:', e)
    resultadoCargaHoraria.value = { error: reporteExcel.errorCargaHoraria.value || 'Error al asignar la carga horaria' }
  }
}

async function descargarExcelConfirmado() {
  // Si ya se asignó carga horaria automática y/o se combinaron materias en
  // la vista previa, se descarga con esos datos exactos (vía POST) para no
  // perder los cambios hechos en pantalla.
  const usarDatosPersonalizados = reporteExcel.cargaHorariaAsignada.value || reporteExcel.materiasCombinadas.value

  if (usarDatosPersonalizados) {
    try {
      await reporteExcel.descargarExcelPersonalizado({
        gestion: reporteExcel.gestionEtiqueta.value,
        version: excelParams.value.version,
      })
      cerrarPreviewExcel()
    } catch (e) {
      alert('No se pudo descargar el Excel con los cambios aplicados en la vista previa')
    }
    return
  }

  const url = reporteExcel.urlDescarga({
    gestion_desde: excelParams.value.gestion_desde,
    gestion_hasta: excelParams.value.gestion_hasta,
    periodo: excelParams.value.periodo,
    version: excelParams.value.version,
    categoria: excelParams.value.categorias,       // ✅ corregido
    tipo_titulo: excelParams.value.tiposTitulo,    // ✅ corregido
  })

  window.open(url, '_blank')
  cerrarPreviewExcel()
}

// ─── Debounce para búsqueda ───
let timeoutId = null
watch(filtroNombre, () => {
  clearTimeout(timeoutId)
  timeoutId = setTimeout(() => {}, 300)
})

onMounted(async () => {
  if (route.query.q) {
    filtroNombre.value = String(route.query.q)
    router.replace({ query: { ...route.query, q: undefined } })
  }
  await cargarCategorias()
  await cargarTipos()
  await cargar()
})
</script>