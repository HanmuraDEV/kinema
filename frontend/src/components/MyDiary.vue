<template>
  <div>
    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando tu diario...
    </div>

    <div v-else-if="items.length === 0" class="glass-panel rounded-xl p-8 text-center">
      <p class="font-body-md text-body-md text-on-surface-variant mb-4">
        Aún no registras películas. Busca una y deja tu reseña.
      </p>
      <a href="/search"><Button label="Descubrir películas" variant="primary" /></a>
    </div>

    <div v-else class="flex flex-col">
      <article
        v-for="item in items"
        :key="item.id"
        class="group flex items-center gap-4 border-b border-outline-variant/20 py-6"
      >
        <a :href="item.movie?.id ? `/movie?id=${item.movie.id}` : '#'" class="h-24 w-16 shrink-0 overflow-hidden rounded-xl shadow-sm bg-surface-container-high flex items-center justify-center text-center font-label-sm text-label-sm text-on-surface-variant p-1">
          <img v-if="item.movie?.poster_path" :alt="item.movie.title" class="h-full w-full object-cover" :src="item.movie.poster_path" />
          <span v-else>{{ item.movie?.title }}</span>
        </a>
        <div class="grow min-w-0">
          <div class="mb-1 flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h3 class="font-label-md text-label-md text-on-background truncate">{{ item.movie?.title }}</h3>
              <p class="font-label-sm text-label-sm text-outline">
                {{ yearOf(item.movie) }}
                <template v-if="item.watched_at"> · vista el {{ formatDate(item.watched_at) }}</template>
                <template v-if="item.rating"> · ★ {{ item.rating }}</template>
              </p>
            </div>
            <button @click="removeItem(item.id)" title="Eliminar del diario" class="shrink-0 font-label-sm text-label-sm text-outline hover:text-error transition-colors cursor-pointer">
              Eliminar
            </button>
          </div>
          <p v-if="item.content" class="font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ item.content }}</p>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import Button from './Button.vue';

const items = ref([]);
const isLoading = ref(true);

const yearOf = (movie) => {
  if (!movie) return '';
  if (movie.release_date) return String(movie.release_date).slice(0, 4);
  return movie.release_year ? String(movie.release_year) : '';
};

const formatDate = (iso) => {
  try {
    return new Date(iso + 'T00:00:00').toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch { return iso; }
};

const load = async () => {
  isLoading.value = true;
  try {
    const me = await api.get('/api/user');
    const { data } = await api.get(`/api/users/${me.data.id}/reviews`);
    items.value = data.data ?? data ?? [];
  } catch (e) {
    console.error('Error cargando diario:', e);
    if (e.response?.status === 401) window.location.href = '/login';
  } finally {
    isLoading.value = false;
  }
};

const removeItem = async (id) => {
  try {
    await api.delete(`/api/reviews/${id}`);
    items.value = items.value.filter((i) => i.id !== id);
  } catch (e) {
    console.error('Error eliminando:', e);
  }
};

onMounted(load);
</script>
