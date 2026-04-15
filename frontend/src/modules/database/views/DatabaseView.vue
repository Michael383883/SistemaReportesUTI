<template>
  <div class="db-config">

    <!-- Header -->
    <div class="db-header">
      <h1>Configuración de bases de datos</h1>
      <p>Estado de conexión y herramientas de migración</p>
    </div>

    <!-- Estado de Conexiones -->
    <div class="db-card">
      <span class="db-section-label">ESTADO DE CONEXIONES</span>

      <div class="db-connection">
        <div class="db-icon pg">PG</div>
        <div class="db-info">
          <strong>PostgreSQL</strong>
          <small>{{ status.postgres.host }} · {{ status.postgres.database }}</small>
        </div>
        <span class="db-badge" :class="status.postgres.connected ? 'connected' : 'disconnected'">
          <i class="dot"></i>
          {{ status.postgres.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>

      <hr class="db-divider" />

      <div class="db-connection">
        <div class="db-icon sql">SQL</div>
        <div class="db-info">
          <strong>SQL Server</strong>
          <small>{{ status.sqlserver.host }} · {{ status.sqlserver.database }}</small>
        </div>
        <span class="db-badge" :class="status.sqlserver.connected ? 'connected' : 'disconnected'">
          <i class="dot"></i>
          {{ status.sqlserver.connected ? 'Conectado' : 'Sin conexión' }}
        </span>
      </div>
    </div>

    <!-- Migración de Datos -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">

    <span class="text-xs font-semibold text-gray-500 uppercase tracking-widest">
        Migración de datos
    </span>

    <!-- ALERTA -->
    <div
        v-if="!status.sqlserver.connected"
        class="mt-4 flex items-start gap-2 p-3 rounded-lg bg-yellow-50 text-yellow-700 border border-yellow-200"
    >
        <span>⚠</span>
        <p class="text-sm">
        SQL Server no tiene conexión activa. Verificá la configuración de red antes de iniciar la migración.
        </p>
    </div>

    <!-- DESCRIPCIÓN -->
    <p class="mt-4 text-sm text-gray-600">
        Transfiere todas las tablas y registros desde SQL Server hacia PostgreSQL sin eliminar datos del origen.
    </p>

    <!-- FLUJO -->
    <div class="mt-6 flex items-center justify-center gap-4">
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 text-sm">
        🗄 <span>SQL Server <em class="text-gray-500">origen</em></span>
        </div>

        <span class="text-gray-400 text-xl">→</span>

        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 text-sm">
        🗄 <span>PostgreSQL <em class="text-gray-500">destino</em></span>
        </div>
    </div>

    <!-- BOTÓN -->
    <button
        class="mt-6 w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition
            bg-blue-600 text-white hover:bg-blue-700
            disabled:bg-gray-300 disabled:cursor-not-allowed disabled:text-gray-600"
        :disabled="!status.sqlserver.connected || migrating"
        @click="handleMigrate"
    >
        <span v-if="migrating" class="animate-spin">⏳</span>
        <span v-else>↩</span>

        {{ migrating ? 'Migrando datos...' : 'Migrar SQL Server → PostgreSQL' }}
    </button>

    <!-- NOTA -->
    <p class="mt-3 text-xs text-gray-500 text-center">
        El proceso puede tardar varios minutos dependiendo del volumen de datos.
    </p>

    <!-- RESULTADO -->
    <div
        v-if="migrationResult"
        class="mt-4 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm"
    >
        ✓ {{ migrationResult }}
    </div>

    <div
        v-if="migrationError"
        class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm"
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

<style scoped>
.db-config {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 2rem;
  max-width: 700px;
}

/* Header */
.db-header h1 {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 0 0 0.2rem;
  color: var(--text-primary, #1a1a1a);
}
.db-header p {
  margin: 0;
  font-size: 0.9rem;
  color: var(--text-muted, #6b7280);
}

/* Card */
.db-card {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.db-section-label {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: var(--text-muted, #9ca3af);
}

/* Connections */
.db-connection {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.db-icon {
  width: 42px;
  height: 42px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.72rem;
  flex-shrink: 0;
}
.db-icon.pg  { background: #dbeafe; color: #1d4ed8; }
.db-icon.sql { background: #fce7f3; color: #be185d; }

.db-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.db-info strong { font-size: 0.95rem; color: var(--text-primary, #111827); }
.db-info small  { font-size: 0.78rem; color: var(--text-muted, #9ca3af); font-family: monospace; }

.db-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.8rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 500;
  white-space: nowrap;
}
.db-badge.connected    { background: #d1fae5; color: #065f46; }
.db-badge.disconnected { background: #fee2e2; color: #991b1b; }

.dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.db-divider {
  border: none;
  border-top: 1px solid var(--border, #f3f4f6);
  margin: 0;
}

/* Alerts */
.db-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  border-radius: 8px;
  padding: 0.9rem 1rem;
  font-size: 0.88rem;
  line-height: 1.5;
}
.db-alert p { margin: 0; }

.db-alert.warning {
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid rgba(251, 191, 36, 0.4);
  color: #92400e;
}
.db-alert.success {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #065f46;
}
.db-alert.error {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #991b1b;
}

/* Description */
.db-desc {
  font-size: 0.88rem;
  color: var(--text-muted, #6b7280);
  margin: 0;
  line-height: 1.6;
}

/* Flow */
.db-flow {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.db-flow-node {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--surface-alt, #f9fafb);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 8px;
  padding: 0.65rem 1rem;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
}
.db-flow-node em {
  font-style: normal;
  font-weight: 400;
  color: var(--text-muted, #9ca3af);
  font-size: 0.78rem;
  margin-left: 0.25rem;
}

.db-flow-arrow { color: var(--text-muted, #9ca3af); font-size: 1.1rem; }

/* Migrate button */
.db-btn-migrate {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.8rem 1.25rem;
  background: var(--surface-alt, #f9fafb);
  border: 1px solid var(--border, #d1d5db);
  border-radius: 8px;
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.db-btn-migrate:hover:not(:disabled) {
  background: var(--surface-hover, #f3f4f6);
  border-color: #9ca3af;
}
.db-btn-migrate:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.db-note {
  font-size: 0.8rem;
  color: var(--text-muted, #9ca3af);
  margin: 0;
}

/* Spinner */
.db-spinner {
  width: 13px;
  height: 13px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>