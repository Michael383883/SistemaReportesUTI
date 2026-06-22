<!--
  Ejemplo de cómo conectar tu botón existente al composable useDarkMode.
  Copia el <script setup> a tu componente y deja tu <template> igual,
  ya que tu botón ya usa `darkMode` y `toggleDarkMode` con esos nombres exactos.
-->
<template>
  <button
    @click="toggleDarkMode"
    class="flex items-center justify-between w-full"
    style="padding:9px 14px; background:none; border:none; cursor:pointer; font-size:13.5px; color:#2d3748; text-align:left; transition:background 0.12s;"
    @mouseover="(e) => (e.currentTarget.style.background = '#f7f8fa')"
    @mouseleave="(e) => (e.currentTarget.style.background = 'none')"
  >
    <div class="flex items-center gap-3">
      <Moon style="width:16px; height:16px; color:#6b7280; flex-shrink:0;" /> Modo oscuro
    </div>
    <div
      style="width:32px; height:18px; border-radius:9px; transition:background 0.2s; flex-shrink:0; position:relative;"
      :style="darkMode ? 'background:#D28B45;' : 'background:#d1d5db;'"
    >
      <div
        style="position:absolute; top:2px; width:14px; height:14px; border-radius:50%; background:#fff; transition:left 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2);"
        :style="darkMode ? 'left:16px;' : 'left:2px;'"
      />
    </div>
  </button>
</template>

<script setup>
import { Moon } from 'lucide-vue-next' // o el ícono que ya estés usando
import { useDarkMode } from '@/composables/useDarkMode'

const { darkMode, toggleDarkMode } = useDarkMode()
</script>