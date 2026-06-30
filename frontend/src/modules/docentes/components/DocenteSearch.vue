<template>
  <div class="relative w-full max-w-xl" ref="containerRef">
    <label class="block text-xs font-semibold tracking-widest uppercase text-slate-400 mb-1.5">
      Buscar
    </label>

    <div
      class="flex items-center bg-slate-800 border border-slate-700 rounded-xl overflow-hidden transition-all duration-200"
      :class="isFocused ? 'border-amber-500 ring-2 ring-amber-500/20' : ''"
    >
      <button
        type="button"
        class="flex items-center gap-1.5 px-3.5 py-2.5 bg-transparent border-none text-slate-400 text-sm font-medium cursor-pointer whitespace-nowrap shrink-0 hover:text-slate-100 hover:bg-white/5 transition-colors"
        :class="{ 'text-slate-100 bg-white/5': isOpen }"
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
          :class="{ 'rotate-180': isOpen }"
          width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
        >
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </button>

      <div class="w-px h-5 bg-slate-700 shrink-0"/>

      <div class="flex-1 flex items-center px-2.5 gap-2">
        <svg class="text-slate-400 shrink-0" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          ref="inputRef"
          v-model="localQuery"
          type="text"
          placeholder="Buscar por nombre o código SIS..."
          autocomplete="off"
          role="combobox"
          aria-haspopup="listbox"
          :aria-expanded="isOpen"
          :aria-activedescendant="highlightedIndex >= 0 ? `docente-opt-${highlightedIndex}` : undefined"
          class="flex-1 bg-transparent border-none outline-none text-slate-100 text-sm py-2.5 min-w-0 placeholder-slate-500"
          @focus="onFocus"
          @blur="onBlur"
          @input="onInput"
          @keydown="onKeydown"
        />
        <button
          v-if="localQuery"
          type="button"
          class="flex items-center p-0.5 rounded bg-transparent border-none cursor-pointer text-slate-400 hover:text-slate-100 hover:bg-white/10 transition-colors"
          @click="handleClear"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>

    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-150 ease-out"
        enter-from-class="opacity-0 -translate-y-1.5"
        leave-active-class="transition-all duration-150 ease-in"
        leave-to-class="opacity-0 -translate-y-1.5"
      >
        <div
          v-if="isOpen"
          :style="dropdownStyle"
          class="fixed z-[9999] bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden"
        >
          <div v-if="loading" class="flex items-center gap-2 px-4 py-4 text-sm text-slate-400">
            <span class="w-3.5 h-3.5 border-2 border-slate-700 border-t-amber-500 rounded-full animate-spin shrink-0"/>
            Cargando docentes...
          </div>

          <div v-else-if="listaEfectiva.length === 0" class="flex flex-col items-center gap-1.5 px-4 py-6 text-sm text-slate-400">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Sin resultados para "<strong class="text-slate-300">{{ localQuery }}</strong>"
          </div>

          <ul v-else ref="listRef" class="list-none m-0 p-1.5 max-h-72 overflow-y-auto" role="listbox">
            <li
              v-for="(docente, index) in listaEfectiva"
              :id="`docente-opt-${index}`"
              :key="docente.id"
              role="option"
              :aria-selected="selectedDocente?.id === docente.id"
              class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-colors"
              :class="[
                selectedDocente?.id === docente.id ? 'bg-amber-500/10' : '',
                highlightedIndex === index ? 'bg-white/10' : 'hover:bg-white/5'
              ]"
              @mousedown.prevent="handleSelect(docente)"
              @mouseenter="highlightedIndex = index"
            >
              <div
                class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-700 to-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0 tracking-wide"
                aria-hidden="true"
              >
                {{ initials(docente) }}
              </div>

              <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                <span class="text-sm font-medium text-slate-100 truncate">
                   {{ docente.apellidos }} {{ docente.nombres }}
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="text-[0.68rem] font-semibold tracking-wide bg-indigo-500/15 text-indigo-300 px-1.5 py-px rounded">
                    SIS: {{ docente.codigo }}
                  </span>
                </span>
              </div>

              <svg
                v-if="selectedDocente?.id === docente.id"
                class="text-amber-500 shrink-0"
                width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
              >
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </li>
          </ul>

          <div class="px-4 py-1.5 text-xs text-slate-500 border-t border-slate-700 text-right">
            {{ listaEfectiva.length }} docente{{ listaEfectiva.length !== 1 ? 's' : '' }}
            encontrado{{ listaEfectiva.length !== 1 ? 's' : '' }}
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'

