<template>
  <div class="flex flex-col gap-6 p-1 max-w-3xl">

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
    <section
      class="bg-slate-800 border border-slate-700 rounded-xl p-6 flex flex-col gap-4"
      aria-labelledby="section-status"
    >
      <div class="flex items-center justify-between">
        <span
          id="section-status"
          class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest"
        >
          Estado de conexiones
        </span>

        <button
          type="button"
          :disabled="loadingStatus"
          :aria-busy="loadingStatus"
          aria-label="Actualizar estado de conexiones"
          @click="loadStatus"
          class="flex items-center gap-1.5 text-[11px] text-slate-400
                 hover:text-slate-200 transition-colors outline-none
                 focus-visible:ring-1 focus-visible:ring-slate-400 rounded
                 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <RefreshCw
            class="w-3.5 h-3.5 transition-transform"
            :class="loadingStatus ? 'animate-spin' : ''"
            aria-hidden="true"
          />
          Actualizar
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        <div class="flex flex-col gap-3 bg-slate-900/50 border border-slate-700/60 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg flex flex-col items-center justify-center gap-0.5
                        shrink-0 bg-pink-500/15 text-pink-300"
                 aria-hidden="true">
              <Database class="w-4 h-4" />
              <span class="text-[9px] font-bold leading-none tracking-tight">2008</span>
            </div>
            <div class="flex flex-col flex-1 min-w-0">
              <strong class="text-[13px] text-slate-200 font-medium">SQL Server 2008</strong>
              <small class="text-[11px] text-slate-500 font-mono truncate">
                {{ status.sqlserver_2008.host }} · {{ status.sqlserver_2008.database }}
              </small>
            </div>
          </div>
          <span
            class="self-start inline-flex items-center gap-1.5 px-3 py-1
                   rounded-full text-[11px] font-semibold"
            :class="status.sqlserver_2008.connected
              ? 'bg-emerald-500/15 text-emerald-400'
              : 'bg-red-500/15 text-red-400'"
          >
            <i class="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
            {{ status.sqlserver_2008.connected ? 'Conectado' : 'Sin conexión' }}
          </span>
        </div>

        <div class="flex flex-col gap-3 bg-slate-900/50 border border-slate-700/60 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg flex flex-col items-center justify-center gap-0.5
                        shrink-0 bg-blue-500/15 text-blue-300"
                 aria-hidden="true">
              <Database class="w-4 h-4" />
              <span class="text-[9px] font-bold leading-none tracking-tight">2022</span>
            </div>
            <div class="flex flex-col flex-1 min-w-0">
              <strong class="text-[13px] text-slate-200 font-medium">SQL Server 2022</strong>
              <small class="text-[11px] text-slate-500 font-mono truncate">
                {{ status.sqlserver_2022.host }} · {{ status.sqlserver_2022.database }}
              </small>
            </div>
          </div>
          <span
            class="self-start inline-flex items-center gap-1.5 px-3 py-1
                   rounded-full text-[11px] font-semibold"
            :class="status.sqlserver_2022.connected
              ? 'bg-emerald-500/15 text-emerald-400'
              : 'bg-red-500/15 text-red-400'"
          >
            <i class="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true" />
            {{ status.sqlserver_2022.connected ? 'Conectado' : 'Sin conexión' }}
          </span>
        </div>

      </div>
    </section>

    <!-- ── Alerta de conexión, compartida por todas las secciones de abajo ── -->
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-200"
      leave-to-class="opacity-0"
    >
      <div
        v-if="!canSync"
        role="alert"
        class="flex items-start gap-2 p-3 rounded-lg
               bg-amber-500/10 border border-amber-500/20
               text-amber-400 text-[12px]"
      >
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

    <!-- ── Migración inicial (carga completa) ──────────────────────────── -->
    <section
      class="bg-slate-800 border border-slate-700 rounded-xl p-6 flex flex-col gap-4"
      aria-labelledby="section-migrate"
    >
      <span
        id="section-migrate"
        class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest"
      >
        Migración inicial
      </span>

      <p class="text-[13px] text-slate-400 leading-relaxed m-0">
        Crea cada tabla en el 2022 (si no existe todavía) y trae <strong>todos</strong> los
        datos del 2008. Solo tiene efecto la primera vez por tabla: si ya existe en el 2022,
        no se toca — usá la sincronización de abajo para traer los cambios nuevos.
      </p>

      <!-- Migrar todas de una -->
      <button
        type="button"
        :disabled="!canSync || migratingAll || migratingTable !== null"
        :aria-busy="migratingAll"
        @click="handleMigrateAll"
        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg
               text-[13px] font-medium transition-colors outline-none
               bg-emerald-600 text-white hover:bg-emerald-700
               focus-visible:ring-2 focus-visible:ring-emerald-400/50
               disabled:bg-slate-700/60 disabled:text-slate-500
               disabled:cursor-not-allowed"
      >
        <DatabaseZap class="w-4 h-4 shrink-0" :class="migratingAll ? 'animate-pulse' : ''" aria-hidden="true" />
        {{ migratingAll ? 'Migrando todas...' : 'Migrar todas las tablas (carga inicial)' }}
      </button>

      <!-- Resultado consolidado de "migrar todas" -->
      <SyncResultBlock
        v-if="migrateAllResult || migrateAllError"
        :result="migrateAllResult"
        :error="migrateAllError"
        is-multi
      />

      <!-- Migración individual -->
      <details class="group">
        <summary
          class="cursor-pointer select-none text-[12px] text-slate-400
                 hover:text-slate-200 transition-colors list-none flex items-center gap-1.5 w-fit"
        >
          <ChevronRight class="w-3.5 h-3.5 transition-transform group-open:rotate-90" aria-hidden="true" />
          Migrar una tabla puntual
        </summary>

        <div class="flex flex-col gap-2 mt-3">
          <div
            v-for="tabla in tablasMigrables"
            :key="tabla"
            class="flex flex-col gap-2 bg-slate-900/50 border border-slate-700/60 rounded-lg p-3"
          >
            <div class="flex items-center justify-between gap-3">
              <span class="font-mono text-[12px] font-semibold text-slate-200 truncate">
                {{ tabla }}
              </span>
              <button
                type="button"
                :disabled="!canSync || migratingAll || migratingTable !== null"
                :aria-busy="migratingTable === tabla"
                @click="handleMigrateTable(tabla)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md shrink-0
                       text-[11px] font-medium transition-colors outline-none
                       bg-slate-700 text-slate-200 hover:bg-slate-600
                       focus-visible:ring-2 focus-visible:ring-emerald-400/50
                       disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <DatabaseZap
                  class="w-3.5 h-3.5 shrink-0"
                  :class="migratingTable === tabla ? 'animate-pulse' : ''"
                  aria-hidden="true"
                />
                {{ migratingTable === tabla ? 'Migrando...' : 'Migrar' }}
              </button>
            </div>

            <SyncResultBlock
              v-if="migrateTableResults[tabla]"
              :result="migrateTableResults[tabla].result"
              :error="migrateTableResults[tabla].error"
              compact
            />
          </div>
        </div>
      </details>
    </section>

    <!-- ── Sincronización de gestión (GRUPOS) ──────────────────────────── -->
    <section
      class="bg-slate-800 border border-slate-700 rounded-xl p-6 flex flex-col gap-4"
      aria-labelledby="section-sync"
    >
      <span
        id="section-sync"
        class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest"
      >
        Sincronización de gestión (GRUPOS)
      </span>

      <p class="text-[13px] text-slate-400 leading-relaxed m-0">
        Trae los grupos nuevos o modificados de la gestión actual desde el 2008.
        No borra nada en destino salvo que actives "Eliminar obsoletos".
      </p>

      <label class="flex items-center gap-2.5 cursor-pointer select-none w-fit">
        <input
          type="checkbox"
          v-model="eliminarObsoletos"
          class="w-4 h-4 rounded border-slate-600 bg-slate-900 text-indigo-500
                 focus:ring-indigo-400/50 focus:ring-offset-0 cursor-pointer"
        />
        <span class="text-[12px] text-slate-300">
          Eliminar en destino las filas que ya no existen en origen (aplica a toda la sincronización)
        </span>
      </label>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button
          type="button"
          :disabled="!canSync || syncingGrupos || syncingGestion"
          :aria-busy="syncingGrupos"
          @click="handleSyncGrupos"
          class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg
                 text-[13px] font-medium transition-colors outline-none
                 bg-slate-700 text-slate-200 hover:bg-slate-600
                 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <RefreshCw class="w-4 h-4 shrink-0" :class="syncingGrupos ? 'animate-spin' : ''" aria-hidden="true" />
          {{ syncingGrupos ? 'Sincronizando...' : 'Sincronizar GRUPOS' }}
        </button>

        <button
          type="button"
          :disabled="!canSync || syncingGrupos || syncingGestion"
          :aria-busy="syncingGestion"
          @click="handleSyncGestion"
          class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg
                 text-[13px] font-medium transition-colors outline-none
                 bg-indigo-600 text-white hover:bg-indigo-700
                 focus-visible:ring-2 focus-visible:ring-indigo-400/50
                 disabled:bg-slate-700/60 disabled:text-slate-500
                 disabled:cursor-not-allowed"
        >
          <RefreshCw class="w-4 h-4 shrink-0" :class="syncingGestion ? 'animate-spin' : ''" aria-hidden="true" />
          {{ syncingGestion ? 'Sincronizando...' : 'Sincronizar toda la gestión' }}
        </button>
      </div>

      <SyncResultBlock :result="grupoResult" :error="grupoError" :eliminar-obsoletos="eliminarObsoletos" />
    </section>

 

  </div>
