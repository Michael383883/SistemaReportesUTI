<template>
  <div class="flex flex-col gap-5 p-8 max-w-2xl">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-0.5">
        Configuración de bases de datos
      </h1>
      <p class="text-sm text-gray-500">
        Estado de conexión y herramientas de migración
      </p>
    </div>

    <!-- Estado de conexiones -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col gap-4">
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">
        Estado de conexiones
      </span>

      <!-- SQL SERVER 2022 -->
      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold text-xs shrink-0 bg-blue-100 text-blue-700">
          22
        </div>
        <div class="flex flex-col flex-1 gap-0.5">
          <strong class="text-sm text-gray-900">SQL Server 2022</strong>
          <small class="text-xs text-gray-400 font-mono">
            {{ status.sqlserver_2022?.host }} · {{ status.sqlserver_2022?.database }}
          </small>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap"
          :class="status.sqlserver_2022?.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
        >
          <i class="inline-block w-1.5 h-1.5 rounded-full bg-current"></i>
          {{ status.sqlserver_2022?.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>

      <hr class="border-gray-100" />

      <!-- SQL SERVER 2008 -->
      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold text-xs shrink-0 bg-pink-100 text-pink-700">
          08
        </div>
        <div class="flex flex-col flex-1 gap-0.5">
          <strong class="text-sm text-gray-900">SQL Server 2008</strong>
          <small class="text-xs text-gray-400 font-mono">
            {{ status.sqlserver_2008?.host }} · {{ status.sqlserver_2008?.database }}
          </small>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap"
          :class="status.sqlserver_2008?.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
        >
          <i class="inline-block w-1.5 h-1.5 rounded-full bg-current"></i>
          {{ status.sqlserver_2008?.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>
    </div>

    <!-- Migración -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col gap-4">
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">
        Migración de datos
      </span>

      <!-- ALERTA sin conexión -->
      <div
        v-if="!status.sqlserver_2008?.connected"
        class="flex items-start gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm"
      >
        <span>⚠</span>
        <p class="m-0">
          SQL Server 2008 no tiene conexión activa.
          Verificá la configuración antes de iniciar la migración.
        </p>
      </div>

      <!-- DESCRIPCIÓN -->
      <p class="text-sm text-gray-500 leading-relaxed">
        Transfiere todas las tablas y registros desde SQL Server 2008
        hacia SQL Server 2022 sin eliminar datos del origen.
      </p>

      <!-- FLUJO -->
      <div class="flex items-center gap-3">
        <div class="flex flex-1 items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-800">
          🗄 SQL Server 2008
          <em class="font-normal text-gray-400 text-xs ml-1 not-italic">origen</em>
        </div>
        <span class="text-gray-400 text-lg">→</span>
        <div class="flex flex-1 items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-800">
          🗄 SQL Server 2022
          <em class="font-normal text-gray-400 text-xs ml-1 not-italic">destino</em>
        </div>
      </div>

      <!-- BOTÓN -->
      <button
        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
        :disabled="!status.sqlserver_2008?.connected || !status.sqlserver_2022?.connected || migrating"
        @click="handleMigrate"
      >
        <span v-if="migrating" class="animate-spin inline-block">⏳</span>
        <span v-else>↩</span>
        {{ migrating ? 'Migrando datos...' : 'Migrar SQL Server 2008 → SQL Server 2022' }}
      </button>

      <p class="text-xs text-gray-400 text-center">
        El proceso puede tardar varios minutos dependiendo del volumen de datos.
      </p>

      <!-- RESULTADO -->
      <div
        v-if="migrationResult"
        class="flex flex-col gap-2 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"
      >
        <p class="font-semibold m-0">
          ✓ Migración completada —
          {{ migrationResult.resumen.exitosas }}/{{ migrationResult.resumen.total }} tablas migradas
        </p>
        <ul class="flex flex-col gap-1 mt-1 list-none p-0 m-0">
          <li
            v-for="item in migrationResult.detalle"
            :key="item.tabla"
            class="flex items-center gap-2 text-xs"
            :class="item.success ? 'text-emerald-700' : 'text-red-500'"
          >
            <span>{{ item.success ? '✓' : '✗' }}</span>
            <span class="font-mono font-semibold">{{ item.tabla }}</span>
            <span class="text-gray-400">— {{ item.message }}</span>
          </li>
        </ul>
      </div>

      <!-- ERROR -->
      <div
        v-if="migrationError"
        class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        ✗ {{ migrationError }}
      </div>

    </div>

  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { databaseService } from '../services/databaseService'

export default {
  name: 'DatabaseView',

  setup () {
    const status = ref({
      sqlserver_2022: { connected: false, host: '—', database: '—' },
      sqlserver_2008: { connected: false, host: '—', database: '—' }
    })

    const migrating       = ref(false)
    const migrationResult = ref(null)
    const migrationError  = ref(null)

    const loadStatus = async () => {
      try {
        const data = await databaseService.getStatus()
        status.value = {
          sqlserver_2022: {
            connected: data?.sqlserver_2022?.connected || false,
            host:      data?.sqlserver_2022?.host      || '—',
            database:  data?.sqlserver_2022?.database  || '—'
          },
          sqlserver_2008: {
            connected: data?.sqlserver_2008?.connected || false,
            host:      data?.sqlserver_2008?.host      || '—',
            database:  data?.sqlserver_2008?.database  || '—'
          }
        }
      } catch (e) {
        console.error('Error al obtener estado de BD:', e)
      }
    }

    const handleMigrate = async () => {
      migrating.value       = true
      migrationResult.value = null
      migrationError.value  = null

      try {
        const res = await databaseService.migrateAll()
        migrationResult.value = res
      } catch (e) {
        migrationError.value =
          e?.response?.data?.message || 'Error durante la migración.'
      } finally {
        migrating.value = false
      }
    }

    onMounted(loadStatus)

    return { status, migrating, migrationResult, migrationError, handleMigrate }
  }
}
</script>