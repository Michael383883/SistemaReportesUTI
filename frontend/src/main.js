
//version1
// import { createApp } from 'vue'
// import { createPinia } from 'pinia'
// import './style.css'
// import App from './App.vue'
// import router from './router'

// createApp(App).use(createPinia()).use(router).mount('#app')


//version2
// import { createApp } from 'vue'
// import { createPinia } from 'pinia'
// import App from './App.vue'
// import router from './router'
// import './assets/main.css'
// import './style.css'

// const app = createApp(App)

// app.use(createPinia())
// app.use(router)

// app.mount('#app')

//version3
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import '@/styles/design-system.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Restaurar sesión antes de montar la app
import { useAuthStore } from '@/modules/auth/store/authStore'

const authStore = useAuthStore()

authStore.fetchMe().finally(() => {
    app.mount('#app')
})
