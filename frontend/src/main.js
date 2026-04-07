// import { createApp } from 'vue'
// import './style.css'
// import App from './App.vue'
// import router from './router'

// App.use(router)
// createApp(App).mount('#app')

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'

createApp(App).use(createPinia()).use(router).mount('#app')