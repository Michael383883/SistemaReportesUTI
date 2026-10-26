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

        <!-- Botón Prelación: abre el mismo preview ya preconfigurado -->
        <button
          @click="abrirPrelacion"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
          title="Reporte de prelación (configuración automática)"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
          </svg>
          Prelación
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
      <!-- Overlay invisible para cerrar los dropdowns al hacer clic fuera -->
      <div
        v-if="filtroCatOpen || filtroTituloOpen || filtroObsOpen"
        class="fixed inset-0 z-20"
        @click="cerrarDropdownsFiltro"
      ></div>

      <!-- Categoría de documento (multi-selección) -->
      <div class="relative flex flex-col gap-0.5" :class="{ 'z-30': filtroCatOpen }">
        <label class="text-[10px] font-medium text-gray-400 px-0.5">Categoría de documento</label>
        <button
          type="button"
          @click="filtroCatOpen = !filtroCatOpen; filtroTituloOpen = false; filtroObsOpen = false"
          class="w-56 flex items-center justify-between gap-2 px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left"
        >
          <span class="truncate" :class="filtros.categoria.length ? 'text-gray-800' : 'text-gray-400'">
            {{ filtros.categoria.length ? `${filtros.categoria.length} seleccionada(s)` : 'Todos' }}
          </span>
          <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div
          v-if="filtroCatOpen"
          class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
          <label
            v-for="cat in categorias"
            :key="cat"
            class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
          >
            <input type="checkbox" :value="cat" v-model="filtros.categoria" @change="cargar" class="accent-blue-600" />
            {{ cat }}
          </label>
          <div v-if="!categorias.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin categorías</div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button
              type="button"
              class="text-[11px] text-gray-500 hover:text-red-500"
              @mousedown.prevent="filtros.categoria = []; cargar()"
            >
              Limpiar
            </button>
            <button
              type="button"
              class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
              @mousedown.prevent="filtroCatOpen = false"
            >
              Listo
            </button>
          </div>
        </div>
      </div>

      <!-- Categoría de título (multi-selección) -->
      <div class="relative flex flex-col gap-0.5" :class="{ 'z-30': filtroTituloOpen }">
        <label class="text-[10px] font-medium text-gray-400 px-0.5">Categoría de título</label>
        <button
          type="button"
          @click="filtroTituloOpen = !filtroTituloOpen; filtroCatOpen = false; filtroObsOpen = false"
          class="w-56 flex items-center justify-between gap-2 px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left"
        >
          <span class="truncate" :class="filtros.tipo_titulo.length ? 'text-gray-800' : 'text-gray-400'">
            {{ filtros.tipo_titulo.length ? `${filtros.tipo_titulo.length} seleccionada(s)` : 'Todos' }}
          </span>
          <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div
          v-if="filtroTituloOpen"
          class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
          <label
            v-for="tipo in tiposTitulo"
            :key="tipo"
            class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
          >
            <input type="checkbox" :value="tipo" v-model="filtros.tipo_titulo" @change="cargar" class="accent-blue-600" />
            {{ tipo }}
          </label>
          <div v-if="!tiposTitulo.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin tipos de título</div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button
              type="button"
              class="text-[11px] text-gray-500 hover:text-red-500"
              @mousedown.prevent="filtros.tipo_titulo = []; cargar()"
            >
              Limpiar
            </button>
            <button
              type="button"
              class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
              @mousedown.prevent="filtroTituloOpen = false"
            >
              Listo
            </button>
          </div>
        </div>
      </div>

      <!-- Observación 1 (multi-selección). Las opciones salen de las observaciones
           que existen en los documentos; si no hay ninguna, el menú sale vacío. -->
      <div class="relative flex flex-col gap-0.5" :class="{ 'z-30': filtroObsOpen }">
        <label class="text-[10px] font-medium text-gray-400 px-0.5">Observación</label>
        <button
          type="button"
          @click="filtroObsOpen = !filtroObsOpen; filtroCatOpen = false; filtroTituloOpen = false"
          class="w-56 flex items-center justify-between gap-2 px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left"
        >
          <span class="truncate" :class="filtros.observacion.length ? 'text-gray-800' : 'text-gray-400'">
            {{ filtros.observacion.length ? `${filtros.observacion.length} seleccionada(s)` : 'Todas' }}
          </span>
          <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div
          v-if="filtroObsOpen"
          class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
          <label
            v-for="obs in observacionesDisponibles"
            :key="obs"
            class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
          >
            <input type="checkbox" :value="obs" v-model="filtros.observacion" class="accent-blue-600" />
            {{ obs }}
          </label>
          <div v-if="!observacionesDisponibles.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin observaciones</div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button
              type="button"
              class="text-[11px] text-gray-500 hover:text-red-500"
              @mousedown.prevent="filtros.observacion = []"
            >
              Limpiar
            </button>
            <button
              type="button"
              class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
              @mousedown.prevent="filtroObsOpen = false"
            >
              Listo
            </button>
          </div>
        </div>
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

      <!-- Exportar (PDF / Excel): solo visible cuando el panel de filtros está desplegado -->
      <ExportarListado :listado="listadoFiltrado" :filtros-texto="filtrosTexto" />

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
              <th class="text-center font-medium text-slate-100 px-3 py-3 text-xs uppercase tracking-wider w-12">N°</th>
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
            <tr v-for="(c, idx) in listadoFiltrado" :key="c.ID_CLASIFICACION_DOCENTE ?? c.COD_DOCENTE ?? c.NOMBRE_DOCENTE"
              class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-3 py-3 text-center text-sm font-semibold text-gray-400 tabular-nums">
                {{ idx + 1 }}
              </td>

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

              <!-- Documento -->
              <td class="px-4 py-3 max-w-[220px]">
                <div class="flex flex-col">
                  <span class="text-sm font-medium text-gray-800 truncate">
                    {{ c.TIPO_DOCUMENTO || '—' }}
                  </span>
                  <span v-if="c.DETALLE_GENERAL" class="text-xs text-gray-400 truncate">
                    {{ c.DETALLE_GENERAL }}
                  </span>
                  <span v-if="c.OBSERVACION" class="text-xs text-indigo-500 truncate" :title="c.OBSERVACION">
                    Obs: {{ c.OBSERVACION }}
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

              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.GESTION || '—' }}</td>
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.PERIODO || '—' }}</td>

              <!-- PDF -->
              <td class="px-4 py-3">
                <button
                  v-if="c.NOMBRE_ARCHIVO"
                  @click="verPdf(c.ID_DOCUMENTO)"
                  :disabled="abriendoPdfId === c.ID_DOCUMENTO"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-800 hover:bg-blue-900 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-60"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  {{ abriendoPdfId === c.ID_DOCUMENTO ? 'Abriendo...' : 'Ver PDF' }}
                </button>
                <span v-else class="text-gray-300 text-sm">—</span>
              </td>

              <!-- Acciones -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
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
        <span>
          Mostrando {{ listadoFiltrado.length }}
          {{ (filtroNombre.trim() || hayFiltroCategoria) ? 'documentos' : 'docentes' }}
        </span>
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

    <!-- ═══════════════════════════════════════════════════════════
         MODAL: Vista previa del reporte Excel (rediseñado)
         Los filtros se aplican solos; los interruptores muestran
         claramente qué está activo.
         ═══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="mostrarPreviewExcel"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-3"
        @click.self="cerrarPreviewExcel"
      >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl h-[94vh] flex flex-col">

          <!-- ── Encabezado ── -->
          <div class="flex items-center justify-between gap-3 px-6 py-3.5 border-b border-gray-100">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base font-semibold text-gray-900">
                  {{ modoPrelacion ? 'Reporte de Prelación' : 'Reporte Excel' }}
                </h3>
                <span v-if="modoPrelacion" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-indigo-100 text-indigo-700">
                  Modo Prelación
                </span>
                <span v-if="estaActualizando" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium rounded-full bg-blue-50 text-blue-600">
                  <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  Actualizando…
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                Así se verá el archivo. Los cambios se aplican automáticamente.
              </p>
            </div>

            <div class="flex items-center gap-2">
              <button
                v-if="!modoPrelacion"
                @click="abrirPrelacion"
                class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors"
                title="Aplica la configuración de Prelación"
              >
                Usar configuración de Prelación
              </button>
              <button
                v-else
                @click="abrirPrelacion"
                class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                title="Vuelve a la configuración original de Prelación"
              >
                Restablecer Prelación
              </button>
              <button
                @click="panelOpciones = !panelOpciones"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              >
                {{ panelOpciones ? 'Ocultar opciones' : 'Mostrar opciones' }}
                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': !panelOpciones }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <button @click="cerrarPreviewExcel" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Cerrar">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- ── Panel de opciones (3 bloques numerados) ── -->
          <div v-show="panelOpciones" class="relative px-6 py-4 bg-slate-50 border-b border-gray-100">

            <!-- Overlay para cerrar dropdowns -->
            <div v-if="dropdownAbierto" class="fixed inset-0 z-20" @click="dropdownAbierto = null"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

              <!-- 1 · Qué datos incluir -->
              <section class="bg-white rounded-xl border border-gray-200 p-4">
                <header class="flex items-center gap-2 mb-3">
                  <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">1</span>
                  <h4 class="text-sm font-semibold text-gray-800">¿Qué datos incluir?</h4>
                </header>

                <div class="grid grid-cols-3 gap-2 mb-2">
                  <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Gestión desde</label>
                    <input v-model="excelParams.gestion_desde" type="text" placeholder="2001"
                      class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Hasta</label>
                    <input v-model="excelParams.gestion_hasta" type="text" placeholder="Opcional"
                      class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Periodo</label>
                    <input v-model="excelParams.periodo" type="text" placeholder="Todos"
                      class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </div>
                </div>

                <!-- Dropdowns multi-selección (se aplican al marcar) -->
                <div class="grid grid-cols-2 gap-2">
                  <div v-for="f in filtrosMulti" :key="f.id" class="relative" :class="{ 'z-30': dropdownAbierto === f.id }">
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">{{ f.label }}</label>
                    <button
                      type="button"
                      @click="dropdownAbierto = dropdownAbierto === f.id ? null : f.id"
                      class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 text-sm border rounded-lg bg-white text-left"
                      :class="excelParams[f.modelo].length ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-300'"
                    >
                      <span class="truncate" :class="excelParams[f.modelo].length ? 'text-gray-800 font-medium' : 'text-gray-400'">
                        {{ excelParams[f.modelo].length ? `${excelParams[f.modelo].length} seleccionada(s)` : 'Todas' }}
                      </span>
                      <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      </svg>
                    </button>

                    <div
                      v-if="dropdownAbierto === f.id"
                      class="absolute left-0 top-full mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto"
                    >
                      <label
                        v-if="f.especial"
                        class="flex items-center gap-2 px-3 py-2 text-[13px] hover:bg-blue-50 cursor-pointer border-b border-gray-100"
                      >
                        <input type="checkbox" :value="f.especial.valor" v-model="excelParams[f.modelo]" class="accent-blue-600" />
                        <span class="italic text-gray-500">{{ f.especial.texto }}</span>
                      </label>
                      <label
                        v-for="op in f.opciones"
                        :key="op"
                        class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
                      >
                        <input type="checkbox" :value="op" v-model="excelParams[f.modelo]" class="accent-blue-600" />
                        {{ op }}
                      </label>
                      <div v-if="!f.opciones.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin opciones</div>
                      <div class="sticky bottom-0 flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
                        <button type="button" class="text-[11px] text-gray-500 hover:text-red-500"
                          @mousedown.prevent="excelParams[f.modelo] = []">
                          Quitar todas
                        </button>
                        <button type="button" class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
                          @mousedown.prevent="dropdownAbierto = null">
                          Listo
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Etiquetas de lo seleccionado -->
                <div v-if="etiquetasSeleccionadas.length" class="flex flex-wrap gap-1.5 mt-2.5">
                  <span
                    v-for="e in etiquetasSeleccionadas"
                    :key="e.modelo + e.valor"
                    class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 text-[11px] font-medium rounded-full bg-blue-50 text-blue-700"
                  >
                    {{ e.texto }}
                    <button type="button" @click="quitarEtiqueta(e)" class="w-4 h-4 rounded-full hover:bg-blue-100 flex items-center justify-center" title="Quitar">
                      <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </span>
                </div>
              </section>

              <!-- 2 · Cómo mostrarlos -->
              <section class="bg-white rounded-xl border border-gray-200 p-4">
                <header class="flex items-center gap-2 mb-3">
                  <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">2</span>
                  <h4 class="text-sm font-semibold text-gray-800">¿Cómo mostrarlos?</h4>
                </header>

                <div class="space-y-1.5">
                  <button
                    v-for="t in interruptores"
                    :key="t.id"
                    type="button"
                    role="switch"
                    :aria-checked="t.activo"
                    :disabled="t.deshabilitado"
                    @click="t.accion()"
                    class="w-full flex items-start gap-3 px-3 py-2 rounded-lg border text-left transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="t.activo ? 'bg-blue-50/60 border-blue-200' : 'bg-white border-gray-200 hover:bg-gray-50'"
                  >
                    <span class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors"
                      :class="t.activo ? 'bg-blue-600' : 'bg-gray-300'">
                      <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                        :class="t.activo ? 'translate-x-4' : ''"></span>
                    </span>
                    <span class="min-w-0">
                      <span class="block text-sm font-medium text-gray-800">{{ t.titulo }}</span>
                      <span class="block text-xs text-gray-500 leading-snug">{{ t.desc }}</span>
                    </span>
                  </button>
                </div>
              </section>

              <!-- 3 · Docentes activos -->
              <section class="bg-white rounded-xl border border-gray-200 p-4">
                <header class="flex items-center gap-2 mb-3">
                  <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">3</span>
                  <h4 class="text-sm font-semibold text-gray-800">Docentes activos</h4>
                </header>

                <div class="flex items-end gap-2 mb-2">
                  <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Año</label>
                    <input v-model.number="reporteExcel.anioActivos.value" type="number"
                      class="w-24 px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Periodo</label>
                    <select v-model.number="reporteExcel.periodoActivos.value"
                      class="px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                      <option :value="1">1</option>
                      <option :value="2">2</option>
                      <option :value="3">3</option>
                      <option :value="4">4</option>
                    </select>
                  </div>
                </div>

                <button
                  type="button"
                  role="switch"
                  :aria-checked="reporteExcel.soloActivos.value"
                  :disabled="cargandoActivosUI"
                  @click="alternarSoloActivos"
                  class="w-full flex items-start gap-3 px-3 py-2 rounded-lg border text-left transition-colors disabled:opacity-60"
                  :class="reporteExcel.soloActivos.value ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200 hover:bg-gray-50'"
                >
                  <span class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors"
                    :class="reporteExcel.soloActivos.value ? 'bg-emerald-600' : 'bg-gray-300'">
                    <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                      :class="reporteExcel.soloActivos.value ? 'translate-x-4' : ''"></span>
                  </span>
                  <span>
                    <span class="block text-sm font-medium text-gray-800">Solo docentes activos</span>
                    <span class="block text-xs text-gray-500 leading-snug">
                      Muestra solo quienes dictan materia en ese año y periodo. Completa sus materias y carga horaria (CH).
                    </span>
                  </span>
                </button>

                <div v-if="reporteExcel.soloActivos.value" class="flex items-center gap-2 mt-2">
                  <button
                    @click="recargarActivos"
                    :disabled="cargandoActivosUI"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg disabled:opacity-50"
                    title="Vuelve a consultar activos y carga horaria"
                  >
                    Recargar
                  </button>
                  <button
                    @click="confirmarAsignarCargaHoraria"
                    :disabled="reporteExcel.cargandoCargaHoraria.value"
                    class="px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg disabled:opacity-50"
                    title="Vuelve a comparar materia por materia con la carga horaria real (experimental)"
                  >
                    Reasignar CH (experimental)
                  </button>
                </div>

                <p v-if="reporteExcel.errorActivos.value" class="text-xs text-red-500 mt-2">{{ reporteExcel.errorActivos.value }}</p>
                <p v-if="resultadoCargaHoraria?.error" class="text-xs text-red-500 mt-2">{{ resultadoCargaHoraria.error }}</p>
                <p v-else-if="resultadoCargaHoraria?.asignadas !== undefined" class="text-xs text-emerald-600 mt-2">
                  ✓ {{ resultadoCargaHoraria.asignadas }} materia(s) con CH asignada
                  <span v-if="resultadoCargaHoraria.sinCoincidencia" class="text-amber-600">
                    · {{ resultadoCargaHoraria.sinCoincidencia }} sin coincidencia
                  </span>
                </p>
              </section>
            </div>
          </div>

          <!-- ── Franja de resumen ── -->
          <div class="flex flex-wrap items-center gap-2 px-6 py-2.5 border-b border-gray-100 text-xs">
            <template v-if="!reporteExcel.loading.value && !reporteExcel.error.value">
              <span class="px-2.5 py-1 rounded-full bg-slate-900 text-white font-semibold">
                {{ reporteExcel.totalDocentes.value }} docentes
              </span>
              <template v-if="reporteExcel.soloActivos.value">
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">
                  {{ reporteExcel.docentesConDocumento.value }} con documento
                </span>
                <span class="px-2.5 py-1 rounded-full bg-orange-50 text-orange-700 font-medium">
                  {{ reporteExcel.docentesSinDocumento.value }} sin documento
                </span>
              </template>
              <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ reporteExcel.previewMostrado.value.length }} filas
              </span>
              <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ reporteExcel.gestionEtiqueta.value }}
              </span>
              <span v-if="reporteExcel.filasOcultasCompartidas.value" class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">
                {{ reporteExcel.filasOcultasCompartidas.value }} acéfala(s) compartida(s) oculta(s)
              </span>
              <span v-if="reporteExcel.soloActivos.value" class="text-gray-400 ml-auto">
                Activos en {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}
                ({{ reporteExcel.docentesActivos.value.length }})
              </span>
            </template>
          </div>

          <!-- ── Tabla ── -->
          <div class="relative flex-1 overflow-auto px-6 pb-3">

            <!-- Velo de carga: mantiene la tabla anterior atenuada -->
            <div v-if="estaActualizando && reporteExcel.previewMostrado.value.length"
              class="absolute inset-0 z-20 bg-white/60 flex items-start justify-center pt-24">
              <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-lg border border-gray-100 text-sm text-gray-600">
                <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Actualizando vista previa…
              </div>
            </div>

            <div v-if="reporteExcel.loading.value && !reporteExcel.previewMostrado.value.length" class="text-center py-16 text-sm text-gray-400">
              <svg class="w-6 h-6 mx-auto animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Cargando vista previa...
            </div>

            <div v-else-if="reporteExcel.error.value" class="text-center py-16 text-sm text-red-500">
              {{ reporteExcel.error.value }}
            </div>

            <table v-else-if="reporteExcel.previewMostrado.value.length" class="w-full text-xs border-collapse">
              <thead class="sticky top-0 z-10 bg-white shadow-[0_1px_0_0_#e5e7eb]">
                <tr>
                  <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider w-10">Nº</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Docente</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Materia</th>
                  <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">CH</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Detalle</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Categoría</th>
                  <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider" title="Total de carga horaria del docente">T. CH</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Nivel</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Fotocopia</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Obs 2</th>
                  <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Obs 3</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(item, i) in reporteExcel.previewMostrado.value" :key="i">
                  <tr
                    class="border-b border-gray-50"
                    :class="[
                      item.FIN_GRUPO ? 'border-b-2 border-b-gray-300' : '',
                      item.SIN_DOCUMENTO ? 'bg-orange-50/60' : '',
                      item.COMPARTIDA ? 'bg-pink-50/60' : ''
                    ]"
                  >
                    <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                      class="px-2 py-2 text-center align-middle border-r border-gray-100 font-medium text-gray-700">
                      {{ item.N }}
                    </td>
                    <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                      class="px-2 py-2 align-middle border-r border-gray-100 font-medium text-gray-800">
                      {{ item.NOMBRE_DOCENTE }}
                    </td>

                    <td v-if="item.INICIO_MATERIA !== false" :rowspan="item.FILAS_MATERIA || 1"
                      class="px-2 py-2 text-gray-700 align-middle" :class="item.NEGRITA ? 'font-semibold' : ''">
                      {{ item.NOMBRE_MATERIA }}
                    </td>
                    <td v-if="item.INICIO_MATERIA !== false" :rowspan="item.FILAS_MATERIA || 1"
                      class="px-2 py-2 text-center text-gray-600 align-middle">
                      {{ item.CH || '—' }}
                    </td>
                    <td class="px-2 py-2 text-gray-600">{{ item.DETALLE || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.CATEGORIA || '—' }}</td>

                    <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                      class="px-2 py-2 text-center align-middle border-x border-gray-100 font-semibold text-gray-800 tabular-nums">
                      {{ item.TOTAL_CH || '—' }}
                    </td>

                    <td class="px-2 py-2 text-gray-600">{{ item.NIVEL || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.FOTOCOPIA_TITULAR || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.OBS2 || '—' }}</td>
                    <td class="px-2 py-2 text-gray-600">{{ item.OBS3 || '—' }}</td>
                  </tr>
                </template>
              </tbody>
            </table>

            <div v-else class="text-center py-16 text-sm text-gray-400">
              <template v-if="reporteExcel.soloActivos.value && reporteExcel.preview.value.length">
                Ningún docente coincide con la lista de activos de {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}.
                <div class="mt-2">
                  <button @click="alternarSoloActivos" class="text-blue-500 hover:underline">Quitar “Solo docentes activos”</button>
                </div>
              </template>
              <template v-else>
                No hay registros con los filtros actuales. Prueba quitando alguna categoría o ampliando la gestión.
              </template>
            </div>
          </div>

          <!-- ── Pie ── -->
          <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5 border-t border-gray-100">
            <div class="flex items-center gap-3">
              <label class="text-[11px] font-medium text-gray-500">Versión (título del archivo)</label>
              <input v-model="excelParams.version" type="text" placeholder="Ej. 5ta Versión"
                class="w-40 px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div class="flex items-center gap-3">
              <button
                @click="cerrarPreviewExcel"
                class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              >
                Cerrar
              </button>
              <button
                @click="descargarExcelConfirmado"
                :disabled="!reporteExcel.previewMostrado.value.length || estaActualizando"
                class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H6a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar Excel
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Confirmación: Reasignar Carga Horaria (experimental) -->
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
            ¿Reasignar carga horaria?
          </h3>
          <p class="text-center text-sm text-gray-500 mb-2">
            Esta función es <span class="font-semibold text-amber-600">experimental</span>.
          </p>
          <p class="text-center text-sm text-gray-500 mb-6">
            Se compara cada docente activo y el nombre de su materia contra la carga horaria real
            de {{ reporteExcel.anioActivos.value }} / periodo {{ reporteExcel.periodoActivos.value }}.
            Si el nombre coincide (ignorando tildes, puntuación y números romanos) o es muy parecido,
            se asigna la CH. Revisa el resultado antes de descargar.
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useClasificacion } from '../composables/useClasificacion'
import { useReporteExcel } from '../composables/useReporteExcel'
import { useCategorias } from '../composables/useCategorias'
import { useTiposTitulo } from '../composables/useTiposTitulo'
import ExportarListado from '../components/ExportarListado.vue'

