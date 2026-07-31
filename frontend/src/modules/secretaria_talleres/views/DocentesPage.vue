<template>
  <div class="min-h-screen">
    <!-- Header -->
     <div class="border-b border-slate-200">
     <div class="w-full px-2 sm:px-2 py-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <div>
          <h1 class="text-xl font-bold text-slate-1000 tracking-tight">Gestión de Docentes</h1>
          <p class="text-xs text-slate-500 flex items-center gap-2">
            <template v-if="filtros.anio && filtros.periodo">
              Facultad de Ciencias Económicas · {{ PERIODOS[filtros.periodo] || filtros.periodo }}/{{ filtros.anio }}
              <span
                v-if="!gestionEsAutomatica"
                class="text-[10px] font-semibold text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5"
              >
                manual
              </span>
            </template>
            <template v-else>
              Facultad de Ciencias Económicas · cargando gestión...
            </template>
          </p>
        </div>
        <div class="flex items-center gap-2">
          
          <span class="bg-teal-50 text-teal-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-teal-200">
            {{ docentesFiltrados.length }} docentes
          </span>

          <!-- ===== Botón EXPORTAR (Ver / Imprimir / Descargar) ===== -->
          <div class="relative" ref="exportarDropdownRef">
            <div
              class="inline-flex rounded-lg overflow-hidden shadow-sm"
              :class="!docentesFiltrados.length ? 'opacity-40 pointer-events-none' : ''"
            >
              <!-- Botón principal -->
              <button
                @click.stop="mostrarMenuExportar = !mostrarMenuExportar"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 text-white text-sm hover:bg-green-600 transition-colors duration-300"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 2H8c-1.1 0-2 .9-2 2v4H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9.5 17l1.8-3-1.7-3h1.8l.8 1.7.8-1.7h1.8l-1.7 3 1.8 3h-1.8l-.9-1.8-.9 1.8H9.5z"/>
                </svg>
                <span>Exportar</span>
              </button>

              <!-- Flecha -->
              <button
                @click.stop="mostrarMenuExportar = !mostrarMenuExportar"
                class="px-2 py-1.5 bg-slate-600 hover:bg-green-600 text-white border-l border-slate-500/50 transition-colors duration-300"
                aria-label="Más opciones"
              >
                <svg
                  width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                  :style="mostrarMenuExportar ? 'transform: rotate(180deg);' : ''"
                  style="transition: transform 0.15s"
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
            </div>

            <!-- Backdrop para cerrar al hacer click fuera -->
            <div v-if="mostrarMenuExportar" class="fixed inset-0 z-40" @click="mostrarMenuExportar = false" />

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
                v-if="mostrarMenuExportar"
                class="absolute right-0 top-full mt-1.5 z-50 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden w-60 p-3"
              >
                <p class="text-xs font-semibold text-slate-500 mb-2 px-0.5">Lista completa</p>
                <div class="flex items-center gap-2">
                  <!-- Ver -->
                  <button
                    @click="exportarExcel('ver'); mostrarMenuExportar = false"
                    title="Ver lista"
                    class="flex-1 flex items-center justify-center py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>

                  <!-- Imprimir -->
                  <button
                    @click="exportarImprimir(); mostrarMenuExportar = false"
                    title="Imprimir"
                    class="flex-1 flex items-center justify-center py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
                    </svg>
                  </button>

                  <!-- Descargar -->
                  <button
                    @click="exportarExcel('descargar'); mostrarMenuExportar = false"
                    title="Descargar Excel"
                    class="flex-1 flex items-center justify-center py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
          <!-- ===== FIN Botón EXPORTAR ===== -->

        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-2.5 bg-white border-b border-slate-100">
      <div class="flex flex-wrap items-center gap-2">

        <!-- Selector de gestión (año/periodo) editable -->
        <div class="flex items-center gap-1.5 shrink-0">
          <select
            v-model="filtros.periodo"
            class="h-[38px] px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
            title="Periodo académico"
          >
            <option v-for="(nombre, cod) in PERIODOS" :key="cod" :value="cod">{{ nombre }}</option>
          </select>

          <select
            v-model="filtros.anio"
            class="h-[38px] px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
            title="Año"
          >
            <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
          </select>

          <button
            v-if="!gestionEsAutomatica"
            @click="volverAGestionActual"
            title="Volver a la gestión actual detectada por el sistema"
            class="h-[38px] px-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition shrink-0"
          >
            Hoy
          </button>
        </div>

        <!-- Búsqueda -->
        <div class="relative flex-1 min-w-64">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, código o CI..."
            class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
          />
        </div>

        <!-- Filtro Grado -->
        <select
          v-model="filtroGrado"
          class="px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
        >
          <option value="">Todos los grados</option>
          <option value="PhD">Doctorado (PhD)</option>
          <option value="Magister">Magíster</option>
          <option value="Licenciado">Licenciado</option>
          <option value="Ingeniero">Ingeniero</option>
        </select>

        <!-- Toggle vista -->
        <div class="flex items-center bg-slate-100 rounded-lg p-1 ml-auto">
          <button
            @click="vista = 'tabla'"
            :class="['px-2.5 py-1 rounded-md text-sm font-medium transition-all', vista === 'tabla' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
            </svg>
          </button>
          <button
            @click="vista = 'cards'"
            :class="['px-2.5 py-1 rounded-md text-sm font-medium transition-all', vista === 'cards' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-4">

      <!-- Estado de carga -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-20">
        <div class="w-8 h-8 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mb-3"></div>
        <p class="text-slate-500 text-sm">Cargando docentes...</p>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="docentesFiltrados.length === 0" class="flex flex-col items-center justify-center py-20">
        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-3">
          <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-slate-600 font-medium text-sm">No se encontraron docentes</p>
        <p class="text-slate-400 text-xs mt-1">Intenta con otros filtros de búsqueda</p>
        <button @click="limpiarFiltros" class="mt-3 text-teal-600 text-sm font-medium hover:underline">Limpiar filtros</button>
      </div>

      <!-- Vista Tabla -->
      <div v-else-if="vista === 'tabla'" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-slate-800 border-b border-slate-200">
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Nro</th>
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Código</th>
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Docente</th>
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">C.I.</th>
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Grado</th>
                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Contacto</th>
                <th class="text-center px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Horario</th>
                <th class="text-center px-3 py-2 text-xs font-semibold text-slate-100 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(docente, idx) in docentesPaginados"
                :key="docente.cod_docente"
                class="hover:bg-slate-50 transition-colors cursor-pointer"
                @click="abrirDetalle(docente)"
              >
                <!-- Nro -->
                <td class="px-3 py-2 text-slate-800 text-xs">{{ (paginaActual - 1) * porPagina + idx + 1 }}</td>

                <!-- Codigo -->
                <td class="px-3 py-2 text-slate-800 font-mono text-2x1">{{ docente.docente }}</td>

                <!-- Nombre -->
              <!-- Nombre -->
