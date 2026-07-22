// utils/planStyles.js
// Helpers compartidos entre EstudiantesPage, MateriaGrupoCard, etc.
// para no repetir los mismos mapas de abreviaturas/colores en cada componente.

export const ABREVS = {
    '109401': 'Adm. Empresas',
    '125091': 'Ing. Comercial',
    '089801': 'Cont. Pública',
    '126091': 'Ing. Financiera',
    '059801': 'Economía',
}

export const COLORES = {
    '109401': 'bg-blue-100 text-blue-700',
    '125091': 'bg-emerald-100 text-emerald-700',
    '089801': 'bg-orange-100 text-orange-700',
    '126091': 'bg-violet-100 text-violet-700',
    '059801': 'bg-rose-100 text-rose-700',
}

export const abreviarPlan = plan => ABREVS[plan] || plan
export const colorPlan = plan => COLORES[plan] || 'bg-slate-100 text-slate-700'