<template>
    <div class="cliente-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botones y filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-users text-primary me-2"></i>
                                Gestión de Clientes
                            </h5>
                            <small class="text-muted">Administra tu cartera de clientes</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-primary" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nuevo Cliente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaClientes" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Contacto</th>
                                <th>Tipo</th>
                                <th>Crédito</th>
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
                            {{ modoEdicion ? 'Editar Cliente' : 'Nuevo Cliente' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarCliente">
                        <!-- Sección: Datos Personales -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>Datos Personales</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Tipo de Documento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Tipo Doc. <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select" 
                                            :class="{ 'is-invalid': errores.tipo_documento }"
                                            v-model="formulario.tipo_documento"
                                            @change="onTipoDocumentoChange"
                                        >
                                            <option value="DNI">DNI</option>
                                            <option value="RUC">RUC</option>
                                            <option value="CE">Carnet Extranjería</option>
                                            <option value="PASAPORTE">Pasaporte</option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errores.tipo_documento">
                                            {{ errores.tipo_documento[0] }}
                                        </div>
                                    </div>

                                    <!-- Número de Documento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            N° Documento <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.numero_documento }"
                                            v-model="formulario.numero_documento"
                                            :placeholder="placeholderDocumento"
                                            :maxlength="maxLengthDocumento"
                                        >
                                        <div class="invalid-feedback" v-if="errores.numero_documento">
                                            {{ errores.numero_documento[0] }}
                                        </div>
                                    </div>

                                    <!-- Nombres (para personas naturales) -->
                                    <div class="col-md-3 mb-3" v-show="esPersonaNatural">
                                        <label class="form-label">
                                            Nombres <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.nombres }"
                                            v-model="formulario.nombres"
                                            placeholder="Nombres"
                                            maxlength="100"
                                        >
                                        <div class="invalid-feedback" v-if="errores.nombres">
                                            {{ errores.nombres[0] }}
                                        </div>
                                    </div>

                                    <!-- Apellidos (para personas naturales) -->
                                    <div class="col-md-3 mb-3" v-show="esPersonaNatural">
                                        <label class="form-label">
                                            Apellidos <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.apellidos }"
                                            v-model="formulario.apellidos"
                                            placeholder="Apellidos"
                                            maxlength="100"
                                        >
                                        <div class="invalid-feedback" v-if="errores.apellidos">
                                            {{ errores.apellidos[0] }}
                                        </div>
                                    </div>

                                    <!-- Razón Social (para RUC) -->
                                    <div class="col-md-6 mb-3" v-show="!esPersonaNatural">
                                        <label class="form-label">
                                            Razón Social <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': errores.razon_social }"
                                            v-model="formulario.razon_social"
                                            placeholder="Razón Social de la Empresa"
                                            maxlength="200"
                                        >
                                        <div class="invalid-feedback" v-if="errores.razon_social">
                                            {{ errores.razon_social[0] }}
                                        </div>
                                    </div>

                                    <!-- Sexo (solo para personas naturales) -->
                                    <div class="col-md-2 mb-3" v-show="esPersonaNatural">
                                        <label class="form-label">Sexo</label>
                                        <select class="form-select" v-model="formulario.sexo">
                                            <option value="">Seleccione</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>

                                    <!-- Fecha de Nacimiento (solo para personas naturales) -->
                                    <div class="col-md-3 mb-3" v-show="esPersonaNatural">
                                        <label class="form-label">Fecha Nacimiento</label>
                                        <input 
                                            type="date" 
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.fecha_nacimiento }"
                                            v-model="formulario.fecha_nacimiento"
                                            :max="fechaMaxima"
                                        >
                                        <div class="invalid-feedback" v-if="errores.fecha_nacimiento">
                                            {{ errores.fecha_nacimiento[0] }}
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Email</label>
                                        <input 
                                            type="email" 
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.email }"
                                            v-model="formulario.email"
                                            placeholder="correo@ejemplo.com"
                                        >
                                        <div class="invalid-feedback" v-if="errores.email">
                                            {{ errores.email[0] }}
                                        </div>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.telefono"
                                            placeholder="(000) 000-0000"
                                            maxlength="20"
                                        >
                                    </div>

                                    <!-- Celular -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Celular</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.celular"
                                            placeholder="000-000-0000"
                                            maxlength="20"
                                        >
                                    </div>

                                    <!-- Dirección -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dirección</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.direccion"
                                            placeholder="Calle, Número, Zona"
                                            maxlength="250"
                                        >
                                    </div>

                                    <!-- Ciudad -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Ciudad</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.ciudad"
                                            placeholder="Ciudad"
                                            maxlength="100"
                                        >
                                    </div>

                                    <!-- Provincia/Departamento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Provincia/Depto</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.provincia"
                                            placeholder="Provincia"
                                            maxlength="100"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Datos de Cliente -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Datos de Cliente</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Código Cliente -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Código Cliente</label>
                                        <input 
                                            type="text" 
                                            class="form-control bg-light"
                                            v-model="formulario.codigo"
                                            readonly
                                        >
                                    </div>

                                    <!-- Tipo Cliente -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Tipo Cliente <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select"
                                            :class="{ 'is-invalid': errores.tipo_cliente }"
                                            v-model="formulario.tipo_cliente"
                                        >
                                            <option value="Regular">Regular</option>
                                            <option value="VIP">VIP</option>
                                            <option value="Corporativo">Corporativo</option>
                                            <option value="Mayorista">Mayorista</option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errores.tipo_cliente">
                                            {{ errores.tipo_cliente[0] }}
                                        </div>
                                    </div>

                                    <!-- Límite de Crédito -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Límite de Crédito</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input 
                                                type="number" 
                                                step="0.01"
                                                class="form-control"
                                                :class="{ 'is-invalid': errores.limite_credito }"
                                                v-model="formulario.limite_credito"
                                                placeholder="0.00"
                                                min="0"
                                            >
                                        </div>
                                        <div class="invalid-feedback" v-if="errores.limite_credito">
                                            {{ errores.limite_credito[0] }}
                                        </div>
                                    </div>

                                    <!-- Días de Crédito -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Días de Crédito</label>
                                        <input 
                                            type="number" 
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.dias_credito }"
                                            v-model="formulario.dias_credito"
                                            placeholder="0"
                                            min="0"
                                            max="365"
                                        >
                                        <div class="invalid-feedback" v-if="errores.dias_credito">
                                            {{ errores.dias_credito[0] }}
                                        </div>
                                    </div>

                                    <!-- Descuento General -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Descuento General (%)</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.descuento_general }"
                                            v-model="formulario.descuento_general"
                                            placeholder="0.00"
                                            min="0"
                                            max="100"
                                        >
                                        <div class="invalid-feedback" v-if="errores.descuento_general">
                                            {{ errores.descuento_general[0] }}
                                        </div>
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="col-md-9 mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea 
                                            class="form-control"
                                            v-model="formulario.observaciones"
                                            placeholder="Notas adicionales sobre el cliente"
                                            rows="2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Cliente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const $ = window.$;

