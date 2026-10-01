<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../lib/api'
import { isAdmin } from '../lib/auth'
import { confirmar } from '../lib/confirm'
import { toast } from '../lib/toast'
import { ESTADO_COLOR, FORMAS_PAGO, capitalizar, fecha, moneda } from '../lib/format'
import AppModal from '../components/AppModal.vue'

const route = useRoute()
const router = useRouter()
const ventas = ref([])
const loading = ref(true)
const filtros = reactive({ q: '', desde: '', hasta: '', estado: 'completada' })
const detalle = ref(null)

const total = computed(() => ventas.value.filter((v) => v.estado === 'completada').reduce((s, v) => s + Number(v.precio), 0))
const saldo = (v) => Number(v.precio) - Number(v.entrega_inicial || 0)
const cuota = (v) => (v.cuotas ? saldo(v) / v.cuotas : 0)

let timer
async function cargar() {
  loading.value = true
  try {
    ventas.value = (await api.get('/ventas', { params: filtros })).data
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

async function ver(id) {
  try {
    detalle.value = (await api.get(`/ventas/${id}`)).data
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

function cerrarDetalle() {
  detalle.value = null
  if (route.query.ver) router.replace({ query: {} })
}

async function anular(v) {
  const ok = await confirmar({
    title: 'Anular venta',
    message: `¿Anular la venta #${v.id}? El vehículo ${v.marca} ${v.modelo} volverá a estar disponible.`,
    okText: 'Anular venta',
  })
  if (!ok) return
  try {
    detalle.value = (await api.post(`/ventas/${v.id}/anular`)).data
    toast('Venta anulada')
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

onMounted(() => {
  cargar()
  if (route.query.ver) ver(route.query.ver)
})
</script>

<template>
  <div class="card mb-4">
    <div class="card-header">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <input v-model="filtros.q" class="form-control form-control-sm" placeholder="Cliente, documento, vehículo..." />
        </div>
        <div class="col-6 col-md-2">
          <input v-model="filtros.desde" type="date" class="form-control form-control-sm" title="Desde" />
        </div>
        <div class="col-6 col-md-2">
          <input v-model="filtros.hasta" type="date" class="form-control form-control-sm" title="Hasta" />
        </div>
        <div class="col-md-2">
          <select v-model="filtros.estado" class="form-select form-select-sm">
            <option value="">Todas</option>
            <option value="completada">Completadas</option>
            <option value="anulada">Anuladas</option>
          </select>
        </div>
        <div class="col-md-3 text-md-end">
          <router-link to="/panel/ventas/nueva" class="btn btn-primary btn-sm"><i class="bi bi-cart-plus me-1"></i>Nueva venta</router-link>
        </div>
      </div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Fecha</th><th>Vehículo</th><th>Cliente</th><th>Pago</th><th class="text-end">Precio</th><th>Vendedor</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
          <tr v-else-if="!ventas.length"><td colspan="9" class="text-center text-secondary py-4">No hay ventas</td></tr>
          <tr v-for="v in (loading ? [] : ventas)" :key="v.id" class="cursor-pointer" @click="ver(v.id)">
            <td>{{ v.id }}</td>
            <td>{{ fecha(v.fecha) }}</td>
            <td>{{ v.marca }} {{ v.modelo }} <span class="small text-secondary">{{ v.anio }}</span></td>
            <td>{{ v.cliente }}</td>
            <td>{{ FORMAS_PAGO[v.forma_pago] }}<span v-if="v.cuotas" class="small text-secondary"> ({{ v.cuotas }} cuotas)</span></td>
            <td class="text-end">{{ moneda(v.precio) }}</td>
            <td>{{ v.vendedor }}</td>
            <td><span class="badge" :class="`text-bg-${ESTADO_COLOR[v.estado]}`">{{ capitalizar(v.estado) }}</span></td>
            <td class="text-end"><i class="bi bi-chevron-right text-secondary"></i></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-end">
      Total vendido (completadas): <strong>{{ moneda(total) }}</strong>
    </div>
  </div>

  <AppModal :show="!!detalle" :title="detalle ? `Venta #${detalle.id}` : ''" size="lg" @close="cerrarDetalle">
    <div v-if="detalle" class="print-area">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h4 class="mb-0"><i class="bi bi-car-front-fill text-primary"></i> Playa de Autos</h4>
          <div class="text-secondary small">Comprobante de venta</div>
        </div>
        <div class="text-end">
          <div class="fw-semibold">N° {{ String(detalle.id).padStart(6, '0') }}</div>
          <div>{{ fecha(detalle.fecha) }}</div>
          <span class="badge" :class="`text-bg-${ESTADO_COLOR[detalle.estado]}`">{{ capitalizar(detalle.estado) }}</span>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <div class="border rounded p-3 h-100">
            <div class="text-secondary small text-uppercase mb-1">Cliente</div>
            <div class="fw-semibold">{{ detalle.cliente }}</div>
            <div>CI/RUC: {{ detalle.cliente_documento }}</div>
            <div v-if="detalle.cliente_telefono">Tel.: {{ detalle.cliente_telefono }}</div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="border rounded p-3 h-100">
            <div class="text-secondary small text-uppercase mb-1">Vehículo</div>
            <div class="fw-semibold">{{ detalle.marca }} {{ detalle.modelo }} ({{ detalle.anio }})</div>
            <div>Chapa: {{ detalle.chapa || '—' }}</div>
            <div>Color: {{ detalle.color || '—' }}</div>
          </div>
        </div>
      </div>
      <table class="table table-sm">
        <tbody>
          <tr><th>Forma de pago</th><td class="text-end">{{ FORMAS_PAGO[detalle.forma_pago] }}</td></tr>
          <template v-if="detalle.forma_pago === 'financiado'">
            <tr><th>Entrega inicial</th><td class="text-end">{{ moneda(detalle.entrega_inicial) }}</td></tr>
            <tr><th>Saldo a financiar</th><td class="text-end">{{ moneda(saldo(detalle)) }}</td></tr>
            <tr><th>Cuotas</th><td class="text-end">{{ detalle.cuotas }} × {{ moneda(cuota(detalle)) }}</td></tr>
          </template>
          <tr class="table-light fs-5"><th>Total</th><td class="text-end fw-bold">{{ moneda(detalle.precio) }}</td></tr>
        </tbody>
      </table>
      <p v-if="detalle.observacion" class="mb-2"><strong>Observación:</strong> {{ detalle.observacion }}</p>
      <div class="small text-secondary">Vendedor: {{ detalle.vendedor }}</div>
    </div>
    <template #footer>
      <button v-if="isAdmin() && detalle?.estado === 'completada'" class="btn btn-outline-danger me-auto" @click="anular(detalle)">
        <i class="bi bi-x-circle me-1"></i>Anular
      </button>
      <button class="btn btn-secondary" @click="cerrarDetalle">Cerrar</button>
      <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
    </template>
  </AppModal>
</template>
