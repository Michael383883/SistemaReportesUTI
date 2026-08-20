import { computed } from 'vue'

function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

// Extrae el código numérico de materia para poder comparar "más alto".
// Ajustá esto si el código viene en un campo propio (ej. m.codigo_materia)
// en vez de venir pegado al nombre (ej. "1302028 COSTOS II").
function codigoMateriaNum(m) {
    const fuente = norm(m.codigo_materia) || norm(m.materia) || norm(m.nombre_materia)
    const match = fuente.match(/^\d+/)
    return match ? parseInt(match[0], 10) : 0
}

// Única fuente de verdad para detectar qué materias son "hijas" de un compartido.
// Antes este algoritmo (PASO 1 y PASO 2) estaba copiado dos veces:
// una vez en ReporteTabla.vue (para pintar el badge "Compartido")
// y otra vez en ReporteTablaCom.vue (para agrupar filas).
//
// Acá se calcula UNA sola vez y se exponen dos vistas de ese mismo resultado:
// - hijasIndices: Set de índices que son hijas (para el modo plano, con badge)
// - filasAgrupadas: filas con las hermanas colgadas del padre (para el modo agrupado)
export function useAgrupacionCompartidos(materiasRef) {
    const base = computed(() => {
        const materias = materiasRef.value ?? []

        // PASO 1: semestre regular (1 y 2), tabla GRUPOS_COMPARTIDOS
        // (comp='0' padre, comp='1' hija), agrupado por orden_comparte + gestión.
        const porClave = new Map()
        materias.forEach((m, idx) => {
            const orden = norm(m.orden_comparte)
            if (!orden) return
            const clave = `${orden}__${norm(m.gestion)}`
            if (!porClave.has(clave)) porClave.set(clave, [])
            porClave.get(clave).push(idx)
        })

        const hermanasDe = new Map()
        const usadaComoHermana = new Array(materias.length).fill(false)

        for (const [, indices] of porClave) {
            if (indices.length < 2) continue
            const origenes = indices.filter(i => norm(materias[i].comp) === '0')
            const derivadas = indices.filter(i => norm(materias[i].comp) === '1')

            if (origenes.length === 1 && derivadas.length >= 1) {
                hermanasDe.set(origenes[0], derivadas)
                derivadas.forEach(i => { usadaComoHermana[i] = true })
            } else {
                const pares = Math.min(origenes.length, derivadas.length)
                for (let p = 0; p < pares; p++) {
                    hermanasDe.set(origenes[p], [derivadas[p]])
                    usadaComoHermana[derivadas[p]] = true
                }
            }
        }

        // PASO 2: verano/invierno (3 y 4), sin orden_comparte.
        // La HIJA es la que tiene algo marcado en la columna "compartido".
        // El PADRE es el que NO tiene nada marcado ahí.
        //
        // Si en el mismo grupo compiten dos candidatos a padre (ambos con
        // "compartido" vacío), gana el de código de materia más alto:
        // ese se queda con todas las hijas, el otro queda como padre solo
        // (no se le cuelga nada, sigue como fila normal).
        const tieneCompartidoMarcado = (m) => norm(m.compartido) !== ''
        const porGestionVI = new Map()
        materias.forEach((m, idx) => {
            if (usadaComoHermana[idx] || hermanasDe.has(idx)) return
            if (norm(m.orden_comparte)) return // ya resuelto en el PASO 1
            const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
            if (!esVI) return
            const clave = norm(m.gestion)
            if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
            porGestionVI.get(clave).push(idx)
        })

        for (const [, indices] of porGestionVI) {
            if (indices.length < 2) continue
            const padres = indices.filter(i => !tieneCompartidoMarcado(materias[i]))
            const hijas = indices.filter(i => tieneCompartidoMarcado(materias[i]))
            if (padres.length === 0 || hijas.length === 0) continue

            // Desempate: si hay más de un candidato a padre, gana el de
            // código de materia más alto. Los demás quedan solos.
            let padreGanador = padres[0]
            if (padres.length > 1) {
                padreGanador = padres.reduce((mejor, actual) =>
                    codigoMateriaNum(materias[actual]) > codigoMateriaNum(materias[mejor]) ? actual : mejor
                , padres[0])
            }

            hermanasDe.set(padreGanador, hijas)
            hijas.forEach(i => { usadaComoHermana[i] = true })
        }

        return { materias, hermanasDe, usadaComoHermana }
    })

    // Modo plano (ReporteTabla): solo interesa saber qué índices son hijas
    const hijasIndices = computed(() => {
        const { usadaComoHermana } = base.value
        const set = new Set()
        usadaComoHermana.forEach((esHermana, idx) => { if (esHermana) set.add(idx) })
        return set
    })

    // Modo agrupado (ReporteTablaCom): filas renumeradas, con hermanas colgadas del padre
    const filasAgrupadas = computed(() => {
        const { materias, hermanasDe, usadaComoHermana } = base.value
        const filas = []
        let contador = 0
        materias.forEach((m, idx) => {
            if (usadaComoHermana[idx]) return
            contador++
            filas.push({
                nro: contador,
                principal: m,
                esHija: false,
                hermanas: (hermanasDe.get(idx) || []).map(i => materias[i]),
            })
        })
        return filas
    })

    return { hijasIndices, filasAgrupadas }
}