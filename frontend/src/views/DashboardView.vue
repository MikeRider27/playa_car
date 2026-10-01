<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../lib/api'
import { isAdmin } from '../lib/auth'
import { FORMAS_PAGO, fecha, moneda } from '../lib/format'

const data = ref(null)
const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']

const maxMonto = computed(() => Math.max(1, ...(data.value?.porMes ?? []).map((m) => Number(m.monto))))
const nombreMes = (ym) => MESES[Number(ym.slice(5, 7)) - 1]

onMounted(async () => {
  data.value = (await api.get('/dashboard')).data
})
</script>

<template>
  <div v-if="!data" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
  <template v-else>
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
          <div class="inner">
            <h3>{{ data.stock.disponibles }}</h3>
            <p>Vehículos disponibles</p>
          </div>
          <i class="small-box-icon bi bi-car-front-fill"></i>
          <router-link to="/panel/vehiculos" class="small-box-footer link-light link-underline-opacity-0">
            Ver vehículos <i class="bi bi-arrow-right-circle"></i>
          </router-link>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
          <div class="inner">
            <h3>{{ data.stock.reservados }}</h3>
            <p>Reservados</p>
          </div>
          <i class="small-box-icon bi bi-bookmark-star-fill"></i>
          <router-link to="/panel/vehiculos" class="small-box-footer link-dark link-underline-opacity-0">
            Ver vehículos <i class="bi bi-arrow-right-circle"></i>
          </router-link>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
          <div class="inner">
            <h3>{{ data.mes.ventas }}</h3>
            <p>Ventas este mes</p>
          </div>
          <i class="small-box-icon bi bi-cart-check-fill"></i>
          <router-link to="/panel/ventas" class="small-box-footer link-light link-underline-opacity-0">
            Ver ventas <i class="bi bi-arrow-right-circle"></i>
          </router-link>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
          <div class="inner">
            <h3>{{ data.clientes }}</h3>
            <p>Clientes</p>
          </div>
          <i class="small-box-icon bi bi-people-fill"></i>
          <router-link to="/panel/clientes" class="small-box-footer link-light link-underline-opacity-0">
            Ver clientes <i class="bi bi-arrow-right-circle"></i>
          </router-link>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-cash-stack"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Facturado este mes</span>
            <span class="info-box-number">{{ moneda(data.mes.monto) }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-info shadow-sm"><i class="bi bi-box-seam"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Valor del stock</span>
            <span class="info-box-number">{{ moneda(data.stock.valor_stock) }}</span>
          </div>
        </div>
      </div>
      <div v-if="isAdmin()" class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-primary shadow-sm"><i class="bi bi-graph-up-arrow"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Ganancia estimada del mes</span>
            <span class="info-box-number">{{ moneda(data.mes.ganancia) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card mb-4">
          <div class="card-header"><h3 class="card-title">Ventas de los últimos 6 meses</h3></div>
          <div class="card-body">
            <div class="chart-bars">
              <div v-for="m in data.porMes" :key="m.mes" class="bar-col" :title="`${m.ventas} ventas · ${moneda(m.monto)}`">
                <small class="text-secondary mb-1">{{ m.ventas }}</small>
                <div class="bar" :style="{ height: `${(Number(m.monto) / maxMonto) * 85}%` }"></div>
                <small class="mt-2 fw-semibold">{{ nombreMes(m.mes) }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Últimas ventas</h3>
            <div class="card-tools">
              <router-link to="/panel/ventas/nueva" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nueva</router-link>
            </div>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead>
                <tr><th>Fecha</th><th>Vehículo</th><th>Cliente</th><th class="text-end">Precio</th></tr>
              </thead>
              <tbody>
                <tr v-for="v in data.ultimas" :key="v.id">
                  <td>{{ fecha(v.fecha) }}</td>
                  <td>{{ v.vehiculo }}</td>
                  <td>{{ v.cliente }}</td>
                  <td class="text-end">
                    {{ moneda(v.precio) }}
                    <div class="small text-secondary">{{ FORMAS_PAGO[v.forma_pago] }}</div>
                  </td>
                </tr>
                <tr v-if="!data.ultimas.length"><td colspan="4" class="text-center text-secondary py-4">Sin ventas registradas</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </template>
</template>
