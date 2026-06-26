import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import LoginView from '@/modules/auth/views/LoginView.vue'

// El store se usa indirectamente a través del composable
vi.mock('@/modules/auth/composables/useAuth', () => ({
    useAuth: vi.fn(),
}))

vi.mock('@/modules/auth/store/authStore', () => ({
    useAuthStore: vi.fn(),
}))

import { useAuth } from '@/modules/auth/composables/useAuth'
import { useAuthStore } from '@/modules/auth/store/authStore'

// Mock del logo SVG para que no rompa el import en tests
vi.mock('@/assets/img/SIA-UTI-logo.svg', () => ({ default: 'logo.svg' }))

function makeAuthStore(overrides = {}) {
    return {
        loading: false,
        error: null,
        clearError: vi.fn(),
        ...overrides,
    }
}

function makeAuth(overrides = {}) {
    return {
        login: vi.fn(),
        logout: vi.fn(),
        clearError: vi.fn(),
        ...overrides,
    }
}

async function mountLogin(authOverrides = {}, storeOverrides = {}) {
    const auth = makeAuth(authOverrides)
    const store = makeAuthStore(storeOverrides)
    useAuth.mockReturnValue(auth)
    useAuthStore.mockReturnValue(store)

    const wrapper = mount(LoginView, {
        global: { plugins: [createPinia()] },
    })
    return { wrapper, auth, store }
}

describe('LoginView', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    // ── renderizado ──────────────────────────────────────────────────────────
    it('renderiza los campos email y contraseña', async () => {
        const { wrapper } = await mountLogin()
        expect(wrapper.find('#email').exists()).toBe(true)
        expect(wrapper.find('#password').exists()).toBe(true)
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
    })

    it('muestra los tres roles disponibles', async () => {
        const { wrapper } = await mountLogin()
        const text = wrapper.text()
        expect(text).toContain('Administrador')
        expect(text).toContain('Secretaría')
        expect(text).toContain('UTI')
    })

    // ── validación del formulario ────────────────────────────────────────────
    it('muestra error si se envía vacío', async () => {
        const { wrapper } = await mountLogin()
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('El correo es requerido')
        expect(wrapper.text()).toContain('La contraseña es requerida')
    })

    it('muestra error con formato de correo inválido', async () => {
        const { wrapper } = await mountLogin()
        await wrapper.find('#email').setValue('no-es-un-email')
        await wrapper.find('#password').setValue('Admin1234!')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('Formato de correo inválido')
    })

    it('muestra error si la contraseña tiene menos de 8 caracteres', async () => {
        const { wrapper } = await mountLogin()
        await wrapper.find('#email').setValue('admin@umss.edu')
        await wrapper.find('#password').setValue('corta')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('Mínimo 8 caracteres')
    })

    // ── submit exitoso ───────────────────────────────────────────────────────
    const USERS = [
        { email: 'admin@umss.edu', password: 'Admin1234!' },
        { email: 'secretaria@umss.edu', password: 'Secret1234!' },
        { email: 'talleres@umss.edu', password: 'Talleres1234!' },
        { email: 'uti@umss.edu', password: 'Uti12345!' },
    ]

    it.each(USERS)('llama a login con las credenciales de $email', async ({ email, password }) => {
        const loginMock = vi.fn().mockResolvedValueOnce(undefined)
        const { wrapper } = await mountLogin({ login: loginMock })

        await wrapper.find('#email').setValue(email)
        await wrapper.find('#password').setValue(password)
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(loginMock).toHaveBeenCalledWith({ email, password })
    })

    // ── toggle de contraseña ─────────────────────────────────────────────────
    it('toggle muestra/oculta la contraseña', async () => {
        const { wrapper } = await mountLogin()
        const input = wrapper.find('#password')
        const toggle = wrapper.find('button[type="button"][aria-label]')

        expect(input.attributes('type')).toBe('password')
        await toggle.trigger('click')
        expect(input.attributes('type')).toBe('text')
        await toggle.trigger('click')
        expect(input.attributes('type')).toBe('password')
    })

    // ── error del store ──────────────────────────────────────────────────────
    it('muestra el error del store cuando existe', async () => {
        const { wrapper } = await mountLogin({}, { error: 'Credenciales incorrectas.' })
        expect(wrapper.text()).toContain('Credenciales incorrectas.')
    })

    it('no llama a login si authStore.loading es true (previene doble envío)', async () => {
        const loginMock = vi.fn()
        const { wrapper } = await mountLogin({ login: loginMock }, { loading: true })

        await wrapper.find('#email').setValue('admin@umss.edu')
        await wrapper.find('#password').setValue('Admin1234!')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(loginMock).not.toHaveBeenCalled()
    })
})