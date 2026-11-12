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

// ─── Normalización de nombres de materia ───
// Más tolerante que antes: además de espacios y mayúsculas,
//  - quita tildes (ÁLGEBRA == ALGEBRA)
//  - quita signos de puntuación (CONTABILIDAD I. == CONTABILIDAD I)
//  - convierte números romanos sueltos a arábigos (CONTABILIDAD II == CONTABILIDAD 2)
const ROMANOS = { I: '1', II: '2', III: '3', IV: '4', V: '5', VI: '6', VII: '7', VIII: '8', IX: '9', X: '10' }
const STOPWORDS = new Set(['DE', 'DEL', 'LA', 'LAS', 'LOS', 'EL', 'Y', 'E', 'EN', 'A', 'AL'])

function normalizarNombreMateria(nombre) {
    const original = (nombre || '').toString().trim()
    if (original === '-') return '-'

    const limpio = original
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()

    return limpio
        .split(' ')
        .map(t => ROMANOS[t] ?? t)
        .join(' ')
}

function tokensMateria(key) {
    return key.split(' ').filter(t => t && !STOPWORDS.has(t))
}

function numerosDe(key) {
    return key.split(' ').filter(t => /^\d+$/.test(t)).join(',')
}

// Similitud de Jaccard entre las palabras (sin conectores) de dos nombres
function similitudMateria(a, b) {
    const A = new Set(tokensMateria(a))
    const B = new Set(tokensMateria(b))
    if (!A.size || !B.size) return 0
    let inter = 0
    A.forEach(t => { if (B.has(t)) inter++ })
    return inter / (A.size + B.size - inter)
}

// Busca en el mapa de materias de UN docente la que corresponde a "key":
// 1) coincidencia exacta (ya normalizada)
// 2) si no hay, la más parecida, pero SOLO si los números coinciden
//    (así "CONTABILIDAD 1" nunca se confunde con "CONTABILIDAD 2")
//    y las palabras se parecen al menos en un 75%.
// Devuelve la clave del mapa o null.
function buscarClaveMateria(key, mapa) {
    if (!key || !mapa || !mapa.size) return null
    if (mapa.has(key)) return key

    const nums = numerosDe(key)
    let mejor = null
    let mejorScore = 0
    for (const cand of mapa.keys()) {
        if (numerosDe(cand) !== nums) continue
        const s = similitudMateria(key, cand)
        if (s > mejorScore) {
            mejorScore = s
            mejor = cand
        }
    }
    return mejorScore >= 0.75 ? mejor : null
}

const PLACEHOLDER_SIN_MATERIA = 'NO REGENTA MATERIA EN LA FCE'

// ─── Exclusiones del reporte de Prelación ───
// 1) Docentes que nunca deben aparecer (por código de docente).
// 2) Materias cuyo CÓDIGO DE MATERIA empieza con 18: esas materias no se
//    muestran. Si el docente tiene otras materias, sigue apareciendo solo con
//    esas; si solo tenía materias 18, el docente desaparece.
// Agrega aquí los códigos de docente que quieras excluir (como texto).
const CODIGOS_DOCENTES_EXCLUIDOS_PRELACION = new Set(['100'])
const PREFIJO_MATERIA_EXCLUIDA_PRELACION = '18'

const esCodigoMateriaExcluido = (codMateria) =>
    String(codMateria ?? '').trim().startsWith(PREFIJO_MATERIA_EXCLUIDA_PRELACION)

// Marcas de agrupación que se recalculan cada vez que se arman los bloques
const MARCAS_GRUPO = ['N', 'NOMBRE_DOCENTE', 'INICIO_GRUPO', 'FILAS_GRUPO', 'FIN_GRUPO', 'INICIO_MATERIA', 'FILAS_MATERIA']

const textoPlano = (s) => String(s ?? '')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()

// Normaliza una categoría para compararla (sin tildes, mayúsculas, sin espacios extra)
const normalizarCategoria = (s) => textoPlano(s).replace(/\s+/g, ' ').trim()

// ─── Quitar filas conservando la estructura por docente ───
// Quita las filas que cumplan `debeQuitar` y recalcula las marcas de grupo
// (Nº, rowspan del docente, FIN_GRUPO...). Si un docente se queda sin filas,
// desaparece. Devuelve { filas, quitadas }.
function quitarFilas(filas, debeQuitar) {
    const quitadas = filas.filter(debeQuitar)
    if (!quitadas.length) return { filas, quitadas: [] }

    // 1) separar en bloques por docente
    const bloques = []
    let i = 0
    while (i < filas.length) {
        const f = filas[i]
        const n = f.INICIO_GRUPO ? (f.FILAS_GRUPO || 1) : 1
        bloques.push(filas.slice(i, i + n))
        i += n
    }

    // 2) reconstruir
    const resultado = []
    let numero = 0
    for (const bloque of bloques) {
        const restantes = bloque.filter(f => !debeQuitar(f))
        if (!restantes.length) continue

        const perdioFilas = restantes.length !== bloque.length
        const copia = restantes.map(f => ({ ...f }))

        if (bloque[0].INICIO_GRUPO) {
            copia.forEach((f, k) => {
                delete f.FIN_GRUPO
                if (perdioFilas) {
                    delete f.INICIO_MATERIA
                    delete f.FILAS_MATERIA
                }
                if (k === 0) {
                    f.N = ++numero
                    f.NOMBRE_DOCENTE = bloque[0].NOMBRE_DOCENTE
                    f.INICIO_GRUPO = true
                    f.FILAS_GRUPO = copia.length
                } else {
                    f.N = null
                    f.NOMBRE_DOCENTE = ''
                    delete f.INICIO_GRUPO
                    delete f.FILAS_GRUPO
                }
            })
            copia[copia.length - 1].FIN_GRUPO = true
        }
        resultado.push(...copia)
    }
    return { filas: resultado, quitadas }
}

