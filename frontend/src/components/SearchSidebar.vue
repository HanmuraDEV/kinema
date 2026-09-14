<template>
  <aside class="lg:sticky lg:top-24 self-start space-y-6">
    <div class="glass-panel rounded-xl p-5">
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
        <h2 class="font-headline-md text-headline-md text-on-surface">Explorar por Vibra</h2>
      </div>
      <p class="font-body-md text-body-md text-on-surface-variant mb-4 opacity-80">Potenciado por Gemini</p>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="tag in vibeTags"
          :key="tag"
          :class="['px-4 py-2 rounded-md border font-label-sm text-label-sm transition-colors', tag === 'Estética Neón' ? 'bg-secondary text-on-secondary border-secondary' : 'border-secondary text-secondary hover:bg-secondary/10']"
          :label="tag"
          variant="ghost"
        />
      </div>
    </div>

    <div class="glass-panel rounded-xl p-5">
      <h2 class="font-headline-md text-headline-md text-on-surface mb-6">Filtros</h2>
      <div class="space-y-4">
        <div>
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2 cursor-pointer group">
            Año
            <span class="material-symbols-outlined text-outline group-hover:text-secondary transition-colors">expand_more</span>
          </label>
          <div class="flex gap-2">
            <input class="w-full rounded-md border border-outline-variant/50 bg-white px-3 py-2 font-body-md text-body-md focus:ring-secondary/30" placeholder="Desde" type="number" />
            <input class="w-full rounded-md border border-outline-variant/50 bg-white px-3 py-2 font-body-md text-body-md focus:ring-secondary/30" placeholder="Hasta" type="number" />
          </div>
        </div>
        <hr class="border-outline-variant/30" />
        <div>
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2 cursor-pointer group">
            Género
            <span class="material-symbols-outlined text-outline group-hover:text-secondary transition-colors">expand_more</span>
          </label>
          <div class="flex flex-wrap gap-2">
            <span class="bg-surface-container-high px-3 py-1 rounded-md font-label-sm text-label-sm text-on-surface-variant">Sci-Fi</span>
            <span class="bg-surface-container-high px-3 py-1 rounded-md font-label-sm text-label-sm text-on-surface-variant">Drama</span>
            <span class="bg-surface-container-high px-3 py-1 rounded-md font-label-sm text-label-sm text-on-surface-variant">Thriller</span>
          </div>
        </div>
        <hr class="border-outline-variant/30" />
        <div>
          <label class="font-label-md text-label-md text-on-surface-variant flex justify-between items-center mb-2 cursor-pointer group">
            Director
            <span class="material-symbols-outlined text-outline group-hover:text-secondary transition-colors">chevron_right</span>
          </label>
        </div>
      </div>
    </div>
  </aside>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import Button from './Button.vue';

export default {
  components: { Button },
  props: {
    vibeTags: { type: Array as PropType<string[]>, default: () => [] },
  },
};
</script>