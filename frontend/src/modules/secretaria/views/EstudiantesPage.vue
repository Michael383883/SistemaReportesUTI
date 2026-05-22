<!-- src/modules/secretaria/views/EstudiantesPage.vue -->
<template>
  <div class="sec-page">
    <div class="sec-page-header">
      <div>
        <h1 class="sec-title">Estudiantes</h1>
        <p class="sec-subtitle">Consulta y filtrado por periodo, materia y grupo</p>
      </div>
    </div>

    <ReportFilters
      v-model="filtros"
      searchPlaceholder="Buscar por nombre o SIS..."
      :showAño="true"
      :showPeriodo="true"
      :showMateria="true"
      :showGrupo="true"
      :materias="MATERIAS"
      @update:modelValue="cargar"
    />

    <EstudiantesTable
      :rows="filas"
      :loading="loading"
      @export="exportar"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import ReportFilters   from '../components/ReportFilters.vue'
import EstudiantesTable from '../components/EstudiantesTable.vue'
import { fetchEstudiantes, exportarExcel } from '../services/estudiantesService'

const MATERIAS = [
  'Cálculo I','Álgebra Lineal','Programación I','Estadística','Física I',
  'Economía','Contabilidad','Derecho Empresarial','Marketing','Base de Datos',
]


let filtros = reactive({ busqueda:'', año:'', periodo:'', materia:'', grupo:'' })
const filas   = ref([])
const loading = ref(true)

async function cargar() {
  loading.value = true
  try   { filas.value = await fetchEstudiantes({ ...filtros }) }
  finally { loading.value = false }
}

function exportar() { exportarExcel(filas.value) }

onMounted(cargar)
</script>

<style scoped>
.sec-page { display:flex; flex-direction:column; gap:18px; }
.sec-page-header { display:flex; justify-content:space-between; align-items:flex-start; }
.sec-title { font-size:20px; font-weight:700; color:#111827; margin:0; }
.sec-subtitle { font-size:13px; color:#6b7280; margin:2px 0 0; }
</style>