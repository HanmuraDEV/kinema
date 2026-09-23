<template>
  <div>
    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant font-body-md text-body-md">
      Buscando recomendaciones...
    </div>

    <template v-else>
      <div class="flex items-center justify-between mb-4">
        <span v-if="personalized" class="text-secondary text-label-sm font-label-sm px-3 py-1 rounded-full border border-secondary/30">Basado en tu perfil</span>
        <span v-else class="text-on-surface-variant text-label-sm font-label-sm px-3 py-1 rounded-full border border-outline-variant/30">Tendencia de la comunidad</span>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <a
          v-for="movie in items"
          :key="movie.id"
          :href="`/movie?id=${movie.id}`"
          class="group relative rounded-xl overflow-hidden ambient-shadow-secondary bg-white"
        >
          <div class="aspect-[2/3] w-full bg-surface-container-high relative overflow-hidden">
            <img
              v-if="movie.poster_path"
              :alt="movie.title"
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
              :src="movie.poster_path"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center font-headline-md text-headline-md text-outline p-4 text-center">
              {{ movie.title }}
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
              <h4 class="font-headline-md text-headline-md text-white">{{ movie.title }}</h4>
              <p class="font-label-sm text-label-sm text-white/80">{{ movie.vibe?.name }}</p>
            </div>
          </div>
          <div class="p-3">
            <h4 class="font-label-md text-label-md font-bold text-on-surface truncate">{{ movie.title }}</h4>
            <p class="font-label-sm text-label-sm text-secondary truncate">{{ movie.reason }}</p>
          </div>
        </a>
      </div>

      <p v-if="items.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center py-6">
        Califica películas para recibir recomendaciones.
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const items = ref([]);
const personalized = ref(false);
const isLoading = ref(true);

onMounted(async () => {
  try {
    // El endpoint es público; con token devuelve personalizadas
    const { data } = await api.get('/api/recommendations');
    personalized.value = !!data.personalized;
    items.value = data.items ?? [];
  } catch (e) {
    console.error('Error cargando recomendaciones:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
