export const ROLES = {
  admin: { label: 'Administrador', badgeClass: 'bg-[#eef2ff] text-navy' },
  secretaria: { label: 'Secretaría', badgeClass: 'bg-[#fff8ee] text-[#8B5E20]' },
  secretaria_talleres: { label: 'Secretaría Talleres', badgeClass: 'bg-[#e0f2fe] text-[#0369a1]' },
  uti: { label: 'UTI', badgeClass: 'bg-red-50 text-red-700' },
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