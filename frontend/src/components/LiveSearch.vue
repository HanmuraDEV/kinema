<template>
  <section class="min-w-0">
    <div class="border border-outline-variant rounded-full p-2 flex items-center relative glass-panel mb-6">
      <span class="material-symbols-outlined text-outline ml-3 absolute">search</span>
      <input
        v-model="query"
        @input="onInput"
        class="w-full bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface pl-10 py-3 rounded-full placeholder-outline-variant"
        placeholder="Buscar películas..."
        type="text"
      />
    </div>

    <div class="flex justify-between items-center mb-6 gap-3 flex-wrap">
      <span class="font-label-md text-label-md text-on-surface-variant">
        <template v-if="query">Mostrando resultados para <strong class="text-secondary">"{{ query }}"</strong></template>
        <template v-else>Escribe para buscar en el catálogo</template>
      </span>
    </div>

    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant">Buscando...</div>

    <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
      <a
        v-for="movie in results"
        :key="movie.id ?? movie.title"
        :href="movie.id ? `/movie?id=${movie.id}` : '#'"
        class="group relative rounded-xl overflow-hidden ambient-shadow-secondary bg-white"
      >
        <div class="aspect-[2/3] w-full bg-surface-container-high relative overflow-hidden">
          <img
            v-if="movie.poster_path || movie.image"
            :alt="movie.title"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            :src="movie.poster_path || movie.image"
          />
          <div v-else class="w-full h-full flex items-center justify-center font-headline-md text-headline-md text-outline p-4 text-center">
            {{ movie.title }}
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
            <h3 class="font-headline-md text-headline-md text-white">{{ movie.title }}</h3>
            <div class="flex justify-between items-center mt-1">
              <span class="font-label-sm text-label-sm text-white/80">{{ (movie.release_date || movie.year || '').toString().slice(0, 4) }}</span>
              <span v-if="movie.vibe" class="font-label-sm text-label-sm text-tertiary-container">{{ movie.vibe.name }}</span>
            </div>
          </div>
        </div>
      </a>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import api from '../services/api';

const props = defineProps({
  initialResults: { type: Array, default: () => [] },
  initialQuery: { type: String, default: '' },
});

const query = ref(props.initialQuery);
const results = ref(props.initialResults);
const isLoading = ref(false);
let timer = null;

const onInput = () => {
  clearTimeout(timer);
  timer = setTimeout(doSearch, 350);
};

const doSearch = async () => {
  const q = query.value.trim();
  if (q.length < 2) {
    results.value = props.initialResults;
    return;
  }
  isLoading.value = true;
  try {
    const { data } = await api.get('/api/movies/search', { params: { q } });
    results.value = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error('Error buscando:', e);
  } finally {
    isLoading.value = false;
  }
};
</script>
