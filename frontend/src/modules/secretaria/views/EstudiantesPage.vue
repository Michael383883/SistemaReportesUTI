<template>
    <div class="p-7 max-w-6xl mx-auto">
        <div class="flex justify-between items-start flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Estudiantes Inscritos</h1>
                <p class="text-gray-500 text-sm mt-1">
                    Gestión · Período {{ filtros.periodo }} / {{ filtros.anio }}
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="bg-blue-100 text-blue-700 font-semibold text-[13px] px-4 py-2 rounded-full">
                    🎓 {{ total }} estudiantes
                </span>
                <button
                    type="button"
                    class="bg-green-100 hover:bg-green-200 text-green-700 font-semibold text-[13px] px-4 py-2 rounded-full disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="cargando"
                    @click="exportarExcel"
                >
                    ⬇ Excel
                </button>
            </div>
        </div>

        <EstudiantesFiltros v-model="filtros" @limpiar="limpiarFiltros" />

        <p v-if="error" class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
            {{ error }}
        </p>

        <EstudiantesTabla :estudiantes="estudiantesFiltrados" :cargando="cargando" />

        <div v-if="totalPages > 1" class="flex items-center justify-center gap-4 mt-6">
            <button
                class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="page === 1"
                @click="irPaginaAnterior"
            >
                ← Anterior
            </button>
            <span class="text-sm text-gray-500">Página {{ page }} de {{ totalPages }}</span>
            <button
                class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="page === totalPages"
                @click="irPaginaSiguiente"
            >
                Siguiente →
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import EstudiantesFiltros from '../components/EstudiantesFiltros.vue'
import EstudiantesTabla from '../components/EstudiantesTabla.vue'
import estudiantesInscritosService, {
    ANIO_ACTUAL,
    PERIODO_ACTUAL,
} from '../services/estudiantesInscritosService.js'

// ✅ Cambio principal: reactive → ref
const filtros = ref({
    anio: ANIO_ACTUAL,
    periodo: PERIODO_ACTUAL,
    plan: '',
    nivel: '',
    busqueda: '',
})

const estudiantes = ref([])
const total = ref(0)
const cargando = ref(false)
const error = ref(null)

const page = ref(1)
const perPage = ref(100)
const totalPages = ref(0)

// ✅ Actualizar acceso a filtros
async function cargarEstudiantes() {
    cargando.value = true
    error.value = null

    try {
        const resp = await estudiantesInscritosService.getInscritos({
            anio: filtros.value.anio,
            periodo: filtros.value.periodo,
            plan: filtros.value.plan || null,
            nivel: filtros.value.nivel || null,
            page: page.value,
            perPage: perPage.value,
        })

        estudiantes.value = resp.data
        total.value = resp.total
        totalPages.value = resp.totalPages
    } catch (e) {
        error.value = e.message || 'Ocurrió un error al cargar los estudiantes.'
        estudiantes.value = []
        total.value = 0
    } finally {
        cargando.value = false
    }
}

// ✅ Actualizar acceso a filtros
const estudiantesFiltrados = ref([])

function aplicarBusquedaLocal() {
    const termino = filtros.value.busqueda.trim().toLowerCase()

    if (!termino) {
        estudiantesFiltrados.value = estudiantes.value
        return
    }

    estudiantesFiltrados.value = estudiantes.value.filter((est) =>
        est.estudiante.toLowerCase().includes(termino) ||
        String(est.codEstudiante).toLowerCase().includes(termino)
    )
}

// ✅ Actualizar watches
watch(estudiantes, aplicarBusquedaLocal)
watch(() => filtros.value.busqueda, aplicarBusquedaLocal)

watch(
    () => [filtros.value.anio, filtros.value.periodo, filtros.value.plan, filtros.value.nivel],
    () => {
        page.value = 1
        cargarEstudiantes()
    }
)

watch(page, cargarEstudiantes)

function irPaginaAnterior() {
    if (page.value > 1) page.value -= 1
}

function irPaginaSiguiente() {
    if (page.value < totalPages.value) page.value += 1
}

// ✅ Actualizar limpiarFiltros
function limpiarFiltros() {
    filtros.value = {
        anio: ANIO_ACTUAL,
        periodo: PERIODO_ACTUAL,
        plan: '',
        nivel: '',
        busqueda: '',
    }
}

// ✅ Actualizar exportarExcel
async function exportarExcel() {
    cargando.value = true
    try {
        const resp = await estudiantesInscritosService.getInscritosCompleto({
            anio: filtros.value.anio,
            periodo: filtros.value.periodo,
            plan: filtros.value.plan || null,
            nivel: filtros.value.nivel || null,
        })

        const filas = resp.data.map((e) => ({
            Carrera: e.siglaPlan,
            Nivel: e.nivel,
            Materia: e.nombreMateria,
            Grupo: e.grupo,
            Codigo: e.codEstudiante,
            Estudiante: e.estudiante,
        }))

        descargarComoCsv(filas, `estudiantes_inscritos_${filtros.value.anio}_${filtros.value.periodo}.csv`)
    } catch (e) {
        error.value = e.message || 'Ocurrió un error al exportar.'
    } finally {
        cargando.value = false
    }
}

function descargarComoCsv(filas, nombreArchivo) {
    if (!filas.length) return

    const encabezados = Object.keys(filas[0])
    const lineas = [
        encabezados.join(','),
        ...filas.map((fila) =>
            encabezados.map((campo) => `"${String(fila[campo] ?? '').replace(/"/g, '""')}"`).join(',')
        ),
    ]

    const blob = new Blob([lineas.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = nombreArchivo
    enlace.click()
    URL.revokeObjectURL(url)
}

onMounted(cargarEstudiantes)
</script>