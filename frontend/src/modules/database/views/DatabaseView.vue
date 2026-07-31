<template>
  <div class="flex flex-col gap-5 p-1 max-w-5xl">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-0.5">
        Configuración de bases de datos
      </h1>
      <p class="text-sm text-gray-500">
        Estado de conexión, migración inicial y sincronización de datos
      </p>
    </div>

    <!-- ── Estado de conexiones ──────────────────────────────────────── -->
    <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm" aria-labelledby="section-status">
      <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
        <span id="section-status" class="text-white font-bold text-sm uppercase tracking-wide">
          Estado de conexiones
        </span>
        <button
          type="button"
          :disabled="loadingStatus"
          :aria-busy="loadingStatus"
          aria-label="Actualizar estado de conexiones"
          @click="loadStatus"
          class="flex items-center gap-1.5 text-[11px] text-slate-300 hover:text-white transition-colors outline-none
                 focus-visible:ring-1 focus-visible:ring-slate-400 rounded disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <RefreshCw class="w-3.5 h-3.5 transition-transform" :class="loadingStatus ? 'animate-spin' : ''" aria-hidden="true" />
          Actualizar
        </button>
      </div>

      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="flex flex-col gap-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg flex flex-col items-center justify-center gap-0.5 shrink-0 bg-pink-500/10 text-pink-600" aria-hidden="true">
              <Database class="w-4 h-4" />
              <span class="text-[9px] font-bold leading-none tracking-tight">2008</span>
            </div>
            <div class="flex flex-col flex-1 min-w-0">
              <strong class="text-[13px] text-gray-900 font-medium">SQL Server 2008 (origen)</strong>
              <small class="text-[11px] text-gray-500 font-mono truncate">
                {{ status.sqlserver_2008.host }} · {{ status.sqlserver_2008.database }}
              </small>
            </div>
          </div>
          <span
            class="self-start inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold"
            :class="status.sqlserver_2008.connected ? 'bg-emerald-500/15 text-emerald-600' : 'bg-red-500/15 text-red-600'"
          >
            <i class="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
            {{ status.sqlserver_2008.connected ? 'Conectado' : 'Sin conexión' }}
          </span>
        </div>

        <div class="flex flex-col gap-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg flex flex-col items-center justify-center gap-0.5 shrink-0 bg-blue-500/10 text-blue-600" aria-hidden="true">
              <Database class="w-4 h-4" />
              <span class="text-[9px] font-bold leading-none tracking-tight">2022</span>
            </div>
            <div class="flex flex-col flex-1 min-w-0">
              <strong class="text-[13px] text-gray-900 font-medium">SQL Server 2022 (destino)</strong>
              <small class="text-[11px] text-gray-500 font-mono truncate">
                {{ status.sqlserver_2022.host }} · {{ status.sqlserver_2022.database }}
              </small>
            </div>
          </div>
          <span
            class="self-start inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold"
            :class="status.sqlserver_2022.connected ? 'bg-emerald-500/15 text-emerald-600' : 'bg-red-500/15 text-red-600'"
          >
            <i class="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
            {{ status.sqlserver_2022.connected ? 'Conectado' : 'Sin conexión' }}
          </span>
        </div>
      </div>
    </section>

    <!-- ── Alerta de conexión ────────────────────────────────────────── -->
    <transition
      enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0"
    >
      <div v-if="!canSync" role="alert" class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[12px]">
        <AlertTriangle class="w-4 h-4 shrink-0" aria-hidden="true" />
        <p class="m-0 leading-relaxed">
          <template v-if="!status.sqlserver_2008.connected && !status.sqlserver_2022.connected">
            Ninguna base de datos tiene conexión activa.
          </template>
          <template v-else-if="!status.sqlserver_2008.connected">
            SQL Server 2008 <strong>(origen)</strong> no tiene conexión activa.
          </template>
          <template v-else>
            SQL Server 2022 <strong>(destino)</strong> no tiene conexión activa.
          </template>
          Verificá la configuración antes de migrar o sincronizar.
        </p>
      </div>
    </transition>

    <!-- ── Fila: Tablas + Docentes lado a lado ──────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">

      <!-- ── Tablas de la base de datos ─────────────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm h-full flex flex-col" aria-labelledby="section-tablas">
        <div class="bg-slate-900 px-6 py-4">
          <span id="section-tablas" class="text-white font-bold text-sm uppercase tracking-wide">
            Tablas de la Base de datos
          </span>
        </div>

        <div class="p-6 flex flex-col gap-4 flex-1">
          <p class="text-[13px] text-gray-500 leading-relaxed m-0">
            Espejo exacto del 2008 (TRUNCATE + INSERT). Se pueden volver a correr en cualquier
            momento, siempre reflejan el estado actual del origen.
          </p>

          <button
            type="button"
            :disabled="!canSync || catalogosBusy"
            :aria-busy="migratingCatalogosAll"
            @click="handleMigrarCatalogosTodos"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-[13px] font-medium transition-colors outline-none
                   bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-2 focus-visible:ring-emerald-400/50
                   disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
          >
            <DatabaseZap class="w-4 h-4 shrink-0" :class="migratingCatalogosAll ? 'animate-pulse' : ''" aria-hidden="true" />
            {{ migratingCatalogosAll ? 'Migrando tablas...' : 'Migrar todas las tablas' }}
          </button>

          <SyncResultBlock v-if="catalogosAllResult || catalogosAllError" :result="catalogosAllResult" :error="catalogosAllError" />

          <details class="group">
            <summary class="cursor-pointer select-none text-[12px] text-gray-500 hover:text-gray-900 transition-colors list-none flex items-center gap-1.5 w-fit">
              <ChevronRight class="w-3.5 h-3.5 transition-transform group-open:rotate-90" aria-hidden="true" />
              Migrar una tabla puntual
            </summary>

            <div class="flex flex-col gap-2 mt-3">
              <div
                v-for="tabla in TABLAS_CATALOGO"
                :key="tabla"
                class="flex flex-col gap-2 bg-gray-50 border border-gray-200 rounded-lg p-3"
              >
                <div class="flex items-center justify-between gap-3">
                  <span class="font-mono text-[12px] font-semibold text-gray-900 truncate">{{ tabla }}</span>
                  <button
                    type="button"
                    :disabled="!canSync || catalogosBusy"
                    :aria-busy="migratingCatalogoTabla === tabla"
                    @click="handleMigrarCatalogo(tabla)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-md shrink-0 text-[11px] font-medium transition-colors outline-none
                           bg-gray-100 text-gray-700 hover:bg-gray-200 focus-visible:ring-2 focus-visible:ring-emerald-400/50
                           disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    <DatabaseZap class="w-3.5 h-3.5 shrink-0" :class="migratingCatalogoTabla === tabla ? 'animate-pulse' : ''" aria-hidden="true" />
                    {{ migratingCatalogoTabla === tabla ? 'Migrando...' : 'Migrar' }}
                  </button>
                </div>
                <SyncResultBlock
                  v-if="catalogoTablaResults[tabla]"
                  :result="catalogoTablaResults[tabla].result"
                  :error="catalogoTablaResults[tabla].error"
                  compact
                />
              </div>
            </div>
          </details>
        </div>
      </section>

      <!-- ── DOCENTES (MERGE dedicado, sin DELETE) ────────────────────── -->
      <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm h-full flex flex-col" aria-labelledby="section-docentes">
        <div class="bg-slate-900 px-6 py-4">
          <span id="section-docentes" class="text-white font-bold text-sm uppercase tracking-wide">
            Docentes
          </span>
        </div>

        <div class="p-6 flex flex-col gap-4 flex-1">
          <p class="text-[13px] text-gray-500 leading-relaxed m-0">
            MERGE de solo INSERT + UPDATE (nunca DELETE). DOCENTES tiene tablas hijas con FK
            (ej. CLASIFICACION_DOCENTE), así que borrar registros desde acá podría romper la
            integridad referencial — para bajas hay que hacerlo aparte, revisando antes sus hijos.
          </p>

          <div class="flex flex-col gap-2 bg-gray-50 border border-gray-200 rounded-lg p-4 mt-auto">
            <div class="flex items-center justify-between gap-3">
              <div>
                <strong class="text-[13px] text-gray-900 font-medium">DOCENTES</strong>
                <p class="text-[13px] text-gray-500 leading-relaxed m-0">
                  MERGE completo (INSERT + UPDATE + DELETE) — espejo exacto del 2008.
                </p>
              </div>
              <button
                type="button"
                :disabled="!canSync || syncingDocentes"
                :aria-busy="syncingDocentes"
                @click="handleSyncDocentes"
                class="flex items-center gap-1.5 px-3 py-2 rounded-md shrink-0 text-[12px] font-medium transition-colors outline-none
                       bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                       disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
              >
                <RefreshCw class="w-3.5 h-3.5 shrink-0" :class="syncingDocentes ? 'animate-spin' : ''" aria-hidden="true" />
                {{ syncingDocentes ? 'Sincronizando...' : 'Sincronizar DOCENTES' }}
              </button>
            </div>
            <SyncResultBlock v-if="docentesResult || docentesError" :result="docentesResult" :error="docentesError" compact />
          </div>
        </div>
      </section>

    </div>

    <!-- ── Carga inicial ─────────────────────────────────────────────── -->
    <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm" aria-labelledby="section-carga-inicial">
      <div class="bg-slate-900 px-6 py-4">
        <span id="section-carga-inicial" class="text-white font-bold text-sm uppercase tracking-wide">
          Carga inicial (GRUPOS, HORARIOS2, KARDEX_EXT)
        </span>
      </div>

      <div class="p-6 flex flex-col gap-4">
        <p class="text-[13px] text-gray-500 leading-relaxed m-0">
          Copia todo el histórico desde el 2008, sin filtrar por semestre. Es de
          <strong>una sola ejecución por tabla</strong>: si la tabla ya existe en el 2022, el
          backend la rechaza. Para repetirla hay que borrarla manualmente en el 2022 primero
          (<code class="text-gray-700 bg-gray-100 px-1 rounded">DROP TABLE dbo.NOMBRE_TABLA</code>).
        </p>
        <p class="text-[12px] text-amber-600 leading-relaxed m-0 flex items-start gap-1.5">
          <AlertTriangle class="w-3.5 h-3.5 shrink-0 mt-0.5" aria-hidden="true" />
          KARDEX_EXT puede tardar bastante (puede tener años de notas). Recomendado: correr
          una tabla a la vez en vez de las 3 juntas.
        </p>

        <div class="flex flex-col gap-2">
          <div
            v-for="tabla in TABLAS_CARGA_INICIAL"
            :key="tabla"
            class="flex flex-col gap-2 bg-gray-50 border border-gray-200 rounded-lg p-3"
          >
            <div class="flex items-center justify-between gap-3">
              <span class="font-mono text-[12px] font-semibold text-gray-900 truncate">{{ tabla }}</span>
              <button
                type="button"
                :disabled="!canSync || cargaInicialBusy"
                :aria-busy="cargaInicialTablaActiva === tabla"
                @click="handleCargaInicialTabla(tabla)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md shrink-0 text-[11px] font-medium transition-colors outline-none
                       bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                       disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
              >
                <DatabaseZap class="w-3.5 h-3.5 shrink-0" :class="cargaInicialTablaActiva === tabla ? 'animate-pulse' : ''" aria-hidden="true" />
                {{ cargaInicialTablaActiva === tabla ? 'Cargando...' : 'Cargar' }}
              </button>
            </div>
            <SyncResultBlock
              v-if="cargaInicialTablaResults[tabla]"
              :result="cargaInicialTablaResults[tabla].result"
              :error="cargaInicialTablaResults[tabla].error"
              compact
            />
          </div>
        </div>

        <details class="group">
          <summary class="cursor-pointer select-none text-[12px] text-gray-500 hover:text-gray-900 transition-colors list-none flex items-center gap-1.5 w-fit">
            <ChevronRight class="w-3.5 h-3.5 transition-transform group-open:rotate-90" aria-hidden="true" />
            Cargar las 3 tablas de una sola vez
          </summary>
          <div class="flex flex-col gap-2 mt-3">
            <button
              type="button"
              :disabled="!canSync || cargaInicialBusy"
              :aria-busy="cargaInicialTodasBusy"
              @click="handleCargaInicialTodas"
              class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-[13px] font-medium transition-colors outline-none
                     bg-gray-100 text-gray-700 hover:bg-gray-200 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                     disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <DatabaseZap class="w-4 h-4 shrink-0" :class="cargaInicialTodasBusy ? 'animate-pulse' : ''" aria-hidden="true" />
              {{ cargaInicialTodasBusy ? 'Cargando...' : 'Ejecutar carga inicial completa' }}
            </button>
            <SyncResultBlock v-if="cargaInicialTodasResult || cargaInicialTodasError" :result="cargaInicialTodasResult" :error="cargaInicialTodasError" />
          </div>
        </details>
      </div>
    </section>

    <!-- ── Sincronización por semestre ──────────────────────────────────── -->
    <section class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm" aria-labelledby="section-semestre">
      <div class="bg-slate-900 px-6 py-4">
        <span id="section-semestre" class="text-white font-bold text-sm uppercase tracking-wide">
          Sincronización por semestre
        </span>
      </div>

      <div class="p-6 flex flex-col gap-4">
        <p class="text-[13px] text-gray-500 leading-relaxed m-0">
          Solo aplica una vez hecha la carga inicial. Idempotente: correrlo varias veces con el
          mismo año/periodo no genera duplicados — si ya está al día vas a ver "Sin cambios".
        </p>

        <SemestreSelector v-model:anio="anio" v-model:periodo="periodo" id-prefix="semestre" />

        <p v-if="!semestreValido" class="text-[11px] text-red-500 m-0">
          Ingresá un año y un periodo válidos antes de sincronizar.
        </p>

        <!-- GRUPOS (MERGE) -->
        <div class="flex flex-col gap-2 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <strong class="text-[13px] text-gray-900 font-medium">GRUPOS</strong>
              <p class="text-[11px] text-gray-500 m-0">MERGE (INSERT + UPDATE + DELETE) acotado a {{ anio || '—' }}-{{ periodo || '—' }}</p>
            </div>
            <button
              type="button"
              :disabled="!canSync || !semestreValido || semestreBusy"
              :aria-busy="syncingGrupos"
              @click="handleSyncGrupos"
              class="flex items-center gap-1.5 px-3 py-2 rounded-md shrink-0 text-[12px] font-medium transition-colors outline-none
                     bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                     disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
            >
              <RefreshCw class="w-3.5 h-3.5 shrink-0" :class="syncingGrupos ? 'animate-spin' : ''" aria-hidden="true" />
              {{ syncingGrupos ? 'Sincronizando...' : 'Sincronizar GRUPOS' }}
            </button>
          </div>
          <SyncResultBlock v-if="grupoResult || grupoError" :result="grupoResult" :error="grupoError" compact />
        </div>

        <!-- HORARIOS2 / KARDEX_EXT (DELETE + INSERT) -->
        <div class="flex flex-col gap-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between gap-3">
            <strong class="text-[13px] text-gray-900 font-medium">HORARIOS2 / KARDEX_EXT</strong>
          </div>

          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="tablasSemestreSeleccionadas"
                value="HORARIOS2"
                class="w-4 h-4 rounded border-gray-300 bg-white text-indigo-600 focus:ring-indigo-400/50 focus:ring-offset-0 cursor-pointer"
              />
              <span class="text-[12px] text-gray-700 font-mono">HORARIOS2</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="tablasSemestreSeleccionadas"
                value="KARDEX_EXT"
                class="w-4 h-4 rounded border-gray-300 bg-white text-indigo-600 focus:ring-indigo-400/50 focus:ring-offset-0 cursor-pointer"
              />
              <span class="text-[12px] text-gray-700 font-mono">KARDEX_EXT</span>
            </label>
          </div>

          <button
            type="button"
            :disabled="!canSync || !semestreValido || semestreBusy || tablasSemestreSeleccionadas.length === 0"
            :aria-busy="syncingSemestre"
            @click="handleSyncSemestreTablas"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-[13px] font-medium transition-colors outline-none
                   bg-gray-100 text-gray-700 hover:bg-gray-200 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                   disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <RefreshCw class="w-4 h-4 shrink-0" :class="syncingSemestre ? 'animate-spin' : ''" aria-hidden="true" />
            {{ syncingSemestre ? 'Sincronizando...' : `Sincronizar ${anio || '—'}-${periodo || '—'}` }}
          </button>

          <SyncResultBlock v-if="semestreResult || semestreError" :result="semestreResult" :error="semestreError" compact />
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Database, DatabaseZap, AlertTriangle, RefreshCw, ChevronRight } from 'lucide-vue-next'
import { databaseService, TABLAS_CATALOGO, TABLAS_CARGA_INICIAL } from '../services/databaseService'
import SyncResultBlock from '../components/SyncResultBlock.vue'
import SemestreSelector from '../components/SemestreSelector.vue'

