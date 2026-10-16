<template>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm mb-3">

    <!-- ── Cabecera del docente ──────────────────────────── -->
    <div class="flex items-center justify-between gap-3 px-5 py-1 bg-slate-800 dark:bg-slate-950 text-white">
      <div class="min-w-0">
        <h2 class="font-semibold text-sm truncate leading-tight">
          {{ docente.cod_docente }} - {{ docente.apellidos }} {{ docente.nombres }}
        </h2>
      </div>

      <div class="flex flex-wrap gap-1.5 justify-end shrink-0">
        <span
          v-for="carrera in docente.carreras"
          :key="'badge-' + carrera.plan + carrera.carrera"
          class="text-xs font-semibold px-2.5 py-1 rounded-md"
          :class="colorClasses[carrera.carrera.toLowerCase()]?.pill ?? 'bg-slate-100 text-slate-700'"
        >
          {{ carrera.carrera }} {{ carrera.subtotal }}
        </span>
      </div>
    </div>

    <!-- ── Tabla de materias ──────────────────────────────── -->
    <table class="w-full text-sm table-fixed">
      <colgroup>
        <col class="w-[46%]" />
        <col class="w-[9%]" />
        <col class="w-[9%]" />
        <col class="w-[13%]" />
        <col class="w-[13%]" />
        <col class="w-[10%]" />
      </colgroup>
      <thead>
        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
          <th class="text-left font-medium text-slate-800 dark:text-slate-400 text-xs px-4 py-2">Materia</th>
          <th class="text-left font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Plan</th>
          <th class="text-center font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2" title="Compartido (0 / 1). Si el número es clicable, tiene descripción.">C</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Regular</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Mesa</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-4 py-2">Total</th>
        </tr>
      </thead>
      <tbody>
        <template
          v-for="(item, idx) in materiasFlat"
          :key="item.key"
        >
          <!-- Fila materia (clic para expandir) -->
          <tr
            class="border-b cursor-pointer transition-colors"
            :class="[
              idx % 2 === 0
                ? 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800'
                : 'bg-slate-50 dark:bg-slate-800/60 border-slate-100 dark:border-slate-800',
              abiertos.has(item.key) ? 'bg-blue-50 dark:bg-slate-700/60' : 'hover:bg-slate-100 dark:hover:bg-slate-700/40'
            ]"
            @click="toggleMateria(item.key)"
          >
            <td class="py-2.5 pr-4" :class="item.anidado ? 'pl-9' : 'pl-4'">
              <div class="flex items-center gap-1.5 min-w-0">
                <span
                  v-if="item.anidado"
                  class="text-indigo-600 dark:text-indigo-400 text-lg font-extrabold leading-none shrink-0 -ml-5 w-4"
                  aria-hidden="true"
                >↳</span>
                <svg
                  class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-150"
                  :class="{ 'rotate-180': abiertos.has(item.key) }"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                <div class="min-w-0">
                  <p class="font-medium text-slate-700 dark:text-slate-200 truncate leading-tight text-[13px]">
                    {{ item.materia.cod_materia }} - {{ item.materia.nom_materia }} - GRP {{ item.materia.grupo }}
                  </p>
                  <p
                    v-if="item.comparte?.length"
                    class="text-[11px] leading-tight mt-0.5 truncate text-indigo-600 dark:text-indigo-300"
                    :title="'Comparte con: ' + textoComparte(item.comparte)"
                  >
                    Comparte con: {{ textoComparte(item.comparte) }}
                  </p>
                </div>
              </div>
            </td>
            <td class="px-2 py-2.5">
              <span
                class="text-[11px] font-semibold px-2 py-0.5 rounded-md"
                :class="colorClasses[item.carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'"
              >
                {{ item.carrera.carrera }}
              </span>
            </td>

            <!-- Columna C: muestra 0 o 1. Si tiene descripción (en 0 o en 1), es clicable y abre el detalle -->
            <td class="text-center px-2 py-2.5">
              <button
                v-if="tieneDetalleComp(item)"
                type="button"
                class="text-[11px] font-bold px-2.5 py-0.5 rounded-md transition-colors cursor-pointer outline-none"
                :class="item.materia.comp
                  ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-900/70'
                  : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600'"
                title="Ver detalle del compartido"
                @click.stop="abrirComp(item)"
              >
                {{ item.materia.comp ? 1 : 0 }}
              </button>
              <span
                v-else
                class="text-[11px] font-semibold"
                :class="item.materia.comp ? 'text-indigo-600 dark:text-indigo-300' : 'text-slate-300 dark:text-slate-600'"
              >
                {{ item.materia.comp ? 1 : 0 }}
              </span>
            </td>

            <td class="text-right px-2 py-2.5 tabular-nums text-slate-700 dark:text-slate-200">
              {{ item.materia.subtotal }}
            </td>
            <td class="text-right px-2 py-2.5 tabular-nums text-slate-500 dark:text-slate-400">
              {{ item.materia.subtotal_examen_mesa || 0 }}
            </td>
            <td class="text-right px-4 py-2.5 tabular-nums font-semibold text-slate-800 dark:text-slate-100">
              {{ item.materia.subtotal + (item.materia.subtotal_examen_mesa || 0) }}
            </td>
          </tr>

          <!-- Fila expandida: acciones PDF + lista de estudiantes -->
          <tr v-if="abiertos.has(item.key)" class="border-b border-slate-200 dark:border-slate-700">
            <td colspan="6" class="p-0 bg-slate-100 dark:bg-slate-800">

              <div class="px-4 pl-10 py-2 max-h-72 overflow-y-auto">

                <table class="w-full text-xs">
                  <thead>
                    <tr class="text-slate-400 dark:text-slate-500">
                      <th class="text-left font-medium py-1 pr-2 w-8">N°</th>
                      <th class="text-left font-medium py-1 pr-2 w-16">Código</th>
                      <th class="text-left font-medium py-1 pr-2">
                        <div class="flex items-center justify-between gap-2">
                          <span>Nombre</span>
                          <div class="flex items-center gap-1">
                            <button
                              type="button"
                              :disabled="generandoLista"
                              title="Ver PDF de esta materia"
                              class="inline-flex items-center justify-center w-6 h-6 rounded-md text-slate-600 bg-white border border-slate-200 hover:bg-slate-200 hover:text-slate-800 transition-colors cursor-pointer outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                              @click.stop="generarPDFMateria(item, 'ver')"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/>
                              </svg>
                            </button>
                            <button
                              type="button"
                              :disabled="generandoLista"
                              title="Imprimir lista de esta materia"
                              class="inline-flex items-center justify-center w-6 h-6 rounded-md text-slate-600 bg-white border border-slate-200 hover:bg-slate-200 hover:text-slate-800 transition-colors cursor-pointer outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                              @click.stop="generarPDFMateria(item, 'imprimir')"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
                              </svg>
                            </button>
                            <button
                              type="button"
                              :disabled="generandoLista"
                              title="Descargar PDF de esta materia"
                              class="inline-flex items-center justify-center w-6 h-6 rounded-md text-amber-700 bg-amber-100 border border-amber-300 hover:bg-amber-200 hover:text-amber-800 transition-colors cursor-pointer outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                              @click.stop="generarPDFMateria(item, 'descargar')"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                              </svg>
                            </button>
                          </div>
                        </div>
                      </th>
                      <th class="text-right font-medium py-1 w-20">Modalidad</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(est, i) in item.materia.inscritos"
                      :key="'r-' + est.codigo"
                      class="border-t border-slate-200 dark:border-slate-700"
                      :class="i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-800/60'"
                    >
                      <td class="py-1.5 pr-2 text-slate-400 dark:text-slate-500 tabular-nums">{{ i + 1 }}</td>
                      <td class="py-1.5 pr-2 font-mono text-slate-500 dark:text-slate-400">{{ est.codigo }}</td>
                      <td class="py-1.5 pr-2 text-slate-700 dark:text-slate-200 truncate">{{ est.nombre }}</td>
                      <td class="py-1.5 text-right">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md"
                              :class="colorClasses[item.carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'">
                          Regular
                        </span>
                      </td>
                    </tr>
                    <tr
                      v-for="(est, i) in item.materia.inscritos_examen_mesa"
                      :key="'e-' + est.codigo"
                      class="border-t border-slate-200 dark:border-slate-700"
                      :class="i % 2 === 0 ? 'bg-amber-50/60 dark:bg-amber-900/20' : 'bg-amber-50/30 dark:bg-amber-900/10'"
                    >
                      <td class="py-1.5 pr-2 text-slate-400 dark:text-slate-500 tabular-nums">
                        {{ (item.materia.inscritos?.length || 0) + i + 1 }}
                      </td>
                      <td class="py-1.5 pr-2 font-mono text-slate-500 dark:text-slate-400">{{ est.codigo }}</td>
                      <td class="py-1.5 pr-2 text-slate-700 dark:text-slate-200 truncate">{{ est.nombre }}</td>
                      <td class="py-1.5 text-right">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                          Mesa
                        </span>
                      </td>
                    </tr>
                    <tr v-if="!item.materia.inscritos?.length && !item.materia.inscritos_examen_mesa?.length">
                      <td colspan="4" class="py-2 text-center text-slate-400 dark:text-slate-500 italic">
                        Sin inscritos
                      </td>
                    </tr>
                  </tbody>
                </table>

              </div>
            </td>
          </tr>
        </template>
      </tbody>

      <!-- ── Fila de totales ────────────────────────────────── -->
      <tfoot>
        <tr class="bg-slate-100 dark:bg-slate-950 border-t-2 border-slate-200 dark:border-slate-700">
          <td class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-200 text-xs" colspan="3">Total</td>
          <td class="text-right px-2 py-2.5 font-semibold tabular-nums text-slate-700 dark:text-slate-200">
            {{ docente.total_inscritos }}
          </td>
          <td class="text-right px-2 py-2.5 font-semibold tabular-nums text-slate-500 dark:text-slate-400">
            {{ docente.total_examen_mesa || 0 }}
          </td>
          <td class="text-right px-4 py-2.5 font-bold tabular-nums text-slate-900 dark:text-white">
            {{ docente.total_inscritos + (docente.total_examen_mesa || 0) }}
          </td>
        </tr>
      </tfoot>
    </table>

    <!-- ── Modal: detalle del compartido ──────────────────────
         Va en <Teleport to="body"> porque el card tiene overflow-hidden
         y un popover dentro de la tabla quedaría cortado. -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-150 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="compSeleccionado"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4"
          @click.self="cerrarComp"
        >
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl w-full max-w-sm p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                Compartido: {{ compSeleccionado.materia.comp ? 1 : 0 }}
                <span v-if="compSeleccionado.rol" class="font-normal text-slate-500 dark:text-slate-400">
                  · {{ compSeleccionado.rol === 'padre' ? 'Padre' : 'Hijo' }}
                </span>
              </h3>
              <button
                type="button"
                class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer outline-none"
                aria-label="Cerrar"
                @click="cerrarComp"
              >✕</button>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
              {{ compSeleccionado.materia.cod_materia }} - {{ compSeleccionado.materia.nom_materia }}
              · GRP {{ compSeleccionado.materia.grupo }}
              · {{ compSeleccionado.carrera.carrera }}
            </p>

            <p
              v-if="compSeleccionado.materia.descripcion_comp"
              class="text-sm text-slate-700 dark:text-slate-200 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-lg px-3 py-2"
            >
              {{ compSeleccionado.materia.descripcion_comp }}
            </p>
            <p
              v-else-if="!compSeleccionado.comparte?.length"
              class="text-sm text-slate-400 dark:text-slate-500 italic"
            >Sin descripción.</p>

            <div v-if="compSeleccionado.comparte?.length" class="mt-3">
              <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400 mb-1.5">
                Comparte con
              </p>
              <ul class="flex flex-col gap-2">
                <li
                  v-for="r in compSeleccionado.comparte"
                  :key="r.carrera + '-' + r.cod_materia + '-' + r.grupo"
                  class="flex items-start gap-2"
                >
                  <span
                    class="text-[11px] font-semibold px-2 py-0.5 rounded-md shrink-0"
                    :class="colorClasses[r.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'"
                  >{{ r.carrera }}</span>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-tight">
                      {{ r.cod_materia }} - {{ r.nom_materia }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      GRP {{ r.grupo }} · {{ r.rol === 'padre' ? 'Padre' : 'Hijo' }}<template v-if="r.inscritos != null"> · Ins: {{ r.inscritos }}</template>
                    </p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useReporteInscritosLista } from '../composables/useReporteInscritosLista'
