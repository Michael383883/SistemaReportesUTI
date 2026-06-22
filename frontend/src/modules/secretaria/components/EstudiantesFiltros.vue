<template>
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="flex items-center gap-2 bg-gray-100 rounded-xl px-4 h-11 flex-1 min-w-[220px]">
            <span class="text-sm opacity-60">🔎</span>
            <input
                type="text"
                placeholder="Buscar estudiante o código..."
                class="bg-transparent outline-none border-none w-full text-sm"
                :value="modelValue.busqueda"
                @input="actualizar('busqueda', $event.target.value)"
            />
        </div>

        <div class="bg-gray-100 rounded-xl px-3 h-11 flex items-center">
            <select
                class="bg-transparent outline-none border-none text-sm text-gray-700 cursor-pointer"
                :value="modelValue.anio"
                @change="actualizar('anio', $event.target.value)"
            >
                <option value="2026">Año 2026</option>
                <option value="2025">Año 2025</option>
                <option value="2024">Año 2024</option>
            </select>
        </div>

        <div class="bg-gray-100 rounded-xl px-3 h-11 flex items-center">
            <select
                class="bg-transparent outline-none border-none text-sm text-gray-700 cursor-pointer"
                :value="modelValue.periodo"
                @change="actualizar('periodo', $event.target.value)"
            >
                <option v-for="p in PERIODOS" :key="p.value" :value="p.value">
                    {{ p.label }}
                </option>
            </select>
        </div>

        <div class="bg-gray-100 rounded-xl px-3 h-11 flex items-center">
            <select
                class="bg-transparent outline-none border-none text-sm text-gray-700 cursor-pointer"
                :value="modelValue.plan"
                @change="actualizar('plan', $event.target.value)"
            >
                <option value="">Todas las carreras</option>
                <option v-for="p in planesOptions" :key="p.codigo" :value="p.codigo">
                    {{ p.nombre }}
                </option>
            </select>
        </div>

        <div class="bg-gray-100 rounded-xl px-3 h-11 flex items-center">
            <select
                class="bg-transparent outline-none border-none text-sm text-gray-700 cursor-pointer"
                :value="modelValue.nivel"
                @change="actualizar('nivel', $event.target.value)"
            >
                <option value="">Todos los niveles</option>
                <option v-for="n in NIVELES" :key="n" :value="n">
                    Nivel {{ n }}
                </option>
            </select>
        </div>

        <button
            type="button"
            class="flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 rounded-xl px-4 h-11 text-sm text-gray-700"
            @click="limpiarFiltros"
        >
            ↺ Limpiar filtros
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { PLANES, PERIODOS, NIVELES } from '../services/estudiantesInscritosService.js'

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        // { anio, periodo, plan, nivel, busqueda }
    },
})

const emit = defineEmits(['update:modelValue', 'limpiar'])

function actualizar(campo, valor) {
    emit('update:modelValue', { ...props.modelValue, [campo]: valor })
}

const planesOptions = computed(() =>
    Object.entries(PLANES).map(([codigo, nombre]) => ({ codigo, nombre }))
)

function limpiarFiltros() {
    emit('limpiar')
}
</script>