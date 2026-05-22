<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="$emit('cerrar')"
    >
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="$emit('cerrar')" />

      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="relative bg-gradient-to-br from-teal-600 to-teal-700 px-6 py-5 flex-shrink-0">
          <button
            @click="$emit('cerrar')"
            class="absolute top-4 right-4 text-teal-200 hover:text-white hover:bg-teal-500/50 rounded-lg p-1.5 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white/20 border border-white/30 flex items-center justify-center flex-shrink-0">
              <i class="ti ti-user" style="font-size: 28px; color: white;" aria-hidden="true"></i>
            </div>
            <div>
              <h2 class="text-lg font-bold text-white leading-tight">{{ formatNombre(docente.nombre_docente) }}</h2>
              <p class="text-teal-200 text-sm mt-0.5">Cód. {{ docente.docente }} · {{ docente.unidad || 'Sin unidad' }}</p>
            </div>
          </div>

          <!-- Tabs -->
          <div class="flex gap-1 mt-4">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="seleccionarTab(tab.id)"
              :class="[
                'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                tabActiva === tab.id
                  ? 'bg-white text-teal-700'
                  : 'text-teal-100 hover:bg-teal-500/40'
              ]"
            >
              <component :is="'span'" v-html="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Contenido scrollable -->
        <div class="flex-1 overflow-y-auto">

          <!-- Tab: Información -->
          <div v-if="tabActiva === 'info'" class="p-6 space-y-5">

            <div class="flex items-center justify-between">
              <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold', badgeGrado(docente.grado_academico)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
                {{ docente.grado_academico || 'Sin grado académico' }}
              </span>
              <span v-if="docente.horas_total" class="text-sm text-slate-500 font-medium">
                <span class="text-slate-800 font-bold text-lg">{{ docente.horas_total }}</span> h/sem
              </span>
            </div>

            <section>
              <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Datos Personales</h3>
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-50 rounded-xl p-3">
                  <p class="text-xs text-slate-400 mb-1">Carnet de Identidad</p>
                  <p class="text-sm font-semibold text-slate-800 font-mono">{{ docente.ci || '—' }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                  <p class="text-xs text-slate-400 mb-1">Código Docente</p>
                  <p class="text-sm font-semibold text-slate-800 font-mono">{{ docente.docente || '—' }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3 col-span-2">
                  <p class="text-xs text-slate-400 mb-1">Unidad Académica</p>
                  <p class="text-sm font-semibold text-slate-800">{{ docente.unidad || '—' }}</p>
                </div>
              </div>
            </section>

            <section>
              <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Contacto</h3>
              <div class="space-y-2">
                <div v-if="docente.fijo_1" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Teléfono fijo</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.fijo_1 }}</p>
                  </div>
                </div>
                <div v-if="docente.fijo_2" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Teléfono fijo 2</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.fijo_2 }}</p>
                  </div>
                </div>
                <div v-if="docente.celular_1" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Celular</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.celular_1 }}</p>
                  </div>
                </div>
                <div v-if="docente.celular_2" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Celular 2</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.celular_2 }}</p>
                  </div>
                </div>
                <div v-if="docente.email" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Correo personal</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.email }}</p>
                  </div>
                </div>
                <div v-if="docente.email_institucional" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-slate-400">Correo institucional</p>
                    <p class="text-sm font-medium text-slate-800">{{ docente.email_institucional }}</p>
                  </div>
                </div>
                <div
                  v-if="!docente.fijo_1 && !docente.fijo_2 && !docente.celular_1 && !docente.celular_2 && !docente.email && !docente.email_institucional"
                  class="text-center py-6 text-slate-400 text-sm"
                >
                  Sin datos de contacto registrados
                </div>
              </div>
            </section>

          </div>

          <!-- Tab: Materias (id='horario') -->
          <div v-if="tabActiva === 'horario'" class="p-6">
            <div v-if="materias.length > 0" class="space-y-3">
              <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Materias asignadas</h3>
              <div
                v-for="(materia, idx) in materias"
                :key="idx"
                class="border border-slate-200 rounded-xl overflow-hidden"
              >
                <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border-b border-slate-100">
                  <div class="w-7 h-7 rounded-lg bg-teal-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-xs font-bold">{{ idx + 1 }}</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ materia.nombre || materia.materia || 'Materia sin nombre' }}</p>
                    <p v-if="materia.grupo || materia.paralelo" class="text-xs text-slate-400">Grupo {{ materia.grupo || materia.paralelo }}</p>
                  </div>
                  <span v-if="materia.horas" class="text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-1 rounded-lg">{{ materia.horas }}h</span>
                </div>
                <div v-if="materia.dia || materia.hora_inicio" class="px-4 py-2 flex items-center gap-4 text-xs text-slate-600">
                  <span v-if="materia.dia" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ materia.dia }}
                  </span>
                  <span v-if="materia.hora_inicio" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ materia.hora_inicio }}{{ materia.hora_fin ? ' – ' + materia.hora_fin : '' }}
                  </span>
                  <span v-if="materia.aula" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    Aula {{ materia.aula }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-16 text-center">
              <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <p class="text-slate-600 font-medium">Sin materias asignadas</p>
              <p class="text-slate-400 text-sm mt-1">Este docente no tiene materias registradas en el período actual</p>
            </div>
          </div>

          <!-- Tab: Carga → abre HorarioRapidoModal -->
          <div v-if="tabActiva === 'carga'" class="p-6 space-y-5">
            <div class="grid grid-cols-3 gap-3">
              <div class="bg-teal-50 border border-teal-100 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-teal-700">{{ docente.horas_total || 0 }}</p>
                <p class="text-xs text-teal-600 mt-1">Horas totales</p>
              </div>
              <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-blue-700">{{ materias.length }}</p>
                <p class="text-xs text-blue-600 mt-1">Materias</p>
              </div>
              <div class="bg-violet-50 border border-violet-100 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-violet-700">{{ porcentajeCarga }}%</p>
                <p class="text-xs text-violet-600 mt-1">Carga máx.</p>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                <span>Carga horaria</span>
                <span>{{ docente.horas_total || 0 }} / 40 horas</span>
              </div>
              <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                <div
                  :class="['h-full rounded-full transition-all duration-500', colorCarga(docente.horas_total)]"
                  :style="{ width: Math.min((docente.horas_total / 40) * 100, 100) + '%' }"
                />
              </div>
              <div class="flex justify-between text-xs mt-1">
                <span class="text-slate-400">0h</span>
                <span class="text-slate-400">40h</span>
              </div>
            </div>

            <div :class="['flex items-center gap-3 p-4 rounded-xl border', estadoCargaStyle.bg]">
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', estadoCargaStyle.icon]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="estadoCargaStyle.path"/>
                </svg>
              </div>
              <div>
                <p :class="['font-semibold text-sm', estadoCargaStyle.text]">{{ estadoCargaStyle.label }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ estadoCargaStyle.desc }}</p>
              </div>
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 flex-shrink-0 flex justify-end">
          <button
            @click="$emit('cerrar')"
            class="px-5 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  docente: { type: Object, required: true },
  modo:    { type: String, default: 'detalle' }
})

