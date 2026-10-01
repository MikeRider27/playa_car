<script setup>
import ToastHost from './components/ToastHost.vue'
import ConfirmHost from './components/ConfirmHost.vue'
</script>

<template>
  <router-view />
  <ToastHost />
  <ConfirmHost />
</template>
