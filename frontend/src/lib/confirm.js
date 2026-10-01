import { reactive } from 'vue'

export const confirmState = reactive({ show: false, title: '', message: '', okText: 'Aceptar', variant: 'danger', resolve: null })

export function confirmar({ title = '¿Estás seguro?', message = '', okText = 'Aceptar', variant = 'danger' } = {}) {
  return new Promise((resolve) => {
    Object.assign(confirmState, { show: true, title, message, okText, variant, resolve })
  })
}
