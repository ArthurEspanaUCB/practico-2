<template>
    <div class="proveedor-module">
        <!-- Vista de Tabla -->
        <div v-show="!mostrarFormulario" class="fade-in">
            <!-- Header con botones y filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">
                                <i class="fas fa-truck text-primary me-2"></i>
                                Gestión de Proveedores
                            </h5>
                            <small class="text-muted"
                                >Administra tus proveedores y suministros</small
                            >
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-end gap-2">
                                <button
                                    class="btn btn-outline-info btn-sm"
                                    @click="cargarDatos"
                                >
                                    <i class="fas fa-sync-alt me-1"></i
                                    >Actualizar
                                </button>
                                <button
                                    class="btn btn-primary"
                                    @click="nuevoRegistro"
                                >
                                    <i class="fas fa-plus me-2"></i>Nuevo
                                    Proveedor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Proveedores -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <table
                        id="tablaProveedores"
                        class="table table-hover table-striped w-100"
                    >
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Proveedor</th>
                                <th>Documento</th>
                                <th>Contacto</th>
                                <th>Tipo/Rubro</th>
                                <th>Calificación</th>
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
                    <div
                        class="d-flex justify-content-between align-items-center"
                    >
                        <h5 class="mb-0">
                            <i
                                :class="
                                    modoEdicion ? 'fas fa-edit' : 'fas fa-plus'
                                "
                                class="me-2"
                            ></i>
                            {{
                                modoEdicion
                                    ? "Editar Proveedor"
                                    : "Nuevo Proveedor"
                            }}
                        </h5>
                        <button
                            class="btn btn-light btn-sm"
                            @click="cancelarFormulario"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarProveedor">
                        <!-- Sección: Datos de Empresa/Persona -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-building me-2"></i>Datos de
                                    Empresa/Persona
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Tipo de Documento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Tipo Doc.
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            class="form-select"
                                            :class="{
                                                'is-invalid':
                                                    errores.tipo_documento,
                                            }"
                                            v-model="formulario.tipo_documento"
                                            @change="onTipoDocumentoChange"
                                        >
                                            <option value="DNI">DNI</option>
                                            <option value="RUC">RUC</option>
                                            <option value="CE">
                                                Carnet Extranjería
                                            </option>
                                            <option value="PASAPORTE">
                                                Pasaporte
                                            </option>
                                        </select>
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.tipo_documento"
                                        >
                                            {{ errores.tipo_documento[0] }}
                                        </div>
                                    </div>

                                    <!-- Número de Documento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            N° Documento
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    errores.numero_documento,
                                            }"
                                            v-model="
                                                formulario.numero_documento
                                            "
                                            :placeholder="placeholderDocumento"
                                            :maxlength="maxLengthDocumento"
                                        />
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.numero_documento"
                                        >
                                            {{ errores.numero_documento[0] }}
                                        </div>
                                    </div>

                                    <!-- Nombres (para personas naturales) -->
                                    <div
                                        class="col-md-3 mb-3"
                                        v-show="esPersonaNatural"
                                    >
                                        <label class="form-label">
                                            Nombres
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{
                                                'is-invalid': errores.nombres,
                                            }"
                                            v-model="formulario.nombres"
                                            placeholder="Nombres"
                                            maxlength="100"
                                        />
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.nombres"
                                        >
                                            {{ errores.nombres[0] }}
                                        </div>
                                    </div>

                                    <!-- Apellidos (para personas naturales) -->
                                    <div
                                        class="col-md-3 mb-3"
                                        v-show="esPersonaNatural"
                                    >
                                        <label class="form-label">
                                            Apellidos
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{
                                                'is-invalid': errores.apellidos,
                                            }"
                                            v-model="formulario.apellidos"
                                            placeholder="Apellidos"
                                            maxlength="100"
                                        />
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.apellidos"
                                        >
                                            {{ errores.apellidos[0] }}
                                        </div>
                                    </div>

                                    <!-- Razón Social (para RUC) -->
                                    <div
                                        class="col-md-6 mb-3"
                                        v-show="!esPersonaNatural"
                                    >
                                        <label class="form-label">
                                            Razón Social
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    errores.razon_social,
                                            }"
                                            v-model="formulario.razon_social"
                                            placeholder="Razón Social de la Empresa"
                                            maxlength="200"
                                        />
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.razon_social"
                                        >
                                            {{ errores.razon_social[0] }}
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Email</label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            :class="{
                                                'is-invalid': errores.email,
                                            }"
                                            v-model="formulario.email"
                                            placeholder="correo@ejemplo.com"
                                        />
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.email"
                                        >
                                            {{ errores.email[0] }}
                                        </div>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label"
                                            >Teléfono</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.telefono"
                                            placeholder="(000) 000-0000"
                                            maxlength="20"
                                        />
                                    </div>

                                    <!-- Celular -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label"
                                            >Celular</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.celular"
                                            placeholder="000-000-0000"
                                            maxlength="20"
                                        />
                                    </div>

                                    <!-- Dirección -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            >Dirección</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.direccion"
                                            placeholder="Calle, Número, Zona"
                                            maxlength="250"
                                        />
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
                                        />
                                    </div>

                                    <!-- Provincia/Departamento -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Provincia/Depto</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.provincia"
                                            placeholder="Provincia"
                                            maxlength="100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Datos de Proveedor -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i
                                    >Datos de Proveedor
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Código Proveedor -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Código Proveedor</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control bg-light"
                                            v-model="formulario.codigo"
                                            readonly
                                        />
                                    </div>

                                    <!-- Tipo Proveedor -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Tipo Proveedor
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            class="form-select"
                                            :class="{
                                                'is-invalid':
                                                    errores.tipo_proveedor,
                                            }"
                                            v-model="formulario.tipo_proveedor"
                                        >
                                            <option value="Producto">
                                                Producto
                                            </option>
                                            <option value="Servicio">
                                                Servicio
                                            </option>
                                            <option value="Ambos">Ambos</option>
                                        </select>
                                        <div
                                            class="invalid-feedback"
                                            v-if="errores.tipo_proveedor"
                                        >
                                            {{ errores.tipo_proveedor[0] }}
                                        </div>
                                    </div>

                                    <!-- Rubro -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Rubro</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.rubro"
                                            placeholder="Ej: Tecnología, Alimentos"
                                            maxlength="150"
                                        />
                                    </div>

                                    <!-- Calificación -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Calificación</label
                                        >
                                        <select
                                            class="form-select"
                                            v-model="formulario.calificacion"
                                        >
                                            <option :value="5">
                                                Sobresaliente
                                            </option>
                                            <option :value="4">
                                                Excelente
                                            </option>
                                            <option :value="3">Bueno</option>
                                            <option :value="2">Regular</option>
                                            <option :value="1">Malo</option>
                                        </select>
                                    </div>

                                    <!-- Límite de Crédito -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Límite de Crédito (nos
                                            otorgan)</label
                                        >
                                        <div class="input-group">
                                            <span class="input-group-text"
                                                >$</span
                                            >
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model="
                                                    formulario.limite_credito
                                                "
                                                placeholder="0.00"
                                                min="0"
                                            />
                                        </div>
                                    </div>

                                    <!-- Días de Crédito -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Días de Crédito</label
                                        >
                                        <input
                                            type="number"
                                            class="form-control"
                                            v-model="formulario.dias_credito"
                                            placeholder="0"
                                            min="0"
                                            max="365"
                                        />
                                    </div>

                                    <!-- Descuento General -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label"
                                            >Descuento General (%)</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            v-model="
                                                formulario.descuento_general
                                            "
                                            placeholder="0.00"
                                            min="0"
                                            max="100"
                                        />
                                    </div>

                                    <!-- Banco -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Banco</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.banco"
                                            placeholder="Nombre del banco"
                                            maxlength="100"
                                        />
                                    </div>

                                    <!-- Cuenta Bancaria -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            >Cuenta Bancaria</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.cuenta_bancaria"
                                            placeholder="Número de cuenta"
                                            maxlength="50"
                                        />
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            >Observaciones</label
                                        >
                                        <textarea
                                            class="form-control"
                                            v-model="formulario.observaciones"
                                            placeholder="Notas adicionales sobre el proveedor"
                                            rows="2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Persona de Contacto -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-tie me-2"></i>Persona
                                    de Contacto
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Nombre Contacto -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"
                                            >Nombre Completo</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.nombre_contacto"
                                            placeholder="Nombre del contacto"
                                            maxlength="150"
                                        />
                                    </div>

                                    <!-- Cargo Contacto -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Cargo</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formulario.cargo_contacto"
                                            placeholder="Ej: Gerente de Ventas"
                                            maxlength="100"
                                        />
                                    </div>

                                    <!-- Teléfono Contacto -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label"
                                            >Teléfono</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="
                                                formulario.telefono_contacto
                                            "
                                            placeholder="000-000-0000"
                                            maxlength="20"
                                        />
                                    </div>

                                    <!-- Email Contacto -->
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Email</label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            v-model="formulario.email_contacto"
                                            placeholder="email@ejemplo.com"
                                            maxlength="150"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="cancelarFormulario"
                            >
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="guardando"
                            >
                                <i class="fas fa-save me-2"></i>
                                {{
                                    guardando
                                        ? "Guardando..."
                                        : "Guardar Proveedor"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

const $ = window.$;

// Estado
const mostrarFormulario = ref(false);
const modoEdicion = ref(false);
const guardando = ref(false);
const dataTable = ref(null);

// Formulario
const formulario = ref({
    // Persona
    tipo_documento: "RUC",
    numero_documento: "",
    nombres: "",
    apellidos: "",
    razon_social: "",
    email: "",
    telefono: "",
    celular: "",
    direccion: "",
    ciudad: "",
    provincia: "",

    // Proveedor
    codigo: "",
    tipo_proveedor: "Producto",
    rubro: "",
    limite_credito: 0,
    dias_credito: 0,
    descuento_general: 0,
    cuenta_bancaria: "",
    banco: "",
    nombre_contacto: "",
    cargo_contacto: "",
    telefono_contacto: "",
    email_contacto: "",
    calificacion: 3,
    observaciones: "",
});

const errores = ref({});

// Computed
const esPersonaNatural = computed(() => {
    return ["DNI", "CE", "PASAPORTE"].includes(formulario.value.tipo_documento);
});

const placeholderDocumento = computed(() => {
    const placeholders = {
        DNI: "Ej: 12345678",
        RUC: "Ej: 12345678901",
        CE: "Ej: 001234567",
        PASAPORTE: "Ej: ABC123456",
    };
    return placeholders[formulario.value.tipo_documento] || "";
});

const maxLengthDocumento = computed(() => {
    const lengths = {
        DNI: 8,
        RUC: 11,
        CE: 12,
        PASAPORTE: 12,
    };
    return lengths[formulario.value.tipo_documento] || 20;
});

// Métodos
const inicializarDataTable = () => {
    setTimeout(() => {
        if (!$ || !$.fn || !$.fn.DataTable) {
            console.error("DataTables no está disponible");
            setTimeout(inicializarDataTable, 500);
            return;
        }

        if ($.fn.DataTable.isDataTable("#tablaProveedores")) {
            $("#tablaProveedores").DataTable().destroy();
        }

        dataTable.value = $("#tablaProveedores").DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: "/api/v1/proveedores",
                type: "GET",
                dataSrc: function (json) {
                    if (json.success) {
                        return json.data.data || json.data;
                    }
                    return [];
                },
                error: function (xhr, error) {
                    console.error("Error al cargar datos:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudieron cargar los proveedores",
                    });
                },
            },
            columns: [
                {
                    data: "codigo",
                    width: "80px",
                    render: function (data) {
                        return `<code>${data}</code>`;
                    },
                },
                {
                    data: "persona",
                    render: function (data) {
                        const nombre =
                            data.razon_social ||
                            `${data.nombres} ${data.apellidos || ""}`;
                        return `<div>
                            <strong>${nombre}</strong>
                            ${
                                data.email
                                    ? `<br><small class="text-muted"><i class="fas fa-envelope me-1"></i>${data.email}</small>`
                                    : ""
                            }
                        </div>`;
                    },
                },
                {
                    data: "persona",
                    width: "130px",
                    render: function (data) {
                        return `<div>
                            <span class="badge bg-secondary">${data.tipo_documento}</span>
                            <br><small>${data.numero_documento}</small>
                        </div>`;
                    },
                },
                {
                    data: null,
                    width: "150px",
                    render: function (data, type, row) {
                        let html = "";
                        if (row.persona.celular) {
                            html += `<small><i class="fas fa-mobile-alt me-1"></i>${row.persona.celular}</small>`;
                        }
                        if (row.nombre_contacto) {
                            html += `<br><small class="text-muted"><i class="fas fa-user me-1"></i>${row.nombre_contacto}</small>`;
                        }
                        return html || '<span class="text-muted">-</span>';
                    },
                },
                {
                    data: null,
                    width: "150px",
                    render: function (data, type, row) {
                        const tipoBadges = {
                            Producto: "bg-primary",
                            Servicio: "bg-info",
                            Ambos: "bg-success",
                        };
                        let html = `<span class="badge ${
                            tipoBadges[row.tipo_proveedor]
                        }">${row.tipo_proveedor}</span>`;
                        if (row.rubro) {
                            html += `<br><small class="text-muted">${row.rubro}</small>`;
                        }
                        return html;
                    },
                },
                {
                    data: "calificacion",
                    width: "100px",
                    className: "text-center",
                    render: function (data) {
                        // Mapping from integer to text and styles
                        const calificaciones = {
                            5: {
                                texto: "Sobresaliente",
                                badge: "bg-success",
                                icon: "fa-star",
                            },
                            4: {
                                texto: "Excelente",
                                badge: "bg-success",
                                icon: "fa-star",
                            },
                            3: {
                                texto: "Bueno",
                                badge: "bg-info",
                                icon: "fa-thumbs-up",
                            },
                            2: {
                                texto: "Regular",
                                badge: "bg-warning text-dark",
                                icon: "fa-minus-circle",
                            },
                            1: {
                                texto: "Malo",
                                badge: "bg-danger",
                                icon: "fa-times-circle",
                            },
                        };
                        const cal = calificaciones[data] || {
                            texto: "Sin calificar",
                            badge: "bg-secondary",
                            icon: "fa-question",
                        };
                        return `<span class="badge ${cal.badge}">
                            <i class="fas ${cal.icon} me-1"></i>${cal.texto}
                        </span>`;
                    },
                },
                {
                    data: "estado",
                    width: "80px",
                    className: "text-center",
                    render: function (data) {
                        if (data) {
                            return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Activo</span>';
                        } else {
                            return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inactivo</span>';
                        }
                    },
                },
                {
                    data: null,
                    width: "120px",
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
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
                    },
                },
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "Todos"],
            ],
            order: [[0, "desc"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            drawCallback: function () {
                $(".btn-editar")
                    .off("click")
                    .on("click", function () {
                        const id = $(this).data("id");
                        editarProveedor(id);
                    });

                $(".btn-activar, .btn-desactivar")
                    .off("click")
                    .on("click", function () {
                        const id = $(this).data("id");
                        const esActivar = $(this).hasClass("btn-activar");
                        toggleEstado(id, esActivar);
                    });
            },
        });
    }, 300);
};

