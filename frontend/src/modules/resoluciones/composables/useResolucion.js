// composables/useResolucion.js
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export function useResolucion() {
    // ── Estado ──────────────────────────────────────────────
    const paso = ref(1) // 1: subir PDF | 2: formulario | 3: preview resultados

    // PDF
    const archivo = ref(null)  // File object
    const pdfUrl = ref(null)  // object URL para preview
    const pdfNombre = ref('')

    // Formulario
    const numero = ref('')  // "RR Nº 034/2026"
    const descripcion = ref('')  // "Del 27 febrero al 07 julio de 2026..."
    const fecha = ref('')  // "2026-02-27"
    const gestion = ref('')  // "I/2026"

    // Resultados del backend
    const resultado = ref(null)  // respuesta completa del endpoint /procesar

    // UI
    const cargando = ref(false)
    const error = ref(null)
    const mensajeOk = ref('')

    // ── Computed ─────────────────────────────────────────────
    const hayResultado = computed(() => resultado.value !== null)
    const totalCarreras = computed(() => resultado.value?.data?.carreras?.length ?? 0)
    const totalMaterias = computed(() =>
        resultado.value?.data?.carreras?.reduce(
            (acc, c) => acc + (c.materias?.length ?? 0), 0
        ) ?? 0
    )

    // ── Validación PDF ────────────────────────────────────────
    const MAX_MB = 5

    function seleccionarPdf(file) {
        error.value = null
        if (!file) return false

        if (file.type !== 'application/pdf') {
            error.value = 'Solo se permiten archivos PDF.'
            return false
        }

        const mb = file.size / 1024 / 1024
        if (mb > MAX_MB) {
            error.value = `El archivo supera el límite de ${MAX_MB} MB.`
            return false
        }

        if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)

        archivo.value = file
        pdfNombre.value = file.name
        pdfUrl.value = URL.createObjectURL(file)
        return true
    }

    function limpiarPdf() {
        if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
        archivo.value = null
        pdfUrl.value = null
        pdfNombre.value = ''
    }

    // ── Navegación entre pasos ────────────────────────────────
    function irAPaso(n) { paso.value = n }

    function siguientePaso() {
        error.value = null
        if (paso.value === 1) {
            if (!archivo.value) { error.value = 'Selecciona un PDF primero.'; return }
            paso.value = 2
        } else if (paso.value === 2) {
            if (!numero.value.trim()) { error.value = 'Ingresa el número de resolución.'; return }
            if (!descripcion.value.trim()) { error.value = 'Ingresa una descripción.'; return }
            procesarPdf()
        }
    }

    function reiniciar() {
        limpiarPdf()
        numero.value = ''
        descripcion.value = ''
        fecha.value = ''
        gestion.value = ''
        resultado.value = null
        error.value = null
        mensajeOk.value = ''
        cargando.value = false
        paso.value = 1
    }

    // ── POST /procesar ────────────────────────────────────────
    async function procesarPdf() {
        cargando.value = true
        error.value = null

        try {
            const form = new FormData()
            form.append('pdf', archivo.value)
            form.append('numero', numero.value.trim())
            form.append('descripcion', descripcion.value.trim())
            if (fecha.value) form.append('fecha', fecha.value)
            if (gestion.value) form.append('gestion', gestion.value)

            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/procesar`,
                form,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )

            if (!data.success) throw new Error(data.message ?? 'Error al procesar el PDF.')

            resultado.value = data  // guarda { success, pdf_path, data: { carreras }, meta }
            paso.value = 3
        } catch (e) {
            console.error('procesarPdf error:', e)
            console.error('response:', e.response?.data)
            error.value = e.response?.data?.message ?? e.message ?? 'Error inesperado.'
        } finally {
            cargando.value = false
        }
    }

    // ── POST /guardar ─────────────────────────────────────────
    async function migrarResolucion() {
        if (!hayResultado.value) return
        cargando.value = true
        error.value = null
        mensajeOk.value = ''

        try {
            const { data } = await axios.post(`${API_BASE}/resoluciones/guardar`, {
                numero: numero.value.trim(),
                descripcion: descripcion.value.trim(),
                fecha: fecha.value || null,
                gestion: gestion.value || null,
                pdf_path: resultado.value?.pdf_path ?? null,
                data: {
                    carreras: resultado.value?.data?.carreras ?? [],
                },
            })

            if (!data.success) throw new Error(data.message ?? 'Error al guardar.')

            mensajeOk.value = data.message ?? 'Resolución migrada correctamente.'
        } catch (e) {
            error.value = e.response?.data?.message ?? e.message ?? 'Error al guardar.'
        } finally {
            cargando.value = false
        }
    }

    // ── Exponer ───────────────────────────────────────────────
    return {
        // estado
        paso,
        archivo,
        pdfUrl,
        pdfNombre,
        numero,
        descripcion,
        fecha,
        gestion,
        resultado,
        cargando,
        error,
        mensajeOk,
        // computed
        hayResultado,
        totalCarreras,
        totalMaterias,
        // métodos
        seleccionarPdf,
        limpiarPdf,
        irAPaso,
        siguientePaso,
        procesarPdf,
        migrarResolucion,
        reiniciar,
    }
}