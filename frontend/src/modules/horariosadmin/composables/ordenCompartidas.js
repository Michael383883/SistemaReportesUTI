// composables/ordenCompartidas.js
// Une cada derivada (COMP 1) con su origen (COMP 0):
//   1) Mismo horario exacto (día + hora + aula) y distinto plan.
//      Si hay varios candidatos, gana el que tenga el mismo ORDEN.
//   2) Sin coincidencia de horario: mismo ORDEN y un único origen.
//   3) Sin pareja: queda como fila normal (conserva su CH).
// Devuelve lista plana [{ mat, anidado }]: padre y debajo sus hijas.

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

function firmaHorario(mat) {
  return (mat.sesiones ?? [])
    .map(s => `${s.dia}|${s.horario}|${s.ambiente}`)
    .sort()
    .join(';')
}

export function ordenarFilasCompartidas(materiasAgrupadas = [], horarios = []) {
  // Respaldo: si la materia agrupada no trae `orden`, se busca en las filas crudas
  const ordenPorClave = new Map()
    ; (horarios ?? []).forEach(h => {
      const o = norm(h.ORDEN)
      if (!o) return
      const k = `${norm(h.CARRERA)}|${norm(h.MATERIA)}|${norm(h.GRUPO)}`
      if (!ordenPorClave.has(k)) ordenPorClave.set(k, o)
    })
  const ordenDe = mat =>
    norm(mat.orden ?? mat.ORDEN) ||
    ordenPorClave.get(`${norm(mat.carrera)}|${norm(mat.materia)}|${norm(mat.grupo)}`) ||
    ''

  // Orden estable: ORDEN → MATERIA → GRUPO → PLAN
  const mats = [...materiasAgrupadas].sort((a, b) =>
    ordenDe(a).localeCompare(ordenDe(b), 'es', { numeric: true }) ||
    norm(a.materia).localeCompare(norm(b.materia), 'es', { numeric: true }) ||
    norm(a.grupo).localeCompare(norm(b.grupo), 'es', { numeric: true }) ||
    norm(a.plan).localeCompare(norm(b.plan), 'es', { numeric: true })
  )

  const firmas = mats.map(firmaHorario)
  const ordenes = mats.map(ordenDe)

  const origenes = []
  const derivadas = []
  mats.forEach((m, i) => {
    const c = norm(m.comp)
    if (c === '0') origenes.push(i)
    else if (c === '1') derivadas.push(i)
  })

  const hijosDe = new Map()
  const esHija = new Set()

  for (const d of derivadas) {
    const ordenD = ordenes[d]
    const candidatos = origenes.filter(o => norm(mats[o].plan) !== norm(mats[d].plan))

    const porHorario = firmas[d]
      ? candidatos.filter(o => firmas[o] === firmas[d])
      : []

    let elegido = null
    if (porHorario.length === 1) {
      elegido = porHorario[0]
    } else if (porHorario.length > 1) {
      elegido =
        porHorario.find(o => ordenD && ordenes[o] === ordenD) ?? porHorario[0]
    } else if (ordenD) {
      const mismoOrden = candidatos.filter(o => ordenes[o] === ordenD)
      if (mismoOrden.length === 1) elegido = mismoOrden[0]
    }

    if (elegido !== null) {
      if (!hijosDe.has(elegido)) hijosDe.set(elegido, [])
      hijosDe.get(elegido).push(d)
      esHija.add(d)
    }
  }

  const salida = []
  mats.forEach((mat, idx) => {
    if (esHija.has(idx)) return
    salida.push({ mat, anidado: false })
      ; (hijosDe.get(idx) ?? []).forEach(h => salida.push({ mat: mats[h], anidado: true }))
  })
  return salida
}