function mensajeError(e, fallback) {
  return e?.response?.data?.message ?? fallback
}

function conTimestamp(data) {
  return { ...data, timestamp: Date.now() }
}

// ── Estado de conexiones ───────────────────────────────────────────────
const defaultServer = () => ({ connected: false, host: '—', database: '—' })
const status = ref({ sqlserver_2022: defaultServer(), sqlserver_2008: defaultServer() })
const loadingStatus = ref(false)

const canSync = computed(() => status.value.sqlserver_2008.connected && status.value.sqlserver_2022.connected)

async function loadStatus() {
  loadingStatus.value = true
  try {
    const data = await databaseService.getStatus()
    status.value = {
      sqlserver_2022: {
        connected: data?.sqlserver_2022?.connected ?? false,
        host: data?.sqlserver_2022?.host ?? '—',
        database: data?.sqlserver_2022?.database ?? '—',
      },
      sqlserver_2008: {
        connected: data?.sqlserver_2008?.connected ?? false,
        host: data?.sqlserver_2008?.host ?? '—',
        database: data?.sqlserver_2008?.database ?? '—',
      },
    }
  } catch (e) {
    console.error('Error al obtener estado de BD:', e)
  } finally {
    loadingStatus.value = false
  }
}

// ── Tablas (antes "catálogos") ────────────────────────────────────────
const migratingCatalogosAll = ref(false)
const catalogosAllResult = ref(null)
const catalogosAllError = ref(null)
const migratingCatalogoTabla = ref(null)
const catalogoTablaResults = reactive({})

