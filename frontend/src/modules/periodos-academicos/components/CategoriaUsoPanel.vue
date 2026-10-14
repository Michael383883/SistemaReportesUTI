<template>
  <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-amber-50/60 dark:bg-amber-500/5">
    <td :colspan="colspan" class="px-4 py-2.5 text-[12px]">

      <!-- Verificando -->
      <div v-if="cargando" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
        <Loader2 class="w-3.5 h-3.5 animate-spin" />
        Verificando si «{{ nombre }}» está en uso...
      </div>

      <!-- En uso: no se puede eliminar, se muestra con qué está relacionada -->
      <div v-else-if="uso?.success && uso.en_uso" class="text-red-600 dark:text-red-400">
        <p class="font-semibold flex items-start gap-1.5">
          <AlertCircle class="w-3.5 h-3.5 shrink-0 mt-0.5" />
          <span>
            No se puede eliminar «{{ nombre }}»: está en uso en {{ uso.total }}
            registro{{ uso.total !== 1 ? 's' : '' }}.
            Cambiala o quitala de esos registros primero.
          </span>
        </p>
        <ul class="mt-1.5 ml-5 list-disc text-slate-600 dark:text-slate-300">
          <li v-for="d in uso.detalle" :key="d.tabla">
            {{ d.etiqueta ?? d.tabla }}: <strong>{{ d.cantidad }}</strong>
          </li>
        </ul>
      </div>

      <!-- Libre: se puede eliminar -->
      <div v-else-if="uso?.success" class="flex items-center justify-between gap-3">
        <span class="text-slate-600 dark:text-slate-300">
          «{{ nombre }}» no está en uso, se puede eliminar.
        </span>
        <button
          type="button"
          :disabled="eliminando"
          @click="$emit('eliminar')"
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11.5px] font-bold
                 bg-red-600 hover:bg-red-500 text-white transition-colors
                 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Loader2 v-if="eliminando" class="w-3.5 h-3.5 animate-spin" />
          <Trash2 v-else class="w-3.5 h-3.5" />
          Eliminar
        </button>
      </div>

      <!-- Falló la verificación: no se ofrece borrar a ciegas -->
      <p v-else-if="uso" class="text-slate-500 dark:text-slate-400">
        {{ uso.message }}
      </p>
    </td>
  </tr>
</template>

<script setup>
import { Loader2, AlertCircle, Trash2 } from 'lucide-vue-next'

defineProps({
  // Nombre de la categoría que se está editando (el original, no el del input)
  nombre: { type: String, required: true },
  // Resultado de consultarUso(): { success, en_uso, total, detalle } | { success:false, message } | null
  uso: { type: Object, default: null },
  // true mientras se consulta el uso
  cargando: { type: Boolean, default: false },
  // true mientras se ejecuta el DELETE
  eliminando: { type: Boolean, default: false },
  // Cantidad de columnas de la tabla donde se inserta la fila
  colspan: { type: Number, default: 2 },
})

defineEmits(['eliminar'])
</script>