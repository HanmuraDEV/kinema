<template>
  <div class="flex flex-col items-center">
    <h3 v-if="showTitle" class="font-headline-md text-headline-md mb-2 text-secondary">{{ title }}</h3>
    <div v-if="items.length === 0" class="font-body-md text-body-md text-on-surface-variant py-8">
      Sin datos de géneros.
    </div>
    <template v-else>
      <div class="relative h-48 w-48">
        <div class="absolute inset-0 rounded-full border border-outline-variant/30"></div>
        <div class="absolute inset-4 rounded-full border border-outline-variant/30"></div>
        <div class="absolute inset-8 rounded-full border border-outline-variant/30"></div>
        <div class="absolute inset-12 rounded-full border border-outline-variant/30"></div>
        <svg class="absolute inset-0 z-10 h-full w-full drop-shadow-md" viewBox="0 0 100 100">
          <polygon :points="points" fill="rgba(100, 248, 244, 0.35)" stroke="#006a68" stroke-linejoin="round" stroke-width="2"></polygon>
          <circle v-for="p in dots" :key="p.label" :cx="p.x" :cy="p.y" r="3" fill="#006a68">
            <title>{{ p.full }}: {{ p.share }}%</title>
          </circle>
        </svg>
        <span
          v-for="p in dots"
          :key="'l-' + p.label"
          class="absolute font-label-sm text-label-sm text-on-surface-variant whitespace-nowrap"
          :style="p.labelStyle"
        >{{ p.label }}</span>
      </div>
      <div v-if="showLegend" class="mt-8 space-y-2 w-full max-w-[240px]">
        <div v-for="item in items.slice(0, 6)" :key="item.name" class="flex items-center justify-between font-label-sm text-label-sm">
          <span class="text-on-surface">{{ item.name }}</span>
          <span class="text-outline">{{ item.share }}%</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] }, // [{name, share}]
  title: { type: String, default: 'Distribución de géneros' },
  showTitle: { type: Boolean, default: true },
  showLegend: { type: Boolean, default: true },
});

const polar = (share, maxShare, index, total) => {
  const angle = ((2 * Math.PI) / total) * index - Math.PI / 2;
  const r = 4 + (36 * share) / Math.max(maxShare, 1);
  return { x: 50 + r * Math.cos(angle), y: 50 + r * Math.sin(angle), angle };
};

const maxShare = computed(() => Math.max(...props.items.map((i) => i.share), 1));

const dots = computed(() =>
  props.items.slice(0, 6).map((item, i, arr) => {
    const p = polar(item.share, maxShare.value, i, arr.length);
    // Etiqueta en anillo exterior fijo (fuera del gráfico), no sobre el punto
    const lr = 49;
    const lx = 50 + lr * Math.cos(p.angle);
    const ly = 50 + lr * Math.sin(p.angle);
    const short = item.name.split(' ').slice(0, 2).join(' ');
    // Anclaje según lado para que el texto nunca se corte en los bordes
    const anchor = lx > 62 ? 'right' : lx < 38 ? 'left' : 'center';
    return {
      ...p,
      x: +p.x.toFixed(1),
      y: +p.y.toFixed(1),
      label: short,
      full: item.name,
      share: item.share,
      showLabel: true,
      labelStyle: {
        left: `${Math.min(Math.max(lx, 12), 88)}%`,
        top: `${Math.min(Math.max(ly, 6), 94)}%`,
        transform: anchor === 'right'
          ? 'translate(-100%, -50%)'
          : anchor === 'left'
            ? 'translate(0, -50%)'
            : 'translate(-50%, -50%)',
        textAlign: anchor === 'center' ? 'center' : anchor,
        fontSize: '10px',
      },
    };
  })
);

const points = computed(() => dots.value.map((p) => `${p.x},${p.y}`).join(' '));
</script>