const catalogosBusy = computed(() => migratingCatalogosAll.value || migratingCatalogoTabla.value !== null)

async function handleMigrarCatalogosTodos() {
  if (migratingCatalogosAll.value) return
  migratingCatalogosAll.value = true
  catalogosAllResult.value = null
  catalogosAllError.value = null
  try {
    const data = await databaseService.migrarCatalogos()
    catalogosAllResult.value = conTimestamp({
      label: `Tablas migradas — ${data.resumen.exitosas}/${data.resumen.total}`,
      detalle: data.detalle,
    })
  } catch (e) {
    catalogosAllError.value = mensajeError(e, 'Error al migrar las tablas. Intenta de nuevo.')
  } finally {
    migratingCatalogosAll.value = false
  }
}

async function handleMigrarCatalogo(tabla) {
  if (migratingCatalogoTabla.value) return
  migratingCatalogoTabla.value = tabla
  catalogoTablaResults[tabla] = null
  try {
    const data = await databaseService.migrarCatalogo(tabla)
    const item = data.detalle?.[0]
    catalogoTablaResults[tabla] = {
      result: conTimestamp({ single: item ?? { tabla, success: data.success, message: 'Migración completada' } }),
      error: null,
    }
  } catch (e) {
    catalogoTablaResults[tabla] = { result: null, error: mensajeError(e, `Error al migrar ${tabla}.`) }
  } finally {
    migratingCatalogoTabla.value = null
  }
}

