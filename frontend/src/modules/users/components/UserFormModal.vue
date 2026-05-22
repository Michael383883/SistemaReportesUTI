<template>
  <BaseModal :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" width="420px">
    <div class="p-7">
      <h2 class="text-[15px] font-medium text-navy mb-5">
        {{ user ? 'Editar usuario' : 'Nuevo usuario' }}
      </h2>

      <form @submit.prevent="handleSubmit" novalidate>
        <div class="grid grid-cols-2 gap-3 mb-5">

          <div class="col-span-2">
            <label class="block text-[11px] text-gray-400 mb-1.5">Nombre completo</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Ej. María López"
              class="w-full px-3 py-2.5 text-[12px] rounded-lg border bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
              :class="errors.name ? 'border-red-400' : 'border-gray-200'"
            />
            <p v-if="errors.name" class="text-[10px] text-red-500 mt-1">{{ errors.name }}</p>
          </div>

          <div class="col-span-2">
            <label class="block text-[11px] text-gray-400 mb-1.5">Correo electrónico</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="correo@umss.edu"
              class="w-full px-3 py-2.5 text-[12px] rounded-lg border bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
              :class="errors.email ? 'border-red-400' : 'border-gray-200'"
            />
            <p v-if="errors.email" class="text-[10px] text-red-500 mt-1">{{ errors.email }}</p>
          </div>

          <div>
            <label class="block text-[11px] text-gray-400 mb-1.5">Rol</label>
            <select
              v-model="form.role"
              class="w-full px-3 py-2.5 text-[12px] rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
            >
              <option value="admin">Administrador</option>
              <option value="secretaria">Secretaría</option>
              <option value="secretaria_talleres">Secretaría Talleres</option>
              <option value="uti">UTI</option>
            </select>
          </div>

          <div>
            <label class="block text-[11px] text-gray-400 mb-1.5">
              {{ user ? 'Contraseña (opcional)' : 'Contraseña' }}
            </label>

            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mín. 8 caracteres"
                class="w-full px-3 py-2.5 pr-10 text-[12px] rounded-lg border bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
                :class="errors.password ? 'border-red-400' : 'border-gray-200'"
              />

              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600"
              >
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7a10.05 10.05 0 012.293-3.95M6.223 6.223A9.956 9.956 0 0112 5c5 0 9 4 10 7a9.956 9.956 0 01-4.293 4.95M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 6L3 3" />
                </svg>

                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1 3-5 7-9 7s-8-4-9-7c1-3 5-7 9-7s8 4 9 7z" />
                </svg>
              </button>
            </div>

            <p v-if="errors.password" class="text-[10px] text-red-500 mt-1">
              {{ errors.password }}
            </p>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <button
            type="button"
            @click="$emit('update:modelValue', false)"
            class="px-4 py-2 text-[12px] text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2 bg-navy text-white text-[12px] rounded-lg hover:bg-[#051828] transition-colors disabled:opacity-60"
          >
            <span v-if="!loading">{{ user ? 'Guardar cambios' : 'Registrar' }}</span>
            <span v-else class="flex items-center gap-1.5">
              <Loader2 class="w-3.5 h-3.5 animate-spin" /> Guardando...
            </span>
          </button>
        </div>
      </form>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, watch, ref } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import BaseModal from '@/shared/components/ui/BaseModal.vue'

const showPassword = ref(false)
const props = defineProps({
  modelValue: { type: Boolean, required: true },
  user:       { type: Object,  default: null },
  loading:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const form   = reactive({ name: '', email: '', role: 'secretaria', password: '' })
const errors = reactive({ name: '', email: '', password: '' })

watch(() => props.modelValue, (open) => {
  if (open) {
    form.name     = props.user?.name  ?? ''
    form.email    = props.user?.email ?? ''
    form.role     = props.user?.role  ?? 'secretaria'
    form.password = ''
    errors.name = errors.email = errors.password = ''
  }
})

function validate() {
  errors.name = errors.email = errors.password = ''
  let valid = true

  if (!form.name.trim())  { errors.name = 'El nombre es requerido'; valid = false }

  if (!form.email.trim()) { errors.email = 'El correo es requerido'; valid = false }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Formato inválido'; valid = false
  }

  if (!props.user && !form.password) {
    errors.password = 'La contraseña es requerida'; valid = false
  } else if (form.password && form.password.length < 8) {
    errors.password = 'Mínimo 8 caracteres'; valid = false
  }

  return valid
}

function handleSubmit() {
  if (!validate()) return
  const payload = { name: form.name, email: form.email, role: form.role }
  if (form.password) payload.password = form.password
  emit('submit', payload)
}
</script>