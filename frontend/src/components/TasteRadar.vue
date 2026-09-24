<template>
  <div class="relative flex min-h-[300px] flex-col items-center justify-center">
    <div v-if="isLoading" class="font-body-md text-body-md text-on-surface-variant">Cargando gustos...</div>
    <p v-else-if="items.length === 0" class="font-body-md text-body-md text-on-surface-variant text-center px-6">
      Sin reseñas todavía.
    </p>
    <div v-else class="relative mt-8 h-48 w-48">
      <div class="absolute inset-0 rounded-full border border-outline-variant/30"></div>
      <div class="absolute inset-4 rounded-full border border-outline-variant/30"></div>
      <div class="absolute inset-8 rounded-full border border-outline-variant/30"></div>
      <div class="absolute inset-12 rounded-full border border-outline-variant/30"></div>
      <svg class="absolute inset-0 z-10 h-full w-full drop-shadow-md" viewBox="0 0 100 100">
        <polygon :points="points" fill="rgba(172, 225, 175, 0.5)" stroke="#396940" stroke-linejoin="round" stroke-width="2"></polygon>
        <circle v-for="p in dots" :key="p.label" :cx="p.x" :cy="p.y" r="3" fill="#396940"></circle>
      </svg>
      <span
        v-for="p in dots"
        :key="'l-' + p.label"
        class="absolute font-label-sm text-label-sm text-outline whitespace-nowrap"
        :style="p.labelStyle"
      >{{ p.label }}</span>
    </div>
    <div v-if="items.length > 0" class="mt-10 space-y-2 w-full max-w-[220px]">
      <div v-for="item in items" :key="item.id" class="flex items-center justify-between font-label-sm text-label-sm">
        <span class="text-on-surface">{{ item.name }}</span>
        <span class="text-outline">{{ item.share }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const props = defineProps({
  userId: { type: [Number, String], default: null },
});

const items = ref([]);
const isLoading = ref(true);

const polar = (share, maxShare, index, total) => {
  const angle = ((2 * Math.PI) / total) * index - Math.PI / 2;
  const r = 4 + (36 * share) / Math.max(maxShare, 1);
  return {
    x: 50 + r * Math.cos(angle),
    y: 50 + r * Math.sin(angle),
    angle,
  };
};

const maxShare = computed(() => Math.max(...items.value.map((i) => i.share), 1));

const dots = computed(() =>
  items.value.slice(0, 6).map((item, i, arr) => {
    const p = polar(item.share, maxShare.value, i, arr.length);
    // Etiqueta en anillo exterior fijo (fuera del gráfico), no sobre el punto
    const lr = 49;
    const lx = 50 + lr * Math.cos(p.angle);
    const ly = 50 + lr * Math.sin(p.angle);
    // Anclaje según lado para que el texto nunca se corte en los bordes
    const anchor = lx > 62 ? 'right' : lx < 38 ? 'left' : 'center';
    return {
      ...p,
      x: +p.x.toFixed(1),
      y: +p.y.toFixed(1),
      label: item.name,
      labelStyle: {
        left: `${Math.min(Math.max(lx, 12), 88)}%`,
        top: `${Math.min(Math.max(ly, 6), 94)}%`,
        transform: anchor === 'right'
          ? 'translate(-100%, -50%)'
          : anchor === 'left'
            ? 'translate(0, -50%)'
            : 'translate(-50%, -50%)',
        textAlign: anchor,
        fontSize: '9px',
        lineHeight: '1.15',
        maxWidth: '72px',
        whiteSpace: 'normal',
      },
    };
  })
);

const points = computed(() => dots.value.map((p) => `${p.x},${p.y}`).join(' '));

onMounted(async () => {
  try {
    const params = {};
    if (props.userId) params.user_id = props.userId;
    const { data } = await api.get('/api/analytics/taste', { params });
    items.value = (Array.isArray(data) ? data : []).slice(0, 6);
  } catch (e) {
    console.error('Error cargando gustos:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
