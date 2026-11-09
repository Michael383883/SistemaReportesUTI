<template>
  <div class="bg-gray-50 pb-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full h-[calc(100vh-6rem)] min-h-[620px] flex flex-col">

      <!-- ── Encabezado ── -->
      <div class="flex items-center justify-between gap-3 px-6 py-3.5 border-b border-gray-100">
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-base font-semibold text-gray-900">Kardex de materias</h3>
            <span v-if="loading" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium rounded-full bg-blue-50 text-blue-600">
              <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Actualizando…
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-0.5 truncate">
            {{ reporte?.titulo || 'Elige la gestión y la categoría. La vista previa se actualiza sola.' }}
          </p>
        </div>

        <div class="flex items-center gap-2">
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
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
          </button>
        </div>
      </div>

      <!-- ── Panel de opciones ── -->
      <div v-show="panelOpciones" class="relative px-6 py-4 bg-slate-50 border-b border-gray-100">

        <!-- Overlay para cerrar el dropdown -->
        <div v-if="dropdownAbierto" class="fixed inset-0 z-20" @click="dropdownAbierto = false"></div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

          <!-- 1 · Gestión -->
          <section class="bg-white rounded-xl border border-gray-200 p-4">
            <header class="flex items-center justify-between gap-2 mb-3">
              <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">1</span>
                <h4 class="text-sm font-semibold text-gray-800">Gestión</h4>
              </div>
              <button
                v-if="!esPeriodoActual && periodoActualListo"
                @click="usarPeriodoActual"
                class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
                title="Volver al periodo vigente"
              >
                Usar periodo actual ({{ periodoActual }}/{{ anioActual }})
              </button>
              <span v-else-if="periodoActualListo" class="text-[11px] font-medium text-emerald-600">
                Periodo actual
              </span>
            </header>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Año</label>
                <input v-model.number="filtros.anio" type="number" min="2000" max="2100"
                  class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Periodo</label>
                <select v-model="filtros.periodo"
                  class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option v-for="p in periodos" :key="p.valor" :value="p.valor">{{ p.texto }}</option>
                </select>
              </div>
            </div>

            <!-- Tipo de ingreso: dropdown multi-selección -->
            <div class="mt-3 relative" :class="{ 'z-30': dropdownAbierto }">
              <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Tipo de ingreso</label>
              <button
                type="button"
                @click="dropdownAbierto = !dropdownAbierto"
                class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 text-sm border rounded-lg bg-white text-left"
                :class="filtros.categorias.length ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-300'"
              >
                <span class="truncate" :class="filtros.categorias.length ? 'text-gray-800 font-medium' : 'text-gray-400'">
                  {{ filtros.categorias.length ? `${filtros.categorias.length} seleccionada(s)` : 'ACÉFALA (por defecto)' }}
                </span>
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <div
                v-if="dropdownAbierto"
                class="absolute left-0 top-full mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto"
              >
                <label
                  v-for="op in categorias"
                  :key="op"
                  class="flex items-center gap-2 px-3 py-2 text-[13px] text-slate-700 hover:bg-blue-50 cursor-pointer"
                >
                  <input type="checkbox" :value="op" v-model="filtros.categorias" class="accent-blue-600" />
                  {{ op }}
                </label>
                <div v-if="!categorias.length" class="px-3 py-2 text-[12px] text-gray-400 italic">Sin opciones</div>
                <div class="sticky bottom-0 flex items-center justify-between px-3 py-2 border-t border-gray-100 bg-gray-50">
                  <button type="button" class="text-[11px] text-gray-500 hover:text-red-500"
                    @mousedown.prevent="filtros.categorias = []">
                    Quitar todas
                  </button>
                  <button type="button" class="text-[11px] font-medium text-blue-600 hover:text-blue-700"
                    @mousedown.prevent="dropdownAbierto = false">
                    Listo
                  </button>
                </div>
              </div>

              <!-- Etiquetas de lo seleccionado -->
              <div v-if="filtros.categorias.length" class="flex flex-wrap gap-1.5 mt-2">
                <span
                  v-for="c in filtros.categorias"
                  :key="c"
                  class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 text-[11px] font-medium rounded-full bg-blue-50 text-blue-700"
                >
                  {{ c }}
                  <button type="button" @click="quitarCategoria(c)" class="w-4 h-4 rounded-full hover:bg-blue-100 flex items-center justify-center" title="Quitar">
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </span>
              </div>
              <p v-else class="text-[11px] text-gray-400 mt-1">Sin selección = ACÉFALA.</p>
            </div>
          </section>

          <!-- 2 · Carreras -->
          <section class="bg-white rounded-xl border border-gray-200 p-4">
            <header class="flex items-center justify-between gap-2 mb-3">
              <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">2</span>
                <h4 class="text-sm font-semibold text-gray-800">Carreras</h4>
              </div>
              <button
                v-if="filtros.planes.length"
                @click="filtros.planes = []"
                class="text-[11px] text-gray-500 hover:text-red-500"
              >
                Quitar selección
              </button>
            </header>

            <div class="space-y-1.5">
              <button
                v-for="p in planes"
                :key="p.cod_plan"
                type="button"
                @click="alternarPlan(p.cod_plan)"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg border text-left text-sm transition-colors"
                :class="filtros.planes.includes(p.cod_plan)
                  ? 'bg-blue-50/60 border-blue-200 text-gray-900'
                  : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'"
              >
                <span class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0"
                  :class="filtros.planes.includes(p.cod_plan) ? 'bg-blue-600 border-blue-600' : 'border-gray-300'">
                  <svg v-if="filtros.planes.includes(p.cod_plan)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </span>
                <span class="font-semibold w-9">{{ p.abrev }}</span>
                <span class="truncate text-[13px]">{{ p.nombre }}</span>
              </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">
              {{ filtros.planes.length ? `${filtros.planes.length} carrera(s) elegida(s)` : 'Sin selección = todas las carreras' }}
            </p>
          </section>

          <!-- 3 · Cómo mostrarlo -->
          <section class="bg-white rounded-xl border border-gray-200 p-4">
            <header class="flex items-center gap-2 mb-3">
              <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">3</span>
              <h4 class="text-sm font-semibold text-gray-800">¿Cómo mostrarlo?</h4>
            </header>

            <div class="space-y-1.5">
              <button
                v-for="t in interruptores"
                :key="t.id"
                type="button"
                role="switch"
                :aria-checked="t.activo"
                @click="t.accion()"
                class="w-full flex items-start gap-3 px-3 py-2 rounded-lg border text-left transition-colors"
                :class="t.activo
                  ? (t.id === 'sindoc' ? 'bg-amber-50/70 border-amber-300' : 'bg-blue-50/60 border-blue-200')
                  : 'bg-white border-gray-200 hover:bg-gray-50'"
              >
                <span class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors"
                  :class="t.activo ? (t.id === 'sindoc' ? 'bg-amber-500' : 'bg-blue-600') : 'bg-gray-300'">
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
        </div>
      </div>

      <!-- ── Franja de resumen + búsqueda + pestañas ── -->
      <div class="flex flex-wrap items-center gap-2 px-6 py-2.5 border-b border-gray-100 text-xs">
        <template v-if="reporte && !error">
          <span class="px-2.5 py-1 rounded-full bg-slate-900 text-white font-semibold">
            {{ reporte.total_materias }} materias
          </span>
          <span v-if="filtros.soloSinDocumento"
            class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-medium">
            Sin documento subido
          </span>
          <span v-if="filtros.incluirCompartidas && reporte.total_filas !== reporte.total_materias"
            class="px-2.5 py-1 rounded-full bg-pink-50 text-pink-700 font-medium">
            {{ reporte.total_filas - reporte.total_materias }} compartida(s)
          </span>
          <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ reporte.categoria }} · {{ etiquetaPeriodo }}/{{ reporte.anio }}
          </span>
        </template>

        <div class="ml-auto flex items-center gap-2">
          <div class="relative">
            <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input v-model="busqueda" type="text" placeholder="Buscar docente, materia o aula…"
              class="w-60 pl-8 pr-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
      </div>

      <div v-if="reporte?.secciones?.length > 1" class="flex items-center gap-1.5 px-6 py-2 border-b border-gray-100 overflow-x-auto">
        <button
          @click="pestana = 'todas'"
          class="px-3 py-1 text-xs font-medium rounded-full whitespace-nowrap transition-colors"
          :class="pestana === 'todas' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
        >
          Todas
        </button>
        <button
          v-for="s in reporte.secciones"
          :key="s.cod_plan"
          @click="pestana = s.cod_plan"
          class="px-3 py-1 text-xs font-medium rounded-full whitespace-nowrap transition-colors"
          :class="pestana === s.cod_plan ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
        >
          {{ s.abrev }} <span class="opacity-70">({{ s.total_materias }})</span>
        </button>
      </div>

      <!-- ── Vista previa ── -->
      <div class="relative flex-1 overflow-auto px-6 pb-3 pt-2">

        <div v-if="loading && reporte" class="absolute inset-0 z-20 bg-white/60 flex items-start justify-center pt-24">
          <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-lg border border-gray-100 text-sm text-gray-600">
            <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Actualizando vista previa…
          </div>
        </div>

        <div v-if="loading && !reporte" class="text-center py-16 text-sm text-gray-400">
          <svg class="w-6 h-6 mx-auto animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Cargando vista previa…
        </div>

        <div v-else-if="error" class="text-center py-16 text-sm text-red-500">
          {{ error }}
        </div>

        <div v-else-if="!anioValido" class="text-center py-16 text-sm text-gray-400">
          Escribe un año válido (entre 2000 y 2100) para ver las materias.
        </div>

        <div v-else-if="seccionesVisibles.length" class="space-y-6">
          <section v-for="s in seccionesVisibles" :key="s.cod_plan">
            <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
              Carrera {{ s.carrera }}
              <span class="ml-1 text-xs font-normal text-gray-500">{{ s.total_materias }} materias</span>
            </h4>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
              <table class="w-full text-xs border-collapse min-w-[1100px]">
                <thead class="bg-gray-100 text-gray-600">
                  <tr>
                    <th class="px-2 py-2 text-center font-semibold w-10">Nº</th>
                    <th class="px-2 py-2 text-center font-semibold">Plan</th>
                    <th class="px-2 py-2 text-center font-semibold">SIS-Mat</th>
                    <th class="px-2 py-2 text-left font-semibold">Materia</th>
                    <th class="px-2 py-2 text-center font-semibold">Grupo</th>
                    <th class="px-2 py-2 text-center font-semibold">CH</th>
                    <th class="px-2 py-2 text-left font-semibold">Docente actual</th>
                    <th class="px-2 py-2 text-left font-semibold">Día - Horario - Aula</th>
                    <th class="px-2 py-2 text-center font-semibold">Insc.</th>
                    <th class="px-2 py-2 text-left font-semibold">Compartido</th>
                    <th class="px-2 py-2 text-left font-semibold">Resolución</th>
                    <th class="px-2 py-2 text-left font-semibold">Designación</th>
                    <th class="px-2 py-2 text-center font-semibold">Tipo</th>
                    <th class="px-2 py-2 text-center font-semibold">PDF</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(f, i) in s.filas"
                    :key="s.cod_plan + '-' + i"
                    class="border-t border-gray-100"
                    :class="[
                      f.es_hija ? 'bg-pink-50/50' : '',
                      f.nro !== null && i > 0 ? 'border-t-2 border-t-gray-200' : ''
                    ]"
                  >
                    <td v-if="f.nro !== null" :rowspan="f.rowspan"
                      class="px-2 py-1.5 text-center align-middle border-r border-gray-100 font-medium text-gray-700">
                      {{ f.nro }}
                    </td>
                    <td class="px-2 py-1.5 text-center">
                      <div class="font-bold text-gray-800">{{ f.plan_abrev }}</div>
                      <div class="text-[10px] text-gray-500 tabular-nums">{{ f.cod_plan }}</div>
                    </td>
                    <td class="px-2 py-1.5 text-center tabular-nums text-gray-700">{{ f.cod_materia }}</td>
                    <td class="px-2 py-1.5 text-gray-800">{{ f.nombre_materia }}</td>
                    <td class="px-2 py-1.5 text-center tabular-nums text-gray-700">{{ f.grupo }}</td>
                    <td class="px-2 py-1.5 text-center tabular-nums text-gray-700">{{ f.ch }}</td>
                    <td class="px-2 py-1.5 text-gray-700">
                      <template v-if="filtros.conAsignacion">{{ f.docente_actual || '—' }}</template>
                      <span v-else class="text-gray-300">en blanco</span>
                    </td>

                    <!-- Horario -->
                    <td class="px-2 py-1.5 text-gray-700 whitespace-nowrap tabular-nums">
                      <template v-if="f.horario && f.horario.length">
                        <div v-for="(h, k) in f.horario" :key="k">{{ h }}</div>
                      </template>
                      <span v-else class="text-gray-300">—</span>
                    </td>

                    <!-- Inscritos -->
                    <td class="px-2 py-1.5 text-center tabular-nums font-semibold text-gray-800">{{ f.inscritos }}</td>

                    <td class="px-2 py-1.5 font-bold text-pink-700 whitespace-nowrap">{{ f.compartido }}</td>
                    <td class="px-2 py-1.5 text-gray-600">
                      <template v-if="filtros.conAsignacion">{{ f.resolucion || '—' }}</template>
                      <span v-else class="text-gray-300">en blanco</span>
                    </td>
                    <td class="px-2 py-1.5 text-gray-600">
                      <template v-if="filtros.conAsignacion">{{ f.designacion || '—' }}</template>
                      <span v-else class="text-gray-300">en blanco</span>
                    </td>
                    <td class="px-2 py-1.5 text-center font-bold text-gray-800">{{ f.tipo_ingreso }}</td>

                    <!-- PDF del documento -->
                    <td class="px-2 py-1.5 text-center">
                      <button
                        v-if="filtros.conAsignacion && f.id_documento && f.nombre_archivo"
                        @click="verPdf(f.id_documento)"
                        :disabled="abriendoPdfId === f.id_documento"
                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-800 hover:bg-blue-900 text-white text-[11px] font-semibold rounded-md shadow-sm transition-colors disabled:opacity-60"
                        title="Ver el PDF del documento"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                        </svg>
                        {{ abriendoPdfId === f.id_documento ? '...' : 'PDF' }}
                      </button>
                      <span v-else-if="f.nro !== null && !f.tiene_documento"
                        class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-amber-100 text-amber-800">
                        Sin doc.
                      </span>
                      <span v-else class="text-gray-300">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div v-else class="text-center py-16 text-sm text-gray-400">
          <template v-if="busqueda">
            Ninguna materia coincide con “{{ busqueda }}”.
            <div class="mt-2">
              <button @click="busqueda = ''" class="text-blue-500 hover:underline">Borrar búsqueda</button>
            </div>
          </template>
          <template v-else-if="filtros.soloSinDocumento">
            No hay materias de tipo “{{ etiquetaCategorias }}” sin documento subido en {{ etiquetaPeriodo }}/{{ filtros.anio }}
            para las carreras elegidas. Todas ya tienen su documento.
          </template>
          <template v-else>
            No hay materias de tipo “{{ etiquetaCategorias }}” en {{ etiquetaPeriodo }}/{{ filtros.anio }}
            para las carreras elegidas. Prueba con otro periodo o quita carreras.
          </template>
        </div>
      </div>

      <!-- ── Pie ── -->
      <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5 border-t border-gray-100">
        <p class="text-xs text-gray-500">
          El Excel sale con el mismo formato de la lista manual: una hoja, cada carrera en su propia página al imprimir.
        </p>
        <div class="flex items-center gap-3">
          <button
            @click="volver"
            class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Volver
          </button>

          <!-- Vista previa PDF -->
          <button
            @click="previsualizarPdf"
            :disabled="!hayDatos || loading || generandoPdf || !seccionesVisibles.length"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors"
            title="Ver cómo quedaría el reporte en PDF"
          >
            <svg v-if="generandoPdf" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ generandoPdf ? 'Generando…' : 'Vista previa PDF' }}
          </button>

          <button
            @click="descargar"
            :disabled="!hayDatos || loading || descargando"
            class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors"
          >
            <svg v-if="descargando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H6a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ descargando ? 'Generando…' : 'Descargar Excel' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Modal vista previa PDF ── -->
    <Teleport to="body">
      <div v-if="mostrarModalPdf" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="cerrarModalPdf">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden">
          <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-gray-900">Vista previa del PDF</h3>
              <p class="text-xs text-gray-500 truncate">{{ nombreArchivoPdf }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="abrirPdfEnPestana"
                class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Abrir en pestaña
              </button>
              <button @click="descargarPdfPrevia"
                class="px-3 py-1.5 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">
                Descargar PDF
              </button>
              <button @click="cerrarModalPdf" title="Cerrar"
                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
          <iframe v-if="urlPdf" :src="urlPdf" class="flex-1 w-full border-0 bg-slate-100" title="Vista previa PDF"></iframe>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useKardexMateria } from '../composables/useKardexMateria'
