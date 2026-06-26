<!-- composables/MateriasAsignarTabla -->
<template>
  <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden relative">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="border-b border-slate-700 bg-slate-900/60">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-14">GRP</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Resolución</th>
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-24">Tipo de ingreso</th>
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-24">Asignar</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro"
            class="border-b border-slate-700/60 transition-colors hover:bg-white/[0.025]"
            :class="[
              i % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20',
              estaMarcada(m) ? 'bg-amber-500/[0.06]' : '',
              !coincideGestion(m) && resolucionActiva ? 'bg-red-500/[0.03]' : ''
            ]"
          >
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-500 font-medium text-[13px] tabular-nums">{{ m.nro }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded"
                    :class="tipoGestion(m.gestion).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGestion(m.gestion).dot"/>
                {{ m.gestion }}
              </span>
              <span
                v-if="resolucionActiva && !coincideGestion(m)"
                class="inline-flex items-center gap-1 ml-1.5 text-[0.65rem] font-medium text-red-400"
                title="La gestión de esta materia no coincide con el periodo de la resolución seleccionada"
              >
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide"
                    :class="tipoGrp(m.plan).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(m.plan).dot"/>
                {{ tipoGrp(m.plan).label }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-200 font-medium">{{ m.materia }}</td>

            <!-- Compartido -->
            <td class="px-4 py-3">
              <span v-if="m.compartido"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300">
                Compartido
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución (existente, ya designada) -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-emerald-400 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- Tipo de designacion-->
            <td class="px-4 py-3">
              <select
                v-model="m.tipo_ingreso"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-200 text-xs px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">-- Seleccionar --</option>
                <option value="ACEFALIA">ACEFALIA</option>
                <option value="TEMPORAL">TEMPORAL</option>
                <option value="TITULAR">TITULAR</option>
                <option value="EXAMEN SUFICIENCIA">EXAMEN SUFICIENCIA</option>
                <option value="EXAMEN COMPETENCIA">EXAMEN COMPETENCIA</option>
              </select>
            </td>

            <!-- Asignar / Editar -->
            <td class="px-4 py-3 text-center">
              <!-- Caso A: ya tiene resolución asignada -> ícono de editar -->
              <button
                v-if="m.resolucion"
                type="button"
                :disabled="!resolucionActiva"
                title="Esta materia ya tiene una resolución asignada. Click para reasignar."
                class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150
                       disabled:opacity-30 disabled:cursor-not-allowed
                       bg-transparent border-slate-600 text-slate-400 hover:border-sky-500/60 hover:text-sky-400"
                @click="onClickEditar(m)"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M12 20h9"/>
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
              </button>

              <!-- Caso B: sin resolución previa -> checkbox normal -->
              <button
                v-else
                type="button"
                :disabled="!resolucionActiva"
                :title="checkTitle(m)"
                class="inline-flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150
                       disabled:opacity-30 disabled:cursor-not-allowed"
                :class="estaMarcada(m)
                  ? 'bg-amber-500 border-amber-500 text-slate-900'
                  : 'bg-transparent border-slate-600 text-transparent hover:border-amber-500/60'"
                @click="onClickAsignar(m)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </button>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 text-xs text-slate-500 text-right">
      {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
    </div>

    <!-- ══════════ Modal de confirmación: gestión no coincide con la resolución ══════════ -->
    <div
      v-if="modalMateria"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
      @click.self="cerrarModal"
    >
      <div class="w-full max-w-sm rounded-xl border border-slate-700 bg-slate-800 shadow-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-700 flex items-start gap-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-red-500/15">
            <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-100 m-0">La gestión no coincide con la resolución</h3>
            <p class="text-xs text-slate-400 m-0 mt-1">
              La materia es de la gestión <span class="font-semibold text-slate-300">{{ modalMateria.gestion }}</span>,
              pero la resolución seleccionada corresponde a
              <span class="font-semibold text-slate-300">{{ resolucionActiva?.anio }}/{{ resolucionActiva?.periodo }}</span>.
              ¿Igual querés asignarla?
            </p>
          </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 bg-slate-900/30">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-300 border border-slate-700 hover:bg-white/5 transition-colors"
            @click="cerrarModal"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-400 text-slate-900 transition-colors"
            @click="confirmarPesarDeAviso"
          >
            Asignar de todos modos
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  materias: { type: Array, default: () => [] },
  resolucionActiva: { type: Object, default: null },
  marcadasKeys: { type: Array, default: () => [] }, // keys ya marcadas globalmente (de este u otro docente)
  docenteCod: { type: [Number, String], default: null },
})