const props = defineProps({
  docentes:         { type: Array,  default: () => [] },
  filteredDocentes: { type: Array,  default: null },
  selectedDocente:  { type: Object, default: null },
  loading:          { type: Boolean, default: false },
})

const emit = defineEmits(['update:searchQuery', 'select', 'clear'])

const containerRef = ref(null)
const inputRef     = ref(null)
const listRef       = ref(null)
const isFocused     = ref(false)
const localQuery    = ref('')
const isOpen        = ref(false)
const highlightedIndex = ref(-1)

const dropdownStyle = ref({})

const updateDropdownPosition = async () => {
  await nextTick()
  if (!containerRef.value) return
  const rect = containerRef.value.getBoundingClientRect()
  dropdownStyle.value = {
    top:   `${rect.bottom + 6}px`,
    left:  `${rect.left}px`,
    width: `${rect.width}px`,
  }
}

const listaEfectiva = computed(() => {
  if (props.filteredDocentes !== null) return props.filteredDocentes
  const q = localQuery.value.trim().toLowerCase()
  if (!q) return props.docentes
  return props.docentes.filter(d => {
    const nombre = `${d.nombres} ${d.apellidos}`.toLowerCase()
    return (
      d.nombres?.toLowerCase().includes(q) ||
      d.apellidos?.toLowerCase().includes(q) ||
      nombre.includes(q) ||
      String(d.codigo).includes(q)
    )
  })
})

// Si la lista cambia (filtro), reseteamos el resaltado
watch(listaEfectiva, () => {
  highlightedIndex.value = listaEfectiva.value.length > 0 ? 0 : -1
})

const initials = (d) =>
  ((d.nombres?.[0] ?? '') + (d.apellidos?.[0] ?? '')).toUpperCase() || '?'

const toggleDropdown = async () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await updateDropdownPosition()
    inputRef.value?.focus()
    highlightedIndex.value = listaEfectiva.value.length > 0 ? 0 : -1
  }
}

const onFocus = async () => {
  isFocused.value = true
  isOpen.value    = true
  await updateDropdownPosition()
  if (highlightedIndex.value < 0 && listaEfectiva.value.length > 0) {
    highlightedIndex.value = 0
  }
}

const onBlur = () => {
  isFocused.value = false
}

const onInput = () => {
  isOpen.value = true
  if (props.filteredDocentes !== null) {
    emit('update:searchQuery', localQuery.value)
  }
}


const onKeydown = async (e) => {
  const total = listaEfectiva.value.length

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (!isOpen.value) {
      isOpen.value = true
      await updateDropdownPosition()
    }
    if (total > 0) {
      highlightedIndex.value = (highlightedIndex.value + 1) % total
      scrollHighlightedIntoView()
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (!isOpen.value) {
      isOpen.value = true
      await updateDropdownPosition()
    }
    if (total > 0) {
      highlightedIndex.value = highlightedIndex.value <= 0 ? total - 1 : highlightedIndex.value - 1
      scrollHighlightedIntoView()
    }
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (isOpen.value && highlightedIndex.value >= 0 && listaEfectiva.value[highlightedIndex.value]) {
      handleSelect(listaEfectiva.value[highlightedIndex.value])
    }
  } else if (e.key === 'Escape') {
    isOpen.value = false
    inputRef.value?.blur()
  } else if (e.key === 'Home' && isOpen.value && total > 0) {
    e.preventDefault()
    highlightedIndex.value = 0
    scrollHighlightedIntoView()
  } else if (e.key === 'End' && isOpen.value && total > 0) {
    e.preventDefault()
    highlightedIndex.value = total - 1
    scrollHighlightedIntoView()
  }
}

const scrollHighlightedIntoView = () => {
  nextTick(() => {
    const el = listRef.value?.children?.[highlightedIndex.value]
    el?.scrollIntoView({ block: 'nearest' })
  })
}

const handleSelect = (docente) => {
  localQuery.value = `${docente.apellidos} ${docente.nombres} `
  isOpen.value     = false
  emit('select', docente)
}

const handleClear = () => {
  localQuery.value = ''
  highlightedIndex.value = -1
  emit('update:searchQuery', '')
  emit('clear')
}

const handleClickOutside = (e) => {
  if (
    containerRef.value && !containerRef.value.contains(e.target)
  ) {
    isOpen.value = false
  }
}

// Recalcular posición si el usuario hace scroll o resize
const handleScroll = () => { if (isOpen.value) updateDropdownPosition() }

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('scroll', handleScroll, true)
  window.addEventListener('resize', handleScroll)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('resize', handleScroll)
})
</script>