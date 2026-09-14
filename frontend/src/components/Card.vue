<template>
  <article :class="['group cursor-pointer overflow-hidden', variantClasses[variant]]">
    <div v-if="imageSrc" :class="['overflow-hidden relative', mediaClass]">
      <img
        :alt="imageAlt"
        :class="['w-full h-full', imageFit === 'contain' ? 'object-contain' : 'object-cover', imageClasses[variant]]"
        :src="imageSrc"
      />
    </div>
    <slot v-else name="media" />

    <div v-if="$slots.default" :class="[bodyClasses[variant], bodyClass]">
      <slot />
    </div>
  </article>
</template>

<script setup lang="ts">
interface Props {
  bodyClass?: string;
  mediaClass?: string;
  imageSrc?: string;
  imageAlt?: string;
  variant?: 'default' | 'feature';
  imageFit?: 'cover' | 'contain';
}

withDefaults(defineProps<Props>(), {
  bodyClass: '',
  mediaClass: 'h-40',
  imageSrc: '',
  imageAlt: '',
  variant: 'default',
  imageFit: 'cover',
});

const variantClasses = {
  default: 'bg-surface-container-lowest rounded-xl border border-outline-variant/30',
  feature: 'bg-transparent rounded-none border-none',
};

const bodyClasses = {
  default: 'p-4',
  feature: 'p-0',
};

const imageClasses = {
  default: 'transition-transform duration-500 group-hover:scale-110',
  feature: 'transition-transform duration-500 group-hover:scale-110',
};
</script>