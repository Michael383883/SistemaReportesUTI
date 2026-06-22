<template>
  <div class="px-6 py-5 max-w-7xl">

    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-[20px] font-semibold text-gray-900">
          Últimas Resoluciones
        </h1>

        <p class="text-[13px] text-slate-400">
          Consulta y descarga de resoluciones registradas
        </p>
      </div>

      <div
        class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full text-[12px] text-indigo-600 font-medium"
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

    <!-- Buscador -->
    <div class="mb-5">

      <label
        class="block text-xs font-semibold tracking-widest uppercase text-slate-400 mb-1.5"
      >
        Buscar resolución
      </label>

      <div
        class="
        flex items-center
        bg-slate-800
        border border-slate-700
        rounded-xl
        overflow-hidden
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
            text-slate-100
            text-sm
            py-2.5
            px-2
            placeholder-slate-500
          "
        />

        <button
          v-if="busqueda"
          @click="limpiarBusqueda"
          class="
            px-3
            text-slate-400
            hover:text-slate-100
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

    <!-- Card principal -->
    <div
      class="
      rounded-xl
      border border-slate-700
      bg-slate-800
      overflow-hidden
      "
    >

      <!-- Header tabla -->
      <div
        class="
        px-5 py-3
        border-b border-slate-700
        flex items-center justify-between
        "
      >
        <div>
          <h3 class="text-sm font-semibold text-slate-100">
            Resoluciones registradas
          </h3>

          <p class="text-xs text-slate-400 mt-0.5">
            Últimas resoluciones disponibles
          </p>
        </div>

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
          Actualizar
        </button>
      </div>

      <!-- Loading -->
      <div
        v-if="loading"
        class="py-10 text-center text-slate-400 text-sm"
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
          bg-white/[0.03]
          border border-slate-700
          flex items-center justify-center
          text-slate-500
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

        <p class="text-sm font-semibold text-slate-300">
          No se encontraron resoluciones
        </p>

        <p class="text-xs text-slate-500 mt-1">
          Las resoluciones registradas aparecerán aquí.
        </p>
      </div>

      <!-- Tabla -->
      <div v-else class="overflow-x-auto">

        <table class="w-full text-sm">

          <thead>
            <tr
              class="
              bg-slate-900/40
              border-b border-slate-700
              "
            >
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                ID
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                Resolución
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                Descripción
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                Archivo
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                Fecha
              </th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                Acciones
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="fila in filas"
              :key="fila.idResolucion"
              class="
              border-b border-slate-700/50
              hover:bg-white/[0.03]
              transition-colors
              "
            >
              <td class="px-4 py-3 text-slate-500">
                {{ fila.idResolucion }}
              </td>

              <td class="px-4 py-3">
                <span
                  class="
                  inline-flex
                  px-2 py-0.5
                  rounded
                  text-[12px]
                  font-semibold
                  bg-indigo-500/15
                  text-indigo-300
                  "
                >
                  {{ fila.nroResolucion }}
                </span>
              </td>

              <td
                class="px-4 py-3 text-slate-300 max-w-sm truncate"
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
                  bg-emerald-500/10
                  text-emerald-400
                  "
                >
                  PDF
                </span>
              </td>

              <td class="px-4 py-3 text-slate-400">
                {{ formatearFecha(fila.fechaSubida) }}
              </td>

              <td class="px-4 py-3">
                <div class="flex gap-2">

                  <a
                    :href="urlVer(fila.idResolucion)"
                    target="_blank"
                    rel="noopener"
                    class="
                      inline-flex items-center
                      px-3 py-1.5
                      rounded-lg
                      text-xs
                      font-semibold
                      bg-slate-700
                      text-slate-200
                      hover:bg-slate-600
                    "
                  >
                    Ver
                  </a>

                  <a
                    :href="urlDescargar(fila.idResolucion)"
                    class="
                      inline-flex items-center
                      px-3 py-1.5
                      rounded-lg
                      text-xs
                      font-semibold
                      bg-amber-500
                      text-slate-900
                      hover:bg-amber-400
                    "
                  >
                    Descargar
                  </a>

                </div>
              </td>
            </tr>
          </tbody>

        </table>

      </div>

    </div>

  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useResolucionListado } from '../composables/useResolucionListado'

const {
  filas,
  loading,
  error,
  busqueda,
  urlVer,
  urlDescargar,
  formatearFecha,
  cargarListado,
  buscar,
  limpiarBusqueda,
} = useResolucionListado()

onMounted(cargarListado)
</script>