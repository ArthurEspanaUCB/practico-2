<template>
    <div class="dashboard-profesional">
        <!-- Cargando -->
        <div v-if="cargando" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando estadísticas...</p>
        </div>

        <template v-else>
            <!-- Tarjetas de resumen -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-success">
                        <div class="stat-card-body">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-value">Bs. {{ formatNumber(stats.resumen?.ventas_mes || 0) }}</h3>
                                <p class="stat-label">Ventas del Mes</p>
                                <div class="stat-change" :class="stats.resumen?.variacion_ventas >= 0 ? 'text-success' : 'text-danger'">
                                    <i :class="stats.resumen?.variacion_ventas >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                                    {{ Math.abs(stats.resumen?.variacion_ventas || 0) }}% vs mes anterior
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-card-body">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-basket"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-value">Bs. {{ formatNumber(stats.resumen?.compras_mes || 0) }}</h3>
                                <p class="stat-label">Compras del Mes</p>
                                <div class="stat-change text-muted">
                                    Inversión en productos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-card-body">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-value">Bs. {{ formatNumber(stats.resumen?.ganancia_estimada || 0) }}</h3>
                                <p class="stat-label">Ganancia Estimada</p>
                                <div class="stat-change" :class="(stats.resumen?.ganancia_estimada || 0) >= 0 ? 'text-success' : 'text-danger'">
                                    {{ (stats.resumen?.ganancia_estimada || 0) >= 0 ? 'Positivo' : 'Negativo' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-info">
                        <div class="stat-card-body">
                            <div class="stat-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-value">{{ stats.resumen?.total_productos || 0 }}</h3>
                                <p class="stat-label">Productos Activos</p>
                                <div class="stat-change" :class="(stats.resumen?.productos_stock_bajo || 0) > 0 ? 'text-warning' : 'text-success'">
                                    <i class="fas fa-exclamation-triangle" v-if="(stats.resumen?.productos_stock_bajo || 0) > 0"></i>
                                    {{ stats.resumen?.productos_stock_bajo || 0 }} con stock bajo
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segunda fila de métricas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon bg-success">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-info">
                            <h4>{{ stats.resumen?.total_clientes || 0 }}</h4>
                            <span>Clientes Registrados</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon bg-primary">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="metric-info">
                            <h4>{{ stats.resumen?.total_proveedores || 0 }}</h4>
                            <span>Proveedores Activos</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon bg-warning">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="metric-info">
                            <h4>Bs. {{ formatNumber(stats.resumen?.ventas_mes_anterior || 0) }}</h4>
                            <span>Ventas Mes Anterior</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="row">
                <!-- Gráfico de ventas y Top productos -->
                <div class="col-xl-8 mb-4">
                    <!-- Top Productos -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-star text-warning me-2"></i>
                                Top 5 Productos del Mes
                            </h5>
                            <span class="badge bg-primary">{{ mesActual }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(prod, index) in stats.graficos?.top_productos || []" :key="index">
                                            <td>
                                                <span class="badge" :class="getBadgeClass(index)">{{ index + 1 }}</span>
                                            </td>
                                            <td><strong>{{ prod.nombre }}</strong></td>
                                            <td class="text-center">{{ prod.cantidad }}</td>
                                            <td class="text-end text-success">
                                                <strong>Bs. {{ formatNumber(prod.total) }}</strong>
                                            </td>
                                        </tr>
                                        <tr v-if="!stats.graficos?.top_productos?.length">
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                                                No hay datos de ventas este mes
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Top Clientes -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-user-friends text-info me-2"></i>
                                Top 5 Clientes del Mes
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Cliente</th>
                                            <th class="text-end">Total Compras</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(cliente, index) in stats.graficos?.top_clientes || []" :key="index">
                                            <td>
                                                <span class="badge" :class="getBadgeClass(index)">{{ index + 1 }}</span>
                                            </td>
                                            <td><strong>{{ cliente.nombre }}</strong></td>
                                            <td class="text-end text-primary">
                                                <strong>Bs. {{ formatNumber(cliente.total) }}</strong>
                                            </td>
                                        </tr>
                                        <tr v-if="!stats.graficos?.top_clientes?.length">
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                                No hay datos de clientes este mes
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar derecho -->
                <div class="col-xl-4">
                    <!-- Alertas de Stock -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Alertas de Stock
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li v-for="prod in stats.alertas?.productos_stock_bajo || []" 
                                    :key="prod.nombre" 
                                    class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ prod.nombre }}</strong>
                                        <br>
                                        <small class="text-muted">Mín: {{ prod.stock_minimo }}</small>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">{{ prod.stock }}</span>
                                </li>
                                <li v-if="!stats.alertas?.productos_stock_bajo?.length" 
                                    class="list-group-item text-center text-success py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                    <strong>¡Sin alertas!</strong>
                                    <br>
                                    <small>Todos los productos tienen stock suficiente</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Últimas Ventas -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Últimas Ventas
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li v-for="venta in stats.actividad?.ultimas_ventas || []" 
                                    :key="venta.codigo" 
                                    class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-primary">{{ venta.codigo }}</strong>
                                        <span class="text-success">Bs. {{ formatNumber(venta.total) }}</span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>{{ venta.cliente }}
                                        <span class="float-end">{{ venta.fecha }}</span>
                                    </small>
                                </li>
                                <li v-if="!stats.actividad?.ultimas_ventas?.length" 
                                    class="list-group-item text-center text-muted py-4">
                                    <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                                    No hay ventas recientes
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// Estado
const cargando = ref(true);
const stats = ref({});

// Computed
const mesActual = computed(() => {
    return new Date().toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

// Métodos
const cargarEstadisticas = async () => {
    try {
        const response = await axios.get('/api/v1/reportes/dashboard');
        if (response.data.success) {
            stats.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    } finally {
        cargando.value = false;
    }
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num || 0);
};

const getBadgeClass = (index) => {
    const classes = ['bg-warning text-dark', 'bg-secondary', 'bg-dark', 'bg-info', 'bg-light text-dark'];
    return classes[index] || 'bg-light text-dark';
};

onMounted(() => {
    cargarEstadisticas();
});
</script>

<style scoped>
/* Tarjetas de estadísticas principales */
.stat-card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card-body {
    padding: 1.5rem;
    display: flex;
    align-items: center;
}

.stat-card-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.stat-card-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.stat-card-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%);
    color: white;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.stat-change {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Tarjetas de métricas secundarias */
.metric-card {
    background: white;
    border-radius: 10px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-3px);
}

.metric-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
    margin-right: 1rem;
}

.metric-info h4 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
    color: #2c3e50;
}

.metric-info span {
    font-size: 0.8rem;
    color: #7f8c8d;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #eee;
}

/* Tablas */
.table thead th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #7f8c8d;
}

/* Badges de ranking */
.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card, .metric-card, .card {
    animation: fadeInUp 0.5s ease forwards;
}

.col-xl-3:nth-child(1) .stat-card { animation-delay: 0.1s; }
.col-xl-3:nth-child(2) .stat-card { animation-delay: 0.2s; }
.col-xl-3:nth-child(3) .stat-card { animation-delay: 0.3s; }
.col-xl-3:nth-child(4) .stat-card { animation-delay: 0.4s; }
</style>
