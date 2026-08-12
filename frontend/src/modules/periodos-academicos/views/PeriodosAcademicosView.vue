<template>
  <div class="bg-slate-100 dark:bg-slate-950 min-h-full -m-6 p-6">
    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-0.5">
          Periodos académicos
        </h1>
        <p class="text-[12px] font-normal text-slate-600 dark:text-slate-400 mt-0.5">
          Rangos de fechas usados para saber qué gestiones aún no han concluido
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="onRestaurar"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] font-semibold
                 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-300
                 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-600
                 transition-all duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <RotateCcw class="w-3.5 h-3.5" /> Restaurar predeterminados
        </button>
        <button
          @click="onGuardar"
          :disabled="loading || !huboCambios"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-bold
                 bg-amber-500 hover:bg-amber-400 active:bg-amber-600
                 text-slate-100 transition-all duration-150 cursor-pointer border-none
                 shadow-lg shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Save class="w-3.5 h-3.5" /> Guardar cambios
        </button>
      </div>
    </div>

    <!-- Error -->
    <transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="error"
        class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-600
               dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
               rounded-lg px-3 py-2 mb-4 text-[12px]"
      >
        <AlertCircle class="w-3.5 h-3.5 shrink-0" />
        {{ error }}
        <button class="ml-auto" @click="clearError" aria-label="Cerrar error">
          <X class="w-3 h-3" />
        </button>
      </div>
    </transition>

    <!-- Aviso informativo -->
    <div
      class="flex items-start gap-2 bg-blue-50 border border-blue-200 text-blue-700
             dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400
             rounded-lg px-3 py-2.5 mb-4 text-[12px]"
    >
      <Info class="w-3.5 h-3.5 shrink-0 mt-0.5" />
      <span>
        Estos rangos se repiten cada año (solo importa el día y el mes). Se usan para
        ocultar automáticamente del reporte de "materias dictadas" las gestiones que
        todavía están en curso.
      </span>
    </div>

    <!-- Tabla -->
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden shadow-md shadow-slate-900/5">
      <div class="overflow-x-auto">
        <div v-if="loading && !draft.length" class="py-12 text-center">
          <Loader2 class="w-5 h-5 animate-spin mx-auto mb-2 text-gray-400 dark:text-slate-600" />
          <p class="text-[12px] text-gray-500 dark:text-slate-500">Cargando periodos académicos...</p>
        </div>

        <table v-else class="w-full text-[13px] border-collapse">
          <thead>
            <tr class="border-b border-b-black-800 bg-[rgb(8,31,51)] dark:border-slate-700 dark:bg-slate-900/60">
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Periodo</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Nombre</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Inicio</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Fin</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Última modificación</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(p, i) in draft"
              :key="p.id"
              class="border-b border-slate-100 dark:border-slate-700/60 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.025]"
              :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/70 dark:bg-slate-900/20'"
            >
              <td class="px-4 py-3">
                <span
                  class="px-2.5 py-1 rounded-md text-xs font-semibold border
                         bg-slate-100 text-slate-700 border-slate-200
                         dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600"
                >
                  {{ etiquetaPeriodo(p.periodo) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="p.nombre"
                  type="text"
                  maxlength="40"
                  class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900
                         text-slate-800 dark:text-slate-200 text-[13px] px-2 py-1.5
                         focus:outline-none focus:ring-2 focus:ring-amber-400"
                />
              </td>
              <td class="px-4 py-3">
                <PeriodoFechaSelector v-model="p.inicio" />
              </td>
              <td class="px-4 py-3">
                <PeriodoFechaSelector v-model="p.fin" />
              </td>
              <td class="px-4 py-3 text-slate-500 dark:text-slate-500 text-[12px]">
                {{ p.actualizado_por?.name ?? '—' }}
                <span v-if="p.updated_at" class="block text-slate-400 dark:text-slate-600">
                  {{ formatearFecha(p.updated_at) }}
                </span>
              </td>
            </tr>

            <tr v-if="!draft.length && !loading">
              <td colspan="5" class="px-4 py-10 text-center text-[12px] text-slate-600 dark:text-slate-500">
                No hay periodos académicos configurados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900/30 text-xs text-slate-600 dark:text-slate-500 text-right">
        {{ draft.length }} periodo{{ draft.length !== 1 ? 's' : '' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { RotateCcw, Save, AlertCircle, X, Info, Loader2 } from 'lucide-vue-next'
import { usePeriodosAcademicos } from '../composables/usePeriodosAcademicos'
import { useNotify } from '@/shared/composables/useNotify'
import PeriodoFechaSelector from '../components/PeriodoFechaSelector.vue'

const {
  periodos,
  loading,
  error,
  fetchPeriodos,
  guardarCambios,
  restaurarPredeterminados,
  clearError
} = usePeriodosAcademicos()
const notify = useNotify()

// Copia editable local: así el admin puede cambiar varios campos y recién
// mandar todo junto al backend cuando presiona "Guardar cambios".
const draft = ref([])

const ETIQUETAS = { '1': 'Semestre I', '2': 'Semestre II', '3': 'Verano', '4': 'Invierno' }
const etiquetaPeriodo = (p) => ETIQUETAS[p] ?? p

function sincronizarDraft() {
  draft.value = periodos.value.map((p) => ({ ...p }))
}

watch(periodos, sincronizarDraft)

const huboCambios = computed(() =>
  draft.value.some((p, i) => {
    const original = periodos.value[i]
    if (!original) return false
    return p.nombre !== original.nombre || p.inicio !== original.inicio || p.fin !== original.fin
  })
)

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function onGuardar() {
  const resultado = await guardarCambios(draft.value)
  if (resultado.success) {
    notify.success(resultado.message ?? 'Cambios guardados correctamente')
  } else {
    notify.error(resultado.message ?? 'Error al guardar los cambios')
  }
}

async function onRestaurar() {
  if (!confirm('¿Restaurar los 4 periodos a sus valores originales? Esto sobrescribirá los cambios actuales.')) return
  const resultado = await restaurarPredeterminados()
  if (resultado.success) {
    notify.success(resultado.message ?? 'Valores restaurados')
  } else {
    notify.error(resultado.message ?? 'Error al restaurar los valores')
  }
}

onMounted(fetchPeriodos)
</script>
