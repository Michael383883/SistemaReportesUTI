<template>
  <div class="bg-gray-50 pb-4">

    <!-- Header compacto -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Documentos de Docentes</h1>
        <p class="text-sm text-gray-500">Listado de documentos registrados</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
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
          placeholder="Buscar docente, documento o archivo (varios separados por coma)..."
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

    <!-- Panel de filtros desplegable: una sola fila compacta de chips -->
    <div
      v-if="mostrarFiltros"
      class="bg-white rounded-xl border border-gray-200 px-3 py-2.5 mb-4 flex flex-wrap items-center gap-2"
    >
      <!-- Overlay invisible para cerrar los dropdowns al hacer clic fuera -->
      <div
        v-if="filtroCatOpen || filtroTituloOpen || filtroObsOpen"
        class="fixed inset-0 z-20"
        @click="cerrarDropdownsFiltro"
      ></div>

      <!-- Categoría de documento -->
      <div class="relative" :class="{ 'z-30': filtroCatOpen }">
        <button
          type="button"
          title="Categoría de documento"
          :aria-expanded="filtroCatOpen"
          @click="filtroCatOpen = !filtroCatOpen; filtroTituloOpen = false; filtroObsOpen = false"
          :class="[pillBase, filtros.categoria.length ? pillOn : pillOff]"
        >
          Categoría
          <span v-if="filtros.categoria.length" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[11px] font-semibold">{{ filtros.categoria.length }}</span>
          <svg class="w-3.5 h-3.5 opacity-60 transition-transform" :class="{ 'rotate-180': filtroCatOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div v-if="filtroCatOpen" class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
          <div class="px-3 pt-2 pb-1 text-[11px] text-gray-400">Categoría de documento</div>
          <div class="max-h-60 overflow-y-auto">
            <label
              v-for="cat in categorias"
              :key="cat"
              class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
            >
              <input type="checkbox" :value="cat" v-model="filtros.categoria" @change="cargar" class="accent-blue-600" />
              {{ cat }}
            </label>
            <div v-if="!categorias.length" class="px-3 py-2 text-xs text-gray-400 italic">Sin categorías</div>
          </div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button type="button" class="text-xs text-gray-500 hover:text-red-500" @mousedown.prevent="filtros.categoria = []; cargar()">Limpiar</button>
            <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700" @mousedown.prevent="filtroCatOpen = false">Listo</button>
          </div>
        </div>
      </div>

      <!-- Categoría de título -->
      <div class="relative" :class="{ 'z-30': filtroTituloOpen }">
        <button
          type="button"
          title="Categoría de título"
          :aria-expanded="filtroTituloOpen"
          @click="filtroTituloOpen = !filtroTituloOpen; filtroCatOpen = false; filtroObsOpen = false"
          :class="[pillBase, filtros.tipo_titulo.length ? pillOn : pillOff]"
        >
          Título
          <span v-if="filtros.tipo_titulo.length" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[11px] font-semibold">{{ filtros.tipo_titulo.length }}</span>
          <svg class="w-3.5 h-3.5 opacity-60 transition-transform" :class="{ 'rotate-180': filtroTituloOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div v-if="filtroTituloOpen" class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
          <div class="px-3 pt-2 pb-1 text-[11px] text-gray-400">Categoría de título</div>
          <div class="max-h-60 overflow-y-auto">
            <label
              v-for="tipo in tiposTitulo"
              :key="tipo"
              class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
            >
              <input type="checkbox" :value="tipo" v-model="filtros.tipo_titulo" @change="cargar" class="accent-blue-600" />
              {{ tipo }}
            </label>
            <div v-if="!tiposTitulo.length" class="px-3 py-2 text-xs text-gray-400 italic">Sin tipos de título</div>
          </div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button type="button" class="text-xs text-gray-500 hover:text-red-500" @mousedown.prevent="filtros.tipo_titulo = []; cargar()">Limpiar</button>
            <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700" @mousedown.prevent="filtroTituloOpen = false">Listo</button>
          </div>
        </div>
      </div>

      <!-- Observación (las opciones salen de las observaciones existentes en los documentos) -->
      <div class="relative" :class="{ 'z-30': filtroObsOpen }">
        <button
          type="button"
          title="Observación"
          :aria-expanded="filtroObsOpen"
          @click="filtroObsOpen = !filtroObsOpen; filtroCatOpen = false; filtroTituloOpen = false"
          :class="[pillBase, filtros.observacion.length ? pillOn : pillOff]"
        >
          Observación
          <span v-if="filtros.observacion.length" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[11px] font-semibold">{{ filtros.observacion.length }}</span>
          <svg class="w-3.5 h-3.5 opacity-60 transition-transform" :class="{ 'rotate-180': filtroObsOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div v-if="filtroObsOpen" class="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
          <div class="max-h-60 overflow-y-auto">
            <label
              v-for="obs in observacionesDisponibles"
              :key="obs"
              class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
            >
              <input type="checkbox" :value="obs" v-model="filtros.observacion" class="accent-blue-600" />
              {{ obs }}
            </label>
            <div v-if="!observacionesDisponibles.length" class="px-3 py-2 text-xs text-gray-400 italic">Sin observaciones</div>
          </div>
          <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
            <button type="button" class="text-xs text-gray-500 hover:text-red-500" @mousedown.prevent="filtros.observacion = []">Limpiar</button>
            <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700" @mousedown.prevent="filtroObsOpen = false">Listo</button>
          </div>
        </div>
      </div>

      <span class="hidden sm:block w-px h-5 bg-gray-200"></span>

      <!-- Nivel: compacto (clic otra vez para quitar) -->
      <div class="inline-flex items-center h-8 pl-3 pr-0.5 gap-1 bg-gray-100 rounded-full" role="group" aria-label="Nivel">
        <span class="text-[13px] text-gray-500 mr-0.5">Nivel</span>
        <button
          v-for="n in niveles"
          :key="n.valor"
          type="button"
          :title="n.valor"
          :aria-pressed="filtros.nivel === n.valor"
          @click="seleccionarNivel(n.valor)"
          class="h-7 w-7 text-[13px] font-medium rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
          :class="filtros.nivel === n.valor ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ n.corto }}
        </button>
      </div>

      <span class="hidden sm:block w-px h-5 bg-gray-200"></span>

      <!-- Gestión -->
      <label class="inline-flex items-center gap-1.5 h-8 px-3 bg-white border border-gray-200 rounded-full hover:border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-colors">
        <span class="text-[13px] text-gray-500">Gestión</span>
        <input v-model="filtros.gestion" @change="cargar" type="text" placeholder="2025 o 2020-2023" title="Una gestión (2025) o un rango (2020-2023)" class="w-28 text-[13px] text-gray-800 placeholder:text-gray-300 bg-transparent focus:outline-none" />
      </label>

      <!-- Periodo -->
      <label class="inline-flex items-center gap-1.5 h-8 px-3 bg-white border border-gray-200 rounded-full hover:border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-colors">
        <span class="text-[13px] text-gray-500">Periodo</span>
        <input v-model="filtros.periodo" @change="cargar" type="text" placeholder="1 o 1-2" title="Un periodo (1) o un rango (1-2)" class="w-16 text-[13px] text-gray-800 placeholder:text-gray-300 bg-transparent focus:outline-none" />
      </label>

      <span class="hidden sm:block w-px h-5 bg-gray-200"></span>

      <!-- Interruptor: mostrar / ocultar materias en la tabla -->
      <button
        type="button"
        role="switch"
        :aria-checked="mostrarMaterias"
        title="Mostrar las materias de cada documento en la tabla"
        @click="mostrarMaterias = !mostrarMaterias"
        :class="[pillBase, mostrarMaterias ? pillOn : pillOff]"
      >
        Materias
        <span
          class="relative inline-block w-7 h-4 rounded-full transition-colors"
          :class="mostrarMaterias ? 'bg-blue-600' : 'bg-gray-300'"
        >
          <span
            class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
            :class="mostrarMaterias ? 'translate-x-3' : 'translate-x-0'"
          ></span>
        </span>
      </button>

      <!-- Acciones a la derecha -->
      <div class="ml-auto flex items-center gap-2">
        <button
          v-if="filtrosActivosCount || filtroNombre"
          type="button"
          @click="limpiarFiltros"
          class="inline-flex items-center gap-1 h-8 px-2 text-[13px] text-gray-500 hover:text-red-600 rounded-lg transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Limpiar
        </button>
        <ExportarListado :listado="listadoFiltrado" :filtros-texto="filtrosTexto" />
      </div>
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
              <td class="px-4 py-3 max-w-[260px]">
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

                  <!-- Materias: solo se ven si el interruptor "Materias" está activo -->
                  <div
                    v-if="mostrarMaterias && c.MATERIAS?.length"
                    class="mt-1.5"
                  >
                    <div class="text-[11px] font-medium text-gray-400 mb-1">
                      {{ c.MATERIAS.length }} {{ c.MATERIAS.length === 1 ? 'materia' : 'materias' }}
                    </div>
                    <ul class="space-y-1 border-l-2 border-blue-100 pl-2">
                      <li
                        v-for="(m, i) in c.MATERIAS"
                        :key="i"
                        class="text-[11px] leading-tight text-slate-600 whitespace-normal break-words"
                      >
                        {{ m.NOMBRE_MATERIA }}
                        <span v-if="m.GRUPO" class="text-gray-400"> · Gr. {{ m.GRUPO }}</span>
                        <span class="ml-1 px-1 py-px rounded bg-gray-100 text-gray-500">{{ textoNota(m) }}</span>
                        <div v-if="m.DETALLE" class="text-gray-400">{{ m.DETALLE }}</div>
                      </li>
                    </ul>
                  </div>
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
          {{ (terminosBusqueda.length || hayFiltroCategoria) ? 'documentos' : 'docentes' }}
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useClasificacion } from '../composables/useClasificacion'
import { useCategorias } from '../composables/useCategorias'
import { useTiposTitulo } from '../composables/useTiposTitulo'
import ExportarListado from '../components/ExportarListado.vue'

