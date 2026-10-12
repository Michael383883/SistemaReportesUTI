<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 p-6 transition-colors">
    <div v-if="loading" class="py-16 text-center text-[13px] text-gray-500 dark:text-slate-400">Cargando…</div>

    <div v-else-if="error"
      class="p-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/40 rounded-lg text-red-600 dark:text-red-300 text-[12px]">
      {{ error }}
    </div>

    <template v-else-if="docente">
      <!-- ═════ Cabecera ═════ -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm px-5 py-4 mb-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
          <button @click="volver" title="Volver al reporte"
            class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100 transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Nombre -->
          <div class="min-w-[220px]">
            <p class="text-[11px] font-bold tracking-wide text-blue-600 dark:text-blue-400 uppercase">Títulos registrados del docente</p>
            <h1 class="text-[20px] font-bold text-gray-900 dark:text-white leading-tight uppercase">
              {{ docente.apellidos }} {{ docente.nombres }}
            </h1>
            <p class="text-[12px] text-gray-500 dark:text-slate-400 mt-0.5">
              Código: {{ docente.codigo }} · {{ titulos.length }} título{{ titulos.length === 1 ? '' : 's' }} registrado{{ titulos.length === 1 ? '' : 's' }}
            </p>
          </div>

          <!-- Los 4 datos, al lado del nombre -->
          <div class="flex-1 min-w-[320px] flex flex-wrap items-center gap-x-8 gap-y-2 lg:pl-6 lg:border-l lg:border-gray-200 dark:lg:border-slate-700">
            <div v-for="d in datosDocente" :key="d.label" class="min-w-[90px]">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">{{ d.label }}</p>
              <p class="text-[13px] font-semibold text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ d.value || '—' }}</p>
            </div>
          </div>

          <!-- Buscador -->
          <div class="relative w-full sm:w-64 flex-shrink-0">
            <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
            <input v-model="busqueda" type="text" placeholder="Buscar título…"
              class="w-full border border-gray-200 dark:border-slate-600 rounded-xl pl-9 pr-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400" />
          </div>
        </div>

        <!-- Resumen por tipo (también filtra) -->
        <div v-if="resumenTipos.length" class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100 dark:border-slate-700">
          <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 mr-1">Tipos:</span>
          <button v-for="r in resumenTipos" :key="r.tipo" @click="tipoSel = (tipoSel === r.tipo ? null : r.tipo)"
            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase transition-all"
            :class="[colorTipo(r.tipo).chip, tipoSel === r.tipo ? 'ring-2 ring-offset-1 ring-gray-400 dark:ring-slate-400 dark:ring-offset-slate-800' : 'opacity-90 hover:opacity-100']">
            {{ r.tipo }}
            <span class="bg-white/70 dark:bg-white/15 rounded-full px-1.5 text-[10px]">{{ r.n }}</span>
          </button>
        </div>
      </div>

      <!-- ═════ Títulos ═════ -->
      <div v-if="!titulos.length"
        class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-8 text-center text-[13px] text-gray-600 dark:text-slate-300">
        Este docente no tiene títulos registrados.
      </div>

      <div v-else-if="!lista.length"
        class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-8 text-center text-[13px] text-gray-600 dark:text-slate-300">
        Ningún título coincide con la búsqueda.
      </div>

      <div v-else class="space-y-2.5">
        <div v-for="t in lista" :key="t.id"
          class="flex rounded-xl border shadow-sm overflow-hidden bg-white dark:bg-slate-800"
          :class="[colorTipo(t.tipo_titulo).soft, colorTipo(t.tipo_titulo).border]">
          <div class="w-1.5 flex-shrink-0" :class="colorTipo(t.tipo_titulo).bar"></div>

          <div class="flex-1 flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
            <div class="flex-1 min-w-[260px]">
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase" :class="colorTipo(t.tipo_titulo).chip">
                  {{ t.tipo_titulo || 'Sin tipo' }}
                </span>
                <span v-if="t.titulo"
                  class="px-2 py-0.5 rounded-md bg-white/80 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 text-gray-600 dark:text-slate-300 text-[11px] font-semibold">
                  {{ t.titulo }}
                </span>
                <span class="text-[12px] text-gray-500 dark:text-slate-400">{{ fmtFecha(t.fecha_titulo) || 'Sin fecha' }}</span>
              </div>

              <h3 class="text-[14px] font-bold text-gray-900 dark:text-slate-100 leading-snug">
                {{ t.nombre_titulo || 'Sin nombre de título' }}
              </h3>

              <p class="text-[12px] text-gray-600 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                <span v-if="t.universidad">{{ t.universidad }}</span>
                <span>País: <b class="font-semibold text-gray-800 dark:text-slate-200">{{ t.pais || '—' }}</b></span>
                <span>N°: <b class="font-semibold text-gray-800 dark:text-slate-200">{{ t.numero || '—' }}</b></span>
              </p>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
              <template v-if="t.documentos.length">
                <button @click="descargar(t.documentos[0])" :disabled="cargandoId === t.documentos[0].id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 text-[12px] font-semibold disabled:opacity-50 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Descargar
                </button>
                <button @click="ver(t.documentos[0])" :disabled="cargandoId === t.documentos[0].id"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 text-white text-[12px] font-bold disabled:opacity-50 transition-colors shadow-sm">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Ver PDF
                </button>
              </template>
              <span v-else class="text-[12px] text-gray-400 dark:text-slate-500 italic">Sin PDF adjunto</span>
            </div>
          </div>
        </div>

        <p v-if="errorDoc" class="text-[12px] text-red-600 dark:text-red-300">{{ errorDoc }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDocenteTitulos } from '../composables/useDocenteTitulos'
import { useDocumentoTitulo } from '../composables/useDocumentoTitulo'
import { norm, fmtFecha, colorTipo } from '../composables/utilsTitulo'

const route = useRoute()
const router = useRouter()
const { docente, titulos, loading, error, cargar } = useDocenteTitulos()
const { cargandoId, errorDoc, ver, descargar } = useDocumentoTitulo()

const busqueda = ref('')
const tipoSel = ref(null)

const lista = computed(() => {
  let l = titulos.value
  if (tipoSel.value) l = l.filter(t => t.tipo_titulo === tipoSel.value)
  const q = norm(busqueda.value)
  if (q) {
    l = l.filter(t =>
      [t.tipo_titulo, t.titulo, t.nombre_titulo, t.universidad, t.pais, t.numero].map(norm).join(' ').includes(q)
    )
  }
  return l
})

const resumenTipos = computed(() => {
  const m = {}
  for (const t of titulos.value) {
    const k = t.tipo_titulo || 'Sin tipo'
    m[k] = (m[k] || 0) + 1
  }
  return Object.entries(m).map(([tipo, n]) => ({ tipo, n }))
})

const sexoTexto = (s) => (s === 'M' ? 'Masculino' : s === 'F' ? 'Femenino' : s)

const datosDocente = computed(() => {
  const d = docente.value || {}
  return [
    { label: 'CI', value: d.ci },
    { label: 'Nacimiento', value: fmtFecha(d.fecha_nac) },
    { label: 'Sexo', value: sexoTexto(d.sexo) },
    { label: 'Nombramiento', value: fmtFecha(d.fecha_nombramiento) },
  ]
})

function volver() {
  if (window.history.length > 1) router.back()
  else router.push('/reportes')
}

onMounted(() => cargar(route.params.codigo))
</script>