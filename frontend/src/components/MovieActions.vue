<template>
  <div class="glass-panel rounded-xl p-6 ambient-shadow-secondary">
    <div v-if="!isAuthenticated" class="text-center">
      <p class="font-body-md text-body-md text-on-surface-variant mb-4">
        Inicia sesión para registrar esta película.
      </p>
      <a href="/login">
        <Button label="Iniciar Sesión" variant="primary" />
      </a>
    </div>

    <div v-else class="flex flex-wrap items-center gap-3">
      <Button
        :label="watched ? '✓ Vista' : (isSaving ? 'Guardando...' : 'Marcar vista')"
        :variant="watched ? 'secondary' : 'primary'"
        icon="visibility"
        :disabled="isSaving"
        @click="markWatched"
      />

      <div class="flex items-center gap-2 flex-1 min-w-[220px]">
        <select
          v-model="selectedList"
          class="flex-1 rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md text-on-surface outline-none focus:border-secondary"
        >
          <option value="" disabled>Añadir a lista...</option>
          <option v-for="list in lists" :key="list.id" :value="list.id">{{ list.name }}</option>
        </select>
        <Button label="Añadir" variant="secondary" :disabled="!selectedList || isSaving" @click="addToList" />
      </div>

      <a href="/lists" class="font-label-md text-label-md text-secondary hover:underline whitespace-nowrap">
        Mis listas
      </a>
    </div>

    <p v-if="message" class="mt-3 text-sm text-on-surface-variant">{{ message }}</p>
    <p v-if="error" class="mt-3 p-3 rounded bg-error-container text-on-error-container text-sm">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const props = defineProps({
  movieId: { type: [Number, String], required: true },
  movieTitle: { type: String, default: '' },
});

const { isAuthenticated, checkAuth } = useAuth();
const lists = ref([]);
const selectedList = ref('');
const watched = ref(false);
const isSaving = ref(false);
const message = ref(null);
const error = ref(null);

const today = () => new Date().toISOString().slice(0, 10);

onMounted(async () => {
  await checkAuth();
  if (!isAuthenticated.value) return;
  try {
    const me = await api.get('/api/user');
    const { data } = await api.get(`/api/users/${me.data.id}/lists`);
    lists.value = data.data ?? data ?? [];
    // ¿Ya la vio? (reseña con watched_at para esta película)
    const { data: reviews } = await api.get(`/api/users/${me.data.id}/reviews`);
    const mine = (reviews.data ?? reviews ?? []).find(
      (r) => Number(r.movie?.id ?? r.movie_id) === Number(props.movieId) && r.watched_at
    );
    watched.value = !!mine;
  } catch (e) {
    console.error('Error cargando estado:', e);
  }
});

const markWatched = async () => {
  isSaving.value = true;
  error.value = null;
  message.value = null;
  try {
    await api.post('/api/reviews', {
      movie_id: Number(props.movieId),
      watched_at: today(),
    });
    watched.value = true;
    message.value = props.movieTitle
      ? `“${props.movieTitle}” marcada como vista.`
      : 'Película marcada como vista.';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo registrar.';
  } finally {
    isSaving.value = false;
  }
};

const addToList = async () => {
  if (!selectedList.value) return;
  isSaving.value = true;
  error.value = null;
  message.value = null;
  try {
    await api.post(`/api/lists/${selectedList.value}/items`, {
      movie_id: Number(props.movieId),
    });
    message.value = 'Añadida a la lista.';
    selectedList.value = '';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo añadir a la lista.';
  } finally {
    isSaving.value = false;
  }
};
</script>
