<template>
  <div class="px-6 py-1 w-full">
    <div class="mb-6">
      <h1 class="text-xl font-bold text-slate-800">Asignar este documento a otros docentes</h1>
      <p class="text-sm text-slate-500">
        Documento: <strong>{{ documento.tipo_documento || documento.detalle_general }}</strong>
        ({{ documento.gestion }}/{{ documento.periodo }})
      </p>
    </div>

    <ResultadoAsignacionResolucion
      v-if="fase === 'resultado'"
      :grupos-actualizados="gruposActualizados"
      :ultimas-asignadas="ultimasAsignadas"
      :resolucion-nro="documento.tipo_documento || 'Documento #' + documento.idDocumento"
      :resolucion-anio-periodo-label="`${documento.gestion} / ${documento.periodo}`"
      :docente-asignado-codigo="docenteAsignadoCodigo"
      :docente-asignado-nombre="docenteAsignadoNombre"
      @ver-reporte="verReporte"
      @asignar-otra="asignarOtraMas"
      @ir-a-listado="$router.push({ name: 'clasificaciones-listado' })"
    />

    <template v-else>
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-4 bg-slate-900">
          <h3 class="text-sm font-semibold text-white m-0">Buscá el docente</h3>
        </div>
        <div class="px-5 py-4">
          <DocenteSearch
            v-model:searchQuery="searchQuery"
            v-model:dropdownOpen="dropdownOpen"
            :filteredDocentes="filteredDocentes"
            :selectedDocente="selectedDocente"
            :loading="loadingDocentes"
            @select="onSeleccionarDocente"
            @clear="onLimpiarDocente"
          />
        </div>
      </div>

      <div v-if="selectedDocente" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-slate-100">
          <p class="text-xs font-semibold text-slate-800 uppercase tracking-wider m-0">
            Materias dictadas: {{ selectedDocente.apellidos }} {{ selectedDocente.nombres }}
          </p>
        </div>
        <div class="p-5">
          <MateriasAsignarTabla
            v-if="reporte"
            :materias="materiasFiltradas"
            :resolucion-activa="true"
            :marcadas-keys="materiasMarcadas.map(m => m.key)"
            :docente-cod="docenteCodActual"
            @toggle="(m) => toggleMateria(selectedDocente, m)"
            @tipo-ingreso-change="(m) => actualizarTipoIngreso(selectedDocente, m)"
          />
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 bg-slate-900">
          <h3 class="text-sm font-semibold text-white m-0">Confirmá la asignación</h3>
        </div>
        <MateriasMarcadasResumen
          :materias="materiasMarcadas"
          :guardando="guardando"
          :error="errorGuardado || errorLocal"
          @quitar="quitarMateria"
          @limpiar-todo="limpiarTodo"
          @terminar="handleTerminar"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DocenteSearch from '../../docentes/components/DocenteSearch.vue'
import { useDocentes } from '../../docentes/composables/useDocentes'
import { useReporte } from '../../reportes/composables/useReporte'
import MateriasAsignarTabla from '../../resoluciones/components/MateriasAsignarTabla.vue'
import MateriasMarcadasResumen from '../../resoluciones/components/MateriasMarcadasResumen.vue'
import ResultadoAsignacionResolucion from '../../resoluciones/components/ResultadoAsignacionResolucion.vue'
import { useAsignacionDocumento } from '../composables/useAsignacionDocumento'

const route = useRoute()
const router = useRouter()

const documento = {
  idDocumento: Number(route.query.documento),
  gestion: route.query.gestion,
  periodo: route.query.periodo,
  tipo_documento: route.query.tipo_documento,
  detalle_general: route.query.detalle_general,
}

const { loading: loadingDocentes, searchQuery, dropdownOpen, filteredDocentes,
        selectedDocente, fetchDocentes, selectDocente, clearSelection } = useDocentes()
fetchDocentes()

const { reporte, generarReporte, limpiarReporte } = useReporte()

const docenteCodActual = computed(() =>
  selectedDocente.value ? (selectedDocente.value.cod_docente ?? selectedDocente.value.CODIGO ?? selectedDocente.value.codigo) : null
)

function onSeleccionarDocente(doc) {
  selectDocente(doc)
  const codigo = doc.codigo ?? doc.CODIGO
  if (codigo) generarReporte(codigo)
}
function onLimpiarDocente() { clearSelection(); limpiarReporte() }

// Solo materias de la misma gestión/periodo del documento (igual que el auto-filtro de resolución)
const materiasFiltradas = computed(() => {
  return (reporte.value?.materias ?? []).filter(m => {
    const [anio, gest] = String(m.gestion ?? '').split('/')
    return (anio?.trim() === String(documento.gestion)) && (gest?.trim() === String(documento.periodo))
  })
})

const {
  materiasMarcadas, guardando, errorGuardado,
  toggleMateria, actualizarTipoIngreso, quitarMateria, limpiarTodo,
  confirmarAsignacion, aplicarEnGrupos,
} = useAsignacionDocumento(documento.idDocumento, documento)

const errorLocal = ref('')
const fase = ref('formulario')
const ultimasAsignadas = ref([])
const gruposActualizados = ref([])
const docenteAsignadoCodigo = ref('')
const docenteAsignadoNombre = ref('')

function normalizarGrupo(g) {
  return {
    anio: g.anio ?? g.ANIO ?? '', periodo: g.periodo ?? g.PERIODO ?? '',
    plan: g.plan ?? g.PLAN ?? '', materia: g.materia ?? g.MATERIA ?? '',
    grupo: g.grupo ?? g.GRUPO ?? '', docente: g.docente ?? g.DOCENTE ?? '',
    tipo: g.tipo ?? g.TIPO ?? '', tipoIngreso: g.tipoIngreso ?? g.TIPO_INGRESO ?? '',
    resolucion: g.resolucion ?? g.RESOLUCION ?? '', designacion: g.designacion ?? g.DESIGNACION ?? '',
  }
}

async function handleTerminar() {
  errorLocal.value = ''
  try {
    ultimasAsignadas.value = [...materiasMarcadas.value]
    docenteAsignadoCodigo.value = docenteCodActual.value ?? ''
    docenteAsignadoNombre.value = selectedDocente.value
      ? `${selectedDocente.value.nombres ?? ''} ${selectedDocente.value.apellidos ?? ''}`.trim()
      : ''

    await confirmarAsignacion()
    const resultado = await aplicarEnGrupos()
    gruposActualizados.value = (resultado?.grupos ?? []).map(normalizarGrupo)
    fase.value = 'resultado'
  } catch (e) {
    errorLocal.value = e?.message ?? 'Error al guardar la asignación.'
  }
}

function asignarOtraMas() {
  limpiarTodo(); onLimpiarDocente(); gruposActualizados.value = []; fase.value = 'formulario'
}
function verReporte(g) {
  const ruta = router.resolve({ name: 'reporte', query: { codigo: g.docente, anio: g.periodo ? `${g.anio}/${g.periodo}` : g.anio } })
  window.open(ruta.href, '_blank')
}
</script>