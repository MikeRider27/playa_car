<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { auth, clearSession, isAdmin } from '../lib/auth'

const route = useRoute()
const router = useRouter()
const userMenu = ref(false)
const userMenuEl = ref(null)

const menu = computed(() => [
  { to: '/panel', label: 'Dashboard', icon: 'bi-speedometer2' },
  { header: 'OPERACIONES' },
  { to: '/panel/ventas/nueva', label: 'Nueva venta', icon: 'bi-cart-plus' },
  { to: '/panel/ventas', label: 'Ventas', icon: 'bi-receipt' },
  { to: '/panel/vehiculos', label: 'Vehículos', icon: 'bi-car-front' },
  { to: '/panel/clientes', label: 'Clientes', icon: 'bi-people' },
  { header: 'CONFIGURACIÓN' },
  { to: '/panel/marcas', label: 'Marcas', icon: 'bi-tags' },
  ...(isAdmin() ? [{ to: '/panel/usuarios', label: 'Usuarios', icon: 'bi-person-gear' }] : []),
])

function toggleSidebar() {
  const body = document.body.classList
  if (window.innerWidth >= 992) body.toggle('sidebar-collapse')
  else body.toggle('sidebar-open')
}

function closeMobileSidebar() {
  document.body.classList.remove('sidebar-open')
}

function onDocumentClick(e) {
  if (userMenuEl.value && !userMenuEl.value.contains(e.target)) userMenu.value = false
}

function salir() {
  clearSession()
  router.push({ name: 'login' })
}

watch(() => route.fullPath, () => {
  closeMobileSidebar()
  userMenu.value = false
})

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.body.classList.remove('sidebar-open', 'sidebar-collapse')
})
</script>

<template>
  <div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="#" role="button" @click.prevent="toggleSidebar"><i class="bi bi-list"></i></a>
          </li>
          <li class="nav-item d-none d-md-block">
            <a href="/" target="_blank" class="nav-link"><i class="bi bi-shop me-1"></i>Ver catálogo</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li ref="userMenuEl" class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" @click.prevent="userMenu = !userMenu">
              <i class="bi bi-person-circle me-1"></i>
              <span class="d-none d-md-inline">{{ auth.usuario?.nombre }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" :class="{ show: userMenu }" data-bs-popper="static">
              <li class="user-header text-bg-primary d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-person-circle display-4"></i>
                <p class="mb-0">
                  {{ auth.usuario?.nombre }}
                  <small>{{ auth.usuario?.email }} · {{ auth.usuario?.rol }}</small>
                </p>
              </li>
              <li class="user-footer">
                <button class="btn btn-outline-danger btn-sm float-end" @click="salir">
                  <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
                </button>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
      <div class="sidebar-brand">
        <router-link to="/panel" class="brand-link">
          <i class="bi bi-car-front-fill fs-4 text-primary me-2"></i>
          <span class="brand-text fw-semibold">Playa de Autos</span>
        </router-link>
      </div>
      <div class="sidebar-wrapper">
        <nav class="mt-2">
          <ul class="nav sidebar-menu flex-column" role="menu">
            <template v-for="item in menu" :key="item.to || item.header">
              <li v-if="item.header" class="nav-header">{{ item.header }}</li>
              <li v-else class="nav-item">
                <router-link :to="item.to" class="nav-link" :class="{ active: route.path === item.to }">
                  <i class="nav-icon bi" :class="item.icon"></i>
                  <p>{{ item.label }}</p>
                </router-link>
              </li>
            </template>
          </ul>
        </nav>
      </div>
    </aside>

    <main class="app-main">
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">{{ route.meta.title }}</h3></div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><router-link to="/panel">Inicio</router-link></li>
                <li v-if="route.name !== 'dashboard'" class="breadcrumb-item active">{{ route.meta.title }}</li>
              </ol>
            </div>
          </div>
        </div>
      </div>
      <div class="app-content">
        <div class="container-fluid">
          <router-view />
        </div>
      </div>
    </main>

    <footer class="app-footer">
      <div class="float-end d-none d-sm-inline">Sistema de gestión</div>
      <strong>Playa de Autos</strong> &copy; {{ new Date().getFullYear() }}
    </footer>
    <div class="sidebar-overlay" @click="closeMobileSidebar"></div>
  </div>
</template>
