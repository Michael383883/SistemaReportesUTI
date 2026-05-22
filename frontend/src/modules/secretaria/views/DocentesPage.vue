<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">Gestión de Docentes</h1>
          <p class="text-sm text-slate-500 mt-0.5">Facultad de Ciencias Económicas · {{ currentPeriod }}</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="bg-teal-50 text-teal-700 text-sm font-semibold px-3 py-1.5 rounded-full border border-teal-200">
            {{ docentesFiltrados.length }} docentes
          </span>
          <button
            @click="exportarExcel"
            class="flex items-center gap-2 bg-white border border-slate-300 text-slate-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar
          </button>
        </div>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="px-6 py-4 bg-white border-b border-slate-100">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Búsqueda -->
        <div class="relative flex-1 min-w-64">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, CI o unidad..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
          />
        </div>

        <!-- Filtro Unidad -->
        <select
          v-model="filtroUnidad"
          class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
        >
          <option value="">Todas las unidades</option>
          <option v-for="u in unidades" :key="u" :value="u">{{ u }}</option>
        </select>

        <!-- Filtro Grado -->
        <select
          v-model="filtroGrado"
          class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
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
            :class="['px-3 py-1.5 rounded-md text-sm font-medium transition-all', vista === 'tabla' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
            </svg>
          </button>
          <button
            @click="vista = 'cards'"
            :class="['px-3 py-1.5 rounded-md text-sm font-medium transition-all', vista === 'cards' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-6">

      <!-- Estado de carga -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-24">
        <div class="w-10 h-10 border-4 border-teal-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-slate-500 text-sm">Cargando docentes...</p>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="docentesFiltrados.length === 0" class="flex flex-col items-center justify-center py-24">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-slate-600 font-medium">No se encontraron docentes</p>
        <p class="text-slate-400 text-sm mt-1">Intenta con otros filtros de búsqueda</p>
        <button @click="limpiarFiltros" class="mt-4 text-teal-600 text-sm font-medium hover:underline">Limpiar filtros</button>
      </div>

      <!-- Vista Tabla -->
      <div v-else-if="vista === 'tabla'" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Docente</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">C.I.</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Grado</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unidad</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Contacto</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Horario</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Carga</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="docente in docentesPaginados"
                :key="docente.docente"
                class="hover:bg-slate-50 transition-colors cursor-pointer"
                @click="abrirDetalle(docente)"
              >
                <!-- Nombre + Avatar -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
              <i class="ti ti-user" style="font-size: 18px; color: #64748b;" aria-hidden="true"></i>
            </div>
                    <div>
                      <p class="font-medium text-slate-800 leading-tight">{{ formatNombre(docente.nombre_docente) }}</p>
                      <p class="text-xs text-slate-400">Cód. {{ docente.docente }}</p>
                    </div>
                  </div>
                </td>
                <!-- CI -->
                <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ docente.ci || '—' }}</td>
                <!-- Grado -->
                <td class="px-4 py-3">
                  <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium', badgeGrado(docente.grado_academico)]">
                    {{ docente.grado_academico || 'Sin especificar' }}
                  </span>
                </td>
                <!-- Unidad -->
                <td class="px-4 py-3 text-slate-600 text-xs">{{ docente.unidad || '—' }}</td>
                <!-- Contacto -->
                <td class="px-4 py-3">
                  <div class="flex flex-col gap-1">
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
                <!-- Horario (NUEVO) -->
                <td class="px-4 py-3">
                  <button
                    v-if="docente.horario_cargado"
                    @click.stop="verHorarioRapido(docente)"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-lg transition-colors flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ docente.total_materias || 0 }} mat.
                  </button>
                  <span v-else class="text-xs text-slate-400">Sin horario</span>
                </td>
                <!-- Carga horaria -->
                <td class="px-4 py-3 text-center">
                  <div v-if="docente.horas_total" class="flex items-center justify-center gap-2">
                    <div class="flex-1 bg-slate-100 rounded-full h-1.5 w-16">
                      <div :class="['h-1.5 rounded-full', colorCarga(docente.horas_total)]" :style="{ width: Math.min((docente.horas_total / 40) * 100, 100) + '%' }"></div>
                    </div>
                    <span class="text-xs text-slate-600 font-medium">{{ docente.horas_total }}h</span>
                  </div>
                  <span v-else class="text-xs text-amber-600 flex items-center justify-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Sin asignar
                  </span>
                </td>
                <!-- Acciones -->
                <td class="px-4 py-3" @click.stop>
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="abrirDetalle(docente)"
                      class="p-1.5 text-slate-400 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                      title="Ver detalle"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </button>
                    <button
                      @click="verHorarioCompleto(docente)"
                      class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Ver materia"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
          <p class="text-sm text-slate-500">
            Mostrando {{ (paginaActual - 1) * porPagina + 1 }}–{{ Math.min(paginaActual * porPagina, docentesFiltrados.length) }} de {{ docentesFiltrados.length }}
          </p>
          <div class="flex items-center gap-1">
            <button
              @click="paginaActual--"
              :disabled="paginaActual === 1"
              class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            >Anterior</button>
            <template v-for="p in totalPaginas" :key="p">
              <button
                v-if="Math.abs(p - paginaActual) <= 2 || p === 1 || p === totalPaginas"
                @click="paginaActual = p"
                :class="['w-8 h-8 text-sm rounded-lg transition-colors', p === paginaActual ? 'bg-teal-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50']"
              >{{ p }}</button>
              <span v-else-if="p === paginaActual - 3 || p === paginaActual + 3" class="px-1 text-slate-400">…</span>
            </template>
            <button
              @click="paginaActual++"
              :disabled="paginaActual === totalPaginas"
              class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            >Siguiente</button>
          </div>
        </div>
      </div>

      <!-- Vista Cards (simplificada) -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="docente in docentesPaginados"
          :key="docente.docente"
          @click="abrirDetalle(docente)"
          class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div class="flex items-start gap-3 mb-3">
            <div class="w-14 h-14 rounded-2xl bg-white/20 border border-white/30 flex items-center justify-center flex-shrink-0">
              <i class="ti ti-user" style="font-size: 28px; color: white;" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-slate-800 text-sm leading-tight truncate group-hover:text-teal-700 transition-colors">{{ formatNombre(docente.nombre_docente) }}</p>
              <p class="text-xs text-slate-400 mt-0.5">{{ docente.unidad || 'Sin unidad' }}</p>
            </div>
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
              </svg>
              <span class="font-mono">{{ docente.ci || '—' }}</span>
            </div>
            <div v-if="docente.email || docente.email_institucional" class="flex items-center gap-2 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <span class="truncate">{{ docente.email || docente.email_institucional }}</span>
            </div>
            <div v-if="docente.horario_cargado" class="flex items-center gap-2 text-xs text-blue-600">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>{{ docente.total_materias || 0 }} materias</span>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', badgeGrado(docente.grado_academico)]">
              {{ docente.grado_academico || 'Sin grado' }}
            </span>
            <span v-if="docente.horas_total" class="text-xs text-slate-500">{{ docente.horas_total }}h/sem</span>
            <span v-else class="text-xs text-amber-500">Sin horario</span>
          </div>
        </div>

        <div class="col-span-full flex items-center justify-center gap-2 mt-2" v-if="totalPaginas > 1">
          <button @click="paginaActual--" :disabled="paginaActual === 1" class="px-4 py-2 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-50">Anterior</button>
          <span class="text-sm text-slate-500">Página {{ paginaActual }} de {{ totalPaginas }}</span>
          <button @click="paginaActual++" :disabled="paginaActual === totalPaginas" class="px-4 py-2 text-sm rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40 hover:bg-slate-50">Siguiente</button>
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
      @ver-horario="abrirHorarioDesdeDetalle"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import DocenteDetalleModal from '@/shared/components/docentes/DocenteDetalleModal.vue'