import { mostrarLoaderPdf } from '../utils/pdfLoader'

const props = defineProps({
  docente: { type: Object, required: true },
  anio: { type: Number, default: null },
  periodo: { type: Number, default: null },
})

const { generandoLista, exportarListaCompleta } = useReporteInscritosLista()

const abiertos = ref(new Set())

// ─── Modal del compartido ────────────────────────────────────────────────
const compSeleccionado = ref(null)

function abrirComp(item) {
  compSeleccionado.value = item
}

function cerrarComp() {
  compSeleccionado.value = null
}

function onKeydown(e) {
  if (e.key === 'Escape' && compSeleccionado.value) cerrarComp()
}
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))

function materiaKey(carrera, materia) {
  return `${carrera.plan}-${materia.cod_materia}-${materia.grupo}`
}

const materiasBase = computed(() =>
  props.docente.carreras.flatMap((carrera) =>
    carrera.materias.map((materia) => ({
      carrera,
      materia,
      key: materiaKey(carrera, materia),
    }))
  )
)

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

function infoRelacion(item, rol) {
  return {
    rol,
    carrera: item.carrera.carrera,
    cod_materia: item.materia.cod_materia,
    nom_materia: item.materia.nom_materia,
    grupo: item.materia.grupo,
    inscritos: item.materia.subtotal,
  }
}

