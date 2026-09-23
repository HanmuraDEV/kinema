<template>
  <div class="md:col-span-4 flex flex-col gap-6">
    <!-- Póster (las acciones viven en MovieActions) -->
    <div class="rounded-xl overflow-hidden ambient-shadow-bondi bg-surface-container-high flex items-center justify-center text-center font-headline-md text-headline-md text-on-surface-variant p-6 min-h-[300px]">
      <img
        v-if="poster"
        :alt="title"
        :src="poster"
        class="w-full h-auto object-cover"
        style="aspect-ratio: 2 / 3;"
      />
      <span v-else>{{ title }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  poster: {
    type: String,
    required: false,
    default: ''
  },
  title: {
    type: String,
    required: true
  }
});
</script>
