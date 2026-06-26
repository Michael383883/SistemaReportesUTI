import { describe, it, expect, vi, beforeEach, afterEach, afterAll } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import { ref } from 'vue'
import axios from 'axios'
import DocentesView from '../../src/modules/docentes/views/DocentesView.vue'
import * as useDocentesModule from '../../src/modules/docentes/composables/useDocentes.js'

const pushMock = vi.fn()

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    useRoute: () => ({ query: {}, params: {}, name: 'docentes' }),
}))

const useDocentesSpy = vi.spyOn(useDocentesModule, 'useDocentes')

const createViewWrapper = () =>
    shallowMount(DocentesView, {
        global: {
            stubs: {
                DocenteSearch: { name: 'DocenteSearch', template: '<div/>' },
                Transition: { name: 'Transition', template: '<div><slot/></div>' },
            },
        },
    })

describe('DocentesView.vue', () => {
    let fetchDocentesMock: ReturnType<typeof vi.fn>
    let selectDocenteMock: ReturnType<typeof vi.fn>
    let clearSelectionMock: ReturnType<typeof vi.fn>
    let docentes: ReturnType<typeof ref>
    let loading: ReturnType<typeof ref>
    let error: ReturnType<typeof ref>
    let searchQuery: ReturnType<typeof ref>
    let selectedDocente: ReturnType<typeof ref>
    let filteredDocentes: ReturnType<typeof ref>

    beforeEach(() => {
        fetchDocentesMock = vi.fn()
        selectDocenteMock = vi.fn()
        clearSelectionMock = vi.fn()
        docentes = ref([])
        loading = ref(false)
        error = ref(null)
        searchQuery = ref('')
        selectedDocente = ref(null)
        filteredDocentes = ref([])

        useDocentesSpy.mockImplementation(() => ({
            docentes,
            loading,
            error,
            searchQuery,
            filteredDocentes,
            selectedDocente,
            fetchDocentes: fetchDocentesMock,
            selectDocente: selectDocenteMock,
            clearSelection: clearSelectionMock,
        }))
    })

    afterEach(() => {
        vi.clearAllMocks()
        useDocentesSpy.mockReset()
    })

    afterAll(() => {
        useDocentesSpy.mockRestore()
    })

    it('calls fetchDocentes on mount and shows empty selection state', () => {
        const wrapper = shallowMount(DocentesView, {
            global: {
                stubs: {
                    DocenteSearch: { name: 'DocenteSearch', template: '<div/>' },
                    Transition: { name: 'Transition', template: '<div><slot/></div>' },
                },
            },
        })

        expect(fetchDocentesMock).toHaveBeenCalledTimes(1)
        expect(wrapper.text()).toContain('Seleccioná un docente')
    })

    it('navigates to report when selectedDocente exists and button is clicked', async () => {
        selectedDocente.value = {
            nombres: 'Ana',
            apellidos: 'Díaz',
            codigo: 'DOC123',
        }

        const wrapper = shallowMount(DocentesView, {
            global: {
                stubs: {
                    DocenteSearch: { name: 'DocenteSearch', template: '<div/>' },
                    Transition: { name: 'Transition', template: '<div><slot/></div>' },
                },
            },
        })

        const button = wrapper.find('button[title="Generar reporte de materias dictadas"]')
        await button.trigger('click')

        expect(pushMock).toHaveBeenCalledWith({ name: 'reporte', query: { codigo: 'DOC123' } })
    })

    it('renders an error message and retries fetchDocentes when retry button is clicked', async () => {
        error.value = 'Error al cargar docentes'

        const wrapper = shallowMount(DocentesView, {
            global: {
                stubs: {
                    DocenteSearch: { name: 'DocenteSearch', template: '<div/>' },
                    Transition: { name: 'Transition', template: '<div><slot/></div>' },
                },
            },
        })

        expect(wrapper.text()).toContain('Error al cargar docentes')

        const retryButton = wrapper.find('button[title="Reintentar"]')
        if (retryButton.exists()) {
            await retryButton.trigger('click')
        } else {
            await wrapper.findAll('button').find((button) => button.text().includes('Reintentar'))?.trigger('click')
        }

        expect(fetchDocentesMock).toHaveBeenCalledTimes(2)
    })

    it('emits select and clear events correctly from DocenteSearch stub', async () => {
        const wrapper = shallowMount(DocentesView, {
            global: {
                stubs: {
                    DocenteSearch: { name: 'DocenteSearch', template: '<div/>' },
                    Transition: { name: 'Transition', template: '<div><slot/></div>' },
                },
            },
        })

        const child = wrapper.findComponent({ name: 'DocenteSearch' })
        expect(child.exists()).toBe(true)

        await child.vm.$emit('select', { id: 1, nombres: 'Luis', apellidos: 'Pérez' })
        await child.vm.$emit('clear')

        expect(selectDocenteMock).toHaveBeenCalledWith({ id: 1, nombres: 'Luis', apellidos: 'Pérez' })
        expect(clearSelectionMock).toHaveBeenCalled()
    })
})