import { useCategorias } from '../../resolucionesExtra/composables/useCategorias'
import { usePeriodoActual } from '../../horariosadmin/composables/usePeriodoActual'
import { generarPDFKardexMateria } from '../composables/usePdfKardexMateria'

const router = useRouter()
const {
  planes, reporte, loading, descargando, error,
  cargarPlanes, consultar, descargarExcel,
  verPdf: abrirPdfDocumento,
} = useKardexMateria()

// Lista de categorías (la misma que usa el Reporte Excel)
const { categorias, cargarCategorias } = useCategorias()

// ─── Periodo actual (mismo composable que usa el reporte de horarios) ───
const { anio: anioActual, periodo: periodoActual } = usePeriodoActual()

const periodos = [
  { valor: '1', texto: '1' },
  { valor: '2', texto: '2' },
  { valor: '3', texto: '3 · Verano' },
  { valor: '4', texto: '4 · Invierno' },
]

const filtros = ref({
  anio: Number(anioActual.value) || new Date().getFullYear(),
  periodo: String(periodoActual.value || '1'),
  categorias: [],          // vacío = ACÉFALA (lo resuelve el backend)
  planes: [],
  incluirCompartidas: true,
  conAsignacion: true,
  soloSinDocumento: false, // true = solo materias sin ningún PDF subido en la gestión
})

