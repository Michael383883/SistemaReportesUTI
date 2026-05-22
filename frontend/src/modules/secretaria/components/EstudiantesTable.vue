<!-- src/modules/secretaria/components/EstudiantesTable.vue -->
<template>
  <div class="et-wrapper">
    <!-- Cabecera -->
    <div class="et-header">
      <span class="et-count">{{ rows.length }} estudiante(s)</span>
      <button class="et-export-btn" @click="$emit('export')">
        <Download style="width:14px;height:14px;" />
        Exportar CSV
      </button>
    </div>

    <!-- Tabla -->
    <div class="et-scroll">
      <table class="et-table">
        <thead>
          <tr>
            <th v-for="col in cols" :key="col.key" @click="sortBy(col.key)" class="et-th">
              <span>{{ col.label }}</span>
              <ChevronUp
                v-if="sort.key === col.key"
                :style="`transform:rotate(${sort.asc ? 0 : 180}deg);transition:.2s`"
                style="width:12px;height:12px;margin-left:4px;"
              />
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-if="loading">
            <tr v-for="n in 8" :key="n">
              <td v-for="c in cols" :key="c.key"><span class="et-skel" /></td>
            </tr>
          </template>
          <template v-else-if="sorted.length === 0">
            <tr>
              <td :colspan="cols.length" class="et-empty">
                <SearchX style="width:36px;height:36px;color:#d1d5db;margin-bottom:6px;" />
                <span>Sin resultados</span>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="row in paginated"
              :key="row.id"
              class="et-row"
            >
              <td>{{ row.sis }}</td>
              <td class="et-nombre">{{ row.nombre }}</td>
              <td>{{ row.año }}</td>
              <td>{{ row.periodo }}</td>
              <td>{{ row.materia }}</td>
              <td>{{ row.grupo }}</td>
              <td>
                <span class="et-badge" :class="`et-badge--${row.estado === 'Activo' ? 'ok' : 'off'}`">
                  {{ row.estado }}
                </span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="et-pager">
      <span class="et-pager-info">Página {{ page }} / {{ totalPages }}</span>
      <div class="et-pager-btns">
        <button :disabled="page <= 1" @click="page--" class="et-pg-btn">‹</button>
        <button
          v-for="p in visiblePages" :key="p"
          @click="page = p"
          class="et-pg-btn"
          :class="{ 'et-pg-btn--active': p === page }"
        >{{ p }}</button>
        <button :disabled="page >= totalPages" @click="page++" class="et-pg-btn">›</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Download, ChevronUp, SearchX } from 'lucide-vue-next'

const props = defineProps({
  rows:    { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
})
defineEmits(['export'])

const PAGE_SIZE = 12
const page  = ref(1)
const sort  = ref({ key: 'nombre', asc: true })

const cols = [
  { key: 'sis',     label: 'SIS'     },
  { key: 'nombre',  label: 'Nombre'  },
  { key: 'año',     label: 'Año'     },
  { key: 'periodo', label: 'Periodo' },
  { key: 'materia', label: 'Materia' },
  { key: 'grupo',   label: 'Grupo'   },
  { key: 'estado',  label: 'Estado'  },
]

function sortBy(key) {
  if (sort.value.key === key) sort.value.asc = !sort.value.asc
  else { sort.value.key = key; sort.value.asc = true }
  page.value = 1
}

const sorted = computed(() => {
  const { key, asc } = sort.value
  return [...props.rows].sort((a, b) => {
    const v = String(a[key]).localeCompare(String(b[key]), undefined, { numeric: true })
    return asc ? v : -v
  })
})

const totalPages  = computed(() => Math.max(1, Math.ceil(sorted.value.length / PAGE_SIZE)))
const paginated   = computed(() => sorted.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))
const visiblePages = computed(() => {
  const total = totalPages.value
  const cur   = page.value
  const range = []
  for (let p = Math.max(1, cur - 2); p <= Math.min(total, cur + 2); p++) range.push(p)
  return range
})
</script>

<style scoped>
.et-wrapper { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.et-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid #f3f4f6; }
.et-count { font-size:12px; color:#6b7280; font-weight:500; }
.et-export-btn {
  display:flex; align-items:center; gap:6px;
  padding:6px 14px; border-radius:7px;
  background:#081F33; color:#fff; font-size:12px; font-weight:500;
  border:none; cursor:pointer; transition:background .15s;
}
.et-export-btn:hover { background:#0d2e4a; }
.et-scroll { overflow-x:auto; }
.et-table { width:100%; border-collapse:collapse; font-size:13px; }
.et-th {
  padding:10px 14px; text-align:left; font-size:11px; font-weight:600;
  color:#6b7280; text-transform:uppercase; letter-spacing:.04em;
  background:#f9fafb; border-bottom:1px solid #e5e7eb;
  cursor:pointer; white-space:nowrap; user-select:none;
}
.et-th:hover { background:#f3f4f6; }
.et-row td { padding:10px 14px; border-bottom:1px solid #f3f4f6; color:#374151; vertical-align:middle; }
.et-row:last-child td { border-bottom:none; }
.et-row:hover td { background:#f9fafb; }
.et-nombre { font-weight:500; color:#111827 !important; }
.et-badge {
  display:inline-block; padding:2px 8px; border-radius:20px;
  font-size:11px; font-weight:600;
}
.et-badge--ok  { background:#d1fae5; color:#065f46; }
.et-badge--off { background:#fee2e2; color:#991b1b; }
.et-empty { text-align:center; padding:40px; color:#9ca3af; display:flex; flex-direction:column; align-items:center; }
.et-skel {
  display:block; height:14px; width:80%; border-radius:4px;
  background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
  background-size:200% 100%; animation:shimmer 1.2s infinite;
}
@keyframes shimmer { 0%{background-position:200% 0}100%{background-position:-200% 0} }
.et-pager { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; border-top:1px solid #f3f4f6; }
.et-pager-info { font-size:12px; color:#9ca3af; }
.et-pager-btns { display:flex; gap:4px; }
.et-pg-btn {
  width:28px; height:28px; border-radius:6px; border:1px solid #e5e7eb;
  background:#fff; font-size:12px; cursor:pointer; transition:background .15s;
}
.et-pg-btn:hover:not(:disabled) { background:#f3f4f6; }
.et-pg-btn:disabled { opacity:.35; cursor:default; }
.et-pg-btn--active { background:#081F33 !important; color:#fff; border-color:#081F33; }
</style>