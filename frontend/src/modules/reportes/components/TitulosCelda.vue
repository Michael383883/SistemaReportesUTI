<template>
  <div class="text-[12px]">
    <span v-if="!titulos.length"
      class="inline-block px-2 py-0.5 rounded-md bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/40 font-medium">
      No tiene título registrado
    </span>

    <ul v-else class="space-y-1.5">
      <li v-for="(t, i) in visibles" :key="i">
        <div class="flex items-center gap-1.5">
          <span class="font-semibold text-gray-900 dark:text-slate-100 leading-tight">{{ t.nombre_titulo || 'Sin nombre' }}</span>

          <button v-if="t.tiene_archivo"
            @click="ver({ id: t.id_documento, nombre_archivo: t.nombre_archivo })"
            :disabled="cargandoId === t.id_documento" title="Ver PDF"
            class="inline-flex items-center justify-center w-6 h-6 rounded-md text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200 dark:text-blue-300 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:border-blue-500/40 disabled:opacity-50 flex-shrink-0 transition-colors">
            <svg v-if="cargandoId !== t.id_documento" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg v-else class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </button>
          <span v-else class="text-[10px] text-gray-400 dark:text-slate-500 flex-shrink-0" title="Sin PDF adjunto">sin PDF</span>

          <!-- Desplegable: solo en el primero -->
          <button v-if="i === 0 && titulos.length > 1" @click="abierto = !abierto"
            class="ml-0.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-800 hover:bg-blue-100 dark:bg-blue-500/15 dark:border-blue-500/40 dark:text-blue-300 dark:hover:bg-blue-500/25 text-[11px] font-bold transition-colors">
            {{ abierto ? 'Ocultar' : '+' + (titulos.length - 1) }}
            <svg class="w-3 h-3 transition-transform" :class="abierto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <p v-if="meta(t)" class="text-[11px] text-gray-500 dark:text-slate-400 leading-tight">{{ meta(t) }}</p>
      </li>
    </ul>

    <p v-if="errorDoc" class="text-red-600 dark:text-red-300 mt-1">{{ errorDoc }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useDocumentoTitulo } from '../composables/useDocumentoTitulo'
import { fmtFecha } from '../composables/utilsTitulo'

const props = defineProps({
  titulos: { type: Array, default: () => [] },
  extras: { type: Array, default: () => [] }, // campos_titulo marcados
})

const abierto = ref(false)
const { cargandoId, errorDoc, ver } = useDocumentoTitulo()

const visibles = computed(() => abierto.value ? props.titulos : props.titulos.slice(0, 1))

function meta(t) {
  const partes = [t.tipo_titulo]
  if (props.extras.includes('UNIVERSIDAD')) partes.push(t.universidad)
  if (props.extras.includes('PAIS')) partes.push(t.pais)
  if (props.extras.includes('FECHA_TITULO')) partes.push(fmtFecha(t.fecha_titulo))
  if (props.extras.includes('NUMERO') && t.numero) partes.push('N° ' + t.numero)
  return partes.filter(Boolean).join(' · ')
}
</script>