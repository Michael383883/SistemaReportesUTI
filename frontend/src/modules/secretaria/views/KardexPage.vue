<template>
  <!-- Ancho completo: antes estaba limitado con max-w-5xl (1024px) y por eso la tabla se veía chica -->
  <div class="w-full max-w-[1600px] mx-auto px-4 lg:px-8 py-2">

    <!-- Encabezado -->
    <div class="flex items-start justify-between mb-7">
      <div>
        <h1 class="text-[22px] font-semibold text-slate-900 dark:text-slate-50 m-0">Kardex de Docentes</h1>
        <p class="text-[14px] text-slate-500 dark:text-slate-400 m-0 mt-1">
          Consultá las materias dictadas de un docente e imprimí su kardex
        </p>
      </div>
    </div>

    <!-- Verificando permiso -->
    <div v-if="!cargado" class="space-y-3 animate-pulse">
      <div class="h-12 max-w-xl rounded-xl bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700"/>
    </div>

    <!-- Módulo NO habilitado por el administrador -->
    <div
      v-else-if="!habilitado"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-4">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
      <p class="text-[15px] font-semibold text-slate-700 dark:text-slate-200 m-0 mb-1">Módulo no habilitado</p>
      <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 max-w-sm leading-relaxed">
        El administrador no habilitó el Kardex para tu rol. Si lo necesitás, solicitá que lo active.
      </p>
    </div>

    <!-- Módulo habilitado -->
    <template v-else>

      <!-- Error de docentes -->
      <div
        v-if="errorDocentes"
        class="flex items-center gap-2 px-3.5 py-2.5 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 rounded-lg text-red-600 dark:text-red-400 text-[14px] mb-5"
      >
        {{ errorDocentes }}
        <button
          class="ml-auto bg-red-100 dark:bg-red-500/15 border-none text-red-600 dark:text-red-400 text-[12px] font-semibold px-2.5 py-1 rounded cursor-pointer hover:bg-red-200 dark:hover:bg-red-500/25 transition-colors"
          @click="fetchDocentes"
        >Reintentar</button>
      </div>

      <!-- Buscador -->
      <div class="mb-6 max-w-2xl">
        <KardexBuscador
          v-model="searchQuery"
          :resultados="filteredDocentes"
          :selected="selectedDocente"
          :loading="loadingDocentes"
          @select="seleccionarDocente"
          @clear="limpiar"
        />
      </div>

      <!-- Resultado -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        leave-active-class="transition-all duration-200 ease-in"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div v-if="selectedDocente" class="space-y-4">

          <!-- Tarjeta del docente + Imprimir -->
          <div class="relative rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm dark:shadow-none overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600"/>
            <div class="flex items-center gap-4 py-4 pr-5 pl-6 flex-wrap">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-700 to-violet-600 text-white text-[15px] font-bold flex items-center justify-center shrink-0 tracking-wide">
                {{ iniciales(selectedDocente) }}
              </div>

              <div class="flex-1 min-w-0">
                <h2 class="text-[17px] font-semibold text-slate-900 dark:text-slate-100 m-0 mb-1.5 tracking-tight">
                  {{ selectedDocente.apellidos }} {{ selectedDocente.nombres }}
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[12px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                    SIS: {{ selectedDocente.codigo }}
                  </span>
                  <span v-if="reporte" class="text-[12px] font-semibold text-amber-600 dark:text-amber-400">
                    {{ reporte.total }} materia{{ reporte.total !== 1 ? 's' : '' }}
                  </span>
                </div>
              </div>

              <button
                type="button"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-semibold bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-900 transition-all duration-150 cursor-pointer border-none shadow-lg shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800"
                :disabled="loadingReporte || !reporte"
                title="Imprimir kardex"
                @click="imprimir"
              >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="6 9 6 2 18 2 18 9"/>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                  <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Imprimir
              </button>
            </div>
          </div>

          <!-- Error del kardex -->
          <div
            v-if="errorReporte"
            class="flex items-center gap-2 px-3.5 py-2.5 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 rounded-lg text-red-600 dark:text-red-400 text-[14px]"
          >
            {{ errorReporte }}
          </div>

          <!-- Cargando -->
          <div v-else-if="loadingReporte" class="space-y-3 animate-pulse">
            <div class="h-10 rounded-lg bg-slate-200 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700/50"/>
            <div class="h-96 rounded-xl bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700"/>
          </div>

          <!-- Tabla: ocupa todo el ancho y hace scroll horizontal solo si hace falta -->
          <div v-else-if="reporte" class="kardex-tabla-wrap w-full overflow-x-auto rounded-xl">
            <KardexTabla :materias="reporte.materias" />
          </div>
        </div>
      </Transition>

      <!-- Estado vacío -->
      <div v-if="!selectedDocente && !loadingDocentes" class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-20 h-20 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-4">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <p class="text-[15px] font-semibold text-slate-700 dark:text-slate-200 m-0 mb-1.5">Seleccioná un docente</p>
        <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 max-w-xs leading-relaxed">
          Buscalo por apellido, nombre o código y su kardex aparece acá abajo.
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import KardexBuscador from '../components/KardexBuscador.vue'
import KardexTabla from '../components/KardexTabla.vue'
import { useKardexPermiso } from '../composables/useKardexPermiso'
import { useKardexSecretaria } from '../composables/useKardexSecretaria'

const { habilitado, cargado, cargar } = useKardexPermiso()

const {
  loadingDocentes,
  errorDocentes,
  searchQuery,
  filteredDocentes,
  selectedDocente,
  reporte,
  loadingReporte,
  errorReporte,
  fetchDocentes,
  seleccionarDocente,
  limpiar,
  imprimir,
} = useKardexSecretaria()

const iniciales = (d) => ((d.nombres?.[0] ?? '') + (d.apellidos?.[0] ?? '')).toUpperCase() || '?'

onMounted(async () => {
  // forzar = true: siempre confirma con el backend al entrar a la vista
  await cargar(true)
  if (habilitado.value) fetchDocentes()
})
</script>

<style scoped>
/*
  Agranda la tabla sin tocar KardexTabla.vue:
  - letra y espaciado de celdas más grandes
  - ancho mínimo para que las columnas no se aprieten
  Si KardexTabla usa <style scoped>, cambiá `:deep(...)` por los estilos dentro del propio componente.
*/
.kardex-tabla-wrap :deep(table) {
  width: 100%;
  min-width: 900px;
}
.kardex-tabla-wrap :deep(th) {
  font-size: 12px;
  padding-top: 14px;
  padding-bottom: 14px;
}
.kardex-tabla-wrap :deep(td) {
  font-size: 14px;
  padding-top: 12px;
  padding-bottom: 12px;
}

/* Modo oscuro de la tabla (filas, rayas y texto) */
:global(.dark) .kardex-tabla-wrap :deep(tbody tr) {
  background-color: #1e293b;          /* slate-800 */
  color: #e2e8f0;                     /* slate-200 */
}
:global(.dark) .kardex-tabla-wrap :deep(tbody tr:nth-child(even)) {
  background-color: #172033;
}
:global(.dark) .kardex-tabla-wrap :deep(tbody tr:hover) {
  background-color: #263449;
}
:global(.dark) .kardex-tabla-wrap :deep(td) {
  color: #e2e8f0;
  border-color: #334155;              /* slate-700 */
}
:global(.dark) .kardex-tabla-wrap :deep(thead tr),
:global(.dark) .kardex-tabla-wrap :deep(thead th) {
  background-color: #0b1220;
  color: #cbd5e1;
}
</style>