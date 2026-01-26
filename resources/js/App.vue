<template>
<div class="app-container">
        <!-- Login Screen -->
        <Login v-if="!usuarioAutenticado" @login-success="handleLoginSuccess" />

        <!-- Main App (Solo si está autenticado) -->
        <div v-else class="authenticated-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <!-- Navbar -->
        <nav class="main-navbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <!-- Left side -->
                    <div class="d-flex align-items-center gap-3">
                        <button class="toggle-sidebar-btn" @click="toggleSidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a class="navbar-brand mb-0" href="#">
                            <i class="fas fa-shopping-cart"></i>
                            <span v-if="!isMobile">Sistema de Ventas</span>
                        </a>
                    </div>

                    <!-- Right side -->
                    <!-- User Profile Dropdown -->
                    <div class="user-profile dropdown" v-if="usuario">
                        <div class="d-flex align-items-center gap-3 dropdown-toggle" 
                             @click.stop="showProfileMenu = !showProfileMenu"
                             :class="{ 'show': showProfileMenu }"
                             role="button" 
                             aria-expanded="false" 
                             style="cursor: pointer;">
                            <div class="user-info text-end" v-if="!isMobile">
                                <div class="user-name fw-bold">{{ usuario.name }}</div>
                                <div class="user-role small text-muted">{{ usuario.roles?.[0] || 'Usuario' }}</div>
                            </div>
                            <div class="user-avatar bg-primary text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                {{ usuario.name?.substring(0,2).toUpperCase() }}
                            </div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" 
                            :class="{ 'show': showProfileMenu }"
                            style="position: absolute; right: 0; left: auto;">
                            <li><h6 class="dropdown-header">Hola, {{ usuario.name }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'collapsed': sidebarCollapsed, 'show': sidebarMobileShow }">
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'dashboard' }"
                       @click="cambiarModulo('dashboard')">
                        <i class="menu-icon fas fa-tachometer-alt"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>

                <div class="menu-divider"></div>

                <!-- Sección Inventario -->
                <li class="menu-item">
                    <div class="menu-section-title">Inventario</div>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'productos' }"
                       @click="cambiarModulo('productos')">
                        <i class="menu-icon fas fa-box"></i>
                        <span class="menu-text">Productos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'categorias' }"
                       @click="cambiarModulo('categorias')">
                        <i class="menu-icon fas fa-tags"></i>
                        <span class="menu-text">Categorías</span>
                    </a>
                </li>

                <div class="menu-divider"></div>

                <!-- Sección Contactos -->
                <li class="menu-item">
                    <div class="menu-section-title">Contactos</div>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'clientes' }"
                       @click="cambiarModulo('clientes')">
                        <i class="menu-icon fas fa-users"></i>
                        <span class="menu-text">Clientes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'proveedores' }"
                       @click="cambiarModulo('proveedores')">
                        <i class="menu-icon fas fa-truck"></i>
                        <span class="menu-text">Proveedores</span>
                    </a>
                </li>

                <div class="menu-divider"></div>

                <!-- Sección Transacciones -->
                <li class="menu-item">
                    <div class="menu-section-title">Transacciones</div>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'compras' }"
                       @click="cambiarModulo('compras')">
                        <i class="menu-icon fas fa-shopping-bag"></i>
                        <span class="menu-text">Compras</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'ventas' }"
                       @click="cambiarModulo('ventas')">
                        <i class="menu-icon fas fa-cash-register"></i>
                        <span class="menu-text">Ventas</span>
                    </a>
                </li>

                <div class="menu-divider"></div>

                <!-- Sección Reportes -->
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'reportes' }"
                       @click="cambiarModulo('reportes')">
                        <i class="menu-icon fas fa-chart-bar"></i>
                        <span class="menu-text">Reportes</span>
                    </a>
                </li>
                
                <div class="menu-divider"></div>

                <!-- Sección Configuración -->
                <li class="menu-item">
                    <div class="menu-section-title">Configuración</div>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'usuarios' }"
                       @click="cambiarModulo('usuarios')">
                        <i class="menu-icon fas fa-users"></i>
                        <span class="menu-text">Usuarios</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'roles' }"
                       @click="cambiarModulo('roles')">
                        <i class="menu-icon fas fa-user-tag"></i>
                        <span class="menu-text">Roles</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a class="menu-link" 
                       :class="{ 'active': moduloActivo === 'permisos' }"
                       @click="cambiarModulo('permisos')">
                        <i class="menu-icon fas fa-key"></i>
                        <span class="menu-text">Permisos</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
            <div class="main-content">
                <!-- Dashboard -->
                <div v-if="moduloActivo === 'dashboard'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Dashboard</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Dashboard</span>
                        </div>
                    </div>
                    <DashboardPro />
                </div>

                <!-- Módulo de Productos -->
                <div v-if="moduloActivo === 'productos'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Productos</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Inventario</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Productos</span>
                        </div>
                    </div>
                    <ProductoList />
                </div>

                <!-- Módulo de Categorías -->
                <div v-if="moduloActivo === 'categorias'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Categorías</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Inventario</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Categorías</span>
                        </div>
                    </div>
                    <CategoriaList />
                </div>

                <!-- Módulo de Clientes -->
                <div v-if="moduloActivo === 'clientes'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Clientes</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Contactos</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Clientes</span>
                        </div>
                    </div>
                    <ClienteList />
                </div>

                <!-- Módulo de Proveedores -->
                <div v-if="moduloActivo === 'proveedores'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Proveedores</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Contactos</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Proveedores</span>
                        </div>
                    </div>
                    <ProveedorList />
                </div>

                <!-- Módulo de Compras -->
                <div v-if="moduloActivo === 'compras'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Compras</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Transacciones</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Compras</span>
                        </div>
                    </div>
                    <CompraList />
                </div>

                <!-- Módulo de Ventas -->
                <div v-if="moduloActivo === 'ventas'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Ventas</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Transacciones</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Ventas</span>
                        </div>
                    </div>
                    <VentaList />
                </div>

                <!-- Módulo de Reportes -->
                <div v-if="moduloActivo === 'reportes'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Reportes</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Reportes</span>
                        </div>
                    </div>
                    <ReporteList />
                </div>

                <!-- Módulo de Usuarios -->
                <div v-if="moduloActivo === 'usuarios'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Usuarios</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Configuración</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Usuarios</span>
                        </div>
                    </div>
                    <UsuarioList />
                </div>

                <!-- Módulo de Roles -->
                <div v-if="moduloActivo === 'roles'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Roles</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Configuración</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Roles</span>
                        </div>
                    </div>
                    <RolList />
                </div>

                <!-- Módulo de Permisos -->
                <div v-if="moduloActivo === 'permisos'" class="fade-in">
                    <div class="page-header">
                        <h1 class="page-title">Permisos</h1>
                        <div class="page-breadcrumb">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Configuración</span>
                            <span class="breadcrumb-separator">/</span>
                            <span>Permisos</span>
                        </div>
                    </div>
                    <PermisoList />
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
            <div class="footer-copyright">
                <i class="far fa-copyright"></i> 2024 Sistema de Ventas. Todos los derechos reservados.
            </div>
            <div class="footer-links">
                <a href="#"><i class="fas fa-question-circle me-1"></i> Ayuda</a>
                <a href="#"><i class="fas fa-book me-1"></i> Documentación</a>
                <a href="#"><i class="fas fa-envelope me-1"></i> Soporte</a>
            </div>
        </footer>
        </div> <!-- End of authenticated-layout -->
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import CategoriaList from './components/categorias/CategoriaList.vue';
import ProductoList from './components/productos/ProductoList.vue';
import ClienteList from './components/clientes/ClienteList.vue';
import ProveedorList from './components/proveedores/ProveedorList.vue';
import CompraList from './components/compras/CompraList.vue';
import VentaList from './components/ventas/VentaList.vue';
import DashboardPro from './components/dashboard/DashboardPro.vue';
import ReporteList from './components/reportes/ReporteList.vue';
import UsuarioList from './components/usuarios/UsuarioList.vue';
import RolList from './components/roles/RolList.vue';
import PermisoList from './components/permisos/PermisoList.vue';
import Login from './components/auth/Login.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import $ from 'jquery';

