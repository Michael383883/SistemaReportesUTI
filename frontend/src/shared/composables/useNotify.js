import { ref } from 'vue'

const notifications = ref([])
let idCounter = 0

export function useNotify() {
  function push(message, type = 'success', duration = 3500) {
    const id = ++idCounter
    notifications.value.push({ id, message, type })
    setTimeout(() => {
      notifications.value = notifications.value.filter((n) => n.id !== id)
    }, duration)
  }

  function success(message) { push(message, 'success') }
  function error(message)   { push(message, 'error') }
  function info(message)    { push(message, 'info') }

  function remove(id) {
    notifications.value = notifications.value.filter((n) => n.id !== id)
  }

  return { notifications, success, error, info, remove }
}
