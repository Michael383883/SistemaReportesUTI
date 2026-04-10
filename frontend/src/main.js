
//version1
// import { createApp } from 'vue'
// import { createPinia } from 'pinia'
// import './style.css'
// import App from './App.vue'
// import router from './router'

// createApp(App).use(createPinia()).use(router).mount('#app')


//version2
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