const panelOpciones = ref(true)
const dropdownAbierto = ref(false)
const busqueda = ref('')
const pestana = ref('todas')

const periodoActualListo = computed(() => !!anioActual.value && !!periodoActual.value)
const esPeriodoActual = computed(
  () => Number(filtros.value.anio) === Number(anioActual.value) &&
        String(filtros.value.periodo) === String(periodoActual.value)
)

function usarPeriodoActual() {
  if (anioActual.value) filtros.value.anio = Number(anioActual.value)
  if (periodoActual.value) filtros.value.periodo = String(periodoActual.value)
}

// Si el periodo llega después (async), se aplica al llegar
watch([anioActual, periodoActual], usarPeriodoActual)

const anioValido = computed(() => {
  const a = Number(filtros.value.anio)
  return Number.isInteger(a) && a >= 2000 && a <= 2100
})

const etiquetaPeriodo = computed(() => ({ '3': 'Verano', '4': 'Invierno' }[String(filtros.value.periodo)] || filtros.value.periodo))

const etiquetaCategorias = computed(() =>
  filtros.value.categorias.length ? filtros.value.categorias.join(', ') : 'ACÉFALA'
)

function quitarCategoria(c) {
  filtros.value.categorias = filtros.value.categorias.filter((x) => x !== c)
}

