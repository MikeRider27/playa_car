<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../lib/api'
import { setSession } from '../lib/auth'

const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref(route.query.expirada ? 'Tu sesión expiró, ingresá nuevamente.' : '')
const loading = ref(false)

async function ingresar() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/auth/login', form)
    setSession(data.token, data.usuario)
    router.replace(route.query.redirect || '/panel')
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page bg-body-secondary">
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <router-link to="/" class="link-dark link-underline-opacity-0">
            <h1 class="mb-0"><i class="bi bi-car-front-fill text-primary"></i> <b>Playa</b>Autos</h1>
          </router-link>
        </div>
        <div class="card-body login-card-body">
          <p class="login-box-msg">Ingresá para iniciar sesión</p>
          <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
          <form @submit.prevent="ingresar">
            <div class="input-group mb-3">
              <input v-model="form.email" type="email" class="form-control" placeholder="Email" required autofocus />
              <div class="input-group-text"><span class="bi bi-envelope"></span></div>
            </div>
            <div class="input-group mb-3">
              <input v-model="form.password" type="password" class="form-control" placeholder="Contraseña" required />
              <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Ingresar
              </button>
            </div>
          </form>
          <p class="mt-3 mb-0 text-center">
            <router-link to="/"><i class="bi bi-arrow-left me-1"></i>Volver al catálogo</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
