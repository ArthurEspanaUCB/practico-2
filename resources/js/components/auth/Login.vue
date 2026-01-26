<template>
    <div class="login-container">
        <div class="login-card shadow-lg">
            <div class="login-header">
                <div class="logo-circle">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3>Sistema de Ventas</h3>
                <p class="text-muted">Ingresa tus credenciales para continuar</p>
            </div>
            
            <form @submit.prevent="login">
                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input 
                            type="email" 
                            class="form-control border-start-0 ps-0" 
                            v-model="credentials.email" 
                            required
                            placeholder="ejemplo@correo.com">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input 
                            type="password" 
                            class="form-control border-start-0 ps-0" 
                            v-model="credentials.password" 
                            required
                            placeholder="••••••••">
                    </div>
                </div>

                <div v-if="error" class="alert alert-danger py-2 mb-4 fs-7">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ error }}
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg gradiente-btn" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ loading ? 'Ingresando...' : 'Iniciar Sesión' }}
                    </button>
                </div>
            </form>

            <div class="login-footer mt-4 text-center">
                <small class="text-muted">© {{ new Date().getFullYear() }} Sistema de Gestión</small>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const emit = defineEmits(['login-success']);

const credentials = reactive({
    email: '',
    password: ''
});

const loading = ref(false);
const error = ref('');

const login = async () => {
    loading.value = true;
    error.value = '';

    try {
        // Asegurar que CSRF cookie esté establecida obteniendo sanctum cookie primero si fuera necesario (en SPA Laravel puro)
        // await axios.get('/sanctum/csrf-cookie');
        
        const response = await axios.post('/api/v1/auth/login', credentials);
        
        if (response.data.success) {
            const token = response.data.data.token;
            const user = response.data.data.user;
            
            // Guardar token y usuario
            localStorage.setItem('auth_token', token);
            localStorage.setItem('user_data', JSON.stringify(user));
            
            // Configurar axios por defecto
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            
            // Emitir evento al componente padre
            emit('login-success', user);
        }
    } catch (err) {
        if (err.response?.status === 401) {
            error.value = 'Credenciales incorrectas';
        } else if (err.response?.status === 422) {
            error.value = 'Datos inválidos. Verifica tu correo.';
        } else if (err.response?.status === 403) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Error de conexión. Inténtalo más tarde.';
        }
        console.error(err);
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-container {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.login-card {
    width: 100%;
    max-width: 400px;
    padding: 2.5rem;
    background: white;
    border-radius: 20px;
    animation: fadeInUp 0.5s ease-out;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.logo-circle {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.gradiente-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
}

.gradiente-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
}

.form-control:focus {
    box-shadow: none;
    border-color: #667eea;
}

.input-group-text {
    background-color: white !important;
    border-right: none;
}

.input-group:focus-within .input-group-text {
    border-color: #667eea;
}

.input-group:focus-within .fas {
    color: #667eea !important;
}

.fs-7 {
    font-size: 0.9rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
