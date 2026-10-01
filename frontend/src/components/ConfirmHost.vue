<script setup>
import AppModal from './AppModal.vue'
import { confirmState } from '../lib/confirm'

function close(result) {
  confirmState.show = false
  confirmState.resolve?.(result)
  confirmState.resolve = null
}
</script>

<template>
  <AppModal :show="confirmState.show" :title="confirmState.title" @close="close(false)">
    <p class="mb-0">{{ confirmState.message }}</p>
    <template #footer>
      <button class="btn btn-secondary" @click="close(false)">Cancelar</button>
      <button class="btn" :class="`btn-${confirmState.variant}`" @click="close(true)">{{ confirmState.okText }}</button>
    </template>
  </AppModal>
</template>