// Estado
const usuarioAutenticado = ref(false);
const moduloActivo = ref('dashboard');
const sidebarCollapsed = ref(false);
const sidebarMobileShow = ref(false);
const isMobile = ref(false);
const showProfileMenu = ref(false);
const usuario = ref(null);

// Métodos UI Auxiliares
const closeProfileMenu = () => {
    showProfileMenu.value = false;
};

const checkMobile = () => {
    isMobile.value = window.innerWidth <= 768;
    if (!isMobile.value) {
        sidebarMobileShow.value = false;
    }
};

const toggleSidebar = () => {
    if (isMobile.value) {
        sidebarMobileShow.value = !sidebarMobileShow.value;
    } else {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }
};

const cambiarModulo = (modulo) => {
    moduloActivo.value = modulo;
    if (isMobile.value) {
        sidebarMobileShow.value = false;
    }
};

// Métodos de Autenticación
// Métodos de Autenticación
const configurarHeaders = (token) => {
    // Configurar Headers para Axios
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    // Configurar Headers para jQuery (DataTables)
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            // Si es una URL relativa o coincide con nuestro dominio, enviamos auth
            if (!/^http/.test(settings.url) || settings.url.includes(window.location.hostname)) {
                    xhr.setRequestHeader("Authorization", `Bearer ${token}`);
            }
        }
    });

    // Configurar manejador de errores global para jQuery (DataTables 401)
    // Remover handlers anteriores para evitar duplicados
    $(document).off('ajaxError'); 
    $(document).ajaxError((event, jqxhr) => {
        if (jqxhr.status === 401) {
            console.warn("jQuery AJAX 401 DETECTADO (DataTables)");
            if (localStorage.getItem('auth_token')) {
                    logoutInvoluntario();
            }
        }
    });
};