const router = useRouter()
const route = useRoute()
const clasificacion = useClasificacion()
const { categorias, cargarCategorias } = useCategorias()
const { tipos: tiposTitulo, cargarTipos } = useTiposTitulo()

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

// ─── Normalizar: quita tildes, mayúsculas y espacios sobrantes ───
const norm = (s) => String(s || '')
  .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  .toUpperCase().trim()

// Estilos de los chips del panel de filtros (misma altura y forma)
const pillBase =
  'inline-flex items-center gap-1.5 h-8 px-3 text-[13px] font-medium rounded-full border transition-colors ' +
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500'
const pillOff = 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
const pillOn = 'bg-blue-50 border-blue-200 text-blue-700'

// Nivel: opciones del control compacto
const niveles = [
  { valor: 'Primer nivel',  corto: '1' },
  { valor: 'Segundo nivel', corto: '2' },
  { valor: 'Tercer nivel',  corto: '3' },
]
function seleccionarNivel(valor) {
  filtros.value.nivel = filtros.value.nivel === valor ? '' : valor
  cargar()
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

// ─── Interruptor: mostrar materias en la tabla (apagado por defecto) ───
const mostrarMaterias = ref(false)

function textoNota(m) {
  return m.NOTA !== null && m.NOTA !== undefined && m.NOTA !== ''
    ? `Nota: ${m.NOTA}`
    : 'Sin calificación'
}

// Términos de búsqueda: varios separados por coma, punto y coma o salto de línea.
// Se muestran los documentos que coincidan con CUALQUIERA de los términos.
const terminosBusqueda = computed(() =>
  filtroNombre.value.split(/[,;\n]+/).map(norm).filter(Boolean)
)

// Gestión y Periodo: un valor ("2025", "1") se filtra en el servidor; con guion
// ("2020-2023", "1-2") es un rango y se filtra en el cliente, mostrando los
// valores entre ambos números (inclusive).
function parseRango(valor) {
  const m = String(valor || '').match(/^\s*(\d+)\s*-\s*(\d+)\s*$/)
  if (!m) return null
  const a = parseInt(m[1], 10)
  const b = parseInt(m[2], 10)
  return { min: Math.min(a, b), max: Math.max(a, b) }
}
function enRango(valor, rango) {
  const n = parseInt(String(valor ?? '').match(/\d+/)?.[0], 10)
  return !Number.isNaN(n) && n >= rango.min && n <= rango.max
}
const rangoPeriodo = computed(() => parseRango(filtros.value.periodo))
const rangoGestion = computed(() => parseRango(filtros.value.gestion))

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
      // Si es un rango (2020-2023) no se envía: se filtra en el cliente
      gestion: rangoGestion.value ? undefined : (filtros.value.gestion || undefined),
      // Si es un rango (1-2) no se envía: se filtra en el cliente
      periodo: rangoPeriodo.value ? undefined : (filtros.value.periodo || undefined),
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
  () => filtros.value.categoria.length > 0 || filtros.value.tipo_titulo.length > 0 || filtros.value.observacion.length > 0 || rangoPeriodo.value !== null || rangoGestion.value !== null
)

const listadoFiltrado = computed(() => {
  const terminos = terminosBusqueda.value

  if (!terminos.length && !hayFiltroCategoria.value) return listadoPorDocente.value

  const coincide = (c) => {
    if (filtros.value.categoria.length) {
      const cats = filtros.value.categoria.map(norm)
      if (!cats.includes(norm(c.CATEGORIA))) return false
    }

    if (filtros.value.observacion.length) {
      const obs = filtros.value.observacion.map(norm)
      if (!obs.includes(norm(c.OBSERVACION))) return false
    }

    if (rangoGestion.value && !enRango(c.GESTION, rangoGestion.value)) return false
    if (rangoPeriodo.value && !enRango(c.PERIODO, rangoPeriodo.value)) return false

    if (!terminos.length) return true

    // Busca en docente, tipo de documento, detalle y nombre del archivo
    const texto = norm([
      c.NOMBRE_DOCENTE,
      c.TIPO_DOCUMENTO,
      c.DETALLE_GENERAL,
      c.NOMBRE_ARCHIVO,
    ].join(' | '))

    return terminos.some(t => texto.includes(t))
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