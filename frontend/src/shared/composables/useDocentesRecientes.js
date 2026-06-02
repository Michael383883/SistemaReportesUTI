import { ref } from 'vue'

const MAX = 10
const STORAGE_KEY = 'docentes_recientes'

const recientes = ref(
    JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
)

export function useDocentesRecientes() {
    function registrar(docente) {
        if (!docente) return
        const id = String(docente.docente || docente.codigo || '')
        if (!id) return

        const lista = recientes.value.filter(d => String(d.codigo) !== id)
        lista.unshift({
            codigo: id,
            nombre: docente.nombre_docente || docente.nombre || '',
            grado: docente.grado_academico || docente.grado || '',
            unidad: docente.unidad || '',
            horas: docente.horas_total ?? docente.horas ?? 0,
            fecha: new Date().toISOString()
        })
        recientes.value = lista.slice(0, MAX)
        localStorage.setItem(STORAGE_KEY, JSON.stringify(recientes.value))
    }

    function limpiar() {
        recientes.value = []
        localStorage.removeItem(STORAGE_KEY)
    }

    return { recientes, registrar, limpiar }
}