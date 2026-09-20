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
      <Button class="rounded-md border-b-2 border-primary px-0 pb-1 text-primary" label="Explorar" variant="ghost" />
      <Button class="rounded-md px-0 text-on-surface-variant hover:text-primary" label="Diario" variant="ghost" />
      <Button class="rounded-md px-0 text-on-surface-variant hover:text-primary" label="Listas" variant="ghost" />

      <!-- Sección de Usuario Autenticado -->
      <template v-if="isAuthenticated">
        <Button icon="add" label="Log Movie" variant="primary" />
        
        <div class="flex items-center gap-4">
          <Button class="p-2 text-secondary hover:text-primary" icon="notifications" variant="ghost" />
          
          <!-- Menú flotante del perfil -->
          <div class="relative group">
            <div class="w-10 h-10 rounded-full bg-surface-variant overflow-hidden cursor-pointer border border-outline-variant/30">
              <img 
                :alt="user?.name || 'User profile'" 
                class="w-full h-full object-cover" 
                :src="user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=random`" 
              />
            </div>

            <!-- Menú desplegable al hacer hover -->
            <div class="absolute right-0 mt-2 w-48 bg-surface-container rounded-md shadow-lg border border-outline-variant/30 py-1 hidden group-hover:block transition-all z-50">
              <div class="px-4 py-2 border-b border-outline-variant/20">
                <p class="text-body-md font-bold text-on-surface truncate">{{ user?.name }}</p>
                <p class="text-body-sm text-on-surface-variant truncate">{{ user?.email }}</p>
              </div>
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
import { onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { user, isAuthenticated, isLoading, checkAuth, logout } = useAuth();

onMounted(() => {
  checkAuth();
});
</script>