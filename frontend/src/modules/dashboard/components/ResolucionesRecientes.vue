<template>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 transition-colors duration-300">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-slate-800 dark:text-white">Resoluciones Recientes</h3>
      <router-link
        to="/resoluciones/listado"
        class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline transition-colors"
      >
        Ver todas <ArrowRight class="w-3 h-3" />
      </router-link>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="flex flex-col gap-2">
      <div v-for="n in 4" :key="n"
        class="h-14 rounded-xl bg-gradient-to-r from-slate-200 dark:from-slate-700 via-slate-100 dark:via-slate-600 to-slate-200 dark:to-slate-700 bg-[length:200%_100%] animate-shimmer" />
    </div>

    <!-- Empty -->
    <div v-else-if="!items.length"
      class="flex flex-col items-center justify-center gap-2 py-10 text-slate-400 dark:text-slate-500 text-sm">
      <FileText class="w-7 h-7 text-slate-300 dark:text-slate-600" />
      Sin resoluciones registradas
    </div>

    <!-- Lista -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1">
      <div
        v-for="res in items"
        :key="res.id"
        class="flex items-center gap-3 px-2 py-2.5 rounded-xl hover:bg-amber-50/60 dark:hover:bg-amber-900/20 cursor-pointer transition-colors"
        @click="$router.push(`/resoluciones/${res.id}`)"
      >
        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
          <FileText class="w-4 h-4 text-amber-600 dark:text-amber-400" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-semibold text-slate-800 dark:text-white">
            Resol. N° {{ res.numero_resolucion }}
          </p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">
            {{ truncate(res.descripcion, 44) }}
          </p>
        </div>
        <span class="text-[11px] text-slate-400 dark:text-slate-500 whitespace-nowrap flex-shrink-0">
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