<template>
    <div class="flex flex-col gap-5">
        <div v-if="cargando" class="text-center py-10 text-gray-500 text-sm">
            Cargando estudiantes...
        </div>

        <div v-else-if="grupos.length === 0" class="text-center py-10 text-gray-500 text-sm">
            No se encontraron estudiantes con los filtros seleccionados.
        </div>

        <div
            v-else
            v-for="grupo in grupos"
            :key="grupo.clave"
            class="rounded-2xl overflow-hidden shadow-sm bg-white"
        >
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-xl bg-white/20 rounded-xl p-2">📋</span>
                    <div>
                        <div class="font-bold text-[15px] tracking-wide">
                            {{ grupo.nombreMateria || grupo.materia }}
                        </div>
                        <div class="text-[13px] opacity-85 mt-0.5">
                            Grupo {{ grupo.grupo }} · {{ grupo.siglaPlan }} · Nivel {{ grupo.nivel }}
                        </div>
                    </div>
                </div>
                <span class="bg-white/20 px-3.5 py-1.5 rounded-full text-[13px] font-semibold">
                    {{ grupo.estudiantes.length }} inscritos
                </span>
            </div>

            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100 w-10">
                            #
                        </th>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100">
                            Nombre del estudiante
                        </th>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100">
                            Código
                        </th>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100">
                            Carrera
                        </th>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100">
                            Nivel
                        </th>
                        <th class="text-left text-[12px] text-gray-500 uppercase tracking-wide px-6 py-3 border-b border-gray-100">
                            Grupo
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(est, idx) in grupo.estudiantes" :key="est.codEstudiante">
                        <td class="px-6 py-3 text-sm text-gray-400 border-b border-gray-50">
                            {{ idx + 1 }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-800 border-b border-gray-50">
                            <span class="mr-2">🎓</span>{{ est.estudiante }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-800 border-b border-gray-50">
                            {{ est.codEstudiante }}
                        </td>
                        <td class="px-6 py-3 border-b border-gray-50">
                            <span class="bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                {{ est.siglaPlan }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-800 border-b border-gray-50">
                            {{ est.nivel }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-800 border-b border-gray-50">
                            {{ est.grupo }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    estudiantes: {
        type: Array,
        required: true,
        default: () => [],
    },
    cargando: {
        type: Boolean,
        default: false,
    },
})

// Agrupa la lista plana de estudiantes en grupos por Materia + Grupo
const grupos = computed(() => {
    const mapa = new Map()

    for (const est of props.estudiantes) {
        const clave = `${est.materia}-${est.grupo}`

        if (!mapa.has(clave)) {
            mapa.set(clave, {
                clave,
                materia: est.materia,
                nombreMateria: est.nombreMateria,
                grupo: est.grupo,
                nivel: est.nivel,
                siglaPlan: est.siglaPlan,
                nombrePlan: est.nombrePlan,
                estudiantes: [],
            })
        }

        mapa.get(clave).estudiantes.push(est)
    }

    return Array.from(mapa.values()).sort((a, b) => {
        if (a.materia !== b.materia) return a.materia.localeCompare(b.materia)
        return String(a.grupo).localeCompare(String(b.grupo))
    })
})
</script>