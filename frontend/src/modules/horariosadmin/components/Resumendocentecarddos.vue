<template>
  <div
    class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header -->
     <div
  class="flex justify-between items-center px-5 py-3 bg-slate-800 text-white"
>
      <div class="flex flex-col min-w-0">
        <h2 class="text-[15px] font-bold truncate">
          {{ docente.apellidos }} {{ docente.nombres }} - {{ docente.docente }}
        </h2>
        
      </div>
      <div class="flex items-center gap-3 shrink-0 ml-4">
        
        <div class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1">
          {{ filasAgrupadas.length }} materias
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-xs" style="table-layout: auto;">
        <thead>
          <tr class="bg-slate-50 border-b-2 border-slate-200">

            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
             Plan - Nvl
            </th>

            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[150px]">
              Materia
            </th>
            
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Grp
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              CH
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Inscritos
            </th>
            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[220px]">
              Comparte con
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Ins. Total
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(fila, i) in filasAgrupadas"
            :key="i"
            class="border-b border-slate-100 hover:bg-slate-50 transition-colors"
          >
          <td class="text-center px-2 py-2">
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border"
                :style="{
                  background: colorCarrera(fila.principal.CARRERA).bg,
                  color: colorCarrera(fila.principal.CARRERA).text,
                  borderColor: colorCarrera(fila.principal.CARRERA).border,
                }"
              >
                {{ fila.principal.CARRERA }} - {{ fila.principal.NIVEL }}
              </span>
            </td>

            <td class="px-3 py-2">
              <span class="font-semibold text-slate-800 text-[12px] leading-tight">
                {{ fila.principal.NOMBRE }}
              </span>
              <br />
              <span class="text-[10px] font-semibold text-slate-400">
                {{ fila.principal.MATERIA }}
              </span>
            </td>

            

            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ fila.principal.GRUPO }}
            </td>

            <td class="text-center px-2 py-2">
              <span class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold">
                {{ fila.principal.CARGA_HORARIA }}
              </span>
            </td>

            <td class="text-center px-2 py-2">
              <div class="flex flex-col items-center">
                <span class="font-semibold text-slate-700">
                  {{ fila.principal.TOTAL_NORMAL ?? '—' }}
                </span>
              </div>
            </td>
<td class="px-3 py-2">
  <div v-if="fila.hermanas && fila.hermanas.length" class="flex flex-col gap-2">
    <!-- Contenedor en fila, con wrap automático cuando no hay espacio -->
    <div class="flex flex-row flex-wrap gap-3">
      <div
        v-for="(h, hi) in fila.hermanas"
        :key="hi"
        class="flex flex-col gap-0.5 border border-slate-600/40 rounded-md px-2 py-1 bg-white/5 min-w-[140px]"
      >
        <span class="text-[11px] font-semibold text-slate-700">
          {{ h.NOMBRE }}
        </span>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[10px] text-slate-800">
            {{ h.MATERIA }} - Ins: {{ h.TOTAL_NORMAL ?? '—' }}
          </span>
          <span
            class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-bold border whitespace-nowrap"
            :style="{
              background: colorCarrera(h.CARRERA).bg,
              color: colorCarrera(h.CARRERA).text,
              borderColor: colorCarrera(h.CARRERA).border,
            }"
          >
            {{ h.CARRERA }} - {{ h.NIVEL }}
          </span>
        </div>
        <span class="text-[10px] w-fit">
          <span class="font-bold text-black">Grp: {{ h.GRUPO }}</span>
        </span>
      </div>
    </div>

    <span class="text-[10px] w-fit">
      <span class="font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
        Suma: {{ resumenSuma(fila) }} = {{ calcularTotal(fila) }}
      </span>
    </span>
  </div>
  <span v-else class="text-slate-300 text-center block">—</span>
</td>

            <td class="text-center px-2 py-2">
              <span
                class="inline-block px-2 py-1 rounded-md font-bold border text-sm bg-slate-50 text-slate-700 border-slate-200"
              >
                {{ calcularTotal(fila) }}
              </span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-50 border-t-2 border-slate-200">
            <td colspan="3" class="text-right px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600">
              Total
            </td>
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-blue-100 text-blue-800 border border-blue-300 text-sm font-extrabold">
                {{ totalChReal }} CH
              </span>
            </td>
            <td class="text-center py-2.5 text-[10px] text-slate-400 italic">—</td>
            <td class="text-center py-2.5 text-[10px] text-slate-400 italic">—</td>
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-blue-100 text-blue-800 border border-blue-300 text-sm font-extrabold">
                {{ totalGeneral }}
              </span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useHorarioResumen } from '../composables/useHorarioResumen'

