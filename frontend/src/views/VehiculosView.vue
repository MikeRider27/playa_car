<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api, { errorMessage, fieldErrors } from '../lib/api'
import { isAdmin } from '../lib/auth'
import { confirmar } from '../lib/confirm'
import { toast } from '../lib/toast'
import { COMBUSTIBLES, ESTADO_COLOR, TRANSMISIONES, capitalizar, moneda, numero } from '../lib/format'
import AppModal from '../components/AppModal.vue'
import VehiculoImagen from '../components/VehiculoImagen.vue'

const vehiculos = ref([])
const marcas = ref([])
const loading = ref(true)
const filtros = reactive({ q: '', estado: '', marca_id: '' })

const vacio = {
  marca_id: '', modelo: '', anio: new Date().getFullYear(), color: '', kilometraje: 0,
  combustible: 'nafta', transmision: 'manual', chapa: '', chasis: '', precio_compra: '',
  precio_venta: '', estado: 'disponible', descripcion: '', imagen_url: '',
}
const modal = ref(false)
const editando = ref(null)
const form = reactive({ ...vacio })
const errors = ref({})
const saving = ref(false)

const resumen = computed(() => ({
  total: vehiculos.value.length,
  valor: vehiculos.value.filter((v) => v.estado !== 'vendido').reduce((s, v) => s + Number(v.precio_venta), 0),
}))

let timer
async function cargar() {
  loading.value = true
  try {
    vehiculos.value = (await api.get('/vehiculos', { params: filtros })).data
  } catch (e) {
    toast(errorMessage(e), 'danger')
  } finally {
    loading.value = false
  }
}
watch(filtros, () => {
  clearTimeout(timer)
  timer = setTimeout(cargar, 300)
})

function abrir(v = null) {
  editando.value = v
  errors.value = {}
  Object.assign(form, vacio, v ? Object.fromEntries(Object.keys(vacio).map((k) => [k, v[k] ?? ''])) : {})
  modal.value = true
}

async function guardar() {
  saving.value = true
  errors.value = {}
  try {
    if (editando.value) await api.put(`/vehiculos/${editando.value.id}`, form)
    else await api.post('/vehiculos', form)
    toast(editando.value ? 'Vehículo actualizado' : 'Vehículo registrado')
    modal.value = false
    cargar()
  } catch (e) {
    errors.value = fieldErrors(e)
    toast(errorMessage(e), 'danger')
  } finally {
    saving.value = false
  }
}