function alternarPlan(cod) {
  const i = filtros.value.planes.indexOf(cod)
  if (i >= 0) filtros.value.planes.splice(i, 1)
  else filtros.value.planes.push(cod)
}

const interruptores = computed(() => [
  {
    id: 'comp',
    titulo: 'Incluir materias compartidas',
    desc: 'Muestra bajo cada materia las filas “(*) COMPARTIDO” de otras carreras, con CH 0.',
    activo: filtros.value.incluirCompartidas,
    accion: () => { filtros.value.incluirCompartidas = !filtros.value.incluirCompartidas },
  },
  {
    id: 'asig',
    titulo: 'Mostrar docente y resolución',
    desc: 'Rellena Nombre Docente, Resolución y Designación. Apagado: salen en blanco para llenar a mano.',
    activo: filtros.value.conAsignacion,
    accion: () => { filtros.value.conAsignacion = !filtros.value.conAsignacion },
  },
  {
    id: 'sindoc',
    titulo: 'Solo sin documento subido',
    desc: 'Lista únicamente las materias de la categoría elegida que aún no tienen ningún PDF subido en la gestión.',
    activo: filtros.value.soloSinDocumento,
    accion: () => { filtros.value.soloSinDocumento = !filtros.value.soloSinDocumento },
  },
])

