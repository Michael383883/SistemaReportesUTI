import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

// Acepta array (['a','b']) o string ('a') o undefined, y devuelve
// siempre el formato que espera el backend: "a,b" (o undefined si está vacío).
function serializarLista(valor) {
    if (Array.isArray(valor)) {
        return valor.length ? valor.join(',') : undefined
    }
    return valor || undefined
}

// Normaliza el nombre de una materia para comparar "igualito":
// quita espacios extra al inicio/fin, colapsa espacios múltiples y pasa a mayúsculas.
function normalizarNombreMateria(nombre) {
    return (nombre || '')
        .toString()
        .trim()
        .toUpperCase()
        .replace(/\s+/g, ' ')
}

const PLACEHOLDER_SIN_MATERIA = 'NO REGENTA MATERIA EN LA FCE'

// Marcas de agrupación que se recalculan cada vez que se arman los bloques
const MARCAS_GRUPO = ['N', 'NOMBRE_DOCENTE', 'INICIO_GRUPO', 'FILAS_GRUPO', 'FIN_GRUPO', 'INICIO_MATERIA', 'FILAS_MATERIA']

export function useReporteExcel() {
    const loading = ref(false)
    const error = ref(null)
    const preview = ref([])
    const gestionEtiqueta = ref('')
    const versionEtiqueta = ref('')
    const totalFilas = ref(0)

    // ── Docentes activos (para el botón "Solo Activos") ──
    const cargandoActivos = ref(false)
    const errorActivos = ref(null)
    const docentesActivos = ref([]) // [{ codigo, apellidos, nombres }, ...]
    const soloActivos = ref(false)
    const anioActivos = ref(new Date().getFullYear())
    const periodoActivos = ref(1)

    // Carga horaria real de TODOS los docentes activos (para completar materias/CH)
    const cargaActivos = ref([])
    // Preview original (sin completar con activos), para poder deshacer
    let previewSinCompletar = null

    // ── Asignar Carga Horaria (experimental) ──
    const cargandoCargaHoraria = ref(false)
    const errorCargaHoraria = ref(null)
    const cargaHorariaAsignada = ref(false) // true si el preview actual ya tiene CH asignada automáticamente

    // ── Combinar Materias ──
    const materiasCombinadas = ref(false) // true si el preview actual tiene materias combinadas
    let previewSinCombinar = null // snapshot para poder deshacer la combinación

    // ── Mostrar Referencias (CLASIFICACION_REFERENCIA en DETALLE) ──
    // El DETALLE se arma en el backend, así que este flag solo se guarda
    // como estado y se reenvía en cada previsualizar()/urlDescarga(); no hay
    // nada que transformar en el cliente sobre datos ya cargados.
    const mostrarReferencias = ref(false)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reportes/docentes-clasificados/preview
    // categoria y tipo_titulo pueden venir como array (multi-selección) o string
    async function previsualizar({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo, mostrar_referencias } = {}) {
        loading.value = true
        error.value = null
        cargaHorariaAsignada.value = false // un preview nuevo descarta cualquier CH asignada previamente
        materiasCombinadas.value = false   // un preview nuevo descarta cualquier combinación previa
        previewSinCombinar = null

        // Si no se pasa explícitamente, se usa el valor actual del ref (para
        // que llamadas internas de recarga no necesiten repetir el flag).
        const referenciasActivas = mostrar_referencias !== undefined
            ? mostrar_referencias
            : mostrarReferencias.value

        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/docentes-clasificados/preview`,
                {
                    params: {
                        gestion_desde: gestion_desde || undefined,
                        gestion_hasta: gestion_hasta || undefined,
                        periodo: periodo || undefined,
                        version: version || undefined,
                        categoria: serializarLista(categoria),
                        tipo_titulo: serializarLista(tipo_titulo),
                        mostrar_referencias: referenciasActivas ? 1 : undefined,
                    },
                    headers: authHeaders(),
                }
            )

            if (!data.ok) {
                error.value = data.error || 'No se pudo obtener la vista previa'
                preview.value = []
                previewSinCompletar = null
                return
            }

            preview.value = data.data
            previewSinCompletar = data.data
            gestionEtiqueta.value = data.gestion
            versionEtiqueta.value = data.version
            totalFilas.value = data.total_filas
            mostrarReferencias.value = referenciasActivas

            // Si "Solo Activos" sigue encendido, se vuelve a completar el
            // preview nuevo para que "Actualizar vista previa" no pierda
            // los docentes/materias agregados.
            if (soloActivos.value && cargaActivos.value.length) {
                aplicarCompletado()
            }
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la vista previa'
            preview.value = []
            previewSinCompletar = null
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/reportes/docentes-activos?anio=X&periodo=Y
    // Trae la lista de docentes que tienen materia asignada en esa gestión/periodo
    // (según HORARIOS2/GRUPOS), para poder filtrar el preview por "Solo Activos".
    async function cargarDocentesActivos({ anio, periodo } = {}) {
        cargandoActivos.value = true
        errorActivos.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/docentes-activos`,
                {
                    params: {
                        anio: anio ?? anioActivos.value,
                        periodo: periodo ?? periodoActivos.value,
                    },
                    headers: authHeaders(),
                }
            )
            docentesActivos.value = data.data || []
        } catch (e) {
            errorActivos.value = e?.response?.data?.message || 'No se pudo obtener la lista de docentes activos'
            docentesActivos.value = []
            throw e
        } finally {
            cargandoActivos.value = false
        }
    }

    // GET /api/reportes/carga-horaria-docentes?anio=X&periodo=Y
    // Trae, por docente, la carga horaria real (CH) de cada materia que dicta
    // en esa gestión/periodo (según HORARIOS2/GRUPOS/MATERIAS).
    async function obtenerCargaHorariaDocentes({ anio, periodo } = {}) {
        cargandoCargaHoraria.value = true
        errorCargaHoraria.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/carga-horaria-docentes`,
                {
                    params: {
                        anio: anio ?? anioActivos.value,
                        periodo: periodo ?? periodoActivos.value,
                    },
                    headers: authHeaders(),
                }
            )
            return data.data || []
        } catch (e) {
            errorCargaHoraria.value = e?.response?.data?.message || 'No se pudo obtener la carga horaria de los docentes'
            throw e
        } finally {
            cargandoCargaHoraria.value = false
        }
    }

    // ── Completar el preview con TODOS los docentes activos ──
    // - Docente activo sin ningún documento: se agrega con todas sus materias
    //   y CH; el resto de columnas queda vacío y la categoría es "SIN DOCUMENTO".
    // - Docente con documentos de solo algunas materias: conserva sus filas
    //   y se le agregan las materias que faltan (también "SIN DOCUMENTO").
    // - La CH se asigna a toda fila cuya materia coincide con la carga real.
    function completarConActivos(filasBase) {
        // 1) Agrupar el preview actual por docente (bloques)
        const bloquesPorDocente = new Map()
        let i = 0
        while (i < filasBase.length) {
            const f = filasBase[i]
            const n = f.INICIO_GRUPO ? (f.FILAS_GRUPO || 1) : 1
            const cod = String(f.COD_DOCENTE)
            if (!bloquesPorDocente.has(cod)) {
                bloquesPorDocente.set(cod, { nombre: f.NOMBRE_DOCENTE, filas: [] })
            }
            bloquesPorDocente.get(cod).filas.push(...filasBase.slice(i, i + n).map(x => ({ ...x })))
            i += n
        }

        // 2) Materias reales por docente: cod -> Map(materiaNormalizada -> {nombre, ch})
        const cargaPorDocente = new Map()
        for (const d of cargaActivos.value) {
            const m = new Map()
            for (const mat of (d.materias || [])) {
                const key = normalizarNombreMateria(mat.nom_materia)
                if (!key) continue
                const prev = m.get(key)
                m.set(key, {
                    nombre: mat.nom_materia,
                    ch: (prev?.ch || 0) + Number(mat.carga_horaria || 0),
                })
            }
            cargaPorDocente.set(String(d.codigo), m)
        }

        const filaSinDocumento = (cod, nombreMateria, ch) => ({
            COD_DOCENTE: cod,
            NOMBRE_MATERIA: nombreMateria,
            CH: ch,
            DETALLE: '',
            CATEGORIA: 'SIN DOCUMENTO',
            NIVEL: '',
            FOTOCOPIA_TITULAR: '',
            OBS2: '',
            OBS3: '',
            SIN_DOCUMENTO: true,
        })

        // 3) Armar el bloque de cada docente activo
        const bloques = []
        const vistos = new Set()

        for (const a of docentesActivos.value) {
            const cod = String(a.codigo)
            if (vistos.has(cod)) continue
            vistos.add(cod)

            const existente = bloquesPorDocente.get(cod)
            const carga = cargaPorDocente.get(cod) || new Map()
            const nombre = existente?.nombre || `${a.apellidos || ''} ${a.nombres || ''}`.trim()
            const filas = existente ? existente.filas : []
            const cubiertas = new Set()

            filas.forEach(f => {
                MARCAS_GRUPO.forEach(k => delete f[k])
                const key = normalizarNombreMateria(f.NOMBRE_MATERIA)
                if (carga.has(key)) {
                    f.CH = carga.get(key).ch
                    cubiertas.add(key)
                }
            })

            // materias que dicta pero no están en ningún documento
            for (const [key, mat] of carga) {
                if (!cubiertas.has(key)) filas.push(filaSinDocumento(cod, mat.nombre, mat.ch))
            }

            // activo sin documentos ni materias
            if (!filas.length) filas.push(filaSinDocumento(cod, '-', null))

            bloques.push({ nombre, filas })
        }

        // 4) Docentes no activos se conservan (previewMostrado los oculta)
        for (const [cod, b] of bloquesPorDocente) {
            if (vistos.has(cod)) continue
            b.filas.forEach(f => MARCAS_GRUPO.forEach(k => delete f[k]))
            bloques.push({ nombre: b.nombre, filas: b.filas })
        }

        bloques.sort((x, y) => (x.nombre || '').localeCompare(y.nombre || '', 'es'))

        // 5) Marcas de grupo + numeración
        const resultado = []
        bloques.forEach((b, idx) => {
            b.filas.forEach((f, k) => {
                if (k === 0) {
                    f.N = idx + 1
                    f.NOMBRE_DOCENTE = b.nombre
                    f.INICIO_GRUPO = true
                    f.FILAS_GRUPO = b.filas.length
                } else {
                    f.N = null
                    f.NOMBRE_DOCENTE = ''
                }
                if (k === b.filas.length - 1) f.FIN_GRUPO = true
            })
            resultado.push(...b.filas)
        })
        return resultado
    }

    function aplicarCompletado() {
        if (!previewSinCompletar) previewSinCompletar = preview.value
        preview.value = completarConActivos(previewSinCompletar)
        cargaHorariaAsignada.value = true
        materiasCombinadas.value = false
        previewSinCombinar = null
    }

    function quitarCompletado() {
        if (previewSinCompletar) {
            preview.value = previewSinCompletar
            previewSinCompletar = null
        }
        cargaHorariaAsignada.value = false
        materiasCombinadas.value = false
        previewSinCombinar = null
    }

    async function cargarActivosYCompletar() {
        await cargarDocentesActivos({ anio: anioActivos.value, periodo: periodoActivos.value })
        try {
            cargaActivos.value = await obtenerCargaHorariaDocentes({
                anio: anioActivos.value,
                periodo: periodoActivos.value,
            })
            aplicarCompletado()
        } catch (e) {
            cargaActivos.value = [] // se activa el filtro, pero sin completar
        }
    }

    // Botón "Actualizar" de Solo Activos: recarga activos + carga horaria
    // sobre el preview original (sin completar).
    async function actualizarActivos() {
        if (previewSinCompletar) {
            preview.value = previewSinCompletar
        }
        materiasCombinadas.value = false
        previewSinCombinar = null
        await cargarActivosYCompletar()
    }

    // Botón "Solo Activos": si aún no cargamos la lista de activos (o cambió
    // el año/periodo), la carga y completa el preview; luego activa el filtro.
    // Si ya estaba activo, lo desactiva (toggle) y deshace lo completado.
    async function alternarSoloActivos({ anio, periodo } = {}) {
        if (soloActivos.value) {
            soloActivos.value = false
            quitarCompletado()
            return
        }
        if (anio !== undefined) anioActivos.value = anio
        if (periodo !== undefined) periodoActivos.value = periodo

        // Si había materias combinadas, se deshacen antes de tomar el snapshot
        if (materiasCombinadas.value) alternarCombinarMaterias()

        previewSinCompletar = preview.value // snapshot antes de completar
        await cargarActivosYCompletar()
        soloActivos.value = true
    }

    // Set de códigos de docente activos, como string, para comparar sin
    // preocuparse si el backend devuelve number o string.
    const codigosActivos = computed(() => {
        return new Set(docentesActivos.value.map(d => String(d.codigo)))
    })

    // Lista que debe mostrarse en la tabla: si "Solo Activos" está apagado,
    // es el preview completo; si está prendido, solo las filas cuyo
    // COD_DOCENTE está en la lista de activos (con N° renumerado sin huecos).
    const previewMostrado = computed(() => {
        if (!soloActivos.value) return preview.value
        let n = 0
        return preview.value
            .filter(item => codigosActivos.value.has(String(item.COD_DOCENTE)))
            .map(item => (item.INICIO_GRUPO ? { ...item, N: ++n } : item))
    })

    // ── Totales de docentes (sobre lo que se está mostrando) ──
    // totalDocentes: cantidad de docentes distintos (bloques) visibles.
    // docentesSinDocumento: docentes cuyas filas son TODAS "SIN DOCUMENTO".
    const resumenDocentes = computed(() => {
        const filas = previewMostrado.value
        let total = 0
        let sinDocumento = 0
        let i = 0
        while (i < filas.length) {
            const f = filas[i]
            const n = f.INICIO_GRUPO ? (f.FILAS_GRUPO || 1) : 1
            total++
            const bloque = filas.slice(i, i + n)
            if (bloque.length && bloque.every(x => x.SIN_DOCUMENTO)) sinDocumento++
            i += n
        }
        return { total, sinDocumento, conDocumento: total - sinDocumento }
    })

    const totalDocentes = computed(() => resumenDocentes.value.total)
    const docentesSinDocumento = computed(() => resumenDocentes.value.sinDocumento)
    const docentesConDocumento = computed(() => resumenDocentes.value.conDocumento)

    // Función "experimental": compara cada fila del preview (docente + nombre de
    // materia) contra la carga horaria real obtenida de /carga-horaria-docentes.
    // Si el nombre de la materia coincide EXACTO (normalizado) para ese mismo
    // docente, asigna la CH a esa fila. Si no hay coincidencia, la deja como está
    // (vacía). No modifica filas sin materia real (placeholder "NO REGENTA...").
    async function asignarCargaHoraria({ anio, periodo } = {}) {
        const cargaDocentes = await obtenerCargaHorariaDocentes({ anio, periodo })

        // Mapa "codigoDocente|NOMBRE MATERIA NORMALIZADO" -> CH (sumada si se repite,
        // por ejemplo por grupos compartidos de la misma materia).
        const mapa = new Map()
        for (const docente of cargaDocentes) {
            for (const materia of (docente.materias || [])) {
                const clave = `${docente.codigo}|${normalizarNombreMateria(materia.nom_materia)}`
                mapa.set(clave, (mapa.get(clave) || 0) + Number(materia.carga_horaria || 0))
            }
        }

        let asignadas = 0
        let sinCoincidencia = 0

        preview.value = preview.value.map(item => {
            const nombreMateria = normalizarNombreMateria(item.NOMBRE_MATERIA)

            if (!item.COD_DOCENTE || !nombreMateria || nombreMateria === PLACEHOLDER_SIN_MATERIA) {
                return item
            }

            const clave = `${item.COD_DOCENTE}|${nombreMateria}`
            if (mapa.has(clave)) {
                asignadas++
                return { ...item, CH: mapa.get(clave) }
            }

            sinCoincidencia++
            return item
        })

        cargaHorariaAsignada.value = true
        return { asignadas, sinCoincidencia }
    }

    // ── Combinar Materias (toggle) ──
    // Dentro de cada docente (bloque INICIO_GRUPO..FILAS_GRUPO), agrupa las
    // filas que tengan el MISMO nombre de materia (normalizado) para que en
    // el Excel/preview se vean como una sola celda combinada (rowspan) en la
    // columna de Materia. NUNCA combina '-' ni "NO REGENTA MATERIA EN LA FCE":
    // esas siempre quedan como filas independientes.
    // El resto de columnas (CH, DETALLE, CATEGORIA, NIVEL, FOTOCOPIA, OBS2,
    // OBS3) NO se tocan: cada fila conserva sus propios datos tal cual, solo
    // se fusiona visualmente la celda de NOMBRE_MATERIA.
    function claveMateria(nombreMateria) {
        const norm = normalizarNombreMateria(nombreMateria)
        if (norm === '-' || norm === PLACEHOLDER_SIN_MATERIA) return null
        return norm
    }

    // Reordena las filas de un bloque de un mismo docente para que las
    // materias iguales queden contiguas (necesario para poder fusionar
    // celdas), preservando el orden de la primera aparición de cada materia.
    function combinarMateriasBloque(bloque) {
        const gruposPorClave = new Map()
        bloque.forEach((fila, idx) => {
            const clave = claveMateria(fila.NOMBRE_MATERIA)
            const claveReal = clave === null ? `__unico_${idx}` : clave
            if (!gruposPorClave.has(claveReal)) gruposPorClave.set(claveReal, [])
            gruposPorClave.get(claveReal).push(fila)
        })

        const yaColocadas = new Set()
        const ordenado = []
        bloque.forEach((fila, idx) => {
            const clave = claveMateria(fila.NOMBRE_MATERIA)
            const claveReal = clave === null ? `__unico_${idx}` : clave
            if (yaColocadas.has(claveReal)) return
            yaColocadas.add(claveReal)
            ordenado.push(...gruposPorClave.get(claveReal))
        })

        return ordenado
    }

    // Recorre todo el preview, docente por docente, y arma el arreglo nuevo
    // con las materias iguales agrupadas + las marcas INICIO_MATERIA/FILAS_MATERIA
    // que el frontend y el backend usan para dibujar/generar el rowspan.
    function combinarMaterias() {
        const filas = preview.value
        const resultado = []
        let i = 0

        while (i < filas.length) {
            const filaActual = filas[i]

            // Si no es el inicio de un bloque de docente, se copia tal cual
            // (no debería pasar con datos generados normalmente).
            if (!filaActual.INICIO_GRUPO) {
                resultado.push({ ...filaActual })
                i++
                continue
            }

            const filasGrupo = filaActual.FILAS_GRUPO || 1
            const bloqueOriginal = filas.slice(i, i + filasGrupo).map(f => ({ ...f }))

            // Se guardan los datos que solo vivían en la primera fila del bloque
            const nValor = bloqueOriginal[0].N
            const nombreDocenteValor = bloqueOriginal[0].NOMBRE_DOCENTE

            // Se limpian marcas de grupo/materia previas, se recalculan después
            bloqueOriginal.forEach(f => {
                delete f.N
                delete f.NOMBRE_DOCENTE
                delete f.INICIO_GRUPO
                delete f.FILAS_GRUPO
                delete f.FIN_GRUPO
                delete f.INICIO_MATERIA
                delete f.FILAS_MATERIA
            })

            const ordenado = combinarMateriasBloque(bloqueOriginal)

            // Reasignar el marcador de "primera fila del docente" (Nº / Nombre)
            // a quien haya quedado primero después de reordenar
            ordenado[0].N = nValor
            ordenado[0].NOMBRE_DOCENTE = nombreDocenteValor
            ordenado[0].INICIO_GRUPO = true
            ordenado[0].FILAS_GRUPO = ordenado.length
            for (let k = 1; k < ordenado.length; k++) {
                ordenado[k].N = null
                ordenado[k].NOMBRE_DOCENTE = ''
            }
            ordenado[ordenado.length - 1].FIN_GRUPO = true

            // Marcar los tramos de materia igual (ya quedaron consecutivos
            // tras reordenar) con INICIO_MATERIA / FILAS_MATERIA, y dejar
            // un solo valor de CH para todo el tramo (NO se suma, se toma
            // el primer CH no vacío que aparezca dentro del tramo).
            let idx = 0
            while (idx < ordenado.length) {
                const clave = claveMateria(ordenado[idx].NOMBRE_MATERIA)

                if (clave === null) {
                    ordenado[idx].INICIO_MATERIA = true
                    ordenado[idx].FILAS_MATERIA = 1
                    idx++
                    continue
                }

                let fin = idx + 1
                while (fin < ordenado.length && claveMateria(ordenado[fin].NOMBRE_MATERIA) === clave) {
                    fin++
                }
                const cantidad = fin - idx

                // Toma el primer CH no nulo/no vacío del tramo (no suma)
                let chUnico = null
                for (let k = idx; k < fin; k++) {
                    const ch = ordenado[k].CH
                    if (ch !== null && ch !== undefined && ch !== '') {
                        chUnico = ch
                        break
                    }
                }

                ordenado[idx].INICIO_MATERIA = true
                ordenado[idx].FILAS_MATERIA = cantidad
                ordenado[idx].CH = chUnico

                for (let k = idx + 1; k < fin; k++) {
                    ordenado[k].INICIO_MATERIA = false
                    ordenado[k].CH = null // se combina visualmente con la de arriba
                }

                idx = fin
            }
            resultado.push(...ordenado)
            i += filasGrupo
        }

        return resultado
    }

    // Botón "Combinar Materias": si ya estaba activo, restaura el snapshot
    // sin combinar (deshacer). Si estaba apagado, guarda snapshot y combina.
    function alternarCombinarMaterias() {
        if (materiasCombinadas.value) {
            if (previewSinCombinar) {
                preview.value = previewSinCombinar
                previewSinCombinar = null
            }
            materiasCombinadas.value = false
            return
        }

        previewSinCombinar = preview.value
        preview.value = combinarMaterias()
        materiasCombinadas.value = true
    }

    // ── Botón "Mostrar Referencias" (toggle) ──
    // El texto de DETALLE (incluida la referencia, CLASIFICACION_REFERENCIA)
    // se arma en el backend, así que no hay nada que transformar sobre los
    // datos ya cargados en el cliente: hay que volver a pedir la vista previa
    // con el flag correspondiente. Se expone como función async para que el
    // componente pueda esperar a que termine antes de, por ejemplo, cerrar un
    // loader.
    async function alternarMostrarReferencias(params = {}) {
        mostrarReferencias.value = !mostrarReferencias.value
        await previsualizar({
            ...params,
            mostrar_referencias: mostrarReferencias.value,
        })
    }

    // Construye la URL de descarga real del Excel (mismo endpoint que ya existe,
    // vía GET). Se usa cuando NO se asignó carga horaria automática ni se
    // combinaron materias en el preview.
    // Si "Solo Activos" está activo, manda también anio/periodo y el flag
    // solo_activos=1 para que el backend aplique el mismo filtro al generar
    // el archivo (requiere el soporte correspondiente en el controller).
    function urlDescarga({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo, mostrar_referencias } = {}) {
        const params = new URLSearchParams()
        if (gestion_desde) params.set('gestion_desde', gestion_desde)
        if (gestion_hasta) params.set('gestion_hasta', gestion_hasta)
        if (periodo) params.set('periodo', periodo)
        if (version) params.set('version', version)

        const categoriaCsv = serializarLista(categoria)
        if (categoriaCsv) params.set('categoria', categoriaCsv)

        const tipoTituloCsv = serializarLista(tipo_titulo)
        if (tipoTituloCsv) params.set('tipo_titulo', tipoTituloCsv)

        // Si no se pasa explícitamente, se usa el estado actual del toggle.
        const referenciasActivas = mostrar_referencias !== undefined
            ? mostrar_referencias
            : mostrarReferencias.value
        if (referenciasActivas) params.set('mostrar_referencias', '1')

        if (soloActivos.value) {
            params.set('solo_activos', '1')
            params.set('anio_activos', anioActivos.value)
            params.set('periodo_activos', periodoActivos.value)
        }

        return `${API_BASE}/api/reportes/docentes-clasificados/excel?${params.toString()}`
    }

    // GET /api/reportes/docentes-clasificados/excel — con token, vía blob
    // Reemplaza a urlDescarga() + window.open(), que no puede llevar
    // el header Authorization y se rompe si la ruta queda protegida.
    async function descargarExcel({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo, mostrar_referencias } = {}) {
        const params = new URLSearchParams()
        if (gestion_desde) params.set('gestion_desde', gestion_desde)
        if (gestion_hasta) params.set('gestion_hasta', gestion_hasta)
        if (periodo) params.set('periodo', periodo)
        if (version) params.set('version', version)
        const categoriaCsv = serializarLista(categoria)
        if (categoriaCsv) params.set('categoria', categoriaCsv)
        const tipoTituloCsv = serializarLista(tipo_titulo)
        if (tipoTituloCsv) params.set('tipo_titulo', tipoTituloCsv)
        const referenciasActivas = mostrar_referencias !== undefined
            ? mostrar_referencias
            : mostrarReferencias.value
        if (referenciasActivas) params.set('mostrar_referencias', '1')
        if (soloActivos.value) {
            params.set('solo_activos', '1')
            params.set('anio_activos', anioActivos.value)
            params.set('periodo_activos', periodoActivos.value)
        }
        try {
            const response = await axios.get(
                `${API_BASE}/api/reportes/docentes-clasificados/excel?${params.toString()}`,
                {
                    headers: authHeaders(),
                    responseType: 'blob',
                }
            )
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            })
            const nombreArchivo = `LISTA_DOCENTES_CLASIFICADOS.xlsx`
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = nombreArchivo
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (e) {
            console.error('Error al descargar el Excel:', e)
            throw e
        }
    }


    // POST /api/reportes/docentes-clasificados/excel-personalizado
    // Se usa cuando ya se asignó carga horaria automática, se combinaron
    // materias o está activo "Solo Activos": manda las filas tal cual se ven
    // en pantalla (previewMostrado) para que el backend genere el Excel
    // exactamente con esos datos, en vez de reconstruirlos desde cero y
    // perder los cambios hechos en el navegador. El estado de "Mostrar
    // Referencias" ya viene implícito en el texto de DETALLE de cada fila.
    async function descargarExcelPersonalizado({ gestion, version } = {}) {
        try {
            const response = await axios.post(
                `${API_BASE}/api/reportes/docentes-clasificados/excel-personalizado`,
                {
                    data: previewMostrado.value,
                    gestion: gestion || gestionEtiqueta.value,
                    version: version || versionEtiqueta.value,
                },
                {
                    headers: authHeaders(),
                    responseType: 'blob',
                }
            )
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            })
            const nombreGestion = (gestion || gestionEtiqueta.value || '').toString().replace(/\//g, '-')
            const nombreArchivo = `LISTA_DOCENTES_CLASIFICADOS_${nombreGestion}.xlsx`
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = nombreArchivo
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (e) {
            console.error('Error al descargar el Excel personalizado:', e)
            throw e
        }
    }

    function reset() {
        error.value = null
        preview.value = []
        gestionEtiqueta.value = ''
        versionEtiqueta.value = ''
        totalFilas.value = 0
        soloActivos.value = false
        docentesActivos.value = []
        cargaActivos.value = []
        previewSinCompletar = null
        cargaHorariaAsignada.value = false
        errorCargaHoraria.value = null
        materiasCombinadas.value = false
        previewSinCombinar = null
        mostrarReferencias.value = false
    }

    return {
        loading,
        error,
        preview,
        gestionEtiqueta,
        versionEtiqueta,
        totalFilas,
        previsualizar,
        urlDescarga,
        reset,

        // Solo Activos
        cargandoActivos,
        errorActivos,
        docentesActivos,
        soloActivos,
        anioActivos,
        periodoActivos,
        cargarDocentesActivos,
        alternarSoloActivos,
        actualizarActivos,
        previewMostrado,

        // Totales de docentes
        totalDocentes,
        docentesConDocumento,
        docentesSinDocumento,

        // Asignar Carga Horaria (experimental)
        cargandoCargaHoraria,
        errorCargaHoraria,
        cargaHorariaAsignada,
        obtenerCargaHorariaDocentes,
        asignarCargaHoraria,
        descargarExcelPersonalizado,
        descargarExcel,

        // Combinar Materias
        materiasCombinadas,
        alternarCombinarMaterias,

        // Mostrar Referencias
        mostrarReferencias,
        alternarMostrarReferencias,
    }
}