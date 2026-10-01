<script setup>
import { reactive, ref, watch } from 'vue'
import AppModal from './AppModal.vue'
import api, { errorMessage, fieldErrors } from '../lib/api'
import { toast } from '../lib/toast'

const props = defineProps({ show: Boolean, cliente: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])

const vacio = { nombre: '', apellido: '', documento: '', telefono: '', email: '', direccion: '' }
const form = reactive({ ...vacio })
const errors = ref({})
const saving = ref(false)

watch(
  () => props.show,
  (open) => {
    if (!open) return
    errors.value = {}
    Object.assign(form, vacio, props.cliente ? Object.fromEntries(Object.keys(vacio).map((k) => [k, props.cliente[k] ?? ''])) : {})
  },
)

async function guardar() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = props.cliente
      ? await api.put(`/clientes/${props.cliente.id}`, form)
      : await api.post('/clientes', form)
    toast(props.cliente ? 'Cliente actualizado' : 'Cliente registrado')
    emit('saved', data)
  } catch (e) {
    errors.value = fieldErrors(e)
    toast(errorMessage(e), 'danger')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppModal :show="show" :title="cliente ? 'Editar cliente' : 'Nuevo cliente'" @close="emit('close')">
    <form id="cliente-form" class="row g-3" @submit.prevent="guardar">
      <div class="col-md-6">
        <label class="form-label">Nombre *</label>
        <input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': errors.nombre }" required />
        <div class="invalid-feedback">{{ errors.nombre }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Apellido *</label>
        <input v-model="form.apellido" class="form-control" :class="{ 'is-invalid': errors.apellido }" required />
        <div class="invalid-feedback">{{ errors.apellido }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Documento (CI/RUC) *</label>
        <input v-model="form.documento" class="form-control" :class="{ 'is-invalid': errors.documento }" required />
        <div class="invalid-feedback">{{ errors.documento }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input v-model="form.telefono" class="form-control" />
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" />
        <div class="invalid-feedback">{{ errors.email }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Dirección</label>
        <input v-model="form.direccion" class="form-control" />
      </div>
    </form>
    <template #footer>
      <button class="btn btn-secondary" @click="emit('close')">Cancelar</button>
      <button type="submit" form="cliente-form" class="btn btn-primary" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>Guardar
      </button>
    </template>
  </AppModal>
</template>
