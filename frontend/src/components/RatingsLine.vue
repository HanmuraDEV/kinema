<template>
  <div class="flex flex-col items-center">
    <h3 class="font-headline-md text-headline-md mb-2 text-secondary">Calificaciones en Kinema</h3>
    <div v-if="rated.length === 0" class="font-body-md text-body-md text-on-surface-variant py-8 text-center">
      Sus películas aún no tienen votos de la comunidad.
    </div>
    <svg v-else :viewBox="`0 0 ${W} ${H}`" class="w-full h-auto" role="img" aria-label="Evolución de calificaciones">
      <!-- rejilla 1-5 -->
      <g v-for="s in [1, 2, 3, 4, 5]" :key="s">
        <line :x1="PAD" :x2="W - PAD" :y1="yOf(s)" :y2="yOf(s)" stroke="#c1c9be" stroke-opacity="0.4" stroke-width="1" />
        <text :x="PAD - 6" :y="yOf(s) + 3" text-anchor="end" font-size="8" fill="#717970">{{ s }}★</text>
      </g>
      <!-- segmentos (se cortan donde no hay votos) -->
      <polyline
        v-for="(seg, i) in segments"
        :key="i"
        :points="seg.map((p) => `${p.x},${p.y}`).join(' ')"
        fill="none" stroke="#006a68" stroke-width="2.5" stroke-linejoin="round"
      />
      <!-- puntos -->
      <g v-for="p in dots" :key="p.title + p.year">
        <circle :cx="p.x" :cy="p.y" r="4.5" :fill="p.avg === null ? '#ffffff' : '#006a68'" stroke="#006a68" stroke-width="2">
          <title>{{ p.title }} ({{ p.year }}): {{ p.avg === null ? 'sin votos' : `${p.avg}/5` }}</title>
        </circle>
        <text :x="p.x" :y="H - 6" text-anchor="middle" font-size="8" fill="#717970">{{ p.year }}</text>
      </g>
    </svg>
    <p class="mt-2 font-label-sm text-label-sm text-on-surface-variant">Evolución cronológica · {{ rated.length }} con votos</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  // [{title, year, avg|null}] ya ordenados cronológicamente
  points: { type: Array, default: () => [] },
});

const W = 420;
const H = 240;
const PAD = 28;

const yOf = (stars) => {
  const min = 0.5;
  const max = 5;
  const t = (Math.min(Math.max(stars, min), max) - min) / (max - min);
  return H - 24 - t * (H - 60);
};

const dots = computed(() => {
  const n = props.points.length;
  return props.points.map((p, i) => ({
    ...p,
    x: n === 1 ? W / 2 : PAD + (i * (W - PAD * 2)) / (n - 1),
    y: p.avg === null ? H - 24 : yOf(Number(p.avg)),
  }));
});

// Segmentos continuos solo donde hay voto (los null rompen la línea)
const segments = computed(() => {
  const segs = [];
  let current = [];
  for (const p of dots.value) {
    if (p.avg === null) {
      if (current.length > 1) segs.push(current);
      current = [];
    } else {
      current.push(p);
    }
  }
  if (current.length > 1) segs.push(current);
  return segs;
});

const rated = computed(() => props.points.filter((p) => p.avg !== null));
</script>