const onTipoDocumentoChange = () => {
    // Limpiar campos según tipo de documento
    if (esPersonaNatural.value) {
        formulario.value.razon_social = "";
    } else {
        formulario.value.apellidos = "";
    }
};

const nuevoRegistro = async () => {
    formulario.value = {
        tipo_documento: "RUC",
        numero_documento: "",
        nombres: "",
        apellidos: "",
        razon_social: "",
        email: "",
        telefono: "",
        celular: "",
        direccion: "",
        ciudad: "",
        provincia: "",
        codigo: "",
        tipo_proveedor: "Producto",
        rubro: "",
        limite_credito: 0,
        dias_credito: 0,
        descuento_general: 0,
        cuenta_bancaria: "",
        banco: "",
        nombre_contacto: "",
        cargo_contacto: "",
        telefono_contacto: "",
        email_contacto: "",
        calificacion: 3,
        observaciones: "",
    };
    errores.value = {};
    modoEdicion.value = false;
    mostrarFormulario.value = true;

    // Generar código
    try {
        const response = await axios.get("/api/v1/proveedores/generar-codigo");
        if (response.data.success) {
            formulario.value.codigo = response.data.data.codigo;
        }
    } catch (error) {
        console.error("Error al generar código:", error);
    }
};

const editarProveedor = async (id) => {
    try {
        const response = await axios.get(`/api/v1/proveedores/${id}`);

        if (response.data.success) {
            const proveedor = response.data.data;
            const persona = proveedor.persona;

            formulario.value = {
                id: proveedor.id,
                // Persona
                tipo_documento: persona.tipo_documento,
                numero_documento: persona.numero_documento,
                nombres: persona.nombres,
                apellidos: persona.apellidos || "",
                razon_social: persona.razon_social || "",
                email: persona.email || "",
                telefono: persona.telefono || "",
                celular: persona.celular || "",
                direccion: persona.direccion || "",
                ciudad: persona.ciudad || "",
                provincia: persona.provincia || "",

                // Proveedor
                codigo: proveedor.codigo,
                tipo_proveedor: proveedor.tipo_proveedor,
                rubro: proveedor.rubro || "",
                limite_credito: proveedor.limite_credito,
                dias_credito: proveedor.dias_credito,
                descuento_general: proveedor.descuento_general,
                cuenta_bancaria: proveedor.cuenta_bancaria || "",
                banco: proveedor.banco || "",
                nombre_contacto: proveedor.nombre_contacto || "",
                cargo_contacto: proveedor.cargo_contacto || "",
                telefono_contacto: proveedor.telefono_contacto || "",
                email_contacto: proveedor.email_contacto || "",
                calificacion: proveedor.calificacion,
                observaciones: proveedor.observaciones || "",
            };

            errores.value = {};
            modoEdicion.value = true;
            mostrarFormulario.value = true;
        }
    } catch (error) {
        console.error("Error al editar:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text:
                error.response?.data?.message ||
                "No se pudo cargar el proveedor",
        });
    }
};