// ── DOCENTES (MERGE dedicado, sin DELETE) ───────────────────────────────
const syncingDocentes = ref(false)
const docentesResult = ref(null)
const docentesError = ref(null)

async function handleSyncDocentes() {
  if (syncingDocentes.value) return
  syncingDocentes.value = true
  docentesResult.value = null
  docentesError.value = null
  try {
    const data = await databaseService.migrarDocentes()
    const item = data.detalle?.[0]
    docentesResult.value = conTimestamp({
      single: item ?? { tabla: 'DOCENTES', success: data.success, message: 'Sincronización completada' },
    })
  } catch (e) {
    docentesError.value = mensajeError(e, 'Error al sincronizar DOCENTES. Intenta de nuevo.')
  } finally {
    syncingDocentes.value = false
  }
}

// ── Carga inicial ──────────────────────────────────────────────────────
const cargaInicialTablaActiva = ref(null)
const cargaInicialTablaResults = reactive({})
const cargaInicialTodasBusy = ref(false)
const cargaInicialTodasResult = ref(null)
const cargaInicialTodasError = ref(null)

const cargaInicialBusy = computed(() => cargaInicialTablaActiva.value !== null || cargaInicialTodasBusy.value)

async function handleCargaInicialTabla(tabla) {
  if (cargaInicialTablaActiva.value) return
  cargaInicialTablaActiva.value = tabla
  cargaInicialTablaResults[tabla] = null
  try {
    const data = await databaseService.cargaInicial([tabla])
    const item = data.detalle?.[0]
    cargaInicialTablaResults[tabla] = {
      result: conTimestamp({ single: item ?? { tabla, success: data.success, message: 'Carga completada' } }),
      error: null,
    }
  } catch (e) {
    cargaInicialTablaResults[tabla] = { result: null, error: mensajeError(e, `Error al cargar ${tabla}.`) }
  } finally {
    cargaInicialTablaActiva.value = null
  }
}

