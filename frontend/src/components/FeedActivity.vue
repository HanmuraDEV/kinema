<template>
  <div class="flex flex-col">
    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando actividad...
    </div>
    <div v-else-if="items.length === 0" class="glass-panel rounded-xl p-6 text-center">
      <p class="font-body-md text-body-md text-on-surface-variant">
        Sigue a otros cinéfilos para ver su actividad aquí.
      </p>
      <a href="/search" class="inline-block mt-3 text-secondary font-label-md text-label-md hover:underline">
        Descubrir películas
      </a>
    </div>
    <article
      v-for="item in items"
      :key="item.feed_type + '-' + item.id"
      class="flex gap-4 py-4 border-b border-outline-variant/30 last:border-b-0 cursor-pointer hover:bg-surface-container/50 transition-colors"
    >
      <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 bg-surface-container-high flex items-center justify-center font-bold text-secondary">
        {{ (item.user?.name || '?').charAt(0).toUpperCase() }}
      </div>
      <div class="flex-grow">
        <p class="font-body-sm text-body-md">
          <span class="font-bold text-on-surface">{{ item.user?.name }}</span>
          <template v-if="item.feed_type === 'review'">
            {{ ' reseñó ' }}
            <span class="font-semibold text-secondary">{{ item.movie?.title }}</span>
          </template>
          <template v-else>
            {{ ' creó la lista ' }}
            <span class="italic text-on-surface-variant">{{ item.name }}</span>
          </template>
        </p>
        <p v-if="item.feed_type === 'review' && item.rating" class="flex items-center gap-1 text-tertiary mt-1 font-label-md text-label-md">
          <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">star</span>
          {{ item.rating }}
        </p>
        <p v-if="item.content" class="text-body-md mt-2 text-on-surface-variant italic border-l-2 border-outline-variant/50 pl-3 py-1 line-clamp-3">
          {{ item.content }}
        </p>
      </div>
    </article>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const items = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  if (typeof localStorage === 'undefined') return;
  if (!localStorage.getItem('kinema_token')) {
    isLoading.value = false;
    return;
  }
  try {
    const { data } = await api.get('/api/feed');
    items.value = Array.isArray(data) ? data : (data.data ?? []);
  } catch (e) {
    console.error('Error cargando feed:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
