<template>
  <div>
    <form @submit.prevent="createList" class="glass-panel rounded-xl p-5 mb-8 flex flex-col md:flex-row gap-3 md:items-end">
      <div class="flex-1 flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant">Nueva lista</label>
        <input
          v-model="form.name"
          required
          maxlength="255"
          placeholder="Nombre de la lista"
          class="rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md text-on-surface outline-none focus:border-secondary"
        />
      </div>
      <div class="flex-1 flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant">Descripción</label>
        <input
          v-model="form.description"
          maxlength="500"
          placeholder="Opcional"
          class="rounded-full border border-outline-variant/50 bg-white px-4 py-2 font-body-md text-body-md text-on-surface outline-none focus:border-secondary"
        />
      </div>
      <label class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant whitespace-nowrap pb-2">
        <input type="checkbox" v-model="form.is_public" class="accent-[#006a68]" />
        Pública
      </label>
      <Button type="submit" :label="isSaving ? 'Creando...' : 'Crear lista'" variant="primary" :disabled="isSaving" />
    </form>
    <p v-if="error" class="mb-4 p-3 rounded bg-error-container text-on-error-container text-sm">{{ error }}</p>

    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando tus listas...
    </div>

    <div v-else-if="lists.length === 0" class="glass-panel rounded-xl p-8 text-center font-body-md text-body-md text-on-surface-variant">
      Aún no tienes listas. Crea la primera arriba.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <a
        v-for="list in lists"
        :key="list.id"
        :href="`/${username}/lists/${list.slug}`"
        class="group overflow-hidden rounded-xl border border-outline-variant/20 bg-surface/60 hover:-translate-y-1 transition-transform"
      >
        <div class="p-5">
          <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-secondary transition-colors">{{ list.name }}</h3>
          <p v-if="list.description" class="mt-2 font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ list.description }}</p>
          <div class="mt-4 flex items-center gap-3">
            <span class="font-label-sm text-label-sm text-secondary">{{ list.items_count ?? 0 }} películas</span>
            <span v-if="!list.is_public" class="font-label-sm text-label-sm px-3 py-1 rounded-full border border-outline-variant/50 text-on-surface-variant">Privada</span>
          </div>
        </div>
      </a>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import api from '../services/api';
import Button from './Button.vue';

const lists = ref([]);
const username = ref('');
const isLoading = ref(true);
const isSaving = ref(false);
const error = ref(null);

const form = reactive({ name: '', description: '', is_public: true });

const load = async () => {
  isLoading.value = true;
  try {
    const me = await api.get('/api/user');
    username.value = me.data.name;
    const { data } = await api.get(`/api/users/${me.data.id}/lists`);
    lists.value = data.data ?? data ?? [];
  } catch (e) {
    console.error('Error cargando listas:', e);
    if (e.response?.status === 401) window.location.href = '/login';
  } finally {
    isLoading.value = false;
  }
};

const createList = async () => {
  if (!form.name.trim()) return;
  isSaving.value = true;
  error.value = null;
  try {
    const { data } = await api.post('/api/lists', {
      name: form.name.trim(),
      description: form.description.trim() || null,
      is_public: form.is_public,
    });
    lists.value.unshift({ ...data.list, items_count: 0 });
    form.name = '';
    form.description = '';
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || 'No se pudo crear la lista.';
  } finally {
    isSaving.value = false;
  }
};

onMounted(load);
</script>
