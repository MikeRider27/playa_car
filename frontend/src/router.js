import { createRouter, createWebHistory } from 'vue-router'
import { auth, isAdmin } from './lib/auth'

const routes = [
  { path: '/', name: 'catalogo', component: () => import('./views/CatalogoView.vue') },
  { path: '/login', name: 'login', component: () => import('./views/LoginView.vue') },
  {
    path: '/panel',
    component: () => import('./components/AdminLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('./views/DashboardView.vue'), meta: { title: 'Dashboard' } },
      { path: 'vehiculos', name: 'vehiculos', component: () => import('./views/VehiculosView.vue'), meta: { title: 'Vehículos' } },
      { path: 'clientes', name: 'clientes', component: () => import('./views/ClientesView.vue'), meta: { title: 'Clientes' } },
      { path: 'ventas', name: 'ventas', component: () => import('./views/VentasView.vue'), meta: { title: 'Ventas' } },
      { path: 'ventas/nueva', name: 'nueva-venta', component: () => import('./views/NuevaVentaView.vue'), meta: { title: 'Nueva venta' } },
      { path: 'marcas', name: 'marcas', component: () => import('./views/MarcasView.vue'), meta: { title: 'Marcas' } },
      { path: 'usuarios', name: 'usuarios', component: () => import('./views/UsuariosView.vue'), meta: { title: 'Usuarios', admin: true } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  if (to.matched.some((r) => r.meta.auth) && !auth.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.admin && !isAdmin()) return { name: 'dashboard' }
  if (to.name === 'login' && auth.token) return { name: 'dashboard' }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Playa de Autos` : 'Playa de Autos'
})

export default router
