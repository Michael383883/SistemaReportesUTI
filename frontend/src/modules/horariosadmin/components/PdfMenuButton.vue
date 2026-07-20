<template>
  <div class="flex flex-col" ref="dropdownRef">
    <label class="text-[0.68rem] invisible">PDF</label>
    <div class="relative">
      <div
        class="inline-flex rounded-full overflow-visible border border-red-700/40 shadow-lg shadow-red-900/20"
        :class="disabled ? 'opacity-40 pointer-events-none' : ''"
      >
        <button
          @click.stop="abierto = !abierto"
          class="inline-flex items-center gap-2 pl-5 pr-4 py-2 text-sm font-semibold
                 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                 rounded-l-full transition-all duration-150"
        >
          <svg :class="loading ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2">
            <template v-if="loading">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round" />
            </template>
            <template v-else>
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
            </template>
          </svg>
          Generar PDF
        </button>

        <button
          @click.stop="abierto = !abierto"
          class="px-3 py-2 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                 border-l border-red-600/60 rounded-r-full transition-all duration-150"
          aria-label="Más opciones de PDF"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
               :style="abierto ? 'transform:rotate(180deg)' : ''" style="transition: transform 0.15s">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>
      </div>

      <div v-if="abierto" class="fixed inset-0 z-40" @click="abierto = false" />

      <Transition
        enter-active-class="transition-all duration-150 ease-out"
        enter-from-class="opacity-0 scale-95 -translate-y-1"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition-all duration-100 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 -translate-y-1"
      >
        <div
          v-if="abierto"
          class="absolute right-0 top-full mt-1.5 z-50 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden"
          :class="menuWidth"
        >
          <!-- `cerrar` se pasa al slot para que cada opción cierre el menú tras hacer click -->
          <slot :cerrar="() => (abierto = false)" />
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

defineProps({
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  menuWidth: { type: String, default: 'w-52' },
})

const abierto = ref(false)
const dropdownRef = ref(null)

function onClickFuera(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    abierto.value = false
  }
}
onMounted(() => document.addEventListener('click', onClickFuera))
onBeforeUnmount(() => document.removeEventListener('click', onClickFuera))
</script>