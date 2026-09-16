import { computed } from 'vue'
import { useClasificacion } from './useClasificacion'

/**
 * Composable de solo-agregación: no llama a rutas nuevas del backend.
 * Reutiliza useClasificacion() para:
 *   1) Agrupar el listado plano (una fila por CLASIFICACION_DOCENTE) en un
 *      listado por documento (una fila por CLASIFICACION_DOCUMENTO), con el
 *      array de docentes vinculados a cada uno. Sirve para la tabla.
 *   2) obtenerCompleto(doc): trae TODO lo de un documento (materias +
 *      título de cada docente vinculado, referencias, datos generales)
 *      llamando show() una vez por cada ID_CLASIFICACION_DOCENTE del
 *      documento, y arma un único objeto "initial" listo para pasarle a
 *      <ClasificacionForm :initial="...">.
 *
 * @param {ReturnType<typeof useClasificacion>} [clasificacionInstance]
 *        Pasa la MISMA instancia que ya usa la vista (la que llama a
 *        listar()) para que `documentos` refleje lo que está en pantalla.
 *        Si no se pasa, crea una instancia propia (útil para el modal de
 *        edición, que no necesita compartir el listado general).
 */
export function useDocumentos(clasificacionInstance) {
    const clasificacion = clasificacionInstance ?? useClasificacion()

    const documentos = computed(() => {
        const mapa = new Map()

        for (const c of clasificacion.listado.value) {
            if (!mapa.has(c.ID_DOCUMENTO)) {
                mapa.set(c.ID_DOCUMENTO, {
                    ID_DOCUMENTO: c.ID_DOCUMENTO,
                    TIPO_DOCUMENTO: c.TIPO_DOCUMENTO,
                    // OJO: el SELECT de index() en el backend no trae
                    // DETALLE_GENERAL hoy en día, así que esto puede venir
                    // undefined hasta que se agregue al controlador (ver
                    // nota al pie del chat). No es obligatorio para que
                    // esta vista funcione.
                    DETALLE_GENERAL: c.DETALLE_GENERAL ?? null,
                    CATEGORIA: c.CATEGORIA,
                    NIVEL: c.NIVEL,
                    GESTION: c.GESTION,
                    PERIODO: c.PERIODO,
                    NOMBRE_ARCHIVO: c.NOMBRE_ARCHIVO,
                    FECHA_REGISTRO: c.FECHA_REGISTRO,
                    docentes: [],
                })
            }

            mapa.get(c.ID_DOCUMENTO).docentes.push({
                ID_CLASIFICACION_DOCENTE: c.ID_CLASIFICACION_DOCENTE,
                COD_DOCENTE: c.COD_DOCENTE,
                NOMBRE_DOCENTE: c.NOMBRE_DOCENTE,
                APELLIDOS: c.APELLIDOS ?? '',
                NOMBRES: c.NOMBRES ?? '',
            })
        }

        return Array.from(mapa.values()).sort((a, b) => b.ID_DOCUMENTO - a.ID_DOCUMENTO)
    })

    /**
     * @param {{ docentes: {ID_CLASIFICACION_DOCENTE:number, COD_DOCENTE:number, NOMBRE_DOCENTE:string}[] }} doc
     *        Una fila de `documentos` (ver arriba).
     * @returns {Promise<{
     *   idClasificacionPrincipal: number,
     *   otrosTitulos: Array,
     *   initial: object,
     * }>}
     */
    async function obtenerCompleto(doc) {
        if (!doc?.docentes?.length) {
            throw new Error('El documento no tiene docentes vinculados')
        }

        // show() ya trae, para cada docente: sus materias, su título propio
        // y (repetidas en cada respuesta) las referencias del documento —
        // por eso solo usamos las de la primera respuesta.
        const respuestas = await Promise.all(
            doc.docentes.map(d => clasificacion.obtener(d.ID_CLASIFICACION_DOCENTE))
        )

        const principal = respuestas[0]
        const materias = []
        let tituloPrincipal = null
        const otrosTitulos = []

        respuestas.forEach((cabecera, i) => {
            const d = doc.docentes[i]

            for (const m of cabecera.materias || []) {
                materias.push({
                    cod_materia: m.COD_MATERIA,
                    nombre_materia: m.NOMBRE_MATERIA,
                    cod_plan: m.COD_PLAN,
                    nota: m.NOTA,
                    grupo: m.GRUPO,
                    detalle: m.DETALLE,
                    // MateriasCard ya soporta materias de "otros docentes"
                    // (así es como el backend distingue al hermano dueño).
                    docente: {
                        cod_docente: d.COD_DOCENTE,
                        nombre_docente: d.NOMBRE_DOCENTE,
                        // 👈 FIX real: antes leía m.APELLIDOS / m.NOMBRES, que NUNCA vienen
                        // en la respuesta de show() porque esa query no junta DOCENTES.
                        // Ahora usa el docente `d` del loop actual (doc.docentes[i]), que
                        // es el mismo docente al que pertenecen TODAS las materias de esta
                        // respuesta (show() ya está filtrado por ID_CLASIFICACION_DOCENTE).
                        apellidos: d.APELLIDOS ?? '',
                        nombres: d.NOMBRES ?? '',
                    },
                    // 👈 FIX Bug 2: marca que esta materia YA existe en el
                    // backend (viene cargada al editar), para que el
                    // borrado pida confirmación SOLO en estas. Las
                    // materias que se agregan nuevas desde el buscador
                    // durante la edición no deben traer esta propiedad
                    // (o debe quedar en false).
                    yaRegistrada: true,
                })
            }

            const t = (cabecera.titulos || [])[0]
            if (t) {
                if (i === 0) {
                    tituloPrincipal = {
                        tipo_titulo: t.TIPO_TITULO,
                        universidad: t.UNIVERSIDAD,
                        pais: t.PAIS,
                        fecha_titulo: t.FECHA_TITULO,
                        nombre_titulo: t.NOMBRE_TITULO,
                        numero: t.NUMERO,
                        cod_docente: d.COD_DOCENTE,
                    }
                } else {
                    // El backend (update()) solo reemplaza el título del
                    // docente "principal" (el $id de la URL). El título de
                    // un hermano no se puede editar desde este mismo form,
                    // así que solo lo mostramos como información.
                    otrosTitulos.push({ docente: d.NOMBRE_DOCENTE, ...t })
                }
            }
        })

        return {
            idClasificacionPrincipal: doc.docentes[0].ID_CLASIFICACION_DOCENTE,
            otrosTitulos,
            initial: {
                cod_docente: principal.COD_DOCENTE,
                categoria: principal.CATEGORIA,
                nivel: principal.NIVEL,
                gestion: principal.GESTION,
                periodo: principal.PERIODO,
                tipo_documento: principal.TIPO_DOCUMENTO,
                detalle_general: principal.DETALLE_GENERAL,
                observacion: principal.OBSERVACION,
                observacion2: principal.OBSERVACION2,
                materias,
                referencias: (principal.referencias || []).map(r => ({
                    nro_referencia: r.NRO_REFERENCIA,
                    id_resolucion: r.ID_RESOLUCION,
                })),
                titulo: tituloPrincipal,
            },
        }
    }

    return { clasificacion, documentos, obtenerCompleto }
}