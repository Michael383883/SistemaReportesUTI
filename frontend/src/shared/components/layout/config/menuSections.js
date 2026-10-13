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
                    { to: '/reportes/docentes', label: 'Kardex Docente' },
                    { to: '/materias-kardex', label: 'Kardex Materia' },

                    { to: '/reportes/edicion-tipo-ingreso', label: 'Asignar Modalidad' },
                    { to: '/reportes/docentes-titulo', label: 'Docentes con Título' },
                ],
            },

            // ── Documentos y Referencia unificados: "Digitalización" y
            // "Documentos Adjuntos" eran, en la práctica, el mismo flujo
            // (subir / listar / asignar) al 95%. Se dejan como un solo
            // ítem de menú; "Resolución" pasa a llamarse "Referencia".
            {
                label: 'Documentos',
                icon: FileText,
                children: [
                    { to: '/clasificaciones/nueva', label: 'Subir Documento' },
                    { to: '/clasificaciones', label: 'Listado de Documentos' },
                    { to: '/clasificaciones/listar-por-documento', label: 'Listado de Archivos' },
                    { to: '/resoluciones/subir', label: 'Subir Referencia' },
                    { to: '/resoluciones/listado', label: 'Lista de Referencias' },
                    { to: '/resoluciones/asignar', label: 'Asignar Solo a Kardex' },
                    { to: '/clasificaciones/subir-titulo', label: 'Subir Título' },
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
                    { to: '/config-bd', label: 'Base de Datos' },
                    { to: '/periodos-academicos', label: 'Períodos Académicos' },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────
    // ROL: SECRETARIA
    // ─────────────────────────────────────────
    {
        label: 'Secretaría',
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
        label: 'UTI',
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
            { to: '/secretariaTalleres/estudiante', label: 'Estudiantes', icon: GraduationCap },
        ],
    },
]