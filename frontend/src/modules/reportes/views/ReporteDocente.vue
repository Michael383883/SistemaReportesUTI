<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="mb-6">
      <h1 class="text-[20px] font-bold text-gray-1000">Reporte de Docentes con Título</h1>
      <p class="text-[14px] text-gray-600 mt-1">Filtra por gestión, periodo y tipo de título, y elige qué columnas incluir</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-300 overflow-hidden mb-6">
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-800">
        <span class="text-[13px] font-bold text-white">Filtros</span>
      </div>

      <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-[12px] font-semibold text-gray-700">Gestión (año) *</label>
          <input v-model.number="filtros.anio" type="number"
            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] text-gray-900" />
        </div>

        <div>
          <label class="text-[12px] font-semibold text-gray-700">Periodo *</label>
          <select v-model="filtros.periodo" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] text-gray-900">
            <option value="1">1</option>
            <option value="4">Invierno</option>
            <option value="2">2</option>
            <option value="3">Verano</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="text-[12px] font-semibold text-gray-700">Tipo de título</label>
          <select v-model="filtros.tipo_titulo" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] text-gray-900">
            <option value="">Todos</option>
            <option v-for="t in tiposTitulo" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>

      <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <p class="text-[12px] font-semibold text-gray-700 mb-2">Datos adicionales del docente</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposDocenteDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer"
              :class="filtros.campos.includes(c.value) ? 'bg-amber-50 border-amber-400 text-amber-700' : 'border-gray-300 text-gray-800'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos" />
              {{ c.label }}
            </label>
          </div>
        </div>

        <div>
          <p class="text-[12px] font-semibold text-gray-700 mb-2">Datos adicionales del título</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposTituloDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer"
              :class="filtros.campos_titulo.includes(c.value) ? 'bg-amber-50 border-amber-400 text-amber-700' : 'border-gray-300 text-gray-800'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos_titulo" />
              {{ c.label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
        <button
          @click="onBuscar"
          :disabled="loading || !filtros.anio || !filtros.periodo"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-800 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors"
        >
          <svg v-if="loading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Vista previa
        </button>

        <button
          @click="onGenerarExcel"
          :disabled="!filtros.anio || !filtros.periodo || generandoExcel"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors"
        >
          <svg v-if="generandoExcel" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Generar Excel
        </button>
      </div>
    </div>

    <div v-if="error" class="mb-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[12px]">
      <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      {{ error }}
    </div>

    <!-- Resultados -->
    <div v-if="datos.length">
      <div class="flex items-center gap-2 mb-2">
        <p class="text-[13px] font-semibold text-gray-700">Resultados ({{ total }})</p>
        <svg v-if="loading" class="w-3.5 h-3.5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>

      <div class="bg-white rounded-xl border border-gray-300 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="bg-slate-800 text-white text-left">
                <th class="px-3 py-2.5 font-semibold">Código</th>
                <th class="px-3 py-2.5 font-semibold">Apellidos</th>
                <th class="px-3 py-2.5 font-semibold">Nombres</th>
                <th class="px-3 py-2.5 font-semibold">Tipo de Título</th>
                <th v-for="c in filtros.campos" :key="c" class="px-3 py-2.5 font-semibold text-slate-100">{{ etiqueta(c) }}</th>
                <th v-for="c in filtros.campos_titulo" :key="c" class="px-3 py-2.5 font-semibold">{{ etiqueta(c) }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in datos" :key="i" class="border-t border-gray-200 text-gray-800">
                <td class="px-3 py-2">{{ row.CODIGO }}</td>
                <td class="px-3 py-2">{{ row.APELLIDOS }}</td>
                <td class="px-3 py-2">{{ row.NOMBRES }}</td>
                <td class="px-3 py-2">{{ row.TIPO_TITULO }}</td>
                <td v-for="c in filtros.campos" :key="c" class="px-3 py-2">{{ row[c] }}</td>
                <td v-for="c in filtros.campos_titulo" :key="c" class="px-3 py-2">{{ row[c] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="buscoAlMenosUnaVez && !loading" class="text-center py-10 text-[13px] text-gray-800">
      No se encontraron docentes con los filtros seleccionados.
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import { useReporteDocente } from '../composables/useReporteDocente'

const {
  loading, error, datos, total,
  tiposTitulo, cargarTiposTitulo,
  buscar, descargarExcel,
} = useReporteDocente()

const filtros = reactive({
  anio: new Date().getFullYear(),
  periodo: '1',
  tipo_titulo: '',
  campos: ['TITULO'],
  campos_titulo: [],
})

const camposDocenteDisponibles = [
  { value: 'CI', label: 'CI' },
  { value: 'FECHA_NAC', label: 'Fecha de nacimiento' },
  { value: 'SEXO', label: 'Sexo' },
  { value: 'TITULO', label: 'Título (abrev.)' },
  { value: 'FECHA_NOMBRAMIENTO', label: 'Fecha de nombramiento' },
]

const camposTituloDisponibles = [
  { value: 'NOMBRE_TITULO', label: 'Nombre del título' },
  { value: 'UNIVERSIDAD', label: 'Universidad' },
  { value: 'PAIS', label: 'País' },
  { value: 'FECHA_TITULO', label: 'Fecha del título' },
  { value: 'NUMERO', label: 'Número' },
]

function etiqueta(campo) {
  return [...camposDocenteDisponibles, ...camposTituloDisponibles].find(c => c.value === campo)?.label || campo
}

const buscoAlMenosUnaVez = ref(false)
const generandoExcel = ref(false)

async function onBuscar() {
  buscoAlMenosUnaVez.value = true
  await buscar({ ...filtros })
}

async function onGenerarExcel() {
  generandoExcel.value = true
  try {
    await descargarExcel({ ...filtros })
  } catch (e) {
    // el error ya queda reflejado si lo quieres mostrar; opcionalmente usa `error` del composable
  } finally {
    generandoExcel.value = false
  }
}

// Si la vista previa ya fue generada, al marcar/desmarcar una columna
// (de docente o de título) se vuelve a consultar automáticamente,
// sin necesidad de volver a hacer clic en "Vista previa".
watch(
  () => [...filtros.campos, ...filtros.campos_titulo],
  async () => {
    if (buscoAlMenosUnaVez.value && !loading.value) {
      await buscar({ ...filtros })
    }
  }
)

onMounted(() => {
  cargarTiposTitulo()
})
</script>