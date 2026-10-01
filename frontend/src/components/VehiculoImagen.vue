<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ src: { type: String, default: null }, alt: { type: String, default: '' } })
const failed = ref(false)
watch(() => props.src, () => (failed.value = false))
</script>

<template>
  <img v-if="src && !failed" :src="src" :alt="alt" class="vehiculo-img" loading="lazy" @error="failed = true" />
  <div v-else class="vehiculo-img-placeholder"><i class="bi bi-car-front-fill"></i></div>
</template>
