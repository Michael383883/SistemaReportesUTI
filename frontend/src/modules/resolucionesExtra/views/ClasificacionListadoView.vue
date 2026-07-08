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

        <router-link
          :to="{ name: 'clasificaciones-nueva' }"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva
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
          placeholder="Buscar docente..."
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
      class="bg-white rounded-xl border border-gray-200 p-3 mb-4 flex flex-wrap items-center gap-2"
    >
      <select
        v-model="filtros.categoria"
        @change="cargar"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
      >
        <option value="">Todos</option>
        <option value="Docentes Titulares">Titulares</option>
        <option value="Docentes Temporales">Temporales</option>
        <option value="Examen de suficiencia">Examen de suficiencia</option>
        <option value="Acefala">Acefala</option>
        <option value="Sin Examen de suficiencia">Sin Examen de suficiencia</option>
      </select>
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
            <tr class="border-b border-gray-100 bg-gray-50/80">
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Docente</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Documento</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Categoria</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Nivel</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Gestión</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Periodo</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">PDF</th>
              <th class="text-right font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in listadoFiltrado"
              :key="c.COD_DOCENTE ?? c.NOMBRE_DOCENTE"
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
                <a
                  v-if="c.NOMBRE_ARCHIVO"
                  :href="clasificacion.urlPdf(c.ID_CLASIFICACION, 'inline')"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
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
                  <router-link
                    v-if="c.COD_DOCENTE"
                    :to="{ name: 'clasificaciones-docente', params: { cod_docente: c.COD_DOCENTE } }"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors"
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
                    class="inline-flex items-center p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
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
            <span v-if="itemAEliminar?._totalClasificaciones > 1" class="text-sm text-amber-600">
              Este docente tiene {{ itemAEliminar._totalClasificaciones }} clasificaciones registradas. Solo se eliminará la más reciente mostrada en el listado.
            </span>
            <span v-else class="text-sm text-red-500">
              Esta acción eliminará todas las materias y referencias asociadas.
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
            <button
              @click="cargarPreviewExcel"
              class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700"
            >
              Actualizar vista previa
            </button>
            <span v-if="!reporteExcel.loading.value && !reporteExcel.error.value" class="text-xs text-gray-400 mb-1.5 ml-auto">
              {{ reporteExcel.totalFilas.value }} fila(s) · {{ reporteExcel.gestionEtiqueta.value }}
            </span>
          </div>

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

            <table v-else-if="reporteExcel.preview.value.length" class="w-full text-xs border-collapse">
              <thead class="sticky top-0 bg-white">
                <tr class="border-b border-gray-200">
                  <th class="text-center font-medium text-gray-500 px-2 py-2 uppercase tracking-wider w-10">Nº</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Docente</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Materia</th>
                  <th class="text-center font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">CH</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Tipo Documento</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Detalle</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Categoria</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Nivel</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Fotocopia</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Obs 2</th>
                  <th class="text-left font-medium text-gray-500 px-2 py-2 uppercase tracking-wider">Obs 3</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(item, i) in reporteExcel.preview.value" :key="i">
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

                    <td class="px-2 py-2 text-gray-700" :class="item.NEGRITA ? 'font-semibold' : ''">
                      {{ item.NOMBRE_MATERIA }}
                    </td>
                    <td class="px-2 py-2 text-center text-gray-600">{{ item.CH || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.TIPO_DOCUMENTO || '—' }}</td>
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
              No hay registros para los parámetros indicados
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
              :disabled="!reporteExcel.preview.value.length"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useClasificacion } from '../composables/useClasificacion'
import { useReporteExcel } from '../composables/useReporteExcel'

const router = useRouter()
const clasificacion = useClasificacion()
const reporteExcel = useReporteExcel()
const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Filtros de la tabla principal
const filtroNombre = ref('')
const filtros = ref({
  categoria: '',
  nivel: '',
  gestion: '',
})

// Panel de filtros colapsable
const mostrarFiltros = ref(false)
const filtrosActivosCount = computed(() => {
  let count = 0
  if (filtros.value.categoria) count++
  if (filtros.value.nivel) count++
  if (filtros.value.gestion) count++
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
      nivel: filtros.value.nivel || undefined,
      gestion: filtros.value.gestion || undefined,
    })
  } catch (e) {
    console.error('Error cargando listado de clasificaciones:', e)
  }
}

function limpiarFiltros() {
  filtroNombre.value = ''
  filtros.value.categoria = ''
  filtros.value.nivel = ''
  filtros.value.gestion = ''
  cargar()
}

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

// ─── Filtrado en cliente (solo por nombre, sobre lo ya cargado) ───
const listadoFiltrado = computed(() => {
  const term = filtroNombre.value.trim().toLowerCase()
  if (!term) return listadoPorDocente.value
  return listadoPorDocente.value.filter(c =>
    (c.NOMBRE_DOCENTE || '').toLowerCase().includes(term)
  )
})

// ─── Badge de categoria ───
function badgeCategoria(categoria) {
  if (categoria === 'Docentes Titulares')  return 'bg-emerald-50 text-emerald-700'
  if (categoria === 'Docentes Temporales') return 'bg-amber-50 text-amber-700'
  if (categoria === 'Examen de suficiencia') return 'bg-blue-50 text-blue-700'
  if (categoria === 'Acefala') return 'bg-gray-50 text-gray-700'
  if (categoria === 'Sin Examen de suficiencia') return 'bg-orange-50 text-red-700'

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

  try {
    const result = await clasificacion.eliminar(itemAEliminar.value.ID_CLASIFICACION)

    if (result?.ok) {
      await cargar()
      cerrarModal()
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

// ─── Vista previa de Reporte Excel (usa el mismo construirDatos() del backend) ───
const mostrarPreviewExcel = ref(false)
const excelParams = ref({
  gestion_desde: '2001',
  gestion_hasta: '',
  periodo: '',
  version: '5ta Versión',
})

async function abrirPreviewExcel() {
  if (filtros.value.gestion) {
    excelParams.value.gestion_desde = filtros.value.gestion
  }
  mostrarPreviewExcel.value = true
  await cargarPreviewExcel()
}

async function cargarPreviewExcel() {
  try {
    await reporteExcel.previsualizar({
      gestion_desde: excelParams.value.gestion_desde,
      gestion_hasta: excelParams.value.gestion_hasta,
      periodo: excelParams.value.periodo,
      version: excelParams.value.version,
    })
  } catch (e) {
    console.error('Error cargando vista previa de Excel:', e)
  }
}

function cerrarPreviewExcel() {
  mostrarPreviewExcel.value = false
}

function descargarExcelConfirmado() {
  const url = reporteExcel.urlDescarga({
    gestion_desde: excelParams.value.gestion_desde,
    gestion_hasta: excelParams.value.gestion_hasta,
    periodo: excelParams.value.periodo,
    version: excelParams.value.version,
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

onMounted(cargar)
</script>