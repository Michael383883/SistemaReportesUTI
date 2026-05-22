// src/modules/secretaria/services/estudiantesService.js

// ─────────────────────────────────────────────
// Constantes
// ─────────────────────────────────────────────
const NOMBRES = [
    'Ana Torres',
    'Carlos Quispe',
    'Luisa Mamani',
    'Pedro Flores',
    'Valeria Rojas',
    'Miguel Condori',
    'Sofia Vargas',
    'Diego Huanca',
    'Camila Pérez',
    'Andrés Lima',
    'Fernanda Cruz',
    'José Choque',
    'María Sánchez',
    'Ricardo Apaza',
    'Gabriela Vega',
]

const MATERIAS = [
    'Cálculo I',
    'Álgebra Lineal',
    'Programación I',
    'Estadística',
    'Física I',
    'Economía',
    'Contabilidad',
    'Derecho Empresarial',
    'Marketing',
    'Base de Datos',
]

// ─────────────────────────────────────────────
// Data Mock
// ─────────────────────────────────────────────
const _estudiantes = Array.from({ length: 60 }, (_, i) => ({
    id: i + 1,
    sis: `SIS-${String(10000 + i).padStart(5, '0')}`,
    nombre: NOMBRES[i % NOMBRES.length],
    año: 2022 + (i % 4),
    periodo: i % 2 === 0 ? '2025-I' : '2025-II',
    materia: MATERIAS[i % MATERIAS.length],
    grupo: `G${(i % 3) + 1}`,
    estado: i % 7 === 0 ? 'Baja' : 'Activo',
}))

// ─────────────────────────────────────────────
// Obtener estudiantes
// ─────────────────────────────────────────────
export async function fetchEstudiantes(filtros = {}) {
    await delay(350)

    let data = [..._estudiantes]

    if (filtros.año) {
        data = data.filter(e => String(e.año) === String(filtros.año))
    }

    if (filtros.periodo) {
        data = data.filter(e => e.periodo === filtros.periodo)
    }

    if (filtros.materia) {
        data = data.filter(e => e.materia === filtros.materia)
    }

    if (filtros.grupo) {
        data = data.filter(e => e.grupo === filtros.grupo)
    }

    if (filtros.estado) {
        data = data.filter(e => e.estado === filtros.estado)
    }

    if (filtros.busqueda) {
        const q = filtros.busqueda.toLowerCase()

        data = data.filter(
            e =>
                e.nombre.toLowerCase().includes(q) ||
                e.sis.toLowerCase().includes(q)
        )
    }

    return data
}

// ─────────────────────────────────────────────
// Exportar CSV / Excel simple
// ─────────────────────────────────────────────
export function exportarExcel(filas = []) {
    const csv = [
        'SIS,Nombre,Año,Periodo,Materia,Grupo,Estado',
        ...filas.map(
            e =>
                `${e.sis},${e.nombre},${e.año},${e.periodo},${e.materia},${e.grupo},${e.estado}`
        ),
    ].join('\n')

    const blob = new Blob([csv], {
        type: 'text/csv;charset=utf-8;',
    })

    const url = URL.createObjectURL(blob)

    const a = document.createElement('a')
    a.href = url
    a.download = 'estudiantes.csv'
    a.click()

    URL.revokeObjectURL(url)
}

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
}