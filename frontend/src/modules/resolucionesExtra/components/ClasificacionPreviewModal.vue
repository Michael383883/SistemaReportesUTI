<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50" @click="$emit('cerrar')"></div>

    <!-- Modal -->
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">

      <!-- Header -->
      <div class="bg-slate-900 px-5 py-3 flex items-center justify-between flex-shrink-0">
        <h3 class="text-[15px] font-semibold text-white">Vista previa de la clasificación</h3>
        <button type="button" @click="$emit('cerrar')" class="text-slate-300 hover:text-white">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-5 space-y-4 overflow-y-auto">

        <!-- Archivo -->
        <div v-if="archivoNombre" class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg">
          <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span class="text-[13px] font-medium text-blue-700 truncate">{{ archivoNombre }}</span>
        </div>

        <!-- Datos generales -->
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
          <h4 class="text-[12px] font-bold text-slate-700 mb-3 uppercase tracking-wide">Datos generales</h4>
          <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-[13px]">
            <div>
              <dt class="text-gray-500 text-[11px]">Docente</dt>
              <dd class="font-medium text-slate-800">{{ nombreDocente || '—' }}</dd>
            </div>
            <div>
  <dt class="text-gray-500 text-[11px]">Tipo/Nº de documento</dt>
  <dd class="font-medium text-slate-800">
    {{ tipoDocumentoMostrado }}
  </dd>
  <p v-if="!form.tipo_documento && form.titulo?.tipo_titulo" class="text-[10px] text-amber-600 mt-0.5">
    (tomado del título)
  </p>
</div>
            <div>
              <dt class="text-gray-500 text-[11px]">Categoría</dt>
              <dd class="font-medium text-slate-800">{{ form.categoria || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 text-[11px]">Nivel</dt>
              <dd class="font-medium text-slate-800">{{ form.nivel || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 text-[11px]">Gestión</dt>
              <dd class="font-medium text-slate-800">{{ form.gestion || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 text-[11px]">Periodo</dt>
              <dd class="font-medium text-slate-800">{{ form.periodo || '—' }}</dd>
            </div>
            <div class="col-span-2" v-if="form.detalle_general">
              <dt class="text-gray-500 text-[11px]">Descripción general</dt>
              <dd class="font-medium text-slate-800">{{ form.detalle_general }}</dd>
            </div>
            <div class="col-span-2" v-if="form.observacion">
              <dt class="text-gray-500 text-[11px]">Observación 1</dt>
              <dd class="text-slate-700">{{ form.observacion }}</dd>
            </div>
            <div class="col-span-2" v-if="form.observacion2">
              <dt class="text-gray-500 text-[11px]">Observación 2</dt>
              <dd class="text-slate-700">{{ form.observacion2 }}</dd>
            </div>
          </dl>
        </div>

        <!-- Título -->
        <div v-if="form.titulo" class="bg-orange-50 rounded-xl border border-orange-200 p-4">
          <h4 class="text-[12px] font-bold text-orange-700 mb-3 uppercase tracking-wide">Título académico</h4>
          <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-[13px]">
            <div class="col-span-2">
              <dt class="text-orange-500 text-[11px]">Nombre del título</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.nombre_titulo || '—' }}</dd>
            </div>
            <div>
              <dt class="text-orange-500 text-[11px]">Tipo</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.tipo_titulo || '—' }}</dd>
            </div>
            <div>
              <dt class="text-orange-500 text-[11px]">Universidad</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.universidad || '—' }}</dd>
            </div>
            <div>
              <dt class="text-orange-500 text-[11px]">País</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.pais || '—' }}</dd>
            </div>
            <div>
              <dt class="text-orange-500 text-[11px]">Fecha</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.fecha_titulo || '—' }}</dd>
            </div>
            <div>
              <dt class="text-orange-500 text-[11px]">Número</dt>
              <dd class="font-medium text-orange-900">{{ form.titulo.numero || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Materias -->
        <div v-if="form.materias.length" class="bg-gray-50 rounded-xl border border-gray-200 p-4">
          <h4 class="text-[12px] font-bold text-slate-700 mb-3 uppercase tracking-wide">
            Materias ({{ form.materias.length }})
          </h4>
          <div class="space-y-2">
            <div
              v-for="(m, i) in form.materias"
              :key="i"
              class="flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[12px]"
            >
              <div class="flex flex-col">
                <span class="font-semibold text-slate-800">
                  {{ m.nombre_materia }}
                  <span v-if="m.cod_materia" class="text-gray-400 font-normal">({{ m.cod_materia }})</span>
                </span>
                <span class="text-gray-500 text-[11px]">
                  <span v-if="m.grupo">Grupo {{ m.grupo }} · </span>
                  <span v-if="m.docente">
                    {{ m.docente.apellidos }} {{ m.docente.nombres }}
                  </span>
                  <span v-else class="text-red-500">Sin docente asignado</span>
                </span>
              </div>
              <span v-if="m.nota" class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded font-bold text-[11px]">
                Nota: {{ m.nota }}
              </span>
            </div>
          </div>
        </div>

        <!-- Referencias -->
        <div v-if="form.referencias.length" class="bg-gray-50 rounded-xl border border-gray-200 p-4">
          <h4 class="text-[12px] font-bold text-slate-700 mb-3 uppercase tracking-wide">
            Referencias ({{ form.referencias.length }})
          </h4>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="(r, i) in form.referencias"
              :key="i"
              class="px-2.5 py-1 bg-white border border-gray-200 rounded-lg text-[12px] font-medium text-slate-700"
            >
              {{ r.nro_referencia }}
              <span v-if="r.id_resolucion" class="text-gray-400">(ID: {{ r.id_resolucion }})</span>
            </span>
          </div>
        </div>

        <!-- Aviso de aplicación en GRUPOS -->
        <div v-if="asignaAGrupos" class="flex items-center gap-2 p-2.5 bg-green-50 border border-green-200 rounded-lg text-green-700 text-[12px]">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          Todas las materias tienen grupo asignado: se aplicará automáticamente en GRUPOS.
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-t border-gray-200 flex-shrink-0">
        <button
          type="button"
          @click="$emit('cerrar')"
          class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-slate-600 hover:text-slate-800 rounded-lg transition-colors"
        >
          Seguir editando
        </button>

        <button
          type="button"
          :disabled="saving"
          @click="$emit('confirmar')"
          class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors"
        >
          <svg v-if="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Confirmar y guardar
        </button>
        <button
          type="button"
          :disabled="saving"
          @click="$emit('confirmar-y-asignar')"
          class="inline-flex items-center gap-2 px-5 py-2 bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-white text-[13px] font-bold rounded-lg transition-colors"
        >
          Guardar + Asignar a otros docentes
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  form: { type: Object, required: true },
  nombreDocente: { type: String, default: '' },
  archivoNombre: { type: String, default: '' },
  asignaAGrupos: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
})

defineEmits(['cerrar', 'confirmar'])

const tipoDocumentoMostrado = computed(() => {
  if (props.form.tipo_documento?.trim()) return props.form.tipo_documento
  if (props.form.titulo?.tipo_titulo) return props.form.titulo.tipo_titulo
  return '—'
})
</script>