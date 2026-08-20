<template>
  <div class="bg-slate-100 dark:bg-slate-950 min-h-full -m-6 p-6">
    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-0.5">
          Periodos académicos
        </h1>
        <p class="text-[12px] font-normal text-slate-600 dark:text-slate-400 mt-0.5">
          Rangos de fechas usados para saber qué gestiones aún no han concluido
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="onRestaurar"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] font-semibold
                 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-300
                 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-600
                 transition-all duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <RotateCcw class="w-3.5 h-3.5" /> Restaurar predeterminados
        </button>
        <button
          @click="onGuardar"
          :disabled="loading || !huboCambios"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-bold
                 bg-amber-500 hover:bg-amber-400 active:bg-amber-600
                 text-slate-100 transition-all duration-150 cursor-pointer border-none
                 shadow-lg shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Save class="w-3.5 h-3.5" /> Guardar cambios
        </button>
      </div>
    </div>

    <!-- Error (periodos) -->
    <transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="error"
        class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-600
               dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
               rounded-lg px-3 py-2 mb-4 text-[12px]"
      >
        <AlertCircle class="w-3.5 h-3.5 shrink-0" />
        {{ error }}
        <button class="ml-auto" @click="clearError" aria-label="Cerrar error">
          <X class="w-3 h-3" />
        </button>
      </div>
    </transition>

    <!-- Aviso informativo -->
    <div
      class="flex items-start gap-2 bg-blue-50 border border-blue-200 text-blue-700
             dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400
             rounded-lg px-3 py-2.5 mb-4 text-[12px]"
    >
      <Info class="w-3.5 h-3.5 shrink-0 mt-0.5" />
      <span>
        Estos rangos se repiten cada año (solo importa el día y el mes). Se usan para
        ocultar automáticamente del reporte de "materias dictadas" las gestiones que
        todavía están en curso. Además, podés <strong>bloquear</strong> manualmente un
        periodo para ocultarlo también de la gestión actual, sin escribir año ni fecha.
      </span>
    </div>

    <!-- Tabla: Periodos académicos -->
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden shadow-md shadow-slate-900/5 mb-6">
      <div class="overflow-x-auto">
        <div v-if="loading && !draft.length" class="py-12 text-center">
          <Loader2 class="w-5 h-5 animate-spin mx-auto mb-2 text-gray-400 dark:text-slate-600" />
          <p class="text-[12px] text-gray-500 dark:text-slate-500">Cargando periodos académicos...</p>
        </div>

        <table v-else class="w-full text-[13px] border-collapse">
          <thead>
            <tr class="border-b border-b-black-800 bg-[rgb(8,31,51)] dark:border-slate-700 dark:bg-slate-900/60">
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Periodo</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Nombre</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Inicio</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Fin</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400 w-36">Bloqueo</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">Última modificación</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(p, i) in draft"
              :key="p.id"
              class="border-b border-slate-100 dark:border-slate-700/60 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.025]"
              :class="p.bloqueado
                ? 'bg-red-50 dark:bg-red-500/10'
                : (i % 2 === 0 ? 'bg-white' : 'bg-slate-50/70 dark:bg-slate-900/20')"
            >
              <td class="px-4 py-3">
                <span
                  class="px-2.5 py-1 rounded-md text-xs font-semibold border"
                  :class="p.bloqueado
                    ? 'bg-red-100 text-red-700 border-red-200 dark:bg-red-500/15 dark:text-red-400 dark:border-red-500/30'
                    : 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600'"
                >
                  {{ etiquetaPeriodo(p.periodo) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="p.nombre"
                  type="text"
                  maxlength="40"
                  :disabled="p.bloqueado"
                  class="w-full rounded-md border border-slate-300 dark:border-slate-600 dark:bg-slate-900
                         text-slate-800 dark:text-slate-200 text-[13px] px-2 py-1.5
                         focus:outline-none focus:ring-2 focus:ring-amber-400
                         disabled:opacity-50 disabled:cursor-not-allowed"
                />
              </td>
              <td class="px-4 py-3">
                <PeriodoFechaSelector v-model="p.inicio" :disabled="p.bloqueado" />
              </td>
              <td class="px-4 py-3">
                <PeriodoFechaSelector v-model="p.fin" :disabled="p.bloqueado" />
              </td>
              <td class="px-4 py-3">
                <button
                  type="button"
                  :disabled="bloqueando === p.id"
                  @click="toggleBloqueo(p)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11.5px] font-bold
                         transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  :class="p.bloqueado
                    ? 'bg-red-600 hover:bg-red-500 text-white'
                    : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600'"
                >
                  <Loader2 v-if="bloqueando === p.id" class="w-3.5 h-3.5 animate-spin" />
                  <template v-else>
                    <Lock v-if="p.bloqueado" class="w-3.5 h-3.5" />
                    <Unlock v-else class="w-3.5 h-3.5" />
                    {{ p.bloqueado ? 'Desbloquear' : 'Bloquear' }}
                  </template>
                </button>
                <p v-if="p.bloqueado && p.bloqueado_anio" class="text-[10.5px] text-red-500 dark:text-red-400 mt-1">
                  Oculto en gestión {{ p.bloqueado_anio }}
                </p>
              </td>
              <td class="px-4 py-3 text-slate-500 dark:text-slate-500 text-[12px]">
                {{ p.actualizado_por?.name ?? '—' }}
                <span v-if="p.updated_at" class="block text-slate-400 dark:text-slate-600">
                  {{ formatearFecha(p.updated_at) }}
                </span>
              </td>
            </tr>

            <tr v-if="!draft.length && !loading">
              <td colspan="6" class="px-4 py-10 text-center text-[12px] text-slate-600 dark:text-slate-500">
                No hay periodos académicos configurados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900/30 text-xs text-slate-600 dark:text-slate-500 text-right">
        {{ draft.length }} periodo{{ draft.length !== 1 ? 's' : '' }}
      </div>
    </div>

    <!-- ══════════════ Categorías (debajo de Periodos académicos, misma vista) ══════════════ -->

    <div
      class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden shadow-md shadow-slate-900/5"
    >
      <!-- Cabecera colapsable: todo el bloque de título es clickeable -->
      <button
        type="button"
        @click="categoriasAbierto = !categoriasAbierto"
        class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left
               hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors cursor-pointer"
      >
        <div class="min-w-0">
          <h2 class="text-[14px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
            Categoría de documento
            <span
              class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                     bg-slate-100 text-slate-500 border border-slate-200
                     dark:bg-slate-700/40 dark:text-slate-400 dark:border-slate-600"
            >
              {{ categorias.length }}
            </span>
          </h2>
          <p class="text-[11px] font-normal text-slate-500 dark:text-slate-400 mt-0.5 truncate">
            Catálogo de categorías disponibles al clasificar una materia.
          </p>
        </div>
        <ChevronDown
          class="w-4 h-4 shrink-0 text-slate-400 transition-transform duration-150"
          :class="{ '-rotate-180': categoriasAbierto }"
        />
      </button>

      <!-- Contenido colapsable -->
      <div v-show="categoriasAbierto" class="border-t border-slate-200 dark:border-slate-700">

        <!-- Error (categorías) -->
        <transition
          enter-active-class="transition-opacity duration-200"
          leave-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          leave-to-class="opacity-0"
        >
          <div
            v-if="errorCategorias"
            class="flex items-center gap-2 bg-red-50 border-b border-red-200 text-red-600
                   dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
                   px-3 py-2 text-[12px]"
          >
            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
            {{ errorCategorias }}
            <button class="ml-auto" @click="clearErrorCategorias" aria-label="Cerrar error">
              <X class="w-3 h-3" />
            </button>
          </div>
        </transition>

        <!-- Agregar: directo, sin modal — se escribe y se agrega -->
        <div class="flex items-center gap-2 px-4 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/20">
          <input
            v-model="nuevaCategoria"
            type="text"
            maxlength="60"
            placeholder="Escribe el nombre de la categoría y presiona Enter para agregarla"
            class="flex-1 px-3 py-1.5 text-[12.5px] bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-600
                   rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400
                   focus:border-transparent transition-colors"
            :disabled="agregandoCategoria"
            @keydown.enter.prevent="agregarCategoria"
          />
          <button
            type="button"
            :disabled="!nuevaCategoria.trim() || agregandoCategoria"
            @click="agregarCategoria"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12.5px] font-bold
                   bg-amber-500 hover:bg-amber-400 active:bg-amber-600
                   text-slate-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Plus class="w-3.5 h-3.5" /> Agregar
          </button>
        </div>

        <div class="overflow-x-auto max-h-72 overflow-y-auto">
          <div v-if="loadingCategorias && !categorias.length" class="py-8 text-center">
            <Loader2 class="w-4 h-4 animate-spin mx-auto mb-2 text-gray-400 dark:text-slate-600" />
            <p class="text-[12px] text-gray-500 dark:text-slate-500">Cargando categorías...</p>
          </div>

          <table v-else class="w-full text-[12.5px] border-collapse">
            <thead>
              <tr class="border-b border-b-black-800 bg-[rgb(8,31,51)] dark:border-slate-700 dark:bg-slate-900/60">
                <th class="text-left px-4 py-2 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">
                  Categoría de documento
                </th>
                <th class="text-right px-4 py-2 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400 w-20">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(c, i) in categorias"
                :key="c.nombre"
                class="border-b border-slate-100 dark:border-slate-700/60 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.025]"
                :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/70 dark:bg-slate-900/20'"
              >
                <!-- Nombre: texto normal, o input editable si está en modo edición -->
                <td class="px-4 py-1.5">
                  <input
                    v-if="categoriaEditando === c.nombre"
                    v-model="valorEdicion"
                    type="text"
                    maxlength="60"
                    class="w-full rounded-md border border-amber-300 dark:border-amber-500 dark:bg-slate-900
                           text-slate-800 dark:text-slate-200 text-[12.5px] px-2 py-1
                           focus:outline-none focus:ring-2 focus:ring-amber-400"
                    ref="inputEdicionRef"
                    @keydown.enter.prevent="guardarEdicion(c)"
                    @keydown.esc="cancelarEdicion"
                  />
                  <span v-else class="font-medium text-slate-800 dark:text-slate-200">
                    {{ c.nombre }}
                  </span>
                </td>
                <td class="px-4 py-1.5 text-right whitespace-nowrap">
                  <template v-if="categoriaEditando === c.nombre">
                    <button
                      type="button"
                      :disabled="guardandoEdicionNombre === c.nombre"
                      @click="guardarEdicion(c)"
                      class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg text-[11.5px] font-semibold
                             text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors"
                    >
                      <Check class="w-3.5 h-3.5" /> Guardar
                    </button>
                    <button
                      type="button"
                      @click="cancelarEdicion"
                      class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg text-[11.5px] font-semibold
                             text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                      <X class="w-3.5 h-3.5" /> Cancelar
                    </button>
                  </template>
                  <button
                    v-else
                    type="button"
                    @click="iniciarEdicion(c)"
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11.5px] font-semibold
                           text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700
                           transition-colors"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>

              <tr v-if="!categorias.length && !loadingCategorias">
                <td colspan="2" class="px-4 py-6 text-center text-[12px] text-slate-600 dark:text-slate-500">
                  No hay categorías registradas todavía.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-4 py-1.5 border-t border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900/30 text-[11px] text-slate-600 dark:text-slate-500 text-right">
          {{ categorias.length }} categoría{{ categorias.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>

    <!-- ══════════════ Tipos de título académico (misma vista, debajo de Categorías) ══════════════ -->

    <div
      class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 overflow-hidden shadow-md shadow-slate-900/5 mt-4"
    >
      <button
        type="button"
        @click="tiposAbierto = !tiposAbierto"
        class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left
               hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors cursor-pointer"
      >
        <div class="min-w-0">
          <h2 class="text-[14px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
            Tipos de título académico
            <span
              class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                     bg-slate-100 text-slate-500 border border-slate-200
                     dark:bg-slate-700/40 dark:text-slate-400 dark:border-slate-600"
            >
              {{ tiposTitulo.length }}
            </span>
          </h2>
          <p class="text-[11px] font-normal text-slate-500 dark:text-slate-400 mt-0.5 truncate">
            Catálogo usado en "Tipo de título" al registrar un título académico. Solo se administra desde aquí.
          </p>
        </div>
        <ChevronDown
          class="w-4 h-4 shrink-0 text-slate-400 transition-transform duration-150"
          :class="{ '-rotate-180': tiposAbierto }"
        />
      </button>

      <div v-show="tiposAbierto" class="border-t border-slate-200 dark:border-slate-700">

        <transition
          enter-active-class="transition-opacity duration-200"
          leave-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          leave-to-class="opacity-0"
        >
          <div
            v-if="errorTipos"
            class="flex items-center gap-2 bg-red-50 border-b border-red-200 text-red-600
                   dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
                   px-3 py-2 text-[12px]"
          >
            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
            {{ errorTipos }}
            <button class="ml-auto" @click="errorTipos = null" aria-label="Cerrar error">
              <X class="w-3 h-3" />
            </button>
          </div>
        </transition>

        <div class="flex items-center gap-2 px-4 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/20">
          <input
            v-model="nuevoTipo"
            type="text"
            maxlength="60"
            placeholder="Escribe el tipo de título y presiona Enter para agregarlo"
            class="flex-1 px-3 py-1.5 text-[12.5px] uppercase placeholder:normal-case bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-600
                   rounded-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400
                   focus:border-transparent transition-colors"
            @keydown.enter.prevent="agregarTipo"
          />
          <button
            type="button"
            :disabled="!nuevoTipo.trim()"
            @click="agregarTipo"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12.5px] font-bold
                   bg-amber-500 hover:bg-amber-400 active:bg-amber-600
                   text-slate-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Plus class="w-3.5 h-3.5" /> Agregar
          </button>
        </div>

        <div class="overflow-x-auto max-h-72 overflow-y-auto">
          <div v-if="loadingTipos && !tiposTitulo.length" class="py-8 text-center">
            <Loader2 class="w-4 h-4 animate-spin mx-auto mb-2 text-gray-400 dark:text-slate-600" />
            <p class="text-[12px] text-gray-500 dark:text-slate-500">Cargando tipos de título...</p>
          </div>

          <table v-else class="w-full text-[12.5px] border-collapse">
            <thead>
              <tr class="border-b border-b-black-800 bg-[rgb(8,31,51)] dark:border-slate-700 dark:bg-slate-900/60">
                <th class="text-left px-4 py-2 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400">
                  Tipo de título
                </th>
                <th class="text-right px-4 py-2 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-100 dark:text-slate-400 w-20">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(t, i) in tiposTitulo"
                :key="t"
                class="border-b border-slate-100 dark:border-slate-700/60 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.025]"
                :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/70 dark:bg-slate-900/20'"
              >
                <td class="px-4 py-1.5">
                  <input
                    v-if="tipoEditando === t"
                    v-model="valorEdicionTipo"
                    type="text"
                    maxlength="60"
                    class="w-full uppercase rounded-md border border-amber-300 dark:border-amber-500 dark:bg-slate-900
                           text-slate-800 dark:text-slate-200 text-[12.5px] px-2 py-1
                           focus:outline-none focus:ring-2 focus:ring-amber-400"
                    ref="inputEdicionTipoRef"
                    @keydown.enter.prevent="guardarEdicionTipo(t)"
                    @keydown.esc="cancelarEdicionTipo"
                  />
                  <span v-else class="font-medium text-slate-800 dark:text-slate-200">
                    {{ t }}
                  </span>
                </td>
                <td class="px-4 py-1.5 text-right whitespace-nowrap">
                  <template v-if="tipoEditando === t">
                    <button
                      type="button"
                      :disabled="guardandoEdicionTipoNombre === t"
                      @click="guardarEdicionTipo(t)"
                      class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg text-[11.5px] font-semibold
                             text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors"
                    >
                      <Check class="w-3.5 h-3.5" /> Guardar
                    </button>
                    <button
                      type="button"
                      @click="cancelarEdicionTipo"
                      class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg text-[11.5px] font-semibold
                             text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                      <X class="w-3.5 h-3.5" /> Cancelar
                    </button>
                  </template>
                  <button
                    v-else
                    type="button"
                    @click="iniciarEdicionTipo(t)"
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11.5px] font-semibold
                           text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700
                           transition-colors"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>

              <tr v-if="!tiposTitulo.length && !loadingTipos">
                <td colspan="2" class="px-4 py-6 text-center text-[12px] text-slate-600 dark:text-slate-500">
                  No hay tipos de título registrados todavía.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-4 py-1.5 border-t border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900/30 text-[11px] text-slate-600 dark:text-slate-500 text-right">
          {{ tiposTitulo.length }} tipo{{ tiposTitulo.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { RotateCcw, Save, AlertCircle, X, Info, Loader2, Plus, Pencil, Check, ChevronDown, Lock, Unlock } from 'lucide-vue-next'
// (todos los iconos usados por la sección de Tipos de título ya están en este import)
import { usePeriodosAcademicos } from '../composables/usePeriodosAcademicos'
import { useCategorias } from '../composables/useCategorias'
import { useTiposTitulo } from '../composables/useTiposTitulo'
import { useNotify } from '@/shared/composables/useNotify'
import PeriodoFechaSelector from '../components/PeriodoFechaSelector.vue'

const notify = useNotify()

// ══════════════ Periodos académicos ══════════════

const {
  periodos,
  loading,
  error,
  fetchPeriodos,
  guardarCambios,
  restaurarPredeterminados,
  bloquearPeriodo,
  desbloquearPeriodo,
  clearError
} = usePeriodosAcademicos()

// Copia editable local: así el admin puede cambiar varios campos y recién
// mandar todo junto al backend cuando presiona "Guardar cambios".
const draft = ref([])

const ETIQUETAS = { '1': 'Semestre I', '2': 'Semestre II', '3': 'Verano', '4': 'Invierno' }
const etiquetaPeriodo = (p) => ETIQUETAS[p] ?? p

function sincronizarDraft() {
  draft.value = periodos.value.map((p) => ({ ...p }))
}

watch(periodos, sincronizarDraft)

const huboCambios = computed(() =>
  draft.value.some((p, i) => {
    const original = periodos.value[i]
    if (!original) return false
    return p.nombre !== original.nombre || p.inicio !== original.inicio || p.fin !== original.fin
  })
)

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function onGuardar() {
  const resultado = await guardarCambios(draft.value)
  if (resultado.success) {
    notify.success(resultado.message ?? 'Cambios guardados correctamente')
  } else {
    notify.error(resultado.message ?? 'Error al guardar los cambios')
  }
}

async function onRestaurar() {
  if (!confirm('¿Restaurar los 4 periodos a sus valores originales? Esto sobrescribirá los cambios actuales.')) return
  const resultado = await restaurarPredeterminados()
  if (resultado.success) {
    notify.success(resultado.message ?? 'Valores restaurados')
  } else {
    notify.error(resultado.message ?? 'Error al restaurar los valores')
  }
}

// ─── Bloquear / Desbloquear (independiente de "Guardar cambios") ───
// Se aplica al toque, sin pasar por el draft ni por el botón "Guardar",
// porque no es un campo editable: es una acción directa contra el backend.
const bloqueando = ref(null) // id del periodo que está en proceso, para deshabilitar solo ese botón

async function toggleBloqueo(p) {
  bloqueando.value = p.id
  const eraBloqueado = p.bloqueado // capturado ANTES de mutar p, para el mensaje correcto
  try {
    const resultado = eraBloqueado
      ? await desbloquearPeriodo(p.id)
      : await bloquearPeriodo(p.id)

    if (resultado.success) {
      // Parchea la fila en 'draft' al toque, sin esperar al watcher
      // (así no se pisan ediciones sin guardar de otras filas).
      if (resultado.periodo) Object.assign(p, resultado.periodo)

      notify.success(
        eraBloqueado
          ? (resultado.message ?? 'Periodo desbloqueado')
          : (resultado.message ?? 'Periodo bloqueado — ya no se mostrará en Materias dictadas')
      )
    } else {
      notify.error(resultado.message)
    }
  } finally {
    bloqueando.value = null
  }
}
onMounted(fetchPeriodos)

// ══════════════ Categorías (misma vista, sección colapsable de abajo) ══════════════

// Colapsada por defecto para no ocupar tanto espacio en la vista.
// Cambiá a "true" si preferís que arranque expandida.
const categoriasAbierto = ref(false)

const {
  categorias,
  loading: loadingCategorias,
  error: errorCategorias,
  cargarCategorias,
  crearCategoria,
  actualizarCategoria,
  clearError: clearErrorCategorias,
} = useCategorias()

// ─── Agregar categoría: directo, sin modal — se escribe y se agrega ───
const nuevaCategoria = ref('')
const agregandoCategoria = ref(false)

async function agregarCategoria() {
  const valor = nuevaCategoria.value.trim()
  if (!valor) return

  const yaExiste = categorias.value.some(
    (c) => c.nombre.toString().trim().toLowerCase() === valor.toLowerCase()
  )
  if (yaExiste) {
    notify.error('Ya existe una categoría con ese nombre')
    return
  }

  agregandoCategoria.value = true
  try {
    const resultado = await crearCategoria(valor)
    if (resultado?.success !== false) {
      nuevaCategoria.value = ''
      notify.success('Categoría agregada')
    } else {
      notify.error(resultado?.message ?? 'Error al agregar la categoría')
    }
  } finally {
    agregandoCategoria.value = false
  }
}

// ─── Editar categoría: lápiz por fila → input inline, sin modal ───
// El backend no maneja "id" de categoría (no hay tabla CATEGORIAS), así que
// se identifica y edita por nombre (PUT /api/categorias { anterior, nuevo }).
const categoriaEditando = ref(null) // guarda el nombre original que se está editando
const valorEdicion = ref('')
const guardandoEdicionNombre = ref(null)
const inputEdicionRef = ref(null)

function iniciarEdicion(c) {
  categoriaEditando.value = c.nombre
  valorEdicion.value = c.nombre
  nextTick(() => inputEdicionRef.value?.[0]?.focus?.())
}

function cancelarEdicion() {
  categoriaEditando.value = null
  valorEdicion.value = ''
}

async function guardarEdicion(c) {
  const valor = valorEdicion.value.trim()
  if (!valor) return

  // Si no cambió nada, simplemente cerramos la edición
  if (valor === c.nombre) {
    cancelarEdicion()
    return
  }

  const yaExiste = categorias.value.some(
    (otro) => otro.nombre !== c.nombre && otro.nombre.toString().trim().toLowerCase() === valor.toLowerCase()
  )
  if (yaExiste) {
    notify.error('Ya existe una categoría con ese nombre')
    return
  }

  guardandoEdicionNombre.value = c.nombre
  try {
    const resultado = await actualizarCategoria(c.nombre, valor)
    if (resultado?.success !== false) {
      notify.success('Categoría actualizada')
      cancelarEdicion()
    } else {
      notify.error(resultado?.message ?? 'Error al actualizar la categoría')
    }
  } finally {
    guardandoEdicionNombre.value = null
  }
}

onMounted(cargarCategorias)

// ══════════════ Tipos de título académico (misma vista, sección colapsable) ══════════════
// Único lugar donde se pueden agregar/editar: el combobox de "Tipo de título"
// en TituloCard.vue ahora es de solo selección (ver ese componente).

const tiposAbierto = ref(false)
const errorTipos = ref(null)

const {
  tipos: tiposTitulo,
  loading: loadingTipos,
  cargarTipos,
  agregarTipoLocal,
  actualizarTipo,
} = useTiposTitulo()

const nuevoTipo = ref('')

function agregarTipo() {
  const valor = nuevoTipo.value.trim()
  if (!valor) return

  const resultado = agregarTipoLocal(valor)
  if (resultado?.success !== false) {
    nuevoTipo.value = ''
    notify.success('Tipo de título agregado')
  } else {
    notify.error(resultado?.message ?? 'Error al agregar el tipo de título')
  }
}

const tipoEditando = ref(null)
const valorEdicionTipo = ref('')
const guardandoEdicionTipoNombre = ref(null)
const inputEdicionTipoRef = ref(null)

function iniciarEdicionTipo(t) {
  tipoEditando.value = t
  valorEdicionTipo.value = t
  nextTick(() => inputEdicionTipoRef.value?.[0]?.focus?.())
}

function cancelarEdicionTipo() {
  tipoEditando.value = null
  valorEdicionTipo.value = ''
}

async function guardarEdicionTipo(t) {
  const valor = valorEdicionTipo.value.trim().toUpperCase()
  if (!valor) return

  if (valor === t) {
    cancelarEdicionTipo()
    return
  }

  const yaExiste = tiposTitulo.value.some((otro) => otro !== t && otro === valor)
  if (yaExiste) {
    notify.error('Ya existe un tipo con ese nombre')
    return
  }

  guardandoEdicionTipoNombre.value = t
  try {
    const resultado = await actualizarTipo(t, valor)
    if (resultado?.success !== false) {
      notify.success('Tipo de título actualizado')
      cancelarEdicionTipo()
    } else {
      errorTipos.value = resultado?.message ?? 'Error al actualizar el tipo de título'
      notify.error(errorTipos.value)
    }
  } finally {
    guardandoEdicionTipoNombre.value = null
  }
}

onMounted(cargarTipos)
</script>