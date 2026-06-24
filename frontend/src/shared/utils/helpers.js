//shared/utils/helpers.js
export const ROLES = {
  admin: { label: 'Administrador', badgeClass: 'text-indigo-300' },
  secretaria: { label: 'Secretaría', badgeClass: 'text-amber-300' },
  secretaria_talleres: { label: 'Secretaría Talleres', badgeClass: 'text-sky-300' },
  uti: { label: 'UTI', badgeClass: 'text-red-300' }
}

export function getRoleLabel(role) {
  return ROLES[role]?.label ?? role ?? '—'
}

export function getRoleBadgeClass(role) {
  return ROLES[role]?.badgeClass ?? 'bg-gray-100 text-gray-500'
}

export function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric',
  }).format(new Date(dateStr))
}

export function getInitials(name = '') {
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}