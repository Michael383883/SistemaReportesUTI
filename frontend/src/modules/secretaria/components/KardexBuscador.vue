<template>
  <div class="relative w-full max-w-xl" ref="containerRef">
    <label class="block text-xs font-semibold tracking-widest uppercase text-slate-500 mb-1.5">
      Buscar docente
    </label>

    <div
      class="flex items-center bg-slate-800 border rounded-xl overflow-hidden transition-all duration-200"
      :class="isFocused ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-700'"
    >
      <svg class="ml-3.5 text-slate-400 shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>

      <input
        ref="inputRef"
        :value="modelValue"
        type="text"
        placeholder="Apellido, nombre o código SIS..."
        autocomplete="off"
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="isOpen"
        :aria-activedescendant="highlighted >= 0 ? `kardex-opt-${highlighted}` : undefined"
        class="flex-1 bg-transparent border-none outline-none text-slate-100 text-sm px-2.5 py-3 min-w-0 placeholder-slate-500"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown="onKeydown"
      />

      <button
        v-if="modelValue"
        type="button"
        class="mr-2 flex items-center p-1 rounded bg-transparent border-none cursor-pointer text-slate-400 hover:text-slate-100 hover:bg-white/10 transition-colors"
        title="Limpiar"
        @mousedown.prevent
        @click="onClear"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <Transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1.5"
      leave-active-class="transition-all duration-150 ease-in"
      leave-to-class="opacity-0 -translate-y-1.5"
    >
      <div
        v-if="isOpen"
        class="absolute left-0 right-0 top-full mt-1.5 z-30 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden"
      >
        <div v-if="loading" class="flex items-center gap-2 px-4 py-4 text-sm text-slate-400">
          <span class="w-3.5 h-3.5 border-2 border-slate-700 border-t-amber-500 rounded-full animate-spin shrink-0"/>
          Cargando docentes...
        </div>

        <div v-else-if="visibles.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
          Sin resultados para "<strong class="text-slate-300">{{ modelValue }}</strong>"
        </div>

        <ul v-else ref="listRef" class="list-none m-0 p-1.5 max-h-72 overflow-y-auto" role="listbox">
          <li
            v-for="(d, i) in visibles"
            :id="`kardex-opt-${i}`"
            :key="d.codigo"
            role="option"
            :aria-selected="selected?.codigo === d.codigo"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-colors"
            :class="[
              selected?.codigo === d.codigo ? 'bg-amber-500/10' : '',
              highlighted === i ? 'bg-white/10' : 'hover:bg-white/5',
            ]"
            @mousedown.prevent="elegir(d)"
            @mouseenter="highlighted = i"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-700 to-violet-600 text-white text-xs font-bold flex items-center justify-center shrink-0 tracking-wide" aria-hidden="true">
              {{ iniciales(d) }}
            </div>
            <div class="flex-1 min-w-0 flex flex-col gap-0.5">
              <span class="text-sm font-medium text-slate-100 truncate">{{ d.apellidos }} {{ d.nombres }}</span>
              <span class="text-[0.68rem] font-semibold tracking-wide bg-indigo-500/15 text-indigo-300 px-1.5 py-px rounded w-fit">
                SIS: {{ d.codigo }}
              </span>
            </div>
          </li>
        </ul>

        <div class="px-4 py-1.5 text-xs text-slate-500 border-t border-slate-700 text-right">
          <template v-if="resultados.length > LIMITE">Mostrando {{ LIMITE }} de {{ resultados.length }} — seguí escribiendo para afinar</template>
          <template v-else>{{ resultados.length }} docente{{ resultados.length !== 1 ? 's' : '' }}</template>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  resultados: { type: Array, default: () => [] },
  selected:   { type: Object, default: null },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select', 'clear'])

const LIMITE = 50

const containerRef = ref(null)
const inputRef = ref(null)
const listRef = ref(null)
const isFocused = ref(false)
const isOpen = ref(false)
const highlighted = ref(-1)

const visibles = computed(() => props.resultados.slice(0, LIMITE))

watch(visibles, () => {
  highlighted.value = visibles.value.length ? 0 : -1
})

const iniciales = (d) => ((d.nombres?.[0] ?? '') + (d.apellidos?.[0] ?? '')).toUpperCase() || '?'

const onFocus = () => {
  isFocused.value = true
  isOpen.value = true
  if (highlighted.value < 0 && visibles.value.length) highlighted.value = 0
}
const onBlur = () => { isFocused.value = false }

const onInput = (e) => {
  isOpen.value = true
  emit('update:modelValue', e.target.value)
}

const elegir = (d) => {
  isOpen.value = false
  emit('select', d)
  inputRef.value?.blur()
}

const onClear = () => {
  emit('clear')
  isOpen.value = true
  nextTick(() => inputRef.value?.focus())
}

const scrollToHighlighted = () =>
  nextTick(() => listRef.value?.children?.[highlighted.value]?.scrollIntoView({ block: 'nearest' }))

const onKeydown = (e) => {
  const total = visibles.value.length

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    isOpen.value = true
    if (total) { highlighted.value = (highlighted.value + 1) % total; scrollToHighlighted() }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    isOpen.value = true
    if (total) { highlighted.value = highlighted.value <= 0 ? total - 1 : highlighted.value - 1; scrollToHighlighted() }
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (isOpen.value && visibles.value[highlighted.value]) elegir(visibles.value[highlighted.value])
  } else if (e.key === 'Escape') {
    isOpen.value = false
    inputRef.value?.blur()
  }
}

const onClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) isOpen.value = false
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>
