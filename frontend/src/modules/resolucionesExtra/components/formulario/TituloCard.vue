<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-800">
      <span class="text-[13px] font-bold text-white">Título académico</span>
      <button type="button" @click="$emit('cerrar')" class="text-slate-300 hover:text-white">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="p-4 grid grid-cols-2 gap-4">

      <!-- Docente: siempre refleja el docente actual de Datos generales -->
      <div class="col-span-2">
        <label class="text-[12px] font-medium text-gray-700">Docente</label>
        <div class="mt-1 text-[13px] px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
          {{ nombreDocenteActual || 'Selecciona un docente en "Datos generales"' }}
        </div>
      </div>

      <!-- Tipo de título: combobox (seleccionar, buscar o crear uno nuevo), en mayúsculas -->
      <div class="md:col-span-2 relative">
        <label class="text-[12px] font-medium text-gray-700">Tipo de título</label>
        <div class="relative mt-1">
          <input
            v-model="tipoTituloModel"
            type="text"
            placeholder="Todos / escribe para buscar o crear..."
            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-[13px] uppercase placeholder:normal-case"
            @focus="tipoOpen = true"
            @input="tipoOpen = true"
            @blur="onBlurTipo"
          />
          <svg v-if="loadingTipos" class="w-3.5 h-3.5 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>

        <ul v-if="tipoOpen" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-auto text-[13px]">
          <li @mousedown.prevent="elegirTipo('')" class="px-3 py-2 hover:bg-amber-50 cursor-pointer text-gray-500 italic">
            Todos
          </li>
          <li v-for="opcion in tiposFiltrados" :key="opcion"
              @mousedown.prevent="elegirTipo(opcion)"
              class="px-3 py-2 hover:bg-amber-50 cursor-pointer">
            {{ opcion }}
          </li>
          <li v-if="tiposFiltrados.length === 0 && tipoTituloModel.trim()"
              @mousedown.prevent="elegirTipo(tipoTituloModel)"
              class="px-3 py-2 hover:bg-amber-50 cursor-pointer text-amber-600 font-medium">
            + Crear "{{ tipoTituloModel.trim() }}"
          </li>
          <li v-else-if="tiposFiltrados.length === 0" class="px-3 py-2 text-gray-400 italic">
            Sin coincidencias
          </li>
        </ul>
      </div>
      <div>
        <label class="text-[12px] font-medium text-gray-700">Nombre del título *</label>
        <input v-model="form.titulo.nombre_titulo" type="text" placeholder="Ej. Diplomado en Docencia Universitaria"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px]" />
      </div>

      <!-- Universidad: combobox con universidades comunes de Bolivia, en mayúsculas -->
      <div class="relative">
        <label class="text-[12px] font-medium text-gray-700">Universidad</label>
        <div class="relative mt-1">
          <input
            v-model="universidadModel"
            type="text"
            placeholder="Selecciona o escribe otra..."
            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-[13px] uppercase placeholder:normal-case"
            @focus="universidadOpen = true"
            @input="universidadOpen = true"
            @blur="onBlurUniversidad"
          />
          <svg class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>

        <ul v-if="universidadOpen" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto text-[13px]">
          <li v-for="uni in universidadesFiltradas" :key="uni"
              @mousedown.prevent="elegirUniversidad(uni)"
              class="px-3 py-2 hover:bg-amber-50 cursor-pointer">
            {{ uni }}
          </li>
          <li v-if="universidadesFiltradas.length === 0" class="px-3 py-2 text-gray-400 italic">
            Sin coincidencias — se usará lo que escribiste
          </li>
        </ul>
      </div>

      <!-- País: combobox con lista de países, en mayúsculas -->
      <div class="relative">
        <label class="text-[12px] font-medium text-gray-700">País</label>
        <div class="relative mt-1">
          <input
            v-model="paisModel"
            type="text"
            placeholder="Escribe para buscar..."
            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-[13px] uppercase placeholder:normal-case"
            @focus="paisOpen = true"
            @input="paisOpen = true"
            @blur="onBlurPais"
          />
          <svg class="w-4 h-4 absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>

        <ul v-if="paisOpen && paisesFiltrados.length > 0" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-auto text-[13px]">
          <li v-for="pais in paisesFiltrados" :key="pais"
              @mousedown.prevent="elegirPais(pais)"
              class="px-3 py-2 hover:bg-amber-50 cursor-pointer">
            {{ pais }}
          </li>
        </ul>
      </div>

      <div>
        <label class="text-[12px] font-medium text-gray-700">Fecha del título</label>
        <input v-model="form.titulo.fecha_titulo" type="date"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px]" />
      </div>

      <div>
        <label class="text-[12px] font-medium text-gray-700">Número</label>
        <input v-model="form.titulo.numero" type="text"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-[13px]" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch  } from 'vue'
import { useTiposTitulo } from '../../composables/useTiposTitulo'

const props = defineProps({
  form: { type: Object, required: true },
  selectedDocente: { type: Object, default: null },
})
defineEmits(['cerrar'])

const nombreDocenteActual = computed(() => {
  if (!props.selectedDocente) return ''
  return `${props.selectedDocente.apellidos ?? ''} ${props.selectedDocente.nombres ?? ''}`.trim()
})

if (!props.form.titulo) {
  props.form.titulo = {
    tipo_titulo: '',
    nombre_titulo: '',
    universidad: '',
    pais: '',
    fecha_titulo: '',
    numero: '',
  }
}

