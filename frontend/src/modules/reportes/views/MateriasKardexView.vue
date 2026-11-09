<template>
  <div class="px-4 py-0 max-w-3xl">
    <div class="mb-7">
      <h1 class="text-[20px] font-semibold text-gray-900">Reporte de Materia y Grupo</h1>
      <p class="text-[13px] text-slate-400 m-0">Historial de docentes que dictaron una materia y grupo</p>
    </div>

    <div class="rounded-xl border border-slate-700 bg-slate-800 px-5 pt-5 pb-8 flex flex-wrap items-end gap-3">

      <!-- BUSCADOR ÚNICO: materia (código o nombre) + "-grupo" opcional -->
      <div ref="buscadorRef" class="flex flex-col gap-1.5 relative">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-300">
          Materia y grupo
        </label>

        <div class="relative">
          <input
            ref="materiaRef"
            v-model="busqueda"
            type="text"
            maxlength="70"
            placeholder="Ej: 1301033-34 o Administración-34"
            autocomplete="off"
            role="combobox"
            :aria-expanded="abierto"
            aria-autocomplete="list"
            class="w-96 max-w-full bg-white border rounded-lg text-slate-800 text-sm pl-3 pr-8 py-2 outline-none focus:ring-2"
            :class="materia
              ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500/20'
              : 'border-slate-300 focus:border-amber-500 focus:ring-amber-500/20'"
            @input="onInput"
            @focus="onFocus"
            @keydown.down.prevent="mover(1)"
            @keydown.up.prevent="mover(-1)"
            @keydown.enter.prevent="onEnter"
            @keydown.esc="abierto = false"
          />

          <button
            v-if="busqueda"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 bg-transparent border-none cursor-pointer p-0 leading-none"
            title="Limpiar"
            @mousedown.prevent="limpiar"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <!-- Materia elegida -->
        <p
          v-if="materia"
          class="absolute top-full left-0 mt-1 text-[11px] text-emerald-400 m-0 whitespace-nowrap"
        >
          Seleccionada: {{ materia }} · {{ plan }} · Grupo {{ grupo }}
        </p>
        <p
          v-else-if="!abierto"
          class="absolute top-full left-0 mt-1 text-[11px] text-slate-400 m-0 whitespace-nowrap"
        >
          Tip: escribe el grupo después de un guion, ej. 1301033-34
        </p>

        <!-- DROPDOWN -->
        <ul
          v-if="abierto"
          role="listbox"
          class="absolute top-full left-0 mt-1 w-[34rem] max-w-[90vw] max-h-72 overflow-auto z-50 bg-white border border-slate-200 rounded-lg shadow-xl list-none p-1 m-0"
        >
          <li v-if="cargando" class="px-3 py-2 text-xs text-slate-400">Buscando...</li>
          <li v-else-if="errorBusqueda" class="px-3 py-2 text-xs text-red-500">{{ errorBusqueda }}</li>
          <li v-else-if="!resultados.length" class="px-3 py-2 text-xs text-slate-400">
            No hay materias con ese código, nombre o grupo
          </li>

          <li
            v-for="(m, i) in resultados"
            :key="`${m.codigo}-${m.cod_plan}-${m.grupo}`"
            role="option"
            :aria-selected="i === indice"
            class="flex items-center gap-2 px-3 py-2 rounded-md cursor-pointer text-sm text-slate-800"
            :class="i === indice ? 'bg-amber-50' : 'hover:bg-slate-50'"
            @mousedown.prevent="seleccionar(m)"
            @mouseenter="indice = i"
          >
            <span
              class="shrink-0 text-[11px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded"
              :title="m.nombre_plan"
            >
              {{ m.plan }}
            </span>
            <span class="shrink-0 font-mono text-xs text-slate-500">{{ m.codigo }}</span>
            <span class="truncate flex-1">{{ m.nombre }}</span>
            <span class="shrink-0 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">
              Grupo {{ m.grupo }}
            </span>
            <span class="shrink-0 text-[10px] text-slate-400">
              {{ m.anio_desde }}–{{ m.anio_hasta }}
            </span>
          </li>
        </ul>
      </div>

      <button
        ref="botonRef"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-semibold bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-900 transition-all cursor-pointer border-none shadow-lg shadow-amber-500/20 disabled:opacity-60 disabled:cursor-not-allowed"
        :disabled="!materia || !String(grupo).trim()"
        @click="irAlReporte"
      >
        Generar reporte kardex materia
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL
const router = useRouter()

// ── Estado ───────────────────────────────────────────────────────────────
const materia = ref('')      // código elegido
const plan = ref('')
const grupo = ref('')        // grupo elegido
const busqueda = ref('')     // texto del input: "1301033-34"
const baseResultados = ref([])   // resultados del servidor (sin filtrar por grupo)
const abierto = ref(false)
const cargando = ref(false)
const errorBusqueda = ref('')
const indice = ref(-1)

const materiaRef = ref(null)
const botonRef = ref(null)
const buscadorRef = ref(null)