<td class="px-3 py-2">
  <p class="font-medium text-slate-800 leading-tight text-sm uppercase">{{ formatNombre(docente.nombre_docente) }}</p>
</td>
                <!-- CI -->
                <td class="px-3 py-2 text-slate-800 font-mono text-2x1">{{ docente.ci || '—' }}</td>

                <!-- Grado -->
                <td class="px-3 py-2">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', badgeGrado(docente.grado_academico)]">
                    {{ docente.grado_academico || 'Sin especificar' }}
                  </span>
                </td>

                <!-- Contacto -->
                <td class="px-3 py-2">
                  <div class="flex flex-col gap-0.5">
                    <span v-if="docente.email || docente.email_institucional" class="text-slate-700 text-xs flex items-center gap-1">
                      <svg class="w-3 h-3 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      <span class="truncate max-w-[140px]">{{ docente.email || docente.email_institucional }}</span>
                    </span>
                    <span v-if="docente.celular_1" class="text-slate-700 text-xs flex items-center gap-1">
                      <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                      {{ docente.celular_1 }}
                    </span>
                    <span v-if="docente.fijo_1" class="text-slate-700 text-xs flex items-center gap-1">
                      <svg class="w-3 h-3 text-teal-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ docente.fijo_1 }}
                    </span>
                    <span v-if="!docente.email && !docente.email_institucional && !docente.celular_1 && !docente.fijo_1" class="text-slate-300 text-xs">
                      Sin contacto
                    </span>
                  </div>
                </td>

                <!-- Horario -->
                <td class="px-3 py-2 text-center">
                  <button
                    v-if="docente.horario_cargado"
                    @click.stop="verHorarioRapido(docente)"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-lg transition-colors inline-flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{  }} materias
                  </button>
                  <span v-else class="text-xs text-slate-400">Sin horario</span>
                </td>

                <!-- Acciones -->
                <td class="px-3 py-2" @click.stop>
                  <div class="flex items-center justify-center">
                    <button
                      @click="abrirDetalle(docente)"
                      class="p-1.5 text-slate-800 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                      title="Ver detalle"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="px-3 py-2.5 border-t border-slate-100 flex items-center justify-between">
          <p class="text-xs text-slate-500">
            Mostrando {{ (paginaActual - 1) * porPagina + 1 }}–{{ Math.min(paginaActual * porPagina, docentesFiltrados.length) }} de {{ docentesFiltrados.length }}
          </p>
          <div class="flex items-center gap-1">
            <button
              @click="paginaActual--"
              :disabled="paginaActual === 1"
              class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            >Anterior</button>
            <template v-for="p in totalPaginas" :key="p">
              <button
                v-if="Math.abs(p - paginaActual) <= 2 || p === 1 || p === totalPaginas"
                @click="paginaActual = p"
                :class="['w-7 h-7 text-xs rounded-lg transition-colors', p === paginaActual ? 'bg-teal-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50']"
              >{{ p }}</button>
              <span v-else-if="p === paginaActual - 3 || p === paginaActual + 3" class="px-1 text-slate-400">…</span>
            </template>
            <button
              @click="paginaActual++"
              :disabled="paginaActual === totalPaginas"
              class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            >Siguiente</button>
          </div>
        </div>
      </div>

      <!-- Vista Cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <div
          v-for="docente in docentesPaginados"
          :key="docente.docente"
          @click="abrirDetalle(docente)"
          class="bg-white rounded-xl border border-slate-200 p-3 hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div class="flex items-start gap-2.5 mb-2.5">
            <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-100 flex items-center justify-center flex-shrink-0">
              <i class="ti ti-user text-teal-600" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-slate-800 text-sm leading-tight truncate uppercase group-hover:text-teal-700 transition-colors">{{ formatNombre(docente.nombre_docente) }}</p>
              <p class="text-xs text-slate-400 font-mono">{{ docente.docente }}</p>
            </div>
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
              </svg>
              <span class="font-mono">{{ docente.ci || '—' }}</span>
            </div>
            <div v-if="docente.email || docente.email_institucional" class="flex items-center gap-1.5 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <span class="truncate">{{ docente.email || docente.email_institucional }}</span>
            </div>
            <div v-if="docente.horario_cargado" class="flex items-center gap-1.5 text-xs text-blue-600">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>{{ docente.total_materias || 0 }} materias</span>
            </div>
          </div>

          <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between">
            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', badgeGrado(docente.grado_academico)]">
              {{ docente.grado_academico || 'Sin grado' }}
            </span>
            <span v-if="docente.horas_total" class="text-xs text-slate-500">{{ docente.horas_total }}h/sem</span>
            <span v-else class="text-xs text-amber-500">Sin horario</span>
          </div>
        </div>

        <div class="col-span-full flex items-center justify-center gap-2 mt-1" v-if="totalPaginas > 1">
          <button @click="paginaActual--" :disabled="paginaActual === 1" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-50">Anterior</button>
          <span class="text-sm text-slate-500">Página {{ paginaActual }} de {{ totalPaginas }}</span>
          <button @click="paginaActual++" :disabled="paginaActual === totalPaginas" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-50">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- Modal rápido de horario -->
    <HorarioRapidoModal
      v-if="docenteHorarioSeleccionado"
      :docente="docenteHorarioSeleccionado"
      @cerrar="cerrarHorarioRapido"
    />

    <!-- Modal Detalle Docente -->
    <DocenteDetalleModal
      v-if="docenteSeleccionado && !docenteHorarioSeleccionado"
      :docente="docenteSeleccionado"
      :modo="modoModal"
      @cerrar="cerrarModal"
      @ver-horario="onVerHorarioDesdeModal"
    />
  </div>