// ─── Consulta automática (con espera para no consultar por cada tecla) ───
let timer = null

const claveConsulta = computed(() => {
  const f = filtros.value
  return [
    f.anio, f.periodo,
    [...f.categorias].sort().join('|'),
    [...f.planes].sort().join('|'),
    f.incluirCompartidas, f.conAsignacion, f.soloSinDocumento,
  ].join('~')
})

function copiaFiltros() {
  return {
    ...filtros.value,
    categorias: [...filtros.value.categorias],
    planes: [...filtros.value.planes],
  }
}

function programarConsulta() {
  clearTimeout(timer)
  if (!anioValido.value) return
  timer = setTimeout(() => consultar(copiaFiltros()), 400)
}

watch(claveConsulta, programarConsulta)

// Si la pestaña elegida ya no existe tras recargar, vuelve a "Todas"
watch(reporte, (r) => {
  if (pestana.value !== 'todas' && !r?.secciones?.some((s) => s.cod_plan === pestana.value)) {
    pestana.value = 'todas'
  }
})

// ─── Búsqueda en cliente (por bloque: materia principal + sus compartidas) ───
const norm = (s) => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()

// Con "Solo sin documento subido" activo, "POR DESIGNAR DOCENTE" se muestra como "-"
const esPorDesignar = (v) => norm(v).includes('por designar')