const router = useRouter()
const route = useRoute()
const clasificacion = useClasificacion()
const reporteExcel = useReporteExcel()
const { categorias, cargarCategorias } = useCategorias()
const { tipos: tiposTitulo, cargarTipos } = useTiposTitulo()
const API_BASE = import.meta.env.VITE_API_URL ?? ''

// ─── Ver PDF (protegido con auth:sanctum) ───
const abriendoPdfId = ref(null)

async function verPdf(idDocumento) {
  if (abriendoPdfId.value) return
  abriendoPdfId.value = idDocumento
  try {
    await clasificacion.verPdf(idDocumento, 'inline')
  } catch (e) {
    alert(e?.response?.status === 401
      ? 'Tu sesión expiró. Vuelve a iniciar sesión.'
      : 'No se pudo abrir el PDF'
    )
  } finally {
    abriendoPdfId.value = null
  }
}

// Filtros de la tabla principal
const filtroNombre = ref('')
const filtros = ref({
  categoria: [],
  tipo_titulo: [],
  observacion: [],
  nivel: '',
  gestion: '',
  periodo: '',
})

// Dropdowns multi-selección del panel de filtros
const filtroCatOpen = ref(false)
const filtroTituloOpen = ref(false)
const filtroObsOpen = ref(false)
function cerrarDropdownsFiltro() {
  filtroCatOpen.value = false
  filtroTituloOpen.value = false
  filtroObsOpen.value = false
}