// Agrupa padres e hijos del docente. Misma idea que el reporte de horarios:
//   1) Mismo ORDEN (M1, M2...) dentro del docente: COMP 0 = origen/padre,
//      COMP 1 = derivadas/hijos. Un origen puede tener varias derivadas.
//   2) Lo que quede suelto: se usa lo que calculó el backend
//      (padre_clave / comparte_con, por código de materia + grupo).
// Devuelve la lista plana: padre arriba, hijos justo debajo (anidado = true).
const materiasFlat = computed(() => {
  const base = materiasBase.value
  const porKey = new Map(base.map((i) => [i.key, i]))
  const porCarreraCodGrupo = new Map(
    base.map((i) => [`${i.carrera.carrera}|${i.materia.cod_materia}|${i.materia.grupo}`, i])
  )

  const clusters = new Map() // key del padre -> { padre, hijos: [] }
  const hijoDe = new Map()   // key del hijo  -> key del padre

  function vincular(padre, hijo) {
    if (padre.key === hijo.key) return
    if (hijoDe.has(hijo.key) || hijoDe.has(padre.key)) return
    if (clusters.has(hijo.key)) return // un padre no puede ser hijo a la vez
    if (!clusters.has(padre.key)) clusters.set(padre.key, { padre, hijos: [] })
    clusters.get(padre.key).hijos.push(hijo)
    hijoDe.set(hijo.key, padre.key)
  }

  // 1) Por ORDEN
  const porOrden = new Map()
  base.forEach((i) => {
    const orden = norm(i.materia.orden_comp)
    if (!orden) return
    if (!porOrden.has(orden)) porOrden.set(orden, [])
    porOrden.get(orden).push(i)
  })

  for (const [, grupo] of porOrden) {
    if (grupo.length < 2) continue
    const origenes = grupo.filter((i) => !i.materia.comp)
    const derivadas = grupo.filter((i) => i.materia.comp)
    if (!origenes.length || !derivadas.length) continue

    derivadas.forEach((d) => {
      let origen = origenes.length === 1 ? origenes[0] : null
      if (!origen) origen = origenes.find((o) => o.key === d.materia.padre_clave)
      if (!origen) origen = origenes.find((o) => !clusters.has(o.key))
      if (origen) vincular(origen, d)
    })
  }

  // 2) Lo que quedó suelto: relación calculada por el backend
  base.forEach((i) => {
    if (!i.materia.comp || hijoDe.has(i.key)) return
    let padre = i.materia.padre_clave ? porKey.get(i.materia.padre_clave) : null
    if (!padre) {
      const p = (i.materia.comparte_con ?? []).find((r) => r.rol === 'padre')
      if (p) padre = porCarreraCodGrupo.get(`${p.carrera}|${p.cod_materia}|${p.grupo}`)
    }
    if (padre) vincular(padre, i)
  })

  // Lista final
  const salida = []
  base.forEach((i) => {
    if (hijoDe.has(i.key)) return

    const cl = clusters.get(i.key)
    if (cl) {
      salida.push({
        ...i,
        anidado: false,
        rol: 'padre',
        comparte: cl.hijos.map((h) => infoRelacion(h, 'hijo')),
      })
      cl.hijos.forEach((h) => {
        salida.push({
          ...h,
          anidado: true,
          rol: 'hijo',
          comparte: [infoRelacion(i, 'padre')],
        })
      })
    } else {
      // Sin pareja en este docente (puede que el otro lado sea de otro docente):
      // se muestra lo que sepa el backend.
      salida.push({
        ...i,
        anidado: false,
        rol: i.materia.rol_comp ?? null,
        comparte: i.materia.comparte_con ?? [],
      })
    }
  })
  return salida
})