const hayDatos = computed(() => (reporte.value?.total_filas || 0) > 0)

const seccionesVisibles = computed(() => {
  const secs = reporte.value?.secciones || []
  const q = norm(busqueda.value.trim())

  return secs
    .filter((s) => pestana.value === 'todas' || s.cod_plan === pestana.value)
    .map((s) => {
      if (!q) return s

      // Agrupa filas en bloques: una fila con nro !== null abre bloque nuevo
      const bloques = []
      for (const f of s.filas) {
        if (f.nro !== null || !bloques.length) bloques.push([])
        bloques[bloques.length - 1].push(f)
      }
      const coincide = (f) =>
        norm(f.nombre_materia).includes(q) ||
        norm(f.docente_actual).includes(q) ||
        norm(f.cod_materia).includes(q) ||
        norm((f.horario || []).join(' ')).includes(q)

      const filtrados = bloques.filter((b) => b.some(coincide))
      return { ...s, filas: filtrados.flat(), total_materias: filtrados.length }
    })
    // Con "solo sin documento": "POR DESIGNAR DOCENTE" → "-"
    .map((s) => {
      if (!filtros.value.soloSinDocumento) return s
      return {
        ...s,
        filas: s.filas.map((f) =>
          esPorDesignar(f.docente_actual) ? { ...f, docente_actual: '-' } : f
        ),
      }
    })
    .filter((s) => s.filas.length)
})

// ─── Acciones ───
async function descargar() {
  const ok = await descargarExcel(copiaFiltros())
  if (!ok && error.value) alert(error.value)
}

function volver() {
  clearTimeout(timer)
  if (window.history.length > 1) router.back()
  else router.push('/')
}

// ─── Ver PDF del documento (protegido con auth:sanctum) ───
const abriendoPdfId = ref(null)

async function verPdf(idDocumento) {
  if (abriendoPdfId.value) return
  abriendoPdfId.value = idDocumento
  try {
    await abrirPdfDocumento(idDocumento, 'inline')
  } catch (e) {
    alert(e?.response?.status === 401
      ? 'Tu sesión expiró. Vuelve a iniciar sesión.'
      : 'No se pudo abrir el PDF'
    )
  } finally {
    abriendoPdfId.value = null
  }
}

// ─── Vista previa en PDF ───
const mostrarModalPdf = ref(false)
const generandoPdf = ref(false)
const urlPdf = ref('')
const nombreArchivoPdf = ref('')

function liberarUrlPdf() {
  if (urlPdf.value) {
    URL.revokeObjectURL(urlPdf.value)
    urlPdf.value = ''
  }
}

async function previsualizarPdf() {
  if (generandoPdf.value) return
  generandoPdf.value = true
  try {
    await nextTick() // deja pintar "Generando…"
    liberarUrlPdf()

    const { url, filename } = generarPDFKardexMateria(seccionesVisibles.value, {
      titulo: 'Kardex de materias',
      subtitulo: reporte.value?.titulo || '',
      conAsignacion: filtros.value.conAsignacion,
      soloSinDocumento: filtros.value.soloSinDocumento,
      anio: filtros.value.anio,
      periodo: filtros.value.periodo,
    })
    urlPdf.value = url
    nombreArchivoPdf.value = filename
    mostrarModalPdf.value = true
  } catch (e) {
    console.error('Error generando la vista previa en PDF:', e)
    alert('No se pudo generar la vista previa en PDF')
  } finally {
    generandoPdf.value = false
  }
}

function cerrarModalPdf() {
  mostrarModalPdf.value = false
  liberarUrlPdf()
}

function abrirPdfEnPestana() {
  if (urlPdf.value) window.open(urlPdf.value, '_blank')
}

function descargarPdfPrevia() {
  if (!urlPdf.value) return
  const a = document.createElement('a')
  a.href = urlPdf.value
  a.download = nombreArchivoPdf.value || 'kardex.pdf'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
}

onMounted(async () => {
  usarPeriodoActual()
  cargarPlanes()
  cargarCategorias()
  programarConsulta()
})

onBeforeUnmount(() => {
  clearTimeout(timer)
  liberarUrlPdf()
})
</script>