</template>

<script setup>
import { ref, computed, onMounted, h, defineComponent } from 'vue'
import {
  Database,
  DatabaseZap,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  RefreshCw,
  ChevronRight,
} from 'lucide-vue-next'
import { databaseService } from '../services/databaseService'

// ── Tablas elegibles para migración inicial (incluye GRUPOS, a diferencia
//    del listado de sync genérico que la excluye porque tiene su propio flujo) ──
const TABLAS_MIGRABLES = [
  'BIOGRAFICOS',
  'BIOGRAFICOS_EXT',
  'DOCENTES',
  'DOCENTES_2',
  'DOCENTES_TELEFONO',
  'GRUPOS',
  'GRUPOS_COMPARTIDOS',
  'HORARIOS2',
  'KARDEX_EXT',
  'MATERIAS',
  'NROINSMATGRPNE',
  'PLANES',
]
const tablasMigrables = ref(TABLAS_MIGRABLES)

// ── Estado inicial ───────────────────────────────────────────────────────
const defaultServer = () => ({ connected: false, host: '—', database: '—' })

const status = ref({
  sqlserver_2022: defaultServer(),
  sqlserver_2008: defaultServer(),
})

const loadingStatus = ref(false)

const canSync = computed(() =>
  status.value.sqlserver_2008.connected && status.value.sqlserver_2022.connected
)

