<template>
  <section class="min-w-0">
    <div class="flex justify-between items-center mb-6 gap-3 flex-wrap">
      <span class="font-label-md text-label-md text-on-surface-variant">
        <template v-if="initialQuery">Mostrando resultados para <strong class="text-secondary">"{{ initialQuery }}"</strong></template>
        <template v-else>Explora el catálogo</template>
      </span>
    </div>

    <!-- Personas -->
    <div v-if="people.length > 0" class="mb-8">
      <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Personas</h3>
      <div class="flex gap-4 overflow-x-auto pb-2 no-scrollbar">
        <a
          v-for="person in people"
          :key="person.id"
          :href="`/artists/${person.id}/profile`"
          class="flex flex-none items-center gap-3 glass-panel rounded-full pl-2 pr-5 py-2 hover:-translate-y-0.5 transition-transform"
        >
          <div class="w-10 h-10 rounded-full overflow-hidden bg-surface-container-high flex items-center justify-center font-bold text-secondary">
            <img v-if="person.profile_path" :alt="person.name" class="w-full h-full object-cover" :src="person.profile_path" />
            <span v-else>{{ person.name.charAt(0).toUpperCase() }}</span>
          </div>
          <div>
            <p class="font-label-md text-label-md text-on-surface">{{ person.name }}</p>
            <p v-if="person.known_for_department" class="font-label-sm text-label-sm text-on-surface-variant">{{ person.known_for_department }}</p>
          </div>
        </a>
      </div>
    </div>

    <!-- Listas -->
    <div v-if="lists.length > 0" class="mb-8">
      <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Listas</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a
          v-for="list in lists"
          :key="list.id"
          :href="`/${list.user?.name}/lists/${list.slug}`"
          class="glass-panel rounded-xl p-4 hover:-translate-y-1 transition-transform"
        >
          <p class="font-label-sm text-label-sm text-on-surface-variant">@{{ list.user?.name }}</p>
          <h4 class="font-headline-md text-headline-md text-on-surface">{{ list.name }}</h4>
          <p v-if="list.description" class="mt-1 font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ list.description }}</p>
          <span class="mt-2 block font-label-sm text-label-sm text-secondary">{{ list.items_count ?? 0 }} películas</span>
        </a>
      </div>
    </div>

    <!-- Películas -->
    <div v-if="results.length > 0">
      <h3 v-if="people.length > 0 || lists.length > 0" class="font-headline-md text-headline-md text-on-surface mb-4">Películas</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
        <a
          v-for="movie in results"
          :key="movie.id ?? movie.title"
          :href="movie.id ? `/movie?id=${movie.id}` : '#'"
          class="group relative rounded-xl overflow-hidden ambient-shadow-secondary bg-white"
        >
          <div class="aspect-[2/3] w-full bg-surface-container-high relative overflow-hidden">
            <img
              v-if="movie.poster_path || movie.image"
              :alt="movie.title"
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
              :src="movie.poster_path || movie.image"
            />
            <div v-else class="w-full h-full flex items-center justify-center font-headline-md text-headline-md text-outline p-4 text-center">
              {{ movie.title }}
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
              <h3 class="font-headline-md text-headline-md text-white">{{ movie.title }}</h3>
              <div class="flex justify-between items-center mt-1">
                <span class="font-label-sm text-label-sm text-white/80">{{ (movie.release_date || movie.year || '').toString().slice(0, 4) }}</span>
                <span v-if="movie.vibe" class="font-label-sm text-label-sm text-tertiary-container">{{ movie.vibe.name }}</span>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

    <p v-if="initialQuery.trim().length >= 2 && results.length === 0 && lists.length === 0 && people.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center py-8">
      Sin resultados para "{{ initialQuery }}".
    </p>
  </section>
</template>

<script setup>
// Presentacional puro: la búsqueda vive en la navbar (?q=) y Astro
// renderiza los resultados en servidor. Sin estado ni fetch de cliente.
const props = defineProps({
  initialResults: { type: Array, default: () => [] },
  initialLists: { type: Array, default: () => [] },
  initialPeople: { type: Array, default: () => [] },
  initialQuery: { type: String, default: '' },
});

// Alias estáticos (las props no cambian tras el SSR)
const results = props.initialResults;
const lists = props.initialLists;
const people = props.initialPeople;
</script>
