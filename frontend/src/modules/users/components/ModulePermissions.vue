<template>
  <section>
    <!-- Encabezado -->
    <div class="mb-4">
      <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-0.5">
        Permisos de módulos
        <p class="text-[12px] font-normal text-slate-600 dark:text-slate-400 mt-0.5">
          Habilitá o deshabilitá módulos para cada rol
        </p>
      </h2>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-600
             dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
             rounded-lg px-3 py-2 mb-4 text-[12px]"
    >
      {{ error }}
      <button class="ml-auto font-semibold" @click="cargar">Reintentar</button>
    </div>

    <!-- Loading -->
    <div v-if="loading && !modulos.length" class="py-12 text-center text-[12px] text-slate-500">
      Cargando permisos...
    </div>

    <!-- Módulos -->
    <div v-else class="grid gap-4 lg:grid-cols-2">
      <div
        v-for="mod in modulos"
        :key="mod.clave"
        class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden shadow-md shadow-slate-900/5"
      >
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
          <h3 class="text-[15px] font-semibold text-slate-900 dark:text-slate-100 m-0">{{ mod.nombre }}</h3>
          <p class="text-[12px] text-slate-600 dark:text-slate-400 m-0 mt-1 leading-relaxed">{{ mod.descripcion }}</p>
        </div>

        <ul class="list-none m-0 p-0">
          <li
            v-for="r in mod.roles"
            :key="r.role"
            class="flex items-center justify-between gap-4 px-5 py-3.5 border-b last:border-b-0 border-slate-100 dark:border-slate-700/60"
          >
            <div class="flex items-center gap-2.5">
              <span :class="['px-2.5 py-1 rounded-md text-xs font-semibold border', getRoleClass(r.role)]">
                {{ r.label }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-[0.68rem] font-semibold"
                :class="r.enabled
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                  : 'bg-gray-100 text-gray-500 dark:bg-slate-700/60 dark:text-slate-400'"
              >
                {{ r.enabled ? 'Habilitado' : 'Deshabilitado' }}
              </span>
            </div>

            <!-- Interruptor -->
            <button
              type="button"
              role="switch"
              :aria-checked="r.enabled"
              :aria-label="`${r.enabled ? 'Deshabilitar' : 'Habilitar'} ${mod.nombre} para ${r.label}`"
              :disabled="guardando[mod.clave + '|' + r.role]"
              class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-none transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              :class="r.enabled ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
              @click="alternar(mod, r)"
            >
              <span
                class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                :class="r.enabled ? 'translate-x-[22px]' : 'translate-x-0.5'"
              />
            </button>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { modulePermissionService } from '../services/modulePermissionService'
import { useNotify } from '@/shared/composables/useNotify'

const notify = useNotify()

const modulos = ref([])
const loading = ref(false)
const error = ref(null)
const guardando = reactive({})

const getRoleClass = (role) => {
  switch (role) {
    case 'admin':               return 'bg-red-100 text-red-800 border-red-200'
    case 'secretaria':          return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'secretaria_talleres': return 'bg-violet-100 text-violet-800 border-violet-200'
    case 'uti':                 return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    default:                    return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

const cargar = async () => {
  loading.value = true
  error.value = null
  try {
    modulos.value = await modulePermissionService.listar()
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Error al cargar los permisos'
  } finally {
    loading.value = false
  }
}

const alternar = async (mod, r) => {
  const clave = `${mod.clave}|${r.role}`
  const nuevo = !r.enabled

  guardando[clave] = true
  try {
    await modulePermissionService.actualizar({ module: mod.clave, role: r.role, enabled: nuevo })
    r.enabled = nuevo
    notify.success(`${mod.nombre}: ${nuevo ? 'habilitado' : 'deshabilitado'} para ${r.label}`)
  } catch (err) {
    notify.error(err.response?.data?.message ?? 'No se pudo guardar el cambio')
  } finally {
    guardando[clave] = false
  }
}

onMounted(cargar)
</script>