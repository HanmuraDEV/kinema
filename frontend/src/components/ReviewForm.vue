<template>
  <div class="glass-panel rounded-xl p-6 ambient-shadow-secondary">
    <div v-if="!isAuthenticated" class="text-center">
      <p class="font-body-md text-body-md text-on-surface-variant mb-4">
        Inicia sesión para dejar tu reseña.
      </p>
      <a href="/login">
        <Button label="Iniciar Sesión" variant="primary" />
      </a>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="flex flex-col gap-4">
      <h3 class="font-headline-md text-headline-md text-on-surface">Tu reseña</h3>

      <div v-if="error" class="p-3 rounded bg-error-container text-on-error-container text-sm">{{ error }}</div>
      <div v-if="success" class="p-3 rounded bg-primary-container text-on-primary-container text-sm">{{ success }}</div>

      <div class="flex items-center gap-2">
        <label class="font-label-md text-label-md text-on-surface-variant">Calificación</label>
        <select v-model.number="form.rating" class="rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md">
          <option :value="null">Sin nota</option>
          <option v-for="n in [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5]" :key="n" :value="n">{{ n }}</option>
        </select>
        <label class="ml-4 flex items-center gap-2 font-label-md text-label-md text-on-surface-variant">
          <input type="checkbox" v-model="form.has_spoilers" class="accent-[#006a68]" />
          Spoilers
        </label>
      </div>

      <textarea
        v-model="form.content"
        rows="4"
        maxlength="2000"
        placeholder="¿Qué te pareció la película?"
        class="w-full rounded-xl border border-outline-variant/50 bg-white p-4 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-secondary"
      />

      <div class="flex justify-end">
        <Button type="submit" :label="isLoading ? 'Guardando...' : 'Publicar reseña'" variant="primary" :disabled="isLoading" />
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const props = defineProps({
  movieId: { type: [Number, String], required: true },
});

const { isAuthenticated, checkAuth } = useAuth();
const isLoading = ref(false);
const error = ref(null);
const success = ref(null);

const form = reactive({
  rating: null,
  content: '',
  has_spoilers: false,
});

onMounted(() => checkAuth());

const handleSubmit = async () => {
  isLoading.value = true;
  error.value = null;
  success.value = null;
  try {
    await api.post('/api/reviews', {
      movie_id: Number(props.movieId),
      rating: form.rating,
      content: form.content || null,
      has_spoilers: form.has_spoilers,
    });
    success.value = 'Reseña guardada con éxito.';
    form.content = '';
    form.rating = null;
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo guardar la reseña.';
  } finally {
    isLoading.value = false;
  }
};
</script>
