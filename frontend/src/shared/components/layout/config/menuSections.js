import {
    LayoutDashboard,
    BarChart2,
    Upload,
    FileText,
    CalendarDays,
    ClipboardCheck,
    Settings,
    GraduationCap,
    BookOpen,
} from 'lucide-vue-next'

// Data pura del menú lateral, agrupada por rol.
// Si mañana se agrega un rol nuevo o una sección nueva,
// este es el único archivo que hay que tocar.
export const menuSections = [
    // ─────────────────────────────────────────
    // ROL: ADMIN
    // ─────────────────────────────────────────
    {
        label: 'Admin',
        roles: ['admin'],
        items: [
            { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },

            {
                label: 'Reportes',
                icon: BarChart2,
                children: [
                    { to: '/reportes/docentes', label: 'Kardex docente' },
                    { to: '/reportes/edicion-tipo-ingreso', label: 'Asignar Modalidad' },
                ],
            },

            {
                label: 'Digitalización',
                icon: Upload,
                children: [
                    { to: '/resoluciones/subir', label: 'Subir Resolución' },
                    { to: '/resoluciones/listado', label: 'Lista de Resoluciones' },
                    { to: '/resoluciones/asignar', label: 'Asignar Resolución' },
                ],
            },

            {
                label: 'Documetos Adjuntos',
                icon: FileText,
                children: [
                    { to: '/clasificaciones/nueva', label: 'Subir Documento' },
                    { to: '/clasificaciones', label: 'Listado de documentos' },
                    
                ],
            },

            {
                label: 'Horarios',
                icon: CalendarDays,
                children: [
                    { to: '/reporte-horario-completo', label: 'Horario Completo' },
                    { to: '/reporte-horario-resumen', label: 'Horario Resumen' },
                    { to: '/reporte-horario-resumen-dos', label: 'Horario Resumen Dos' },
                ],
            },

            { to: '/inscritos', label: 'Inscritos', icon: ClipboardCheck },

            {
                label: 'Administración',
                icon: Settings,
                children: [
                    { to: '/usuarios', label: 'Usuarios' },
                    { to: '/config-bd', label: 'Base de datos' },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────
    // ROL: SECRETARIA
    // ─────────────────────────────────────────
    {
        label: 'Secretaria',
        roles: ['secretaria'],
        items: [
            { to: '/secretaria/dashboard', label: 'Dashboard', icon: LayoutDashboard },
            { to: '/secretaria/estudiantes', label: 'Estudiantes', icon: GraduationCap },
            { to: '/secretaria/docentes', label: 'Docentes', icon: BookOpen },
        ],
    },

    // ─────────────────────────────────────────
    // ROL: UTI
    // ─────────────────────────────────────────
    {
        label: 'Secretaria',
        roles: ['uti'],
        items: [
            { to: '/secretaria/dashboard', label: 'Dashboard', icon: LayoutDashboard },
            { to: '/secretaria/estudiantes', label: 'Estudiantes', icon: GraduationCap },
            { to: '/secretaria/docentes', label: 'Docentes', icon: BookOpen },
        ],
    },

    // ─────────────────────────────────────────
    // ROL: SECRETARIA TALLERES
    // ─────────────────────────────────────────
    {
        label: 'Sec. Talleres',
        roles: ['secretaria_talleres'],
        items: [
            { to: '/secretariaTalleres/dashboard', label: 'Dashboard', icon: LayoutDashboard },
            { to: '/secretariaTalleres/docentes', label: 'Docentes', icon: BookOpen },
            { to: '/secretariaTalleres/estudiante', label: 'Estudiante', icon: GraduationCap },
        ],
    },
]
