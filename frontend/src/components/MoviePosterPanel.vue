<template>
  <div class="md:col-span-4 flex flex-col gap-6">
    
    <!-- Contenedor del Póster -->
    <div class="rounded-xl overflow-hidden ambient-shadow-bondi bg-white">
      <img 
        :alt="title" 
        :src="poster" 
        class="w-full h-auto object-cover" 
        style="aspect-ratio: 2 / 3;"
      />
    </div>

    <!-- Contenedor de Acciones -->
    <div class="flex flex-col gap-4">
      
      <!-- 
        Reutilizamos el Button de Vue.
        Si en el futuro quieres que abra un modal, solo agregarás @click="abrirModal"
      -->
      <Button 
        class="w-full" 
        icon="edit" 
        label="Registrar" 
        variant="tertiary" 
      />

      <!-- Botón secundario (Bitácora) -->
      <Button
        class="w-full justify-between rounded-lg bg-surface-variant p-4 text-on-surface-variant hover:bg-surface-container-highest"
        icon="book"
        iconFilled
        label="Abrir Bitácora"
        variant="ghost"
      />

    </div>
  </div>
</template>

<script setup>
// Importamos el Button base que migramos en el paso anterior
import Button from './Button.vue';

// Definimos los props con sus tipos (buena práctica para Vue)
defineProps({
  poster: {
    type: String,
    required: true
  },
  title: {
    type: String,
    required: true
  }
});
</script>