<template>
  <aside class="lg:sticky lg:top-24 self-start space-y-6">
    <div class="glass-panel rounded-xl p-5">
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
        <h2 class="font-headline-md text-headline-md text-on-surface">Explorar por Vibra</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant mb-4 opacity-80">Clusters del catálogo</p>
      <div class="flex flex-wrap gap-2">
        <a
          v-for="tag in vibeTags"
          :key="tag.id ?? tag.name"
          :href="withParam('vibe', tag.name)"
          :class="['px-4 py-2 rounded-full border font-label-sm text-label-sm transition-colors', isActive('vibe', tag.name) ? 'bg-secondary text-on-secondary border-secondary' : 'border-secondary text-secondary hover:bg-secondary/10']"
        >
          {{ tag.name }}
        </a>
      </div>
      <a v-if="current.vibe" href="/search" class="inline-block mt-3 font-label-sm text-label-sm text-on-surface-variant hover:text-error transition-colors">
        Quitar filtro ✕
      </a>
    </div>

    <div class="glass-panel rounded-xl p-5">
      <h2 class="font-headline-md text-headline-md text-on-surface mb-6">Filtros</h2>
      <div class="space-y-4">
        <form action="/search" method="get">
          <input v-if="current.q" type="hidden" name="q" :value="current.q" />
          <input v-if="current.vibe" type="hidden" name="vibe" :value="current.vibe" />
          <input v-if="current.genre" type="hidden" name="genre" :value="current.genre" />
          <input v-if="current.person" type="hidden" name="person" :value="current.person" />
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2">
            Año
          </label>
          <div class="flex gap-2">
            <input name="year_from" :value="current.year_from" class="w-full rounded-full border border-outline-variant/50 bg-white px-3 py-2 font-body-md text-body-md" placeholder="Desde" type="number" min="1900" max="2030" />
            <input name="year_to" :value="current.year_to" class="w-full rounded-full border border-outline-variant/50 bg-white px-3 py-2 font-body-md text-body-md" placeholder="Hasta" type="number" min="1900" max="2030" />
          </div>
          <button type="submit" class="mt-3 w-full rounded-full bg-secondary px-4 py-2 font-label-md text-label-md text-on-secondary hover:bg-secondary/90 transition-colors cursor-pointer">
            Aplicar años
          </button>
        </form>
        <hr class="border-outline-variant/30" />
        <div>
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2">
            Género
          </label>
          <div class="flex flex-wrap gap-2">
            <a
              v-for="g in genres"
              :key="g.name"
              :href="withParam('genre', g.name)"
              :class="['px-3 py-1 rounded-full font-label-sm text-label-sm transition-colors', isActive('genre', g.name) ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface-variant hover:bg-secondary/10']"
            >
              {{ g.name }} ({{ g.count }})
            </a>
          </div>
        </div>
        <hr class="border-outline-variant/30" />
        <form action="/search" method="get">
          <input v-if="current.q" type="hidden" name="q" :value="current.q" />
          <input v-if="current.vibe" type="hidden" name="vibe" :value="current.vibe" />
          <input v-if="current.genre" type="hidden" name="genre" :value="current.genre" />
          <input v-if="current.year_from" type="hidden" name="year_from" :value="current.year_from" />
          <input v-if="current.year_to" type="hidden" name="year_to" :value="current.year_to" />
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2">
            Director o actor
          </label>
          <div class="flex gap-2">
            <input name="person" :value="current.person" class="w-full rounded-full border border-outline-variant/50 bg-white px-3 py-2 font-body-md text-body-md" placeholder="Nombre..." type="text" />
            <button type="submit" class="shrink-0 rounded-full bg-secondary px-4 py-2 font-label-md text-label-md text-on-secondary hover:bg-secondary/90 transition-colors cursor-pointer">
              Ir
            </button>
          </div>
        </form>
        <a v-if="hasAnyFilter" href="/search" class="block text-center font-label-md text-label-md text-error hover:underline pt-2">
          Limpiar todo ✕
        </a>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  vibeTags: { type: Array, default: () => [] },
  genres: { type: Array, default: () => [] },
  current: {
    type: Object,
    default: () => ({ q: '', vibe: '', year_from: '', year_to: '', genre: '', person: '' }),
  },
});

// Construye /search mezclando los params actuales con el cambio dado.
// Valor vacío = quitar ese filtro.
const withParam = (key, value) => {
  const params = new URLSearchParams();
  for (const k of ['q', 'vibe', 'year_from', 'year_to', 'genre', 'person']) {
    const v = key === k ? value : props.current[k];
    if (v !== undefined && v !== null && String(v).trim() !== '') {
      params.set(k, String(v).trim());
    }
  }
  const qs = params.toString();
  return qs ? `/search?${qs}` : '/search';
};

const isActive = (key, value) =>
  String(props.current[key] ?? '').toLowerCase() === String(value ?? '').toLowerCase();

const hasAnyFilter = computed(() =>
  ['vibe', 'year_from', 'year_to', 'genre', 'person'].some(
    (k) => String(props.current[k] ?? '').trim() !== ''
  )
);
</script>
