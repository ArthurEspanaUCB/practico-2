<template>
    <div class="producto-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botones y filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-box text-primary me-2"></i>
                                Gestión de Productos
                            </h5>
                            <small class="text-muted">Administra tu inventario de productos</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-primary" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nuevo Producto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Productos -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaProductos" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>P. Compra</th>
                                <th>P. Venta</th>
                                <th>Stock</th>
                                <th>Estado</th>
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
                            {{ modoEdicion ? 'Editar Producto' : 'Nuevo Producto' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarProducto">
                        <div class="row">
                            <!-- Columna Izquierda -->
                            <div class="col-md-6">
                                <!-- Código -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Código <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.codigo }"
                                            v-model="formulario.codigo"
                                            placeholder="PROD000001"
                                            maxlength="50"
                                        >
                                        <button 
                                            class="btn btn-outline-secondary" 
                                            type="button"
                                            @click="generarCodigo"
                                            :disabled="modoEdicion"
                                        >
                                            <i class="fas fa-magic"></i> Generar
                                        </button>
                                        <div class="invalid-feedback" v-if="errores.codigo">
                                            {{ errores.codigo[0] }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': errores.nombre }"
                                        v-model="formulario.nombre"
                                        placeholder="Nombre del producto"
                                        maxlength="150"
                                    >
                                    <div class="invalid-feedback" v-if="errores.nombre">
                                        {{ errores.nombre[0] }}
                                    </div>
                                </div>

                                <!-- Categoría con Select2 -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Categoría <span class="text-danger">*</span>
                                    </label>
                                    <select 
                                        id="selectCategoria"
                                        class="form-select" 
                                        :class="{ 'is-invalid': errores.categoria_id }"
                                    >
                                        <option value="">Seleccione una categoría</option>
                                    </select>
                                    <div class="invalid-feedback d-block" v-if="errores.categoria_id">
                                        {{ errores.categoria_id[0] }}
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea 
                                        class="form-control" 
                                        :class="{ 'is-invalid': errores.descripcion }"
                                        v-model="formulario.descripcion"
                                        placeholder="Descripción del producto"
                                        rows="3"
                                    ></textarea>
                                    <div class="invalid-feedback" v-if="errores.descripcion">
                                        {{ errores.descripcion[0] }}
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha -->
                            <div class="col-md-6">
                                <!-- Precio de Compra -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Precio de Compra <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs.</span>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.precio_compra }"
                                            v-model="formulario.precio_compra"
                                            placeholder="0.00"
                                            @input="calcularMargen"
                                        >
                                        <div class="invalid-feedback" v-if="errores.precio_compra">
                                            {{ errores.precio_compra[0] }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Precio de Venta -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Precio de Venta <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.precio_venta }"
                                            v-model="formulario.precio_venta"
                                            placeholder="0.00"
                                            @input="calcularMargen"
                                        >
                                        <div class="invalid-feedback" v-if="errores.precio_venta">
                                            {{ errores.precio_venta[0] }}
                                        </div>
                                    </div>
                                    <small class="text-muted" v-if="margenUtilidad !== null">
                                        Margen: <strong :class="margenUtilidad > 0 ? 'text-success' : 'text-danger'">
                                            {{ margenUtilidad }}%
                                        </strong>
                                    </small>
                                </div>

                                <!-- Stock y Stock Mínimo -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Stock <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.stock }"
                                            v-model="formulario.stock"
                                            placeholder="0"
                                            min="0"
                                        >
                                        <div class="invalid-feedback" v-if="errores.stock">
                                            {{ errores.stock[0] }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Stock Mínimo <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.stock_minimo }"
                                            v-model="formulario.stock_minimo"
                                            placeholder="0"
                                            min="0"
                                        >
                                        <div class="invalid-feedback" v-if="errores.stock_minimo">
                                            {{ errores.stock_minimo[0] }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Unidad de Medida -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Unidad de Medida <span class="text-danger">*</span>
                                    </label>
                                    <select 
                                        class="form-select" 
                                        :class="{ 'is-invalid': errores.unidad_medida }"
                                        v-model="formulario.unidad_medida"
                                    >
                                        <option value="UND">Unidad</option>
                                        <option value="KG">Kilogramo</option>
                                        <option value="GR">Gramo</option>
                                        <option value="LT">Litro</option>
                                        <option value="ML">Mililitro</option>
                                        <option value="MT">Metro</option>
                                        <option value="CM">Centímetro</option>
                                        <option value="CAJ">Caja</option>
                                        <option value="PAQ">Paquete</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="errores.unidad_medida">
                                        {{ errores.unidad_medida[0] }}
                                    </div>
                                </div>

                                <!-- Imagen -->
                                <div class="mb-3">
                                    <label class="form-label">Imagen del Producto</label>
                                    <input 
                                        type="file" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': errores.imagen }"
                                        @change="onImagenChange"
                                        accept="image/*"
                                        ref="inputImagen"
                                    >
                                    <div class="invalid-feedback" v-if="errores.imagen">
                                        {{ errores.imagen[0] }}
                                    </div>
                                    <!-- Vista previa -->
                                    <div v-if="vistaPrevia" class="mt-2">
                                        <img :src="vistaPrevia" alt="Vista previa" class="img-thumbnail" style="max-width: 200px;">
                                        <button type="button" class="btn btn-sm btn-danger ms-2" @click="eliminarImagen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Producto' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, onBeforeUnmount } from 'vue';
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
const select2Inicializado = ref(false);
const margenUtilidad = ref(null);
const vistaPrevia = ref(null);
const inputImagen = ref(null);
const categorias = ref([]);

// Formulario
const formulario = ref({
    id: null,
    codigo: '',
    nombre: '',
    descripcion: '',
    categoria_id: '',
    precio_compra: '',
    precio_venta: '',
    stock: 0,
    stock_minimo: 0,
    unidad_medida: 'UND',
    imagen: null
});

const errores = ref({});

// Métodos
const inicializarDataTable = () => {
    nextTick(() => {
        if ($.fn.DataTable.isDataTable('#tablaProductos')) {
            $('#tablaProductos').DataTable().destroy();
        }

        dataTable.value = $('#tablaProductos').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/productos',
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
                        text: 'No se pudieron cargar los productos'
                    });
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
                    render: function(data, type, row) {
                        return `<div>
                            <strong>${data}</strong>
                            ${row.descripcion ? `<br><small class="text-muted">${row.descripcion.substring(0, 50)}...</small>` : ''}
                        </div>`;
                    }
                },
                { 
                    data: 'categoria',
                    width: '120px',
                    render: function(data) {
                        return data ? `<span class="badge bg-info">${data.nombre}</span>` : '-';
                    }
                },
                { 
                    data: 'precio_compra',
                    width: '100px',
                    className: 'text-end',
                    render: function(data) {
                        return `Bs. ${parseFloat(data).toFixed(2)}`;
                    }
                },
                { 
                    data: 'precio_venta',
                    width: '100px',
                    className: 'text-end',
                    render: function(data) {
                        return `<strong>Bs. ${parseFloat(data).toFixed(2)}</strong>`;
                    }
                },
                { 
                    data: 'stock',
                    width: '100px',
                    className: 'text-center',
                    render: function(data, type, row) {
                        const stockBajo = data <= row.stock_minimo;
                        const badgeClass = stockBajo ? 'bg-danger' : 'bg-success';
                        const icon = stockBajo ? 'fa-exclamation-triangle' : 'fa-check';
                        return `<span class="badge ${badgeClass}">
                            <i class="fas ${icon} me-1"></i>${data} ${row.unidad_medida}
                        </span>`;
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
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            drawCallback: function() {
                $('.btn-editar').off('click').on('click', function() {
                    const id = $(this).data('id');
                    editarProducto(id);
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

const cargarCategorias = async () => {
    try {
        const response = await axios.get('/api/v1/categorias', {
            params: { all: 'true', estado: true }
        });

        if (response.data.success) {
            categorias.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar las categorías'
        });
    }
};

const inicializarSelect2 = () => {
    nextTick(() => {
        // Destruir select2 existente si hay
        if (select2Inicializado.value) {
            try {
                $('#selectCategoria').select2('destroy');
            } catch {
                console.log('Select2 no estaba inicializado');
            }
        }

        // Verificar que jQuery y Select2 están disponibles
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            console.error('jQuery o Select2 no están disponibles');
            return;
        }

        // Limpiar y agregar opciones
        const selectElement = $('#selectCategoria');
        selectElement.empty();
        selectElement.append('<option value="">Seleccione una categoría</option>');
        
        categorias.value.forEach(cat => {
            selectElement.append(`<option value="${cat.id}">${cat.nombre}</option>`);
        });

        // Inicializar Select2
        try {
            selectElement.select2({
                placeholder: 'Seleccione una categoría',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                theme: 'bootstrap-5'
            });

            // Evento change
            selectElement.on('change', function() {
                formulario.value.categoria_id = $(this).val();
            });

            select2Inicializado.value = true;

        } catch (error) {
            console.error('Error al inicializar Select2:', error);
        }
    });
};

const generarCodigo = async () => {
    try {
        const response = await axios.get('/api/v1/productos/generar-codigo');
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error('Error al generar código:', error);
    }
};

const calcularMargen = () => {
    const compra = parseFloat(formulario.value.precio_compra) || 0;
    const venta = parseFloat(formulario.value.precio_venta) || 0;
    
    if (compra > 0) {
        margenUtilidad.value = (((venta - compra) / compra) * 100).toFixed(2);
    } else {
        margenUtilidad.value = null;
    }
};

const onImagenChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        formulario.value.imagen = file;
        vistaPrevia.value = URL.createObjectURL(file);
    }
};

const eliminarImagen = () => {
    formulario.value.imagen = null;
    vistaPrevia.value = null;
    if (inputImagen.value) {
        inputImagen.value.value = '';
    }
};

const nuevoRegistro = async () => {
    formulario.value = {
        id: null,
        codigo: '',
        nombre: '',
        descripcion: '',
        categoria_id: '',
        precio_compra: '',
        precio_venta: '',
        stock: 0,
        stock_minimo: 0,
        unidad_medida: 'UND',
        imagen: null
    };
    errores.value = {};
    modoEdicion.value = false;
    vistaPrevia.value = null;
    margenUtilidad.value = null;
    mostrarFormulario.value = true;
    
    await cargarCategorias();
    inicializarSelect2();
    await generarCodigo();
};

const editarProducto = async (id) => {
    try {
        const response = await axios.get(`/api/v1/productos/${id}`);
        
        if (response.data.success) {
            const producto = response.data.data;
            formulario.value = {
                id: producto.id,
                codigo: producto.codigo,
                nombre: producto.nombre,
                descripcion: producto.descripcion || '',
                categoria_id: producto.categoria_id,
                precio_compra: producto.precio_compra,
                precio_venta: producto.precio_venta,
                stock: producto.stock,
                stock_minimo: producto.stock_minimo,
                unidad_medida: producto.unidad_medida,
                imagen: null
            };
            
            if (producto.imagen) {
                vistaPrevia.value = `/storage/${producto.imagen}`;
            } else {
                vistaPrevia.value = null;
            }
            
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
            
            await cargarCategorias();
            inicializarSelect2();
            
            nextTick(() => {
                $('#selectCategoria').val(producto.categoria_id).trigger('change');
                calcularMargen();
            });
        }
    } catch (error) {
        console.error('Error al editar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar el producto'
        });
    }
};

const guardarProducto = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const formData = new FormData();
        formData.append('codigo', formulario.value.codigo);
        formData.append('nombre', formulario.value.nombre);
        formData.append('descripcion', formulario.value.descripcion || '');
        formData.append('categoria_id', formulario.value.categoria_id);
        formData.append('precio_compra', formulario.value.precio_compra);
        formData.append('precio_venta', formulario.value.precio_venta);
        formData.append('stock', formulario.value.stock);
        formData.append('stock_minimo', formulario.value.stock_minimo);
        formData.append('unidad_medida', formulario.value.unidad_medida);
        
        if (formulario.value.imagen && formulario.value.imagen instanceof File) {
            formData.append('imagen', formulario.value.imagen);
        }

        if (modoEdicion.value) {
            formData.append('_method', 'PUT');
        }

        const url = modoEdicion.value 
            ? `/api/v1/productos/${formulario.value.id}`
            : '/api/v1/productos';

        const response = await axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
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
        console.error('Error al guardar:', error);
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
        title: `¿${esActivar ? 'Activar' : 'Desactivar'} producto?`,
        text: `¿Está seguro de ${esActivar ? 'activar' : 'desactivar'} este producto?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: esActivar ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${esActivar ? 'activar' : 'desactivar'}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/productos/${id}/toggle-estado`);
            
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
            console.error('Error al cambiar estado:', error);
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
        codigo: '',
        nombre: '',
        descripcion: '',
        categoria_id: '',
        precio_compra: '',
        precio_venta: '',
        stock: 0,
        stock_minimo: 0,
        unidad_medida: 'UND',
        imagen: null
    };
    errores.value = {};
    vistaPrevia.value = null;
    margenUtilidad.value = null;
    
    if (select2Inicializado.value) {
        try {
            $('#selectCategoria').select2('destroy');
            select2Inicializado.value = false;
        } catch (e) {
            console.log('Error al destruir select2:', e);
        }
    }
};

const cargarDatos = () => {
    if (dataTable.value) {
        dataTable.value.ajax.reload();
    }
};

// Lifecycle
onMounted(() => {
    inicializarDataTable();
});

onBeforeUnmount(() => {
    if (select2Inicializado.value) {
        try {
            $('#selectCategoria').select2('destroy');
        } catch (e) {
            console.log('Error al destruir select2 en unmount:', e);
        }
    }
});
</script>

<style scoped>
.producto-module {
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

.img-thumbnail {
    border-radius: 10px;
}
</style>