<template>
  <!-- ===== TARJETA DE CONTACTO ===== -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" />

        <!-- Card -->
        <div
          class="relative z-10 w-full max-w-sm rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden"
        >
          <!-- Header degradado -->
          <div class="bg-gradient-to-br from-blue-700 to-blue-500 px-6 py-5 flex items-center gap-4">
            <!-- Avatar inicial -->
            <!-- DESPUÉS -->
              <div class="h-14 w-14 rounded-full bg-white/20 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                  <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                </svg>
              </div>
            <div class="flex-1 min-w-0">
              <p class="text-white font-semibold text-base leading-snug truncate">
                {{ estudiante.nom_estudiante }}
              </p>
              <p class="text-blue-100 text-xs mt-0.5">Cód. {{ estudiante.codigo }}</p>
            </div>
            <!-- Botón cerrar -->
            <button
              @click="$emit('close')"
              class="ml-auto text-white/70 hover:text-white transition-colors"
              aria-label="Cerrar"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="px-6 py-5 space-y-3">
            <!-- Plan -->
            <div class="flex items-start gap-3">
              <div class="mt-0.5 text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0121 13c0 5-4.5 9-9 9s-9-4-9-9c0-.342.023-.678.067-1.007L12 14z" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-medium">Carrera</p>
                <p class="text-slate-700 text-sm font-medium leading-snug">{{ nombrePlan }}</p>
              </div>
            </div>

            <!-- Materia -->
            <div class="flex items-start gap-3">
              <div class="mt-0.5 text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-medium">Materia · Grupo</p>
                <p class="text-slate-700 text-sm font-medium">{{ estudiante.nombre_materia }} — Grupo {{ estudiante.grupo }}</p>
              </div>
            </div>

            <!-- Docente -->
            <div class="flex items-start gap-3">
              <div class="mt-0.5 text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-medium">Docente</p>
                <p class="text-slate-700 text-sm font-medium">{{ estudiante.docente }}</p>
              </div>
            </div>

            <!-- Divider -->
            <hr class="border-slate-100 my-1" />

            <!-- Contacto (futuro) -->
            <div class="rounded-xl bg-slate-50 border border-dashed border-slate-200 px-4 py-3">
              <p class="text-[10px] uppercase tracking-widest text-slate-400 font-medium mb-2">Datos de Contacto</p>

              <!-- Email -->
              <div class="flex items-center gap-2 mb-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span v-if="contacto?.email" class="text-sm text-slate-700">{{ contacto.email }}</span>
                <span v-else class="text-xs text-slate-400 italic">Email pendiente de registro</span>
              </div>

              <!-- Celular -->
              <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span v-if="contacto?.celular" class="text-sm text-slate-700">{{ contacto.celular }}</span>
                <span v-else class="text-xs text-slate-400 italic">Celular pendiente de registro</span>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 pb-5">
            <button
              @click="$emit('close')"
              class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold py-2.5 transition-colors shadow"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { PLANES } from '../services/estudiantesService'

const props = defineProps({
  visible:    { type: Boolean, default: false },
  estudiante: { type: Object,  default: () => ({}) },
  contacto:   { type: Object,  default: null },
})

defineEmits(['close'])

const iniciales = computed(() => {
  if (!props.estudiante?.nom_estudiante) return '?'
  return props.estudiante.nom_estudiante
    .split(' ')
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase()
})

const nombrePlan = computed(() => {
  return PLANES[props.estudiante?.plan] || props.estudiante?.plan || '—'
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 0.2s ease;
}
.modal-fade-enter-from .relative {
  transform: scale(0.95) translateY(8px);
}
.modal-fade-leave-to .relative {
  transform: scale(0.95) translateY(8px);
}
</style>