// ─── Acéfala + compartido ───
// Una materia es "compartida" SOLO si en la carga horaria real la columna
// COMP. es mayor a 0 (campo `compartida` o `comp` de cada materia que
// devuelve /api/reportes/carga-horaria-docentes).
//
// Una fila del reporte es "acéfala compartida" si:
//   - su categoría es ACEFALA, y
//   - esa materia (para ese docente) tiene COMP > 0.
//
// Según el botón "Compartido":
//   - desactivado (por defecto): esas filas se excluyen del reporte.
//   - activado: esas filas se muestran, con CH = '-'.
// Las demás materias acéfalas (COMP = 0) se muestran SIEMPRE con normalidad.

const esMateriaCompartida = (mat) => Number(mat?.compartida ?? mat?.comp ?? 0) > 0

// ¿La fila pertenece a la categoría ACÉFALA?
const esFilaAcefala = (f) => textoPlano(f.CATEGORIA).includes('ACEFALA')

// Construye: cod_docente -> Map(materiaNormalizada -> true), solo con compartidas
function construirMapaCompartidas(carga) {
    const mapa = new Map()
    for (const d of carga || []) {
        // materia -> true solo si TODOS sus grupos son compartidos
        const porMateria = new Map()
        for (const mat of (d.materias || [])) {
            const key = normalizarNombreMateria(mat.nom_materia)
            if (!key) continue
            const comp = esMateriaCompartida(mat)
            porMateria.set(key, porMateria.has(key) ? (porMateria.get(key) && comp) : comp)
        }
        const m = new Map()
        porMateria.forEach((esComp, key) => { if (esComp) m.set(key, true) })
        if (m.size) mapa.set(String(d.codigo), m)
    }
    return mapa
}

function esAcefalaCompartida(fila, mapaCompartidas) {
    if (!esFilaAcefala(fila)) return false
    const m = mapaCompartidas?.get(String(fila.COD_DOCENTE))
    if (!m) return false
    return !!buscarClaveMateria(normalizarNombreMateria(fila.NOMBRE_MATERIA), m)
}

// Si mostrar === false: quita las filas ACEFALA + compartida y recalcula
// las marcas de grupo. Si un docente se queda sin filas, desaparece.
// Si mostrar === true: las deja, con CH = '-' y la marca COMPARTIDA: true.
function procesarCompartidas(filas, mostrar = false, mapaCompartidas = new Map()) {
    const esComp = (f) => esAcefalaCompartida(f, mapaCompartidas)

    if (mostrar) {
        const resultado = filas.map(f =>
            esComp(f) ? { ...f, CH: '-', COMPARTIDA: true } : { ...f }
        )
        return { filas: resultado, ocultas: 0 }
    }

    const { filas: resultado, quitadas } = quitarFilas(filas, esComp)
    return { filas: resultado, ocultas: quitadas.length }
}

// Suma la CH de un bloque (docente). Si una misma materia aparece en varias
// filas, su CH se cuenta una sola vez. Las CH '-' (compartidas) no suman.
function totalChBloque(bloque) {
    const vistos = new Set()
    let total = 0
    for (const f of bloque) {
        const ch = Number(f.CH)
        if (!ch || Number.isNaN(ch)) continue
        const key = normalizarNombreMateria(f.NOMBRE_MATERIA)
        if (key && key !== '-' && key !== PLACEHOLDER_SIN_MATERIA) {
            if (vistos.has(key)) continue
            vistos.add(key)
        }
        total += ch
    }
    return total
}

// T. CH x 4 (equivalente mensual). Devuelve null si el total no es un
// número válido (vacío, 0, '-', etc.).
function totalMesDe(totalCh) {
    const n = Number(totalCh)
    if (!Number.isFinite(n) || n <= 0) return null
    return Math.round(n * 4 * 100) / 100
}

// ─── Antigüedad del docente en la materia ───
// Cada periodo (1 o 2) en que dictó la materia vale 0,5 año.
// Ej.: 4 periodos = 2 años, 5 periodos = 2,5 años, 2 periodos = 1 año.
const ANIOS_POR_PERIODO = 0.5

