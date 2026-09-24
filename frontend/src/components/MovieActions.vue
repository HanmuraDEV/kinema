<template>
  <div class="flex flex-col gap-3">
    <div v-if="!isAuthenticated" class="glass-panel rounded-xl p-4 text-center">
      <p class="font-body-md text-body-md text-on-surface-variant mb-3 text-sm">
        Inicia sesión para registrar esta película.
      </p>
      <a href="/login">
        <Button label="Iniciar Sesión" variant="primary" class="w-full" />
      </a>
    </div>

    <template v-else>
      <Button
        :label="watched ? '✓ Vista' : (isSaving ? 'Guardando...' : 'Marcar vista')"
        :variant="watched ? 'secondary' : 'primary'"
        icon="visibility"
        :disabled="isSaving"
        class="w-full"
        @click="markWatched"
      />
      <Button
        label="Añadir a lista"
        variant="secondary"
        icon="add"
        class="w-full"
        @click="showModal = true"
      />
    </template>

    <p v-if="message" class="text-sm text-on-surface-variant">{{ message }}</p>
    <p v-if="error" class="p-3 rounded bg-error-container text-on-error-container text-sm">{{ error }}</p>

    <!-- Popup con mis listas -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-inverse-surface/50"
      @click.self="showModal = false"
    >
      <div class="glass-panel rounded-xl p-6 w-full max-w-sm ambient-shadow-secondary">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-headline-md text-headline-md text-on-surface">Añadir a lista</h3>
          <button @click="showModal = false" class="text-on-surface-variant hover:text-on-surface cursor-pointer" title="Cerrar">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div v-if="lists.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center py-4">
          No tienes listas todavía.
          <a href="/lists" class="block mt-2 text-secondary hover:underline">Crear una</a>
        </div>

        <div v-else class="flex flex-col gap-2 max-h-64 overflow-y-auto">
          <button
            v-for="list in lists"
            :key="list.id"
            @click="addToList(list.id)"
            :disabled="isSaving"
            class="flex items-center justify-between gap-3 rounded-full border border-outline-variant/40 px-4 py-2 font-body-md text-body-md text-on-surface hover:border-secondary hover:bg-secondary/5 transition-colors cursor-pointer disabled:opacity-50"
          >
            <span class="truncate">{{ list.name }}</span>
            <span class="font-label-sm text-label-sm text-on-surface-variant shrink-0">{{ list.items_count ?? 0 }}</span>
          </button>
        </div>
      </div>
    </div>
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
const watched = ref(false);
const showModal = ref(false);
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
    const mine = await api.get(`/api/movies/${props.movieId}/my-review`);
    watched.value = !!mine.data?.watched_at;
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
    message.value = 'Marcada como vista.';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo registrar.';
  } finally {
    isSaving.value = false;
  }
};

const addToList = async (listId) => {
  isSaving.value = true;
  error.value = null;
  message.value = null;
  try {
    await api.post(`/api/lists/${listId}/items`, {
      movie_id: Number(props.movieId),
    });
    const target = lists.value.find((l) => l.id === listId);
    message.value = target ? `Añadida a “${target.name}”.` : 'Añadida a la lista.';
    showModal.value = false;
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo añadir a la lista.';
  } finally {
    isSaving.value = false;
  }
};
</script>
