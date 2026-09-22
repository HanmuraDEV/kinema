<template>
  <form @submit.prevent="handleSubmit" class="flex flex-col gap-4 max-w-md mx-auto p-6 glass-panel rounded-xl ambient-shadow-secondary">
    <h2 class="text-xl font-bold text-on-surface">Iniciar Sesión</h2>

    <div v-if="error" class="p-3 rounded bg-error-container text-on-error-container text-sm">
      {{ error }}
    </div>

    <div class="flex flex-col gap-1">
      <label for="email" class="text-sm font-medium text-on-surface-variant">Correo electrónico</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        autocomplete="email"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="usuario@ejemplo.com"
      />
    </div>

    <div class="flex flex-col gap-1">
      <label for="password" class="text-sm font-medium text-on-surface-variant">Contraseña</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        required
        autocomplete="current-password"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="••••••••"
      />
    </div>

    <Button
      type="submit"
      variant="primary"
      :label="isLoading ? 'Ingresando...' : 'Iniciar Sesión'"
      :disabled="isLoading"
      class="mt-2"
    />

    <p class="text-sm text-on-surface-variant text-center">
      ¿No tienes cuenta?
      <a href="/register" class="text-secondary hover:underline">Regístrate</a>
    </p>
  </form>
</template>

<script setup>
import { reactive } from 'vue';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { login, isLoading, error } = useAuth();

const form = reactive({
  email: '',
  password: ''
});

const handleSubmit = async () => {
  try {
    await login(form);
    window.location.href = '/';
  } catch (e) {
    // El mensaje de error ya es capturado y expuesto por useAuth
  }
};
</script>