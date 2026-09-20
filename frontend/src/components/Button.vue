<template>
  <component
    :is="isLink ? 'a' : 'button'"
    :href="isLink ? href : undefined"
    :type="!isLink ? type : undefined"
    class="group inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 font-label-md text-label-md transition-all duration-300 ease-out cursor-pointer active:scale-95"
    :class="variantClasses[variant]"
  >
    <!-- Ícono con leve animación de escala al hacer hover en el botón -->
    <span 
      v-if="icon" 
      class="material-symbols-outlined text-[18px] transition-transform duration-300 group-hover:scale-110" 
      :style="{ fontVariationSettings: iconFilled ? '\'FILL\' 1' : '\'FILL\' 0' }"
    >
      {{ icon }}
    </span>
    
    <span v-if="label">{{ label }}</span>
    <slot v-else />
  </component>
</template>

<script>
export default {
  props: {
    href: { type: String, default: '#' },
    icon: { type: String, default: '' },
    iconFilled: { type: Boolean, default: false },
    label: { type: String, default: '' },
    type: { type: String, default: 'button' },
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'tertiary', 'ghost'].includes(value),
    },
  },
  computed: {
    isLink() {
      return this.href && this.href !== '#';
    },
    variantClasses() {
      return {
        primary: 'bg-primary text-on-primary hover:bg-primary-hover hover:shadow-lg hover:-translate-y-1',
        secondary: 'bg-secondary text-on-secondary hover:bg-secondary-hover hover:shadow-lg hover:-translate-y-1',
        tertiary: 'bg-tertiary text-on-tertiary hover:bg-tertiary-hover hover:shadow-lg hover:-translate-y-1',
        ghost: 'bg-transparent text-on-surface-variant hover:bg-surface-variant hover:text-on-surface hover:scale-105',
      };
    },
  },
};
</script>