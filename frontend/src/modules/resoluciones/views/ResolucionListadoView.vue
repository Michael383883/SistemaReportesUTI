<template>
  <div class="px-6 py-1 w-full">

    <!-- Header de página -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-[20px] font-bold text-gray-900">
          Lista de resoluciones registradas
        </h1>

        <p class="text-[13px] text-slate-500">
          Consulta y descarga de resoluciones registradas
        </p>
      </div>

      <div
        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-full text-[12px] text-indigo-800 font-medium"
      >
        <svg
          width="15"
          height="15"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>

        <span>{{ filas.length }} registros</span>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="flex items-center gap-2 px-3.5 py-2.5 bg-red-50 border border-red-200 rounded-lg text-red-600 text-[14px] mb-4"
    >
      {{ error }}

      <button
        class="ml-auto px-3 py-1 rounded bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold"
        @click="cargarListado"
      >
        Reintentar
      </button>
    </div>

    <!-- Buscador + Filtro por año -->
    <div class="mb-5 flex flex-col sm:flex-row gap-3">

      <div class="flex-1">
        <label
          class="block text-xs font-semibold tracking-widest uppercase text-slate-700 mb-1.5"
        >
          Buscar resolución
        </label>

        <div
          class="
          flex items-center
          bg-white
          border border-gray-300
          rounded-xl
          overflow-hidden
          focus-within:ring-2 focus-within:ring-amber-400/40 focus-within:border-amber-400
          "
        >
          <div class="flex items-center px-3 text-slate-400">
            <svg
              width="15"
              height="15"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </div>

          <input
            type="text"
            :value="busqueda"
            @input="buscar($event.target.value)"
            @keyup.enter="cargarListado()"
            placeholder="Buscar por número de resolución..."
            class="
              flex-1
              bg-transparent
              border-none
              outline-none
              text-slate-800
              text-sm
              py-2.5
              px-2
              placeholder-slate-400
            "
          />

          <button
            v-if="busqueda"
            @click="limpiarBusqueda"
            class="
              px-3
              text-slate-500
              hover:text-slate-800
              transition-colors
            "
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Filtro por año -->
      <div class="sm:w-48">
        <label
          class="block text-xs font-semibold tracking-widest uppercase text-slate-700 mb-1.5"
        >
          Año
        </label>

        <select
          :value="anioSeleccionado"
          @change="filtrarPorAnio($event.target.value)"
          class="
            w-full
            bg-white
            border border-gray-300
            rounded-xl
            text-slate-800
            text-sm
            py-2.5
            px-3
            outline-none
            cursor-pointer
            focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400
          "
        >
          <option value="">Todos los años</option>
          <option
            v-for="anio in aniosDisponibles"
            :key="anio"
            :value="anio"
          >
            {{ anio }}
          </option>
        </select>
      </div>

    </div>

    <!-- Tabla (componente hijo) -->
    <TablaResoluciones
      :filas="filas"
      :loading="loading"
      :url-ver="urlVer"
      :url-descargar="urlDescargar"
      :formatear-fecha="formatearFecha"
      :clase-badge-periodo="claseBadgePeriodo"
      @borrar="pedirConfirmacionBorrar"
      @recargar="cargarListado"
    />

    <!-- Modal de confirmación de borrado -->
    <div
      v-if="filaParaBorrar"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
      @click.self="cancelarBorrado"
    >
      <div
        class="
        w-full max-w-md
        rounded-2xl
        bg-white
        shadow-xl
        overflow-hidden
        "
      >
        <!-- Encabezado, mismo lenguaje visual que la card -->
        <div class="px-5 py-4 bg-slate-800 flex items-center gap-3">
          <div
            class="
            w-9 h-9 shrink-0
            rounded-full
            bg-red-500/15
            text-red-400
            flex items-center justify-center
            "
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 9v4"/>
              <path d="M12 17h.01"/>
              <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
          </div>

          <h3 class="text-sm font-semibold text-white">
            ¿Estás seguro de borrar esta resolución?
          </h3>
        </div>

        <div class="p-5">
          <p class="text-xs text-slate-600">
            Se eliminará la resolución
            <span class="font-semibold text-slate-800">{{ filaParaBorrar.nroResolucion }}</span>,
            su archivo PDF y <span class="font-semibold text-slate-800">todos los docentes/materias asignados</span>
            a ella. Esta acción no se puede deshacer.
          </p>

          <p v-if="errorEliminar" class="text-xs text-red-500 mt-3">
            {{ errorEliminar }}
          </p>

          <div class="flex justify-end gap-2 mt-5">
            <button
              :disabled="eliminando"
              @click="cancelarBorrado"
              class="
                px-4 py-2
                rounded-lg
                text-xs font-semibold
                bg-white
                border border-gray-300
                text-slate-600
                hover:bg-slate-50
                disabled:opacity-50
              "
            >
              Cancelar
            </button>

            <button
              :disabled="eliminando"
              @click="confirmarBorrado"
              class="
                px-4 py-2
                rounded-lg
                text-xs font-semibold
                bg-red-500
                text-white
                hover:bg-red-400
                disabled:opacity-50
              "
            >
              {{ eliminando ? 'Borrando...' : 'Sí, borrar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useResolucionListado } from '../composables/useResolucionListado'
import TablaResoluciones from '../components/TablaResoluciones.vue'

defineEmits(['editar'])

const {
  filas,
  loading,
  error,
  busqueda,
  anioSeleccionado,
  aniosDisponibles,
  urlVer,
  urlDescargar,
  formatearFecha,
  cargarListado,
  buscar,
  filtrarPorAnio,
  limpiarBusqueda,
  eliminando,
  errorEliminar,
  eliminarResolucion,
} = useResolucionListado()

// Fila pendiente de confirmación de borrado (null = modal cerrado)
const filaParaBorrar = ref(null)

function pedirConfirmacionBorrar(fila) {
  errorEliminar.value = ''
  filaParaBorrar.value = fila
}

function cancelarBorrado() {
  if (eliminando.value) return
  filaParaBorrar.value = null
  errorEliminar.value = ''
}

async function confirmarBorrado() {
  if (!filaParaBorrar.value) return

  const ok = await eliminarResolucion(filaParaBorrar.value.idResolucion)

  if (ok) {
    filaParaBorrar.value = null
  }
  // Si falla, el modal queda abierto mostrando errorEliminar
}

// Paleta aplicada según la imagen de referencia: pink -> blue -> teal -> amber
// (periodo 4 y el color por defecto comparten el mismo amber, tal como en la imagen)
function claseBadgePeriodo(periodo) {
  const mapa = {
    1: 'bg-pink-50 text-pink-600',
    2: 'bg-blue-50 text-blue-600',
    3: 'bg-teal-50 text-teal-600',
    4: 'bg-amber-50 text-amber-600',
  }
  return mapa[Number(periodo)] || 'bg-amber-50 text-amber-600'
}

onMounted(cargarListado)
</script>