async function handleCargaInicialTodas() {
  if (cargaInicialTodasBusy.value) return
  cargaInicialTodasBusy.value = true
  cargaInicialTodasResult.value = null
  cargaInicialTodasError.value = null
  try {
    const data = await databaseService.cargaInicial(TABLAS_CARGA_INICIAL)
    cargaInicialTodasResult.value = conTimestamp({
      label: `Carga inicial completada — ${data.resumen.exitosas}/${data.resumen.total}`,
      detalle: data.detalle,
    })
  } catch (e) {
    cargaInicialTodasError.value = mensajeError(e, 'Error en la carga inicial. Intenta de nuevo.')
  } finally {
    cargaInicialTodasBusy.value = false
  }
}

// ── Sincronización por semestre ───────────────────────────────────────
const currentYear = new Date().getFullYear().toString()
const anio = ref(currentYear)
const periodo = ref('1')
const semestreValido = computed(() =>
  /^\d{4}$/.test(anio.value) && ['1', '2', '3', '4'].includes(periodo.value)
)
const syncingGrupos = ref(false)
const grupoResult = ref(null)
const grupoError = ref(null)

const syncingSemestre = ref(false)
const semestreResult = ref(null)
const semestreError = ref(null)
const tablasSemestreSeleccionadas = ref(['HORARIOS2', 'KARDEX_EXT'])