// Observaciones distintas que existen en los documentos cargados.
// Sin duplicados (ignora tildes y mayúsculas). Si no hay ninguna, queda vacío.
// Las ya seleccionadas se conservan aunque otro filtro las deje fuera de la lista.
const observacionesDisponibles = computed(() => {
  const mapa = new Map()
  for (const c of clasificacion.listado.value) {
    const o = String(c.OBSERVACION || '').trim()
    if (o && !mapa.has(norm(o))) mapa.set(norm(o), o)
  }
  for (const o of filtros.value.observacion) {
    if (!mapa.has(norm(o))) mapa.set(norm(o), o)
  }
  return [...mapa.values()].sort((a, b) => a.localeCompare(b, 'es'))
})

// Panel de filtros colapsable
const mostrarFiltros = ref(false)
const filtrosActivosCount = computed(() => {
  let count = 0
  if (filtros.value.categoria.length) count++
  if (filtros.value.tipo_titulo.length) count++
  if (filtros.value.observacion.length) count++
  if (filtros.value.nivel) count++
  if (filtros.value.gestion) count++
  if (filtros.value.periodo) count++
  return count
})

// Texto que resume los filtros activos (se imprime en el PDF / Excel exportado)
const filtrosTexto = computed(() => {
  const partes = []
  if (filtroNombre.value.trim()) partes.push(`Búsqueda: "${filtroNombre.value.trim()}"`)
  if (filtros.value.categoria.length) partes.push(`Categoría: ${filtros.value.categoria.join(', ')}`)
  if (filtros.value.tipo_titulo.length) partes.push(`Título: ${filtros.value.tipo_titulo.join(', ')}`)
  if (filtros.value.observacion.length) partes.push(`Observación: ${filtros.value.observacion.join(', ')}`)
  if (filtros.value.nivel) partes.push(`Nivel: ${filtros.value.nivel}`)
  if (filtros.value.gestion) partes.push(`Gestión: ${filtros.value.gestion}`)
  if (filtros.value.periodo) partes.push(`Periodo: ${filtros.value.periodo}`)
  return partes.join(' · ')
})