let timer = null
let ultimaPeticion = 0
let textoCargado = ''            // último texto de materia consultado al servidor
const cache = new Map()          // texto -> lista

// ── Separar "materia - grupo" ────────────────────────────────────────────
// "1301033-34"        -> { texto: "1301033", grupoFiltro: "34" }
// "Administración -3" -> { texto: "Administración", grupoFiltro: "3" }
// "1301033-"          -> { texto: "1301033", grupoFiltro: "" }
const parsear = (raw) => {
  const m = raw.match(/^(.*?)\s*-\s*([A-Za-z0-9]{0,10})$/)
  if (m) return { texto: m[1].trim(), grupoFiltro: m[2].trim() }
  return { texto: raw.trim(), grupoFiltro: '' }
}

const parsed = computed(() => parsear(busqueda.value))

const norm = (g) => String(g).trim().toLowerCase().replace(/^0+(?=.)/, '') // "01" -> "1"

// Resultados visibles = resultados del servidor filtrados por grupo (en memoria)
const resultados = computed(() => {
  const f = parsed.value.grupoFiltro
  if (!f) return baseResultados.value
  const nf = norm(f)
  const lista = baseResultados.value.filter(
    (m) => norm(m.grupo).startsWith(nf) || String(m.grupo).trim().startsWith(f)
  )
  // los grupos exactos primero
  return lista.sort((a, b) => Number(norm(b.grupo) === nf) - Number(norm(a.grupo) === nf))
})

// ── Búsqueda: GET /api/materias/buscar?q=... ─────────────────────────────
const buscarMaterias = async (texto) => {
  if (cache.has(texto)) return cache.get(texto)
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE}/api/materias/buscar`, {
    params: { q: texto },
    headers: { Authorization: `Bearer ${token}` },
  })
  const lista = Array.isArray(data) ? data : (data.data ?? [])
  cache.set(texto, lista)
  return lista
}

const onInput = () => {
  materia.value = ''
  plan.value = ''
  grupo.value = ''
  errorBusqueda.value = ''
  clearTimeout(timer)

  const { texto } = parsed.value
  if (texto.length < 2) {
    ultimaPeticion++
    baseResultados.value = []
    textoCargado = ''
    abierto.value = false
    cargando.value = false
    return
  }

  abierto.value = true
  indice.value = 0

  // Si solo cambió el grupo, NO se vuelve a llamar al servidor
  if (texto === textoCargado) {
    cargando.value = false
    return
  }

  timer = setTimeout(async () => {
    const id = ++ultimaPeticion
    cargando.value = true
    try {
      const lista = await buscarMaterias(texto)
      if (id !== ultimaPeticion) return
      baseResultados.value = lista
      textoCargado = texto
      indice.value = 0
    } catch (e) {
      if (id !== ultimaPeticion) return
      console.error('[buscarMaterias]', e.response?.data || e.message)
      baseResultados.value = []
      errorBusqueda.value = 'No se pudo buscar. Intenta de nuevo.'
    } finally {
      if (id === ultimaPeticion) cargando.value = false
    }
  }, 200)
}

const onFocus = () => {
  if (!materia.value && parsed.value.texto.length >= 2) abierto.value = true
}

// ── Selección ────────────────────────────────────────────────────────────
const seleccionar = async (m) => {
  const escribioGrupo = !!parsed.value.grupoFiltro

  materia.value = String(m.codigo).trim()
  plan.value = m.plan
  grupo.value = String(m.grupo).trim()
  busqueda.value = `${materia.value}-${grupo.value}`
  textoCargado = ''          // fuerza nueva búsqueda si el usuario edita
  abierto.value = false

  // Si el usuario ya escribió el grupo, va directo al reporte
  if (escribioGrupo) {
    irAlReporte()
    return
  }
  await nextTick()
  botonRef.value?.focus()
}

const limpiar = () => {
  clearTimeout(timer)
  ultimaPeticion++
  busqueda.value = ''
  materia.value = ''
  plan.value = ''
  grupo.value = ''
  baseResultados.value = []
  textoCargado = ''
  abierto.value = false
  materiaRef.value?.focus()
}

const mover = (d) => {
  if (!resultados.value.length) return
  abierto.value = true
  indice.value = (indice.value + d + resultados.value.length) % resultados.value.length
}

const onEnter = () => {
  if (abierto.value && resultados.value[indice.value]) {
    seleccionar(resultados.value[indice.value])
  } else if (materia.value) {
    irAlReporte()
  }
}

// ── Navegación al reporte ────────────────────────────────────────────────
const irAlReporte = () => {
  const g = String(grupo.value).trim()
  if (!materia.value || !g) return
  router.push({
    name: 'reporte',
    query: { modo: 'materia', materia: materia.value, grupo: g },
  })
}

// ── Cerrar dropdown al hacer click fuera ─────────────────────────────────
const clickFuera = (e) => {
  if (buscadorRef.value && !buscadorRef.value.contains(e.target)) abierto.value = false
}

onMounted(() => {
  materiaRef.value?.focus()
  document.addEventListener('click', clickFuera)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', clickFuera)
  clearTimeout(timer)
})
</script>