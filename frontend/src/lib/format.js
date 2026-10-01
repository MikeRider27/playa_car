const money = new Intl.NumberFormat('es-PY', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
const num = new Intl.NumberFormat('es-PY')

export const moneda = (v) => money.format(Number(v || 0))
export const numero = (v) => num.format(Number(v || 0))

export function fecha(v) {
  if (!v) return ''
  const d = new Date(v.length === 10 ? `${v}T00:00:00` : v)
  return d.toLocaleDateString('es-PY')
}

export function hoy() {
  const d = new Date()
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10)
}

export const ESTADO_COLOR = { disponible: 'success', reservado: 'warning', vendido: 'secondary', completada: 'success', anulada: 'danger' }
export const COMBUSTIBLES = { nafta: 'Nafta', diesel: 'Diésel', flex: 'Flex', hibrido: 'Híbrido', electrico: 'Eléctrico' }
export const TRANSMISIONES = { manual: 'Manual', automatica: 'Automática' }
export const FORMAS_PAGO = { contado: 'Contado', transferencia: 'Transferencia', financiado: 'Financiado' }
export const capitalizar = (s) => (s ? s[0].toUpperCase() + s.slice(1) : '')
