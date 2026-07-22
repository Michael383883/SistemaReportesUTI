<template>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <!-- Header docente (clickable para desplegar/colapsar) -->
    <button
      @click="abierto = !abierto"
      class="w-full flex items-center justify-between px-6 py-3 bg-slate-800 hover:bg-slate-700 transition-colors text-left rounded-t-xl"
    >
      <div class="flex items-center gap-3 text-white min-w-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <h2 class="font-bold text-sm uppercase truncate">
          {{cod_docente}} - {{ docente || 'Docente no asignado' }}
        </h2>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <span class="rounded-full bg-slate-700 px-3 py-1 text-xs text-white">
          {{ materias.length }} materia{{ materias.length !== 1 ? 's' : '' }}
        </span>
        <span class="rounded-full bg-green-600/20 px-3 py-1 text-xs text-green-300 font-semibold">
          {{ totalEstudiantes }} inscritos
        </span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-slate-300 transition-transform duration-200"
          :class="{ 'rotate-180': abierto }"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </div>
    </button>

    <!-- Materias del docente -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      leave-active-class="transition-all duration-150 ease-in"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="abierto" class="divide-y divide-slate-100">
        <MateriaGrupoCard
          v-for="m in materias"
          :plan="m.plan"
          :key="`${m.codigoMateria}_${m.grupo}`"
          :materia="m.materia"
          :codigo-materia="m.codigoMateria"
          :grupo="m.grupo"
          :estudiantes="m.estudiantes"
          @ver-contacto="est => $emit('ver-contacto', est)"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
defineOptions({ name: 'GrupoDocenteCard' })

import { ref } from 'vue'
import MateriaGrupoCard from './MateriaGrupoCard.vue'

const props = defineProps({
    plan: { type: String, default: '' },
    codigoMateria: { type: String, default: '' },
  docente:            { type: String, default: '' },
  cod_docente:        { type: String, default: '' },
  materias:           { type: Array, default: () => [] }, // [{ materia, codigoMateria, grupo, estudiantes }]
  totalEstudiantes:   { type: Number, default: 0 },
  // Si el docente arranca desplegado o colapsado al cargar la página.
  abiertoPorDefecto:  { type: Boolean, default: true },
})

defineEmits(['ver-contacto'])

const abierto = ref(props.abiertoPorDefecto)
</script>