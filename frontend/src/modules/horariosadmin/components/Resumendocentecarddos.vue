<template>
  <div
    class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header -->
    <div
      class="flex justify-between items-center px-5 py-1 bg-slate-800 text-white"
    >
      <div class="flex flex-col min-w-0">
        <h2 class="text-[15px] font-bold truncate">
         {{ docente.docente }} - {{ docente.apellidos }} {{ docente.nombres }} 
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
          <tr class="bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700">
            <th class="px-2 py-2 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Plan - Nvl
            </th>
            <th class="px-2 py-2 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Materia
            </th>
            <th class="px-2 py-2 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Grp
            </th>

            <th class="px-2 py-2 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Insc.
            </th>
            <th class="px-3 py-2 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 min-w-[220px]">
              Comparte con
            </th>
            <th class="px-2 py-2 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              CH
            </th>
            <th class="px-2 py-2 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Ins. Total
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(fila, i) in filasAgrupadas"
            :key="i"
            :class="i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-gray-100 dark:bg-slate-800'"
            class="border-b border-slate-200 dark:border-slate-700 hover:bg-blue-50 dark:hover:bg-slate-700/60 transition-colors"
          >
            <!-- Plan - Nivel -->
            <td class="text-center px-2 py-1.5 align-top">
              <span
                class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border whitespace-nowrap"
                :style="{
                  background: colorCarrera(fila.principal.CARRERA).bg,
                  color: colorCarrera(fila.principal.CARRERA).text,
                  borderColor: colorCarrera(fila.principal.CARRERA).border,
                }"
              >
                {{ fila.principal.CARRERA }} - {{ fila.principal.NIVEL }}
              </span>
            </td>

            <!-- Materia: código + nombre en una sola línea, compacto -->
            <td class="px-3 py-1.5 align-top max-w-[220px]">
              <span class="block font-semibold text-slate-800 dark:text-slate-100 text-[11px] leading-tight truncate" :title="fila.principal.NOMBRE">
                <span class="text-slate-800 dark:text-slate-100 font-medium">{{ fila.principal.MATERIA }}</span>
                {{ fila.principal.NOMBRE }}
              </span>
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-1.5 align-top font-semibold text-slate-600 dark:text-slate-300">
              {{ fila.principal.GRUPO }}
            </td>

            <!-- Inscritos -->
            <td class="text-center px-2 py-1.5 align-top font-semibold text-slate-700 dark:text-slate-200">
              {{ fila.principal.TOTAL_NORMAL ?? '—' }}
            </td>

            <!-- Comparte con -->
            <td class="px-3 py-1.5 align-top">
              <div v-if="fila.hermanas && fila.hermanas.length" class="flex flex-col gap-1">
                <div
                  v-for="(h, hi) in fila.hermanas"
                  :key="hi"
                  class="flex items-center gap-1.5 flex-wrap text-[10.5px] leading-snug"
                >
                  <span
                    class="inline-block px-1 py-0.5 rounded text-[9.5px] font-bold border whitespace-nowrap shrink-0"
                    :style="{
                      background: colorCarrera(h.CARRERA).bg,
                      color: colorCarrera(h.CARRERA).text,
                      borderColor: colorCarrera(h.CARRERA).border,
                    }"
                  >
                    {{ h.CARRERA }} - {{ h.NIVEL }}
                  </span>
                  <span class="text-slate-800 dark:text-slate-100 font-medium truncate">
                    {{ h.MATERIA }} - {{ h.NOMBRE }}
                  </span>
                  <span class="text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    · Grp {{ h.GRUPO }} · Ins: {{ h.TOTAL_NORMAL ?? '—' }}
                  </span>
                </div>

                <span class="text-[10px] w-fit mt-0.5">
                  <span class="font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-700">
                    Suma: {{ resumenSuma(fila) }} = {{ calcularTotal(fila) }}
                  </span>
                </span>
              </div>
              <span v-else class="text-slate-300 dark:text-slate-600">—</span>
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-1.5 align-top">
              <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 font-bold text-[11px]">
                {{ fila.principal.CARGA_HORARIA }}
              </span>
            </td>

            <!-- Ins. Total -->
            <td class="text-center px-2 py-1.5 align-top">
              <span class="inline-block px-2 py-0.5 rounded-md font-bold border text-[12px] bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600">
                {{ calcularTotal(fila) }}
              </span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-100 dark:bg-slate-800 border-t-2 border-slate-200 dark:border-slate-700">
            <td colspan="5" class="text-right px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
              Total
            </td>
            <td class="text-center py-2">
              <span class="inline-block px-2.5 py-1 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-[12px] font-extrabold">
                {{ totalChReal }}
              </span>
              <span
                translate="no"
                class="inline-block px-3 py-1 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-700 text-sm font-extrabold"
              >
                Mes({{ totalChReal * 4 }})
              </span>
            </td>

            <td class="text-center py-2">
              <span class="inline-block px-2.5 py-1 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-[12px] font-extrabold">
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