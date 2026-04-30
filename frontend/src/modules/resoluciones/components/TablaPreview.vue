<template>
  <div class="space-y-5">

    <!-- Resumen header -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Resolución</span>
          </div>
          <h2 class="text-lg font-bold text-gray-900">{{ numero || '—' }}</h2>
          <p class="text-sm text-gray-500 mt-0.5">{{ descripcion || 'Sin descripción' }}</p>
        </div>
        <!-- Stats -->
        <div class="flex gap-5 flex-shrink-0">
          <div class="text-center">
            <p class="text-2xl font-bold text-gray-900">{{ totalCarreras }}</p>
            <p class="text-xs text-gray-400">Carreras</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">{{ totalMaterias }}</p>
            <p class="text-xs text-gray-400">Materias</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-gray-900">{{ totalDocentes }}</p>
            <p class="text-xs text-gray-400">Docentes</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tablas por carrera -->
    <div
      v-for="(tabla, ti) in tablas"
      :key="ti"
      class="bg-white rounded-xl border border-gray-200 overflow-hidden"
    >
      <!-- Carrera header -->
      <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-600 text-white tracking-wide">
            {{ tabla.carrera }}
          </span>
          <div>
            <span class="text-sm font-semibold text-gray-800">{{ nombreCarrera(tabla.carrera) }}</span>
            <span class="text-xs text-gray-400 ml-2">· Cod. {{ tabla.codigo }}</span>
          </div>
        </div>
        <span class="text-xs text-gray-400">{{ tabla.materias.length }} materia(s)</span>
      </div>

      <!-- Tabla de materias -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-5 py-3 w-8">N°</th>
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-3">SIS-MAT</th>
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-3">Materia</th>
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-3 w-16">Grupo</th>
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-3 w-14">C/H</th>
              <th class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-3">Docente</th>
              <th class="px-4 py-3 w-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr
              v-for="(materia, mi) in tabla.materias"
              :key="mi"
              class="hover:bg-gray-50 transition-colors group"
            >
              <td class="px-5 py-3 text-gray-400 text-xs">{{ materia.numero }}</td>
              <td class="px-4 py-3">
                <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                  {{ materia.sisMat }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="font-medium text-gray-800">{{ materia.nombreMateria }}</span>
                <span v-if="materia.esCompartido" class="ml-2 text-xs bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded font-medium">
                  Compartido
                </span>
              </td>
              <td class="px-4 py-3 text-gray-600 text-center">{{ materia.grupo }}</td>
              <td class="px-4 py-3 text-gray-600 text-center">{{ materia.ch }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-semibold text-gray-600">{{ iniciales(materia.docente) }}</span>
                  </div>
                  <span class="text-gray-700 font-medium">{{ materia.docente }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <!-- Indicador OK por fila -->
                <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <svg class="w-3 h-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="!tablas || tablas.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
      <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-gray-500 font-medium">No se encontraron tablas</p>
      <p class="text-gray-400 text-sm mt-1">El PDF no contiene datos de materias reconocibles.</p>
    </div>

    <!-- Advertencia si hay problemas -->
    <div v-if="hasWarnings" class="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
      <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      <div>
        <p class="text-sm font-semibold text-yellow-800">Revisa los datos antes de migrar</p>
        <p class="text-sm text-yellow-700 mt-0.5">Algunos campos pueden estar incompletos o requerir corrección manual.</p>
      </div>
    </div>

    <!-- Acciones finales -->
    <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-6 py-4">
      <button
        @click="$emit('back')"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-red-600 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Volver a escanear
      </button>

      <button
        @click="$emit('migrar')"
        :disabled="loading || !tablas || tablas.length === 0"
        class="flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200"
        :class="loading || !tablas?.length
          ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
          : 'bg-green-600 hover:bg-green-700 text-white shadow-sm hover:shadow-md'"
      >
        <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        {{ loading ? 'Migrando...' : 'Migrar Resolución' }}
      </button>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Object, default: null },
  numero: { type: String, default: '' },
  descripcion: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})

defineEmits(['migrar', 'back'])

// El backend devuelve: { carreras: [ { carrera, codigo, materias: [...] } ] }
const tablas = computed(() => props.data?.carreras || mockTablas)

