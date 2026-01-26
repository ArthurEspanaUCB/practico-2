<template>
    <div class="usuario-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-users text-info me-2"></i>
                                Gestión de Usuarios
                            </h5>
                            <small class="text-muted">Administra los usuarios del sistema</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <select class="form-select form-select-sm" style="width: 150px;" v-model="filtros.estado" @change="aplicarFiltros">
                                    <option value="">Todos</option>
                                    <option value="true">Activos</option>
                                    <option value="false">Inactivos</option>
                                </select>
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-info" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nuevo Usuario
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaUsuarios" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Vista de Formulario -->
        <div v-show="mostrarFormulario" class="fade-in">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i :class="modoEdicion ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
                            {{ modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarUsuario">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.name }"
                                    v-model="formulario.name"
                                    placeholder="Nombre completo"
                                    maxlength="255"
                                >
                                <div class="invalid-feedback" v-if="errores.name">
                                    {{ errores.name[0] }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.email }"
                                    v-model="formulario.email"
                                    placeholder="correo@ejemplo.com"
                                    maxlength="255"
                                >
                                <div class="invalid-feedback" v-if="errores.email">
                                    {{ errores.email[0] }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Contraseña {{ modoEdicion ? '(dejar vacío para mantener)' : '' }}
                                    <span class="text-danger" v-if="!modoEdicion">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.password }"
                                    v-model="formulario.password"
                                    placeholder="••••••••"
                                    minlength="6"
                                >
                                <div class="invalid-feedback" v-if="errores.password">
                                    {{ errores.password[0] }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Confirmar Contraseña
                                    <span class="text-danger" v-if="!modoEdicion">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control"
                                    v-model="formulario.password_confirmation"
                                    placeholder="••••••••"
                                >
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" v-model="formulario.estado">
                                    <option :value="true">Activo</option>
                                    <option :value="false">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sección de Roles -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-tag me-2"></i>Roles del Usuario
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-2" v-for="rol in roles" :key="rol.id">
                                        <div class="form-check">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox"
                                                :id="'rol-' + rol.id"
                                                :value="rol.id"
                                                v-model="formulario.roles"
                                            >
                                            <label class="form-check-label" :for="'rol-' + rol.id">
                                                <strong>{{ rol.nombre }}</strong>
                                                <small class="text-muted d-block">{{ rol.descripcion }}</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small mb-0" v-if="roles.length === 0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No hay roles disponibles. Crea roles desde el módulo de Roles.
                                </p>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-info" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const $ = window.$;

// Estado
const mostrarFormulario = ref(false);
const modoEdicion = ref(false);
const guardando = ref(false);
const dataTable = ref(null);
const roles = ref([]);

// Filtros
const filtros = reactive({
    estado: ''
});

// Formulario
const formulario = ref({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    estado: true,
    roles: []
});

const errores = ref({});

// Métodos
const cargarRoles = async () => {
    try {
        const response = await axios.get('/api/v1/usuarios/roles');
        if (response.data.success) {
            roles.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar roles:', error);
    }
};

const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
            $('#tablaUsuarios').DataTable().destroy();
        }

        dataTable.value = $('#tablaUsuarios').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/usuarios',
                type: 'GET',
                data: function(d) {
                    d.estado = filtros.estado;
                },
                dataSrc: function(json) {
                    return json.success ? (json.data.data || json.data) : [];
                }
            },
            columns: [
                {
                    data: 'id',
                    width: '60px'
                },
                {
                    data: 'name',
                    render: function(data) {
                        return `<strong>${data}</strong>`;
                    }
                },
                {
                    data: 'email',
                    render: function(data) {
                        return `<a href="mailto:${data}">${data}</a>`;
                    }
                },
                {
                    data: 'nombres_roles',
                    render: function(data) {
                        if (!data) return '<span class="text-muted">Sin roles</span>';
                        return data.split(', ').map(r => `<span class="badge bg-primary me-1">${r}</span>`).join('');
                    }
                },
                {
                    data: 'estado',
                    width: '80px',
                    className: 'text-center',
                    render: function(data) {
                        return data 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    width: '120px',
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary btn-editar" data-id="${row.id}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm ${row.estado ? 'btn-warning' : 'btn-success'} btn-toggle" data-id="${row.id}" title="${row.estado ? 'Desactivar' : 'Activar'}">
                                    <i class="fas ${row.estado ? 'fa-ban' : 'fa-check'}"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${row.id}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>`;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            drawCallback: function() {
                $('.btn-editar').off('click').on('click', function() {
                    editarUsuario($(this).data('id'));
                });

                $('.btn-toggle').off('click').on('click', function() {
                    toggleEstado($(this).data('id'));
                });

                $('.btn-eliminar').off('click').on('click', function() {
                    eliminarUsuario($(this).data('id'));
                });
            }
        });
    }, 300);
};

const cargarDatos = () => {
    if (dataTable.value) {
        dataTable.value.ajax.reload();
    }
};

const aplicarFiltros = () => {
    cargarDatos();
};

const nuevoRegistro = () => {
    resetFormulario();
    modoEdicion.value = false;
    mostrarFormulario.value = true;
};

const editarUsuario = async (id) => {
    try {
        const response = await axios.get(`/api/v1/usuarios/${id}`);
        if (response.data.success) {
            const usuario = response.data.data;
            formulario.value = {
                id: usuario.id,
                name: usuario.name,
                email: usuario.email,
                password: '',
                password_confirmation: '',
                estado: usuario.estado,
                roles: usuario.roles.map(r => r.id)
            };
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar el usuario'
        });
    }
};

const guardarUsuario = async () => {
    guardando.value = true;
    errores.value = {};

    try {
        const url = modoEdicion.value 
            ? `/api/v1/usuarios/${formulario.value.id}` 
            : '/api/v1/usuarios';
        const method = modoEdicion.value ? 'put' : 'post';

        const datos = { ...formulario.value };
        if (modoEdicion.value && !datos.password) {
            delete datos.password;
            delete datos.password_confirmation;
        }

        const response = await axios[method](url, datos);

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false
            });
            cancelarFormulario();
            cargarDatos();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'No se pudo guardar el usuario'
            });
        }
    } finally {
        guardando.value = false;
    }
};

const toggleEstado = async (id) => {
    try {
        const response = await axios.patch(`/api/v1/usuarios/${id}/toggle-estado`);
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.data.message,
                timer: 1500,
                showConfirmButton: false
            });
            cargarDatos();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cambiar el estado'
        });
    }
};

const eliminarUsuario = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/api/v1/usuarios/${id}`);
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                cargarDatos();
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'No se pudo eliminar el usuario'
            });
        }
    }
};

const resetFormulario = () => {
    formulario.value = {
        id: null,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        estado: true,
        roles: []
    };
    errores.value = {};
};

const cancelarFormulario = () => {
    resetFormulario();
    mostrarFormulario.value = false;
    modoEdicion.value = false;
};

onMounted(() => {
    cargarRoles();
    inicializarDataTable();
});
</script>

<style scoped>
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
