<template>
    <div class="reportes-module">
        <div class="row">
            <!-- Sidebar de tipos de reportes -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-pdf me-2"></i>Tipos de Reportes
                        </h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#" 
                           v-for="tipo in tiposReporte" 
                           :key="tipo.id"
                           class="list-group-item list-group-item-action d-flex align-items-center"
                           :class="{ 'active': tipoSeleccionado === tipo.id }"
                           @click.prevent="seleccionarTipo(tipo.id)">
                            <i :class="tipo.icono" class="me-3" style="width: 20px;"></i>
                            <div>
                                <strong>{{ tipo.nombre }}</strong>
                                <small class="d-block text-muted" v-if="tipoSeleccionado !== tipo.id">
                                    {{ tipo.descripcion }}
                                </small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Panel de configuración del reporte -->
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i :class="tipoActual.icono" class="me-2"></i>
                            {{ tipoActual.nombre }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">{{ tipoActual.descripcion }}</p>

                        <!-- Filtros dinámicos según el tipo de reporte -->
                        <div class="row mb-4">
                            <!-- Filtros de fechas (para ventas, compras, finanzas) -->
                            <template v-if="['ventas', 'compras', 'finanzas'].includes(tipoSeleccionado)">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" v-model="filtros.fecha_inicio">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" class="form-control" v-model="filtros.fecha_fin">
                                </div>
                            </template>

                            <!-- Filtro de estado (para ventas, compras) -->
                            <template v-if="['ventas', 'compras'].includes(tipoSeleccionado)">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" v-model="filtros.estado">
                                        <option value="">Todos</option>
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Completada">Completada</option>
                                        <option value="Anulada">Anulada</option>
                                    </select>
                                </div>
                            </template>

                            <!-- Filtro de cliente (para ventas) -->
                            <template v-if="tipoSeleccionado === 'ventas'">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Cliente</label>
                                    <select class="form-select" v-model="filtros.cliente_id">
                                        <option value="">Todos</option>
                                        <option v-for="cliente in datosF.clientes" :key="cliente.id" :value="cliente.id">
                                            {{ cliente.nombre }}
                                        </option>
                                    </select>
                                </div>
                            </template>

                            <!-- Filtro de proveedor (para compras) -->
                            <template v-if="tipoSeleccionado === 'compras'">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Proveedor</label>
                                    <select class="form-select" v-model="filtros.proveedor_id">
                                        <option value="">Todos</option>
                                        <option v-for="prov in datosF.proveedores" :key="prov.id" :value="prov.id">
                                            {{ prov.nombre }}
                                        </option>
                                    </select>
                                </div>
                            </template>

                            <!-- Filtro de categoría (para inventario) -->
                            <template v-if="tipoSeleccionado === 'inventario'">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" v-model="filtros.categoria_id">
                                        <option value="">Todas</option>
                                        <option v-for="cat in datosF.categorias" :key="cat.id" :value="cat.id">
                                            {{ cat.nombre }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock</label>
                                    <select class="form-select" v-model="filtros.stock_bajo">
                                        <option value="">Todos</option>
                                        <option value="true">Solo Stock Bajo</option>
                                    </select>
                                </div>
                            </template>

                            <!-- Filtro de estado (para clientes, proveedores) -->
                            <template v-if="['clientes', 'proveedores'].includes(tipoSeleccionado)">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" v-model="filtros.estado_entidad">
                                        <option value="">Todos</option>
                                        <option value="true">Activos</option>
                                        <option value="false">Inactivos</option>
                                    </select>
                                </div>
                            </template>
                        </div>

                        <!-- Botón de generar -->
                        <div class="d-flex gap-3">
                            <button 
                                class="btn btn-danger btn-lg"
                                @click="generarPDF"
                                :disabled="generando">
                                <i class="fas fa-file-pdf me-2"></i>
                                {{ generando ? 'Generando...' : 'Generar PDF' }}
                            </button>
                            <button class="btn btn-outline-secondary" @click="limpiarFiltros">
                                <i class="fas fa-eraser me-2"></i>Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vista previa / información -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Información del Reporte
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tipo de Reporte:</strong> {{ tipoActual.nombre }}</p>
                                <p><strong>Formato de Salida:</strong> PDF (A4)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fecha de Generación:</strong> {{ fechaActual }}</p>
                                <p><strong>Usuario:</strong> Administrador</p>
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Consejo:</strong> {{ tipoActual.consejo }}
                        </div>
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

// Estado
const tipoSeleccionado = ref('ventas');
const generando = ref(false);
const datosF = ref({
    clientes: [],
    proveedores: [],
    categorias: []
});

// Filtros
const filtros = reactive({
    fecha_inicio: '',
    fecha_fin: '',
    estado: '',
    cliente_id: '',
    proveedor_id: '',
    categoria_id: '',
    stock_bajo: '',
    estado_entidad: ''
});

// Tipos de reportes disponibles
const tiposReporte = [
    {
        id: 'ventas',
        nombre: 'Reporte de Ventas',
        descripcion: 'Listado de ventas con totales y estados',
        icono: 'fas fa-shopping-cart text-success',
        consejo: 'Filtra por fechas para obtener un resumen mensual o semanal de tus ventas.'
    },
    {
        id: 'compras',
        nombre: 'Reporte de Compras',
        descripcion: 'Historial de compras a proveedores',
        icono: 'fas fa-truck text-primary',
        consejo: 'Usa el filtro de proveedor para analizar tus compras por cada uno.'
    },
    {
        id: 'inventario',
        nombre: 'Reporte de Inventario',
        descripcion: 'Estado actual del stock de productos',
        icono: 'fas fa-boxes text-warning',
        consejo: 'Activa "Solo Stock Bajo" para identificar productos que necesitan reposición.'
    },
    {
        id: 'clientes',
        nombre: 'Reporte de Clientes',
        descripcion: 'Listado de clientes con estadísticas',
        icono: 'fas fa-users text-info',
        consejo: 'Este reporte incluye el total de compras de cada cliente.'
    },
    {
        id: 'proveedores',
        nombre: 'Reporte de Proveedores',
        descripcion: 'Listado de proveedores con estadísticas',
        icono: 'fas fa-industry text-secondary',
        consejo: 'Incluye el historial de compras realizadas a cada proveedor.'
    },
    {
        id: 'finanzas',
        nombre: 'Reporte Financiero',
        descripcion: 'Resumen de ingresos, gastos y ganancias',
        icono: 'fas fa-chart-line text-danger',
        consejo: 'Ideal para análisis mensual de rentabilidad del negocio.'
    }
];

// Computed
const tipoActual = computed(() => {
    return tiposReporte.find(t => t.id === tipoSeleccionado.value) || tiposReporte[0];
});

const fechaActual = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
});

