<template>
  <div class="flex flex-col items-center">
    <h3 v-if="showTitle" class="font-headline-md text-headline-md mb-2 text-secondary">{{ title }}</h3>
    <div v-if="items.length === 0" class="font-body-md text-body-md text-on-surface-variant py-8">
      Sin datos de géneros.
    </div>
    <template v-else>
      <div class="relative w-full max-w-[460px] aspect-square">
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
          class="absolute font-label-sm text-label-sm text-on-surface-variant"
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

const dots = computed(() => {
  const raw = props.items.slice(0, 6).map((item, i, arr) => {
    const p = polar(item.share, maxShare.value, i, arr.length);
    // Etiqueta fuera de los círculos: anillo amplio que puede invadir el
    // padding de la card (overflow visible), nunca sobre el polígono
    const lr = 56;
    const lx = 50 + lr * Math.cos(p.angle);
    const ly = 50 + lr * Math.sin(p.angle);
    // Anclaje según lado para que el texto nunca se corte en los bordes
    const anchor = Math.cos(p.angle) > 0.33 ? 'right' : Math.cos(p.angle) < -0.33 ? 'left' : 'center';
    return {
      ...p,
      x: +p.x.toFixed(1),
      y: +p.y.toFixed(1),
      label: item.name,
      full: item.name,
      share: item.share,
      showLabel: true,
      anchor,
      lx, ly,
    };
  });

  // Separa verticalmente TODAS las etiquetas que choquen, sin importar el lado
  const ordered = [...raw].sort((a, b) => a.ly - b.ly);
  const MIN = 10;
  for (let i = 1; i < ordered.length; i++) {
    if (ordered[i].ly - ordered[i - 1].ly < MIN) ordered[i].ly = ordered[i - 1].ly + MIN;
  }
  const overflow = ordered.length ? ordered[ordered.length - 1].ly - 102 : 0;
  if (overflow > 0) ordered.forEach((d) => { d.ly -= overflow; });
  ordered.forEach((d) => { d.ly = Math.min(Math.max(d.ly, -2), 102); });

  return raw.map((d) => ({
    x: d.x,
    y: d.y,
    label: d.label,
    full: d.full,
    share: d.share,
    showLabel: true,
    labelStyle: {
      left: `${Math.min(Math.max(d.lx, -10), 110)}%`,
      top: `${d.ly}%`,
      transform: d.anchor === 'right'
        ? 'translate(-100%, -50%)'
        : d.anchor === 'left'
          ? 'translate(0, -50%)'
          : 'translate(-50%, -50%)',
      textAlign: d.anchor,
      fontSize: '10px',
      lineHeight: '1.2',
      maxWidth: '88px',
      whiteSpace: 'normal',
    },
  }));
});

const points = computed(() => dots.value.map((p) => `${p.x},${p.y}`).join(' '));
</script>
