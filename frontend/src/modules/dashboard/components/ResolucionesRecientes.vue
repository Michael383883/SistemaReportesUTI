<template>
  <div class="bg-white border border-[#e8edf2] rounded-2xl p-5">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-[#081F33]">Resoluciones Recientes</h3>
      <router-link
        to="/resoluciones/listado"
        class="inline-flex items-center gap-1 text-xs font-semibold
               text-[#D28B45] hover:underline"
      >
        Ver todas <ArrowRight class="w-3 h-3" />
      </router-link>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="flex flex-col gap-2">
      <div v-for="n in 4" :key="n"
        class="h-14 rounded-xl bg-gradient-to-r from-[#e8edf2] via-[#f4f6f9]
               to-[#e8edf2] bg-[length:200%_100%] animate-shimmer" />
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length"
      class="flex flex-col items-center justify-center gap-2 py-10
             text-gray-400 text-sm">
      <FileText class="w-7 h-7 text-gray-300" />
      Sin resoluciones registradas
    </div>

    <!-- Lista -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1">
      <div
        v-for="res in items"
        :key="res.id"
        class="flex items-center gap-3 px-2 py-2.5 rounded-xl
               hover:bg-amber-50/60 cursor-pointer transition-colors"
        @click="$router.push(`/resoluciones/${res.id}`)"
      >
        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center
                    justify-center flex-shrink-0">
          <FileText class="w-4 h-4 text-[#D28B45]" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-semibold text-gray-800">
            Resol. N° {{ res.numero_resolucion }}
          </p>
          <p class="text-[11px] text-gray-400 truncate">
            {{ truncate(res.descripcion, 44) }}
          </p>
        </div>
        <span class="text-[11px] text-gray-400 whitespace-nowrap flex-shrink-0">
          {{ formatDate(res.created_at) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ArrowRight, FileText } from 'lucide-vue-next'

const props = defineProps({
  items:   { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})

function truncate(str, len) {
  if (!str) return '—'
  return str.length > len ? str.slice(0, len) + '…' : str
}
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-BO',
    { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>