// Estado
const mostrarFormulario = ref(false);
const modoEdicion = ref(false);
const guardando = ref(false);
const dataTable = ref(null);

// Formulario
const formulario = ref({
    // Persona
    tipo_documento: 'DNI',
    numero_documento: '',
    nombres: '',
    apellidos: '',
    razon_social: '',
    email: '',
    telefono: '',
    celular: '',
    direccion: '',
    ciudad: '',
    provincia: '',
    fecha_nacimiento: '',
    sexo: '',
    
    // Cliente
    codigo: '',
    tipo_cliente: 'Regular',
    limite_credito: 0,
    dias_credito: 0,
    descuento_general: 0,
    observaciones: ''
});

const errores = ref({});

// Computed
const esPersonaNatural = computed(() => {
    return ['DNI', 'CE', 'PASAPORTE'].includes(formulario.value.tipo_documento);
});

const placeholderDocumento = computed(() => {
    const placeholders = {
        'DNI': 'Ej: 12345678',
        'RUC': 'Ej: 12345678901',
        'CE': 'Ej: 001234567',
        'PASAPORTE': 'Ej: ABC123456'
    };
    return placeholders[formulario.value.tipo_documento] || '';
});

const maxLengthDocumento = computed(() => {
    const lengths = {
        'DNI': 8,
        'RUC': 11,
        'CE': 12,
        'PASAPORTE': 12
    };
    return lengths[formulario.value.tipo_documento] || 20;
});

const fechaMaxima = computed(() => {
    return new Date().toISOString().split('T')[0];
});