// ─── Helper: computed que fuerza mayúsculas en el modelo ───
function campoMayusculas(campo) {
  return computed({
    get: () => props.form.titulo[campo] || '',
    set: (val) => { props.form.titulo[campo] = (val || '').toUpperCase() },
  })
}

// ─── Combobox: Tipo de título ───
// Usa el composable compartido: trae los tipos reales desde el backend
// y permite que, si el usuario escribe uno nuevo, quede disponible de
// inmediato en todos los componentes que usen useTiposTitulo (por
// ejemplo, en el filtro del reporte de docentes).
const { tipos: TIPOS_TITULO, loading: loadingTipos, cargarTipos, agregarTipoLocal } = useTiposTitulo()

const tipoOpen = ref(false)
const tipoTituloModel = campoMayusculas('tipo_titulo')

const tiposFiltrados = computed(() => {
  const q = tipoTituloModel.value.trim()
  if (!q) return TIPOS_TITULO.value
  return TIPOS_TITULO.value.filter(t => t.includes(q))
})

function elegirTipo(opcion) {
  tipoTituloModel.value = opcion
  if (opcion) agregarTipoLocal(opcion)
  tipoOpen.value = false
}

function onBlurTipo() {
  // Si el usuario escribió un tipo que no está en la lista, lo agregamos
  // al vuelo para que quede disponible de inmediato y pueda categorizar
  // rápido sin tener que recargar la página.
  const valor = tipoTituloModel.value.trim()
  if (valor) agregarTipoLocal(valor)
  setTimeout(() => { tipoOpen.value = false }, 150)
}

// ─── Combobox: Universidad (comunes de Bolivia) ───
const UNIVERSIDADES = [
  'UNIVERSIDAD MAYOR DE SAN SIMÓN',
  'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
  'UNIVERSIDAD AUTÓNOMA GABRIEL RENÉ MORENO',
  'UNIVERSIDAD AUTÓNOMA TOMÁS FRÍAS',
  'UNIVERSIDAD TÉCNICA DE ORURO',
  'UNIVERSIDAD AUTÓNOMA JUAN MISAEL SARACHO',
  'UNIVERSIDAD PRIVADA BOLIVIANA',
  'UNIVERSIDAD CATÓLICA BOLIVIANA "SAN PABLO"',
  'UNIVERSIDAD NUR',
  'UNIVERSIDAD AMAZÓNICA DE PANDO',
]
const universidadOpen = ref(false)
const universidadModel = campoMayusculas('universidad')

const universidadesFiltradas = computed(() => {
  const q = universidadModel.value.trim()
  if (!q) return UNIVERSIDADES
  return UNIVERSIDADES.filter(u => u.includes(q))
})

function elegirUniversidad(uni) {
  universidadModel.value = uni
  universidadOpen.value = false
}
function onBlurUniversidad() {
  setTimeout(() => { universidadOpen.value = false }, 150)
}

// ─── Combobox: País ───
const PAISES = [
  'BOLIVIA', 'ARGENTINA', 'BRASIL', 'CHILE', 'COLOMBIA', 'COSTA RICA', 'CUBA',
  'ECUADOR', 'EL SALVADOR', 'ESPAÑA', 'ESTADOS UNIDOS', 'GUATEMALA', 'HONDURAS',
  'MÉXICO', 'NICARAGUA', 'PANAMÁ', 'PARAGUAY', 'PERÚ', 'PUERTO RICO',
  'REPÚBLICA DOMINICANA', 'URUGUAY', 'VENEZUELA',
  'ALEMANIA', 'CANADÁ', 'FRANCIA', 'ITALIA', 'PORTUGAL', 'REINO UNIDO',
  'CHINA', 'JAPÓN', 'COREA DEL SUR', 'INDIA', 'RUSIA',
]
const paisOpen = ref(false)
const paisModel = campoMayusculas('pais')

const paisesFiltrados = computed(() => {
  const q = paisModel.value.trim()
  if (!q) return PAISES
  return PAISES.filter(p => p.includes(q))
})

function elegirPais(pais) {
  paisModel.value = pais
  paisOpen.value = false
}
function onBlurPais() {
  setTimeout(() => { paisOpen.value = false }, 150)
}

onMounted(() => {
  cargarTipos()
})

// ─── Sincronización: "Tipo o Número de Documento" ← "Tipo de título" ───
// Solo se autocompleta mientras el usuario NO haya escrito nada distinto
// a mano en "Tipo o Número de Documento". Usamos `ultimoValorSincronizado`
// para saber si el valor actual de tipo_documento fue puesto por este mismo
// watcher (y por tanto se puede seguir actualizando letra por letra) o si
// el usuario lo modificó manualmente (y por tanto ya no se debe tocar más).
const ultimoValorSincronizado = ref('')

watch(
  () => props.form.titulo?.tipo_titulo,
  (nuevoTipo) => {
    const tipoDocActual = props.form.tipo_documento || ''
    const estaVacio = tipoDocActual.trim() === ''

    // Comparamos SIN recortar espacios, para que un espacio intermedio
    // (al escribir un tipo de título de varias palabras) no rompa la
    // comparación con el último valor que este mismo watcher sincronizó.
    const noTocadoPorUsuario =
      estaVacio || tipoDocActual === ultimoValorSincronizado.value

    if (noTocadoPorUsuario) {
      props.form.tipo_documento = nuevoTipo
      ultimoValorSincronizado.value = nuevoTipo
    }
  }
)

</script>