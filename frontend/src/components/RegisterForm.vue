<template>
  <form @submit.prevent="handleSubmit" class="flex flex-col gap-4 max-w-md mx-auto p-6 glass-panel rounded-xl ambient-shadow-secondary">
    <h2 class="text-xl font-bold text-on-surface">Crear cuenta</h2>

    <div v-if="error" class="p-3 rounded bg-error-container text-on-error-container text-sm">
      {{ error }}
    </div>

    <div class="flex flex-col gap-1">
      <label for="reg-name" class="text-sm font-medium text-on-surface-variant">Nombre</label>
      <input
        id="reg-name"
        v-model="form.name"
        type="text"
        required
        autocomplete="name"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="Tu nombre"
      />
    </div>

    <div class="flex flex-col gap-1">
      <label for="reg-email" class="text-sm font-medium text-on-surface-variant">Correo electrónico</label>
      <input
        id="reg-email"
        v-model="form.email"
        type="email"
        required
        autocomplete="email"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="usuario@ejemplo.com"
      />
    </div>

    <div class="flex flex-col gap-1">
      <label for="reg-password" class="text-sm font-medium text-on-surface-variant">Contraseña (mín. 8)</label>
      <input
        id="reg-password"
        v-model="form.password"
        type="password"
        required
        minlength="8"
        autocomplete="new-password"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="••••••••"
      />
    </div>

    <div class="flex flex-col gap-1">
      <label for="reg-password-confirm" class="text-sm font-medium text-on-surface-variant">Confirmar contraseña</label>
      <input
        id="reg-password-confirm"
        v-model="form.password_confirmation"
        type="password"
        required
        minlength="8"
        autocomplete="new-password"
        class="px-4 py-2 rounded-full border border-outline bg-surface text-on-surface focus:outline-none focus:border-primary transition-colors"
        placeholder="••••••••"
      />
    </div>

    <Button
      type="submit"
      variant="primary"
      :label="isLoading ? 'Creando cuenta...' : 'Registrarse'"
      :disabled="isLoading"
      class="mt-2"
    />

    <p class="text-sm text-on-surface-variant text-center">
      ¿Ya tienes cuenta?
      <a href="/login" class="text-secondary hover:underline">Inicia sesión</a>
    </p>
  </form>
</template>

<script setup>
import { reactive } from 'vue';
import { useAuth } from '../composables/useAuth';
import Button from './Button.vue';

const { register, isLoading, error } = useAuth();

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const handleSubmit = async () => {
  try {
    await register(form);
    window.location.href = '/';
  } catch (e) {
    // El mensaje de error ya es capturado y expuesto por useAuth
  }
};
</script>
