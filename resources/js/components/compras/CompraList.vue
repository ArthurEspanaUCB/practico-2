<template>
    <div class="compra-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botones y filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-bag text-primary me-2"></i>
                                Gestión de Compras
                            </h5>
                            <small class="text-muted">Administra las órdenes de compra a proveedores</small>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <!-- Filtros -->
                                <select class="form-select form-select-sm" style="width: 150px;" v-model="filtros.estado" @change="aplicarFiltros">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Completada">Completada</option>
                                    <option value="Anulada">Anulada</option>
                                </select>
                                <button class="btn btn-outline-info btn-sm" @click="cargarDatos">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button class="btn btn-primary" @click="nuevoRegistro">
                                    <i class="fas fa-plus me-2"></i>Nueva Compra
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Compras -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table id="tablaCompras" class="table table-hover table-striped w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Comprobante</th>
                                <th>Total</th>
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
                            {{ modoEdicion ? 'Editar Compra' : 'Nueva Compra' }}
                        </h5>
                        <button class="btn btn-light btn-sm" @click="cancelarFormulario">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarCompra">
                        <!-- Sección: Datos del Proveedor y Compra -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-truck me-2"></i>Datos de la Compra
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Código -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Código</label>
                                        <input type="text" class="form-control bg-light" v-model="formulario.codigo" readonly>
                                    </div>

                                    <!-- Proveedor -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            Proveedor <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select" 
                                            :class="{ 'is-invalid': errores.proveedor_id }"
                                            v-model="formulario.proveedor_id"
                                            @change="onProveedorChange"
                                        >
                                            <option value="">Seleccione un proveedor</option>
                                            <option 
                                                v-for="proveedor in proveedores" 
                                                :key="proveedor.id" 
                                                :value="proveedor.id"
                                            >
                                                {{ proveedor.codigo }} - {{ proveedor.nombre }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errores.proveedor_id">
                                            {{ errores.proveedor_id[0] }}
                                        </div>
                                        <small class="text-muted" v-if="proveedorSeleccionado">
                                            Crédito disponible: ${{ proveedorSeleccionado.credito_disponible?.toLocaleString() }}
                                            | Días crédito: {{ proveedorSeleccionado.dias_credito }}
                                        </small>
                                    </div>

                                    <!-- Tipo Compra -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">
                                            Tipo Compra <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select"
                                            :class="{ 'is-invalid': errores.tipo_compra }"
                                            v-model="formulario.tipo_compra"
                                            @change="onTipoCompraChange"
                                        >
                                            <option value="Contado">Contado</option>
                                            <option value="Credito">Crédito</option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errores.tipo_compra">
                                            {{ errores.tipo_compra[0] }}
                                        </div>
                                    </div>

                                    <!-- Tipo Comprobante -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">
                                            Comprobante <span class="text-danger">*</span>
                                        </label>
                                        <select 
                                            class="form-select"
                                            :class="{ 'is-invalid': errores.tipo_comprobante }"
                                            v-model="formulario.tipo_comprobante"
                                        >
                                            <option value="Factura">Factura</option>
                                            <option value="Nota">Nota</option>
                                            <option value="Recibo">Recibo</option>
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

                                    <!-- Fecha Compra -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Fecha Compra <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="date" 
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.fecha_compra }"
                                            v-model="formulario.fecha_compra"
                                        >
                                        <div class="invalid-feedback" v-if="errores.fecha_compra">
                                            {{ errores.fecha_compra[0] }}
                                        </div>
                                    </div>

                                    <!-- Fecha Vencimiento (solo para crédito) -->
                                    <div class="col-md-3 mb-3" v-show="formulario.tipo_compra === 'Credito'">
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
                                                        Stock actual: {{ getProductoById(detalle.producto_id)?.stock }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        class="form-control form-control-sm"
                                                        v-model.number="detalle.cantidad"
                                                        min="1"
                                                        @input="calcularSubtotalDetalle(index)"
                                                    >
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
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
                                                    <strong>${{ detalle.subtotal?.toFixed(2) || '0.00' }}</strong>
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

                                <!-- Error de detalles -->
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
                                                        <strong>${{ totales.subtotal.toFixed(2) }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-end">Descuento ({{ formulario.porcentaje_descuento || 0 }}%):</td>
                                                    <td class="text-end text-danger">
                                                        -${{ totales.descuento.toFixed(2) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-end">Impuesto ({{ formulario.porcentaje_impuesto || 0 }}%):</td>
                                                    <td class="text-end">
                                                        ${{ totales.impuesto.toFixed(2) }}
                                                    </td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td class="text-end"><strong>TOTAL:</strong></td>
                                                    <td class="text-end">
                                                        <strong class="fs-5">${{ totales.total.toFixed(2) }}</strong>
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
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <i class="fas fa-save me-2"></i>
                                {{ guardando ? 'Guardando...' : 'Guardar Compra' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Ver Detalle -->
        <div class="modal fade" id="modalDetalleCompra" tabindex="-1" ref="modalDetalle">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-eye me-2"></i>Detalle de Compra
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="compraDetalle">
                        <!-- Info Cabecera -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Código:</strong> {{ compraDetalle.codigo }}</p>
                                <p><strong>Proveedor:</strong> {{ compraDetalle.proveedor?.persona?.razon_social || compraDetalle.proveedor?.persona?.nombres }}</p>
                                <p><strong>Fecha:</strong> {{ formatDate(compraDetalle.fecha_compra) }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tipo:</strong> {{ compraDetalle.tipo_compra }}</p>
                                <p><strong>Comprobante:</strong> {{ compraDetalle.tipo_comprobante }} {{ compraDetalle.numero_comprobante }}</p>
                                <p>
                                    <strong>Estado:</strong> 
                                    <span :class="getEstadoBadgeClass(compraDetalle.estado)">{{ compraDetalle.estado }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Tabla Detalles -->
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
                                <tr v-for="detalle in compraDetalle.detalles" :key="detalle.id">
                                    <td>{{ detalle.producto?.nombre }}</td>
                                    <td class="text-center">{{ detalle.cantidad }}</td>
                                    <td class="text-end">Bs. {{ parseFloat(detalle.precio_unitario).toFixed(2) }}</td>
                                    <td class="text-end">Bs. {{ parseFloat(detalle.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal:</td>
                                    <td class="text-end">Bs. {{ parseFloat(compraDetalle.subtotal).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Descuento:</td>
                                    <td class="text-end text-danger">-Bs. {{ parseFloat(compraDetalle.descuento).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Impuesto:</td>
                                    <td class="text-end">Bs. {{ parseFloat(compraDetalle.impuesto).toFixed(2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end"><strong>Bs. {{ parseFloat(compraDetalle.total).toFixed(2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Observaciones -->
                        <div v-if="compraDetalle.observaciones" class="mt-3">
                            <strong>Observaciones:</strong>
                            <p class="text-muted mb-0">{{ compraDetalle.observaciones }}</p>
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
const proveedores = ref([]);
const productos = ref([]);
const compraDetalle = ref(null);
const modalDetalleInstance = ref(null);

// Filtros
const filtros = reactive({
    estado: ''
});

// Formulario
const formulario = ref({
    id: null,
    codigo: '',
    proveedor_id: '',
    tipo_compra: 'Contado',
    tipo_comprobante: 'Factura',
    numero_comprobante: '',
    fecha_compra: new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    porcentaje_impuesto: 0,
    porcentaje_descuento: 0,
    observaciones: '',
    detalles: [crearDetalleVacio()]
});

const errores = ref({});

// Totales calculados
const totales = reactive({
    subtotal: 0,
    descuento: 0,
    impuesto: 0,
    total: 0
});

// Computed
const proveedorSeleccionado = computed(() => {
    if (!formulario.value.proveedor_id) return null;
    return proveedores.value.find(p => p.id === formulario.value.proveedor_id);
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
const cargarProveedores = async () => {
    try {
        const response = await axios.get('/api/v1/compras/proveedores');
        if (response.data.success) {
            proveedores.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar proveedores:', error);
    }
};

const cargarProductos = async () => {
    try {
        const response = await axios.get('/api/v1/compras/productos');
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
            console.error('DataTables no está disponible');
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable('#tablaCompras')) {
            $('#tablaCompras').DataTable().destroy();
        }

        dataTable.value = $('#tablaCompras').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/api/v1/compras',
                type: 'GET',
                data: function(d) {
                    d.estado = filtros.estado;
                },
                dataSrc: function(json) {
                    if (json.success) {
                        return json.data.data || json.data;
                    }
                    return [];
                },
                error: function(xhr, error) {
                    console.error('Error al cargar datos:', error);
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
                    data: 'proveedor',
                    render: function(data) {
                        const nombre = data?.persona?.razon_social || 
                            `${data?.persona?.nombres || ''} ${data?.persona?.apellidos || ''}`;
                        return `<strong>${nombre}</strong>`;
                    }
                },
                {
                    data: 'fecha_compra',
                    width: '100px',
                    render: function(data) {
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                {
                    data: 'tipo_compra',
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
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><rtip',
            drawCallback: function() {
                $('.btn-ver').off('click').on('click', function() {
                    verCompra($(this).data('id'));
                });

                $('.btn-editar').off('click').on('click', function() {
                    editarCompra($(this).data('id'));
                });

                $('.btn-completar').off('click').on('click', function() {
                    completarCompra($(this).data('id'));
                });

                $('.btn-anular').off('click').on('click', function() {
                    anularCompra($(this).data('id'));
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

    // Generar código
    try {
        const response = await axios.get('/api/v1/compras/generar-codigo');
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error('Error al generar código:', error);
    }
};

const editarCompra = async (id) => {
    try {
        const response = await axios.get(`/api/v1/compras/${id}`);

        if (response.data.success) {
            const compra = response.data.data;

            formulario.value = {
                id: compra.id,
                codigo: compra.codigo,
                proveedor_id: compra.proveedor_id,
                tipo_compra: compra.tipo_compra,
                tipo_comprobante: compra.tipo_comprobante,
                numero_comprobante: compra.numero_comprobante || '',
                fecha_compra: compra.fecha_compra?.split('T')[0],
                fecha_vencimiento: compra.fecha_vencimiento?.split('T')[0] || '',
                porcentaje_impuesto: parseFloat(compra.porcentaje_impuesto) || 0,
                porcentaje_descuento: parseFloat(compra.porcentaje_descuento) || 0,
                observaciones: compra.observaciones || '',
                detalles: compra.detalles.map(d => ({
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
        console.error('Error al cargar compra:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo cargar la compra'
        });
    }
};

const verCompra = async (id) => {
    try {
        const response = await axios.get(`/api/v1/compras/${id}`);
        if (response.data.success) {
            compraDetalle.value = response.data.data;
            
            // Mostrar modal
            if (!modalDetalleInstance.value) {
                const modalEl = document.getElementById('modalDetalleCompra');
                modalDetalleInstance.value = new bootstrap.Modal(modalEl);
            }
            modalDetalleInstance.value.show();
        }
    } catch (error) {
        console.error('Error al cargar detalle:', error);
    }
};

const completarCompra = async (id) => {
    const result = await Swal.fire({
        title: '¿Completar compra?',
        text: 'Esta acción actualizará el stock de los productos. ¿Desea continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(`/api/v1/compras/${id}/completar`);
            
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
                text: error.response?.data?.message || 'No se pudo completar la compra'
            });
        }
    }
};

const anularCompra = async (id) => {
    const result = await Swal.fire({
        title: '¿Anular compra?',
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
            const response = await axios.patch(`/api/v1/compras/${id}/anular`);
            
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
                text: error.response?.data?.message || 'No se pudo anular la compra'
            });
        }
    }
};

const guardarCompra = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const url = modoEdicion.value
            ? `/api/v1/compras/${formulario.value.id}`
            : '/api/v1/compras';

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

const agregarDetalle = () => {
    formulario.value.detalles.push(crearDetalleVacio());
};

const eliminarDetalle = (index) => {
    if (formulario.value.detalles.length > 1) {
        formulario.value.detalles.splice(index, 1);
        calcularTotales();
    }
};

const onProveedorChange = () => {
    // Si el proveedor tiene días de crédito, calcular fecha vencimiento
    if (proveedorSeleccionado.value && proveedorSeleccionado.value.dias_credito > 0 && formulario.value.tipo_compra === 'Credito') {
        const fechaCompra = new Date(formulario.value.fecha_compra);
        fechaCompra.setDate(fechaCompra.getDate() + proveedorSeleccionado.value.dias_credito);
        formulario.value.fecha_vencimiento = fechaCompra.toISOString().split('T')[0];
    }
};

const onTipoCompraChange = () => {
    if (formulario.value.tipo_compra === 'Contado') {
        formulario.value.fecha_vencimiento = '';
    } else {
        onProveedorChange();
    }
};

const onProductoChange = (index) => {
    const detalle = formulario.value.detalles[index];
    const producto = getProductoById(detalle.producto_id);
    
    if (producto) {
        detalle.precio_unitario = producto.precio_compra;
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
    // Subtotal = suma de subtotales de detalles
    totales.subtotal = formulario.value.detalles.reduce((sum, d) => sum + (d.subtotal || 0), 0);
    
    // Descuento general
    totales.descuento = totales.subtotal * (formulario.value.porcentaje_descuento / 100);
    
    // Base imponible
    const baseImponible = totales.subtotal - totales.descuento;
    
    // Impuesto
    totales.impuesto = baseImponible * (formulario.value.porcentaje_impuesto / 100);
    
    // Total
    totales.total = baseImponible + totales.impuesto;
};

const resetFormulario = () => {
    formulario.value = {
        id: null,
        codigo: '',
        proveedor_id: '',
        tipo_compra: 'Contado',
        tipo_comprobante: 'Factura',
        numero_comprobante: '',
        fecha_compra: new Date().toISOString().split('T')[0],
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
    await Promise.all([cargarProveedores(), cargarProductos()]);
    inicializarDataTable();
});
</script>

<style scoped>
.compra-module {
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

.form-select-sm, .form-control-sm {
    font-size: 0.875rem;
}
</style>
