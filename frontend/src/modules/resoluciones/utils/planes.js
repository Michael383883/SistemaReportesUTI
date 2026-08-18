// src/features/resoluciones/utils/planes.js  (ajustá la ruta a tu estructura)
export const GRP_MAP = {
    '059801': { label: 'CON', class: 'bg-violet-100 text-violet-700', dot: 'bg-violet-500' },
    '109401': { label: 'ADM', class: 'bg-sky-100 text-sky-700', dot: 'bg-sky-500' },
    '125091': { label: 'COM', class: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
    '126091': { label: 'FIN', class: 'bg-teal-100 text-teal-700', dot: 'bg-teal-500' },
    '089801': { label: 'ECO', class: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
}

export function abrevPlan(plan) {
    return GRP_MAP[plan]?.label ?? plan
}