<script setup>
import { onMounted, ref, watch } from 'vue'
import api, { errorMessage } from '../lib/api'
import { isAdmin } from '../lib/auth'
import { confirmar } from '../lib/confirm'
import { toast } from '../lib/toast'
import { ESTADO_COLOR, capitalizar, fecha, moneda } from '../lib/format'
import AppModal from '../components/AppModal.vue'
import ClienteFormModal from '../components/ClienteFormModal.vue'

const clientes = ref([])
const loading = ref(true)
const q = ref('')
const formModal = ref(false)
const editando = ref(null)
const detalle = ref(null)

let timer
async function cargar() {
  loading.value = true
  try {
    clientes.value = (await api.get('/clientes', { params: { q: q.value } })).data
  } finally {
    loading.value = false
  }
}
watch(q, () => {
  clearTimeout(timer)
  timer = setTimeout(cargar, 300)
})

function abrir(c = null) {
  editando.value = c
  formModal.value = true
}

function guardado() {
  formModal.value = false
  cargar()
}

async function verDetalle(c) {
  try {
    detalle.value = (await api.get(`/clientes/${c.id}`)).data
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

async function eliminar(c) {
  const ok = await confirmar({ title: 'Eliminar cliente', message: `¿Eliminar a ${c.nombre} ${c.apellido}?`, okText: 'Eliminar' })
  if (!ok) return
  try {
    await api.delete(`/clientes/${c.id}`)
    toast('Cliente eliminado')
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

onMounted(cargar)
</script>

<template>
  <div class="card mb-4">
    <div class="card-header">
      <div class="row g-2 align-items-center">
        <div class="col-md-5">
          <input v-model="q" class="form-control form-control-sm" placeholder="Buscar por nombre o documento..." />
        </div>
        <div class="col-md-7 text-md-end">
          <button class="btn btn-primary btn-sm" @click="abrir()"><i class="bi bi-person-plus me-1"></i>Nuevo cliente</button>
        </div>
      </div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>Cliente</th><th>Documento</th><th>Teléfono</th><th>Email</th><th class="text-center">Compras</th><th class="text-end">Acciones</th></tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
          <tr v-else-if="!clientes.length"><td colspan="6" class="text-center text-secondary py-4">No hay clientes</td></tr>
          <tr v-for="c in (loading ? [] : clientes)" :key="c.id">
            <td>
              <a href="#" class="fw-semibold link-underline-opacity-0" @click.prevent="verDetalle(c)">{{ c.apellido }}, {{ c.nombre }}</a>
              <div v-if="c.direccion" class="small text-secondary">{{ c.direccion }}</div>
            </td>
            <td>{{ c.documento }}</td>
            <td>{{ c.telefono || '—' }}</td>
            <td>{{ c.email || '—' }}</td>
            <td class="text-center"><span class="badge text-bg-primary">{{ c.compras }}</span></td>
            <td class="text-end text-nowrap">
              <button class="btn btn-secondary btn-sm me-1" title="Ver" @click="verDetalle(c)"><i class="bi bi-eye"></i></button>
              <button class="btn btn-info btn-sm me-1" title="Editar" @click="abrir(c)"><i class="bi bi-pencil"></i></button>
              <button v-if="isAdmin()" class="btn btn-danger btn-sm" title="Eliminar" @click="eliminar(c)"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <ClienteFormModal :show="formModal" :cliente="editando" @close="formModal = false" @saved="guardado" />

  <AppModal :show="!!detalle" :title="detalle ? `${detalle.nombre} ${detalle.apellido}` : ''" size="lg" @close="detalle = null">
    <template v-if="detalle">
      <dl class="row">
        <dt class="col-sm-3">Documento</dt><dd class="col-sm-9">{{ detalle.documento }}</dd>
        <dt class="col-sm-3">Teléfono</dt><dd class="col-sm-9">{{ detalle.telefono || '—' }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ detalle.email || '—' }}</dd>
        <dt class="col-sm-3">Dirección</dt><dd class="col-sm-9">{{ detalle.direccion || '—' }}</dd>
        <dt class="col-sm-3">Cliente desde</dt><dd class="col-sm-9">{{ fecha(detalle.created_at) }}</dd>
      </dl>
      <h6 class="fw-semibold">Historial de compras</h6>
      <table class="table table-sm mb-0">
        <thead><tr><th>Fecha</th><th>Vehículo</th><th class="text-end">Precio</th><th>Estado</th></tr></thead>
        <tbody>
          <tr v-for="v in detalle.ventas" :key="v.id">
            <td>{{ fecha(v.fecha) }}</td>
            <td>{{ v.vehiculo }}</td>
            <td class="text-end">{{ moneda(v.precio) }}</td>
            <td><span class="badge" :class="`text-bg-${ESTADO_COLOR[v.estado]}`">{{ capitalizar(v.estado) }}</span></td>
          </tr>
          <tr v-if="!detalle.ventas.length"><td colspan="4" class="text-secondary text-center">Sin compras</td></tr>
        </tbody>
      </table>
    </template>
  </AppModal>
</template>