describe('useDocentes composable', () => {
    beforeEach(() => {
        vi.restoreAllMocks()
        vi.clearAllMocks()
    })

    it('filters docentes by nombre, apellido and codigo', () => {
        const { docentes, filteredDocentes, searchQuery } = useDocentesModule.useDocentes()

        docentes.value = [
            { nombres: 'Ana', apellidos: 'Díaz', codigo: 'doc1' },
            { nombres: 'Luis', apellidos: 'Gómez', codigo: 'doc2' },
        ]

        searchQuery.value = 'ana'
        expect(filteredDocentes.value).toHaveLength(1)
        expect(filteredDocentes.value[0].codigo).toBe('doc1')

        searchQuery.value = 'gómez'
        expect(filteredDocentes.value[0].codigo).toBe('doc2')

        searchQuery.value = 'doc1'
        expect(filteredDocentes.value).toHaveLength(1)
        expect(filteredDocentes.value[0].codigo).toBe('doc1')
    })

    it('selectDocente and clearSelection update state correctly', () => {
        const { selectedDocente, searchQuery, selectDocente, clearSelection } = useDocentesModule.useDocentes()

        selectDocente({ nombres: 'Ana', apellidos: 'Díaz' })
        expect(selectedDocente.value).toEqual({ nombres: 'Ana', apellidos: 'Díaz' })
        expect(searchQuery.value).toBe('Ana Díaz')

        clearSelection()
        expect(selectedDocente.value).toBeNull()
        expect(searchQuery.value).toBe('')
    })

    it('normalizes backend fields and stores docentes on success', async () => {
        const getMock = vi.spyOn(axios, 'get').mockResolvedValueOnce({
            data: [
                { ID: 1, NOMBRES: 'Luis', APELLIDOS: 'Gómez', CODIGO: 'DOC123' },
            ],
        })

        vi.spyOn(window.localStorage, 'getItem').mockReturnValue('fake-token')

        const { docentes, fetchDocentes, loading, error } = useDocentesModule.useDocentes()
        await fetchDocentes()

        expect(getMock).toHaveBeenCalled()
        expect(docentes.value[0]).toMatchObject({ nombres: 'Luis', apellidos: 'Gómez', codigo: 'DOC123' })
        expect(loading.value).toBe(false)
        expect(error.value).toBeNull()
    })

    it('sets an error message when fetchDocentes fails', async () => {
        const rejected = { response: { data: { message: 'No autorizado' } } }
        vi.spyOn(axios, 'get').mockRejectedValueOnce(rejected)
        vi.spyOn(window.localStorage, 'getItem').mockReturnValue('fake-token')

        const { fetchDocentes, error, loading } = useDocentesModule.useDocentes()
        await fetchDocentes()

        expect(error.value).toBe('No autorizado')
        expect(loading.value).toBe(false)
    })
})
