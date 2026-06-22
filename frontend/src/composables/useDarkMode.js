import { ref, watch } from 'vue'

const STORAGE_KEY = 'theme'

// Estado compartido entre todos los componentes que usen el composable
const darkMode = ref(getInitialPreference())

function getInitialPreference() {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === 'dark') return true
    if (saved === 'light') return false
    // Sin preferencia guardada: usar la del sistema operativo
    return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function applyClass(isDark) {
    const html = document.documentElement
    if (isDark) {
        html.classList.add('dark')
    } else {
        html.classList.remove('dark')
    }
}

// Aplica el estado inicial inmediatamente (antes de montar componentes)
applyClass(darkMode.value)

// Cada vez que cambie, persiste y actualiza la clase en <html>
watch(darkMode, (isDark) => {
    applyClass(isDark)
    localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light')
})

export function useDarkMode() {
    function toggleDarkMode() {
        darkMode.value = !darkMode.value
    }

    function setDarkMode(value) {
        darkMode.value = value
    }

    return {
        darkMode,
        toggleDarkMode,
        setDarkMode,
    }
}