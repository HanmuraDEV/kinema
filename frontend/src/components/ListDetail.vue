<template>
  <div>
    <section v-if="isOwner" class="mb-12">
      <div class="border border-outline-variant rounded-full p-2 flex items-center relative glass-panel">
        <span class="material-symbols-outlined text-outline ml-3 absolute">search</span>
        <input
          v-model="query"
          @input="onInput"
          class="w-full bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface pl-10 py-3 rounded-full placeholder-outline-variant"
          placeholder="Buscar película para añadir..."
          type="text"
        />
      </div>
      <div v-if="searchResults.length > 0" class="mt-3 glass-panel rounded-xl p-3 space-y-2">
        <div v-for="m in searchResults" :key="m.id" class="flex items-center justify-between gap-3">
          <span class="font-body-md text-body-md text-on-surface truncate">{{ m.title }} <span class="text-on-surface-variant">({{ String(m.release_date || '').slice(0, 4) }})</span></span>
          <Button label="Añadir" variant="primary" @click="addMovie(m.id)" :disabled="isSaving" />
        </div>
      </div>
      <p v-if="formError" class="mt-2 text-sm text-error">{{ formError }}</p>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-4 gap-gutter">
      <article
        v-for="item in items"
        :key="item.movie?.id ?? item.movie_id"
        class="relative group rounded-xl overflow-hidden ambient-shadow-bondi hover:scale-[1.02] transition-transform duration-300"
      >
        <a :href="item.movie?.id ? `/movie?id=${item.movie.id}` : '#'">
          <img
            v-if="item.movie?.poster_path"
            :alt="item.movie.title"
            class="w-full aspect-[2/3] object-cover"
            :src="item.movie.poster_path"
          />
          <div v-else class="w-full aspect-[2/3] bg-surface-container-high flex items-center justify-center p-4 text-center font-label-md text-label-md text-on-surface-variant">
            {{ item.movie?.title }}
          </div>
        </a>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4 pointer-events-none">
          <h3 class="font-headline-md text-headline-md text-white">{{ item.movie?.title }}</h3>
        </div>
        <button
          v-if="isOwner"
          @click="removeMovie(item.movie?.id ?? item.movie_id)"
          title="Quitar de la lista"
          class="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white items-center justify-center hidden group-hover:flex hover:bg-error transition-colors cursor-pointer"
        >
          <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
      </article>
    </section>
    <p v-if="items.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center py-8">
      Esta lista aún no tiene películas.
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import Button from './Button.vue';

const props = defineProps({
  listId: { type: [Number, String], default: null },
  initialItems: { type: Array, default: () => [] },
});

const items = ref(props.initialItems);
const isOwner = ref(false);
const query = ref('');
const searchResults = ref([]);
const isSaving = ref(false);
const formError = ref(null);
let timer = null;

onMounted(async () => {
  // Solo el dueño ve los controles de añadir/quitar (requiere token válido)
  if (typeof localStorage === 'undefined' || !localStorage.getItem('kinema_token') || !props.listId) return;
  try {
    const { data } = await api.get('/api/user');
    // El detalle público no expone user_id al anónimo; lo pedimos de nuevo con auth
    const detail = await api.get(`/api/lists/${props.listId}`);
    isOwner.value = data && detail.data && Number(detail.data.user_id) === Number(data.id);
  } catch { isOwner.value = false; }
});

const onInput = () => {
  clearTimeout(timer);
  timer = setTimeout(async () => {
    const q = query.value.trim();
    if (q.length < 2) { searchResults.value = []; return; }
    try {
      const { data } = await api.get('/api/movies/search', { params: { q } });
      searchResults.value = (Array.isArray(data) ? data : []).slice(0, 5);
    } catch { searchResults.value = []; }
  }, 350);
};

const addMovie = async (movieId) => {
  isSaving.value = true;
  formError.value = null;
  try {
    await api.post(`/api/lists/${props.listId}/items`, { movie_id: movieId });
    const { data } = await api.get(`/api/lists/${props.listId}`);
    items.value = data.items ?? [];
    query.value = '';
    searchResults.value = [];
  } catch (e) {
    formError.value = e.response?.data?.message || 'No se pudo añadir la película.';
  } finally {
    isSaving.value = false;
  }
};

const removeMovie = async (movieId) => {
  try {
    await api.delete(`/api/lists/${props.listId}/items/${movieId}`);
    items.value = items.value.filter((i) => (i.movie?.id ?? i.movie_id) !== movieId);
  } catch (e) {
    console.error('Error quitando película:', e);
  }
};
</script>
