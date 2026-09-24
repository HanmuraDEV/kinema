<template>
  <div class="glass-panel rounded-xl p-6 ambient-shadow-secondary">
    <div class="flex items-end justify-between mb-2">
      <div>
        <h3 class="font-headline-md text-headline-md text-primary">Mapa del cine</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">
          Cada punto es una película, agrupada por su vibra. Pasa el cursor y haz clic para abrirla.
        </p>
      </div>
      <span class="hidden md:inline font-label-sm text-label-sm text-on-surface-variant">{{ points.length }} películas</span>
    </div>

    <div v-if="isLoading" class="py-12 text-center text-on-surface-variant font-body-md text-body-md">
      Dibujando el mapa...
    </div>

    <svg v-else :viewBox="`0 0 ${W} ${H}`" class="w-full h-auto select-none" role="img" aria-label="Mapa semántico del catálogo">
      <g v-for="p in points" :key="p.id">
        <a :href="`/movie?id=${p.id}`">
          <circle
            :cx="p.x" :cy="p.y" :r="p.r"
            :fill="p.color" fill-opacity="0.75"
            class="hover:stroke-secondary transition-all"
          >
            <title>{{ p.title }} ({{ p.vibe }})</title>
          </circle>
        </a>
      </g>
    </svg>

    <div v-if="legend.length > 0" class="mt-4 flex flex-wrap gap-2">
      <span
        v-for="v in legend"
        :key="v.name"
        class="inline-flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant border border-outline-variant/30 rounded-full px-3 py-1"
      >
        <span class="inline-block h-3 w-3 rounded-full" :style="{ background: v.color }"></span>
        {{ v.name }} ({{ v.count }})
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const W = 600;
const H = 380;

const PALETTE = [
  '#396940', '#006a68', '#9f3f39', '#7c5cbf', '#b87a1e',
  '#2f7fc4', '#c44fa3', '#5a9e3f', '#d45555', '#3fa7a3',
  '#8a6d3b', '#6b7fd4',
];

const points = ref([]);
const legend = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/api/analytics/map');
    const movies = Array.isArray(data) ? data : [];
    if (movies.length === 0) return;

    const xs = movies.map((m) => Number(m.x_coordinate));
    const ys = movies.map((m) => Number(m.y_coordinate));
    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);

    const vibes = [...new Set(movies.map((m) => m.vibe?.name || 'Sin vibra'))];
    const colorOf = (name) => PALETTE[vibes.indexOf(name) % PALETTE.length];

    const counts = {};
    points.value = movies.map((m) => {
      const vn = m.vibe?.name || 'Sin vibra';
      counts[vn] = (counts[vn] || 0) + 1;
      const nx = maxX === minX ? 0.5 : (Number(m.x_coordinate) - minX) / (maxX - minX);
      const ny = maxY === minY ? 0.5 : (Number(m.y_coordinate) - minY) / (maxY - minY);
      return {
        id: m.id,
        title: m.title,
        vibe: vn,
        color: colorOf(vn),
        x: +(30 + nx * (W - 60)).toFixed(1),
        y: +(30 + ny * (H - 60)).toFixed(1),
        r: m.poster_path ? 7 : 5,
      };
    });

    legend.value = Object.entries(counts)
      .map(([name, count]) => ({ name, count, color: colorOf(name) }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 8);
  } catch (e) {
    console.error('Error cargando mapa:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
