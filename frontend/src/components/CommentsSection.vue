<template>
  <section>
    <h2 class="mb-6 flex items-center gap-2 font-headline-md text-headline-md font-bold text-on-surface">
      <span class="material-symbols-outlined text-secondary">chat_bubble</span>
      Comentarios ({{ total }})
    </h2>

    <div v-if="isAuthenticated" class="mb-8 flex items-start gap-4 border-b border-outline-variant/30 pb-8">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container font-bold text-on-primary-container">
        {{ (user?.name || '?').charAt(0).toUpperCase() }}
      </div>
      <div class="flex-1">
        <div v-if="error" class="mb-2 p-3 rounded bg-error-container text-on-error-container text-sm">{{ error }}</div>
        <textarea
          v-model="draft"
          class="w-full resize-none rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-3 font-body-md text-body-md text-on-surface outline-none transition-all focus:border-secondary"
          placeholder="Añade un comentario..."
          rows="2"
          maxlength="1000"
        />
        <div class="mt-2 flex justify-end">
          <Button :label="isPosting ? 'Publicando...' : 'Publicar'" variant="primary" :disabled="isPosting || !draft.trim()" @click="postComment" />
        </div>
      </div>
    </div>
    <div v-else class="mb-8 glass-panel rounded-xl p-4 text-center font-body-md text-body-md text-on-surface-variant">
      <a href="/login" class="text-secondary hover:underline">Inicia sesión</a> para comentar.
    </div>

    <div class="space-y-4">
      <div v-for="comment in comments" :key="comment.id" class="flex gap-4 border-b border-outline-variant/20 py-4 last:border-0">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-container-high font-bold text-secondary">
          {{ (comment.user?.name || '?').charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1">
          <div class="mb-1 flex items-baseline gap-2">
            <span class="font-label-md text-label-md font-bold text-on-surface">{{ comment.user?.name }}</span>
            <span class="font-label-sm text-label-sm font-normal text-on-surface-variant">{{ formatDate(comment.created_at) }}</span>
          </div>
          <p class="font-body-md text-body-md text-on-surface-variant">{{ comment.content }}</p>
          <div v-if="user && comment.user?.id === user.id" class="mt-2 flex gap-3">
            <button @click="removeComment(comment.id)" class="font-label-sm text-label-sm text-outline hover:text-error transition-colors cursor-pointer">Eliminar</button>
          </div>
        </div>
      </div>
      <p v-if="!isLoading && comments.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center py-4">
        Sé la primera persona en comentar.
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const props = defineProps({
  reviewId: { type: [Number, String], required: true },
  initialComments: { type: Array, default: () => [] },
});

const { user, isAuthenticated, checkAuth } = useAuth();
const comments = ref(props.initialComments);
const total = ref(props.initialComments.length);
const draft = ref('');
const isLoading = ref(false);
const isPosting = ref(false);
const error = ref(null);

const formatDate = (iso) => {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch { return ''; }
};

const load = async () => {
  isLoading.value = true;
  try {
    const { data } = await api.get(`/api/reviews/${props.reviewId}/comments`);
    const list = data.data ?? data ?? [];
    comments.value = Array.isArray(list) ? list : [];
    total.value = data.total ?? comments.value.length;
  } catch (e) {
    console.error('Error cargando comentarios:', e);
  } finally {
    isLoading.value = false;
  }
};

const postComment = async () => {
  if (!draft.value.trim()) return;
  isPosting.value = true;
  error.value = null;
  try {
    const { data } = await api.post(`/api/reviews/${props.reviewId}/comments`, { content: draft.value.trim() });
    comments.value.unshift(data.comment);
    total.value += 1;
    draft.value = '';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo publicar el comentario.';
  } finally {
    isPosting.value = false;
  }
};

const removeComment = async (id) => {
  try {
    await api.delete(`/api/comments/${id}`);
    comments.value = comments.value.filter((c) => c.id !== id);
    total.value = Math.max(0, total.value - 1);
  } catch (e) {
    console.error('Error eliminando comentario:', e);
  }
};

onMounted(async () => {
  await checkAuth();
  await load();
});
</script>
