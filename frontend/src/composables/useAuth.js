import { ref, computed } from 'vue';
import { login as loginService, logout as logoutService, fetchUser, register as registerService } from '../services/auth';

// Estado global compartido (fuera de la función)
const user = ref(null);
const isLoading = ref(false);
const error = ref(null);
const isInitialized = ref(false);

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value);

    /**
     * Verifica la sesión contra el servidor (ideal al cargar la app).
     * Usa el Bearer token guardado en localStorage.
     */
    const checkAuth = async () => {
        if (isInitialized.value) return;

        isLoading.value = true;
        error.value = null;
        try {
            const userData = await fetchUser();
            user.value = userData;
        } catch (err) {
            user.value = null;
        } finally {
            isLoading.value = false;
            isInitialized.value = true;
        }
    };

    /**
     * Inicia sesión y actualiza el estado reactivo del usuario.
     */
    const login = async (credentials) => {
        isLoading.value = true;
        error.value = null;

        try {
            const data = await loginService(credentials);
            user.value = data.user ?? (await fetchUser().catch(() => null));
            return true;
        } catch (err) {
            user.value = null;
            if (err.response?.status === 422) {
                const msg = err.response.data?.message || err.response.data?.errors?.email?.[0];
                error.value = msg || 'Credenciales incorrectas. Verifica tu correo y contraseña.';
            } else if (err.response?.status === 419) {
                error.value = 'Sesión expirada. Recarga la página e inténtalo de nuevo.';
            } else {
                error.value = 'Error al conectar con el servidor. ¿Laravel está corriendo en :8000?';
            }
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Registra un usuario nuevo (name, email, password, password_confirmation).
     */
    const register = async (payload) => {
        isLoading.value = true;
        error.value = null;

        try {
            const data = await registerService(payload);
            user.value = data.user ?? (await fetchUser().catch(() => null));
            return true;
        } catch (err) {
            user.value = null;
            if (err.response?.status === 422) {
                const errors = err.response.data?.errors;
                error.value = errors
                    ? Object.values(errors).flat().join(' ')
                    : (err.response.data?.message || 'Revisa los datos del formulario.');
            } else {
                error.value = 'Error al conectar con el servidor. ¿Laravel está corriendo en :8000?';
            }
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Cierra la sesión (revoca el token en Laravel) y limpia el estado local.
     */
    const logout = async () => {
        isLoading.value = true;
        try {
            await logoutService();
        } catch (err) {
            console.error('Error al cerrar sesión:', err);
        } finally {
            user.value = null;
            isInitialized.value = false;
            isLoading.value = false;
            window.location.href = '/login';
        }
    };

    const requireAuth = async () => {
        await checkAuth(); // Nos aseguramos de tener el estado actual

        if (!isAuthenticated.value) {
            window.location.href = '/login';
        }
    };

    return {
        user,
        isAuthenticated,
        isLoading,
        error,
        checkAuth,
        login,
        register,
        logout,
        requireAuth
    };
}
