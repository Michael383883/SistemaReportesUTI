// composables/ordenCompartidas.js
// Ordena las materias de un docente: cada padre (COMP 0) seguido de sus hijos (COMP 1).
// Devuelve [{ mat, anidado }] en el orden en que debe mostrarse.
// Lo usan la vista (HorarioDocenteCard) y el PDF (usePdfResumenDos).

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

export function ordenarFilasCompartidas(mats = [], horarios = []) {
  // ORDEN por carrera+materia+grupo, tomado de las filas crudas
  const ordenPorClave = new Map()
  ;(horarios ?? []).forEach((h) => {
    const orden = norm(h.ORDEN)
    if (!orden) return
    const k = `${norm(h.CARRERA)}|${norm(h.MATERIA)}|${norm(h.GRUPO)}`
    if (!ordenPorClave.has(k)) ordenPorClave.set(k, orden)
  })

  const ordenDe = (mat) =>
    norm(mat.orden ?? mat.ORDEN) ||
    ordenPorClave.get(`${norm(mat.carrera)}|${norm(mat.materia)}|${norm(mat.grupo)}`) ||
    ''

  const hijoDe = new Map()   // índice hijo  -> índice padre
  const hijosDe = new Map()  // índice padre -> [índices hijos]

  function vincular(padre, hijo) {
    if (padre === hijo || hijoDe.has(hijo) || hijoDe.has(padre) || hijosDe.has(hijo)) return
    hijoDe.set(hijo, padre)
    if (!hijosDe.has(padre)) hijosDe.set(padre, [])
    hijosDe.get(padre).push(hijo)
  }

  // 1) Por ORDEN
  const porOrden = new Map()
  mats.forEach((m, idx) => {
    const orden = ordenDe(m)
    if (!orden) return
    if (!porOrden.has(orden)) porOrden.set(orden, [])
    porOrden.get(orden).push(idx)
  })

  for (const [, indices] of porOrden) {
    if (indices.length < 2) continue
    const origenes = indices.filter((i) => norm(mats[i].comp) === '0')
    const derivadas = indices.filter((i) => norm(mats[i].comp) === '1')
    if (!origenes.length || !derivadas.length) continue

    derivadas.forEach((d, n) => {
      const origen = origenes.length === 1 ? origenes[0] : origenes[n]
      if (origen !== undefined) vincular(origen, d)
    })
  }

  // 2) Sueltas con COMPARTIDO: misma CH y distinta carrera
  const sueltas = mats
    .map((m, idx) => idx)
    .filter((idx) => !hijoDe.has(idx) && !hijosDe.has(idx) && norm(mats[idx].compartido) !== '')

  const usadas = new Set()
  for (let a = 0; a < sueltas.length; a++) {
    if (usadas.has(a)) continue
    const ma = mats[sueltas[a]]
    for (let b = a + 1; b < sueltas.length; b++) {
      if (usadas.has(b)) continue
      const mb = mats[sueltas[b]]
      const mismaChYDistintaCarrera =
        norm(ma.carga) === norm(mb.carga) && norm(ma.carrera) !== norm(mb.carrera)
      if (!mismaChYDistintaCarrera) continue

      const compA = norm(ma.comp)
      const compB = norm(mb.comp)
      if (compA === '0' && compB === '1') vincular(sueltas[a], sueltas[b])
      else if (compA === '1' && compB === '0') vincular(sueltas[b], sueltas[a])
      else continue

      usadas.add(a)
      usadas.add(b)
      break
    }
  }

  // Lista final
  const salida = []
  mats.forEach((mat, idx) => {
    if (hijoDe.has(idx)) return
    salida.push({ mat, anidado: false })
    ;(hijosDe.get(idx) ?? []).forEach((h) => salida.push({ mat: mats[h], anidado: true }))
  })
  return salida
}