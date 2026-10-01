<script setup>
import { onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  title: { type: String, default: '' },
  size: { type: String, default: '' },
})
const emit = defineEmits(['close'])

function onKey(e) {
  if (e.key === 'Escape') emit('close')
}

watch(
  () => props.show,
  (open) => {
    document.body.classList.toggle('modal-open', open)
    if (open) document.addEventListener('keydown', onKey)
    else document.removeEventListener('keydown', onKey)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  document.body.classList.remove('modal-open')
  document.removeEventListener('keydown', onKey)
})
</script>

<template>
  <Teleport to="body">
    <template v-if="show">
      <div class="modal d-block" tabindex="-1" role="dialog" @mousedown.self="emit('close')">
        <div class="modal-dialog modal-dialog-scrollable" :class="size && `modal-${size}`">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ title }}</h5>
              <button type="button" class="btn-close no-print" aria-label="Cerrar" @click="emit('close')"></button>
            </div>
            <div class="modal-body"><slot /></div>
            <div v-if="$slots.footer" class="modal-footer no-print"><slot name="footer" /></div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop show"></div>
    </template>
  </Teleport>
</template>