async function eliminar(v) {
  const ok = await confirmar({ title: 'Eliminar vehículo', message: `¿Eliminar ${v.marca} ${v.modelo} (${v.chapa || 'sin chapa'})?`, okText: 'Eliminar' })
  if (!ok) return
  try {
    await api.delete(`/vehiculos/${v.id}`)
    toast('Vehículo eliminado')
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

onMounted(async () => {
  cargar()
  marcas.value = (await api.get('/marcas')).data
})
</script>

<template>
  <div class="card mb-4">
    <div class="card-header">
      <div class="row g-2 align-items-center">
        <div class="col-md-4">
          <input v-model="filtros.q" class="form-control form-control-sm" placeholder="Buscar modelo, marca, chapa..." />
        </div>
        <div class="col-6 col-md-2">
          <select v-model="filtros.estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            <option value="disponible">Disponible</option>
            <option value="reservado">Reservado</option>
            <option value="vendido">Vendido</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <select v-model="filtros.marca_id" class="form-select form-select-sm">
            <option value="">Todas las marcas</option>
            <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
          </select>
        </div>
        <div class="col-md-4 text-md-end">
          <button class="btn btn-primary btn-sm" @click="abrir()"><i class="bi bi-plus-lg me-1"></i>Nuevo vehículo</button>
        </div>
      </div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Vehículo</th>
            <th>Año</th>
            <th>Chapa</th>
            <th class="text-end">Km</th>
            <th class="text-end">Precio</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
          <tr v-else-if="!vehiculos.length"><td colspan="7" class="text-center text-secondary py-4">No hay vehículos</td></tr>
          <tr v-for="v in (loading ? [] : vehiculos)" :key="v.id">
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="vehiculo-thumb rounded overflow-hidden"><VehiculoImagen :src="v.imagen_url" /></div>
                <div>
                  <div class="fw-semibold">{{ v.marca }} {{ v.modelo }}</div>
                  <div class="small text-secondary">{{ COMBUSTIBLES[v.combustible] }} · {{ TRANSMISIONES[v.transmision] }} · {{ v.color || 's/color' }}</div>
                </div>
              </div>
            </td>
            <td>{{ v.anio }}</td>
            <td><span class="font-monospace">{{ v.chapa || '—' }}</span></td>
            <td class="text-end">{{ numero(v.kilometraje) }}</td>
            <td class="text-end">
              {{ moneda(v.precio_venta) }}
              <div v-if="isAdmin() && v.precio_compra" class="small text-secondary">Costo {{ moneda(v.precio_compra) }}</div>
            </td>
            <td><span class="badge" :class="`text-bg-${ESTADO_COLOR[v.estado]}`">{{ capitalizar(v.estado) }}</span></td>
            <td class="text-end text-nowrap">
              <router-link v-if="v.estado !== 'vendido'" :to="{ name: 'nueva-venta', query: { vehiculo: v.id } }" class="btn btn-success btn-sm me-1" title="Vender">
                <i class="bi bi-cart-plus"></i>
              </router-link>
              <button class="btn btn-info btn-sm me-1" title="Editar" @click="abrir(v)"><i class="bi bi-pencil"></i></button>
              <button v-if="isAdmin()" class="btn btn-danger btn-sm" title="Eliminar" @click="eliminar(v)"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer small text-secondary">
      {{ resumen.total }} vehículos · Valor en stock (no vendidos): <strong>{{ moneda(resumen.valor) }}</strong>
    </div>
  </div>

  <AppModal :show="modal" :title="editando ? 'Editar vehículo' : 'Nuevo vehículo'" size="lg" @close="modal = false">
    <form id="vehiculo-form" class="row g-3" @submit.prevent="guardar">
      <div class="col-md-4">
        <label class="form-label">Marca *</label>
        <select v-model="form.marca_id" class="form-select" :class="{ 'is-invalid': errors.marca_id }" required>
          <option value="" disabled>Seleccionar...</option>
          <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
        </select>
        <div class="invalid-feedback">{{ errors.marca_id }}</div>
      </div>
      <div class="col-md-5">
        <label class="form-label">Modelo *</label>
        <input v-model="form.modelo" class="form-control" :class="{ 'is-invalid': errors.modelo }" required />
        <div class="invalid-feedback">{{ errors.modelo }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Año *</label>
        <input v-model.number="form.anio" type="number" class="form-control" :class="{ 'is-invalid': errors.anio }" required />
        <div class="invalid-feedback">{{ errors.anio }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Color</label>
        <input v-model="form.color" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">Kilometraje</label>
        <input v-model.number="form.kilometraje" type="number" min="0" class="form-control" :class="{ 'is-invalid': errors.kilometraje }" />
        <div class="invalid-feedback">{{ errors.kilometraje }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Combustible</label>
        <select v-model="form.combustible" class="form-select">
          <option v-for="(label, key) in COMBUSTIBLES" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Transmisión</label>
        <select v-model="form.transmision" class="form-select">
          <option v-for="(label, key) in TRANSMISIONES" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Chapa</label>
        <input v-model="form.chapa" class="form-control text-uppercase" placeholder="AAAA 123" />
      </div>
      <div class="col-md-4">
        <label class="form-label">N° de chasis</label>
        <input v-model="form.chasis" class="form-control text-uppercase" />
      </div>
      <div class="col-md-4">
        <label class="form-label">Estado</label>
        <select v-model="form.estado" class="form-select" :disabled="editando?.estado === 'vendido'">
          <option value="disponible">Disponible</option>
          <option value="reservado">Reservado</option>
          <option v-if="editando?.estado === 'vendido'" value="vendido">Vendido</option>
        </select>
      </div>
      <div v-if="isAdmin() || !editando" class="col-md-6">
        <label class="form-label">Precio de compra (USD)</label>
        <input v-model="form.precio_compra" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.precio_compra }" />
        <div class="invalid-feedback">{{ errors.precio_compra }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Precio de venta (USD) *</label>
        <input v-model="form.precio_venta" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.precio_venta }" required />
        <div class="invalid-feedback">{{ errors.precio_venta }}</div>
      </div>
      <div class="col-12">
        <label class="form-label">URL de imagen</label>
        <input v-model="form.imagen_url" type="url" class="form-control" placeholder="https://..." />
      </div>
      <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea v-model="form.descripcion" rows="3" class="form-control"></textarea>
      </div>
    </form>
    <template #footer>
      <button class="btn btn-secondary" @click="modal = false">Cancelar</button>
      <button type="submit" form="vehiculo-form" class="btn btn-primary" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>Guardar
      </button>
    </template>
  </AppModal>
</template>