const { colorCarrera } = useHorarioResumen()

const props = defineProps({
  docente: { type: Object, required: true },
})

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

const filasAgrupadas = computed(() => {
  const materias = props.docente.materias ?? []
  const usada = new Array(materias.length).fill(false)
  const filas = []

  const porOrden = new Map()
  materias.forEach((m, idx) => {
    const orden = norm(m.ORDEN)
    if (!orden) return
    if (!porOrden.has(orden)) porOrden.set(orden, [])
    porOrden.get(orden).push(idx)
  })

  for (const [, indices] of porOrden) {
    if (indices.length < 2) continue
    const origenes = indices.filter(i => norm(materias[i].COMP) === '0')
    const derivadas = indices.filter(i => norm(materias[i].COMP) === '1')

    if (origenes.length === 1 && derivadas.length >= 1) {
      // ✅ Un solo origen puede tener VARIAS derivadas (comparte con más de una carrera)
      const iOrigen = origenes[0]
      filas.push({
        principal: materias[iOrigen],
        hermanas: derivadas.map(i => materias[i]),
      })
      usada[iOrigen] = true
      derivadas.forEach(i => { usada[i] = true })
    } else {
      // Caso general / fallback: varios orígenes en el mismo ORDEN, emparejar 1 a 1
      const pares = Math.min(origenes.length, derivadas.length)
      for (let p = 0; p < pares; p++) {
        const iOrigen = origenes[p]
        const iDerivada = derivadas[p]
        if (usada[iOrigen] || usada[iDerivada]) continue
        filas.push({ principal: materias[iOrigen], hermanas: [materias[iDerivada]] })
        usada[iOrigen] = true
        usada[iDerivada] = true
      }
      ;[...origenes, ...derivadas].forEach(i => {
        if (!usada[i]) {
          filas.push({ principal: materias[i], hermanas: [] })
          usada[i] = true
        }
      })
    }
  }

  const sinProcesar = materias
    .map((m, idx) => ({ m, idx }))
    .filter(({ idx }) => !usada[idx] && norm(materias[idx].COMPARTIDO) !== '')

  const usadaSP = new Set()
  for (let a = 0; a < sinProcesar.length; a++) {
    if (usadaSP.has(a)) continue
    const ma = sinProcesar[a].m
    let encontrado = -1
    for (let b = a + 1; b < sinProcesar.length; b++) {
      if (usadaSP.has(b)) continue
      const mb = sinProcesar[b].m
      const mismaChYDistintaCarrera =
        norm(ma.CARGA_HORARIA) === norm(mb.CARGA_HORARIA) &&
        norm(ma.CARRERA) !== norm(mb.CARRERA)
      if (mismaChYDistintaCarrera) {
        encontrado = b
        break
      }
    }
    if (encontrado !== -1) {
      const [origen, derivada] =
        norm(sinProcesar[a].m.COMP) === '0'
          ? [sinProcesar[a], sinProcesar[encontrado]]
          : [sinProcesar[encontrado], sinProcesar[a]]
      filas.push({ principal: origen.m, hermanas: [derivada.m] })
      usadaSP.add(a)
      usadaSP.add(encontrado)
      usada[sinProcesar[a].idx] = true
      usada[sinProcesar[encontrado].idx] = true
    } else {
      filas.push({ principal: ma, hermanas: [] })
      usadaSP.add(a)
      usada[sinProcesar[a].idx] = true
    }
  }

  materias.forEach((m, idx) => {
    if (!usada[idx]) filas.push({ principal: m, hermanas: [] })
  })

  return filas
})

function calcularTotal(fila) {
  const p = Number(fila.principal.TOTAL_NORMAL) || 0
  const h = (fila.hermanas || []).reduce(
    (acc, m) => acc + (Number(m.TOTAL_NORMAL) || 0),
    0
  )
  return p + h
}

function resumenSuma(fila) {
  const partes = [
    Number(fila.principal.TOTAL_NORMAL) || 0,
    ...(fila.hermanas || []).map(m => Number(m.TOTAL_NORMAL) || 0),
  ]
  return partes.join(' + ')
}

const totalChReal = computed(() =>
  filasAgrupadas.value.reduce(
    (acc, fila) => acc + (Number(fila.principal.CARGA_HORARIA) || 0),
    0
  )
)

const totalGeneral = computed(() =>
  filasAgrupadas.value.reduce((acc, fila) => acc + calcularTotal(fila), 0)
)
</script>