// ← NUEVO: emite 'ver-horario' para que DocentesView abra el HorarioRapidoModal
const emit = defineEmits(['cerrar', 'ver-horario'])

const tabActiva = ref(props.modo === 'horario' ? 'horario' : 'info')

watch(() => props.modo, (val) => {
  tabActiva.value = val === 'horario' ? 'horario' : 'info'
})

// ← NUEVO: cuando el usuario toca el tab "Horario" (id='carga'),
//   cierra este modal y le dice al padre que abra el HorarioRapidoModal
function seleccionarTab(id) {
  if (id === 'carga') {
    emit('ver-horario', props.docente)
    return
  }
  tabActiva.value = id
}

const tabs = [
  {
    id: 'info',
    label: 'Información',
    icon: `<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`
  },
  {
    id: 'horario',
    label: 'Materias',
    icon: `<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`
  },
  {
    id: 'carga',
    label: 'Horario',
    icon: `<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`
  }
]

const materias = computed(() => props.docente.materias || props.docente.horario || [])

const porcentajeCarga = computed(() => {
  if (!props.docente.horas_total) return 0
  return Math.round((props.docente.horas_total / 40) * 100)
})

const estadoCargaStyle = computed(() => {
  const h = props.docente.horas_total || 0
  if (h === 0) return {
    bg: 'bg-slate-50 border-slate-200', icon: 'bg-slate-200 text-slate-500',
    text: 'text-slate-600', label: 'Sin carga asignada',
    desc: 'El docente no tiene horas registradas',
    path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
  }
  if (h >= 30) return {
    bg: 'bg-green-50 border-green-200', icon: 'bg-green-200 text-green-700',
    text: 'text-green-700', label: 'Carga completa',
    desc: `${h} horas asignadas — por encima del mínimo recomendado`,
    path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
  }
  if (h >= 15) return {
    bg: 'bg-amber-50 border-amber-200', icon: 'bg-amber-200 text-amber-700',
    text: 'text-amber-700', label: 'Carga parcial',
    desc: `${h} horas asignadas — por debajo del mínimo recomendado`,
    path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
  }
  return {
    bg: 'bg-red-50 border-red-200', icon: 'bg-red-200 text-red-700',
    text: 'text-red-700', label: 'Carga baja',
    desc: `Solo ${h} horas asignadas — requiere revisión`,
    path: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
  }
})

function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function badgeGrado(grado) {
  const map = {
    'PhD': 'bg-violet-100 text-violet-700',
    'Doctorado': 'bg-violet-100 text-violet-700',
    'Magister': 'bg-blue-100 text-blue-700',
    'Licenciado': 'bg-teal-100 text-teal-700',
    'Ingeniero': 'bg-orange-100 text-orange-700',
  }
  return map[grado] || 'bg-slate-100 text-slate-500'
}

function colorCarga(horas) {
  if (!horas) return 'bg-slate-300'
  if (horas >= 30) return 'bg-green-500'
  if (horas >= 15) return 'bg-amber-500'
  return 'bg-red-400'
}
</script>