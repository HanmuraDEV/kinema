<template>
  <header class="hidden md:flex justify-between items-center w-full px-margin-desktop max-w-container-max mx-auto h-20 bg-background docked full-width top-0 sticky border-b border-outline-variant/30 z-50">
    <div class="flex items-center gap-gutter">
      <a class="font-headline-md text-headline-md font-bold text-primary" href="#">Kinema</a>
      <div class="relative bg-surface-container rounded-md overflow-hidden flex items-center px-4 py-2 border border-outline-variant/30">
        <span class="material-symbols-outlined text-secondary mr-2">search</span>
        <input class="bg-transparent border-none text-body-md w-64 placeholder-on-surface-variant/70 text-secondary" placeholder="Buscar películas..." type="text" />
      </div>
    </div>

    <nav class="flex items-center gap-8">
      <Button class="rounded-md border-b-2 border-primary px-0 pb-1 text-primary" label="Explorar" variant="ghost" />
      <Button class="rounded-md px-0 text-on-surface-variant hover:text-primary" label="Diario" variant="ghost" />
      <Button class="rounded-md  px-0 text-on-surface-variant hover:text-primary" label="Listas" variant="ghost" />
      <Button icon="add" label="Log Movie" variant="primary" />
      <div class="flex items-center gap-4">
        <Button class="p-2 text-secondary hover:text-primary" icon="notifications" variant="ghost" />
        <div class="w-10 h-10 rounded-full bg-surface-variant overflow-hidden cursor-pointer">
          <img alt="User profile" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDlzU5oFP7bdYEYODi5KGgeBMPdYD7paMb7QAnjUTTn7mxjjdila09r_OmzTeaqU0ybdOBbf0WwdrGn_aitbkvYKWMdiZbqv6ruID2xU8muGQb2Nn22Rv6tVk-cd3Ke7ZgcrRXbmjMLBJVBD61TyNpnZum7m-z6yRZ_AiygEqZA-_dIGXUANjgTE4wENBmMll0HU_BMaBBgrOZzc__P2gZeO0NShiW_VVoza8bGytDnTapiFSUWjgHTHU0Gw47e_eFvEN3nrtQ-ko8" />
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>
import Button from './Button.vue';
</script>