const verificarSesion = () => {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');

    if (token && userData) {
        try {
            usuario.value = JSON.parse(userData);
            configurarHeaders(token);
            usuarioAutenticado.value = true;
            console.log("Sesión restaurada correctamente.");
        } catch (e) {
            console.error("Error parsing user data", e);
            limpiarSesion();
        }
    } else {
        console.log("No hay sesión activa.");
    }
};

const handleLoginSuccess = (user) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        usuario.value = user;
        configurarHeaders(token);
        usuarioAutenticado.value = true;
        moduloActivo.value = 'dashboard';
    }
};

const limpiarSesion = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    delete axios.defaults.headers.common['Authorization'];
    usuarioAutenticado.value = false;
    usuario.value = null;
    moduloActivo.value = 'dashboard';
};

const logoutInvoluntario = () => {
    console.warn("!! 401 DETECTADO. Ejecutando logout forzado !!");
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    // Redirección forzada
    window.location.href = '/';
};

const logout = async () => {
    try {
        await axios.post('/api/v1/auth/logout');
    } catch (e) {
        console.error('Error al cerrar sesión', e);
    }

    limpiarSesion();
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Sesión cerrada',
        showConfirmButton: false,
        timer: 1500
    });
};

// Inicialización Inmediata (Antes de montar componentes hijos)
verificarSesion();

// Configurar Interceptor Global de Axios
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            // Si el backend dice "Unauthenticated" y nosotros creíamos estar autenticados:
            if (usuarioAutenticado.value) {
                logoutInvoluntario();
            }
        }
        return Promise.reject(error);
    }
);

// Lifecycle Hooks
onMounted(() => {
    // UI Events initialization
    checkMobile();
    window.addEventListener('resize', checkMobile);
    window.addEventListener('click', closeProfileMenu);
});

onUnmounted(() => {
    window.removeEventListener('click', closeProfileMenu);
    window.removeEventListener('resize', checkMobile);
});
</script>

<style>
/* Los estilos ya están en app.css */
</style>