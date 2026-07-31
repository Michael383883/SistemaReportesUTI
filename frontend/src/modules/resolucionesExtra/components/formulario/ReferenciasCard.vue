<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
    <!-- Header oscuro, mismo estilo que "Datos generales" -->
    <div class="bg-slate-900 px-5 py-2 rounded-t-2xl flex items-center justify-between">
      <h3 class="text-[15px] font-semibold text-white">
        Referencias
        <span class="text-[12px] font-normal text-gray-400">(opcional)</span>
      </h3>

      <button
        type="button"
        class="text-gray-400 hover:text-red-400"
        title="Quitar esta sección"
        @click="onCerrar"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="p-5 space-y-3">
      <BuscadorReferencias
        :referenciasSeleccionadas="form.referencias"
        @agregar-referencia="onAgregarReferencia"
      />

      <div v-if="form.referencias.length" class="mt-1">
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-[12px] font-medium text-slate-600">Referencias seleccionadas ({{ form.referencias.length }})</h4>
          <button
            @click="form.referencias = []"
            class="text-[11px] font-medium text-red-600 hover:text-red-700"
          >
            Limpiar todas
          </button>
        </div>
        <div class="flex flex-wrap gap-1.5">
          <span
            v-for="(r, i) in form.referencias"
            :key="i"
            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-orange-50 border border-orange-200 rounded-xl text-[12px] text-orange-700 font-semibold"
          >
            {{ r.nro_referencia }}
            <span v-if="r.id_resolucion" class="text-orange-400 text-[11px] font-semibold">(ID: {{ r.id_resolucion }})</span>
            <button
              @click="form.referencias.splice(i, 1)"
              class="text-orange-400 hover:text-red-500 ml-1.5 font-bold"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" clas="font-bold">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BuscadorReferencias from '../BuscadorReferencias.vue'

const props = defineProps({
  form: { type: Object, required: true },
})

const emit = defineEmits(['cerrar'])

const { form } = props

function onAgregarReferencia(referenciaData) {
  const existe = form.referencias.some(r => r.id_resolucion === referenciaData.id_resolucion)
  if (existe) return
  form.referencias.push(referenciaData)
}

function onCerrar() {
  if (form.referencias.length && !confirm('¿Quitar esta sección? Se perderán las referencias agregadas.')) return
  form.referencias = []
  emit('cerrar')
}
</script>