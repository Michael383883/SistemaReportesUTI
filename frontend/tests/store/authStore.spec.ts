import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/modules/auth/store/authStore'

vi.mock('@/modules/auth/services/authService', () => ({
    authService: {
        login:  vi.fn(),
        logout: vi.fn(),
        me:     vi.fn(),
    },
}))

vi.mock('@/router', () => ({
    default: { push: vi.fn() },
}))

import { authService } from '@/modules/auth/services/authService'
import router from '@/router'

const USERS = [
    { email: 'admin@umss.edu',      password: 'Admin1234!',    role: 'admin',               name: 'Administrador UTI'    },
    { email: 'secretaria@umss.edu', password: 'Secret1234!',   role: 'secretaria',          name: 'Secretaría FCE'       },
    { email: 'talleres@umss.edu',   password: 'Talleres1234!', role: 'secretaria_talleres', name: 'Secretaría Talleres'  },
    { email: 'uti@umss.edu',        password: 'Uti12345!',     role: 'uti',                 name: 'Técnico UTI'          },
]

describe('authStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        localStorage.getItem.mockReturnValue(null)
    })

    // ── estado inicial ───────────────────────────────────────────────────────
    it('estado inicial correcto cuando no hay token en localStorage', () => {
        const store = useAuthStore()
        expect(store.user).toBeNull()
        expect(store.token).toBeNull()
        expect(store.isAuthenticated).toBe(false)
        expect(store.loading).toBe(false)
        expect(store.error).toBeNull()
    })

    it('carga el token de localStorage al inicializar', () => {
        localStorage.getItem.mockReturnValue('stored-token')
        const store = useAuthStore()
        expect(store.token).toBe('stored-token')
        expect(store.isAuthenticated).toBe(true)
    })

    // ── login ────────────────────────────────────────────────────────────────
    describe('login()', () => {
        it.each(USERS)('login exitoso para $role — persiste token y user', async ({ email, password, role, name }) => {
            const token = `token-${role}`
            authService.login.mockResolvedValueOnce({ token, user: { email, role, name } })

            const store = useAuthStore()
            await store.login({ email, password })

            expect(store.token).toBe(token)
            expect(store.user.role).toBe(role)
            expect(store.isAuthenticated).toBe(true)
            expect(store.error).toBeNull()
            expect(localStorage.setItem).toHaveBeenCalledWith('token', token)
        })

        it.each(USERS)('computed de rol correcto para $role', async ({ email, password, role }) => {
            authService.login.mockResolvedValueOnce({ token: 'tok', user: { email, role } })
            const store = useAuthStore()
            await store.login({ email, password })

            expect(store.userRole).toBe(role)
            expect(store.isAdmin).toBe(role === 'admin')
            expect(store.isSecretaria).toBe(role === 'secretaria')
            expect(store.isUTI).toBe(role === 'uti')
        })

        it('activa loading durante la request y lo desactiva al terminar', async () => {
            let resolveFn
            authService.login.mockReturnValueOnce(new Promise(r => { resolveFn = r }))
            const store = useAuthStore()

            const promise = store.login({ email: 'admin@umss.edu', password: 'Admin1234!' })
            expect(store.loading).toBe(true)

            resolveFn({ token: 'tok', user: { role: 'admin' } })
            await promise
            expect(store.loading).toBe(false)
        })

        it('clasifica error 401 correctamente', async () => {
            authService.login.mockRejectedValueOnce({ response: { status: 401, data: {} } })
            const store = useAuthStore()

            await expect(store.login({ email: 'x@x.com', password: 'wrong' })).rejects.toBeDefined()
            expect(store.error).toBe('Credenciales incorrectas.')
            expect(store.loading).toBe(false)
        })

        it('clasifica error de red (sin response)', async () => {
            authService.login.mockRejectedValueOnce({ code: 'ERR_NETWORK', message: 'Network Error' })
            const store = useAuthStore()

            await expect(store.login({ email: 'admin@umss.edu', password: 'Admin1234!' })).rejects.toBeDefined()
            expect(store.error).toContain('No se puede conectar')
        })

        it('clasifica error 429 (rate limit)', async () => {
            authService.login.mockRejectedValueOnce({ response: { status: 429, data: {} } })
            const store = useAuthStore()

            await expect(store.login({ email: 'admin@umss.edu', password: 'Admin1234!' })).rejects.toBeDefined()
            expect(store.error).toContain('Demasiados intentos')
        })
    })

    // ── logout ───────────────────────────────────────────────────────────────
    describe('logout()', () => {
        it('limpia sesión y redirige a /login', async () => {
            authService.logout.mockResolvedValueOnce(undefined)
            const store = useAuthStore()
            store.token = 'tok'
            store.user  = { role: 'admin' }

            await store.logout()

            expect(store.token).toBeNull()
            expect(store.user).toBeNull()
            expect(localStorage.removeItem).toHaveBeenCalledWith('token')
            expect(router.push).toHaveBeenCalledWith('/login')
        })

        it('limpia sesión aunque el backend devuelva 401', async () => {
            authService.logout.mockRejectedValueOnce({ response: { status: 401 } })
            const store = useAuthStore()
            store.token = 'expired-token'

            await store.logout()

            expect(store.token).toBeNull()
            expect(router.push).toHaveBeenCalledWith('/login')
        })
    })

    // ── fetchMe ──────────────────────────────────────────────────────────────
    describe('fetchMe()', () => {
        it('no llama al service si no hay token', async () => {
            const store = useAuthStore()
            await store.fetchMe()
            expect(authService.me).not.toHaveBeenCalled()
        })

        it.each(USERS)('hidrata user para rol $role', async ({ email, role }) => {
            authService.me.mockResolvedValueOnce({ user: { email, role } })
            const store = useAuthStore()
            store.token = 'tok'

            await store.fetchMe()

            expect(store.user.role).toBe(role)
        })

        it('limpia sesión si /me devuelve error', async () => {
            authService.me.mockRejectedValueOnce(new Error('Unauthorized'))
            const store = useAuthStore()
            store.token = 'tok'
            store.user  = { role: 'admin' }

            await store.fetchMe()

            expect(store.token).toBeNull()
            expect(store.user).toBeNull()
        })
    })

    // ── clearError ───────────────────────────────────────────────────────────
    it('clearError() limpia el error del store', () => {
        const store = useAuthStore()
        store.error = 'Credenciales incorrectas.'
        store.clearError()
        expect(store.error).toBeNull()
    })
})