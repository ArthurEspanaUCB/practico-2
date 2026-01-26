<template>
    <div class="categoria-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botón nuevo -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-tags text-primary me-2"></i>
                                Gestión de Categorías
                            </h5>
                            <small class="text-muted">Administra las categorías de productos</small>
                        </div>
                        <button class="btn btn-primary" @click="nuevoRegistro">
                            <i class="fas fa-plus me-2"></i>Nueva Categoría
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Categorías -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaCategorias" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTable cargará los datos aquí -->
                        </tbody>
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
                            {{ modoEdicion ? 'Editar Categoría' : 'Nueva Categoría' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarCategoria">
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    :class="{ 'is-invalid': errores.nombre }"
                                    v-model="formulario.nombre"
                                    placeholder="Ej: Electrónicos"
                                    maxlength="100"
                                >
                                <div class="invalid-feedback" v-if="errores.nombre">
                                    {{ errores.nombre[0] }}
                                </div>
                            </div>

                            <!-- Descripción -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea 
                                    class="form-control" 
                                    :class="{ 'is-invalid': errores.descripcion }"
                                    v-model="formulario.descripcion"
                                    placeholder="Descripción de la categoría"
                                    rows="3"
                                    maxlength="500"
                                ></textarea>
                                <div class="invalid-feedback" v-if="errores.descripcion">
                                    {{ errores.descripcion[0] }}
                                </div>
                                <small class="text-muted">
                                    {{ formulario.descripcion ? formulario.descripcion.length : 0 }}/500 caracteres
                                </small>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

// Estado
const mostrarFormulario = ref(false);
const modoEdicion = ref(false);
const guardando = ref(false);
const dataTable = ref(null);

// Formulario
const formulario = ref({
    id: null,
    nombre: '',
    descripcion: ''
});

const errores = ref({});

// Métodos
const inicializarDataTable = () => {
    nextTick(() => {
        if ($.fn.DataTable.isDataTable('#tablaCategorias')) {
            $('#tablaCategorias').DataTable().destroy();
        }

        dataTable.value = $('#tablaCategorias').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/categorias',
                type: 'GET',
                dataSrc: function(json) {
                    if (json.success) {
                        return json.data.data || json.data;
                    }
                    return [];
                },
                error: function(xhr, error) {
                    console.error('Error al cargar datos:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar las categorías'
                    });
                }
            },
            columns: [
                { 
                    data: 'id',
                    width: '60px',
                    className: 'text-center'
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
                        return data || '<span class="text-muted">Sin descripción</span>';
                    }
                },
                { 
                    data: 'estado',
                    width: '100px',
                    className: 'text-center',
                    render: function(data) {
                        if (data) {
                            return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Activo</span>';
                        } else {
                            return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inactivo</span>';
                        }
                    }
                },
                { 
                    data: 'created_at',
                    width: '150px',
                    render: function(data) {
                        return new Date(data).toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                    }
                },
                {
                    data: null,
                    width: '180px',
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        const estadoBtn = row.estado 
                            ? `<button class="btn btn-sm btn-warning btn-desactivar" data-id="${row.id}" title="Desactivar">
                                   <i class="fas fa-ban"></i>
                               </button>`
                            : `<button class="btn btn-sm btn-success btn-activar" data-id="${row.id}" title="Activar">
                                   <i class="fas fa-check"></i>
                               </button>`;

                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info btn-editar" data-id="${row.id}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${estadoBtn}
                            </div>
                        `;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            drawCallback: function() {
                // Eventos de botones
                $('.btn-editar').off('click').on('click', function() {
                    const id = $(this).data('id');
                    editarCategoria(id);
                });

                $('.btn-activar, .btn-desactivar').off('click').on('click', function() {
                    const id = $(this).data('id');
                    const esActivar = $(this).hasClass('btn-activar');
                    toggleEstado(id, esActivar);
                });
            }
        });
    });
};

const nuevoRegistro = () => {
    formulario.value = {
        id: null,
        nombre: '',
        descripcion: ''
    };
    errores.value = {};
    modoEdicion.value = false;
    mostrarFormulario.value = true;
};

const editarCategoria = async (id) => {
    try {
        const response = await axios.get(`/api/v1/categorias/${id}`);
        
        if (response.data.success) {
            formulario.value = {
                id: response.data.data.id,
                nombre: response.data.data.nombre,
                descripcion: response.data.data.descripcion || ''
            };
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar la categoría'
        });
    }
};

const guardarCategoria = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const url = modoEdicion.value 
            ? `/api/v1/categorias/${formulario.value.id}`
            : '/api/v1/categorias';

        const method = modoEdicion.value ? 'put' : 'post';

        const response = await axios[method](url, {
            nombre: formulario.value.nombre,
            descripcion: formulario.value.descripcion
        });

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false
            });

            cancelarFormulario();
            dataTable.value.ajax.reload();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors || {};
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: error.response.data.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al guardar'
            });
        }
    } finally {
        guardando.value = false;
    }
};

const toggleEstado = async (id, esActivar) => {
    const result = await Swal.fire({
        title: `¿${esActivar ? 'Activar' : 'Desactivar'} categoría?`,
        text: `¿Está seguro de ${esActivar ? 'activar' : 'desactivar'} esta categoría?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: esActivar ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${esActivar ? 'activar' : 'desactivar'}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/categorias/${id}/toggle-estado`);
            
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                dataTable.value.ajax.reload(null, false);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al cambiar el estado'
            });
        }
    }
};

const cancelarFormulario = () => {
    mostrarFormulario.value = false;
    modoEdicion.value = false;
    formulario.value = {
        id: null,
        nombre: '',
        descripcion: ''
    };
    errores.value = {};
};

// Lifecycle
onMounted(() => {
    inicializarDataTable();
});
</script>

<style scoped>
.categoria-module {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn-group .btn {
    margin: 0 2px;
}

.table-hover tbody tr:hover {
    background-color: rgba(78, 115, 223, 0.05);
}
</style>