</template>

<script setup>
defineOptions({ name: 'DocentesPage' })
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue'
import DocenteDetalleModal from '@/shared/components/docentes/DocenteDetalleModal.vue'
import HorarioRapidoModal from '@/shared/components/docentes/HorarioRapidoModal.vue'
import { docentesService } from '@/shared/services/docentesService'
import { useDocentesRecientes } from '@/shared/composables/useDocentesRecientes'
import * as XLSX from 'xlsx'

const cargando = ref(false)
const docentes = ref([])
const busqueda = ref('')
const filtroGrado = ref('')
const vista = ref('tabla')
const paginaActual = ref(1)
const porPagina = ref(15)
const docenteSeleccionado = ref(null)
const docenteHorarioSeleccionado = ref(null)
const modoModal = ref('detalle')
const origenHorario = ref(null) // 'tabla' o 'detalle'

// Menú desplegable del botón "Exportar"
const mostrarMenuExportar = ref(false)
const exportarDropdownRef = ref(null)

// anio/periodo arrancan en null: el backend calcula la gestión actual
// automáticamente (PeriodoAcademicoService) en la primera carga.
// El usuario puede después cambiarlos con los selects — en ese caso
// se le pasan al backend como override.
const filtros = reactive({
  anio: null,
  periodo: null,
})