// Métodos
const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            console.error('DataTables no está disponible');
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaClientes')) {
            $('#tablaClientes').DataTable().destroy();
        }

        dataTable.value = $('#tablaClientes').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/clientes',
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
                        text: 'No se pudieron cargar los clientes'
                    });
                }
            },
            columns: [
                { 
                    data: 'codigo',
                    width: '80px',
                    render: function(data) {
                        return `<code>${data}</code>`;
                    }
                },
                { 
                    data: 'persona',
                    render: function(data) {
                        const nombre = data.razon_social || `${data.nombres} ${data.apellidos || ''}`;
                        return `<div>
                            <strong>${nombre}</strong>
                            ${data.email ? `<br><small class="text-muted"><i class="fas fa-envelope me-1"></i>${data.email}</small>` : ''}
                        </div>`;
                    }
                },
                { 
                    data: 'persona',
                    width: '130px',
                    render: function(data) {
                        return `<div>
                            <span class="badge bg-secondary">${data.tipo_documento}</span>
                            <br><small>${data.numero_documento}</small>
                        </div>`;
                    }
                },
                { 
                    data: 'persona',
                    width: '120px',
                    render: function(data) {
                        let html = '';
                        if (data.celular) {
                            html += `<small><i class="fas fa-mobile-alt me-1"></i>${data.celular}</small>`;
                        }
                        if (data.telefono) {
                            html += `<br><small><i class="fas fa-phone me-1"></i>${data.telefono}</small>`;
                        }
                        return html || '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'tipo_cliente',
                    width: '100px',
                    className: 'text-center',
                    render: function(data) {
                        const badges = {
                            'Regular': 'bg-secondary',
                            'VIP': 'bg-warning text-dark',
                            'Corporativo': 'bg-info',
                            'Mayorista': 'bg-primary'
                        };
                        return `<span class="badge ${badges[data]}">${data}</span>`;
                    }
                },
                { 
                    data: null,
                    width: '120px',
                    className: 'text-end',
                    render: function(data, type, row) {
                        const disponible = row.limite_credito - row.credito_usado;
                        const porcentaje = row.limite_credito > 0 ? (disponible / row.limite_credito * 100) : 100;
                        const clase = porcentaje > 50 ? 'success' : porcentaje > 25 ? 'warning' : 'danger';
                        
                        if (row.limite_credito > 0) {
                            return `<div>
                                <small class="text-muted">Disponible:</small>
                                <br><strong class="text-${clase}">$${disponible.toFixed(2)}</strong>
                                <br><small class="text-muted">de $${parseFloat(row.limite_credito).toFixed(2)}</small>
                            </div>`;
                        }
                        return '<span class="text-muted">Sin crédito</span>';
                    }
                },
                { 
                    data: 'estado',
                    width: '80px',
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
                    width: '120px',
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
                $('.btn-editar').off('click').on('click', function() {
                    const id = $(this).data('id');
                    editarCliente(id);
                });

                $('.btn-activar, .btn-desactivar').off('click').on('click', function() {
                    const id = $(this).data('id');
                    const esActivar = $(this).hasClass('btn-activar');
                    toggleEstado(id, esActivar);
                });
            }
        });
    }, 300);
};

const onTipoDocumentoChange = () => {
    // Limpiar campos según tipo de documento
    if (esPersonaNatural.value) {
        formulario.value.razon_social = '';
    } else {
        formulario.value.apellidos = '';
        formulario.value.fecha_nacimiento = '';
        formulario.value.sexo = '';
    }
};

const nuevoRegistro = async () => {
    formulario.value = {
        tipo_documento: 'DNI',
        numero_documento: '',
        nombres: '',
        apellidos: '',
        razon_social: '',
        email: '',
        telefono: '',
        celular: '',
        direccion: '',
        ciudad: '',
        provincia: '',
        fecha_nacimiento: '',
        sexo: '',
        codigo: '',
        tipo_cliente: 'Regular',
        limite_credito: 0,
        dias_credito: 0,
        descuento_general: 0,
        observaciones: ''
    };
    errores.value = {};
    modoEdicion.value = false;
    mostrarFormulario.value = true;
    
    // Generar código
    try {
        const response = await axios.get('/api/v1/clientes/generar-codigo');
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error('Error al generar código:', error);
    }
};

const editarCliente = async (id) => {
    try {
        const response = await axios.get(`/api/v1/clientes/${id}`);
        
        if (response.data.success) {
            const cliente = response.data.data;
            const persona = cliente.persona;
            
            formulario.value = {
                id: cliente.id,
                // Persona
                tipo_documento: persona.tipo_documento,
                numero_documento: persona.numero_documento,
                nombres: persona.nombres,
                apellidos: persona.apellidos || '',
                razon_social: persona.razon_social || '',
                email: persona.email || '',
                telefono: persona.telefono || '',
                celular: persona.celular || '',
                direccion: persona.direccion || '',
                ciudad: persona.ciudad || '',
                provincia: persona.provincia || '',
                fecha_nacimiento: persona.fecha_nacimiento || '',
                sexo: persona.sexo || '',
                
                // Cliente
                codigo: cliente.codigo,
                tipo_cliente: cliente.tipo_cliente,
                limite_credito: cliente.limite_credito,
                dias_credito: cliente.dias_credito,
                descuento_general: cliente.descuento_general,
                observaciones: cliente.observaciones || ''
            };
            
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        console.error('Error al editar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar el cliente'
        });
    }
};

const guardarCliente = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const url = modoEdicion.value 
            ? `/api/v1/clientes/${formulario.value.id}`
            : '/api/v1/clientes';

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
        title: `¿${esActivar ? 'Activar' : 'Desactivar'} cliente?`,
        text: `¿Está seguro de ${esActivar ? 'activar' : 'desactivar'} este cliente?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: esActivar ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${esActivar ? 'activar' : 'desactivar'}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/clientes/${id}/toggle-estado`);
            
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
        tipo_documento: 'DNI',
        numero_documento: '',
        nombres: '',
        apellidos: '',
        razon_social: '',
        email: '',
        telefono: '',
        celular: '',
        direccion: '',
        ciudad: '',
        provincia: '',
        fecha_nacimiento: '',
        sexo: '',
        codigo: '',
        tipo_cliente: 'Regular',
        limite_credito: 0,
        dias_credito: 0,
        descuento_general: 0,
        observaciones: ''
    };
    errores.value = {};
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
</script>

<style scoped>
.cliente-module {
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

.card-header {
    font-weight: 600;
}
</style>