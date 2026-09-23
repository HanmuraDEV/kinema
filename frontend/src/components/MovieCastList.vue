<template>
  <div class="pt-6 border-t border-outline-variant/30">
    <div class="mb-4 flex items-center justify-between gap-4">
      <h3 class="font-headline-md text-headline-md text-secondary">Reparto Principal</h3>
      <div class="flex gap-2">
        <Button class="h-9 w-9 p-0" icon="arrow_back" variant="ghost" @click="scrollCast(-1)" />
        <Button class="h-9 w-9 p-0" icon="arrow_forward" variant="ghost" @click="scrollCast(1)" />
      </div>
    </div>
    <div ref="castTrack" class="flex gap-4 overflow-x-auto pb-4 snap-x no-scrollbar scroll-smooth">
      <component
        :is="member.id ? 'a' : 'div'"
        :href="member.id ? `/artists/${member.id}/profile` : undefined"
        v-for="member in cast"
        :key="member.name"
        class="flex w-28 flex-none flex-col gap-2 snap-start hover:-translate-y-1 transition-transform"
      >
        <div class="h-28 w-28 flex-none overflow-hidden rounded-full ambient-shadow-bondi bg-surface-container-high flex items-center justify-center text-center font-label-sm text-label-sm text-on-surface-variant p-2">
          <img v-if="member.image" :alt="member.name" class="h-full w-full object-cover" :src="member.image" />
          <span v-else>{{ member.name }}</span>
        </div>
        <div class="text-center">
          <p class="font-label-md text-label-md text-on-background truncate">{{ member.name }}</p>
          <p class="font-label-sm text-label-sm text-on-surface-variant truncate">{{ member.role }}</p>
        </div>
      </component>
    </div>
  </div>
</template>

<script>
import Button from './Button.vue';

export default {
  components: { Button },
  props: {
    cast: { type: Array, default: () => [] },
  },
  methods: {
    scrollCast(direction) {
      this.$refs.castTrack?.scrollBy({ left: direction * 160, behavior: 'smooth' });
    },
  },
};
</script>