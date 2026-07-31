<template>
  <div class="px-6 py-1 w-full">

    <!-- Header de página -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-[20px] font-bold text-gray-900 dark:text-white">
          Lista de resoluciones registradas
        </h1>

        <p class="text-[13px] text-slate-500 dark:text-slate-400">
          Consulta y descarga de resoluciones registradas
        </p>
      </div>

      <div
        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-indigo-500/15 border border-indigo-100 dark:border-indigo-500/20 rounded-full text-[12px] text-indigo-800 dark:text-indigo-300 font-medium"
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
      class="flex items-center gap-2 px-3.5 py-2.5 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg text-red-600 dark:text-red-300 text-[14px] mb-4"
    >
      {{ error }}

      <button
        class="ml-auto px-3 py-1 rounded bg-red-100 dark:bg-red-500/20 hover:bg-red-200 dark:hover:bg-red-500/30 text-red-700 dark:text-red-200 text-xs font-semibold"
        @click="cargarListado"
      >
        Reintentar
      </button>
    </div>

    <!-- Buscador + Filtro por año -->
    <div class="mb-5 flex flex-col sm:flex-row gap-3">

      <div class="flex-1">
        <label
          class="block text-xs font-semibold tracking-widest uppercase text-slate-800 dark:text-slate-400 mb-1.5"
        >
          Buscar resolución
        </label>

        <div
          class="
          flex items-center
          bg-white dark:bg-slate-800
          border border-slate-200 dark:border-slate-700
          rounded-xl
          overflow-hidden
          focus-within:ring-2 focus-within:ring-amber-400/40 focus-within:border-amber-400
          "
        >
          <div class="flex items-center px-3 text-slate-400 dark:text-slate-500">
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
              text-slate-800 dark:text-slate-100
              text-sm
              py-2.5
              px-2
              placeholder-slate-400 dark:placeholder-slate-500
            "
          />

          <button
            v-if="busqueda"
            @click="limpiarBusqueda"
            class="
              px-3
              text-slate-700 dark:text-slate-400
              hover:text-slate-900 dark:hover:text-slate-100
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
          class="block text-xs font-semibold tracking-widest uppercase text-slate-800 dark:text-slate-400 mb-1.5"
        >
          Año
        </label>

        <select
          :value="anioSeleccionado"
          @change="filtrarPorAnio($event.target.value)"
          class="
            w-full
            bg-white dark:bg-slate-800
            border border-slate-200 dark:border-slate-700
            rounded-xl
            text-slate-800 dark:text-slate-100
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

    <!-- Card principal: solo el encabezado es oscuro -->
    <div
      class="
      rounded-2xl
      border border-slate-200 dark:border-slate-700
      bg-white dark:bg-slate-900
      shadow-sm
      overflow-hidden
      "
    >

     

      <!-- Loading -->
      <div
        v-if="loading"
        class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm"
      >
        Cargando resoluciones...
      </div>

      <!-- Vacío -->
      <div
        v-else-if="filas.length === 0"
        class="flex flex-col items-center justify-center py-12"
      >
        <div
          class="
          w-16 h-16
          rounded-2xl
          bg-slate-50 dark:bg-slate-800
          border border-slate-200 dark:border-slate-700
          flex items-center justify-center
          text-slate-400 dark:text-slate-500
          mb-3
          "
        >
          <svg
            width="28"
            height="28"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>

        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
          No se encontraron resoluciones
        </p>

        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
          Las resoluciones registradas aparecerán aquí.
        </p>
      </div>

      <!-- Tabla -->
      <div v-else class="overflow-x-auto">

        <table class="w-full text-sm">

          <thead>
            <tr
              class="
              bg-slate-900 dark:bg-slate-950
              border-b border-slate-200 dark:border-slate-800
              "
            >
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                ID
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Resolución
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Año/Periodo
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Descripción
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Archivo
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Fecha de creacion
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                Acciones
              </th>
              <th class="px-1 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-100">
                <button
          :disabled="loading"
          @click="cargarListado"
          class="
            inline-flex items-center gap-2
            px-4 py-2
            rounded-lg
            text-xs font-semibold
            bg-amber-500
            text-slate-900
            hover:bg-amber-400
            disabled:opacity-50
            transition-colors
          "
        >
          <svg
            :class="{ 'animate-spin': loading }"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
            <polyline points="21 3 21 9 15 9"/>
          </svg>
          
        </button>
                </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(fila, index) in filas"
              :key="fila.idResolucion"
              :class="[
                index % 2 === 0
                  ? 'bg-white dark:bg-slate-900'
                  : 'bg-slate-50 dark:bg-slate-800/40',
                'border-b border-slate-100 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700/40 transition-colors'
              ]"
            >
              <td class="px-4 py-3 text-slate-800 dark:text-slate-500">
                {{ index + 1 }}
              </td>

              <td class="px-4 py-3">
                <span
                  class="
                  inline-flex
                  px-2 py-0.5
                  rounded
                  text-[12px]
                  font-bold
                  bg-indigo-50 dark:bg-indigo-500/15
                  text-black-800 dark:text-indigo-300
                  "
                >
                  {{ fila.nroResolucion }}
                </span>
              </td>

              <td class="px-4 py-3">
                <span
                  class="inline-flex px-2 py-0.5 rounded text-[12px] font-bold"
                  :class="claseBadgePeriodo(fila.periodo)"
                >
                  {{ fila.anio }}/{{ fila.periodo }}
                </span>
              </td>

              <td
                class="px-4 py-3 text-slate-800 dark:text-slate-300 max-w-sm truncate"
                :title="fila.descripcion"
              >
                {{ fila.descripcion }}
              </td>

              <td class="px-4 py-3">
                <span
                  class="
                  inline-flex
                  px-2 py-0.5
                  rounded
                  text-[12px]
                  font-semibold
                  bg-emerald-50 dark:bg-emerald-500/15
                  text-emerald-600 dark:text-emerald-300
                  "
                >
                  PDF
                </span>
              </td>

              <td class="px-4 py-3 text-slate-800 dark:text-slate-400">
                {{ formatearFecha(fila.fechaSubida) }}
              </td>

              <td class="px-4 py-3">
                <div class="flex items-center gap-2">

                  <!-- Ver -->
                  <a
                    :href="urlVer(fila.idResolucion)"
                    target="_blank"
                    rel="noopener"
                    title="Ver"
                    class="
                      inline-flex items-center justify-center
                      w-8 h-8
                      rounded-lg
                      bg-slate-100 dark:bg-slate-800
                      text-slate-600 dark:text-slate-300
                      hover:bg-slate-200 dark:hover:bg-slate-700
                      transition-colors
                    "
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </a>

                  

                  <!-- Descargar -->
                  <a
                    :href="urlDescargar(fila.idResolucion)"
                    title="Descargar"
                    class="
                      inline-flex items-center justify-center
                      w-8 h-8
                      rounded-lg
                      bg-amber-500
                      text-slate-900
                      hover:bg-amber-400
                      transition-colors
                    "
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="7 10 12 15 17 10"/>
                      <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </a>

                  <!-- Borrar -->
                  <button
                    title="Borrar"
                    @click="pedirConfirmacionBorrar(fila)"
                    class="
                      inline-flex items-center justify-center
                      w-8 h-8
                      rounded-lg
                      bg-red-50 dark:bg-red-500/15
                      text-red-600 dark:text-red-300
                      hover:bg-red-100 dark:hover:bg-red-500/25
                      transition-colors
                    "
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      <line x1="10" y1="11" x2="10" y2="17"/>
                      <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                  </button>

                </div>
              </td>
            </tr>
          </tbody>

        </table>

      </div>

    </div>

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
        bg-white dark:bg-slate-900
        shadow-xl
        overflow-hidden
        "
      >
        <!-- Encabezado oscuro del modal, mismo lenguaje visual que la card -->
        <div class="px-5 py-4 bg-slate-900 dark:bg-slate-950 flex items-center gap-3">
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
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Se eliminará la resolución
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ filaParaBorrar.nroResolucion }}</span>,
            su archivo PDF y <span class="font-semibold text-slate-700 dark:text-slate-200">todos los docentes/materias asignados</span>
            a ella. Esta acción no se puede deshacer.
          </p>

          <p v-if="errorEliminar" class="text-xs text-red-500 dark:text-red-400 mt-3">
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
                bg-white dark:bg-slate-800
                border border-slate-200 dark:border-slate-700
                text-slate-600 dark:text-slate-300
                hover:bg-slate-50 dark:hover:bg-slate-700
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
    1: 'bg-pink-50 text-pink-600 dark:bg-pink-500/15 dark:text-pink-300',
    2: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300',
    3: 'bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300',
    4: 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
  }
  return mapa[Number(periodo)] || 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300'
}

onMounted(cargarListado)
</script>