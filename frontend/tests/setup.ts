import { vi } from 'vitest'

// Mock vue-router composables globally
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: vi.fn() }),
    useRoute: () => ({
        query: {},
        params: {},
        name: 'docentes',
    }),
}))

// Mock axios globally if tests use it directly
vi.mock('axios', () => {
    const get = vi.fn()
    const post = vi.fn()
    const put = vi.fn()
    const del = vi.fn()

    return {
        __esModule: true,
        default: { get, post, put, delete: del },
        get,
        post,
        put,
        delete: del,
    }
})

// Provide a basic global mock for localStorage
Object.defineProperty(window, 'localStorage', {
    value: {
        getItem: vi.fn(),
        setItem: vi.fn(),
        removeItem: vi.fn(),
    },
    writable: true,
})

// Ensure global fetch is available in jsdom environment
if (!window.fetch) {
    window.fetch = vi.fn()
}
