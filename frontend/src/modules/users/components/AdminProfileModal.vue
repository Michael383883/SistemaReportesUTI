<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal-content profile-modal">
      <div class="modal-header">
        <h3>Mi perfil</h3>
        <button class="btn-close" @click="close">&times;</button>
      </div>

      <div class="modal-body">
        <!-- ===== Datos del administrador (solo lectura) ===== -->
        <section class="profile-section">
          <h4 class="section-title">Datos de la cuenta</h4>

          <div class="profile-info">
            <div class="info-row">
              <span class="info-label">Nombre</span>
              <span class="info-value">{{ authStore.user?.name ?? '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Correo</span>
              <span class="info-value">{{ authStore.user?.email ?? '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Rol</span>
              <span class="info-value badge-role">{{ roleLabel }}</span>
            </div>
          </div>
        </section>

        <hr class="divider" />

        <!-- ===== Cambio de contraseña ===== -->
        <section class="profile-section">
          <h4 class="section-title">Cambiar contraseña</h4>

          <form @submit.prevent="handleChangePassword">
            <div class="form-group">
              <label for="currentPassword">Contraseña actual</label>
              <input
                id="currentPassword"
                v-model="form.currentPassword"
                type="password"
                autocomplete="current-password"
                :class="{ 'input-error': errors.currentPassword }"
                @input="clearFieldError('currentPassword')"
              />
              <span v-if="errors.currentPassword" class="field-error">{{ errors.currentPassword }}</span>
            </div>

            <div class="form-group">
              <label for="newPassword">Nueva contraseña</label>
              <input
                id="newPassword"
                v-model="form.newPassword"
                type="password"
                autocomplete="new-password"
                :class="{ 'input-error': errors.newPassword }"
                @input="clearFieldError('newPassword')"
              />
              <span v-if="errors.newPassword" class="field-error">{{ errors.newPassword }}</span>
            </div>

            <div class="form-group">
              <label for="confirmPassword">Confirmar nueva contraseña</label>
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                type="password"
                autocomplete="new-password"
                :class="{ 'input-error': errors.confirmPassword }"
                @input="clearFieldError('confirmPassword')"
              />
              <span v-if="errors.confirmPassword" class="field-error">{{ errors.confirmPassword }}</span>
            </div>

            <p v-if="authStore.error" class="form-error">{{ authStore.error }}</p>
            <p v-if="successMessage" class="form-success">{{ successMessage }}</p>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="close">Cerrar</button>
              <button type="submit" class="btn-primary" :disabled="authStore.loading">
                {{ authStore.loading ? 'Guardando...' : 'Actualizar contraseña' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useAuthStore } from '../store/authStore'

const emit = defineEmits(['close'])

const authStore = useAuthStore()

const roleLabels = {
  admin: 'Administrador',
  secretaria: 'Secretaria',
  uti: 'UTI',
}
const roleLabel = computed(() => roleLabels[authStore.userRole] ?? authStore.userRole ?? '—')

const form = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const errors = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const successMessage = ref('')

function clearFieldError(field) {
  errors[field] = ''
  successMessage.value = ''
  authStore.clearError()
}

function validate() {
  let valid = true

  if (!form.currentPassword) {
    errors.currentPassword = 'Ingresa tu contraseña actual'
    valid = false
  }

  if (!form.newPassword) {
    errors.newPassword = 'Ingresa una nueva contraseña'
    valid = false
  } else if (form.newPassword.length < 6) {
    errors.newPassword = 'Debe tener al menos 6 caracteres'
    valid = false
  }

  if (!form.confirmPassword) {
    errors.confirmPassword = 'Confirma la nueva contraseña'
    valid = false
  } else if (form.newPassword && form.confirmPassword !== form.newPassword) {
    errors.confirmPassword = 'Las contraseñas no coinciden'
    valid = false
  }

  return valid
}

async function handleChangePassword() {
  successMessage.value = ''
  authStore.clearError()

  if (!validate()) return

  try {
    await authStore.changePassword({
      currentPassword: form.currentPassword,
      newPassword: form.newPassword,
    })

    successMessage.value = 'Contraseña actualizada correctamente'
    form.currentPassword = ''
    form.newPassword = ''
    form.confirmPassword = ''
  } catch {
    // authStore.error ya contiene el mensaje, se muestra en el template
  }
}

function close() {
  emit('close')
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content.profile-modal {
  background: #fff;
  border-radius: 8px;
  width: 100%;
  max-width: 420px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: #6b7280;
}

.btn-close:hover {
  color: #111827;
}

.modal-body {
  padding: 20px;
}

.profile-section {
  margin-bottom: 8px;
}

.section-title {
  margin: 0 0 12px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.profile-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.info-label {
  color: #6b7280;
  font-size: 0.875rem;
}

.info-value {
  color: #111827;
  font-weight: 500;
  font-size: 0.9rem;
}

.badge-role {
  background: #eef2ff;
  color: #4338ca;
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
}

.divider {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 20px 0;
}

.form-group {
  margin-bottom: 14px;
}

.form-group label {
  display: block;
  margin-bottom: 4px;
  font-size: 0.85rem;
  color: #374151;
}

.form-group input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.input-error {
  border-color: #ef4444 !important;
}

.field-error {
  display: block;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 4px;
}

.form-error {
  color: #ef4444;
  font-size: 0.85rem;
  margin: 8px 0;
}

.form-success {
  color: #16a34a;
  font-size: 0.85rem;
  margin: 8px 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.btn-primary {
  background: #4f46e5;
  color: #fff;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary:hover:not(:disabled) {
  background: #4338ca;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #fff;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-secondary:hover {
  background: #f9fafb;
}
</style>