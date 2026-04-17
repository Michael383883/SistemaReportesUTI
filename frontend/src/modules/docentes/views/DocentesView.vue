<template>
  <div class="px-9 py-8 max-w-3xl">

    <!-- Header -->
    <div class="flex items-start justify-between mb-7">
      <div>
        <h1 class="text-2xl font-bold text-black-100 tracking-tight m-0 mb-1">Docentes</h1>
        <p class="text-xs text-slate-400 m-0">Búsqueda y consulta de docentes registrados</p>
      </div>
      <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/10 border border-indigo-500/20 rounded-full text-xs text-indigo-400 font-medium">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
        </svg>
        <span>{{ docentes.length }} registrados</span>
      </div>
    </div>

    <!-- Error banner -->
    <div v-if="error" class="flex items-center gap-2 px-3.5 py-2.5 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm mb-5">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ error }}
      <button
        class="ml-auto bg-red-500/15 border-none text-red-400 text-xs font-semibold px-2.5 py-1 rounded cursor-pointer hover:bg-red-500/25 transition-colors"
        @click="fetchDocentes"
      >
        Reintentar
      </button>
    </div>

    <!-- Search -->
    <div class="mb-7">
      <DocenteSearch
        v-model:searchQuery="searchQuery"
        v-model:dropdownOpen="dropdownOpen"
        :filteredDocentes="filteredDocentes"
        :selectedDocente="selectedDocente"
        :loading="loading"
        @select="selectDocente"
        @clear="clearSelection"
      />
    </div>

    <!-- Selected docente card -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2"
      leave-active-class="transition-all duration-200 ease-in"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div v-if="selectedDocente" class="relative rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
        <!-- Left accent bar -->
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600 rounded-l-xl"/>

        <div class="flex items-center gap-4 py-5 pr-6 pl-7">
          <!-- Avatar -->
          <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-700 to-violet-600 text-white text-base font-bold flex items-center justify-center shrink-0 tracking-wide">
            {{ initials(selectedDocente) }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h2 class="text-base font-semibold text-slate-100 m-0 mb-2 tracking-tight">
              {{ selectedDocente.nombres }} {{ selectedDocente.apellidos }}
            </h2>

            <!-- Tags -->
            <div class="flex flex-wrap gap-1.5 mb-2.5">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.72rem] font-semibold bg-indigo-500/15 text-indigo-300">
                SIS: {{ selectedDocente.codigo }}
              </span>
              <span v-if="selectedDocente.departamento" class="inline-flex items-center px-2 py-0.5 rounded text-[0.72rem] font-semibold bg-emerald-500/10 text-emerald-400">
                {{ selectedDocente.departamento }}
              </span>
              <span v-if="selectedDocente.categoria" class="inline-flex items-center px-2 py-0.5 rounded text-[0.72rem] font-semibold bg-amber-500/10 text-amber-400">
                {{ selectedDocente.categoria }}
              </span>
            </div>

            <!-- Meta -->
            <div class="flex flex-wrap gap-3.5">
              <div v-if="selectedDocente.email" class="flex items-center gap-1.5 text-xs text-slate-400">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
                {{ selectedDocente.email }}
              </div>
              <div v-if="selectedDocente.telefono" class="flex items-center gap-1.5 text-xs text-slate-400">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                {{ selectedDocente.telefono }}
              </div>
            </div>
          </div>

          <!-- Close button -->
          <button
            class="bg-transparent border-none cursor-pointer text-slate-400 p-1.5 rounded-lg flex items-center self-start hover:text-slate-100 hover:bg-white/10 transition-colors"
            title="Cerrar"
            @click="clearSelection"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Empty state -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-200"
      leave-to-class="opacity-0"
    >
      <div v-if="!selectedDocente && !loading" class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-20 h-20 rounded-2xl bg-white/[0.03] border border-dashed border-slate-700 flex items-center justify-center text-slate-400 mb-4">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <p class="text-sm font-semibold text-slate-400 m-0 mb-1.5">Seleccioná un docente</p>
        <p class="text-xs text-slate-500 m-0 max-w-xs leading-relaxed">
          Usá el buscador de arriba para encontrar un docente por nombre o código
        </p>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import DocenteSearch from '../components/DocenteSearch.vue'
import { useDocentes } from '../composables/useDocentes'

const {
  docentes,
  loading,
  error,
  searchQuery,
  dropdownOpen,
  filteredDocentes,
  selectedDocente,
  fetchDocentes,
  selectDocente,
  clearSelection,
} = useDocentes()

const initials = (d) => ((d.nombres?.[0] || '') + (d.apellidos?.[0] || '')).toUpperCase() || '?'

onMounted(() => fetchDocentes())
</script>