const guardarProveedor = async () => {
    try {
        guardando.value = true;
        errores.value = {};

        const url = modoEdicion.value
            ? `/api/v1/proveedores/${formulario.value.id}`
            : "/api/v1/proveedores";

        const method = modoEdicion.value ? "put" : "post";

        const response = await axios[method](url, formulario.value);

        if (response.data.success) {
            Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: response.data.message,
                timer: 2000,
                showConfirmButton: false,
            });

            cancelarFormulario();
            dataTable.value.ajax.reload();
        }
    } catch (error) {
        console.error("Error al guardar:", error);
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors || {};
            Swal.fire({
                icon: "error",
                title: "Error de validación",
                text: error.response.data.message,
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text:
                    error.response?.data?.message ||
                    "Ocurrió un error al guardar",
            });
        }
    } finally {
        guardando.value = false;
    }
};

const toggleEstado = async (id, esActivar) => {
    const result = await Swal.fire({
        title: `¿${esActivar ? "Activar" : "Desactivar"} proveedor?`,
        text: `¿Está seguro de ${
            esActivar ? "activar" : "desactivar"
        } este proveedor?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: esActivar ? "#28a745" : "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: `Sí, ${esActivar ? "activar" : "desactivar"}`,
        cancelButtonText: "Cancelar",
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.patch(
                `/api/v1/proveedores/${id}/toggle-estado`
            );

            if (response.data.success) {
                Swal.fire({
                    icon: "success",
                    title: "¡Éxito!",
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false,
                });

                dataTable.value.ajax.reload(null, false);
            }
        } catch (error) {
            console.error("Error al cambiar estado:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text:
                    error.response?.data?.message ||
                    "Ocurrió un error al cambiar el estado",
            });
        }
    }
};

const cancelarFormulario = () => {
    mostrarFormulario.value = false;
    modoEdicion.value = false;
    formulario.value = {
        tipo_documento: "RUC",
        numero_documento: "",
        nombres: "",
        apellidos: "",
        razon_social: "",
        email: "",
        telefono: "",
        celular: "",
        direccion: "",
        ciudad: "",
        provincia: "",
        codigo: "",
        tipo_proveedor: "Producto",
        rubro: "",
        limite_credito: 0,
        dias_credito: 0,
        descuento_general: 0,
        cuenta_bancaria: "",
        banco: "",
        nombre_contacto: "",
        cargo_contacto: "",
        telefono_contacto: "",
        email_contacto: "",
        calificacion: 3,
        observaciones: "",
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
.proveedor-module {
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
