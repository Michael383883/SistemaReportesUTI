<template>
  <div class="bg-white rounded-xl border border-gray-200">

    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
      <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
           :class="fase === 'grupos' ? 'bg-blue-100' : 'bg-green-100'">
        <svg v-if="fase !== 'grupos'" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <svg v-else class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6M3 3h18"/>
        </svg>
      </div>
      <div>
        <h2 class="text-sm font-medium text-gray-800">
          {{ fase === 'grupos' ? 'Grupos actualizados' : 'Resolución guardada correctamente' }}
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">
          {{ fase === 'grupos' ? `${gruposActualizados.length} registro(s) actualizados en la tabla grupos.` : 'Verifica los datos registrados antes de finalizar.' }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center gap-3 py-16 text-gray-400">
      <svg class="w-6 h-6 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <span class="text-xs">{{ fase === 'grupos' ? 'Aplicando en grupos…' : 'Cargando datos guardados…' }}</span>
    </div>

    <!-- Error -->
        <div v-else-if="error || errorLocal"
          class="m-6 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-xs">
          <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          {{ error || errorLocal }}
        </div>

    <!-- ══════════════ FASE 1: preview resolución ══════════════ -->
    <div v-else-if="fase === 'preview'" class="divide-y divide-gray-100">

      <!-- Datos de la resolución -->
      <div>
        <p class="px-6 py-3 text-[10px] font-medium text-gray-400 uppercase tracking-wider bg-gray-50">
          Datos de la resolución
        </p>
        <table class="w-full text-xs">
          <tbody class="divide-y divide-gray-100">
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-[10px] font-medium text-gray-400 uppercase tracking-wider w-36">Número</td>
              <td class="px-6 py-2.5 text-gray-800 font-medium">{{ resolucion?.nro_resolucion ?? '—' }}</td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-[10px] font-medium text-gray-400 uppercase tracking-wider">Año</td>
              <td class="px-6 py-2.5 text-gray-800 font-medium">{{ resolucion?.anio ?? '—' }}</td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-[10px] font-medium text-gray-400 uppercase tracking-wider">Periodo</td>
              <td class="px-6 py-2.5 text-gray-800 font-medium">{{ resolucion?.periodo ?? '—' }}</td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-[10px] font-medium text-gray-400 uppercase tracking-wider">Archivo</td>
              <td class="px-6 py-2.5 text-gray-800 font-medium truncate max-w-xs">{{ resolucion?.nombre_archivo ?? '—' }}</td>
            </tr>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-[10px] font-medium text-gray-400 uppercase tracking-wider">Descripción</td>
              <td class="px-6 py-2.5 text-gray-700 leading-relaxed">{{ resolucion?.descripcion ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Materias acéfalas -->
      <div>
        <div class="px-6 py-3 bg-gray-50 flex items-center gap-2">
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Materias acéfalas</p>
          <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-medium">{{ detalles.length }}</span>
        </div>

        <div v-if="detalles.length > 0" class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider w-10">N°</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Cód. docente</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Materia</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Grupo</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Observación</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(d, i) in detalles" :key="d.id_detalle ?? i"
                  class="hover:bg-gray-50 transition-colors"
                  :class="d.observacion === 'COMPARTIDO' ? 'bg-amber-50' : ''">
                <td class="px-4 py-2.5 text-gray-400 font-mono">{{ i + 1 }}</td>
                <td class="px-4 py-2.5 text-gray-700 font-mono">{{ d.cod_docente }}</td>
                <td class="px-4 py-2.5 text-gray-600 font-mono">{{ d.cod_plan }}</td>
                <td class="px-4 py-2.5 text-gray-600 font-mono">{{ d.cod_materia }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ d.grupo || '—' }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ d.tipo || '—' }}</td>
                <td class="px-4 py-2.5">
                  <span v-if="d.observacion"
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">
                    {{ d.observacion }}
                  </span>
                  <span v-else class="text-gray-300">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="flex flex-col items-center gap-2 py-10 text-gray-300">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path d="M9 17v-2m3 2v-4m3 4v-6M3 3h18M3 8h18M3 13h9"/>
          </svg>
          <p class="text-xs">Sin detalles registrados.</p>
        </div>
      </div>
    </div>

    <!-- ══════════════ FASE 2: grupos actualizados ══════════════ -->
    <div v-else-if="fase === 'grupos'" class="divide-y divide-gray-100">
      <div>
        <div class="px-6 py-3 bg-gray-50 flex items-center gap-2">
          <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Registros actualizados en grupos</p>
          <span class="px-2 py-0.5 rounded-full bg-green-50 text-green-600 text-[10px] font-medium">
            {{ gruposActualizados.length }}
          </span>
        </div>

        <div v-if="gruposActualizados.length > 0" class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Año</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Per.</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Materia</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Grupo</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Docente</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Resolución</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wider">Designación</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(g, i) in gruposActualizados" :key="i"
                  class="hover:bg-green-50 transition-colors">
                <td class="px-4 py-2.5 text-gray-700 font-mono">{{ g.anio }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ g.periodo }}</td>
                <td class="px-4 py-2.5 text-gray-600 font-mono">{{ g.plan }}</td>
                <td class="px-4 py-2.5 text-gray-600 font-mono">{{ g.materia }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ g.grupo }}</td>
                <td class="px-4 py-2.5 text-gray-700 font-mono">{{ g.docente }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ g.tipo }}</td>
                <td class="px-4 py-2.5 text-blue-600 font-medium">{{ g.resolucion }}</td>
                <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" :title="g.designacion">{{ g.designacion }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="flex flex-col items-center gap-2 py-10 text-gray-300">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path d="M9 17v-2m3 2v-4m3 4v-6M3 3h18"/>
          </svg>
          <p class="text-xs">No se encontraron grupos coincidentes para actualizar.</p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-end px-6 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
      <!-- Fase preview → botón "Terminar" llama aplicarEnGrupos -->
      <button v-if="fase === 'preview'"
        type="button"
        :disabled="loading"
        @click="handleTerminar"
        class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-medium rounded-lg transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        Terminar
      </button>

      <!-- Fase grupos → botón "Finalizar" emite evento al padre -->
      <button v-else-if="fase === 'grupos'"
        type="button"
        @click="$emit('finalizar')"
        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
        Finalizar
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useResolucion } from '../composables/useResolucion'

