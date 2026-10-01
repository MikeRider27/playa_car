<script setup>
import { onMounted, reactive, ref } from 'vue'
import api, { errorMessage, fieldErrors } from '../lib/api'
import { auth } from '../lib/auth'
import { confirmar } from '../lib/confirm'
import { toast } from '../lib/toast'
import { capitalizar, fecha } from '../lib/format'
import AppModal from '../components/AppModal.vue'

const usuarios = ref([])
const modal = ref(false)
const editando = ref(null)
const vacio = { nombre: '', email: '', rol: 'vendedor', activo: true, password: '' }
const form = reactive({ ...vacio })
const errors = ref({})
const saving = ref(false)

async function cargar() {
  usuarios.value = (await api.get('/usuarios')).data
}

function abrir(u = null) {
  editando.value = u
  errors.value = {}
  Object.assign(form, vacio, u ? { nombre: u.nombre, email: u.email, rol: u.rol, activo: u.activo } : {})
  modal.value = true
}

async function guardar() {
  saving.value = true
  errors.value = {}
  try {
    if (editando.value) await api.put(`/usuarios/${editando.value.id}`, form)
    else await api.post('/usuarios', form)
    toast(editando.value ? 'Usuario actualizado' : 'Usuario creado')
    modal.value = false
    cargar()
  } catch (e) {
    errors.value = fieldErrors(e)
    toast(errorMessage(e), 'danger')
  } finally {
    saving.value = false
  }
}

async function eliminar(u) {
  if (!(await confirmar({ title: 'Eliminar usuario', message: `¿Eliminar a ${u.nombre}? Si tiene ventas registradas, desactivalo en su lugar.`, okText: 'Eliminar' }))) return
  try {
    await api.delete(`/usuarios/${u.id}`)
    toast('Usuario eliminado')
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
      <h3 class="card-title">Usuarios del sistema</h3>
      <div class="card-tools">
        <button class="btn btn-primary btn-sm" @click="abrir()"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</button>
      </div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Alta</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <tr v-for="u in usuarios" :key="u.id">
            <td>{{ u.nombre }} <span v-if="u.id === auth.usuario.id" class="badge text-bg-light border">Vos</span></td>
            <td>{{ u.email }}</td>
            <td><span class="badge" :class="u.rol === 'admin' ? 'text-bg-primary' : 'text-bg-info'">{{ capitalizar(u.rol) }}</span></td>
            <td><span class="badge" :class="u.activo ? 'text-bg-success' : 'text-bg-secondary'">{{ u.activo ? 'Activo' : 'Inactivo' }}</span></td>
            <td>{{ fecha(u.created_at) }}</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-info btn-sm me-1" title="Editar" @click="abrir(u)"><i class="bi bi-pencil"></i></button>
              <button v-if="u.id !== auth.usuario.id" class="btn btn-danger btn-sm" title="Eliminar" @click="eliminar(u)"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <AppModal :show="modal" :title="editando ? 'Editar usuario' : 'Nuevo usuario'" @close="modal = false">
    <form id="usuario-form" class="row g-3" @submit.prevent="guardar">
      <div class="col-12">
        <label class="form-label">Nombre *</label>
        <input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': errors.nombre }" required />
        <div class="invalid-feedback">{{ errors.nombre }}</div>
      </div>
      <div class="col-12">
        <label class="form-label">Email *</label>
        <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" required />
        <div class="invalid-feedback">{{ errors.email }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Rol</label>
        <select v-model="form.rol" class="form-select">
          <option value="vendedor">Vendedor</option>
          <option value="admin">Administrador</option>
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
          <input id="activo" v-model="form.activo" class="form-check-input" type="checkbox" />
          <label for="activo" class="form-check-label">Activo</label>
        </div>
      </div>
      <div class="col-12">
        <label class="form-label">{{ editando ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' }}</label>
        <input v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': errors.password }" :required="!editando" autocomplete="new-password" />
        <div class="invalid-feedback">{{ errors.password }}</div>
      </div>
    </form>
    <template #footer>
      <button class="btn btn-secondary" @click="modal = false">Cancelar</button>
      <button type="submit" form="usuario-form" class="btn btn-primary" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>Guardar
      </button>
    </template>
  </AppModal>
</template>
