<template>
  <div class="flex flex-col gap-6 p-1 max-w-3xl">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-0.5">
        Configuración de bases de datos
      </h1>
      <p class="text-sm text-gray-500">
        Estado de conexión y herramientas de migración
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

        <!-- Botón actualizar -->
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
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-3.5 h-3.5 transition-transform"
            :class="loadingStatus ? 'animate-spin' : ''"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0
                 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003
                 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Actualizar
        </button>
      </div>

      <!-- Grid 2 columnas en sm+ -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        <!-- SQL Server 2022 -->
        <div class="flex flex-col gap-3 bg-slate-900/50 border border-slate-700/60 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                        font-extrabold text-xs shrink-0 bg-blue-500/15 text-blue-300">
              22
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

        <!-- SQL Server 2008 -->
        <div class="flex flex-col gap-3 bg-slate-900/50 border border-slate-700/60 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                        font-extrabold text-xs shrink-0 bg-pink-500/15 text-pink-300">
              08
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

      </div>
    </section>

    <!-- ── Migración ─────────────────────────────────────────────────── -->
    <section
      class="bg-slate-800 border border-slate-700 rounded-xl p-6 flex flex-col gap-4"
      aria-labelledby="section-migration"
    >
      <span
        id="section-migration"
        class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest"
      >
        Migración de datos
      </span>

      <!-- Alerta contextual según qué servidor falla -->
      <transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
      >
        <div
          v-if="!status.sqlserver_2008.connected || !status.sqlserver_2022.connected"
          role="alert"
          class="flex items-start gap-2 p-3 rounded-lg
                 bg-amber-500/10 border border-amber-500/20
                 text-amber-400 text-[12px]"
        >
          <span class="shrink-0" aria-hidden="true">⚠</span>
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
            Verificá la configuración antes de iniciar la migración.
          </p>
        </div>
      </transition>

      <!-- Descripción -->
      <p class="text-[13px] text-slate-400 leading-relaxed m-0">
        Transfiere todas las tablas y registros desde SQL Server 2008
        hacia SQL Server 2022 sin eliminar datos del origen.
      </p>

      <!-- Flujo visual: grid 3 columnas -->
      <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
        <div class="flex flex-col gap-1 bg-slate-900/60 border border-slate-700 rounded-lg px-4 py-3">
          <span class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Origen</span>
          <span class="text-[13px] font-medium text-slate-300">🗄 SQL Server 2008</span>
        </div>

        <span class="text-slate-500 text-lg text-center leading-none" aria-hidden="true">→</span>

        <div class="flex flex-col gap-1 bg-slate-900/60 border border-slate-700 rounded-lg px-4 py-3">
          <span class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Destino</span>
          <span class="text-[13px] font-medium text-slate-300">🗄 SQL Server 2022</span>
        </div>
      </div>

      <!-- Botón -->
      <button
        type="button"
        :disabled="!status.sqlserver_2008.connected || !status.sqlserver_2022.connected || migrating"
        :aria-busy="migrating"
        @click="handleMigrate"
        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg
               text-[13px] font-medium transition-colors outline-none
               bg-indigo-600 text-white hover:bg-indigo-700
               focus-visible:ring-2 focus-visible:ring-indigo-400/50
               disabled:bg-slate-700/60 disabled:text-slate-500
               disabled:cursor-not-allowed disabled:active:scale-100"
      >
        <svg
          v-if="migrating"
          class="w-4 h-4 animate-spin shrink-0"
          xmlns="http://www.w3.org/2000/svg"
          fill="none" viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
        <span v-else aria-hidden="true">↩</span>
        {{ migrating ? 'Migrando datos...' : 'Migrar SQL Server 2008 → SQL Server 2022' }}
      </button>

      <p class="text-[11px] text-slate-500 text-center m-0">
        El proceso puede tardar varios minutos dependiendo del volumen de datos.
      </p>

      <!-- Resultado -->
      <transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
      >
        <div
          v-if="migrationResult"
          role="status"
          aria-live="polite"
          class="flex flex-col gap-2 p-4 rounded-lg
                 bg-emerald-500/10 border border-emerald-500/20 text-[13px]"
        >
          <p class="font-medium m-0 text-emerald-400">
            ✓ Migración completada —
            {{ migrationResult.resumen.exitosas }}/{{ migrationResult.resumen.total }} tablas migradas
          </p>
          <ul class="flex flex-col gap-1 mt-1 list-none p-0 m-0">
            <li
              v-for="item in migrationResult.detalle"
              :key="item.tabla"
              class="flex items-center gap-2 text-[11px]"
              :class="item.success ? 'text-emerald-400' : 'text-red-400'"
            >
              <span aria-hidden="true">{{ item.success ? '✓' : '✗' }}</span>
              <span class="font-mono font-semibold">{{ item.tabla }}</span>
              <span class="text-slate-500">— {{ item.message }}</span>
            </li>
          </ul>
        </div>
      </transition>

      <!-- Error migración -->
      <transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
      >
        <div
          v-if="migrationError"
          role="alert"
          aria-live="assertive"
          class="p-3 rounded-lg bg-red-500/10 border border-red-500/20
                 text-red-400 text-[12px]"
        >
          <span aria-hidden="true">✗</span> {{ migrationError }}
        </div>
      </transition>

    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { databaseService } from '../services/databaseService'

// ── Estado inicial ────────────────────────────────────────────────────────────
const defaultServer = () => ({ connected: false, host: '—', database: '—' })

const status = ref({
  sqlserver_2022: defaultServer(),
  sqlserver_2008: defaultServer(),
})

const loadingStatus   = ref(false)
const migrating       = ref(false)
const migrationResult = ref(null)
const migrationError  = ref(null)

// ── Cargar estado ─────────────────────────────────────────────────────────────
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

// ── Migración ─────────────────────────────────────────────────────────────────
async function handleMigrate() {
  if (migrating.value) return
  migrating.value       = true
  migrationResult.value = null
  migrationError.value  = null

  try {
    migrationResult.value = await databaseService.migrateAll()
  } catch (e) {
    migrationError.value =
      e?.response?.data?.message ?? 'Error durante la migración. Intenta de nuevo.'
  } finally {
    migrating.value = false
  }
}

onMounted(loadStatus)
</script>