<template>
  <div class="mt-2 -mb-6">
    <div class="flex justify-end mb-2">
      <button
        class="px-3 py-1 rounded-lg bg-white text-slate-700 text-sm font-semibold border border-slate-200 hover:bg-slate-200 cursor-pointer outline-none"
        @click="emit('cerrar')"
      >
        ✕ Cerrar
      </button>
    </div>

    <div
      v-if="buscando || loading"
      class="bg-white rounded-xl p-6 text-center text-sm text-slate-500 mb-6"
    >
      Cargando horario...
    </div>

    <div
      v-else-if="error"
      class="bg-white rounded-xl p-6 text-center text-sm text-red-600 mb-6"
    >
      {{ error }}
    </div>

    <HorarioDocenteCard v-else-if="docenteHorario" :docente="docenteHorario" />

    <div
      v-else
      class="bg-white rounded-xl p-6 text-center text-sm text-slate-500 mb-6"
    >
      No se encontró el horario de este docente.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, toRef, onMounted, onUnmounted } from 'vue'
import HorarioDocenteCard from './HorarioDocenteCard.vue'
import { useHorarioAdmin } from '../composables/useHorarioAdmin'
import { useReporteHorario } from '../composables/useReporteHorario'

const props = defineProps({
  docente: { type: Object, required: true },
  anio: { type: [Number, String], required: true },
  periodo: { type: [Number, String], required: true },
})

const emit = defineEmits(['cerrar'])

// El resumen no trae las sesiones (días/horas/aulas), así que se piden aquí
const {
  docentes, loading, error,
  busqueda, ejecutarBusqueda,
} = useReporteHorario(useHorarioAdmin(), {
  anio: toRef(props, 'anio'),
  periodo: toRef(props, 'periodo'),
})

const buscando = ref(true)

// Entre los resultados, se queda con el docente exacto (por código)
const docenteHorario = computed(() =>
  docentes.value.find(d => String(d.docente) === String(props.docente.docente))
  ?? docentes.value[0]
  ?? null
)

function onKey(e) {
  if (e.key === 'Escape') emit('cerrar')
}

onMounted(async () => {
  window.addEventListener('keydown', onKey)

  busqueda.value = `${props.docente.apellidos ?? ''} ${props.docente.nombres ?? ''}`.trim()
  await ejecutarBusqueda()
  buscando.value = false
})

onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>