<template>
  <div class="flex flex-col gap-5 p-8 max-w-2xl">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-0.5">Configuración de bases de datos</h1>
      <p class="text-sm text-gray-500">Estado de conexión y herramientas de migración</p>
    </div>

    <!-- Estado de Conexiones -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col gap-4">
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">Estado de conexiones</span>

      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold text-xs shrink-0 bg-blue-100 text-blue-700">PG</div>
        <div class="flex flex-col flex-1 gap-0.5">
          <strong class="text-sm text-gray-900">PostgreSQL</strong>
          <small class="text-xs text-gray-400 font-mono">{{ status.postgres.host }} · {{ status.postgres.database }}</small>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap"
          :class="status.postgres.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
        >
          <i class="inline-block w-1.5 h-1.5 rounded-full bg-current"></i>
          {{ status.postgres.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>

      <hr class="border-gray-100" />

      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold text-xs shrink-0 bg-pink-100 text-pink-700">SQL</div>
        <div class="flex flex-col flex-1 gap-0.5">
          <strong class="text-sm text-gray-900">SQL Server</strong>
          <small class="text-xs text-gray-400 font-mono">{{ status.sqlserver.host }} · {{ status.sqlserver.database }}</small>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap"
          :class="status.sqlserver.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
        >
          <i class="inline-block w-1.5 h-1.5 rounded-full bg-current"></i>
          {{ status.sqlserver.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>
    </div>

    <!-- Migración de Datos -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col gap-4">
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">Migración de datos</span>

      <!-- Alerta -->
      <div
        v-if="!status.sqlserver.connected"
        class="flex items-start gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm"
      >
        <span>⚠</span>
        <p class="m-0">SQL Server no tiene conexión activa. Verificá la configuración de red antes de iniciar la migración.</p>
      </div>

      <!-- Descripción -->
      <p class="text-sm text-gray-500 leading-relaxed">
        Transfiere todas las tablas y registros desde SQL Server hacia PostgreSQL sin eliminar datos del origen.
      </p>

      <!-- Flujo -->
      <div class="flex items-center gap-3">
        <div class="flex flex-1 items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-800">
          🗄 SQL Server <em class="font-normal text-gray-400 text-xs ml-1 not-italic">origen</em>
        </div>
        <span class="text-gray-400 text-lg">→</span>
        <div class="flex flex-1 items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-800">
          🗄 PostgreSQL <em class="font-normal text-gray-400 text-xs ml-1 not-italic">destino</em>
        </div>
      </div>

      <!-- Botón -->
      <button
        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold transition-colors
               bg-blue-600 text-white hover:bg-blue-700
               disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed"
        :disabled="!status.sqlserver.connected || migrating"
        @click="handleMigrate"
      >
        <span v-if="migrating" class="animate-spin inline-block">⏳</span>
        <span v-else>↩</span>
        {{ migrating ? 'Migrando datos...' : 'Migrar SQL Server → PostgreSQL' }}
      </button>

      <!-- Nota -->
      <p class="text-xs text-gray-400 text-center">
        El proceso puede tardar varios minutos dependiendo del volumen de datos.
      </p>

      <!-- Resultado -->
      <div v-if="migrationResult" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
        ✓ {{ migrationResult }}
      </div>
      <div v-if="migrationError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
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
      postgres:  { connected: false, host: 'localhost:5432',    database: 'db_reportes' },
      sqlserver: { connected: false, host: '192.168.1.10:1433', database: 'BD_FCE' }
    })
    const migrating       = ref(false)
    const migrationResult = ref(null)
    const migrationError  = ref(null)

    const loadStatus = async () => {
      try {
        const data = await databaseService.getStatus()
        status.value = data
      } catch (e) {
        console.error('Error al obtener estado de BD:', e)
      }
    }

    const handleMigrate = async () => {
      migrating.value       = true
      migrationResult.value = null
      migrationError.value  = null
      try {
        const res = await databaseService.migrate()
        migrationResult.value = res.message || 'Migración completada exitosamente.'
      } catch (e) {
        migrationError.value = e?.response?.data?.message || 'Error durante la migración.'
      } finally {
        migrating.value = false
      }
    }

    onMounted(loadStatus)

    return { status, migrating, migrationResult, migrationError, handleMigrate }
  }
}
</script>