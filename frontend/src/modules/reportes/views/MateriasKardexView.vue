<template>
  <div class="px-4 py-0 max-w-3xl">
    <div class="mb-7">
      <h1 class="text-[20px] font-semibold text-gray-900">Reporte de Materia y Grupo</h1>
      <p class="text-[13px] text-slate-400 m-0">Historial de docentes que dictaron una materia y grupo</p>
    </div>

    <div class="rounded-xl border border-slate-700 bg-slate-800 p-5 flex flex-wrap items-end gap-3">
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-300">Código de materia</label>
        <input
          ref="materiaRef" v-model="materia" type="text" maxlength="20" placeholder="Ej: 1301033"
          class="w-56 bg-white border border-slate-300 rounded-lg text-slate-800 text-sm px-3 py-2 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          @keyup.enter="grupoRef?.focus()"
        />
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-300">Grupo</label>
        <input
          ref="grupoRef" v-model="grupo" type="text" maxlength="10" placeholder="Ej: 01"
          class="w-28 bg-white border border-slate-300 rounded-lg text-slate-800 text-sm px-3 py-2 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          @keyup.enter="irAlReporte"
        />
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-semibold bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-900 transition-all cursor-pointer border-none shadow-lg shadow-amber-500/20 disabled:opacity-60 disabled:cursor-not-allowed"
        :disabled="!materia.trim() || !grupo.trim()"
        @click="irAlReporte"
      >
        Generar reporte kardex materia
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const materia = ref('')
const grupo = ref('')
const materiaRef = ref(null)
const grupoRef = ref(null)

const irAlReporte = () => {
  if (!materia.value.trim() || !grupo.value.trim()) return
  // Misma página de reporte que ya existe, en modo materia
  router.push({
    name: 'reporte',
    query: { modo: 'materia', materia: materia.value.trim(), grupo: grupo.value.trim() },
  })
}

onMounted(() => materiaRef.value?.focus())
</script>