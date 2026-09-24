<template>
  <div class="glass-panel rounded-xl p-5 mb-8">
    <div class="flex items-center gap-2 mb-1">
      <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">category</span>
      <h2 class="font-headline-md text-headline-md text-on-surface">Explorar por género</h2>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant mb-4 opacity-80">Del catálogo completo</p>
    <div v-if="isLoading" class="py-4 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando géneros...
    </div>
    <div v-else class="space-y-2">
      <div v-for="g in genres" :key="g.name" class="flex items-center gap-3">
        <span class="w-32 shrink-0 truncate font-label-md text-label-md text-on-surface text-right">{{ g.name }}</span>
        <div class="flex-1 h-3 overflow-hidden rounded-full bg-surface-container-highest">
          <div class="h-full rounded-full bg-gradient-to-r from-secondary-fixed-dim to-secondary" :style="{ width: pct(g.count) + '%' }"></div>
        </div>
        <span class="w-10 shrink-0 font-label-sm text-label-sm text-on-surface-variant">{{ g.count }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const genres = ref([]);
const isLoading = ref(true);
let max = 1;

const pct = (count) => Math.max(4, Math.round((count / max) * 100));

onMounted(async () => {
  try {
    const { data } = await api.get('/api/analytics/genres');
    genres.value = Array.isArray(data) ? data : [];
    max = Math.max(...genres.value.map((g) => g.count), 1);
  } catch (e) {
    console.error('Error cargando géneros:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