// Modal de eliminación
const mostrarModal = ref(false)
const itemAEliminar = ref(null)
const eliminando = ref(false)

// ─── Cargar datos de la tabla principal ───
async function cargar() {
  try {
    await clasificacion.listar({
      categoria: filtros.value.categoria.length ? filtros.value.categoria : undefined,
      tipo_titulo: filtros.value.tipo_titulo.length ? filtros.value.tipo_titulo : undefined,
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
  filtros.value.categoria = []
  filtros.value.tipo_titulo = []
  filtros.value.observacion = []
  filtros.value.nivel = ''
  filtros.value.gestion = ''
  filtros.value.periodo = ''
  cerrarDropdownsFiltro()
  cargar()
}

// Cuenta cuántos docentes distintos tiene cada documento
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
const hayFiltroCategoria = computed(
  () => filtros.value.categoria.length > 0 || filtros.value.tipo_titulo.length > 0 || filtros.value.observacion.length > 0
)

const listadoFiltrado = computed(() => {
  const term = filtroNombre.value.trim().toLowerCase()

  if (!term && !hayFiltroCategoria.value) return listadoPorDocente.value

  const coincide = (c) => {
    if (filtros.value.categoria.length) {
      const cats = filtros.value.categoria.map(norm)
      if (!cats.includes(norm(c.CATEGORIA))) return false
    }

    if (filtros.value.observacion.length) {
      const obs = filtros.value.observacion.map(norm)
      if (!obs.includes(norm(c.OBSERVACION))) return false
    }

    if (!term) return true

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

// ─── Normalizar: quita tildes, mayúsculas y espacios sobrantes ───
const norm = (s) => String(s || '')
  .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  .toUpperCase().trim()

// ─── Color por categoría ───
const COLORES_CATEGORIA = {
  'TITULARES':                'bg-emerald-50 text-emerald-700',
  'TITULARES REV':            'bg-green-50 text-green-700',
  'REEMPLAZO TEMPORAL':       'bg-amber-50 text-amber-700',
  'SUPLENCIA':                'bg-yellow-50 text-yellow-700',
  'EXAMEN SUFICIENCIA':       'bg-blue-50 text-blue-700',
  'ACEFALA':                  'bg-sky-100 text-sky-800',
  'SIN CATEGORIA':            'bg-orange-50 text-red-700',
  'CERTIFICADO':              'bg-violet-50 text-violet-700',
  'CONCURSO DE MERITO':       'bg-fuchsia-50 text-fuchsia-700',
  'PLANILLA DE CALIFICACION': 'bg-cyan-50 text-cyan-700',
  'COORDINADOR':              'bg-indigo-50 text-indigo-700',
  'DIRECTOR':                 'bg-purple-50 text-purple-700',
  'JEFE':                     'bg-rose-50 text-rose-700',
  'HISTORICO':                'bg-stone-100 text-stone-700',
  'PRELACION':                'bg-teal-50 text-teal-700',
}

function badgeCategoria(categoria) {
  return COLORES_CATEGORIA[norm(categoria)] ?? 'bg-gray-50 text-gray-600'
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

// ═══════════════════════════════════════════════════════════════
// VISTA PREVIA DEL REPORTE EXCEL
// ═══════════════════════════════════════════════════════════════
const mostrarPreviewExcel = ref(false)
const modoPrelacion = ref(false)
const panelOpciones = ref(true)
const dropdownAbierto = ref(null) // 'cat' | 'titulo' | null

const excelParams = ref({
  gestion_desde: '2001',
  gestion_hasta: '',
  periodo: '',
  version: '5ta Versión',
  categorias: [],
  tiposTitulo: [],   // puede incluir el sentinel '__SIN_TITULO__'
})

const resultadoCargaHoraria = ref(null) // { asignadas, sinCoincidencia } | { error }

// Define los dos dropdowns multi-selección en un solo lugar
const filtrosMulti = computed(() => [
  {
    id: 'cat',
    label: 'Categoría de documento',
    modelo: 'categorias',
    opciones: categorias.value,
    especial: null,
  },
  {
    id: 'titulo',
    label: 'Categoría de título',
    modelo: 'tiposTitulo',
    opciones: tiposTitulo.value,
    especial: { valor: '__SIN_TITULO__', texto: 'Sin título registrado' },
  },
])

// Etiquetas (chips) de lo seleccionado, con botón para quitar cada una
const etiquetasSeleccionadas = computed(() => {
  const lista = []
  for (const v of excelParams.value.categorias) {
    lista.push({ modelo: 'categorias', valor: v, texto: v })
  }
  for (const v of excelParams.value.tiposTitulo) {
    lista.push({
      modelo: 'tiposTitulo',
      valor: v,
      texto: v === '__SIN_TITULO__' ? 'Sin título registrado' : v,
    })
  }
  return lista
})

function quitarEtiqueta(e) {
  excelParams.value[e.modelo] = excelParams.value[e.modelo].filter(x => x !== e.valor)
}

// ¿Hay alguna operación en curso?
const cargandoActivosUI = computed(
  () => reporteExcel.cargandoActivos.value || reporteExcel.cargandoCargaHoraria.value
)
const estaActualizando = computed(
  () => reporteExcel.loading.value || cargandoActivosUI.value || recargando.value
)
const recargando = ref(false)

// Interruptores de "¿Cómo mostrarlos?"
const interruptores = computed(() => [
  {
    id: 'combinar',
    titulo: 'Combinar materias repetidas',
    desc: 'Une en una sola celda las materias que se repiten dentro de un mismo docente.',
    activo: reporteExcel.materiasCombinadas.value,
    deshabilitado: estaActualizando.value,
    accion: () => reporteExcel.alternarCombinarMaterias(),
  },
  {
    id: 'refs',
    titulo: 'Mostrar referencias',
    desc: 'Agrega el número de referencia del documento dentro de la columna Detalle.',
    activo: reporteExcel.mostrarReferencias.value,
    deshabilitado: estaActualizando.value,
    accion: alternarMostrarReferencias,
  },
  {
    id: 'comp',
    titulo: 'Incluir materias compartidas',
    desc: reporteExcel.soloActivos.value
      ? 'Muestra las materias acéfalas compartidas, con CH “-”. Apagado: se ocultan.'
      : 'Requiere activar “Solo docentes activos” (paso 3) para detectar las compartidas.',
    activo: reporteExcel.mostrarCompartidas.value,
    deshabilitado: estaActualizando.value || !reporteExcel.soloActivos.value,
    accion: alternarMostrarCompartidas,
  },
])

// ─── Aplicación automática de filtros ───
// Cuando cambian gestión / periodo / categorías, se vuelve a consultar solo
// (con una pequeña espera para no disparar una consulta por cada tecla).
let bloqueandoAuto = false   // true mientras se aplica un preset (Prelación, etc.)
let timerAuto = null

const claveFiltros = computed(() => {
  const p = excelParams.value
  return [
    p.gestion_desde, p.gestion_hasta, p.periodo,
    [...p.categorias].join('|'), [...p.tiposTitulo].join('|'),
  ].join('~')
})

watch(claveFiltros, () => {
  if (!mostrarPreviewExcel.value || bloqueandoAuto) return
  programarRecarga()
})

// Cambiar año/periodo de activos recarga activos + carga horaria
const claveActivos = computed(
  () => `${reporteExcel.anioActivos.value}~${reporteExcel.periodoActivos.value}`
)
let timerActivos = null
watch(claveActivos, () => {
  if (!mostrarPreviewExcel.value || bloqueandoAuto || !reporteExcel.soloActivos.value) return
  clearTimeout(timerActivos)
  timerActivos = setTimeout(recargarActivos, 500)
})

function programarRecarga() {
  clearTimeout(timerAuto)
  recargando.value = true
  timerAuto = setTimeout(async () => {
    if (reporteExcel.loading.value) {       // ya hay una consulta en curso: reintenta
      programarRecarga()
      return
    }
    await conservandoVista(() => cargarPreviewExcel())
    recargando.value = false
  }, 400)
}

// Ejecuta una acción que reinicia el preview y, al terminar, vuelve a
// "Combinar materias" si estaba activo (así no se pierde al cambiar un filtro).
async function conservandoVista(accion) {
  const combinar = reporteExcel.materiasCombinadas.value
  try {
    await accion()
  } catch (e) {
    console.error(e)
  }
  if (combinar && !reporteExcel.materiasCombinadas.value) {
    reporteExcel.alternarCombinarMaterias()
  }
}

// ─── Abrir el modal (configuración normal) ───
async function abrirPreviewExcel() {
  bloqueandoAuto = true
  try {
    modoPrelacion.value = false
    reporteExcel.soloActivos.value = false
    reporteExcel.mostrarReferencias.value = false
    reporteExcel.mostrarCompartidas.value = false
    dropdownAbierto.value = null
    resultadoCargaHoraria.value = null
    excelParams.value = {
      gestion_desde: '2001',
      gestion_hasta: '',
      periodo: '',
      version: excelParams.value.version || '5ta Versión',
      categorias: [],
      tiposTitulo: [],
    }

    if (filtros.value.gestion) {
      excelParams.value.gestion_desde = filtros.value.gestion
    }
    if (filtros.value.categoria.length) {
      excelParams.value.categorias = [...filtros.value.categoria]
    }
    if (filtros.value.tipo_titulo.length) {
      excelParams.value.tiposTitulo = [...filtros.value.tipo_titulo]
    }
    mostrarPreviewExcel.value = true
    await cargarPreviewExcel()
  } finally {
    await nextTick()
    bloqueandoAuto = false
  }
}

// ─── Prelación: configuración preestablecida ───
// Categorías incluidas: TITULARES, EXAMEN SUFICIENCIA y ACÉFALAS.
function resolverCategoriasPrelacion() {
  return categorias.value.filter((cat) => {
    const n = norm(cat)
    return (
      n.includes('TITULARES') ||
      n.includes('EXAMEN SUFICIENCIA') ||
      n.includes('ACEFAL')
    )
  })
}

async function abrirPrelacion() {
  bloqueandoAuto = true
  clearTimeout(timerAuto)
  clearTimeout(timerActivos)
  try {
    modoPrelacion.value = true
    dropdownAbierto.value = null
    resultadoCargaHoraria.value = null

    reporteExcel.soloActivos.value = false
    reporteExcel.mostrarReferencias.value = true
    reporteExcel.mostrarCompartidas.value = false

    excelParams.value = {
      gestion_desde: '1970',
      gestion_hasta: '',
      periodo: '',
      version: excelParams.value.version || '5ta Versión',
      categorias: resolverCategoriasPrelacion(),
      tiposTitulo: [],
    }

    mostrarPreviewExcel.value = true

    // 1. Preview base
    await cargarPreviewExcel()
    if (reporteExcel.error.value) return

    // 2. Solo Activos (completa docentes sin documento, materias faltantes y CH)
    await reporteExcel.alternarSoloActivos({
      anio: reporteExcel.anioActivos.value,
      periodo: reporteExcel.periodoActivos.value,
    })

    // 3. Asignar CH (antes de combinar, para que la CH quede en la primera fila)
    resultadoCargaHoraria.value = await reporteExcel.asignarCargaHoraria({
      anio: reporteExcel.anioActivos.value,
      periodo: reporteExcel.periodoActivos.value,
    })

    // 4. Combinar materias
    if (!reporteExcel.materiasCombinadas.value) {
      reporteExcel.alternarCombinarMaterias()
    }
  } catch (e) {
    console.error('Error preparando el reporte de prelación:', e)
    resultadoCargaHoraria.value = {
      error: reporteExcel.errorCargaHoraria.value || 'Error preparando el reporte de prelación',
    }
  } finally {
    await nextTick()
    bloqueandoAuto = false
  }
}

async function cargarPreviewExcel() {
  resultadoCargaHoraria.value = null
  try {
    await reporteExcel.previsualizar({
      gestion_desde: excelParams.value.gestion_desde,
      gestion_hasta: excelParams.value.gestion_hasta,
      periodo: excelParams.value.periodo,
      version: excelParams.value.version,
      categoria: excelParams.value.categorias,
      tipo_titulo: excelParams.value.tiposTitulo,
    })
  } catch (e) {
    console.error('Error cargando vista previa de Excel:', e)
  }
}

function cerrarPreviewExcel() {
  clearTimeout(timerAuto)
  clearTimeout(timerActivos)
  recargando.value = false
  dropdownAbierto.value = null
  mostrarPreviewExcel.value = false
}

// ─── Interruptores ───
async function alternarMostrarReferencias() {
  resultadoCargaHoraria.value = null
  await conservandoVista(() =>
    reporteExcel.alternarMostrarReferencias({
      gestion_desde: excelParams.value.gestion_desde,
      gestion_hasta: excelParams.value.gestion_hasta,
      periodo: excelParams.value.periodo,
      version: excelParams.value.version,
      categoria: excelParams.value.categorias,
      tipo_titulo: excelParams.value.tiposTitulo,
    })
  )
}

async function alternarMostrarCompartidas() {
  resultadoCargaHoraria.value = null
  await conservandoVista(async () => reporteExcel.alternarMostrarCompartidas())
}

async function alternarSoloActivos() {
  resultadoCargaHoraria.value = null
  await conservandoVista(() =>
    reporteExcel.alternarSoloActivos({
      anio: reporteExcel.anioActivos.value,
      periodo: reporteExcel.periodoActivos.value,
    })
  )
}

async function recargarActivos() {
  resultadoCargaHoraria.value = null
  await conservandoVista(() => reporteExcel.actualizarActivos())
}

// ─── Reasignar Carga Horaria (experimental) ───
const mostrarModalCargaHoraria = ref(false)

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
  // Si hay cambios hechos en el navegador (CH, materias combinadas, Solo Activos,
  // Compartido u ocultas), se descarga con esos datos exactos (vía POST).
  const usarDatosPersonalizados =
    reporteExcel.cargaHorariaAsignada.value ||
    reporteExcel.materiasCombinadas.value ||
    reporteExcel.soloActivos.value ||
    reporteExcel.mostrarCompartidas.value ||
    reporteExcel.filasOcultasCompartidas.value > 0

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

  await reporteExcel.descargarExcel({
    gestion_desde: excelParams.value.gestion_desde,
    gestion_hasta: excelParams.value.gestion_hasta,
    periodo: excelParams.value.periodo,
    version: excelParams.value.version,
    categoria: excelParams.value.categorias,
    tipo_titulo: excelParams.value.tiposTitulo,
    mostrar_referencias: reporteExcel.mostrarReferencias.value,
  })
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