import HorarioRapidoModal from '@/shared/components/docentes/HorarioRapidoModal.vue'
import { docentesService } from '@/shared/services/docentesService'

const cargando = ref(false)
const docentes = ref([])
const busqueda = ref('')
const filtroUnidad = ref('')
const filtroGrado = ref('')
const vista = ref('tabla')
const paginaActual = ref(1)
const porPagina = ref(15)
const docenteSeleccionado = ref(null)
const docenteHorarioSeleccionado = ref(null)
const modoModal = ref('detalle')
const currentPeriod = ref('mayo 2026')
const horarioAbridoDesdeDetalle = ref(false)

onMounted(async () => {
  await cargarDocentes()
})

async function cargarDocentes() {
  cargando.value = true
  try {
    // Cargar docentes y sus horarios en paralelo
    const [docentesData, horariosData] = await Promise.all([
      docentesService.getAll(),
      docentesService.getAllHorarios()
    ])
    
    // Mapear horarios a docentes
    const horariosMap = new Map()
    if (Array.isArray(horariosData)) {
      horariosData.forEach(h => {
        horariosMap.set(String(h.docente), h)
      })
    }
    
    // Enriquecer datos de docentes con horarios
    docentes.value = docentesData.map(docente => {
      const horario = horariosMap.get(String(docente.docente))
      if (horario) {
        return {
          ...docente,
          horario_cargado: true,
          total_materias: horario.total_horarios || horario.materias?.length || 0,
          horas_total: horario.carga_horaria_total || docente.horas_total || 0,
          materias: horario.materias || [],
          horario_completo: horario
        }
      }
      return docente
    })
  } catch (e) {
    console.error('Error cargando docentes:', e)
    // Si falla, cargar solo docentes básicos
    try {
      docentes.value = await docentesService.getAll()
    } catch (e2) {
      console.error('Error crítico:', e2)
    }
  } finally {
    cargando.value = false
  }
}

