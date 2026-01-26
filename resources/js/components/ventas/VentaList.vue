<template>
    <div class="venta-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botones y filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-cash-register text-success me-2"></i>
                                Gestión de Ventas
                            </h5>
                            <small class="text-muted">Administra las ventas a clientes</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <select class="form-select form-select-sm" style="width: 150px;" v-model="filtros.estado" @change="aplicarFiltros">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Completada">Completada</option>
                                    <option value="Anulada">Anulada</option>
                                </select>
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-success" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nueva Venta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Ventas -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaVentas" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Comprobante</th>
                                <th>Total</th>
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
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i :class="modoEdicion ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
                            {{ modoEdicion ? 'Editar Venta' : 'Nueva Venta' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarVenta">
                        <!-- Sección: Datos del Cliente y Venta -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Datos de la Venta
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Código -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Código</label>
                                        <input type="text" class="form-control bg-light" v-model="formulario.codigo" readonly>
                                    </div>

                                    <!-- Cliente -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            Cliente <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select" 
                                            :class="{ 'is-invalid': errores.cliente_id }"
                                            v-model="formulario.cliente_id"
                                            @change="onClienteChange"
                                        >
                                            <option value="">Seleccione un cliente</option>
                                            <option 
                                                v-for="cliente in clientes" 
                                                :key="cliente.id" 
                                                :value="cliente.id"
                                            >
                                                {{ cliente.codigo }} - {{ cliente.nombre }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errores.cliente_id">
                                            {{ errores.cliente_id[0] }}
                                        </div>
                                        <small class="text-muted" v-if="clienteSeleccionado">
                                            Crédito disponible: Bs. {{ clienteSeleccionado.credito_disponible?.toLocaleString() }}
                                            | Días crédito: {{ clienteSeleccionado.dias_credito }}
                                        </small>
                                    </div>

                                    <!-- Tipo Venta -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">
                                            Tipo Venta <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select"
                                            :class="{ 'is-invalid': errores.tipo_venta }"
                                            v-model="formulario.tipo_venta"
                                            @change="onTipoVentaChange"
                                        >
                                            <option value="Contado">Contado</option>
                                            <option value="Credito">Crédito</option>
                                        </select>
                                    </div>

                                    <!-- Tipo Comprobante -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">
                                            Comprobante <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select"
                                            v-model="formulario.tipo_comprobante"
                                        >
                                            <option value="Factura">Factura</option>
                                            <option value="Boleta">Boleta</option>
                                            <option value="Nota">Nota</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>

                                    <!-- Número Comprobante -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">N° Comprobante</label>
                                        <input 
                                            type="text" 
                                            class="form-control"
                                            v-model="formulario.numero_comprobante"
                                            placeholder="Ej: F001-00123"
                                            maxlength="100"
                                        >
                                    </div>

                                    <!-- Fecha Venta -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Fecha Venta <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="date" 
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.fecha_venta }"
                                            v-model="formulario.fecha_venta"
                                        >
                                    </div>

                                    <!-- Fecha Vencimiento -->
                                    <div class="col-md-3 mb-3" v-show="formulario.tipo_venta === 'Credito'">
                                        <label class="form-label">Fecha Vencimiento</label>
                                        <input 
                                            type="date" 
                                            class="form-control"
                                            v-model="formulario.fecha_vencimiento"
                                        >
                                    </div>

                                    <!-- Impuesto -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">% Impuesto</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control"
                                            v-model="formulario.porcentaje_impuesto"
                                            placeholder="0"
                                            min="0"
                                            max="100"
                                            @input="calcularTotales"
                                        >
                                    </div>

                                    <!-- Descuento General -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">% Descuento</label>
                                        <input 
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            v-model="formulario.porcentaje_descuento"
                                            placeholder="0"
                                            min="0"
                                            max="100"
                                            @input="calcularTotales"
                                        >
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea 
                                            class="form-control"
                                            v-model="formulario.observaciones"
                                            placeholder="Notas adicionales"
                                            rows="2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Detalle de Productos -->
                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-boxes me-2"></i>Detalle de Productos
                                </h6>
                                <button type="button" class="btn btn-success btn-sm" @click="agregarDetalle">
                                    <i class="fas fa-plus me-1"></i>Agregar Producto
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th style="width: 40%;">Producto</th>
                                                <th style="width: 12%;">Cantidad</th>
                                                <th style="width: 15%;">Precio Unit.</th>
                                                <th style="width: 10%;">% Desc.</th>
                                                <th style="width: 15%;">Subtotal</th>
                                                <th style="width: 8%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(detalle, index) in formulario.detalles" :key="index">
                                                <td>
                                                    <select 
                                                        class="form-select form-select-sm"
                                                        v-model="detalle.producto_id"
                                                        @change="onProductoChange(index)"
                                                    >
                                                        <option value="">Seleccione producto</option>
                                                        <option 
                                                            v-for="producto in productos" 
                                                            :key="producto.id" 
                                                            :value="producto.id"
                                                        >
                                                            {{ producto.codigo }} - {{ producto.nombre }}
                                                        </option>
                                                    </select>
                                                    <small class="text-muted" v-if="detalle.producto_id">
                                                        Stock: {{ getProductoById(detalle.producto_id)?.stock }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        class="form-control form-control-sm"
                                                        :class="{ 'is-invalid': detalle.cantidad > (getProductoById(detalle.producto_id)?.stock || 0) }"
                                                        v-model.number="detalle.cantidad"
                                                        min="1"
                                                        @input="calcularSubtotalDetalle(index)"
                                                    >
                                                    <small class="text-danger" v-if="detalle.producto_id && detalle.cantidad > (getProductoById(detalle.producto_id)?.stock || 0)">
                                                        Stock insuficiente
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Bs.</span>
                                                        <input 
                                                            type="number" 
                                                            step="0.01"
                                                            class="form-control form-control-sm"
                                                            v-model.number="detalle.precio_unitario"
                                                            min="0"
                                                            @input="calcularSubtotalDetalle(index)"
                                                        >
                                                    </div>
                                                </td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        step="0.01"
                                                        class="form-control form-control-sm"
                                                        v-model.number="detalle.porcentaje_descuento"
                                                        min="0"
                                                        max="100"
                                                        @input="calcularSubtotalDetalle(index)"
                                                    >
                                                </td>
                                                <td class="text-end">
                                                    <strong>Bs. {{ detalle.subtotal?.toFixed(2) || '0.00' }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-danger btn-sm"
                                                        @click="eliminarDetalle(index)"
                                                        :disabled="formulario.detalles.length === 1"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="formulario.detalles.length === 0">
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-box-open fa-2x mb-2"></i>
                                                    <p class="mb-0">No hay productos agregados</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-danger py-2" v-if="errores.detalles">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    {{ errores.detalles[0] }}
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Totales -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-calculator me-2"></i>Resumen de Totales
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row justify-content-end">
                                    <div class="col-md-4">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="text-end">Subtotal:</td>
                                                    <td class="text-end" style="width: 120px;">
                                                        <strong>Bs. {{ totales.subtotal.toFixed(2) }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-end">Descuento ({{ formulario.porcentaje_descuento || 0 }}%):</td>
                                                    <td class="text-end text-danger">
                                                        -Bs. {{ totales.descuento.toFixed(2) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-end">Impuesto ({{ formulario.porcentaje_impuesto || 0 }}%):</td>
                                                    <td class="text-end">
                                                        Bs. {{ totales.impuesto.toFixed(2) }}
                                                    </td>
                                                </tr>
                                                <tr class="table-success">
                                                    <td class="text-end"><strong>TOTAL:</strong></td>
                                                    <td class="text-end">
                                                        <strong class="fs-5">Bs. {{ totales.total.toFixed(2) }}</strong>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="cancelarFormulario">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-success" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Venta' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ver Detalle -->
        <div class="modal fade" id="modalDetalleVenta" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-eye me-2"></i>Detalle de Venta
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="ventaDetalle">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Código:</strong> {{ ventaDetalle.codigo }}</p>
                                <p><strong>Cliente:</strong> {{ ventaDetalle.cliente?.persona?.razon_social || ventaDetalle.cliente?.persona?.nombres }}</p>
                                <p><strong>Fecha:</strong> {{ formatDate(ventaDetalle.fecha_venta) }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tipo:</strong> {{ ventaDetalle.tipo_venta }}</p>
                                <p><strong>Comprobante:</strong> {{ ventaDetalle.tipo_comprobante }} {{ ventaDetalle.numero_comprobante }}</p>
                                <p>
                                    <strong>Estado:</strong> 
                                    <span :class="getEstadoBadgeClass(ventaDetalle.estado)">{{ ventaDetalle.estado }}</span>
                                </p>
                            </div>
                        </div>

                        <table class="table table-sm table-bordered">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">P. Unitario</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detalle in ventaDetalle.detalles" :key="detalle.id">
                                    <td>{{ detalle.producto?.nombre }}</td>
                                    <td class="text-center">{{ detalle.cantidad }}</td>
                                    <td class="text-end">Bs. {{ parseFloat(detalle.precio_unitario).toFixed(2) }}</td>
                                    <td class="text-end">Bs. {{ parseFloat(detalle.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal:</td>
                                    <td class="text-end">Bs. {{ parseFloat(ventaDetalle.subtotal).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Descuento:</td>
                                    <td class="text-end text-danger">-Bs. {{ parseFloat(ventaDetalle.descuento).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Impuesto:</td>
                                    <td class="text-end">Bs. {{ parseFloat(ventaDetalle.impuesto).toFixed(2) }}</td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end"><strong>Bs. {{ parseFloat(ventaDetalle.total).toFixed(2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div v-if="ventaDetalle.observaciones" class="mt-3">
                            <strong>Observaciones:</strong>
                            <p class="text-muted mb-0">{{ ventaDetalle.observaciones }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

const $ = window.$;

// Estado
const mostrarFormulario = ref(false);
const modoEdicion = ref(false);
const guardando = ref(false);
const dataTable = ref(null);
const clientes = ref([]);
const productos = ref([]);
const ventaDetalle = ref(null);
const modalDetalleInstance = ref(null);

// Filtros
const filtros = reactive({
    estado: ''
});

// Formulario
const formulario = ref({
    id: null,
    codigo: '',
    cliente_id: '',
    tipo_venta: 'Contado',
    tipo_comprobante: 'Factura',
    numero_comprobante: '',
    fecha_venta: new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    porcentaje_impuesto: 0,
    porcentaje_descuento: 0,
    observaciones: '',
    detalles: [crearDetalleVacio()]
});

const errores = ref({});

const totales = reactive({
    subtotal: 0,
    descuento: 0,
    impuesto: 0,
    total: 0
});

// Computed
const clienteSeleccionado = computed(() => {
    if (!formulario.value.cliente_id) return null;
    return clientes.value.find(c => c.id === formulario.value.cliente_id);
});

// Funciones auxiliares
function crearDetalleVacio() {
    return {
        producto_id: '',
        cantidad: 1,
        precio_unitario: 0,
        porcentaje_descuento: 0,
        subtotal: 0
    };
}

function getProductoById(id) {
    return productos.value.find(p => p.id === id);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function getEstadoBadgeClass(estado) {
    const classes = {
        'Pendiente': 'badge bg-warning text-dark',
        'Completada': 'badge bg-success',
        'Anulada': 'badge bg-danger'
    };
    return classes[estado] || 'badge bg-secondary';
}

// Métodos
const cargarClientes = async () => {
    try {
        const response = await axios.get('/api/v1/ventas/clientes');
        if (response.data.success) {
            clientes.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar clientes:', error);
    }
};

const cargarProductos = async () => {
    try {
        const response = await axios.get('/api/v1/ventas/productos');
        if (response.data.success) {
            productos.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
};

const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaVentas')) {
            $('#tablaVentas').DataTable().destroy();
        }

        dataTable.value = $('#tablaVentas').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/ventas',
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
                    data: 'cliente',
                    render: function(data) {
                        const nombre = data?.persona?.razon_social || 
                            `${data?.persona?.nombres || ''} ${data?.persona?.apellidos || ''}`;
                        return `<strong>${nombre}</strong>`;
                    }
                },
                {
                    data: 'fecha_venta',
                    width: '100px',
                    render: function(data) {
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                {
                    data: 'tipo_venta',
                    width: '90px',
                    render: function(data) {
                        const badge = data === 'Contado' ? 'bg-info' : 'bg-warning text-dark';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    width: '120px',
                    render: function(data, type, row) {
                        return `${row.tipo_comprobante}<br><small class="text-muted">${row.numero_comprobante || '-'}</small>`;
                    }
                },
                {
                    data: 'total',
                    width: '100px',
                    className: 'text-end',
                    render: function(data) {
                        return `<strong>Bs. ${parseFloat(data).toLocaleString('es-ES', {minimumFractionDigits: 2})}</strong>`;
                    }
                },
                {
                    data: 'estado',
                    width: '100px',
                    className: 'text-center',
                    render: function(data) {
                        const badges = {
                            'Pendiente': 'bg-warning text-dark',
                            'Completada': 'bg-success',
                            'Anulada': 'bg-danger'
                        };
                        const icons = {
                            'Pendiente': 'fa-clock',
                            'Completada': 'fa-check',
                            'Anulada': 'fa-ban'
                        };
                        return `<span class="badge ${badges[data]}"><i class="fas ${icons[data]} me-1"></i>${data}</span>`;
                    }
                },
                {
                    data: null,
                    width: '150px',
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        let botones = `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info btn-ver" data-id="${row.id}" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </button>`;
                        
                        if (row.estado === 'Pendiente') {
                            botones += `
                                <button class="btn btn-sm btn-primary btn-editar" data-id="${row.id}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-success btn-completar" data-id="${row.id}" title="Completar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-anular" data-id="${row.id}" title="Anular">
                                    <i class="fas fa-ban"></i>
                                </button>`;
                        }
                        
                        botones += '</div>';
                        return botones;
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
                $('.btn-ver').off('click').on('click', function() {
                    verVenta($(this).data('id'));
                });

                $('.btn-editar').off('click').on('click', function() {
                    editarVenta($(this).data('id'));
                });

                $('.btn-completar').off('click').on('click', function() {
                    completarVenta($(this).data('id'));
                });

                $('.btn-anular').off('click').on('click', function() {
                    anularVenta($(this).data('id'));
                });
            }
        });
    }, 300);
};

const aplicarFiltros = () => {
    if (dataTable.value) {
        dataTable.value.ajax.reload();
    }
};

const nuevoRegistro = async () => {
    resetFormulario();
    modoEdicion.value = false;
    mostrarFormulario.value = true;

    try {
        const response = await axios.get('/api/v1/ventas/generar-codigo');
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error('Error al generar código:', error);
    }
};

const editarVenta = async (id) => {
    try {
        const response = await axios.get(`/api/v1/ventas/${id}`);

        if (response.data.success) {
            const venta = response.data.data;

            formulario.value = {
                id: venta.id,
                codigo: venta.codigo,
                cliente_id: venta.cliente_id,
                tipo_venta: venta.tipo_venta,
                tipo_comprobante: venta.tipo_comprobante,
                numero_comprobante: venta.numero_comprobante || '',
                fecha_venta: venta.fecha_venta?.split('T')[0],
                fecha_vencimiento: venta.fecha_vencimiento?.split('T')[0] || '',
                porcentaje_impuesto: parseFloat(venta.porcentaje_impuesto) || 0,
                porcentaje_descuento: parseFloat(venta.porcentaje_descuento) || 0,
                observaciones: venta.observaciones || '',
                detalles: venta.detalles.map(d => ({
                    producto_id: d.producto_id,
                    cantidad: d.cantidad,
                    precio_unitario: parseFloat(d.precio_unitario),
                    porcentaje_descuento: parseFloat(d.porcentaje_descuento) || 0,
                    subtotal: parseFloat(d.subtotal)
                }))
            };

            calcularTotales();
            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar la venta'
        });
    }
};

const verVenta = async (id) => {
    try {
        const response = await axios.get(`/api/v1/ventas/${id}`);
        if (response.data.success) {
            ventaDetalle.value = response.data.data;
            
            if (!modalDetalleInstance.value) {
                const modalEl = document.getElementById('modalDetalleVenta');
                modalDetalleInstance.value = new bootstrap.Modal(modalEl);
            }
            modalDetalleInstance.value.show();
        }
    } catch (error) {
        console.error('Error al cargar detalle:', error);
    }
};

const completarVenta = async (id) => {
    const result = await Swal.fire({
        title: '¿Completar venta?',
        text: 'Esta acción reducirá el stock de los productos. ¿Desea continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/ventas/${id}/completar`);
            
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Completada!',
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
                text: error.response?.data?.message || 'No se pudo completar la venta'
            });
        }
    }
};

const anularVenta = async (id) => {
    const result = await Swal.fire({
        title: '¿Anular venta?',
        text: 'Esta acción no se puede deshacer. ¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/ventas/${id}/anular`);
            
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Anulada!',
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
                text: error.response?.data?.message || 'No se pudo anular la venta'
            });
        }
    }
};

const guardarVenta = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const url = modoEdicion.value
            ? `/api/v1/ventas/${formulario.value.id}`
            : '/api/v1/ventas';

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

const agregarDetalle = () => {
    formulario.value.detalles.push(crearDetalleVacio());
};

const eliminarDetalle = (index) => {
    if (formulario.value.detalles.length > 1) {
        formulario.value.detalles.splice(index, 1);
        calcularTotales();
    }
};

const onClienteChange = () => {
    if (clienteSeleccionado.value && clienteSeleccionado.value.dias_credito > 0 && formulario.value.tipo_venta === 'Credito') {
        const fechaVenta = new Date(formulario.value.fecha_venta);
        fechaVenta.setDate(fechaVenta.getDate() + clienteSeleccionado.value.dias_credito);
        formulario.value.fecha_vencimiento = fechaVenta.toISOString().split('T')[0];
    }
};

const onTipoVentaChange = () => {
    if (formulario.value.tipo_venta === 'Contado') {
        formulario.value.fecha_vencimiento = '';
    } else {
        onClienteChange();
    }
};

const onProductoChange = (index) => {
    const detalle = formulario.value.detalles[index];
    const producto = getProductoById(detalle.producto_id);
    
    if (producto) {
        detalle.precio_unitario = producto.precio_venta;
        calcularSubtotalDetalle(index);
    }
};

const calcularSubtotalDetalle = (index) => {
    const detalle = formulario.value.detalles[index];
    const precioBase = detalle.cantidad * detalle.precio_unitario;
    const descuento = precioBase * (detalle.porcentaje_descuento / 100);
    detalle.subtotal = precioBase - descuento;
    calcularTotales();
};

const calcularTotales = () => {
    totales.subtotal = formulario.value.detalles.reduce((sum, d) => sum + (d.subtotal || 0), 0);
    totales.descuento = totales.subtotal * (formulario.value.porcentaje_descuento / 100);
    const baseImponible = totales.subtotal - totales.descuento;
    totales.impuesto = baseImponible * (formulario.value.porcentaje_impuesto / 100);
    totales.total = baseImponible + totales.impuesto;
};

const resetFormulario = () => {
    formulario.value = {
        id: null,
        codigo: '',
        cliente_id: '',
        tipo_venta: 'Contado',
        tipo_comprobante: 'Factura',
        numero_comprobante: '',
        fecha_venta: new Date().toISOString().split('T')[0],
        fecha_vencimiento: '',
        porcentaje_impuesto: 0,
        porcentaje_descuento: 0,
        observaciones: '',
        detalles: [crearDetalleVacio()]
    };
    errores.value = {};
    totales.subtotal = 0;
    totales.descuento = 0;
    totales.impuesto = 0;
    totales.total = 0;
};

const cancelarFormulario = () => {
    mostrarFormulario.value = false;
    modoEdicion.value = false;
    resetFormulario();
};

const cargarDatos = () => {
    if (dataTable.value) {
        dataTable.value.ajax.reload();
    }
};

// Lifecycle
onMounted(async () => {
    await Promise.all([cargarClientes(), cargarProductos()]);
    inicializarDataTable();
});
</script>

<style scoped>
.venta-module {
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
    background-color: rgba(40, 167, 69, 0.05);
}

.card-header {
    font-weight: 600;
}
</style>