// "ADM · 1301132 - NOMBRE (GRP 42) · Ins: 35" para cada materia con la que comparte
function textoComparte(lista) {
  return (lista ?? [])
    .map((r) => {
      const ins = r.inscritos != null ? ` · Ins: ${r.inscritos}` : ''
      return `${r.carrera} · ${r.cod_materia} - ${r.nom_materia} (GRP ${r.grupo})${ins}`
    })
    .join('  |  ')
}

// El número de la columna C es clicable si hay descripción o materias relacionadas
function tieneDetalleComp(item) {
  return !!item.materia.descripcion_comp || !!item.comparte?.length
}

function toggleMateria(key) {
  const next = new Set(abiertos.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  abiertos.value = next
}

// ─── PDF de UNA sola materia ─────────────────────────────────────────────
// Reutiliza el mismo generador de la lista completa, pasándole un docente
// con una única carrera y una única materia.
async function generarPDFMateria(item, modo = 'descargar') {
  const { carrera, materia } = item

  // Ventana pre-abierta solo para 'ver' (evita el bloqueo de popups)
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
    if (ventana) mostrarLoaderPdf(ventana, 'Generando lista de la materia...')
  }

  const abandonos = materia.subtotal_abandonos ?? 0

  const dataMateria = [{
    ...props.docente,
    carreras: [{
      ...carrera,
      subtotal: materia.subtotal,
      materias: [materia],
    }],
    total_inscritos: materia.subtotal,
    total_abandonos: abandonos,
  }]

  const nombreArchivo = `Lista_${materia.cod_materia}_Gr${materia.grupo}_${props.anio}_${props.periodo}.pdf`

  await exportarListaCompleta(dataMateria, props.anio, props.periodo, modo, ventana, {
    titulo: 'LISTA DE INSCRITOS POR MATERIA',
    etiquetaTotal: 'TOTAL INSCRITOS DE LA MATERIA',
    filename: nombreArchivo,
  })
}

const colorClasses = {
  adm: { badge: 'bg-blue-100 text-blue-700',       pill: 'bg-blue-100 text-blue-700' },
  eco: { badge: 'bg-emerald-100 text-emerald-700', pill: 'bg-emerald-100 text-emerald-700' },
  ccp: { badge: 'bg-purple-100 text-purple-700',   pill: 'bg-purple-100 text-purple-700' },
  com: { badge: 'bg-orange-100 text-orange-700',   pill: 'bg-orange-100 text-orange-700' },
  fin: { badge: 'bg-yellow-100 text-yellow-700',   pill: 'bg-yellow-100 text-yellow-700' },
  nn:  { badge: 'bg-slate-100 text-slate-600',     pill: 'bg-slate-100 text-slate-600' },
}
</script>