const unidades = computed(() => {
  const u = new Set(docentes.value.map(d => d.unidad).filter(Boolean))
  return [...u].sort()
})

const docentesFiltrados = computed(() => {
  let lista = docentes.value
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase()
    lista = lista.filter(d =>
      (d.nombre_docente || '').toLowerCase().includes(q) ||
      String(d.ci || '').includes(q) ||
      (d.unidad || '').toLowerCase().includes(q)
    )
  }
  if (filtroUnidad.value) lista = lista.filter(d => d.unidad === filtroUnidad.value)
  if (filtroGrado.value) lista = lista.filter(d => d.grado_academico === filtroGrado.value)
  return lista
})

const totalPaginas = computed(() => Math.ceil(docentesFiltrados.value.length / porPagina.value))

const docentesPaginados = computed(() => {
  const start = (paginaActual.value - 1) * porPagina.value
  return docentesFiltrados.value.slice(start, start + porPagina.value)
})

watch([busqueda, filtroUnidad, filtroGrado], () => { paginaActual.value = 1 })

// Funciones para horarios
async function verHorarioRapido(docente) {
  horarioAbridoDesdeDetalle.value = false  // ← vino de la tabla
  if (docente.horario_completo) {
    docenteHorarioSeleccionado.value = docente
    return
  }
  try {
    const horario = await docentesService.getHorario(docente.docente)
    docenteHorarioSeleccionado.value = { ...docente, horario_completo: horario }
  } catch (e) {
    console.error('Error cargando horario:', e)
  }
}


function verHorarioCompleto(docente) {
  docenteSeleccionado.value = docente
  modoModal.value = 'horario'
}

function abrirDetalle(docente) {
  docenteSeleccionado.value = docente
  modoModal.value = 'detalle'
}

function cerrarModal() {
  docenteSeleccionado.value = null
  docenteHorarioSeleccionado.value = null
}

function limpiarFiltros() {
  busqueda.value = ''
  filtroUnidad.value = ''
  filtroGrado.value = ''
}

function exportarExcel() {
  alert('Exportando a Excel...')
}


function abrirHorarioDesdeDetalle(docente) {
  docenteSeleccionado.value = null
  docenteHorarioSeleccionado.value = docente
  horarioAbridoDesdeDetalle.value = true   // ← vino del modal
}

function cerrarHorarioRapido() {
  const docente = docenteHorarioSeleccionado.value
  docenteHorarioSeleccionado.value = null

  if (horarioAbridoDesdeDetalle.value && docente) {
    // Vuelve al modal de detalle
    docenteSeleccionado.value = docente
    modoModal.value = 'detalle'
    horarioAbridoDesdeDetalle.value = false
  }
}
// Helpers
function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function iniciales(nombre) {
  if (!nombre) return '?'
  const partes = nombre.trim().split(' ').filter(Boolean)
  return partes.length >= 2 ? partes[0][0] + partes[1][0] : partes[0]?.[0] || '?'
}

const avatarColors = ['bg-teal-600', 'bg-blue-600', 'bg-violet-600', 'bg-rose-500', 'bg-amber-600', 'bg-emerald-600', 'bg-cyan-600', 'bg-indigo-600']
function colorAvatar(nombre) {
  if (!nombre) return avatarColors[0]
  const idx = nombre.charCodeAt(0) % avatarColors.length
  return avatarColors[idx]
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

function colorCarga(horas) {
  if (horas >= 30) return 'bg-green-500'
  if (horas >= 15) return 'bg-amber-500'
  return 'bg-red-400'
}
</script>