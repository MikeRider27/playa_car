<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage, fieldErrors } from '../lib/api'
import { toast } from '../lib/toast'
import { COMBUSTIBLES, FORMAS_PAGO, TRANSMISIONES, hoy, moneda, numero } from '../lib/format'
import ClienteFormModal from '../components/ClienteFormModal.vue'
import VehiculoImagen from '../components/VehiculoImagen.vue'

const route = useRoute()
const router = useRouter()
const vehiculos = ref([])
const clientes = ref([])
const clienteModal = ref(false)
const errors = ref({})
const saving = ref(false)
const form = reactive({
  vehiculo_id: '', cliente_id: '', fecha: hoy(), precio: '', forma_pago: 'contado',
  entrega_inicial: '', cuotas: 12, observacion: '',
})

const vehiculo = computed(() => vehiculos.value.find((v) => v.id === form.vehiculo_id))
const cliente = computed(() => clientes.value.find((c) => c.id === form.cliente_id))
const financiado = computed(() => form.forma_pago === 'financiado')
const saldo = computed(() => Math.max(0, Number(form.precio || 0) - Number(form.entrega_inicial || 0)))
const cuota = computed(() => (form.cuotas > 0 ? saldo.value / form.cuotas : 0))

watch(() => form.vehiculo_id, () => {
  if (vehiculo.value) form.precio = Number(vehiculo.value.precio_venta)
})

async function cargarClientes() {
  clientes.value = (await api.get('/clientes')).data
}

function clienteCreado(c) {
  clienteModal.value = false
  cargarClientes().then(() => (form.cliente_id = c.id))
}

async function registrar() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await api.post('/ventas', form)
    toast(`Venta #${data.id} registrada`)
    router.push({ name: 'ventas', query: { ver: data.id } })
  } catch (e) {
    errors.value = fieldErrors(e)
    toast(errorMessage(e), 'danger')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [v] = await Promise.all([api.get('/vehiculos'), cargarClientes()])
  vehiculos.value = v.data.filter((x) => x.estado !== 'vendido')
  const preseleccion = Number(route.query.vehiculo)
  if (vehiculos.value.some((x) => x.id === preseleccion)) form.vehiculo_id = preseleccion
})
</script>

<template>
  <form class="row" @submit.prevent="registrar">
    <div class="col-lg-8">
      <div class="card card-primary card-outline mb-4">
        <div class="card-header"><h3 class="card-title"><i class="bi bi-car-front me-1"></i>Vehículo</h3></div>
        <div class="card-body">
          <select v-model="form.vehiculo_id" class="form-select mb-3" :class="{ 'is-invalid': errors.vehiculo_id }" required>
            <option value="" disabled>Seleccionar vehículo disponible...</option>
            <option v-for="v in vehiculos" :key="v.id" :value="v.id">
              {{ v.marca }} {{ v.modelo }} {{ v.anio }} — {{ v.chapa || 's/chapa' }} — {{ moneda(v.precio_venta) }}{{ v.estado === 'reservado' ? ' (reservado)' : '' }}
            </option>
          </select>
          <div v-if="vehiculo" class="row g-3 align-items-center">
            <div class="col-sm-4"><VehiculoImagen :src="vehiculo.imagen_url" class="rounded" /></div>
            <div class="col-sm-8">
              <h5 class="mb-1">{{ vehiculo.marca }} {{ vehiculo.modelo }} ({{ vehiculo.anio }})</h5>
              <div class="text-secondary">
                {{ numero(vehiculo.kilometraje) }} km · {{ COMBUSTIBLES[vehiculo.combustible] }} · {{ TRANSMISIONES[vehiculo.transmision] }} · {{ vehiculo.color }}
              </div>
              <div class="mt-1">Precio de lista: <strong>{{ moneda(vehiculo.precio_venta) }}</strong></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-primary card-outline mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-person me-1"></i>Cliente</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-outline-primary btn-sm" @click="clienteModal = true"><i class="bi bi-person-plus me-1"></i>Nuevo cliente</button>
          </div>
        </div>
        <div class="card-body">
          <select v-model="form.cliente_id" class="form-select" :class="{ 'is-invalid': errors.cliente_id }" required>
            <option value="" disabled>Seleccionar cliente...</option>
            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.apellido }}, {{ c.nombre }} — CI {{ c.documento }}</option>
          </select>
          <div v-if="cliente" class="mt-2 text-secondary small">
            <i class="bi bi-telephone me-1"></i>{{ cliente.telefono || '—' }}
            <i class="bi bi-envelope ms-3 me-1"></i>{{ cliente.email || '—' }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card card-success card-outline mb-4">
        <div class="card-header"><h3 class="card-title"><i class="bi bi-cash-coin me-1"></i>Pago</h3></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input v-model="form.fecha" type="date" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Precio final (USD)</label>
            <input v-model.number="form.precio" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.precio }" required />
            <div class="invalid-feedback">{{ errors.precio }}</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Forma de pago</label>
            <select v-model="form.forma_pago" class="form-select">
              <option v-for="(label, key) in FORMAS_PAGO" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <template v-if="financiado">
            <div class="mb-3">
              <label class="form-label">Entrega inicial (USD)</label>
              <input v-model.number="form.entrega_inicial" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.entrega_inicial }" />
              <div class="invalid-feedback">{{ errors.entrega_inicial }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Cantidad de cuotas</label>
              <input v-model.number="form.cuotas" type="number" min="1" max="360" class="form-control" :class="{ 'is-invalid': errors.cuotas }" required />
              <div class="invalid-feedback">{{ errors.cuotas }}</div>
            </div>
            <div class="alert alert-info py-2">
              Saldo {{ moneda(saldo) }} en {{ form.cuotas || 0 }} cuotas de <strong>{{ moneda(cuota) }}</strong>
            </div>
          </template>
          <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea v-model="form.observacion" rows="2" class="form-control"></textarea>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-check2-circle me-1"></i>Registrar venta
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <ClienteFormModal :show="clienteModal" @close="clienteModal = false" @saved="clienteCreado" />
</template>
