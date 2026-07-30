<template>
  <div class="flex flex-col h-screen bg-[var(--bg)] text-[var(--text)]">
    <TopBar />

    <div class="flex flex-1 overflow-hidden">
      <Sidebar />

      <main class="flex-1 overflow-y-auto p-5">
        <router-view v-slot="{ Component }">
          <keep-alive :include="['DocentesPage', 'EstudiantesPage', 'SecretariaDashboard', 'DashboardPage']">
            <component :is="Component" />
          </keep-alive>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import TopBar from './components/TopBar.vue'
import Sidebar from './components/Sidebar.vue'
</script>

<style>
.sidebar-active {
  @apply !text-[#D28B45] !border-l-[#D28B45] !bg-[#D28B45]/[0.08];
}

.flyout-active {
  @apply !text-[#D28B45] !bg-[#D28B45]/[0.12];
}
</style>
