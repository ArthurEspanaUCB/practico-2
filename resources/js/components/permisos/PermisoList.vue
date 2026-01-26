<template>
    <div class="permiso-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-key text-warning me-2"></i>
                                Gestión de Permisos
                            </h5>
                            <small class="text-muted">Administra los permisos del sistema</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <select class="form-select form-select-sm" style="width: 180px;" v-model="filtros.modulo" @change="aplicarFiltros">
                                    <option value="">Todos los módulos</option>
                                    <option v-for="(nombre, codigo) in modulos" :key="codigo" :value="codigo">
                                        {{ nombre }}
                                    </option>
                                </select>
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-warning" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nuevo Permiso
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaPermisos" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Módulo</th>
                                <th>Descripción</th>
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
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i :class="modoEdicion ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
                            {{ modoEdicion ? 'Editar Permiso' : 'Nuevo Permiso' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarPermiso">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    Código <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.codigo }"
                                    v-model="formulario.codigo"
                                    placeholder="Ej: productos.ver"
                                    maxlength="100"
                                >
                                <div class="invalid-feedback" v-if="errores.codigo">
                                    {{ errores.codigo[0] }}
                                </div>
                                <small class="text-muted">Formato: modulo.accion (ej: productos.ver)</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errores.nombre }"
                                    v-model="formulario.nombre"
                                    placeholder="Ej: Ver Productos"
                                    maxlength="150"
                                >
                                <div class="invalid-feedback" v-if="errores.nombre">
                                    {{ errores.nombre[0] }}
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    Módulo <span class="text-danger">*</span>
                                </label>
                                <select 
                                    class="form-select"
                                    :class="{ 'is-invalid': errores.modulo }"
                                    v-model="formulario.modulo"
                                >
                                    <option value="">Seleccione módulo</option>
                                    <option v-for="(nombre, codigo) in modulos" :key="codigo" :value="codigo">
                                        {{ nombre }}
                                    </option>
                                </select>
                                <div class="invalid-feedback" v-if="errores.modulo">
                                    {{ errores.modulo[0] }}
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea 
                                    class="form-control"
                                    v-model="formulario.descripcion"
                                    placeholder="Descripción del permiso"
                                    rows="2"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-warning" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Permiso' }}
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
const modulos = ref({});

// Filtros
const filtros = reactive({
    modulo: ''
});

// Formulario
const formulario = ref({
    id: null,
    codigo: '',
    nombre: '',
    modulo: '',
    descripcion: ''
});

const errores = ref({});

// Métodos
const cargarModulos = async () => {
    try {
        const response = await axios.get('/api/v1/permisos/modulos');
        if (response.data.success) {
            modulos.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar módulos:', error);
    }
};

const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaPermisos')) {
            $('#tablaPermisos').DataTable().destroy();
        }

        dataTable.value = $('#tablaPermisos').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/permisos',
                type: 'GET',
                data: function(d) {
                    d.modulo = filtros.modulo;
                },
                dataSrc: function(json) {
                    return json.success ? (json.data.data || json.data) : [];
                }
            },
            columns: [
                {
                    data: 'codigo',
                    width: '150px',
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
                    data: 'modulo',
                    width: '120px',
                    render: function(data) {
                        return `<span class="badge bg-secondary text-capitalize">${data}</span>`;
                    }
                },
                {
                    data: 'descripcion',
                    render: function(data) {
                        return data ? `<small class="text-muted">${data.substring(0, 50)}${data.length > 50 ? '...' : ''}</small>` : '-';
                    }
                },
                {
                    data: null,
                    width: '100px',
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary btn-editar" data-id="${row.id}" title="Editar">
                                    <i class="fas fa-edit"></i>
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
            pageLength: 15,
            order: [[2, 'asc'], [1, 'asc']],
            drawCallback: function() {
                $('.btn-editar').off('click').on('click', function() {
                    editarPermiso($(this).data('id'));
                });

                $('.btn-eliminar').off('click').on('click', function() {
                    eliminarPermiso($(this).data('id'));
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

const editarPermiso = async (id) => {
    try {
        const response = await axios.get(`/api/v1/permisos/${id}`);
        if (response.data.success) {
            const permiso = response.data.data;
            formulario.value = {
                id: permiso.id,
                codigo: permiso.codigo,
                nombre: permiso.nombre,
                modulo: permiso.modulo,
                descripcion: permiso.descripcion || ''
            };
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar el permiso'
        });
    }
};

const guardarPermiso = async () => {
    guardando.value = true;
    errores.value = {};

    try {
        const url = modoEdicion.value 
            ? `/api/v1/permisos/${formulario.value.id}` 
            : '/api/v1/permisos';
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
                text: error.response?.data?.message || 'No se pudo guardar el permiso'
            });
        }
    } finally {
        guardando.value = false;
    }
};

const eliminarPermiso = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar permiso?',
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
            const response = await axios.delete(`/api/v1/permisos/${id}`);
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
                text: error.response?.data?.message || 'No se pudo eliminar el permiso'
            });
        }
    }
};

const resetFormulario = () => {
    formulario.value = {
        id: null,
        codigo: '',
        nombre: '',
        modulo: '',
        descripcion: ''
    };
    errores.value = {};
};

const cancelarFormulario = () => {
    resetFormulario();
    mostrarFormulario.value = false;
    modoEdicion.value = false;
};

onMounted(() => {
    cargarModulos();
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