// data (backend) -> Map(codDocente -> Map(materiaNormalizada -> Set(periodos)))
function construirMapaAntiguedad(data) {
    const mapa = new Map()
    for (const d of data || []) {
        const m = new Map()
        for (const mat of (d.materias || [])) {
            const key = normalizarNombreMateria(mat.nom_materia)
            if (!key) continue
            const set = m.get(key) || new Set()
                ; (mat.periodos || []).forEach(p => set.add(p))
            m.set(key, set)
        }
        mapa.set(String(d.codigo), m)
    }
    return mapa
}

// Años de antigüedad de una fila (mismo criterio de coincidencia que la CH).
// Devuelve null si no aplica o no hay datos.
function antiguedadDeFila(fila, mapa) {
    const nombre = normalizarNombreMateria(fila.NOMBRE_MATERIA)
    if (!fila.COD_DOCENTE || !nombre || nombre === '-' || nombre === PLACEHOLDER_SIN_MATERIA) return null

    const materiasDocente = mapa.get(String(fila.COD_DOCENTE))
    const clave = buscarClaveMateria(nombre, materiasDocente)
    if (!clave) return null

    return materiasDocente.get(clave).size * ANIOS_POR_PERIODO
}

export function useReporteExcel() {
    const loading = ref(false)
    const error = ref(null)
    const preview = ref([])
    const gestionEtiqueta = ref('')
    const versionEtiqueta = ref('')
    const totalFilas = ref(0)

    // Cantidad de filas ACEFALA + compartida que se ocultaron del preview
    const filasOcultasCompartidas = ref(0)

    // ── Exclusiones de Prelación ──
    // true: se excluyen los docentes de CODIGOS_DOCENTES_EXCLUIDOS_PRELACION
    // y las materias cuyo código empieza con 18 (se aplica al completar con
    // "Solo docentes activos", que es lo que usa Prelación).
    const excluirPrelacion = ref(false)

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

    // Materias compartidas reales (COMP. > 0): cod_docente -> Map(materia -> true)
    let compartidasMap = new Map()

    // ── Asignar Carga Horaria (experimental) ──
    const cargandoCargaHoraria = ref(false)
    const errorCargaHoraria = ref(null)
    const cargaHorariaAsignada = ref(false) // true si el preview actual ya tiene CH asignada automáticamente

    // ── Combinar Materias ──
    const materiasCombinadas = ref(false) // true si el preview actual tiene materias combinadas
    let previewSinCombinar = null // snapshot para poder deshacer la combinación

    // ── Mostrar Referencias (CLASIFICACION_REFERENCIA en DETALLE) ──
    // El DETALLE se arma en el backend, así que este flag solo se guarda
    // como estado y se reenvía en cada previsualizar()/urlDescarga().
    const mostrarReferencias = ref(false)

    // ── Antigüedad (columna al lado de CH, apagada por defecto) ──
    // Años que el docente lleva dictando ESA materia (cada periodo 1 o 2 = 0,5).
    const mostrarAntiguedad = ref(false)
    const cargandoAntiguedad = ref(false)
    const errorAntiguedad = ref(null)
    const antiguedadMapa = ref(new Map())
    let antiguedadClave = '' // "anio~periodo" de los datos cargados

    // GET /api/reportes/antiguedad-docentes-materia
    async function cargarAntiguedad({ anio, periodo, forzar = false } = {}) {
        const a = anio ?? anioActivos.value
        const p = periodo ?? periodoActivos.value
        const clave = `${a}~${p}`
        if (!forzar && clave === antiguedadClave && antiguedadMapa.value.size) return

        cargandoAntiguedad.value = true
        errorAntiguedad.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/antiguedad-docentes-materia`,
                { params: { anio: a, periodo: p }, headers: authHeaders() }
            )
            antiguedadMapa.value = construirMapaAntiguedad(data.data)
            antiguedadClave = clave
        } catch (e) {
            errorAntiguedad.value = e?.response?.data?.message || 'No se pudo obtener la antigüedad de los docentes'
            antiguedadMapa.value = new Map()
            antiguedadClave = ''
            throw e
        } finally {
            cargandoAntiguedad.value = false
        }
    }

    // Interruptor: al encender carga los datos; al apagar solo oculta la columna
    async function alternarAntiguedad() {
        if (mostrarAntiguedad.value) {
            mostrarAntiguedad.value = false
            return
        }
        try {
            await cargarAntiguedad()
            mostrarAntiguedad.value = true
        } catch (e) {
            mostrarAntiguedad.value = false
        }
    }

    // ── Ocultar parte del TÍTULO en el DETALLE ──
    // También se arma en el backend: al cambiar se vuelve a pedir el preview.
    const ocultarNombreTitulo = ref(false) // oculta NOMBRE_TITULO
    const ocultarTipoTitulo = ref(false)   // oculta TIPO_TITULO (categoría del título)

    // ── Quitar materias por clasificación (Acéfala, Suplencia, ...) ──
    // categoriasDisponibles: categorías distintas que vienen en el preview
    //   (para pintar los botones/selector de forma dinámica).
    // categoriasExcluidas: categorías (texto original) elegidas para quitar.
    //   Se quita la fila de la materia y su CH. Con "Solo Activos", esa
    //   materia tampoco vuelve a entrar desde la carga horaria real.
    const categoriasDisponibles = ref([])
    const categoriasExcluidas = ref([])
    const filasQuitadasPorCategoria = ref(0)
    // cod_docente -> Map(materiaNormalizada -> true) de las materias quitadas
    // (y que el docente no conserva en otra fila válida)
    let materiasQuitadasMap = new Map()

    // ── Botón "Compartido" (por defecto desactivado) ──
    // false: se ocultan las filas acéfalas compartidas (COMP. > 0).
    // true: se muestran, con CH = '-'.
    const mostrarCompartidas = ref(false)
    let datosCrudos = null // filas tal cual llegaron del backend

    // Calcula qué materias se quitaron por categoría y el docente no conserva
    // en ninguna otra fila (para no re-agregarlas desde la carga horaria real).
    function calcularMateriasQuitadas(filasRestantes, quitadas) {
        const restantesPorDocente = new Map()
        for (const f of filasRestantes) {
            const key = normalizarNombreMateria(f.NOMBRE_MATERIA)
            if (!key || key === '-' || key === PLACEHOLDER_SIN_MATERIA) continue
            const cod = String(f.COD_DOCENTE)
            if (!restantesPorDocente.has(cod)) restantesPorDocente.set(cod, new Map())
            restantesPorDocente.get(cod).set(key, true)
        }

        const mapa = new Map()
        for (const f of quitadas) {
            const key = normalizarNombreMateria(f.NOMBRE_MATERIA)
            if (!key || key === '-' || key === PLACEHOLDER_SIN_MATERIA) continue
            const cod = String(f.COD_DOCENTE)
            if (buscarClaveMateria(key, restantesPorDocente.get(cod))) continue
            if (!mapa.has(cod)) mapa.set(cod, new Map())
            mapa.get(cod).set(key, true)
        }
        return mapa
    }

    // Aplica categorías excluidas + filtro de compartidas sobre los datos crudos
    function recalcularBase() {
        if (!datosCrudos) return

        // 1) Quitar categorías elegidas (materia + CH)
        const excluidas = new Set(categoriasExcluidas.value.map(normalizarCategoria))
        let base = datosCrudos
        if (excluidas.size) {
            const r = quitarFilas(datosCrudos, f => excluidas.has(normalizarCategoria(f.CATEGORIA)))
            base = r.filas
            filasQuitadasPorCategoria.value = r.quitadas.length
            materiasQuitadasMap = calcularMateriasQuitadas(base, r.quitadas)
        } else {
            filasQuitadasPorCategoria.value = 0
            materiasQuitadasMap = new Map()
        }

        // 2) Acéfalas compartidas
        const { filas, ocultas } = procesarCompartidas(base, mostrarCompartidas.value, compartidasMap)
        preview.value = filas
        previewSinCompletar = filas
        filasOcultasCompartidas.value = ocultas
        cargaHorariaAsignada.value = false
        materiasCombinadas.value = false
        previewSinCombinar = null
    }

    function procesarCrudos() {
        recalcularBase()

        // Si "Solo Activos" sigue encendido, se vuelve a completar
        if (soloActivos.value && cargaActivos.value.length) {
            aplicarCompletado()
        }
    }

    // Toggle del botón "Compartido": no hace falta volver al backend
    function alternarMostrarCompartidas() {
        mostrarCompartidas.value = !mostrarCompartidas.value
        procesarCrudos()
    }

    // ── Botones "Quitar categoría" ──
    // Quita/restaura UNA categoría exacta (ej. 'Suplencia'). Sin ir al backend.
    function alternarCategoriaExcluida(categoria) {
        const clave = normalizarCategoria(categoria)
        const existe = categoriasExcluidas.value.some(c => normalizarCategoria(c) === clave)
        categoriasExcluidas.value = existe
            ? categoriasExcluidas.value.filter(c => normalizarCategoria(c) !== clave)
            : [...categoriasExcluidas.value, categoria]
        procesarCrudos()
    }

    // Quita/restaura TODAS las categorías disponibles que contengan el texto
    // (ej. alternarGrupoCategorias('acefala') cubre "Acefala", "Acefala compartida"...).
    // Si ninguna de las coincidentes está excluida, las excluye todas; si no, las restaura.
    function alternarGrupoCategorias(texto) {
        const buscado = normalizarCategoria(texto)
        const coincidentes = categoriasDisponibles.value.filter(c => normalizarCategoria(c).includes(buscado))
        if (!coincidentes.length) return

        const clavesCoinc = new Set(coincidentes.map(normalizarCategoria))
        const algunaExcluida = categoriasExcluidas.value.some(c => clavesCoinc.has(normalizarCategoria(c)))

        categoriasExcluidas.value = algunaExcluida
            ? categoriasExcluidas.value.filter(c => !clavesCoinc.has(normalizarCategoria(c)))
            : [...categoriasExcluidas.value, ...coincidentes]
        procesarCrudos()
    }

    // Reemplaza toda la selección de una vez (útil para un selector múltiple)
    function establecerCategoriasExcluidas(lista = []) {
        categoriasExcluidas.value = [...lista]
        procesarCrudos()
    }

    function limpiarCategoriasExcluidas() {
        establecerCategoriasExcluidas([])
    }

    function estaCategoriaExcluida(categoria) {
        const clave = normalizarCategoria(categoria)
        return categoriasExcluidas.value.some(c => normalizarCategoria(c) === clave)
    }

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reportes/docentes-clasificados/preview
    // categoria y tipo_titulo pueden venir como array (multi-selección) o string
    // OJO: cada fila debe traer COD_MATERIA (código de la materia) para poder
    // excluir las materias que empiezan con 18 en Prelación.
    async function previsualizar({
        gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo,
        mostrar_referencias, ocultar_nombre_titulo, ocultar_tipo_titulo,
    } = {}) {
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
        const ocultarNombre = ocultar_nombre_titulo !== undefined
            ? ocultar_nombre_titulo
            : ocultarNombreTitulo.value
        const ocultarTipo = ocultar_tipo_titulo !== undefined
            ? ocultar_tipo_titulo
            : ocultarTipoTitulo.value

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
                        ocultar_nombre_titulo: ocultarNombre ? 1 : undefined,
                        ocultar_tipo_titulo: ocultarTipo ? 1 : undefined,
                    },
                    headers: authHeaders(),
                }
            )

            if (!data.ok) {
                error.value = data.error || 'No se pudo obtener la vista previa'
                preview.value = []
                previewSinCompletar = null
                datosCrudos = null
                filasOcultasCompartidas.value = 0
                filasQuitadasPorCategoria.value = 0
                categoriasDisponibles.value = []
                return
            }

            gestionEtiqueta.value = data.gestion
            versionEtiqueta.value = data.version
            totalFilas.value = data.total_filas
            mostrarReferencias.value = referenciasActivas
            ocultarNombreTitulo.value = ocultarNombre
            ocultarTipoTitulo.value = ocultarTipo

            // Categorías distintas presentes en los datos (botones dinámicos).
            // Se mantienen también las ya excluidas para poder restaurarlas.
            const distintas = new Map()
            for (const f of data.data || []) {
                const c = (f.CATEGORIA ?? '').toString().trim()
                if (c && c !== '-') distintas.set(normalizarCategoria(c), c)
            }
            for (const c of categoriasExcluidas.value) {
                distintas.set(normalizarCategoria(c), c)
            }
            categoriasDisponibles.value = [...distintas.values()].sort((a, b) => a.localeCompare(b, 'es'))

            // Guarda los datos crudos y aplica categorías excluidas + filtro
            // de compartidas según el estado actual de los botones
            datosCrudos = data.data
            procesarCrudos()
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la vista previa'
            preview.value = []
            previewSinCompletar = null
            datosCrudos = null
            filasOcultasCompartidas.value = 0
            filasQuitadasPorCategoria.value = 0
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
    // Cada materia debe traer también `compartida` (columna COMP., 0 o 1)
    // y `cod_materia` (código de la materia, p. ej. 1803024).
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
    //   y CH; el resto de columnas queda vacío y la categoría es "-".
    // - Docente con documentos de solo algunas materias: conserva sus filas
    //   y se le agregan las materias que faltan (también sin documento).
    // - Las filas ACÉFALA solo se conservan si su materia se dicta en el
    //   año/periodo seleccionado (según la carga horaria real). Las demás
    //   categorías no se filtran.
    // - La CH se asigna a toda fila cuya materia coincide con la carga real
    //   (coincidencia exacta normalizada o, si no hay, la más parecida).
    // - Las filas COMPARTIDA conservan CH = '-' y cuentan como materia cubierta.
    // - Las materias compartidas (COMP. > 0) que no están en ningún documento
    //   solo se agregan si "Compartido" está activo (con CH '-').
    // - Con excluirPrelacion: se omiten los docentes excluidos y las materias
    //   con código que empieza con 18.
    // - Materias QUITADAS por categoría (Acéfala, Suplencia...): no se
    //   re-agregan desde la carga real, así que ni la materia ni su CH vuelven.
    function completarConActivos(filasBase) {
        const excluir = excluirPrelacion.value

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

        // 2) Materias reales por docente: cod -> Map(materiaNormalizada -> {nombre, ch, compartida})
        const cargaPorDocente = new Map()
        const docentesCon18EnCarga = new Set() // docentes que dictan alguna materia 18
        for (const d of cargaActivos.value) {
            const m = new Map()
            const quitadasDoc = materiasQuitadasMap.get(String(d.codigo))
            for (const mat of (d.materias || [])) {
                // Materia 18: se ignora (no suma CH ni se agrega como faltante)
                if (excluir && esCodigoMateriaExcluido(mat.cod_materia)) {
                    docentesCon18EnCarga.add(String(d.codigo))
                    continue
                }
                const key = normalizarNombreMateria(mat.nom_materia)
                if (!key) continue

                // Materia quitada por categoría: no vuelve ni suma su CH
                if (quitadasDoc && buscarClaveMateria(key, quitadasDoc)) continue

                const prev = m.get(key)
                m.set(key, {
                    nombre: mat.nom_materia,
                    ch: (prev?.ch || 0) + Number(mat.carga_horaria || 0),
                    compartida: prev
                        ? (prev.compartida && esMateriaCompartida(mat))
                        : esMateriaCompartida(mat),
                })
            }
            cargaPorDocente.set(String(d.codigo), m)
        }

        // Fila de un docente activo que no tiene documento: la categoría
        // se muestra directamente como "-". La marca interna SIN_DOCUMENTO
        // se mantiene para los totales.
        const filaSinDocumento = (cod, nombreMateria, ch) => ({
            COD_DOCENTE: cod,
            NOMBRE_MATERIA: nombreMateria,
            CH: ch,
            DETALLE: '',
            CATEGORIA: '-',
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

            // Docente excluido del reporte de Prelación
            if (excluir && CODIGOS_DOCENTES_EXCLUIDOS_PRELACION.has(cod)) continue

            const existente = bloquesPorDocente.get(cod)
            const carga = cargaPorDocente.get(cod) || new Map()
            const nombre = existente?.nombre || `${a.apellidos || ''} ${a.nombres || ''}`.trim()

            // Las filas ACÉFALA solo se conservan si el documento es del año/periodo
            // seleccionado. Las demás categorías no se tocan.
            const esDelPeriodo = (f) =>
                String(f.GESTION ?? '').trim() === String(anioActivos.value) &&
                String(f.PERIODO ?? '').trim() === String(periodoActivos.value)

            let filas = existente
                ? existente.filas.filter(f => {
                    if (!esFilaAcefala(f)) return true
                    // Respaldo: si el backend aún no envía GESTION/PERIODO, filtra por materia
                    if (f.GESTION === undefined || f.GESTION === null) {
                        return !!buscarClaveMateria(normalizarNombreMateria(f.NOMBRE_MATERIA), carga)
                    }
                    return esDelPeriodo(f)
                })
                : []

            // Materias con código 18: se quitan de las filas con documento.
            // Si el docente se queda sin nada por esto, no se muestra.
            let tuvoMateria18 = docentesCon18EnCarga.has(cod)
            if (excluir) {
                const antes = filas.length
                filas = filas.filter(f => !esCodigoMateriaExcluido(f.COD_MATERIA))
                if (filas.length < antes) tuvoMateria18 = true
            }

            const cubiertas = new Set()

            filas.forEach(f => {
                MARCAS_GRUPO.forEach(k => delete f[k])
                const key = normalizarNombreMateria(f.NOMBRE_MATERIA)
                const claveCarga = buscarClaveMateria(key, carga)
                if (claveCarga) {
                    cubiertas.add(claveCarga)
                    // Compartidas: se mantiene CH = '-'
                    if (!f.COMPARTIDA) {
                        f.CH = carga.get(claveCarga).ch
                    }
                }
            })

            // materias que dicta pero no están en ningún documento
            for (const [key, mat] of carga) {
                if (cubiertas.has(key)) continue

                if (mat.compartida) {
                    // Compartida (COMP. > 0): solo se muestra si el botón está activo
                    if (!mostrarCompartidas.value) continue
                    filas.push({ ...filaSinDocumento(cod, mat.nombre, '-'), COMPARTIDA: true })
                    continue
                }

                filas.push(filaSinDocumento(cod, mat.nombre, mat.ch))
            }

            // Solo tenía materias 18: el docente no se muestra
            if (tuvoMateria18 && !filas.length) continue

            // Se quitaron materias por categoría y no queda nada para este
            // docente (ni documento ni materia real): no se muestra.
            if (!filas.length && materiasQuitadasMap.has(cod)) continue

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

            // Ahora que se conoce la columna COMP. real, se guardan las
            // materias compartidas y se recalcula la base (oculta/marca
            // solo las acéfalas con COMP. > 0) antes de completar.
            compartidasMap = construirMapaCompartidas(cargaActivos.value)
            recalcularBase()
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
    // Además, en la primera fila de cada docente se agrega:
    //   - TOTAL_CH:     suma de la columna CH del docente (cada materia una vez).
    //   - TOTAL_CH_MES: TOTAL_CH x 4 (equivalente mensual), o null si no aplica.
    // Y, si "Mostrar antigüedad" está activo, en cada fila:
    //   - ANTIGUEDAD:   años dictando esa materia (null en filas combinadas).
    // Estos campos viajan al backend en descargarExcelPersonalizado().
    const previewMostrado = computed(() => {
        const base = soloActivos.value
            ? preview.value.filter(item => codigosActivos.value.has(String(item.COD_DOCENTE)))
            : preview.value

        const resultado = []
        let n = 0
        let i = 0
        while (i < base.length) {
            const f = base[i]
            const nFilas = f.INICIO_GRUPO ? (f.FILAS_GRUPO || 1) : 1
            const bloque = base.slice(i, i + nFilas)
            const total = totalChBloque(bloque)

            bloque.forEach((fila, k) => {
                const copia = { ...fila }
                if (k === 0) {
                    copia.TOTAL_CH = total
                    copia.TOTAL_CH_MES = totalMesDe(total)
                    if (soloActivos.value && copia.INICIO_GRUPO) copia.N = ++n
                }
                // Antigüedad: misma celda combinada que la CH
                if (mostrarAntiguedad.value) {
                    copia.ANTIGUEDAD = fila.INICIO_MATERIA === false
                        ? null
                        : antiguedadDeFila(fila, antiguedadMapa.value)
                }
                resultado.push(copia)
            })
            i += nFilas
        }
        return resultado
    })

    // ── Totales de docentes (sobre lo que se está mostrando) ──
    // totalDocentes: cantidad de docentes distintos (bloques) visibles.
    // docentesSinDocumento: docentes cuyas filas son TODAS sin documento.
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
    // Busca primero coincidencia exacta (normalizada: sin tildes, sin puntuación,
    // romanos = arábigos) y, si no hay, la materia más parecida de ESE docente
    // (mismos números y ≥ 75% de palabras en común). Si hay coincidencia asigna
    // la CH; si no, deja la fila como está. No toca filas sin materia real
    // (placeholder "NO REGENTA...") ni las filas compartidas (CH = '-').
    // Con excluirPrelacion, las materias con código 18 no entran al mapa.
    async function asignarCargaHoraria({ anio, periodo } = {}) {
        const cargaDocentes = await obtenerCargaHorariaDocentes({ anio, periodo })

        // Mapa codigoDocente -> Map(materiaNormalizada -> CH). La CH se suma si
        // la materia se repite (por ejemplo por grupos de la misma materia).
        const mapaPorDocente = new Map()
        for (const docente of cargaDocentes) {
            const m = new Map()
            for (const materia of (docente.materias || [])) {
                if (excluirPrelacion.value && esCodigoMateriaExcluido(materia.cod_materia)) continue
                const key = normalizarNombreMateria(materia.nom_materia)
                if (!key) continue
                m.set(key, (m.get(key) || 0) + Number(materia.carga_horaria || 0))
            }
            mapaPorDocente.set(String(docente.codigo), m)
        }

        let asignadas = 0
        let sinCoincidencia = 0

        preview.value = preview.value.map(item => {
            // Compartidas: no se toca su CH ('-')
            if (item.COMPARTIDA) return item

            const nombreMateria = normalizarNombreMateria(item.NOMBRE_MATERIA)

            if (!item.COD_DOCENTE || !nombreMateria || nombreMateria === '-' || nombreMateria === PLACEHOLDER_SIN_MATERIA) {
                return item
            }

            const materiasDocente = mapaPorDocente.get(String(item.COD_DOCENTE))
            const clave = buscarClaveMateria(nombreMateria, materiasDocente)
            if (clave) {
                asignadas++
                return { ...item, CH: materiasDocente.get(clave) }
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
            // el primer CH real que aparezca dentro del tramo).
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

                // Toma el primer CH real del tramo (no suma); '-' solo si no hay otro
                let chUnico = null
                let hayGuion = false
                for (let k = idx; k < fin; k++) {
                    const ch = ordenado[k].CH
                    if (ch === '-') { hayGuion = true; continue }
                    if (ch !== null && ch !== undefined && ch !== '') {
                        chUnico = ch
                        break
                    }
                }
                if (chUnico === null && hayGuion) chUnico = '-'

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
    // El texto de DETALLE (incluida la referencia) se arma en el backend, así
    // que hay que volver a pedir la vista previa con el flag correspondiente.
    async function alternarMostrarReferencias(params = {}) {
        mostrarReferencias.value = !mostrarReferencias.value
        await previsualizar({
            ...params,
            mostrar_referencias: mostrarReferencias.value,
        })
    }

    // ── Botones "Ocultar título" ──
    // `params` = los filtros actuales (gestion_desde, gestion_hasta, periodo,
    // version, categoria, tipo_titulo), igual que en alternarMostrarReferencias.

    // Oculta/muestra el NOMBRE del título
    async function alternarOcultarNombreTitulo(params = {}) {
        await previsualizar({
            ...params,
            ocultar_nombre_titulo: !ocultarNombreTitulo.value,
            ocultar_tipo_titulo: ocultarTipoTitulo.value,
        })
    }

    // Oculta/muestra la CATEGORÍA (tipo) del título
    async function alternarOcultarTipoTitulo(params = {}) {
        await previsualizar({
            ...params,
            ocultar_nombre_titulo: ocultarNombreTitulo.value,
            ocultar_tipo_titulo: !ocultarTipoTitulo.value,
        })
    }

    // UN SOLO botón que cicla:
    //   todo visible → sin nombre → sin categoría → sin ambos → todo visible
    async function ciclarOcultarTitulo(params = {}) {
        const n = ocultarNombreTitulo.value
        const t = ocultarTipoTitulo.value

        let siguiente
        if (!n && !t) siguiente = { nombre: true, tipo: false }
        else if (n && !t) siguiente = { nombre: false, tipo: true }
        else if (!n && t) siguiente = { nombre: true, tipo: true }
        else siguiente = { nombre: false, tipo: false }

        await previsualizar({
            ...params,
            ocultar_nombre_titulo: siguiente.nombre,
            ocultar_tipo_titulo: siguiente.tipo,
        })
    }

    // Texto para el botón único de ciclo
    const etiquetaOcultarTitulo = computed(() => {
        if (ocultarNombreTitulo.value && ocultarTipoTitulo.value) return 'Título: oculto'
        if (ocultarNombreTitulo.value) return 'Título: sin nombre'
        if (ocultarTipoTitulo.value) return 'Título: sin categoría'
        return 'Título: completo'
    })

    // Arma los params de query comunes para las descargas por GET
    function armarParamsDescarga({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo, mostrar_referencias } = {}) {
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

        if (ocultarNombreTitulo.value) params.set('ocultar_nombre_titulo', '1')
        if (ocultarTipoTitulo.value) params.set('ocultar_tipo_titulo', '1')

        const excluirCsv = serializarLista(categoriasExcluidas.value)
        if (excluirCsv) params.set('excluir_categoria', excluirCsv)

        if (soloActivos.value) {
            params.set('solo_activos', '1')
            params.set('anio_activos', anioActivos.value)
            params.set('periodo_activos', periodoActivos.value)
        }
        return params
    }

    // Construye la URL de descarga real del Excel (mismo endpoint que ya existe,
    // vía GET). OJO: este endpoint NO incluye "Mes" (T. CH x 4) ni Antigüedad;
    // para eso usa descargarExcelPersonalizado().
    function urlDescarga(opciones = {}) {
        const params = armarParamsDescarga(opciones)
        return `${API_BASE}/api/reportes/docentes-clasificados/excel?${params.toString()}`
    }

    // GET /api/reportes/docentes-clasificados/excel — con token, vía blob
    // (versión sin "Mes"; se conserva por compatibilidad).
    async function descargarExcel(opciones = {}) {
        const params = armarParamsDescarga(opciones)
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
    // Manda las filas tal cual se ven en pantalla (previewMostrado), que
    // incluye TOTAL_CH y TOTAL_CH_MES en la primera fila de cada docente
    // (y ANTIGUEDAD en cada fila si el interruptor está activo), para que
    // el backend genere el Excel exactamente con esos datos.
    // El estado de "Mostrar Referencias", "Ocultar título" y las categorías
    // quitadas ya viene implícito en las filas que se ven en pantalla.
    async function descargarExcelPersonalizado({ gestion, version } = {}) {
        try {
            const response = await axios.post(
                `${API_BASE}/api/reportes/docentes-clasificados/excel-personalizado`,
                {
                    data: previewMostrado.value,
                    gestion: gestion || gestionEtiqueta.value,
                    version: version || versionEtiqueta.value,
                    mostrar_antiguedad: mostrarAntiguedad.value ? 1 : 0,
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
        mostrarAntiguedad.value = false
        antiguedadMapa.value = new Map()
        antiguedadClave = ''
        errorAntiguedad.value = null
        ocultarNombreTitulo.value = false
        ocultarTipoTitulo.value = false
        categoriasDisponibles.value = []
        categoriasExcluidas.value = []
        filasQuitadasPorCategoria.value = 0
        materiasQuitadasMap = new Map()
        mostrarCompartidas.value = false
        excluirPrelacion.value = false
        datosCrudos = null
        compartidasMap = new Map()
        filasOcultasCompartidas.value = 0
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

        // Acéfalas compartidas ocultadas
        filasOcultasCompartidas,

        // Compartido
        mostrarCompartidas,
        alternarMostrarCompartidas,

        // Exclusiones de Prelación (docente 100 + materias con código 18)
        excluirPrelacion,

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

        // Antigüedad
        mostrarAntiguedad,
        cargandoAntiguedad,
        errorAntiguedad,
        cargarAntiguedad,
        alternarAntiguedad,

        // Ocultar nombre / categoría del título
        ocultarNombreTitulo,
        ocultarTipoTitulo,
        etiquetaOcultarTitulo,
        alternarOcultarNombreTitulo,
        alternarOcultarTipoTitulo,
        ciclarOcultarTitulo,

        // Quitar materias por clasificación (Acéfala, Suplencia, ...)
        categoriasDisponibles,
        categoriasExcluidas,
        filasQuitadasPorCategoria,
        alternarCategoriaExcluida,
        alternarGrupoCategorias,
        establecerCategoriasExcluidas,
        limpiarCategoriasExcluidas,
        estaCategoriaExcluida,
    }
}