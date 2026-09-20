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
import api from '../services/api';
import Button from './Button.vue';

const props = defineProps({
  userId: { type: Number, required: true },
  initialState: { type: Boolean, default: false }
});

const emit = defineEmits(['update:following']);

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
    const { data } = await api.post(`/api/users/${props.userId}/follow`);
    if (typeof data.is_following === 'boolean') {
      isFollowing.value = data.is_following;
    } else {
      isFollowing.value = !isFollowing.value;
    }
    emit('update:following', isFollowing.value);
  } catch (error) {
    console.error('Error al seguir/dejar de seguir:', error);
    if (error.response?.status === 401) {
      window.location.href = '/login';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>