// Métodos
const cargarFiltros = async () => {
    try {
        const response = await axios.get('/api/v1/reportes/filtros');
        if (response.data.success) {
            datosF.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar filtros:', error);
    }
};

const seleccionarTipo = (tipo) => {
    tipoSeleccionado.value = tipo;
    limpiarFiltros();
};

const limpiarFiltros = () => {
    Object.keys(filtros).forEach(key => {
        filtros[key] = '';
    });
};

const generarPDF = async () => {
    generando.value = true;

    try {
        // Construir query params
        const params = new URLSearchParams();
        
        if (filtros.fecha_inicio) params.append('fecha_inicio', filtros.fecha_inicio);
        if (filtros.fecha_fin) params.append('fecha_fin', filtros.fecha_fin);
        if (filtros.estado) params.append('estado', filtros.estado);
        if (filtros.cliente_id) params.append('cliente_id', filtros.cliente_id);
        if (filtros.proveedor_id) params.append('proveedor_id', filtros.proveedor_id);
        if (filtros.categoria_id) params.append('categoria_id', filtros.categoria_id);
        if (filtros.stock_bajo) params.append('stock_bajo', filtros.stock_bajo);
        if (filtros.estado_entidad) params.append('estado', filtros.estado_entidad);

        const url = `/api/v1/reportes/${tipoSeleccionado.value}/pdf?${params.toString()}`;
        
        // Abrir en nueva ventana
        window.open(url, '_blank');

        Swal.fire({
            icon: 'success',
            title: 'Reporte Generado',
            text: 'El PDF se ha abierto en una nueva pestaña',
            timer: 2000,
            showConfirmButton: false
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo generar el reporte'
        });
    } finally {
        generando.value = false;
    }
};

onMounted(() => {
    cargarFiltros();
    
    // Establecer fechas por defecto (mes actual)
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    filtros.fecha_inicio = primerDia.toISOString().split('T')[0];
    filtros.fecha_fin = hoy.toISOString().split('T')[0];
});
</script>

<style scoped>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.list-group-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
}

.list-group-item:hover:not(.active) {
    background-color: #f8f9fa;
}
</style>
