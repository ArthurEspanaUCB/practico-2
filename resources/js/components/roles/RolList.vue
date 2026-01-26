<template>
    <div class="rol-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tag text-primary me-2"></i>
                                Gestión de Roles
                            </h5>
                            <small class="text-muted">Administra los roles del sistema</small>
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
                                <button class="btn btn-primary" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nuevo Rol
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaRoles" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Permisos</th>
                                <th>Usuarios</th>
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
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i :class="modoEdicion ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
                            {{ modoEdicion ? 'Editar Rol' : 'Nuevo Rol' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarRol">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código</label>
                                <input type="text" class="form-control bg-light" v-model="formulario.codigo" readonly>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.nombre }"
                                    v-model="formulario.nombre"
                                    placeholder="Ej: Administrador"
                                    maxlength="100"
                                >
                                <div class="invalid-feedback" v-if="errores.nombre">
                                    {{ errores.nombre[0] }}
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" v-model="formulario.estado">
                                    <option :value="true">Activo</option>
                                    <option :value="false">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea 
                                    class="form-control"
                                    v-model="formulario.descripcion"
                                    placeholder="Descripción del rol"
                                    rows="2"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Sección de Permisos -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-key me-2"></i>Permisos del Rol
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div 
                                        class="col-md-4 mb-3" 
                                        v-for="(permisos, modulo) in permisosAgrupados" 
                                        :key="modulo"
                                    >
                                        <div class="card h-100">
                                            <div class="card-header bg-secondary text-white py-2">
                                                <div class="form-check">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox"
                                                        :id="'modulo-' + modulo"
                                                        :checked="todosSeleccionados(permisos)"
                                                        @change="toggleModulo(permisos, $event.target.checked)"
                                                    >
                                                    <label class="form-check-label text-capitalize" :for="'modulo-' + modulo">
                                                        <strong>{{ modulo }}</strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body p-2">
                                                <div 
                                                    class="form-check" 
                                                    v-for="permiso in permisos" 
                                                    :key="permiso.id"
                                                >
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox"
                                                        :id="'permiso-' + permiso.id"
                                                        :value="permiso.id"
                                                        v-model="formulario.permisos"
                                                    >
                                                    <label class="form-check-label small" :for="'permiso-' + permiso.id">
                                                        {{ permiso.nombre }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small mb-0" v-if="Object.keys(permisosAgrupados).length === 0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No hay permisos configurados. Crea permisos desde el módulo de Permisos.
                                </p>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Rol' }}
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
const permisosAgrupados = ref({});

// Filtros
const filtros = reactive({
    estado: ''
});

// Formulario
const formulario = ref({
    id: null,
    codigo: '',
    nombre: '',
    descripcion: '',
    estado: true,
    permisos: []
});

const errores = ref({});

// Métodos
const cargarPermisos = async () => {
    try {
        const response = await axios.get('/api/v1/roles/permisos');
        if (response.data.success) {
            permisosAgrupados.value = response.data.data.agrupados || {};
        }
    } catch (error) {
        console.error('Error al cargar permisos:', error);
    }
};

const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaRoles')) {
            $('#tablaRoles').DataTable().destroy();
        }

        dataTable.value = $('#tablaRoles').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/roles',
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
                    data: 'codigo',
                    width: '100px',
                    render: function(data) {
                        return `<code>${data}</code>`;
                    }
                },
                {
                    data: 'nombre',
                    render: function(data) {
                        return `<strong>${data}</strong>`;
                    }
                },
                {
                    data: 'descripcion',
                    render: function(data) {
                        return data ? `<small class="text-muted">${data.substring(0, 50)}${data.length > 50 ? '...' : ''}</small>` : '-';
                    }
                },
                {
                    data: 'cantidad_permisos',
                    width: '80px',
                    className: 'text-center',
                    render: function(data) {
                        return `<span class="badge bg-info">${data}</span>`;
                    }
                },
                {
                    data: 'cantidad_usuarios',
                    width: '80px',
                    className: 'text-center',
                    render: function(data) {
                        return `<span class="badge bg-secondary">${data}</span>`;
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
                    editarRol($(this).data('id'));
                });

                $('.btn-toggle').off('click').on('click', function() {
                    toggleEstado($(this).data('id'));
                });

                $('.btn-eliminar').off('click').on('click', function() {
                    eliminarRol($(this).data('id'));
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

const nuevoRegistro = async () => {
    resetFormulario();
    modoEdicion.value = false;
    mostrarFormulario.value = true;

    try {
        const response = await axios.get('/api/v1/roles/generar-codigo');
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error('Error al generar código:', error);
    }
};

const editarRol = async (id) => {
    try {
        const response = await axios.get(`/api/v1/roles/${id}`);
        if (response.data.success) {
            const rol = response.data.data;
            formulario.value = {
                id: rol.id,
                codigo: rol.codigo,
                nombre: rol.nombre,
                descripcion: rol.descripcion || '',
                estado: rol.estado,
                permisos: rol.permisos.map(p => p.id)
            };
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar el rol'
        });
    }
};

const guardarRol = async () => {
    guardando.value = true;
    errores.value = {};

    try {
        const url = modoEdicion.value 
            ? `/api/v1/roles/${formulario.value.id}` 
            : '/api/v1/roles';
        const method = modoEdicion.value ? 'put' : 'post';

        const response = await axios[method](url, formulario.value);

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
                text: error.response?.data?.message || 'No se pudo guardar el rol'
            });
        }
    } finally {
        guardando.value = false;
    }
};

const toggleEstado = async (id) => {
    try {
        const response = await axios.patch(`/api/v1/roles/${id}/toggle-estado`);
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

const eliminarRol = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar rol?',
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
            const response = await axios.delete(`/api/v1/roles/${id}`);
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
                text: error.response?.data?.message || 'No se pudo eliminar el rol'
            });
        }
    }
};

const resetFormulario = () => {
    formulario.value = {
        id: null,
        codigo: '',
        nombre: '',
        descripcion: '',
        estado: true,
        permisos: []
    };
    errores.value = {};
};

const cancelarFormulario = () => {
    resetFormulario();
    mostrarFormulario.value = false;
    modoEdicion.value = false;
};

const todosSeleccionados = (permisos) => {
    return permisos.every(p => formulario.value.permisos.includes(p.id));
};

const toggleModulo = (permisos, checked) => {
    const ids = permisos.map(p => p.id);
    if (checked) {
        ids.forEach(id => {
            if (!formulario.value.permisos.includes(id)) {
                formulario.value.permisos.push(id);
            }
        });
    } else {
        formulario.value.permisos = formulario.value.permisos.filter(id => !ids.includes(id));
    }
};

onMounted(() => {
    cargarPermisos();
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
