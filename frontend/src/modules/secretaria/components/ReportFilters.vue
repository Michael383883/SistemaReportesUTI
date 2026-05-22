<!-- src/modules/secretaria/components/ReportFilters.vue -->
<template>
  <div class="rf-wrapper">
    <!-- Búsqueda -->
    <div v-if="showSearch" class="rf-field rf-field--wide">
      <label class="rf-label">Buscar</label>
      <div class="rf-input-wrap">
        <Search class="rf-input-icon" />
        <input
          v-model="local.busqueda"
          class="rf-input"
          :placeholder="searchPlaceholder"
          @input="emit('update:modelValue', { ...local })"
        />
      </div>
    </div>

    <!-- Año -->
    <div v-if="showAño" class="rf-field">
      <label class="rf-label">Año</label>
      <select v-model="local.año" class="rf-select" @change="emit('update:modelValue', { ...local })">
        <option value="">Todos</option>
        <option v-for="a in años" :key="a" :value="a">{{ a }}</option>
      </select>
    </div>

    <!-- Periodo -->
    <div v-if="showPeriodo" class="rf-field">
      <label class="rf-label">Periodo</label>
      <select v-model="local.periodo" class="rf-select" @change="emit('update:modelValue', { ...local })">
        <option value="">Todos</option>
        <option v-for="p in periodos" :key="p" :value="p">{{ p }}</option>
      </select>
    </div>

    <!-- Materia -->
    <div v-if="showMateria" class="rf-field">
      <label class="rf-label">Materia</label>
      <select v-model="local.materia" class="rf-select" @change="emit('update:modelValue', { ...local })">
        <option value="">Todas</option>
        <option v-for="m in materias" :key="m" :value="m">{{ m }}</option>
      </select>
    </div>

    <!-- Grupo -->
    <div v-if="showGrupo" class="rf-field">
      <label class="rf-label">Grupo</label>
      <select v-model="local.grupo" class="rf-select" @change="emit('update:modelValue', { ...local })">
        <option value="">Todos</option>
        <option v-for="g in grupos" :key="g" :value="g">{{ g }}</option>
      </select>
    </div>

    <!-- Estado (docentes) -->
    <div v-if="showEstado" class="rf-field">
      <label class="rf-label">Estado</label>
      <select v-model="local.estado" class="rf-select" @change="emit('update:modelValue', { ...local })">
        <option value="">Todos</option>
        <option value="Activo">Activo</option>
        <option value="Sin carga">Sin carga</option>
      </select>
    </div>

    <!-- Limpiar -->
    <div class="rf-field rf-field--center">
      <label class="rf-label">&nbsp;</label>
      <button class="rf-clear-btn" @click="clearFilters">
        <X style="width:13px;height:13px;" /> Limpiar
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Search, X } from 'lucide-vue-next'

const props = defineProps({
  modelValue:        { type: Object,  default: () => ({}) },
  showSearch:        { type: Boolean, default: true },
  searchPlaceholder: { type: String,  default: 'Buscar...' },
  showAño:           { type: Boolean, default: false },
  showPeriodo:       { type: Boolean, default: false },
  showMateria:       { type: Boolean, default: false },
  showGrupo:         { type: Boolean, default: false },
  showEstado:        { type: Boolean, default: false },
  años:    { type: Array, default: () => [2022, 2023, 2024, 2025] },
  periodos:{ type: Array, default: () => ['2025-I', '2025-II'] },
  materias:{ type: Array, default: () => [] },
  grupos:  { type: Array, default: () => ['G1', 'G2', 'G3'] },
})
const emit = defineEmits(['update:modelValue'])

const local = reactive({ ...props.modelValue })

watch(() => props.modelValue, v => Object.assign(local, v), { deep: true })

function clearFilters() {
  Object.keys(local).forEach(k => (local[k] = ''))
  emit('update:modelValue', { ...local })
}
</script>

<style scoped>
.rf-wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 16px;
}
.rf-field { display: flex; flex-direction: column; gap: 4px; min-width: 140px; }
.rf-field--wide { min-width: 220px; flex: 1; }
.rf-field--center { justify-content: flex-end; }
.rf-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
.rf-input-wrap { position: relative; display: flex; align-items: center; }
.rf-input-icon { position: absolute; left: 9px; width: 14px; height: 14px; color: #9ca3af; pointer-events: none; }
.rf-input {
  width: 100%; padding: 7px 10px 7px 30px;
  border: 1px solid #d1d5db; border-radius: 7px;
  font-size: 13px; color: #111827; outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.rf-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.rf-select {
  padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 7px;
  font-size: 13px; color: #111827; outline: none; background: #fff; cursor: pointer;
  transition: border-color .15s;
}
.rf-select:focus { border-color: #3b82f6; }
.rf-clear-btn {
  display: flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 7px;
  border: 1px solid #e5e7eb; background: #f9fafb;
  font-size: 12px; color: #6b7280; cursor: pointer;
  transition: background .15s, color .15s;
}
.rf-clear-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
</style>