export const norm = (s) =>
    String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase()

export function fmtFecha(v) {
    if (!v) return ''
    const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/)
    return m ? `${m[3]}/${m[2]}/${m[1]}` : String(v)
}

// Colores fijos por tipo, con variante oscura. Para agregar otro tipo, añade una línea con su
// nombre normalizado (minúsculas y sin tildes).
const TIPOS = {
    'certificado de titularidad': {
        chip: 'bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-300',
        bar: 'bg-teal-500',
        soft: 'bg-teal-50/50 dark:bg-teal-500/5',
        border: 'border-teal-200 dark:border-teal-500/30',
    },
    'diploma academico': {
        chip: 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
        bar: 'bg-blue-500',
        soft: 'bg-blue-50/50 dark:bg-blue-500/5',
        border: 'border-blue-200 dark:border-blue-500/30',
    },
    'diploma de titularidad': {
        chip: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300',
        bar: 'bg-indigo-500',
        soft: 'bg-indigo-50/50 dark:bg-indigo-500/5',
        border: 'border-indigo-200 dark:border-indigo-500/30',
    },
    'diplomado': {
        chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
        bar: 'bg-emerald-500',
        soft: 'bg-emerald-50/50 dark:bg-emerald-500/5',
        border: 'border-emerald-200 dark:border-emerald-500/30',
    },
    'doctorado': {
        chip: 'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300',
        bar: 'bg-violet-500',
        soft: 'bg-violet-50/50 dark:bg-violet-500/5',
        border: 'border-violet-200 dark:border-violet-500/30',
    },
    'especialidad': {
        chip: 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
        bar: 'bg-amber-500',
        soft: 'bg-amber-50/50 dark:bg-amber-500/5',
        border: 'border-amber-200 dark:border-amber-500/30',
    },
    'maestria': {
        chip: 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/20 dark:text-fuchsia-300',
        bar: 'bg-fuchsia-500',
        soft: 'bg-fuchsia-50/50 dark:bg-fuchsia-500/5',
        border: 'border-fuchsia-200 dark:border-fuchsia-500/30',
    },
}

// Para tipos nuevos que no estén arriba: color automático y estable
const FALLBACK = [
    {
        chip: 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300',
        bar: 'bg-sky-500',
        soft: 'bg-sky-50/50 dark:bg-sky-500/5',
        border: 'border-sky-200 dark:border-sky-500/30',
    },
    {
        chip: 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
        bar: 'bg-rose-500',
        soft: 'bg-rose-50/50 dark:bg-rose-500/5',
        border: 'border-rose-200 dark:border-rose-500/30',
    },
    {
        chip: 'bg-lime-100 text-lime-800 dark:bg-lime-500/20 dark:text-lime-300',
        bar: 'bg-lime-500',
        soft: 'bg-lime-50/50 dark:bg-lime-500/5',
        border: 'border-lime-200 dark:border-lime-500/30',
    },
    {
        chip: 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300',
        bar: 'bg-orange-500',
        soft: 'bg-orange-50/50 dark:bg-orange-500/5',
        border: 'border-orange-200 dark:border-orange-500/30',
    },
    {
        chip: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300',
        bar: 'bg-cyan-500',
        soft: 'bg-cyan-50/50 dark:bg-cyan-500/5',
        border: 'border-cyan-200 dark:border-cyan-500/30',
    },
    {
        chip: 'bg-pink-100 text-pink-800 dark:bg-pink-500/20 dark:text-pink-300',
        bar: 'bg-pink-500',
        soft: 'bg-pink-50/50 dark:bg-pink-500/5',
        border: 'border-pink-200 dark:border-pink-500/30',
    },
]

export function colorTipo(tipo) {
    const k = norm(tipo)
    if (TIPOS[k]) return TIPOS[k]
    let h = 0
    for (let i = 0; i < k.length; i++) h = (h * 31 + k.charCodeAt(i)) >>> 0
    return FALLBACK[h % FALLBACK.length]
}