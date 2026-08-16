<template>
  <div class="px-6 py-6">

    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight m-0 mb-0.5">
          Edición de Modo de Ingreso
        </h1>
        <p class="text-xs text-slate-500 m-0">
          Buscá un docente, elegí el nuevo modo de ingreso por materia y aplicá los cambios cuando estés listo.
        </p>
      </div>
    </div>

    <!-- Vista de resultado final tras aplicar los cambios -->
    <div v-if="fase === 'resultado'" class="space-y-5">
      <!-- DESPUÉS -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-4">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-emerald-100">
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-medium text-slate-800 m-0">Modo de ingreso actualizado</h2>
            <p class="text-xs text-slate-500 m-0 mt-0.5">
              {{ ultimosCambios.length }} registro{{ ultimosCambios.length !== 1 ? 's' : '' }} modificado{{ ultimosCambios.length !== 1 ? 's' : '' }}
              · {{ resultadoGuardado?.total_grupos_actualizados ?? 0 }} actualizados en grupos
              · {{ resultadoGuardado?.total_detalles_actualizados ?? 0 }} actualizados en detalle de resolución
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-500">Docente</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-500">Plan</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-500">Materia</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-500">Grupo</th>
                <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-500">Nuevo tipo de ingreso</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(c, i) in ultimosCambios" :key="i" class="hover:bg-emerald-50/60 transition-colors">
                <td class="px-4 py-2.5 text-slate-700 font-mono">{{ c.cod_docente }}</td>
                <td class="px-4 py-2.5 text-slate-500 font-mono">{{ c.cod_plan }}</td>
                <td class="px-4 py-2.5 text-slate-500 font-mono">{{ c.cod_materia }}</td>
                <td class="px-4 py-2.5 text-slate-500">{{ c.grupo ?? '—' }}</td>
                <td class="px-4 py-2.5 text-emerald-600 font-semibold">{{ c.tipo_ingreso || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-end px-6 py-4 border-t border-slate-200 bg-slate-50 gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-medium border border-slate-300 text-slate-600 bg-white hover:bg-slate-50 transition-colors"
            @click="editarOtraMas"
          >
            Editar otro docente
          </button>
        </div>
      </div>
    </div>

    <!-- Flujo principal -->
    <template v-else>
      <!-- Buscador de docente -->
      <div class="rounded-xl overflow-hidden mb-4">
        <div class="px-5 py-3">
          <DocenteSearch
            :docentes="docentes"
            :selectedDocente="selectedDocente"
            :loading="loadingDocentes"
            @select="onSeleccionarDocente"
            @clear="onLimpiarDocente"
          />
        </div>
      </div>

      <!-- Materias del docente seleccionado -->
      <div v-if="selectedDocente" class="mb-4" :class="hayCambiosPendientes ? 'pb-24' : ''">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Materias dictadas — {{ selectedDocente.nombres ?? selectedDocente.NOMBRES }} {{ selectedDocente.apellidos ?? selectedDocente.APELLIDOS }}
          </p>

          <!-- Filtros por año y gestión -->
          <div class="flex items-center gap-2">
            <select
              v-model="filtroAnio"
              class="bg-white border border-slate-300 text-slate-700 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-colors
                     cursor-pointer"
            >
              <option value="">Todos los años</option>
              <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
            </select>

            <select
              v-model="filtroGestion"
              class="bg-white border border-slate-300 text-slate-700 text-xs rounded-lg px-2.5 py-1.5
                     outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-colors
                     cursor-pointer"
            >
              <option value="">Todas las gestiones</option>
              <option v-for="g in gestionesDisponibles" :key="g" :value="g">{{ g }}</option>
            </select>

            <button
              v-if="filtroAnio || filtroGestion"
              type="button"
              class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 transition-colors"
              @click="filtroAnio = ''; filtroGestion = ''"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              Limpiar
            </button>
          </div>
        </div>

        <div v-if="loadingReporte" class="h-40 rounded-xl bg-slate-100 border border-slate-200 animate-pulse"/>
        <div v-else-if="errorReporte" class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-xs">
          {{ errorReporte }}
        </div>
        <TipoIngresoTabla
          v-else-if="reporte"
          :materias="materiasFiltradas"
          :cambios="cambiosPendientes"
          :docente-cod="docenteCodActual"
          @cambiar="onCambiar"
        />
      </div>

      <!--
        Barra de "Aplicar cambios": queda fija (sticky) al fondo del viewport
        en cuanto hay al menos un cambio pendiente, así siempre es visible
        sin importar cuánto scrolleés la tabla — no hace falta bajar hasta
        el final de la página para encontrarla.
      -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-3"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-3"
      >
        <div
          v-if="hayCambiosPendientes"
          class="fixed bottom-4 left-1/2 -translate-x-1/2 w-[calc(100%-3rem)] max-w-5xl z-40
                 rounded-xl border border-amber-300 bg-white shadow-lg shadow-amber-900/10 overflow-hidden"
        >
          <div class="flex items-center gap-3 px-5 py-3.5">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-amber-100">
              <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 m-0">
                {{ cantidadCambios }} materia{{ cantidadCambios !== 1 ? 's' : '' }} con modo de ingreso modificado
              </p>
              <p v-if="errorGuardado" class="text-xs text-red-500 m-0 mt-0.5">{{ errorGuardado }}</p>
              <p v-else class="text-xs text-slate-500 m-0 mt-0.5 truncate">
                Revisá las filas marcadas en verde antes de aplicar.
              </p>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                type="button"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium border border-slate-800 text-slate-800 bg-white hover:bg-slate-50 transition-colors"
                :disabled="guardando"
                @click="limpiarCambios"
              >
                Descartar
              </button>
              <button
                type="button"
                :disabled="guardando"
                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-slate-100 text-xs font-semibold rounded-lg transition-colors shadow-sm shadow-amber-500/20"
                @click="handleAplicar"
              >
                <svg v-if="guardando" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ guardando ? 'Aplicando…' : 'Aplicar cambios' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </template>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import DocenteSearch from '../../docentes/components/DocenteSearch.vue'
import { useDocentes } from '../../docentes/composables/useDocentes'
import { useReporte } from '../../reportes/composables/useReporte'
import { useTipoIngreso } from '../composables/useTipoIngreso'
import TipoIngresoTabla from '../components/TipoIngresoTabla.vue'

// ─── Docentes ───────────────────────────────────────────────────
const {
  loading: loadingDocentes,
  docentes,            // lista completa — se pasa directo a DocenteSearch
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection: clearSelectionDocente,
} = useDocentes()

fetchDocentes()

const docenteCodActual = computed(() =>
  selectedDocente.value
    ? (selectedDocente.value.cod_docente ?? selectedDocente.value.CODIGO ?? selectedDocente.value.codigo)
    : null
)

// ─── Reporte de materias del docente seleccionado ────────────────
const { reporte, loading: loadingReporte, error: errorReporte, generarReporte, limpiarReporte } = useReporte()

function onSeleccionarDocente(doc) {
  selectDocente(doc)
  const codigo = doc.codigo ?? doc.CODIGO
  if (codigo) generarReporte(codigo)
}

function onLimpiarDocente() {
  clearSelectionDocente()
  limpiarReporte()
  filtroAnio.value = ''
  filtroGestion.value = ''
  limpiarCambios()
}

// ─── Edición de tipo de ingreso ───────────────────────────────────
const {
  cambiosPendientes,
  cantidadCambios,
  hayCambiosPendientes,
  guardando,
  errorGuardado,
  resultadoGuardado,
  registrarCambio,
  limpiarCambios: limpiarCambiosBase,
  aplicarCambios,
} = useTipoIngreso()

const contextoCambios = ref({})

function onCambiar({ key, materia, valor }) {
  registrarCambio({ key, materia, valor })
  const copia = { ...contextoCambios.value }
  if (valor === (materia.tipo_ingreso ?? '')) {
    delete copia[key]
  } else {
    copia[key] = { docenteCod: docenteCodActual.value, materia }
  }
  contextoCambios.value = copia
}

function limpiarCambios() {
  limpiarCambiosBase()
  contextoCambios.value = {}
}

function extraerCodMateria(materiaRaw) {
  if (!materiaRaw) return ''
  return String(materiaRaw).trim().split(/\s+/)[0]
}

async function handleAplicar() {
  const items = Object.entries(cambiosPendientes.value).map(([key, tipoIngreso]) => {
    const ctx = contextoCambios.value[key]
    return {
      cod_docente: ctx?.docenteCod,
      cod_plan: ctx?.materia.plan,
      cod_materia: extraerCodMateria(ctx?.materia.materia),
      grupo: ctx?.materia.grp || null,
      gestion: ctx?.materia.gestion,
      tipo_ingreso: tipoIngreso,
    }
  })

  try {
    await aplicarCambios(items)
    ultimosCambios.value = items
    contextoCambios.value = {}
    fase.value = 'resultado'
  } catch (e) {
    // El error ya queda expuesto en errorGuardado
  }
}

// ─── Filtros de materias ──────────────────────────────────────────
const filtroAnio    = ref('')
const filtroGestion = ref('')

const materiasDelReporte = computed(() => reporte.value?.materias ?? [])

const aniosDisponibles = computed(() => {
  const set = new Set()
  materiasDelReporte.value.forEach(m => {
    const anio = String(m.gestion ?? '').split('/')[0]?.trim()
    if (anio) set.add(anio)
  })
  return [...set].sort((a, b) => b - a)
})

const gestionesDisponibles = computed(() => {
  const set = new Set()
  materiasDelReporte.value.forEach(m => {
    const partes = String(m.gestion ?? '').split('/')
    const parte = partes.slice(1).join('/').trim()
    if (parte) set.add(parte)
  })
  return [...set].sort()
})

const materiasFiltradas = computed(() => {
  return materiasDelReporte.value.filter(m => {
    const partes = String(m.gestion ?? '').split('/')
    const anio = partes[0]?.trim()
    const gest = partes.slice(1).join('/').trim()
    if (filtroAnio.value && anio !== filtroAnio.value) return false
    if (filtroGestion.value && gest !== filtroGestion.value) return false
    return true
  })
})

// ─── Fase final ────────────────────────────────────────────────────
const fase          = ref('formulario')
const ultimosCambios = ref([])

function editarOtraMas() {
  limpiarCambios()
  onLimpiarDocente()
  ultimosCambios.value = []
  fase.value = 'formulario'
}
</script>