// true mientras filtros.anio/periodo son los que detectó el sistema;
// false en cuanto el usuario los cambia manualmente con los selects.
const gestionEsAutomatica = ref(true)

const PERIODOS = {
  '1': 'I',
  '2': 'II',
}

// Rango razonable de años para el selector
const aniosDisponibles = computed(() => {
  const actual = new Date().getFullYear()
  const desde = actual - 5
  const anios = []
  for (let a = actual + 1; a >= desde; a--) anios.push(a)
  return anios
})

const { registrar } = useDocentesRecientes()

function abrirDetalle(docente) {
  registrar(docente)
  docenteSeleccionado.value = docente
  modoModal.value = 'detalle'
}

onMounted(async () => {
  await cargarDocentes()
})

async function cargarDocentes() {
  cargando.value = true
  try {
    const [docentesData, horariosResp] = await Promise.all([
      docentesService.getAll(),
      docentesService.getAllHorarios({
        anio: filtros.anio || null,
        periodo: filtros.periodo || null,
      })
    ])

    const horariosData = horariosResp.data || []

    // El backend informa qué año/periodo usó para armar esta lista
    // (automático por PeriodoAcademicoService, o el override que mandamos).
    if (horariosResp.anio) filtros.anio = horariosResp.anio
    if (horariosResp.periodo) filtros.periodo = String(horariosResp.periodo)
    gestionEsAutomatica.value = horariosResp.automatico ?? true

    const horariosMap = new Map()
    horariosData.forEach(h => {
      horariosMap.set(String(h.docente), h)
    })

    docentes.value = docentesData
      .filter(docente => horariosMap.has(String(docente.docente)))
      .map(docente => {
        const horario = horariosMap.get(String(docente.docente))
        return {
          ...docente,
          horario_cargado: true,
          total_materias: horario.total_horarios || horario.materias?.length || 0,
          horas_total: horario.carga_horaria_total || docente.horas_total || 0,
          materias: horario.materias || [],
          horario_completo: horario
        }
      })
  } catch (e) {
    console.error('Error cargando docentes:', e)
    try {
      docentes.value = await docentesService.getAll()
    } catch (e2) {
      console.error('Error crítico:', e2)
    }
  } finally {
    cargando.value = false
  }
}

