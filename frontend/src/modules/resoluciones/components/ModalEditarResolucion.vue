<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50" @click="cerrar"></div>

    <!-- Modal -->
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">

      <!-- Header -->
      <div class="bg-slate-900 px-5 py-3 flex items-center justify-between flex-shrink-0">
        <h3 class="text-[15px] font-semibold text-white">
          Editar referencia {{ resolucion?.nroResolucion }}
        </h3>
        <button type="button" @click="cerrar" class="text-slate-300 hover:text-white">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 space-y-4 overflow-y-auto">

        <div>
          <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
            N° Referencia
          </label>
          <input
            v-model="form.nroResolucion"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400"
          />
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
            Descripción
          </label>
          <textarea
            v-model="form.descripcion"
            rows="2"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
              Año / Gestión
            </label>
            <input
              v-model="form.anio"
              type="number"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400"
            />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
              Periodo
            </label>
            <input
              v-model="form.periodo"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px] outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400"
            />
          </div>
        </div>

        <!-- Advertencia: cambiar año/periodo puede afectar docentes/materias ya enlazados -->
        <div
          v-if="cambioAnioOPeriodo"
          class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-[12px]"
        >
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
          </svg>
          <div>
            <p class="font-semibold">Cuidado: estás cambiando {{ etiquetaCambio }}.</p>
            <p class="mt-0.5">
              Esta referencia puede tener docentes o materias ya enlazados (en GRUPOS) que se
              relacionan con el año/periodo original. Si cambiás este dato, esos vínculos pueden
              dejar de coincidir. Verificá antes de guardar.
            </p>
          </div>
        </div>

        <!-- Docentes / materias asignados a esta referencia -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
              Docentes asignados
            </label>
            <span class="text-[11px] text-gray-400">{{ detalles.length }}</span>
          </div>

          <div v-if="cargandoDetalles" class="text-[12px] text-slate-500 py-3 text-center">
            Cargando docentes...
          </div>

          <p v-else-if="errorDetalles" class="text-[12px] text-red-500">{{ errorDetalles }}</p>

          <p
            v-else-if="detalles.length === 0"
            class="text-[12px] text-slate-400 py-3 text-center border border-dashed border-gray-200 rounded-lg"
          >
            Esta referencia no tiene docentes asignados.
          </p>

          <ul
            v-else
            class="max-h-60 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-lg"
          >
            <li
              v-for="d in detalles"
              :key="d.idDetalle"
              class="flex items-center gap-3 px-3 py-2"
            >
              <div class="flex-1 min-w-0">
                <p class="text-[13px] font-medium text-slate-800 truncate">{{ d.nombreCompleto }}</p>
                <p class="text-[11px] text-slate-500 truncate">
                  {{ d.codMateria }} · Plan {{ d.codPlan }} · Gr. {{ d.grupo ?? '-' }} · {{ d.tipo ?? '-' }}
                  <span v-if="d.tipoIngreso"> · {{ d.tipoIngreso }}</span>
                </p>
              </div>

              <button
                type="button"
                :disabled="quitandoDetalleId === d.idDetalle || editando"
                @click="pedirQuitar(d)"
                class="shrink-0 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-red-50 text-red-600 hover:bg-red-100 disabled:opacity-50"
              >
                {{ quitandoDetalleId === d.idDetalle ? 'Quitando...' : 'Quitar' }}
              </button>
            </li>
          </ul>
        </div>

        <!-- Archivo (solo lectura, no editable) -->
        <div>
          <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">
            Archivo PDF
          </label>

          <div
            v-if="resolucion?.nombreArchivo"
            class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg"
          >
            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-[13px] font-medium text-blue-700 truncate">{{ resolucion.nombreArchivo }}</span>
          </div>

          <p class="text-[11px] text-gray-400 mt-1">
            El PDF no se puede reemplazar desde aquí. Si necesitás cambiar el archivo, borrá la referencia y volvé a subirla.
          </p>
        </div>

        <p v-if="errorEditar" class="text-[12px] text-red-500">{{ errorEditar }}</p>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-t border-gray-200 flex-shrink-0">
        <button
          type="button"
          :disabled="editando"
          @click="cerrar"
          class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-slate-600 hover:text-slate-800 rounded-lg transition-colors disabled:opacity-50"
        >
          Cancelar
        </button>

        <button
          type="button"
          :disabled="editando"
          @click="guardar"
          class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors"
        >
          <svg v-if="editando" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ editando ? 'Guardando...' : (cambioAnioOPeriodo ? 'Confirmar y guardar' : 'Guardar cambios') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  resolucion: { type: Object, default: null },
  editando: { type: Boolean, default: false },
  errorEditar: { type: String, default: '' },
  detalles: { type: Array, default: () => [] },
  cargandoDetalles: { type: Boolean, default: false },
  errorDetalles: { type: String, default: '' },
  quitandoDetalleId: { type: [Number, String], default: null },
})

const emit = defineEmits(['cerrar', 'guardar', 'quitar-detalle'])

const form = ref({
  nroResolucion: '',
  descripcion: '',
  anio: '',
  periodo: '',
})

// Guardamos los valores originales para poder comparar y detectar el cambio
const original = ref({ anio: '', periodo: '' })

watch(
  () => props.resolucion,
  (fila) => {
    form.value = {
      nroResolucion: fila?.nroResolucion ?? '',
      descripcion: fila?.descripcion ?? '',
      anio: fila?.anio ?? '',
      periodo: fila?.periodo ?? '',
    }
    original.value = {
      anio: fila?.anio ?? '',
      periodo: fila?.periodo ?? '',
    }
  },
  { immediate: true }
)

const anioCambio = computed(() => String(form.value.anio) !== String(original.value.anio))
const periodoCambio = computed(() => String(form.value.periodo) !== String(original.value.periodo))
const cambioAnioOPeriodo = computed(() => anioCambio.value || periodoCambio.value)

const etiquetaCambio = computed(() => {
  if (anioCambio.value && periodoCambio.value) return 'el año y el periodo'
  if (anioCambio.value) return 'el año'
  return 'el periodo'
})

function cerrar() {
  if (props.editando) return
  emit('cerrar')
}

// Quitar un docente/materia es inmediato (no espera a "Guardar cambios"),
// por eso se pide confirmación antes de emitir el evento.
function pedirQuitar(d) {
  const ok = window.confirm(
    `¿Quitar a ${d.nombreCompleto} (${d.codMateria}, grupo ${d.grupo ?? '-'}) de esta referencia?`
  )
  if (ok) emit('quitar-detalle', d.idDetalle)
}

function guardar() {
  // Confirmación extra si se está tocando año o periodo, justo por el
  // riesgo de desalinear docentes/materias ya enlazados en GRUPOS.
  if (cambioAnioOPeriodo.value) {
    const ok = window.confirm(
      `Estás por cambiar ${etiquetaCambio.value} de esta referencia. ` +
      `Esto puede afectar docentes/materias ya enlazados. ¿Continuar?`
    )
    if (!ok) return
  }

  const fd = new FormData()
  fd.append('nro_resolucion', form.value.nroResolucion)
  fd.append('descripcion', form.value.descripcion ?? '')
  fd.append('anio', form.value.anio)
  fd.append('periodo', form.value.periodo)
  // Ya no se envía archivo_pdf: el PDF no es editable desde este modal.

  emit('guardar', fd)
}
</script>