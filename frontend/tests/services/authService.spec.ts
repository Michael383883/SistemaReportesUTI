import { describe, it, expect, vi, beforeEach } from 'vitest'
import { authService } from '@/modules/auth/services/authService'

// Mock del cliente axios configurado
vi.mock('@/shared/services/api', () => ({
    default: {
        post: vi.fn(),
        get: vi.fn(),
    },
}))

import api from '@/shared/services/api'

const USERS = [
    { email: 'admin@umss.edu', password: 'Admin1234!', role: 'admin' },
    { email: 'secretaria@umss.edu', password: 'Secret1234!', role: 'secretaria' },
    { email: 'talleres@umss.edu', password: 'Talleres1234!', role: 'secretaria_talleres' },
    { email: 'uti@umss.edu', password: 'Uti12345!', role: 'uti' },
]

describe('authService', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        localStorage.setItem.mockClear?.()
    })

    // ── login ────────────────────────────────────────────────────────────────
    describe('login()', () => {
        it.each(USERS)('persiste token para el rol $role', async ({ email, password, role }) => {
            const token = `fake-token-${role}`
            api.post.mockResolvedValueOnce({ data: { token, user: { email, role } } })

            const result = await authService.login({ email, password })

            expect(api.post).toHaveBeenCalledWith('/api/auth/login', { email, password })
            expect(localStorage.setItem).toHaveBeenCalledWith('token', token)
            expect(result.token).toBe(token)
            expect(result.user.role).toBe(role)
        })

        it('propaga el error si la request falla', async () => {
            api.post.mockRejectedValueOnce({ response: { status: 401, data: { message: 'Credenciales incorrectas.' } } })

            await expect(authService.login({ email: 'x@x.com', password: 'wrong' })).rejects.toMatchObject({
                response: { status: 401 },
            })
        })
    })

    // ── logout ───────────────────────────────────────────────────────────────
    describe('logout()', () => {
        it('llama POST /api/auth/logout sin limpiar localStorage', async () => {
            api.post.mockResolvedValueOnce({})

            await authService.logout()

            expect(api.post).toHaveBeenCalledWith('/api/auth/logout')
            // El service NO debe borrar el token — eso es responsabilidad del store
            expect(localStorage.removeItem).not.toHaveBeenCalled()
        })
    })

    // ── me ───────────────────────────────────────────────────────────────────
    describe('me()', () => {
        it.each(USERS)('devuelve el usuario autenticado para rol $role', async ({ email, role }) => {
            api.get.mockResolvedValueOnce({ data: { user: { email, role } } })

            const result = await authService.me()

            expect(api.get).toHaveBeenCalledWith('/api/auth/me')
            expect(result.user.role).toBe(role)
        })
    })
})