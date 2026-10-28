<template>
  <div class="bg-gray-50 pb-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full h-[calc(100vh-6rem)] min-h-[620px] flex flex-col">

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
            :disabled="estaActualizando"
            class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors disabled:opacity-50"
            title="Aplica la configuración de Prelación"
          >
            Usar configuración de Prelación
          </button>
          <template v-else>
            <button
              @click="abrirPrelacion"
              :disabled="estaActualizando"
              class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
              title="Vuelve a la configuración original de Prelación"
            >
              Restablecer Prelación
            </button>
            <button
              @click="aplicarConfiguracionNormal"
              :disabled="estaActualizando"
              class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
              title="Quita la configuración de Prelación"
            >
              Configuración normal
            </button>
          </template>
          <button
            @click="panelOpciones = !panelOpciones"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            {{ panelOpciones ? 'Ocultar opciones' : 'Mostrar opciones' }}
            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': !panelOpciones }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
            </svg>
          </button>
          <button
            @click="volver"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            title="Volver al listado"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
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

        <div v-if="(reporteExcel.loading.value || iniciando) && !reporteExcel.previewMostrado.value.length" class="text-center py-16 text-sm text-gray-400">
          <svg class="w-6 h-6 mx-auto animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ iniciando ? 'Preparando el reporte de prelación...' : 'Cargando vista previa...' }}
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
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider" title="Total de carga horaria del docente (y su equivalente mensual: x 4)">T. CH</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Nivel</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Fotocopia</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Obs 2</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Obs 3</th>
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">PDF</th>
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

                <!-- T. CH: total del docente y, debajo, el equivalente mensual (x 4) -->
                <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                  class="px-2 py-2 text-center align-middle border-x border-gray-100 tabular-nums">
                  <div class="font-semibold text-gray-800">{{ item.TOTAL_CH || '—' }}</div>
                  <div v-if="item.TOTAL_CH_MES" class="mt-0.5 text-[11px] font-medium text-indigo-600 whitespace-nowrap">
                    Mes {{ item.TOTAL_CH_MES }}
                  </div>
                </td>

                <td class="px-2 py-2 text-gray-600">{{ item.NIVEL || '—' }}</td>
                <td class="px-2 py-2 text-gray-600">{{ item.FOTOCOPIA_TITULAR || '—' }}</td>
                <td class="px-2 py-2 text-gray-600">{{ item.OBS2 || '—' }}</td>
                <td class="px-2 py-2 text-gray-600">{{ item.OBS3 || '—' }}</td>

                <!-- PDF del documento -->
                <td class="px-2 py-2 text-center">
                  <button
                    v-if="item.ID_DOCUMENTO && item.NOMBRE_ARCHIVO"
                    @click="verPdf(item.ID_DOCUMENTO)"
                    :disabled="abriendoPdfId === item.ID_DOCUMENTO"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-blue-800 hover:bg-blue-900 text-white text-[11px] font-semibold rounded-md shadow-sm transition-colors disabled:opacity-60"
                    title="Ver el PDF del documento"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                    </svg>
                    {{ abriendoPdfId === item.ID_DOCUMENTO ? '...' : 'PDF' }}
                  </button>
                  <span v-else class="text-gray-300">—</span>
                </td>
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
            @click="volver"
            class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Volver
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
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useReporteExcel } from '../composables/useReporteExcel'
import { useClasificacion } from '../composables/useClasificacion'
import { useCategorias } from '../composables/useCategorias'
import { useTiposTitulo } from '../composables/useTiposTitulo'

const router = useRouter()
const reporteExcel = useReporteExcel()
const clasificacion = useClasificacion()
const { categorias, cargarCategorias } = useCategorias()
const { tipos: tiposTitulo, cargarTipos } = useTiposTitulo()

// ─── Normalizar: quita tildes, mayúsculas y espacios sobrantes ───
const norm = (s) => String(s || '')
  .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  .toUpperCase().trim()

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

// ═══════════════════════════════════════════════════════════════
// ESTADO DE LA VISTA
// ═══════════════════════════════════════════════════════════════
const modoPrelacion = ref(false)
const panelOpciones = ref(true)
const dropdownAbierto = ref(null) // 'cat' | 'titulo' | null
const iniciando = ref(true)       // true mientras se aplica Prelación al abrir la vista
const recargando = ref(false)

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
let bloqueandoAuto = true   // true mientras se aplica un preset (Prelación, etc.)
let timerAuto = null
let timerActivos = null

const claveFiltros = computed(() => {
  const p = excelParams.value
  return [
    p.gestion_desde, p.gestion_hasta, p.periodo,
    [...p.categorias].join('|'), [...p.tiposTitulo].join('|'),
  ].join('~')
})

watch(claveFiltros, () => {
  if (bloqueandoAuto) return
  programarRecarga()
})

// Cambiar año/periodo de activos recarga activos + carga horaria
const claveActivos = computed(
  () => `${reporteExcel.anioActivos.value}~${reporteExcel.periodoActivos.value}`
)
watch(claveActivos, () => {
  if (bloqueandoAuto || !reporteExcel.soloActivos.value) return
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

// ─── Configuración normal (sin Prelación) ───
async function aplicarConfiguracionNormal() {
  bloqueandoAuto = true
  clearTimeout(timerAuto)
  clearTimeout(timerActivos)
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
  recargando.value = false
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

function volver() {
  clearTimeout(timerAuto)
  clearTimeout(timerActivos)
  if (window.history.length > 1) router.back()
  else router.push('/')
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

// ─── Descargar ───
// Siempre se usa el endpoint personalizado (POST): envía las filas tal cual se
// ven en pantalla, incluyendo TOTAL_CH y TOTAL_CH_MES ("Mes 96"), para que el
// Excel salga igual que la vista previa.
async function descargarExcelConfirmado() {
  try {
    await reporteExcel.descargarExcelPersonalizado({
      gestion: reporteExcel.gestionEtiqueta.value,
      version: excelParams.value.version,
    })
  } catch (e) {
    alert('No se pudo descargar el Excel')
  }
}

// ─── Al entrar a la vista: aplica Prelación directamente ───
onMounted(async () => {
  try {
    await cargarCategorias()
    await cargarTipos()
    await abrirPrelacion()
  } finally {
    iniciando.value = false
  }
})

onBeforeUnmount(() => {
  clearTimeout(timerAuto)
  clearTimeout(timerActivos)
})
</script>