async function loadStatus() {
  loadingStatus.value = true
  try {
    const data = await databaseService.getStatus()
    status.value = {
      sqlserver_2022: {
        connected: data?.sqlserver_2022?.connected ?? false,
        host:      data?.sqlserver_2022?.host      ?? '—',
        database:  data?.sqlserver_2022?.database  ?? '—',
      },
      sqlserver_2008: {
        connected: data?.sqlserver_2008?.connected ?? false,
        host:      data?.sqlserver_2008?.host      ?? '—',
        database:  data?.sqlserver_2008?.database  ?? '—',
      },
    }
  } catch (e) {
    console.error('Error al obtener estado de BD:', e)
  } finally {
    loadingStatus.value = false
  }
}

// ── Toggle global "eliminar obsoletos" (solo aplica a sincronización) ────
const eliminarObsoletos = ref(false)

// ── Migración inicial ─────────────────────────────────────────────────────
const migratingAll        = ref(false)
const migrateAllResult    = ref(null)
const migrateAllError     = ref(null)
const migratingTable      = ref(null)   // nombre de la tabla en migración, o null
const migrateTableResults = ref({})     // { [tabla]: { result, error } }

async function handleMigrateAll() {
  if (migratingAll.value) return
  migratingAll.value = true
  migrateAllResult.value = null
  migrateAllError.value  = null

  try {
    const data = await databaseService.migrateAll()
    migrateAllResult.value = {
      label: `Migración completa — ${data.resumen.exitosas}/${data.resumen.total} tablas`,
      detalle: data.detalle,
    }
    
  } catch (e) {
    migrateAllError.value = e?.response?.data?.message ?? 'Error al migrar las tablas. Intenta de nuevo.'
  } finally {
    migratingAll.value = false
  }
}



// ── Sincronización de gestión (GRUPOS) ───────────────────────────────────
const syncingGrupos  = ref(false)
const syncingGestion = ref(false)
const grupoResult    = ref(null)
const grupoError     = ref(null)

async function handleSyncGrupos() {
  if (syncingGrupos.value) return
  syncingGrupos.value = true
  grupoResult.value = null
  grupoError.value  = null

  try {
    const data = await databaseService.syncGrupos(eliminarObsoletos.value)
    grupoResult.value = { label: data.message ?? 'Sincronización de GRUPOS completada', single: data }
  } catch (e) {
    grupoError.value = e?.response?.data?.message ?? 'Error al sincronizar GRUPOS. Intenta de nuevo.'
  } finally {
    syncingGrupos.value = false
  }
}

async function handleSyncGestion() {
  if (syncingGestion.value) return
  syncingGestion.value = true
  grupoResult.value = null
  grupoError.value  = null

  try {
    const data = await databaseService.syncGestion(eliminarObsoletos.value)
    grupoResult.value = {
      label: `Gestión sincronizada — ${data.resumen.exitosas}/${data.resumen.total} tablas`,
      detalle: data.detalle,
    }
  } catch (e) {
    grupoError.value = e?.response?.data?.message ?? 'Error al sincronizar la gestión. Intenta de nuevo.'
  } finally {
    syncingGestion.value = false
  }
}




