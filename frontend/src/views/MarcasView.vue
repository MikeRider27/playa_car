<script setup>
import { onMounted, ref } from 'vue'
import api, { errorMessage } from '../lib/api'
import { isAdmin } from '../lib/auth'
import { confirmar } from '../lib/confirm'
import { toast } from '../lib/toast'

const marcas = ref([])
const nueva = ref('')
const editandoId = ref(null)
const editandoNombre = ref('')

async function cargar() {
  marcas.value = (await api.get('/marcas')).data
}

async function agregar() {
  try {
    await api.post('/marcas', { nombre: nueva.value })
    toast('Marca agregada')
    nueva.value = ''
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

function editar(m) {
  editandoId.value = m.id
  editandoNombre.value = m.nombre
}

async function guardar(m) {
  try {
    await api.put(`/marcas/${m.id}`, { nombre: editandoNombre.value })
    toast('Marca actualizada')
    editandoId.value = null
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

async function eliminar(m) {
  if (!(await confirmar({ title: 'Eliminar marca', message: `¿Eliminar la marca ${m.nombre}?`, okText: 'Eliminar' }))) return
  try {
    await api.delete(`/marcas/${m.id}`)
    toast('Marca eliminada')
    cargar()
  } catch (e) {
    toast(errorMessage(e), 'danger')
  }
}

onMounted(cargar)
</script>

<template>
  <div class="row">
    <div class="col-lg-7">
      <div class="card mb-4">
        <div class="card-header">
          <form class="input-group input-group-sm" @submit.prevent="agregar">
            <input v-model="nueva" class="form-control" placeholder="Nombre de la nueva marca" required />
            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
          </form>
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead><tr><th>Marca</th><th class="text-center">Vehículos</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="m in marcas" :key="m.id">
                <td>
                  <form v-if="editandoId === m.id" class="input-group input-group-sm" @submit.prevent="guardar(m)">
                    <input v-model="editandoNombre" class="form-control" required />
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i></button>
                    <button type="button" class="btn btn-secondary" @click="editandoId = null"><i class="bi bi-x-lg"></i></button>
                  </form>
                  <span v-else>{{ m.nombre }}</span>
                </td>
                <td class="text-center"><span class="badge text-bg-secondary">{{ m.vehiculos }}</span></td>
                <td class="text-end text-nowrap">
                  <button class="btn btn-info btn-sm me-1" title="Editar" @click="editar(m)"><i class="bi bi-pencil"></i></button>
                  <button v-if="isAdmin()" class="btn btn-danger btn-sm" title="Eliminar" :disabled="m.vehiculos > 0" @click="eliminar(m)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
