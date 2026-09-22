<template>
  <div class="flex h-full flex-col items-center justify-center text-center">
    <div v-if="phase === 'done'" class="glass-panel rounded-xl p-6 w-full">
      <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
      <h3 class="mt-2 mb-2 font-headline-md text-headline-md text-on-surface">¡Historial importado!</h3>
      <div class="font-body-md text-body-md text-on-surface-variant space-y-1">
        <p v-for="line in summaryLines" :key="line">{{ line }}</p>
      </div>
      <a href="/" class="inline-block mt-4">
        <Button label="Ver mi cine" variant="primary" />
      </a>
    </div>

    <template v-else>
      <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-secondary-container/30 text-secondary">
        <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">upload_file</span>
      </div>
      <h2 class="mb-4 font-headline-md text-headline-md text-on-surface">¿Vienes de Letterboxd?</h2>
      <p class="mb-8 max-w-sm font-body-md text-body-md text-on-surface-variant">
        No pierdas tu historial. Sube el ZIP exportado y organizaremos tus películas.
      </p>

      <div v-if="error" class="mb-4 p-3 rounded bg-error-container text-on-error-container text-sm max-w-sm">{{ error }}</div>

      <div v-if="phase === 'uploading'" class="w-full max-w-sm">
        <h3 class="mb-2 font-headline-md text-headline-md text-secondary">Importando tu historial...</h3>
        <div class="mb-4 h-4 w-full overflow-hidden rounded-full bg-surface-container-highest">
          <div class="progress-flat h-full w-3/4 rounded-full"></div>
        </div>
      </div>

      <label v-else class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-secondary px-8 py-3 font-label-md text-label-md text-on-secondary transition-colors hover:bg-secondary/90 ambient-shadow-secondary">
        <span class="material-symbols-outlined">attach_file</span>
        Seleccionar archivo ZIP
        <input accept=".zip" class="hidden" type="file" @change="onFile" />
      </label>
      <p class="mt-4 font-label-sm text-label-sm text-on-surface-variant/70">El ZIP de Letterboxd tal cual se descarga (hasta 20MB)</p>
      <p v-if="!isAuthenticated" class="mt-2 font-label-sm text-label-sm text-on-surface-variant/70">
        <a href="/login" class="text-secondary hover:underline">Inicia sesión</a> para importar a tu cuenta.
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { isAuthenticated, checkAuth } = useAuth();
const phase = ref('idle'); // idle | uploading | done
const error = ref(null);
const summary = ref({});

const LABELS = {
  ratings: 'valoraciones',
  watched: 'vistas en el diario',
  reviews_text: 'reseñas con texto',
  watchlisted: 'en watchlist',
  liked: 'likes importados',
  lists: 'listas creadas',
  list_items: 'películas en listas',
  movies_matched: 'películas del catálogo',
  movies_created: 'películas nuevas',
  enriched: 'enriquecidas con TMDB',
  merged: 'fusionadas sin duplicar',
};

const summaryLines = computed(() =>
  Object.entries(summary.value)
    .filter(([, v]) => Number(v) > 0)
    .map(([k, v]) => `${v} ${LABELS[k] || k}`)
);

onMounted(() => checkAuth());

const onFile = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  error.value = null;

  if (!isAuthenticated.value) {
    window.location.href = '/login';
    return;
  }

  phase.value = 'uploading';
  try {
    const form = new FormData();
    form.append('file', file);
    const { data } = await api.post('/api/import/letterboxd', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000, // el enrich TMDB tarda según el tamaño del historial
    });
    summary.value = data.summary ?? {};
    phase.value = 'done';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo importar el archivo.';
    phase.value = 'idle';
  }
};
</script>
