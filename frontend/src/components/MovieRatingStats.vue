<template>
  <div class="flex flex-col justify-between">
    <h3 class="font-headline-md text-headline-md mb-2 text-secondary">Distribución de Calificaciones</h3>
    <div class="flex items-end gap-1 h-32 w-full mt-4 border-b border-outline-variant/30 pb-2">
      <div
        v-for="bar in ratingBars"
        :key="bar.label"
        class="w-full bg-secondary/20 rounded-t-sm hover:bg-secondary transition-colors"
        :style="{ height: bar.height }"
        :title="bar.label"
      />
    </div>
    <div class="flex justify-between text-on-surface-variant font-label-sm text-label-sm mt-2">
      <span>0.5</span>
      <span v-if="average !== null && average !== undefined" class="font-bold text-secondary">{{ average }} Promedio · {{ count }} votos</span>
      <span v-else class="text-on-surface-variant/70">Sin votos aún</span>
      <span>5.0</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    ratingBars: { type: Array, default: () => [] },
    average: { type: [Number, String], default: null },
    count: { type: Number, default: 0 },
  },
};
</script>