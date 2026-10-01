<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import api from '../lib/api'
import { auth } from '../lib/auth'
import { COMBUSTIBLES, TRANSMISIONES, moneda, numero } from '../lib/format'
import AppModal from '../components/AppModal.vue'
import VehiculoImagen from '../components/VehiculoImagen.vue'

const vehiculos = ref([])
const marcas = ref([])
const loading = ref(true)
const filtros = reactive({ q: '', marca_id: '', precio_max: '', orden: '' })
const seleccionado = ref(null)

let timer
async function cargar() {
  loading.value = true
  try {
    vehiculos.value = (await api.get('/catalogo', { params: filtros })).data
  } finally {
    loading.value = false
  }
}

watch(filtros, () => {
  clearTimeout(timer)
  timer = setTimeout(cargar, 300)
})

onMounted(async () => {
  cargar()
  marcas.value = (await api.get('/marcas')).data.filter((m) => m.vehiculos > 0)
})
</script>

<template>
  <div class="bg-body-tertiary min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand bg-dark" data-bs-theme="dark">
      <div class="container">
        <router-link to="/" class="navbar-brand fw-semibold">
          <i class="bi bi-car-front-fill text-primary me-2"></i>Playa de Autos
        </router-link>
        <router-link :to="auth.token ? '/panel' : '/login'" class="btn btn-outline-light btn-sm">
          <i class="bi" :class="auth.token ? 'bi-speedometer2' : 'bi-person'"></i>
          {{ auth.token ? 'Ir al panel' : 'Ingresar' }}
        </router-link>
      </div>
    </nav>

    <header class="hero text-white py-5">
      <div class="container">
        <h1 class="display-5 fw-bold">Encontrá tu próximo vehículo</h1>
        <p class="lead mb-0 opacity-75">Vehículos seleccionados, revisados y listos para transferir. Financiación disponible.</p>
      </div>
    </header>

    <main class="container py-4 flex-grow-1">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-5">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input v-model="filtros.q" class="form-control" placeholder="Buscar por modelo, marca o color..." />
              </div>
            </div>
            <div class="col-6 col-md-2">
              <select v-model="filtros.marca_id" class="form-select">
                <option value="">Todas las marcas</option>
                <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <input v-model="filtros.precio_max" type="number" min="0" class="form-control" placeholder="Precio máx. (USD)" />
            </div>
            <div class="col-md-3">
              <select v-model="filtros.orden" class="form-select">
                <option value="">Más recientes</option>
                <option value="precio_asc">Menor precio</option>
                <option value="precio_desc">Mayor precio</option>
                <option value="anio_desc">Más nuevos</option>
                <option value="km_asc">Menos kilómetros</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
      <div v-else-if="!vehiculos.length" class="text-center text-secondary py-5">
        <i class="bi bi-search display-4"></i>
        <p class="mt-2">No encontramos vehículos con esos filtros.</p>
      </div>
      <div v-else class="row g-4">
        <div v-for="v in vehiculos" :key="v.id" class="col-sm-6 col-lg-4 col-xl-3">
          <div class="card h-100 shadow-sm catalogo-card overflow-hidden" @click="seleccionado = v">
            <div class="position-relative">
              <VehiculoImagen :src="v.imagen_url" :alt="`${v.marca} ${v.modelo}`" />
              <span v-if="v.estado === 'reservado'" class="badge text-bg-warning position-absolute top-0 start-0 m-2">Reservado</span>
            </div>
            <div class="card-body">
              <div class="text-secondary small">{{ v.marca }} · {{ v.anio }}</div>
              <h5 class="card-title float-none mb-2">{{ v.modelo }}</h5>
              <div class="d-flex flex-wrap gap-1 small mb-3">
                <span class="badge text-bg-light border"><i class="bi bi-speedometer me-1"></i>{{ numero(v.kilometraje) }} km</span>
                <span class="badge text-bg-light border"><i class="bi bi-fuel-pump me-1"></i>{{ COMBUSTIBLES[v.combustible] }}</span>
                <span class="badge text-bg-light border"><i class="bi bi-gear me-1"></i>{{ TRANSMISIONES[v.transmision] }}</span>
              </div>
              <div class="fs-4 fw-bold text-primary">{{ moneda(v.precio_venta) }}</div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="bg-dark text-white-50 py-3 text-center small">
      Playa de Autos &copy; {{ new Date().getFullYear() }}
    </footer>

    <AppModal :show="!!seleccionado" :title="seleccionado ? `${seleccionado.marca} ${seleccionado.modelo}` : ''" size="lg" @close="seleccionado = null">
      <template v-if="seleccionado">
        <div class="row g-4">
          <div class="col-md-7">
            <VehiculoImagen :src="seleccionado.imagen_url" class="rounded" />
          </div>
          <div class="col-md-5">
            <div class="fs-2 fw-bold text-primary mb-3">{{ moneda(seleccionado.precio_venta) }}</div>
            <dl class="row mb-0">
              <dt class="col-6">Año</dt><dd class="col-6">{{ seleccionado.anio }}</dd>
              <dt class="col-6">Kilometraje</dt><dd class="col-6">{{ numero(seleccionado.kilometraje) }} km</dd>
              <dt class="col-6">Combustible</dt><dd class="col-6">{{ COMBUSTIBLES[seleccionado.combustible] }}</dd>
              <dt class="col-6">Transmisión</dt><dd class="col-6">{{ TRANSMISIONES[seleccionado.transmision] }}</dd>
              <dt class="col-6">Color</dt><dd class="col-6">{{ seleccionado.color || '—' }}</dd>
            </dl>
            <div v-if="seleccionado.estado === 'reservado'" class="alert alert-warning py-2 mt-2 mb-0">Este vehículo está reservado.</div>
          </div>
        </div>
        <p v-if="seleccionado.descripcion" class="mt-3 mb-0">{{ seleccionado.descripcion }}</p>
      </template>
    </AppModal>
  </div>
</template>
