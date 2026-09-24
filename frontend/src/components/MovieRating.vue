<template>
  <div class="glass-panel rounded-xl p-6 ambient-shadow-secondary">
    <div v-if="!isAuthenticated" class="text-center">
      <p class="font-body-md text-body-md text-on-surface-variant">
        <a href="/login" class="text-secondary hover:underline">Inicia sesión</a>
        para calificar esta película.
      </p>
    </div>

    <div v-else class="flex flex-wrap items-center gap-4">
      <div>
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">Tu calificación</p>
        <div class="flex items-center gap-1">
          <button
            v-for="n in 5"
            :key="n"
            @click="rate(n)"
            :disabled="isSaving"
            :title="`${n} estrella${n > 1 ? 's' : ''}`"
            class="cursor-pointer transition-transform hover:scale-125 disabled:cursor-wait"
          >
            <span
              class="material-symbols-outlined text-[32px]"
              :class="n <= (hover || rating || 0) ? 'text-tertiary' : 'text-outline-variant'"
              :style="`font-variation-settings: 'FILL' ${n <= (hover || rating || 0) ? 1 : 0};`"
              @mouseenter="hover = n"
              @mouseleave="hover = 0"
            >star</span>
          </button>
          <button
            v-if="rating"
            @click="clearRating"
            :disabled="isSaving"
            title="Quitar calificación"
            class="ml-2 font-label-sm text-label-sm text-on-surface-variant hover:text-error transition-colors cursor-pointer"
          >
            Quitar
          </button>
        </div>
      </div>
      <p v-if="message" class="font-label-md text-label-md text-primary">{{ message }}</p>
      <p v-if="error" class="p-2 rounded bg-error-container text-on-error-container text-sm">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';

const props = defineProps({
  movieId: { type: [Number, String], required: true },
});

const { isAuthenticated, checkAuth } = useAuth();
const rating = ref(0);
const hover = ref(0);
const isSaving = ref(false);
const message = ref(null);
const error = ref(null);

onMounted(async () => {
  await checkAuth();
  if (!isAuthenticated.value) return;
  try {
    const { data } = await api.get(`/api/movies/${props.movieId}/my-review`);
    if (data?.rating) rating.value = Math.round(Number(data.rating));
  } catch (e) {
    console.error('Error cargando calificación:', e);
  }
});

const rate = async (stars) => {
  isSaving.value = true;
  error.value = null;
  message.value = null;
  try {
    await api.post('/api/reviews', {
      movie_id: Number(props.movieId),
      rating: stars,
    });
    rating.value = stars;
    message.value = `Guardada: ${stars}/5.`;
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo guardar.';
  } finally {
    isSaving.value = false;
  }
};

const clearRating = async () => {
  // Sin endpoint de "quitar nota": se guarda sin rating vía update directo
  isSaving.value = true;
  error.value = null;
  try {
    const { data } = await api.get(`/api/movies/${props.movieId}/my-review`);
    if (data?.id) {
      await api.put(`/api/reviews/${data.id}`, { rating: null, content: data.content });
    }
    rating.value = 0;
    message.value = 'Calificación eliminada.';
  } catch (e) {
    console.error(e);
    error.value = 'No se pudo eliminar.';
  } finally {
    isSaving.value = false;
  }
};
</script>