const props = defineProps({
  resolucion: { type: Object,  default: null },
  detalles:   { type: Array,   default: () => [] },
  loading:    { type: Boolean, default: false },
  error:      { type: String,  default: '' },
  resolucionId: { type: [Number, String], default: null },    // ← NUEVO
  aplicarEnGrupos:  { type: Function, default: null },      // ← NUEVO
})

const emit = defineEmits(['terminar', 'finalizar'])

const { aplicarEnGrupos } = useResolucion()

const fase               = ref('preview')   // 'preview' | 'grupos'
const gruposActualizados = ref([])
const loadingLocal       = ref(false)
const errorLocal         = ref('')

async function handleTerminar() {
  // Usa el id directo, no depende de mapKeysToCamelCase
  const id = props.resolucionId ?? props.resolucion?.id_resolucion ?? props.resolucion?.iDResolucion

  if (!id) {
    errorLocal.value = 'No se encontró el ID de la resolución. (id=' + JSON.stringify(props.resolucion) + ')'
    return
  }

  loadingLocal.value = true
  errorLocal.value   = ''

  try {
    const resultado = await props.aplicarEnGrupos(id)
    gruposActualizados.value = resultado.grupos ?? []
    fase.value = 'grupos'
    emit('terminar')
  } catch (e) {
    errorLocal.value = e?.message ?? 'Error al aplicar en grupos.'
  } finally {
    loadingLocal.value = false
  }
}
</script>