function volverAGestionActual() {
  filtros.anio = null
  filtros.periodo = null
  gestionEsAutomatica.value = true
  cargarDocentes()
}

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
    cargarDocentes()
  },
)

// Búsqueda por nombre, código de docente o CI
const docentesFiltrados = computed(() => {
  let lista = docentes.value
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase().trim()
    lista = lista.filter(d =>
      (d.nombre_docente || '').toLowerCase().includes(q) ||
      String(d.docente ?? '').toLowerCase().includes(q) ||
      String(d.ci ?? '').toLowerCase().includes(q)
    )
  }
  if (filtroGrado.value) lista = lista.filter(d => d.grado_academico === filtroGrado.value)
  return lista
})

const totalPaginas = computed(() => Math.max(1, Math.ceil(docentesFiltrados.value.length / porPagina.value)))

const docentesPaginados = computed(() => {
  const start = (paginaActual.value - 1) * porPagina.value
  return docentesFiltrados.value.slice(start, start + porPagina.value)
})

watch([busqueda, filtroGrado], () => { paginaActual.value = 1 })

// Funciones para horarios
async function onVerHorarioDesdeModal(docente) {
  origenHorario.value = 'detalle'
  docenteSeleccionado.value = null
  await nextTick()
  await verHorarioRapido(docente)
}

async function verHorarioRapido(docente) {
  registrar(docente)
  if (origenHorario.value !== 'detalle') {
    origenHorario.value = 'tabla'
  }
  if (docente.horario_completo) {
    docenteHorarioSeleccionado.value = docente
    return
  }
  try {
    const horario = await docentesService.getHorario(docente.docente, {
      anio: filtros.anio,
      periodo: filtros.periodo,
    })
    docenteHorarioSeleccionado.value = { ...docente, horario_completo: horario }
  } catch (e) {
    console.error('Error cargando horario:', e)
  }
}

function cerrarHorarioRapido() {
  const docente = docenteHorarioSeleccionado.value
  docenteHorarioSeleccionado.value = null

  if (origenHorario.value === 'detalle') {
    origenHorario.value = null
    docenteSeleccionado.value = docente
    modoModal.value = 'detalle'
  } else {
    origenHorario.value = null
  }
}

function cerrarModal() {
  docenteSeleccionado.value = null
  docenteHorarioSeleccionado.value = null
}

function limpiarFiltros() {
  busqueda.value = ''
  filtroGrado.value = ''
}

/**
 * Arma la hoja (worksheet) de docentes filtrados. Se usa tanto para
 * la vista previa ('ver'), la impresión ('imprimir') como para la
 * descarga real ('descargar').
 */
function construirHojaDocentes() {
  const datos = docentesFiltrados.value.map(d => ({
    'Código': d.docente || '',
    'Nombre': formatNombre(d.nombre_docente),
    'C.I.': d.ci || '',
    'Grado Académico': d.grado_academico || '',
    'Email': d.email || d.email_institucional || '',
    'Celular': d.celular_1 || '',
    'Teléfono Fijo': d.fijo_1 || '',
    
  }))

  const hoja = XLSX.utils.json_to_sheet(datos)

  hoja['!cols'] = [
    { wch: 10 }, // Código
    { wch: 30 }, // Nombre
    { wch: 12 }, // CI
    { wch: 18 }, // Grado
    { wch: 30 }, // Email
    { wch: 15 }, // Celular
    { wch: 15 }, // Fijo
    { wch: 20 }, // Materias
    { wch: 18 }, // Carga
  ]

  return hoja
}