const semestreBusy = computed(() => syncingGrupos.value || syncingSemestre.value)

async function handleSyncGrupos() {
  if (syncingGrupos.value || !semestreValido.value) return
  syncingGrupos.value = true
  grupoResult.value = null
  grupoError.value = null
  try {
    const data = await databaseService.migrarGrupos(anio.value, periodo.value)
    const item = data.detalle?.[0]
    grupoResult.value = conTimestamp({
      single: item ?? { tabla: 'GRUPOS', success: data.success, message: 'Sincronización completada' },
    })
  } catch (e) {
    grupoError.value = mensajeError(e, 'Error al sincronizar GRUPOS. Intenta de nuevo.')
  } finally {
    syncingGrupos.value = false
  }
}

async function handleSyncSemestreTablas() {
  if (syncingSemestre.value || !semestreValido.value || tablasSemestreSeleccionadas.value.length === 0) return
  syncingSemestre.value = true
  semestreResult.value = null
  semestreError.value = null
  try {
    const data = await databaseService.migrarSemestre(anio.value, periodo.value, tablasSemestreSeleccionadas.value)
    semestreResult.value = conTimestamp({
      label: `Sincronizado ${anio.value}-${periodo.value} — ${data.resumen.exitosas}/${data.resumen.total}`,
      detalle: data.detalle,
    })
  } catch (e) {
    semestreError.value = mensajeError(e, 'Error al sincronizar. Intenta de nuevo.')
  } finally {
    syncingSemestre.value = false
  }
}

onMounted(() => {
  loadStatus()
})
</script>