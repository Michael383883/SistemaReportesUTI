import { ref } from 'vue'
import axios from 'axios'

// Mismo patrón que useReporte: URL del backend + token Bearer de localStorage
const API_BASE = import.meta.env.VITE_API_URL
const BASE = `${API_BASE}/api/reporte-kardex-materia`

const authHeaders = () => ({
    Authorization: `Bearer ${localStorage.getItem('token')}`,
})

const PLANES_RESPALDO = [
    { cod_plan: '109401', abrev: 'ADM', nombre: 'ADMINISTRACIÓN DE EMPRESAS' },
    { cod_plan: '125091', abrev: 'COM', nombre: 'INGENIERÍA COMERCIAL' },
    { cod_plan: '126091', abrev: 'FIN', nombre: 'INGENIERÍA FINANCIERA' },
    { cod_plan: '089801', abrev: 'CCP', nombre: 'CONTADURÍA PÚBLICA' },
    { cod_plan: '059801', abrev: 'ECO', nombre: 'ECONOMÍA' },
]

// Arma la query: categorias[]=a&planes[]=b, booleanos como 1/0
function construirParams(f) {
    const p = new URLSearchParams()
    p.set('anio', String(f.anio))
    p.set('periodo', String(f.periodo))
        ; (f.categorias || []).forEach((c) => p.append('categorias[]', c))
        ; (f.planes || []).forEach((c) => p.append('planes[]', c))
    p.set('incluir_compartidas', f.incluirCompartidas ? '1' : '0')
    p.set('con_asignacion', f.conAsignacion ? '1' : '0')
    return p
}

// Extrae un mensaje legible de un error de Laravel (422, 404, 500...)
function mensajeError(e) {
    if (e?.message === 'RESPUESTA_INVALIDA') {
        return 'El servidor no devolvió el reporte. Revisa VITE_API_URL en tu .env.'
    }
    const data = e?.response?.data
    if (data?.errors) return Object.values(data.errors).flat().join(' ')
    if (data?.message) return data.message
    if (e?.response?.status === 401) return 'Tu sesión expiró. Vuelve a iniciar sesión.'
    return 'No se pudo conectar con el servidor.'
}

export function useKardexMateria() {
    const planes = ref([...PLANES_RESPALDO])
    const reporte = ref(null)      // respuesta de /listado
    const loading = ref(false)
    const descargando = ref(false)
    const error = ref('')          // error del listado (reemplaza la tabla)
    const errorAccion = ref('')    // error de descarga (se muestra como aviso)

    let token = 0 // descarta respuestas viejas si el usuario cambia filtros rápido

    async function cargarPlanes() {
        try {
            const { data } = await axios.get(`${BASE}/planes`, { headers: authHeaders() })
            if (Array.isArray(data) && data.length) planes.value = data
        } catch {
            /* se queda con la lista de respaldo */
        }
    }

    async function consultar(filtros) {
        const mio = ++token
        loading.value = true
        error.value = ''
        try {
            const { data } = await axios.get(`${BASE}/listado`, {
                params: construirParams(filtros),
                headers: authHeaders(),
            })
            if (mio !== token) return
            if (!data || typeof data !== 'object' || !Array.isArray(data.secciones)) {
                console.error('Respuesta inesperada de /listado:', data)
                throw new Error('RESPUESTA_INVALIDA')
            }
            reporte.value = data
        } catch (e) {
            if (mio !== token) return
            reporte.value = null
            error.value = mensajeError(e)
        } finally {
            if (mio === token) loading.value = false
        }
    }

    async function descargarExcel(filtros) {
        descargando.value = true
        errorAccion.value = ''
        try {
            const res = await axios.get(`${BASE}/excel`, {
                params: construirParams(filtros),
                headers: authHeaders(),
                responseType: 'blob',
            })

            const disp = res.headers?.['content-disposition'] || ''
            const m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disp)
            const nombre = m ? decodeURIComponent(m[1]) : `kardex_materias_${filtros.anio}_${filtros.periodo}.xlsx`

            const url = URL.createObjectURL(res.data)
            const a = document.createElement('a')
            a.href = url
            a.download = nombre
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            URL.revokeObjectURL(url)
            return true
        } catch (e) {
            // Con responseType 'blob' el error JSON llega como Blob: hay que leerlo
            if (e?.response?.data instanceof Blob) {
                try {
                    e.response.data = JSON.parse(await e.response.data.text())
                } catch { /* no era JSON */ }
            }
            errorAccion.value = mensajeError(e)
            return false
        } finally {
            descargando.value = false
        }
    }

    // Abre el PDF del documento (ruta protegida con auth:sanctum: hay que mandar el token).
    // Mismo endpoint que useClasificacion.verPdf. Lanza el error para que el componente lo muestre.
    //
    // IMPORTANTE: la pestaña se abre ANTES del await (dentro del clic del usuario);
    // si se abriera después, el navegador la bloquearía como pop-up.
    async function verPdf(idDocumento, modo = 'inline') {
        const ventana = window.open('', '_blank')
        try {
            const res = await axios.get(`${API_BASE}/api/clasificaciones/${idDocumento}/pdf`, {
                params: { modo },
                headers: authHeaders(),
                responseType: 'blob',
            })
            const blobUrl = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))

            if (ventana) ventana.location.href = blobUrl
            else window.open(blobUrl, '_blank')

            setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
        } catch (e) {
            if (ventana) ventana.close()
            throw e
        }
    }

    return { planes, reporte, loading, descargando, error, errorAccion, cargarPlanes, consultar, descargarExcel, verPdf }
}