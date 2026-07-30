<template>
  <!-- ── Ítem sin hijos ── -->
  <router-link
    v-if="!item.children"
    :to="item.to"
    class="flex items-center transition-all border-l-[3px] border-transparent text-white/65 no-underline"
    :class="sidebarOpen ? 'gap-2 py-2 px-3.5 text-[13.5px] whitespace-nowrap' : 'justify-center py-2.5'"
    active-class="sidebar-active"
    :title="!sidebarOpen ? item.label : ''"
  >
    <component :is="item.icon" class="w-[17px] h-[17px] shrink-0" />
    <span v-if="sidebarOpen">{{ item.label }}</span>
  </router-link>

  <!-- ── Ítem con hijos ── -->
  <div v-else class="relative">

    <!-- ━━ MODO EXPANDIDO ━━ -->
    <template v-if="sidebarOpen">
      <button
        @click="submenuOpen = !submenuOpen"
        class="flex items-center justify-between w-full py-2 px-3.5 text-white/65 bg-transparent border-none border-l-[3px] border-transparent cursor-pointer text-[13.5px] whitespace-nowrap"
      >
        <div class="flex items-center gap-2">
          <component :is="item.icon" class="w-[17px] h-[17px] shrink-0" />
          <span>{{ item.label }}</span>
        </div>
        <ChevronDown
          class="w-3.5 h-3.5 transition-transform duration-200"
          :class="submenuOpen ? 'rotate-180' : ''"
        />
      </button>

      <div v-if="submenuOpen">
        <router-link
          v-for="child in item.children"
          :key="child.to"
          :to="child.to"
          class="flex items-center gap-2 pt-[7px] pr-3.5 pb-[7px] pl-[38px] text-[13px] text-white/50 no-underline border-l-[3px] border-transparent whitespace-nowrap"
          active-class="sidebar-active"
        >
          <span class="w-1 h-1 rounded-full bg-white/30 shrink-0"></span>
          {{ child.label }}
        </router-link>
      </div>
    </template>

    <!-- ━━ MODO COLAPSADO: ícono + flyout al hover ━━ -->
    <template v-else>
      <div ref="anchorRef" class="relative" @mouseenter="openFlyout" @mouseleave="closeFlyout">
        <div
          class="py-2.5 flex items-center justify-center border-l-[3px] cursor-pointer transition-colors duration-150"
          :class="isChildActive ? 'text-[#D28B45] border-l-[#D28B45] bg-[#D28B45]/[0.08]' : 'text-white/65 border-transparent'"
          :title="item.label"
        >
          <component :is="item.icon" class="w-[17px] h-[17px] shrink-0" />
        </div>

        <!-- Flyout panel -->
        <div
          v-if="flyoutOpen"
          class="fixed left-[52px] bg-[#0d2748] rounded-tr-lg rounded-br-lg shadow-[4px_4px_16px_rgba(0,0,0,0.4)] border border-white/10 border-l-2 border-l-[#D28B45] min-w-[185px] overflow-hidden z-[9999]"
          :style="{ top: flyoutTop + 'px' }"
          @mouseenter="openFlyout"
          @mouseleave="closeFlyout"
        >
          <div class="pt-2 px-3.5 pb-[7px] border-b border-white/[0.08] text-[10.5px] font-bold text-[#D28B45] uppercase tracking-[0.1em]">
            {{ item.label }}
          </div>

          <div class="py-1">
            <router-link
              v-for="child in item.children"
              :key="child.to"
              :to="child.to"
              class="flex items-center gap-2 py-[9px] px-4 text-[13px] text-white/70 no-underline whitespace-nowrap transition-colors duration-[120ms] hover:bg-[#D28B45]/[0.12]"
              active-class="flyout-active"
              @click="flyoutOpen = false"
            >
              <span class="w-[5px] h-[5px] rounded-full bg-white/25 shrink-0"></span>
              {{ child.label }}
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  item: { type: Object, required: true },
  sidebarOpen: { type: Boolean, default: true },
})

const route = useRoute()

const submenuOpen = ref(false)
const flyoutOpen = ref(false)
const flyoutTop = ref(0)
const anchorRef = ref(null)
let closeTimer = null

const isChildActive = computed(() =>
  props.item.children?.some(child => route.path.startsWith(child.to)) ?? false
)

function openFlyout() {
  if (closeTimer) {
    clearTimeout(closeTimer)
    closeTimer = null
  }
  nextTick(() => {
    if (anchorRef.value) {
      flyoutTop.value = anchorRef.value.getBoundingClientRect().top
    }
  })
  flyoutOpen.value = true
}

function closeFlyout() {
  closeTimer = setTimeout(() => {
    flyoutOpen.value = false
  }, 80)
}

onUnmounted(() => closeTimer && clearTimeout(closeTimer))
</script>
