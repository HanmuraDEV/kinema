<template>
  <div v-if="!isReady" class="flex gap-3">
  </div>
  <div v-else-if="isAuthenticated" class="flex gap-3">
    <a href="/diary"><Button label="Mi diario" variant="secondary" /></a>
    <a href="/lists"><Button label="Mis listas" variant="secondary" /></a>
  </div>
  <div v-else class="flex gap-3">
    <a href="/search"><Button label="Descubrir" variant="primary" /></a>
    <a href="/register"><Button label="Crear cuenta" variant="secondary" /></a>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { isAuthenticated, checkAuth } = useAuth();
const isReady = ref(false);

onMounted(async () => {
  await checkAuth();
  isReady.value = true;
});
</script>