const emit = defineEmits(['toggle'])

function keyDe(m) {
  return `${props.docenteCod}__${m.plan}__${m.materia}__${m.grp}__${m.gestion}`
}

function estaMarcada(m) {
  return props.marcadasKeys.includes(keyDe(m))
}

function checkTitle(m) {
  if (!props.resolucionActiva) return 'Seleccioná primero una resolución'
  return estaMarcada(m) ? 'Quitar asignación' : 'Asignar resolución a esta materia'
}

// ─── Validación: gestión de la materia vs año/periodo de la resolución ───
// La gestión viene como "2024/2", "2024/1", "2024/Verano", etc.
// Comparamos año y periodo contra resolucionActiva.anio / .periodo.
function coincideGestion(m) {
  if (!props.resolucionActiva) return true
  const partes = String(m.gestion ?? '').split('/')
  const anioMateria = partes[0]?.trim()
  const periodoMateria = partes.slice(1).join('/').trim()

  const anioResolucion = String(props.resolucionActiva.anio ?? '').trim()
  const periodoResolucion = String(props.resolucionActiva.periodo ?? '').trim()

  if (anioResolucion && anioMateria !== anioResolucion) return false
  if (periodoResolucion && periodoMateria !== periodoResolucion) return false
  return true
}

// ─── Flujo de click ───────────────────────────────────────────────
// Si ya está marcada (se quiere desmarcar) o la gestión coincide: directo.
// Si la gestión NO coincide y se está intentando marcar: pedir confirmación.
const modalMateria = ref(null)

function onClickAsignar(m) {
  if (!props.resolucionActiva) return

  // Desmarcar siempre es directo, sin advertencia.
  if (estaMarcada(m)) {
    emit('toggle', m)
    return
  }

  if (!coincideGestion(m)) {
    modalMateria.value = m
    return
  }

  emit('toggle', m)
}

// Reasignar una materia que ya tiene resolución previa: sobrescribe directo,
// pero igual respeta la validación de gestión antes de confirmar.
function onClickEditar(m) {
  if (!props.resolucionActiva) return

  if (!coincideGestion(m)) {
    modalMateria.value = m
    return
  }

  emit('toggle', m)
}

function cerrarModal() {
  modalMateria.value = null
}

function confirmarPesarDeAviso() {
  if (modalMateria.value) {
    emit('toggle', modalMateria.value)
  }
  modalMateria.value = null
}

const tipoGestion = (gestion) => {
  if (gestion?.includes('Verano'))
    return { class: 'bg-orange-500/10 text-orange-400', dot: 'bg-orange-400' }
  if (gestion?.includes('Invierno'))
    return { class: 'bg-sky-500/10 text-sky-400', dot: 'bg-sky-400' }
  return { class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
}

const GRP_MAP = {
  '059801': { label: 'CON', class: 'bg-violet-500/15 text-violet-300', dot: 'bg-violet-400' },
  '109401': { label: 'ADM', class: 'bg-blue-500/15 text-blue-300',     dot: 'bg-blue-400'   },
  '125091': { label: 'COM', class: 'bg-green-500/15 text-green-300',   dot: 'bg-green-400'  },
  '126091': { label: 'FIN', class: 'bg-teal-500/15 text-teal-300',     dot: 'bg-teal-400'   },
  '089801': { label: 'ECO', class: 'bg-amber-500/15 text-amber-300',   dot: 'bg-amber-400'  },
}

const tipoGrp = (plan) =>
  GRP_MAP[plan] ?? { label: plan, class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
</script>