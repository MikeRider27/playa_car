import axios from 'axios'
import { auth, clearSession } from './auth'

const api = axios.create({ baseURL: '/api' })

api.interceptors.request.use((config) => {
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && auth.token) {
      clearSession()
      window.location.href = '/login?expirada=1'
    }
    return Promise.reject(error)
  },
)

export const errorMessage = (e) => e.response?.data?.error || e.message || 'Error inesperado'
export const fieldErrors = (e) => e.response?.data?.errors || {}

export default api
