<template>
  <div class="relative w-full max-w-xl" ref="containerRef">
    <!-- Label -->
    <label class="block text-xs font-semibold tracking-widest uppercase text-slate-400 mb-1.5">
      Buscar
    </label>

    <div
      class="flex items-center bg-slate-800 border border-slate-700 rounded-xl overflow-hidden transition-all duration-200"
      :class="isFocused ? 'border-amber-500 ring-2 ring-amber-500/20' : ''"
    >
      <!-- Dropdown trigger -->
      <button
        type="button"
        class="flex items-center gap-1.5 px-3.5 py-2.5 bg-transparent border-none text-slate-400 text-sm font-medium cursor-pointer whitespace-nowrap shrink-0 hover:text-slate-100 hover:bg-white/5 transition-colors"
        :class="{ 'text-slate-100 bg-white/5': dropdownOpen }"
        @click="toggleDropdown"
        title="Ver todos los docentes"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span>Todos</span>
        <svg
          class="transition-transform duration-200"
          :class="{ 'rotate-180': dropdownOpen }"
          width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
        >
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </button>

      <!-- Divider -->
      <div class="w-px h-5 bg-slate-700 shrink-0"/>

      <!-- Text input -->
      <div class="flex-1 flex items-center px-2.5 gap-2">
        <svg class="text-slate-400 shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          ref="inputRef"
          v-model="searchQuery"
          type="text"
          placeholder="Buscar por nombre o código..."
          autocomplete="off"
          class="flex-1 bg-transparent border-none outline-none text-slate-100 text-sm py-2.5 min-w-0 placeholder-slate-500"
          @focus="onFocus"
          @blur="onBlur"
          @input="onInput"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="flex items-center p-0.5 rounded bg-transparent border-none cursor-pointer text-slate-400 hover:text-slate-100 hover:bg-white/10 transition-colors"
          @click="clearSelection"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Dropdown list -->
    <Transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1.5"
      leave-active-class="transition-all duration-150 ease-in"
      leave-to-class="opacity-0 -translate-y-1.5"
    >
      <div
        v-if="dropdownOpen"
        class="absolute top-full left-0 right-0 mt-1.5 z-50 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden"
      >
        <!-- Loading -->
        <div v-if="loading" class="flex items-center gap-2 px-4 py-4 text-sm text-slate-400">
          <span class="w-3.5 h-3.5 border-2 border-slate-700 border-t-amber-500 rounded-full animate-spin shrink-0"/>
          Cargando docentes...
        </div>

        <!-- Empty -->
        <div v-else-if="filteredDocentes.length === 0" class="flex flex-col items-center gap-1.5 px-4 py-6 text-sm text-slate-400">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          Sin resultados para "<strong class="text-slate-300">{{ searchQuery }}</strong>"
        </div>

        <!-- List -->
        <ul v-else class="list-none m-0 p-1.5 max-h-72 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-700" role="listbox">
          <li
            v-for="docente in filteredDocentes"
            :key="docente.id"
            role="option"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-colors"
            :class="selectedDocente?.id === docente.id ? 'bg-amber-500/10' : 'hover:bg-white/5'"
            @mousedown.prevent="selectDocente(docente)"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-700 to-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0 tracking-wide">
              {{ initials(docente) }}
            </div>
            <div class="flex-1 min-w-0 flex flex-col gap-0.5">
              <span class="text-sm font-medium text-slate-100 truncate">{{ docente.nombres }} {{ docente.apellidos }}</span>
              <span class="flex items-center gap-1.5">
                <span class="text-[0.68rem] font-semibold tracking-wide bg-indigo-500/15 text-indigo-300 px-1.5 py-px rounded">
                  SIS: {{ docente.codigo}}
                </span>
              </span>
            </div>
            <svg v-if="selectedDocente?.id === docente.id" class="text-amber-500 shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </li>
        </ul>

        <!-- Footer -->
        <div class="px-4 py-1.5 text-xs text-slate-500 border-t border-slate-700 text-right">
          {{ filteredDocentes.length }} docente{{ filteredDocentes.length !== 1 ? 's' : '' }} encontrado{{ filteredDocentes.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  searchQuery:      { type: String,  default: '' },
  dropdownOpen:     { type: Boolean, default: false },
  filteredDocentes: { type: Array,   default: () => [] },
  selectedDocente:  { type: Object,  default: null },
  loading:          { type: Boolean, default: false },
})

const emit = defineEmits(['update:searchQuery', 'update:dropdownOpen', 'select', 'clear'])

const containerRef = ref(null)
const inputRef     = ref(null)
const isFocused    = ref(false)

const searchQuery  = ref(props.searchQuery)
const dropdownOpen = ref(props.dropdownOpen)

const initials = (d) => ((d.nombres?.[0] || '') + (d.apellidos?.[0] || '')).toUpperCase() || '?'

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
  emit('update:dropdownOpen', dropdownOpen.value)
  if (dropdownOpen.value) inputRef.value?.focus()
}

const onFocus = () => {
  isFocused.value    = true
  dropdownOpen.value = true
  emit('update:dropdownOpen', true)
}

const onBlur = () => { isFocused.value = false }

const onInput = () => {
  dropdownOpen.value = true
  emit('update:searchQuery', searchQuery.value)
  emit('update:dropdownOpen', true)
}

const selectDocente = (docente) => {
  emit('select', docente)
  searchQuery.value  = `${docente.nombres} ${docente.apellidos}`
  dropdownOpen.value = false
  emit('update:dropdownOpen', false)
}

const clearSelection = () => {
  searchQuery.value = ''
  emit('update:searchQuery', '')
  emit('clear')
}

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    dropdownOpen.value = false
    emit('update:dropdownOpen', false)
  }
}

onMounted(()      => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>