/**
 * Abre una vista previa HTML de la hoja en una pestaña nueva. Un
 * .xlsx no se puede "mostrar" inline en el navegador (no es un
 * formato renderizable como el PDF), así que para el modo 'ver'
 * (y como base del modo 'imprimir') generamos una tabla HTML
 * equivalente en vez de descargar el archivo.
 *
 * @param {object} hoja - worksheet de XLSX
 * @param {string} gestionLabel - etiqueta de la gestión (ej. "1-2026")
 * @param {{ autoImprimir?: boolean }} [opciones]
 */
function abrirVistaPreviaDocentes(hoja, gestionLabel, { autoImprimir = false } = {}) {
  const ventana = window.open('', '_blank')

  if (!ventana) {
    alert('Tu navegador bloqueó la ventana de vista previa. Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.')
    return
  }

  const tablaHtml = XLSX.utils.sheet_to_html(hoja, { editable: false, header: '', footer: '' })

  ventana.document.write(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="utf-8" />
      <title>Vista previa – Docentes ${gestionLabel}</title>
      <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 24px; color: #1e293b; }
        h1 { font-size: 15px; margin: 0 0 16px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        td, th { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; white-space: nowrap; }
        th { background: #f1f5f9; font-weight: 600; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button {
          background: #2563eb; color: #fff; border: none; border-radius: 8px;
          padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .toolbar button:hover { background: #1d4ed8; }
        @media print {
          .toolbar { display: none; }
        }
      </style>
    </head>
    <body>
      <h1>Docentes – Gestión ${gestionLabel}</h1>
      <div class="toolbar">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
      </div>
      ${tablaHtml}
    </body>
    </html>
  `)
  ventana.document.close()

  if (autoImprimir) {
    // Espera a que la ventana termine de pintar antes de abrir el diálogo de impresión.
    ventana.onload = () => {
      ventana.focus()
      ventana.print()
    }
    // Fallback por si onload ya se disparó (algunos navegadores con document.write).
    setTimeout(() => {
      ventana.focus()
      ventana.print()
    }, 300)
  }
}

/**
 * @param {'ver'|'descargar'} modo
 */
function exportarExcel(modo = 'descargar') {
  const hoja = construirHojaDocentes()
  const gestion = filtros.periodo && filtros.anio
    ? `${filtros.periodo}-${filtros.anio}`
    : new Date().toISOString().slice(0, 10)

  if (modo === 'ver') {
    abrirVistaPreviaDocentes(hoja, gestion)
    return
  }

  const libro = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(libro, hoja, 'Docentes')
  XLSX.writeFile(libro, `docentes_${gestion}.xlsx`)
}

/**
 * Abre la vista previa e inmediatamente dispara el diálogo de
 * impresión del navegador.
 */
function exportarImprimir() {
  const hoja = construirHojaDocentes()
  const gestion = filtros.periodo && filtros.anio
    ? `${filtros.periodo}-${filtros.anio}`
    : new Date().toISOString().slice(0, 10)

  abrirVistaPreviaDocentes(hoja, gestion, { autoImprimir: true })
}

// Helpers
function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function badgeGrado(grado) {
  const map = {
    'PhD': 'bg-violet-100 text-violet-700',
    'Doctorado': 'bg-violet-100 text-violet-700',
    'Magister': 'bg-blue-100 text-blue-700',
    'Licenciado': 'bg-teal-100 text-teal-700',
    'Ingeniero': 'bg-orange-100 text-orange-700',
  }
  return map[grado] || 'bg-slate-100 text-slate-500'
}
</script>