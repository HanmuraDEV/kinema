<template>
  <!-- 
    Usamos el componente UI y le pasamos los datos calculados.
    Las props no declaradas (como @click y :disabled) pasarán directo a la etiqueta <button>
  -->
  <Button 
    @click="toggleFollow" 
    :disabled="isLoading"
    :variant="isFollowing ? 'secondary' : 'primary'"
    :icon="isFollowing ? 'person_check' : 'person_add'"
    :iconFilled="isFollowing"
    :label="buttonText"
    :class="isLoading ? 'opacity-50 cursor-wait' : ''"
  />
</template>

<script setup>
import { ref, computed } from 'vue';
import Button from './Button.vue';

const props = defineProps({
  userId: { type: Number, required: true },
  initialState: { type: Boolean, default: false }
});

const isFollowing = ref(props.initialState);
const isLoading = ref(false);

// Calculamos el texto de forma dinámica
const buttonText = computed(() => {
  if (isLoading.value) return 'Procesando...';
  return isFollowing.value ? 'Siguiendo' : 'Seguir';
});

const toggleFollow = async () => {
  isLoading.value = true;
  
  try {
    // Aquí conectaremos con Axios más adelante
    await new Promise(resolve => setTimeout(resolve, 500)); 
    isFollowing.value = !isFollowing.value;
  } catch (error) {
    console.error("Error:", error);
  } finally {
    isLoading.value = false;
  }
};
</script>