<template>
  <header class="hidden md:flex justify-between items-center w-full px-margin-desktop max-w-container-max mx-auto h-20 bg-background docked full-width top-0 sticky border-b border-outline-variant/30 z-50">
    <div class="flex items-center gap-gutter">
      <a class="font-headline-md text-headline-md font-bold text-primary" href="/">Kinema</a>
      <form action="/search" method="get" class="relative bg-surface-container rounded-full overflow-hidden flex items-center px-4 py-2 border border-outline-variant/30">
        <span class="material-symbols-outlined text-secondary mr-2">search</span>
        <input name="q" class="bg-transparent border-none text-body-md w-64 placeholder-on-surface-variant/70 text-secondary" placeholder="Buscar películas..." type="text" />
      </form>
    </div>

    <nav class="flex items-center gap-8">
      <a href="/search"><Button class="rounded-md border-b-2 border-primary px-0 pb-1 text-primary" label="Explorar" variant="ghost" /></a>
      <a href="/diary"><Button class="rounded-md px-0 text-on-surface-variant hover:text-primary" label="Diario" variant="ghost" /></a>
      <a href="/lists"><Button class="rounded-md px-0 text-on-surface-variant hover:text-primary" label="Listas" variant="ghost" /></a>

      <!-- Sección de Usuario Autenticado -->
      <template v-if="isAuthenticated">
        <a href="/diary"><Button icon="add" label="Log Movie" variant="primary" /></a>

        <div class="flex items-center gap-4">
          <a :href="profileUrl" title="Mi perfil">
            <div class="w-10 h-10 rounded-full bg-surface-variant overflow-hidden cursor-pointer border border-outline-variant/30 hover:border-secondary transition-colors">
              <img
                :alt="user?.name || 'User profile'"
                class="w-full h-full object-cover"
                :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=random`"
              />
            </div>
          </a>

          <!-- Menú flotante del perfil -->
          <div class="relative group">
            <Button class="p-2 text-secondary hover:text-primary" icon="expand_more" variant="ghost" />

            <!-- Menú desplegable al hacer hover -->
            <div class="absolute right-0 mt-2 w-48 bg-surface-container rounded-md shadow-lg border border-outline-variant/30 py-1 hidden group-hover:block transition-all z-50">
              <div class="px-4 py-2 border-b border-outline-variant/20">
                <p class="text-body-md font-bold text-on-surface truncate">{{ user?.name }}</p>
                <p class="text-body-sm text-on-surface-variant truncate">{{ user?.email }}</p>
              </div>
              <a :href="profileUrl" class="block px-4 py-2 text-body-sm text-on-surface hover:bg-surface-variant/50 transition-colors">Mi perfil</a>
              <button
                @click="logout"
                class="w-full text-left px-4 py-2 text-body-sm text-error hover:bg-surface-variant/50 transition-colors flex items-center gap-2 cursor-pointer"
              >
                <span class="material-symbols-outlined text-sm">logout</span>
                Cerrar sesión
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Estado Invitado (No Autenticado) -->
      <template v-else-if="!isLoading">
        <a href="/login">
          <Button label="Iniciar Sesión" variant="primary" />
        </a>
      </template>
    </nav>
  </header>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { user, isAuthenticated, isLoading, checkAuth, logout } = useAuth();

const profileUrl = computed(() =>
  user.value?.name ? `/${encodeURIComponent(user.value.name)}/profile` : '/login'
);

onMounted(() => {
  checkAuth();
});
</script>
