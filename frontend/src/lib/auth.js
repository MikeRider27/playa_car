import { reactive } from 'vue'

function load() {
  try {
    const data = JSON.parse(localStorage.getItem('auth') || 'null')
    const payload = data && JSON.parse(atob(data.token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
    return payload && payload.exp * 1000 > Date.now() ? data : null
  } catch {
    return null
  }
}

const stored = load()

export const auth = reactive({
  token: stored?.token ?? null,
  usuario: stored?.usuario ?? null,
})

export function setSession(token, usuario) {
  auth.token = token
  auth.usuario = usuario
  localStorage.setItem('auth', JSON.stringify({ token, usuario }))
}

export function clearSession() {
  auth.token = null
  auth.usuario = null
  localStorage.removeItem('auth')
}

export const isAdmin = () => auth.usuario?.rol === 'admin'
