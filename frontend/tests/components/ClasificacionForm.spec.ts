import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ClasificacionForm from '../../src/modules/resolucionesExtra/components/ClasificacionForm.vue'

const fetchDocentesMock = vi.fn()
const selectDocenteMock = vi.fn()
const clearSelectionMock = vi.fn()

const searchQuery = ref('')
const dropdownOpen = ref(false)
const filteredDocentes = ref([])
const selectedDocente = ref(null)
const loadingDocentes = ref(false)

vi.mock('../../src/modules/resolucionesExtra/composables/useDocentesReportes', () => ({
    useDocentesReportes: () => ({
        loading: loadingDocentes,
        searchQuery,
        dropdownOpen,
        filteredDocentes,
        selectedDocente,
        fetchDocentes: fetchDocentesMock,
        selectDocente: selectDocenteMock,
        clearSelection: clearSelectionMock,
    }),
}))

vi.mock('../../src/modules/resolucionesExtra/components/BuscadorMaterias.vue', () => ({
    default: {
        name: 'BuscadorMaterias',
        template: '<div />',
        emits: ['agregar-materia'],
    },
}))

vi.mock('../../src/modules/resolucionesExtra/components/BuscadorReferencias.vue', () => ({
    default: {
        name: 'BuscadorReferencias',
        template: '<div />',
        emits: ['agregar-referencia'],
    },
}))

describe('ClasificacionForm.vue', () => {
    beforeEach(() => {
        fetchDocentesMock.mockClear()
        selectDocenteMock.mockClear()
        clearSelectionMock.mockClear()

        searchQuery.value = ''
        dropdownOpen.value = false
        filteredDocentes.value = []
        selectedDocente.value = null
        loadingDocentes.value = false
    })

    it('emits guardar with a copy of the form when the form is valid', async () => {
        const wrapper = mount(ClasificacionForm, {
            props: {
                initial: {
                    cod_docente: 'DOC001',
                    categoria: 'Docentes Titulares',
                    nivel: 'PRIMER NIVEL',
                    gestion: '2024',
                    periodo: '1',
                    detalle_general: 'RES-001',
                    observacion: 'Observación de prueba',
                    observacion2: 'Detalle adicional',
                    materias: [],
                    referencias: [],
                },
            },
        })

        expect(fetchDocentesMock).toHaveBeenCalledTimes(1)

        const saveButton = wrapper.findAll('button').find((button) => button.text().includes('Guardar clasificación'))
        expect(saveButton).toBeTruthy()

        await saveButton?.trigger('click')

        const emitted = wrapper.emitted('guardar')
        expect(emitted).toHaveLength(1)
        expect(emitted?.[0]?.[0]).toMatchObject({
            cod_docente: 'DOC001',
            categoria: 'Docentes Titulares',
            nivel: 'PRIMER NIVEL',
            gestion: '2024',
            detalle_general: 'RES-001',
            materias: [],
            referencias: [],
        })
    })
})
