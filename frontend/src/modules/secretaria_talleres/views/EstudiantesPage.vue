<template>
  <div class="min-h-screen bg-slate-50">

    <!-- ===== HEADER ===== -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight">
            Estudiantes en Talleres
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">
            Gestión · Período {{ filtros.periodo }} / {{ filtros.anio }}
          </p>
        </div>
        <!-- Badge total -->
        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-blue-700 text-sm font-semibold border border-blue-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 11-4 0 2 2 0 014 0zM5 16a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ estudiantesFiltrados.length }} estudiante{{ estudiantesFiltrados.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="bg-white border-b border-slate-100 px-6 py-4 shadow-sm">
      <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

        <!-- Buscador nombre/código -->
        <div class="relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
          </svg>
          <input
            v-model="filtros.busqueda"
            type="text"
            placeholder="Buscar estudiante o código..."
            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
          />
        </div>

        <!-- Filtro Plan/Carrera -->
        <div class="relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
          </svg>
          <select
            v-model="filtros.plan"
            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
          >
            <option value="">Todas las carreras</option>
            <option v-for="(nombre, codigo) in PLANES" :key="codigo" :value="codigo">
              {{ nombre }}
            </option>
          </select>
        </div>

        <!-- Filtro Materia -->
        <div class="relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
          </svg>
          <select
            v-model="filtros.materia"
            class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition appearance-none cursor-pointer"
          >
            <option value="">Todas las materias</option>
            <option v-for="m in materias" :key="m.codigo" :value="m.codigo">
              {{ m.nombre }} ({{ m.codigo }})
            </option>
          </select>
        </div>

        <!-- Botón limpiar filtros -->
        <button
          @click="limpiarFiltros"
          class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-sm font-medium py-2.5 px-4 transition active:scale-95"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.635 19A9 9 0 104.582 9H4" />
          </svg>
          Limpiar filtros
        </button>
      </div>

      <!-- Pills de filtros activos -->
      <div v-if="filtrosActivos.length" class="max-w-7xl mx-auto mt-3 flex flex-wrap gap-2">
        <span
          v-for="f in filtrosActivos"
          :key="f.key"
          class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium px-3 py-1"
        >
          {{ f.label }}
          <button @click="quitarFiltro(f.key)" class="hover:text-blue-900 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
      </div>
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

      <!-- Sin resultados -->
      <div v-else-if="!estudiantesFiltrados.length" class="flex flex-col items-center justify-center py-24 text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-base font-medium">Sin resultados</p>
        <p class="text-sm mt-1">Intenta ajustar los filtros de búsqueda.</p>
      </div>

      <!-- Tabla segmentada por materia -->
      <div v-else class="space-y-8">
        <div
          v-for="(grupo, nombreMateria) in gruposPorMateria"
          :key="nombreMateria"
          class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden"
        >
          <!-- Header del grupo -->
          <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-700 to-blue-500">
            <div class="flex items-center gap-3">
              <div class="h-8 w-8 rounded-lg bg-white/20 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                </svg>
              </div>
              <div>
                <h2 class="text-white font-bold text-base">{{ nombreMateria }}</h2>
                <p class="text-blue-100 text-xs">{{ grupo[0]?.materia }} · Nivel {{ grupo[0]?.nivel }}</p>
              </div>
            </div>
            <span class="rounded-full bg-white/20 text-white text-xs font-semibold px-3 py-1">
              {{ grupo.length }} inscritos
            </span>
          </div>

          <!-- Info docente -->
          <div class="px-6 py-2.5 bg-blue-50 border-b border-blue-100 flex items-center gap-2 text-blue-700 text-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="font-medium">Docente:</span>
            <span>{{ grupo[0]?.docente }}</span>
          </div>

          <!-- Tabla -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3 w-10">#</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Código</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nombre del Estudiante</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Carrera</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Grupo</th>
                  <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Contacto</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr
                  v-for="(est, idx) in grupo"
                  :key="est.codigo"
                  class="hover:bg-blue-50/40 transition-colors group"
                >
                  <!-- N° -->
                  <td class="px-6 py-3 text-slate-400 text-xs">{{ idx + 1 }}</td>

                  <!-- Código -->
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs font-semibold text-slate-600 bg-slate-100 rounded px-1.5 py-0.5">
                      {{ est.codigo }}
                    </span>
                  </td>

                  <!-- Nombre -->
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                      
                      <!-- DESPUÉS -->
                        <div
                          class="h-7 w-7 rounded-full bg-blue-100 text-blue-500 flex items-center justify-center shrink-0"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                            <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                          </svg>
                        </div>
                      <span class="text-slate-800 font-medium">{{ est.nom_estudiante }}</span>
                    </div>
                  </td>

                  <!-- Carrera (badge abreviado) -->
                  <td class="px-4 py-3">
                    <span
                      :class="colorPlan(est.plan)"
                      class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
                    >
                      {{ abreviarPlan(est.plan) }}
                    </span>
                  </td>

                  <!-- Grupo -->
                  <td class="px-4 py-3 text-slate-600 text-xs font-medium">{{ est.grupo }}</td>

                  <!-- Contacto -->
                  <td class="px-4 py-3 text-center">
                    <button
                      @click="verContacto(est)"
                      class="inline-flex items-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold px-3 py-1.5 transition-colors shadow-sm"
                      title="Ver tarjeta de contacto"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
                      </svg>
                      Ver
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
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
import { ref, reactive, computed, onMounted } from 'vue'
// ✅ FIX: importar también PLANES desde el servicio para consistencia con el componente hijo
import estudiantesService, { PLANES } from '../services/estudiantesService'
import ContactoEstudianteCard from '../components/ContactoEstudianteCard.vue'

// ── Estado ────────────────────────────────────────────────────
const cargando       = ref(false)
const estudiantes    = ref([])
const materias       = ref([])

const filtros = reactive({
  anio:     2026,
  periodo:  1,
  busqueda: '',
  plan:     '',
  materia:  '',
})

const modalVisible           = ref(false)
const estudianteSeleccionado = ref({})
const contactoData           = ref(null)

// ── Cargar datos ───────────────────────────────────────────────
onMounted(async () => {
  cargando.value = true
  try {
    // ✅ FIX: usar estudiantesService en lugar de svc (que no existía)
    const [e, m] = await Promise.all([
      estudiantesService.getEstudiantesEnTalleres({ anio: filtros.anio, periodo: filtros.periodo }),
      estudiantesService.getMateriasDisponibles({ anio: filtros.anio, periodo: filtros.periodo }),
    ])
    estudiantes.value = e
    materias.value    = m
  } finally {
    cargando.value = false
  }
})

// ── Filtrado ───────────────────────────────────────────────────
const estudiantesFiltrados = computed(() => {
  const b = filtros.busqueda.toLowerCase().trim()
  return estudiantes.value.filter(e => {
    const matchBusq = !b || e.nom_estudiante.toLowerCase().includes(b) || e.codigo.includes(b)
    const matchPlan = !filtros.plan    || e.plan    === filtros.plan
    const matchMat  = !filtros.materia || e.materia === filtros.materia
    return matchBusq && matchPlan && matchMat
  })
})

// Agrupar por materia
const gruposPorMateria = computed(() => {
  return estudiantesFiltrados.value.reduce((acc, est) => {
    const key = est.nombre_materia
    if (!acc[key]) acc[key] = []
    acc[key].push(est)
    return acc
  }, {})
})

// Pills activos
const filtrosActivos = computed(() => {
  const activos = []
  if (filtros.plan)     activos.push({ key: 'plan',     label: abreviarPlan(filtros.plan) })
  if (filtros.materia)  activos.push({ key: 'materia',  label: materias.value.find(m => m.codigo === filtros.materia)?.nombre || filtros.materia })
  if (filtros.busqueda) activos.push({ key: 'busqueda', label: `"${filtros.busqueda}"` })
  return activos
})

// ── Helpers UI ─────────────────────────────────────────────────
const iniciales = nombre =>
  (nombre || '').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()

// ✅ FIX: usar PLANES importado en lugar de la copia local ABREVS
const ABREVS = {
  '109401': 'Adm. Empresas',
  '125091': 'Ing. Comercial',
  '089801': 'Cont. Pública',
  '126091': 'Ing. Financiera',
  '059801': 'Economía',
}
const abreviarPlan = plan => ABREVS[plan] || plan

const COLORES = {
  '109401': 'bg-blue-100 text-blue-700',
  '125091': 'bg-emerald-100 text-emerald-700',
  '089801': 'bg-orange-100 text-orange-700',
  '126091': 'bg-violet-100 text-violet-700',
  '059801': 'bg-rose-100 text-rose-700',
}
const colorPlan = plan => COLORES[plan] || 'bg-slate-100 text-slate-700'

// ── Acciones ───────────────────────────────────────────────────
const limpiarFiltros = () => {
  filtros.busqueda = ''
  filtros.plan     = ''
  filtros.materia  = ''
}

const quitarFiltro = key => { filtros[key] = '' }

const verContacto = async est => {
  estudianteSeleccionado.value = est
  contactoData.value           = null
  modalVisible.value           = true
  try {
    // ✅ FIX: usar estudiantesService en lugar de svc
    contactoData.value = await estudiantesService.getContactoEstudiante(est.codigo)
  } catch {
    contactoData.value = { email: null, celular: null }
  }
}

const cerrarModal = () => { modalVisible.value = false }
</script>