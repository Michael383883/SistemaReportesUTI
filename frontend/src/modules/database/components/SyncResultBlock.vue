<template>
  <!-- Error de red / HTTP 422 / 500 -->
  <div
    v-if="error"
    role="alert"
    aria-live="assertive"
    class="flex items-center gap-2 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-[12px]"
  >
    <XCircle class="w-4 h-4 shrink-0" aria-hidden="true" />
    <span>{{ error }}</span>
  </div>

  <!-- Resultado (éxito total, éxito parcial 207, o resultado de una sola tabla) -->
  <div
    v-else-if="result"
    role="status"
    aria-live="polite"
    class="flex flex-col gap-2.5 rounded-lg border text-[13px]"
    :class="[
      compact ? 'p-3' : 'p-4',
      isPartial
        ? 'bg-amber-500/10 border-amber-500/20'
        : 'bg-emerald-500/10 border-emerald-500/20',
    ]"
  >
    <div class="flex items-center justify-between gap-2">
      <p
        class="font-medium m-0 flex items-center gap-2"
        :class="isPartial ? 'text-amber-400' : 'text-emerald-400'"
      >
        <component :is="isPartial ? AlertTriangle : CheckCircle2" class="w-4 h-4 shrink-0" aria-hidden="true" />
        {{ result.label }}
      </p>
      <span v-if="relativeTime" class="flex items-center gap-1 text-[10.5px] text-slate-500 shrink-0">
        <Clock class="w-3 h-3" aria-hidden="true" />
        {{ relativeTime }}
      </span>
    </div>

    <!-- Total acumulado, solo cuando hay más de una tabla en juego -->
    <div v-if="totalCambios" class="flex items-center gap-2 pl-6 border-l-2 border-slate-600/40 ml-1">
      <span class="text-[10.5px] text-slate-500 uppercase tracking-wide shrink-0">Total</span>
      <CambiosSummary :cambios="totalCambios" />
    </div>

    <!-- Resultado de una sola tabla -->
    <div v-if="result.single" class="flex flex-col gap-1.5 ml-6">
      <div
        class="flex items-center gap-2 text-[11px]"
        :class="result.single.success ? 'text-emerald-400' : 'text-red-400'"
      >
        <component :is="result.single.success ? CheckCircle2 : XCircle" class="w-3.5 h-3.5 shrink-0" aria-hidden="true" />
        <span class="text-slate-400">{{ result.single.message }}</span>
      </div>
      <div v-if="result.single.cambios" class="pl-5.5 ml-0.5">
        <CambiosSummary :cambios="result.single.cambios" />
      </div>
    </div>

    <!-- Resultado con detalle[] (varias tablas) -->
    <ul v-else-if="result.detalle" class="flex flex-col gap-2.5 mt-0.5 list-none p-0 m-0">
      <li
        v-for="item in result.detalle"
        :key="item.tabla"
        class="flex flex-col gap-1.5"
      >
        <div
          class="flex items-center gap-2 text-[11px]"
          :class="item.success ? 'text-emerald-400' : 'text-red-400'"
        >
          <component :is="item.success ? CheckCircle2 : XCircle" class="w-3.5 h-3.5 shrink-0" aria-hidden="true" />
          <span class="font-mono font-semibold">{{ item.tabla }}</span>
          <span class="text-slate-500 truncate">— {{ item.message }}</span>
        </div>
        <div v-if="item.cambios" class="pl-5.5 ml-0.5">
          <CambiosSummary :cambios="item.cambios" />
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { CheckCircle2, XCircle, AlertTriangle, Clock } from 'lucide-vue-next'
import CambiosSummary from './CambiosSummary.vue'

const props = defineProps({
  // { label, detalle?: [{tabla, success, message, cambios?}], single?: {tabla, success, message, cambios?}, timestamp?: number }
  result: { type: Object, default: null },
  error: { type: String, default: null },
  compact: { type: Boolean, default: false },
})

// HTTP 207: alguna tabla falló dentro de un resultado múltiple
const isPartial = computed(() => {
  if (!props.result?.detalle) return false
  return props.result.detalle.some((item) => !item.success)
})

// Suma insertados/actualizados/eliminados de todas las tablas del run,
// para que un "migrar-catalogos" o "migrar-semestre" con varias tablas
// muestre de un vistazo el movimiento total, no solo tabla por tabla.
const totalCambios = computed(() => {
  const detalle = props.result?.detalle
  if (!detalle || detalle.length <= 1) return null

  const acc = { insertados: 0, actualizados: 0, eliminados: 0 }
  let huboAlgunCambioContable = false

  for (const item of detalle) {
    const c = item.cambios
    if (!c || c.filas_antes !== undefined) continue // no sumamos la forma "catálogo" (antes/después)
    if (c.insertados !== undefined) { acc.insertados += c.insertados; huboAlgunCambioContable = true }
    if (c.actualizados !== undefined) { acc.actualizados += c.actualizados; huboAlgunCambioContable = true }
    if (c.eliminados !== undefined) { acc.eliminados += c.eliminados; huboAlgunCambioContable = true }
  }

  return huboAlgunCambioContable ? acc : null
})

// "hace 5s" / "hace 3 min" — se refresca solo, sin depender de re-render externo
const now = ref(Date.now())
let intervalId = null
onMounted(() => { intervalId = setInterval(() => { now.value = Date.now() }, 15000) })
onUnmounted(() => { if (intervalId) clearInterval(intervalId) })

const relativeTime = computed(() => {
  const ts = props.result?.timestamp
  if (!ts) return null
  const diffSec = Math.max(0, Math.round((now.value - ts) / 1000))
  if (diffSec < 5) return 'justo ahora'
  if (diffSec < 60) return `hace ${diffSec}s`
  const diffMin = Math.round(diffSec / 60)
  if (diffMin < 60) return `hace ${diffMin} min`
  const diffH = Math.round(diffMin / 60)
  return `hace ${diffH} h`
})
</script>