const totalCarreras = computed(() => tablas.value.length)
const totalMaterias = computed(() => tablas.value.reduce((acc, t) => acc + t.materias.length, 0))
const totalDocentes = computed(() => {
  const set = new Set()
  tablas.value.forEach(t => t.materias.forEach(m => set.add(m.docente)))
  return set.size
})

const hasWarnings = computed(() =>
  tablas.value.some(t => t.materias.some(m => !m.docente || !m.nombreMateria))
)

function iniciales(nombre) {
  if (!nombre) return '?'
  const partes = nombre.trim().split(' ')
  return partes.slice(0, 2).map(p => p[0]).join('').toUpperCase()
}

function nombreCarrera(codigo) {
  const map = {
    ADM: 'Administración de Empresas',
    CCP: 'Contaduría Pública',
    ECO: 'Economía',
    COM: 'Ingeniería Comercial',
    FIN: 'Ingeniería Financiera',
  }
  return map[codigo] || codigo
}

// Datos de ejemplo basados en las resoluciones de las fotos
const mockTablas = [
  {
    carrera: 'ADM',
    codigo: '109401',
    materias: [
      { numero: 1, sisMat: '1301027', nombreMateria: 'ADMINISTRACION PUBLICA', grupo: '20', ch: 24, docente: 'PANTOJA ROCHA YURI NEWTON', esCompartido: false },
      { numero: 2, sisMat: '1301016', nombreMateria: 'ESTADISTICA II', grupo: '02', ch: 16, docente: 'NINA LAURA ALVARO JESUS', esCompartido: false },
      { numero: 3, sisMat: '1301005', nombreMateria: 'CALCULO', grupo: '21', ch: 16, docente: 'SANDOVAL ARNEZ JUAN ALBERTO', esCompartido: false },
      { numero: 4, sisMat: '1301005', nombreMateria: 'CALCULO', grupo: '20', ch: 16, docente: 'RODRIGUEZ HURTADO JOSE LUIS', esCompartido: false },
    ],
  },
  {
    carrera: 'CCP',
    codigo: '089801',
    materias: [
      { numero: 1, sisMat: '1302028', nombreMateria: 'COSTOS II', grupo: '02', ch: 24, docente: 'INCA QUISPE RONALD DAVID', esCompartido: false },
      { numero: 2, sisMat: '1302034', nombreMateria: 'TALLER DE COSTOS', grupo: '22', ch: 24, docente: 'GUZMAN RODRIGUEZ JESUS', esCompartido: false },
      { numero: 3, sisMat: '1302034', nombreMateria: 'TALLER DE COSTOS', grupo: '24', ch: 24, docente: 'GARCIA ZAMORANO JESUS CESAR', esCompartido: false },
      { numero: 4, sisMat: '1302044', nombreMateria: 'AUDITORIA II', grupo: '22', ch: 24, docente: 'LAZARTE MALDONADO GROVER ANGEL', esCompartido: false },
      { numero: 5, sisMat: '1302002', nombreMateria: 'ALGEBRA', grupo: '22', ch: 24, docente: 'HINOJOSA DE IRAOLA MARCELO', esCompartido: false },
      { numero: 6, sisMat: '1302057', nombreMateria: 'TALLER', grupo: '40', ch: 8, docente: 'OLIVERA TAPIA JUAN CARLOS', esCompartido: false },
    ],
  },
  {
    carrera: 'ECO',
    codigo: '059801',
    materias: [
      { numero: 1, sisMat: '1304135', nombreMateria: 'MERCADO DE CAPITALES', grupo: '21', ch: 24, docente: 'BARRIENTOS BALDERRAMA CLAUDIA NINOZKA', esCompartido: false },
      { numero: 2, sisMat: '1304014', nombreMateria: 'MACROECONOMIA II', grupo: '01', ch: 16, docente: 'ASCARRAGA SEJAS WILMAR HENRY', esCompartido: false },
      { numero: 3, sisMat: '1304033', nombreMateria: 'TEORIAS ECONOMICAS ACTUALES', grupo: '01', ch: 16, docente: 'SARAVIA LOPEZ VICKY ALEJANDRA', esCompartido: false },
    ],
  },
]
</script>