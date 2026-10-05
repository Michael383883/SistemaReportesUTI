import { computed } from 'vue'
import { useClasificacion } from './useClasificacion'

const NO_REGENTA_LABEL = 'NO REGENTA MATERIA EN LA FCE'

/**
 * Composable de solo-agregación: no llama a rutas nuevas del backend.
 * Reutiliza useClasificacion() para:
 *   1) Agrupar el listado plano (una fila por CLASIFICACION_DOCENTE) en un
 *      listado por documento, con el array de docentes vinculados.
 *   2) obtenerCompleto(doc): trae TODO lo de un documento llamando show()
 *      una vez por cada ID_CLASIFICACION_DOCENTE, y arma un único objeto
 *      "initial" listo para <ClasificacionForm :initial="...">.
 *
 * @param {ReturnType<typeof useClasificacion>} [clasificacionInstance]
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
     */
    async function obtenerCompleto(doc) {
        if (!doc?.docentes?.length) {
            throw new Error('El documento no tiene docentes vinculados')
        }

        // show() trae, para cada docente: sus materias, su título propio y
        // (repetidas en cada respuesta) las referencias del documento —
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
                const esNoRegenta = !m.COD_MATERIA && m.NOMBRE_MATERIA === NO_REGENTA_LABEL

                materias.push({
                    cod_materia: m.COD_MATERIA,
                    nombre_materia: m.NOMBRE_MATERIA,
                    cod_plan: m.COD_PLAN,
                    nota: m.NOTA,
                    grupo: m.GRUPO,
                    detalle: m.DETALLE,
                    manual: !m.COD_MATERIA,
                    docente: {
                        cod_docente: d.COD_DOCENTE,
                        nombre_docente: d.NOMBRE_DOCENTE,
                        apellidos: d.APELLIDOS ?? '',
                        nombres: d.NOMBRES ?? '',
                    },
                    // Marca que esta materia YA existe en el backend: el
                    // borrado pide confirmación SOLO en estas.
                    yaRegistrada: true,
                    // FIX: sin esta bandera, al editar un documento "No regenta"
                    // el check de MateriasCard no se reconocía como marcado.
                    ...(esNoRegenta ? { esNoRegenta: true } : {}),
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
                    // update() solo reemplaza el título del docente principal.
                    // El de un hermano se muestra solo como información.
                    otrosTitulos.push({ docente: d.NOMBRE_DOCENTE, ...t })
                }
            }
        })

        return {
            idClasificacionPrincipal: doc.docentes[0].ID_CLASIFICACION_DOCENTE,
            otrosTitulos,
            initial: {
                cod_docente: principal.COD_DOCENTE,
                // FIX: en edición el composable de búsqueda no tiene docente
                // seleccionado; con esto el form puede mostrarlo y asignarlo
                // a las materias nuevas.
                docente_principal: {
                    codigo: principal.COD_DOCENTE,
                    apellidos: (principal.APELLIDOS ?? '').trim(),
                    nombres: (principal.NOMBRES ?? '').trim(),
                },
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