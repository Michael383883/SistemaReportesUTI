export const ROLES = {
    admin: { label: 'Administrador', color: 'bg-navy text-white' },
    secretaria: { label: 'Secretaría', color: 'bg-gold/20 text-gold-dark' },
    uti: { label: 'UTI', color: 'bg-red-50 text-red-700' },
}

export function getRoleLabel(role) {
    return ROLES[role]?.label ?? role
}

export function getRoleColor(role) {
    return ROLES[role]?.color ?? 'bg-gray-100 text-gray-600'
}

export function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Intl.DateTimeFormat('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric',
    }).format(new Date(dateStr))
}
