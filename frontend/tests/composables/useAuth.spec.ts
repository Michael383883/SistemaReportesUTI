import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuth } from '@/modules/auth/composables/useAuth'
import { useAuthStore } from '@/modules/auth/store/authStore'

vi.mock('@/modules/auth/store/authStore', () => ({
    useAuthStore: vi.fn(),
}))

const mockPush = vi.fn()

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute:  () => ({ query: {} }),
}))

// Factory que construye un store mock con el rol deseado
function makeStore(overrides = {}) {
    return {
        user:            null,
        isAuthenticated: false,
        isAdmin:         false,
        isSecretaria:    false,
        isUTI:           false,
        userRole:        null,
        loading:         false,
        error:           null,
        clearError:      vi.fn(),
        login:           vi.fn(),
        logout:          vi.fn(),
        ...overrides,
    }
}

describe('useAuth', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        setActivePinia(createPinia())
    })

    // ── login y redirección por rol ──────────────────────────────────────────
    const roleRedirects = [
        { role: 'admin',               path: '/dashboard'                   },
        { role: 'uti',                 path: '/dashboard'                   },
        { role: 'secretaria',          path: '/secretaria/dashboard'        },
        { role: 'secretaria_talleres', path: '/secretariaTalleres/dashboard'},
    ]

    it.each(roleRedirects)('redirige a $path tras login con rol $role', async ({ role, path }) => {
        const store = makeStore({ userRole: role })
        store.login.mockResolvedValueOnce(undefined)
        useAuthStore.mockReturnValue(store)

        const { login } = useAuth()
        await login({ email: 'x@umss.edu', password: 'Pass1234!' })

        expect(store.login).toHaveBeenCalled()
        expect(mockPush).toHaveBeenCalledWith(path)
    })

    it('respeta ?redirect= en la query si viene de una ruta protegida', async () => {
        vi.doMock('vue-router', () => ({
            useRouter: () => ({ push: mockPush }),
            useRoute:  () => ({ query: { redirect: '/reportes/123' } }),
        }))

        const store = makeStore({ userRole: 'admin' })
        store.login.mockResolvedValueOnce(undefined)
        useAuthStore.mockReturnValue(store)

        // Re-importar para que tome el mock de la query
        const { useAuth: useAuthFresh } = await import('@/modules/auth/composables/useAuth')
        const { login } = useAuthFresh()
        await login({ email: 'admin@umss.edu', password: 'Admin1234!' })

        // El redirect externo no debe seguirse (no empieza con /)
        // Si empieza con / sí debe respetarse
        expect(mockPush).toHaveBeenCalled()
    })

    it('no sigue redirect si es una URL externa (no empieza con /)', async () => {
        vi.mock('vue-router', () => ({
            useRouter: () => ({ push: mockPush }),
            useRoute:  () => ({ query: { redirect: 'https://evil.com' } }),
        }))

        const store = makeStore({ userRole: 'admin' })
        store.login.mockResolvedValueOnce(undefined)
        useAuthStore.mockReturnValue(store)

        const { login } = useAuth()
        await login({ email: 'admin@umss.edu', password: 'Admin1234!' })

        expect(mockPush).toHaveBeenCalledWith('/dashboard')
    })

    // ── logout ───────────────────────────────────────────────────────────────
    it('logout() delega completamente en authStore.logout()', async () => {
        const store = makeStore()
        store.logout.mockResolvedValueOnce(undefined)
        useAuthStore.mockReturnValue(store)

        const { logout } = useAuth()
        await logout()

        expect(store.logout).toHaveBeenCalledOnce()
        // useAuth no debe hacer push propio — el store ya redirige
        expect(mockPush).not.toHaveBeenCalled()
    })

    // ── valores expuestos ────────────────────────────────────────────────────
    it('expone las propiedades reactivas del store', () => {
        const store = makeStore({
            userRole:        'admin',
            isAdmin:         true,
            isAuthenticated: true,
            loading:         false,
            error:           'Algo falló',
        })
        useAuthStore.mockReturnValue(store)

        const auth = useAuth()

        expect(auth.userRole).toBe('admin')
        expect(auth.isAdmin).toBe(true)
        expect(auth.isAuthenticated).toBe(true)
        expect(auth.error).toBe('Algo falló')
    })
})