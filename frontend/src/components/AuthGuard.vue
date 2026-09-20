<template>
  <div v-if="isLoading" class="flex min-h-[240px] items-center justify-center text-on-surface-variant">
    <span class="font-body-md text-body-md">Verificando sesión...</span>
  </div>
  <slot v-else-if="isAuthenticated" />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { isAuthenticated, isLoading, requireAuth } = useAuth();

onMounted(() => {
  requireAuth();
});
</script>
