import { ref } from 'vue'
import { useReporteClasificacion } from '../useReporteClasificacion'

// verPdfResolucionFn: función (nro, descargar) => Promise, distinta según
// se esté mostrando la versión normal o la de compartidos (cada una pega
// a un endpoint distinto). Se la inyecta el componente que use este composable.
export function usePdfMateria(verPdfResolucionFn) {
    const { verPdfClasificacion } = useReporteClasificacion()
    const loadingPdf = ref({})

    async function verPdfConFallback(nro, codDocente, descargar) {
        try {
            await verPdfResolucionFn(nro, descargar)
        } catch (e) {
            console.warn('[resoluciones] fallo, probando clasificación →', e.response?.status, e.response?.data)
            try {
                await verPdfClasificacion(nro, codDocente, descargar)
            } catch (e2) {
                let backendMsg = null
                try {
                    if (e2.response?.data instanceof Blob) {
                        backendMsg = JSON.parse(await e2.response.data.text())
                    } else {
                        backendMsg = e2.response?.data
                    }
                } catch (_) { }
                console.error('[clasificacion] también falló →', e2.response?.status, backendMsg, e2.config?.url)
                alert('No se encontró el PDF en ninguna de las dos fuentes.')
            }
        }
    }

    async function handleVer(m, codDocente) {
        loadingPdf.value[m.nro] = true
        try {
            await verPdfConFallback(m.resolucion, codDocente, false)
        } finally {
            loadingPdf.value[m.nro] = false
        }
    }

    async function handleDescargar(m, codDocente) {
        loadingPdf.value[m.nro] = true
        try {
            await verPdfConFallback(m.resolucion, codDocente, true)
        } finally {
            loadingPdf.value[m.nro] = false
        }
    }

    return { loadingPdf, handleVer, handleDescargar }
}