async function handleSyncTable(tabla) {
  if (syncingTable.value) return
  syncingTable.value = tabla
  tableResults.value = { ...tableResults.value, [tabla]: null }

  try {
    const data = await databaseService.syncTable(tabla, eliminarObsoletos.value)
    tableResults.value = {
      ...tableResults.value,
      [tabla]: { result: { label: data.message, single: data }, error: null },
    }
  } catch (e) {
    tableResults.value = {
      ...tableResults.value,
      [tabla]: { result: null, error: e?.response?.data?.message ?? `Error al sincronizar ${tabla}.` },
    }
  } finally {
    syncingTable.value = null
  }
}



// ── Bloque de resultado reutilizable (inline, sin archivo aparte) ────────
const SyncResultBlock = defineComponent({
  props: {
    result: { type: Object, default: null },
    error: { type: String, default: null },
    eliminarObsoletos: { type: Boolean, default: false },
    isMulti: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
  },
  setup(props) {
    return () => {
      if (props.error) {
        return h('div', {
          role: 'alert',
          'aria-live': 'assertive',
          class: 'flex items-center gap-2 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-[12px]',
        }, [
          h(XCircle, { class: 'w-4 h-4 shrink-0' }),
          h('span', props.error),
        ])
      }

      if (!props.result) return null

      const detalleSingle = (item) => {
        if (!item) return null
        const nodes = [
          h('div', {
            class: ['flex items-center gap-2 text-[11px]', item.success ? 'text-emerald-400' : 'text-red-400'],
          }, [
            item.success ? h(CheckCircle2, { class: 'w-3.5 h-3.5 shrink-0' }) : h(XCircle, { class: 'w-3.5 h-3.5 shrink-0' }),
            h('span', { class: 'font-mono font-semibold' }, item.tabla),
            h('span', { class: 'text-slate-500' }, `— ${item.message}`),
          ]),
        ]

        if (item.detalle) {
          nodes.push(h('div', { class: 'flex gap-3 text-[11px] text-slate-400 ml-5' }, [
            h('span', ['Insertados: ', h('strong', { class: 'text-slate-200' }, item.detalle.INSERT ?? 0)]),
            h('span', ['Actualizados: ', h('strong', { class: 'text-slate-200' }, item.detalle.UPDATE ?? 0)]),
            props.eliminarObsoletos
              ? h('span', ['Eliminados: ', h('strong', { class: 'text-slate-200' }, item.detalle.DELETE ?? 0)])
              : null,
          ].filter(Boolean)))
        }

        if (item.cambios && item.cambios.length) {
          nodes.push(h('ul', { class: 'flex flex-col gap-0.5 mt-1 list-none p-0 m-0 max-h-40 overflow-y-auto' },
            item.cambios.slice(0, 50).map((c, i) =>
              h('li', { key: i, class: 'flex items-center gap-2 text-[10px] text-slate-400 ml-5' }, [
                h('span', {
                  class: [
                    'px-1.5 py-0.5 rounded text-[9px] font-bold uppercase shrink-0',
                    c.accion === 'INSERT' ? 'bg-emerald-500/15 text-emerald-400'
                      : c.accion === 'UPDATE' ? 'bg-amber-500/15 text-amber-400'
                      : 'bg-red-500/15 text-red-400',
                  ],
                }, c.accion),
                h('span', { class: 'font-mono truncate' }, Object.values(c.llave).join(' / ')),
              ])
            )
          ))
          if (item.cambios.length > 50) {
            nodes.push(h('p', { class: 'text-[10px] text-slate-500 ml-5 m-0' }, `+ ${item.cambios.length - 50} cambios más`))
          }
        }

        return nodes
      }

      const body = []

      if (props.result.single) {
        body.push(...(detalleSingle(props.result.single) || []))
      } else if (props.result.detalle) {
        body.push(h('ul', { class: 'flex flex-col gap-2 mt-1 list-none p-0 m-0' },
          props.result.detalle.map((item) =>
            h('li', { key: item.tabla, class: 'flex flex-col gap-1' }, detalleSingle(item))
          )
        ))
      }

      return h('div', {
        role: 'status',
        'aria-live': 'polite',
        class: ['flex flex-col gap-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[13px]', props.compact ? 'p-3' : 'p-4'],
      }, [
        h('p', { class: 'font-medium m-0 text-emerald-400 flex items-center gap-2' }, [
          h(CheckCircle2, { class: 'w-4 h-4 shrink-0' }),
          props.result.label,
        ]),
        ...body,
      ])
    }
  },
})

onMounted(() => {
  loadStatus()
  
})
</script>