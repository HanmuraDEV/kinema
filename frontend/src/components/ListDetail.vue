<template>
  <div>
    <div v-if="isLoading" class="py-12 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando lista...
    </div>

    <div v-else-if="loadError" class="glass-panel rounded-xl p-8 text-center">
      <p class="font-body-md text-body-md text-on-surface-variant mb-2">No pudimos cargar esta lista.</p>
      <p class="font-label-sm text-label-sm text-on-surface-variant/70">Puede ser privada o ya no existe.</p>
      <a href="/lists" class="inline-block mt-4 font-label-md text-label-md text-secondary hover:underline">Mis listas</a>
    </div>

    <template v-else>
      <!-- Encabezado con datos reales (SSR primero, cliente después) -->
      <section class="mb-12 pb-8 border-b border-outline-variant/30">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center font-bold text-[12px] text-on-secondary-container">
                {{ (list?.user?.name || '?').charAt(0).toUpperCase() }}
              </div>
              <span class="font-label-md text-label-md text-on-surface-variant">
                Creado por @{{ list?.user?.name }}
              </span>
              <span v-if="list && !list.is_public" class="font-label-sm text-label-sm px-3 py-1 rounded-full border border-outline-variant/50 text-on-surface-variant">Privada</span>
            </div>

            <h1 class="font-headline-xl text-headline-xl text-primary mb-4">{{ list?.name }}</h1>
            <p v-if="list?.description" class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
              {{ list.description }}
            </p>
            <p class="mt-2 font-label-sm text-label-sm text-on-surface-variant">{{ items.length }} películas</p>
          </div>

          <div v-if="isOwner" class="flex flex-wrap gap-4">
            <Button
              icon="edit"
              :label="showSettings ? 'Cerrar' : 'Editar lista'"
              variant="secondary"
              @click="showSettings = !showSettings"
            />
          </div>
        </div>

        <!-- Ajustes de la lista (solo dueño) -->
        <div v-if="isOwner && showSettings" class="mt-6 glass-panel rounded-xl p-5">
          <div v-if="formError" class="mb-3 p-3 rounded bg-error-container text-on-error-container text-sm">{{ formError }}</div>
          <div v-if="formOk" class="mb-3 p-3 rounded bg-primary-container text-on-primary-container text-sm">{{ formOk }}</div>
          <div class="flex flex-col md:flex-row gap-3 md:items-end">
            <div class="flex-1 flex flex-col gap-1">
              <label class="font-label-md text-label-md text-on-surface-variant">Nombre</label>
              <input
                v-model="settingsForm.name"
                maxlength="255"
                class="rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md text-on-surface outline-none focus:border-secondary"
              />
            </div>
            <div class="flex-[2] flex flex-col gap-1">
              <label class="font-label-md text-label-md text-on-surface-variant">Descripción</label>
              <input
                v-model="settingsForm.description"
                maxlength="500"
                class="rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md text-on-surface outline-none focus:border-secondary"
              />
            </div>
            <label class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant whitespace-nowrap pb-2">
              <input type="checkbox" v-model="settingsForm.is_public" class="accent-[#006a68]" />
              Pública
            </label>
            <Button label="Guardar" variant="primary" :disabled="isSaving" @click="saveSettings" />
            <Button label="Eliminar" variant="tertiary" :disabled="isSaving" @click="deleteList" />
          </div>
        </div>
      </section>

      <!-- Añadir (solo dueño) -->
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
        <p v-if="formError && !showSettings" class="mt-2 text-sm text-error">{{ formError }}</p>
      </section>

      <!-- Películas -->
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
    </template>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import api from '../services/api';
import Button from './Button.vue';

const props = defineProps({
  listId: { type: [Number, String], default: null },
  username: { type: String, default: '' },
  slug: { type: String, default: '' },
  initialList: { type: Object, default: null },
  initialItems: { type: Array, default: () => [] },
});

const list = ref(props.initialList);
const items = ref(props.initialItems);
const isOwner = ref(false);
const isLoading = ref(!props.initialList);
const loadError = ref(false);
const showSettings = ref(false);
const query = ref('');
const searchResults = ref([]);
const isSaving = ref(false);
const formError = ref(null);
const formOk = ref(null);

const settingsForm = reactive({ name: '', description: '', is_public: true });
let timer = null;

const syncSettingsForm = () => {
  settingsForm.name = list.value?.name ?? '';
  settingsForm.description = list.value?.description ?? '';
  settingsForm.is_public = list.value ? !!list.value.is_public : true;
};

const load = async () => {
  // 1) Detalle: SSR primero; si falta (lista privada vista sin sesión en SSR),
  //    se pide con el token del navegador
  if (!list.value && props.username && props.slug) {
    isLoading.value = true;
    try {
      const { data } = await api.get(
        `/api/users/${encodeURIComponent(props.username)}/lists/${encodeURIComponent(props.slug)}`
      );
      list.value = data;
      items.value = data.items ?? [];
    } catch (e) {
      console.error('Error cargando lista:', e);
      loadError.value = true;
      isLoading.value = false;
      return;
    }
  } else if (list.value) {
    items.value = list.value.items ?? props.initialItems;
  }
  syncSettingsForm();
  isLoading.value = false;

  // 2) ¿Dueño? Solo con sesión válida
  if (typeof localStorage !== 'undefined' && localStorage.getItem('kinema_token') && list.value?.id) {
    try {
      const { data } = await api.get('/api/user');
      const detail = await api.get(`/api/lists/${list.value.id}`);
      isOwner.value = data && detail.data && Number(detail.data.user_id) === Number(data.id);
    } catch { isOwner.value = false; }
  }
};

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

const refreshItems = async () => {
  const { data } = await api.get(`/api/lists/${list.value.id}`);
  items.value = data.items ?? [];
};

const addMovie = async (movieId) => {
  isSaving.value = true;
  formError.value = null;
  try {
    await api.post(`/api/lists/${list.value.id}/items`, { movie_id: movieId });
    await refreshItems();
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
    await api.delete(`/api/lists/${list.value.id}/items/${movieId}`);
    items.value = items.value.filter((i) => (i.movie?.id ?? i.movie_id) !== movieId);
  } catch (e) {
    console.error('Error quitando película:', e);
  }
};

const saveSettings = async () => {
  if (!settingsForm.name.trim()) return;
  isSaving.value = true;
  formError.value = null;
  formOk.value = null;
  try {
    const { data } = await api.put(`/api/lists/${list.value.id}`, {
      name: settingsForm.name.trim(),
      description: settingsForm.description.trim() || null,
      is_public: settingsForm.is_public,
    });
    list.value = { ...list.value, ...data.list };
    syncSettingsForm();
    formOk.value = 'Lista actualizada.';
  } catch (e) {
    formError.value = e.response?.data?.message || 'No se pudo guardar.';
  } finally {
    isSaving.value = false;
  }
};

const deleteList = async () => {
  if (!confirm(`¿Eliminar la lista “${list.value?.name}” para siempre?`)) return;
  isSaving.value = true;
  try {
    await api.delete(`/api/lists/${list.value.id}`);
    window.location.href = '/lists';
  } catch (e) {
    formError.value = e.response?.data?.message || 'No se pudo eliminar.';
    isSaving.value = false;
  }
};

onMounted(load);
</script>
