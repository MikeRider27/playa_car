<script setup>
import { toasts } from '../lib/toast'

const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' }
</script>

<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090">
    <div v-for="t in toasts" :key="t.id" class="toast show align-items-center border-0" :class="`text-bg-${t.type}`" role="alert">
      <div class="d-flex">
        <div class="toast-body"><i class="bi me-2" :class="icons[t.type]"></i>{{ t.message }}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toasts.splice(toasts.indexOf(t), 1)"></button>
      </div>
    </div>
  </div>
</template>
