// Formato visual compartido entre todas las tablas de materias.
// Antes vivía duplicado (idéntico) dentro de ReporteTabla.vue y ReporteTablaCom.vue.
export function useTablaFormato() {
    function tipoGestion(gestion) {
        if (gestion?.includes('Verano'))
            return { class: 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400', dot: 'bg-orange-500 dark:bg-orange-400' }
        if (gestion?.includes('Invierno'))
            return { class: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400', dot: 'bg-sky-500 dark:bg-sky-400' }
        return { class: 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300', dot: 'bg-slate-400' }
    }

    const GRP_MAP = {
        '059801': { label: 'CCP', class: 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300', dot: 'bg-violet-500 dark:bg-violet-400' },
        '109401': { label: 'ADM', class: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300', dot: 'bg-blue-500 dark:bg-blue-400' },
        '125091': { label: 'COM', class: 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-300', dot: 'bg-green-500 dark:bg-green-400' },
        '126091': { label: 'FIN', class: 'bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300', dot: 'bg-teal-500 dark:bg-teal-400' },
        '089801': { label: 'ECO', class: 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300', dot: 'bg-amber-500 dark:bg-amber-400' },
    }

    function tipoGrp(plan) {
        return GRP_MAP[plan] ?? { label: plan, class: 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300', dot: 'bg-slate-400' }
    }

    return { tipoGestion, tipoGrp }
}